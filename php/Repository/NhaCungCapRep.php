<?php
/** Repository NhaCungCap - CRUD bảng nha_cung_cap bằng PDO. */
require_once dirname(__FILE__) . '/../Entity/NhaCungCap.php';
require_once dirname(__FILE__) . '/KetNoiDB.php';

class NhaCungCapRep
{
    private $db;
    private $trongGiaoDich = false;

    public function __construct() { $this->db = KetNoiDB::layKetNoi(); }

    public function batDauGiaoDich() { $this->db->beginTransaction(); $this->trongGiaoDich = true; }
    public function xacNhan() { $this->db->commit(); $this->trongGiaoDich = false; }
    public function hoanTac() { if ($this->trongGiaoDich) { $this->db->rollBack(); $this->trongGiaoDich = false; } }

    public function layTatCa()
    {
        $danhSach = array();
        $st = $this->db->query('SELECT * FROM nha_cung_cap ORDER BY ma_nha_cung_cap DESC');
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) $danhSach[] = NhaCungCap::tuDong($row);
        return $danhSach;
    }

    public function layTheoMa($ma)
    {
        $st = $this->db->prepare('SELECT * FROM nha_cung_cap WHERE ma_nha_cung_cap = ? LIMIT 1');
        $st->execute(array((int) $ma));
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? NhaCungCap::tuDong($row) : null;
    }

    public function tonTaiTen($ten, $boQuaMa = 0)
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM nha_cung_cap WHERE ten_nha_cung_cap = ? AND ma_nha_cung_cap <> ?');
        $st->execute(array($ten, (int) $boQuaMa));
        return (int) $st->fetchColumn() > 0;
    }

    public function them(NhaCungCap $ncc)
    {
        $st = $this->db->prepare('INSERT INTO nha_cung_cap
            (ten_nha_cung_cap, email, dia_chi, loai_san_pham_cung_cap, trang_thai)
            VALUES (?, ?, ?, ?, ?)');
        $st->execute(array($ncc->getTen(), $ncc->getEmail(), $ncc->getDiaChi(), $ncc->getLoaiSanPham(), $ncc->getTrangThai()));
        return (int) $this->db->lastInsertId();
    }

    public function capNhat(NhaCungCap $ncc)
    {
        $st = $this->db->prepare('UPDATE nha_cung_cap SET ten_nha_cung_cap = ?, email = ?, dia_chi = ?,
            loai_san_pham_cung_cap = ?, trang_thai = ? WHERE ma_nha_cung_cap = ?');
        $st->execute(array($ncc->getTen(), $ncc->getEmail(), $ncc->getDiaChi(), $ncc->getLoaiSanPham(),
            $ncc->getTrangThai(), $ncc->getMa()));
        return $st->rowCount() >= 0;
    }

    public function dangDuocThamChieu($ma)
    {
        foreach (array('SELECT COUNT(*) FROM san_pham WHERE ma_nha_cung_cap = ?',
                       'SELECT COUNT(*) FROM phieu_de_nghi_dat_hang WHERE ma_nha_cung_cap = ?') as $sql) {
            $st = $this->db->prepare($sql);
            $st->execute(array((int) $ma));
            if ((int) $st->fetchColumn() > 0) return true;
        }
        return false;
    }

    public function xoa($ma)
    {
        $st = $this->db->prepare('DELETE FROM nha_cung_cap WHERE ma_nha_cung_cap = ?');
        $st->execute(array((int) $ma));
        return $st->rowCount() === 1;
    }
}
