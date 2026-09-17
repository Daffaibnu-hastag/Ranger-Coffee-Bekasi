<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Header respon JSON
header('Content-Type: application/json');

// Cek Sesi Login Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'unauthorized', 'message' => 'Akses ditolak. Silakan login kembali.']);
    exit;
}

/** @var mysqli $conn */
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ==========================================
    // 1. TAMBAH MENU BARU
    // ==========================================
    if ($action === 'tambah_menu') {
        $nama_menu   = trim($_POST['nama_menu'] ?? '');
        $id_kategori = intval($_POST['id_kategori'] ?? 0);
        $harga       = floatval($_POST['harga'] ?? 0);
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $status      = $_POST['status'] ?? 'Tersedia';
        $harga_modal = floatval($_POST['harga_modal'] ?? 0);

        if (empty($nama_menu) || $id_kategori <= 0 || $harga <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Lengkapi nama menu, kategori, dan harga!']);
            exit;
        }

        // --- VALIDASI DUPLIKAT NAMA MENU ---
        $nama_clean = mysqli_real_escape_string($conn, $nama_menu);
        $cek_duplikat = query("SELECT id_menu FROM menu WHERE LOWER(nama_menu) = LOWER('$nama_clean')");

        if (!empty($cek_duplikat)) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Menu "' . htmlspecialchars($nama_menu) . '" sudah ada di daftar!'
            ]);
            exit;
        }

        // Handle Upload Gambar Menu
        $nama_gambar = 'kopi.png';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $tmp_name   = $_FILES['gambar']['tmp_name'];
            $file_name  = $_FILES['gambar']['name'];
            $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed    = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed)) {
                $nama_gambar = 'menu_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $target_dir  = __DIR__ . '/../uploads/menu/';

                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                move_uploaded_file($tmp_name, $target_dir . $nama_gambar);
            }
        }

        $deskripsi_clean = mysqli_real_escape_string($conn, $deskripsi);
        $insert = mysqli_query($conn, "INSERT INTO menu (id_kategori, nama_menu, deskripsi, harga, harga_modal, status, gambar) VALUES ('$id_kategori', '$nama_clean', '$deskripsi_clean', '$harga', '$harga_modal', '$status', '$nama_gambar')");

        if ($insert) {
            echo json_encode(['status' => 'success', 'message' => 'Menu baru berhasil ditambahkan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan menu ke database.']);
        }
        exit;
    }

    // ==========================================
    // 2. EDIT MENU KOPI
    // ==========================================
    if ($action === 'edit_menu') {
        $id_menu     = intval($_POST['id_menu'] ?? 0);
        $nama_menu   = trim($_POST['nama_menu'] ?? '');
        $id_kategori = intval($_POST['id_kategori'] ?? 0);
        $harga       = floatval($_POST['harga'] ?? 0);
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $status      = $_POST['status'] ?? 'Tersedia';
        $harga_modal = floatval($_POST['harga_modal'] ?? 0);

        if ($id_menu <= 0 || empty($nama_menu)) {
            echo json_encode(['status' => 'error', 'message' => 'Data menu tidak valid!']);
            exit;
        }

        // --- VALIDASI DUPLIKAT NAMA MENU (KECUALI MENU INI) ---
        $nama_clean = mysqli_real_escape_string($conn, $nama_menu);
        $cek_duplikat = query("SELECT id_menu FROM menu WHERE LOWER(nama_menu) = LOWER('$nama_clean') AND id_menu != '$id_menu'");

        if (!empty($cek_duplikat)) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Nama menu "' . htmlspecialchars($nama_menu) . '" sudah dipakai menu lain!'
            ]);
            exit;
        }

        $deskripsi_clean = mysqli_real_escape_string($conn, $deskripsi);

        // Pengecekan Upload Gambar Baru (Jika Ada)
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $tmp_name   = $_FILES['gambar']['tmp_name'];
            $file_name  = $_FILES['gambar']['name'];
            $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed    = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed)) {
                $nama_gambar = 'menu_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $target_dir  = __DIR__ . '/../uploads/menu/';

                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                if (move_uploaded_file($tmp_name, $target_dir . $nama_gambar)) {
                    mysqli_query($conn, "UPDATE menu SET gambar = '$nama_gambar' WHERE id_menu = '$id_menu'");
                }
            }
        }

