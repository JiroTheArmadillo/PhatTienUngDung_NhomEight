<?php
session_start();
require_once dirname(__FILE__) . '/../php/Control/hoanTraController.php';
require_once dirname(__FILE__) . '/../php/Control/donHangController.php';
require_once dirname(__FILE__) . '/../php/XacThuc.php';

$nguoiDung = XacThuc::yeuCauDangNhap();
$coQuyenXemDonHang = DonHangController::choPhepXem($nguoiDung);
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

$tenNguoiDung = isset($nguoiDung['ho_ten']) ? $nguoiDung['ho_ten'] : '';

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

    .content h2 {
        font-size: 22px;
        margin-bottom: 15px;
        color: #2c3e50;
    }

    .chuyen-giao-dien {
        display: flex;
        gap: 10px;
        margin: 0 0 15px;
        flex-wrap: wrap;
    }

    table {
        width: 100%;
        border-collapse: collapse;
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
    .btn-warning { background-color: #e67e22; color: white; }

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

    /* Form nhập lý do từ chối */
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
    <link rel="stylesheet" href="khung-giao-dien.css">
</head>
<body class="giao-dien-chung">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">EIGHT STORE</div>
        <a href="GD_TrangChu.php">dashboard</a>
        <a href="GD_QuanLyCaLamViec.php">Quản lý ca làm việc</a>
        <a href="GD_QuanLyBanHang.php" class="active" aria-current="page">Quản lý Bán Hàng</a>
        <a href="GD_QuanLyNhanVien.php" <?php echo ($nguoiDung['vai_tro'] !== 'QuanLy') ? 'style="pointer-events:none;"' : ''; ?>>quản lý nhân viên</a>
        <a href="GD_QuanLyNhaCungCap.php">quản lý nhà cung cấp</a>
        <a href="GD_LapPhieuDatHang.php">Lập phiếu đặt hàng</a>
        <a href="GD_DuyetPhieuDatHang.php" <?php echo ($nguoiDung['vai_tro'] !== 'QuanLy') ? 'style="pointer-events:none;"' : ''; ?>>Duyệt phiếu đặt hàng</a>
        <a href="GD_BaoCao.php">Báo cáo</a>
        <a href="logout.php">Đăng xuất</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="user-info">Chào mừng, <b><?php echo e($tenNguoiDung); ?></b></div>
        </div>

        <div class="content">
            <h2>Danh Sách Yêu Cầu Hoàn Trả Hàng</h2>

            <div class="chuyen-giao-dien">
                <a class="btn btn-secondary" href="GD_QuanLyBanHang.php">Lập hóa đơn bán hàng</a>
                <?php if ($coQuyenXemDonHang): ?>
                <a class="btn btn-secondary" href="GD_DanhSachDonHang.php">Xem danh sách đơn hàng</a>
                <?php endif; ?>
                <a class="btn btn-warning" href="GD_XuLyHoanTra.php" aria-current="page">Yêu cầu hoàn trả</a>
            </div>

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
                            <button type="button" class="btn btn-primary" style="padding: 5px 10px;"
                                    onclick="moChiTiet(<?php echo (int)$yc['ma_yeu_cau']; ?>);">Xem chi tiết</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal chi tiết yêu cầu hoàn trả: xem thông tin, chấp nhận hoặc từ chối -->
    <div id="modal-chi-tiet" class="modal" style="display: none;" onclick="if (event.target === this) { dongChiTiet(); }">
        <div class="modal-box" style="width: 760px; max-height: 90%; overflow-y: auto;">
            <h3>Chi tiết yêu cầu hoàn trả <span id="ct-ma-yeu-cau"></span></h3>
            <div id="ct-noi-dung"></div>

            <!-- Nhập lý do từ chối (hiện khi bấm "Từ chối") -->
            <form id="form-tu-choi" method="post" action="../php/Control/xuLyHoanTra.php" onsubmit="return kiemTraLyDo();" style="display: none; margin-top: 15px;">
                <input type="hidden" name="id" id="tu-choi-id" value="">
                <input type="hidden" name="action" value="reject">
                <label for="modal-ly-do">Lý do từ chối</label>
                <textarea name="ly_do_tu_choi" id="modal-ly-do" rows="4" maxlength="255"></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="dongLyDoTuChoi();">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xác nhận từ chối</button>
                </div>
            </form>

            <div id="ct-hanh-dong" class="modal-actions" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 8px;">
                    <form id="form-chap-nhan" method="post" action="../php/Control/xuLyHoanTra.php" style="display: none;">
                        <input type="hidden" name="id" id="chap-nhan-id" value="">
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" class="btn btn-success" style="margin-left: 0;">Chấp nhận</button>
                    </form>
                    <button type="button" id="nut-tu-choi" class="btn btn-danger" style="display: none; margin-left: 0;" onclick="moLyDoTuChoi();">Từ chối</button>
                </div>
                <button type="button" class="btn btn-secondary" onclick="dongChiTiet();">Đóng</button>
            </div>
        </div>
    </div>

    <script>
        var URL_CHI_TIET = '../php/Control/chiTietHoanTra.php';
        var lanGoi = 0;
        var maDangMo = 0;

        function $(id) { return document.getElementById(id); }

        function esc(s) {
            if (s === null || s === undefined) { s = ''; }
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function hienLoi(thongBao) {
            $('ct-noi-dung').innerHTML = '<div class="alert alert-error">' + esc(thongBao) + '</div>';
            $('form-chap-nhan').style.display = 'none';
            $('nut-tu-choi').style.display = 'none';
        }

        function moChiTiet(id) {
            var luot = ++lanGoi;
            maDangMo = id;
            $('ct-ma-yeu-cau').innerHTML = '';
            $('form-tu-choi').style.display = 'none';
            $('ct-hanh-dong').style.display = 'flex';
            $('form-chap-nhan').style.display = 'none';
            $('nut-tu-choi').style.display = 'none';
            $('ct-noi-dung').innerHTML = '<p style="text-align:center;color:#888;padding:25px;">Đang tải dữ liệu...</p>';
            $('modal-chi-tiet').style.display = 'flex';

            fetch(URL_CHI_TIET + '?id=' + encodeURIComponent(id), { credentials: 'same-origin' })
                .then(function (res) { return res.text(); })
                .then(function (text) {
                    if (luot !== lanGoi) { return; }
                    var kq;
                    try { kq = JSON.parse(text); } catch (err) { throw new Error('Phản hồi không hợp lệ'); }
                    if (!kq.ok) { hienLoi(kq.message); return; }
                    veChiTiet(kq);
                })
                .catch(function () {
                    if (luot !== lanGoi) { return; }
                    hienLoi('Không thể tải chi tiết yêu cầu hoàn trả.');
                });
        }

        function veChiTiet(kq) {
            var yc = kq.yeu_cau;
            var html = '';

            $('ct-ma-yeu-cau').innerHTML = esc(yc.ma_yeu_cau);

            html += '<div style="margin-bottom:10px;line-height:1.9;font-size:14px;">';
            html += 'Hóa đơn: <b>' + esc(yc.ma_hoa_don) + '</b> &nbsp;|&nbsp; ';
            html += 'Khách hàng: <b>' + esc(yc.ma_khach_hang) + '</b><br>';
            html += 'Ngày tạo: <b>' + esc(yc.ngay_tao) + '</b>';
            if (yc.nhan_vien_tao) { html += ' &nbsp;|&nbsp; Nhân viên tạo: <b>' + esc(yc.nhan_vien_tao) + '</b>'; }
            html += '<br>Trạng thái: <span class="badge' + (yc.lop_trang_thai ? ' badge-' + esc(yc.lop_trang_thai) : '') + '">'
                 + esc(yc.trang_thai) + '</span><br>';
            html += 'Lý do hoàn trả: <b>' + esc(yc.ly_do_hoan_tra) + '</b>';
            if (yc.ly_do_tu_choi) { html += '<br>Lý do từ chối: <b style="color:#c0392b;">' + esc(yc.ly_do_tu_choi) + '</b>'; }
            if (yc.ngay_xu_ly) {
                html += '<br>Xử lý lúc: <b>' + esc(yc.ngay_xu_ly) + '</b>';
                if (yc.nhan_vien_xu_ly) { html += ' bởi <b>' + esc(yc.nhan_vien_xu_ly) + '</b>'; }
            }
            html += '</div>';

            html += '<table><thead><tr>'
                 + '<th>Mã SP</th><th>Tên sản phẩm</th>'
                 + '<th style="text-align:right;">Đã mua</th><th style="text-align:right;">Hoàn trả</th>'
                 + '<th style="text-align:right;">Đơn giá</th><th style="text-align:right;">Thành tiền</th>'
                 + '</tr></thead><tbody>';
            if (!kq.chi_tiet || kq.chi_tiet.length === 0) {
                html += '<tr><td colspan="6" class="empty">Yêu cầu chưa có sản phẩm.</td></tr>';
            } else {
                for (var i = 0; i < kq.chi_tiet.length; i++) {
                    var ct = kq.chi_tiet[i];
                    html += '<tr>'
                        + '<td>' + esc(ct.ma_san_pham) + '</td>'
                        + '<td>' + esc(ct.ten_san_pham) + '</td>'
                        + '<td style="text-align:right;">' + esc(ct.so_luong_mua) + '</td>'
                        + '<td style="text-align:right;">' + esc(ct.so_luong_hoan) + '</td>'
                        + '<td style="text-align:right;">' + esc(ct.don_gia) + '</td>'
                        + '<td style="text-align:right;">' + esc(ct.thanh_tien) + '</td>'
                        + '</tr>';
                }
            }
            html += '<tr style="background:#f8f9fa;font-weight:700;"><td colspan="5" style="text-align:right;">Tổng tiền hoàn trả</td>'
                 + '<td style="text-align:right;">' + esc(kq.tong_hoan) + '</td></tr>';
            html += '</tbody></table>';

            $('ct-noi-dung').innerHTML = html;

            $('chap-nhan-id').value = yc.id;
            $('tu-choi-id').value = yc.id;
            $('form-chap-nhan').style.display = yc.cho_duyet ? 'block' : 'none';
            $('nut-tu-choi').style.display = yc.cho_duyet ? 'inline-block' : 'none';
        }

        function dongChiTiet() {
            lanGoi++;
            $('form-tu-choi').style.display = 'none';
            $('modal-chi-tiet').style.display = 'none';
        }

        function moLyDoTuChoi() {
            $('modal-ly-do').value = '';
            $('ct-hanh-dong').style.display = 'none';
            $('form-tu-choi').style.display = 'block';
            $('modal-ly-do').focus();
        }

        function dongLyDoTuChoi() {
            $('form-tu-choi').style.display = 'none';
            $('ct-hanh-dong').style.display = 'flex';
        }

        function kiemTraLyDo() {
            var lyDo = $('modal-ly-do').value.replace(/^\s+|\s+$/g, '');
            if (lyDo === '') {
                alert('Vui lòng nhập lý do từ chối.');
                return false;
            }
            return true;
        }

        document.onkeydown = function (ev) {
            ev = ev || window.event;
            if (ev.keyCode === 27) { dongChiTiet(); }
        };
    </script>

</body>
</html>