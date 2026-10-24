<?php
// ===================================================
// database/setup.php
// Script Inisialisasi Database & Demo Data Seeder (CLI & Web)
// ===================================================

require_once __DIR__ . '/../config/app.php';

$is_cli = (php_sapi_name() === 'cli');

$hostname = env('DB_HOST', '127.0.0.1');
$port     = (int)env('DB_PORT', 3306);
$username = env('DB_USER', 'root');
$password = env('DB_PASS', 'test');
$database = env('DB_NAME', 'sibarang');

$log_messages = [];

function log_msg($msg, $is_cli) {
    global $log_messages;
    $log_messages[] = $msg;
    if ($is_cli) {
        echo strip_tags($msg) . "\n";
    }
}

// 1. Matikan strict exception sementara untuk penanganan error yang ramah
mysqli_report(MYSQLI_REPORT_OFF);

try {
    $conn = @mysqli_connect($hostname, $username, $password, '', $port);
} catch (Throwable $e) {
    $conn = false;
}

if (!$conn) {
    $err = "❌ Gagal terhubung ke MySQL Server ($hostname:$port) dengan user '$username': " . mysqli_connect_error();
    if ($is_cli) {
        die($err . "\n");
    } else {
        die("<div style='font-family:sans-serif;padding:20px;background:#fee;color:#c00;max-width:600px;margin:40px auto;border-radius:8px;'>$err</div>");
    }
}

// 2. Buat database jika belum ada
$create_db_sql = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
if (mysqli_query($conn, $create_db_sql)) {
    log_msg("✅ Database `<strong>$database</strong>` berhasil disiapkan.", $is_cli);
} else {
    log_msg("❌ Gagal membuat database: " . mysqli_error($conn), $is_cli);
}

// 3. Pilih database
mysqli_select_db($conn, $database);
mysqli_set_charset($conn, "utf8mb4");

// 4. Eksekusi Schema DDL
$schema_file = __DIR__ . '/schema.sql';
if (file_exists($schema_file)) {
    $schema_sql = file_get_contents($schema_file);
    if (mysqli_multi_query($conn, $schema_sql)) {
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        log_msg("✅ Skema tabel DDL (<code>schema.sql</code>) berhasil diimpor.", $is_cli);
    } else {
        log_msg("❌ Gagal mengeksekusi schema.sql: " . mysqli_error($conn), $is_cli);
    }
} else {
    log_msg("❌ File schema.sql tidak ditemukan.", $is_cli);
}

// 5. Eksekusi Seeders
$seeders_file = __DIR__ . '/seeders.sql';
if (file_exists($seeders_file)) {
    $seeders_sql = file_get_contents($seeders_file);
    if (mysqli_multi_query($conn, $seeders_sql)) {
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        log_msg("✅ Data demo awal (<code>seeders.sql</code>) berhasil diisi.", $is_cli);
    } else {
        log_msg("❌ Gagal mengeksekusi seeders.sql: " . mysqli_error($conn), $is_cli);
    }
} else {
    log_msg("❌ File seeders.sql tidak ditemukan.", $is_cli);
}

mysqli_close($conn);

if (!$is_cli):
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inisialisasi Database - <?= h(APP_NAME); ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px 20px; line-height: 1.6; }
        .setup-card { max-width: 650px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); border: 1px solid #334155; }
        h1 { margin-top: 0; font-size: 24px; color: #38bdf8; display: flex; align-items: center; gap: 10px; }
        .log-box { background: #0f172a; border-radius: 8px; padding: 15px; margin: 20px 0; border: 1px solid #334155; font-size: 14px; }
        .log-item { margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
        th, td { padding: 10px 12px; border: 1px solid #334155; text-align: left; }
        th { background: #334155; color: #38bdf8; }
        .btn { display: inline-block; background: #38bdf8; color: #0f172a; font-weight: bold; padding: 12px 24px; border-radius: 6px; text-decoration: none; margin-top: 15px; }
        .btn:hover { background: #0ea5e9; }
    </style>
</head>
<body>
    <div class="setup-card">
        <h1><span>📦</span> Inisialisasi Database SI-BARANG</h1>
        <p>Proses instalasi skema dan pengisian data demo telah selesai dijalankan.</p>

        <div class="log-box">
            <?php foreach ($log_messages as $m): ?>
                <div class="log-item"><?= $m; ?></div>
            <?php endforeach; ?>
        </div>

        <h3 style="color: #f1f5f9; margin-bottom: 8px;">🔑 Kredensial Akun Demo:</h3>
        <table>
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Hak Akses</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span style="color: #f87171; font-weight: bold;">🔐 Superadmin</span></td>
                    <td><code>superadmin</code></td>
                    <td><code>superadmin123</code></td>
                    <td>Akses penuh seluruh modul + kelola akun superadmin</td>
                </tr>
                <tr>
                    <td><span style="color: #60a5fa; font-weight: bold;">👤 Admin</span></td>
                    <td><code>admin</code></td>
                    <td><code>admin123</code></td>
                    <td>Kelola master data, laporan, dan tambah staff</td>
                </tr>
                <tr>
                    <td><span style="color: #94a3b8; font-weight: bold;">📋 Staff</span></td>
                    <td><code>staff</code></td>
                    <td><code>staff123</code></td>
                    <td>Input barang masuk, barang keluar, & profil mandiri</td>
                </tr>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 25px;">
            <a href="<?= base_url('login.php'); ?>" class="btn">🚀 Buka Halaman Login Demo →</a>
        </div>
    </div>
</body>
</html>
<?php endif; ?>
