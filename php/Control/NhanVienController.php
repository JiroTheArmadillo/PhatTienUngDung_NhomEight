<?php
/**
 * Control NhanVienController - điều phối UC03 (Thêm), UC04 (Sửa), UC05 (Xóa), UC06 (Xem danh sách).
 * Tương thích PHP 5.2.
 */
require_once dirname(__FILE__) . '/../Entity/NhanVien.php';
require_once dirname(__FILE__) . '/../Repository/NhanVienRep.php';

class NhanVienController
{

    private $rep = null;

  
    public function xuLy()
    {
        if (!session_id()) { session_start(); }

        $vm = array('danhSach' => array(), 'thongBao' => null, 'loiTai' => null, 'form' => null);

        // thông báo lưu từ lần xử lý trước (Post-Redirect-Get)
        if (isset($_SESSION['nv_thongbao'])) {
            $vm['thongBao'] = $_SESSION['nv_thongbao'];
            unset($_SESSION['nv_thongbao']);
        }

        try {
            $this->rep = new NhanVienRep();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $hanhDong = isset($_POST['action']) ? $_POST['action'] : '';
                if ($hanhDong === 'them')     { $vm['form'] = $this->xuLyThem(); }
                elseif ($hanhDong === 'sua')  { $vm['form'] = $this->xuLySua(); }
                elseif ($hanhDong === 'xoa')  { $this->xuLyXoa(); }
            } else {
                $hanhDong = isset($_GET['action']) ? $_GET['action'] : '';
                if ($hanhDong === 'them') {
                    $vm['form'] = $this->bieuMau('them', 0, array('ten' => '', 'cccd' => '', 'gioi_tinh' => '', 'sdt' => '', 'ngay_sinh' => ''));
                } elseif ($hanhDong === 'sua') {
                    $nv = $this->rep->layTheoMa(isset($_GET['id']) ? $_GET['id'] : 0);
                    if ($nv === null) {
                        $vm['thongBao'] = array('danger', 'Không tìm thấy nhân viên cần sửa.');
                    } else {
                        $vm['form'] = $this->bieuMau('sua', $nv->getMaNhanVien(), array(
                            'ten' => $nv->getTen(), 'cccd' => $nv->getCccd(),
                            'gioi_tinh' => $nv->getGioiTinh(), 'sdt' => $nv->getSdt(),
                            'ngay_sinh' => $nv->getNgaySinh()));
                    }
                }
            }
        } catch (Exception $e) {
            error_log('[NhanVien] ' . $e->getMessage());
            $vm['thongBao'] = array('danger', 'Không thể kết nối hoặc xử lý dữ liệu. Vui lòng thử lại sau.');
        }

