<?php
require_once dirname(__FILE__) . '/../../db.php';

class BaoCao {
    private $db;

    public function __construct() {
        $this->db = eightmart_mysqli_connection();
    }

    private function rows($sql) {
        $result = $this->db->query($sql);
        if (!$result) throw new Exception('Loi truy van bao cao: ' . $this->db->error);
        $data = array();
        while ($row = $result->fetch_assoc()) $data[] = $row;
        $result->free();
        return $data;
    }

    private function id($value) {
        $value = trim((string)$value);
        if (!preg_match('/^[0-9]{1,10}$/', $value) || (float)$value < 1 || (float)$value > 2147483647) {
            throw new Exception('Ma tra cuu khong hop le.');
        }
        return (int)$value;
    }

    public function getDanhSachNhanVien() {
        return $this->rows('SELECT nv.ID AS ma_nv, nv.Ten AS ho_ten, nv.GioiTinh AS gioi_tinh,
            nv.Sdt AS so_dien_thoai, tk.VaiTro AS chuc_vu
            FROM NhanVien nv LEFT JOIN TaiKhoan tk ON tk.NhanVienID = nv.ID ORDER BY nv.ID');
    }

    public function getDanhSachCa() {
        return $this->rows('SELECT ID AS ma_ca, NgayGioBatDau AS bat_dau,
            NgayGioKetThuc AS ket_thuc, TrangThai AS trang_thai,
            NhanVienMoCaID AS ma_nv_mo_ca FROM CaLamViec ORDER BY NgayGioBatDau DESC');
    }

    private function thongKe($field, $id) {
        // Một hóa đơn được tính một lần, kể cả khi có nhiều bản ghi thanh toán.
        $sql = "SELECT COUNT(*) AS tong_hoa_don, COALESCE(SUM(d.TongTien), 0) AS tong_doanh_thu " .
               "FROM HoaDon d WHERE d." . $field . " = " . $id .
               " AND d.TrangThai IN ('DaThanhToan', 'HoanThanh') " .
               "AND EXISTS (SELECT 1 FROM ThanhToan t WHERE t.HoaDonID = d.ID AND t.TrangThai IN ('ThanhCong', 'DaThanhToan'))";
        $rows = $this->rows($sql);
        return $rows[0];
    }

    public function getBaoCaoNhanVien($maNV) {
        $id = $this->id($maNV);
        $list = $this->rows('SELECT nv.ID AS ma_nv, nv.Ten AS ho_ten, nv.GioiTinh AS gioi_tinh,
            nv.Sdt AS so_dien_thoai, tk.VaiTro AS chuc_vu
            FROM NhanVien nv LEFT JOIN TaiKhoan tk ON tk.NhanVienID = nv.ID
            WHERE nv.ID = ' . $id . ' LIMIT 1');
        if (count($list) === 0) return null;
        return array('thong_tin' => $list[0], 'thong_ke' => $this->thongKe('NhanVienID', $id));
    }

    public function getBaoCaoCa($maCa) {
        $id = $this->id($maCa);
        $list = $this->rows('SELECT ID AS ma_ca, NgayGioBatDau AS bat_dau,
            NgayGioKetThuc AS ket_thuc, TrangThai AS trang_thai,
            NhanVienMoCaID AS ma_nv_mo_ca FROM CaLamViec WHERE ID = ' . $id . ' LIMIT 1');
        if (count($list) === 0) return null;
        return array('thong_tin' => $list[0], 'thong_ke' => $this->thongKe('CaLamViecID', $id));
    }
}
?>
