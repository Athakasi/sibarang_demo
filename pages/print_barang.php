<?php
// ===================================================
// pages/print_barang.php
// Cetak Dokumen Master Data Barang
// ===================================================

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$page_title = 'Cetak Master Barang';

$tgl = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$bulan = array(
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
);

$barang_sql = "
    SELECT b.*, kb.nama_kategori 
    FROM barang b
    LEFT JOIN kategori_barang kb ON kb.id_kategori = b.id_kategori
    ORDER BY b.nama_barang ASC
";
$barang_data = fetchAll($conn, $barang_sql);

$total_stok = 0;
foreach ($barang_data as $b) {
    $total_stok += (int)$b['stok'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Master Barang - <?= h(APP_NAME); ?></title>
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
            <h1>LAPORAN MASTER DATA BARANG</h1>
            <p><?= h(APP_NAME); ?> - SISTEM INFORMASI INVENTARIS</p>
            <p>Dokumen Resmi Rekapitulasi Inventaris Barang</p>
        </div>

        <div class="period">
            Dicetak pada: <?= $tgl->format('d') . ' ' . $bulan[(int)$tgl->format('m')] . ' ' . $tgl->format('Y') . ' - Pukul ' . $tgl->format('H:i'); ?> WIB
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-box">
                <strong>Total Jenis Barang</strong>
                <div class="value"><?= count($barang_data); ?> Item</div>
            </div>
            <div class="info-box">
                <strong>Total Akumulasi Stok</strong>
                <div class="value"><?= number_format($total_stok); ?> Pcs</div>
            </div>
        </div>

        <!-- Tabel Barang -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th style="width: 15%; text-align: right;">Stok</th>
                    <th style="width: 18%; text-align: center;">Tanggal Ditambah</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($barang_data) > 0): ?>
                    <?php $no = 1; foreach ($barang_data as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><strong><?= h($row['nama_barang']); ?></strong></td>
                            <td><?= h($row['nama_kategori'] ?? 'Umum'); ?></td>
                            <td class="text-right"><?= number_format($row['stok']); ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal_ditambah'])); ?></td>
                            <td><?= h($row['keterangan'] ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-data">Tidak ada data master barang</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Footer Tanda Tangan -->
        <div class="footer">
            <div class="footer-item" style="text-align: left;">
                <p>Mengetahui,<br>Penanggung Jawab Inventaris</p><br><br>
                <strong>( ________________________ )</strong>
            </div>
            <div class="footer-item" style="text-align: right;">
                <p><?= $tgl->format('d') . ' ' . $bulan[(int)$tgl->format('m')] . ' ' . $tgl->format('Y'); ?><br>Petugas / Pengelola,</p><br><br>
                <strong>( <?= h($_SESSION['admin_name'] ?? 'Admin'); ?> )</strong>
            </div>
        </div>
    </div>
</body>
</html>
