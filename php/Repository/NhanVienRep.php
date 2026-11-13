<?php
require_once dirname(__FILE__).'/../Entity/NhanVien.php';
require_once dirname(__FILE__).'/../../db.php';
require_once dirname(__FILE__).'/../BaoMat.php';
class NhanVienRep {
    private $db; private $trongGiaoDich = false;
    public function __construct() { $this->db = eightmart_mysqli_connection(); }
    public function batDauGiaoDich() { eightmart_mysqli_begin_transaction(); $this->trongGiaoDich=true; }
    public function xacNhan() { eightmart_mysqli_commit(); $this->trongGiaoDich=false; }
    public function hoanTac() { if($this->trongGiaoDich) {eightmart_mysqli_rollback();$this->trongGiaoDich=false;} }
    public function layTatCa() {
        $st=eightmart_mysqli_execute('SELECT nv.*, tk.TenDangNhap, tk.VaiTro FROM NhanVien nv LEFT JOIN TaiKhoan tk ON tk.NhanVienID=nv.ID ORDER BY nv.ID');
        $out=array(); foreach(eightmart_mysqli_fetch_all_assoc($st) as $r) $out[]=NhanVien::tuDong($r); return $out;
    }
    public function layTheoMa($ma) {
        $st=eightmart_mysqli_execute('SELECT nv.*, tk.TenDangNhap, tk.VaiTro FROM NhanVien nv LEFT JOIN TaiKhoan tk ON tk.NhanVienID=nv.ID WHERE nv.ID=?', array((int)$ma));
        $r=eightmart_mysqli_fetch_one_assoc($st); return $r ? NhanVien::tuDong($r) : null;
    }
    public function tonTaiCccd($cccd,$boQuaMa=0) {
        $st=eightmart_mysqli_execute('SELECT COUNT(*) FROM NhanVien WHERE Cccd=? AND ID<>?', array($cccd,(int)$boQuaMa));return (int)eightmart_mysqli_fetch_value($st)>0;
    }
    public function tonTaiTenDangNhap($ten,$boQuaMa=0) {
        $st=eightmart_mysqli_execute('SELECT COUNT(*) FROM TaiKhoan WHERE TenDangNhap=? AND NhanVienID<>?', array($ten,(int)$boQuaMa));return (int)eightmart_mysqli_fetch_value($st)>0;
    }
    public function them(NhanVien $nv) {
        eightmart_mysqli_execute('INSERT INTO NhanVien (Ten,Cccd,GioiTinh,Sdt,NgaySinh,TrangThai) VALUES (?,?,?,?,?,?)',
            array($nv->getTen(),$nv->getCccd(),$nv->getGioiTinh(),$nv->getSdt(),$nv->getNgaySinh(),NhanVien::TT_HOAT_DONG))->close();
        return (int)$this->db->insert_id;
    }
    public function taoTaiKhoan($ma,$ten,$pass) {
        $st=eightmart_mysqli_execute('INSERT INTO TaiKhoan (TenDangNhap,MatKhau,VaiTro,TrangThai,NhanVienID) VALUES (?,?,?,?,?)',
            array($ten,BaoMat::bamMatKhau($pass),NhanVien::VAI_TRO_NV,NhanVien::TT_HOAT_DONG,(int)$ma));
        $st->close();
        return $ten;
    }
    public function capNhat(NhanVien $nv,$choDoiCccd=true) {
        $old=$this->layTheoMa($nv->getMaNhanVien());if(!$old)throw new Exception('Không tìm thấy nhân viên');
        if(!$choDoiCccd && $nv->getCccd()!==$old->getCccd())throw new Exception('Không có quyền sửa CCCD');
        $st=eightmart_mysqli_execute('UPDATE NhanVien SET Ten=?,Cccd=?,GioiTinh=?,Sdt=?,NgaySinh=? WHERE ID=?',
            array($nv->getTen(),$nv->getCccd(),$nv->getGioiTinh(),$nv->getSdt(),$nv->getNgaySinh(),$nv->getMaNhanVien()));
        $st->close();
        if($old->getTenDangNhap()===$old->getCccd() && $old->getCccd()!==$nv->getCccd()) {
            $st=eightmart_mysqli_execute('UPDATE TaiKhoan SET TenDangNhap=? WHERE NhanVienID=?',
                array($nv->getCccd(),$nv->getMaNhanVien()));
            $st->close();
        }
    }
    public function dangDuocThamChieu($ma) {
        $ma = (int)$ma;
        // Tất cả khóa ngoại trỏ trực tiếp đến NhanVien trong schema.sql.
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
                $query = eightmart_mysqli_execute('SELECT 1 FROM `' . $bang . '` WHERE `' . $tenCot . '`=? LIMIT 1', array($ma));
                if (eightmart_mysqli_fetch_value($query) !== null) { return true; }
            }
        }
        // Bao phủ cả bảng mới bổ sung trong tương lai có FOREIGN KEY đến NhanVien.
        $st = eightmart_mysqli_execute("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE "
            . "WHERE REFERENCED_TABLE_SCHEMA=DATABASE() "
            . "AND LOWER(REFERENCED_TABLE_NAME)='nhanvien' AND REFERENCED_COLUMN_NAME='ID' "
            . "AND LOWER(TABLE_NAME)<>'taikhoan'");
        foreach (eightmart_mysqli_fetch_all_assoc($st) as $r) {
            $table = str_replace('`', '``', $r['TABLE_NAME']);
            $column = str_replace('`', '``', $r['COLUMN_NAME']);
            $q = eightmart_mysqli_execute('SELECT 1 FROM `' . $table . '` WHERE `' . $column . '`=? LIMIT 1', array($ma));
            if (eightmart_mysqli_fetch_value($q) !== null) { return true; }
        }
        return false;
    }
    public function xoa($ma) {
        $this->batDauGiaoDich();
        try {
            if($this->dangDuocThamChieu($ma)) { $this->hoanTac();return false; }
            $st=eightmart_mysqli_execute('DELETE FROM TaiKhoan WHERE NhanVienID=?', array((int)$ma));$st->close();
            $st=eightmart_mysqli_execute('DELETE FROM NhanVien WHERE ID=?', array((int)$ma));
            $ok=eightmart_mysqli_affected_rows($st)===1;if(!$ok)throw new Exception('Không tìm thấy nhân viên');
            $this->xacNhan();return true;
        }catch(Exception $e){$this->hoanTac();throw $e;}
    }
}
