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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori    = (int)($_POST['id_kategori'] ?? 0);
    $nama_kategori  = mysqli_real_escape_string($conn, trim($_POST['nama_kategori'] ?? ''));
    $icon           = mysqli_real_escape_string($conn, trim($_POST['icon'] ?? 'fa-coffee'));

    if ($id_kategori <= 0 || empty($nama_kategori)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama kategori tidak boleh kosong!']);
        exit;
    }

    $query = "UPDATE kategori SET nama_kategori = '$nama_kategori', icon = '$icon' WHERE id_kategori = $id_kategori";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil diperbarui!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui kategori di database.']);
    }
    exit;
}