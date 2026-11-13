<?php
// Xu ly thanh toan cho trang html/GD_QuanLyBanHang.php
// Cac action:
//   GET  ?action=tim_sp&ma=SP001        -> tra thong tin san pham
//   POST action=thanh_toan              -> tao don hang + thanh toan
//   POST action=xac_nhan                -> xac nhan da nhan tien chuyen khoan
//   POST action=huy                     -> huy don dang cho chuyen khoan
session_start();
require_once dirname(__FILE__) . '/../db.php';
require_once dirname(__FILE__) . '/helpers.php';
require_once dirname(__FILE__) . '/../php/XacThuc.php';
$conn = eightmart_mysqli_connection();
header('Content-Type: application/json; charset=utf-8');

// Thoi han cua ma QR chuyen khoan (giay). 300 giay = 5 phut.
define('THOI_HAN_QR', 300);

$nguoiDung = XacThuc::layNguoiDung();
if ($nguoiDung === null || !isset($nguoiDung['ma_nhan_vien'])
    || (int)$nguoiDung['ma_nhan_vien'] <= 0) {
    tra_ve(array('ok' => false, 'loi' => 'Phiên đăng nhập không hợp lệ.'));
}
$ma_nhan_vien = (int)$nguoiDung['ma_nhan_vien'];

$action = isset($_GET['action']) ? $_GET['action'] : lay_post('action');

if ($action == 'tim_sp') {
    tim_san_pham($conn);
} elseif ($action == 'thanh_toan') {
    thanh_toan($conn, $ma_nhan_vien);
} elseif ($action == 'xac_nhan') {
    xac_nhan_chuyen_khoan($conn, $ma_nhan_vien);
} elseif ($action == 'huy') {
    huy_chuyen_khoan($conn);
} else {
    tra_ve(array('ok' => false, 'loi' => 'Thao tác không hợp lệ'));
}

// ---------- Ham dung chung ----------

function tra_ve($du_lieu)
{
    echo json_encode($du_lieu);
    exit;
}

// Huy giao dich (rollback) roi bao loi
function bao_loi($conn, $thong_bao)
{
    mysqli_rollback($conn);
    mysqli_autocommit($conn, true);
    tra_ve(array('ok' => false, 'loi' => $thong_bao));
}

// Chay cau SQL, loi thi rollback
function chay($conn, $sql)
{
    $kq = mysqli_query($conn, $sql);
    if ($kq === false) {
        bao_loi($conn, 'Lỗi CSDL: ' . mysqli_error($conn));
    }
    return $kq;
}

