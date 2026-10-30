<?php
// Trang quản lý bán hàng đổ trực tiếp danh sách sản phẩm từ CSDL, giống trang quản lý nhân viên.
session_start();
require_once dirname(__FILE__) . '/../ThanhToan/clsketnoi.php';

function hSanPham($giaTri) { return htmlspecialchars((string) $giaTri, ENT_QUOTES, 'UTF-8'); }

$loiSanPham = '';
$quanLyDanhSachSanPham = array();
$resultSanPham = mysqli_query($conn,
    "SELECT ma_san_pham, ten_san_pham, gia_ban, so_luong_ton
     FROM san_pham
     WHERE trang_thai = 'HoatDong'
     ORDER BY ma_san_pham");

if (!$resultSanPham) {
    $loiSanPham = 'Không thể tải danh sách sản phẩm từ CSDL.';
} else {
    while ($dongSanPham = mysqli_fetch_assoc($resultSanPham)) {
        $quanLyDanhSachSanPham[] = $dongSanPham;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bán hàng - EIGHT STORE</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; min-height: 100vh; font-family: Arial, sans-serif; color: #333; background: #f4f6f8; }
        .sidebar { width: 230px; flex-shrink: 0; background: #2c3e50; color: white; display: flex; flex-direction: column; }
        .logo { padding: 20px; text-align: center; font-weight: bold; background: #1a252f; }
        .sidebar a { padding: 15px 20px; color: #bdc3c7; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; }
        .main { flex: 1; padding: 20px; }
        h2 { text-align: center; margin-bottom: 20px; }
        .nhap-sp { display: flex; gap: 10px; padding: 15px; margin-bottom: 15px; background: white; border-radius: 6px; }
        .khung-danh-sach { margin-bottom: 15px; padding: 15px; background: white; border-radius: 6px; }
        .khung-danh-sach h3 { margin-bottom: 10px; }
        .nhap-sp input { padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        #txtMaSP { flex: 1; } #txtSoLuong { width: 90px; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        input.sl { width: 70px; padding: 6px; }
        .tong { display: flex; justify-content: space-between; align-items: center; padding: 15px; margin-top: 15px; background: white; font-size: 18px; font-weight: bold; }
        button, .btn { border: 0; border-radius: 4px; padding: 10px 14px; color: white; cursor: pointer; text-decoration: none; display: inline-block; }
        .primary { background: #3498db; } .success { background: #27ae60; } .danger { background: #e74c3c; } .secondary { background: #7f8c8d; }
        .modal { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,.5); align-items: center; justify-content: center; }
        .modal-content { width: 420px; max-width: 92%; padding: 25px; background: white; border-radius: 7px; text-align: center; }
        .buoc { display: none; } #buocChon { display: block; }
        .nut-dong { display: flex; gap: 10px; justify-content: center; margin-top: 15px; }
        .nut-dong button, .nut-dong .btn { flex: 1; }
        .loi { min-height: 20px; margin-top: 10px; color: #c0392b; }
        #txtTienKhachDua { width: 100%; padding: 10px; margin-top: 8px; border: 1px solid #ccc; }
        .qr { max-width: 220px; margin: 12px 0; }
        .thongtin { padding: 10px; background: #f2f4f5; border-radius: 5px; text-align: left; line-height: 1.7; }
    </style>
    <link rel="stylesheet" href="giao-dien.css">
</head>
<body>
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

    <div class="main">
        <h2>Lập hóa đơn bán hàng</h2>
        <div class="nhap-sp">
            <input type="text" id="txtMaSP" placeholder="Nhập mã SP, ví dụ SP001" autofocus>
            <input type="number" id="txtSoLuong" min="1" value="1" title="Số lượng">
            <button class="primary" onclick="themSanPham()">Thêm vào hóa đơn</button>
        </div>

        <!-- Danh sách sản phẩm đổ từ server, thao tác mẫu giống trang quản lý nhân viên. -->
        <div class="khung-danh-sach">
            <h3>Danh sách sản phẩm</h3>
            <table>
                <thead><tr><th>Mã SP</th><th>Tên sản phẩm</th><th>Giá bán</th><th>Số lượng tồn</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php if ($loiSanPham): ?>
                    <tr><td colspan="6" style="text-align:center;color:#e74c3c;"><?php echo hSanPham($loiSanPham); ?></td></tr>
                    <?php elseif (count($quanLyDanhSachSanPham) === 0): ?>
                    <tr><td colspan="6" style="text-align:center;">Chưa có dữ liệu sản phẩm.</td></tr>
                    <?php else: foreach ($quanLyDanhSachSanPham as $sp):
                        $maHienThiSP = ma_sp_hien_thi((int) $sp['ma_san_pham']);
                        $tonKho = (int) $sp['so_luong_ton'];
                    ?>
                    <tr>
                        <td><?php echo hSanPham($maHienThiSP); ?></td>
                        <td><?php echo hSanPham($sp['ten_san_pham']); ?></td>
                        <td><?php echo number_format((float) $sp['gia_ban'], 0, ',', '.'); ?> đ</td>
                        <td><?php echo $tonKho; ?></td>
                        <td><?php echo $tonKho > 0 ? 'Còn hàng' : 'Hết hàng'; ?></td>
                        <td>
                            <button type="button" class="primary" style="padding:5px 10px;font-size:12px;"
                                onclick="themSanPhamTuDanhSach('<?php echo hSanPham($maHienThiSP); ?>', <?php echo $tonKho; ?>)"
                                <?php echo $tonKho <= 0 ? 'disabled style="padding:5px 10px;font-size:12px;background:#95a5a6"' : ''; ?>>Thêm vào hóa đơn</button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <table>
            <thead><tr><th>Mã SP</th><th>Tên sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th><th>Thao tác</th></tr></thead>
            <tbody id="dsSanPham"><tr><td colspan="6" style="text-align:center">Chưa có sản phẩm</td></tr></tbody>
        </table>

        <div class="tong">
            <span>Tổng cộng: <span id="lblTongTien" style="color:#e74c3c">0 đ</span></span>
            <button class="success" onclick="moThanhToan()">Thanh toán hóa đơn</button>
        </div>
    </div>

    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div id="buocChon" class="buoc">
                <h3>Chọn phương thức thanh toán</h3>
                <p>Tổng tiền: <b class="tongModal"></b></p><br>
                <button class="primary" style="width:100%;margin-bottom:10px" onclick="chonPhuongThuc('TienMat')">Tiền mặt</button>
                <button id="btnChuyenKhoan" class="success" style="width:100%" onclick="chonPhuongThuc('ChuyenKhoan')">Chuyển khoản ngân hàng</button>
                <div id="loiChon" class="loi"></div>
                <div class="nut-dong"><button class="secondary" onclick="dongThanhToan()">Hủy</button></div>
            </div>

            <div id="buocTienMat" class="buoc">
                <h3>Thanh toán tiền mặt</h3>
                <p>Tổng tiền: <b class="tongModal"></b></p>
                <input type="text" id="txtTienKhachDua" placeholder="Tiền khách đưa" oninput="tinhTienThua()">
                <p style="margin-top:10px">Tiền thừa: <b id="lblTienThua">0 đ</b></p>
                <div id="loiTienMat" class="loi"></div>
                <div class="nut-dong"><button class="secondary" onclick="hienBuoc('buocChon')">Quay lại</button><button id="btnTienMat" class="success" onclick="guiThanhToan('TienMat')">Xác nhận</button></div>
            </div>

            <div id="buocChuyenKhoan" class="buoc">
                <h3>Chuyển khoản</h3>
                <p>Vui lòng quét mã QR trong thời gian quy định.</p>
                <p>Mã QR hết hạn sau <b id="lblDemNguoc">05:00</b></p>
                <img id="imgQR" class="qr" alt="QR chuyển khoản">
                <div class="thongtin">
                    Ngân hàng: <b>MB</b><br>
                    Số tài khoản: <b>0123456789</b><br>
                    Chủ tài khoản: <b>EIGHT STORE</b><br>
                    Nội dung: <b id="lblNoiDungCK"></b><br>
                    Số tiền: <b class="tongModal"></b>
                </div>
                <div id="loiXacNhan" class="loi"></div>
                <div class="nut-dong"><button class="danger" onclick="huyDonChuyenKhoan()">Hủy đơn</button><button class="success" onclick="xacNhanChuyenKhoan()">Đã nhận tiền</button></div>
            </div>

            <div id="buocHetHan" class="buoc"><h3>Mã QR đã hết hạn</h3><p>Hóa đơn đã bị hủy.</p><div class="nut-dong"><button class="secondary" onclick="dongThanhToan()">Đóng</button></div></div>

            <div id="buocXong" class="buoc">
                <h3>Thanh toán thành công</h3><p id="lblKetQua"></p>
                <div class="nut-dong"><a id="lnkHoaDon" class="btn primary" href="#" target="_blank">In hóa đơn</a><button class="success" onclick="hoaDonMoi()">Hóa đơn mới</button></div>
            </div>
        </div>
    </div>

<script>
// Đường dẫn API thanh toán nằm trong thư mục ThanhToan (cùng cấp thư mục html).
const URL_XU_LY = '../ThanhToan/xulythanhtoan.php';
let gioHang = [];
let maThanhToan = 0;
let boDem = null;

const tien = so => Number(so).toLocaleString('vi-VN') + ' đ';
const tongTien = () => gioHang.reduce((tong, sp) => tong + sp.gia * sp.soLuong, 0);

async function goiAPI(url, phuongThuc = 'GET', duLieu = null) {
    let opt = { method: phuongThuc };
    if (duLieu) {
        opt.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
        opt.body = duLieu;
    }
    const res = await fetch(url, opt);
    const text = await res.text();
    try { return JSON.parse(text); } catch (e) { throw new Error('Lỗi server PHP: ' + text.substring(0, 200)); }
}

function veBang() {
    const tbody = document.getElementById('dsSanPham');
    if (!gioHang.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">Chưa có sản phẩm</td></tr>';
    } else {
        tbody.innerHTML = gioHang.map((sp, i) => `<tr>
            <td>${sp.maHienThi}</td><td>${sp.ten}</td><td>${tien(sp.gia)}</td>
            <td><input class="sl" type="number" min="1" max="${sp.ton}" value="${sp.soLuong}" onchange="doiSoLuong(${i},this.value)"></td>
            <td>${tien(sp.gia * sp.soLuong)}</td>
            <td><button class="danger" onclick="xoaDong(${i})">Xóa</button></td></tr>`).join('');
    }
    document.getElementById('lblTongTien').innerText = tien(tongTien());
}

// Lấy mã từ bảng danh sách rồi dùng lại hàm thêm sản phẩm hiện có của hóa đơn.
function themSanPhamTuDanhSach(maSP, tonKho) {
    if (tonKho <= 0) return alert('Sản phẩm đã hết hàng');
    document.getElementById('txtMaSP').value = maSP;
    document.getElementById('txtSoLuong').value = '1';
    themSanPham();
}

async function themSanPham() {
    const oMa = document.getElementById('txtMaSP');
    const ma = oMa.value.trim();
    const soLuong = parseInt(document.getElementById('txtSoLuong').value, 10) || 1;
    if (!ma) return oMa.focus();
    try {
        const kq = await goiAPI(URL_XU_LY + '?action=tim_sp&ma=' + encodeURIComponent(ma));
        if (!kq.ok) return alert(kq.loi);
        const cu = gioHang.find(sp => sp.ma === kq.ma_san_pham);
        const tongSL = soLuong + (cu ? cu.soLuong : 0);
        if (tongSL > kq.ton) return alert('Sản phẩm chỉ còn ' + kq.ton + ' trong kho');
        if (cu) cu.soLuong = tongSL;
        else gioHang.push({ ma:kq.ma_san_pham, maHienThi:kq.ma_hien_thi, ten:kq.ten, gia:kq.gia, ton:kq.ton, soLuong });
        oMa.value = ''; oMa.focus(); veBang();
    } catch (e) { alert(e.message); }
}

function doiSoLuong(i, giaTri) {
    let sl = parseInt(giaTri, 10) || 1;
    if (sl > gioHang[i].ton) sl = gioHang[i].ton;
    gioHang[i].soLuong = Math.max(1, sl); veBang();
}
function xoaDong(i) { gioHang.splice(i, 1); veBang(); }

function hienBuoc(id) {
    document.querySelectorAll('.buoc').forEach(el => el.style.display = 'none');
    document.getElementById(id).style.display = 'block';
    document.querySelectorAll('.tongModal').forEach(el => el.innerText = tien(tongTien()));
}
function moThanhToan() {
    if (!gioHang.length) return alert('Hóa đơn chưa có sản phẩm');
    document.getElementById('paymentModal').style.display = 'flex';
    hienBuoc('buocChon');
}
function dongThanhToan() { document.getElementById('paymentModal').style.display = 'none'; }
function chonPhuongThuc(pt) {
    if (pt === 'TienMat') { document.getElementById('txtTienKhachDua').value = ''; hienBuoc('buocTienMat'); }
    else guiThanhToan('ChuyenKhoan');
}

function docSo(chuoi) { return parseInt(String(chuoi).replace(/[^0-9]/g, ''), 10) || 0; }
function tinhTienThua() {
    const dua = docSo(document.getElementById('txtTienKhachDua').value);
    document.getElementById('lblTienThua').innerText = dua >= tongTien() ? tien(dua - tongTien()) : 'Chưa đủ tiền';
}

async function guiThanhToan(phuongThuc) {
    const ds = new URLSearchParams();
    ds.append('action', 'thanh_toan'); ds.append('phuong_thuc', phuongThuc);
    if (phuongThuc === 'TienMat') {
        const dua = docSo(document.getElementById('txtTienKhachDua').value);
        if (dua < tongTien()) return document.getElementById('loiTienMat').innerText = 'Tiền khách đưa chưa đủ';
        ds.append('tien_khach_dua', dua);
    }
    gioHang.forEach(sp => { ds.append('ma_sp[]', sp.ma); ds.append('so_luong[]', sp.soLuong); });
    try {
        const kq = await goiAPI(URL_XU_LY, 'POST', ds.toString());
        if (!kq.ok) return document.getElementById(phuongThuc === 'TienMat' ? 'loiTienMat' : 'loiChon').innerText = kq.loi;
        if (kq.trang_thai === 'ThanhCong') {
            hienKetQua('Hóa đơn HD' + kq.ma_hoa_don + ' đã thanh toán. Tiền thừa: ' + tien(kq.tien_thua), kq.ma_hoa_don);
        } else {
            maThanhToan = kq.ma_thanh_toan;
            document.getElementById('lblNoiDungCK').innerText = kq.noi_dung_ck;
            hienBuoc('buocChuyenKhoan');
            const qr = 'https://img.vietqr.io/image/MB-0123456789-compact.png?amount=' + Math.round(kq.tong_tien) + '&addInfo=' + encodeURIComponent(kq.noi_dung_ck);
            document.getElementById('imgQR').src = qr;
            demNguoc(kq.thoi_han_giay || 300);
        }
    } catch (e) { document.getElementById('loiXacNhan').innerText = e.message; }
}

function demNguoc(giaTri) {
    if (boDem) clearInterval(boDem);
    let conLai = giaTri;
    const capNhat = () => {
        document.getElementById('lblDemNguoc').innerText = String(Math.floor(conLai/60)).padStart(2,'0') + ':' + String(conLai%60).padStart(2,'0');
        if (conLai <= 0) { clearInterval(boDem); hienBuoc('buocHetHan'); huyTheoMa(); }
        conLai--;
    }; capNhat(); boDem = setInterval(capNhat, 1000);
}
async function huyTheoMa() {
    if (!maThanhToan) return;
    const ds = new URLSearchParams({action:'huy', ma_thanh_toan:String(maThanhToan), ly_do:'het_han'});
    await goiAPI(URL_XU_LY, 'POST', ds.toString()); maThanhToan = 0;
}
async function huyDonChuyenKhoan() {
    if (!confirm('Hủy hóa đơn đang chờ chuyển khoản?')) return;
    const ds = new URLSearchParams({action:'huy', ma_thanh_toan:String(maThanhToan)});
    const kq = await goiAPI(URL_XU_LY, 'POST', ds.toString());
    if (!kq.ok) return alert(kq.loi); clearInterval(boDem); maThanhToan = 0; dongThanhToan();
}
async function xacNhanChuyenKhoan() {
    const ds = new URLSearchParams({action:'xac_nhan', ma_thanh_toan:String(maThanhToan)});
    try {
        const kq = await goiAPI(URL_XU_LY, 'POST', ds.toString());
        if (!kq.ok) return document.getElementById('loiXacNhan').innerText = kq.loi;
        clearInterval(boDem); hienKetQua('Hóa đơn HD' + kq.ma_hoa_don + ' đã nhận chuyển khoản.', kq.ma_hoa_don);
    } catch (e) { document.getElementById('loiXacNhan').innerText = e.message; }
}
function hienKetQua(noiDung, maHoaDon) {
    document.getElementById('lblKetQua').innerText = noiDung;
    document.getElementById('lnkHoaDon').href = '../ThanhToan/hoadon.php?id=' + maHoaDon;
    hienBuoc('buocXong');
}
function hoaDonMoi() { gioHang=[]; maThanhToan=0; veBang(); dongThanhToan(); }

document.getElementById('txtMaSP').addEventListener('keydown', e => { if (e.key === 'Enter') themSanPham(); });
veBang();
</script>
</body>
</html>
