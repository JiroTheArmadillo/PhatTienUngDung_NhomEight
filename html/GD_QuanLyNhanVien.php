<?php
// BỔ SUNG ĐĂNG NHẬP: bắt buộc đăng nhập trước khi truy cập quản lý nhân viên.
require_once dirname(__FILE__) . '/../php/XacThuc.php';
$nguoiDungDangNhap = XacThuc::yeuCauDangNhap();
// Nạp lớp điều khiển, xử lý Thêm / Sửa / Xóa / Xem danh sách nhân viên
require_once dirname(__FILE__) . '/../php/Control/NhanVienController.php';
$controller = new NhanVienController();
$vm = $controller->xuLy();
$self = basename($_SERVER['SCRIPT_NAME']);   // tên file hiện tại (.php)
function h($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title>Hệ Thống Quản Lý Cửa Hàng</title>
        <!-- <link rel="stylesheet" href="css/style.css"> -->
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
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }

    /* Header nằm trên cùng */
    .header {
        background: white;
        padding: 15px 30px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        border-bottom: 1px solid #e0e0e0;
    }

    .header .user-info {
        font-size: 14px;
    }

    /* Content bên trong */
    .content {
        padding: 25px 30px;
    }

    .content h2 {
        font-size: 22px;
        margin-bottom: 15px;
        color: #2c3e50;
    }

    table {
        width: 100%;
        border-collapse: collapse; /*để mỗi ô trong bảng liên kết không bị tách ra  */
        background: white;
        margin-top: 15px;
        
        
    }

    th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }

    th {
        background-color: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
    }

    tr:hover {
        background-color: #f1f5f9;
    }


    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary { background-color: #3498db; color: white; }
    .btn-success { background-color: #2ecc71; color: white; }
    .btn-danger  { background-color: #e74c3c; color: white; }
    .btn-secondary { background-color: #95a5a6; color: white; }






    /* ---- Bổ sung cho chức năng: thông báo + biểu mẫu thêm/sửa ---- */
    .alert { padding: 12px 15px; border-radius: 4px; margin: 10px 0; font-size: 14px; }
    .alert-success { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; }
    .alert-danger  { background: #fdedec; color: #c0392b; border: 1px solid #f5b7b1; }
    .form-nv { background: white; padding: 15px 0; max-width: 420px; }
    .form-nv h3 { font-size: 16px; margin-bottom: 12px; color: #2c3e50; }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
    .form-group input, .form-group select { width: 100%; padding: 8px 10px; border: 1px solid #ccc;
                     border-radius: 4px; font-size: 14px; box-sizing: border-box; }
    .form-group .error-text { color: #e74c3c; font-size: 12px; margin-top: 4px; }
    .form-group input.has-error, .form-group select.has-error { border-color: #e74c3c; }
    .form-actions { text-align: right; margin-top: 18px; }

    /* BỔ SUNG: hộp thoại xác nhận xóa tự thiết kế, không dùng confirm() của trình duyệt. */
    .modal-overlay { display: none; position: fixed; z-index: 9999; top: 0; right: 0;
        bottom: 0; left: 0; background: rgba(0,0,0,.5); align-items: center;
        justify-content: center; padding: 16px; }
    .modal-overlay.is-open { display: flex; }
    .modal-box { width: 100%; max-width: 430px; box-sizing: border-box;
        border-radius: 9px; padding: 24px; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
    .modal-box h3 { color: #2c3e50; font-size: 20px; margin-bottom: 12px; }
    .modal-box p { line-height: 1.6; font-size: 14px; margin-bottom: 22px; overflow-wrap: anywhere; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
    .modal-actions .btn { min-width: 85px; }
    .form-group input:focus, .form-group select:focus { outline: 2px solid #3498db; outline-offset: 1px; }
    .form-group input.has-error:focus, .form-group select.has-error:focus { outline-color: #e74c3c; }

     </style>
    </head>
    <body>

        <!-- Sidebar Menu -->
        <div class="sidebar">
             <!-- Tro ve trang chu; quan ly nhan vien chi hien khi chon menu nay. -->
             <div class="logo">EIGHT STORE</div>
             <a href="GD_TrangChu.php">dashboard</a>
         <a href="GD_QuanLyBanHang.html">Quản lý Bán Hàng</a>
        <a href="GD_QuanLyNhanVien.php" class="active" aria-current="page">Quản lý nhân viên</a>
        <a href="GD_LapPhieuDatHang.html">Lập phiếu đặt hàng</a>
        <a href="GD_DuyetPhieuDatHang.html">Duyệt phiếu đặt hàng</a>
        <a href="GD_XuLyHoanTra.html">Xử lý hoàn trả</a>
        <!-- UC02: nút Đăng xuất gửi POST, kết thúc phiên và quay về đăng nhập. -->
        <form method="post" action="logout.php" style="margin-top: auto; margin-bottom: 0;">
            <input type="hidden" name="csrf_token" value="<?php echo h(XacThuc::layTokenDangXuat()); ?>">
            <button type="submit" style="display: block; width: 100%; padding: 15px 20px;
                border: none; background: transparent; color: #ffb4a8; text-align: left;
                font: inherit; font-size: 14px; cursor: pointer;">Đăng xuất</button>
        </form>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <!-- BỔ SUNG: hiển thị họ tên và vai trò thực của tài khoản đăng nhập. -->
                <span>Chào mừng, <b><?php echo h($nguoiDungDangNhap['ho_ten']); ?> (<?php echo h($nguoiDungDangNhap['vai_tro'] === 'ChuDoanhNghiep' ? 'Chủ doanh nghiệp' : 'Nhân viên'); ?>)</b></span>
            </div>

            <div class="content">
                <h2>Danh sách Nhân viên</h2>

                <?php if ($vm['thongBao']): ?>
                <div class="alert alert-<?php echo h($vm['thongBao'][0]); ?>"><?php echo h($vm['thongBao'][1]); ?></div>
                <?php endif; ?>

                <!-- Nút thêm mới -->
                <div style="margin: 15px 0;">
                    <a href="<?php echo $self; ?>?action=them" class="btn btn-primary">+ Thêm
                        nhân viên mới</a>
                </div>

        <?php if ($vm['form']): $f = $vm['form']; $e = $f['errors']; $v = $f['values']; ?>
        <div class="form-nv">
                <h3><?php echo $f['mode'] === 'them' ? 'Thêm nhân viên mới' : 'Cập nhật thông tin nhân viên'; ?></h3>
                <?php if ($f['loiChung']): ?><div class="alert alert-danger"><?php echo h($f['loiChung']); ?></div><?php endif; ?>
                <?php if ($e): ?><div class="alert alert-danger">Vui lòng kiểm tra lại thông tin đã nhập.</div><?php endif; ?>
                <form id="nhanVienForm" method="post" action="<?php echo $self; ?>" novalidate>
                    <input type="hidden" name="action" value="<?php echo h($f['mode']); ?>">
                    <input type="hidden" name="ma_nhan_vien" value="<?php echo (int) $f['ma']; ?>">

                    <div class="form-group">
                        <label>Họ và tên *</label>
                        <input type="text" name="ten" maxlength="120" required value="<?php echo h($v['ten']); ?>"<?php if (isset($e['ten'])) echo ' class="has-error"'; ?>>
                        <div class="error-text" data-error-for="ten"><?php if (isset($e['ten'])) echo h($e['ten']); ?></div>
                    </div>
                    <div class="form-group">
                        <label>CCCD *</label>
                        <input type="text" name="cccd" maxlength="12" inputmode="numeric" required value="<?php echo h($v['cccd']); ?>"<?php if (isset($e['cccd'])) echo ' class="has-error"'; ?>>
                        <div class="error-text" data-error-for="cccd"><?php if (isset($e['cccd'])) echo h($e['cccd']); ?></div>
                    </div>
                    <div class="form-group">
                        <label>Giới tính *</label>
                        <select name="gioi_tinh" required<?php if (isset($e['gioi_tinh'])) echo ' class="has-error"'; ?>>
                            <option value="">-- Chọn giới tính --</option>
                            <option value="Nam"<?php if ($v['gioi_tinh'] === 'Nam') echo ' selected'; ?>>Nam</option>
                            <option value="Nu"<?php if ($v['gioi_tinh'] === 'Nu') echo ' selected'; ?>>Nữ</option>
                        </select>
                        <div class="error-text" data-error-for="gioi_tinh"><?php if (isset($e['gioi_tinh'])) echo h($e['gioi_tinh']); ?></div>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại *</label>
                        <input type="text" name="sdt" maxlength="10" inputmode="numeric" required value="<?php echo h($v['sdt']); ?>"<?php if (isset($e['sdt'])) echo ' class="has-error"'; ?>>
                        <div class="error-text" data-error-for="sdt"><?php if (isset($e['sdt'])) echo h($e['sdt']); ?></div>
                    </div>

                    <!-- BỔ SUNG: ngày sinh phục vụ tạo mật khẩu mặc định (ddmmyyyy). -->
                    <div class="form-group">
                        <label for="ngay_sinh">Ngày sinh *</label>
                        <input type="date" id="ngay_sinh" name="ngay_sinh" required
                            max="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo h($v['ngay_sinh']); ?>"<?php if (isset($e['ngay_sinh'])) echo ' class="has-error"'; ?>>
                        <div class="error-text" data-error-for="ngay_sinh"><?php if (isset($e['ngay_sinh'])) echo h($e['ngay_sinh']); ?></div>
                    </div>

                    <div class="form-actions">
                        <a href="<?php echo $self; ?>" class="btn btn-secondary">Hủy</a>
                        <button type="submit" class="btn btn-success">Lưu</button>
                    </div>
                </form>
        </div>
        <?php endif; ?>

                <!-- Bảng hiển thị -->
                <table>
                    <thead>
                        <tr>
                            <th>Mã NV</th>
                            <th>Họ và Tên</th>
                            <th>Giới tính</th>
                            <th>Số điện thoại</th>
                            <th>Chức vụ</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($vm['loiTai']): ?>
                        <tr><td colspan="6" style="text-align:center; color:#e74c3c;"><?php echo h($vm['loiTai']); ?></td></tr>
                        <?php elseif (count($vm['danhSach']) === 0): ?>
                        <tr><td colspan="6" style="text-align:center;">Chưa có dữ liệu nhân viên.</td></tr>
                        <?php else: foreach ($vm['danhSach'] as $nv): ?>
                        <tr>
                            <td><?php echo h($nv->getMaHienThi()); ?></td>
                            <td><?php echo h($nv->getTen()); ?></td>
                            <td><?php echo h($nv->getGioiTinhHienThi()); ?></td>
                            <td><?php echo h($nv->getSdt()); ?></td>
                            <td><?php echo h($nv->getChucVuHienThi()); ?></td>
                            <td>
                                <a href="<?php echo $self; ?>?action=sua&amp;id=<?php echo $nv->getMaNhanVien(); ?>"
                                    class="btn btn-primary"
                                    style="padding: 5px 10px; font-size: 12px;">Sửa</a>
                                <!-- BỔ SUNG: chỉ mở modal xóa, không dùng window.confirm(). -->
                                <button type="button" class="btn btn-danger btn-mo-xoa"
                                    data-id="<?php echo (int) $nv->getMaNhanVien(); ?>"
                                    data-name="<?php echo h($nv->getMaHienThi() . ' - ' . $nv->getTen()); ?>"
                                    style="padding: 5px 10px; font-size: 12px;">Xóa</button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BỔ SUNG: modal xóa xuất hiện ngay trên trang, 2 nút Xác nhận / Hủy. -->
        <div id="modalXoaNhanVien" class="modal-overlay" aria-hidden="true">
            <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalXoaTitle"
                aria-describedby="modalXoaMessage">
                <h3 id="modalXoaTitle">Xác nhận xóa nhân viên</h3>
                <p id="modalXoaMessage">Bạn có chắc chắn muốn xóa nhân viên này không?</p>
                <form id="formXoaNhanVien" method="post" action="<?php echo $self; ?>">
                    <input type="hidden" name="action" value="xoa">
                    <input type="hidden" name="ma_nhan_vien" id="maNhanVienCanXoa" value="">
                    <div class="modal-actions">
                        <button type="button" id="huyXoaNhanVien" class="btn btn-secondary">Hủy</button>
                        <button type="submit" id="xacNhanXoaNhanVien" class="btn btn-danger">Xác nhận</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        // BỔ SUNG: kiểm tra biểu mẫu trên giao diện; PHP vẫn kiểm tra lại trước khi ghi DB.
        (function () {
            var form = document.getElementById('nhanVienForm');
            if (form) {
                function kiemTraTruong(tenTruong) {
                    var input = form.elements[tenTruong], giaTri = input.value.replace(/^\s+|\s+$/g, '');
                    var loi = '';
                    if (tenTruong === 'ten') {
                        if (!giaTri) loi = 'Vui lòng nhập họ và tên.';
                        else if (giaTri.length > 120) loi = 'Họ và tên tối đa 120 ký tự.';
                    } else if (tenTruong === 'cccd') {
                        if (!giaTri) loi = 'Vui lòng nhập CCCD.';
                        else if (!/^[0-9]{12}$/.test(giaTri)) loi = 'CCCD phải gồm đúng 12 chữ số.';
                    } else if (tenTruong === 'gioi_tinh') {
                        if (giaTri !== 'Nam' && giaTri !== 'Nu') loi = 'Vui lòng chọn giới tính.';
                    } else if (tenTruong === 'sdt') {
                        if (!giaTri) loi = 'Vui lòng nhập số điện thoại.';
                        else if (!/^0[0-9]{9}$/.test(giaTri)) loi = 'Số điện thoại gồm 10 chữ số, bắt đầu bằng 0.';
                    } else if (tenTruong === 'ngay_sinh') {
                        var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(giaTri);
                        if (!match) loi = 'Vui lòng chọn ngày sinh hợp lệ.';
                        else {
                            var y = Number(match[1]), m = Number(match[2]), d = Number(match[3]);
                            var date = new Date(y, m - 1, d);
                            if (y < 100 || date.getFullYear() !== y || date.getMonth() !== m - 1 ||
                                date.getDate() !== d || date.getTime() > (new Date()).getTime()) {
                                loi = 'Ngày sinh không hợp lệ hoặc lớn hơn ngày hiện tại.';
                            }
                        }
                    }
                    var thongBao = form.querySelector('[data-error-for="' + tenTruong + '"]');
                    if (thongBao) thongBao.textContent = loi;
                    if (loi) input.classList.add('has-error');
                    else input.classList.remove('has-error');
                    return loi === '';
                }
                var truong = ['ten', 'cccd', 'gioi_tinh', 'sdt', 'ngay_sinh'];
                for (var i = 0; i < truong.length; i++) {
                    (function (ten) {
                        var input = form.elements[ten];
                        input.addEventListener('blur', function () { kiemTraTruong(ten); });
                        input.addEventListener(ten === 'gioi_tinh' || ten === 'ngay_sinh' ? 'change' : 'input',
                            function () { kiemTraTruong(ten); });
                    })(truong[i]);
                }
                form.addEventListener('submit', function (event) {
                    var hopLe = true, truongDauTien = null;
                    for (var j = 0; j < truong.length; j++) {
                        if (!kiemTraTruong(truong[j])) {
                            hopLe = false;
                            if (!truongDauTien) truongDauTien = form.elements[truong[j]];
                        }
                    }
                    if (!hopLe) { event.preventDefault(); truongDauTien.focus(); }
                });
            }

            // BỔ SUNG: hiển thị modal trong trang thay vì popup "localhost says".
            var modal = document.getElementById('modalXoaNhanVien');
            var nutHuy = document.getElementById('huyXoaNhanVien');
            var nutXacNhan = document.getElementById('xacNhanXoaNhanVien');
            var nutTruoc = null;
            function dongModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.getElementById('maNhanVienCanXoa').value = '';
                if (nutTruoc) nutTruoc.focus();
            }
            var nutXoa = document.querySelectorAll('.btn-mo-xoa');
            for (var k = 0; k < nutXoa.length; k++) {
                nutXoa[k].addEventListener('click', function () {
                    nutTruoc = this;
                    document.getElementById('maNhanVienCanXoa').value = this.getAttribute('data-id');
                    document.getElementById('modalXoaMessage').textContent =
                        'Bạn có chắc muốn xóa nhân viên ' + this.getAttribute('data-name') + '?';
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    nutHuy.focus();
                });
            }
            nutHuy.addEventListener('click', dongModal);
            modal.addEventListener('click', function (event) { if (event.target === modal) dongModal(); });
            document.addEventListener('keydown', function (event) {
                if ((event.key === 'Escape' || event.keyCode === 27) && modal.classList.contains('is-open')) dongModal();
            });
        })();
        </script>
    </body>
</html>