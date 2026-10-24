<?php
// ===================================================
// api/get_barang_transactions.php
// Helper JSON untuk memeriksa riwayat transaksi barang
// ===================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Mode 1: Fetch barang_keluar by ID
if (isset($_GET['id']) && !isset($_GET['type'])) {
    $id = (int)$_GET['id'];
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Parameter ID tidak valid']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT * FROM barang_keluar WHERE id_keluar = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$data) {
        http_response_code(404);
        echo json_encode(['error' => 'Data transaksi keluar tidak ditemukan']);
        exit;
    }
    
    $data['tanggal_keluar'] = date('d/m/Y', strtotime($data['tanggal_keluar']));
    echo json_encode($data);
    exit;
}

// Mode 2: Fetch barang_masuk by ID
if (isset($_GET['id']) && isset($_GET['type']) && $_GET['type'] === 'masuk') {
    $id = (int)$_GET['id'];
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Parameter ID tidak valid']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT * FROM barang_masuk WHERE id_masuk = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$data) {
        http_response_code(404);
        echo json_encode(['error' => 'Data transaksi masuk tidak ditemukan']);
        exit;
    }
    
    $data['tanggal_masuk'] = date('d/m/Y', strtotime($data['tanggal_masuk']));
    echo json_encode($data);
    exit;
}

// Mode 3: Cek akumulasi transaksi by nama_barang
if (!isset($_GET['nama_barang'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter nama_barang diperlukan']);
    exit;
}

$nama_barang = trim($_GET['nama_barang']);

// Cek transaksi masuk
$stmt1 = $conn->prepare("SELECT COUNT(*) as count FROM barang_masuk WHERE nama_barang = ?");
$stmt1->bind_param('s', $nama_barang);
$stmt1->execute();
$count_masuk = (int)($stmt1->get_result()->fetch_assoc()['count'] ?? 0);
$stmt1->close();

// Cek transaksi keluar
$stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM barang_keluar WHERE nama_barang = ?");
$stmt2->bind_param('s', $nama_barang);
$stmt2->execute();
$count_keluar = (int)($stmt2->get_result()->fetch_assoc()['count'] ?? 0);
$stmt2->close();

$total_transaksi = $count_masuk + $count_keluar;

echo json_encode([
    'success' => true,
    'nama_barang' => $nama_barang,
    'count_masuk' => $count_masuk,
    'count_keluar' => $count_keluar,
    'total' => $total_transaksi
]);
