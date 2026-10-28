<?php
// Bật hiển thị lỗi
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Nhúng class Xác thực của Thái, db.php và Control
require_once dirname(__FILE__) . '/../php/XacThuc.php';
require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/../php/Control/LapPhieuDatHangControl.php';

if (!isset($conn)) {
    die("Lỗi: Không tìm thấy kết nối CSDL trong db.php!");
}

// Kích hoạt session và kiểm tra xác thực từ nhánh Thai
XacThuc::batDau();
$nguoiDung = XacThuc::layNguoiDung();

// Nếu chưa đăng nhập -> chuyển hướng về trang Đăng nhập
if ($nguoiDung === null) {
    header("Location: GD_DangNhap.php");
    exit();
}

// Lấy Mã và Tên nhân viên động từ Session đăng nhập
$ma_nhan_vien = 2; // Mặc định fallback
$ten_nhan_vien = 'Nhân viên';

if (is_array($nguoiDung)) {
    $ma_nhan_vien = isset($nguoiDung['ma_nhan_vien']) ? $nguoiDung['ma_nhan_vien'] : (isset($nguoiDung['ma_nv']) ? $nguoiDung['ma_nv'] : 2);
    $ten_nhan_vien = isset($nguoiDung['ho_ten']) ? $nguoiDung['ho_ten'] : (isset($nguoiDung['ten_nv']) ? $nguoiDung['ten_nv'] : (isset($nguoiDung['ten_dang_nhap']) ? $nguoiDung['ten_dang_nhap'] : 'Nhân viên'));
} else if (is_object($nguoiDung)) {
    $ma_nhan_vien = isset($nguoiDung->ma_nhan_vien) ? $nguoiDung->ma_nhan_vien : (isset($nguoiDung->ma_nv) ? $nguoiDung->ma_nv : 2);
    $ten_nhan_vien = isset($nguoiDung->ho_ten) ? $nguoiDung->ho_ten : (isset($nguoiDung->ten_nv) ? $nguoiDung->ten_nv : (isset($nguoiDung->ten_dang_nhap) ? $nguoiDung->ten_dang_nhap : 'Nhân viên'));
}

$control = new LapPhieuDatHangControl($conn);

$loi_he_thong = '';
$thong_bao_thanh_cong = false;
$danh_sach_phieu_vua_tao = array();

// 2. XỬ LÝ SUBMIT FORM (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_lap_phieu'])) {
    $ds_sp_duoc_chon = isset($_POST['chk_sp']) ? $_POST['chk_sp'] : array();
    $ds_ncc          = isset($_POST['ma_ncc']) ? $_POST['ma_ncc'] : array();
    $ds_so_luong      = isset($_POST['so_luong_dat']) ? $_POST['so_luong_dat'] : array();
    $ds_don_gia      = isset($_POST['don_gia_nhap']) ? $_POST['don_gia_nhap'] : array();

    // Gọi Control xử lý với mã nhân viên từ Session
    $res = $control->xuLyLapPhieu($ma_nhan_vien, $ds_sp_duoc_chon, $ds_ncc, $ds_so_luong, $ds_don_gia);

    if ($res) {
        $phieu_ids_str = is_array($res) ? implode(',', $res) : $res;
        // Chuyển hướng sang GET để hiển thị thông báo & tránh lỗi resubmit khi F5
        header("Location: GD_LapPhieuDatHang.php?success=1&phieu_ids=" . $phieu_ids_str);
        exit();
    } else {
        $loi_he_thong = "Vui lòng chọn sản phẩm, chọn Nhà cung cấp và nhập Số lượng hợp lệ (từ 1 đến 100)!";
    }
}

// 3. HIỂN THỊ THÔNG BÁO VÀ PHIẾU VỪA TẠO (GET)
if (isset($_GET['success']) && $_GET['success'] == 1 && !empty($_GET['phieu_ids'])) {
    $thong_bao_thanh_cong = true;
    $list_id = explode(',', $_GET['phieu_ids']);

    foreach ($list_id as $id_phieu) {
        $id_phieu = intval($id_phieu);
        $info = $control->layThongTinPhieuVuaTao($id_phieu);
        $detail = $control->layChiTietPhieuVuaTao($id_phieu);
        if ($info) {
            $danh_sach_phieu_vua_tao[] = array(
                'info' => $info,
                'detail' => $detail
            );
        }
    }
}

