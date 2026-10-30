<?php
require_once dirname(__FILE__).'/../php/XacThuc.php'; XacThuc::yeuCauDangNhap();
require_once dirname(__FILE__).'/../php/Control/KhachHangBaoCao.php';
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function tien($v){return number_format((float)$v,0,',','.').' đ';}
$error='';$rows=array();$tong=array('so_don'=>0,'doanh_thu'=>0);
$loai=isset($_GET['loai'])?$_GET['loai']:'thang';if(!in_array($loai,array('ngay','thang','quy','nam')))$loai='thang';
$nam=isset($_GET['nam'])?(int)$_GET['nam']:(int)date('Y');
$thang=isset($_GET['thang'])?(int)$_GET['thang']:(int)date('n');
$quy=isset($_GET['quy'])?(int)$_GET['quy']:(int)ceil(date('n')/3);
$ngay=isset($_GET['ngay'])?$_GET['ngay']:date('Y-m-d');
try{$control=new KhachHangBaoCao();$data=$control->baoCaoTheoKy($loai,$nam,$thang,$quy,$ngay);$rows=$data['rows'];$tong=$data['tong'];}
catch(Exception $e){$error=$e->getMessage();}
?><!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Báo cáo tổng doanh thu - EIGHT STORE</title><style>
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

    <div class="main-content"><div class="header"><div class="user-info">EIGHT STORE</div></div><div class="content"><div class="heading"><h2>Báo cáo tổng doanh thu</h2><a class="btn secondary" href="GD_QuanLyBaoCao.php">← Quay lại quản lý báo cáo</a></div>
<div class="panel"><h3>Bộ lọc kỳ báo cáo</h3><form method="get" class="toolbar"><label>Thống kê theo<select name="loai"><option value="ngay" <?php if($loai==='ngay')echo 'selected'; ?>>Ngày</option><option value="thang" <?php if($loai==='thang')echo 'selected'; ?>>Tháng</option><option value="quy" <?php if($loai==='quy')echo 'selected'; ?>>Quý</option><option value="nam" <?php if($loai==='nam')echo 'selected'; ?>>Năm</option></select></label><label>Ngày<input type="date" name="ngay" value="<?php echo h($ngay); ?>"></label><label>Tháng<select name="thang"><?php for($i=1;$i<=12;$i++){ ?><option value="<?php echo $i; ?>" <?php if($i==$thang)echo 'selected'; ?>>Tháng <?php echo $i; ?></option><?php } ?></select></label><label>Quý<select name="quy"><?php for($i=1;$i<=4;$i++){ ?><option value="<?php echo $i; ?>" <?php if($i==$quy)echo 'selected'; ?>>Quý <?php echo $i; ?></option><?php } ?></select></label><label>Năm<input type="number" name="nam" min="2000" max="2100" value="<?php echo (int)$nam; ?>"></label><button type="submit">Xem báo cáo</button></form><p class="muted">Chọn kỳ cần thống kê. Ngày: theo giờ; tháng: theo ngày; quý: theo tháng; năm: theo tháng.</p></div>
<?php if($error!==''){ ?><p class="panel error"><?php echo h($error); ?></p><?php } ?>
<div class="stats-container"><div class="stat-box"><h3>Số hóa đơn đã thanh toán</h3><div class="number"><?php echo (int)$tong['so_don']; ?></div></div><div class="stat-box" style="border-left-color:#3498db"><h3>Doanh thu kỳ báo cáo</h3><div class="number"><?php echo tien($tong['doanh_thu']); ?></div></div></div>
<div class="panel"><h3>Biểu đồ doanh thu theo <?php echo h($loai); ?></h3><?php if(count($rows)){ $max=0;foreach($rows as $r){if((float)$r['doanh_thu']>$max)$max=(float)$r['doanh_thu'];} ?><div class="scroll"><div class="chart" style="min-width:<?php echo max(550,count($rows)*66); ?>px"><?php foreach($rows as $r){$pct=$max>0?round(100*(float)$r['doanh_thu']/$max):0; ?><div class="chart-col"><span class="chart-number" title="<?php echo tien($r['doanh_thu']); ?>"><?php echo number_format((float)$r['doanh_thu']/1000,0,',','.'); ?>k</span><div class="chart-bar" style="height:<?php echo max(2,(int)$pct*2); ?>px" title="<?php echo h($r['nhan']).': '.tien($r['doanh_thu']); ?>"></div><span class="chart-label"><?php echo h($r['nhan']); ?></span></div><?php } ?></div></div><?php }else{ ?><div class="empty">Không có hóa đơn đã thanh toán trong khoảng thời gian này.</div><?php } ?><p class="muted">Đơn vị biểu đồ: nghìn đồng (k). Doanh thu tính từ các đơn thanh toán thành công.</p></div><div class="panel"><h3>Bảng chi tiết doanh thu</h3><div class="scroll"><table><thead><tr><th>Kỳ</th><th>Số hóa đơn</th><th>Doanh thu</th></tr></thead><tbody><?php foreach($rows as $r){ ?><tr><td><?php echo h($r['nhan']); ?></td><td><?php echo (int)$r['so_don']; ?></td><td><?php echo tien($r['doanh_thu']); ?></td></tr><?php } ?><tr><th>Tổng</th><th><?php echo (int)$tong['so_don']; ?></th><th><?php echo tien($tong['doanh_thu']); ?></th></tr></tbody></table></div></div></div></div></body></html>