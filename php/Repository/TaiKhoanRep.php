<?php
/**
 * BỔ SUNG TRANG ĐĂNG NHẬP: đọc tài khoản từ bảng `Tai Khoan`.
 * Dùng MySQLi prepared statements, tương thích PHP 5.2 / MySQL 5.x.
 */
require_once dirname(__FILE__) . '/../../db.php';

class TaiKhoanRep
{
    private $db;

    public function __construct()
    {
        $this->db = eightmart_mysqli_connection();
    }

    /**
     * Lấy tài khoản có tên đăng nhập chính xác. Không trả dữ liệu mật khẩu cho giao diện.
     * Hỗ trợ admin, nhanvien và tài khoản CCCD thêm từ quản lý nhân viên.
     */
    public function xacThuc($tenDangNhap, $matKhau)
    {
        $st = eightmart_mysqli_execute(
            'SELECT tk.ma_tai_khoan, tk.ma_nhan_vien, tk.ten_dang_nhap, tk.mat_khau,
                    tk.vai_tro, tk.trang_thai, nv.ten AS ho_ten,
                    nv.trang_thai AS trang_thai_nhan_vien
             FROM `Tai Khoan` AS tk
             INNER JOIN nhan_vien AS nv ON nv.ma_nhan_vien = tk.ma_nhan_vien
             WHERE tk.ten_dang_nhap = ? LIMIT 1',
            array($tenDangNhap)
        );
        $row = eightmart_mysqli_fetch_one_assoc($st);

        // Database gốc dùng MD5 và cột CHAR(32); giữ tương thích với tài khoản mẫu.
        if (!$row || $row['trang_thai'] !== 'HoatDong'
            || $row['trang_thai_nhan_vien'] !== 'HoatDong'
            || $row['mat_khau'] !== md5($matKhau)) {
            return null;
        }

        return array(
            'ma_tai_khoan' => (int) $row['ma_tai_khoan'],
            'ma_nhan_vien' => (int) $row['ma_nhan_vien'],
            'ten_dang_nhap' => $row['ten_dang_nhap'],
            'ho_ten' => $row['ho_ten'],
            'vai_tro' => $row['vai_tro']
        );
    }
}
