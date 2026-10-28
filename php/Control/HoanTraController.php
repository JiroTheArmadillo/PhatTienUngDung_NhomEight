<?php
require_once dirname(__FILE__) . '/../Repository/HoanTraRepository.php';

/**
 * Control: xử lý nghiệp vụ "Xử lý yêu cầu hoàn trả" (Quản lý bán hàng).
 *  - Chấp nhận: cập nhật trạng thái "Đã duyệt".
 *  - Từ chối: bắt buộc có lý do, cập nhật trạng thái "Từ chối" và lưu lý do.
 */
class HoanTraController
{
    const CHO_DUYET = 'ChoDuyet';
    const DA_DUYET  = 'DaDuyet';
    const TU_CHOI   = 'TuChoi';

    private $repo;

    public function __construct()
    {
        $this->repo = new HoanTraRepository();
    }

    public function layDanhSach()
    {
        return $this->repo->layDanhSach(self::CHO_DUYET);
    }

    /** Trả về array('ok' => bool, 'message' => string). */
    public function chapNhan($maYeuCau, $maNguoiXuLy = null)
    {
        $maYeuCau = (int)$maYeuCau;

        try {
            $loi = $this->kiemTraDangChoDuyet($maYeuCau);
            if ($loi !== null) {
                return $this->ketQua(false, $loi);
            }
            if (!$this->repo->capNhatTrangThai($maYeuCau, self::DA_DUYET, null, $maNguoiXuLy, self::CHO_DUYET)) {
                return $this->ketQua(false, 'Yêu cầu này đã được xử lý trước đó.');
            }
            return $this->ketQua(true, 'Đã chấp nhận yêu cầu hoàn trả.');
        } catch (Exception $e) {
            return $this->loiHeThong($e);
        }
    }

    /** Trả về array('ok' => bool, 'message' => string). */
    public function tuChoi($maYeuCau, $lyDo, $maNguoiXuLy = null)
    {
        $maYeuCau = (int)$maYeuCau;
        $lyDo = trim((string)$lyDo);

        if ($lyDo === '') {
            return $this->ketQua(false, 'Vui lòng nhập lý do từ chối.');
        }
        if ($this->doDai($lyDo) > 500) {
            return $this->ketQua(false, 'Lý do từ chối không được quá 500 ký tự.');
        }

        try {
            $loi = $this->kiemTraDangChoDuyet($maYeuCau);
            if ($loi !== null) {
                return $this->ketQua(false, $loi);
            }
            if (!$this->repo->capNhatTrangThai($maYeuCau, self::TU_CHOI, $lyDo, $maNguoiXuLy, self::CHO_DUYET)) {
                return $this->ketQua(false, 'Yêu cầu này đã được xử lý trước đó.');
            }
            return $this->ketQua(true, 'Đã từ chối yêu cầu hoàn trả.');
        } catch (Exception $e) {
            return $this->loiHeThong($e);
        }
    }

    // ---------------- Hàm hỗ trợ ----------------

    /** null nếu yêu cầu tồn tại và đang chờ duyệt, ngược lại trả về thông báo lỗi. */
    private function kiemTraDangChoDuyet($maYeuCau)
    {
        $trangThai = $this->repo->layTrangThai($maYeuCau);

        if ($trangThai === null) {
            return 'Không tìm thấy yêu cầu hoàn trả.';
        }
        if (trim($trangThai) !== self::CHO_DUYET) {
            return 'Yêu cầu này đã được xử lý trước đó.';
        }
        return null;
    }

    /** Độ dài chuỗi UTF-8 (không phụ thuộc extension mbstring). */
    private function doDai($s)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($s, 'UTF-8');
        }
        return preg_match_all('/./us', $s, $m);
    }

    private function loiHeThong($e)
    {
        error_log('[HoanTra] ' . $e->getMessage());
        return $this->ketQua(false, 'Có lỗi hệ thống khi xử lý yêu cầu, vui lòng thử lại.');
    }

    private function ketQua($ok, $message)
    {
        return array('ok' => $ok, 'message' => $message);
    }
}
