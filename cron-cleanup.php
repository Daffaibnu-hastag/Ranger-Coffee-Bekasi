<?php
require_once __DIR__ . '/config/database.php';

date_default_timezone_set('Asia/Jakarta');

/** @var mysqli $conn */
global $conn;

// Khusus Testing: Cari pesanan pending yang berumur >= 10 detik
// (Ubah kembali ke 6 HOUR setelah selesai testing)
$sql_check = "SELECT id_pesanan, kode_pesanan, bukti_transfer 
              FROM pesanan 
              WHERE status = 'pending' 
              AND created_at <= NOW() - INTERVAL 6 HOUR";

$result = mysqli_query($conn, $sql_check);

if ($result && mysqli_num_rows($result) > 0) {
    $deleted_ids = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $deleted_ids[] = $row['id_pesanan'];

        // Hapus bukti transfer jika ada
        if (!empty($row['bukti_transfer'])) {
            $file_path = __DIR__ . '/uploads/bukti/' . $row['bukti_transfer'];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
    }

    if (!empty($deleted_ids)) {
        $ids_string = implode(',', array_map('intval', $deleted_ids));

        // Hapus detail item dulu (Foreign Key Safety)
        mysqli_query($conn, "DELETE FROM detail_pesanan WHERE id_pesanan IN ($ids_string)");

        // Hapus data pesanan utama
        mysqli_query($conn, "DELETE FROM pesanan WHERE id_pesanan IN ($ids_string)");
    }
}

// HAPUS ATAU JANGAN GUNAKAN EXIT DI SINI AGAR INDEX.PHP TETAP BISA DILOAD