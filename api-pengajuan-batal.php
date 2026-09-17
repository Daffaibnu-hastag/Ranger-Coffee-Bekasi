<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

/** @var mysqli $conn */
global $conn;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$kode   = mysqli_real_escape_string($conn, trim($_POST['kode'] ?? ''));
$alasan = mysqli_real_escape_string($conn, trim($_POST['alasan'] ?? ''));

if (empty($kode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode pesanan kosong']);
    exit;
}

if (empty($alasan)) {
    echo json_encode(['status' => 'error', 'message' => 'Alasan pembatalan wajib diisi']);
    exit;
}

// Cek pesanan di database
$check = query("SELECT * FROM pesanan WHERE kode_pesanan = '$kode' LIMIT 1");

if (empty($check)) {
    echo json_encode(['status' => 'error', 'message' => 'Pesanan tidak ditemukan']);
    exit;
}

$pesanan = $check[0];
$st = strtolower(trim($pesanan['status']));

// Batasi pembatalan hanya jika status masih pending atau diproses
if ($st === 'selesai' || $st === 'dibatalkan') {
    echo json_encode(['status' => 'error', 'message' => 'Pesanan sudah selesai atau dibatalkan, tidak dapat diajukan batal']);
    exit;
}

// Update status menjadi 'batal_pending' dan simpan alasan dari pelanggan
$update = mysqli_query($conn, "UPDATE pesanan SET status = 'batal_pending', alasan_batal_pelanggan = '$alasan' WHERE kode_pesanan = '$kode'");

if ($update) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Pengajuan pembatalan telah dikirim ke admin. Mohon tunggu konfirmasi.'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database']);
}
exit;