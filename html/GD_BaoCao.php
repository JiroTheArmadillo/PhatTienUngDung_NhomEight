<?php
require_once dirname(__FILE__) . '/../php/control_baocao/BaoCao.php';
function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function money($value) { return number_format((float)$value, 0, ',', '.') . ' đ'; }
function fmtDate($value) { return $value ? date('d/m/Y H:i:s', strtotime($value)) : 'Chưa kết thúc'; }
$error = '';
$nhanVien = array();
$caLamViec = array();
try {
    $baoCao = new BaoCao();
    $nhanVien = $baoCao->getDanhSachNhanVien();
    $caLamViec = $baoCao->getDanhSachCa();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Thống Kê - EIGHT</title>
    <style>
        * {
            margin: 0;
            padding: 0;
           
        }

        body {
            display: flex;
            flex-direction: row;
            width: 100vw;
            height: 100vh;
            background-color: #f4f6f9;
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            width: 250px;
            background-color: #1a252f;
            flex-shrink: 0;
        }

        .sidebar .logo {
            padding: 20px;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            color: white;
            background-color: #2c3e50;
           
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

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow-y: auto;
        }

        /* Header */
        .main-content .header {
            display: flex;
            flex-direction: row;
            justify-content: flex-end;
            align-items: center;
            padding: 15px 30px;
          
        }

        .user-info {
            font-weight: bold;
            color: #2c3e50;
        }

    
        .content {
            padding: 30px;
        }

        .report-section {
            background: #ffffff;
            padding: 20px;
           
            margin-bottom: 20px;
        }

        .report-section h2 {
            
            color: #2c3e50;
            font-size: 18px;
            padding-bottom: 10px;
        }
       
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            
        }

        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        th {
            background-color: #f8fafc;
            color: #4a5568;
            font-weight: bold;
        }

    

        td a {
            background-color: #3498db;
            color: white;
            padding: 6px 12px;
         
            text-decoration: none;
            font-size: 12px;
        }

      
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">EIGHT</div>
        <a href="GD_TrangChu.html">Dashboard</a>
        <a href="GD_QuanLyBanHang.html">Quản lý Bán Hàng</a>
        <a href="GD_QuanLyNhanVien.html">Quản lý nhân viên</a>
        <a href="GD_LapPhieuDatHat.html">Lập phiếu đặt hàng</a>
        <a href="GD_DuyetPhieuDatHat.html">Duyệt phiếu đặt hàng</a>
        <a href="GD_XuLyHoanTra.html">Xử lý hoàn trả</a>
        <a href="logout.php" style="margin-top: auto; color: #e74c3c;">Đăng xuất</a>
    </div>

    <!-- Main Content -->
     <div class="main-content">
        <div class="header">
            <div class="user-info">Chào mừng ! Vinh</div>
        </div>
        
        <div class="content">
            <?php if ($error !== '') { ?><p style="color:#b00020;padding:12px;background:white;"><?php echo h($error); ?></p><?php } ?>
            <div class="report-section nhanVien-report">
                <h2>Báo Cáo Thống Kê Nhân Viên</h2>
                <table><thead><tr><th>Mã NV</th><th>Họ và Tên</th><th>Giới tính</th><th>Số điện thoại</th><th>Chức vụ</th><th>Thao tác</th></tr></thead><tbody>
                <?php if (isset($nhanVien)) { foreach ($nhanVien as $nv) { ?>
                <tr><td><?php echo h($nv['ma_nv']); ?></td><td><?php echo h($nv['ho_ten']); ?></td><td><?php echo h($nv['gioi_tinh']); ?></td><td><?php echo h($nv['so_dien_thoai']); ?></td><td><?php echo h($nv['chuc_vu']); ?></td><td><a href="Form_BaoCaoNhanVien.php?ma_nv=<?php echo urlencode($nv['ma_nv']); ?>">Xem</a></td></tr>
                <?php } if (count($nhanVien) === 0) { ?><tr><td colspan="6">Chưa có dữ liệu nhân viên.</td></tr><?php } } ?>
                </tbody></table>
            </div>
            <div class="report-section ca-report">
                <h2>Báo Cáo Thống Kê Ca Làm Việc</h2>
                <table><thead><tr><th>Mã ca</th><th>Thời gian bắt đầu</th><th>Thời gian kết thúc</th><th>Trạng thái</th><th>Nhân viên mở ca</th><th>Thao tác</th></tr></thead><tbody>
                <?php if (isset($caLamViec)) { foreach ($caLamViec as $ca) { ?>
                <tr><td><?php echo h($ca['ma_ca']); ?></td><td><?php echo h(fmtDate($ca['bat_dau'])); ?></td><td><?php echo h(fmtDate($ca['ket_thuc'])); ?></td><td><?php echo h($ca['trang_thai']); ?></td><td><?php echo h($ca['ma_nv_mo_ca']); ?></td><td><a href="Form_BaoCaoCa.php?ma_ca=<?php echo urlencode($ca['ma_ca']); ?>">Xem</a></td></tr>
                <?php } if (count($caLamViec) === 0) { ?><tr><td colspan="6">Chưa có dữ liệu ca làm việc.</td></tr><?php } } ?>
                </tbody></table>
            </div>
        </div>
    </div>


    
</body>
</html>