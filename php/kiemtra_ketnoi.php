<?php
// Mở http://localhost/PTUD/php/kiemtra_ketnoi.php để kiểm tra kết nối CSDL schema.
header('Content-Type: text/plain; charset=utf-8');
require_once dirname(__FILE__) . '/../db.php';
try {

    $db = KetNoiDB::layKetNoi();
    $n = $db->query('SELECT COUNT(*) FROM NhanVien')->fetchColumn();
    echo "Kết nối thành công CSDL '" . KetNoiDB::TEN_DB . "'. Số nhân viên: " . $n;
} catch (Exception $e) {
    echo "Kết nối thất bại: " . $e->getMessage() . "\n\n"
       . "Kiểm tra: đã bật MySQL trong WAMP, đã import schema.sql, và user/mật khẩu trong KetNoiDB.php.";

}
