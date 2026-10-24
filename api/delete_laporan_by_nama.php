<?php
// ===================================================
// api/delete_laporan_by_nama.php
// Endpoint Hapus Entri Laporan Terkait by Nama Barang
// ===================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$nama_barang = trim($_POST['nama_barang'] ?? '');

if (empty($nama_barang)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter nama_barang diperlukan']);
    exit;
}

// Cek apakah tabel laporan_barang ada
$check_table = $conn->query("SHOW TABLES LIKE 'laporan_barang'");
if ($check_table && $check_table->num_rows > 0) {
    $stmt = $conn->prepare("DELETE FROM laporan_barang WHERE nama_barang = ?");
    if ($stmt) {
        $stmt->bind_param('s', $nama_barang);
        $stmt->execute();
        $stmt->close();
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Entri laporan berhasil dibersihkan'
]);