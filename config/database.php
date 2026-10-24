<?php
// ===================================================
// config/database.php
// File Konfigurasi Koneksi Database MySQL
// ===================================================

require_once __DIR__ . '/app.php';

$hostname = env('DB_HOST', '127.0.0.1');
$port     = (int)env('DB_PORT', 3306);
$username = env('DB_USER', 'root');
$password = env('DB_PASS', 'test');
$database = env('DB_NAME', 'sibarang');

// Koneksi MySQL
$conn = @mysqli_connect($hostname, $username, $password, $database, $port);

// Cek koneksi
if (!$conn) {
    if (APP_DEBUG) {
        die("<div style='font-family:sans-serif;padding:20px;background:#fee;color:#c00;border:1px solid #fcc;border-radius:8px;max-width:600px;margin:40px auto;'>"
            . "<h3>❌ Koneksi Database Gagal</h3>"
            . "<p><strong>Pesan:</strong> " . htmlspecialchars(mysqli_connect_error()) . "</p>"
            . "<p>Pastikan database MySQL aktif dan kredensial di file <code>.env</code> sudah sesuai.</p>"
            . "</div>");
    } else {
        die("Terjadi gangguan koneksi database. Silakan coba beberapa saat lagi.");
    }
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Fungsi helper untuk query
function executeQuery($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        if (APP_DEBUG) {
            die("Query gagal: " . mysqli_error($conn) . "<br>SQL: " . htmlspecialchars($sql));
        } else {
            die("Terjadi kesalahan saat memproses data.");
        }
    }
    return $result;
}

// Fungsi untuk sanitize input
function sanitize($conn, $input) {
    if ($input === null) return '';
    return mysqli_real_escape_string($conn, trim((string)$input));
}

// Fungsi untuk fetch single row
function fetchOne($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

// Fungsi untuk fetch all rows
function fetchAll($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $data = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

// Fungsi untuk insert/update/delete
function executeInsertUpdateDelete($conn, $sql) {
    return (bool)mysqli_query($conn, $sql);
}
