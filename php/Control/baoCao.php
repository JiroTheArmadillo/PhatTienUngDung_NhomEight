<?php
// Dat file tai php/Control/baoCao.php. Su dung ket noi trong db.php o thu muc goc.
require_once dirname(__FILE__) . '/../../db.php';

class BaoCao
{
    private $db;

    public function __construct()
    {
        $this->db = eightmart_mysqli_connection();
    }

    private function rows($sql)
    {
        $result = $this->db->query($sql);
        if (!$result) {
            throw new Exception('Loi truy van bao cao: ' . $this->db->error);
        }
        $data = array();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $result->free();
        return $data;
    }

    private function id($value)
    {
        if (!is_scalar($value)) {
            throw new Exception('Ma tra cuu khong hop le.');
        }
        $value = trim((string)$value);
        if (!preg_match('/^[0-9]{1,10}$/D', $value)
            || (float)$value < 1 || (float)$value > 2147483647) {
            throw new Exception('Ma tra cuu khong hop le.');
        }
        return (int)$value;
    }

    private function sqlNhanVien()
    {
        // Vai tro nam trong TaiKhoan, khong nam trong NhanVien.
        // LEFT JOIN giu ca nhan vien chua duoc cap tai khoan.
        return "SELECT nv.ID AS ma_nv, nv.Ten AS ho_ten,
                       nv.GioiTinh AS gioi_tinh, nv.Sdt AS so_dien_thoai,
                       COALESCE(tk.VaiTro, '') AS chuc_vu
                FROM NhanVien nv
                LEFT JOIN TaiKhoan tk ON tk.NhanVienID = nv.ID";
    }

    private function sqlCa()
    {
        return 'SELECT ID AS ma_ca, NgayGioBatDau AS bat_dau,
                       NgayGioKetThuc AS ket_thuc, TrangThai AS trang_thai,
                       NhanVienMoCaID AS ma_nv_mo_ca
                FROM CaLamViec';
    }

    public function getDanhSachNhanVien()
    {
        return $this->rows($this->sqlNhanVien() . ' ORDER BY nv.ID');
    }

    public function getDanhSachCa()
    {
        return $this->rows($this->sqlCa() . ' ORDER BY NgayGioBatDau DESC, ID DESC');
    }

    private function thongKe($field, $id)
    {
        // Chi cho phep hai cot noi bo, khong nhan ten cot tuy y.
        if ($field !== 'NhanVienID' && $field !== 'CaLamViecID') {
            throw new Exception('Truong thong ke khong hop le.');
        }
        $id = $this->id($id);
        // Schema hien tai: HoaDon = HoanThanh; ThanhToan = DaThanhToan.
        // EXISTS giup moi hoa don chi duoc tinh mot lan du co nhieu dong thanh toan.
        $sql = "SELECT COUNT(*) AS tong_hoa_don,
                       COALESCE(SUM(h.TongTien), 0) AS tong_doanh_thu
                FROM HoaDon h
                WHERE h." . $field . ' = ' . $id . "
                  AND h.TrangThai = 'HoanThanh'
                  AND EXISTS (
                      SELECT 1 FROM ThanhToan t
                      WHERE t.HoaDonID = h.ID
                        AND t.TrangThai = 'DaThanhToan'
                  )";
        $rows = $this->rows($sql);
        return $rows[0];
    }

    public function getBaoCaoNhanVien($maNV)
    {
        $id = $this->id($maNV);
        $list = $this->rows($this->sqlNhanVien() . ' WHERE nv.ID = ' . $id . ' LIMIT 1');
        if (count($list) === 0) {
            return null;
        }
        return array('thong_tin' => $list[0],
                     'thong_ke' => $this->thongKe('NhanVienID', $id));
    }

    public function getBaoCaoCa($maCa)
    {
        $id = $this->id($maCa);
        $list = $this->rows($this->sqlCa() . ' WHERE ID = ' . $id . ' LIMIT 1');
        if (count($list) === 0) {
            return null;
        }
        return array('thong_tin' => $list[0],
                     'thong_ke' => $this->thongKe('CaLamViecID', $id));
    }

