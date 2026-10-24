<?php
// ===================================================
// pages/daftar_barang.php
// Halaman Master Data Barang & Manajemen Stok
// ===================================================

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

// Ambil kategori untuk dropdown form
$kategori_list = fetchAll(
    $conn,
    "SELECT id_kategori, nama_kategori FROM kategori_barang ORDER BY nama_kategori ASC"
);

// Ambil semua data barang beserta kategorinya
$barang_sql = "
    SELECT 
        b.*,
        kb.nama_kategori
    FROM barang b
    LEFT JOIN kategori_barang kb ON kb.id_kategori = b.id_kategori
    ORDER BY b.tanggal_ditambah DESC, b.id_barang DESC
";
$data_barang = fetchAll($conn, $barang_sql);

// ===== PROSES TAMBAH BARANG =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    validate_csrf();
    
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $stok = trim($_POST['stok'] ?? '');
    $tanggal_ditambah = trim($_POST['tanggal_ditambah'] ?? date('Y-m-d'));
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    if (empty($nama_barang)) {
        set_flash('error', '⚠️ Nama barang tidak boleh kosong!');
    } elseif ($id_kategori <= 0) {
        set_flash('error', '⚠️ Kategori barang harus dipilih!');
    } elseif ($stok === '' || !is_numeric($stok) || (int)$stok < 0) {
        set_flash('error', '⚠️ Stok harus diisi dengan angka minimal 0!');
    } elseif (empty($tanggal_ditambah)) {
        set_flash('error', '⚠️ Tanggal ditambah tidak boleh kosong!');
    } else {
        // Cek duplikasi nama barang dengan prepared statement
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM barang WHERE nama_barang = ?");
        if ($check_stmt) {
            $check_stmt->bind_param('s', $nama_barang);
            $check_stmt->execute();
            $row = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ((int)$row['count'] > 0) {
                set_flash('error', '❌ Barang "' . h($nama_barang) . '" sudah terdaftar!');
            } else {
                $insert_stmt = $conn->prepare(
                    "INSERT INTO barang (nama_barang, id_kategori, stok, tanggal_ditambah, keterangan) VALUES (?, ?, ?, ?, ?)"
                );

                if ($insert_stmt) {
                    $stok_int = (int)$stok;
                    $insert_stmt->bind_param('siiss', $nama_barang, $id_kategori, $stok_int, $tanggal_ditambah, $keterangan);

                    if ($insert_stmt->execute()) {
                        set_flash('success', '✅ Barang "' . h($nama_barang) . '" berhasil ditambahkan!');
                        $insert_stmt->close();
                        redirect('pages/daftar_barang.php');
                    } else {
                        set_flash('error', '❌ Gagal menambahkan barang: ' . h($insert_stmt->error));
                        $insert_stmt->close();
                    }
                }
            }
        }
    }
}

