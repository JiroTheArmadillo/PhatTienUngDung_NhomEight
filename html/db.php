<?php
// db.php
$host = 'localhost';
$user = 'root';
$pass = ''; // Điền mật khẩu MySQL của bạn nếu có
$dbname = 'eightmart';

// Tạo kết nối MySQLi
$conn = new mysqli($host, $user, $pass, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối CSDL thất bại: " . $conn->connect_error);
}

// Thiết lập UTF-8 để không bị lỗi font Tiếng Việt
$conn->set_charset("utf8");
?>