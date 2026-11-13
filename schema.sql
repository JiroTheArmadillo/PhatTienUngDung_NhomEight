SET NAMES utf8;

CREATE DATABASE IF NOT EXISTS eightmart DEFAULT CHARACTER SET utf8;
USE eightmart;

CREATE TABLE NhanVien (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Ten NVARCHAR(255) NOT NULL,
    Cccd NVARCHAR(255) unique NOT NULL,
    GioiTinh NVARCHAR(255) NOT NULL,
    Sdt NVARCHAR(255) NOT NULL,
    NgaySinh TIMESTAMP NOT NULL,
    TrangThai NVARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE TaiKhoan (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    TenDangNhap NVARCHAR(255) UNIQUE NOT NULL,
    MatKhau NVARCHAR(255) UNIQUE NOT NULL,
    VaiTro NVARCHAR(255) NULL,
    TrangThai NVARCHAR(255) NULL,
    NhanVienID INT NOT NULL UNIQUE,
    FOREIGN KEY (NhanVienID) REFERENCES NhanVien(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE KhachHang (
    ID INT AUTO_INCREMENT PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE NhaCungCap (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    TenNhaCungCap NVARCHAR(255) NULL,
    Email NVARCHAR(255) NULL,
    DiaChi NVARCHAR(255) NULL,
    LoaiSanPhamCungCap NVARCHAR(255) NULL,
    TrangThai NVARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE SanPham (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    TenSanPham NVARCHAR(255) NULL,
    GiaBan DECIMAL(19,4) NOT NULL,
    TrangThai NVARCHAR(255) NULL,
    MucTonToiThieu INT NOT NULL,
    MucTonMucTieu INT NOT NULL,
    SoLuongTon INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ChuongTrinhKhuyenMai (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    TenChuongTrinh NVARCHAR(255) NULL,
    MoTa NVARCHAR(255) NULL,
    ThoiGianBatDau TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ThoiGianKetThuc TIMESTAMP NULL,
    HinhThucKhuyenMai NVARCHAR(255) NULL,
    MucGiamGia FLOAT NOT NULL,
    TrangThai NVARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE CaLamViec (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    NgayGioBatDau TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    NgayGioKetThuc TIMESTAMP NULL,
    TrangThai NVARCHAR(255) NULL,
    NhanVienMoCaID INT NOT NULL,
    NhanVienDongCaID INT NULL,
    FOREIGN KEY (NhanVienMoCaID) REFERENCES NhanVien(ID),
    FOREIGN KEY (NhanVienDongCaID) REFERENCES NhanVien(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE HoaDon (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    KhachHangID INT NOT NULL,
    ThoiGianTao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    TrangThai NVARCHAR(255) NULL,
    TongTien DECIMAL(19,4) NOT NULL,
    LyDoHuy NVARCHAR(255) NULL,
    NhanVienID INT NOT NULL,
    CaLamViecID INT NOT NULL,
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID),
    FOREIGN KEY (NhanVienID) REFERENCES NhanVien(ID),
    FOREIGN KEY (CaLamViecID) REFERENCES CaLamViec(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE HoaDon_ChuongTrinhKhuyenMai (
    HoaDonID INT NOT NULL,
    ChuongTrinhKhuyenMaiID INT NOT NULL,
    PRIMARY KEY (HoaDonID, ChuongTrinhKhuyenMaiID),
    FOREIGN KEY (HoaDonID) REFERENCES HoaDon(ID),
    FOREIGN KEY (ChuongTrinhKhuyenMaiID) REFERENCES ChuongTrinhKhuyenMai(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ChiTietHoaDon (
    SoLuong INT NOT NULL,
    HoaDonID INT NOT NULL,
    DonGia DECIMAL(19,4) NOT NULL,
    ThanhTien DECIMAL(19,4) NOT NULL,
    SanPhamID INT NOT NULL,
    PRIMARY KEY (HoaDonID, SanPhamID),
    FOREIGN KEY (HoaDonID) REFERENCES HoaDon(ID),
    FOREIGN KEY (SanPhamID) REFERENCES SanPham(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ThanhToan (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    PhuongThucThanhToan NVARCHAR(255) NULL,
    SoTien DECIMAL(19,4) NOT NULL,
    TrangThai NVARCHAR(255) NULL,
    ThoiGian TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    MaGiaoDichNganHang NVARCHAR(255) NULL,
    NhanVienXacNhanID INT NULL,
    HoaDonID INT NOT NULL,
    KhachHangID INT NOT NULL,
    FOREIGN KEY (HoaDonID) REFERENCES HoaDon(ID),
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID),
    FOREIGN KEY (NhanVienXacNhanID) REFERENCES NhanVien(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE PhieuYeuCauTraHang (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    HoaDonID INT NOT NULL,
    LyDoHoanTra NVARCHAR(255) NULL,
    LyDoTuChoi NVARCHAR(255) NULL,
    NhanVienID INT NOT NULL,
    NhanVienXuLyID INT NULL,
    KhachHangID INT NOT NULL,
    NgayTao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    NgayXuLy TIMESTAMP NULL,
    TrangThai NVARCHAR(25) NOT NULL DEFAULT 'ChoDuyet',
    UNIQUE (ID, HoaDonID),
    FOREIGN KEY (HoaDonID) REFERENCES HoaDon(ID),
    FOREIGN KEY (NhanVienID) REFERENCES NhanVien(ID),
    FOREIGN KEY (NhanVienXuLyID) REFERENCES NhanVien(ID),
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ChiTietPhieuYeuCauTraHang (
    PhieuYeuCauTraHangID INT NOT NULL,
    HoaDonID INT NOT NULL,
    SanPhamID INT NOT NULL,
    SoLuong INT NOT NULL,
    PRIMARY KEY (PhieuYeuCauTraHangID, SanPhamID),
    FOREIGN KEY (PhieuYeuCauTraHangID, HoaDonID) REFERENCES PhieuYeuCauTraHang(ID, HoaDonID),
    FOREIGN KEY (HoaDonID, SanPhamID) REFERENCES ChiTietHoaDon(HoaDonID, SanPhamID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE NhaCungCap_SanPham (
    NhaCungCapID INT NOT NULL,
    SanPhamID INT NOT NULL,
    PRIMARY KEY (NhaCungCapID, SanPhamID),
    FOREIGN KEY (NhaCungCapID) REFERENCES NhaCungCap(ID),
    FOREIGN KEY (SanPhamID) REFERENCES SanPham(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE PhieuDeNghiDatHang (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    LyDoTuChoi NVARCHAR(255) NULL,
    TongChiPhi DECIMAL(19,4) NOT NULL,
    NhanVienID INT NOT NULL,
    NhanVienDuyetID INT NULL,
    NgayLap TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    NgayDuyet TIMESTAMP NULL,
    TrangThai NVARCHAR(25) NOT NULL DEFAULT 'ChoDuyet',
    FOREIGN KEY (NhanVienID) REFERENCES NhanVien(ID),
    FOREIGN KEY (NhanVienDuyetID) REFERENCES NhanVien(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ChiTietPhieuDeNghiDatHang (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    SoLuong INT NOT NULL,
    DonGia DECIMAL(19,4) NOT NULL,
    SanPhamID INT NOT NULL,
    PhieuDeNghiDatHangID INT NOT NULL,
    NhaCungCapID INT NOT NULL,
    UNIQUE (PhieuDeNghiDatHangID, SanPhamID, NhaCungCapID),
    FOREIGN KEY (PhieuDeNghiDatHangID) REFERENCES PhieuDeNghiDatHang(ID),
    FOREIGN KEY (NhaCungCapID, SanPhamID) REFERENCES NhaCungCap_SanPham(NhaCungCapID, SanPhamID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE PhieuNhapKho (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    NgayNhap TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    NhanVienID INT NOT NULL,
    PhieuDeNghiDatHangID INT NOT NULL,
    FOREIGN KEY (NhanVienID) REFERENCES NhanVien(ID),
    FOREIGN KEY (PhieuDeNghiDatHangID) REFERENCES PhieuDeNghiDatHang(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ChiTietPhieuNhapKho (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    SoLuongThucNhan INT NOT NULL,
    PhieuNhapKhoID INT NOT NULL,
    ChiTietPhieuDeNghiDatHangID INT NOT NULL,
    FOREIGN KEY (PhieuNhapKhoID) REFERENCES PhieuNhapKho(ID),
    FOREIGN KEY (ChiTietPhieuDeNghiDatHangID) REFERENCES ChiTietPhieuDeNghiDatHang(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;





-- ------------ DỮ LIỆU MẪU NÀY -----------------

INSERT INTO NhanVien (Ten, Cccd, GioiTinh, Sdt, NgaySinh, TrangThai) VALUES
('Nguyễn Văn An', '079201000001', 'Nam', '0901000001', '1995-03-12', 'HoatDong'),
('Trần Thị Bình', '079201000002', 'Nữ',  '0901000002', '1998-07-25', 'HoatDong'),
('Lê Văn Cường',  '079201000003', 'Nam', '0901000003', '1993-11-02', 'HoatDong'),
('Phạm Thị Dung', '079201000004', 'Nữ',  '0901000004', '2000-01-18', 'NgungHoatDong');

INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai, NhanVienID) VALUES
('an.nguyen',  MD5('12031995'), 'QuanLy',  'HoatDong',      1),
('binh.tran',  MD5('25071998'), 'ThuNgan', 'HoatDong',      2),
('cuong.le',   MD5('02111993'), 'ThuKho',  'HoatDong',      3),
('dung.pham',  MD5('18012000'), 'ThuNgan', 'NgungHoatDong', 4);

INSERT INTO KhachHang () VALUES (), (), ();

INSERT INTO NhaCungCap (TenNhaCungCap, Email, DiaChi, LoaiSanPhamCungCap, TrangThai) VALUES
('Công ty Thực phẩm Minh Phát', 'minhphat@example.com', '12 Lê Lợi, Quận 1, TP.HCM',       'Thực phẩm khô', 'HoatDong'),
('Công ty Đồ uống Hoàng Gia',   'hoanggia@example.com', '45 Nguyễn Huệ, Quận 1, TP.HCM',   'Đồ uống',       'HoatDong'),
('Công ty Gia dụng Tân Tiến',   'tantien@example.com',  '78 Trần Hưng Đạo, Quận 5, TP.HCM', 'Đồ gia dụng',   'NgungHoatDong');

INSERT INTO SanPham (TenSanPham, GiaBan, TrangThai, MucTonToiThieu, MucTonMucTieu, SoLuongTon) VALUES
('Mì gói',         25000.0000, 'HoatDong', 20, 200, 200),
('Bánh quy',       18000.0000, 'HoatDong', 20, 150, 150),
('Nước suối',      12000.0000, 'HoatDong', 10, 100,   8),
('Nước ngọt',      45000.0000, 'HoatDong', 15,  80,  60),
('Cà phê hòa tan', 30000.0000, 'HoatDong', 15, 100,  90),
('Bột giặt',       55000.0000, 'HoatDong', 10,  50,  40);

INSERT INTO NhaCungCap_SanPham (NhaCungCapID, SanPhamID) VALUES
(1, 1), (1, 2), (1, 3),
(2, 3), (2, 4), (2, 5),
(3, 6);

INSERT INTO ChuongTrinhKhuyenMai (TenChuongTrinh, MoTa, ThoiGianBatDau, ThoiGianKetThuc, HinhThucKhuyenMai, MucGiamGia, TrangThai) VALUES
('Giảm 10% đầu tháng', 'Giảm 10% tổng hoá đơn',  '2026-11-01 00:00:00', '2026-11-30 23:59:59', 'PhanTram', 10,   'HoatDong'),
('Giảm 5.000đ',        'Giảm trực tiếp 5.000đ', '2026-11-05 00:00:00', '2026-11-10 23:59:59', 'SoTien',   5000, 'HoatDong');

INSERT INTO CaLamViec (NgayGioBatDau, NgayGioKetThuc, TrangThai, NhanVienMoCaID, NhanVienDongCaID) VALUES
('2026-11-03 07:00:00', '2026-11-03 15:00:00', 'DaDong', 2, 2),
('2026-11-03 15:00:00', '2026-11-03 22:00:00', 'DaDong', 3, 3),
('2026-11-04 07:00:00', '2026-11-04 15:00:00', 'DaDong', 2, 2),
('2026-11-05 07:00:00', NULL,                  'DangMo', 2, NULL);

INSERT INTO HoaDon (KhachHangID, ThoiGianTao, TrangThai, TongTien, LyDoHuy, NhanVienID, CaLamViecID) VALUES
(1, '2026-11-03 09:30:00', 'HoanThanh', 68000.0000, NULL,          2, 1),
(2, '2026-11-03 10:15:00', 'HoanThanh', 81000.0000, NULL,          2, 1),
(3, '2026-11-03 18:20:00', 'HoanThanh', 76500.0000, NULL,          3, 2),
(1, '2026-11-04 08:45:00', 'HoanThanh', 91000.0000, NULL,          2, 3),
(2, '2026-11-04 12:00:00', 'DaHuy',     25000.0000, 'Khách đổi ý', 2, 3),
(3, '2026-11-05 09:10:00', 'HoanThanh', 85000.0000, NULL,          2, 4);

INSERT INTO HoaDon_ChuongTrinhKhuyenMai (HoaDonID, ChuongTrinhKhuyenMaiID) VALUES
(3, 1),
(6, 2);

INSERT INTO ChiTietHoaDon (SoLuong, HoaDonID, DonGia, ThanhTien, SanPhamID) VALUES
(2, 1, 25000.0000, 50000.0000, 1),
(1, 1, 18000.0000, 18000.0000, 2),
(3, 2, 12000.0000, 36000.0000, 3),
(1, 2, 45000.0000, 45000.0000, 4),
(2, 3, 30000.0000, 60000.0000, 5),
(1, 3, 25000.0000, 25000.0000, 1),
(2, 4, 18000.0000, 36000.0000, 2),
(1, 4, 55000.0000, 55000.0000, 6),
(1, 5, 25000.0000, 25000.0000, 1),
(2, 6, 45000.0000, 90000.0000, 4);

INSERT INTO ThanhToan (PhuongThucThanhToan, SoTien, TrangThai, ThoiGian, HoaDonID, KhachHangID) VALUES
('TienMat',     68000.0000, 'DaThanhToan', '2026-11-03 09:31:00', 1, 1),
('ChuyenKhoan', 81000.0000, 'DaThanhToan', '2026-11-03 10:16:00', 2, 2),
('TienMat',     76500.0000, 'DaThanhToan', '2026-11-03 18:21:00', 3, 3),
('ChuyenKhoan', 91000.0000, 'DaThanhToan', '2026-11-04 08:46:00', 4, 1),
('TienMat',     85000.0000, 'DaThanhToan', '2026-11-05 09:11:00', 6, 3);

INSERT INTO PhieuYeuCauTraHang (HoaDonID, LyDoHoanTra, LyDoTuChoi, NhanVienID, KhachHangID, NgayTao, TrangThai) VALUES
(1, 'Sản phẩm bị lỗi', NULL,              2, 1, '2026-11-03 10:00:00', 'ChoDuyet'),
(4, 'Không vừa ý',     'Quá hạn đổi trả', 2, 1, '2026-11-04 10:00:00', 'TuChoi');

INSERT INTO ChiTietPhieuYeuCauTraHang (PhieuYeuCauTraHangID, HoaDonID, SanPhamID, SoLuong) VALUES
(1, 1, 2, 1),
(2, 4, 6, 1);

INSERT INTO PhieuDeNghiDatHang (LyDoTuChoi, TongChiPhi, NhanVienID, NgayLap, TrangThai) VALUES
(NULL,             2760000.0000, 3, '2026-11-01 10:00:00', 'ChoDuyet'),
(NULL,             2630000.0000, 3, '2026-11-02 10:00:00', 'ChoDuyet'),
('Vượt ngân sách', 1200000.0000, 3, '2026-11-03 10:00:00', 'TuChoi');

INSERT INTO ChiTietPhieuDeNghiDatHang (SoLuong, DonGia, SanPhamID, PhieuDeNghiDatHangID, NhaCungCapID) VALUES
(100, 18000.0000, 1, 1, 1),
( 80, 12000.0000, 2, 1, 1),
( 50, 35000.0000, 4, 2, 2),
( 40, 22000.0000, 5, 2, 2),
( 30, 40000.0000, 6, 3, 3);

INSERT INTO PhieuNhapKho (NgayNhap, NhanVienID, PhieuDeNghiDatHangID) VALUES
('2026-11-02 08:00:00', 3, 1),
('2026-11-03 08:00:00', 3, 2);

INSERT INTO ChiTietPhieuNhapKho (SoLuongThucNhan, PhieuNhapKhoID, ChiTietPhieuDeNghiDatHangID) VALUES
(100, 1, 1),
( 80, 1, 2),
( 50, 2, 3),
( 35, 2, 4);