        // UC06: truy xuất danh sách
        try {
            if ($this->rep === null) { throw new Exception('Chưa kết nối CSDL'); }
            $vm['danhSach'] = $this->rep->layTatCa();
        } catch (Exception $e) {
            error_log('[NhanVien] ' . $e->getMessage());                 // 5.2.2 ghi nhận lỗi
            $vm['loiTai'] = 'Không thể tải dữ liệu nhân viên.';          // 5.2.1
        }
        return $vm;
    }

    // ------------------------------------------------------------------ UC03
    private function xuLyThem()
    {
        $nv = new NhanVien($this->post('ten'), $this->post('cccd'), $this->post('gioi_tinh'), $this->post('sdt'), $this->post('ngay_sinh'));
        $loi = $nv->kiemTra();                                           // 6.1, 6.2
        if (!isset($loi['cccd']) && $this->rep->tonTaiCccd($nv->getCccd())) {
            $loi['cccd'] = 'CCCD đã tồn tại trong hệ thống.';
        }
        // BỔ SUNG: vì CCCD cũng dùng làm username, phải kiểm tra trùng username.
        if (!isset($loi['cccd']) && $this->rep->tonTaiTenDangNhap($nv->getCccd())) {
            $loi['cccd'] = 'CCCD này đã được dùng làm tên đăng nhập.';
        }
        if ($loi) {
            return $this->bieuMau('them', 0, $this->giaTri($nv), $loi);
        }

        // BỔ SUNG: mật khẩu = ngày tháng năm sinh ddmmyyyy, ví dụ 02/11/2005 -> 02112005.
        $matKhau = $nv->getMatKhauNgaySinh();
        // BỔ SUNG: phải lưu được CẢ nhân viên lẫn bảng Tai Khoan mới commit.
        try {
            $this->rep->batDauGiaoDich();
            $ma = $this->rep->them($nv, $matKhau);
            $tenDangNhap = $this->rep->taoTaiKhoan($ma, $nv->getCccd(), $matKhau);
            $this->rep->xacNhan();
        } catch (Exception $e) {
            $this->rep->hoanTac();
            error_log('[NhanVien] them/taoTaiKhoan: ' . $e->getMessage());
            return $this->bieuMau('them', 0, $this->giaTri($nv), array(),
                'Không thể thêm nhân viên hoặc tạo tài khoản. Đã hoàn tác toàn bộ thay đổi; kiểm tra cập nhật SQL.');
        }
        $this->chuyenHuong('success', 'Thêm nhân viên thành công. Tên đăng nhập: '
            . $tenDangNhap . ' | Mật khẩu mặc định: ngày sinh (ddmmyyyy) | Vai trò: Nhân viên.');
        return null;
    }

    // ------------------------------------------------------------------ UC04
    private function xuLySua()
    {
        $ma = (int) $this->post('ma_nhan_vien');
        $hienTai = $this->rep->layTheoMa($ma);
        if ($hienTai === null) {
            $this->chuyenHuong('danger', 'Không tìm thấy nhân viên cần sửa.');
            return null;
        }
        $nv = new NhanVien($this->post('ten'), $this->post('cccd'), $this->post('gioi_tinh'), $this->post('sdt'), $this->post('ngay_sinh'));
        $nv->setMaNhanVien($ma);
        $loi = $nv->kiemTra();                                           // 8.1 + sai định dạng
        if (!isset($loi['cccd']) && $this->rep->tonTaiCccd($nv->getCccd(), $ma)) {
            $loi['cccd'] = 'CCCD đã tồn tại trong hệ thống.';
        }
        // Nếu CCCD của tài khoản tự tạo thay đổi thì username cũng đổi theo.
        if (!isset($loi['cccd']) && $hienTai->getTenDangNhap() === $hienTai->getCccd()
            && $nv->getCccd() !== $hienTai->getCccd()
            && $this->rep->tonTaiTenDangNhap($nv->getCccd(), $ma)) {
            $loi['cccd'] = 'CCCD này đã được dùng làm tên đăng nhập.';
        }
        if ($loi) {
            return $this->bieuMau('sua', $ma, $this->giaTri($nv), $loi);
        }
        try {
            // BỔ SUNG: đồng bộ thay đổi CCCD với tài khoản trong cùng transaction.
            $this->rep->batDauGiaoDich();
            $this->rep->capNhat($nv);
            $this->rep->xacNhan();
        } catch (Exception $e) {
            $this->rep->hoanTac();
            error_log('[NhanVien] capNhat: ' . $e->getMessage());
            return $this->bieuMau('sua', $ma, $this->giaTri($nv), array(),
                'Cập nhật không thành công. Dữ liệu hiện có được giữ nguyên.');
        }
        $this->chuyenHuong('success', 'Cập nhật thông tin nhân viên ' . $hienTai->getMaHienThi() . ' thành công.');
        return null;
    }

    // ------------------------------------------------------------------ UC05
    private function xuLyXoa()
    {
        $nv = $this->rep->layTheoMa($this->post('ma_nhan_vien'));
        if ($nv === null) {
            $this->chuyenHuong('danger', 'Nhân viên không tồn tại hoặc đã bị xóa.');
            return;
        }
        if ($nv->laChuDoanhNghiep()) {
            $this->chuyenHuong('danger', 'Không thể xóa tài khoản Chủ doanh nghiệp.');
            return;
        }
        try {
            if ($this->rep->dangDuocThamChieu($nv->getMaNhanVien())) {   // bước 10
                $this->chuyenHuong('danger', 'Không thể xóa ' . $nv->getMaHienThi() . ' - ' . $nv->getTen()
                    . ' vì nhân viên đã phát sinh dữ liệu (ca làm việc, đơn hàng, phiếu...).');
                return;
            }
            $this->rep->xoa($nv->getMaNhanVien());                       // bước 11
        } catch (Exception $e) {
            error_log('[NhanVien] xoa: ' . $e->getMessage());
            $this->chuyenHuong('danger', 'Xóa không thành công. Dữ liệu hiện có được giữ nguyên.');
            return;
        }
        $this->chuyenHuong('success', 'Đã xóa nhân viên ' . $nv->getMaHienThi() . ' - ' . $nv->getTen() . '.');
    }

    // ------------------------------------------------------------------ tiện ích
    private function post($k)
    {
        return isset($_POST[$k]) ? $_POST[$k] : '';
    }

    private function giaTri(NhanVien $nv)
    {
        return array('ten' => $nv->getTen(), 'cccd' => $nv->getCccd(),
                     'gioi_tinh' => $nv->getGioiTinh(), 'sdt' => $nv->getSdt(),
                     'ngay_sinh' => $nv->getNgaySinh());
    }

    private function bieuMau($mode, $ma, $values, $errors = array(), $thongBaoLoi = '')
    {
        return array('mode' => $mode, 'ma' => $ma, 'values' => $values,
                     'errors' => $errors, 'loiChung' => $thongBaoLoi);
    }

    /** Lưu thông báo vào session rồi chuyển về trang danh sách (tránh gửi lại form khi F5). */
    private function chuyenHuong($loai, $noiDung)
    {
        $_SESSION['nv_thongbao'] = array($loai, $noiDung);
        header('Location: ' . basename($_SERVER['SCRIPT_NAME']));
        exit;
    }
}
