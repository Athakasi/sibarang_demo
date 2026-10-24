<?php
// ===================================================
// pages/kategori_barang.php
// Halaman Master Kategori Barang
// ===================================================

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

function findKategoriById(mysqli $conn, int $idKategori): ?array {
    $stmt = $conn->prepare('SELECT id_kategori, nama_kategori, keterangan FROM kategori_barang WHERE id_kategori = ? LIMIT 1');
    if (!$stmt) return null;
    $stmt->bind_param('i', $idKategori);
    $stmt->execute();
    $result = $stmt->get_result();
    $kategori = $result->fetch_assoc() ?: null;
    $stmt->close();
    return $kategori;
}

function kategoriNameExists(mysqli $conn, string $namaKategori, ?int $excludeId = null): bool {
    if ($excludeId === null) {
        $stmt = $conn->prepare('SELECT id_kategori FROM kategori_barang WHERE nama_kategori = ? LIMIT 1');
        if (!$stmt) return true;
        $stmt->bind_param('s', $namaKategori);
    } else {
        $stmt = $conn->prepare('SELECT id_kategori FROM kategori_barang WHERE nama_kategori = ? AND id_kategori != ? LIMIT 1');
        if (!$stmt) return true;
        $stmt->bind_param('si', $namaKategori, $excludeId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function getUmumKategoriId(mysqli $conn): ?int {
    $stmt = $conn->prepare("SELECT id_kategori FROM kategori_barang WHERE nama_kategori = 'Umum' LIMIT 1");
    if (!$stmt) return null;
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['id_kategori'] : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $action = $_POST['action'] ?? '';
    $nama_kategori = trim((string)($_POST['nama_kategori'] ?? ''));
    $keterangan = trim((string)($_POST['keterangan'] ?? ''));

    if ($action === 'add_kategori') {
        if ($nama_kategori === '') {
            set_flash('error', '⚠️ Nama kategori tidak boleh kosong!');
        } elseif (kategoriNameExists($conn, $nama_kategori)) {
            set_flash('error', '⚠️ Nama kategori sudah terdaftar!');
        } else {
            $stmt = $conn->prepare('INSERT INTO kategori_barang (nama_kategori, keterangan) VALUES (?, ?)');
            if ($stmt) {
                $stmt->bind_param('ss', $nama_kategori, $keterangan);
                if ($stmt->execute()) {
                    set_flash('success', '✓ Kategori "' . h($nama_kategori) . '" berhasil ditambahkan!');
                } else {
                    set_flash('error', '⚠️ Gagal menambahkan kategori: ' . h($stmt->error));
                }
                $stmt->close();
            }
        }
        redirect('pages/kategori_barang.php');
    }

    if ($action === 'edit_kategori') {
        $id_kategori = (int)($_POST['id_kategori'] ?? 0);
        $kategori = findKategoriById($conn, $id_kategori);

        if (!$kategori) {
            set_flash('error', '⚠️ Kategori tidak ditemukan!');
        } elseif ($nama_kategori === '') {
            set_flash('error', '⚠️ Nama kategori tidak boleh kosong!');
        } elseif ($kategori['nama_kategori'] === 'Umum' && $nama_kategori !== 'Umum') {
            set_flash('error', '⚠️ Nama kategori default "Umum" tidak boleh diubah!');
        } elseif (kategoriNameExists($conn, $nama_kategori, $id_kategori)) {
            set_flash('error', '⚠️ Nama kategori sudah digunakan!');
        } else {
            $stmt = $conn->prepare('UPDATE kategori_barang SET nama_kategori = ?, keterangan = ? WHERE id_kategori = ?');
            if ($stmt) {
                $stmt->bind_param('ssi', $nama_kategori, $keterangan, $id_kategori);
                if ($stmt->execute()) {
                    set_flash('success', '✓ Kategori berhasil diperbarui!');
                } else {
                    set_flash('error', '⚠️ Gagal memperbarui kategori: ' . h($stmt->error));
                }
                $stmt->close();
            }
        }
        redirect('pages/kategori_barang.php');
    }

    if ($action === 'delete_kategori') {
        $id_kategori = (int)($_POST['id_kategori'] ?? 0);
        $kategori = findKategoriById($conn, $id_kategori);

        if (!$kategori) {
            set_flash('error', '⚠️ Kategori tidak ditemukan!');
        } elseif ($kategori['nama_kategori'] === 'Umum') {
            set_flash('error', '⚠️ Kategori default "Umum" tidak boleh dihapus!');
        } else {
            $id_umum = getUmumKategoriId($conn);
            if ($id_umum === null) {
                set_flash('error', '⚠️ Kategori default "Umum" belum tersedia!');
            } else {
                $conn->begin_transaction();
                try {
                    $update_stmt = $conn->prepare('UPDATE barang SET id_kategori = ? WHERE id_kategori = ?');
                    $update_stmt->bind_param('ii', $id_umum, $id_kategori);
                    $update_stmt->execute();
                    $moved_count = $update_stmt->affected_rows;
                    $update_stmt->close();

                    $delete_stmt = $conn->prepare('DELETE FROM kategori_barang WHERE id_kategori = ?');
                    $delete_stmt->bind_param('i', $id_kategori);
                    $delete_stmt->execute();
                    $delete_stmt->close();

                    $conn->commit();
                    set_flash('success', "✓ Kategori berhasil dihapus. $moved_count barang terkait dipindahkan ke kategori 'Umum'.");
                } catch (Throwable $e) {
                    $conn->rollback();
                    set_flash('error', '⚠️ Gagal menghapus kategori: ' . $e->getMessage());
                }
            }
        }
        redirect('pages/kategori_barang.php');
    }
}

$kategori_sql = "
    SELECT
        kb.id_kategori,
        kb.nama_kategori,
        kb.keterangan,
        COUNT(b.id_barang) AS jumlah_barang
    FROM kategori_barang kb
    LEFT JOIN barang b ON b.id_kategori = kb.id_kategori
    GROUP BY kb.id_kategori, kb.nama_kategori, kb.keterangan
    ORDER BY kb.nama_kategori ASC
";
$kategori_list = fetchAll($conn, $kategori_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Barang - <?= h(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/table_and_form.css'); ?>">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="content-wrapper">
                <h1 class="page-title">Manajemen Kategori Barang</h1>

                <?php include __DIR__ . '/../includes/flash.php'; ?>

                <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="openModalAddKategori()">+ Tambah Kategori</button>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th>Nama Kategori</th>
                                <th style="text-align: center;">Jumlah Barang</th>
                                <th>Keterangan</th>
                                <th style="text-align: center; width: 160px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($kategori_list) > 0): ?>
                                <?php $no = 1; foreach ($kategori_list as $kategori): ?>
                                    <?php
                                    $id_kategori = (int)$kategori['id_kategori'];
                                    $nama_kategori = (string)$kategori['nama_kategori'];
                                    $keterangan = (string)($kategori['keterangan'] ?? '');
                                    $jumlah_barang = (int)$kategori['jumlah_barang'];
                                    $is_umum = $nama_kategori === 'Umum';
                                    ?>
                                    <tr>
                                        <td style="text-align: center;"><?= $no++; ?></td>
                                        <td><strong><?= h($nama_kategori); ?></strong></td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-1"><?= $jumlah_barang; ?></span>
                                        </td>
                                        <td><?= h($keterangan !== '' ? $keterangan : '-'); ?></td>
                                        <td class="action-cell">
                                            <button
                                                type="button"
                                                class="btn btn-warning btn-sm"
                                                onclick='openModalEditKategori(<?= json_encode([
                                                    'id' => $id_kategori,
                                                    'nama' => $nama_kategori,
                                                    'keterangan' => $keterangan,
                                                    'isUmum' => $is_umum,
                                                ]); ?>)'>
                                                ✏️ Edit
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-danger btn-sm"
                                                <?= $is_umum ? 'disabled title="Kategori Umum tidak dapat dihapus"' : ''; ?>
                                                onclick='openModalDeleteKategori(<?= json_encode([
                                                    'id' => $id_kategori,
                                                    'nama' => $nama_kategori,
                                                    'jumlahBarang' => $jumlah_barang,
                                                ]); ?>)'>
                                                🗑️ Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center" style="padding: 25px; color: #888;">
                                        Belum ada data kategori barang.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Kategori -->
    <div id="modalAddKategori" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModalAddKategori()">&times;</span>
            <h2 style="color: white;">Tambah Kategori Barang</h2>

            <form method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_kategori">

                <div class="form-group">
                    <label for="nama_kategori_add">Nama Kategori *</label>
                    <input type="text" id="nama_kategori_add" name="nama_kategori" maxlength="100" placeholder="Misal: Elektronik, ATK, Alat Laboratorium" required>
                </div>

                <div class="form-group">
                    <label for="keterangan_add">Keterangan</label>
                    <textarea id="keterangan_add" name="keterangan" maxlength="255" placeholder="Keterangan tambahan"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModalAddKategori()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Kategori -->
    <div id="modalEditKategori" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModalEditKategori()">&times;</span>
            <h2 style="color: white;">Edit Kategori Barang</h2>

            <form method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="edit_kategori">
                <input type="hidden" id="id_kategori_edit" name="id_kategori">

                <div class="form-group">
                    <label for="nama_kategori_edit">Nama Kategori *</label>
                    <input type="text" id="nama_kategori_edit" name="nama_kategori" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="keterangan_edit">Keterangan</label>
                    <textarea id="keterangan_edit" name="keterangan" maxlength="255"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModalEditKategori()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="modalDeleteKategori" class="modal">
        <div class="modal-content" style="text-align: center;">
            <h2 style="color: white;">⚠️ Konfirmasi Hapus Kategori</h2>
            <p id="deleteMessage" style="margin: 20px 0; color: white;"></p>

            <form method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="delete_kategori">
                <input type="hidden" id="id_kategori_delete" name="id_kategori">

                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModalDeleteKategori()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModalAddKategori() {
            document.getElementById('modalAddKategori').style.display = 'block';
        }

        function closeModalAddKategori() {
            document.getElementById('modalAddKategori').style.display = 'none';
        }

        function openModalEditKategori(kategori) {
            const namaInput = document.getElementById('nama_kategori_edit');
            document.getElementById('id_kategori_edit').value = kategori.id;
            namaInput.value = kategori.nama;
            namaInput.readOnly = kategori.isUmum;
            namaInput.title = kategori.isUmum ? 'Kategori default Umum tidak boleh diganti nama' : '';
            document.getElementById('keterangan_edit').value = kategori.keterangan || '';
            document.getElementById('modalEditKategori').style.display = 'block';
        }

        function closeModalEditKategori() {
            document.getElementById('modalEditKategori').style.display = 'none';
        }

        function openModalDeleteKategori(kategori) {
            const message = kategori.jumlahBarang > 0
                ? 'Kategori "' + kategori.nama + '" masih dipakai oleh ' + kategori.jumlahBarang + ' barang. Barang tersebut akan otomatis dipindahkan ke kategori Umum.'
                : 'Apakah Anda yakin ingin menghapus kategori "' + kategori.nama + '"?';

            document.getElementById('deleteMessage').textContent = message;
            document.getElementById('id_kategori_delete').value = kategori.id;
            document.getElementById('modalDeleteKategori').style.display = 'block';
        }

        function closeModalDeleteKategori() {
            document.getElementById('modalDeleteKategori').style.display = 'none';
        }

        window.onclick = function(event) {
            const modalAdd = document.getElementById('modalAddKategori');
            const modalEdit = document.getElementById('modalEditKategori');
            const modalDelete = document.getElementById('modalDeleteKategori');

            if (event.target === modalAdd) closeModalAddKategori();
            if (event.target === modalEdit) closeModalEditKategori();
            if (event.target === modalDelete) closeModalDeleteKategori();
        }
    </script>
</body>
</html>
