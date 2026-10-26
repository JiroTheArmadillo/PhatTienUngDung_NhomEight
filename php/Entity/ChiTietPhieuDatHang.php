<?php
// php/Entity/ChiTietPhieuDatHang.php

class ChiTietPhieuDatHang {
    private $ma_phieu_de_nghi_dat_hang;
    private $ma_san_pham;
    private $so_luong;
    private $don_gia;

    public function __construct($ma_san_pham, $so_luong, $don_gia, $ma_phieu = null) {
        $this->ma_phieu_de_nghi_dat_hang = $ma_phieu;
        $this->ma_san_pham = $ma_san_pham;
        $this->so_luong = $so_luong;
        $this->don_gia = $don_gia;
    }

    public function getMaSanPham() { return $this->ma_san_pham; }
    public function getSoLuong() { return $this->so_luong; }
    public function getDonGia() { return $this->don_gia; }
    public function getThanhTien() { return $this->so_luong * $this->don_gia; }
    
    public function setMaPhieu($ma_phieu) {
        $this->ma_phieu_de_nghi_dat_hang = $ma_phieu;
    }
}
?>