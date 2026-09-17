<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silakan login admin terlebih dahulu.']);
    exit;
}

$id_pesanan = isset($_POST['id_pesanan']) ? intval($_POST['id_pesanan']) : 0;
$alasan     = isset($_POST['alasan']) ? trim($_POST['alasan']) : '';

if ($id_pesanan <= 0 || empty($alasan)) {
    echo json_encode(['status' => 'error', 'message' => 'ID Pesanan dan alasan pembatalan wajib diisi!']);
    exit;
}

/** @var mysqli $conn */
global $conn;

$alasan_clean = mysqli_real_escape_string($conn, $alasan);

// Update status pesanan & simpan alasan pembatalan
$query = "UPDATE pesanan SET status = 'dibatalkan', alasan_batal = '$alasan_clean' WHERE id_pesanan = $id_pesanan";

if (mysqli_query($conn, $query)) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Pesanan berhasil dibatalkan dan alasan tersimpan.'
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal memperbarui database: ' . mysqli_error($conn)
    ]);
}