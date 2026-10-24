<?php
// ===================================================
// includes/auth_guard.php
// Middleware Keamanan Autentikasi & Otorisasi Role
// ===================================================

require_once __DIR__ . '/../config/app.php';

// Pastikan user sudah login
if (!isset($_SESSION['admin_id'])) {
    $script_name = basename($_SERVER['SCRIPT_NAME'] ?? '');
    // Jika mengakses halaman dalam (pages/...) tanpa login, simpan redirect dan beri notifikasi
    if ($script_name !== 'index.php') {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        set_flash('error', '⚠️ Silakan login terlebih dahulu untuk mengakses halaman ini.');
    }
    redirect('login.php');
    exit;
}

/**
 * Validasi role user yang berhak mengakses halaman
 * @param array|string $allowed_roles Contoh: ['superadmin', 'admin'] atau 'superadmin'
 */
function check_role($allowed_roles) {
    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    $current_role = $_SESSION['admin_role'] ?? 'staff';

    if (!in_array($current_role, $allowed_roles, true)) {
        set_flash('error', '⛔ Anda tidak memiliki hak akses (permission) ke halaman tersebut.');
        redirect('index.php');
        exit;
    }
}
