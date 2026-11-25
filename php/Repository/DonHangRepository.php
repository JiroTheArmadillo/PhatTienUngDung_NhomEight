<?php
/**
 * Repository: truy cập CSDL cho các chức năng về đơn hàng:
 * xem danh sách, xem chi tiết, hủy đơn (UC33), tạo yêu cầu hoàn trả (UC37).
 * Bảng sử dụng: HoaDon, ChiTietHoaDon, SanPham; ThanhToan (hủy đơn); PhieuYeuCauTraHang + ChiTietPhieuYeuCauTraHang (yêu cầu hoàn trả).
 * Tương thích PHP 5.2.6 + MySQL 5.0 (MySQLi).
 */
require_once dirname(__FILE__) . '/../../db.php';

class DonHangRepository
{
    // ---------------- Truy vấn chỉ đọc ----------------

    /** Danh sách đơn hàng, mới nhất xếp trước. */
    public function layDanhSach()
    {
        $sql = "SELECT h.ID AS ma_hoa_don, h.ThoiGianTao AS thoi_gian_tao,
                       h.TrangThai AS trang_thai, h.TongTien AS tong_tien,
                       h.NhanVienID AS ma_nhan_vien
                  FROM HoaDon h
                 ORDER BY h.ThoiGianTao DESC, h.ID DESC";
        $stmt = eightmart_mysqli_execute($sql);
        return eightmart_mysqli_fetch_all_assoc($stmt);
    }

    /** Thông tin chung của 1 đơn hàng, null nếu không tồn tại. */
    public function layThongTin($maHoaDon)
    {
        $sql = "SELECT h.ID AS ma_hoa_don, h.ThoiGianTao AS thoi_gian_tao,
                       h.TrangThai AS trang_thai, h.TongTien AS tong_tien,
                       h.LyDoHuy AS ly_do_huy, h.NhanVienID AS ma_nhan_vien,
                       h.KhachHangID AS ma_khach_hang, h.CaLamViecID AS ma_ca
                  FROM HoaDon h
                 WHERE h.ID = ?";
        $stmt = eightmart_mysqli_execute($sql, array((int)$maHoaDon));
        return eightmart_mysqli_fetch_one_assoc($stmt);
    }

    /** Các dòng sản phẩm của 1 đơn hàng, kèm số lượng đã có trong yêu cầu hoàn trả (Chờ duyệt / Đã duyệt). */
    public function layChiTiet($maHoaDon)
    {
        $sql = "SELECT c.SanPhamID AS ma_san_pham, sp.TenSanPham AS ten_san_pham,
                       c.SoLuong AS so_luong, c.DonGia AS don_gia, c.ThanhTien AS thanh_tien,
                       (SELECT IFNULL(SUM(r.SoLuong), 0)
                          FROM ChiTietPhieuYeuCauTraHang r
                         INNER JOIN PhieuYeuCauTraHang p ON p.ID = r.PhieuYeuCauTraHangID
                         WHERE r.HoaDonID = c.HoaDonID AND r.SanPhamID = c.SanPhamID
                           AND p.TrangThai IN ('ChoDuyet', 'DaDuyet')) AS da_yeu_cau_hoan
                  FROM ChiTietHoaDon c
                 INNER JOIN SanPham sp ON sp.ID = c.SanPhamID
                 WHERE c.HoaDonID = ?
                 ORDER BY c.SanPhamID";
        $stmt = eightmart_mysqli_execute($sql, array((int)$maHoaDon));
        return eightmart_mysqli_fetch_all_assoc($stmt);
    }

    // ---------------- Giao dịch ----------------

    public function batDauGiaoDich() { eightmart_mysqli_begin_transaction(); }
    public function xacNhanGiaoDich() { eightmart_mysqli_commit(); }
    public function hoanTacGiaoDich() { eightmart_mysqli_rollback(); }

