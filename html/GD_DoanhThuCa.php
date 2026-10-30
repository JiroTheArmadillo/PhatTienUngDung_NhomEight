<?php
require_once dirname(__FILE__).'/../php/XacThuc.php'; XacThuc::yeuCauDangNhap();
require_once dirname(__FILE__).'/../php/Control/KhachHangBaoCao.php';
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function tien($v){return number_format((float)$v,0,',','.').' đ';}
$error='';$rows=array();$id=isset($_GET['ma_ca'])?(int)$_GET['ma_ca']:0;
try{$control=new KhachHangBaoCao();$rows=$control->doanhThuTungCa();if($id>0)$tk=$control->doanhThuCa($id);}
catch(Exception $e){$error=$e->getMessage();}
?><!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Báo cáo doanh thu trong ca - EIGHT STORE</title><style>
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
    
body{font-family:Arial,sans-serif;background:#f4f6f9}.main-content{min-width:0;overflow:auto}.content{padding:24px}.panel{background:#fff;padding:20px;margin:16px 0;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,.05)}.stats-container{gap:16px;flex-wrap:wrap}.stat-box{min-width:170px}.stat-box .number{overflow-wrap:anywhere}.toolbar{display:flex;gap:12px;align-items:end;flex-wrap:wrap}.toolbar label{display:flex;flex-direction:column;gap:6px;font-size:14px}.toolbar input,.toolbar select{padding:9px;border:1px solid #ccd5df;border-radius:4px}.btn,button{display:inline-block;padding:10px 15px;border:0;border-radius:4px;background:#2c3e50;color:white;text-decoration:none;cursor:pointer;font-size:14px}.btn.secondary{background:#edf2f7;color:#2c3e50;border:1px solid #cbd5e1}.btn:hover,button:hover{background:#34495e;color:white}table{width:100%;border-collapse:collapse}th,td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0}th{background:#eaf0f5;color:#2c3e50}.error{color:#b42318}.chart{height:280px;display:flex;align-items:stretch;gap:12px;border-bottom:1px solid #cbd5e1;padding:20px 10px 0}.chart-col{min-width:32px;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:end;gap:6px}.chart-bar{width:70%;max-width:60px;background:#3498db;border-radius:4px 4px 0 0;min-height:2px}.chart-label{font-size:12px;color:#475569;text-align:center;white-space:nowrap}.chart-number{font-size:11px;color:#475569;text-align:center}.muted{color:#64748b;font-size:13px;margin-top:8px}.heading{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.scroll{overflow-x:auto}.empty{padding:22px;color:#64748b;text-align:center}
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

    <div class="main-content"><div class="header"><div class="user-info">EIGHT STORE</div></div><div class="content"><div class="heading"><h2>Báo cáo doanh thu trong ca</h2><a class="btn secondary" href="GD_QuanLyBaoCao.php">← Quay lại quản lý báo cáo</a></div>
<div class="panel"><form class="toolbar" method="get"><label>Chọn ca làm việc<select name="ma_ca"><option value="0">Tất cả ca</option><?php foreach($rows as $r){ ?><option value="<?php echo (int)$r['ma_ca']; ?>" <?php if($id==(int)$r['ma_ca'])echo 'selected'; ?>>Ca <?php echo (int)$r['ma_ca']; ?> - <?php echo h($r['nhan_vien']); ?></option><?php } ?></select></label><button>Xem chi tiết ca</button></form></div>
<?php if($error!==''){ ?><p class="panel error"><?php echo h($error); ?></p><?php } ?>
<?php if(isset($tk)){ ?><div class="stats-container"><div class="stat-box"><h3>Hóa đơn trong ca <?php echo $id; ?></h3><div class="number"><?php echo (int)$tk['so_don']; ?></div></div><div class="stat-box" style="border-left-color:#3498db"><h3>Doanh thu ca <?php echo $id; ?></h3><div class="number"><?php echo tien($tk['doanh_thu']); ?></div></div></div><?php } ?>
<div class="panel"><h3>Biểu đồ so sánh doanh thu các ca</h3><?php if(count($rows)){ $max=0;foreach($rows as $r){if((float)$r['doanh_thu']>$max)$max=(float)$r['doanh_thu'];} ?><div class="scroll"><div class="chart" style="min-width:<?php echo max(550,count($rows)*66); ?>px"><?php foreach($rows as $r){$pct=$max>0?round(100*(float)$r['doanh_thu']/$max):0; ?><div class="chart-col"><span class="chart-number" title="<?php echo tien($r['doanh_thu']); ?>"><?php echo number_format((float)$r['doanh_thu']/1000,0,',','.'); ?>k</span><div class="chart-bar" style="height:<?php echo max(2,(int)$pct*2); ?>px" title="<?php echo h('Ca '.$r['ma_ca']).': '.tien($r['doanh_thu']); ?>"></div><span class="chart-label"><?php echo h('Ca '.$r['ma_ca']); ?></span></div><?php } ?></div></div><?php }else{ ?><div class="empty">Không có hóa đơn đã thanh toán trong khoảng thời gian này.</div><?php } ?><p class="muted">Đơn vị biểu đồ: nghìn đồng (k). Doanh thu tính từ các đơn thanh toán thành công.</p></div>
<div class="panel"><h3>Bảng thống kê doanh thu theo ca</h3><div class="scroll"><table><tr><th>Mã ca</th><th>Nhân viên</th><th>Ngày bắt đầu</th><th>Số hóa đơn</th><th>Doanh thu</th><th>Chi tiết</th></tr><?php foreach($rows as $r){ ?><tr><td>Ca <?php echo (int)$r['ma_ca']; ?></td><td><?php echo h($r['nhan_vien']); ?></td><td><?php echo h($r['ngay_gio_bat_dau']); ?></td><td><?php echo (int)$r['so_don']; ?></td><td><?php echo tien($r['doanh_thu']); ?></td><td><a href="?ma_ca=<?php echo (int)$r['ma_ca']; ?>">Chọn ca</a></td></tr><?php } ?></table></div></div></div></div></body></html>