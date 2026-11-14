<?php
require_once dirname(__FILE__) . '/../Repository/DonHangRepository.php';
require_once dirname(__FILE__) . '/../BaoMat.php';

/**
 * Control: nghiệp vụ về đơn hàng (Quản lý bán hàng):
 *  - Xem danh sách đơn hàng, xem chi tiết đơn hàng (UC28) - actor Chủ doanh nghiệp.
 *  - Thêm sản phẩm vào đơn hàng (UC31), xóa sản phẩm khỏi đơn hàng (UC32) - actor Nhân viên,
 *    chỉ với đơn đang ở trạng thái Chưa thanh toán (ChoThanhToan, chờ xác nhận chuyển khoản);
 *    tổng tiền mới được đồng bộ sang giao dịch ThanhToan đang chờ.
 *
 * Các hàm trả về array('ok' => bool, 'message' => string, 'du_lieu' => mixed).
 */
class DonHangController
{
    const HOAN_THANH    = 'HoanThanh';
    const DA_THANH_TOAN = 'DaThanhToan'; // trạng thái do luồng thanh toán ở trang bán hàng ghi vào HoaDon
    const DA_HUY        = 'DaHuy';

    const KHOA_CSRF = 'eightmart_csrf_donhang';

    private $repo;

    public function __construct()
    {
        $this->repo = new DonHangRepository();
    }

    // ---------------- Phân quyền & CSRF ----------------

    /** Xem danh sách / chi tiết: Chủ doanh nghiệp (vai trò QuanLy / ChuDoanhNghiep). */
    public static function choPhepXem($nguoiDung)
    {
        if (!is_array($nguoiDung) || !isset($nguoiDung['vai_tro'])) {
            return false;
        }
        $vaiTro = strtolower(trim((string)$nguoiDung['vai_tro']));
        return ($vaiTro === 'quanly' || $vaiTro === 'chudoanhnghiep');
    }

    /** Thêm / xóa sản phẩm: Nhân viên đã đăng nhập (Pre-condition UC31, UC32). */
    public static function choPhepSua($nguoiDung)
    {
        return is_array($nguoiDung) && count($nguoiDung) > 0;
    }

    /** HoaDon.TrangThai "Chưa thanh toán": xulythanhtoan.php ghi ChoThanhToan khi tạo đơn chuyển khoản chờ xác nhận. */
    public static function cacTrangThaiChuaThanhToan()
    {
        return array('ChoThanhToan');
    }

    public static function laChuaThanhToan($trangThai)
    {
        return in_array(trim((string)$trangThai), self::cacTrangThaiChuaThanhToan(), true);
    }

    public static function layTokenCsrf()
    {
        if (session_id() === '') {
            session_start();
        }
        if (!isset($_SESSION[self::KHOA_CSRF])) {
            $_SESSION[self::KHOA_CSRF] = BaoMat::taoNgauNhien(16);
        }
        return $_SESSION[self::KHOA_CSRF];
    }

    public static function kiemTraTokenCsrf($token)
    {
        if (session_id() === '') {
            session_start();
        }
        return is_string($token) && $token !== ''
            && isset($_SESSION[self::KHOA_CSRF])
            && BaoMat::bangNhau($token, $_SESSION[self::KHOA_CSRF]);
    }

    // ---------------- Xem danh sách / chi tiết ----------------

    /** UC "Xem danh sách đơn hàng". Danh sách rỗng vẫn là ok = true (luồng 4.1). */
    public function layDanhSach()
    {
        try {
            return $this->ketQua(true, '', $this->repo->layDanhSach());
        } catch (Exception $e) {
            return $this->loiHeThong($e, 'Không thể tải danh sách đơn hàng.');
        }
    }

    /** UC28 "Xem chi tiết đơn hàng": du_lieu = array('hoa_don' => ..., 'chi_tiet' => array(...)). */
    public function layChiTiet($maHoaDon)
    {
        $maHoaDon = (int)$maHoaDon;
        if ($maHoaDon <= 0) {
            return $this->ketQua(false, 'Mã đơn hàng không hợp lệ.');
        }

        try {
            $hoaDon = $this->repo->layThongTin($maHoaDon);
            if ($hoaDon === null) {
                return $this->ketQua(false, 'Không tìm thấy đơn hàng.');
            }
            $chiTiet = $this->repo->layChiTiet($maHoaDon);
            return $this->ketQua(true, '', array('hoa_don' => $hoaDon, 'chi_tiet' => $chiTiet));
        } catch (Exception $e) {
            return $this->loiHeThong($e, 'Không thể tải dữ liệu chi tiết đơn hàng.');
        }
    }

