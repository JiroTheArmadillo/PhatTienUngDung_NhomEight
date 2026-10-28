<?php
// php/Entity/PhieuDeNghiDatHang.php

class PhieuDeNghiDatHang {
    private $ma_phieu_de_nghi_dat_hang;
    private $ma_nhan_vien;
    private $ma_nha_cung_cap;
    private $ngay_lap;
    private $trang_thai;
    private $tong_chi_phi;
    private $danh_sach_chi_tiet = array();

    public function __construct($ma_nhan_vien, $ma_nha_cung_cap, $tong_chi_phi = 0, $trang_thai = 'ChoDuyet', $ngay_lap = null) {
        $this->ma_nhan_vien = $ma_nhan_vien;
        $this->ma_nha_cung_cap = $ma_nha_cung_cap;
        $this->tong_chi_phi = $tong_chi_phi;
        $this->trang_thai = $trang_thai;
        $this->ngay_lap = $ngay_lap ? $ngay_lap : date('Y-m-d H:i:s');
    }

    public function getMaNhanVien() { return $this->ma_nhan_vien; }
    public function getMaNhaCungCap() { return $this->ma_nha_cung_cap; }
    public function getNgayLap() { return $this->ngay_lap; }
    public function getTrangThai() { return $this->trang_thai; }
    public function getTongChiPhi() { return $this->tong_chi_phi; }

    public function themChiTiet(ChiTietPhieuDatHang $chi_tiet) {
        $this->danh_sach_chi_tiet[] = $chi_tiet;
        $this->tong_chi_phi += $chi_tiet->getThanhTien();
    }

    public function getDanhSachChiTiet() {
        return $this->danh_sach_chi_tiet;
    }
}
?>