<?php
/**
 * Repository NhanVienRep - truy cập bảng `nhan_vien` (CSDL eightmart) bằng MySQLi.
 * Tương thích PHP 5.2 / MySQL 5.x.
 */
require_once dirname(__FILE__) . '/../Entity/NhanVien.php';
require_once dirname(__FILE__) . '/../../db.php';

class NhanVienRep
{
    private $db;

    public function __construct()
    {
        $this->db = eightmart_mysqli_connection();
    }

    // ---- giao dịch ----
    private $trongGiaoDich = false;       // Theo dõi trạng thái giao dịch để tương thích các phiên bản MySQLi cũ.

    public function batDauGiaoDich() { eightmart_mysqli_begin_transaction(); $this->trongGiaoDich = true; }
    public function xacNhan()        { eightmart_mysqli_commit(); $this->trongGiaoDich = false; }
    public function hoanTac()
    {
        if ($this->trongGiaoDich) { eightmart_mysqli_rollback(); $this->trongGiaoDich = false; }
    }

    // ---- UC06: xem danh sách ----
    /** @return NhanVien[] */
    public function layTatCa()
    {
        $kq = array();
        $st = eightmart_mysqli_execute('SELECT * FROM nhan_vien ORDER BY ma_nhan_vien');
        foreach (eightmart_mysqli_fetch_all_assoc($st) as $row) {
            $kq[] = NhanVien::tuDong($row);
        }
        return $kq;
    }

    /** @return NhanVien|null */
    public function layTheoMa($ma)
    {
        $st = eightmart_mysqli_execute('SELECT * FROM nhan_vien WHERE ma_nhan_vien = ?', array((int) $ma));
        $row = eightmart_mysqli_fetch_one_assoc($st);
        return $row ? NhanVien::tuDong($row) : null;
    }

    /** CCCD đã tồn tại? (bỏ qua nhân viên có mã $boQuaMa khi sửa) */
    public function tonTaiCccd($cccd, $boQuaMa = 0)
    {
        $st = eightmart_mysqli_execute('SELECT COUNT(*) FROM nhan_vien WHERE cccd = ? AND ma_nhan_vien <> ?', array($cccd, (int) $boQuaMa));
        return (int) eightmart_mysqli_fetch_value($st) > 0;
    }