    /** Sản phẩm có thể chọn khi thêm vào đơn hàng. */
    public function layDanhSachSanPham()
    {
        try {
            return $this->ketQua(true, '', $this->repo->layDanhSachSanPham());
        } catch (Exception $e) {
            return $this->loiHeThong($e, 'Không thể tải danh sách sản phẩm.');
        }
    }

    // ---------------- UC31: Thêm sản phẩm vào đơn hàng ----------------

    public function themSanPham($maHoaDon, $maSanPham, $soLuong)
    {
        $maHoaDon = (int)$maHoaDon;
        $maSanPham = (int)$maSanPham;

        if ($maHoaDon <= 0) {
            return $this->ketQua(false, 'Mã đơn hàng không hợp lệ.');
        }
        if ($maSanPham <= 0) {
            return $this->ketQua(false, 'Vui lòng chọn sản phẩm.');
        }
        if (!preg_match('/^[0-9]{1,6}$/', trim((string)$soLuong)) || (int)$soLuong <= 0) {
            return $this->ketQua(false, 'Số lượng phải là số nguyên dương.');
        }
        $soLuong = (int)$soLuong;

        $daBatDau = false;
        try {
            $this->repo->batDauGiaoDich();
            $daBatDau = true;

            $loi = $this->kiemTraDuocSua($maHoaDon);
            if ($loi !== null) {
                $this->hoanTac();
                return $this->ketQua(false, $loi);
            }

            $sanPham = $this->repo->laySanPham($maSanPham);
            if ($sanPham === null || trim((string)$sanPham['trang_thai']) !== 'HoatDong') {
                $this->hoanTac();
                return $this->ketQua(false, 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.');
            }

            $dong = $this->repo->layDong($maHoaDon, $maSanPham);
            $daCo = ($dong === null) ? 0 : (int)$dong['so_luong'];
            $ton = (int)$sanPham['so_luong_ton'];
            if ($daCo + $soLuong > $ton) {
                $this->hoanTac();
                return $this->ketQua(false, 'Số lượng vượt quá tồn kho (tồn ' . $ton . ', đơn đã có ' . $daCo . ').');
            }

            if ($dong === null) {
                $ok = $this->repo->themDongMoi($maHoaDon, $maSanPham, $soLuong);
            } else {
                $ok = $this->repo->tangSoLuongDong($maHoaDon, $maSanPham, $soLuong);
            }
            if (!$ok) {
                throw new Exception('Không ghi được dòng chi tiết hóa đơn ' . $maHoaDon . ' / sản phẩm ' . $maSanPham);
            }

            $this->repo->capNhatTongTien($maHoaDon);
            $this->repo->xacNhanGiaoDich();
            return $this->ketQua(true, 'Đã thêm sản phẩm vào đơn hàng.');
        } catch (Exception $e) {
            if ($daBatDau) {
                $this->hoanTac();
            }
            return $this->loiHeThong($e, 'Có lỗi hệ thống khi thêm sản phẩm, vui lòng thử lại.');
        }
    }

    // ---------------- UC32: Xóa sản phẩm khỏi đơn hàng ----------------

    public function xoaSanPham($maHoaDon, $maSanPham)
    {
        $maHoaDon = (int)$maHoaDon;
        $maSanPham = (int)$maSanPham;

        if ($maHoaDon <= 0 || $maSanPham <= 0) {
            return $this->ketQua(false, 'Dữ liệu không hợp lệ.');
        }

        $daBatDau = false;
        try {
            $this->repo->batDauGiaoDich();
            $daBatDau = true;

            $loi = $this->kiemTraDuocSua($maHoaDon);
            if ($loi !== null) {
                $this->hoanTac();
                return $this->ketQua(false, $loi);
            }

            if (!$this->repo->xoaDong($maHoaDon, $maSanPham)) {
                $this->hoanTac();
                return $this->ketQua(false, 'Sản phẩm không còn trong đơn hàng.');
            }

            $this->repo->capNhatTongTien($maHoaDon);
            $this->repo->xacNhanGiaoDich();
            return $this->ketQua(true, 'Đã xóa sản phẩm khỏi đơn hàng.');
        } catch (Exception $e) {
            if ($daBatDau) {
                $this->hoanTac();
            }
            return $this->loiHeThong($e, 'Có lỗi hệ thống khi xóa sản phẩm, vui lòng thử lại.');
        }
    }

    // ---------------- Hàm định dạng hiển thị (dùng chung cho trang và endpoint) ----------------

    public static function hienMaHoaDon($id)    { return 'HD' . str_pad((int)$id, 3, '0', STR_PAD_LEFT); }
    public static function hienMaSanPham($id)   { return 'SP' . str_pad((int)$id, 3, '0', STR_PAD_LEFT); }
    public static function hienMaNhanVien($id)  { return 'NV' . str_pad((int)$id, 3, '0', STR_PAD_LEFT); }
    public static function hienMaKhachHang($id) { return 'KH' . str_pad((int)$id, 3, '0', STR_PAD_LEFT); }

    public static function dinhDangTien($so)
    {
        return number_format((float)$so, 0, ',', '.') . ' đ';
    }

    public static function dinhDangNgayGio($chuoi)
    {
        $t = strtotime((string)$chuoi);
        return ($t === false || $t === -1) ? (string)$chuoi : date('d/m/Y H:i', $t);
    }

    /** Trả về array('nhan' => chữ hiển thị, 'lop' => success|danger|warning|'' dùng làm màu badge). */
    public static function trangThaiHienThi($trangThai)
    {
        $tt = trim((string)$trangThai);
        // Đơn đã thanh toán xong cũng là đơn hoàn thành (dữ liệu mẫu ghi HoanThanh,
        // còn luồng thanh toán thực tế ghi DaThanhToan) nên hiển thị cùng một nhãn.
        if ($tt === self::HOAN_THANH || $tt === self::DA_THANH_TOAN) {
            return array('nhan' => 'Hoàn thành', 'lop' => 'success');
        }
        if ($tt === self::DA_HUY) {
            return array('nhan' => 'Đã hủy', 'lop' => 'danger');
        }
        if (self::laChuaThanhToan($tt)) {
            return array('nhan' => 'Chưa thanh toán', 'lop' => 'warning');
        }
        return array('nhan' => $tt, 'lop' => '');
    }

    // ---------------- Hàm hỗ trợ ----------------

    /**
     * Khóa dòng hóa đơn rồi kiểm tra: đơn phải tồn tại và đang Chưa thanh toán.
     * Trả về null nếu được phép sửa, ngược lại trả về thông báo lỗi.
     */
    private function kiemTraDuocSua($maHoaDon)
    {
        // Thứ tự khóa: ThanhToan -> HoaDon (giống xac_nhan_chuyen_khoan) để không deadlock.
        $thanhToan = $this->repo->khoaThanhToanChoXacNhan($maHoaDon);
        $hoaDon = $this->repo->khoaHoaDon($maHoaDon);
        if ($hoaDon === null) {
            return 'Không tìm thấy đơn hàng.';
        }
        if (!self::laChuaThanhToan($hoaDon['trang_thai'])) {
            return 'Chỉ được chỉnh sửa đơn hàng đang ở trạng thái Chưa thanh toán.';
        }
        if ($thanhToan === null) {
            return 'Đơn hàng không còn ở trạng thái chờ thanh toán.';
        }
        return null;
    }

    private function hoanTac()
    {
        try {
            $this->repo->hoanTacGiaoDich();
        } catch (Exception $e) {
            error_log('[DonHang] Không hoàn tác được giao dịch: ' . $e->getMessage());
        }
    }

    /** Luồng ngoại lệ: ghi nhận lỗi, thông báo không thể thực hiện. */
    private function loiHeThong($e, $thongBao)
    {
        error_log('[DonHang] ' . $e->getMessage());
        return $this->ketQua(false, $thongBao);
    }

    private function ketQua($ok, $message, $duLieu = null)
    {
        return array('ok' => $ok, 'message' => $message, 'du_lieu' => $duLieu);
    }
}