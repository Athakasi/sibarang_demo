<?php
// ===================================================
// pages/print_keluar.php
// Cetak Laporan Riwayat Barang Keluar
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

// Query data barang keluar
$keluar_sql = "
    SELECT 
        bk.id_keluar,
        bk.nama_barang,
        bk.jumlah_keluar,
        bk.tanggal_keluar,
        bk.keterangan
    FROM barang_keluar bk
    WHERE DATE(bk.tanggal_keluar) BETWEEN '$tanggal_awal_clean' AND '$tanggal_akhir_clean'
    ORDER BY bk.tanggal_keluar DESC, bk.id_keluar DESC
";

$keluar_data = fetchAll($conn, $keluar_sql);
$total_keluar = 0;
foreach ($keluar_data as $row) {
    $total_keluar += (int)$row['jumlah_keluar'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Barang Keluar - <?= h(APP_NAME); ?></title>
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
            <h1>LAPORAN TRANSAKSI BARANG KELUAR</h1>
            <p><?= h(APP_NAME); ?> - SISTEM INFORMASI INVENTARIS</p>
            <p>Rekapitulasi Pengeluaran & Distribusi Barang</p>
        </div>

        <div class="period">
            Periode: <?= date('d/m/Y', strtotime($tanggal_awal)); ?> s/d <?= date('d/m/Y', strtotime($tanggal_akhir)); ?>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-box">
                <strong>Total Transaksi</strong>
                <div class="value"><?= count($keluar_data); ?> Transaksi</div>
            </div>
            <div class="info-box">
                <strong>Total Barang Keluar</strong>
                <div class="value">-<?= number_format($total_keluar); ?> Pcs</div>
            </div>
        </div>

        <!-- Tabel Laporan -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No</th>
                    <th style="width: 30%;">Nama Barang</th>
                    <th style="width: 15%; text-align: right;">Jumlah Keluar</th>
                    <th style="width: 18%; text-align: center;">Tanggal Keluar</th>
                    <th>Keterangan / Keperluan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($keluar_data) > 0): ?>
                    <?php $no = 1; foreach ($keluar_data as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><strong><?= h($row['nama_barang']); ?></strong></td>
                            <td class="text-right">-<?= number_format($row['jumlah_keluar']); ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal_keluar'])); ?></td>
                            <td><?= h($row['keterangan'] ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">Tidak ada data transaksi barang keluar pada periode ini</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Footer Tanda Tangan -->
        <div class="footer">
            <div class="footer-item" style="text-align: left;">
                <p>Mengetahui,<br>Kepala Bagian Logistik</p><br><br>
                <strong>( ________________________ )</strong>
            </div>
            <div class="footer-item" style="text-align: right;">
                <p><?= $tgl->format('d') . ' ' . $bulan[(int)$tgl->format('m')] . ' ' . $tgl->format('Y'); ?><br>Petugas Input Data,</p><br><br>
                <strong>( <?= h($_SESSION['admin_name'] ?? 'Admin'); ?> )</strong>
            </div>
        </div>
    </div>
</body>
</html>