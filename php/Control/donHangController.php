<?php
require_once dirname(__FILE__) . '/khuyenMaiController.php';
require_once dirname(__FILE__) . '/../Repository/DonHangRepository.php';
require_once dirname(__FILE__) . '/../BaoMat.php';

/**
 * Control: nghiệp vụ về đơn hàng (Quản lý bán hàng):
 *  - Xem danh sách đơn hàng, xem chi tiết đơn hàng (UC28) - actor Chủ doanh nghiệp.
 *  - Hủy đơn hàng (UC33) và tạo yêu cầu hoàn trả (UC37) - actor Nhân viên.
 *
 * Các hàm trả về array('ok' => bool, 'message' => string, 'du_lieu' => mixed).
 */
class DonHangController
{
    const HOAN_THANH    = 'HoanThanh';
    const DA_THANH_TOAN = 'DaThanhToan'; // trạng thái do luồng thanh toán ở trang bán hàng ghi vào HoaDon
    const DA_HUY        = 'DaHuy';

    /** PhieuYeuCauTraHang.TrangThai khi mới tạo - giống HoanTraController::CHO_DUYET (màn hình Xử lý hoàn trả). */
    const YEU_CAU_CHO_DUYET = 'ChoDuyet';

    const KHOA_CSRF = 'eightmart_csrf_donhang';

    private $repo;

    public function __construct()
    {
        $this->repo = new DonHangRepository();
    }

    // ---------------- Phân quyền & CSRF ----------------

    /** Xem danh sách / chi tiết: Chủ doanh nghiệp (vai trò QuanLy / ChuDoanhNghiep). */
 /** Xem danh sách / chi tiết đơn hàng: cả Chủ doanh nghiệp và Nhân viên đã đăng nhập. */
    public static function choPhepXem($nguoiDung)
    {
        if (!is_array($nguoiDung)) {
            return false;
        }
        return count($nguoiDung) > 0;
    }

    /** Kiểm tra quyền Chủ doanh nghiệp (vai trò QuanLy / ChuDoanhNghiep) cho chức năng Xử lý hoàn trả. */
    public static function laChuDoanhNghiep($nguoiDung)
    {
        if (!is_array($nguoiDung)) {
            return false;
        }
        if (!isset($nguoiDung['vai_tro'])) {
            return false;
        }
        $vaiTro = strtolower(trim((string)$nguoiDung['vai_tro']));
        if ($vaiTro === 'quanly') {
            return true;
        }
        if ($vaiTro === 'chudoanhnghiep') {
            return true;
        }
        return false;
    }

    /** Hủy đơn / tạo yêu cầu hoàn trả: Nhân viên đã đăng nhập (Pre-condition UC33, UC37). */
    public static function choPhepSua($nguoiDung)
    {
        return is_array($nguoiDung) && count($nguoiDung) > 0;
    }

    /** HoaDon.TrangThai "Chưa thanh toán": xulythanhtoan.php ghi ChoThanhToan khi tạo đơn chuyển khoản chờ xác nhận. */
    public static function cacTrangThaiChuaThanhToan()
    {
        return array('ChoThanhToan');
    }

