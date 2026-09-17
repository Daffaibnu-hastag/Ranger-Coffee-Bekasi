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

/** @var mysqli $conn */
global $conn;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =============================================
// 1. GET STATISTIK KEUANGAN
// =============================================
if ($action === 'get_stats') {
    $dari   = $_GET['dari']   ?? date('Y-m-01');
    $sampai = $_GET['sampai'] ?? date('Y-m-d');

    $dari_clean   = mysqli_real_escape_string($conn, $dari);
    $sampai_clean = mysqli_real_escape_string($conn, $sampai);

    // Total Omzet (Pesanan selesai)
    $omzet_row = query("SELECT COALESCE(SUM(total_harga), 0) as omzet 
                        FROM pesanan 
                        WHERE status = 'selesai' 
                        AND DATE(created_at) BETWEEN '$dari_clean' AND '$sampai_clean'");
    $total_omzet = (float)($omzet_row[0]['omzet'] ?? 0);

    // Total HPP (Modal barang terjual)
    $hpp_row = query("SELECT COALESCE(SUM(dp.jumlah * m.harga_modal), 0) as hpp 
                      FROM detail_pesanan dp 
                      JOIN pesanan p ON dp.id_pesanan = p.id_pesanan 
                      JOIN menu m ON dp.id_menu = m.id_menu 
                      WHERE p.status = 'selesai' 
                      AND DATE(p.created_at) BETWEEN '$dari_clean' AND '$sampai_clean'");
    $total_hpp = (float)($hpp_row[0]['hpp'] ?? 0);

    // Total Modal Operasional
    $op_row = query("SELECT COALESCE(SUM(jumlah), 0) as total 
                     FROM modal_operasional 
                     WHERE tanggal BETWEEN '$dari_clean' AND '$sampai_clean'");
    $total_modal_operasional = (float)($op_row[0]['total'] ?? 0);

    $laba_kotor  = $total_omzet - $total_hpp;
    $laba_bersih = $laba_kotor - $total_modal_operasional;

    echo json_encode([
        'status'                  => 'success',
        'periode'                 => ['dari' => $dari, 'sampai' => $sampai],
        'total_omzet'             => $total_omzet,
        'total_omzet_rp'          => rupiah($total_omzet),
        'total_hpp'               => $total_hpp,
        'total_hpp_rp'            => rupiah($total_hpp),
        'total_modal_operasional' => $total_modal_operasional,
        'total_modal_op_rp'       => rupiah($total_modal_operasional),
        'laba_kotor'              => $laba_kotor,
        'laba_kotor_rp'           => rupiah($laba_kotor),
        'laba_bersih'             => $laba_bersih,
        'laba_bersih_rp'          => rupiah($laba_bersih),
        'is_laba'                 => $laba_bersih >= 0
    ]);
    exit;
}

// =============================================
// 2. GET RINCIAN PER MENU
// =============================================
if ($action === 'get_per_menu') {
    $dari   = mysqli_real_escape_string($conn, $_GET['dari'] ?? date('Y-m-01'));
    $sampai = mysqli_real_escape_string($conn, $_GET['sampai'] ?? date('Y-m-d'));

    $rows = query("SELECT 
                        m.id_menu,
                        m.nama_menu,
                        m.harga as harga_jual,
                        m.harga_modal,
                        COALESCE(SUM(dp.jumlah), 0) as total_terjual,
                        COALESCE(SUM(dp.jumlah * m.harga), 0) as total_omzet,
                        COALESCE(SUM(dp.jumlah * m.harga_modal), 0) as total_modal,
                        COALESCE(SUM(dp.jumlah * (m.harga - m.harga_modal)), 0) as total_laba
                    FROM menu m
                    LEFT JOIN detail_pesanan dp ON dp.id_menu = m.id_menu
                    LEFT JOIN pesanan p ON dp.id_pesanan = p.id_pesanan 
                        AND p.status = 'selesai' 
                        AND DATE(p.created_at) BETWEEN '$dari' AND '$sampai'
                    GROUP BY m.id_menu
                    ORDER BY total_laba DESC");

    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'id_menu'         => (int)$r['id_menu'],
            'nama_menu'       => htmlspecialchars($r['nama_menu']),
            'harga_jual'      => (float)$r['harga_jual'],
            'harga_jual_rp'   => rupiah((float)$r['harga_jual']),
            'harga_modal'     => (float)$r['harga_modal'],
            'harga_modal_rp'  => rupiah((float)$r['harga_modal']),
            'margin_unit'     => (float)($r['harga_jual'] - $r['harga_modal']),
            'margin_unit_rp'  => rupiah((float)($r['harga_jual'] - $r['harga_modal'])),
            'total_terjual'   => (int)$r['total_terjual'],
            'total_omzet_rp'  => rupiah((float)$r['total_omzet']),
            'total_modal_rp'  => rupiah((float)$r['total_modal']),
            'total_laba'      => (float)$r['total_laba'],
            'total_laba_rp'   => rupiah((float)$r['total_laba']),
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $result]);
    exit;
}

// =============================================
// 3. UPDATE HARGA MODAL MENU
// =============================================
if ($action === 'update_harga_modal') {
    $id_menu     = (int)($_POST['id_menu'] ?? 0);
    $harga_modal = (float)($_POST['harga_modal'] ?? 0);

    if ($id_menu <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID Menu tidak valid!']);
        exit;
    }

    $q = mysqli_query($conn, "UPDATE menu SET harga_modal = '$harga_modal' WHERE id_menu = $id_menu");
    if ($q) {
        echo json_encode(['status' => 'success', 'message' => 'Harga modal berhasil diupdate!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update: ' . mysqli_error($conn)]);
    }
    exit;
}

// =============================================
// 4. TAMBAH MODAL OPERASIONAL
// =============================================
if ($action === 'tambah_modal_op') {
    $keterangan = mysqli_real_escape_string($conn, trim($_POST['keterangan'] ?? ''));
    $jumlah     = (float)($_POST['jumlah'] ?? 0);
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? date('Y-m-d'));

    if (empty($keterangan) || $jumlah <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Keterangan dan jumlah wajib diisi!']);
        exit;
    }

    $q = mysqli_query($conn, "INSERT INTO modal_operasional (keterangan, jumlah, tanggal) 
                              VALUES ('$keterangan', '$jumlah', '$tanggal')");
    if ($q) {
        echo json_encode(['status' => 'success', 'message' => 'Modal operasional berhasil ditambahkan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal simpan: ' . mysqli_error($conn)]);
    }
    exit;
}

// =============================================
// 5. GET LIST MODAL OPERASIONAL
// =============================================
if ($action === 'get_modal_op') {
    $dari   = mysqli_real_escape_string($conn, $_GET['dari'] ?? date('Y-m-01'));
    $sampai = mysqli_real_escape_string($conn, $_GET['sampai'] ?? date('Y-m-d'));

    $rows = query("SELECT * FROM modal_operasional 
                   WHERE tanggal BETWEEN '$dari' AND '$sampai' 
                   ORDER BY tanggal DESC, id_modal DESC");

    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'id_modal'    => (int)$r['id_modal'],
            'keterangan'  => htmlspecialchars($r['keterangan']),
            'jumlah'      => (float)$r['jumlah'],
            'jumlah_rp'   => rupiah((float)$r['jumlah']),
            'tanggal'     => date('d M Y', strtotime($r['tanggal']))
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $result]);
    exit;
}

// =============================================
// 6. HAPUS MODAL OPERASIONAL
// =============================================
if ($action === 'hapus_modal_op') {
    $id = (int)($_POST['id_modal'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid!']);
        exit;
    }
    $q = mysqli_query($conn, "DELETE FROM modal_operasional WHERE id_modal = $id");
    echo json_encode([
        'status'  => $q ? 'success' : 'error',
        'message' => $q ? 'Berhasil dihapus!' : 'Gagal hapus: ' . mysqli_error($conn)
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak valid!']);
exit;