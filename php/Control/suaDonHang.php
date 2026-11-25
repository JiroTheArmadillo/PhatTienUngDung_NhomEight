<?php
/**
 * Endpoint POST thao tác ghi trên đơn hàng từ modal ở GD_DanhSachDonHang.php:
 *   action = huy_don   : hủy đơn hàng (UC33)    - ma_hoa_don, ly_do
 *   action = hoan_tra  : tạo yêu cầu hoàn trả (UC37) - ma_hoa_don, ly_do, so_luong[<mã sản phẩm>]
 * Tất cả kèm token (CSRF).
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

$controller = new DonHangController();

if ($hanhDong === 'huy_don') {
    $kq = $controller->huyDonHang($maHoaDon, isset($_POST['ly_do']) ? $_POST['ly_do'] : '');
} elseif ($hanhDong === 'hoan_tra') {
    $dsSoLuong = (isset($_POST['so_luong']) && is_array($_POST['so_luong'])) ? $_POST['so_luong'] : array();
    $kq = $controller->taoYeuCauHoanTra(
        $maHoaDon,
        $dsSoLuong,
        isset($_POST['ly_do']) ? $_POST['ly_do'] : '',
        isset($nguoiDung['ma_nhan_vien']) ? (int)$nguoiDung['ma_nhan_vien'] : 0
    );
} else {
    baoLoi('Thao tác không hợp lệ.');
}

traVeJson(array('ok' => $kq['ok'], 'message' => $kq['message']));