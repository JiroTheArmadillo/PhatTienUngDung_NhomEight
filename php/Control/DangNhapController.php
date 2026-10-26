<?php
/** BỔ SUNG TRANG ĐĂNG NHẬP: xác thực với bảng `Tai Khoan`. */
require_once dirname(__FILE__) . '/../XacThuc.php';
require_once dirname(__FILE__) . '/../Repository/TaiKhoanRep.php';

class DangNhapController
{
    /** UC01 - Bước 4: kiểm tra cú pháp dữ liệu, trước khi truy vấn DB.
     * Tên đăng nhập hỗ trợ CCCD và tên tài khoản mặc định trong SQL.
     */
    public function kiemTraCuPhapDauVao($tenDangNhap, $matKhau)
    {
        $loi = array();
        if ($tenDangNhap === '') {
            $loi['ten_dang_nhap'] = 'Vui lòng nhập tên đăng nhập.';
        } elseif (strlen($tenDangNhap) > 60 || !preg_match('/^[a-zA-Z0-9_.@-]+$/D', $tenDangNhap)) {
            $loi['ten_dang_nhap'] = 'Tên đăng nhập chỉ gồm chữ, số hoặc các ký tự . _ @ - (tối đa 60 ký tự).';
        }
        if ($matKhau === '') {
            $loi['mat_khau'] = 'Vui lòng nhập mật khẩu.';
        } elseif (strlen($matKhau) > 255) {
            $loi['mat_khau'] = 'Mật khẩu không được vượt quá 255 ký tự.';
        }
        return $loi;
    }

    public function xuLy()
    {
        XacThuc::batDau();
        // Không đưa trang login trở lại nếu đã có phiên hợp lệ.
        if (XacThuc::layNguoiDung() !== null) {
            // Sau khi xac thuc thanh cong, vao trang chu truoc (khong vao quan ly nhan vien).
            header('Location: GD_TrangChu.php');
            exit;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate');
        if (!isset($_SESSION['eightmart_csrf_login'])) {
            $_SESSION['eightmart_csrf_login'] = sha1(uniqid(mt_rand(), true));
        }

        $vm = array('loi' => '', 'errors' => array(), 'ten_dang_nhap' => '',
            'csrf' => $_SESSION['eightmart_csrf_login']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $vm;
        }

        $username = isset($_POST['ten_dang_nhap']) ? trim((string) $_POST['ten_dang_nhap']) : '';
        $password = isset($_POST['mat_khau']) ? (string) $_POST['mat_khau'] : '';
        $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
        $vm['ten_dang_nhap'] = $username;

        if ($token === '' || $token !== $_SESSION['eightmart_csrf_login']) {
            $vm['loi'] = 'Phiên biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
            return $vm;
        }
        // UC01 - Bước 4: cú pháp hợp lệ mới cho phép xác thực CSDL (bước 5).
        $vm['errors'] = $this->kiemTraCuPhapDauVao($username, $password);
        if (!empty($vm['errors'])) {
            $vm['loi'] = 'Vui lòng kiểm tra lại thông tin đăng nhập.';
            return $vm;
        }
        // Giới hạn số lần thử sai trong một phiên nhằm giảm đoán mật khẩu.
        if (isset($_SESSION['eightmart_khoa_den']) && time() < $_SESSION['eightmart_khoa_den']) {
            $vm['loi'] = 'Đăng nhập sai nhiều lần. Vui lòng thử lại sau ít phút.';
            return $vm;
        }

        try {
            $rep = new TaiKhoanRep();
            $nguoiDung = $rep->xacThuc($username, $password);
            if ($nguoiDung !== null) {
                XacThuc::dangNhap($nguoiDung);
                unset($_SESSION['eightmart_csrf_login']);
                // Chuyen den trang chu; quan ly nhan vien chi mo khi bam menu.
                header('Location: GD_TrangChu.php');
                exit;
            }
        } catch (Exception $e) {
            error_log('[DangNhap] ' . $e->getMessage());
            $vm['loi'] = 'Không thể kết nối cơ sở dữ liệu. Kiểm tra MySQL và cấu hình kết nối.';
            return $vm;
        }

        $sai = isset($_SESSION['eightmart_so_lan_sai']) ? (int) $_SESSION['eightmart_so_lan_sai'] + 1 : 1;
        $_SESSION['eightmart_so_lan_sai'] = $sai;
        if ($sai >= 5) {
            $_SESSION['eightmart_khoa_den'] = time() + 120;
            $_SESSION['eightmart_so_lan_sai'] = 0;
            $vm['loi'] = 'Đăng nhập sai nhiều lần. Vui lòng thử lại sau 2 phút.';
        } else {
            $vm['loi'] = 'Sai tài khoản hoặc mật khẩu. Vui lòng nhập lại.';
        }
        return $vm;
    }
}
