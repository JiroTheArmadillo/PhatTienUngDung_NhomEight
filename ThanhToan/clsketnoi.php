<?php
// Ket noi CSDL eightmart (WAMP mac dinh: user root, mat khau rong)
// Viet theo cu phap PHP 5.2 de chay duoc tren WAMP cu va moi.

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF); // tu kiem tra loi, khong de PHP 8 nem exception
}

$conn = mysqli_connect('localhost', 'root', '', 'eightmart');
if (!$conn) {
    die('Không kết nối được CSDL: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8');

// Lay du lieu POST, bo dau \ neu WAMP bat magic_quotes (PHP 5.2)
function lay_post($ten, $mac_dinh = '')
{
    if (!isset($_POST[$ten])) {
        return $mac_dinh;
    }
    $gia_tri = $_POST[$ten];
    if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()) {
        $gia_tri = is_array($gia_tri) ? array_map('stripslashes', $gia_tri) : stripslashes($gia_tri);
    }
    return $gia_tri;
}

// Doi "SP001" hoac "1" thanh so 1
function ma_sp_tu_chuoi($chuoi)
{
    return intval(preg_replace('/[^0-9]/', '', $chuoi));
}

// Doi so 1 thanh "SP001" de hien thi
function ma_sp_hien_thi($so)
{
    return 'SP' . str_pad($so, 3, '0', STR_PAD_LEFT);
}