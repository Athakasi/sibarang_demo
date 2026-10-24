-- ===================================================
-- SI-BARANG: Database Seeders Data
-- ===================================================

-- ---------------------------------------------------
-- 1. Seed Akun Pengguna Demo
-- Passwords:
--   superadmin -> superadmin123
--   admin      -> admin123
--   staff      -> staff123
-- ---------------------------------------------------
INSERT INTO `admin` (`id_admin`, `nama_admin`, `password`, `role`) VALUES
(1, 'superadmin', '$2y$12$njovzyHs37CkzWEFPKmOoOk7ikEzTb/1xfF7voZUTtEbQmXp2de6W', 'superadmin'),
(2, 'admin',      '$2y$12$LsTFXJoBhrx00iCofsuhheAAyHBoo4xC5TEEWsJ9obr/hBNjwntxu', 'admin'),
(3, 'staff',      '$2y$12$04LkOEqHdSXG.fcrKWxfd.3b.0KOSkDL20LAsHEQOZn9NfEade.3u', 'staff');

-- ---------------------------------------------------
-- 2. Seed Master Kategori Barang
-- ---------------------------------------------------
INSERT INTO `kategori_barang` (`id_kategori`, `nama_kategori`, `keterangan`) VALUES
(1, 'Umum', 'Kategori default untuk barang umum'),
(2, 'Alat Tulis Kantor (ATK)', 'Kertas, pena, spidol, binder, dan perlengkapan tulis'),
(3, 'Peralatan Elektronik', 'Komputer, proyektor, router, dan periferal digital'),
(4, 'Alat Laboratorium', 'Peralatan praktikum IPA, biologi, kimia, dan fisika'),
(5, 'Perlengkapan Kelas', 'Papan tulis, penghapus, meja kursi, dan stopkontak'),
(6, 'Kebersihan & Sanitasi', 'Cairan pembersih, sabun cuci tangan, dan desinfektan');

-- ---------------------------------------------------
-- 3. Seed Master Data Barang
-- ---------------------------------------------------
INSERT INTO `barang` (`id_barang`, `nama_barang`, `id_kategori`, `stok`, `tanggal_ditambah`, `keterangan`) VALUES
(1,  'Spidol Whiteboard Snowman Hitam',      2, 120, '2026-06-01', 'Kemasan dus isi 12 pcs, tinta pekat mudah dihapus'),
(2,  'Kertas HVS A4 80gr PaperOne',          2, 45,  '2026-06-01', 'Karton isi 5 rim / 2500 lembar'),
(3,  'Pulpen Standard AE7 0.5mm Hitam',      2, 180, '2026-06-05', 'Ballpoint tip jarum untuk administrasi umum'),
(4,  'Proyektor Epson EB-E01 XGA',           3, 8,   '2026-06-10', 'Proyektor 3300 lumens untuk ruang kelas & meeting'),
(5,  'Kabel HDMI 5 Meter Braided',           3, 15,  '2026-06-10', 'Kabel display resolusi 4K untuk proyektor kelas'),
(6,  'Router Wi-Fi TP-Link Archer AX12',     3, 6,   '2026-06-15', 'Dual band Wi-Fi 6 untuk laboratorium komputer'),
(7,  'Microphone Wireless Shure BLX24',      3, 4,   '2026-06-20', 'Set mic handheld untuk aula dan ruang serbaguna'),
(8,  'Mikroskop Biologi Monokuler Olympus',  4, 12,  '2026-06-22', 'Mikroskop optik perbesaran 1000x untuk praktikum'),
(9,  'Tabung Reaksi Kaca Pyrex 15x150mm',    4, 75,  '2026-06-25', 'Tabung reaksi borosilikat tahan panas'),
(10, 'Gelas Ukur 100ml Herma Glass',         4, 25,  '2026-06-25', 'Gelas ukur kaca dengan skala presisi tinggi'),
(11, 'Penghapus Whiteboard Magnetik',        5, 28,  '2026-07-01', 'Penghapus papan tulis magnetik ergonomis'),
(12, 'Papan Whiteboard Gantung 120x240cm',   5, 10,  '2026-07-02', 'Papan tulis formika putih berkualitas tinggi'),
(13, 'Stopkontak Kabel 5 Lubang Uticon 3M',  5, 16,  '2026-07-05', 'Terminal kuningan anti panas dengan saklar'),
(14, 'Cairan Pembersih Lantai Wipol 750ml',  6, 22,  '2026-07-08', 'Desinfektan aroma pinus untuk sanitasi ruangan'),
(15, 'Sabun Cuci Tangan Dettol 250ml Pump',  6, 30,  '2026-07-10', 'Handwash antibakteri untuk wastafel sekolah');

