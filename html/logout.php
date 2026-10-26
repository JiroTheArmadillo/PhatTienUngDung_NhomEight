<?php
/** UC02 - Đăng xuất.
 * Bước 1: người dùng nhấn nút Đăng xuất (form POST).
 * Bước 2: hủy dữ liệu session + cookie phiên.
 * Bước 3: chuyển đến giao diện Đăng nhập.
 */
require_once dirname(__FILE__) . '/../php/XacThuc.php';
XacThuc::batDau();
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Không tự động đăng xuất khi người dùng/crawler mở URL bằng GET.
    header('Location: ' . (XacThuc::layNguoiDung() === null ? 'GD_DangNhap.php' : 'GD_TrangChu.php'));
    exit;
}

$token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
if (!XacThuc::kiemTraTokenDangXuat($token)) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Yêu cầu đăng xuất không hợp lệ. Vui lòng quay lại trang chủ.';
    exit;
}

XacThuc::dangXuat();
header('Location: GD_DangNhap.php');
exit;
