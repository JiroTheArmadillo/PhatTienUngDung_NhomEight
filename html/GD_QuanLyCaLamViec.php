<?php
session_start();
require_once dirname(__FILE__) . '/../php/Control/caLamViecController.php';
require_once dirname(__FILE__) . '/../php/XacThuc.php';

$nguoiDung = XacThuc::yeuCauDangNhap();
$maNhanVien = isset($nguoiDung['ma_nhan_vien']) ? (int)$nguoiDung['ma_nhan_vien'] : 0;

// Thông báo sau khi xử lý xong (bắt đầu / kết thúc ca, hủy đơn)
$flash = isset($_SESSION['flash_ca_lam_viec']) ? $_SESSION['flash_ca_lam_viec'] : null;
unset($_SESSION['flash_ca_lam_viec']);

$controller = new CaLamViecController();
$kq = $controller->layTrangThai($maNhanVien);

$loiTaiDuLieu = null;
$caDangMo = null;
$donDangXuLy = array();
$tongKet = null;
$lichSu = array();
if ($kq['ok']) {
    $caDangMo = $kq['du_lieu']['ca_dang_mo'];
    $donDangXuLy = $kq['du_lieu']['don_dang_xu_ly'];
    $tongKet = $kq['du_lieu']['tong_ket'];
    $lichSu = $kq['du_lieu']['lich_su'];
} else {
    $loiTaiDuLieu = $kq['message'];
}

$token = CaLamViecController::layTokenCsrf();

