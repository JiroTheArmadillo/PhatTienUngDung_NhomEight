<?php
/**
 * Repository: truy cập CSDL cho chức năng "Xử lý yêu cầu hoàn trả".
 * Bảng sử dụng: phieu_yeu_cau_tra_hang, chi_tiet_tra_hang.
 * Tương thích PHP 5.2.6 + MySQL 5.0 (PDO).
 */
class HoanTraRepository
{
    private $db = null;

    /** Kết nối được tạo khi cần dùng lần đầu (lỗi kết nối sẽ được Control bắt). */
    private function db()
    {
        if ($this->db === null) {
            // Nếu nhóm đã có chỗ kết nối chung thì thay đoạn này bằng nó.
            $host     = 'localhost';
            $database = 'eightmart';
            $user     = 'root';
            $password = '';

            $this->db = new PDO('mysql:host=' . $host . ';dbname=' . $database, $user, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec('SET NAMES utf8');
        }
        return $this->db;
    }

    /** Danh sách yêu cầu: dòng có trạng thái $trangThaiCho xếp trước, rồi mới nhất xếp trước. */
    public function layDanhSach($trangThaiCho)
    {
        $sql = "SELECT y.ma_yeu_cau, y.ma_hoa_don, y.ly_do_hoan_tra, y.ly_do_tu_choi,
                       y.trang_thai, y.ngay_tao,
                       (SELECT IFNULL(SUM(c.so_luong), 0)
                          FROM chi_tiet_tra_hang c
                         WHERE c.ma_yeu_cau = y.ma_yeu_cau) AS tong_so_luong
                  FROM phieu_yeu_cau_tra_hang y
                 ORDER BY (y.trang_thai = ?) DESC, y.ngay_tao DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(array($trangThaiCho));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Trạng thái hiện tại của yêu cầu, null nếu không tồn tại. */
    public function layTrangThai($maYeuCau)
    {
        $stmt = $this->db()->prepare('SELECT trang_thai FROM phieu_yeu_cau_tra_hang WHERE ma_yeu_cau = ?');
        $stmt->execute(array($maYeuCau));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['trang_thai'] : null;
    }

    /**
     * Cập nhật trạng thái bằng 1 câu UPDATE có điều kiện trạng thái hiện tại
     * => nếu người khác xử lý trước thì không dòng nào đổi (trả về false).
     */
    public function capNhatTrangThai($maYeuCau, $trangThaiMoi, $lyDoTuChoi, $maNguoiXuLy, $trangThaiHienTai)
    {
        $stmt = $this->db()->prepare(
            "UPDATE phieu_yeu_cau_tra_hang
                SET trang_thai = ?, ly_do_tu_choi = ?, ma_nguoi_xu_ly = ?, ngay_xu_ly = NOW()
              WHERE ma_yeu_cau = ? AND trang_thai = ?"
        );
        $stmt->execute(array($trangThaiMoi, $lyDoTuChoi, $maNguoiXuLy, $maYeuCau, $trangThaiHienTai));
        return $stmt->rowCount() > 0;
    }
}
