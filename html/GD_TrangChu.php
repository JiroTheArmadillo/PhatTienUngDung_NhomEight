<?php
/**
 * GIAO DIỆN TRANG CHỦ – thiết kế theo file GD_TrangChu.php người dùng cung cấp.
 * Chỉ cập nhật giao diện trang chủ; giữ nguyên luồng đăng nhập/đăng xuất của dự án.
 * Số liệu thống kê và thông tin tài khoản lấy từ DB/session, không dùng dữ liệu mẫu cố định.
 * Tương thích PHP 5.2 trở lên.
 */
require_once dirname(__FILE__) . '/../php/XacThuc.php';
require_once dirname(__FILE__) . '/../db.php';
$nguoiDung = XacThuc::yeuCauDangNhap();
$laChuDoanhNghiep = isset($nguoiDung['vai_tro']) && $nguoiDung['vai_tro'] === 'ChuDoanhNghiep';
$tenNguoiDung = isset($nguoiDung['ho_ten']) ? $nguoiDung['ho_ten'] : '';
$tenTaiKhoan = isset($nguoiDung['ten_dang_nhap']) ? $nguoiDung['ten_dang_nhap'] : '';
$vaiTroHienThi = $laChuDoanhNghiep ? 'Chủ doanh nghiệp' : 'Nhân viên';

function hTrangChu($chuoi)
{
    return htmlspecialchars((string) $chuoi, ENT_QUOTES, 'UTF-8');
}

// Các chỉ số ở GD_TrangChu.php ban đầu là số minh họa;
// đọc số thực từ cơ sở dữ liệu eightmart để không hiển thị thống kê sai.
$soSanPhamTrongKho = '—';
$soHoaDonDaThanhToan = '—';
$tongDoanhThu = '—';
$loiThongKe = false;
try {
    $soLuongTon = eightmart_mysqli_fetch_value(eightmart_mysqli_execute(
        'SELECT COALESCE(SUM(so_luong_ton), 0) FROM san_pham'));
    $soHoaDon = eightmart_mysqli_fetch_value(eightmart_mysqli_execute(
        "SELECT COUNT(DISTINCT ma_hoa_don) FROM thanh_toan WHERE trang_thai = 'ThanhCong'"));
    $doanhThu = eightmart_mysqli_fetch_value(eightmart_mysqli_execute(
        "SELECT COALESCE(SUM(so_tien), 0) FROM thanh_toan WHERE trang_thai = 'ThanhCong'"));
    $soSanPhamTrongKho = number_format((float) $soLuongTon, 0, ',', '.');
    $soHoaDonDaThanhToan = number_format((float) $soHoaDon, 0, ',', '.');
    $tongDoanhThu = number_format((float) $doanhThu, 0, ',', '.') . ' đ';
} catch (Exception $e) {
    // Không làm trang chủ bị lỗi khi MySQL tạm thời không truy cập được.
    error_log('[TrangChu] Khong the tai thong ke: ' . $e->getMessage());
    $loiThongKe = true;
}

