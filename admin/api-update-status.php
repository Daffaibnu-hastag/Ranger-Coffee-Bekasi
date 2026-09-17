<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi admin tidak valid. Silakan login kembali.']);
    exit;
}

/** @var mysqli $conn */
global $conn;

$id_pesanan = (int)($_POST['id_pesanan'] ?? $_GET['id_pesanan'] ?? 0);
$kode       = mysqli_real_escape_string($conn, $_POST['kode'] ?? $_GET['kode'] ?? '');
$status     = strtolower(trim(mysqli_real_escape_string($conn, $_POST['status'] ?? $_GET['status'] ?? '')));
$alasan     = mysqli_real_escape_string($conn, $_POST['alasan'] ?? '');

if (($id_pesanan === 0 && empty($kode)) || empty($status)) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter pesanan dan status wajib diisi.']);
    exit;
}

// Validasi status yang diperbolehkan
$allowed_status = ['pending', 'diproses', 'siap_ambil', 'selesai', 'dibatalkan'];
if (!in_array($status, $allowed_status)) {
    echo json_encode(['status' => 'error', 'message' => 'Status tidak valid.']);
    exit;
}

// Cek kolom alasan yang tersedia di database
$columns_res = mysqli_query($conn, "SHOW COLUMNS FROM pesanan");
$existing_cols = [];
if ($columns_res) {
    while ($col = mysqli_fetch_assoc($columns_res)) {
        $existing_cols[] = $col['Field'];
    }
}

$alasan_col = null;
foreach (['alasan_batal', 'alasan', 'alasan_batal_admin'] as $c) {
    if (in_array($c, $existing_cols)) {
        $alasan_col = $c;
        break;
    }
}

// Susun Query SQL berdasarkan ID / Kode
$where_clause = $id_pesanan > 0 ? "id_pesanan = $id_pesanan" : "kode_pesanan = '$kode'";

if ($status === 'dibatalkan' && $alasan_col) {
    $sql = "UPDATE pesanan SET status = '$status', {$alasan_col} = '$alasan' WHERE $where_clause";
} else {
    $sql = "UPDATE pesanan SET status = '$status' WHERE $where_clause";
}

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        'status'  => 'success', 
        'message' => 'Status pesanan berhasil diubah menjadi ' . strtoupper(str_replace('_', ' ', $status))
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database: ' . mysqli_error($conn)]);
}
exit;