// 4. LẤY DỮ LIỆU BẢNG SẢN PHẨM & NCC
$list_ncc  = $control->layDanhSachNCC();
$result_sp = $control->layDanhSachSanPhamCanNhap();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lập Phiếu Đặt Hàng</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { display: flex; height: 100vh; color: #333; font-family: Arial, sans-serif; }
        .sidebar { width: 30%; background-color: #2c3e50; color: white; display: flex; flex-direction: column; }
        .sidebar .logo { padding: 20px; font-size: 20px; font-weight: bold; background-color: #1a252f; text-align: center; }
        .sidebar a { padding: 15px 20px; color: #bdc3c7; text-decoration: none; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .header { background: white; padding: 15px 30px; display: flex; justify-content: flex-end; align-items: center; border-bottom: 1px solid #e0e0e0; }
        .header .user-info { font-size: 14px; }
        .content { padding: 25px 30px; }
        .content h2 { font-size: 22px; margin-bottom: 15px; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background-color: #f8f9fa; color: #2c3e50; font-weight: 600; }
        tr:hover { background-color: #f1f5f9; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-success { background-color: #2ecc71; color: white; }
        input[type="number"], select { padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .phieu-card { background: #fff; border: 2px solid #2ecc71; border-radius: 6px; padding: 20px; margin-bottom: 20px; }
    </style>
    <link rel="stylesheet" href="giao-dien.css">
</head>
<body>

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

    <div class="main-content">
        <div class="header">
            <div class="user-info">
                Nhân viên thao tác: <b><?php echo htmlspecialchars($ten_nhan_vien); ?></b> (Mã NV: <b><?php echo htmlspecialchars($ma_nhan_vien); ?></b>)
            </div>
        </div>

        <div class="content">
            <h2>Lập Phiếu Đề Nghị Đặt Hàng</h2>

            <?php if (!empty($loi_he_thong)): ?>
                <div class="alert-danger">✕ <?php echo htmlspecialchars($loi_he_thong); ?></div>
            <?php endif; ?>

            <?php if ($thong_bao_thanh_cong && !empty($danh_sach_phieu_vua_tao)): ?>
                <div class="alert-success">
                    ✓ <b>Lập phiếu đề nghị đặt hàng thành công!</b> Đã tạo <?php echo count($danh_sach_phieu_vua_tao); ?> phiếu ở trạng thái <b>'ChoDuyet'</b>.
                </div>

                <?php foreach ($danh_sach_phieu_vua_tao as $item): 
                    $info = $item['info'];
                    $detail = $item['detail'];
                ?>
                    <div class="phieu-card">
                        <h3 style="color: #27ae60; margin-bottom: 10px;">THÔNG TIN PHIẾU ĐẶT HÀNG VỪA TẠO</h3>
                        <p><b>Mã phiếu:</b> PDH<?php echo str_pad($info['ma_phieu_de_nghi_dat_hang'], 4, '0', STR_PAD_LEFT); ?></p>
                        <p><b>Ngày lập:</b> <?php echo $info['ngay_lap']; ?></p>
                        <p><b>Nhân viên lập:</b> <?php echo htmlspecialchars($info['ten_nv']); ?></p>
                        <p><b>Nhà cung cấp:</b> <?php echo htmlspecialchars($info['ten_nha_cung_cap']); ?></p>
                        <p><b>Trạng thái:</b> <span style="color: #e67e22; font-weight: bold;"><?php echo $info['trang_thai']; ?></span></p>

                        <h4 style="margin-top: 15px; margin-bottom: 8px;">Chi tiết sản phẩm đặt:</h4>
                        <table style="margin-top: 0;">
                            <thead>
                                <tr style="background: #e8f8f5;">
                                    <th>Tên Sản Phẩm</th>
                                    <th>Số Lượng Đặt</th>
                                    <th>Đơn Giá Dự Kiến</th>
                                    <th>Thành Tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail as $ct): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ct['ten_san_pham']); ?></td>
                                        <td><b><?php echo $ct['so_luong']; ?></b></td>
                                        <td><?php echo number_format($ct['don_gia'], 0, ',', '.'); ?> đ</td>
                                        <td><?php echo number_format($ct['so_luong'] * $ct['don_gia'], 0, ',', '.'); ?> đ</td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="3" style="text-align: right; font-weight: bold;">TỔNG CHI PHÍ DỰ KIẾN:</td>
                                    <td style="color: #e74c3c; font-weight: bold; font-size: 16px;">
                                        <?php echo number_format($info['tong_chi_phi'], 0, ',', '.'); ?> đ
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
                <hr style="margin: 30px 0; border: none; border-top: 1px dashed #ccc;">
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action_lap_phieu" value="1">
                <p style="font-size: 14px; color: #7f8c8d; margin-bottom: 10px;">
                    <i>(Tích chọn sản phẩm, chọn Nhà cung cấp và nhập số lượng đặt để xác nhận)</i>
                </p>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">Chọn</th>
                            <th>Mã SP</th>
                            <th>Tên Sản Phẩm</th>
                            <th style="text-align: center;">Số Lượng Tồn</th>
                            <th>Chọn Nhà Cung Cấp</th>
                            <th>Số Lượng Đặt (≤ 100)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_sp && $result_sp->num_rows > 0): ?>
                            <?php while ($sp = $result_sp->fetch_assoc()): 
                                $sp_id = $sp['ma_san_pham'];
                                $don_gia_dap = $sp['gia_ban'] * 0.7;
                            ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="chk_sp[]" value="<?php echo $sp_id; ?>" style="transform: scale(1.2);">
                                        <input type="hidden" name="don_gia_nhap[<?php echo $sp_id; ?>]" value="<?php echo $don_gia_dap; ?>">
                                    </td>
                                    <td>SP<?php echo str_pad($sp_id, 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><b><?php echo htmlspecialchars($sp['ten_san_pham']); ?></b></td>
                                    <td style="text-align: center;">
                                        <span style="color: #e74c3c; font-weight: bold;"><?php echo $sp['so_luong_ton']; ?></span>
                                    </td>
                                    <td>
                                        <select name="ma_ncc[<?php echo $sp_id; ?>]" style="width: 100%;">
                                            <option value="" selected>-- Chọn Nhà Cung Cấp --</option>
                                            <?php foreach ($list_ncc as $ncc): ?>
                                                <option value="<?php echo $ncc['ma_nha_cung_cap']; ?>">
                                                    <?php echo htmlspecialchars($ncc['ten_nha_cung_cap']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="so_luong_dat[<?php echo $sp_id; ?>]" min="1" max="100" value="" placeholder="Nhập SL" style="width: 90px;">
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #7f8c8d; padding: 20px;">
                                    Hiện không có sản phẩm nào cần lập phiếu đặt hàng.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($result_sp && $result_sp->num_rows > 0): ?>
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn btn-success">Xác Nhận Lập Phiếu Đề Nghị</button>
                    </div>
                <?php endif; ?>
            </form>

        </div>
    </div>

</body>
</html>
