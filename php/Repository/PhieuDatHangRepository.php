<?php
// php/Repository/PhieuDatHangRepository.php
require_once dirname(__FILE__) . '/../Entity/PhieuDeNghiDatHang.php';
require_once dirname(__FILE__) . '/../Entity/ChiTietPhieuDatHang.php';
require_once dirname(__FILE__) . '/../../db.php';

class PhieuDatHangRepository {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function getDanhSachNhaCungCap() {
        $list = array();
        $res = $this->conn->query("SELECT ID AS ma_nha_cung_cap, TenNhaCungCap AS ten_nha_cung_cap
            FROM NhaCungCap WHERE TrangThai = 'HoatDong' ORDER BY TenNhaCungCap");
        if (!$res) throw new Exception('Không thể tải danh sách nhà cung cấp: ' . $this->conn->error);
        while ($row = $res->fetch_assoc()) $list[] = $row;
        $res->free();
        return $list;
    }

    public function getDanhSachNhaCungCapTheoSanPham($maSanPham) {
        $stmt = eightmart_mysqli_execute("SELECT n.ID AS ma_nha_cung_cap,
                n.TenNhaCungCap AS ten_nha_cung_cap
            FROM NhaCungCap n
            INNER JOIN NhaCungCap_SanPham ns ON ns.NhaCungCapID = n.ID
            WHERE ns.SanPhamID = ? AND n.TrangThai = 'HoatDong'
            ORDER BY n.TenNhaCungCap", array((int)$maSanPham));
        return eightmart_mysqli_fetch_all_assoc($stmt);
    }

    public function getSanPhamCanNhap() {
        // Lấy sản phẩm dưới mức tồn tối thiểu chưa có trong phiếu đang chờ duyệt.
        $sql = "SELECT sp.ID AS ma_san_pham, sp.TenSanPham AS ten_san_pham,
                       sp.GiaBan AS gia_ban, sp.SoLuongTon AS so_luong_ton
                  FROM SanPham sp
                 WHERE sp.SoLuongTon <= sp.MucTonToiThieu
                   AND sp.TrangThai = 'HoatDong'
                   AND NOT EXISTS (
                       SELECT 1
                         FROM ChiTietPhieuDeNghiDatHang ct
                         INNER JOIN PhieuDeNghiDatHang p ON p.ID = ct.PhieuDeNghiDatHangID
                        WHERE ct.SanPhamID = sp.ID AND p.TrangThai = 'ChoDuyet'
                   )
                 ORDER BY sp.ID";
        $result = $this->conn->query($sql);
        if (!$result) throw new Exception('Không thể tải danh sách sản phẩm cần nhập: ' . $this->conn->error);
        return $result;
    }

    // Nhận trực tiếp đối tượng Entity PhieuDeNghiDatHang
    public function savePhieuDatHang(PhieuDeNghiDatHang $phieu) {
        $transactionStarted = false;
        try {
            eightmart_mysqli_begin_transaction();
            $transactionStarted = true;
            $ma_nv = (int)$phieu->getMaNhanVien();
            $tong_chi_phi = (float)$phieu->getTongChiPhi();
            $st = eightmart_mysqli_execute("INSERT INTO PhieuDeNghiDatHang
                (LyDoTuChoi, TongChiPhi, NhanVienID, NgayLap, TrangThai)
                VALUES (NULL, ?, ?, ?, 'ChoDuyet')",
                array($tong_chi_phi, $ma_nv, $phieu->getNgayLap()));
            $st->close();
            $ma_phieu_moi = (int)$this->conn->insert_id;

            foreach ($phieu->getDanhSachChiTiet() as $ct) {
                $kiemTraNcc = eightmart_mysqli_execute("SELECT COUNT(*) FROM NhaCungCap_SanPham ns
                    INNER JOIN NhaCungCap n ON n.ID = ns.NhaCungCapID
                    WHERE ns.NhaCungCapID = ? AND ns.SanPhamID = ? AND n.TrangThai = 'HoatDong'",
                    array((int)$phieu->getMaNhaCungCap(), (int)$ct->getMaSanPham()));
                if ((int)eightmart_mysqli_fetch_value($kiemTraNcc) === 0) {
                    throw new InvalidArgumentException('Nhà cung cấp không cung cấp sản phẩm đã chọn.');
                }
                $st = eightmart_mysqli_execute("INSERT INTO ChiTietPhieuDeNghiDatHang
                    (SoLuong, DonGia, SanPhamID, PhieuDeNghiDatHangID, NhaCungCapID)
                    VALUES (?, ?, ?, ?, ?)",
                    array((int)$ct->getSoLuong(), (float)$ct->getDonGia(), (int)$ct->getMaSanPham(),
                        $ma_phieu_moi, (int)$phieu->getMaNhaCungCap()));
                $st->close();
            }
            eightmart_mysqli_commit();
            $transactionStarted = false;
            return $ma_phieu_moi;
        } catch (Exception $e) {
            if ($transactionStarted) eightmart_mysqli_rollback();
            throw $e;
        }
    }

    public function getPhieuVuaTao($ma_phieu) {
        $st = eightmart_mysqli_execute("SELECT p.ID AS ma_phieu_de_nghi_dat_hang,
                p.NgayLap AS ngay_lap, p.TrangThai AS trang_thai,
                p.TongChiPhi AS tong_chi_phi, nv.Ten AS ten_nv,
                (SELECT n.TenNhaCungCap
                   FROM ChiTietPhieuDeNghiDatHang ct
                   INNER JOIN NhaCungCap n ON n.ID = ct.NhaCungCapID
                  WHERE ct.PhieuDeNghiDatHangID = p.ID LIMIT 1) AS ten_nha_cung_cap
            FROM PhieuDeNghiDatHang p
            INNER JOIN NhanVien nv ON nv.ID = p.NhanVienID
            WHERE p.ID = ? LIMIT 1", array((int)$ma_phieu));
        return eightmart_mysqli_fetch_one_assoc($st);
    }

    public function getChiTietPhieuVuaTao($ma_phieu) {
        $st = eightmart_mysqli_execute("SELECT ct.SoLuong AS so_luong, ct.DonGia AS don_gia,
                sp.TenSanPham AS ten_san_pham
            FROM ChiTietPhieuDeNghiDatHang ct
            INNER JOIN SanPham sp ON sp.ID = ct.SanPhamID
            WHERE ct.PhieuDeNghiDatHangID = ? ORDER BY ct.ID",
            array((int)$ma_phieu));
        return eightmart_mysqli_fetch_all_assoc($st);
    }
}
?>