<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// -------------------------------------------------------------------------
// HANDLE REQUEST POST (TAMBAH, EDIT QTY, HAPUS, KOSONGKAN)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Tambah Ke Keranjang (via AJAX / Form)
    if ($action === 'tambah') {
        $id_menu    = (int)($_POST['id_menu'] ?? 0);
        $level_gula = $_POST['level_gula'] ?? 'Normal Sugar';
        $jumlah     = (int)($_POST['jumlah'] ?? 1);

        $nama_menu_dipilih = 'Menu'; // Fallback default

        // ================= CEK STATUS TOKO =================
        if (!isTokoOpen()) {
            $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                       (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($is_ajax) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Toko sedang tutup. Silakan pesan kembali pada jam operasional (Senin-Jumat 19.00-22.00 WIB, Sabtu-Minggu 08.00-22.00 WIB).'
                ]);
                exit;
            }

            header("Location: keranjang.php?error=tutup");
            exit;
        }
        // ===================================================

        if ($id_menu > 0 && $jumlah > 0) {
            // Ambil nama menu asli dari DB sesuai id_menu yang diklik
            $get_m = query("SELECT nama_menu FROM menu WHERE id_menu = $id_menu");
            if (!empty($get_m)) {
                $nama_menu_dipilih = $get_m[0]['nama_menu'];
            }

            $key = $id_menu . '_' . md5($level_gula);
            if (isset($_SESSION['keranjang'][$key])) {
                $_SESSION['keranjang'][$key]['jumlah'] += $jumlah;
            } else {
                $_SESSION['keranjang'][$key] = [
                    'id_menu'    => $id_menu,
                    'level_gula' => $level_gula,
                    'jumlah'     => $jumlah
                ];
            }
        }

        // Cek jika request dikirim via AJAX / Fetch JS
        $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                   (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($is_ajax) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'     => 'success',
                'message'    => htmlspecialchars($nama_menu_dipilih) . ' berhasil ditambahkan ke keranjang!',
                'total_item' => total_item_keranjang()
            ]);
            exit;
        }

        header("Location: keranjang.php");
        exit;
    }

    // 2. Update Quantity (+ / -)
    if ($action === 'update_qty') {
        $key  = $_POST['key'] ?? '';
        $type = $_POST['type'] ?? '';

        if (isset($_SESSION['keranjang'][$key])) {
            if ($type === 'plus') {
                $_SESSION['keranjang'][$key]['jumlah']++;
            } elseif ($type === 'minus') {
                $_SESSION['keranjang'][$key]['jumlah']--;
                if ($_SESSION['keranjang'][$key]['jumlah'] <= 0) {
                    unset($_SESSION['keranjang'][$key]);
                }
            }
        }
        header("Location: keranjang.php");
        exit;
    }

    // 3. Hapus Single Item
    if ($action === 'hapus') {
        $key = $_POST['key'] ?? '';
        if (isset($_SESSION['keranjang'][$key])) {
            unset($_SESSION['keranjang'][$key]);
        }
        header("Location: keranjang.php");
        exit;
    }

    // 4. Kosongkan Keranjang
    if ($action === 'kosongkan') {
        $_SESSION['keranjang'] = [];
        header("Location: keranjang.php");
        exit;
    }
}

// -------------------------------------------------------------------------
// KALKULASI RINGKASAN KERANJANG UNTUK TAMPILAN (VIEW)
// -------------------------------------------------------------------------
$total_bayar = 0;
$items_detail = [];