// Giữ các mục menu đúng theo giao diện HTML mẫu.
// Chuc nang chua co file: hien thi nhan nhung khong dan den trang 404.
$menuTrangChu = array(
    array('ten' => 'Quản lý Bán Hàng', 'file' => 'GD_QuanLyBanHang.php', 'chiChu' => true),
    array('ten' => 'Quản lý nhân viên', 'file' => 'GD_QuanLyNhanVien.php', 'chiChu' => true),

    array('ten' => 'Lập phiếu đặt hàng', 'file' => 'GD_LapPhieuDatHang.php', 'chiChu' => true),

    array('ten' => 'Quản lý nhà cung cấp', 'file' => 'GD_QuanLyNhaCungCap.php', 'chiChu' => true),

    array('ten' => 'Duyệt phiếu đặt hàng', 'file' => 'GD_DuyetPhieuDatHang.php', 'chiChu' => true),
    array('ten' => 'Xử lý hoàn trả', 'file' => 'GD_XuLyHoanTra.php', 'chiChu' => true),
    array('ten' => 'Xem báo cáo', 'file' => 'GD_BaoCao.php', 'chiChu' => true)
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trang Chủ - Hệ Thống Quản Lý Cửa Hàng</title>
    <style>
        * { margin: 0; padding: 0; }
        .main-content .content h2 { text-align: center; }
        .info-card { background: white; padding: 20px; border-radius: 6px;
            margin-bottom: 20px; font-size: 15px; }
        .stats-heading { margin-top: 30px; margin-bottom: 10px;
            color: #2c3e50; font-size: 18px; }
        .stats-container { display: flex; margin-top: 20px; }
        .stat-box { flex: 1; min-width: 0; background: white; padding: 20px;
            border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-left: 4px solid #3498db; }
        .stat-box h3 { font-size: 14px; color: #7f8c8d;
            margin-bottom: 10px; text-transform: uppercase; }
        .stat-box .number { font-size: 24px; font-weight: bold;
            color: #2c3e50; overflow-wrap: anywhere; }
        .stats-warning { margin: 15px 20px; color: #a65008; font-size: 13px; }
        @media (max-width: 840px) {
            .stats-container { flex-wrap: wrap; }
            .stat-box { flex: 1 1 180px; }
        }
        @media (max-width: 540px) {
            .stats-container { display: block; }
            .stat-box { margin-bottom: 8px; }
        }
    </style>
    <link rel="stylesheet" href="khung-giao-dien.css">
</head>
<body class="giao-dien-chung">
    <!-- Trang chu hien thi ngay sau khi dang nhap; vao nhan vien qua menu ben trai. -->
    <aside class="sidebar">
        <div class="logo">EIGHT STORE</div>
        <a href="GD_TrangChu.php" class="active" aria-current="page">dashboard</a>
        <?php foreach ($menuTrangChu as $muc): ?>
            <?php if (is_file(dirname(__FILE__) . '/' . $muc['file'])): ?>
                <a href="<?php echo hTrangChu($muc['file']); ?>"><?php echo hTrangChu($muc['ten']); ?></a>
            <?php else: ?>
                <span class="side-disabled" title="Chức năng chưa khả dụng"><?php echo hTrangChu($muc['ten']); ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
        <!-- Đăng xuất giữ nguyên cơ chế POST + CSRF token của dự án. -->
        <form class="logout" method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?php echo hTrangChu(XacThuc::layTokenDangXuat()); ?>">
            <button type="submit">Đăng xuất</button>
        </form>
    </aside>

    <main class="main-content">
        <div class="header">
            <div class="user-info">Chào mừng, <b><?php echo hTrangChu($tenNguoiDung); ?> (<?php echo hTrangChu($vaiTroHienThi); ?>)</b></div>
        </div>
        <div class="content">
            <h2>Tổng Quan Hệ Thống</h2>

            <div class="info-card">
                <b>Giới thiệu cửa hàng:</b>
                Chào mừng bạn đến với hệ thống quản lý chuỗi Cửa hàng Tiện lợi <b>EIGHT Store</b>.
                Hệ thống hỗ trợ tối ưu hóa quy trình bán hàng, kiểm soát kho hàng, quản lý nhân sự
                và theo dõi doanh thu một cách nhanh chóng, minh bạch.
            </div>

            <div class="info-card">
                <b>Thông tin tài khoản hiện tại:</b>
                <span>Tên tài khoản: <b><?php echo hTrangChu($tenTaiKhoan); ?></b></span>
                <span class="divider">|</span>
                <span>Họ và tên: <b><?php echo hTrangChu($tenNguoiDung); ?></b></span>
                <span class="divider">|</span>
                <span>Vai trò: <b><?php echo hTrangChu($vaiTroHienThi); ?></b></span>
            </div>

            <h3 class="stats-heading">Thống kê hoạt động kinh doanh</h3>
            <div class="stats-container">
                <div class="stat-box" style="border-left-color: #3498db;">
                    <h3>Sản phẩm trong kho</h3>
                    <div class="number"><?php echo hTrangChu($soSanPhamTrongKho); ?></div>
                </div>
                <div class="stat-box" style="border-left-color: #2ecc71;">
                    <h3>Hóa đơn đã thanh toán</h3>
                    <div class="number"><?php echo hTrangChu($soHoaDonDaThanhToan); ?></div>
                </div>
                <div class="stat-box" style="border-left-color: #e67e22;">
                    <h3>Tổng doanh thu</h3>
                    <div class="number"><?php echo hTrangChu($tongDoanhThu); ?></div>
                </div>
            </div>
            <?php if ($loiThongKe): ?>
                <p class="stats-warning">Chưa tải được dữ liệu thống kê. Vui lòng kiểm tra kết nối MySQL.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
