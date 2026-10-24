<?php
// ===================================================
// pages/admin.php
// Halaman Manajemen Pengguna & Admin (SUPERADMIN / ADMIN)
// ===================================================

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

// Proteksi role: Hanya superadmin dan admin yang berhak
check_role(['superadmin', 'admin']);

// Ambil semua daftar admin
$admin_list_sql = "SELECT id_admin, nama_admin, role FROM admin ORDER BY id_admin ASC";
$admin_list = fetchAll($conn, $admin_list_sql);

// Proses form action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    validate_csrf();
    $action = $_POST['action'];

    // 1. Reset Database Demo (Khusus Superadmin)
    if ($action === 'reset_demo') {
        if ($_SESSION['admin_role'] !== 'superadmin') {
            set_flash('error', '⛔ Hanya Superadmin yang berhak mereset database demo!');
            redirect('pages/admin.php');
        }

        $schema_file = ROOT_PATH . '/database/schema.sql';
        $seeders_file = ROOT_PATH . '/database/seeders.sql';

        if (file_exists($schema_file) && file_exists($seeders_file)) {
            $schema_sql = file_get_contents($schema_file);
            $seeders_sql = file_get_contents($seeders_file);

            mysqli_report(MYSQLI_REPORT_OFF);

            // Eksekusi schema
            if (mysqli_multi_query($conn, $schema_sql)) {
                do {
                    if ($res = mysqli_store_result($conn)) {
                        mysqli_free_result($res);
                    }
                } while (mysqli_more_results($conn) && mysqli_next_result($conn));
            }

            // Eksekusi seeders
            if (mysqli_multi_query($conn, $seeders_sql)) {
                do {
                    if ($res = mysqli_store_result($conn)) {
                        mysqli_free_result($res);
                    }
                } while (mysqli_more_results($conn) && mysqli_next_result($conn));
            }

            set_flash('success', '✓ Database demo berhasil direset ke kondisi awal lengkap dengan 3 akun demo, kategori, master barang, dan transaksi!');
        } else {
            set_flash('error', '⚠️ File schema.sql atau seeders.sql tidak ditemukan.');
        }
        redirect('pages/admin.php');
    }

    // 2. Edit Profil Sendiri
    if ($action === 'edit_profile') {
        $nama_admin = trim($_POST['nama_admin'] ?? '');
        $password_baru = (string)($_POST['password_baru'] ?? '');
        $confirm_password = (string)($_POST['confirm_password'] ?? '');
        $user_id = (int)$_SESSION['admin_id'];

        if (empty($nama_admin)) {
            set_flash('error', '⚠️ Nama admin tidak boleh kosong!');
        } elseif (!empty($password_baru) && $password_baru !== $confirm_password) {
            set_flash('error', '⚠️ Konfirmasi password baru tidak cocok!');
        } elseif (!empty($password_baru) && strlen($password_baru) < 6) {
            set_flash('error', '⚠️ Password baru minimal 6 karakter!');
        } else {
            $check_stmt = $conn->prepare("SELECT id_admin FROM admin WHERE nama_admin = ? AND id_admin != ? LIMIT 1");
            $check_stmt->bind_param('si', $nama_admin, $user_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($exists) {
                set_flash('error', '⚠️ Username tersebut sudah digunakan oleh akun lain!');
            } else {
                if (!empty($password_baru)) {
                    $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE admin SET nama_admin = ?, password = ? WHERE id_admin = ?");
                    $update_stmt->bind_param('ssi', $nama_admin, $password_hash, $user_id);
                } else {
                    $update_stmt = $conn->prepare("UPDATE admin SET nama_admin = ? WHERE id_admin = ?");
                    $update_stmt->bind_param('si', $nama_admin, $user_id);
                }

                if ($update_stmt && $update_stmt->execute()) {
                    $_SESSION['admin_name'] = $nama_admin;
                    set_flash('success', '✓ Profil Anda berhasil diperbarui!');
                    $update_stmt->close();
                } else {
                    set_flash('error', '⚠️ Gagal memperbarui data profil.');
                }
            }
        }
        redirect('pages/admin.php');
    }
    
    // 3. Tambah Admin / Staff Baru
    if ($action === 'add_admin') {
        $nama_admin = trim($_POST['nama_admin'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm_password = (string)($_POST['confirm_password'] ?? '');
        $role = $_POST['role'] ?? 'admin';
        
        if (empty($nama_admin)) {
            set_flash('error', '⚠️ Nama admin tidak boleh kosong!');
        } elseif (empty($password)) {
            set_flash('error', '⚠️ Password wajib diisi!');
        } elseif ($password !== $confirm_password) {
            set_flash('error', '⚠️ Konfirmasi password tidak cocok!');
        } elseif (strlen($password) < 6) {
            set_flash('error', '⚠️ Password minimal 6 karakter!');
        } elseif (!in_array($role, ['admin', 'superadmin', 'staff'], true)) {
            set_flash('error', '⚠️ Role tidak valid!');
        } elseif ($role === 'superadmin' && $_SESSION['admin_role'] !== 'superadmin') {
            set_flash('error', '⚠️ Hanya Superadmin yang berhak membuat akun Superadmin!');
        } else {
            $check_stmt = $conn->prepare("SELECT id_admin FROM admin WHERE nama_admin = ? LIMIT 1");
            if ($check_stmt) {
                $check_stmt->bind_param('s', $nama_admin);
                $check_stmt->execute();
                $exists = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if ($exists) {
                    set_flash('error', '⚠️ Nama admin/username sudah terdaftar!');
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $insert_stmt = $conn->prepare("INSERT INTO admin (nama_admin, password, role) VALUES (?, ?, ?)");
                    if ($insert_stmt) {
                        $insert_stmt->bind_param('sss', $nama_admin, $password_hash, $role);
                        if ($insert_stmt->execute()) {
                            set_flash('success', "✓ Pengguna baru '$nama_admin' ($role) berhasil ditambahkan!");
                        } else {
                            set_flash('error', '⚠️ Gagal menambahkan pengguna: ' . h($insert_stmt->error));
                        }
                        $insert_stmt->close();
                    }
                }
            }
        }
        redirect('pages/admin.php');
    }
    
    // 4. Hapus Admin / Staff
    if ($action === 'delete_admin') {
        $id_admin = (int)($_POST['id_admin'] ?? 0);
        
        if ($id_admin === (int)$_SESSION['admin_id']) {
            set_flash('error', '⚠️ Anda tidak dapat menghapus akun Anda sendiri saat sedang aktif login!');
        } else {
            $target_admin = fetchOne($conn, "SELECT role, nama_admin FROM admin WHERE id_admin = $id_admin");
            if ($target_admin && $target_admin['role'] === 'superadmin' && $_SESSION['admin_role'] !== 'superadmin') {
                set_flash('error', '⚠️ Hanya sesama Superadmin yang dapat mengelola akun Superadmin!');
            } else {
                $delete_stmt = $conn->prepare("DELETE FROM admin WHERE id_admin = ?");
                if ($delete_stmt) {
                    $delete_stmt->bind_param('i', $id_admin);
                    if ($delete_stmt->execute()) {
                        set_flash('success', "✓ Pengguna '{$target_admin['nama_admin']}' berhasil dihapus!");
                    } else {
                        set_flash('error', '⚠️ Gagal menghapus pengguna.');
                    }
                    $delete_stmt->close();
                }
            }
        }
        redirect('pages/admin.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - <?= h(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/table_and_form.css'); ?>">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="content-wrapper">
                <h1 class="page-title">Manajemen Akun Pengguna & Admin</h1>
                
                <?php include __DIR__ . '/../includes/flash.php'; ?>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <?php if ($_SESSION['admin_role'] === 'superadmin'): ?>
                            <button class="btn btn-danger btn-sm" onclick="openModalResetDemo()" title="Reset seluruh database ke kondisi demo awal">
                                🔄 Reset Data Demo
                            </button>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="openModalAddAdmin()">+ Tambah Pengguna Baru</button>
                    </div>
                </div>
                
                <!-- Daftar Admin User -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th>Nama Pengguna (Username)</th>
                                <th style="text-align: center;">Role Akses</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center; width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($admin_list) > 0): ?>
                                <?php $no = 1; foreach ($admin_list as $admin): ?>
                                    <?php
                                    $is_current = ((int)$admin['id_admin'] === (int)$_SESSION['admin_id']);
                                    $status = $is_current ? '<span style="color: #27ae60; font-weight: bold;">👤 Profil Aktif</span>' : '<span style="color: #777;">Terdaftar</span>';
                                    
                                    if ($admin['role'] === 'superadmin') {
                                        $role_badge = '<span style="background: #e74c3c; color: white; padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 12px;">🔐 Superadmin</span>';
                                    } elseif ($admin['role'] === 'admin') {
                                        $role_badge = '<span style="background: #3498db; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px;">👤 Admin</span>';
                                    } else {
                                        $role_badge = '<span style="background: #95a5a6; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px;">📋 Staff</span>';
                                    }
                                    ?>
                                    <tr>
                                        <td style="text-align: center;"><?= $no++; ?></td>
                                        <td><strong><?= h($admin['nama_admin']); ?></strong></td>
                                        <td style="text-align: center;"><?= $role_badge; ?></td>
                                        <td style="text-align: center;"><?= $status; ?></td>
                                        <td class="action-cell">
                                            <?php if ($is_current): ?>
                                                <button class="btn btn-warning btn-sm" onclick="openModalEditProfile(<?= json_encode($admin['nama_admin']); ?>)">✏️ Edit Profil</button>
                                            <?php elseif ($admin['role'] === 'superadmin' && $_SESSION['admin_role'] !== 'superadmin'): ?>
                                                <span style="color: #999; font-size: 12px;">🛡️ Terproteksi</span>
                                            <?php else: ?>
                                                <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= (int)$admin['id_admin']; ?>, '<?= h($admin['nama_admin']); ?>')">🗑️ Hapus</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center" style="padding: 25px; color: #888;">
                                        Belum ada pengguna lain terdaftar.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Profil Saya -->
    <div id="modalEditProfile" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModalEditProfile()">&times;</span>
            <h2 style="color: white;">Edit Profil Saya</h2>
            
            <form method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="edit_profile">
                
                <div class="form-group">
                    <label for="nama_admin_edit">Username Akun *</label>
                    <input type="text" id="nama_admin_edit" name="nama_admin" value="<?= h($_SESSION['admin_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password_baru_edit">Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                    <input type="password" id="password_baru_edit" name="password_baru" placeholder="Minimal 6 karakter">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password_edit">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password_edit" name="confirm_password" placeholder="Masukkan ulang password baru">
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Simpan Profil</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModalEditProfile()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Admin -->
    <div id="modalAddAdmin" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModalAddAdmin()">&times;</span>
            <h2 style="color: white;">Tambah Pengguna Baru</h2>
            
            <form method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_admin">
                
                <div class="form-group">
                    <label for="nama_admin_add">Username *</label>
                    <input type="text" id="nama_admin_add" name="nama_admin" placeholder="Masukkan username" required>
                </div>
                
                <div class="form-group">
                    <label for="password_add">Password *</label>
                    <input type="password" id="password_add" name="password" placeholder="Minimal 6 karakter" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password_add">Konfirmasi Password *</label>
                    <input type="password" id="confirm_password_add" name="confirm_password" placeholder="Masukkan ulang password" required>
                </div>
                
                <div class="form-group">
                    <label for="role_add">Role Hak Akses *</label>
                    <select id="role_add" name="role" required>
                        <option value="staff">📋 Staff (Operasional Transaksi)</option>
                        <option value="admin">👤 Admin (Akses Standar Master Data)</option>
                        <?php if ($_SESSION['admin_role'] === 'superadmin'): ?>
                            <option value="superadmin">🔐 Superadmin (Akses Penuh Seluruh Sistem)</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModalAddAdmin()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Delete -->
    <div id="modalDelete" class="modal">
        <div class="modal-content" style="text-align: center;">
            <h2 style="color: white;">⚠️ Konfirmasi Hapus Pengguna</h2>
            <p id="deleteMessage" style="margin: 20px 0; color: white;"></p>
            
            <form id="deleteForm" method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="delete_admin">
                <input type="hidden" name="id_admin" id="delete_id">
                
                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-danger">Ya, Hapus Akun</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModalDelete()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Reset Demo (Khusus Superadmin) -->
    <?php if ($_SESSION['admin_role'] === 'superadmin'): ?>
    <div id="modalResetDemo" class="modal">
        <div class="modal-content" style="text-align: center;">
            <h2 style="color: white;">⚠️ Konfirmasi Reset Database Demo</h2>
            <div style="margin: 20px 0; color: white; line-height: 1.6; text-align: left; background: rgba(231,76,60,0.15); padding: 15px; border-radius: 8px; border: 1px solid #e74c3c;">
                <p style="margin: 0 0 8px 0; font-weight: 600; color: #ff6b6b;">Perhatian:</p>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                    <li>Seluruh data master barang, kategori, dan transaksi masuk/keluar akan dikembalikan ke data demo awal.</li>
                    <li>Akun pengguna akan direset kembali menjadi 3 akun default (<code>superadmin</code>, <code>admin</code>, <code>staff</code>).</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="reset_demo">
                
                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-danger">Ya, Reset Database Sekarang</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModalResetDemo()">Batal</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openModalResetDemo() {
            document.getElementById('modalResetDemo').style.display = 'block';
        }

        function closeModalResetDemo() {
            document.getElementById('modalResetDemo').style.display = 'none';
        }

        function openModalEditProfile(username) {
            document.getElementById('nama_admin_edit').value = username;
            document.getElementById('password_baru_edit').value = '';
            document.getElementById('confirm_password_edit').value = '';
            document.getElementById('modalEditProfile').style.display = 'block';
        }

        function closeModalEditProfile() {
            document.getElementById('modalEditProfile').style.display = 'none';
        }

        function openModalAddAdmin() {
            document.getElementById('modalAddAdmin').style.display = 'block';
        }

        function closeModalAddAdmin() {
            document.getElementById('modalAddAdmin').style.display = 'none';
        }

        function confirmDelete(id, nama) {
            document.getElementById('deleteMessage').textContent = 'Apakah Anda yakin ingin menghapus akun pengguna: ' + nama + ' ?';
            document.getElementById('delete_id').value = id;
            document.getElementById('modalDelete').style.display = 'block';
        }

        function closeModalDelete() {
            document.getElementById('modalDelete').style.display = 'none';
        }

        window.onclick = function(event) {
            let modalAdd = document.getElementById('modalAddAdmin');
            let modalDel = document.getElementById('modalDelete');
            let modalEdit = document.getElementById('modalEditProfile');
            let modalReset = document.getElementById('modalResetDemo');
            if (event.target === modalAdd) closeModalAddAdmin();
            if (event.target === modalDel) closeModalDelete();
            if (event.target === modalEdit) closeModalEditProfile();
            if (modalReset && event.target === modalReset) closeModalResetDemo();
        }
    </script>
</body>
</html>
