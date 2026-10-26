<?php
/**
 * Entity NhanVien - ánh xạ bảng `nhan_vien` (CSDL eightmart).
 * Tương thích PHP 5.2.
 */
class NhanVien
{
    const VAI_TRO_CHU     = 'ChuDoanhNghiep';
    const VAI_TRO_NV      = 'NhanVien';
    const TT_HOAT_DONG    = 'HoatDong';

    private $maNhanVien = 0;
    private $ten        = '';
    private $cccd       = '';
    private $gioiTinh   = '';
    private $sdt        = '';
    // BỔ SUNG: ngày sinh để tạo mật khẩu mặc định ddmmyyyy.
    private $ngaySinh   = '';
    private $tenDangNhap = '';
    private $vaiTro     = self::VAI_TRO_NV;
    private $trangThai  = self::TT_HOAT_DONG;

    public function __construct($ten = '', $cccd = '', $gioiTinh = '', $sdt = '', $ngaySinh = '')
    {
        $this->setTen($ten);
        $this->setCccd($cccd);
        $this->setGioiTinh($gioiTinh);
        $this->setSdt($sdt);
        $this->setNgaySinh($ngaySinh);
    }

    /** Tạo entity từ một dòng kết quả của bảng nhan_vien. */
    public static function tuDong($row)
    {
        $nv = new NhanVien($row['ten'], $row['cccd'], $row['gioi_tinh'], $row['sdt'], isset($row['ngay_sinh']) ? $row['ngay_sinh'] : '');
        $nv->maNhanVien  = (int) $row['ma_nhan_vien'];
        $nv->tenDangNhap = $row['ten_dang_nhap'];
        $nv->vaiTro      = $row['vai_tro'];
        $nv->trangThai   = $row['trang_thai'];
        return $nv;
    }

    // ---- getter / setter ----
    public function getMaNhanVien() { return $this->maNhanVien; }
    public function setMaNhanVien($v) { $this->maNhanVien = (int) $v; }

    public function getTen() { return $this->ten; }
    public function setTen($v) { $this->ten = preg_replace('/\s+/u', ' ', trim((string) $v)); }

    public function getCccd() { return $this->cccd; }
    public function setCccd($v) { $this->cccd = preg_replace('/\s+/', '', (string) $v); }

    public function getGioiTinh() { return $this->gioiTinh; }
    public function setGioiTinh($v) { $this->gioiTinh = trim((string) $v); }

    public function getSdt() { return $this->sdt; }
    public function setSdt($v) { $this->sdt = preg_replace('/\s+/', '', (string) $v); }

    public function getNgaySinh() { return $this->ngaySinh; }
    public function setNgaySinh($v)
    {
        $v = trim((string) $v);
        // CHINH SUA: input type=date gui yyyy-mm-dd; neu nhap 02/11/2005 thi chuan hoa ve 2005-11-02.
        if (preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $v, $ngay)) {
            $v = $ngay[3] . '-' . $ngay[2] . '-' . $ngay[1];
        }
        $this->ngaySinh = $v;
    }

    /**
     * CHINH SUA: mat khau mac dinh la ngay + thang + nam (ddmmyyyy), du 8 chu so.
     * Vi du: 02/11/2005 => "02112005" (giu nguyen so 0 o dau).
     * CSDL luu MD5 cua chuoi nay de tuong thich chuc nang dang nhap hien tai.
     */
    public function getMatKhauNgaySinh()
    {
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $this->ngaySinh, $ngay)
            || !checkdate((int) $ngay[2], (int) $ngay[3], (int) $ngay[1])) {
            throw new Exception('Ngay sinh khong hop le de tao mat khau mac dinh.');
        }
        return $ngay[3] . $ngay[2] . $ngay[1];
    }

    public function getTenDangNhap() { return $this->tenDangNhap; }
    public function getVaiTro() { return $this->vaiTro; }
    public function getTrangThai() { return $this->trangThai; }

    public function laChuDoanhNghiep()
    {
        return $this->vaiTro === self::VAI_TRO_CHU;
    }

    // ---- hiển thị ----
    /** 3 -> NV003 */
    public function getMaHienThi()
    {
        return 'NV' . str_pad($this->maNhanVien, 3, '0', STR_PAD_LEFT);
    }

    public function getGioiTinhHienThi()
    {
        if ($this->gioiTinh === 'Nam') { return 'Nam'; }
        if ($this->gioiTinh === 'Nu')  { return 'Nữ'; }
        return $this->gioiTinh;
    }

    public function getChucVuHienThi()
    {
        return $this->vaiTro === self::VAI_TRO_CHU ? 'Chủ doanh nghiệp' : 'Nhân viên';
    }

    /**
     * Kiểm tra dữ liệu nhập (UC03 bước 6, UC04 bước 8).
     * @return array  [tên trường => thông báo lỗi]; mảng rỗng nếu hợp lệ.
     *   - 6.1 thiếu thông tin bắt buộc
     *   - 6.2 sai định dạng
     */
    public function kiemTra()
    {
        $loi = array();

        // 6.1 - thiếu thông tin bắt buộc
        if ($this->ten === '')      { $loi['ten']       = 'Vui lòng nhập họ và tên.'; }
        if ($this->cccd === '')     { $loi['cccd']      = 'Vui lòng nhập số CCCD.'; }
        if ($this->gioiTinh === '') { $loi['gioi_tinh'] = 'Vui lòng chọn giới tính.'; }
        if ($this->sdt === '')      { $loi['sdt']       = 'Vui lòng nhập số điện thoại.'; }
        if ($this->ngaySinh === '') { $loi['ngay_sinh'] = 'Vui lòng chọn ngày sinh.'; }

        // 6.2 - sai định dạng
        if (!isset($loi['ten'])
            && (!preg_match('/^.{1,120}$/us', $this->ten)
                || !preg_match('/^\p{L}[\p{L} .\'-]*$/u', $this->ten))) {
            $loi['ten'] = 'Họ và tên không hợp lệ (chỉ gồm chữ cái, tối đa 120 ký tự).';
        }
        if (!isset($loi['cccd']) && !preg_match('/^[0-9]{12}$/', $this->cccd)) {
            $loi['cccd'] = 'CCCD không hợp lệ (gồm đúng 12 chữ số).';
        }
        if (!isset($loi['gioi_tinh']) && $this->gioiTinh !== 'Nam' && $this->gioiTinh !== 'Nu') {
            $loi['gioi_tinh'] = 'Giới tính không hợp lệ.';
        }
        if (!isset($loi['sdt']) && !preg_match('/^0[0-9]{9}$/', $this->sdt)) {
            $loi['sdt'] = 'Số điện thoại không hợp lệ (10 chữ số, bắt đầu bằng 0).';
        }
        // BỔ SUNG: kiểm tra ngày thực tế (kể cả năm nhuận), không cho chọn tương lai.
        if (!isset($loi['ngay_sinh'])) {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $this->ngaySinh, $ngay)
                || !checkdate((int) $ngay[2], (int) $ngay[3], (int) $ngay[1])
                || $this->ngaySinh > date('Y-m-d')) {
                $loi['ngay_sinh'] = 'Ngày sinh không hợp lệ hoặc lớn hơn ngày hiện tại.';
            }
        }
        return $loi;
    }
}
