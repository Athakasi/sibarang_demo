<?php
// ===================================================
// pages/barang_masuk.php
// Halaman Pencatatan Transaksi Barang Masuk
// ===================================================

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

// Ambil semua barang untuk dropdown
$barang_list = fetchAll($conn, "SELECT * FROM barang ORDER BY nama_barang ASC");

// Proses input barang masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    validate_csrf();
    
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '');
    $nama_barang = sanitize($conn, trim($_POST['nama_barang'] ?? ''));
    $jumlah_masuk = (int)($_POST['jumlah_masuk'] ?? 0);
    $keterangan = sanitize($conn, trim($_POST['keterangan'] ?? ''));

    // Validasi input
    if (empty($tanggal_masuk)) {
        set_flash('error', '⚠️ Tanggal masuk wajib diisi!');
    } elseif (empty($nama_barang)) {
        set_flash('error', '⚠️ Nama barang harus dipilih!');
    } elseif ($jumlah_masuk <= 0) {
        set_flash('error', '⚠️ Jumlah masuk harus lebih dari 0!');
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_masuk)) {
        set_flash('error', '⚠️ Format tanggal tidak valid!');
    } else {
        $barang = fetchOne($conn, "SELECT * FROM barang WHERE nama_barang = '$nama_barang'");

        if (!$barang) {
            set_flash('error', '⚠️ Barang tidak ditemukan di data master!');
        } else {
            $nama_barang_db = sanitize($conn, $barang['nama_barang']);

            $conn->begin_transaction();
            try {
                $insert_stmt = $conn->prepare("INSERT INTO barang_masuk (tanggal_masuk, nama_barang, jumlah_masuk, keterangan) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param('ssis', $tanggal_masuk, $nama_barang_db, $jumlah_masuk, $keterangan);
                $insert_stmt->execute();
                $insert_stmt->close();

                $update_stmt = $conn->prepare("UPDATE barang SET stok = stok + ? WHERE nama_barang = ?");
                $update_stmt->bind_param('is', $jumlah_masuk, $nama_barang_db);
                $update_stmt->execute();
                $update_stmt->close();

                $conn->commit();
                $stok_baru = $barang['stok'] + $jumlah_masuk;
                set_flash('success', "✓ Barang masuk berhasil dicatat! Stok barang '{$barang['nama_barang']}' kini menjadi: $stok_baru");
            } catch (Throwable $e) {
                $conn->rollback();
                set_flash('error', '⚠️ Gagal mencatat barang masuk: ' . $e->getMessage());
            }
        }
    }
    redirect('pages/barang_masuk.php');
}

// Filter tanggal
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');

$tanggal_awal_clean = sanitize($conn, $tanggal_awal);
$tanggal_akhir_clean = sanitize($conn, $tanggal_akhir);

