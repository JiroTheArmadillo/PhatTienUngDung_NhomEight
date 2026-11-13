<?php
session_start();
require_once dirname(__FILE__) . '/../db.php';
require_once dirname(__FILE__) . '/../php/XacThuc.php';
$conn = eightmart_mysqli_connection();
$nguoiDung = XacThuc::layNguoiDung();

$message = '';
$message_type = '';
$ma_hoa_don = isset($_GET['ma_hoa_don']) ? intval($_GET['ma_hoa_don']) : 0;
if (isset($_POST['ma_hoa_don'])) {
    $ma_hoa_don = intval($_POST['ma_hoa_don']);
}
$ma_nhan_vien = $nguoiDung !== null && isset($nguoiDung['ma_nhan_vien'])
    ? (int)$nguoiDung['ma_nhan_vien'] : 0;

if ($ma_nhan_vien <= 0) {
    $message = 'Bạn cần đăng nhập tài khoản nhân viên trước khi thanh toán.';
    $message_type = 'error';
}

// ===== 2. Xu ly xac nhan thanh toan =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['xac_nhan_thanh_toan']) && $ma_nhan_vien > 0) {
    $phuong_thuc = isset($_POST['phuong_thuc']) ? $_POST['phuong_thuc'] : '';
    if ($phuong_thuc != 'TienMat' && $phuong_thuc != 'ChuyenKhoan') {
        $message = 'Phuong thuc thanh toan khong hop le.';
        $message_type = 'error';
    } else {
        // Lay tong tien tu database, khong tin so tien gui tu form.
        $sql = "SELECT ID AS ma_hoa_don, KhachHangID AS ma_khach_hang,
                       TongTien AS tong_tien, TrangThai AS trang_thai
                  FROM HoaDon WHERE ID = " . $ma_hoa_don . " LIMIT 1";
        $result = mysqli_query($conn, $sql);
        $hoa_don = $result ? mysqli_fetch_assoc($result) : false;

        if (!$hoa_don) {
            $message = 'Khong tim thay hoa don.';
            $message_type = 'error';
        } elseif ($hoa_don['trang_thai'] != 'ChoThanhToan') {
            $message = 'Hoa don khong o trang thai ChoThanhToan. Khong the thanh toan lap lai.';
            $message_type = 'error';
        } else {
            $tong_tien = floatval($hoa_don['tong_tien']);
            if ($tong_tien <= 0) {
                $message = 'Tong tien hoa don khong hop le.';
                $message_type = 'error';
            } elseif ($phuong_thuc == 'ChuyenKhoan') {
                // Khong tu dong danh dau thanh cong khi chua xac minh voi ngan hang.
                $ma_giao_dich = isset($_POST['ma_giao_dich_ngan_hang']) ? trim($_POST['ma_giao_dich_ngan_hang']) : '';
                $ma_giao_dich_sql = ($ma_giao_dich == '') ? 'NULL' : "'" . mysqli_real_escape_string($conn, $ma_giao_dich) . "'";
                $sql = "INSERT INTO ThanhToan
                    (HoaDonID, KhachHangID, PhuongThucThanhToan, SoTien, TrangThai, ThoiGian, MaGiaoDichNganHang)
                    VALUES (" . $ma_hoa_don . ", " . (int)$hoa_don['ma_khach_hang'] . ",
                    'ChuyenKhoan', " . $tong_tien . ", 'ChoXacNhan', NOW(), " . $ma_giao_dich_sql . ")";
                if (mysqli_query($conn, $sql)) {
                    $message = 'Da ghi nhan yeu cau chuyen khoan. Giao dich dang ChoXacNhan; nhan vien phai kiem tra tien vao tai khoan ngan hang truoc khi xac nhan thanh cong.';
                    $message_type = 'success';
                } else {
                    $message = 'Khong luu duoc giao dich: ' . mysqli_error($conn);
                    $message_type = 'error';
                }
            } else {
                // Tien mat: kiem tra ton kho va cap nhat trong transaction.
                mysqli_query($conn, 'START TRANSACTION');
                $chi_tiet_sql = "SELECT c.SanPhamID AS ma_san_pham, c.SoLuong AS so_luong,
                    s.TenSanPham AS ten_san_pham, s.SoLuongTon AS so_luong_ton
                    FROM ChiTietHoaDon c INNER JOIN SanPham s ON c.SanPhamID = s.ID
                    WHERE c.HoaDonID = " . $ma_hoa_don;
                $chi_tiet_result = mysqli_query($conn, $chi_tiet_sql);
                $co_chi_tiet = false;
                $du_ton_kho = true;
                $loi_ton_kho = '';
                if ($chi_tiet_result) {
                    while ($dong = mysqli_fetch_assoc($chi_tiet_result)) {
                        $co_chi_tiet = true;
                        if (intval($dong['so_luong_ton']) < intval($dong['so_luong'])) {
                            $du_ton_kho = false;
                            $loi_ton_kho = 'San pham ' . $dong['ten_san_pham'] . ' khong du ton kho.';
                            break;
                        }
                    }
                }

                if (!$chi_tiet_result || !$co_chi_tiet || !$du_ton_kho) {
                    mysqli_query($conn, 'ROLLBACK');
                    $message = !$chi_tiet_result ? 'Khong doc duoc chi tiet hoa don.' : (!$co_chi_tiet ? 'Hoa don chua co san pham.' : $loi_ton_kho);
                    $message_type = 'error';
                } else {
                    $cap_nhat_ton = mysqli_query($conn, "UPDATE SanPham s
                        INNER JOIN ChiTietHoaDon c ON s.ID = c.SanPhamID
                        SET s.SoLuongTon = s.SoLuongTon - c.SoLuong WHERE c.HoaDonID = " . $ma_hoa_don);
                    $insert_tt = mysqli_query($conn, "INSERT INTO ThanhToan
                        (HoaDonID, KhachHangID, PhuongThucThanhToan, SoTien, TrangThai, ThoiGian, NhanVienXacNhanID)
                        VALUES (" . $ma_hoa_don . ", " . (int)$hoa_don['ma_khach_hang'] . ",
                        'TienMat', " . $tong_tien . ", 'ThanhCong', NOW(), " . $ma_nhan_vien . ")");
                    $update_hd = mysqli_query($conn, "UPDATE HoaDon SET TrangThai = 'DaThanhToan'
                        WHERE ID = " . $ma_hoa_don . " AND TrangThai = 'ChoThanhToan'");
                    if ($cap_nhat_ton && $insert_tt && $update_hd && mysqli_affected_rows($conn) == 1) {
                        mysqli_query($conn, 'COMMIT');
                        $message = 'Thanh toan tien mat thanh cong. Hoa don da duoc cap nhat va ton kho da tru.';
                        $message_type = 'success';
                    } else {
                        mysqli_query($conn, 'ROLLBACK');
                        $message = 'Thanh toan that bai. He thong da huy cac thay doi; vui long thu lai. Chi tiet: ' . mysqli_error($conn);
                        $message_type = 'error';
                    }
                }
            }
        }
    }
}

// ===== 3. Lay thong tin hoa don de hien thi =====
$hoa_don = false;
$chi_tiet = false;
if ($ma_hoa_don > 0) {
    $sql_hd = "SELECT d.ID AS ma_hoa_don, d.ThoiGianTao AS ngay_lap,
        d.TrangThai AS trang_thai, d.TongTien AS tong_tien, n.Ten AS ten_nhan_vien,
        NULL AS ten_khach_hang
        FROM HoaDon d INNER JOIN NhanVien n ON d.NhanVienID = n.ID
        WHERE d.ID = " . $ma_hoa_don . " LIMIT 1";
    $result_hd = mysqli_query($conn, $sql_hd);
    if ($result_hd) $hoa_don = mysqli_fetch_assoc($result_hd);
    if ($hoa_don) {
        $chi_tiet = mysqli_query($conn, "SELECT c.SanPhamID AS ma_san_pham,
            s.TenSanPham AS ten_san_pham, c.SoLuong AS so_luong, c.DonGia AS don_gia,
            c.ThanhTien AS thanh_tien FROM ChiTietHoaDon c
            INNER JOIN SanPham s ON c.SanPhamID = s.ID WHERE c.HoaDonID = " . $ma_hoa_don);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Thanh toan - EightMart</title>
<link rel="stylesheet" href="../html/khung-giao-dien.css">
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:24px;color:#222}
.container{max-width:900px;margin:0 auto;background:#fff;padding:24px;border-radius:8px}
h1{margin-top:0;color:#176b45}.message{padding:12px;margin:16px 0;border-radius:5px}.success{background:#e6f6eb;color:#155724}.error{background:#fde8e8;color:#8a1c1c}
table{width:100%;border-collapse:collapse;margin:16px 0}th,td{padding:10px;border:1px solid #ddd;text-align:left}th{background:#f0f3f2}
input[type=text],select{padding:9px;width:100%;max-width:380px;box-sizing:border-box;margin:6px 0 14px}button{padding:11px 18px;border:0;border-radius:5px;background:#16834a;color:white;cursor:pointer}button:disabled{background:#999;cursor:not-allowed}.note{color:#555;font-size:14px}
</style>
</head>
<body>
<div class="container">
<h1>Thanh toan - EightMart</h1>
<?php if ($message != '') { ?><div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
<?php if (!$hoa_don) { ?>
<p>Khong tim thay hoa don. Hay mo trang theo dang <b>thanh_toan.php?ma_hoa_don=1</b> va thay 1 bang ma hoa don can thanh toan.</p>
<?php } else { ?>
<h3>Thong tin hoa don #<?php echo intval($hoa_don['ma_hoa_don']); ?></h3>
<p><b>Ngay lap:</b> <?php echo htmlspecialchars($hoa_don['ngay_lap'], ENT_QUOTES, 'UTF-8'); ?></p>
<p><b>Nhan vien:</b> <?php echo htmlspecialchars($hoa_don['ten_nhan_vien'], ENT_QUOTES, 'UTF-8'); ?></p>
<p><b>Khach hang:</b> <?php echo htmlspecialchars($hoa_don['ten_khach_hang'] ? $hoa_don['ten_khach_hang'] : 'Khach le', ENT_QUOTES, 'UTF-8'); ?></p>
<p><b>Trang thai:</b> <?php echo htmlspecialchars($hoa_don['trang_thai'], ENT_QUOTES, 'UTF-8'); ?></p>
<table><thead><tr><th>San pham</th><th>So luong</th><th>Don gia</th><th>Thanh tien</th></tr></thead><tbody>
<?php if ($chi_tiet) { while ($sp = mysqli_fetch_assoc($chi_tiet)) { ?>
<tr><td><?php echo htmlspecialchars($sp['ten_san_pham'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo intval($sp['so_luong']); ?></td><td><?php echo number_format($sp['don_gia'], 0, ',', '.'); ?> VND</td><td><?php echo number_format($sp['thanh_tien'], 0, ',', '.'); ?> VND</td></tr>
<?php } } ?>
</tbody></table>
<h2>Tong tien: <?php echo number_format($hoa_don['tong_tien'], 0, ',', '.'); ?> VND</h2>
<?php if ($hoa_don['trang_thai'] == 'ChoThanhToan' && $ma_nhan_vien > 0) { ?>
<form method="post" action="thanh_toan.php?ma_hoa_don=<?php echo intval($hoa_don['ma_hoa_don']); ?>" onsubmit="return confirm('Ban chac chan muon ghi nhan thanh toan?');">
<input type="hidden" name="ma_hoa_don" value="<?php echo intval($hoa_don['ma_hoa_don']); ?>">
<label for="phuong_thuc"><b>Phuong thuc thanh toan</b></label><br>
<select name="phuong_thuc" id="phuong_thuc" onchange="document.getElementById('ma_giao_dich_box').style.display=(this.value=='ChuyenKhoan'?'block':'none');">
<option value="TienMat">Tien mat</option><option value="ChuyenKhoan">Chuyen khoan</option>
</select>
<div id="ma_giao_dich_box" style="display:none"><label for="ma_giao_dich_ngan_hang"><b>Ma giao dich ngan hang (neu co)</b></label><br><input type="text" name="ma_giao_dich_ngan_hang" id="ma_giao_dich_ngan_hang" maxlength="100"><p class="note">Nhap ma de doi chieu; ma nay khong tu dong xac nhan tien da vao tai khoan.</p></div>
<p class="note">Tien mat se duoc ghi nhan thanh cong sau khi kiem tra ton kho. Chuyen khoan se o trang thai ChoXacNhan cho den khi nhan vien doi chieu voi ngan hang.</p>
<button type="submit" name="xac_nhan_thanh_toan" value="1">Xac nhan thanh toan</button>
</form>
<?php } else { ?><p class="note">Hoa don khong o trang thai ChoThanhToan hoac ban chua dang nhap nhan vien, nen khong the thanh toan tai day.</p><?php } ?>
<?php } ?>
</div>
</body>
</html>