// ===== PROSES EDIT BARANG =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    validate_csrf();
    
    $id_barang = (int)($_POST['id_barang'] ?? 0);
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $stok = trim($_POST['stok'] ?? '');
    $tanggal_ditambah = trim($_POST['tanggal_ditambah'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($id_barang <= 0) {
        set_flash('error', '⚠️ ID barang tidak valid!');
    } elseif (empty($nama_barang)) {
        set_flash('error', '⚠️ Nama barang tidak boleh kosong!');
    } elseif ($id_kategori <= 0) {
        set_flash('error', '⚠️ Kategori barang harus dipilih!');
    } elseif ($stok === '' || !is_numeric($stok) || (int)$stok < 0) {
        set_flash('error', '⚠️ Stok harus diisi dengan angka minimal 0!');
    } else {
        $barang_check = fetchOne($conn, "SELECT * FROM barang WHERE id_barang = $id_barang");
        if (!$barang_check) {
            set_flash('error', '⚠️ Data barang tidak ditemukan!');
        } else {
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM barang WHERE nama_barang = ? AND id_barang != ?");
            if ($check_stmt) {
                $check_stmt->bind_param('si', $nama_barang, $id_barang);
                $check_stmt->execute();
                $row = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if ((int)$row['count'] > 0) {
                    set_flash('error', '❌ Nama barang "' . h($nama_barang) . '" sudah digunakan oleh barang lain!');
                } else {
                    $update_stmt = $conn->prepare(
                        "UPDATE barang SET nama_barang = ?, id_kategori = ?, stok = ?, tanggal_ditambah = ?, keterangan = ? WHERE id_barang = ?"
                    );

                    if ($update_stmt) {
                        $stok_int = (int)$stok;
                        $update_stmt->bind_param('siissi', $nama_barang, $id_kategori, $stok_int, $tanggal_ditambah, $keterangan, $id_barang);

                        if ($update_stmt->execute()) {
                            if ($barang_check['nama_barang'] !== $nama_barang) {
                                $old_nama = sanitize($conn, $barang_check['nama_barang']);
                                $new_nama = sanitize($conn, $nama_barang);
                                executeQuery($conn, "UPDATE barang_masuk SET nama_barang = '$new_nama' WHERE nama_barang = '$old_nama'");
                                executeQuery($conn, "UPDATE barang_keluar SET nama_barang = '$new_nama' WHERE nama_barang = '$old_nama'");
                            }

                            set_flash('success', '✅ Data barang "' . h($nama_barang) . '" berhasil diperbarui!');
                            $update_stmt->close();
                            redirect('pages/daftar_barang.php');
                        } else {
                            set_flash('error', '❌ Gagal memperbarui barang: ' . h($update_stmt->error));
                            $update_stmt->close();
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Barang - <?= h(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/table_and_form.css'); ?>">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content-wrapper">
                <h1 class="page-title">Daftar Master Barang</h1>
                
                <?php include __DIR__ . '/../includes/flash.php'; ?>

                <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="openModalAdd()">+ Tambah Barang</button>
                        <a href="<?= base_url('pages/print_barang.php'); ?>" target="_blank" class="btn btn-secondary btn-print">🖨️ Cetak Daftar</a>
                    </div>
                </div>
                
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
                                <th style="text-align: center; width: 160px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($data_barang) > 0): ?>
                                <?php $no = 1; foreach ($data_barang as $barang): ?>
                                <tr>
                                    <td style="text-align: center;"><?= $no++; ?></td>
                                    <td><strong><?= h($barang['nama_barang']); ?></strong></td>
                                    <td><?= h($barang['nama_kategori'] ?? 'Belum dikategorikan'); ?></td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-1"><?= number_format($barang['stok']); ?></span>
                                    </td>
                                    <td style="text-align: center;"><?= date('d/m/Y', strtotime($barang['tanggal_ditambah'])); ?></td>
                                    <td><?= h($barang['keterangan'] ?: '-'); ?></td>
                                    <td class="action-cell">
                                        <button class="btn btn-warning btn-sm" onclick='openModalEdit(<?= (int)$barang['id_barang']; ?>, <?= json_encode($barang['nama_barang']); ?>, <?= (int)$barang['id_kategori']; ?>, <?= (int)$barang['stok']; ?>, <?= json_encode($barang['tanggal_ditambah']); ?>, <?= json_encode($barang['keterangan'] ?? ''); ?>)'>✏️ Edit</button>
                                        <button class="btn btn-danger btn-sm" onclick='deleteBarangWithTransactions(<?= (int)$barang['id_barang']; ?>, <?= json_encode($barang['nama_barang']); ?>)'>🗑️ Hapus</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center" style="padding: 25px; color: #888;">
                                        Belum ada data barang. Silakan klik tombol "+ Tambah Barang".
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Barang -->
    <div id="modalAdd" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModalAdd()">&times;</span>
            <h2>Tambah Master Barang Baru</h2>
            <form method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Nama Barang *</label>
                    <input type="text" name="nama_barang" placeholder="Misal: Spidol Whiteboard" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori Barang *</label>
                    <select name="id_kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategori_list as $kategori): ?>
                            <option value="<?= (int)$kategori['id_kategori']; ?>">
                                <?= h($kategori['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Stok Awal *</label>
                    <input type="number" name="stok" min="0" value="0" required>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Ditambah *</label>
                    <input type="date" name="tanggal_ditambah" value="<?= date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan" placeholder="Keterangan tambahan (opsional)"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Simpan Barang</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModalAdd()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Barang -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModalEdit()">&times;</span>
            <h2>Edit Master Barang</h2>
            <form method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_barang" id="edit_id_barang">
                
                <div class="form-group">
                    <label>Nama Barang *</label>
                    <input type="text" name="nama_barang" id="edit_nama_barang" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori Barang *</label>
                    <select name="id_kategori" id="edit_id_kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategori_list as $kategori): ?>
                            <option value="<?= (int)$kategori['id_kategori']; ?>">
                                <?= h($kategori['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Stok *</label>
                    <input type="number" name="stok" id="edit_stok" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Ditambah *</label>
                    <input type="date" name="tanggal_ditambah" id="edit_tanggal_ditambah" required>
                </div>
                
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan" id="edit_keterangan"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModalEdit()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="modalDeleteConfirm" class="modal">
        <div class="modal-content">
            <h2 style="color: white;">⚠️ Konfirmasi Hapus Barang</h2>
            <div id="deleteMessage" style="margin-top: 15px;"></div>
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
                <button class="btn btn-danger" onclick="proceedDelete()">Ya, Hapus Semua</button>
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
        const API_BASE_URL = '<?= base_url('api/'); ?>';
        let deleteData = {};

        function openModalAdd() {
            document.getElementById('modalAdd').style.display = 'block';
        }

        function closeModalAdd() {
            document.getElementById('modalAdd').style.display = 'none';
        }

        function openModalEdit(id, nama, idKategori, stok, tanggal, keterangan) {
            document.getElementById('edit_id_barang').value = id;
            document.getElementById('edit_nama_barang').value = nama;
            document.getElementById('edit_id_kategori').value = idKategori;
            document.getElementById('edit_stok').value = stok;
            document.getElementById('edit_tanggal_ditambah').value = tanggal;
            document.getElementById('edit_keterangan').value = keterangan || '';
            document.getElementById('modalEdit').style.display = 'block';
        }

        function closeModalEdit() {
            document.getElementById('modalEdit').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('modalDeleteConfirm').style.display = 'none';
        }

        function deleteBarangWithTransactions(id, nama) {
            deleteData = { id: id, nama: nama };

            fetch(API_BASE_URL + 'get_barang_transactions.php?nama_barang=' + encodeURIComponent(nama))
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showNotification('❌ Error', data.error);
                        return;
                    }

                    const masuk = data.count_masuk || 0;
                    const keluar = data.count_keluar || 0;

                    let message = '<p style="color: white;">Barang: <strong>' + escapeHtml(nama) + '</strong></p>';
                    message += '<p style="margin-top: 10px; color: white">Transaksi terkait:</p>';
                    message += '<p style="color: #27ae60;">📥 Barang Masuk: <strong>' + masuk + '</strong> data</p>';
                    message += '<p style="color: #e74c3c;">📤 Barang Keluar: <strong>' + keluar + '</strong> data</p>';
                    message += '<p style="margin-top: 15px; color: #f39c12; font-size: 13px;">⚠️ Menghapus barang master juga akan menghapus seluruh data transaksi terkait.</p>';

                    document.getElementById('deleteMessage').innerHTML = message;
                    document.getElementById('modalDeleteConfirm').style.display = 'block';
                })
                .catch(error => {
                    showNotification('❌ Error', 'Gagal memeriksa transaksi: ' + error);
                });
        }

        function proceedDelete() {
            const id = deleteData.id;
            const nama = deleteData.nama;

            closeDeleteModal();

            const formData1 = new FormData();
            formData1.append('nama_barang', nama);

            fetch(API_BASE_URL + 'delete_barang_masuk.php', { method: 'POST', body: formData1 })
            .then(res => res.json())
            .then(() => {
                const formData2 = new FormData();
                formData2.append('nama_barang', nama);
                return fetch(API_BASE_URL + 'delete_barang_keluar.php', { method: 'POST', body: formData2 });
            })
            .then(res => res.json())
            .then(() => {
                const formData3 = new FormData();
                formData3.append('id_barang', id);
                return fetch(API_BASE_URL + 'delete_barang.php', { method: 'POST', body: formData3 });
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification('✅ Sukses', 'Barang dan riwayat transaksi berhasil dihapus!');
                    setTimeout(() => { location.reload(); }, 800);
                } else {
                    showNotification('❌ Gagal', data.error || 'Terjadi kesalahan saat menghapus data.');
                }
            })
            .catch(error => {
                showNotification('❌ Error', error.message || error);
            });
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

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value;
            return div.innerHTML;
        }

        window.onclick = function(event) {
            let modalAdd = document.getElementById('modalAdd');
            let modalEdit = document.getElementById('modalEdit');
            let modalDelete = document.getElementById('modalDeleteConfirm');
            let modalNotification = document.getElementById('notificationModal');
            
            if (event.target === modalAdd) closeModalAdd();
            if (event.target === modalEdit) closeModalEdit();
            if (event.target === modalDelete) closeDeleteModal();
            if (event.target === modalNotification) closeNotification();
        }
    </script>
</body>
</html>
