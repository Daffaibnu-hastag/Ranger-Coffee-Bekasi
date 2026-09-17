<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak!']);
    exit;
}

/** @var mysqli $conn */
global $conn;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper: konversi satuan ke base unit (gram / ml / pcs)
function baseUnit($satuan) {
    if ($satuan === 'kg')    return ['satuan' => 'gram', 'multiplier' => 1000];
    if ($satuan === 'liter') return ['satuan' => 'ml',   'multiplier' => 1000];
    return ['satuan' => 'pcs', 'multiplier' => 1];
}

// ===========================
// 1. LIST BAHAN BAKU
// ===========================
if ($action === 'list') {
    $rows = query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC");
    $result = [];
    foreach ($rows as $r) {
        $base = baseUnit($r['kemasan_satuan']);
        $total_base = (float)$r['kemasan_jumlah'] * $base['multiplier'];
        $harga_per_base = $total_base > 0 ? ((float)$r['harga_beli'] / $total_base) : 0;

        $result[] = [
            'id_bahan'         => (int)$r['id_bahan'],
            'nama_bahan'       => htmlspecialchars($r['nama_bahan']),
            'kemasan_jumlah'   => (float)$r['kemasan_jumlah'],
            'kemasan_satuan'   => $r['kemasan_satuan'],
            'harga_beli'       => (float)$r['harga_beli'],
            'harga_beli_rp'    => rupiah((float)$r['harga_beli']),
            'satuan_base'      => $base['satuan'],
            'harga_per_base'   => $harga_per_base,
            'harga_per_base_rp' => rupiah($harga_per_base) . '/' . $base['satuan'],
            'display'          => $r['kemasan_jumlah'] . ' ' . $r['kemasan_satuan'] . ' = ' . rupiah((float)$r['harga_beli'])
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $result]);
    exit;
}

