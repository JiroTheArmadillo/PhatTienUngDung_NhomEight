<?php
// UC01: trang vào hệ thống - điều hướng theo trạng thái đăng nhập.
require_once dirname(__FILE__) . '/php/XacThuc.php';
if (XacThuc::layNguoiDung() !== null) {
    header('Location: html/GD_TrangChu.php');
} else {
    header('Location: html/GD_DangNhap.php');
}
exit;
