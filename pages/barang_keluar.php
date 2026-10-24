<?php
// ===================================================
// pages/barang_keluar.php
// Halaman Pencatatan Transaksi Barang Keluar
// ===================================================

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

// Ambil semua barang untuk dropdown
$barang_list = fetchAll($conn, "SELECT * FROM barang ORDER BY nama_barang ASC");

// Proses input barang keluar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    validate_csrf();
    
    $tanggal_keluar = trim($_POST['tanggal_keluar'] ?? '');
    $nama_barang = sanitize($conn, trim($_POST['nama_barang'] ?? ''));
    $jumlah_keluar = (int)($_POST['jumlah_keluar'] ?? 0);
    $keterangan = sanitize($conn, trim($_POST['keterangan'] ?? ''));

    // Validasi input
    if (empty($tanggal_keluar)) {
        set_flash('error', '⚠️ Tanggal keluar wajib diisi!');
    } elseif (empty($nama_barang)) {
        set_flash('error', '⚠️ Nama barang harus dipilih!');
    } elseif ($jumlah_keluar <= 0) {
        set_flash('error', '⚠️ Jumlah keluar harus lebih dari 0!');
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_keluar)) {
        set_flash('error', '⚠️ Format tanggal tidak valid!');
    } else {
        $barang = fetchOne($conn, "SELECT * FROM barang WHERE nama_barang = '$nama_barang'");

        if (!$barang) {
            set_flash('error', '⚠️ Barang tidak ditemukan di data master!');
        } elseif ((int)$barang['stok'] < $jumlah_keluar) {
            set_flash('error', "⚠️ Stok tidak mencukupi! Stok tersedia saat ini: {$barang['stok']} pcs.");
        } else {
            $nama_barang_db = sanitize($conn, $barang['nama_barang']);

            $conn->begin_transaction();
            try {
                $insert_stmt = $conn->prepare("INSERT INTO barang_keluar (tanggal_keluar, nama_barang, jumlah_keluar, keterangan) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param('ssis', $tanggal_keluar, $nama_barang_db, $jumlah_keluar, $keterangan);
                $insert_stmt->execute();
                $insert_stmt->close();

                $update_stmt = $conn->prepare("UPDATE barang SET stok = stok - ? WHERE nama_barang = ?");
                $update_stmt->bind_param('is', $jumlah_keluar, $nama_barang_db);
                $update_stmt->execute();
                $update_stmt->close();

                $conn->commit();
                $stok_sisa = $barang['stok'] - $jumlah_keluar;
                set_flash('success', "✓ Barang keluar berhasil dicatat! Sisa stok '{$barang['nama_barang']}': $stok_sisa");
            } catch (Throwable $e) {
                $conn->rollback();
                set_flash('error', '⚠️ Gagal mencatat barang keluar: ' . $e->getMessage());
            }
        }
    }
    redirect('pages/barang_keluar.php');
}

// Filter tanggal
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');

$tanggal_awal_clean = sanitize($conn, $tanggal_awal);
$tanggal_akhir_clean = sanitize($conn, $tanggal_akhir);