    private function ngayHopLe($ngay)
    {
        if (!is_string($ngay) || !preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $ngay, $m)) {
            return false;
        }
        return (int)$m[1] >= 1970 && (int)$m[1] <= 9998
            && checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }

    public function getDashboard($loai, $ma, $tuNgay, $denNgay, $nhom)
    {
        if (!in_array($loai, array('tong', 'ca', 'nv'), true)
            || !in_array($nhom, array('ngay', 'thang'), true)) {
            throw new Exception('Loai bao cao khong hop le.');
        }
        $info = null;
        $params = array();
        $where = '';
        if ($loai === 'ca') {
            $ma = $this->id($ma);
            $rows = $this->rows($this->sqlCa() . ' WHERE ID = ' . $ma);
            if (!$rows) throw new Exception('Ca lam viec khong ton tai.');
            $info = $rows[0];
            $where .= ' AND h.CaLamViecID = ?';
            $params[] = $ma;
        } else {
            if (!$this->ngayHopLe($tuNgay) || !$this->ngayHopLe($denNgay) || $tuNgay > $denNgay) {
                throw new Exception('Khoang ngay khong hop le. Ngay bat dau phai truoc hoac bang ngay ket thuc.');
            }
            if ((strtotime($denNgay) - strtotime($tuNgay)) / 86400 > 366) {
                throw new Exception('Vui long chon khoang thoi gian toi da 367 ngay.');
            }
            $where .= ' AND t.ThoiGian >= ? AND t.ThoiGian < ?';
            $params[] = $tuNgay . ' 00:00:00';
            $params[] = date('Y-m-d', strtotime($denNgay . ' +1 day')) . ' 00:00:00';
            if ($loai === 'nv') {
                $ma = $this->id($ma);
                $rows = $this->rows($this->sqlNhanVien() . ' WHERE nv.ID = ' . $ma);
                if (!$rows) throw new Exception('Nhan vien khong ton tai.');
                $info = $rows[0];
                $where .= ' AND h.NhanVienID = ?';
                $params[] = $ma;
            }
        }
        // Chon lan thanh toan thanh cong cuoi cua moi hoa don, khong nhan doi doanh thu.
        // Luong ban hang hien tai dung mot phuong thuc thanh toan cho mot hoa don.
        $sql = "SELECT h.ID AS ma_hoa_don, h.CaLamViecID AS ma_ca,
                       h.TongTien AS tong_tien, nv.Ten AS nhan_vien,
                       t.ThoiGian AS thoi_gian, t.PhuongThucThanhToan AS phuong_thuc
                FROM HoaDon h
                INNER JOIN NhanVien nv ON nv.ID = h.NhanVienID
                INNER JOIN ThanhToan t ON t.HoaDonID = h.ID
                WHERE h.TrangThai = 'HoanThanh'
                  AND t.TrangThai = 'DaThanhToan'
                  AND t.ID = (SELECT MAX(t2.ID) FROM ThanhToan t2
                              WHERE t2.HoaDonID = h.ID AND t2.TrangThai = 'DaThanhToan')";
        $st = eightmart_mysqli_execute($sql . $where . ' ORDER BY t.ThoiGian DESC, h.ID DESC', $params);
        $hoaDon = eightmart_mysqli_fetch_all_assoc($st);
        $stats = array('doanh_thu'=>0, 'so_don'=>count($hoaDon), 'tien_mat'=>0,
                       'chuyen_khoan'=>0, 'khac'=>0, 'trung_binh'=>0);
        $bieuDo = array();
        if ($loai !== 'ca') {
            // Them moc khong co giao dich de bieu do khong bo qua ngay doanh thu bang 0.
            $cursor = strtotime($tuNgay);
            $end = strtotime($denNgay);
            while ($cursor <= $end) {
                $key = date($nhom === 'thang' ? 'Y-m' : 'Y-m-d', $cursor);
                $bieuDo[$key] = 0;
                $cursor = strtotime('+1 day', $cursor);
            }
        }
        foreach ($hoaDon as $hd) {
            $tien = (float)$hd['tong_tien'];
            $stats['doanh_thu'] += $tien;
            $key = $hd['phuong_thuc'] === 'TienMat' ? 'tien_mat' :
                ($hd['phuong_thuc'] === 'ChuyenKhoan' ? 'chuyen_khoan' : 'khac');
            $stats[$key] += $tien;
            if ($hd['thoi_gian']) {
                $format = $loai === 'ca' ? 'Y-m-d H:00' : ($nhom === 'thang' ? 'Y-m' : 'Y-m-d');
                $moc = date($format, strtotime($hd['thoi_gian']));
            } else {
                $moc = 'Chua co thoi gian';
            }
            if (!isset($bieuDo[$moc])) $bieuDo[$moc] = 0;
            $bieuDo[$moc] += $tien;
        }
        ksort($bieuDo);
        if ($stats['so_don']) $stats['trung_binh'] = $stats['doanh_thu'] / $stats['so_don'];
        return array('thong_tin'=>$info, 'thong_ke'=>$stats, 'bieu_do'=>$bieuDo, 'hoa_don'=>$hoaDon);
    }
}
