<?php
/**
 * BaoMat - các tiện ích dùng được trên PHP 5.2 / WAMP 2.0.
 * Hỗ trợ mật khẩu MD5 cũ và PBKDF2-HMAC-SHA256 cho tài khoản mới.
 * Không sử dụng password_hash(), password_verify() hoặc hash_equals() (PHP mới).
 */
class BaoMat
{
    const SO_VONG_BAM = 30000;

    public static function bangNhau($a, $b)
    {
        if (!is_string($a) || !is_string($b)) { return false; }
        $doDaiA = strlen($a);
        $doDaiB = strlen($b);
        if ($doDaiA !== $doDaiB) { return false; }
        $khac = 0;
        for ($i = 0; $i < $doDaiA; $i++) {
            $khac |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $khac === 0;
    }

    /** Tạo muối/token bằng nguồn ngẫu nhiên tốt nhất có sẵn trên WAMP. */
    public static function taoNgauNhien($soByte)
    {
        $bytes = false;
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($soByte);
        }
        if ((!is_string($bytes) || strlen($bytes) !== $soByte)
            && function_exists('mcrypt_create_iv') && defined('MCRYPT_DEV_URANDOM')) {
            $bytes = mcrypt_create_iv($soByte, MCRYPT_DEV_URANDOM);
        }
        if (!is_string($bytes) || strlen($bytes) !== $soByte) {
            // WAMP 2.0 có thể chưa bật OpenSSL/mcrypt; giữ chức năng chạy thử
            // nhưng nên bật OpenSSL trước khi dùng với dữ liệu thực.
            $bytes = '';
            for ($i = 0; $i < $soByte; $i++) {
                $bytes .= chr(mt_rand(0, 255));
            }
        }
        return bin2hex($bytes);
    }

    private static function pbkdf2($matKhau, $saltHex, $soVong)
    {
        $salt = pack('H*', $saltHex);
        $u = hash_hmac('sha256', $salt . pack('N', 1), $matKhau, true);
        $ketQua = $u;
        for ($i = 1; $i < $soVong; $i++) {
            $u = hash_hmac('sha256', $u, $matKhau, true);
            $ketQua = $ketQua ^ $u;
        }
        return bin2hex($ketQua);
    }

    public static function bamMatKhau($matKhau)
    {
        if (!function_exists('hash_hmac')) {
            throw new Exception('PHP chua bat extension hash; khong the ma hoa mat khau.');
        }
        $salt = self::taoNgauNhien(16);
        return 'pbkdf2_sha256$' . self::SO_VONG_BAM . '$' . $salt . '$'
            . self::pbkdf2($matKhau, $salt, self::SO_VONG_BAM);
    }

    public static function kiemTraMatKhau($matKhau, $daLuu)
    {
        if (!is_string($daLuu)) { return false; }
        // Tương thích dữ liệu cũ được lưu bằng MD5(...) trong schema SQL.
        if (preg_match('/^[0-9a-f]{32}$/iD', $daLuu)) {
            return is_string($matKhau)
                && self::bangNhau(md5($matKhau), strtolower($daLuu));
        }
        if (preg_match('/^pbkdf2_sha256\$([0-9]+)\$([0-9a-f]{32})\$([0-9a-f]{64})$/D', $daLuu, $m)) {
            $soVong = (int) $m[1];
            if ($soVong < 1000 || $soVong > 1000000) { return false; }
            return self::bangNhau(self::pbkdf2($matKhau, $m[2], $soVong), $m[3]);
        }
        // Hỗ trợ tài khoản đã băm bcrypt trên PHP mới, nếu máy có thư viện tương ứng.
        if ((substr($daLuu, 0, 4) === '$2y$' || substr($daLuu, 0, 4) === '$2a$')
            && function_exists('password_verify')) {
            return password_verify($matKhau, $daLuu);
        }
        if ((substr($daLuu, 0, 4) === '$2a$' || substr($daLuu, 0, 4) === '$2y$')
            && defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH) {
            return self::bangNhau(crypt($matKhau, $daLuu), $daLuu);
        }
        return false;
    }
}
