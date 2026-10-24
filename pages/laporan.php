<?php
// ===================================================
// pages/laporan.php
// Halaman Rekapitulasi Laporan Arus Stok Barang
// ===================================================

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

// Filter tanggal
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');

$tanggal_awal_clean = sanitize($conn, $tanggal_awal);
$tanggal_akhir_clean = sanitize($conn, $tanggal_akhir);

// Query semua barang dengan data masuk/keluar pada periode
$laporan_sql = "
    SELECT 
        b.id_barang,
        b.nama_barang,
        b.stok AS stok_saat_ini,
        COALESCE(SUM(bm.jumlah_masuk), 0) AS total_masuk,
        COALESCE(SUM(bk.jumlah_keluar), 0) AS total_keluar
    FROM barang b
    LEFT JOIN barang_masuk bm ON bm.nama_barang = b.nama_barang AND DATE(bm.tanggal_masuk) BETWEEN '$tanggal_awal_clean' AND '$tanggal_akhir_clean'
    LEFT JOIN barang_keluar bk ON bk.nama_barang = b.nama_barang AND DATE(bk.tanggal_keluar) BETWEEN '$tanggal_awal_clean' AND '$tanggal_akhir_clean'
    GROUP BY b.id_barang, b.nama_barang, b.stok
    ORDER BY b.nama_barang ASC
";

$data_laporan = fetchAll($conn, $laporan_sql);

// Perhitungan stok awal & akhir
$total_masuk_keseluruhan = 0;
$total_keluar_keseluruhan = 0;

foreach ($data_laporan as &$row) {
    $row['stok_awal'] = $row['stok_saat_ini'] - $row['total_masuk'] + $row['total_keluar'];
    $row['stok_akhir'] = $row['stok_saat_ini'];
    $total_masuk_keseluruhan += (int)$row['total_masuk'];
    $total_keluar_keseluruhan += (int)$row['total_keluar'];
}
unset($row);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Stok - <?= h(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/table_and_form.css'); ?>">
    <style>
        .stats-container {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            flex: 1;
            padding: 18px 24px;
            background: var(--color-surface);
            border: 1px solid var(--color-card-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            text-align: center;
        }
        .stat-card h3 {
            font-size: 14px;
            color: var(--color-text-secondary);
            margin-bottom: 8px;
        }
        .summary-number {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="content-wrapper">
                <h1 class="page-title">Laporan Rekapitulasi Arus Stok</h1>

                <?php include __DIR__ . '/../includes/flash.php'; ?>

                <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px;">
                    <button class="btn btn-secondary btn-print" onclick="cetakLaporan()">🖨️ Cetak Dokumen Laporan</button>
                </div>

                <!-- Filter Form -->
                <div class="filter-section">
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <label for="tanggal_awal">Tanggal Awal</label>
                            <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?= h($tanggal_awal); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="tanggal_akhir">Tanggal Akhir</label>
                            <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?= h($tanggal_akhir); ?>" required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">🔍 Filter Periode</button>
                        </div>
                    </form>
                </div>

                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Total Barang Masuk</h3>
                        <p class="summary-number" style="color: #27ae60;">+<?= number_format($total_masuk_keseluruhan); ?></p>
                    </div>

                    <div class="stat-card">
                        <h3>Total Barang Keluar</h3>
                        <p class="summary-number" style="color: #e74c3c;">-<?= number_format($total_keluar_keseluruhan); ?></p>
                    </div>

                    <div class="stat-card">
                        <h3>Periode Laporan</h3>
                        <p class="summary-number" style="font-size: 16px; color: #764ba2; margin-top: 5px;">
                            <?= date('d/m/Y', strtotime($tanggal_awal)); ?> s/d <?= date('d/m/Y', strtotime($tanggal_akhir)); ?>
                        </p>
                    </div>
                </div>

                <!-- Main Table -->
                <div class="table-container">
                    <table class="data-table" id="reportTable">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th>Nama Barang</th>
                                <th style="text-align: center;">Stok Awal Periode</th>
                                <th style="text-align: center;">Jumlah Masuk</th>
                                <th style="text-align: center;">Jumlah Keluar</th>
                                <th style="text-align: center;">Stok Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($data_laporan) > 0): ?>
                                <?php $no = 1; foreach ($data_laporan as $row): ?>
                                    <tr>
                                        <td style="text-align: center;"><?= $no++; ?></td>
                                        <td><strong><?= h($row['nama_barang']); ?></strong></td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-1"><?= number_format($row['stok_awal']); ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-green">+<?= number_format($row['total_masuk']); ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-red">-<?= number_format($row['total_keluar']); ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-2"><strong><?= number_format($row['stok_akhir']); ?></strong></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 25px; color: #888;">
                                        Tidak ada data barang pada periode ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cetakLaporan() {
            const tanggalAwal = document.getElementById('tanggal_awal').value;
            const tanggalAkhir = document.getElementById('tanggal_akhir').value;
            const url = `<?= base_url('pages/print_laporan.php'); ?>?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`;
            window.open(url, '_blank');
        }
    </script>
</body>
</html>