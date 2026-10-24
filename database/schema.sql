-- ===================================================
-- SI-BARANG: Database Schema DDL
-- ===================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `barang_keluar`;
DROP TABLE IF EXISTS `barang_masuk`;
DROP TABLE IF EXISTS `barang`;
DROP TABLE IF EXISTS `kategori_barang`;
DROP TABLE IF EXISTS `admin`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------
-- 1. Table: admin (Akun Pengguna & Level Otorisasi)
-- ---------------------------------------------------
CREATE TABLE `admin` (
  `id_admin` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_admin` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin', 'admin', 'staff') NOT NULL DEFAULT 'staff',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `uk_admin_nama` (`nama_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 2. Table: kategori_barang (Master Kategori)
-- ---------------------------------------------------
CREATE TABLE `kategori_barang` (
  `id_kategori` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `keterangan` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kategori`),
  UNIQUE KEY `uk_kategori_nama` (`nama_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 3. Table: barang (Master Data Barang & Saldo Stok)
-- ---------------------------------------------------
CREATE TABLE `barang` (
  `id_barang` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_barang` VARCHAR(150) NOT NULL,
  `id_kategori` INT UNSIGNED NOT NULL,
  `stok` INT NOT NULL DEFAULT 0,
  `tanggal_ditambah` DATE NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_barang`),
  UNIQUE KEY `uk_barang_nama` (`nama_barang`),
  KEY `idx_barang_kategori` (`id_kategori`),
  CONSTRAINT `fk_barang_kategori` FOREIGN KEY (`id_kategori`) 
    REFERENCES `kategori_barang` (`id_kategori`) 
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 4. Table: barang_masuk (Riwayat Transaksi Masuk)
-- ---------------------------------------------------
CREATE TABLE `barang_masuk` (
  `id_masuk` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tanggal_masuk` DATE NOT NULL,
  `nama_barang` VARCHAR(150) NOT NULL,
  `jumlah_masuk` INT UNSIGNED NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_masuk`),
  KEY `idx_masuk_tanggal` (`tanggal_masuk`),
  KEY `idx_masuk_barang` (`nama_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 5. Table: barang_keluar (Riwayat Transaksi Keluar)
-- ---------------------------------------------------
CREATE TABLE `barang_keluar` (
  `id_keluar` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tanggal_keluar` DATE NOT NULL,
  `nama_barang` VARCHAR(150) NOT NULL,
  `jumlah_keluar` INT UNSIGNED NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_keluar`),
  KEY `idx_keluar_tanggal` (`tanggal_keluar`),
  KEY `idx_keluar_barang` (`nama_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