// Tim ca dang mo cua nhan vien, chua co thi tu mo ca moi
function lay_ca_dang_mo($conn, $ma_nhan_vien)
{
    $kq = chay($conn, "SELECT ID AS ma_ca FROM CaLamViec
                       WHERE NhanVienMoCaID = $ma_nhan_vien AND TrangThai = 'DangMo'
                       ORDER BY ID DESC LIMIT 1");
    $dong = mysqli_fetch_assoc($kq);
    if ($dong) {
        return intval($dong['ma_ca']);
    }
    chay($conn, "INSERT INTO CaLamViec (NhanVienMoCaID, NgayGioBatDau, TrangThai)
                 VALUES ($ma_nhan_vien, NOW(), 'DangMo')");
    return mysqli_insert_id($conn);
}

// Danh dau thanh toan that bai va huy don hang
function danh_dau_huy($conn, $ma_thanh_toan, $ma_hoa_don, $ly_do)
{
    $ly_do_sql = mysqli_real_escape_string($conn, $ly_do);
    chay($conn, "UPDATE ThanhToan SET TrangThai = 'ThatBai' WHERE ID = $ma_thanh_toan");
    chay($conn, "UPDATE HoaDon SET TrangThai = 'DaHuy', LyDoHuy = '$ly_do_sql'
                 WHERE ID = $ma_hoa_don");
}

// ---------- 1. Tim san pham theo ma ----------

function tim_san_pham($conn)
{
    $ma = ma_sp_tu_chuoi(isset($_GET['ma']) ? $_GET['ma'] : '');
    if ($ma <= 0) {
        tra_ve(array('ok' => false, 'loi' => 'Mã sản phẩm không hợp lệ'));
    }
    $kq = mysqli_query($conn, "SELECT ID AS ma_san_pham, TenSanPham AS ten_san_pham,
                                      GiaBan AS gia_ban, SoLuongTon AS so_luong_ton
                               FROM SanPham
                               WHERE ID = $ma AND TrangThai = 'HoatDong'");
    $sp = $kq ? mysqli_fetch_assoc($kq) : null;
    if (!$sp) {
        tra_ve(array('ok' => false, 'loi' => 'Không tìm thấy sản phẩm ' . ma_sp_hien_thi($ma)));
    }
    tra_ve(array(
        'ok' => true,
        'ma_san_pham' => intval($sp['ma_san_pham']),
        'ma_hien_thi' => ma_sp_hien_thi($sp['ma_san_pham']),
        'ten' => $sp['ten_san_pham'],
        'gia' => floatval($sp['gia_ban']),
        'ton' => intval($sp['so_luong_ton'])
    ));
}

// ---------- 2. Thanh toan hoa don ----------

function thanh_toan($conn, $ma_nhan_vien)
{
    $phuong_thuc = lay_post('phuong_thuc');
    if ($phuong_thuc != 'TienMat' && $phuong_thuc != 'ChuyenKhoan') {
        tra_ve(array('ok' => false, 'loi' => 'Phương thức thanh toán không hợp lệ'));
    }

    // Gom gio hang, cong don neu trung ma
    $ds_ma = lay_post('ma_sp', array());
    $ds_sl = lay_post('so_luong', array());
    if (!is_array($ds_ma) || !is_array($ds_sl) || count($ds_ma) == 0 || count($ds_ma) != count($ds_sl)) {
        tra_ve(array('ok' => false, 'loi' => 'Hóa đơn chưa có sản phẩm'));
    }
    $gio_hang = array();
    for ($i = 0; $i < count($ds_ma); $i++) {
        $ma = intval($ds_ma[$i]);
        $sl = intval($ds_sl[$i]);
        if ($ma <= 0 || $sl <= 0) {
            tra_ve(array('ok' => false, 'loi' => 'Số lượng phải lớn hơn 0'));
        }
        $gio_hang[$ma] = (isset($gio_hang[$ma]) ? $gio_hang[$ma] : 0) + $sl;
    }

    // Bat dau giao dich: loi o bat ky buoc nao thi huy het
    mysqli_autocommit($conn, false);
    $ma_ca = lay_ca_dang_mo($conn, $ma_nhan_vien);

    // Khong co thanh vien: moi lan thanh toan tu phat sinh 1 ma khach hang moi
    chay($conn, "INSERT INTO KhachHang () VALUES ()");
    $ma_khach_hang = mysqli_insert_id($conn);

    // Lay gia tu CSDL (khong tin gia gui tu trinh duyet) va kiem tra ton kho
    $tong_tien = 0;
    $chi_tiet = array();
    foreach ($gio_hang as $ma => $sl) {
        $kq = chay($conn, "SELECT TenSanPham AS ten_san_pham, GiaBan AS gia_ban,
                                  SoLuongTon AS so_luong_ton FROM SanPham
                           WHERE ID = $ma AND TrangThai = 'HoatDong' FOR UPDATE");
        $sp = mysqli_fetch_assoc($kq);
        if (!$sp) {
            bao_loi($conn, 'Sản phẩm ' . ma_sp_hien_thi($ma) . ' không còn bán');
        }
        if (intval($sp['so_luong_ton']) < $sl) {
            bao_loi($conn, $sp['ten_san_pham'] . ' chỉ còn ' . $sp['so_luong_ton'] . ' trong kho');
        }
        $don_gia = floatval($sp['gia_ban']);
        $thanh_tien = $don_gia * $sl;
        $tong_tien += $thanh_tien;
        $chi_tiet[] = array('ma' => $ma, 'sl' => $sl, 'don_gia' => $don_gia, 'thanh_tien' => $thanh_tien);
    }

    $tien_khach_dua = floatval(lay_post('tien_khach_dua', 0));
    if ($phuong_thuc == 'TienMat' && $tien_khach_dua < $tong_tien) {
        bao_loi($conn, 'Tiền khách đưa chưa đủ');
    }

    // Tao don hang
    chay($conn, "INSERT INTO HoaDon
                 (NhanVienID, KhachHangID, CaLamViecID, ThoiGianTao, TrangThai, TongTien)
                 VALUES ($ma_nhan_vien, $ma_khach_hang, $ma_ca, NOW(), 'ChoThanhToan', $tong_tien)");
    $ma_hoa_don = mysqli_insert_id($conn);

    foreach ($chi_tiet as $ct) {
        chay($conn, "INSERT INTO ChiTietHoaDon (HoaDonID, SanPhamID, SoLuong, DonGia, ThanhTien)
                     VALUES ($ma_hoa_don, {$ct['ma']}, {$ct['sl']}, {$ct['don_gia']}, {$ct['thanh_tien']})");
    }

    if ($phuong_thuc == 'TienMat') {
        // Tien mat: thu ngan nhan tien ngay -> thanh cong luon, tru kho
        chay($conn, "INSERT INTO ThanhToan
                     (HoaDonID, KhachHangID, PhuongThucThanhToan, SoTien, TrangThai, ThoiGian, NhanVienXacNhanID)
                     VALUES ($ma_hoa_don, $ma_khach_hang, 'TienMat', $tong_tien, 'ThanhCong', NOW(), $ma_nhan_vien)");
        $ma_thanh_toan = mysqli_insert_id($conn);
        foreach ($chi_tiet as $ct) {
            chay($conn, "UPDATE SanPham SET SoLuongTon = SoLuongTon - {$ct['sl']} WHERE ID = {$ct['ma']}");
        }
        chay($conn, "UPDATE HoaDon SET TrangThai = 'DaThanhToan' WHERE ID = $ma_hoa_don");
        $trang_thai = 'ThanhCong';
    } else {
        // Chuyen khoan: cho thu ngan kiem tra tai khoan roi moi xac nhan
        chay($conn, "INSERT INTO ThanhToan
                     (HoaDonID, KhachHangID, PhuongThucThanhToan, SoTien, TrangThai, ThoiGian)
                     VALUES ($ma_hoa_don, $ma_khach_hang, 'ChuyenKhoan', $tong_tien, 'ChoXacNhan', NOW())");
        $ma_thanh_toan = mysqli_insert_id($conn);
        $trang_thai = 'ChoXacNhan';
    }

    mysqli_commit($conn);
    mysqli_autocommit($conn, true);

    tra_ve(array(
        'ok' => true,
        'ma_hoa_don' => $ma_hoa_don,
        'ma_thanh_toan' => $ma_thanh_toan,
        'ma_khach_hang' => $ma_khach_hang,
        'tong_tien' => $tong_tien,
        'tien_thua' => ($phuong_thuc == 'TienMat') ? $tien_khach_dua - $tong_tien : 0,
        'trang_thai' => $trang_thai,
        'noi_dung_ck' => 'EIGHT HD' . $ma_hoa_don,
        'thoi_han_giay' => THOI_HAN_QR
    ));
}

// ---------- 3. Xac nhan da nhan tien chuyen khoan ----------

function xac_nhan_chuyen_khoan($conn, $ma_nhan_vien)
{
    $ma_thanh_toan = intval(lay_post('ma_thanh_toan'));

    mysqli_autocommit($conn, false);

    $kq = chay($conn, "SELECT HoaDonID AS ma_hoa_don,
                              TIMESTAMPDIFF(SECOND, ThoiGian, NOW()) AS so_giay_da_qua
                       FROM ThanhToan
                       WHERE ID = $ma_thanh_toan AND TrangThai = 'ChoXacNhan' FOR UPDATE");
    $tt = mysqli_fetch_assoc($kq);
    if (!$tt) {
        bao_loi($conn, 'Giao dịch không tồn tại hoặc đã xử lý');
    }
    $ma_hoa_don = intval($tt['ma_hoa_don']);

    // Qua thoi han QR thi khong cho xac nhan nua, huy don luon
    if (intval($tt['so_giay_da_qua']) > THOI_HAN_QR) {
        danh_dau_huy($conn, $ma_thanh_toan, $ma_hoa_don, 'Mã QR hết hạn');
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        tra_ve(array('ok' => false, 'het_han' => true, 'loi' => 'Mã QR đã hết hạn, hóa đơn đã bị hủy'));
    }

    // Kiem tra lai ton kho luc nhan tien roi moi tru kho
    $kq = chay($conn, "SELECT ct.SanPhamID AS ma_san_pham, ct.SoLuong AS so_luong,
                              sp.TenSanPham AS ten_san_pham, sp.SoLuongTon AS so_luong_ton
                       FROM ChiTietHoaDon ct JOIN SanPham sp ON sp.ID = ct.SanPhamID
                       WHERE ct.HoaDonID = $ma_hoa_don FOR UPDATE");
    $ds = array();
    while ($dong = mysqli_fetch_assoc($kq)) {
        if (intval($dong['so_luong_ton']) < intval($dong['so_luong'])) {
            bao_loi($conn, $dong['ten_san_pham'] . ' không còn đủ hàng');
        }
        $ds[] = $dong;
    }
    foreach ($ds as $dong) {
        chay($conn, "UPDATE SanPham SET SoLuongTon = SoLuongTon - " . intval($dong['so_luong']) .
                    " WHERE ID = " . intval($dong['ma_san_pham']));
    }

    chay($conn, "UPDATE ThanhToan
                 SET TrangThai = 'ThanhCong', ThoiGian = NOW(), NhanVienXacNhanID = $ma_nhan_vien
                 WHERE ID = $ma_thanh_toan");
    chay($conn, "UPDATE HoaDon SET TrangThai = 'DaThanhToan' WHERE ID = $ma_hoa_don");

    mysqli_commit($conn);
    mysqli_autocommit($conn, true);
    tra_ve(array('ok' => true, 'ma_hoa_don' => $ma_hoa_don, 'trang_thai' => 'ThanhCong'));
}

// ---------- 4. Huy don dang cho chuyen khoan ----------

function huy_chuyen_khoan($conn)
{
    $ma_thanh_toan = intval(lay_post('ma_thanh_toan'));
    mysqli_autocommit($conn, false);

    $kq = chay($conn, "SELECT HoaDonID AS ma_hoa_don FROM ThanhToan
                       WHERE ID = $ma_thanh_toan AND TrangThai = 'ChoXacNhan' FOR UPDATE");
    $tt = mysqli_fetch_assoc($kq);
    if (!$tt) {
        bao_loi($conn, 'Giao dịch không tồn tại hoặc đã xử lý');
    }
    $ma_hoa_don = intval($tt['ma_hoa_don']);

    $ly_do = (lay_post('ly_do') == 'het_han') ? 'Mã QR hết hạn' : 'Khách không chuyển khoản';
    danh_dau_huy($conn, $ma_thanh_toan, $ma_hoa_don, $ly_do);

    mysqli_commit($conn);
    mysqli_autocommit($conn, true);
    tra_ve(array('ok' => true, 'ma_hoa_don' => $ma_hoa_don, 'trang_thai' => 'DaHuy'));
}
