<?php
/** Entity NhaCungCap - ánh xạ bảng nha_cung_cap trong database eightmart. */
class NhaCungCap
{
    const TT_HOAT_DONG = 'HoatDong';
    const TT_NGUNG_HOAT_DONG = 'NgungHoatDong';

    private $ma = 0;
    private $ten = '';
    private $email = '';
    private $diaChi = '';
    private $loaiSanPham = '';
    private $trangThai = self::TT_HOAT_DONG;

    public function __construct($ten = '', $email = '', $diaChi = '', $loaiSanPham = '', $trangThai = self::TT_HOAT_DONG)
    {
        $this->setTen($ten);
        $this->setEmail($email);
        $this->setDiaChi($diaChi);
        $this->setLoaiSanPham($loaiSanPham);
        $this->setTrangThai($trangThai);
    }

    public static function tuDong($row)
    {
        $ncc = new NhaCungCap(
            isset($row['ten_nha_cung_cap']) ? $row['ten_nha_cung_cap'] : '',
            isset($row['email']) ? $row['email'] : '',
            isset($row['dia_chi']) ? $row['dia_chi'] : '',
            isset($row['loai_san_pham_cung_cap']) ? $row['loai_san_pham_cung_cap'] : '',
            isset($row['trang_thai']) ? $row['trang_thai'] : self::TT_HOAT_DONG
        );
        $ncc->ma = (int) $row['ma_nha_cung_cap'];
        return $ncc;
    }

    public function getMa() { return $this->ma; }
    public function setMa($ma) { $this->ma = (int) $ma; }
    public function getTen() { return $this->ten; }
    public function setTen($ten) { $this->ten = trim(preg_replace('/\s+/u', ' ', (string) $ten)); }
    public function getEmail() { return $this->email; }
    public function setEmail($email) { $this->email = trim((string) $email); }
    public function getDiaChi() { return $this->diaChi; }
    public function setDiaChi($diaChi) { $this->diaChi = trim((string) $diaChi); }
    public function getLoaiSanPham() { return $this->loaiSanPham; }
    public function setLoaiSanPham($loai) { $this->loaiSanPham = trim((string) $loai); }
    public function getTrangThai() { return $this->trangThai; }
    public function setTrangThai($trangThai) { $this->trangThai = trim((string) $trangThai); }
    public function getMaHienThi() { return 'NCC' . str_pad($this->ma, 3, '0', STR_PAD_LEFT); }

    public function kiemTra()
    {
        $loi = array();
        if ($this->ten === '') $loi['ten'] = 'Vui lòng nhập tên nhà cung cấp.';
        elseif (!preg_match('/^.{1,150}$/us', $this->ten)) $loi['ten'] = 'Tên nhà cung cấp tối đa 150 ký tự.';

        if ($this->email !== '' && (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $this->email)
            || strlen($this->email) > 120)) {
            $loi['email'] = 'Email không hợp lệ.';
        }
        if (strlen($this->diaChi) > 255) $loi['dia_chi'] = 'Địa chỉ tối đa 255 ký tự.';
        if (strlen($this->loaiSanPham) > 200) $loi['loai_san_pham'] = 'Loại sản phẩm tối đa 200 ký tự.';
        if ($this->trangThai !== self::TT_HOAT_DONG && $this->trangThai !== self::TT_NGUNG_HOAT_DONG) {
            $loi['trang_thai'] = 'Trạng thái không hợp lệ.';
        }
        return $loi;
    }
}
