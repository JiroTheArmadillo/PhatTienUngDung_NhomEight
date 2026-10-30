<?php
require_once dirname(__FILE__) . '/../php/XacThuc.php';
$nguoiDungDangNhap = XacThuc::yeuCauDangNhap();
require_once dirname(__FILE__) . '/../php/Control/NhaCungCapController.php';
$controller = new NhaCungCapController();
$vm = $controller->xuLy();
$self = basename($_SERVER['SCRIPT_NAME']);
function h($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function trangThaiNcc($status) { return $status === 'HoatDong' ? 'Hoạt động' : 'Ngừng hoạt động'; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý nhà cung cấp - EIGHT STORE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { display: flex; min-height: 100vh; color: #333; font-family: Arial, sans-serif; }
        .sidebar { width: 260px; flex-shrink: 0; background: #2c3e50; color: #fff; display: flex; flex-direction: column; }
        .sidebar .logo { padding: 20px; font-size: 20px; font-weight: bold; background: #1a252f; text-align: center; }
        .sidebar a { padding: 15px 20px; color: #bdc3c7; text-decoration: none; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: #fff; }
        .sidebar form { margin-top: auto; }
        .main-content { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .header { background: #fff; padding: 15px 30px; border-bottom: 1px solid #e0e0e0; text-align: right; }
        .content { padding: 25px 30px; }
        h2 { margin-bottom: 15px; color: #2c3e50; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .alert { padding: 12px 15px; border-radius: 4px; margin: 10px 0; font-size: 14px; }
        .alert-success { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; }
        .alert-danger { background: #fdedec; color: #c0392b; border: 1px solid #f5b7b1; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: top; }
        th { background: #f8f9fa; color: #2c3e50; }
        tr:hover { background: #f1f5f9; }
        .form-ncc { max-width: 520px; background: #fff; padding: 15px 0; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 9px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .form-group textarea { min-height: 70px; resize: vertical; }
        .form-group .error-text { color: #e74c3c; font-size: 12px; margin-top: 4px; }
        .has-error { border-color: #e74c3c !important; }
        .form-actions { text-align: right; margin-top: 18px; }
        .modal-overlay { display: none; position: fixed; z-index: 9999; inset: 0; background: rgba(0,0,0,.5); align-items: center; justify-content: center; padding: 16px; }
        .modal-overlay.is-open { display: flex; }
        .modal-box { width: 100%; max-width: 430px; border-radius: 9px; padding: 24px; background: #fff; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        @media (max-width: 840px) { body { display: block; } .sidebar { width: 100%; } .main-content { width: 100%; } .table-wrap { overflow-x: auto; } table { min-width: 850px; } }
    </style>
    <link rel="stylesheet" href="giao-dien.css">
</head>
<body>
<div class="sidebar">
    <div class="logo">EIGHT STORE</div>
    
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
        <form method="post" action="logout.php">
        <input type="hidden" name="csrf_token" value="<?php echo h(XacThuc::layTokenDangXuat()); ?>">
        <button type="submit" style="width:100%;padding:15px 20px;border:0;background:transparent;color:#ffb4a8;text-align:left;font:inherit;cursor:pointer">Đăng xuất</button>
    </form>
</div>
<div class="main-content">
    <div class="header"><span>Chào mừng, <b><?php echo h($nguoiDungDangNhap['ho_ten']); ?> (<?php echo h($nguoiDungDangNhap['vai_tro'] === 'ChuDoanhNghiep' ? 'Chủ doanh nghiệp' : 'Nhân viên'); ?>)</b></span></div>
    <div class="content">
        <h2>Danh sách nhà cung cấp</h2>
        <?php if ($vm['thongBao']): ?><div class="alert alert-<?php echo h($vm['thongBao'][0]); ?>"><?php echo h($vm['thongBao'][1]); ?></div><?php endif; ?>
        <div style="margin:15px 0"><a href="<?php echo $self; ?>?action=them" class="btn btn-primary">+ Thêm nhà cung cấp mới</a></div>

        <?php if ($vm['form']): $f = $vm['form']; $e = $f['errors']; $v = $f['values']; ?>
        <div class="form-ncc">
            <h3 style="margin-bottom:12px"><?php echo $f['mode'] === 'them' ? 'Thêm nhà cung cấp mới' : 'Cập nhật nhà cung cấp'; ?></h3>
            <?php if ($f['loiChung']): ?><div class="alert alert-danger"><?php echo h($f['loiChung']); ?></div><?php endif; ?>
            <?php if ($e): ?><div class="alert alert-danger">Vui lòng kiểm tra lại các trường được đánh dấu.</div><?php endif; ?>
            <form method="post" action="<?php echo $self; ?>">
                <input type="hidden" name="action" value="<?php echo $f['mode'] === 'sua' ? 'sua' : 'them'; ?>">
                <?php if ($f['mode'] === 'sua'): ?><input type="hidden" name="ma_nha_cung_cap" value="<?php echo (int)$f['ma']; ?>"><?php endif; ?>
                <div class="form-group"><label>Tên nhà cung cấp *</label><input type="text" name="ten_nha_cung_cap" value="<?php echo h($v['ten_nha_cung_cap']); ?>" maxlength="150" class="<?php echo isset($e['ten'])?'has-error':''; ?>"><?php if(isset($e['ten'])): ?><div class="error-text"><?php echo h($e['ten']); ?></div><?php endif; ?></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo h($v['email']); ?>" maxlength="120" class="<?php echo isset($e['email'])?'has-error':''; ?>"><?php if(isset($e['email'])): ?><div class="error-text"><?php echo h($e['email']); ?></div><?php endif; ?></div>
                <div class="form-group"><label>Địa chỉ</label><textarea name="dia_chi" maxlength="255"><?php echo h($v['dia_chi']); ?></textarea><?php if(isset($e['dia_chi'])): ?><div class="error-text"><?php echo h($e['dia_chi']); ?></div><?php endif; ?></div>
                <div class="form-group"><label>Loại sản phẩm cung cấp</label><input type="text" name="loai_san_pham_cung_cap" value="<?php echo h($v['loai_san_pham_cung_cap']); ?>" maxlength="200" class="<?php echo isset($e['loai_san_pham'])?'has-error':''; ?>"><?php if(isset($e['loai_san_pham'])): ?><div class="error-text"><?php echo h($e['loai_san_pham']); ?></div><?php endif; ?></div>
                <div class="form-group"><label>Trạng thái *</label><select name="trang_thai" class="<?php echo isset($e['trang_thai'])?'has-error':''; ?>"><option value="HoatDong" <?php echo $v['trang_thai']==='HoatDong'?'selected':''; ?>>Hoạt động</option><option value="NgungHoatDong" <?php echo $v['trang_thai']==='NgungHoatDong'?'selected':''; ?>>Ngừng hoạt động</option></select><?php if(isset($e['trang_thai'])): ?><div class="error-text"><?php echo h($e['trang_thai']); ?></div><?php endif; ?></div>
                <div class="form-actions"><button type="submit" class="btn btn-success">Lưu</button> <a href="<?php echo $self; ?>" class="btn btn-secondary">Hủy</a></div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($vm['loiTai']): ?><div class="alert alert-danger"><?php echo h($vm['loiTai']); ?></div><?php endif; ?>
        <div class="table-wrap"><table><thead><tr><th>Mã NCC</th><th>Tên nhà cung cấp</th><th>Email</th><th>Địa chỉ</th><th>Loại sản phẩm</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
        <?php if (!$vm['danhSach']): ?><tr><td colspan="7" style="text-align:center;color:#777">Chưa có nhà cung cấp</td></tr>
        <?php else: foreach ($vm['danhSach'] as $ncc): ?>
        <tr><td><?php echo h($ncc->getMaHienThi()); ?></td><td><?php echo h($ncc->getTen()); ?></td><td><?php echo h($ncc->getEmail()); ?></td><td><?php echo h($ncc->getDiaChi()); ?></td><td><?php echo h($ncc->getLoaiSanPham()); ?></td><td><?php echo h(trangThaiNcc($ncc->getTrangThai())); ?></td><td><a href="<?php echo $self; ?>?action=sua&amp;id=<?php echo (int)$ncc->getMa(); ?>" class="btn btn-primary">Sửa</a> <button type="button" class="btn btn-danger btn-xoa" data-ma="<?php echo (int)$ncc->getMa(); ?>" data-ten="<?php echo h($ncc->getTen()); ?>">Xóa</button></td></tr>
        <?php endforeach; endif; ?></tbody></table></div>
    </div>
</div>
<div class="modal-overlay" id="modalXoa" role="dialog" aria-modal="true"><div class="modal-box"><h3>Xác nhận xóa</h3><p id="noiDungXoa">Bạn có chắc muốn xóa nhà cung cấp này?</p><form method="post" action="<?php echo $self; ?>" id="formXoa"><input type="hidden" name="action" value="xoa"><input type="hidden" name="ma_nha_cung_cap" id="maXoa"><div class="modal-actions"><button type="button" class="btn btn-secondary" id="nutHuy">Hủy</button><button type="submit" class="btn btn-danger">Xóa</button></div></form></div></div>
<script>
(function(){ var modal=document.getElementById('modalXoa'), ma=document.getElementById('maXoa'), noiDung=document.getElementById('noiDungXoa'); document.querySelectorAll('.btn-xoa').forEach(function(btn){btn.addEventListener('click',function(){ma.value=this.getAttribute('data-ma');noiDung.textContent='Bạn có chắc muốn xóa nhà cung cấp "'+this.getAttribute('data-ten')+'"?';modal.classList.add('is-open');});}); document.getElementById('nutHuy').addEventListener('click',function(){modal.classList.remove('is-open');}); modal.addEventListener('click',function(e){if(e.target===modal)modal.classList.remove('is-open');});})();
</script>
</body>
</html>

