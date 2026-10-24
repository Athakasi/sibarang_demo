<?php
// ===================================================
// pages/profile.php
// Halaman Edit Profil Akun Pengguna Aktif
// ===================================================

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

// Halaman profil mandiri ini khusus untuk role Staff.
// Admin & Superadmin mengelola akun dan profil di Manajemen Admin (admin.php)
if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] !== 'staff') {
    redirect('pages/admin.php');
}

$user_id = (int)$_SESSION['admin_id'];
$current_admin = fetchOne($conn, "SELECT id_admin, nama_admin, role FROM admin WHERE id_admin = $user_id");

if (!$current_admin) {
    set_flash('error', 'Akun tidak ditemukan.');
    redirect('logout.php');
}

// Proses edit profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_profile') {
    validate_csrf();
    
    $nama_admin = trim($_POST['nama_admin'] ?? '');
    $password_baru = (string)($_POST['password_baru'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');
    
    if (empty($nama_admin)) {
        set_flash('error', '⚠️ Nama admin tidak boleh kosong!');
    } elseif (!empty($password_baru) && $password_baru !== $confirm_password) {
        set_flash('error', '⚠️ Konfirmasi password baru tidak cocok!');
    } elseif (!empty($password_baru) && strlen($password_baru) < 6) {
        set_flash('error', '⚠️ Password baru minimal 6 karakter!');
    } else {
        // Cek duplikasi nama jika nama diubah
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
                redirect('pages/profile.php');
            } else {
                set_flash('error', '⚠️ Gagal memperbarui data profil.');
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
    <title>Profil Saya - <?= h(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?= asset_url('css/table_and_form.css'); ?>">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content-wrapper">
                <h1 class="page-title">Pengaturan Profil Akun</h1>
                
                <?php include __DIR__ . '/../includes/flash.php'; ?>

                <!-- Card Profil -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: 1px solid rgba(0,0,0,0.08); padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 600px; margin: 20px auto;">
                    
                    <!-- Info Profil Badge -->
                    <div style="text-align: center; margin-bottom: 25px;">
                        <div style="width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 12px auto; font-weight: bold;">
                            <?= strtoupper(substr($current_admin['nama_admin'], 0, 1)); ?>
                        </div>
                        <h2 style="margin: 0 0 5px 0; color: #fff; font-size: 22px;"><?= h($current_admin['nama_admin']); ?></h2>
                        <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; background: #eef2ff; color: #4338ca;">
                            <?= $current_admin['role'] === 'superadmin' ? '🔐 Superadmin' : ($current_admin['role'] === 'admin' ? '👤 Admin' : '📋 Staff'); ?>
                        </span>
                    </div>

                    <!-- Form Edit Profil -->
                    <form method="POST" action="">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="edit_profile">
                        
                        <div class="form-group">
                            <label for="nama_admin" style="font-weight: 600; color: #fff;">Username Akun *</label>
                            <input type="text" id="nama_admin" name="nama_admin" 
                                   value="<?= h($current_admin['nama_admin']); ?>" 
                                   required style="width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px;">
                        </div>
                        
                        <div style="margin-top: 25px; padding: 20px; border-radius: 8px;">
                            <p style="margin: 0 0 15px 0; color: #fff; font-size: 13px; font-weight: 600;">
                                🔒 Ganti Password (Kosongkan bila tidak ingin mengubah)
                            </p>
                            
                            <div class="form-group">
                                <label for="password_baru" style="font-size: 13px; color: #fff;">Password Baru</label>
                                <input type="password" id="password_baru" name="password_baru" 
                                       placeholder="Minimal 6 karakter" 
                                       style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="confirm_password" style="font-size: 13px; color: #fff;">Konfirmasi Password Baru</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       placeholder="Ketik ulang password baru" 
                                       style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
                            <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                            <a href="<?= base_url('index.php'); ?>" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
