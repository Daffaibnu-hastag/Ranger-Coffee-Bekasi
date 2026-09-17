<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['admin_login']) || $_SESSION['admin_login'] !== true) {
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

/** @var mysqli $conn */
global $conn;

// Ambil Batas Jam Shift Hari Ini (Jam 06.00 Pagi)
$start_shift = getStartOfShift();

// Query Statistik Dashboard Khusus Shift Hari Ini (>= Jam 06.00)
$total_pending  = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending' AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_diproses = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'diproses' AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_selesai  = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'selesai' AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_batal    = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'dibatalkan' AND created_at >= '$start_shift'")[0]['total'] ?? 0;

$res_omset   = query("SELECT SUM(total_harga) as omset FROM pesanan WHERE status = 'selesai' AND created_at >= '$start_shift'");
$total_omset = $res_omset[0]['omset'] ?? 0;

// Query List Pesanan Live Shift Hari Ini
$q_pending  = query("SELECT * FROM pesanan WHERE status = 'pending' AND created_at >= '$start_shift' ORDER BY id_pesanan DESC");
$q_diproses = query("SELECT * FROM pesanan WHERE status = 'diproses' AND created_at >= '$start_shift' ORDER BY id_pesanan DESC");
$q_selesai  = query("SELECT * FROM pesanan WHERE status = 'selesai' AND created_at >= '$start_shift' ORDER BY id_pesanan DESC");
$q_batal    = query("SELECT * FROM pesanan WHERE status = 'dibatalkan' AND created_at >= '$start_shift' ORDER BY id_pesanan DESC");

function formatPesananList($list) {
    $result = [];
    foreach ($list as $p) {
        $result[] = [
            'id_pesanan'        => $p['id_pesanan'],
            'kode_pesanan'      => $p['kode_pesanan'],
            'nama_pelanggan'    => htmlspecialchars($p['nama_pelanggan']),
            'no_hp'             => htmlspecialchars($p['no_hp']),
            'total_harga_rp'    => rupiah($p['total_harga']),
            'status'            => $p['status'],
            'bukti_transfer'    => $p['bukti_transfer'],
            'created_timestamp' => strtotime($p['created_at']),
            'formatted_date'    => date('d/m/Y H:i', strtotime($p['created_at']))
        ];
    }
    return $result;
}

echo json_encode([
    'status'           => 'success',
    'shift_start'      => $start_shift,
    'total_pending'    => (int)$total_pending,
    'total_diproses'   => (int)$total_diproses,
    'total_selesai'    => (int)$total_selesai,
    'total_batal'      => (int)$total_batal,
    'total_omset'      => rupiah($total_omset),
    'pesanan_pending'  => formatPesananList($q_pending),
    'pesanan_diproses' => formatPesananList($q_diproses),
    'pesanan_selesai'  => formatPesananList($q_selesai),
    'pesanan_batal'    => formatPesananList($q_batal)
]);
exit;