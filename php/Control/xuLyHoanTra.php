<?php
session_start();
require_once dirname(__FILE__) . '/hoanTraController.php';
require_once dirname(__FILE__) . '/../XacThuc.php';

$trangDanhSach = '../../html/GD_XuLyHoanTra.php';

// Chỉ nhận POST (thao tác thay đổi dữ liệu không nên dùng link GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $trangDanhSach);
    exit;
}

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

$nguoiDung = XacThuc::layNguoiDung();
if ($nguoiDung === null) {
    header('Location: ../../html/GD_DangNhap.php');
    exit;
}
$maNguoiXuLy = isset($nguoiDung['ma_nhan_vien']) ? (int)$nguoiDung['ma_nhan_vien'] : 0;
if ($maNguoiXuLy <= 0) {
    $_SESSION['flash'] = array('ok' => false, 'message' => 'Không xác định được nhân viên đang đăng nhập.');
    header('Location: ' . $trangDanhSach);
    exit;
}

$controller = new HoanTraController();

if ($id <= 0) {
    $ketQua = array('ok' => false, 'message' => 'Mã yêu cầu không hợp lệ.');
} elseif ($action === 'accept') {
    $ketQua = $controller->chapNhan($id, $maNguoiXuLy);
} elseif ($action === 'reject') {
    $lyDo = isset($_POST['ly_do_tu_choi']) ? $_POST['ly_do_tu_choi'] : '';
    $ketQua = $controller->tuChoi($id, $lyDo, $maNguoiXuLy);
} else {
    $ketQua = array('ok' => false, 'message' => 'Thao tác không hợp lệ.');
}

$_SESSION['flash'] = $ketQua;
header('Location: ' . $trangDanhSach);
exit;
