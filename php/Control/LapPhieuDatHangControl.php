<?php
// php/Control/LapPhieuDatHangControl.php
require_once dirname(__FILE__) . '/../Repository/PhieuDatHangRepository.php';
require_once dirname(__FILE__) . '/../Entity/PhieuDeNghiDatHang.php';
require_once dirname(__FILE__) . '/../Entity/ChiTietPhieuDatHang.php';

class LapPhieuDatHangControl {
    private $repo;

    public function __construct($db_connection) {
        $this->repo = new PhieuDatHangRepository($db_connection);
    }

    public function layDanhSachNCC() {
        return $this->repo->getDanhSachNhaCungCap();
    }

    public function layDanhSachSanPhamCanNhap() {
        return $this->repo->getSanPhamCanNhap();
    }

    public function xuLyLapPhieu($ma_nv, $ds_sp_duoc_chon, $ds_ncc, $ds_so_luong, $ds_don_gia) {
    // 1. Phân loại chi tiết sản phẩm theo từng Nhà cung cấp
    $nhom_theo_ncc = array();

    foreach ($ds_sp_duoc_chon as $ma_sp) {
        $ma_sp  = intval($ma_sp);
        $ncc_id = isset($ds_ncc[$ma_sp]) ? intval($ds_ncc[$ma_sp]) : 0;
        $sl     = isset($ds_so_luong[$ma_sp]) ? intval($ds_so_luong[$ma_sp]) : 0;
        $dg     = isset($ds_don_gia[$ma_sp]) ? floatval($ds_don_gia[$ma_sp]) : 0;

        if ($ncc_id > 0 && $sl > 0 && $sl <= 100) {
            $chi_tiet_item = new ChiTietPhieuDatHang($ma_sp, $sl, $dg);
            // Nhóm theo ID của Nhà cung cấp
            $nhom_theo_ncc[$ncc_id][] = $chi_tiet_item;
        }
    }

    if (empty($nhom_theo_ncc)) {
        return false;
    }

    $ds_ma_phieu_tao_thanh_cong = array();

    // 2. Tạo mỗi phiếu riêng cho từng Nhà cung cấp
    foreach ($nhom_theo_ncc as $ncc_id => $ds_chi_tiet) {
        $phieu_entity = new PhieuDeNghiDatHang($ma_nv, $ncc_id);
        foreach ($ds_chi_tiet as $ct) {
            $phieu_entity->themChiTiet($ct);
        }

        $ma_phieu_moi = $this->repo->savePhieuDatHang($phieu_entity);
        if ($ma_phieu_moi) {
            $ds_ma_phieu_tao_thanh_cong[] = $ma_phieu_moi;
        }
    }

    // Trả về danh sách mã các phiếu vừa được tạo
    return !empty($ds_ma_phieu_tao_thanh_cong) ? $ds_ma_phieu_tao_thanh_cong : false;
}

    public function layThongTinPhieuVuaTao($ma_phieu) {
        return $this->repo->getPhieuVuaTao($ma_phieu);
    }

    public function layChiTietPhieuVuaTao($ma_phieu) {
        return $this->repo->getChiTietPhieuVuaTao($ma_phieu);
    }
}
?>