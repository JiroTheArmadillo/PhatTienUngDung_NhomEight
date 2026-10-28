-- MySQL dump 10.11
--
-- Host: localhost    Database: eightmart
-- ------------------------------------------------------
-- Server version	5.0.51b-community-nt

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ca_lam_viec`
--

DROP TABLE IF EXISTS `ca_lam_viec`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ca_lam_viec` (
  `ma_ca` int(11) NOT NULL auto_increment,
  `ma_nhan_vien` int(11) NOT NULL,
  `ngay_gio_bat_dau` datetime NOT NULL,
  `ngay_gio_ket_thuc` datetime default NULL,
  `trang_thai` varchar(20) NOT NULL default 'DangMo',
  PRIMARY KEY  (`ma_ca`),
  KEY `ix_ca_nv` (`ma_nhan_vien`),
  CONSTRAINT `ca_lam_viec_ibfk_1` FOREIGN KEY (`ma_nhan_vien`) REFERENCES `nhan_vien` (`ma_nhan_vien`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `ca_lam_viec`
--

LOCK TABLES `ca_lam_viec` WRITE;
/*!40000 ALTER TABLE `ca_lam_viec` DISABLE KEYS */;
INSERT INTO `ca_lam_viec` VALUES (1,2,'2026-10-20 08:00:00','2026-10-20 16:00:00','DaDong'),(2,2,'2026-10-28 17:32:12',NULL,'DangMo');
/*!40000 ALTER TABLE `ca_lam_viec` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chi_tiet_don_hang`
--

DROP TABLE IF EXISTS `chi_tiet_don_hang`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `chi_tiet_don_hang` (
  `ma_hoa_don` int(11) NOT NULL,
  `ma_san_pham` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL,
  `don_gia` decimal(12,2) NOT NULL,
  `thanh_tien` decimal(14,2) NOT NULL,
  PRIMARY KEY  (`ma_hoa_don`,`ma_san_pham`),
  KEY `ma_san_pham` (`ma_san_pham`),
  CONSTRAINT `chi_tiet_don_hang_ibfk_1` FOREIGN KEY (`ma_hoa_don`) REFERENCES `don_hang` (`ma_hoa_don`),
  CONSTRAINT `chi_tiet_don_hang_ibfk_2` FOREIGN KEY (`ma_san_pham`) REFERENCES `san_pham` (`ma_san_pham`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `chi_tiet_don_hang`
--

LOCK TABLES `chi_tiet_don_hang` WRITE;
/*!40000 ALTER TABLE `chi_tiet_don_hang` DISABLE KEYS */;
INSERT INTO `chi_tiet_don_hang` VALUES (1,1,2,'6000.00','12000.00'),(2,1,3,'6000.00','18000.00'),(2,2,3,'15000.00','45000.00'),(3,1,1,'6000.00','6000.00'),(3,2,1,'15000.00','15000.00');
/*!40000 ALTER TABLE `chi_tiet_don_hang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chi_tiet_phieu_dat_hang`
--

DROP TABLE IF EXISTS `chi_tiet_phieu_dat_hang`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `chi_tiet_phieu_dat_hang` (
  `ma_phieu_de_nghi_dat_hang` int(11) NOT NULL,
  `ma_san_pham` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL,
  `don_gia` decimal(12,2) NOT NULL,
  PRIMARY KEY  (`ma_phieu_de_nghi_dat_hang`,`ma_san_pham`),
  KEY `ma_san_pham` (`ma_san_pham`),
  CONSTRAINT `chi_tiet_phieu_dat_hang_ibfk_1` FOREIGN KEY (`ma_phieu_de_nghi_dat_hang`) REFERENCES `phieu_de_nghi_dat_hang` (`ma_phieu_de_nghi_dat_hang`),
  CONSTRAINT `chi_tiet_phieu_dat_hang_ibfk_2` FOREIGN KEY (`ma_san_pham`) REFERENCES `san_pham` (`ma_san_pham`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `chi_tiet_phieu_dat_hang`
--

LOCK TABLES `chi_tiet_phieu_dat_hang` WRITE;
/*!40000 ALTER TABLE `chi_tiet_phieu_dat_hang` DISABLE KEYS */;
INSERT INTO `chi_tiet_phieu_dat_hang` VALUES (1,1,30,'4200.00');
/*!40000 ALTER TABLE `chi_tiet_phieu_dat_hang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chi_tiet_phieu_nhap_kho`
--

DROP TABLE IF EXISTS `chi_tiet_phieu_nhap_kho`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `chi_tiet_phieu_nhap_kho` (
  `ma_phieu_nhap` int(11) NOT NULL,
  `ma_san_pham` int(11) NOT NULL,
  `so_luong_thuc_nhan` int(11) NOT NULL,
  `so_lo` varchar(60) NOT NULL,
  `han_su_dung` date default NULL,
  `don_gia_nhap` decimal(12,2) default NULL,
  PRIMARY KEY  (`ma_phieu_nhap`,`ma_san_pham`,`so_lo`),
  KEY `ma_san_pham` (`ma_san_pham`),
  CONSTRAINT `chi_tiet_phieu_nhap_kho_ibfk_1` FOREIGN KEY (`ma_phieu_nhap`) REFERENCES `phieu_nhap_kho` (`ma_phieu_nhap`),
  CONSTRAINT `chi_tiet_phieu_nhap_kho_ibfk_2` FOREIGN KEY (`ma_san_pham`) REFERENCES `san_pham` (`ma_san_pham`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `chi_tiet_phieu_nhap_kho`
--

LOCK TABLES `chi_tiet_phieu_nhap_kho` WRITE;
/*!40000 ALTER TABLE `chi_tiet_phieu_nhap_kho` DISABLE KEYS */;
/*!40000 ALTER TABLE `chi_tiet_phieu_nhap_kho` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chi_tiet_tra_hang`
--

DROP TABLE IF EXISTS `chi_tiet_tra_hang`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `chi_tiet_tra_hang` (
  `ma_yeu_cau` int(11) NOT NULL,
  `ma_san_pham` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL,
  PRIMARY KEY  (`ma_yeu_cau`,`ma_san_pham`),
  KEY `ma_san_pham` (`ma_san_pham`),
  CONSTRAINT `chi_tiet_tra_hang_ibfk_1` FOREIGN KEY (`ma_yeu_cau`) REFERENCES `phieu_yeu_cau_tra_hang` (`ma_yeu_cau`),
  CONSTRAINT `chi_tiet_tra_hang_ibfk_2` FOREIGN KEY (`ma_san_pham`) REFERENCES `san_pham` (`ma_san_pham`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `chi_tiet_tra_hang`
--

LOCK TABLES `chi_tiet_tra_hang` WRITE;
/*!40000 ALTER TABLE `chi_tiet_tra_hang` DISABLE KEYS */;
/*!40000 ALTER TABLE `chi_tiet_tra_hang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chuong_trinh_khuyen_mai`
--

DROP TABLE IF EXISTS `chuong_trinh_khuyen_mai`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `chuong_trinh_khuyen_mai` (
  `ma_khuyen_mai` int(11) NOT NULL auto_increment,
  `ten_chuong_trinh` varchar(150) NOT NULL,
  `mo_ta` text,
  `thoi_gian_bat_dau` datetime NOT NULL,
  `thoi_gian_ket_thuc` datetime NOT NULL,
  `hinh_thuc_khuyen_mai` varchar(50) NOT NULL,
  `muc_giam_gia` decimal(12,2) NOT NULL default '0.00',
  `trang_thai` varchar(20) NOT NULL default 'HoatDong',
  PRIMARY KEY  (`ma_khuyen_mai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `chuong_trinh_khuyen_mai`
--

LOCK TABLES `chuong_trinh_khuyen_mai` WRITE;
/*!40000 ALTER TABLE `chuong_trinh_khuyen_mai` DISABLE KEYS */;
/*!40000 ALTER TABLE `chuong_trinh_khuyen_mai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `don_hang`
--

DROP TABLE IF EXISTS `don_hang`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `don_hang` (
  `ma_hoa_don` int(11) NOT NULL auto_increment,
  `ma_nhan_vien` int(11) NOT NULL,
  `ma_khach_hang` int(11) default NULL,
  `ma_ca` int(11) NOT NULL,
  `ngay_lap` datetime NOT NULL,
  `trang_thai` varchar(25) NOT NULL default 'ChoThanhToan',
  `tong_tien` decimal(14,2) NOT NULL default '0.00',
  `ly_do_huy` text,
  PRIMARY KEY  (`ma_hoa_don`),
  KEY `ma_nhan_vien` (`ma_nhan_vien`),
  KEY `ma_khach_hang` (`ma_khach_hang`),
  KEY `ma_ca` (`ma_ca`),
  CONSTRAINT `don_hang_ibfk_1` FOREIGN KEY (`ma_nhan_vien`) REFERENCES `nhan_vien` (`ma_nhan_vien`),
  CONSTRAINT `don_hang_ibfk_2` FOREIGN KEY (`ma_khach_hang`) REFERENCES `khach_hang` (`ma_khach_hang`),
  CONSTRAINT `don_hang_ibfk_3` FOREIGN KEY (`ma_ca`) REFERENCES `ca_lam_viec` (`ma_ca`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `don_hang`
--

LOCK TABLES `don_hang` WRITE;
/*!40000 ALTER TABLE `don_hang` DISABLE KEYS */;
INSERT INTO `don_hang` VALUES (1,2,1,1,'2026-10-20 09:00:00','DaThanhToan','12000.00',NULL),(2,2,2,2,'2026-10-28 17:32:12','DaThanhToan','63000.00',NULL),(3,2,3,2,'2026-10-28 17:49:22','DaThanhToan','21000.00',NULL);
/*!40000 ALTER TABLE `don_hang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `khach_hang`
--

DROP TABLE IF EXISTS `khach_hang`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `khach_hang` (
  `ma_khach_hang` int(11) NOT NULL auto_increment,
  `ten` varchar(120) NOT NULL,
  `sdt` varchar(20) default NULL,
  `dia_chi` varchar(255) default NULL,
  `hang_thanh_vien` varchar(30) default NULL,
  `diem` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ma_khach_hang`),
  UNIQUE KEY `uq_kh_sdt` (`sdt`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `khach_hang`
--

LOCK TABLES `khach_hang` WRITE;
/*!40000 ALTER TABLE `khach_hang` DISABLE KEYS */;
INSERT INTO `khach_hang` VALUES (1,'Khach hang mau','0911111111','TP HCM','Thuong',0),(2,'Khách lẻ',NULL,NULL,NULL,0),(3,'Khách lẻ',NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `khach_hang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nha_cung_cap`
--

DROP TABLE IF EXISTS `nha_cung_cap`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nha_cung_cap` (
  `ma_nha_cung_cap` int(11) NOT NULL auto_increment,
  `ten_nha_cung_cap` varchar(150) NOT NULL,
  `email` varchar(120) default NULL,
  `dia_chi` varchar(255) default NULL,
  `loai_san_pham_cung_cap` varchar(200) default NULL,
  `trang_thai` varchar(20) NOT NULL default 'HoatDong',
  PRIMARY KEY  (`ma_nha_cung_cap`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `nha_cung_cap`
--

LOCK TABLES `nha_cung_cap` WRITE;
/*!40000 ALTER TABLE `nha_cung_cap` DISABLE KEYS */;
INSERT INTO `nha_cung_cap` VALUES (1,'NCC thuc pham','ncc@example.com','TP HCM','Thuc pham','HoatDong'),(2,'VINHSO','nguyenvancongvinh10a11@gmail.com','ÁDFSDFAS','milk','HoatDong');
/*!40000 ALTER TABLE `nha_cung_cap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nhan_vien`
--

DROP TABLE IF EXISTS `nhan_vien`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nhan_vien` (
  `ma_nhan_vien` int(11) NOT NULL auto_increment,
  `ten` varchar(120) NOT NULL,
  `cccd` varchar(20) NOT NULL,
  `gioi_tinh` varchar(10) default NULL,
  `sdt` varchar(20) default NULL,
  `ngay_sinh` date default NULL,
  `ten_dang_nhap` varchar(60) NOT NULL,
  `mat_khau` char(32) NOT NULL,
  `vai_tro` varchar(20) NOT NULL,
  `trang_thai` varchar(20) NOT NULL default 'HoatDong',
  PRIMARY KEY  (`ma_nhan_vien`),
  UNIQUE KEY `uq_cccd` (`cccd`),
  UNIQUE KEY `uq_username` (`ten_dang_nhap`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `nhan_vien`
--

LOCK TABLES `nhan_vien` WRITE;
/*!40000 ALTER TABLE `nhan_vien` DISABLE KEYS */;
INSERT INTO `nhan_vien` VALUES (1,'Chu doanh nghiep','005000000001','Nu','0900000001','2026-10-14','admin','0192023a7bbd73250516f069df18b500','ChuDoanhNghiep','HoatDong'),(2,'Nhan vien mau','123456789101','Nam','0900000002','2000-02-22','nhanvien','e10adc3949ba59abbe56e057f20f883e','NhanVien','HoatDong');
/*!40000 ALTER TABLE `nhan_vien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phieu_de_nghi_dat_hang`
--

DROP TABLE IF EXISTS `phieu_de_nghi_dat_hang`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `phieu_de_nghi_dat_hang` (
  `ma_phieu_de_nghi_dat_hang` int(11) NOT NULL auto_increment,
  `ma_nhan_vien` int(11) NOT NULL,
  `ma_nha_cung_cap` int(11) NOT NULL,
  `ma_nguoi_duyet` int(11) default NULL,
  `ngay_lap` datetime NOT NULL,
  `ngay_duyet` datetime default NULL,
  `trang_thai` varchar(20) NOT NULL default 'ChoDuyet',
  `ly_do_tu_choi` text,
  `tong_chi_phi` decimal(14,2) NOT NULL default '0.00',
  PRIMARY KEY  (`ma_phieu_de_nghi_dat_hang`),
  KEY `ma_nhan_vien` (`ma_nhan_vien`),
  KEY `ma_nha_cung_cap` (`ma_nha_cung_cap`),
  KEY `ma_nguoi_duyet` (`ma_nguoi_duyet`),
  CONSTRAINT `phieu_de_nghi_dat_hang_ibfk_1` FOREIGN KEY (`ma_nhan_vien`) REFERENCES `nhan_vien` (`ma_nhan_vien`),
  CONSTRAINT `phieu_de_nghi_dat_hang_ibfk_2` FOREIGN KEY (`ma_nha_cung_cap`) REFERENCES `nha_cung_cap` (`ma_nha_cung_cap`),
  CONSTRAINT `phieu_de_nghi_dat_hang_ibfk_3` FOREIGN KEY (`ma_nguoi_duyet`) REFERENCES `nhan_vien` (`ma_nhan_vien`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `phieu_de_nghi_dat_hang`
--

LOCK TABLES `phieu_de_nghi_dat_hang` WRITE;
/*!40000 ALTER TABLE `phieu_de_nghi_dat_hang` DISABLE KEYS */;
INSERT INTO `phieu_de_nghi_dat_hang` VALUES (1,1,2,NULL,'2026-10-28 17:52:14',NULL,'ChoDuyet',NULL,'126000.00');
/*!40000 ALTER TABLE `phieu_de_nghi_dat_hang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phieu_nhap_kho`
--

DROP TABLE IF EXISTS `phieu_nhap_kho`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `phieu_nhap_kho` (
  `ma_phieu_nhap` int(11) NOT NULL auto_increment,
  `ma_phieu_de_nghi_dat_hang` int(11) NOT NULL,
  `ma_nhan_vien` int(11) NOT NULL,
  `ngay_nhap` datetime NOT NULL,
  PRIMARY KEY  (`ma_phieu_nhap`),
  KEY `ma_phieu_de_nghi_dat_hang` (`ma_phieu_de_nghi_dat_hang`),
  KEY `ma_nhan_vien` (`ma_nhan_vien`),
  CONSTRAINT `phieu_nhap_kho_ibfk_1` FOREIGN KEY (`ma_phieu_de_nghi_dat_hang`) REFERENCES `phieu_de_nghi_dat_hang` (`ma_phieu_de_nghi_dat_hang`),
  CONSTRAINT `phieu_nhap_kho_ibfk_2` FOREIGN KEY (`ma_nhan_vien`) REFERENCES `nhan_vien` (`ma_nhan_vien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `phieu_nhap_kho`
--

LOCK TABLES `phieu_nhap_kho` WRITE;
/*!40000 ALTER TABLE `phieu_nhap_kho` DISABLE KEYS */;
/*!40000 ALTER TABLE `phieu_nhap_kho` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phieu_yeu_cau_tra_hang`
--

DROP TABLE IF EXISTS `phieu_yeu_cau_tra_hang`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `phieu_yeu_cau_tra_hang` (
  `ma_yeu_cau` int(11) NOT NULL auto_increment,
  `ma_hoa_don` int(11) NOT NULL,
  `ma_nhan_vien_tao` int(11) NOT NULL,
  `ma_nguoi_xu_ly` int(11) default NULL,
  `ngay_tao` datetime NOT NULL,
  `ngay_xu_ly` datetime default NULL,
  `ly_do_hoan_tra` text NOT NULL,
  `ly_do_tu_choi` text,
  `trang_thai` varchar(25) NOT NULL default 'ChoDuyet',
  PRIMARY KEY  (`ma_yeu_cau`),
  KEY `ma_hoa_don` (`ma_hoa_don`),
  KEY `ma_nhan_vien_tao` (`ma_nhan_vien_tao`),
  KEY `ma_nguoi_xu_ly` (`ma_nguoi_xu_ly`),
  CONSTRAINT `phieu_yeu_cau_tra_hang_ibfk_1` FOREIGN KEY (`ma_hoa_don`) REFERENCES `don_hang` (`ma_hoa_don`),
  CONSTRAINT `phieu_yeu_cau_tra_hang_ibfk_2` FOREIGN KEY (`ma_nhan_vien_tao`) REFERENCES `nhan_vien` (`ma_nhan_vien`),
  CONSTRAINT `phieu_yeu_cau_tra_hang_ibfk_3` FOREIGN KEY (`ma_nguoi_xu_ly`) REFERENCES `nhan_vien` (`ma_nhan_vien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `phieu_yeu_cau_tra_hang`
--

LOCK TABLES `phieu_yeu_cau_tra_hang` WRITE;
/*!40000 ALTER TABLE `phieu_yeu_cau_tra_hang` DISABLE KEYS */;
/*!40000 ALTER TABLE `phieu_yeu_cau_tra_hang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `san_pham`
--

DROP TABLE IF EXISTS `san_pham`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `san_pham` (
  `ma_san_pham` int(11) NOT NULL auto_increment,
  `ten_san_pham` varchar(150) NOT NULL,
  `gia_ban` decimal(12,2) NOT NULL default '0.00',
  `trang_thai` varchar(20) NOT NULL default 'HoatDong',
  `muc_ton_toi_thieu` int(11) NOT NULL default '30',
  `muc_ton_muc_tieu` int(11) NOT NULL default '100',
  `so_luong_ton` int(11) NOT NULL default '0',
  `ma_nha_cung_cap` int(11) default NULL,
  PRIMARY KEY  (`ma_san_pham`),
  KEY `ix_sp_ncc` (`ma_nha_cung_cap`),
  CONSTRAINT `san_pham_ibfk_1` FOREIGN KEY (`ma_nha_cung_cap`) REFERENCES `nha_cung_cap` (`ma_nha_cung_cap`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `san_pham`
--

LOCK TABLES `san_pham` WRITE;
/*!40000 ALTER TABLE `san_pham` DISABLE KEYS */;
INSERT INTO `san_pham` VALUES (1,'Nuoc suoi 500ml','6000.00','HoatDong',30,100,21,1),(2,'Banh quy','15000.00','HoatDong',30,100,76,1);
/*!40000 ALTER TABLE `san_pham` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tai khoan`
--

DROP TABLE IF EXISTS `tai khoan`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `tai khoan` (
  `ma_tai_khoan` int(11) NOT NULL auto_increment,
  `ma_nhan_vien` int(11) NOT NULL,
  `ten_dang_nhap` varchar(60) NOT NULL,
  `mat_khau` char(32) NOT NULL,
  `vai_tro` varchar(20) NOT NULL,
  `trang_thai` varchar(20) NOT NULL default 'HoatDong',
  PRIMARY KEY  (`ma_tai_khoan`),
  UNIQUE KEY `uq_tai_khoan_nhan_vien` (`ma_nhan_vien`),
  UNIQUE KEY `uq_tai_khoan_ten_dang_nhap` (`ten_dang_nhap`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `tai khoan`
--

LOCK TABLES `tai khoan` WRITE;
/*!40000 ALTER TABLE `tai khoan` DISABLE KEYS */;
INSERT INTO `tai khoan` VALUES (1,1,'admin','0192023a7bbd73250516f069df18b500','ChuDoanhNghiep','HoatDong'),(2,2,'nhanvien','e10adc3949ba59abbe56e057f20f883e','NhanVien','HoatDong');
/*!40000 ALTER TABLE `tai khoan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thanh_toan`
--

DROP TABLE IF EXISTS `thanh_toan`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `thanh_toan` (
  `ma_thanh_toan` int(11) NOT NULL auto_increment,
  `ma_hoa_don` int(11) NOT NULL,
  `phuong_thuc_thanh_toan` varchar(30) NOT NULL,
  `so_tien` decimal(14,2) NOT NULL,
  `trang_thai` varchar(25) NOT NULL default 'ChoXacNhan',
  `thoi_gian` datetime NOT NULL,
  `ma_giao_dich_ngan_hang` varchar(100) default NULL,
  `ma_nhan_vien_xac_nhan` int(11) default NULL,
  PRIMARY KEY  (`ma_thanh_toan`),
  KEY `ma_hoa_don` (`ma_hoa_don`),
  KEY `ma_nhan_vien_xac_nhan` (`ma_nhan_vien_xac_nhan`),
  CONSTRAINT `thanh_toan_ibfk_1` FOREIGN KEY (`ma_hoa_don`) REFERENCES `don_hang` (`ma_hoa_don`),
  CONSTRAINT `thanh_toan_ibfk_2` FOREIGN KEY (`ma_nhan_vien_xac_nhan`) REFERENCES `nhan_vien` (`ma_nhan_vien`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `thanh_toan`
--

LOCK TABLES `thanh_toan` WRITE;
/*!40000 ALTER TABLE `thanh_toan` DISABLE KEYS */;
INSERT INTO `thanh_toan` VALUES (1,1,'TienMat','12000.00','ThanhCong','2026-10-20 09:05:00',NULL,2),(2,2,'TienMat','63000.00','ThanhCong','2026-10-28 17:32:12',NULL,2),(3,3,'ChuyenKhoan','21000.00','ThanhCong','2026-10-28 17:49:27',NULL,2);
/*!40000 ALTER TABLE `thanh_toan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `vw_can_nhap_hang`
--

DROP TABLE IF EXISTS `vw_can_nhap_hang`;
/*!50001 DROP VIEW IF EXISTS `vw_can_nhap_hang`*/;
/*!50001 CREATE TABLE `vw_can_nhap_hang` (
  `ma_san_pham` int(11),
  `ten_san_pham` varchar(150),
  `so_luong_ton` int(11),
  `muc_ton_toi_thieu` int(11),
  `muc_ton_muc_tieu` int(11),
  `so_luong_de_nghi` bigint(12)
) */;

--
-- Temporary table structure for view `vw_doanh_thu_ca`
--

DROP TABLE IF EXISTS `vw_doanh_thu_ca`;
/*!50001 DROP VIEW IF EXISTS `vw_doanh_thu_ca`*/;
/*!50001 CREATE TABLE `vw_doanh_thu_ca` (
  `ma_ca` int(11),
  `ma_nhan_vien` int(11),
  `ngay_gio_bat_dau` datetime,
  `so_don` bigint(21),
  `doanh_thu` decimal(36,2)
) */;

--
-- Temporary table structure for view `vw_doanh_thu_ngay`
--

DROP TABLE IF EXISTS `vw_doanh_thu_ngay`;
/*!50001 DROP VIEW IF EXISTS `vw_doanh_thu_ngay`*/;
/*!50001 CREATE TABLE `vw_doanh_thu_ngay` (
  `ngay` date,
  `doanh_thu` decimal(36,2)
) */;

--
-- Dumping routines for database 'eightmart'
--
DELIMITER ;;
DELIMITER ;

--
-- Final view structure for view `vw_can_nhap_hang`
--

/*!50001 DROP TABLE `vw_can_nhap_hang`*/;
/*!50001 DROP VIEW IF EXISTS `vw_can_nhap_hang`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_can_nhap_hang` AS select `san_pham`.`ma_san_pham` AS `ma_san_pham`,`san_pham`.`ten_san_pham` AS `ten_san_pham`,`san_pham`.`so_luong_ton` AS `so_luong_ton`,`san_pham`.`muc_ton_toi_thieu` AS `muc_ton_toi_thieu`,`san_pham`.`muc_ton_muc_tieu` AS `muc_ton_muc_tieu`,(`san_pham`.`muc_ton_muc_tieu` - `san_pham`.`so_luong_ton`) AS `so_luong_de_nghi` from `san_pham` where ((`san_pham`.`so_luong_ton` <= `san_pham`.`muc_ton_toi_thieu`) and (`san_pham`.`trang_thai` = _utf8'HoatDong')) */;

--
-- Final view structure for view `vw_doanh_thu_ca`
--

/*!50001 DROP TABLE `vw_doanh_thu_ca`*/;
/*!50001 DROP VIEW IF EXISTS `vw_doanh_thu_ca`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_doanh_thu_ca` AS select `c`.`ma_ca` AS `ma_ca`,`c`.`ma_nhan_vien` AS `ma_nhan_vien`,`c`.`ngay_gio_bat_dau` AS `ngay_gio_bat_dau`,count(distinct `t`.`ma_hoa_don`) AS `so_don`,sum(`t`.`so_tien`) AS `doanh_thu` from ((`ca_lam_viec` `c` join `don_hang` `d` on((`d`.`ma_ca` = `c`.`ma_ca`))) join `thanh_toan` `t` on(((`t`.`ma_hoa_don` = `d`.`ma_hoa_don`) and (`t`.`trang_thai` = _utf8'ThanhCong')))) group by `c`.`ma_ca`,`c`.`ma_nhan_vien`,`c`.`ngay_gio_bat_dau` */;

--
-- Final view structure for view `vw_doanh_thu_ngay`
--

/*!50001 DROP TABLE `vw_doanh_thu_ngay`*/;
/*!50001 DROP VIEW IF EXISTS `vw_doanh_thu_ngay`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_doanh_thu_ngay` AS select cast(`t`.`thoi_gian` as date) AS `ngay`,sum(`t`.`so_tien`) AS `doanh_thu` from `thanh_toan` `t` where (`t`.`trang_thai` = _utf8'ThanhCong') group by cast(`t`.`thoi_gian` as date) */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-28 11:36:56
