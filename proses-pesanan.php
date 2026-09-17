<?php
// Mencegah output HTML error mentah yang merusak respon JSON
ob_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set header ke JSON
header('Content-Type: application/json; charset=utf-8');

// Fungsi pembantu untuk selalu mengirimkan JSON bersih
function sendJsonResponse($data) {
    if (ob_get_length()) {
        ob_clean(); // Bersihkan output buffer jika ada teks/warning liar
    }
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['status' => 'error', 'message' => 'Method request tidak valid!']);
}

if (empty($_SESSION['keranjang'])) {
    sendJsonResponse(['status' => 'error', 'message' => 'Keranjang belanjaan kamu kosong!']);
}

/** @var mysqli $conn */
global $conn;

// Aktifkan pelaporan error MySQLi via Exception
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$nama_pelanggan = mysqli_real_escape_string($conn, trim($_POST['nama_pelanggan'] ?? ''));
$no_hp          = mysqli_real_escape_string($conn, trim($_POST['no_hp'] ?? ''));
$catatan        = mysqli_real_escape_string($conn, trim($_POST['catatan'] ?? ''));

if (empty($nama_pelanggan) || empty($no_hp)) {
    sendJsonResponse(['status' => 'error', 'message' => 'Nama dan No HP wajib diisi!']);
}

try {
    // 1. Hitung Total Bayar dari Database
    $total_harga = 0;
    foreach ($_SESSION['keranjang'] as $item) {
        $id_m = (int)$item['id_menu'];
        $m_data = query("SELECT harga FROM menu WHERE id_menu = $id_m");
        if (!empty($m_data)) {
            $total_harga += ($m_data[0]['harga'] * (int)$item['jumlah']);
        }
    }

    $kode_pesanan = 'RNG-' . date('YmdHis') . '-' . rand(100, 999);

    // 2. Mulai Transaksi Database (Atomic)
    mysqli_begin_transaction($conn);

    // Simpan Pesanan Utama ke Tabel `pesanan`
    $query_pesanan = "INSERT INTO pesanan (kode_pesanan, nama_pelanggan, no_hp, total_harga, status, catatan, created_at) 
                      VALUES ('$kode_pesanan', '$nama_pelanggan', '$no_hp', $total_harga, 'pending', '$catatan', NOW())";
    
    mysqli_query($conn, $query_pesanan);
    $id_pesanan = mysqli_insert_id($conn);

    // Cek jika AUTO_INCREMENT tidak mengembalikan ID
    if ($id_pesanan == 0) {
        throw new Exception("Kolom 'id_pesanan' di tabel 'pesanan' belum diatur sebagai AUTO_INCREMENT.");
    }

    // 3. Simpan Rincian Item ke Tabel `pesanan_detail`
    foreach ($_SESSION['keranjang'] as $item) {
        $id_m  = (int)$item['id_menu'];
        $gula  = mysqli_real_escape_string($conn, $item['level_gula'] ?? 'Normal Sugar');
        $qty   = (int)$item['jumlah'];
        
        $m_data = query("SELECT harga FROM menu WHERE id_menu = $id_m");
        $harga  = $m_data[0]['harga'] ?? 0;

        $q_detail = "INSERT INTO detail_pesanan (id_pesanan, id_menu, level_gula, jumlah, harga) 
                     VALUES ($id_pesanan, $id_m, '$gula', $qty, $harga)";
        
        mysqli_query($conn, $q_detail);
    }

    // Commit transaksi jika semua query sukses
    mysqli_commit($conn);

    // Bersihkan keranjang belanja setelah sukses
    $_SESSION['keranjang'] = [];

    // Response JSON Sukses
    sendJsonResponse([
        'status'       => 'success',
        'kode_pesanan' => $kode_pesanan
    ]);

} catch (mysqli_sql_exception $e) {
    // Rollback jika terjadi kesalahan database
    if (isset($conn) && $conn instanceof mysqli) {
        mysqli_rollback($conn);
    }

    // Tangkap error spesifik default value / auto increment
    if (strpos($e->getMessage(), "Field 'id_pesanan' doesn't have a default value") !== false) {
        $error_msg = "Gagal memproses: Kolom 'id_pesanan' pada tabel 'pesanan' belum AUTO_INCREMENT. Silakan jalankan: ALTER TABLE pesanan MODIFY id_pesanan INT AUTO_INCREMENT;";
    } elseif (strpos($e->getMessage(), "Field 'id_detail' doesn't have a default value") !== false) {
        $error_msg = "Gagal memproses: Kolom 'id_detail' pada tabel 'pesanan_detail' belum AUTO_INCREMENT. Silakan jalankan: ALTER TABLE pesanan_detail MODIFY id_detail INT AUTO_INCREMENT;";
    } else {
        $error_msg = "Database Error: " . $e->getMessage();
    }

    sendJsonResponse([
        'status'  => 'error',
        'message' => $error_msg
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        mysqli_rollback($conn);
    }

    sendJsonResponse([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}

if (!isTokoOpen()) {
    sendJsonResponse(['status' => 'error', 'message' => 'Toko sedang tutup. Silakan pesan kembali pada jam operasional.']);
}