$update = mysqli_query($conn, "UPDATE menu SET 
                               id_kategori = '$id_kategori',
                               nama_menu   = '$nama_clean',
                               harga       = '$harga',
                               harga_modal = '$harga_modal',
                               deskripsi   = '$deskripsi_clean',
                               status      = '$status'
                               WHERE id_menu = '$id_menu'");
        if ($update) {
            echo json_encode(['status' => 'success', 'message' => 'Data menu berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui menu.']);
        }
        exit;
    }

    // ==========================================
    // 3. HAPUS MENU KOPI
    // ==========================================
    if ($action === 'hapus_menu') {
        $id_menu = intval($_POST['id_menu'] ?? 0);

        if ($id_menu <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID Menu tidak valid.']);
            exit;
        }

        $delete = mysqli_query($conn, "DELETE FROM menu WHERE id_menu = '$id_menu'");

        if ($delete) {
            echo json_encode(['status' => 'success', 'message' => 'Menu berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus menu.']);
        }
        exit;
    }

    // ==========================================
    // 4. TAMBAH KATEGORI BARU
    // ==========================================
    if ($action === 'tambah_kategori') {
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $icon          = trim($_POST['icon'] ?? 'fa-coffee');

        if (empty($nama_kategori)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama kategori tidak boleh kosong!']);
            exit;
        }

        // --- VALIDASI DUPLIKAT KATEGORI ---
        $kat_clean = mysqli_real_escape_string($conn, $nama_kategori);
        $cek_duplikat = query("SELECT id_kategori FROM kategori WHERE LOWER(nama_kategori) = LOWER('$kat_clean')");

        if (!empty($cek_duplikat)) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Kategori "' . htmlspecialchars($nama_kategori) . '" sudah ada!'
            ]);
            exit;
        }

        $icon_clean = mysqli_real_escape_string($conn, $icon);
        $insert = mysqli_query($conn, "INSERT INTO kategori (nama_kategori, icon) VALUES ('$kat_clean', '$icon_clean')");

        if ($insert) {
            echo json_encode(['status' => 'success', 'message' => 'Kategori baru berhasil ditambahkan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan kategori.']);
        }
        exit;
    }

    // ==========================================
    // 5. EDIT KATEGORI
    // ==========================================
    if ($action === 'edit_kategori') {
        $id_kategori   = intval($_POST['id_kategori'] ?? 0);
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $icon          = trim($_POST['icon'] ?? 'fa-coffee');

        if ($id_kategori <= 0 || empty($nama_kategori)) {
            echo json_encode(['status' => 'error', 'message' => 'Data kategori tidak valid!']);
            exit;
        }

        // --- VALIDASI DUPLIKAT KATEGORI (KECUALI KATEGORI INI) ---
        $kat_clean = mysqli_real_escape_string($conn, $nama_kategori);
        $cek_duplikat = query("SELECT id_kategori FROM kategori WHERE LOWER(nama_kategori) = LOWER('$kat_clean') AND id_kategori != '$id_kategori'");

        if (!empty($cek_duplikat)) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Nama kategori "' . htmlspecialchars($nama_kategori) . '" sudah dipakai!'
            ]);
            exit;
        }

        $icon_clean = mysqli_real_escape_string($conn, $icon);
        $update = mysqli_query($conn, "UPDATE kategori SET nama_kategori = '$kat_clean', icon = '$icon_clean' WHERE id_kategori = '$id_kategori'");

        if ($update) {
            echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui kategori.']);
        }
        exit;
    }

    // ==========================================
    // 6. HAPUS KATEGORI
    // ==========================================
    if ($action === 'hapus_kategori') {
        $id_kategori = intval($_POST['id_kategori'] ?? 0);

        if ($id_kategori <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID Kategori tidak valid.']);
            exit;
        }

        // Hapus menu di dalam kategori ini dulu
        mysqli_query($conn, "DELETE FROM menu WHERE id_kategori = '$id_kategori'");
        $delete = mysqli_query($conn, "DELETE FROM kategori WHERE id_kategori = '$id_kategori'");

        if ($delete) {
            echo json_encode(['status' => 'success', 'message' => 'Kategori beserta menu di dalamnya berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus kategori.']);
        }
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid.']);
exit;