foreach ($_SESSION['keranjang'] as $key => $item) {
    $id_m = (int)$item['id_menu'];
    $m_data = query("SELECT * FROM menu WHERE id_menu = $id_m");
    if (!empty($m_data)) {
        $menu_info = $m_data[0];
        $subtotal = $menu_info['harga'] * $item['jumlah'];
        $total_bayar += $subtotal;

        $items_detail[] = [
            'key'        => $key,
            'id_menu'    => $id_m,
            'nama_menu'  => $menu_info['nama_menu'],
            'harga'      => $menu_info['harga'],
            'gambar'     => !empty($menu_info['gambar']) ? $menu_info['gambar'] : 'kopi.png',
            'level_gula' => $item['level_gula'],
            'jumlah'     => $item['jumlah'],
            'subtotal'   => $subtotal
        ];
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// ================= TAMPILKAN NOTIFIKASI ERROR TOKO TUTUP =================
$error_message = '';
if (isset($_GET['error']) && $_GET['error'] == 'tutup') {
    $error_message = 'Toko sedang tutup. Anda tidak dapat menambahkan item ke keranjang.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Ranger Coffee</title>

    <style>
        :root {
            --bg-black: #080808;
            --bg-card: #141414;
            --accent-gold: #c49a6c;
            --accent-orange: #d96b27;
            --border-color: rgba(255, 255, 255, 0.08);
        }

        body {
            background-color: var(--bg-black);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        .cart-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.25rem;
        }

        @media (min-width: 768px) {
            .cart-card {
                padding: 1.75rem;
            }
        }

        /* Card Item Produk Keranjang */
        .cart-item-row {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 12px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }

        .cart-item-row:last-child {
            margin-bottom: 0;
        }

        .cart-item-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid rgba(196, 154, 108, 0.25);
        }

        @media (min-width: 768px) {
            .cart-item-img {
                width: 80px;
                height: 80px;
            }
        }

        .sugar-badge {
            background: rgba(196, 154, 108, 0.15);
            color: var(--accent-gold);
            border: 1px solid rgba(196, 154, 108, 0.3);
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 600;
        }

        /* Counter (+ / -) UI Touch-Friendly */
        .qty-control-box {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 2px;
        }

        .btn-qty-action {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.08);
            border: none;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            transition: all 0.2s;
        }

        .btn-qty-action:hover {
            background: var(--accent-gold);
            color: #000;
        }

        .btn-delete-item {
            color: rgba(231, 76, 60, 0.7);
            background: transparent;
            border: none;
            padding: 6px;
            transition: color 0.2s;
        }

        .btn-delete-item:hover {
            color: #e74c3c;
        }

        /* Tombol Checkout Gold */
        .btn-gold-checkout {
            background: linear-gradient(135deg, var(--accent-gold), #a87d52);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(196, 154, 108, 0.2);
        }

        .btn-gold-checkout:hover {
            background: linear-gradient(135deg, #d6ab7d, var(--accent-gold));
            color: #000;
            transform: translateY(-2px);
        }

        .btn-gold-checkout.disabled {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="container my-3 my-md-5 px-3">

    <!-- Header Keranjang -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-white m-0">Keranjang Belanja</h3>
            <small class="text-white-50">Kelola item pesanan kopi kamu di sini</small>
        </div>

        <?php if (!empty($items_detail)): ?>
            <form action="keranjang.php" method="POST" onsubmit="return confirm('Apakah kamu yakin ingin mengosongkan keranjang?');">
                <input type="hidden" name="action" value="kosongkan">
                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3 px-3">
                    <i class="fas fa-trash-alt me-1"></i>Kosongkan
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Notifikasi Error Toko Tutup -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-warning alert-dismissible fade show border-0 rounded-4 mb-4 text-white" style="background: rgba(255,193,7,0.15); border: 1px solid #ffc107 !important;">
            <i class="fas fa-exclamation-triangle me-2 text-warning"></i><?= $error_message; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Kondisi Keranjang Kosong -->
    <?php if (empty($items_detail)): ?>
        <div class="cart-card text-center py-5 shadow-lg">
            <i class="fas fa-shopping-basket fa-4x mb-3 text-white-50" style="opacity: 0.3;"></i>
            <h5 class="fw-bold text-white mb-2">Keranjang Kamu Masih Kosong</h5>
            <p class="text-white-50 small mb-4">Sepertinya kamu belum menambahkan menu kopi favorit ke keranjang.</p>
            <a href="index.php" class="btn btn-gold-checkout px-4 py-2 text-decoration-none d-inline-block" style="width: auto;">
                <i class="fas fa-coffee me-2"></i>Lihat Menu Kopi
            </a>
        </div>
    <?php else: ?>

        <div class="row g-4 justify-content-center">
            
            <!-- Daftar Item Produk (Kolom Kiri) -->
            <div class="col-12 col-lg-7">
                <div class="cart-card shadow-lg mb-3">
                    <h6 class="fw-bold mb-3" style="color: var(--accent-gold);">
                        <i class="fas fa-list me-2"></i>Daftar Pesanan (<?= count($items_detail); ?> Jenis)
                    </h6>

                    <?php foreach ($items_detail as $item): ?>
                        <div class="cart-item-row d-flex align-items-center justify-content-between gap-2">
                            
                            <!-- Gambar & Detail Produk -->
                            <div class="d-flex align-items-center gap-3 overflow-hidden">
                                <img src="uploads/menu/<?= $item['gambar']; ?>" class="cart-item-img flex-shrink-0" alt="<?= htmlspecialchars($item['nama_menu']); ?>" onerror="this.src='uploads/menu/kopi.png'">
                                
                                <div class="overflow-hidden">
                                    <h6 class="fw-bold text-white mb-1 text-truncate fs-6"><?= htmlspecialchars($item['nama_menu']); ?></h6>
                                    <div class="mb-2">
                                        <span class="sugar-badge"><?= htmlspecialchars($item['level_gula']); ?></span>
                                    </div>
                                    <div class="fw-bold small" style="color: var(--accent-gold);"><?= rupiah($item['subtotal']); ?></div>
                                </div>
                            </div>

                            <!-- Tombol Counter & Delete (Kanan) -->
                            <div class="d-flex flex-column align-items-end justify-content-between gap-2 flex-shrink-0">
                                <!-- Form Hapus Single Item -->
                                <form action="keranjang.php" method="POST">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="key" value="<?= $item['key']; ?>">
                                    <button type="submit" class="btn-delete-item" title="Hapus Item">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>

                                <!-- Form Plus Minus Quantity -->
                                <div class="qty-control-box">
                                    <!-- Button Minus -->
                                    <form action="keranjang.php" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_qty">
                                        <input type="hidden" name="key" value="<?= $item['key']; ?>">
                                        <input type="hidden" name="type" value="minus">
                                        <button type="submit" class="btn-qty-action">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </form>

                                    <!-- Angka Qty -->
                                    <span class="px-2 fw-bold text-white small"><?= $item['jumlah']; ?></span>

                                    <!-- Button Plus -->
                                    <form action="keranjang.php" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_qty">
                                        <input type="hidden" name="key" value="<?= $item['key']; ?>">
                                        <input type="hidden" name="type" value="plus">
                                        <button type="submit" class="btn-qty-action">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>

                    <div class="mt-3 pt-2 border-top border-secondary border-opacity-25 text-end">
                        <a href="index.php#menu" class="text-white-50 small text-decoration-none">
                            <i class="fas fa-plus-circle me-1"></i>Tambah Menu Lain
                        </a>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Bayar & Tombol Checkout (Kolom Kanan) -->
            <div class="col-12 col-lg-5">
                <div class="cart-card shadow-lg">
                    <h5 class="fw-bold mb-3" style="color: var(--accent-gold);">
                        <i class="fas fa-receipt me-2"></i>Ringkasan Transaksi
                    </h5>

                    <div class="d-flex justify-content-between align-items-center mb-2 text-white-50 small">
                        <span>Total Quantity:</span>
                        <strong class="text-white"><?= total_item_keranjang(); ?> item</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 text-white-50 small">
                        <span>Status Pemesanan:</span>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success px-2 py-1">Siap Checkout</span>
                    </div>

                    <!-- Card Highlight Total Bayar -->
                    <div class="p-3 rounded-3 mb-4" style="background: rgba(196, 154, 108, 0.08); border: 1px solid rgba(196, 154, 108, 0.2);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold text-white">Total Estimasi:</span>
                            <span class="fw-bold fs-4" style="color: var(--accent-gold);"><?= rupiah($total_bayar); ?></span>
                        </div>
                    </div>

                    <div class="d-grid">
                        <?php if (isTokoOpen()): ?>
                            <a href="checkout.php" class="btn btn-gold-checkout w-100 text-center text-decoration-none">
                                Lanjut ke Checkout <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        <?php else: ?>
                            <a href="checkout.php" class="btn btn-gold-checkout disabled w-100 text-center text-decoration-none">
                                <i class="fas fa-lock me-2"></i>Toko Tutup, Tidak Bisa Checkout
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>