    // ---- UC03: thêm ----
    /**
     * BỔ SUNG: tài khoản có username = CCCD, password = MD5(ddmmyyyy).
     * Vẫn ghi trường đăng nhập cũ trong nhan_vien để KHÔNG ảnh hưởng màn đăng nhập hiện tại.
     * Controller bọc cả thêm NV lẫn thêm tài khoản trong MỘT transaction.
     * @return int mã nhân viên mới
     */
    public function them(NhanVien $nv, $matKhauMacDinh)
    {
        $st = eightmart_mysqli_execute(
            'INSERT INTO nhan_vien (ten, cccd, gioi_tinh, sdt, ngay_sinh, ten_dang_nhap, mat_khau, vai_tro, trang_thai)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array(
            $nv->getTen(), $nv->getCccd(), $nv->getGioiTinh(), $nv->getSdt(), $nv->getNgaySinh(),
            $nv->getCccd(), md5($matKhauMacDinh), NhanVien::VAI_TRO_NV, NhanVien::TT_HOAT_DONG
        ));
        eightmart_mysqli_affected_rows($st);
        return (int) $this->db->insert_id;
    }

    /**
     * BỔ SUNG: tạo bản ghi riêng trong bảng `Tai Khoan` (tên bảng có dấu cách).
     * Việc cập nhật tài khoản và nhân viên là nguyên tử (transaction ở Controller).
     */
    public function taoTaiKhoan($ma, $tenDangNhap, $matKhauMacDinh)
    {
        $st = eightmart_mysqli_execute(
            'INSERT INTO `Tai Khoan` (ma_nhan_vien, ten_dang_nhap, mat_khau, vai_tro, trang_thai)
             VALUES (?, ?, ?, ?, ?)',
            array((int) $ma, $tenDangNhap, md5($matKhauMacDinh),
                NhanVien::VAI_TRO_NV, NhanVien::TT_HOAT_DONG));
        if (eightmart_mysqli_affected_rows($st) !== 1) { throw new Exception('Không tạo được tài khoản.'); }
        return $tenDangNhap;
    }

    /** Chống trùng username giữa tài khoản cũ và mới. */
    public function tonTaiTenDangNhap($tenDangNhap, $boQuaMa = 0)
    {
        $st = eightmart_mysqli_execute('SELECT COUNT(*) FROM nhan_vien WHERE ten_dang_nhap = ? AND ma_nhan_vien <> ?', array($tenDangNhap, (int) $boQuaMa));
        if ((int) eightmart_mysqli_fetch_value($st) > 0) { return true; }
        $st = eightmart_mysqli_execute('SELECT COUNT(*) FROM `Tai Khoan` WHERE ten_dang_nhap = ? AND ma_nhan_vien <> ?', array($tenDangNhap, (int) $boQuaMa));
        return (int) eightmart_mysqli_fetch_value($st) > 0;
    }

    // ---- UC04: sửa ----
    public function capNhat(NhanVien $nv)
    {
        // BỔ SUNG: chỉ đổi username khi đó là tài khoản tạo từ CCCD.
        // Tài khoản cũ như admin/nhanvien giữ nguyên tên đăng nhập.
        $st = eightmart_mysqli_execute('SELECT cccd, ten_dang_nhap FROM nhan_vien WHERE ma_nhan_vien = ?', array($nv->getMaNhanVien()));
        $cu = eightmart_mysqli_fetch_one_assoc($st);
        if (!$cu) { throw new Exception('Không tìm thấy nhân viên.'); }
        $tenDangNhap = $cu['ten_dang_nhap'];
        $doiUsername = ($cu['cccd'] === $tenDangNhap && $cu['cccd'] !== $nv->getCccd());
        if ($doiUsername) { $tenDangNhap = $nv->getCccd(); }
        $st = eightmart_mysqli_execute(
            'UPDATE nhan_vien SET ten = ?, cccd = ?, gioi_tinh = ?, sdt = ?, ngay_sinh = ?, ten_dang_nhap = ?
             WHERE ma_nhan_vien = ?',
            array($nv->getTen(), $nv->getCccd(), $nv->getGioiTinh(), $nv->getSdt(),
                $nv->getNgaySinh(), $tenDangNhap, $nv->getMaNhanVien()));
        eightmart_mysqli_affected_rows($st);

        if ($doiUsername) {
            // Chỉ đồng bộ username nếu tài khoản hiện hành gắn với CCCD cũ.
            $st = eightmart_mysqli_execute(
                'UPDATE `Tai Khoan` SET ten_dang_nhap = ? WHERE ma_nhan_vien = ? AND ten_dang_nhap = ?',
                array($tenDangNhap, $nv->getMaNhanVien(), $cu['cccd']));
            eightmart_mysqli_affected_rows($st);
        }
        // Sửa ngày sinh KHÔNG tự reset mật khẩu đã cấp trước đó.
    }

    // ---- UC05: xóa ----
    /** Nhân viên có đang được các bảng khác tham chiếu không? (bước 10) */
    public function dangDuocThamChieu($ma)
    {
        $truyVan = array(
            'SELECT COUNT(*) FROM ca_lam_viec WHERE ma_nhan_vien = ?',
            'SELECT COUNT(*) FROM don_hang WHERE ma_nhan_vien = ?',
            'SELECT COUNT(*) FROM thanh_toan WHERE ma_nhan_vien_xac_nhan = ?',
            'SELECT COUNT(*) FROM phieu_de_nghi_dat_hang WHERE ma_nhan_vien = ? OR ma_nguoi_duyet = ?',
            'SELECT COUNT(*) FROM phieu_nhap_kho WHERE ma_nhan_vien = ?',
            'SELECT COUNT(*) FROM phieu_yeu_cau_tra_hang WHERE ma_nhan_vien_tao = ? OR ma_nguoi_xu_ly = ?',
        );
        foreach ($truyVan as $sql) {
            $tham = array((int) $ma);
            if (substr_count($sql, '?') === 2) { $tham[] = (int) $ma; }
            $st = eightmart_mysqli_execute($sql, $tham);
            if ((int) eightmart_mysqli_fetch_value($st) > 0) { return true; }
        }
        return false;
    }

    public function xoa($ma)
    {
        $st = eightmart_mysqli_execute('DELETE FROM nhan_vien WHERE ma_nhan_vien = ?', array((int) $ma));
        return eightmart_mysqli_affected_rows($st) === 1;
    }
}
