<?php
// ===================================================
// includes/flash.php
// Komponen render notifikasi flash session
// ===================================================

$flash_success = get_flash('success');
$flash_error   = get_flash('error');
$flash_warning = get_flash('warning');
$flash_info    = get_flash('info');

// Support legacy session success/error if any exists
if (!$flash_success && isset($_SESSION['success'])) {
    $flash_success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (!$flash_error && isset($_SESSION['error'])) {
    $flash_error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<?php if (!empty($flash_success)): ?>
    <div class="alert alert-success" style="padding: 12px 16px; margin-bottom: 20px; border-radius: 8px; background: rgba(39, 174, 96, 0.15); border: 1px solid #27ae60; color: #27ae60; display: flex; align-items: center; gap: 10px; font-weight: 500;">
        <span>✅</span>
        <div><?= h($flash_success); ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($flash_error)): ?>
    <div class="alert alert-danger" style="padding: 12px 16px; margin-bottom: 20px; border-radius: 8px; background: rgba(231, 76, 60, 0.15); border: 1px solid #e74c3c; color: #e74c3c; display: flex; align-items: center; gap: 10px; font-weight: 500;">
        <span>❌</span>
        <div><?= h($flash_error); ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($flash_warning)): ?>
    <div class="alert alert-warning" style="padding: 12px 16px; margin-bottom: 20px; border-radius: 8px; background: rgba(243, 156, 18, 0.15); border: 1px solid #f39c12; color: #f39c12; display: flex; align-items: center; gap: 10px; font-weight: 500;">
        <span>⚠️</span>
        <div><?= h($flash_warning); ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($flash_info)): ?>
    <div class="alert alert-info" style="padding: 12px 16px; margin-bottom: 20px; border-radius: 8px; background: rgba(52, 152, 219, 0.15); border: 1px solid #3498db; color: #3498db; display: flex; align-items: center; gap: 10px; font-weight: 500;">
        <span>ℹ️</span>
        <div><?= h($flash_info); ?></div>
    </div>
<?php endif; ?>
