<?php
// ===================================================
// login.php
// Halaman Login Admin SI-BARANG (dengan Quick Login & Reset Demo)
// ===================================================

require_once __DIR__ . '/config/database.php';

// Jika sudah login, langsung redirect ke dashboard
if (isset($_SESSION['admin_id'])) {
    redirect('index.php');
}

// 1. Handler Reset Database Demo dari Halaman Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_demo') {
    validate_csrf();
    
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
    redirect('login.php');
}

$error = '';
// 2. Handler Form Login Reguler / Quick Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'login')) {
    validate_csrf();
    
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        // Query admin dengan prepared statement
        $stmt = $conn->prepare("SELECT id_admin, nama_admin, password, role FROM admin WHERE nama_admin = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            $stmt->close();
            
            $login_success = false;
            
            if ($admin) {
                // Cek password: coba password_verify dahulu, jika gagal cek md5 untuk legacy hash
                if (password_verify($password, $admin['password'])) {
                    $login_success = true;
                } elseif (md5($password) === $admin['password'] || $password === $admin['password']) {
                    $login_success = true;
                    // Auto-rehash password ke standard bcrypt yang aman
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param('si', $new_hash, $admin['id_admin']);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }
            }
            
            if ($login_success) {
                // Set session
                $_SESSION['admin_id']   = $admin['id_admin'];
                $_SESSION['admin_name'] = $admin['nama_admin'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                $_SESSION['login_time'] = time();
                
                // Redirect ke URL tujuan atau dashboard
                $redirect_to = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                set_flash('success', 'Selamat datang kembali, ' . $admin['nama_admin'] . '!');
                redirect($redirect_to);
            } else {
                $error = 'Username atau password tidak sesuai!';
            }
        } else {
            $error = 'Terjadi kesalahan sistem saat memproses login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= h(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/login.css'); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div style="font-size: 38px; margin-bottom: 8px;">📦</div>
            <h1><?= h(APP_NAME); ?></h1>
            <p>Sistem Informasi Inventaris Barang</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                ⚠️ <?= h($error); ?>
            </div>
        <?php endif; ?>

        <?php require_once __DIR__ . '/includes/flash.php'; ?>

        <form id="loginForm" method="POST" action="">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?= h($_POST['username'] ?? ''); ?>"
                    required 
                    placeholder="Masukkan username"
                    autocomplete="username"
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                >
            </div>
            
            <button type="submit" class="btn-login">Masuk ke Sistem</button>

            <!-- Quick Demo Login & Reset Box -->
            <div style="margin-top: 24px; padding: 16px; background: rgba(102, 126, 234, 0.08); border: 1px dashed #667eea; border-radius: 8px; text-align: center;">
                <p style="margin: 0 0 10px 0; font-size: 13px; color: #555; font-weight: 600;">
                    💡 Fitur Demo Aplikasi:
                </p>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <button type="button" class="btn" style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'" onclick="quickLoginAdmin()">
                        <span>⚡</span> Login sebagai Admin (Otomatis)
                    </button>
                    <button type="button" class="btn" style="width: 100%; background: transparent; color: #e74c3c; border: 1px solid #e74c3c; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='rgba(231, 76, 60, 0.08)'" onmouseout="this.style.background='transparent'" onclick="confirmResetDemo()">
                        <span>🔄</span> Reset Database ke Kondisi Awal
                    </button>
                </div>
            </div>
        </form>

        <!-- Hidden Form untuk Eksekusi Reset Demo -->
        <form id="resetDemoForm" method="POST" action="" style="display: none;">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="reset_demo">
        </form>
    </div>

    <script>
        function quickLoginAdmin() {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
            document.getElementById('loginForm').submit();
        }

        function confirmResetDemo() {
            if (confirm("Apakah Anda yakin ingin mereset database ke data demo awal?\n\nSemua master barang, kategori, riwayat transaksi, dan akun default akan dikembalikan seperti semula.")) {
                document.getElementById('resetDemoForm').submit();
            }
        }
    </script>
</body>
</html>
