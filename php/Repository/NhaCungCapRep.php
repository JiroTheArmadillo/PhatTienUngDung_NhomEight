<?php
/** Repository NhaCungCap - CRUD bảng NhaCungCap bằng MySQLi. */
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
        $st = eightmart_mysqli_execute('SELECT ID AS ma_nha_cung_cap, TenNhaCungCap AS ten_nha_cung_cap,
            Email AS email, DiaChi AS dia_chi, LoaiSanPhamCungCap AS loai_san_pham_cung_cap,
            TrangThai AS trang_thai FROM NhaCungCap ORDER BY ID DESC');
        foreach (eightmart_mysqli_fetch_all_assoc($st) as $row) $danhSach[] = NhaCungCap::tuDong($row);
        return $danhSach;
    }

    public function layTheoMa($ma)
    {
        $st = eightmart_mysqli_execute('SELECT ID AS ma_nha_cung_cap, TenNhaCungCap AS ten_nha_cung_cap,
            Email AS email, DiaChi AS dia_chi, LoaiSanPhamCungCap AS loai_san_pham_cung_cap,
            TrangThai AS trang_thai FROM NhaCungCap WHERE ID = ? LIMIT 1', array((int) $ma));
        $row = eightmart_mysqli_fetch_one_assoc($st);
        return $row ? NhaCungCap::tuDong($row) : null;
    }

    public function tonTaiTen($ten, $boQuaMa = 0)
    {
        $st = eightmart_mysqli_execute('SELECT COUNT(*) FROM NhaCungCap WHERE TenNhaCungCap = ? AND ID <> ?', array($ten, (int) $boQuaMa));
        return (int) eightmart_mysqli_fetch_value($st) > 0;
    }

    public function them(NhaCungCap $ncc)
    {
        $st = eightmart_mysqli_execute('INSERT INTO NhaCungCap
            (TenNhaCungCap, Email, DiaChi, LoaiSanPhamCungCap, TrangThai)
            VALUES (?, ?, ?, ?, ?)',
            array($ncc->getTen(), $ncc->getEmail(), $ncc->getDiaChi(), $ncc->getLoaiSanPham(), $ncc->getTrangThai()));
        eightmart_mysqli_affected_rows($st);
        return (int) $this->db->insert_id;
    }

    public function capNhat(NhaCungCap $ncc)
    {
        $st = eightmart_mysqli_execute('UPDATE NhaCungCap SET TenNhaCungCap = ?, Email = ?, DiaChi = ?,
            LoaiSanPhamCungCap = ?, TrangThai = ? WHERE ID = ?',
            array($ncc->getTen(), $ncc->getEmail(), $ncc->getDiaChi(), $ncc->getLoaiSanPham(),
            $ncc->getTrangThai(), $ncc->getMa()));
        return eightmart_mysqli_affected_rows($st) >= 0;
    }

    public function dangDuocThamChieu($ma)
    {
        foreach (array('SELECT COUNT(*) FROM NhaCungCap_SanPham WHERE NhaCungCapID = ?',
                       'SELECT COUNT(*) FROM ChiTietPhieuDeNghiDatHang WHERE NhaCungCapID = ?') as $sql) {
            $st = eightmart_mysqli_execute($sql, array((int) $ma));
            if ((int) eightmart_mysqli_fetch_value($st) > 0) return true;
        }
        return false;
    }

    public function xoa($ma)
    {
        $st = eightmart_mysqli_execute('DELETE FROM NhaCungCap WHERE ID = ?', array((int) $ma));
        return eightmart_mysqli_affected_rows($st) === 1;
    }
}
