<?php
require_once dirname(__FILE__) . '/../php/XacThuc.php';
require_once dirname(__FILE__) . '/../php/Control/baoCao.php';
XacThuc::yeuCauDangNhap();
function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function money($value) { return number_format((float)$value, 0, ',', '.') . ' đ'; }
function fmtDate($value) { return $value ? date('d/m/Y H:i:s', strtotime($value)) : 'Chưa kết thúc'; }
$error = '';
try {
    $baoCao = new BaoCao();
    $ma = isset($_GET['ma_nv']) ? $_GET['ma_nv'] : '';
    $report = $baoCao->getBaoCaoNhanVien($ma);
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Thống Kê - EIGHT</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        .content {
            display: flex;
            flex-direction: column;
        }

       .content .target-report{
    display: flex;
    gap: 20px;
}

.content .target-report .total-invoice,
.content .target-report .total-venue{
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    flex: 1;

    
}



/* Label */
.total-invoice label,
.total-venue label{
    font-size: 14px;
    color: #7f8c8d;
    font-weight: 500;
}

/* Value */
.total-invoice-value,
.total-venue-value{
    font-size: 32px;
    font-weight: bold;
    color: #2c3e50;
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
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
        }

       

        

        
    </style>
    <link rel="stylesheet" href="khung-giao-dien.css">
</head>
<body class="giao-dien-chung">


    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="user-info">Chào mừng ! Vinh </div>
        </div>
        
        <div class="content">
            <?php if ($error !== '') { ?><p style="color:#b00020;"><?php echo h($error); ?></p><?php } ?>
            <?php if (isset($report) && $report !== null) { $info = $report['thong_tin']; $stats = $report['thong_ke']; ?>
            <div class="target-info"><table><thead><tr><th>Mã NV</th><th>Họ và Tên</th><th>Giới tính</th><th>Số điện thoại</th><th>Chức vụ</th><th>Thao tác</th></tr></thead><tbody>
            <tr><td><?php echo h($info['ma_nv']); ?></td><td><?php echo h($info['ho_ten']); ?></td><td><?php echo h($info['gioi_tinh']); ?></td><td><?php echo h($info['so_dien_thoai']); ?></td><td><?php echo h($info['chuc_vu']); ?></td><td><a href="GD_BaoCao.php">Xong</a></td></tr>
            </tbody></table></div>
            <div class="target-report">
                <div class="total-invoice"><label>Tổng số hóa đơn</label><div class="total-invoice-value"><?php echo h($stats['tong_hoa_don']); ?></div></div>
                <div class="total-venue"><label>Tổng doanh thu</label><div class="total-venue-value"><?php echo h(money($stats['tong_doanh_thu'])); ?></div></div>
            </div>
            <?php } elseif ($error === '') { ?><p>Không tìm thấy dữ liệu. <a href="GD_BaoCao.php">Quay lại báo cáo</a></p><?php } ?>
        </div> 
    </div>
    

</body>
</html>
