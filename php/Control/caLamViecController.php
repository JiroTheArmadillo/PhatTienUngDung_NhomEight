<?php
require_once dirname(__FILE__) . '/../Repository/CaLamViecRepository.php';
require_once dirname(__FILE__) . '/../BaoMat.php';

/**
 * Control: nghiệp vụ "Quản lý ca làm việc".
 *  - UC35 Bắt đầu ca làm việc: mỗi nhân viên chỉ có 1 ca đang mở.
 *  - UC36 Kết thúc ca làm việc: ca phải đang mở và không còn đơn chưa hoàn tất.
 *  - Hủy đơn đang xử lý: để nhân viên xử lý được luồng thay thế 4.1 của UC36.
 *
 * Các hàm trả về array('ok' => bool, 'message' => string, 'du_lieu' => mixed).
 */
class CaLamViecController
{
    const DANG_MO = 'DangMo';
    const DA_DONG = 'DaDong';
    /** HoaDon.TrangThai của đơn chưa hoàn tất (đang chờ chuyển khoản), theo ThanhToan/xulythanhtoan.php. */
    const DON_DANG_XU_LY = 'ChoThanhToan';

    const KHOA_CSRF = 'eightmart_csrf_calamviec';

    private $repo;

    public function __construct()
    {
        $this->repo = new CaLamViecRepository();
    }

    // ---------------- CSRF ----------------

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

    // ---------------- Xem trạng thái ca ----------------

    /**
     * Dữ liệu cho giao diện Quản lý ca làm việc:
     * du_lieu = array('ca_dang_mo' => row|null, 'don_dang_xu_ly' => array, 'tong_ket' => row|null, 'lich_su' => array).
     */
    public function layTrangThai($maNhanVien)
    {
        $maNhanVien = (int)$maNhanVien;
        if ($maNhanVien <= 0) {
            return $this->ketQua(false, 'Không xác định được nhân viên đang đăng nhập.');
        }
        try {
            $ca = $this->repo->layCaDangMo($maNhanVien);
            $donDangXuLy = array();
            $tongKet = null;
            if ($ca !== null) {
                $donDangXuLy = $this->repo->layDonDangXuLy($ca['ma_ca'], self::DON_DANG_XU_LY);
                $tongKet = $this->repo->layTongKetCa($ca['ma_ca']);
            }
            return $this->ketQua(true, '', array(
                'ca_dang_mo' => $ca,
                'don_dang_xu_ly' => $donDangXuLy,
                'tong_ket' => $tongKet,
                'lich_su' => $this->repo->layLichSu($maNhanVien, 10)
            ));
        } catch (Exception $e) {
            return $this->loiHeThong($e, 'Không thể tải dữ liệu ca làm việc.');
        }
    }

    // ---------------- UC35: Bắt đầu ca làm việc ----------------

    public function batDauCa($maNhanVien)
    {
        $maNhanVien = (int)$maNhanVien;
        if ($maNhanVien <= 0) {
            return $this->ketQua(false, 'Không xác định được nhân viên đang đăng nhập.');
        }

        $daBatDau = false;
        try {
            $this->repo->batDauGiaoDich();
            $daBatDau = true;

            if (!$this->repo->khoaNhanVien($maNhanVien)) {
                $this->hoanTac();
                return $this->ketQua(false, 'Không tìm thấy nhân viên.');
            }

            // Bước 4: kiểm tra trạng thái ca hiện tại. Luồng 4.1: đã có ca đang mở thì không tạo ca mới.
            $ca = $this->repo->layCaDangMo($maNhanVien);
            if ($ca !== null) {
                $this->hoanTac();
                return $this->ketQua(false, 'Bạn đang có ca làm việc (Ca #' . (int)$ca['ma_ca'] . ') đang mở, không thể tạo ca mới.');
            }

            // Bước 5: tạo ca mới, ghi nhận nhân viên và thời điểm bắt đầu.
            if (!$this->repo->taoCa($maNhanVien)) {
                throw new Exception('Không tạo được ca làm việc cho nhân viên ' . $maNhanVien);
            }
            $this->repo->xacNhanGiaoDich();
            return $this->ketQua(true, 'Bắt đầu ca làm việc thành công.');
        } catch (Exception $e) {
            if ($daBatDau) {
                $this->hoanTac();
            }
            // Luồng 5.1: lỗi tạo ca, không cho bán hàng theo ca.
            return $this->loiHeThong($e, 'Không thể bắt đầu ca làm việc, bạn chưa thể bán hàng theo ca. Vui lòng thử lại.');
        }
    }

    // ---------------- UC36: Kết thúc ca làm việc ----------------

