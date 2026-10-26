<?php
/**
 * KetNoiDB - kết nối MySQL (CSDL eightmart) chạy trên localhost (XAMPP/WAMP).
 * Sửa 4 hằng số bên dưới nếu máy bạn cấu hình khác.
 * Tương thích PHP 5.2.
 */
class KetNoiDB
{
    const HOST    = 'localhost';
    const PORT    = '3306';
    const TEN_DB  = 'eightmart';
    const USER    = 'root';
    const PASS    = '';          // XAMPP mặc định để trống

    private static $conn = null;

    /** Trả về đối tượng PDO dùng chung (tạo một lần). */
    public static function layKetNoi()
    {
        if (self::$conn === null) {
            if (defined('NV_DSN')) {                 // chỉ dùng khi kiểm thử với CSDL khác
                $dsn = NV_DSN;
                $conn = new PDO($dsn);
            } else {
                $dsn = 'mysql:host=' . self::HOST . ';port=' . self::PORT . ';dbname=' . self::TEN_DB;
                $conn = new PDO($dsn, self::USER, self::PASS);
                $conn->exec('SET NAMES utf8');
            }
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$conn = $conn;
        }
        return self::$conn;
    }
}
