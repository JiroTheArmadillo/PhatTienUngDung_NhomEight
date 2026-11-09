<?php
/** Kết nối tới DB schema đã import từ file schema.sql RIÊNG của người dùng. */
class KetNoiDB
{
    const HOST = '127.0.0.1';
    const PORT = '3306';
    const TEN_DB = 'schema';
    const USER = 'root';
    const PASS = '';
    private static $conn = null;

    public static function layKetNoi()
    {
        if (self::$conn === null) {
            // PDO MySQL trên WAMP 2.0 không phải lúc nào cũng hỗ trợ charset= trong DSN.
            $dsn = 'mysql:host=' . self::HOST . ';port=' . self::PORT
                 . ';dbname=' . self::TEN_DB;
            self::$conn = new PDO($dsn, self::USER, self::PASS,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            // MySQL cũ không hỗ trợ utf8mb4; dùng utf8 cho WAMP 2.0.
            self::$conn->exec('SET NAMES utf8');
        }
        return self::$conn;
    }
}
