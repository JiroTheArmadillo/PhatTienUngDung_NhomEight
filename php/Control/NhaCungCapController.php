<?php
/** Controller quản lý nhà cung cấp: xem, thêm, sửa, xóa. */
require_once dirname(__FILE__) . '/../Entity/NhaCungCap.php';
require_once dirname(__FILE__) . '/../Repository/NhaCungCapRep.php';

class NhaCungCapController
{
    private $rep;

    public function xuLy()
    {
        if (!session_id()) session_start();
        $vm = array('danhSach' => array(), 'thongBao' => null, 'loiTai' => null, 'form' => null);

        if (isset($_SESSION['ncc_thongbao'])) {
            $vm['thongBao'] = $_SESSION['ncc_thongbao'];
            unset($_SESSION['ncc_thongbao']);
        }

        try {
            $this->rep = new NhaCungCapRep();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = isset($_POST['action']) ? $_POST['action'] : '';
                if ($action === 'them') $vm['form'] = $this->xuLyThem();
                elseif ($action === 'sua') $vm['form'] = $this->xuLySua();
                elseif ($action === 'xoa') $this->xuLyXoa();
            } else {
                $action = isset($_GET['action']) ? $_GET['action'] : '';
                if ($action === 'them') {
                    $vm['form'] = $this->bieuMau('them', 0, $this->giaTriTrong(), array(), '');
                } elseif ($action === 'sua') {
                    $ncc = $this->rep->layTheoMa(isset($_GET['id']) ? $_GET['id'] : 0);
                    if ($ncc === null) $vm['thongBao'] = array('danger', 'Không tìm thấy nhà cung cấp cần sửa.');
                    else $vm['form'] = $this->bieuMau('sua', $ncc->getMa(), $this->giaTriTuEntity($ncc), array(), '');
                }
            }
        } catch (Exception $e) {
            error_log('[NhaCungCap] ' . $e->getMessage());
            $vm['thongBao'] = array('danger', 'Không thể kết nối hoặc xử lý dữ liệu nhà cung cấp.');
        }

        try {
            if ($this->rep === null) throw new Exception('Chưa kết nối CSDL');
            $vm['danhSach'] = $this->rep->layTatCa();
        } catch (Exception $e) {
            error_log('[NhaCungCap] taiDanhSach: ' . $e->getMessage());
            $vm['loiTai'] = 'Không thể tải dữ liệu nhà cung cấp.';
        }
        return $vm;
    }

    private function xuLyThem()
    {
        $ncc = $this->entityTuPost();
        $loi = $ncc->kiemTra();
        if (!isset($loi['ten']) && $this->rep->tonTaiTen($ncc->getTen())) {
            $loi['ten'] = 'Tên nhà cung cấp đã tồn tại.';
        }
        if ($loi) return $this->bieuMau('them', 0, $this->giaTriTuEntity($ncc), $loi, '');
        try {
            $this->rep->batDauGiaoDich();
            $ma = $this->rep->them($ncc);
            $ncc->setMa($ma);
            $this->rep->xacNhan();
        } catch (Exception $e) {
            $this->rep->hoanTac(); error_log('[NhaCungCap] them: ' . $e->getMessage());
            return $this->bieuMau('them', 0, $this->giaTriTuEntity($ncc), array(), 'Không thể thêm nhà cung cấp.');
        }
        $this->chuyenHuong('success', 'Thêm nhà cung cấp thành công: ' . $ncc->getMaHienThi());
        return null;
    }

    private function xuLySua()
    {
        $ma = (int) $this->post('ma_nha_cung_cap');
        $hienTai = $this->rep->layTheoMa($ma);
        if ($hienTai === null) { $this->chuyenHuong('danger', 'Nhà cung cấp không tồn tại.'); return null; }
        $ncc = $this->entityTuPost(); $ncc->setMa($ma);
        $loi = $ncc->kiemTra();
        if (!isset($loi['ten']) && $this->rep->tonTaiTen($ncc->getTen(), $ma)) $loi['ten'] = 'Tên nhà cung cấp đã tồn tại.';
        if ($loi) return $this->bieuMau('sua', $ma, $this->giaTriTuEntity($ncc), $loi, '');
        try {
            $this->rep->batDauGiaoDich(); $this->rep->capNhat($ncc); $this->rep->xacNhan();
        } catch (Exception $e) {
            $this->rep->hoanTac(); error_log('[NhaCungCap] sua: ' . $e->getMessage());
            return $this->bieuMau('sua', $ma, $this->giaTriTuEntity($ncc), array(), 'Cập nhật không thành công.');
        }
        $this->chuyenHuong('success', 'Cập nhật nhà cung cấp thành công.'); return null;
    }

    private function xuLyXoa()
    {
        $ma = (int) $this->post('ma_nha_cung_cap');
        $ncc = $this->rep->layTheoMa($ma);
        if ($ncc === null) { $this->chuyenHuong('danger', 'Nhà cung cấp không tồn tại.'); return; }
        try {
            if ($this->rep->dangDuocThamChieu($ma)) {
                $this->chuyenHuong('danger', 'Không thể xóa nhà cung cấp vì đang được dùng trong sản phẩm hoặc phiếu đặt hàng.'); return;
            }
            $this->rep->xoa($ma);
        } catch (Exception $e) {
            error_log('[NhaCungCap] xoa: ' . $e->getMessage());
            $this->chuyenHuong('danger', 'Xóa nhà cung cấp không thành công.'); return;
        }
        $this->chuyenHuong('success', 'Đã xóa nhà cung cấp ' . $ncc->getMaHienThi() . '.');
    }

    private function entityTuPost()
    {
        return new NhaCungCap($this->post('ten_nha_cung_cap'), $this->post('email'), $this->post('dia_chi'),
            $this->post('loai_san_pham_cung_cap'), $this->post('trang_thai') === '' ? NhaCungCap::TT_HOAT_DONG : $this->post('trang_thai'));
    }

    private function giaTriTrong() { return array('ten_nha_cung_cap'=>'', 'email'=>'', 'dia_chi'=>'', 'loai_san_pham_cung_cap'=>'', 'trang_thai'=>NhaCungCap::TT_HOAT_DONG); }
    private function giaTriTuEntity($ncc) { return array('ten_nha_cung_cap'=>$ncc->getTen(), 'email'=>$ncc->getEmail(), 'dia_chi'=>$ncc->getDiaChi(), 'loai_san_pham_cung_cap'=>$ncc->getLoaiSanPham(), 'trang_thai'=>$ncc->getTrangThai()); }

    private function bieuMau($mode, $ma, $values, $errors, $loiChung)
    { return array('mode'=>$mode, 'ma'=>$ma, 'values'=>$values, 'errors'=>$errors, 'loiChung'=>$loiChung); }
    private function post($key) { return isset($_POST[$key]) ? trim((string) $_POST[$key]) : ''; }
    private function chuyenHuong($type, $message) { $_SESSION['ncc_thongbao'] = array($type, $message); header('Location: GD_QuanLyNhaCungCap.php'); exit; }
}
