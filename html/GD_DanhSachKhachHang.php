<?php
require_once dirname(__FILE__) . "/../php/XacThuc.php"; XacThuc::yeuCauDangNhap();
require_once dirname(__FILE__) . '/../php/Control/KhachHangBaoCao.php';
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function tien($v){return number_format((float)$v,0,',','.').' đ';}
$error='';
try { $control=new KhachHangBaoCao(); $ds=$control->danhSachKhachHang(); }
catch(Exception $e){$error=$e->getMessage();}
?><!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>GD_DanhSachKhachHang - EIGHT STORE</title><style>
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

body{font-family:Arial,sans-serif;background:#f4f6f9}
.main-content{min-width:0;overflow:auto}
.main-content .content{padding:24px}
.panel{background:white;padding:20px;margin:16px 0;border-radius:6px}
table{width:100%;border-collapse:collapse;background:white}
th,td{padding:12px;border-bottom:1px solid #ddd;text-align:left}
th{background:#e7eef5}
.btn,button{background:#2980b9;color:white;border:0;padding:10px 14px;text-decoration:none;cursor:pointer;border-radius:4px}
input,select{padding:9px;margin:6px}
a{color:#2980b9}
.stats-container{gap:16px;flex-wrap:wrap}
.stat-box{min-width:180px}
.stat-box .number{overflow-wrap:anywhere}
.error{color:#b42318}
.bar-row{display:flex;align-items:center;gap:10px;margin:12px 0}
.bar-label{width:110px;flex-shrink:0}
.bar-track{background:#e8eef4;flex:1;height:22px;border-radius:4px;overflow:hidden}
.bar-fill{height:100%;background:#3498db}
.bar-value{min-width:125px;text-align:right}
</style></head><body>
    <div class="sidebar">
        <div class="logo">EIGHT STORE</div>
        <a href="GD_TrangChu.php" class="active">dashboard</a>
        <a href="GD_QuanLyBanHang.php" class="active">Quản lý Bán Hàng</a>
        <a href="GD_QuanLyNhanVien.html" class="active">Quản lý nhân viên</a>
        <a href="GD_LapPhieuDatHang.php">Lập phiếu đặt hàng</a>
        <a href="GD_DuyetPhieuDatHang.html">Duyệt phiếu đặt hàng</a>
        <a href="GD_XuLyHoanTra.php">Xử lý hoàn trả</a>
        <a href="GD_DanhSachKhachHang.php">Quản lý khách hàng</a>
        <a href="GD_QuanLyBaoCao.php">Quản lý báo cáo</a>
        <a href="logout.php" style="margin-top: auto; color: #e74c3c;">Đăng xuất</a>
    </div>

<div class="main-content"><div class="header"><div class="user-info">EIGHT STORE</div></div><div class="content"><h2>Quản lý khách hàng</h2><?php if($error!=='')echo '<p class="error">'.h($error).'</p>'; ?><div class="panel"><h3>Danh sách khách hàng</h3><table><tr><th>Mã khách hàng</th><th>Tên</th><th>Số điện thoại</th><th>Địa chỉ</th><th>Thao tác</th></tr><?php if(isset($ds))foreach($ds as $r){ ?><tr><td>KH<?php echo (int)$r['ma_khach_hang']; ?></td><td><?php echo h($r['ten']); ?></td><td><?php echo h($r['sdt']); ?></td><td><?php echo h($r['dia_chi']); ?></td><td><a class="btn" href="GD_CapNhatKhachHang.php?id=<?php echo urlencode($r['ma_khach_hang']); ?>">Cập nhật</a></td></tr><?php } ?></table></div></div></div></body></html>