-- ---------------------------------------------------
-- 4. Seed Transaksi Barang Masuk
-- ---------------------------------------------------
INSERT INTO `barang_masuk` (`id_masuk`, `tanggal_masuk`, `nama_barang`, `jumlah_masuk`, `keterangan`) VALUES
(1,  '2026-06-02', 'Spidol Whiteboard Snowman Hitam',     150, 'Pengadaan ATK semester ganjil - CV Berkah Stationery'),
(2,  '2026-06-02', 'Kertas HVS A4 80gr PaperOne',         60,  'Pengadaan kertas kantor - Toko Prima Paper'),
(3,  '2026-06-06', 'Pulpen Standard AE7 0.5mm Hitam',     240, 'Pengadaan logistik kantor TU'),
(4,  '2026-06-11', 'Proyektor Epson EB-E01 XGA',          10,  'Pengadaan sarana IT dari anggaran BOS'),
(5,  '2026-06-11', 'Kabel HDMI 5 Meter Braided',          20,  'Aksesoris proyektor kelas - Toko Multi Komputer'),
(6,  '2026-06-16', 'Router Wi-Fi TP-Link Archer AX12',    8,   'Upgrade jaringan lab komputer'),
(7,  '2026-06-21', 'Microphone Wireless Shure BLX24',     5,   'Pengadaan sound system aula utama'),
(8,  '2026-06-23', 'Mikroskop Biologi Monokuler Olympus', 15,  'Pengadaan laboratorium biologi baru'),
(9,  '2026-06-26', 'Tabung Reaksi Kaca Pyrex 15x150mm',   100, 'Restock alat praktikum kimia'),
(10, '2026-06-26', 'Gelas Ukur 100ml Herma Glass',        30,  'Restock alat gelas laboratorium'),
(11, '2026-07-02', 'Penghapus Whiteboard Magnetik',       35,  'Perlengkapan kelas baru tahun ajaran baru'),
(12, '2026-07-03', 'Papan Whiteboard Gantung 120x240cm',  12,  'Pengadaan papan tulis gedung B'),
(13, '2026-07-06', 'Stopkontak Kabel 5 Lubang Uticon 3M', 20,  'Distribusi instalasi listrik ruang guru'),
(14, '2026-07-09', 'Cairan Pembersih Lantai Wipol 750ml', 30,  'Logistik kebersihan bulanan'),
(15, '2026-07-11', 'Sabun Cuci Tangan Dettol 250ml Pump', 40,  'Sanitasi wastafel koridor dan laboratorium');

-- ---------------------------------------------------
-- 5. Seed Transaksi Barang Keluar
-- ---------------------------------------------------
INSERT INTO `barang_keluar` (`id_keluar`, `tanggal_keluar`, `nama_barang`, `jumlah_keluar`, `keterangan`) VALUES
(1,  '2026-06-15', 'Spidol Whiteboard Snowman Hitam',     30,  'Distribusi rutin untuk ruang guru dan kelas XII'),
(2,  '2026-06-18', 'Kertas HVS A4 80gr PaperOne',         15,  'Pencetakan soal ujian akhir semester'),
(3,  '2026-06-20', 'Pulpen Standard AE7 0.5mm Hitam',     60,  'Distribusi perlengkapan pengawas ujian'),
(4,  '2026-06-25', 'Proyektor Epson EB-E01 XGA',          2,   'Pemasangan di Ruang Multimedia dan Lab Bahasa'),
(5,  '2026-06-25', 'Kabel HDMI 5 Meter Braided',          5,   'Instalasi display proyektor ruang multimedia'),
(6,  '2026-06-28', 'Router Wi-Fi TP-Link Archer AX12',    2,   'Pemasangan access point di Ruang Guru dan Perpustakaan'),
(7,  '2026-07-05', 'Microphone Wireless Shure BLX24',     1,   'Penempatan unit audio di Ruang Rapat Pimpinan'),
(8,  '2026-07-10', 'Mikroskop Biologi Monokuler Olympus', 3,   'Penempatan unit mikroskop di meja praktikum Lab IPA'),
(9,  '2026-07-12', 'Tabung Reaksi Kaca Pyrex 15x150mm',   25,  'Pemakaian praktikum titrasi larutan kelas XI'),
(10, '2026-07-15', 'Gelas Ukur 100ml Herma Glass',        5,   'Pemakaian praktikum kimia larutan asam-basa'),
(11, '2026-07-20', 'Penghapus Whiteboard Magnetik',       7,   'Penggantian penghapus ruang kelas X-A s/d X-G'),
(12, '2026-07-22', 'Papan Whiteboard Gantung 120x240cm',  2,   'Pemasangan papan tulis baru di Ruang Osis dan BK'),
(13, '2026-07-25', 'Stopkontak Kabel 5 Lubang Uticon 3M', 4,   'Instalasi kelistrikan ruang podcast & server mini'),
(14, '2026-07-28', 'Cairan Pembersih Lantai Wipol 750ml', 8,   'Distribusi ke petugas cleaning service lantai 1 & 2'),
(15, '2026-08-01', 'Sabun Cuci Tangan Dettol 250ml Pump', 10,  'Refill dispenser sabun wastafel gedung utama');
