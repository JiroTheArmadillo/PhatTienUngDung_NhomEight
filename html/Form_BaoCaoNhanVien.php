<?php
$loaiBaoCao = 'nv';
require_once dirname(__FILE__) . '/../php/XacThuc.php';
require_once dirname(__FILE__) . '/../php/Control/baoCao.php';
$nguoiDung = XacThuc::yeuCauDangNhap();

function bcH($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function bcTien($value) { return number_format((float)$value, 0, ',', '.') . ' đ'; }
function bcNgay($value) { return $value ? date('d/m/Y H:i', strtotime($value)) : 'Chưa kết thúc'; }
function bcGet($key, $default)
{
    return isset($_GET[$key]) && is_scalar($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}
function bcNgan($value)
{
    if ($value >= 1000000000) return number_format($value / 1000000000, 1, ',', '.') . ' tỷ';
    if ($value >= 1000000) return number_format($value / 1000000, 1, ',', '.') . ' tr';
    if ($value >= 1000) return number_format($value / 1000, fmod((float)$value, 1000.0) == 0.0 ? 0 : 1, ',', '.') . 'k';
    return number_format($value, 0, ',', '.');
}
function bcCot($labels, $values, $color, $minWidth)
{
    if (!count($values) || max($values) <= 0) {
        echo '<div class="empty">Chưa có doanh thu trong phạm vi đã chọn.</div>';
        return;
    }
    $count = count($values);
    $width = max($minWidth, $count * 44 + 100);
    $left = 64; $right = 16; $top = 24; $bottom = 224;
    $plotWidth = $width - $left - $right;
    $step = pow(10, floor(log10(max($values))));
    $max = ceil(max($values) / $step) * $step;
    $stride = $plotWidth / $count;
    $barWidth = min(52, $stride * 0.57);
    echo '<div class="chart-scroll"><svg class="chart-svg" style="min-width:' . $width . 'px" viewBox="0 0 ' . $width . ' 292" role="img" aria-label="Biểu đồ cột doanh thu, đơn vị đồng">';
    for ($i = 0; $i <= 4; $i++) {
        $y = $bottom - ($bottom-$top)*$i/4;
        echo '<line class="axis" x1="' . $left . '" y1="' . $y . '" x2="' . ($width-$right) . '" y2="' . $y . '" />';
        echo '<text x="' . ($left-10) . '" y="' . ($y+4) . '" text-anchor="end">' . bcH(bcNgan($max*$i/4)) . '</text>';
    }
    foreach ($values as $i => $value) {
        $height = ($bottom-$top) * $value/$max;
        $x = $left + $stride*$i + ($stride-$barWidth)/2;
        $y = $bottom-$height;
        $center = $left + $stride*($i+0.5);
        echo '<rect class="column" x="' . $x . '" y="' . $y . '" width="' . $barWidth . '" height="' . $height . '" rx="4" fill="' . bcH($color) . '"><title>' . bcH($labels[$i] . ': ' . bcTien($value)) . '</title></rect>';
        if ($count <= 12 && $value > 0) {
            echo '<text class="bar-value" x="' . $center . '" y="' . ($y-8) . '" text-anchor="middle">' . bcH(bcNgan($value)) . '</text>';
        }
        if ($count <= 16 || $i % (int)ceil($count/16) === 0 || $i === $count-1) {
            echo '<text x="' . $center . '" y="245" text-anchor="middle"';
            if ($count > 16) echo ' transform="rotate(-30 ' . $center . ' 245)"';
            echo '>' . bcH($labels[$i]) . '</text>';
        }
    }
    echo '</svg></div>';
}

$vaiTro = isset($nguoiDung['vai_tro']) ? $nguoiDung['vai_tro'] : '';
$laChu = in_array($vaiTro, array('ChuDoanhNghiep','QuanLy'), true);
if (!$laChu && !in_array($vaiTro, array('NhanVien','ThuNgan','ThuKho'), true)) {
    header('HTTP/1.1 403 Forbidden'); exit('Bạn không có quyền xem báo cáo.');
}
if (!isset($loaiBaoCao) || $loaiBaoCao === '') {
    $loaiBaoCao = bcGet('loai', $laChu ? 'tong' : 'ca');
}
if (!in_array($loaiBaoCao, array('tong','ca','nv'), true)) {
    header('HTTP/1.1 400 Bad Request'); exit('Loại báo cáo không hợp lệ.');
}
if (!$laChu && $loaiBaoCao !== 'ca') {
    header('HTTP/1.1 403 Forbidden'); exit('Chỉ chủ doanh nghiệp được xem báo cáo tổng và báo cáo nhân viên.');
}
$tieuDe = $loaiBaoCao === 'tong' ? 'Tổng doanh thu' : ($loaiBaoCao === 'ca' ? 'Doanh thu trong ca' : 'Doanh thu theo nhân viên');
$tuNgay = bcGet('tu_ngay', date('Y-m-01'));
$denNgay = bcGet('den_ngay', date('Y-m-d'));
$nhom = bcGet('nhom', 'ngay');
$ma = bcGet($loaiBaoCao === 'nv' ? 'ma_nv' : 'ma_ca', '');
$error = ''; $dsCa = array(); $dsNhanVien = array(); $report = null;
try {
    $baoCao = new BaoCao();
    if ($loaiBaoCao === 'ca') {
        $dsCa = $baoCao->getDanhSachCa();
        if ($ma === '' && count($dsCa)) $ma = $dsCa[0]['ma_ca'];
    } elseif ($loaiBaoCao === 'nv') {
        $dsNhanVien = $baoCao->getDanhSachNhanVien();
        if ($ma === '' && count($dsNhanVien)) $ma = $dsNhanVien[0]['ma_nv'];
    }
    if ($loaiBaoCao === 'tong' || $ma !== '') {
        $report = $baoCao->getDashboard($loaiBaoCao, $ma, $tuNgay, $denNgay, $nhom);
    }
} catch (Exception $e) {
    error_log('[DashboardBaoCao] ' . $e->getMessage());
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo bcH($tieuDe); ?> - EIGHT STORE</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f6fa;color:#26384e;font:14px/1.5 Arial,Helvetica,sans-serif;display:flex;min-height:100vh}.sidebar{width:230px;flex-shrink:0;padding:24px 14px;background:#1c3049;color:white;display:flex;flex-direction:column}.sidebar .logo{padding:0 16px 22px;font-size:24px;font-weight:bold;letter-spacing:1px}.sidebar a{display:block;padding:12px 14px;margin:3px 0;border-radius:7px;color:#c9d4e4;text-decoration:none;font-size:13px}.sidebar a:hover,.sidebar a.active{background:#2c496a;color:white}.sidebar form{margin-top:auto}.sidebar button{padding:16px 14px;color:#ffbdb5;background:transparent;border:0;cursor:pointer;text-align:left}.main-content{flex:1;min-width:0}.header{background:white;border-bottom:1px solid #e4eaf2;padding:18px 30px;display:flex;justify-content:space-between;gap:15px}.header span{color:#6c7e95}.content{padding:28px;max-width:1500px;margin:auto}.title-row{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:20px}.eyebrow{font-size:10px;letter-spacing:1.6px;font-weight:bold;color:#71839a;margin:0 0 4px}h1{font-size:26px;margin:0}h2{font-size:16px;margin:0}p{margin:7px 0}.muted{color:#78879b;font-size:12px}.tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}.tabs a{padding:9px 15px;text-decoration:none;border:1px solid #d8e1ed;background:white;border-radius:7px;color:#52677f}.tabs a.active{background:#2366b1;border-color:#2366b1;color:white}.panel,.stat{border:1px solid #e0e7f0;background:white;border-radius:10px;margin-bottom:18px}.panel{padding:20px}.filters{display:flex;gap:12px;align-items:end;flex-wrap:wrap}.filters label{display:block;font-size:12px;color:#60738c;margin-bottom:5px}input,select{font:inherit;color:#26384e;background:white;border:1px solid #cad5e4;border-radius:6px;padding:10px 12px;max-width:100%}button,.button{font:inherit;border:0;border-radius:6px;padding:11px 16px;background:#246ab7;color:white;text-decoration:none;cursor:pointer;display:inline-block}.button.secondary{background:#e7edf5;color:#365472}.scope{display:flex;gap:18px;flex-wrap:wrap;margin-top:14px;font-size:12px;color:#60738c}.notice{padding:12px 16px;border-radius:6px;background:#eaf3ff;color:#28568b;margin-bottom:18px}.notice.error{color:#a5323b;background:#fff0f0}.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.stat{padding:20px;border-top:3px solid #2c74c1;min-width:0}.stat:nth-child(2){border-top-color:#169d89}.stat:nth-child(3){border-top-color:#d89a33}.stat:nth-child(4){border-top-color:#7b69bf}.stat .label{font-size:11px;color:#7a8b9f;letter-spacing:.5px}.stat strong{display:block;font-size:25px;margin:10px 0 4px;overflow-wrap:anywhere}.stat small{color:#8794a5;font-size:11px}.charts{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(0,1fr);gap:18px}.panel-heading{display:flex;justify-content:space-between;gap:15px;align-items:center;margin-bottom:12px}.badge{font-size:11px;background:#edf4fb;color:#3c75af;border-radius:20px;padding:4px 10px;white-space:nowrap}.chart-scroll{overflow-x:auto;max-width:100%}.chart-svg{display:block;width:100%;height:auto;max-height:320px}.chart-svg text{font-family:Arial,Helvetica,sans-serif;fill:#74859a;font-size:11px}.chart-svg .axis{stroke:#e7edf4;stroke-width:1}.chart-svg .column{transition:opacity .15s}.chart-svg .column:hover{opacity:.75}.chart-svg .bar-value{fill:#365471;font-size:10px}.empty{min-height:240px;display:flex;align-items:center;justify-content:center;color:#7b8a9c;text-align:center;background:#fbfcfe;border-radius:7px;padding:20px}.payment-values{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;border-top:1px solid #edf1f6;margin-top:8px;padding-top:12px;font-size:11px}.payment-values b{display:block;font-size:13px;color:#385471}.table-scroll{overflow-x:auto;max-height:450px}table{border-collapse:collapse;width:100%;white-space:nowrap;font-size:13px}th,td{padding:12px 13px;border-bottom:1px solid #e6edf4;text-align:left}th{color:#6f8197;font-size:11px;background:#f8fafc}thead th{position:sticky;top:0}.num{text-align:right}.method{background:#edf6f3;color:#27816d;padding:4px 9px;border-radius:20px;font-size:11px}.table-total{display:flex;justify-content:space-between;padding-top:14px;color:#47617e}.footnote{font-size:11px;color:#8492a3;margin-top:16px;line-height:1.7}@media(max-width:1100px){.charts{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.stat strong{font-size:23px}}@media(max-width:700px){body{display:block}.sidebar{width:100%;padding:14px}.sidebar .logo{padding:0 10px 12px}.sidebar nav{display:flex;flex-wrap:wrap}.sidebar a{padding:8px 10px}.sidebar button{padding:8px 10px}.header{padding:14px 18px;flex-wrap:wrap}.content{padding:18px}.title-row{align-items:start}.title-row .button{font-size:12px;padding:9px 12px}h1{font-size:22px}.stats{gap:10px}.stat{padding:14px}.stat strong{font-size:20px}.panel{padding:15px}.filters>div{max-width:100%}}@media print{body{display:block;background:white}.sidebar,.header,.tabs,.filters,.print-hide{display:none}.content{padding:0}.stats{grid-template-columns:repeat(4,minmax(0,1fr))}.charts{display:block}.panel,.stat{break-inside:avoid}.table-scroll{max-height:none;overflow:visible}.chart-scroll{overflow:visible}.chart-svg{min-width:0!important;max-height:none}.stat strong{font-size:18px}.footnote{color:#555}thead{display:table-header-group}table{white-space:normal}}

</style></head>
<body class="giao-dien-chung">
<aside class="sidebar"><div class="logo">EIGHT STORE</div><nav>
<a href="GD_TrangChu.php">Trang chủ</a><a href="GD_QuanLyBanHang.php">Quản lý bán hàng</a>
<?php if ($laChu): ?><a href="GD_QuanLyNhanVien.php">Quản lý nhân viên</a><?php endif; ?>
<a href="GD_QuanLyNhaCungCap.php">Quản lý nhà cung cấp</a><a href="GD_LapPhieuDatHang.php">Lập phiếu đặt hàng</a>
<?php if ($laChu): ?><a href="GD_DuyetPhieuDatHang.php">Duyệt phiếu đặt hàng</a><?php endif; ?>
<a href="GD_XuLyHoanTra.php">Xử lý hoàn trả</a><a href="GD_BaoCao.php" class="active" aria-current="page">Báo cáo doanh thu</a>
</nav><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?php echo bcH(XacThuc::layTokenDangXuat()); ?>"><button type="submit">Đăng xuất</button></form></aside>
<main class="main-content"><header class="header"><span>EightMart / Báo cáo</span><b><?php echo bcH(isset($nguoiDung['ho_ten']) ? $nguoiDung['ho_ten'] : 'Người dùng'); ?></b></header>
<div class="content"><div class="title-row"><div><p class="eyebrow">DASHBOARD KINH DOANH</p><h1><?php echo bcH($tieuDe); ?></h1></div><button type="button" class="print-hide" onclick="window.print()">In báo cáo</button></div>
<nav class="tabs" aria-label="Loại báo cáo">
<?php if ($laChu): ?><a href="GD_BaoCao.php?loai=tong" class="<?php echo $loaiBaoCao === 'tong' ? 'active' : ''; ?>">Tổng doanh thu</a><?php endif; ?>
<a href="Form_BaoCaoCa.php" class="<?php echo $loaiBaoCao === 'ca' ? 'active' : ''; ?>">Doanh thu trong ca</a>
<?php if ($laChu): ?><a href="Form_BaoCaoNhanVien.php" class="<?php echo $loaiBaoCao === 'nv' ? 'active' : ''; ?>">Theo nhân viên</a><?php endif; ?>
</nav>
<section class="panel"><form class="filters" method="get" action="GD_BaoCao.php">
<input type="hidden" name="loai" value="<?php echo bcH($loaiBaoCao); ?>">
<?php if ($loaiBaoCao === 'ca'): ?>
<div><label for="ma_ca">Ca làm việc</label><select name="ma_ca" id="ma_ca" required>
<?php if (!$dsCa): ?><option value="">Chưa có ca làm việc</option><?php endif; ?>
<?php foreach ($dsCa as $ca): ?><option value="<?php echo (int)$ca['ma_ca']; ?>" <?php echo (string)$ma === (string)$ca['ma_ca'] ? 'selected' : ''; ?>><?php echo bcH('Ca #' . $ca['ma_ca'] . ' · ' . bcNgay($ca['bat_dau']) . ' · NV' . $ca['ma_nv_mo_ca']); ?></option><?php endforeach; ?>
</select></div>
<?php else: ?>
<?php if ($loaiBaoCao === 'nv'): ?><div><label for="ma_nv">Nhân viên</label><select name="ma_nv" id="ma_nv" required>
<?php if (!$dsNhanVien): ?><option value="">Chưa có nhân viên</option><?php endif; ?>
<?php foreach ($dsNhanVien as $nv): ?><option value="<?php echo (int)$nv['ma_nv']; ?>" <?php echo (string)$ma === (string)$nv['ma_nv'] ? 'selected' : ''; ?>><?php echo bcH('NV' . $nv['ma_nv'] . ' · ' . $nv['ho_ten']); ?></option><?php endforeach; ?>
</select></div><?php endif; ?>
<div><label for="tu_ngay">Từ ngày</label><input id="tu_ngay" name="tu_ngay" type="date" required value="<?php echo bcH($tuNgay); ?>"></div>
<div><label for="den_ngay">Đến ngày</label><input id="den_ngay" name="den_ngay" type="date" required value="<?php echo bcH($denNgay); ?>"></div>
<div><label for="nhom">Thống kê theo</label><select name="nhom" id="nhom"><option value="ngay">Ngày</option><option value="thang" <?php echo $nhom === 'thang' ? 'selected' : ''; ?>>Tháng</option></select></div>
<?php endif; ?>
<button type="submit">Xem báo cáo</button><a class="button secondary" href="GD_BaoCao.php?loai=<?php echo bcH($loaiBaoCao); ?>">Đặt lại</a></form>
<?php if ($report): $info = $report['thong_tin']; ?>
<div class="scope">
<?php if ($loaiBaoCao === 'ca'): ?><span><b>Ca #<?php echo (int)$info['ma_ca']; ?></b></span><span>Mở: <?php echo bcH(bcNgay($info['bat_dau'])); ?></span><span>Đóng: <?php echo bcH(bcNgay($info['ket_thuc'])); ?></span><span>Nhân viên mở: NV<?php echo (int)$info['ma_nv_mo_ca']; ?></span>
<?php else: ?><span>Kỳ báo cáo: <b><?php echo bcH(date('d/m/Y', strtotime($tuNgay))); ?> – <?php echo bcH(date('d/m/Y', strtotime($denNgay))); ?></b></span><?php if ($info): ?><span>Nhân viên: <b><?php echo bcH($info['ho_ten']); ?></b></span><?php endif; ?><span>Tính theo thời điểm thanh toán thành công</span><?php endif; ?>
</div><?php endif; ?></section>
<?php if ($error !== ''): ?><div class="notice error" role="alert"><?php echo bcH($error); ?></div>
<?php elseif (!$report): ?><div class="notice">Chưa có dữ liệu <?php echo $loaiBaoCao === 'ca' ? 'ca làm việc' : 'nhân viên'; ?> để xem báo cáo.</div>
<?php else: $stats = $report['thong_ke']; ?>
<?php if ($loaiBaoCao === 'ca' && $info['trang_thai'] === 'DangMo'): ?><div class="notice">Ca đang mở, số liệu tạm thời. Bấm “Xem báo cáo” để lấy dữ liệu mới nhất.</div><?php endif; ?>
<div class="stats">
<div class="stat"><span class="label">TỔNG DOANH THU</span><strong><?php echo bcTien($stats['doanh_thu']); ?></strong><small>Hóa đơn hoàn thành và đã thanh toán</small></div>
<div class="stat"><span class="label">SỐ HÓA ĐƠN</span><strong><?php echo (int)$stats['so_don']; ?></strong><small>Mỗi hóa đơn được tính một lần</small></div>
<div class="stat"><span class="label">TIỀN MẶT</span><strong><?php echo bcTien($stats['tien_mat']); ?></strong><small>Doanh thu thanh toán bằng tiền mặt</small></div>
<div class="stat"><span class="label">CHUYỂN KHOẢN</span><strong><?php echo bcTien($stats['chuyen_khoan']); ?></strong><small>Giao dịch đã thanh toán</small></div>
</div>
<div class="charts">
<section class="panel"><div class="panel-heading"><h2>Doanh thu theo <?php echo $loaiBaoCao === 'ca' ? 'giờ' : ($nhom === 'thang' ? 'tháng' : 'ngày'); ?></h2><span class="badge">Đơn vị: VNĐ</span></div>
<?php
$labels = array(); $values = array();
foreach ($report['bieu_do'] as $moc => $tien) {
    if ($loaiBaoCao === 'ca') $label = $moc === 'Chua co thoi gian' ? 'Chưa rõ giờ' : date('d/m H\h', strtotime($moc));
    else $label = $nhom === 'thang' ? date('m/Y', strtotime($moc . '-01')) : date('d/m', strtotime($moc));
    $labels[] = $label; $values[] = $tien;
}
bcCot($labels, $values, '#3478c7', 640);
?>
<p class="muted"><?php echo $loaiBaoCao === 'ca' ? 'Hiển thị các giờ có thanh toán. Di chuột vào cột để xem số tiền.' : 'Ngày/tháng không có giao dịch được tính bằng 0. Di chuột vào cột để xem số tiền.'; ?></p></section>
<section class="panel"><div class="panel-heading"><h2>Theo phương thức thanh toán</h2></div>
<?php bcCot(array('Tiền mặt','Chuyển khoản','Khác'), array($stats['tien_mat'],$stats['chuyen_khoan'],$stats['khac']), '#20a58b', 340); ?>
<div class="payment-values"><span>Tiền mặt<b><?php echo bcTien($stats['tien_mat']); ?></b></span><span>Chuyển khoản<b><?php echo bcTien($stats['chuyen_khoan']); ?></b></span><span>Khác<b><?php echo bcTien($stats['khac']); ?></b></span></div>
</section></div>
<section class="panel"><div class="panel-heading"><h2>Hóa đơn đã thanh toán</h2><span class="badge"><?php echo (int)$stats['so_don']; ?> hóa đơn</span></div>
<div class="table-scroll"><table><thead><tr><th>HÓA ĐƠN</th><th>THANH TOÁN LÚC</th><th>CA</th><th>NHÂN VIÊN</th><th>PHƯƠNG THỨC</th><th class="num">DOANH THU</th></tr></thead><tbody>
<?php foreach ($report['hoa_don'] as $hd): ?><tr><td>HD<?php echo (int)$hd['ma_hoa_don']; ?></td><td><?php echo $hd['thoi_gian'] ? bcH(bcNgay($hd['thoi_gian'])) : 'Chưa có thời gian'; ?></td><td>#<?php echo (int)$hd['ma_ca']; ?></td><td><?php echo bcH($hd['nhan_vien']); ?></td><td><span class="method"><?php echo bcH($hd['phuong_thuc'] === 'TienMat' ? 'Tiền mặt' : ($hd['phuong_thuc'] === 'ChuyenKhoan' ? 'Chuyển khoản' : 'Khác')); ?></span></td><td class="num"><?php echo bcTien($hd['tong_tien']); ?></td></tr><?php endforeach; ?>
<?php if (!$report['hoa_don']): ?><tr><td colspan="6">Không có hóa đơn đã thanh toán trong phạm vi đã chọn.</td></tr><?php endif; ?>
</tbody></table></div><div class="table-total"><span>Trung bình / hóa đơn: <b><?php echo bcTien($stats['trung_binh']); ?></b></span><b>Tổng: <?php echo bcTien($stats['doanh_thu']); ?></b></div></section>
<p class="footnote">Doanh thu tính theo tổng tiền hóa đơn, chưa trừ các khoản hoàn tiền. Báo cáo chỉ tổng hợp hóa đơn HoanThanh có thanh toán DaThanhToan. Mỗi hóa đơn dùng phương thức và thời điểm của lần thanh toán thành công cuối cùng.</p>
<?php endif; ?></div></main></body></html>
