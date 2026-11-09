<?php
/**
 * BỔ SUNG TRANG ĐĂNG NHẬP: quản lý session cho tất cả giao diện PHP.
 * Không lưu mật khẩu vào session; phiên hết hạn sau 30 phút không hoạt động.
 * Tương thích PHP 5.2.
 */
require_once dirname(__FILE__) . '/BaoMat.php';

class XacThuc
{
    const THOI_GIAN_HET_HAN = 1800;

    public static function batDau()
    {
        if (session_id() === '') {
            ini_set('session.use_only_cookies', '1');
            session_start();
        }
    }

    public static function layNguoiDung()
    {
        self::batDau();
        if (!isset($_SESSION['eightmart_user']) || !is_array($_SESSION['eightmart_user'])) {
            return null;
        }
        if (!isset($_SESSION['eightmart_hoat_dong'])
            || (time() - (int) $_SESSION['eightmart_hoat_dong']) > self::THOI_GIAN_HET_HAN) {
            unset($_SESSION['eightmart_user'], $_SESSION['eightmart_hoat_dong']);
            return null;
        }
        // Phiên phải còn hiệu lực trong DB: nếu tài khoản bị khóa/xóa,
        // nhân viên bị ngừng hoạt động hoặc bị đổi quyền thì cập nhật ngay.
        try {
            require_once dirname(__FILE__) . '/Repository/TaiKhoanRep.php';
            $rep = new TaiKhoanRep();
            $userId = isset($_SESSION['eightmart_user']['ma_tai_khoan'])
                ? (int)$_SESSION['eightmart_user']['ma_tai_khoan'] : 0;
            $user = $rep->layNguoiDungTheoTaiKhoan($userId);
            if ($user === null) {
                unset($_SESSION['eightmart_user'], $_SESSION['eightmart_hoat_dong']);
                return null;
            }
            $_SESSION['eightmart_user'] = $user;
        } catch (Exception $e) {
            error_log('[XacThuc] Khong kiem tra duoc phien: ' . $e->getMessage());
            return null; // lỗi DB: không cấp quyền dựa vào dữ liệu session cũ
        }
        $_SESSION['eightmart_hoat_dong'] = time();
        return $_SESSION['eightmart_user'];
    }

    public static function yeuCauDangNhap()
    {
        $nguoiDung = self::layNguoiDung();
        if ($nguoiDung === null) {
            header('Location: GD_DangNhap.php');
            exit;
        }
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        return $nguoiDung;
    }

    public static function dangNhap($nguoiDung)
    {
        self::batDau();
        // Chống session fixation: đổi session ID sau khi đăng nhập thành công.
        session_regenerate_id(true);
        $_SESSION['eightmart_user'] = $nguoiDung;
        $_SESSION['eightmart_hoat_dong'] = time();
        unset($_SESSION['eightmart_so_lan_sai'], $_SESSION['eightmart_khoa_den'], $_SESSION['eightmart_csrf_logout']);
    }

    /** UC02: tạo token để nút Đăng xuất gửi POST, tránh bị ép đăng xuất qua liên kết. */
    public static function layTokenDangXuat()
    {
        self::batDau();
        if (!isset($_SESSION['eightmart_csrf_logout'])) {
            $_SESSION['eightmart_csrf_logout'] = BaoMat::taoNgauNhien(16);
        }
        return $_SESSION['eightmart_csrf_logout'];
    }

    public static function kiemTraTokenDangXuat($token)
    {
        self::batDau();
        return is_string($token) && $token !== ''
            && isset($_SESSION['eightmart_csrf_logout'])
            && BaoMat::bangNhau($token, $_SESSION['eightmart_csrf_logout']);
    }

    public static function dangXuat()
    {
        self::batDau();
        $_SESSION = array();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