    /** Đơn đã thanh toán xong: DaThanhToan (luồng thanh toán) hoặc HoanThanh (dữ liệu mẫu). */
    public static function laDaThanhToan($trangThai)
    {
        $tt = trim((string)$trangThai);
        return $tt === self::DA_THANH_TOAN || $tt === self::HOAN_THANH;
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

            $tamTinh = 0;
            foreach ($chiTiet as $ct) {
                $tamTinh += (float)$ct['thanh_tien'];
            }
            $tienGiam = max(0, $tamTinh - (float)$hoaDon['tong_tien']);
            $khuyenMai = KhuyenMaiController::layTheoHoaDon($maHoaDon);

            return $this->ketQua(true, '', array(
                'hoa_don'    => $hoaDon,
                'chi_tiet'   => $chiTiet,
                'khuyen_mai' => $khuyenMai,
                'tam_tinh'   => $tamTinh,
                'tien_giam'  => $tienGiam
            ));
        } catch (Exception $e) {
            return $this->loiHeThong($e, 'Không thể tải dữ liệu chi tiết đơn hàng.');
        }
    }

    // ---------------- UC33: Hủy đơn hàng ----------------

    /** Hủy đơn chưa hoàn tất thanh toán: đơn -> DaHuy kèm lý do, giao dịch chờ -> ThatBai (không ghi nhận doanh thu). */
    public function huyDonHang($maHoaDon, $lyDo)
    {
        $maHoaDon = (int)$maHoaDon;
        $lyDo = trim((string)$lyDo);

        if ($maHoaDon <= 0) {
            return $this->ketQua(false, 'Mã đơn hàng không hợp lệ.');
        }
        if ($lyDo === '') {
            return $this->ketQua(false, 'Vui lòng nhập hoặc chọn lý do hủy.');
        }
        if ($this->doDai($lyDo) > 255) {
            return $this->ketQua(false, 'Lý do hủy không được quá 255 ký tự.');
        }

        $daBatDau = false;
        try {
            $this->repo->batDauGiaoDich();
            $daBatDau = true;

            // Thứ tự khóa: ThanhToan -> HoaDon (giống xac_nhan_chuyen_khoan) để không deadlock.
            $this->repo->khoaThanhToanChoXacNhan($maHoaDon);
            $hoaDon = $this->repo->khoaHoaDon($maHoaDon);
            if ($hoaDon === null) {
                $this->hoanTac();
                return $this->ketQua(false, 'Không tìm thấy đơn hàng.');
            }
            if (!self::laChuaThanhToan($hoaDon['trang_thai'])) {
                $this->hoanTac();
                return $this->ketQua(false, 'Chỉ được hủy đơn hàng chưa hoàn tất thanh toán.');
            }

            $this->repo->danhDauThanhToanThatBai($maHoaDon);
            if (!$this->repo->huyHoaDon($maHoaDon, $lyDo, $hoaDon['trang_thai'])) {
                throw new Exception('Không cập nhật được trạng thái hủy của hóa đơn ' . $maHoaDon);
            }

            $this->repo->xacNhanGiaoDich();
            return $this->ketQua(true, 'Đã hủy đơn hàng ' . self::hienMaHoaDon($maHoaDon) . '.');
        } catch (Exception $e) {
            if ($daBatDau) {
                $this->hoanTac();
            }
            // Luồng 10.1: lỗi cập nhật, giữ nguyên trạng thái đơn hàng.
            return $this->loiHeThong($e, 'Lỗi cập nhật khi hủy đơn hàng. Đơn hàng được giữ nguyên, vui lòng thử lại.');
        }
    }

    // ---------------- UC37: Tạo yêu cầu hoàn trả ----------------

    /**
     * Tạo yêu cầu hoàn trả cho đơn đã thanh toán.
     * $dsSoLuong: array(mã sản phẩm => số lượng hoàn trả). Lưu với trạng thái Chờ duyệt,
     * Chủ doanh nghiệp xử lý ở màn hình "Xử lý hoàn trả".
     */
    public function taoYeuCauHoanTra($maHoaDon, $dsSoLuong, $lyDo, $maNhanVien)
    {
        $maHoaDon = (int)$maHoaDon;
        $maNhanVien = (int)$maNhanVien;
        $lyDo = trim((string)$lyDo);

        if ($maHoaDon <= 0 || $maNhanVien <= 0) {
            return $this->ketQua(false, 'Dữ liệu không hợp lệ.');
        }

        // Bước 10: kiểm tra thông tin (luồng 10.1 nếu không hợp lệ).
        $yeuCau = array(); // mã sản phẩm => số lượng
        if (is_array($dsSoLuong)) {
            foreach ($dsSoLuong as $maSp => $sl) {
                $sl = trim((string)$sl);
                if ($sl === '') {
                    continue;
                }
                if (!preg_match('/^[0-9]{1,6}$/', $sl) || (int)$maSp <= 0) {
                    return $this->ketQua(false, 'Số lượng hoàn trả phải là số nguyên không âm.');
                }
                if ((int)$sl > 0) {
                    $yeuCau[(int)$maSp] = (int)$sl;
                }
            }
        }
        if (count($yeuCau) === 0) {
            return $this->ketQua(false, 'Vui lòng chọn ít nhất một sản phẩm và nhập số lượng cần hoàn trả.');
        }
        if ($lyDo === '') {
            return $this->ketQua(false, 'Vui lòng nhập lý do hoàn trả.');
        }
        if ($this->doDai($lyDo) > 255) {
            return $this->ketQua(false, 'Lý do hoàn trả không được quá 255 ký tự.');
        }

        $daBatDau = false;
        try {
            $this->repo->batDauGiaoDich();
            $daBatDau = true;

            // Khóa đơn để 2 yêu cầu cùng lúc không cùng hoàn quá số lượng đã bán.
            $hoaDon = $this->repo->khoaHoaDon($maHoaDon);
            if ($hoaDon === null) {
                $this->hoanTac();
                return $this->ketQua(false, 'Không tìm thấy đơn hàng.');
            }
            if (!self::laDaThanhToan($hoaDon['trang_thai'])) {
                $this->hoanTac();
                return $this->ketQua(false, 'Chỉ tạo yêu cầu hoàn trả cho đơn hàng đã thanh toán.');
            }

            $dongDon = array();
            foreach ($this->repo->layChiTiet($maHoaDon) as $d) {
                $dongDon[(int)$d['ma_san_pham']] = $d;
            }
            foreach ($yeuCau as $maSp => $sl) {
                if (!isset($dongDon[$maSp])) {
                    $this->hoanTac();
                    return $this->ketQua(false, 'Sản phẩm ' . self::hienMaSanPham($maSp) . ' không thuộc đơn hàng này.');
                }
                $conLai = (int)$dongDon[$maSp]['so_luong'] - (int)$dongDon[$maSp]['da_yeu_cau_hoan'];
                if ($sl > $conLai) {
                    $this->hoanTac();
                    return $this->ketQua(false, 'Số lượng hoàn trả của ' . $dongDon[$maSp]['ten_san_pham']
                        . ' vượt quá số lượng có thể hoàn (còn ' . max(0, $conLai) . ').');
                }
            }

            // Bước 11: tạo yêu cầu, lưu với trạng thái Chờ xử lý.
            $maPhieu = $this->repo->taoPhieuHoanTra($maHoaDon, $lyDo, $maNhanVien, self::YEU_CAU_CHO_DUYET);
            if ($maPhieu <= 0) {
                throw new Exception('Không tạo được phiếu yêu cầu hoàn trả cho hóa đơn ' . $maHoaDon);
            }
            foreach ($yeuCau as $maSp => $sl) {
                if (!$this->repo->themChiTietPhieuHoanTra($maPhieu, $maHoaDon, $maSp, $sl)) {
                    throw new Exception('Không lưu được chi tiết yêu cầu hoàn trả ' . $maPhieu . ' / sản phẩm ' . $maSp);
                }
            }

            $this->repo->xacNhanGiaoDich();
            return $this->ketQua(true, 'Tạo yêu cầu hoàn trả thành công. Yêu cầu đã được gửi đến Chủ doanh nghiệp để phê duyệt.');
        } catch (Exception $e) {
            if ($daBatDau) {
                $this->hoanTac();
            }
            // Luồng 11.1: tạo yêu cầu thất bại.
            return $this->loiHeThong($e, 'Không thể tạo yêu cầu hoàn trả, vui lòng thử lại.');
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

    /** Độ dài chuỗi UTF-8 (không phụ thuộc extension mbstring). */
    private function doDai($chuoi)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($chuoi, 'UTF-8');
        }
        return preg_match_all('/./us', $chuoi, $m);
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