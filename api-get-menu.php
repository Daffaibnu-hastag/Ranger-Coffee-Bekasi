<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

/** @var mysqli $conn */
global $conn;

$kat_selected = isset($_GET['kat']) ? (int)$_GET['kat'] : null;

if ($kat_selected) {
    $menu = query("SELECT * FROM menu WHERE id_kategori = '$kat_selected'");
} else {
    $menu = query("SELECT * FROM menu");
}

$data_menu = [];
foreach ($menu as $m) {
    $is_habis = (strtolower($m['status'] ?? '') === 'habis');
    $gambar   = !empty($m['gambar']) ? $m['gambar'] : 'kopi.png';

    $data_menu[] = [
        'id_menu'     => (int)$m['id_menu'],
        'nama_menu'   => htmlspecialchars($m['nama_menu']),
        'deskripsi'   => htmlspecialchars($m['deskripsi'] ?? ''),
        'harga_rp'    => rupiah($m['harga']),
        'harga_num'   => (int)$m['harga'],
        'status'      => strtolower($m['status'] ?? 'tersedia'),
        'is_habis'    => $is_habis,
        'gambar'      => $gambar,
        'id_kategori' => (int)$m['id_kategori']
    ];
}

echo json_encode([
    'status' => 'success',
    'data'   => $data_menu
]);
exit;