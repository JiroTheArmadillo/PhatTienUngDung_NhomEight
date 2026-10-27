<?php
/**
 * Repository NhanVienRep - truy cập bảng `nhan_vien` (CSDL eightmart) bằng PDO.
 * Tương thích PHP 5.2 / MySQL 5.x.
 */
require_once dirname(__FILE__) . '/../Entity/NhanVien.php';
require_once dirname(__FILE__) . '/KetNoiDB.php';

class NhanVienRep
{
    private $db;

    public function __construct()
    {
        $this->db = KetNoiDB::layKetNoi();
    }

    // ---- giao dịch ----
    private $trongGiaoDich = false;   // PDO::inTransaction() chỉ có từ PHP 5.3.3

    public function batDauGiaoDich() { $this->db->beginTransaction(); $this->trongGiaoDich = true; }
    public function xacNhan()        { $this->db->commit();   $this->trongGiaoDich = false; }
    public function hoanTac()
    {
        if ($this->trongGiaoDich) { $this->db->rollBack(); $this->trongGiaoDich = false; }
    }

    // ---- UC06: xem danh sách ----
    /** @return NhanVien[] */
    public function layTatCa()
    {
        $kq = array();
        $st = $this->db->query('SELECT * FROM nhan_vien ORDER BY ma_nhan_vien');
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $kq[] = NhanVien::tuDong($row);
        }
        return $kq;
    }

    /** @return NhanVien|null */
    public function layTheoMa($ma)
    {
        $st = $this->db->prepare('SELECT * FROM nhan_vien WHERE ma_nhan_vien = ?');
        $st->execute(array((int) $ma));
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? NhanVien::tuDong($row) : null;
    }

    /** CCCD đã tồn tại? (bỏ qua nhân viên có mã $boQuaMa khi sửa) */
    public function tonTaiCccd($cccd, $boQuaMa = 0)
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM nhan_vien WHERE cccd = ? AND ma_nhan_vien <> ?');
        $st->execute(array($cccd, (int) $boQuaMa));
        return (int) $st->fetchColumn() > 0;
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
        $st = $this->db->prepare(
            'INSERT INTO nhan_vien (ten, cccd, gioi_tinh, sdt, ngay_sinh, ten_dang_nhap, mat_khau, vai_tro, trang_thai)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $st->execute(array(
            $nv->getTen(), $nv->getCccd(), $nv->getGioiTinh(), $nv->getSdt(), $nv->getNgaySinh(),
            $nv->getCccd(), md5($matKhauMacDinh), NhanVien::VAI_TRO_NV, NhanVien::TT_HOAT_DONG
        ));
        return (int) $this->db->lastInsertId();
    }

    /**
     * BỔ SUNG: tạo bản ghi riêng trong bảng `Tai Khoan` (tên bảng có dấu cách).
     * Việc cập nhật tài khoản và nhân viên là nguyên tử (transaction ở Controller).
     */
    public function taoTaiKhoan($ma, $tenDangNhap, $matKhauMacDinh)
    {
        $st = $this->db->prepare(
            'INSERT INTO `Tai Khoan` (ma_nhan_vien, ten_dang_nhap, mat_khau, vai_tro, trang_thai)
             VALUES (?, ?, ?, ?, ?)');
        $st->execute(array((int) $ma, $tenDangNhap, md5($matKhauMacDinh),
            NhanVien::VAI_TRO_NV, NhanVien::TT_HOAT_DONG));
        if ($st->rowCount() !== 1) { throw new Exception('Không tạo được tài khoản.'); }
        return $tenDangNhap;
    }

    /** Chống trùng username giữa tài khoản cũ và mới. */
    public function tonTaiTenDangNhap($tenDangNhap, $boQuaMa = 0)
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM nhan_vien WHERE ten_dang_nhap = ? AND ma_nhan_vien <> ?');
        $st->execute(array($tenDangNhap, (int) $boQuaMa));
        if ((int) $st->fetchColumn() > 0) { return true; }
        $st = $this->db->prepare('SELECT COUNT(*) FROM `Tai Khoan` WHERE ten_dang_nhap = ? AND ma_nhan_vien <> ?');
        $st->execute(array($tenDangNhap, (int) $boQuaMa));
        return (int) $st->fetchColumn() > 0;
    }

    // ---- UC04: sửa ----
    public function capNhat(NhanVien $nv)
    {
        // BỔ SUNG: chỉ đổi username khi đó là tài khoản tạo từ CCCD.
        // Tài khoản cũ như admin/nhanvien giữ nguyên tên đăng nhập.
        $st = $this->db->prepare('SELECT cccd, ten_dang_nhap FROM nhan_vien WHERE ma_nhan_vien = ?');
        $st->execute(array($nv->getMaNhanVien()));
        $cu = $st->fetch(PDO::FETCH_ASSOC);
        if (!$cu) { throw new Exception('Không tìm thấy nhân viên.'); }
        $tenDangNhap = $cu['ten_dang_nhap'];
        $doiUsername = ($cu['cccd'] === $tenDangNhap && $cu['cccd'] !== $nv->getCccd());
        if ($doiUsername) { $tenDangNhap = $nv->getCccd(); }
        $st = $this->db->prepare(
            'UPDATE nhan_vien SET ten = ?, cccd = ?, gioi_tinh = ?, sdt = ?, ngay_sinh = ?, ten_dang_nhap = ?
             WHERE ma_nhan_vien = ?');
        $st->execute(array($nv->getTen(), $nv->getCccd(), $nv->getGioiTinh(), $nv->getSdt(),
            $nv->getNgaySinh(), $tenDangNhap, $nv->getMaNhanVien()));

        if ($doiUsername) {
            // Chỉ đồng bộ username nếu tài khoản hiện hành gắn với CCCD cũ.
            $st = $this->db->prepare(
                'UPDATE `Tai Khoan` SET ten_dang_nhap = ? WHERE ma_nhan_vien = ? AND ten_dang_nhap = ?');
            $st->execute(array($tenDangNhap, $nv->getMaNhanVien(), $cu['cccd']));
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
            $st = $this->db->prepare($sql);
            $tham = array((int) $ma);
            if (substr_count($sql, '?') === 2) { $tham[] = (int) $ma; }
            $st->execute($tham);
            if ((int) $st->fetchColumn() > 0) { return true; }
        }
        return false;
    }

    public function xoa($ma)
    {
        $st = $this->db->prepare('DELETE FROM nhan_vien WHERE ma_nhan_vien = ?');
        $st->execute(array((int) $ma));
        return $st->rowCount() === 1;
    }
}
