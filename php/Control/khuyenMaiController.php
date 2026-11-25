<?php
// php/Control/khuyenMaiController.php (Tương thích PHP 5.2.6 + eightmart_mysqli_connection)
class KhuyenMaiController {

    private static function layKetNoi($conn = null) {
        if ($conn) {
            return $conn;
        }
        if (function_exists('eightmart_mysqli_connection')) {
            return eightmart_mysqli_connection();
        }
        return null;
    }

    // Lấy danh sách chương trình khuyến mãi đang hoạt động và còn hạn
    public static function layDanhSachDangHoatDong($conn = null) {
        $db = self::layKetNoi($conn);
        if (!$db) {
            return array();
        }

        $sql = "SELECT ID, TenChuongTrinh, MoTa, HinhThucKhuyenMai, MucGiamGia
                FROM ChuongTrinhKhuyenMai
                WHERE TrangThai = 'HoatDong'
                  AND ThoiGianBatDau <= NOW()
                  AND (ThoiGianKetThuc IS NULL OR ThoiGianKetThuc >= NOW())
                ORDER BY ID DESC";

        $ds = array();
        $rs = mysqli_query($db, $sql);
        if ($rs) {
            while ($row = mysqli_fetch_assoc($rs)) {
                $ds[] = $row;
            }
        }
        return $ds;
    }

    // Lấy 1 chương trình khuyến mãi hợp lệ theo ID (dùng trong transaction thanh toán)
    public static function layTheoId($conn, $id) {
        $db = self::layKetNoi($conn);
        $id = intval($id);
        if (!$db) {
            return null;
        }
        if ($id <= 0) {
            return null;
        }

        $sql = "SELECT ID, TenChuongTrinh, HinhThucKhuyenMai, MucGiamGia
                FROM ChuongTrinhKhuyenMai
                WHERE ID = $id
                  AND TrangThai = 'HoatDong'
                  AND ThoiGianBatDau <= NOW()
                  AND (ThoiGianKetThuc IS NULL OR ThoiGianKetThuc >= NOW())
                LIMIT 1";

        $rs = mysqli_query($db, $sql);
        return ($rs) ? mysqli_fetch_assoc($rs) : null;
    }

    // Tính số tiền được giảm
    public static function tinhTienGiam($tamTinh, $km) {
        if (!$km) {
            return 0;
        }
        if ($tamTinh <= 0) {
            return 0;
        }

        $hinhThuc = isset($km['HinhThucKhuyenMai']) ? $km['HinhThucKhuyenMai'] : '';
        $mucGiam  = isset($km['MucGiamGia']) ? floatval($km['MucGiamGia']) : 0;
        $giam = 0;

        if ($hinhThuc == 'PhanTram') {
            $giam = $tamTinh * ($mucGiam / 100);
        } elseif ($hinhThuc == 'SoTien') {
            $giam = $mucGiam;
        }

        if ($giam > $tamTinh) {
            $giam = $tamTinh;
        }
        if ($giam < 0) {
            $giam = 0;
        }
        return round($giam);
    }

    // Lưu liên kết hóa đơn - khuyến mãi vào bảng HoaDon_ChuongTrinhKhuyenMai
    public static function luuKhuyenMaiHoaDon($conn, $hoaDonId, $khuyenMaiId) {
        $db = self::layKetNoi($conn);
        $hoaDonId = intval($hoaDonId);
        $khuyenMaiId = intval($khuyenMaiId);
        if (!$db) {
            return false;
        }
        if ($hoaDonId <= 0) {
            return false;
        }
        if ($khuyenMaiId <= 0) {
            return false;
        }

        $sql = "INSERT INTO HoaDon_ChuongTrinhKhuyenMai (HoaDonID, ChuongTrinhKhuyenMaiID)
                VALUES ($hoaDonId, $khuyenMaiId)";
        return mysqli_query($db, $sql);
    }
    // Lấy chương trình khuyến mãi đã áp dụng cho 1 hóa đơn
    public static function layTheoHoaDon($hoaDonId,$conn = null) {
        $db = self::layKetNoi($conn);
        $hoaDonId = intval($hoaDonId);
        if (!$db) {
            return null;
        }
        if ($hoaDonId <= 0) {
            return null;
        }

        $sql = "SELECT km.ID, km.TenChuongTrinh, km.HinhThucKhuyenMai, km.MucGiamGia
                FROM HoaDon_ChuongTrinhKhuyenMai hdkm
                JOIN ChuongTrinhKhuyenMai km ON km.ID = hdkm.ChuongTrinhKhuyenMaiID
                WHERE hdkm.HoaDonID = $hoaDonId
                LIMIT 1";

        $rs = mysqli_query($db,$sql);
        return ($rs) ? mysqli_fetch_assoc($rs) : null;
    }
}
?>