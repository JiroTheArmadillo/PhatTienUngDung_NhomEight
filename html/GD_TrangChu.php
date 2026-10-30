<?php
// Kết nối CSDL MySQL (nếu cần lấy dữ liệu thật)
// include 'db.php'; 
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Trang Chủ - Hệ Thống Quản Lý Cửa Hàng</title>

    <style>
        * {

            margin: 0;
            padding: 0;

        }

        body {
            display: flex;
            height: 100vh;
            color: #333;
        }

        .sidebar {
            width: 30%;
            background-color: #2c3e50;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .sidebar .logo {
            padding: 20px;
            font-size: 20px;
            font-weight: bold;
            background-color: #1a252f;
            text-align: center;

        }

        .sidebar a {
            padding: 15px 20px;
            color: #bdc3c7;
            text-decoration: none;
            font-size: 14px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #34495e;
            color: white;

        }

        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }


        .main-content .header {
            display: flex;
            flex-direction: row;
            justify-content: end;
        }

        .main-content .content h2 {
            text-align: center;
        }

        .main-content .header .user-info {
            margin: 20px;
        }

        /* CSS bổ sung riêng cho trang tổng quan (dashboard) để bố cục thẻ thống kê đẹp mắt */
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;

            font-size: 15px;

        }

        .stats-container {
            display: flex;
            margin-top: 20px;
        }

        .stat-box {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #3498db;
        }

        .stat-box h3 {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-box .number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>

<body>

    <!-- Sidebar Menu -->
    <div class="sidebar">
        <div class="logo">EIGHT STORE</div>
        <a href="GD_TrangChu.php" class="active">Trang Chủ</a>
        <a href="GD_QuanLyBanHang.php" class="active">Quản lý Bán Hàng</a>
        <a href="GD_QuanLyNhanVien.html" class="active">Quản lý nhân viên</a>
        <a href="GD_LapPhieuDatHang.php">Lập phiếu đặt hàng</a>
        <a href="GD_DuyetPhieuDatHang.html">Duyệt phiếu đặt hàng</a>
        <a href="GD_XuLyHoanTra.php">Xử lý hoàn trả</a>
        <a href="GD_DanhSachKhachHang.php">Quản lý khách hàng</a>
        <a href="GD_QuanLyBaoCao.php">Quản lý báo cáo</a>
        <a href="logout.php" style="margin-top: auto; color: #e74c3c;">Đăng xuất</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="user-info">Chào mừng, <b>Vinh (Chủ doanh nghiệp)</b></div>
        </div>

        <div class="content">
            <h2>Tổng Quan Hệ Thống</h2>

            <!-- Dòng giới thiệu về cửa hàng -->
            <div class="info-card">
                <b>ðŸª Giới thiệu cửa hàng:</b> Chào mừng bạn đến với hệ thống quản lý chuỗi Cửa hàng Tiện lợi <b>EIGHT
                    Store</b>. Hệ thống hỗ trợ tối ưu hóa quy trình bán hàng, kiểm soát kho hàng, quản lý nhân sự và
                theo dõi doanh thu một cách nhanh chóng, minh bạch.
            </div>

            <!-- Dòng thể hiện tài khoản đăng nhập -->
            <div class="info-card">
                <b>ðŸ‘¤ Thông tin tài khoản hiện tại:</b>
                <span>Tên tài khoản: <b>admin_vinh</b></span> |
                <span>Họ và tên: <b>Nguyễn Văn Công Vinh</b></span> |
                <span>Vai trò: <b>Chủ doanh nghiệp / Quản lý cấp cao</b></span>
            </div>

            <!-- Các dòng thể hiện thống kê (Hiển thị dạng các hộp thống kê trực quan) -->
            <h3 style="margin-top: 30px; margin-bottom: 10px; color: #2c3e50; font-size: 18px;">Thống kê hoạt động kinh
                doanh</h3>

            <div class="stats-container">
                <!-- Thống kê 1: Số sản phẩm trong kho -->
                <div class="stat-box" style="border-left-color: #3498db;">
                    <h3>Sản phẩm trong kho</h3>
                    <div class="number">1,250</div> <!-- Sau này thay bằng biến PHP từ CSDL -->
                </div>

                <!-- Thống kê 2: Số hóa đơn đã thanh toán -->
                <div class="stat-box" style="border-left-color: #2ecc71;">
                    <h3>Hóa đơn đã thanh toán</h3>
                    <div class="number">348</div> <!-- Sau này thay bằng biến PHP từ CSDL -->
                </div>

                <!-- Thống kê 3: Tổng doanh thu -->
                <div class="stat-box" style="border-left-color: #e67e22;">
                    <h3>Tổng doanh thu</h3>
                    <div class="number">45,850,000 đ</div> <!-- Sau này thay bằng biến PHP từ CSDL -->
                </div>
            </div>
        </div>
    </div>

</body>

</html>
