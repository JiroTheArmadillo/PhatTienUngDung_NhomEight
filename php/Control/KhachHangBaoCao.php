<?php
require_once dirname(__FILE__) . '/../Repository/KetNoiDB.php';
class KhachHangBaoCao {
    private $db;
    function __construct() { $this->db = KetNoiDB::layKetNoi(); }
    function danhSachKhachHang() { return $this->db->query('SELECT ma_khach_hang,ten,sdt,dia_chi FROM khach_hang ORDER BY ma_khach_hang DESC')->fetchAll(PDO::FETCH_ASSOC); }
    function themKhachHang($ten,$sdt,$diachi) {
        $q=$this->db->prepare('INSERT INTO khach_hang(ten,sdt,dia_chi) VALUES (?,?,?)');
        $q->execute(array($ten,$sdt===''?null:$sdt,$diachi===''?null:$diachi));
        return $this->db->lastInsertId();
    }
    function doanhThuTungCa() {
        return $this->db->query("SELECT c.ma_ca,c.ngay_gio_bat_dau,n.ten AS nhan_vien,COUNT(d.ma_hoa_don) AS so_don,COALESCE(SUM(d.tong_tien),0) AS doanh_thu FROM ca_lam_viec c JOIN nhan_vien n ON n.ma_nhan_vien=c.ma_nhan_vien LEFT JOIN don_hang d ON d.ma_ca=c.ma_ca AND d.trang_thai='DaThanhToan' AND EXISTS(SELECT 1 FROM thanh_toan t WHERE t.ma_hoa_don=d.ma_hoa_don AND t.trang_thai='ThanhCong') GROUP BY c.ma_ca,c.ngay_gio_bat_dau,n.ten ORDER BY c.ma_ca DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
    function layKhachHang($id) { $q=$this->db->prepare('SELECT ma_khach_hang,ten,sdt,dia_chi FROM khach_hang WHERE ma_khach_hang=?');$q->execute(array($id));return $q->fetch(PDO::FETCH_ASSOC); }
    function capNhat($id,$ten,$sdt,$diachi) { $q=$this->db->prepare('UPDATE khach_hang SET ten=?,sdt=?,dia_chi=? WHERE ma_khach_hang=?');return $q->execute(array($ten,$sdt===''?null:$sdt,$diachi,$id)); }
    function danhSachCa() { return $this->db->query('SELECT c.ma_ca,c.ngay_gio_bat_dau,c.ngay_gio_ket_thuc,n.ten AS nhan_vien FROM ca_lam_viec c JOIN nhan_vien n ON n.ma_nhan_vien=c.ma_nhan_vien ORDER BY c.ma_ca DESC')->fetchAll(PDO::FETCH_ASSOC); }
    function doanhThuCa($id) { $q=$this->db->prepare("SELECT COUNT(*) AS so_don,COALESCE(SUM(d.tong_tien),0) AS doanh_thu FROM don_hang d WHERE d.ma_ca=? AND d.trang_thai='DaThanhToan' AND EXISTS (SELECT 1 FROM thanh_toan t WHERE t.ma_hoa_don=d.ma_hoa_don AND t.trang_thai='ThanhCong')");$q->execute(array($id));return $q->fetch(PDO::FETCH_ASSOC); }
    function tongDoanhThu() { return $this->db->query("SELECT COUNT(*) AS so_don,COALESCE(SUM(d.tong_tien),0) AS doanh_thu FROM don_hang d WHERE d.trang_thai='DaThanhToan' AND EXISTS (SELECT 1 FROM thanh_toan t WHERE t.ma_hoa_don=d.ma_hoa_don AND t.trang_thai='ThanhCong')")->fetch(PDO::FETCH_ASSOC); }
    function doanhThuNgay() { return $this->db->query("SELECT DATE(d.ngay_lap) AS ngay,COUNT(*) AS so_don,SUM(d.tong_tien) AS doanh_thu FROM don_hang d WHERE d.trang_thai='DaThanhToan' AND EXISTS (SELECT 1 FROM thanh_toan t WHERE t.ma_hoa_don=d.ma_hoa_don AND t.trang_thai='ThanhCong') GROUP BY DATE(d.ngay_lap) ORDER BY ngay DESC")->fetchAll(PDO::FETCH_ASSOC); }

    // Lọc theo ngày/tháng/quý/năm; chỉ tính đơn đã thanh toán thành công.
    function baoCaoTheoKy($loai,$nam,$thang,$quy,$ngay) {
        $loai = in_array($loai,array('ngay','thang','quy','nam')) ? $loai : 'thang';
        $nam=(int)$nam; $thang=(int)$thang; $quy=(int)$quy;
        if($nam<2000 || $nam>2100) throw new Exception('Năm không hợp lệ');
        if($thang<1 || $thang>12 || $quy<1 || $quy>4) throw new Exception('Tháng hoặc quý không hợp lệ');
        $batDau=''; $ketThuc='';
        if($loai==='ngay') {
            $parts=explode('-',$ngay);
            if(count($parts)!==3 || !checkdate((int)$parts[1],(int)$parts[2],(int)$parts[0])) throw new Exception('Ngày không hợp lệ');
            $batDau=$ngay.' 00:00:00'; $ketThuc=date('Y-m-d H:i:s',strtotime($batDau.' +1 day'));
            $nhom='HOUR(d.ngay_lap)'; $nhan="LPAD(HOUR(d.ngay_lap),2,'0')";
        } elseif($loai==='thang') {
            $batDau=sprintf('%04d-%02d-01 00:00:00',$nam,$thang);
            $ketThuc=date('Y-m-d H:i:s',strtotime($batDau.' +1 month'));
            $nhom='DATE(d.ngay_lap)'; $nhan="DATE_FORMAT(d.ngay_lap,'%d/%m')";
        } elseif($loai==='quy') {
            $m=($quy-1)*3+1;
            $batDau=sprintf('%04d-%02d-01 00:00:00',$nam,$m);
            $ketThuc=date('Y-m-d H:i:s',strtotime($batDau.' +3 months'));
            $nhom='MONTH(d.ngay_lap)'; $nhan="DATE_FORMAT(d.ngay_lap,'%m/%Y')";
        } else {
            $batDau=sprintf('%04d-01-01 00:00:00',$nam);
            $ketThuc=sprintf('%04d-01-01 00:00:00',$nam+1);
            $nhom='MONTH(d.ngay_lap)'; $nhan="DATE_FORMAT(d.ngay_lap,'%m/%Y')";
        }
        $where="d.ngay_lap >= ? AND d.ngay_lap < ? AND d.trang_thai='DaThanhToan' AND EXISTS (SELECT 1 FROM thanh_toan t WHERE t.ma_hoa_don=d.ma_hoa_don AND t.trang_thai='ThanhCong')";
        $sql='SELECT '.$nhan.' AS nhan,COUNT(*) AS so_don,COALESCE(SUM(d.tong_tien),0) AS doanh_thu FROM don_hang d WHERE '.$where.' GROUP BY '.$nhom.' ORDER BY '.$nhom;
        $q=$this->db->prepare($sql);$q->execute(array($batDau,$ketThuc));$rows=$q->fetchAll(PDO::FETCH_ASSOC);
        $q=$this->db->prepare('SELECT COUNT(*) AS so_don,COALESCE(SUM(d.tong_tien),0) AS doanh_thu FROM don_hang d WHERE '.$where);
        $q->execute(array($batDau,$ketThuc));$tong=$q->fetch(PDO::FETCH_ASSOC);
        return array('rows'=>$rows,'tong'=>$tong,'bat_dau'=>$batDau,'ket_thuc'=>$ketThuc);
    }
}
?>