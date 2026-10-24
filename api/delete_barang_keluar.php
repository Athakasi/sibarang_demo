<?php
// ===================================================
// api/delete_barang_keluar.php
// Endpoint Hapus Seluruh Transaksi Barang Keluar by Nama Barang
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

$stmt = $conn->prepare("DELETE FROM barang_keluar WHERE nama_barang = ?");
if ($stmt) {
    $stmt->bind_param('s', $nama_barang);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => "Berhasil menghapus $affected riwayat barang keluar",
        'deleted_rows' => $affected
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
}