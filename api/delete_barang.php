<?php
// ===================================================
// api/delete_barang.php
// Endpoint Hapus Master Barang
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

$id_barang = isset($_POST['id_barang']) ? (int)$_POST['id_barang'] : 0;

if ($id_barang <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID barang tidak valid']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
if ($stmt) {
    $stmt->bind_param('i', $id_barang);
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Barang master berhasil dihapus'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menghapus barang: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Query error: ' . $conn->error]);
}