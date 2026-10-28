<?php
require_once dirname(__FILE__) . '/config.php';

class BaoCao {
    private $db;

    public function __construct() {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (mysqli_connect_errno()) {
            throw new Exception('Khong the ket noi MySQL. Kiem tra backend/config.php va WampServer.');
        }
        $this->db->query('SET NAMES utf8');
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
        return $this->rows('SELECT ma_nhan_vien AS ma_nv, ten AS ho_ten, gioi_tinh, sdt AS so_dien_thoai, vai_tro AS chuc_vu FROM nhan_vien ORDER BY ma_nhan_vien');
    }

    public function getDanhSachCa() {
        return $this->rows('SELECT ma_ca, ngay_gio_bat_dau AS bat_dau, ngay_gio_ket_thuc AS ket_thuc, trang_thai, ma_nhan_vien AS ma_nv_mo_ca FROM ca_lam_viec ORDER BY ngay_gio_bat_dau DESC');
    }

    private function thongKe($field, $id) {
        // Mot hoa don duoc tinh mot lan, ke ca khi co nhieu dong thanh_toan.
        // Chi tinh hoa don da thanh toan va co thanh toan thanh cong.
        $sql = "SELECT COUNT(*) AS tong_hoa_don, COALESCE(SUM(d.tong_tien), 0) AS tong_doanh_thu " .
               "FROM don_hang d WHERE d." . $field . " = " . $id .
               " AND d.trang_thai = 'DaThanhToan' " .
               "AND EXISTS (SELECT 1 FROM thanh_toan t WHERE t.ma_hoa_don = d.ma_hoa_don AND t.trang_thai = 'ThanhCong')";
        $rows = $this->rows($sql);
        return $rows[0];
    }

    public function getBaoCaoNhanVien($maNV) {
        $id = $this->id($maNV);
        $list = $this->rows('SELECT ma_nhan_vien AS ma_nv, ten AS ho_ten, gioi_tinh, sdt AS so_dien_thoai, vai_tro AS chuc_vu FROM nhan_vien WHERE ma_nhan_vien = ' . $id . ' LIMIT 1');
        if (count($list) === 0) return null;
        return array('thong_tin' => $list[0], 'thong_ke' => $this->thongKe('ma_nhan_vien', $id));
    }

    public function getBaoCaoCa($maCa) {
        $id = $this->id($maCa);
        $list = $this->rows('SELECT ma_ca, ngay_gio_bat_dau AS bat_dau, ngay_gio_ket_thuc AS ket_thuc, trang_thai, ma_nhan_vien AS ma_nv_mo_ca FROM ca_lam_viec WHERE ma_ca = ' . $id . ' LIMIT 1');
        if (count($list) === 0) return null;
        return array('thong_tin' => $list[0], 'thong_ke' => $this->thongKe('ma_ca', $id));
    }

    public function __destruct() {
        if ($this->db) $this->db->close();
    }
}
?>
