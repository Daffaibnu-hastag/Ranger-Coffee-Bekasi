<?php
require_once __DIR__ . '/includes/functions.php';

// Masukkan nomor WA kamu untuk tes
$no_tes = '081234567890'; 
$pesan  = "Halo! Ini tes notifikasi WhatsApp dari Ranger Coffee ☕";

$hasil = kirimWA($no_tes, $pesan);

echo "<h3>Hasil Tes Pengiriman WhatsApp:</h3>";
echo "<pre>";
print_r($hasil);
echo "</pre>";
?>