function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Ca Làm Việc - EIGHT STORE</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        h2 { text-align: center; margin-bottom: 20px; }
        h3 { font-size: 17px; margin-bottom: 12px; }
        .khung { margin-bottom: 15px; padding: 18px; background: white; border-radius: 6px; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        tr:hover td { background-color: #f1f5f9; }
        .text-right { text-align: right; }
        button, .btn { border: 0; border-radius: 4px; padding: 10px 14px; color: white; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-nho { padding: 6px 10px; font-size: 13px; }
        .primary { background: #3498db; } .success { background: #27ae60; } .danger { background: #e74c3c; } .secondary { background: #7f8c8d; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #ecf0f1; color: #555; }
        .badge-success { background: #d5f5e3; color: #1e8449; }
        .alert { padding: 12px 15px; border-radius: 4px; font-size: 14px; margin-bottom: 15px; }
        .alert-error { background: #fadbd8; color: #c0392b; }
        .alert-success { background: #d5f5e3; color: #1e8449; }
        .alert-warning { background: #fdebd0; color: #b9770e; }
        .trang-thai-ca { display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; }
        .thong-tin-ca { line-height: 1.9; font-size: 14px; }
        .empty { text-align: center; color: #888; padding: 20px; }
        .hanh-dong form { display: inline; }
    </style>
    <link rel="stylesheet" href="khung-giao-dien.css">
</head>
<body class="giao-dien-chung">
    <div class="sidebar">
        <div class="logo">EIGHT STORE</div>
        <a href="GD_TrangChu.php">dashboard</a>
        <a href="GD_QuanLyCaLamViec.php" class="active" aria-current="page">Quản lý ca làm việc</a>
        <a href="GD_QuanLyBanHang.php">Quản lý Bán Hàng</a>
        <a href="GD_QuanLyNhanVien.php">quản lý nhân viên</a>
        <a href="GD_QuanLyNhaCungCap.php">quản lý nhà cung cấp</a>
        <a href="GD_LapPhieuDatHang.php">Lập phiếu đặt hàng</a>
        <a href="GD_DuyetPhieuDatHang.php">Duyệt phiếu đặt hàng</a>
        <a href="GD_XuLyHoanTra.php">Xử lý hoàn trả</a>
        <a href="GD_BaoCao.php">Báo cáo</a>
        <a href="logout.php">Đăng xuất</a>
    </div>

    <div class="main">
        <h2>Quản lý ca làm việc</h2>

        <?php if ($flash): ?>
            <div class="alert <?php echo $flash['ok'] ? 'alert-success' : 'alert-error'; ?>"><?php echo e($flash['message']); ?></div>
        <?php endif; ?>

        <?php if ($loiTaiDuLieu): ?>
            <div class="alert alert-error"><?php echo e($loiTaiDuLieu); ?></div>
        <?php else: ?>

            <!-- Trạng thái ca hiện tại -->
            <div class="khung">
                <h3>Ca làm việc hiện tại</h3>
                <?php if ($caDangMo === null): ?>
                    <div class="trang-thai-ca">
                        <div class="thong-tin-ca">Bạn chưa có ca làm việc đang mở. Hãy bắt đầu ca để có thể bán hàng.</div>
                        <form method="post" action="../php/Control/xuLyCaLamViec.php">
                            <input type="hidden" name="token" value="<?php echo e($token); ?>">
                            <input type="hidden" name="action" value="bat_dau">
                            <button type="submit" class="success">Bắt đầu ca làm việc</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="trang-thai-ca">
                        <div class="thong-tin-ca">
                            <span class="badge badge-success">Đang mở</span>
                            <b>Ca #<?php echo (int)$caDangMo['ma_ca']; ?></b><br>
                            Bắt đầu lúc: <b><?php echo e(CaLamViecController::dinhDangNgayGio($caDangMo['bat_dau'])); ?></b><br>
                            <?php if ($tongKet !== null): ?>
                                Đã thanh toán trong ca: <b><?php echo (int)$tongKet['so_don']; ?> đơn</b>,
                                tổng <b><?php echo e(CaLamViecController::dinhDangTien($tongKet['tong_tien'])); ?></b>
                            <?php endif; ?>
                        </div>
                        <form method="post" action="../php/Control/xuLyCaLamViec.php"
                              onsubmit="return confirm('Bạn có chắc muốn kết thúc ca làm việc này?');">
                            <input type="hidden" name="token" value="<?php echo e($token); ?>">
                            <input type="hidden" name="action" value="ket_thuc">
                            <button type="submit" class="danger">Kết thúc ca làm việc</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Đơn đang xử lý: phải hoàn tất hoặc hủy trước khi kết thúc ca (UC36, luồng 4.1) -->
            <?php if ($caDangMo !== null && count($donDangXuLy) > 0): ?>
                <div class="khung">
                    <h3>Đơn hàng đang xử lý trong ca</h3>
                    <div class="alert alert-warning">
                        Còn <?php echo count($donDangXuLy); ?> đơn chưa hoàn tất. Hãy hoàn tất thanh toán ở màn hình Quản lý bán hàng
                        hoặc hủy đơn tại đây trước khi kết thúc ca.
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Thời gian tạo</th>
                                <th class="text-right">Tổng tiền</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($donDangXuLy as $don): ?>
                            <tr>
                                <td><?php echo e(CaLamViecController::hienMaHoaDon($don['ma_hoa_don'])); ?></td>
                                <td><?php echo e(CaLamViecController::dinhDangNgayGio($don['thoi_gian_tao'])); ?></td>
                                <td class="text-right"><?php echo e(CaLamViecController::dinhDangTien($don['tong_tien'])); ?></td>
                                <td class="hanh-dong">
                                    <form method="post" action="../php/Control/xuLyCaLamViec.php"
                                          onsubmit="return confirm('Hủy đơn <?php echo e(CaLamViecController::hienMaHoaDon($don['ma_hoa_don'])); ?>?');">
                                        <input type="hidden" name="token" value="<?php echo e($token); ?>">
                                        <input type="hidden" name="action" value="huy_don">
                                        <input type="hidden" name="ma_hoa_don" value="<?php echo (int)$don['ma_hoa_don']; ?>">
                                        <button type="submit" class="danger btn-nho">Hủy đơn</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Các ca gần đây -->
            <div class="khung">
                <h3>Các ca làm việc gần đây của bạn</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Ca</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($lichSu)): ?>
                        <tr><td colspan="4" class="empty">Chưa có ca làm việc nào.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($lichSu as $ca): ?>
                        <?php $dangMo = (trim($ca['trang_thai']) === CaLamViecController::DANG_MO); ?>
                        <tr>
                            <td>#<?php echo (int)$ca['ma_ca']; ?></td>
                            <td><?php echo e(CaLamViecController::dinhDangNgayGio($ca['bat_dau'])); ?></td>
                            <td><?php echo $ca['ket_thuc'] ? e(CaLamViecController::dinhDangNgayGio($ca['ket_thuc'])) : '—'; ?></td>
                            <td><span class="badge<?php echo $dangMo ? ' badge-success' : ''; ?>"><?php echo $dangMo ? 'Đang mở' : 'Đã đóng'; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>