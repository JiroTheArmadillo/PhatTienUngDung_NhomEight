<?php
/** Repository NhaCungCap - CRUD bảng nha_cung_cap bằng MySQLi. */
require_once dirname(__FILE__) . '/../Entity/NhaCungCap.php';
require_once dirname(__FILE__) . '/../../db.php';

class NhaCungCapRep
{
    private $db;
    private $trongGiaoDich = false;

    public function __construct() { $this->db = eightmart_mysqli_connection(); }

    public function batDauGiaoDich() { eightmart_mysqli_begin_transaction(); $this->trongGiaoDich = true; }
    public function xacNhan() { eightmart_mysqli_commit(); $this->trongGiaoDich = false; }
    public function hoanTac() { if ($this->trongGiaoDich) { eightmart_mysqli_rollback(); $this->trongGiaoDich = false; } }

    public function layTatCa()
    {
        $danhSach = array();
        $st = eightmart_mysqli_execute('SELECT * FROM nha_cung_cap ORDER BY ma_nha_cung_cap DESC');
        foreach (eightmart_mysqli_fetch_all_assoc($st) as $row) $danhSach[] = NhaCungCap::tuDong($row);
        return $danhSach;
    }

    public function layTheoMa($ma)
    {
        $st = eightmart_mysqli_execute('SELECT * FROM nha_cung_cap WHERE ma_nha_cung_cap = ? LIMIT 1', array((int) $ma));
        $row = eightmart_mysqli_fetch_one_assoc($st);
        return $row ? NhaCungCap::tuDong($row) : null;
    }

    public function tonTaiTen($ten, $boQuaMa = 0)
    {
        $st = eightmart_mysqli_execute('SELECT COUNT(*) FROM nha_cung_cap WHERE ten_nha_cung_cap = ? AND ma_nha_cung_cap <> ?', array($ten, (int) $boQuaMa));
        return (int) eightmart_mysqli_fetch_value($st) > 0;
    }

    public function them(NhaCungCap $ncc)
    {
        $st = eightmart_mysqli_execute('INSERT INTO nha_cung_cap
            (ten_nha_cung_cap, email, dia_chi, loai_san_pham_cung_cap, trang_thai)
            VALUES (?, ?, ?, ?, ?)',
            array($ncc->getTen(), $ncc->getEmail(), $ncc->getDiaChi(), $ncc->getLoaiSanPham(), $ncc->getTrangThai()));
        eightmart_mysqli_affected_rows($st);
        return (int) $this->db->insert_id;
    }

    public function capNhat(NhaCungCap $ncc)
    {
        $st = eightmart_mysqli_execute('UPDATE nha_cung_cap SET ten_nha_cung_cap = ?, email = ?, dia_chi = ?,
            loai_san_pham_cung_cap = ?, trang_thai = ? WHERE ma_nha_cung_cap = ?',
            array($ncc->getTen(), $ncc->getEmail(), $ncc->getDiaChi(), $ncc->getLoaiSanPham(),
            $ncc->getTrangThai(), $ncc->getMa()));
        return eightmart_mysqli_affected_rows($st) >= 0;
    }

    public function dangDuocThamChieu($ma)
    {
        foreach (array('SELECT COUNT(*) FROM san_pham WHERE ma_nha_cung_cap = ?',
                       'SELECT COUNT(*) FROM phieu_de_nghi_dat_hang WHERE ma_nha_cung_cap = ?') as $sql) {
            $st = eightmart_mysqli_execute($sql, array((int) $ma));
            if ((int) eightmart_mysqli_fetch_value($st) > 0) return true;
        }
        return false;
    }

    public function xoa($ma)
    {
        $st = eightmart_mysqli_execute('DELETE FROM nha_cung_cap WHERE ma_nha_cung_cap = ?', array((int) $ma));
        return eightmart_mysqli_affected_rows($st) === 1;
    }
}
