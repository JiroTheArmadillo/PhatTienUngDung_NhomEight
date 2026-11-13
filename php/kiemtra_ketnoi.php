<?php
// Mở http://localhost/PTUD/php/kiemtra_ketnoi.php để kiểm tra kết nối CSDL.
header('Content-Type: text/plain; charset=utf-8');
require_once dirname(__FILE__) . '/../db.php';
try {

    $n = eightmart_mysqli_fetch_value(eightmart_mysqli_execute('SELECT COUNT(*) FROM NhanVien'));
    echo "Kết nối thành công CSDL '" . DB_NAME . "'. Số nhân viên: " . $n;
} catch (Exception $e) {
    echo "Kết nối thất bại: " . $e->getMessage() . "\n\n"
       . "Kiểm tra: đã bật MySQL trong WAMP, đã import schema.sql vào CSDL '" . DB_NAME . "', "
       . "và thông tin kết nối trong db.php.";

}
