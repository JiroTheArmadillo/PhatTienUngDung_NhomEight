<?php
session_start();
require_once dirname(__FILE__) . '/../php/Control/donHangController.php';
require_once dirname(__FILE__) . '/../php/XacThuc.php';

$nguoiDung = XacThuc::yeuCauDangNhap();
$coQuyen = DonHangController::choPhepXem($nguoiDung);
$coQuyenHoanTra = DonHangController::laChuDoanhNghiep($nguoiDung);

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
        .chuyen-giao-dien { display: flex; gap: 10px; justify-content: center; margin: -5px 0 15px; flex-wrap: wrap; }
        .khung-danh-sach { margin-bottom: 15px; padding: 15px; background: white; border-radius: 6px; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        tr:hover td { background-color: #f1f5f9; }
        .text-right { text-align: right; }
        button, .btn { border: 0; border-radius: 4px; padding: 10px 14px; color: white; cursor: pointer; text-decoration: none; display: inline-block; }
        button[disabled] { opacity: .6; cursor: default; }
        .btn-nho { padding: 6px 10px; font-size: 13px; }
        .warn { background: #e67e22; }
        .nhom-nut { display: flex; gap: 6px; }
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
        .modal-box .dong-phu td { background: #fcfcfc; color: #444; }
        .modal-box .dong-giam td { background: #fcfcfc; color: #27ae60; font-weight: 600; }
        .modal-box .tong-cong td { font-weight: 700; border-bottom: none; background: #f8f9fa; }
        .modal-actions { display: flex; gap: 10px; justify-content: space-between; margin-top: 15px; }

        /* Khung thêm sản phẩm / xác nhận xóa */
        .khung-phu { display: none; margin-top: 12px; padding: 14px; background: #f8f9fa; border: 1px solid #e1e5e8; border-radius: 6px; }
        .khung-phu h4 { font-size: 15px; margin-bottom: 10px; }
        .khung-phu label { display: block; font-size: 13px; margin: 8px 0 4px; }
        .khung-phu select, .khung-phu input, .khung-phu textarea { width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; font-family: inherit; }
        .khung-phu textarea { resize: vertical; }
        .khung-phu table input { width: 80px; text-align: right; }
        .khung-phu table { margin-bottom: 4px; }
        .hang-nut { display: flex; gap: 10px; justify-content: flex-end; margin-top: 12px; }
    </style>
    <link rel="stylesheet" href="khung-giao-dien.css">
</head>
<body class="giao-dien-chung">
    <div class="sidebar">
        <div class="logo">EIGHT STORE</div>
        <a href="GD_TrangChu.php">dashboard</a>
        <a href="GD_QuanLyCaLamViec.php">Quản lý ca làm việc</a>
        <a href="GD_QuanLyBanHang.php" class="active" aria-current="page">Quản lý Bán Hàng</a>
        <a href="GD_QuanLyNhanVien.php">quản lý nhân viên</a>
        <a href="GD_QuanLyNhaCungCap.php">quản lý nhà cung cấp</a>
        <a href="GD_LapPhieuDatHang.php">Lập phiếu đặt hàng</a>
        <a href="GD_DuyetPhieuDatHang.php">Duyệt phiếu đặt hàng</a>
        <a href="GD_BaoCao.php">Báo cáo</a>
        <a href="logout.php">Đăng xuất</a>
    </div>

    <div class="main">
        <h2>Danh sách đơn hàng</h2>

        <div class="chuyen-giao-dien">
            <a class="btn secondary" href="GD_QuanLyBanHang.php">Lập hóa đơn bán hàng</a>
            <a class="btn primary" href="GD_DanhSachDonHang.php" aria-current="page">Xem danh sách đơn hàng</a>
            <?php if ($coQuyenHoanTra): ?>
            <a class="btn warn" href="GD_XuLyHoanTra.php">Yêu cầu hoàn trả</a>
            <?php endif; ?>
        </div>

        <div id="thong-bao-trang"></div>

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
                            <td id="trang-thai-<?php echo (int)$dh['ma_hoa_don']; ?>"><span class="badge<?php echo $tt['lop'] !== '' ? ' badge-' . e($tt['lop']) : ''; ?>"><?php echo e($tt['nhan']); ?></span></td>
                            <td>
                                <div class="nhom-nut">
                                    <button type="button" class="primary btn-nho"
                                            onclick="moChiTiet(<?php echo (int)$dh['ma_hoa_don']; ?>);">Xem chi tiết</button>
                                    <?php if (DonHangController::laChuaThanhToan($dh['trang_thai'])): ?>
                                        <button type="button" class="danger btn-nho" id="nut-huy-<?php echo (int)$dh['ma_hoa_don']; ?>"
                                                onclick="moHuyDon(<?php echo (int)$dh['ma_hoa_don']; ?>, '<?php echo e(DonHangController::hienMaHoaDon($dh['ma_hoa_don'])); ?>');">Hủy đơn</button>
                                    <?php endif; ?>
                                    <?php if (DonHangController::laDaThanhToan($dh['trang_thai'])): ?>
                                        <a class="btn success btn-nho" target="_blank" rel="noopener"
                                           href="../ThanhToan/hoadon.php?id=<?php echo (int)$dh['ma_hoa_don']; ?>">Xuất hóa đơn</a>
                                    <?php endif; ?>
                                </div>
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

            <!-- UC37: tạo yêu cầu hoàn trả -->
            <div id="khung-hoan-tra" class="khung-phu">
                <h4>Tạo yêu cầu hoàn trả</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-right">Đã mua</th>
                            <th class="text-right">Có thể hoàn</th>
                            <th class="text-right">SL hoàn trả</th>
                        </tr>
                    </thead>
                    <tbody id="hoan-tra-dong"></tbody>
                </table>
                <label for="hoan-tra-ly-do">Lý do hoàn trả</label>
                <textarea id="hoan-tra-ly-do" rows="3" maxlength="255" placeholder="Nhập lý do hoàn trả"></textarea>
                <div class="hang-nut">
                    <button type="button" class="secondary" onclick="huyHoanTra();">Hủy</button>
                    <button type="button" class="success nut-gui" onclick="xacNhanHoanTra();">Xác nhận</button>
                </div>
            </div>

            <div class="modal-actions">
                <div style="display:flex;gap:10px;">
                    <button type="button" class="warn" id="nut-hoan-tra" style="display:none;" onclick="moFormHoanTra();">Tạo yêu cầu hoàn trả</button>
                </div>
                <button type="button" class="secondary" style="margin-left:auto;" onclick="dongChiTiet();">Đóng</button>
            </div>
        </div>
    </div>

    <!-- UC33: hủy đơn hàng -->
    <div id="modal-huy-don" class="modal" onclick="if (event.target === this) { dongHuyDon(); }">
        <div class="modal-box" style="width: 480px;">
            <h3>Hủy đơn hàng <span id="huy-ma-hoa-don"></span></h3>
            <div id="huy-thong-bao"></div>

            <div id="huy-buoc-nhap" class="khung-phu" style="display:block;">
                <label for="huy-chon-ly-do">Chọn lý do</label>
                <select id="huy-chon-ly-do" onchange="chonLyDoMau();">
                    <option value="">-- Chọn lý do có sẵn hoặc nhập bên dưới --</option>
                    <option value="Khách đổi ý">Khách đổi ý</option>
                    <option value="Khách không chuyển khoản">Khách không chuyển khoản</option>
                    <option value="Nhập sai sản phẩm">Nhập sai sản phẩm</option>
                    <option value="Hết hàng">Hết hàng</option>
                </select>
                <label for="huy-ly-do">Lý do hủy</label>
                <textarea id="huy-ly-do" rows="3" maxlength="255" placeholder="Nhập lý do hủy"></textarea>
                <div class="hang-nut">
                    <button type="button" class="secondary" onclick="dongHuyDon();">Đóng</button>
                    <button type="button" class="danger" onclick="yeuCauXacNhanHuy();">Hủy đơn hàng</button>
                </div>
            </div>

            <div id="huy-buoc-xac-nhan" class="khung-phu">
                <p>Bạn có chắc muốn hủy đơn hàng <b id="huy-ma-xac-nhan"></b> với lý do: <b id="huy-ly-do-hien-thi"></b>?</p>
                <div class="hang-nut">
                    <button type="button" class="secondary" onclick="khongHuy();">Không</button>
                    <button type="button" class="danger nut-gui" onclick="xacNhanHuy();">Xác nhận hủy</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        var URL_CHI_TIET = '../php/Control/chiTietDonHang.php';
        var URL_SUA = '../php/Control/suaDonHang.php';
        var CSRF_TOKEN = '<?php echo e(DonHangController::layTokenCsrf()); ?>';

        var lanGoi = 0;
        var maDangMo = 0;
        var chiTietHienTai = null;
        var dangGui = false;

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

        function cat(s) { return String(s).replace(/^\s+|\s+$/g, ''); }

        function anCacKhung() {
            $('khung-hoan-tra').style.display = 'none';
        }

        function hienLoi(thongBao) {
            $('modal-noi-dung').innerHTML = '<div class="alert alert-error">' + esc(thongBao) + '</div>';
            $('nut-hoan-tra').style.display = 'none';
        }

        // ---- Xem chi tiết (UC28) ----
        function moChiTiet(id) {
            lanGoi++;
            maDangMo = id;
            chiTietHienTai = null;
            anCacKhung();
            hienThongBao('', '');
            $('modal-ma-hoa-don').innerHTML = '';
            $('nut-hoan-tra').style.display = 'none';
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
            html += ' &nbsp;|&nbsp; Khuyến mãi: <b>' + (hd.ten_khuyen_mai ? esc(hd.ten_khuyen_mai) : 'Không áp dụng') + '</b>';
            if (hd.ly_do_huy) {
                html += '<br>Lý do hủy: <b>' + esc(hd.ly_do_huy) + '</b>';
            }
            html += '</div>';

            html += '<table><thead><tr>';
            html += '<th>Mã SP</th><th>Tên sản phẩm</th>';
            html += '<th class="text-right">Số lượng</th><th class="text-right">Đơn giá</th><th class="text-right">Thành tiền</th>';
            html += '</tr></thead><tbody>';

            if (!kq.chi_tiet || kq.chi_tiet.length === 0) {
                html += '<tr><td colspan="5" class="empty">Đơn hàng chưa có sản phẩm.</td></tr>';
            } else {
                for (var i = 0; i < kq.chi_tiet.length; i++) {
                    var ct = kq.chi_tiet[i];
                    html += '<tr>'
                        + '<td>' + esc(ct.ma_san_pham) + '</td>'
                        + '<td>' + esc(ct.ten_san_pham) + '</td>'
                        + '<td class="text-right">' + esc(ct.so_luong) + '</td>'
                        + '<td class="text-right">' + esc(ct.don_gia) + '</td>'
                        + '<td class="text-right">' + esc(ct.thanh_tien) + '</td>';
                    html += '</tr>';
                }
            }

            if (hd.co_khuyen_mai) {
                html += '<tr class="dong-phu"><td colspan="4" class="text-right">Tạm tính</td>'
                     + '<td class="text-right">' + esc(hd.tam_tinh) + '</td></tr>';
                html += '<tr class="dong-giam"><td colspan="4" class="text-right">Giảm giá'
                     + (hd.ten_khuyen_mai ? ' (' + esc(hd.ten_khuyen_mai) + ')' : '') + '</td>'
                     + '<td class="text-right">-' + esc(hd.tien_giam) + '</td></tr>';
            }

            html += '<tr class="tong-cong"><td colspan="4" class="text-right">Tổng tiền hóa đơn</td>'
                 + '<td class="text-right">' + esc(hd.tong_tien) + '</td></tr>';
            html += '</tbody></table>';

            $('modal-noi-dung').innerHTML = html;
            $('nut-hoan-tra').style.display = hd.cho_hoan_tra ? '' : 'none';

            var oTong = $('tong-tien-' + hd.id);
            if (oTong) { oTong.textContent = hd.tong_tien; }
        }

        function dongChiTiet() {
            lanGoi++;
            anCacKhung();
            $('modal-chi-tiet').style.display = 'none';
        }

        function guiYeuCau(thamSo, khiXong) {
            if (dangGui) { return; }
            dangGui = true;
            khoaNutGui(true);

            var body = 'token=' + encodeURIComponent(CSRF_TOKEN);
            for (var k in thamSo) {
                if (thamSo.hasOwnProperty(k)) { body += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(thamSo[k]); }
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
                    khiXong({ ok: false, message: 'Không thể thực hiện thao tác, vui lòng thử lại.' });
                })
                .then(function () {
                    dangGui = false;
                    khoaNutGui(false);
                });
        }

        function khoaNutGui(khoa) {
            var ds = document.querySelectorAll('.nut-gui');
            for (var i = 0; i < ds.length; i++) { ds[i].disabled = khoa; }
        }

        // ---- UC37: tạo yêu cầu hoàn trả ----
        function moFormHoanTra() {
            if (!chiTietHienTai) { return; }
            anCacKhung();
            hienThongBao('', '');

            var ds = chiTietHienTai.chi_tiet || [];
            var html = '';
            for (var i = 0; i < ds.length; i++) {
                var ct = ds[i];
                var conLai = ct.so_luong - ct.da_yeu_cau_hoan;
                if (conLai < 0) { conLai = 0; }
                html += '<tr>'
                    + '<td>' + esc(ct.ma_san_pham + ' - ' + ct.ten_san_pham) + '</td>'
                    + '<td class="text-right">' + esc(ct.so_luong) + '</td>'
                    + '<td class="text-right">' + esc(conLai) + '</td>'
                    + '<td class="text-right"><input type="number" class="o-so-luong-hoan" min="0" max="' + conLai + '" step="1" value="0"'
                    + ' data-ma="' + esc(ct.id_san_pham) + '" data-max="' + conLai + '"' + (conLai === 0 ? ' disabled' : '') + '></td>'
                    + '</tr>';
            }
            $('hoan-tra-dong').innerHTML = html;
            $('hoan-tra-ly-do').value = '';
            $('khung-hoan-tra').style.display = 'block';
        }

        function huyHoanTra() {
            $('khung-hoan-tra').style.display = 'none';
            hienThongBao('', '');
        }

        function xacNhanHoanTra() {
            var o = document.querySelectorAll('#hoan-tra-dong input.o-so-luong-hoan');
            var tham = { action: 'hoan_tra', ma_hoa_don: maDangMo };
            var coSanPham = false;

            for (var i = 0; i < o.length; i++) {
                var v = cat(o[i].value);
                if (v === '') { continue; }
                if (!/^[0-9]+$/.test(v)) {
                    hienThongBao('error', 'Số lượng hoàn trả phải là số nguyên không âm.');
                    return;
                }
                if (parseInt(v, 10) > parseInt(o[i].getAttribute('data-max'), 10)) {
                    hienThongBao('error', 'Số lượng hoàn trả vượt quá số lượng có thể hoàn.');
                    return;
                }
                if (parseInt(v, 10) > 0) {
                    tham['so_luong[' + o[i].getAttribute('data-ma') + ']'] = v;
                    coSanPham = true;
                }
            }
            if (!coSanPham) {
                hienThongBao('error', 'Vui lòng chọn ít nhất một sản phẩm và nhập số lượng cần hoàn trả.');
                return;
            }
            var lyDo = cat($('hoan-tra-ly-do').value);
            if (lyDo === '') {
                hienThongBao('error', 'Vui lòng nhập lý do hoàn trả.');
                return;
            }
            tham.ly_do = lyDo;

            guiYeuCau(tham, function (kq) {
                if (kq.ok) {
                    anCacKhung();
                    taiChiTiet(maDangMo, kq.message);
                } else {
                    hienThongBao('error', kq.message);
                }
            });
        }

        // ---- UC33: hủy đơn hàng ----
        var maDonCanHuy = 0;
        var lyDoCanHuy = '';

        function hienThongBaoTrang(loai, noiDung) {
            $('thong-bao-trang').innerHTML = '<div class="alert alert-' + loai + '">' + esc(noiDung) + '</div>';
            window.scrollTo(0, 0);
        }

        function moHuyDon(id, maHienThi) {
            maDonCanHuy = id;
            $('huy-ma-hoa-don').textContent = maHienThi;
            $('huy-ma-xac-nhan').textContent = maHienThi;
            $('huy-thong-bao').innerHTML = '';
            $('huy-chon-ly-do').value = '';
            $('huy-ly-do').value = '';
            $('huy-buoc-nhap').style.display = 'block';
            $('huy-buoc-xac-nhan').style.display = 'none';
            $('modal-huy-don').style.display = 'flex';
            $('huy-ly-do').focus();
        }

        function chonLyDoMau() {
            $('huy-ly-do').value = $('huy-chon-ly-do').value;
            $('huy-ly-do').focus();
        }

        function yeuCauXacNhanHuy() {
            var lyDo = cat($('huy-ly-do').value);
            if (lyDo === '') {
                $('huy-thong-bao').innerHTML = '<div class="alert alert-error">Vui lòng nhập hoặc chọn lý do hủy.</div>';
                return;
            }
            lyDoCanHuy = lyDo;
            $('huy-thong-bao').innerHTML = '';
            $('huy-ly-do-hien-thi').textContent = lyDo;
            $('huy-buoc-nhap').style.display = 'none';
            $('huy-buoc-xac-nhan').style.display = 'block';
        }

        function khongHuy() {
            dongHuyDon();
        }

        function dongHuyDon() {
            $('modal-huy-don').style.display = 'none';
        }

        function xacNhanHuy() {
            guiYeuCau({ action: 'huy_don', ma_hoa_don: maDonCanHuy, ly_do: lyDoCanHuy }, function (kq) {
                if (kq.ok) {
                    dongHuyDon();
                    var oTrangThai = $('trang-thai-' + maDonCanHuy);
                    if (oTrangThai) { oTrangThai.innerHTML = '<span class="badge badge-danger">Đã hủy</span>'; }
                    var nut = $('nut-huy-' + maDonCanHuy);
                    if (nut) { nut.parentNode.removeChild(nut); }
                    hienThongBaoTrang('success', kq.message);
                } else {
                    $('huy-buoc-xac-nhan').style.display = 'none';
                    $('huy-buoc-nhap').style.display = 'block';
                    $('huy-thong-bao').innerHTML = '<div class="alert alert-error">' + esc(kq.message) + '</div>';
                }
            });
        }

        document.onkeydown = function (ev) {
            ev = ev || window.event;
            if (ev.keyCode === 27) { dongChiTiet(); dongHuyDon(); }
        };
    </script>

</body>
</html>