<?php
// ===================================================
// api/barang/delete_barang_masuk_by_id.php
// Endpoint Hapus Transaksi Barang Masuk by ID & Rollback Stok
// ===================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';

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

$idMasuk = isset($_POST['id_masuk']) ? (int)$_POST['id_masuk'] : 0;

if ($idMasuk <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter id_masuk tidak valid']);
    exit;
}

// Ambil data transaksi barang masuk
$stmt = $conn->prepare("SELECT * FROM barang_masuk WHERE id_masuk = ? LIMIT 1");
$stmt->bind_param('i', $idMasuk);
$stmt->execute();
$barangMasuk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$barangMasuk) {
    http_response_code(404);
    echo json_encode(['error' => 'Data barang masuk tidak ditemukan']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Kurangi stok di master barang (rollback)
    $stmtUpdate = $conn->prepare("UPDATE barang SET stok = stok - ? WHERE nama_barang = ?");
    $stmtUpdate->bind_param('is', $barangMasuk['jumlah_masuk'], $barangMasuk['nama_barang']);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    // 2. Hapus data transaksi
    $stmtDel = $conn->prepare("DELETE FROM barang_masuk WHERE id_masuk = ?");
    $stmtDel->bind_param('i', $idMasuk);
    $stmtDel->execute();
    $stmtDel->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Transaksi barang masuk berhasil dihapus dan stok dikurangi kembali."
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menghapus transaksi: ' . $e->getMessage()]);
}
