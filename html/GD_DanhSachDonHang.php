<?php
session_start();
require_once dirname(__FILE__) . '/../php/Control/donHangController.php';
require_once dirname(__FILE__) . '/../php/XacThuc.php';

$nguoiDung = XacThuc::yeuCauDangNhap();
$coQuyen = DonHangController::choPhepXem($nguoiDung);

$danhSach = array();
$loiTaiDuLieu = null;

if ($coQuyen) {
    $controller = new DonHangController();
    $kq = $controller->layDanhSach();
    if ($kq['ok']) {
        $danhSach = $kq['du_lieu'];
    } else {
        $loiTaiDuLieu = $kq['message'];
    }
}

function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh Sách Đơn Hàng - EIGHT STORE</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        h2 { text-align: center; margin-bottom: 20px; }
        .chuyen-giao-dien { display: flex; gap: 10px; justify-content: center; margin: -5px 0 15px; }
        .khung-danh-sach { margin-bottom: 15px; padding: 15px; background: white; border-radius: 6px; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        tr:hover td { background-color: #f1f5f9; }
        .text-right { text-align: right; }
        button, .btn { border: 0; border-radius: 4px; padding: 10px 14px; color: white; cursor: pointer; text-decoration: none; display: inline-block; }
        button[disabled] { opacity: .6; cursor: default; }
        .btn-nho { padding: 6px 10px; font-size: 13px; }
        .primary { background: #3498db; } .success { background: #27ae60; } .danger { background: #e74c3c; } .secondary { background: #7f8c8d; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #ecf0f1; color: #555; }
        .badge-success { background: #d5f5e3; color: #1e8449; }
        .badge-danger { background: #fadbd8; color: #c0392b; }
        .badge-warning { background: #fdebd0; color: #b9770e; }
        .alert { padding: 12px 15px; border-radius: 4px; font-size: 14px; margin-bottom: 15px; }
        .alert-error { background: #fadbd8; color: #c0392b; }
        .alert-success { background: #d5f5e3; color: #1e8449; }
        .empty { text-align: center; color: #888; padding: 25px; }
        .dang-tai { text-align: center; color: #888; padding: 25px; }

        /* Modal chi tiết đơn hàng */
        .modal { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,.5); align-items: center; justify-content: center; }
        .modal-box { width: 760px; max-width: 94%; max-height: 90%; overflow-y: auto; padding: 20px 25px; background: white; border-radius: 7px; }
        .modal-box h3 { font-size: 18px; margin-bottom: 14px; }
        .thong-tin-chung { margin-bottom: 10px; line-height: 1.9; font-size: 14px; }
        .modal-box .tong-cong td { font-weight: 700; border-bottom: none; background: #f8f9fa; }
        .modal-actions { display: flex; gap: 10px; justify-content: space-between; margin-top: 15px; }

        /* Khung thêm sản phẩm / xác nhận xóa */
        .khung-phu { display: none; margin-top: 12px; padding: 14px; background: #f8f9fa; border: 1px solid #e1e5e8; border-radius: 6px; }
        .khung-phu h4 { font-size: 15px; margin-bottom: 10px; }
        .khung-phu label { display: block; font-size: 13px; margin: 8px 0 4px; }
        .khung-phu select, .khung-phu input { width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .hang-nut { display: flex; gap: 10px; justify-content: flex-end; margin-top: 12px; }
    </style>
    <link rel="stylesheet" href="khung-giao-dien.css">
</head>
<body class="giao-dien-chung">
    <div class="sidebar">
        <div class="logo">EIGHT STORE</div>
        <a href="GD_TrangChu.php">dashboard</a>
        <a href="GD_QuanLyBanHang.php" class="active" aria-current="page">Quản lý Bán Hàng</a>
        <a href="GD_QuanLyNhanVien.php">quản lý nhân viên</a>
        <a href="GD_QuanLyNhaCungCap.php">quản lý nhà cung cấp</a>
        <a href="GD_LapPhieuDatHang.php">Lập phiếu đặt hàng</a>
        <a href="GD_DuyetPhieuDatHang.php">Duyệt phiếu đặt hàng</a>
        <a href="GD_XuLyHoanTra.php">Xử lý hoàn trả</a>
        <a href="GD_BaoCao.php">Báo cáo</a>
        <a href="logout.php">Đăng xuất</a>
    </div>

    <div class="main">
        <h2>Danh sách đơn hàng</h2>

        <div class="chuyen-giao-dien">
            <a class="btn secondary" href="GD_QuanLyBanHang.php">Lập hóa đơn bán hàng</a>
            <a class="btn primary" href="GD_DanhSachDonHang.php" aria-current="page">Xem danh sách đơn hàng</a>
        </div>

        <?php if (!$coQuyen): ?>
            <div class="alert alert-error">Bạn không có quyền xem danh sách đơn hàng.</div>

        <?php elseif ($loiTaiDuLieu): ?>
            <div class="alert alert-error"><?php echo e($loiTaiDuLieu); ?></div>

        <?php else: ?>
            <div class="khung-danh-sach">
                <table>
                    <thead>
                        <tr>
                            <th>Mã hóa đơn</th>
                            <th>Thời gian tạo</th>
                            <th>Nhân viên lập</th>
                            <th class="text-right">Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($danhSach)): ?>
                        <tr><td colspan="6" class="empty">Chưa có dữ liệu đơn hàng.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($danhSach as $dh): ?>
                        <?php $tt = DonHangController::trangThaiHienThi($dh['trang_thai']); ?>
                        <tr>
                            <td><?php echo e(DonHangController::hienMaHoaDon($dh['ma_hoa_don'])); ?></td>
                            <td><?php echo e(DonHangController::dinhDangNgayGio($dh['thoi_gian_tao'])); ?></td>
                            <td><?php echo e(DonHangController::hienMaNhanVien($dh['ma_nhan_vien'])); ?></td>
                            <td class="text-right" id="tong-tien-<?php echo (int)$dh['ma_hoa_don']; ?>"><?php echo e(DonHangController::dinhDangTien($dh['tong_tien'])); ?></td>
                            <td><span class="badge<?php echo $tt['lop'] !== '' ? ' badge-' . e($tt['lop']) : ''; ?>"><?php echo e($tt['nhan']); ?></span></td>
                            <td>
                                <button type="button" class="primary btn-nho"
                                        onclick="moChiTiet(<?php echo (int)$dh['ma_hoa_don']; ?>);">Xem chi tiết</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal chi tiết đơn hàng -->
    <div id="modal-chi-tiet" class="modal" onclick="if (event.target === this) { dongChiTiet(); }">
        <div class="modal-box">
            <h3>Chi tiết đơn hàng <span id="modal-ma-hoa-don"></span></h3>
            <div id="modal-thong-bao"></div>
            <div id="modal-noi-dung"></div>

            <!-- UC31: thêm sản phẩm vào đơn hàng -->
            <div id="khung-them" class="khung-phu">
                <h4>Thêm sản phẩm vào đơn hàng</h4>
                <label for="chon-san-pham">Sản phẩm</label>
                <select id="chon-san-pham"></select>
                <label for="nhap-so-luong">Số lượng</label>
                <input type="number" id="nhap-so-luong" min="1" step="1" value="1">
                <div class="hang-nut">
                    <button type="button" class="secondary" onclick="huyThem();">Hủy</button>
                    <button type="button" class="success" id="nut-xac-nhan-them" onclick="xacNhanThem();">Xác nhận thêm</button>
                </div>
            </div>

            <!-- UC32: xác nhận xóa sản phẩm -->
            <div id="khung-xoa" class="khung-phu">
                <h4>Xóa sản phẩm khỏi đơn hàng</h4>
                <p>Bạn có chắc muốn xóa <b id="xoa-ten-san-pham"></b> khỏi đơn hàng này?</p>
                <div class="hang-nut">
                    <button type="button" class="secondary" onclick="tuChoiXoa();">Từ chối</button>
                    <button type="button" class="danger" id="nut-xac-nhan-xoa" onclick="xacNhanXoa();">Xác nhận</button>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="primary" id="nut-them-san-pham" style="display:none;" onclick="moFormThem();">+ Thêm sản phẩm</button>
                <button type="button" class="secondary" style="margin-left:auto;" onclick="dongChiTiet();">Đóng</button>
            </div>
        </div>
    </div>

    <script>
        var URL_CHI_TIET = '../php/Control/chiTietDonHang.php';
        var URL_SUA = '../php/Control/suaDonHang.php';
        var CSRF_TOKEN = '<?php echo e(DonHangController::layTokenCsrf()); ?>';

        var lanGoi = 0;          // bỏ qua kết quả của lần mở cũ nếu người dùng mở đơn khác quá nhanh
        var maDangMo = 0;        // id đơn hàng đang xem trong modal
        var chiTietHienTai = null;
        var maSanPhamCanXoa = 0;
        var dangGui = false;     // chặn bấm 2 lần khi đang gửi yêu cầu

        function $(id) { return document.getElementById(id); }

        function esc(s) {
            if (s === null || s === undefined) { s = ''; }
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function hienThongBao(loai, noiDung) {
            $('modal-thong-bao').innerHTML = noiDung
                ? '<div class="alert alert-' + loai + '">' + esc(noiDung) + '</div>' : '';
        }

        function anCacKhung() {
            $('khung-them').style.display = 'none';
            $('khung-xoa').style.display = 'none';
        }

        function hienLoi(thongBao) {
            $('modal-noi-dung').innerHTML = '<div class="alert alert-error">' + esc(thongBao) + '</div>';
            $('nut-them-san-pham').style.display = 'none';
        }

        // ---- Xem chi tiết (UC28) ----
        function moChiTiet(id) {
            lanGoi++;
            maDangMo = id;
            chiTietHienTai = null;
            anCacKhung();
            hienThongBao('', '');
            $('modal-ma-hoa-don').innerHTML = '';
            $('nut-them-san-pham').style.display = 'none';
            $('modal-noi-dung').innerHTML = '<div class="dang-tai">Đang tải dữ liệu...</div>';
            $('modal-chi-tiet').style.display = 'flex';
            taiChiTiet(id, '');
        }

        function taiChiTiet(id, thongBaoThanhCong) {
            var luot = ++lanGoi;
            fetch(URL_CHI_TIET + '?id=' + encodeURIComponent(id), { credentials: 'same-origin' })
                .then(function (res) { return res.text(); })
                .then(function (text) {
                    if (luot !== lanGoi) { return; }
                    var kq;
                    try { kq = JSON.parse(text); } catch (err) { throw new Error('Phản hồi không hợp lệ'); }
                    if (!kq.ok) { hienLoi(kq.message); return; }
                    veChiTiet(kq);
                    if (thongBaoThanhCong) { hienThongBao('success', thongBaoThanhCong); }
                })
                .catch(function () {
                    if (luot !== lanGoi) { return; }
                    hienLoi('Không thể tải dữ liệu chi tiết đơn hàng.');
                });
        }

        function veChiTiet(kq) {
            var hd = kq.hoa_don;
            var choSua = !!hd.cho_sua;
            var html = '';
            chiTietHienTai = kq;

            $('modal-ma-hoa-don').innerHTML = esc(hd.ma_hoa_don);

            html += '<div class="thong-tin-chung">';
            html += 'Thời gian tạo: <b>' + esc(hd.thoi_gian_tao) + '</b><br>';
            html += 'Nhân viên lập: <b>' + esc(hd.ma_nhan_vien) + '</b> &nbsp;|&nbsp; ';
            html += 'Khách hàng: <b>' + esc(hd.ma_khach_hang) + '</b> &nbsp;|&nbsp; ';
            html += 'Ca làm việc: <b>' + esc(hd.ma_ca) + '</b><br>';
            html += 'Trạng thái: <span class="badge' + (hd.lop_trang_thai ? ' badge-' + esc(hd.lop_trang_thai) : '') + '">'
                 + esc(hd.trang_thai) + '</span>';
            if (hd.ly_do_huy) {
                html += '<br>Lý do hủy: <b>' + esc(hd.ly_do_huy) + '</b>';
            }
            html += '</div>';

            var soCot = choSua ? 6 : 5;
            html += '<table><thead><tr>';
            html += '<th>Mã SP</th><th>Tên sản phẩm</th>';
            html += '<th class="text-right">Số lượng</th><th class="text-right">Đơn giá</th><th class="text-right">Thành tiền</th>';
            if (choSua) { html += '<th>Thao tác</th>'; }
            html += '</tr></thead><tbody>';

            if (!kq.chi_tiet || kq.chi_tiet.length === 0) {
                html += '<tr><td colspan="' + soCot + '" class="empty">Đơn hàng chưa có sản phẩm.</td></tr>';
            } else {
                for (var i = 0; i < kq.chi_tiet.length; i++) {
                    var ct = kq.chi_tiet[i];
                    html += '<tr>'
                        + '<td>' + esc(ct.ma_san_pham) + '</td>'
                        + '<td>' + esc(ct.ten_san_pham) + '</td>'
                        + '<td class="text-right">' + esc(ct.so_luong) + '</td>'
                        + '<td class="text-right">' + esc(ct.don_gia) + '</td>'
                        + '<td class="text-right">' + esc(ct.thanh_tien) + '</td>';
                    if (choSua) {
                        html += '<td><button type="button" class="danger btn-nho" onclick="hoiXoa(' + i + ');">Xóa</button></td>';
                    }
                    html += '</tr>';
                }
            }

            html += '<tr class="tong-cong"><td colspan="' + (soCot - 1) + '" class="text-right">Tổng tiền hóa đơn</td>'
                 + '<td class="text-right">' + esc(hd.tong_tien) + '</td></tr>';
            html += '</tbody></table>';

            $('modal-noi-dung').innerHTML = html;
            $('nut-them-san-pham').style.display = choSua ? '' : 'none';

            // Tổng tiền ở danh sách phía sau modal cũng được cập nhật theo.
            var oTong = $('tong-tien-' + hd.id);
            if (oTong) { oTong.textContent = hd.tong_tien; }
        }

        function dongChiTiet() {
            lanGoi++;
            anCacKhung();
            $('modal-chi-tiet').style.display = 'none';
        }

        // ---- Gửi yêu cầu thêm / xóa ----
        function guiYeuCau(thamSo, khiXong) {
            if (dangGui) { return; }
            dangGui = true;
            $('nut-xac-nhan-them').disabled = true;
            $('nut-xac-nhan-xoa').disabled = true;

            var body = 'token=' + encodeURIComponent(CSRF_TOKEN);
            for (var k in thamSo) {
                if (thamSo.hasOwnProperty(k)) { body += '&' + k + '=' + encodeURIComponent(thamSo[k]); }
            }

            fetch(URL_SUA, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
                .then(function (res) { return res.text(); })
                .then(function (text) {
                    var kq;
                    try { kq = JSON.parse(text); } catch (err) { throw new Error('Phản hồi không hợp lệ'); }
                    khiXong(kq);
                })
                .catch(function () {
                    hienThongBao('error', 'Không thể thực hiện thao tác, vui lòng thử lại.');
                })
                .then(function () {
                    dangGui = false;
                    $('nut-xac-nhan-them').disabled = false;
                    $('nut-xac-nhan-xoa').disabled = false;
                });
        }

        // ---- UC31: thêm sản phẩm ----
        function moFormThem() {
            if (!chiTietHienTai) { return; }
            anCacKhung();
            hienThongBao('', '');

            var ds = chiTietHienTai.san_pham_them || [];
            var chon = $('chon-san-pham');
            var opt = '<option value="">-- Chọn sản phẩm --</option>';
            for (var i = 0; i < ds.length; i++) {
                opt += '<option value="' + esc(ds[i].id) + '">' + esc(ds[i].nhan) + '</option>';
            }
            chon.innerHTML = opt;
            $('nhap-so-luong').value = '1';
            $('khung-them').style.display = 'block';
            chon.focus();
        }

        function huyThem() { // luồng thay thế 9.1: hủy thêm, quay lại chi tiết đơn hàng
            $('khung-them').style.display = 'none';
            hienThongBao('', '');
        }

        function xacNhanThem() {
            var maSp = $('chon-san-pham').value;
            var soLuong = $('nhap-so-luong').value.replace(/^\s+|\s+$/g, '');
            if (maSp === '') { hienThongBao('error', 'Vui lòng chọn sản phẩm.'); return; }
            if (!/^[0-9]+$/.test(soLuong) || parseInt(soLuong, 10) <= 0) {
                hienThongBao('error', 'Số lượng phải là số nguyên dương.');
                return;
            }
            guiYeuCau({ action: 'them', ma_hoa_don: maDangMo, ma_san_pham: maSp, so_luong: soLuong }, function (kq) {
                if (kq.ok) {
                    anCacKhung();
                    taiChiTiet(maDangMo, kq.message);
                } else {
                    hienThongBao('error', kq.message);
                }
            });
        }

        // ---- UC32: xóa sản phẩm ----
        function hoiXoa(viTri) {
            if (!chiTietHienTai || !chiTietHienTai.chi_tiet[viTri]) { return; }
            anCacKhung();
            hienThongBao('', '');
            var ct = chiTietHienTai.chi_tiet[viTri];
            maSanPhamCanXoa = ct.id_san_pham;
            $('xoa-ten-san-pham').textContent = ct.ma_san_pham + ' - ' + ct.ten_san_pham;
            $('khung-xoa').style.display = 'block';
        }

        function tuChoiXoa() { // luồng thay thế 10.1: giữ nguyên sản phẩm
            $('khung-xoa').style.display = 'none';
        }

        function xacNhanXoa() {
            guiYeuCau({ action: 'xoa', ma_hoa_don: maDangMo, ma_san_pham: maSanPhamCanXoa }, function (kq) {
                if (kq.ok) {
                    anCacKhung();
                    taiChiTiet(maDangMo, kq.message);
                } else {
                    hienThongBao('error', kq.message);
                    $('khung-xoa').style.display = 'none';
                }
            });
        }

        document.onkeydown = function (ev) {
            ev = ev || window.event;
            if (ev.keyCode === 27) { dongChiTiet(); }
        };
    </script>

</body>
</html>