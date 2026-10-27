<?php
// Mở http://localhost/<thư mục>/php/kiemtra_ketnoi.php để kiểm tra kết nối CSDL eightmart.
header('Content-Type: text/plain; charset=utf-8');
require_once dirname(__FILE__) . '/Repository/KetNoiDB.php';
try {
    $db = KetNoiDB::layKetNoi();
    $n = $db->query('SELECT COUNT(*) FROM nhan_vien')->fetchColumn();
    echo "Kết nối thành công CSDL '" . KetNoiDB::TEN_DB . "'. Số nhân viên: " . $n;
} catch (Exception $e) {
    echo "Kết nối thất bại: " . $e->getMessage() . "\n\n"
       . "Kiểm tra: đã bật MySQL trong XAMPP, đã import EightMart_Database.sql, và user/mật khẩu trong KetNoiDB.php.";
}