$riwayat_masuk = fetchAll($conn, "
    SELECT * FROM barang_masuk 
    WHERE DATE(tanggal_masuk) BETWEEN '$tanggal_awal_clean' AND '$tanggal_akhir_clean'
    ORDER BY tanggal_masuk DESC, id_masuk DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Masuk - <?= h(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/table_and_form.css'); ?>">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="content-wrapper">
                <h1 class="page-title">Pencatatan Barang Masuk</h1>

                <?php include __DIR__ . '/../includes/flash.php'; ?>

                <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="openModalAdd()">+ Tambah Barang Masuk</button>
                        <button class="btn btn-secondary btn-print" onclick="cetakLaporan()">🖨️ Cetak Riwayat</button>
                    </div>
                </div>

                <!-- Filter Periode -->
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

                <!-- Tabel Riwayat -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th>Nama Barang</th>
                                <th style="text-align: center;">Jumlah Masuk</th>
                                <th style="text-align: center;">Tanggal Masuk</th>
                                <th>Keterangan</th>
                                <th style="text-align: center; width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($riwayat_masuk) > 0): ?>
                                <?php $no = 1; foreach ($riwayat_masuk as $row): ?>
                                    <tr>
                                        <td style="text-align: center;"><?= $no++; ?></td>
                                        <td><strong><?= h($row['nama_barang']); ?></strong></td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-green">+<?= number_format($row['jumlah_masuk']); ?></span>
                                        </td>
                                        <td style="text-align: center;"><?= date('d/m/Y', strtotime($row['tanggal_masuk'])); ?></td>
                                        <td><?= h($row['keterangan'] ?: '-'); ?></td>
                                        <td class="action-cell">
                                            <button class="btn btn-danger btn-sm" onclick="deleteBarangWithId(<?= (int)$row['id_masuk']; ?>)">🗑️ Hapus</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 25px; color: #888;">
                                        Tidak ada data barang masuk pada periode ini (<?= date('d/m/Y', strtotime($tanggal_awal)); ?> s/d <?= date('d/m/Y', strtotime($tanggal_akhir)); ?>).
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div id="modalAdd" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModalAdd()">&times;</span>
            <h2 style="color: white;">Tambah Barang Masuk</h2>

            <form method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="nama_barang">Pilih Barang *</label>
                    <select id="nama_barang" name="nama_barang" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($barang_list as $b): ?>
                            <option value="<?= h($b['nama_barang']); ?>">
                                <?= h($b['nama_barang']); ?> (Stok saat ini: <?= number_format($b['stok']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jumlah_masuk">Jumlah Masuk (Pcs) *</label>
                    <input type="number" id="jumlah_masuk" name="jumlah_masuk" min="1" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_masuk">Tanggal Masuk *</label>
                    <input type="date" id="tanggal_masuk" name="tanggal_masuk" value="<?= date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="keterangan">Keterangan / Supplier</label>
                    <textarea id="keterangan" name="keterangan" placeholder="Contoh: Pengadaan BOS, Pembelian Toko ABC, dll"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">📥 Simpan Transaksi</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModalAdd()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Delete -->
    <div id="modalDeleteConfirm" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModalDelete()">&times;</span>
            <h2 style="color: white;">Konfirmasi Hapus Transaksi</h2>
            <div id="deleteMessage" style="margin: 20px 0; color: white; line-height: 1.6;">
                <p>Apakah Anda yakin ingin menghapus data transaksi barang masuk ini?</p>
                <p style="color: #ffc107; margin-top: 10px;">⚠️ Stok barang akan otomatis dikurangi kembali sesuai jumlah transaksi ini.</p>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeModalDelete()">Batal</button>
                <button class="btn btn-danger" onclick="confirmDelete()">Ya, Hapus & Rollback Stok</button>
            </div>
        </div>
    </div>

    <!-- Modal Notifikasi -->
    <div id="notificationModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width:440px; padding:18px;">
            <span class="modal-close" onclick="closeNotification()">&times;</span>
            <h3 id="notificationTitle" style="margin-top:0; color:#fff;"></h3>
            <p id="notificationMessage" style="color:#fff; margin-top:8px;"></p>
        </div>
    </div>

    <script>
        const API_DELETE_URL = '<?= base_url('api/barang/delete_barang_masuk_by_id.php'); ?>';
        let deleteIdToConfirm = null;

        function openModalAdd() {
            document.getElementById('modalAdd').style.display = 'block';
        }

        function closeModalAdd() {
            document.getElementById('modalAdd').style.display = 'none';
        }

        function cetakLaporan() {
            const tanggalAwal = document.getElementById('tanggal_awal').value;
            const tanggalAkhir = document.getElementById('tanggal_akhir').value;
            const url = `<?= base_url('pages/print_masuk.php'); ?>?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`;
            window.open(url, '_blank');
        }

        function deleteBarangWithId(id) {
            deleteIdToConfirm = id;
            document.getElementById('modalDeleteConfirm').style.display = 'block';
        }

        function confirmDelete() {
            if (!deleteIdToConfirm) return;

            const formData = new FormData();
            formData.append('id_masuk', deleteIdToConfirm);

            fetch(API_DELETE_URL, {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                closeModalDelete();
                if (data.success) {
                    showNotification('✓ Sukses', data.message || 'Transaksi berhasil dihapus.');
                    setTimeout(() => { location.reload(); }, 800);
                } else {
                    showNotification('❌ Error', data.error || data.message || 'Gagal menghapus transaksi.');
                }
            })
            .catch(error => {
                closeModalDelete();
                showNotification('❌ Error', error.message || error);
            });
        }

        function closeModalDelete() {
            document.getElementById('modalDeleteConfirm').style.display = 'none';
            deleteIdToConfirm = null;
        }

        function showNotification(title, message) {
            let modal = document.getElementById('notificationModal');
            if (modal) {
                document.getElementById('notificationTitle').textContent = title;
                document.getElementById('notificationMessage').textContent = message;
                modal.style.display = 'block';
            } else {
                alert(title + ': ' + message);
            }
        }

        function closeNotification() {
            let modal = document.getElementById('notificationModal');
            if (modal) modal.style.display = 'none';
        }

        window.onclick = function(event) {
            let modalAdd = document.getElementById('modalAdd');
            let modalDel = document.getElementById('modalDeleteConfirm');
            let modalNotif = document.getElementById('notificationModal');
            if (event.target === modalAdd) closeModalAdd();
            if (event.target === modalDel) closeModalDelete();
            if (event.target === modalNotif) closeNotification();
        }
    </script>
</body>
</html>