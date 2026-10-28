<?php
session_start();
require_once dirname(__FILE__) . '/../php/Control/HoanTraController.php';
 
$controller = new HoanTraController();
$danhSach = array();
$loiTaiDuLieu = null;
 
try {
    $danhSach = $controller->layDanhSach();
} catch (Exception $e) {
    error_log('[HoanTra] ' . $e->getMessage());
    $loiTaiDuLieu = 'Không thể tải danh sách yêu cầu hoàn trả.';
}
 
// Thông báo (flash) sau khi xử lý xong
$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
unset($_SESSION['flash']);
 
$tenNguoiDung = isset($_SESSION['ten']) ? $_SESSION['ten'] : 'Vinh';
 
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
 
// Hiển thị mã theo dạng YCHT01 / HD012 như giao diện gốc
function hienMaYeuCau($id) { return 'YCHT' . str_pad($id, 2, '0', STR_PAD_LEFT); }
function hienMaHoaDon($id) { return 'HD' . str_pad($id, 3, '0', STR_PAD_LEFT); }
 
function hienTrangThai($tt)
{
    switch (trim($tt)) {
        case HoanTraController::CHO_DUYET:
            return '<span class="badge badge-warning">Chờ xử lý</span>';
        case HoanTraController::DA_DUYET:
            return '<span class="badge badge-success">Đã chấp nhận</span>';
        case HoanTraController::TU_CHOI:
            return '<span class="badge badge-danger">Đã từ chối</span>';
        default:
            return '<span class="badge">' . e($tt) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xử Lý Yêu Cầu Hoàn Trả</title>
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

/* bổ sung */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    background: #ecf0f1;
    color: #555;
}
.badge-warning { background: #fdebd0; color: #b9770e; }
.badge-success { background: #d5f5e3; color: #1e8449; }
.badge-danger  { background: #fadbd8; color: #c0392b; }

.alert {
    padding: 12px 15px;
    border-radius: 4px;
    font-size: 14px;
    margin-bottom: 15px;
}
.alert-success { background: #d5f5e3; color: #1e8449; }
.alert-error   { background: #fadbd8; color: #c0392b; }

.actions { display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap; }
.actions form { margin: 0; }

/* Form nhập lý do từ chối*/
.modal {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal-box {
    background: white;
    width: 420px;
    max-width: 90%;
    padding: 20px 25px;
    border-radius: 6px;
}
.modal-box h3 { font-size: 17px; color: #2c3e50; margin-bottom: 12px; }
.modal-box label { display: block; font-size: 13px; margin-bottom: 6px; }
.modal-box textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
}
.modal-actions { margin-top: 15px; text-align: right; }
.modal-actions .btn { margin-left: 8px; }
.ly-do-tu-choi { display: block; margin-top: 4px; font-size: 12px; color: #c0392b; }
.empty { text-align: center; color: #888; padding: 25px; }

     </style>
    <link rel="stylesheet" href="giao-dien.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
         <div class="logo">EIGHT STORE</div>
         <a href="GD_TrangChu.php">dashboard</a>
         <a href="GD_QuanLyBanHang.php">Quản lý Bán Hàng</a>
        <a href="GD_QuanLyNhanVien.php" class="active" aria-current="page">Quản lý nhân viên</a>
        <a href="GD_QuanLyNhaCungCap.php">Quản lý nhà cung cấp</a>
        <a href="GD_LapPhieuDatHang.php">Lập phiếu đặt hàng</a>
        <a href="GD_DuyetPhieuDatHang.php">Duyệt phiếu đặt hàng</a>
        <a href="GD_XuLyHoanTra.php">Xử lý hoàn trả</a>       
        <a href="GD_BaoCao.php" class="active" aria-current="page">Báo cáo</a>
        <a href="logout.php" style="margin-top: auto; color: #e74c3c;">Đăng xuất</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="user-info">Chào mừng, <b>Vinh</b></div>
        </div>

        <div class="content">
            <h2>Danh Sách Yêu Cầu Hoàn Trả Hàng</h2>
 
            <?php if ($flash): ?>
                <div class="alert <?php echo $flash['ok'] ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo e($flash['message']); ?>
                </div>
            <?php endif; ?>
 
            <?php if ($loiTaiDuLieu): ?>
                <div class="alert alert-error"><?php echo e($loiTaiDuLieu); ?></div>
            <?php endif; ?>
 
            <table>
                <thead>
                    <tr>
                        <th>Mã Yêu Cầu</th>
                        <th>Mã Hóa Đơn</th>
                        <th>Lý Do Hoàn Trả</th>
                        <th>Số Lượng</th>
                        <th>Trạng Thái</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($danhSach)): ?>
                    <tr><td colspan="6" class="empty">Chưa có yêu cầu hoàn trả nào.</td></tr>
                <?php endif; ?>
 
                <?php foreach ($danhSach as $yc): ?>
                    <?php $choDuyet = (trim($yc['trang_thai']) === HoanTraController::CHO_DUYET); ?>
                    <tr>
                        <td><?php echo e(hienMaYeuCau($yc['ma_yeu_cau'])); ?></td>
                        <td><?php echo e(hienMaHoaDon($yc['ma_hoa_don'])); ?></td>
                        <td><?php echo e($yc['ly_do_hoan_tra']); ?></td>
                        <td><?php echo (int)$yc['tong_so_luong']; ?></td>
                        <td>
                            <?php echo hienTrangThai($yc['trang_thai']); ?>
                            <?php if (!empty($yc['ly_do_tu_choi'])): ?>
                                <span class="ly-do-tu-choi">Lý do: <?php echo e($yc['ly_do_tu_choi']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($choDuyet): ?>
                                <div class="actions">
                                    <form method="post" action="../php/Control/xuly_hoantra.php">
                                        <input type="hidden" name="id" value="<?php echo (int)$yc['ma_yeu_cau']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-success" style="padding: 5px 10px;">Chấp nhận</button>
                                    </form>
                                    <button type="button" class="btn btn-danger" style="padding: 5px 10px;"
                                            onclick="moFormTuChoi(<?php echo (int)$yc['ma_yeu_cau']; ?>, '<?php echo e(hienMaYeuCau($yc['ma_yeu_cau'])); ?>');">Từ chối</button>
                                </div>
                            <?php else: ?>
                                <span style="color:#999;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
 
    <!-- Form nhập lý do từ chối (hiện khi bấm "Từ chối") -->
    <div id="modal-tu-choi" class="modal" style="display: none;">
        <div class="modal-box">
            <h3>Từ chối yêu cầu hoàn trả <span id="modal-ma-yeu-cau"></span></h3>
            <form method="post" action="../php/Control/xuly_hoantra.php" onsubmit="return kiemTraLyDo();">
                <input type="hidden" name="id" id="modal-id" value="">
                <input type="hidden" name="action" value="reject">
                <label for="modal-ly-do">Lý do từ chối</label>
                <textarea name="ly_do_tu_choi" id="modal-ly-do" rows="4" maxlength="500"></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="dongFormTuChoi();">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xác nhận</button>
                </div>
            </form>
        </div>
    </div>
 
    <script>
        function moFormTuChoi(id, maHienThi) {
            document.getElementById('modal-id').value = id;
            document.getElementById('modal-ma-yeu-cau').innerHTML = maHienThi;
            document.getElementById('modal-ly-do').value = '';
            document.getElementById('modal-tu-choi').style.display = 'flex';
            document.getElementById('modal-ly-do').focus();
        }
        function dongFormTuChoi() {
            document.getElementById('modal-tu-choi').style.display = 'none';
        }
        function kiemTraLyDo() {
            var lyDo = document.getElementById('modal-ly-do').value.replace(/^\s+|\s+$/g, '');
            if (lyDo === '') {
                alert('Vui lòng nhập lý do từ chối.');
                return false;
            }
            return true;
        }
    </script>
 
</body>
</html>
 
