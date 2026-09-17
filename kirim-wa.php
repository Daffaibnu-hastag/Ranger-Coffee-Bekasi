<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// Keamanan: Cegah akses langsung tanpa kirim POST atau keranjang kosong
if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SESSION['keranjang'])) {
  header("Location: index.php");
  exit;
}

/** @var mysqli $conn */
global $conn;

$nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
$no_hp          = mysqli_real_escape_string($conn, $_POST['no_hp']);
$catatan        = mysqli_real_escape_string($conn, $_POST['catatan']);

// Ambil nomor WA admin dari tabel pengaturan
$pengaturan = get_pengaturan();
$wa_admin   = !empty($pengaturan['no_whatsapp']) ? $pengaturan['no_whatsapp'] : '6281780532551';

// Hitung Grand Total & Generate Kode Pesanan
$total_harga  = 0;
$kode_pesanan = "RC-" . date("YmdHis");

foreach ($_SESSION['keranjang'] as $item) {
  $id_m = (int)$item['id_menu'];
  $m_data = query("SELECT harga FROM menu WHERE id_menu = $id_m");
  if (!empty($m_data)) {
    $total_harga += ($m_data[0]['harga'] * $item['jumlah']);
  }
}

// 1. Simpan ke tabel `pesanan`
$query_pesanan = "INSERT INTO pesanan (kode_pesanan, nama_pelanggan, no_hp, catatan, total_harga, status) 
                  VALUES ('$kode_pesanan', '$nama_pelanggan', '$no_hp', '$catatan', '$total_harga', 'pending')";
mysqli_query($conn, $query_pesanan);
$id_pesanan = mysqli_insert_id($conn);

// 2. Simpan ke tabel `pesanan_detail` & Rakit teks WhatsApp
$wa_detail_text = "";
foreach ($_SESSION['keranjang'] as $item) {
  $id_m = (int)$item['id_menu'];
  $m_data = query("SELECT * FROM menu WHERE id_menu = $id_m");
  if (empty($m_data)) continue;

  $m          = $m_data[0];
  $level_gula = mysqli_real_escape_string($conn, $item['level_gula']);
  $jumlah     = (int)$item['jumlah'];
  $harga      = (int)$m['harga'];
  $subtotal   = $harga * $jumlah;

  // Insert detail ke DB
  $query_detail = "INSERT INTO pesanan_detail (id_pesanan, id_menu, level_gula, jumlah, harga, subtotal) 
                     VALUES ('$id_pesanan', '$id_m', '$level_gula', '$jumlah', '$harga', '$subtotal')";
  mysqli_query($conn, $query_detail);

  // Teks Rincian untuk WA
  $wa_detail_text .= "• *" . $m['nama_menu'] . "* (" . $level_gula . ")\n  " . $jumlah . "x @ " . rupiah($harga) . " = " . rupiah($subtotal) . "\n";
}

// 3. Reset Session Keranjang Belanja
unset($_SESSION['keranjang']);

// 4. Susun Pesan WhatsApp
$nama_toko = !empty($pengaturan['nama_toko']) ? strtoupper($pengaturan['nama_toko']) : 'RANGER COFFEE';

$pesan_wa  = "*HALO " . $nama_toko . "! Saya ingin memesan kopi:*\n\n";
$pesan_wa .= "*Kode Pesanan:* " . $kode_pesanan . "\n";
$pesan_wa .= "*Nama:* " . $nama_pelanggan . "\n";
$pesan_wa .= "*No. HP:* " . $no_hp . "\n\n";
$pesan_wa .= "*Rincian Pesanan:*\n" . $wa_detail_text . "\n";
$pesan_wa .= "*Total Bayar:* " . rupiah($total_harga) . "\n";
if (!empty($catatan)) {
  $pesan_wa .= "*Catatan:* " . $catatan . "\n";
}
$pesan_wa .= "\nMohon diproses ya, Terima kasih!";

// Format ulang nomor WA admin ke kode negara 62 jika diawali angka 0
$wa_admin = preg_replace('/[^0-9]/', '', $wa_admin);
if (substr($wa_admin, 0, 1) === '0') {
  $wa_admin = '62' . substr($wa_admin, 1);
}

// Redirect ke URL API WhatsApp
$url_wa = "https://api.whatsapp.com/send?phone=" . $wa_admin . "&text=" . urlencode($pesan_wa);
header("Location: " . $url_wa);
exit;