// ===========================
// 2. TAMBAH BAHAN BAKU
// ===========================
if ($action === 'tambah') {
    $nama    = mysqli_real_escape_string($conn, trim($_POST['nama_bahan'] ?? ''));
    $jumlah  = (float)($_POST['kemasan_jumlah'] ?? 1);
    $satuan  = mysqli_real_escape_string($conn, $_POST['kemasan_satuan'] ?? 'kg');
    $harga   = (float)($_POST['harga_beli'] ?? 0);

    if (empty($nama) || $harga <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Nama dan harga wajib diisi!']);
        exit;
    }

    mysqli_query($conn, "INSERT INTO bahan_baku (nama_bahan, kemasan_jumlah, kemasan_satuan, harga_beli) 
                         VALUES ('$nama', '$jumlah', '$satuan', '$harga')");
    echo json_encode(['status' => 'success', 'message' => 'Bahan baku berhasil ditambahkan!']);
    exit;
}

// ===========================
// 3. EDIT BAHAN BAKU
// ===========================
if ($action === 'edit') {
    $id      = (int)($_POST['id_bahan'] ?? 0);
    $nama    = mysqli_real_escape_string($conn, trim($_POST['nama_bahan'] ?? ''));
    $jumlah  = (float)($_POST['kemasan_jumlah'] ?? 1);
    $satuan  = mysqli_real_escape_string($conn, $_POST['kemasan_satuan'] ?? 'kg');
    $harga   = (float)($_POST['harga_beli'] ?? 0);

    if ($id <= 0 || empty($nama)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid!']);
        exit;
    }

    mysqli_query($conn, "UPDATE bahan_baku SET 
                         nama_bahan = '$nama', 
                         kemasan_jumlah = '$jumlah', 
                         kemasan_satuan = '$satuan', 
                         harga_beli = '$harga' 
                         WHERE id_bahan = $id");
    echo json_encode(['status' => 'success', 'message' => 'Bahan baku berhasil diupdate!']);
    exit;
}

// ===========================
// 4. HAPUS BAHAN BAKU
// ===========================
if ($action === 'hapus') {
    $id = (int)($_POST['id_bahan'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    mysqli_query($conn, "DELETE FROM resep_menu WHERE id_bahan = $id");
    mysqli_query($conn, "DELETE FROM bahan_baku WHERE id_bahan = $id");
    echo json_encode(['status' => 'success', 'message' => 'Bahan baku dihapus!']);
    exit;
}

// ===========================
// 5. GET RESEP SUATU MENU
// ===========================
if ($action === 'get_resep') {
    $id_menu = (int)($_GET['id_menu'] ?? 0);
    $rows = query("SELECT r.*, b.nama_bahan, b.kemasan_jumlah, b.kemasan_satuan, b.harga_beli 
                   FROM resep_menu r 
                   JOIN bahan_baku b ON r.id_bahan = b.id_bahan 
                   WHERE r.id_menu = $id_menu");

    $result = [];
    $total_hpp = 0;
    foreach ($rows as $r) {
        $base = baseUnit($r['kemasan_satuan']);
        $total_base = (float)$r['kemasan_jumlah'] * $base['multiplier'];
        $harga_per_base = $total_base > 0 ? ((float)$r['harga_beli'] / $total_base) : 0;
        $subtotal = $harga_per_base * (float)$r['jumlah_pakai'];
        $total_hpp += $subtotal;

        $result[] = [
            'id_resep'      => (int)$r['id_resep'],
            'id_bahan'      => (int)$r['id_bahan'],
            'nama_bahan'    => htmlspecialchars($r['nama_bahan']),
            'jumlah_pakai'  => (float)$r['jumlah_pakai'],
            'satuan_base'   => $base['satuan'],
            'harga_per_base'=> $harga_per_base,
            'subtotal'      => $subtotal,
            'subtotal_rp'   => rupiah($subtotal)
        ];
    }
    echo json_encode([
        'status' => 'success',
        'data' => $result,
        'total_hpp' => $total_hpp,
        'total_hpp_rp' => rupiah($total_hpp)
    ]);
    exit;
}

// ===========================
// 6. SIMPAN RESEP (Ganti semua resep menu)
// ===========================
if ($action === 'simpan_resep') {
    $id_menu = (int)($_POST['id_menu'] ?? 0);
    $items   = json_decode($_POST['items'] ?? '[]', true);

    if ($id_menu <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Menu tidak valid!']);
        exit;
    }

    // Hapus resep lama
    mysqli_query($conn, "DELETE FROM resep_menu WHERE id_menu = $id_menu");

    // Insert resep baru
    $total_hpp = 0;
    foreach ($items as $it) {
        $id_bahan = (int)($it['id_bahan'] ?? 0);
        $jumlah   = (float)($it['jumlah_pakai'] ?? 0);
        if ($id_bahan > 0 && $jumlah > 0) {
            mysqli_query($conn, "INSERT INTO resep_menu (id_menu, id_bahan, jumlah_pakai) 
                                 VALUES ($id_menu, $id_bahan, $jumlah)");

            // Hitung HPP
            $b = query("SELECT kemasan_jumlah, kemasan_satuan, harga_beli FROM bahan_baku WHERE id_bahan = $id_bahan");
            if (!empty($b)) {
                $base = baseUnit($b[0]['kemasan_satuan']);
                $total_base = (float)$b[0]['kemasan_jumlah'] * $base['multiplier'];
                $harga_per_base = $total_base > 0 ? ((float)$b[0]['harga_beli'] / $total_base) : 0;
                $total_hpp += $harga_per_base * $jumlah;
            }
        }
    }

    // Update harga_modal di tabel menu (auto-calculated dari resep)
    $total_hpp_clean = number_format($total_hpp, 2, '.', '');
    mysqli_query($conn, "UPDATE menu SET harga_modal = '$total_hpp_clean' WHERE id_menu = $id_menu");

    echo json_encode([
        'status' => 'success',
        'message' => 'Resep berhasil disimpan! HPP per menu: ' . rupiah($total_hpp),
        'total_hpp' => $total_hpp
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak valid!']);
exit;