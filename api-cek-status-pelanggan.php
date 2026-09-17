<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

/** @var mysqli $conn */
global $conn;

$kode = mysqli_real_escape_string($conn, trim($_GET['kode'] ?? ''));

if (empty($kode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode pesanan kosong']);
    exit;
}

// 1. Deteksi kolom alasan di database
$columns_res = mysqli_query($conn, "SHOW COLUMNS FROM pesanan");
$existing_cols = [];
if ($columns_res) {
    while ($col = mysqli_fetch_assoc($columns_res)) {
        $existing_cols[] = $col['Field'];
    }
}

$possible_alasan_cols = ['alasan_batal', 'alasan_batal_admin', 'alasan', 'alasan_batal_pelanggan'];
$selected_alasan_col = "''";

foreach ($possible_alasan_cols as $col_name) {
    if (in_array($col_name, $existing_cols)) {
        $selected_alasan_col = "`$col_name`";
        break;
    }
}

// 2. Query Data
$sql = "SELECT kode_pesanan, nama_pelanggan, status, total_harga, created_at, {$selected_alasan_col} AS alasan_outlet 
        FROM pesanan 
        WHERE kode_pesanan = '$kode' 
        LIMIT 1";

$pesanan = query($sql);

if (empty($pesanan)) {
    echo json_encode(['status' => 'not_found', 'message' => 'Pesanan tidak ditemukan']);
    exit;
}

$p = $pesanan[0];
$status_lc = strtolower(trim($p['status'] ?? 'pending'));
$alasan_raw = trim($p['alasan_outlet'] ?? '');
$alasan_final = !empty($alasan_raw) ? $alasan_raw : 'Pesanan dibatalkan oleh pihak outlet.';

// Output JSON kompatibel lengkap untuk 4 status normal
echo json_encode([
    'status'         => 'success',
    'kode_pesanan'   => $p['kode_pesanan'],
    'nama'           => htmlspecialchars($p['nama_pelanggan'] ?? 'Pelanggan'),
    'status_pesanan' => $status_lc,
    'status_order'   => $status_lc,
    'order_status'   => $status_lc,
    'alasan_batal'   => $alasan_final,
    'total_rp'       => function_exists('rupiah') ? rupiah($p['total_harga']) : 'Rp ' . number_format($p['total_harga'], 0, ',', '.'),
    'waktu'          => date('H:i', strtotime($p['created_at']))
]);
exit;