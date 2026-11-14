<?php
/**
 * Repository: truy cập CSDL cho các chức năng về đơn hàng:
 * xem danh sách, xem chi tiết, thêm / xóa sản phẩm khỏi đơn hàng (UC31, UC32).
 * Bảng sử dụng: HoaDon, ChiTietHoaDon, SanPham; ThanhToan (chỉ khi sửa đơn chưa thanh toán, để đồng bộ số tiền).
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

    /** Các dòng sản phẩm của 1 đơn hàng. */
    public function layChiTiet($maHoaDon)
    {
        $sql = "SELECT c.SanPhamID AS ma_san_pham, sp.TenSanPham AS ten_san_pham,
                       c.SoLuong AS so_luong, c.DonGia AS don_gia, c.ThanhTien AS thanh_tien
                  FROM ChiTietHoaDon c
                 INNER JOIN SanPham sp ON sp.ID = c.SanPhamID
                 WHERE c.HoaDonID = ?
                 ORDER BY c.SanPhamID";
        $stmt = eightmart_mysqli_execute($sql, array((int)$maHoaDon));
        return eightmart_mysqli_fetch_all_assoc($stmt);
    }

    /** Sản phẩm đang kinh doanh, dùng cho danh sách chọn khi thêm vào đơn. */
    public function layDanhSachSanPham()
    {
        $sql = "SELECT ID AS ma_san_pham, TenSanPham AS ten_san_pham,
                       GiaBan AS gia_ban, SoLuongTon AS so_luong_ton
                  FROM SanPham
                 WHERE TrangThai = 'HoatDong'
                 ORDER BY ID";
        $stmt = eightmart_mysqli_execute($sql);
        return eightmart_mysqli_fetch_all_assoc($stmt);
    }

    /** Thông tin 1 sản phẩm, null nếu không tồn tại. */
    public function laySanPham($maSanPham)
    {
        $sql = "SELECT ID AS ma_san_pham, TenSanPham AS ten_san_pham, GiaBan AS gia_ban,
                       SoLuongTon AS so_luong_ton, TrangThai AS trang_thai
                  FROM SanPham
                 WHERE ID = ?";
        $stmt = eightmart_mysqli_execute($sql, array((int)$maSanPham));
        return eightmart_mysqli_fetch_one_assoc($stmt);
    }

    /** Dòng chi tiết (SoLuong, DonGia) của 1 sản phẩm trong đơn, null nếu chưa có. */
    public function layDong($maHoaDon, $maSanPham)
    {
        $sql = "SELECT SoLuong AS so_luong, DonGia AS don_gia
                  FROM ChiTietHoaDon
                 WHERE HoaDonID = ? AND SanPhamID = ?";
        $stmt = eightmart_mysqli_execute($sql, array((int)$maHoaDon, (int)$maSanPham));
        return eightmart_mysqli_fetch_one_assoc($stmt);
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

    // ---------------- Ghi dữ liệu (gọi trong giao dịch) ----------------

    /** Thêm dòng mới; đơn giá lấy từ GiaBan hiện tại, thành tiền = GiaBan * số lượng. */
    public function themDongMoi($maHoaDon, $maSanPham, $soLuong)
    {
        $stmt = eightmart_mysqli_execute(
            "INSERT INTO ChiTietHoaDon (SoLuong, HoaDonID, DonGia, ThanhTien, SanPhamID)
             SELECT ?, ?, GiaBan, GiaBan * ?, ID FROM SanPham WHERE ID = ?",
            array((int)$soLuong, (int)$maHoaDon, (int)$soLuong, (int)$maSanPham)
        );
        return eightmart_mysqli_affected_rows($stmt) > 0;
    }

    /**
     * Sản phẩm đã có trong đơn: cộng thêm số lượng, giữ nguyên đơn giá cũ.
     * ThanhTien đặt TRƯỚC SoLuong vì MySQL cập nhật các cột theo thứ tự từ trái sang phải.
     */
    public function tangSoLuongDong($maHoaDon, $maSanPham, $soLuongThem)
    {
        $stmt = eightmart_mysqli_execute(
            "UPDATE ChiTietHoaDon
                SET ThanhTien = (SoLuong + ?) * DonGia, SoLuong = SoLuong + ?
              WHERE HoaDonID = ? AND SanPhamID = ?",
            array((int)$soLuongThem, (int)$soLuongThem, (int)$maHoaDon, (int)$maSanPham)
        );
        return eightmart_mysqli_affected_rows($stmt) > 0;
    }

    public function xoaDong($maHoaDon, $maSanPham)
    {
        $stmt = eightmart_mysqli_execute(
            'DELETE FROM ChiTietHoaDon WHERE HoaDonID = ? AND SanPhamID = ?',
            array((int)$maHoaDon, (int)$maSanPham)
        );
        return eightmart_mysqli_affected_rows($stmt) > 0;
    }

    /** Tính lại tổng tiền = tổng thành tiền các dòng chi tiết. */
    public function capNhatTongTien($maHoaDon)
    {
        $stmt = eightmart_mysqli_execute(
            "UPDATE HoaDon
                SET TongTien = (SELECT IFNULL(SUM(c.ThanhTien), 0) FROM ChiTietHoaDon c WHERE c.HoaDonID = ?)
              WHERE ID = ?",
            array((int)$maHoaDon, (int)$maHoaDon)
        );
        eightmart_mysqli_affected_rows($stmt);

        // Giao dịch chuyển khoản đang chờ phải khớp với tổng tiền mới của hóa đơn.
        $stmt = eightmart_mysqli_execute(
            "UPDATE ThanhToan
                SET SoTien = (SELECT TongTien FROM HoaDon WHERE ID = ?)
              WHERE HoaDonID = ? AND TrangThai = 'ChoXacNhan'",
            array((int)$maHoaDon, (int)$maHoaDon)
        );
        eightmart_mysqli_affected_rows($stmt);
    }
}