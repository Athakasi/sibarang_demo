<?php
// ===================================================
// includes/sidebar.php
// Sidebar Menu Navigation Terpusat & Responsif
// ===================================================

require_once __DIR__ . '/../config/app.php';

$current_script = basename($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF'] ?? '');

// Helper untuk memeriksa menu aktif
function isMenuActive($page_name, $current_script) {
    if (is_array($page_name)) {
        return in_array($current_script, $page_name, true) ? 'active' : '';
    }
    return ($current_script === $page_name) ? 'active' : '';
}

$is_laporan_active = in_array($current_script, ['laporan.php', 'print_barang.php', 'print_masuk.php', 'print_keluar.php', 'print_laporan.php'], true);
$user_role = $_SESSION['admin_role'] ?? 'staff';
$user_name = $_SESSION['admin_name'] ?? 'Admin';
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?= base_url('index.php'); ?>" style="text-decoration: none;">
            <h1 style="color: white; font-size: 22px; margin: 0; display: flex; align-items: center; gap: 8px;" class="sidebar-title">
                <span>📦</span> SI-BARANG
            </h1>
        </a>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= base_url('index.php'); ?>" class="nav-item <?= isMenuActive('index.php', $current_script); ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-text">Dashboard</span>
        </a>

        <a href="<?= base_url('pages/kategori_barang.php'); ?>" class="nav-item <?= isMenuActive('kategori_barang.php', $current_script); ?>">
            <span class="nav-icon">🗂️</span>
            <span class="nav-text">Kategori Barang</span>
        </a>

        <a href="<?= base_url('pages/daftar_barang.php'); ?>" class="nav-item <?= isMenuActive('daftar_barang.php', $current_script); ?>">
            <span class="nav-icon">📦</span>
            <span class="nav-text">Daftar Barang</span>
        </a>

        <a href="<?= base_url('pages/barang_masuk.php'); ?>" class="nav-item <?= isMenuActive('barang_masuk.php', $current_script); ?>">
            <span class="nav-icon">📥</span>
            <span class="nav-text">Barang Masuk</span>
        </a>

        <a href="<?= base_url('pages/barang_keluar.php'); ?>" class="nav-item <?= isMenuActive('barang_keluar.php', $current_script); ?>">
            <span class="nav-icon">📤</span>
            <span class="nav-text">Barang Keluar</span>
        </a>

        <div class="nav-item has-submenu <?= $is_laporan_active ? 'active' : ''; ?>">
            <span class="nav-icon">📋</span>
            <span class="nav-text">Laporan</span>
            <span class="submenu-arrow">▼</span>
        </div>
        <div class="submenu <?= $is_laporan_active ? 'show' : ''; ?>">
            <a href="<?= base_url('pages/laporan.php'); ?>" class="nav-item <?= isMenuActive('laporan.php', $current_script); ?>">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Laporan Rekap</span>
            </a>
            <a href="<?= base_url('pages/print_barang.php'); ?>" class="nav-item <?= isMenuActive('print_barang.php', $current_script); ?>" target="_blank">
                <span class="nav-icon">📑</span>
                <span class="nav-text">Cetak Daftar Barang</span>
            </a>
        </div>

        <?php if (in_array($user_role, ['superadmin', 'admin'], true)): ?>
            <a href="<?= base_url('pages/admin.php'); ?>" class="nav-item <?= isMenuActive('admin.php', $current_script); ?>">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Manajemen Admin</span>
            </a>
        <?php endif; ?>

        <?php if ($user_role === 'staff'): ?>
            <a href="<?= base_url('pages/profile.php'); ?>" class="nav-item <?= isMenuActive('profile.php', $current_script); ?>">
                <span class="nav-icon">👤</span>
                <span class="nav-text">Profil Saya</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar" style="background: rgba(255,255,255,0.2); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                <?= $user_role === 'superadmin' ? '🔐' : ($user_role === 'admin' ? '👤' : '📋'); ?>
            </div>
            <div>
                <p class="user-name" style="margin: 0; font-weight: 600;"><?= h($user_name); ?></p>
                <small style="color: rgba(255,255,255,0.7); text-transform: capitalize;"><?= h($user_role); ?></small>
            </div>
        </div>
        <a href="<?= base_url('logout.php'); ?>" class="btn-logout" title="Keluar">
            <span>🚪 Logout</span>
        </a>
    </div>
</aside>

<script>
    // Submenu Toggle Navigation
    document.addEventListener('DOMContentLoaded', function() {
        const submenuParents = document.querySelectorAll('.has-submenu');
        submenuParents.forEach(function(item) {
            const submenu = item.nextElementSibling;
            item.addEventListener('click', function(e) {
                e.preventDefault();
                submenu.classList.toggle('show');
                item.classList.toggle('open');
            });
        });
    });
</script>
<script src="<?= asset_url('js/table-sort.js'); ?>" defer></script>