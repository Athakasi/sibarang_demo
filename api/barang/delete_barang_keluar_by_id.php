<?php
// ===================================================
// api/barang/delete_barang_keluar_by_id.php
// Endpoint Hapus Transaksi Barang Keluar by ID & Rollback Stok
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

$idKeluar = isset($_POST['id_keluar']) ? (int)$_POST['id_keluar'] : 0;

if ($idKeluar <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter id_keluar tidak valid']);
    exit;
}

// Ambil data transaksi barang keluar
$stmt = $conn->prepare("SELECT * FROM barang_keluar WHERE id_keluar = ? LIMIT 1");
$stmt->bind_param('i', $idKeluar);
$stmt->execute();
$barangKeluar = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$barangKeluar) {
    http_response_code(404);
    echo json_encode(['error' => 'Data barang keluar tidak ditemukan']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Tambah kembali stok di master barang (rollback)
    $stmtUpdate = $conn->prepare("UPDATE barang SET stok = stok + ? WHERE nama_barang = ?");
    $stmtUpdate->bind_param('is', $barangKeluar['jumlah_keluar'], $barangKeluar['nama_barang']);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    // 2. Hapus data transaksi
    $stmtDel = $conn->prepare("DELETE FROM barang_keluar WHERE id_keluar = ?");
    $stmtDel->bind_param('i', $idKeluar);
    $stmtDel->execute();
    $stmtDel->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Transaksi barang keluar berhasil dihapus dan stok dikembalikan."
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menghapus transaksi: ' . $e->getMessage()]);
}