$riwayat_keluar = fetchAll($conn, "
    SELECT * FROM barang_keluar 
    WHERE DATE(tanggal_keluar) BETWEEN '$tanggal_awal_clean' AND '$tanggal_akhir_clean'
    ORDER BY tanggal_keluar DESC, id_keluar DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Keluar - <?= h(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/table_and_form.css'); ?>">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="content-wrapper">
                <h1 class="page-title">Pencatatan Barang Keluar</h1>

                <?php include __DIR__ . '/../includes/flash.php'; ?>

                <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="openModalAdd()">+ Tambah Barang Keluar</button>
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
                                <th style="text-align: center;">Jumlah Keluar</th>
                                <th style="text-align: center;">Tanggal Keluar</th>
                                <th>Keterangan / Keperluan</th>
                                <th style="text-align: center; width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($riwayat_keluar) > 0): ?>
                                <?php $no = 1; foreach ($riwayat_keluar as $row): ?>
                                    <tr>
                                        <td style="text-align: center;"><?= $no++; ?></td>
                                        <td><strong><?= h($row['nama_barang']); ?></strong></td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-red">-<?= number_format($row['jumlah_keluar']); ?></span>
                                        </td>
                                        <td style="text-align: center;"><?= date('d/m/Y', strtotime($row['tanggal_keluar'])); ?></td>
                                        <td><?= h($row['keterangan'] ?: '-'); ?></td>
                                        <td class="action-cell">
                                            <button class="btn btn-danger btn-sm" onclick="deleteBarangWithId(<?= (int)$row['id_keluar']; ?>)">🗑️ Hapus</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 25px; color: #888;">
                                        Tidak ada data barang keluar pada periode ini (<?= date('d/m/Y', strtotime($tanggal_awal)); ?> s/d <?= date('d/m/Y', strtotime($tanggal_akhir)); ?>).
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
            <h2 style="color: white;">Tambah Barang Keluar</h2>

            <form method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="nama_barang">Pilih Barang *</label>
                    <select id="nama_barang" name="nama_barang" required onchange="checkStok()">
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($barang_list as $b): ?>
                            <option value="<?= h($b['nama_barang']); ?>" data-stok="<?= (int)$b['stok']; ?>">
                                <?= h($b['nama_barang']); ?> (Stok tersedia: <?= number_format($b['stok']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jumlah_keluar">Jumlah Keluar (Pcs) *</label>
                    <input type="number" id="jumlah_keluar" name="jumlah_keluar" min="1" required oninput="validateStok()">
                    <div id="stok_warning" style="margin-top: 8px; color: #ff6b6b; font-weight: 500; display: none;">
                        ⚠️ Jumlah melebihi stok yang tersedia!
                    </div>
                </div>

                <div class="form-group">
                    <label for="tanggal_keluar">Tanggal Keluar *</label>
                    <input type="date" id="tanggal_keluar" name="tanggal_keluar" value="<?= date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="keterangan">Keperluan / Penerima</label>
                    <textarea id="keterangan" name="keterangan" placeholder="Contoh: Praktikum Lab Komputer, Keperluan TU, dll"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" id="submit_btn" class="btn btn-primary">📤 Simpan Barang Keluar</button>
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
                <p>Apakah Anda yakin ingin menghapus data transaksi barang keluar ini?</p>
                <p style="color: #ffc107; margin-top: 10px;">⚠️ Stok barang akan otomatis dikembalikan (ditambahkan) kembali ke master barang.</p>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeModalDelete()">Batal</button>
                <button class="btn btn-danger" onclick="confirmDelete()">Ya, Hapus & Kembalikan Stok</button>
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
        const API_DELETE_URL = '<?= base_url('api/barang/delete_barang_keluar_by_id.php'); ?>';
        let deleteIdToConfirm = null;

        function checkStok() {
            validateStok();
        }

        function validateStok() {
            const select = document.getElementById('nama_barang');
            const jumlah = parseInt(document.getElementById('jumlah_keluar').value) || 0;
            const warning = document.getElementById('stok_warning');
            const submitBtn = document.getElementById('submit_btn');

            if (select.value && select.selectedIndex >= 0) {
                const selectedOption = select.options[select.selectedIndex];
                const stok = parseInt(selectedOption.getAttribute('data-stok')) || 0;

                if (jumlah > stok) {
                    warning.style.display = 'block';
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                } else {
                    warning.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                }
            }
        }

        function openModalAdd() {
            document.getElementById('modalAdd').style.display = 'block';
        }

        function closeModalAdd() {
            document.getElementById('modalAdd').style.display = 'none';
        }

        function cetakLaporan() {
            const tanggalAwal = document.getElementById('tanggal_awal').value;
            const tanggalAkhir = document.getElementById('tanggal_akhir').value;
            const url = `<?= base_url('pages/print_keluar.php'); ?>?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`;
            window.open(url, '_blank');
        }

        function deleteBarangWithId(id) {
            deleteIdToConfirm = id;
            document.getElementById('modalDeleteConfirm').style.display = 'block';
        }

        function confirmDelete() {
            if (!deleteIdToConfirm) return;

            const formData = new FormData();
            formData.append('id_keluar', deleteIdToConfirm);

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