    public function ketThucCa($maNhanVien)
    {
        $maNhanVien = (int)$maNhanVien;
        if ($maNhanVien <= 0) {
            return $this->ketQua(false, 'Không xác định được nhân viên đang đăng nhập.');
        }

        $daBatDau = false;
        try {
            $this->repo->batDauGiaoDich();
            $daBatDau = true;

            if (!$this->repo->khoaNhanVien($maNhanVien)) {
                $this->hoanTac();
                return $this->ketQua(false, 'Không tìm thấy nhân viên.');
            }

            // Pre-condition: nhân viên đang có ca mở.
            $ca = $this->repo->layCaDangMo($maNhanVien);
            if ($ca === null) {
                $this->hoanTac();
                return $this->ketQua(false, 'Bạn chưa có ca làm việc đang mở.');
            }

            // Bước 4 + luồng thay thế 4.1: còn đơn chưa hoàn tất thì cảnh báo và không đóng ca.
            $soDon = $this->repo->demDonDangXuLy($ca['ma_ca'], self::DON_DANG_XU_LY);
            if ($soDon > 0) {
                $this->hoanTac();
                return $this->ketQua(false, 'Còn ' . $soDon . ' đơn hàng chưa hoàn tất trong ca. Vui lòng hoàn tất thanh toán hoặc hủy đơn trước khi kết thúc ca.');
            }

            // Bước 5: ghi nhận thời điểm kết thúc và đóng ca.
            if (!$this->repo->dongCa($ca['ma_ca'], $maNhanVien)) {
                throw new Exception('Không đóng được ca làm việc ' . $ca['ma_ca']);
            }
            $this->repo->xacNhanGiaoDich();
            return $this->ketQua(true, 'Kết thúc ca làm việc thành công.');
        } catch (Exception $e) {
            if ($daBatDau) {
                $this->hoanTac();
            }
            // Luồng 5.1: lỗi cập nhật trạng thái ca, giữ nguyên trạng thái ca.
            return $this->loiHeThong($e, 'Lỗi cập nhật trạng thái ca. Ca làm việc được giữ nguyên, vui lòng thử lại.');
        }
    }

    // ---------------- Hủy đơn đang xử lý của ca hiện tại ----------------

    public function huyDonDangXuLy($maNhanVien, $maHoaDon)
    {
        $maNhanVien = (int)$maNhanVien;
        $maHoaDon = (int)$maHoaDon;
        if ($maNhanVien <= 0 || $maHoaDon <= 0) {
            return $this->ketQua(false, 'Dữ liệu không hợp lệ.');
        }

        $daBatDau = false;
        try {
            $this->repo->batDauGiaoDich();
            $daBatDau = true;

            $ca = $this->repo->layCaDangMo($maNhanVien);
            if ($ca === null) {
                $this->hoanTac();
                return $this->ketQua(false, 'Bạn chưa có ca làm việc đang mở.');
            }

            // Thứ tự khóa: ThanhToan -> HoaDon (giống xac_nhan_chuyen_khoan) để không deadlock.
            $this->repo->khoaThanhToanChoXacNhan($maHoaDon);
            $hoaDon = $this->repo->khoaHoaDon($maHoaDon);
            if ($hoaDon === null) {
                $this->hoanTac();
                return $this->ketQua(false, 'Không tìm thấy đơn hàng.');
            }
            if ((int)$hoaDon['ma_ca'] !== (int)$ca['ma_ca']) {
                $this->hoanTac();
                return $this->ketQua(false, 'Đơn hàng không thuộc ca làm việc hiện tại.');
            }
            if (trim((string)$hoaDon['trang_thai']) !== self::DON_DANG_XU_LY) {
                $this->hoanTac();
                return $this->ketQua(false, 'Đơn hàng này đã được xử lý trước đó.');
            }

            $this->repo->danhDauThanhToanThatBai($maHoaDon);
            if (!$this->repo->huyHoaDon($maHoaDon, 'Hủy khi kết thúc ca làm việc', self::DON_DANG_XU_LY)) {
                throw new Exception('Không hủy được hóa đơn ' . $maHoaDon);
            }
            $this->repo->xacNhanGiaoDich();
            return $this->ketQua(true, 'Đã hủy đơn ' . self::hienMaHoaDon($maHoaDon) . '.');
        } catch (Exception $e) {
            if ($daBatDau) {
                $this->hoanTac();
            }
            return $this->loiHeThong($e, 'Không thể hủy đơn hàng, vui lòng thử lại.');
        }
    }

    // ---------------- Hàm định dạng hiển thị ----------------

    public static function hienMaHoaDon($id) { return 'HD' . str_pad((int)$id, 3, '0', STR_PAD_LEFT); }

    public static function dinhDangTien($so)
    {
        return number_format((float)$so, 0, ',', '.') . ' đ';
    }

    public static function dinhDangNgayGio($chuoi)
    {
        $t = strtotime((string)$chuoi);
        return ($t === false || $t === -1) ? (string)$chuoi : date('d/m/Y H:i', $t);
    }

    // ---------------- Hàm hỗ trợ ----------------

    private function hoanTac()
    {
        try {
            $this->repo->hoanTacGiaoDich();
        } catch (Exception $e) {
            error_log('[CaLamViec] Không hoàn tác được giao dịch: ' . $e->getMessage());
        }
    }

    private function loiHeThong($e, $thongBao)
    {
        error_log('[CaLamViec] ' . $e->getMessage());
        return $this->ketQua(false, $thongBao);
    }

    private function ketQua($ok, $message, $duLieu = null)
    {
        return array('ok' => $ok, 'message' => $message, 'du_lieu' => $duLieu);
    }
}