<?php
/**
 * Endpoint GET: trả JSON chi tiết 1 đơn hàng cho modal ở GD_DanhSachDonHang.php.
 * Gọi: chiTietDonHang.php?id=<mã hóa đơn>
 * Chỉ đọc dữ liệu, không thay đổi CSDL.
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

$nguoiDung = XacThuc::layNguoiDung();
if ($nguoiDung === null) {
    baoLoi('Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.');
}
if (!DonHangController::choPhepXem($nguoiDung)) {
    baoLoi('Bạn không có quyền xem chi tiết đơn hàng.');
}

$maHoaDon = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$controller = new DonHangController();
$kq = $controller->layChiTiet($maHoaDon);
if (!$kq['ok']) {
    baoLoi($kq['message']);
}

$hd = $kq['du_lieu']['hoa_don'];
$tt = DonHangController::trangThaiHienThi($hd['trang_thai']);

// Chỉ đơn Chưa thanh toán mới được thêm / xóa sản phẩm (UC31, UC32).
$choSua = DonHangController::choPhepSua($nguoiDung) && DonHangController::laChuaThanhToan($hd['trang_thai']);

$dsChiTiet = array();
foreach ($kq['du_lieu']['chi_tiet'] as $ct) {
    $dsChiTiet[] = array(
        'id_san_pham' => (int)$ct['ma_san_pham'],
        'ma_san_pham' => DonHangController::hienMaSanPham($ct['ma_san_pham']),
        'ten_san_pham' => (string)$ct['ten_san_pham'],
        'so_luong' => (int)$ct['so_luong'],
        'don_gia' => DonHangController::dinhDangTien($ct['don_gia']),
        'thanh_tien' => DonHangController::dinhDangTien($ct['thanh_tien'])
    );
}

$dsSanPham = array();
if ($choSua) {
    $kqSp = $controller->layDanhSachSanPham();
    if ($kqSp['ok']) {
        foreach ($kqSp['du_lieu'] as $sp) {
            $dsSanPham[] = array(
                'id' => (int)$sp['ma_san_pham'],
                'nhan' => DonHangController::hienMaSanPham($sp['ma_san_pham']) . ' - ' . $sp['ten_san_pham']
                    . ' (' . DonHangController::dinhDangTien($sp['gia_ban']) . ', tồn ' . (int)$sp['so_luong_ton'] . ')'
            );
        }
    }
}

traVeJson(array(
    'ok' => true,
    'hoa_don' => array(
        'id' => (int)$hd['ma_hoa_don'],
        'ma_hoa_don' => DonHangController::hienMaHoaDon($hd['ma_hoa_don']),
        'thoi_gian_tao' => DonHangController::dinhDangNgayGio($hd['thoi_gian_tao']),
        'trang_thai' => $tt['nhan'],
        'lop_trang_thai' => $tt['lop'],
        'tong_tien' => DonHangController::dinhDangTien($hd['tong_tien']),
        'ly_do_huy' => (string)$hd['ly_do_huy'],
        'ma_nhan_vien' => DonHangController::hienMaNhanVien($hd['ma_nhan_vien']),
        'ma_khach_hang' => DonHangController::hienMaKhachHang($hd['ma_khach_hang']),
        'ma_ca' => (int)$hd['ma_ca'],
        'cho_sua' => $choSua
    ),
    'chi_tiet' => $dsChiTiet,
    'san_pham_them' => $dsSanPham
));