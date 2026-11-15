<?php
/**
 * Repository: truy cập CSDL cho "Bắt đầu ca làm việc" (UC35) và "Kết thúc ca làm việc" (UC36).
 * Bảng sử dụng: CaLamViec, NhanVien (chỉ để khóa), HoaDon + ThanhToan (đơn đang xử lý của ca).
 * Tương thích PHP 5.2.6 + MySQL 5.0 (MySQLi).
 */
require_once dirname(__FILE__) . '/../../db.php';

class CaLamViecRepository
{
    // ---------------- Truy vấn chỉ đọc ----------------

    /** Ca đang mở của nhân viên (cùng cách xác định với lay_ca_dang_mo trong xulythanhtoan.php), null nếu không có. */
    public function layCaDangMo($maNhanVien)
    {
        $sql = "SELECT ID AS ma_ca, NgayGioBatDau AS bat_dau
                  FROM CaLamViec
                 WHERE NhanVienMoCaID = ? AND TrangThai = 'DangMo'
                 ORDER BY ID DESC LIMIT 1";
        $stmt = eightmart_mysqli_execute($sql, array((int)$maNhanVien));
        return eightmart_mysqli_fetch_one_assoc($stmt);
    }

    /** Các ca gần đây của nhân viên, mới nhất xếp trước. */
    public function layLichSu($maNhanVien, $gioiHan)
    {
        $sql = "SELECT ID AS ma_ca, NgayGioBatDau AS bat_dau, NgayGioKetThuc AS ket_thuc, TrangThai AS trang_thai
                  FROM CaLamViec
                 WHERE NhanVienMoCaID = ?
                 ORDER BY ID DESC
                 LIMIT " . (int)$gioiHan;
        $stmt = eightmart_mysqli_execute($sql, array((int)$maNhanVien));
        return eightmart_mysqli_fetch_all_assoc($stmt);
    }

    /** Đơn chưa hoàn tất (đang chờ thanh toán) của ca. */
    public function layDonDangXuLy($maCa, $trangThaiDangXuLy)
    {
        $sql = "SELECT ID AS ma_hoa_don, ThoiGianTao AS thoi_gian_tao, TongTien AS tong_tien
                  FROM HoaDon
                 WHERE CaLamViecID = ? AND TrangThai = ?
                 ORDER BY ID";
        $stmt = eightmart_mysqli_execute($sql, array((int)$maCa, $trangThaiDangXuLy));
        return eightmart_mysqli_fetch_all_assoc($stmt);
    }

    public function demDonDangXuLy($maCa, $trangThaiDangXuLy)
    {
        $stmt = eightmart_mysqli_execute(
            'SELECT COUNT(*) AS so_don FROM HoaDon WHERE CaLamViecID = ? AND TrangThai = ?',
            array((int)$maCa, $trangThaiDangXuLy)
        );
        return (int)eightmart_mysqli_fetch_value($stmt);
    }

    /** Số đơn đã thanh toán và tổng tiền của ca (hiển thị tham khảo). */
    public function layTongKetCa($maCa)
    {
        $sql = "SELECT COUNT(*) AS so_don, IFNULL(SUM(TongTien), 0) AS tong_tien
                  FROM HoaDon
                 WHERE CaLamViecID = ? AND TrangThai IN ('DaThanhToan', 'HoanThanh')";
        $stmt = eightmart_mysqli_execute($sql, array((int)$maCa));
        return eightmart_mysqli_fetch_one_assoc($stmt);
    }

    // ---------------- Giao dịch ----------------

    public function batDauGiaoDich() { eightmart_mysqli_begin_transaction(); }
    public function xacNhanGiaoDich() { eightmart_mysqli_commit(); }
    public function hoanTacGiaoDich() { eightmart_mysqli_rollback(); }

