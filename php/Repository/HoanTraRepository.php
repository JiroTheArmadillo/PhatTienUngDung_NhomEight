<?php
/**
 * Repository: truy cập CSDL cho chức năng "Xử lý yêu cầu hoàn trả".
 * Bảng sử dụng: PhieuYeuCauTraHang, ChiTietPhieuYeuCauTraHang.
 * Tương thích PHP 5.2.6 + MySQL 5.0 (MySQLi).
 */
require_once dirname(__FILE__) . '/../../db.php';

class HoanTraRepository
{
    private $db = null;

    /** Kết nối được tạo khi cần dùng lần đầu (lỗi kết nối sẽ được Control bắt). */
    private function db()
    {
        if ($this->db === null) {
            $this->db = eightmart_mysqli_connection();
        }
        return $this->db;
    }

    /** Danh sách yêu cầu: dòng có trạng thái $trangThaiCho xếp trước, rồi mới nhất xếp trước. */
    public function layDanhSach($trangThaiCho)
    {
        $sql = "SELECT y.ID AS ma_yeu_cau, y.HoaDonID AS ma_hoa_don,
                       y.LyDoHoanTra AS ly_do_hoan_tra, y.LyDoTuChoi AS ly_do_tu_choi,
                       y.TrangThai AS trang_thai, y.NgayTao AS ngay_tao,
                       (SELECT IFNULL(SUM(c.SoLuong), 0)
                          FROM ChiTietPhieuYeuCauTraHang c
                         WHERE c.PhieuYeuCauTraHangID = y.ID) AS tong_so_luong
                  FROM PhieuYeuCauTraHang y
                 ORDER BY (y.TrangThai = ?) DESC, y.NgayTao DESC";
        $stmt = eightmart_mysqli_execute($sql, array($trangThaiCho));
        return eightmart_mysqli_fetch_all_assoc($stmt);
    }

    /** Trạng thái hiện tại của yêu cầu, null nếu không tồn tại. */
    public function layTrangThai($maYeuCau)
    {
        $stmt = eightmart_mysqli_execute('SELECT TrangThai AS trang_thai FROM PhieuYeuCauTraHang WHERE ID = ?', array((int)$maYeuCau));
        $row = eightmart_mysqli_fetch_one_assoc($stmt);
        return $row ? $row['trang_thai'] : null;
    }

    /**
     * Cập nhật trạng thái bằng 1 câu UPDATE có điều kiện trạng thái hiện tại
     * => nếu người khác xử lý trước thì không dòng nào đổi (trả về false).
     */
    public function capNhatTrangThai($maYeuCau, $trangThaiMoi, $lyDoTuChoi, $maNguoiXuLy, $trangThaiHienTai)
    {
        $stmt = eightmart_mysqli_execute(
            "UPDATE PhieuYeuCauTraHang
                SET TrangThai = ?, LyDoTuChoi = ?, NhanVienXuLyID = ?, NgayXuLy = NOW()
              WHERE ID = ? AND TrangThai = ?",
            array($trangThaiMoi, $lyDoTuChoi, $maNguoiXuLy, (int)$maYeuCau, $trangThaiHienTai)
        );
        return eightmart_mysqli_affected_rows($stmt) > 0;
    }
}
