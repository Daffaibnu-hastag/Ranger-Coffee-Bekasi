<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak!']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'open') {
    // Paksa Buka
    mysqli_query($conn, "UPDATE pengaturan SET manual_override = 1, manual_status = 1");
    echo json_encode(['status' => 'success', 'message' => 'Toko dipaksa BUKA secara manual!']);
} elseif ($action === 'close') {
    // Paksa Tutup
    mysqli_query($conn, "UPDATE pengaturan SET manual_override = 1, manual_status = 0");
    echo json_encode(['status' => 'success', 'message' => 'Toko dipaksa TUTUP secara manual!']);
} elseif ($action === 'auto') {
    // Kembali ke Mode Jadwal Otomatis
    mysqli_query($conn, "UPDATE pengaturan SET manual_override = 0");
    echo json_encode(['status' => 'success', 'message' => 'Mode Otomatis (Jadwal) diaktifkan kembali!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Action tidak valid!']);
}
exit;