<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

/** @var mysqli $conn */
global $conn;

// Pastikan function getStartOfShift() ada di includes/functions.php
$start_shift = function_exists('getStartOfShift') ? getStartOfShift() : date('Y-m-d 06:00:00');

// 1. HITUNG STATISTIK CARD (Masukkan 'batal_pending' ke hitungan pending)
$total_pending  = query("SELECT COUNT(*) as total FROM pesanan WHERE status IN ('pending', 'batal_pending') AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_diproses = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'diproses' AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_selesai  = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'selesai' AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_batal    = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'dibatalkan' AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_omset    = query("SELECT SUM(total_harga) as omset FROM pesanan WHERE status = 'selesai' AND created_at >= '$start_shift'")[0]['omset'] ?? 0;

// 2. AMBIL LIST DATA PESANAN REALTIME
// Filter IN ('pending', 'batal_pending') agar pesanan minta batal tetap masuk di TAB PENDING
$pesanan_pending  = query("SELECT * FROM pesanan WHERE status IN ('pending', 'batal_pending') AND created_at >= '$start_shift' ORDER BY id_pesanan DESC");
$pesanan_diproses = query("SELECT * FROM pesanan WHERE status = 'diproses' AND created_at >= '$start_shift' ORDER BY id_pesanan DESC");
$pesanan_selesai  = query("SELECT * FROM pesanan WHERE status = 'selesai' AND created_at >= '$start_shift' ORDER BY id_pesanan DESC");
$pesanan_batal    = query("SELECT * FROM pesanan WHERE status = 'dibatalkan' AND created_at >= '$start_shift' ORDER BY id_pesanan DESC");

// 3. FORMAT DATA UNTUK JAVASCRIPT
function formatPesanan($list) {
    $arr = [];
    if (!empty($list)) {
        foreach ($list as $row) {
            $row['formatted_date'] = date('H:i', strtotime($row['created_at'])) . ' WIB';
            $row['total_harga_rp'] = rupiah($row['total_harga']);
            $arr[] = $row;
        }
    }
    return $arr;
}

echo json_encode([
    'status'           => 'success',
    'total_pending'    => (int)$total_pending,
    'total_diproses'   => (int)$total_diproses,
    'total_selesai'    => (int)$total_selesai,
    'total_batal'      => (int)$total_batal,
    'total_omset'      => rupiah($total_omset),
    'pesanan_pending'  => formatPesanan($pesanan_pending),
    'pesanan_diproses' => formatPesanan($pesanan_diproses),
    'pesanan_selesai'  => formatPesanan($pesanan_selesai),
    'pesanan_batal'    => formatPesanan($pesanan_batal)
]);
exit;