    /**
     * Khóa dòng nhân viên để các thao tác bắt đầu / kết thúc ca của cùng 1 nhân viên chạy lần lượt
     * (bảng CaLamViec không có ràng buộc duy nhất nên nếu không khóa, bấm 2 lần có thể tạo 2 ca đang mở).
     */
    public function khoaNhanVien($maNhanVien)
    {
        $stmt = eightmart_mysqli_execute('SELECT ID FROM NhanVien WHERE ID = ? FOR UPDATE', array((int)$maNhanVien));
        return eightmart_mysqli_fetch_one_assoc($stmt) !== null;
    }

    // ---------------- Ghi dữ liệu (gọi trong giao dịch) ----------------

    public function taoCa($maNhanVien)
    {
        $stmt = eightmart_mysqli_execute(
            "INSERT INTO CaLamViec (NhanVienMoCaID, NgayGioBatDau, TrangThai) VALUES (?, NOW(), 'DangMo')",
            array((int)$maNhanVien)
        );
        return eightmart_mysqli_affected_rows($stmt) > 0;
    }

    /**
     * Đóng ca: ghi thời điểm kết thúc, trạng thái DaDong, nhân viên đóng ca.
     * NgayGioBatDau = NgayGioBatDau để chắc chắn cột TIMESTAMP đầu tiên không bị MySQL tự cập nhật lại.
     */
    public function dongCa($maCa, $maNhanVien)
    {
        $stmt = eightmart_mysqli_execute(
            "UPDATE CaLamViec
                SET NgayGioBatDau = NgayGioBatDau, NgayGioKetThuc = NOW(),
                    TrangThai = 'DaDong', NhanVienDongCaID = ?
              WHERE ID = ? AND TrangThai = 'DangMo'",
            array((int)$maNhanVien, (int)$maCa)
        );
        return eightmart_mysqli_affected_rows($stmt) > 0;
    }

    // ---------------- Hủy đơn đang xử lý (để có thể kết thúc ca) ----------------

    /** Khóa giao dịch chuyển khoản đang chờ của hóa đơn (cùng thứ tự khóa với xac_nhan_chuyen_khoan: ThanhToan trước). */
    public function khoaThanhToanChoXacNhan($maHoaDon)
    {
        $stmt = eightmart_mysqli_execute(
            "SELECT ID AS ma_thanh_toan FROM ThanhToan WHERE HoaDonID = ? AND TrangThai = 'ChoXacNhan' FOR UPDATE",
            array((int)$maHoaDon)
        );
        return eightmart_mysqli_fetch_one_assoc($stmt);
    }

    /** Khóa dòng hóa đơn, trả về array('trang_thai', 'ma_ca') hoặc null. */
    public function khoaHoaDon($maHoaDon)
    {
        $stmt = eightmart_mysqli_execute(
            'SELECT TrangThai AS trang_thai, CaLamViecID AS ma_ca FROM HoaDon WHERE ID = ? FOR UPDATE',
            array((int)$maHoaDon)
        );
        return eightmart_mysqli_fetch_one_assoc($stmt);
    }

    public function danhDauThanhToanThatBai($maHoaDon)
    {
        $stmt = eightmart_mysqli_execute(
            "UPDATE ThanhToan SET TrangThai = 'ThatBai' WHERE HoaDonID = ? AND TrangThai = 'ChoXacNhan'",
            array((int)$maHoaDon)
        );
        eightmart_mysqli_affected_rows($stmt);
    }

    /** ThoiGianTao = ThoiGianTao để thời điểm tạo đơn không bị MySQL tự cập nhật lại. */
    public function huyHoaDon($maHoaDon, $lyDo, $trangThaiDangXuLy)
    {
        $stmt = eightmart_mysqli_execute(
            "UPDATE HoaDon SET ThoiGianTao = ThoiGianTao, TrangThai = 'DaHuy', LyDoHuy = ?
              WHERE ID = ? AND TrangThai = ?",
            array($lyDo, (int)$maHoaDon, $trangThaiDangXuLy)
        );
        return eightmart_mysqli_affected_rows($stmt) > 0;
    }
}