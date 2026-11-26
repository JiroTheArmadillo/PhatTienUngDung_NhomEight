<?php
/**
 * Endpoint GET: trả JSON chi tiết 1 yêu cầu hoàn trả cho modal ở GD_XuLyHoanTra.php.
 * Gọi: chiTietHoanTra.php?id=<mã yêu cầu>
 * Chỉ đọc dữ liệu, không thay đổi CSDL.
 */
ini_set('display_errors', '0'); // tránh lỗi/notice PHP làm hỏng JSON

require_once dirname(__FILE__) . '/hoanTraController.php';
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

function hienMaYeuCau($id) { return 'YCHT' . str_pad((int)$id, 2, '0', STR_PAD_LEFT); }
function hienMaHoaDon($id) { return 'HD' . str_pad((int)$id, 3, '0', STR_PAD_LEFT); }
function hienMaSanPham($id) { return 'SP' . str_pad((int)$id, 3, '0', STR_PAD_LEFT); }
function hienMaKhachHang($id) { return 'KH' . str_pad((int)$id, 3, '0', STR_PAD_LEFT); }
function dinhDangTien($so) { return number_format((float)$so, 0, ',', '.') . ' đ'; }
function dinhDangNgayGio($chuoi)
{
    if ($chuoi === null || $chuoi === '') {
        return '';
    }
    $t = strtotime((string)$chuoi);
    return ($t === false || $t === -1) ? (string)$chuoi : date('d/m/Y H:i', $t);
}

// Giống GD_XuLyHoanTra.php: chỉ cần đã đăng nhập (phân quyền sẽ bổ sung sau).
$nguoiDung = XacThuc::layNguoiDung();
if ($nguoiDung === null) {
    baoLoi('Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.');
}
require_once dirname(__FILE__) . '/donHangController.php';
if (!DonHangController::laChuDoanhNghiep($nguoiDung)) {
    echo json_encode(array('ok' => false, 'message' => 'Bạn không có quyền xem chi tiết yêu cầu hoàn trả.'));
    exit;
}
$maYeuCau = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$controller = new HoanTraController();
$kq = $controller->layChiTiet($maYeuCau);
if (!$kq['ok']) {
    baoLoi($kq['message']);
}

$yc = $kq['du_lieu']['yeu_cau'];
$trangThai = trim((string)$yc['trang_thai']);
if ($trangThai === HoanTraController::CHO_DUYET) {
    $nhan = 'Chờ xử lý'; $lop = 'warning';
} elseif ($trangThai === HoanTraController::DA_DUYET) {
    $nhan = 'Đã chấp nhận'; $lop = 'success';
} elseif ($trangThai === HoanTraController::TU_CHOI) {
    $nhan = 'Đã từ chối'; $lop = 'danger';
} else {
    $nhan = $trangThai; $lop = '';
}

$tongHoan = 0;
$dsChiTiet = array();
foreach ($kq['du_lieu']['chi_tiet'] as $ct) {
    $thanhTien = (float)$ct['don_gia'] * (int)$ct['so_luong_hoan'];
    $tongHoan += $thanhTien;
    $dsChiTiet[] = array(
        'ma_san_pham' => hienMaSanPham($ct['ma_san_pham']),
        'ten_san_pham' => (string)$ct['ten_san_pham'],
        'so_luong_mua' => (int)$ct['so_luong_mua'],
        'so_luong_hoan' => (int)$ct['so_luong_hoan'],
        'don_gia' => dinhDangTien($ct['don_gia']),
        'thanh_tien' => dinhDangTien($thanhTien)
    );
}

traVeJson(array(
    'ok' => true,
    'yeu_cau' => array(
        'id' => (int)$yc['ma_yeu_cau'],
        'ma_yeu_cau' => hienMaYeuCau($yc['ma_yeu_cau']),
        'ma_hoa_don' => hienMaHoaDon($yc['ma_hoa_don']),
        'ma_khach_hang' => hienMaKhachHang($yc['ma_khach_hang']),
        'ngay_tao' => dinhDangNgayGio($yc['ngay_tao']),
        'nhan_vien_tao' => (string)$yc['ten_nhan_vien_tao'],
        'ly_do_hoan_tra' => (string)$yc['ly_do_hoan_tra'],
        'trang_thai' => $nhan,
        'lop_trang_thai' => $lop,
        'ly_do_tu_choi' => (string)$yc['ly_do_tu_choi'],
        'ngay_xu_ly' => dinhDangNgayGio($yc['ngay_xu_ly']),
        'nhan_vien_xu_ly' => (string)$yc['ten_nhan_vien_xu_ly'],
        'cho_duyet' => ($trangThai === HoanTraController::CHO_DUYET)
    ),
    'chi_tiet' => $dsChiTiet,
    'tong_hoan' => dinhDangTien($tongHoan)
));