    /**
     * Khóa giao dịch chuyển khoản đang ChoXacNhan của hóa đơn. Phải gọi TRƯỚC khoaHoaDon():
     * xac_nhan_chuyen_khoan (ThanhToan/xulythanhtoan.php) cũng khóa ThanhToan trước,
     * nên 2 bên chạy lần lượt và không bị deadlock. Trả về array('ma_thanh_toan' => ...) hoặc null.
     */
    public function khoaThanhToanChoXacNhan($maHoaDon)
    {
        $stmt = eightmart_mysqli_execute(
            "SELECT ID AS ma_thanh_toan FROM ThanhToan WHERE HoaDonID = ? AND TrangThai = 'ChoXacNhan' FOR UPDATE",
            array((int)$maHoaDon)
        );
        return eightmart_mysqli_fetch_one_assoc($stmt);
    }

    /**
     * Khóa dòng hóa đơn (SELECT ... FOR UPDATE) để các thao tác sửa cùng 1 đơn chạy lần lượt.
     * Phải gọi trong giao dịch. Trả về array('trang_thai' => ...) hoặc null nếu không có đơn.
     */
    public function khoaHoaDon($maHoaDon)
    {
        $stmt = eightmart_mysqli_execute('SELECT TrangThai AS trang_thai FROM HoaDon WHERE ID = ? FOR UPDATE', array((int)$maHoaDon));
        return eightmart_mysqli_fetch_one_assoc($stmt);
    }

    // ---------------- UC33: Hủy đơn hàng (gọi trong giao dịch) ----------------

    /** Giao dịch chuyển khoản đang chờ -> ThatBai (cùng cách với danh_dau_huy trong xulythanhtoan.php). */
    public function danhDauThanhToanThatBai($maHoaDon)
    {
        $stmt = eightmart_mysqli_execute(
            "UPDATE ThanhToan SET TrangThai = 'ThatBai' WHERE HoaDonID = ? AND TrangThai = 'ChoXacNhan'",
            array((int)$maHoaDon)
        );
        eightmart_mysqli_affected_rows($stmt);
    }

    /**
     * Đơn -> DaHuy kèm lý do. Chỉ đổi khi trạng thái vẫn là $trangThaiHienTai.
     * ThoiGianTao = ThoiGianTao để thời điểm tạo đơn không bị MySQL tự cập nhật lại.
     */
    public function huyHoaDon($maHoaDon, $lyDo, $trangThaiHienTai)
    {
        $stmt = eightmart_mysqli_execute(
            "UPDATE HoaDon SET ThoiGianTao = ThoiGianTao, TrangThai = 'DaHuy', LyDoHuy = ?
              WHERE ID = ? AND TrangThai = ?",
            array($lyDo, (int)$maHoaDon, $trangThaiHienTai)
        );
        return eightmart_mysqli_affected_rows($stmt) > 0;
    }

    // ---------------- UC37: Tạo yêu cầu hoàn trả (gọi trong giao dịch) ----------------

    /** Tạo phiếu yêu cầu hoàn trả; khách hàng lấy từ hóa đơn. Trả về mã phiếu mới, 0 nếu không tạo được. */
    public function taoPhieuHoanTra($maHoaDon, $lyDo, $maNhanVien, $trangThai)
    {
        $stmt = eightmart_mysqli_execute(
            "INSERT INTO PhieuYeuCauTraHang (HoaDonID, LyDoHoanTra, NhanVienID, KhachHangID, TrangThai)
             SELECT ID, ?, ?, KhachHangID, ? FROM HoaDon WHERE ID = ?",
            array($lyDo, (int)$maNhanVien, $trangThai, (int)$maHoaDon)
        );
        if (eightmart_mysqli_affected_rows($stmt) <= 0) {
            return 0;
        }
        return (int)eightmart_mysqli_fetch_value(eightmart_mysqli_execute('SELECT LAST_INSERT_ID()'));
    }

    public function themChiTietPhieuHoanTra($maPhieu, $maHoaDon, $maSanPham, $soLuong)
    {
        $stmt = eightmart_mysqli_execute(
            "INSERT INTO ChiTietPhieuYeuCauTraHang (PhieuYeuCauTraHangID, HoaDonID, SanPhamID, SoLuong)
             VALUES (?, ?, ?, ?)",
            array((int)$maPhieu, (int)$maHoaDon, (int)$maSanPham, (int)$soLuong)
        );
        return eightmart_mysqli_affected_rows($stmt) > 0;
    }
}