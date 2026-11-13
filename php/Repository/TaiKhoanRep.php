<?php
/** Truy cập bảng TaiKhoan trong schema.sql bằng kết nối MySQLi dùng chung. */
require_once dirname(__FILE__) . '/../../db.php';
require_once dirname(__FILE__) . '/../BaoMat.php';

class TaiKhoanRep
{
    private $db;

    public function __construct() { $this->db = eightmart_mysqli_connection(); }

    /** Chuẩn hóa các vai trò cũ trong SQL mẫu thành 2 cấp quyền của ứng dụng. */
    public static function chuanHoaVaiTro($vaiTro)
    {

        if ($vaiTro === 'ChuDoanhNghiep' || $vaiTro === 'QuanLy') {
            return 'ChuDoanhNghiep';
        }
        // ThuNgan / ThuKho / NhanVien đều là nhân viên trong module nhân sự.
        return 'NhanVien';
    }

    /** Dùng khi kiểm tra phiên: quyền và trạng thái được đọc từ DB mỗi request. */
    public function layNguoiDungTheoTaiKhoan($taiKhoanID)
    {

        $st = eightmart_mysqli_execute('SELECT tk.ID AS ma_tai_khoan, '
            . 'tk.NhanVienID AS ma_nhan_vien, tk.TenDangNhap AS ten_dang_nhap, '
            . 'tk.VaiTro AS vai_tro, tk.TrangThai AS trang_thai, '
            . 'nv.Ten AS ho_ten, nv.Cccd AS cccd, nv.TrangThai AS trang_thai_nhan_vien '
            . 'FROM TaiKhoan tk INNER JOIN NhanVien nv ON nv.ID=tk.NhanVienID WHERE tk.ID=?',
            array((int)$taiKhoanID));
        $r = eightmart_mysqli_fetch_one_assoc($st);
        if (!$r || $r['trang_thai'] !== 'HoatDong' || $r['trang_thai_nhan_vien'] !== 'HoatDong') {

            return null;
        }
        return array('ma_tai_khoan' => (int)$r['ma_tai_khoan'],
            'ma_nhan_vien' => (int)$r['ma_nhan_vien'],
            'ten_dang_nhap' => $r['cccd'], 'ho_ten' => $r['ho_ten'],
            'vai_tro' => self::chuanHoaVaiTro($r['vai_tro']),
            'vai_tro_goc' => $r['vai_tro']);
    }

    public function xacThuc($tenDangNhap, $matKhau)
    {
        // Dữ liệu mẫu SQL có username an.nguyen nhưng CCCD vẫn đăng nhập được.
        // Tài khoản do chức năng Thêm tạo sẽ có TenDangNhap = CCCD.
        $theoCccd = preg_match('/^[0-9]{12}$/D', $tenDangNhap) === 1;
        $where = $theoCccd ? 'nv.Cccd=?' : 'tk.TenDangNhap=?';
        $st = eightmart_mysqli_execute('SELECT tk.ID AS ma_tai_khoan, '
            . 'tk.NhanVienID AS ma_nhan_vien, tk.TenDangNhap AS ten_dang_nhap, '
            . 'tk.MatKhau AS mat_khau, tk.VaiTro AS vai_tro, '
            . 'tk.TrangThai AS trang_thai, nv.Ten AS ho_ten, nv.Cccd AS cccd, '
            . 'nv.TrangThai AS trang_thai_nhan_vien, nv.NgaySinh AS ngay_sinh '
            . 'FROM TaiKhoan tk INNER JOIN NhanVien nv ON nv.ID=tk.NhanVienID '
            . 'WHERE ' . $where . ' LIMIT 1', array($tenDangNhap));
        $r = eightmart_mysqli_fetch_one_assoc($st);
        if (!$r || $r['trang_thai'] !== 'HoatDong' || $r['trang_thai_nhan_vien'] !== 'HoatDong') {
            return null;
        }
        $daLuu = $r['mat_khau'];
        $hopLe = BaoMat::kiemTraMatKhau($matKhau, $daLuu);

        // schema.sql gốc dùng 'hash_matkhau_01'... làm dữ liệu mẫu giả,
        // cho phép dùng ngày sinh 8 số đúng 1 lần; sau đó băm và lưu thực tế.
        $taiKhoanMau = preg_match('/^hash_matkhau_[0-9]+$/D', $daLuu) === 1;
        if (!$hopLe && $taiKhoanMau) {
            $ngay = substr($r['ngay_sinh'], 0, 10);
            if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $ngay, $m)) {
                $matKhauBanDau = $m[3] . $m[2] . $m[1];
                $hopLe = BaoMat::bangNhau($matKhau, $matKhauBanDau);
            }
            if ($hopLe) {
                $stCapNhat = eightmart_mysqli_execute('UPDATE TaiKhoan SET MatKhau=? WHERE ID=? AND MatKhau=?',
                    array(BaoMat::bamMatKhau($matKhau), (int)$r['ma_tai_khoan'], $daLuu));
                // Tránh trường hợp mật khẩu bị đổi đồng thời bởi tiến trình khác.
                if (eightmart_mysqli_affected_rows($stCapNhat) !== 1) { return null; }
            }
        }
        if (!$hopLe) { return null; }
        return array('ma_tai_khoan' => (int)$r['ma_tai_khoan'],
            'ma_nhan_vien' => (int)$r['ma_nhan_vien'],
            'ten_dang_nhap' => $r['cccd'], 'ho_ten' => $r['ho_ten'],
            'vai_tro' => self::chuanHoaVaiTro($r['vai_tro']),
            'vai_tro_goc' => $r['vai_tro']);
    }
}
