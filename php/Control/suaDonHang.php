<?php
/**
 * Endpoint POST: thêm / xóa sản phẩm khỏi đơn hàng (UC31, UC32).
 * Tham số: token (CSRF), action = them | xoa, ma_hoa_don, ma_san_pham, so_luong (chỉ khi thêm).
 * Trả JSON: array('ok' => bool, 'message' => string).
 */
ini_set('display_errors', '0'); // tránh lỗi/notice PHP làm hỏng JSON

require_once dirname(__FILE__) . '/donHangController.php';
require_once dirname(__FILE__) . '/../XacThuc.php';

function traVeJson($duLieu)
{
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    echo json_encode($duLieu);
    exit;
}

function baoLoi($message)
{
    traVeJson(array('ok' => false, 'message' => $message));
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    baoLoi('Yêu cầu không hợp lệ.');
}

$nguoiDung = XacThuc::layNguoiDung();
if ($nguoiDung === null) {
    baoLoi('Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.');
}
if (!DonHangController::choPhepSua($nguoiDung)) {
    baoLoi('Bạn không có quyền chỉnh sửa đơn hàng.');
}
if (!DonHangController::kiemTraTokenCsrf(isset($_POST['token']) ? $_POST['token'] : '')) {
    baoLoi('Phiên làm việc không hợp lệ, vui lòng tải lại trang.');
}

$hanhDong = isset($_POST['action']) ? $_POST['action'] : '';
$maHoaDon = isset($_POST['ma_hoa_don']) ? $_POST['ma_hoa_don'] : 0;
$maSanPham = isset($_POST['ma_san_pham']) ? $_POST['ma_san_pham'] : 0;

$controller = new DonHangController();

if ($hanhDong === 'them') {
    $soLuong = isset($_POST['so_luong']) ? $_POST['so_luong'] : '';
    $kq = $controller->themSanPham($maHoaDon, $maSanPham, $soLuong);
} elseif ($hanhDong === 'xoa') {
    $kq = $controller->xoaSanPham($maHoaDon, $maSanPham);
} else {
    baoLoi('Thao tác không hợp lệ.');
}

traVeJson(array('ok' => $kq['ok'], 'message' => $kq['message']));