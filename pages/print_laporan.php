<?php
// ===================================================
// pages/print_laporan.php
// Cetak Dokumen Rekapitulasi Laporan Stok Barang
// ===================================================

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$tgl = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$bulan = array(
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
);

// Filter tanggal
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');

$tanggal_awal_clean = sanitize($conn, $tanggal_awal);
$tanggal_akhir_clean = sanitize($conn, $tanggal_akhir);

// Ambil data semua barang dengan data masuk/keluar pada periode
$laporan_sql = "
    SELECT 
        b.nama_barang,
        b.stok AS stok_akhir,
        COALESCE(SUM(bm.jumlah_masuk), 0) AS jumlah_masuk,
        COALESCE(SUM(bk.jumlah_keluar), 0) AS jumlah_keluar
    FROM barang b
    LEFT JOIN barang_masuk bm ON bm.nama_barang = b.nama_barang AND DATE(bm.tanggal_masuk) BETWEEN '$tanggal_awal_clean' AND '$tanggal_akhir_clean'
    LEFT JOIN barang_keluar bk ON bk.nama_barang = b.nama_barang AND DATE(bk.tanggal_keluar) BETWEEN '$tanggal_awal_clean' AND '$tanggal_akhir_clean'
    GROUP BY b.id_barang, b.nama_barang, b.stok
    ORDER BY b.nama_barang ASC
";

$laporan_data = fetchAll($conn, $laporan_sql);

// Hitung stok awal untuk setiap item
foreach ($laporan_data as &$r) {
    $r['stok_awal'] = $r['stok_akhir'] - $r['jumlah_masuk'] + $r['jumlah_keluar'];
}
unset($r);

$total_masuk = 0;
$total_keluar = 0;
foreach ($laporan_data as $r) {
    $total_masuk += (int)$r['jumlah_masuk'];
    $total_keluar += (int)$r['jumlah_keluar'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Stok - <?= h(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/print.css'); ?>">
</head>
<body>
    <div class="no-print" style="margin: 20px auto; max-width: 900px;">
        <div class="button-group" style="display: flex; gap: 10px;">
            <button class="btn btn-print" onclick="window.print()">🖨️ Cetak Dokumen</button>
            <button class="btn" style="background: #6c757d; color: white;" onclick="window.close()">Tutup</button>
        </div>
    </div>

    <div class="print-container">
        <!-- Header -->
        <div class="header">
            <h1>LAPORAN REKAPITULASI ARUS STOK BARANG</h1>
            <p><?= h(APP_NAME); ?> - SISTEM INFORMASI INVENTARIS</p>
            <p>Rekapitulasi Posisi Stok Awal, Masuk, Keluar, dan Stok Akhir</p>
        </div>

        <div class="period">
            Periode: <?= date('d/m/Y', strtotime($tanggal_awal)); ?> s/d <?= date('d/m/Y', strtotime($tanggal_akhir)); ?>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-box">
                <strong>Total Jumlah Masuk</strong>
                <div class="value">+<?= number_format($total_masuk); ?> Pcs</div>
            </div>
            <div class="info-box">
                <strong>Total Jumlah Keluar</strong>
                <div class="value">-<?= number_format($total_keluar); ?> Pcs</div>
            </div>
            <div class="info-box">
                <strong>Total Jenis Barang</strong>
                <div class="value"><?= count($laporan_data); ?> Item</div>
            </div>
        </div>

        <!-- Tabel Laporan -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No</th>
                    <th style="width: 35%;">Nama Barang</th>
                    <th style="width: 15%; text-align: right;">Stok Awal</th>
                    <th style="width: 15%; text-align: right;">Masuk</th>
                    <th style="width: 15%; text-align: right;">Keluar</th>
                    <th style="width: 15%; text-align: right;">Stok Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($laporan_data) > 0): ?>
                    <?php $no = 1; foreach ($laporan_data as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><strong><?= h($row['nama_barang']); ?></strong></td>
                            <td class="text-right"><?= number_format($row['stok_awal']); ?></td>
                            <td class="text-right">+<?= number_format($row['jumlah_masuk']); ?></td>
                            <td class="text-right">-<?= number_format($row['jumlah_keluar']); ?></td>
                            <td class="text-right"><strong><?= number_format($row['stok_akhir']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-data">Tidak ada data laporan untuk periode ini</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Footer Tanda Tangan -->
        <div class="footer">
            <div class="footer-item" style="text-align: left;">
                <p>Mengetahui,<br>Kepala Bagian Inventaris</p><br><br>
                <strong>( ________________________ )</strong>
            </div>
            <div class="footer-item" style="text-align: right;">
                <p><?= $tgl->format('d') . ' ' . $bulan[(int)$tgl->format('m')] . ' ' . $tgl->format('Y'); ?><br>Petugas Rekapitulasi Data,</p><br><br>
                <strong>( <?= h($_SESSION['admin_name'] ?? 'Admin'); ?> )</strong>
            </div>
        </div>
    </div>
</body>
</html>
