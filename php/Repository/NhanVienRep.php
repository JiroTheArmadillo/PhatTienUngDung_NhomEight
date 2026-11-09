<?php
require_once dirname(__FILE__).'/../Entity/NhanVien.php';
require_once dirname(__FILE__).'/KetNoiDB.php';
require_once dirname(__FILE__).'/../BaoMat.php';
class NhanVienRep {
    private $db; private $trongGiaoDich = false;
    public function __construct() { $this->db = KetNoiDB::layKetNoi(); }
    public function batDauGiaoDich() { $this->db->beginTransaction(); $this->trongGiaoDich=true; }
    public function xacNhan() { $this->db->commit(); $this->trongGiaoDich=false; }
    public function hoanTac() { if($this->trongGiaoDich) {$this->db->rollBack();$this->trongGiaoDich=false;} }
    public function layTatCa() {
        $st=$this->db->query('SELECT nv.*, tk.TenDangNhap, tk.VaiTro FROM NhanVien nv LEFT JOIN TaiKhoan tk ON tk.NhanVienID=nv.ID ORDER BY nv.ID');
        $out=array(); foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r) $out[]=NhanVien::tuDong($r); return $out;
    }
    public function layTheoMa($ma) {
        $st=$this->db->prepare('SELECT nv.*, tk.TenDangNhap, tk.VaiTro FROM NhanVien nv LEFT JOIN TaiKhoan tk ON tk.NhanVienID=nv.ID WHERE nv.ID=?');
        $st->execute(array((int)$ma)); $r=$st->fetch(PDO::FETCH_ASSOC); return $r ? NhanVien::tuDong($r) : null;
    }
    public function tonTaiCccd($cccd,$boQuaMa=0) {
        $st=$this->db->prepare('SELECT COUNT(*) FROM NhanVien WHERE Cccd=? AND ID<>?'); $st->execute(array($cccd,(int)$boQuaMa));return (int)$st->fetchColumn()>0;
    }
    public function tonTaiTenDangNhap($ten,$boQuaMa=0) {
        $st=$this->db->prepare('SELECT COUNT(*) FROM TaiKhoan WHERE TenDangNhap=? AND NhanVienID<>?');$st->execute(array($ten,(int)$boQuaMa));return (int)$st->fetchColumn()>0;
    }
    public function them(NhanVien $nv) {
        $st=$this->db->prepare('INSERT INTO NhanVien (Ten,Cccd,GioiTinh,Sdt,NgaySinh,TrangThai) VALUES (?,?,?,?,?,?)');
        $st->execute(array($nv->getTen(),$nv->getCccd(),$nv->getGioiTinh(),$nv->getSdt(),$nv->getNgaySinh(),NhanVien::TT_HOAT_DONG));
        return (int)$this->db->lastInsertId();
    }
    public function taoTaiKhoan($ma,$ten,$pass) {
        $st=$this->db->prepare('INSERT INTO TaiKhoan (TenDangNhap,MatKhau,VaiTro,TrangThai,NhanVienID) VALUES (?,?,?,?,?)');
        $st->execute(array($ten,BaoMat::bamMatKhau($pass),NhanVien::VAI_TRO_NV,NhanVien::TT_HOAT_DONG,(int)$ma));
        return $ten;
    }
    public function capNhat(NhanVien $nv,$choDoiCccd=true) {
        $old=$this->layTheoMa($nv->getMaNhanVien());if(!$old)throw new Exception('Không tìm thấy nhân viên');
        if(!$choDoiCccd && $nv->getCccd()!==$old->getCccd())throw new Exception('Không có quyền sửa CCCD');
        $st=$this->db->prepare('UPDATE NhanVien SET Ten=?,Cccd=?,GioiTinh=?,Sdt=?,NgaySinh=? WHERE ID=?');
        $st->execute(array($nv->getTen(),$nv->getCccd(),$nv->getGioiTinh(),$nv->getSdt(),$nv->getNgaySinh(),$nv->getMaNhanVien()));
        if($old->getTenDangNhap()===$old->getCccd() && $old->getCccd()!==$nv->getCccd()) {
            $st=$this->db->prepare('UPDATE TaiKhoan SET TenDangNhap=? WHERE NhanVienID=?');
            $st->execute(array($nv->getCccd(),$nv->getMaNhanVien()));
        }
    }
    public function dangDuocThamChieu($ma) {
        $ma = (int)$ma;
        // Tất cả khóa ngoại trỏ trực tiếp đến NhanVien trong schema.sql riêng.
        // Không tính TaiKhoan vì tài khoản của nhân viên được xóa cùng nhân viên.
        $bangLienQuan = array(
            'CaLamViec' => array('NhanVienMoCaID', 'NhanVienDongCaID'),
            'HoaDon' => array('NhanVienID'),
            'PhieuYeuCauTraHang' => array('NhanVienID'),
            'PhieuDeNghiDatHang' => array('NhanVienID'),
            'PhieuNhapKho' => array('NhanVienID')
        );
        foreach ($bangLienQuan as $bang => $cot) {
            foreach ($cot as $tenCot) {
                $query = $this->db->prepare('SELECT 1 FROM `' . $bang . '` WHERE `' . $tenCot . '`=? LIMIT 1');
                $query->execute(array($ma));
                if ($query->fetchColumn() !== false) { return true; }
            }
        }
        // Bao phủ cả bảng mới bổ sung trong tương lai có FOREIGN KEY đến NhanVien.
        $st = $this->db->prepare("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE "
            . "WHERE REFERENCED_TABLE_SCHEMA=DATABASE() "
            . "AND LOWER(REFERENCED_TABLE_NAME)='nhanvien' AND REFERENCED_COLUMN_NAME='ID' "
            . "AND LOWER(TABLE_NAME)<>'taikhoan'");
        $st->execute();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $table = str_replace('`', '``', $r['TABLE_NAME']);
            $column = str_replace('`', '``', $r['COLUMN_NAME']);
            $q = $this->db->prepare('SELECT 1 FROM `' . $table . '` WHERE `' . $column . '`=? LIMIT 1');
            $q->execute(array($ma));
            if ($q->fetchColumn() !== false) { return true; }
        }
        return false;
    }
    public function xoa($ma) {
        $this->batDauGiaoDich();
        try {
            if($this->dangDuocThamChieu($ma)) { $this->hoanTac();return false; }
            $st=$this->db->prepare('DELETE FROM TaiKhoan WHERE NhanVienID=?');$st->execute(array((int)$ma));
            $st=$this->db->prepare('DELETE FROM NhanVien WHERE ID=?');$st->execute(array((int)$ma));
            $ok=$st->rowCount()===1;if(!$ok)throw new Exception('Không tìm thấy nhân viên');
            $this->xacNhan();return true;
        }catch(Exception $e){$this->hoanTac();throw $e;}
    }
}
