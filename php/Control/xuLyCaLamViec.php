<?php
/**
 * Nhận POST từ html/GD_QuanLyCaLamViec.php: bat_dau | ket_thuc | huy_don.
 * Xử lý xong lưu thông báo vào session rồi chuyển về giao diện Quản lý ca làm việc.
 */
session_start();
require_once dirname(__FILE__) . '/caLamViecController.php';
require_once dirname(__FILE__) . '/../XacThuc.php';

function chuyenVe($ok, $thongBao)
{
    $_SESSION['flash_ca_lam_viec'] = array('ok' => $ok, 'message' => $thongBao);
    header('Location: ../../html/GD_QuanLyCaLamViec.php');
    exit;
}

$nguoiDung = XacThuc::layNguoiDung();
if ($nguoiDung === null) {
    header('Location: ../../html/GD_DangNhap.php');
    exit;
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenVe(false, 'Yêu cầu không hợp lệ.');
}
if (!CaLamViecController::kiemTraTokenCsrf(isset($_POST['token']) ? $_POST['token'] : '')) {
    chuyenVe(false, 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
}

$maNhanVien = isset($nguoiDung['ma_nhan_vien']) ? (int)$nguoiDung['ma_nhan_vien'] : 0;
$hanhDong = isset($_POST['action']) ? $_POST['action'] : '';
$controller = new CaLamViecController();

if ($hanhDong === 'bat_dau') {
    $kq = $controller->batDauCa($maNhanVien);
} elseif ($hanhDong === 'ket_thuc') {
    $kq = $controller->ketThucCa($maNhanVien);
} elseif ($hanhDong === 'huy_don') {
    $kq = $controller->huyDonDangXuLy($maNhanVien, isset($_POST['ma_hoa_don']) ? $_POST['ma_hoa_don'] : 0);
} else {
    chuyenVe(false, 'Thao tác không hợp lệ.');
}

chuyenVe($kq['ok'], $kq['message']);