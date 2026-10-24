<?php
// ===================================================
// index.php
// Dashboard Utama SI-BARANG
// ===================================================

require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';

// Ambil data statistik untuk dashboard
$total_barang_res = fetchOne($conn, "SELECT COUNT(*) as total FROM barang");
$total_barang = $total_barang_res ? (int)$total_barang_res['total'] : 0;

$masuk_month_res = fetchOne($conn, "SELECT SUM(jumlah_masuk) as total FROM barang_masuk WHERE MONTH(tanggal_masuk) = MONTH(CURDATE()) AND YEAR(tanggal_masuk) = YEAR(CURDATE())");
$masuk_month = $masuk_month_res && $masuk_month_res['total'] !== null ? (int)$masuk_month_res['total'] : 0;

$keluar_month_res = fetchOne($conn, "SELECT SUM(jumlah_keluar) as total FROM barang_keluar WHERE MONTH(tanggal_keluar) = MONTH(CURDATE()) AND YEAR(tanggal_keluar) = YEAR(CURDATE())");
$keluar_month = $keluar_month_res && $keluar_month_res['total'] !== null ? (int)$keluar_month_res['total'] : 0;

// Ambil 5 data barang terbaru
$barang_terbaru_sql = "
    SELECT b.*, kb.nama_kategori 
    FROM barang b
    LEFT JOIN kategori_barang kb ON kb.id_kategori = b.id_kategori
    ORDER BY b.tanggal_ditambah DESC 
    LIMIT 5
";
$data_barang = fetchAll($conn, $barang_terbaru_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= h(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/table_and_form.css'); ?>">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-wrapper">
                <h1 class="page-title">Dashboard Overview</h1>

                <!-- Flash Messages -->
                <?php include __DIR__ . '/includes/flash.php'; ?>

                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card stat-barang">
                        <div class="stat-icon">📦</div>
                        <div class="stat-info">
                            <h3>Total Barang</h3>
                            <p class="stat-number"><?= number_format($total_barang); ?></p>
                        </div>
                    </div>
                                    
                    <div class="stat-card stat-masuk">
                        <div class="stat-icon">📥</div>
                        <div class="stat-info">
                            <h3>Masuk Bulan Ini</h3>
                            <p class="stat-number"><?= number_format($masuk_month); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-keluar">
                        <div class="stat-icon">📤</div>
                        <div class="stat-info">
                            <h3>Keluar Bulan Ini</h3>
                            <p class="stat-number"><?= number_format($keluar_month); ?></p>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 35px; margin-bottom: 15px; color: #fff;">
                    <h2 style="margin: 0; font-size: 20px;">5 Barang Ditambahkan Terbaru</h2>
                    <a href="<?= base_url('pages/daftar_barang.php'); ?>" class="btn btn-primary">Lihat Semua Barang →</a>
                </div>

                <!-- Recent Items Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th style="text-align: center;">Stok</th>
                                <th style="text-align: center;">Tanggal Ditambah</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($data_barang) > 0): ?>
                                <?php $no = 1; foreach ($data_barang as $barang): ?>
                                <tr>
                                    <td style="text-align: center;"><?= $no++; ?></td>
                                    <td><strong><?= h($barang['nama_barang']); ?></strong></td>
                                    <td><?= h($barang['nama_kategori'] ?? 'Umum'); ?></td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-1"><?= number_format($barang['stok']); ?></span>
                                    </td>
                                    <td style="text-align: center;"><?= date('d/m/Y', strtotime($barang['tanggal_ditambah'])); ?></td>
                                    <td><?= h($barang['keterangan'] ?: '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 25px; color: #888;">
                                        Belum ada data barang tersimpan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?= asset_url('js/main.js'); ?>"></script>
</body>
</html>
