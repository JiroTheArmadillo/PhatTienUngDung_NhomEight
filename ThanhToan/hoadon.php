<?php
// Trang in hoa don: hoadon.php?id=1
require_once dirname(__FILE__) . '/../db.php';
$conn = eightmart_mysqli_connection();

$ma_hoa_don = isset($_GET['id']) ? intval($_GET['id']) : 0;

$kq = mysqli_query($conn, "SELECT d.ID AS ma_hoa_don, d.ThoiGianTao AS ngay_lap,
                                  d.TrangThai AS trang_thai, d.TongTien AS tong_tien,
                                  nv.Ten AS ten_nhan_vien, d.KhachHangID AS ma_khach_hang
                           FROM HoaDon d
                           JOIN NhanVien nv ON nv.ID = d.NhanVienID
                           WHERE d.ID = $ma_hoa_don");
$hd = $kq ? mysqli_fetch_assoc($kq) : null;
if (!$hd) {
    die('Không tìm thấy hóa đơn');
}

$ds_sp = mysqli_query($conn, "SELECT ct.SanPhamID AS ma_san_pham,
                                     sp.TenSanPham AS ten_san_pham,
                                     ct.SoLuong AS so_luong, ct.DonGia AS don_gia,
                                     ct.ThanhTien AS thanh_tien
                              FROM ChiTietHoaDon ct
                              JOIN SanPham sp ON sp.ID = ct.SanPhamID
                              WHERE ct.HoaDonID = $ma_hoa_don");

$kq_tt = mysqli_query($conn, "SELECT PhuongThucThanhToan AS phuong_thuc_thanh_toan,
                                     TrangThai AS trang_thai, ThoiGian AS thoi_gian,
                                     MaGiaoDichNganHang AS ma_giao_dich_ngan_hang
                              FROM ThanhToan WHERE HoaDonID = $ma_hoa_don
                              ORDER BY ID DESC LIMIT 1");
$tt = $kq_tt ? mysqli_fetch_assoc($kq_tt) : null;

function tien($so)
{
    return number_format($so, 0, ',', '.') . ' đ';
}

$ten_phuong_thuc = array('TienMat' => 'Tiền mặt', 'ChuyenKhoan' => 'Chuyển khoản');
$ten_trang_thai = array('ChoThanhToan' => 'Chờ thanh toán', 'DaThanhToan' => 'Đã thanh toán',
    'HoanThanh' => 'Đã thanh toán', 'DaHuy' => 'Đã hủy');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa Đơn HD<?php echo $ma_hoa_don; ?></title>
    <link rel="stylesheet" href="../html/khung-giao-dien.css">
    <style>
        body { font-family: Arial, sans-serif; width: 380px; margin: 20px auto; color: #222; font-size: 14px; }
        h2 { text-align: center; margin-bottom: 4px; }
        .giua { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { padding: 6px 4px; border-bottom: 1px dashed #aaa; text-align: left; }
        td.so, th.so { text-align: right; }
        .tong { font-size: 18px; font-weight: bold; text-align: right; }
        button { margin-top: 15px; width: 100%; padding: 10px; cursor: pointer; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <h2>EIGHT STORE</h2>
    <p class="giua">HÓA ĐƠN BÁN HÀNG</p>
    <p>Mã hóa đơn: <b>HD<?php echo $hd['ma_hoa_don']; ?></b></p>
    <p>Ngày lập: <?php echo date('d/m/Y H:i', strtotime($hd['ngay_lap'])); ?></p>
    <p>Thu ngân: <?php echo htmlspecialchars($hd['ten_nhan_vien']); ?></p>
    <p>Mã khách hàng: <?php echo $hd['ma_khach_hang'] ? 'KH' . $hd['ma_khach_hang'] : 'Không có'; ?></p>

    <table>
        <tr><th>Sản phẩm</th><th class="so">SL</th><th class="so">Đơn giá</th><th class="so">T.Tiền</th></tr>
        <?php while ($sp = mysqli_fetch_assoc($ds_sp)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($sp['ten_san_pham']); ?></td>
            <td class="so"><?php echo $sp['so_luong']; ?></td>
            <td class="so"><?php echo tien($sp['don_gia']); ?></td>
            <td class="so"><?php echo tien($sp['thanh_tien']); ?></td>
        </tr>
        <?php } ?>
    </table>

    <p class="tong">Tổng cộng: <?php echo tien($hd['tong_tien']); ?></p>
    <?php if ($tt) { ?>
    <p>Thanh toán: <?php echo isset($ten_phuong_thuc[$tt['phuong_thuc_thanh_toan']]) ? $ten_phuong_thuc[$tt['phuong_thuc_thanh_toan']] : $tt['phuong_thuc_thanh_toan']; ?></p>
    <?php if ($tt['ma_giao_dich_ngan_hang']) { ?>
    <p>Mã giao dịch: <?php echo htmlspecialchars($tt['ma_giao_dich_ngan_hang']); ?></p>
    <?php } ?>
    <?php } ?>
    <p>Trạng thái: <b><?php echo isset($ten_trang_thai[$hd['trang_thai']]) ? $ten_trang_thai[$hd['trang_thai']] : $hd['trang_thai']; ?></b></p>
    <p class="giua">Cảm ơn quý khách!</p>

    <button onclick="window.print()">In hóa đơn</button>
</body>
</html>