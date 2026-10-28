<?php
// php/Repository/PhieuDatHangRepository.php
require_once dirname(__FILE__) . '/../Entity/PhieuDeNghiDatHang.php';
require_once dirname(__FILE__) . '/../Entity/ChiTietPhieuDatHang.php';

class PhieuDatHangRepository {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function getDanhSachNhaCungCap() {
        $list = array();
        $res = $this->conn->query("SELECT ma_nha_cung_cap, ten_nha_cung_cap FROM nha_cung_cap WHERE trang_thai = 'HoatDong'");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $list[] = $row;
            }
        }
        return $list;
    }

    

    public function getSanPhamCanNhap() {
    // Lấy tất cả sản phẩm có tồn kho <= mức tồn tối thiểu 
    // và chưa nằm trong các phiếu đang ở trạng thái 'ChoDuyet'
    $sql = "SELECT sp.* 
            FROM san_pham sp
            WHERE sp.so_luong_ton <= sp.muc_ton_toi_thieu 
              AND sp.trang_thai = 'HoatDong'
              AND sp.ma_san_pham NOT IN (
                  SELECT ct.ma_san_pham 
                  FROM chi_tiet_phieu_dat_hang ct
                  JOIN phieu_de_nghi_dat_hang p ON ct.ma_phieu_de_nghi_dat_hang = p.ma_phieu_de_nghi_dat_hang
                  WHERE p.trang_thai = 'ChoDuyet'
              )";
    return $this->conn->query($sql);
}

    // Nhận trực tiếp đối tượng Entity PhieuDeNghiDatHang
    public function savePhieuDatHang(PhieuDeNghiDatHang $phieu) {
        $ma_nv = $phieu->getMaNhanVien();
        $ma_ncc = $phieu->getMaNhaCungCap();
        $ngay_lap = $phieu->getNgayLap();
        $tong_chi_phi = $phieu->getTongChiPhi();

        $stmt = $this->conn->prepare("INSERT INTO phieu_de_nghi_dat_hang (ma_nhan_vien, ma_nha_cung_cap, ngay_lap, trang_thai, tong_chi_phi) VALUES (?, ?, ?, 'ChoDuyet', ?)");
        $stmt->bind_param("iisd", $ma_nv, $ma_ncc, $ngay_lap, $tong_chi_phi);
        
        if ($stmt->execute()) {
            $ma_phieu_moi = $this->conn->insert_id;

            // Lưu từng chi tiết Entity
            $stmt_ct = $this->conn->prepare("INSERT INTO chi_tiet_phieu_dat_hang (ma_phieu_de_nghi_dat_hang, ma_san_pham, so_luong, don_gia) VALUES (?, ?, ?, ?)");
            foreach ($phieu->getDanhSachChiTiet() as $ct) {
                $ma_sp = $ct->getMaSanPham();
                $sl = $ct->getSoLuong();
                $dg = $ct->getDonGia();
                $stmt_ct->bind_param("iiid", $ma_phieu_moi, $ma_sp, $sl, $dg);
                $stmt_ct->execute();
            }
            return $ma_phieu_moi;
        }
        return false;
    }

    public function getPhieuVuaTao($ma_phieu) {
        $sql = "SELECT p.*, nv.ten AS ten_nv, ncc.ten_nha_cung_cap 
                FROM phieu_de_nghi_dat_hang p
                JOIN nhan_vien nv ON p.ma_nhan_vien = nv.ma_nhan_vien
                JOIN nha_cung_cap ncc ON p.ma_nha_cung_cap = ncc.ma_nha_cung_cap
                WHERE p.ma_phieu_de_nghi_dat_hang = $ma_phieu";
        $res = $this->conn->query($sql);
        return $res ? $res->fetch_assoc() : null;
    }

    public function getChiTietPhieuVuaTao($ma_phieu) {
        $list = array();
        $sql = "SELECT ct.*, sp.ten_san_pham 
                FROM chi_tiet_phieu_dat_hang ct
                JOIN san_pham sp ON ct.ma_san_pham = sp.ma_san_pham
                WHERE ct.ma_phieu_de_nghi_dat_hang = $ma_phieu";
        $res = $this->conn->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $list[] = $r;
            }
        }
        return $list;
    }
}
?>