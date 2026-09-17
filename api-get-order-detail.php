<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$kode = isset($_GET['kode']) ? trim($_GET['kode']) : '';

if (empty($kode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode pesanan tidak ditemukan.']);
    exit;
}

// 1. Ambil Data Pesanan
$pesanan = query("SELECT * FROM pesanan WHERE kode_pesanan = '$kode'");

if (empty($pesanan)) {
    echo json_encode(['status' => 'not_found', 'message' => 'Pesanan tidak ditemukan.']);
    exit;
}

$id_pesanan = $pesanan[0]['id_pesanan'];
$total = (float)$pesanan[0]['total_harga'];
$order_status = $pesanan[0]['status'];

// 2. Ambil Detail Item Pesanan (Join dengan Tabel Menu)
$items_query = query("SELECT dp.*, m.nama_menu, m.gambar 
                      FROM detail_pesanan dp 
                      LEFT JOIN menu m ON dp.id_menu = m.id_menu 
                      WHERE dp.id_pesanan = '$id_pesanan'");

$items = [];
foreach ($items_query as $it) {
    $items[] = [
        'id_menu' => (int)$it['id_menu'],
        'nama_menu' => $it['nama_menu'] ?? 'Menu Kopi',
        'jumlah' => (int)$it['jumlah'],
        'harga' => (float)$it['harga_satuan'],
        'level_gula' => $it['level_gula'] ?? 'Normal Sugar'
    ];
}

echo json_encode([
    'status' => 'success',
    'kode' => $kode,
    'total' => $total,
    'order_status' => $order_status,
    'items' => $items
]);
exit;