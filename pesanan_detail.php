<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/** @var mysqli $conn */
global $conn;

// Ambil ID atau Kode Pesanan dari URL
$id_pesanan   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$kode_pesanan = isset($_GET['kode']) ? trim($_GET['kode']) : '';

if ($id_pesanan <= 0 && empty($kode_pesanan)) {
    header("Location: index.php");
    exit;
}

if ($id_pesanan > 0) {
    $where = "p.id_pesanan = $id_pesanan";
} else {
    $kode_clean = mysqli_real_escape_string($conn, $kode_pesanan);
    $where = "p.kode_pesanan = '$kode_clean'";
}

// Fetch Header Pesanan
$pesanan = query("SELECT p.* FROM pesanan p WHERE $where LIMIT 1");

if (empty($pesanan)) {
    echo "<script>alert('Pesanan tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

$detail = $pesanan[0];
$id_pesanan_actual = $detail['id_pesanan'];

// Fetch Detail Item Pesanan
$items = query("SELECT dp.*, m.nama_menu, m.gambar 
                FROM detail_pesanan dp 
                LEFT JOIN menu m ON dp.id_menu = m.id_menu 
                WHERE dp.id_pesanan = $id_pesanan_actual");

// --- HELPER UNTUK FORMAT NOMOR WHATSAPP KE 62 ---
$raw_phone = preg_replace('/[^0-9]/', '', $detail['no_hp'] ?? '');
if (substr($raw_phone, 0, 1) === '0') {
    $wa_phone = '62' . substr($raw_phone, 1);
} elseif (substr($raw_phone, 0, 2) === '62') {
    $wa_phone = $raw_phone;
} else {
    $wa_phone = '62' . $raw_phone;
}

// Text Draf Pesan Otomatis dari Penjual ke Pembeli
$pesan_admin_wa = "Halo Kak " . ($detail['nama_pelanggan'] ?? 'Pelanggan') . ", kami dari Ranger Coffee mau konfirmasi mengenai pesanan Kakak dengan Kode: #" . $detail['kode_pesanan'] . ".";
$url_wa_pembeli = "https://wa.me/" . $wa_phone . "?text=" . urlencode($pesan_admin_wa);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= htmlspecialchars($detail['kode_pesanan']); ?> - Ranger Coffee</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SWEETALERT2 UNTUK POP-UP NOTIFIKASI -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-black: #0a0a0a;
            --bg-card: #141414;
            --accent-gold: #c49a6c;
            --accent-gold-light: #e0b88f;
            --border-color: rgba(255, 255, 255, 0.08);
        }

        body {
            background-color: var(--bg-black);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }

        .card-custom {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
        }

        .badge-pending {
            background-color: rgba(255, 193, 7, 0.15);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.4);
        }

        .badge-diproses {
            background-color: rgba(13, 202, 240, 0.15);
            color: #0dcaf0;
            border: 1px solid rgba(13, 202, 240, 0.4);
        }

        .badge-selesai {
            background-color: rgba(25, 135, 84, 0.15);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.4);
        }

        .badge-dibatalkan {
            background-color: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.4);
        }

        .item-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            transition: background 0.2s ease;
        }

        .item-card:hover {
            background: rgba(255, 255, 255, 0.04);
        }
    </style>
</head>

<body>

    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container py-5 mt-3">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-7">

                <!-- BANNER NOTIFIKASI ALASAN PEMBATALAN -->
                <div id="bannerDibatalkan" class="alert alert-danger border-danger bg-danger bg-opacity-10 text-white rounded-4 p-3 mb-4 shadow-lg <?= strtolower($detail['status']) === 'dibatalkan' ? '' : 'd-none'; ?>">
                    <div class="fw-bold text-danger d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-exclamation-triangle fs-5"></i>
                        <span>Pesanan Dibatalkan oleh Outlet</span>
                    </div>
                    <small class="text-white-50 d-block mb-1">Alasan Pembatalan:</small>
                    <div class="p-3 bg-dark rounded-3 border border-danger border-opacity-50 text-white fw-medium" id="textAlasanBatal">
                        <?= htmlspecialchars($detail['alasan_batal'] ?? 'Tidak ada alasan khusus dari outlet.'); ?>
                    </div>
                </div>

                <!-- MAIN CARD DETAIL PESANAN -->
                <div class="card-custom p-4 p-md-5">

                    <!-- HEADER NOTA -->
                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-25 pb-3 mb-4">
                        <div>
                            <small class="text-white-50 d-block">Kode Pesanan</small>
                            <h4 class="fw-bold m-0" style="color: var(--accent-gold);">
                                #<?= htmlspecialchars($detail['kode_pesanan']); ?>
                            </h4>
                        </div>
                        <div class="text-end">
                            <small class="text-white-50 d-block mb-1">Status Pesanan</small>
                            <?php
                            $st = strtolower($detail['status']);
                            $badgeClass = 'badge-pending';
                            if ($st === 'diproses') $badgeClass = 'badge-diproses';
                            elseif ($st === 'selesai') $badgeClass = 'badge-selesai';
                            elseif ($st === 'dibatalkan') $badgeClass = 'badge-dibatalkan';
                            ?>
                            <span id="badgeStatus" class="badge <?= $badgeClass; ?> px-3 py-2 rounded-3 text-uppercase fw-bold" style="letter-spacing: 0.5px;">
                                <?= ucfirst($detail['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- RINCIAN PEMESAN & TOMBOL HUBUNGI PEMBELI -->
                    <div class="bg-dark rounded-4 p-3 mb-4 border border-secondary border-opacity-25">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                            <span class="fw-bold text-white"><i class="fas fa-user-circle me-2 text-warning"></i>Informasi Pembeli</span>
                            
                            <!-- TOMBOL KHUSUS PENJUAL UNTUK HUBUNGI PEMBELI VIA WA -->
                            <a href="<?= $url_wa_pembeli; ?>" target="_blank" class="btn btn-sm btn-success rounded-3 px-3 fw-bold">
                                <i class="fab fa-whatsapp me-1"></i> Hubngi Admin   
                            </a>
                        </div>

                        <div class="row g-2 text-white-50 small">
                            <div class="col-6 col-sm-4">Nama Pemesan</div>
                            <div class="col-6 col-sm-8 text-end text-sm-start text-white fw-medium">: <?= htmlspecialchars($detail['nama_pelanggan'] ?? 'Pelanggan'); ?></div>
                            
                            <div class="col-6 col-sm-4">No. WhatsApp</div>
                            <div class="col-6 col-sm-8 text-end text-sm-start text-white fw-medium">: <?= htmlspecialchars($detail['no_hp'] ?? '-'); ?></div>
                            
                            <div class="col-6 col-sm-4">Waktu Pemesanan</div>
                            <div class="col-6 col-sm-8 text-end text-sm-start text-white fw-medium">: <?= date('d M Y, H:i', strtotime($detail['created_at'])); ?> WIB</div>
                        </div>
                    </div>

                    <!-- LIST ITEM PESANAN -->
                    <h6 class="fw-bold mb-3" style="color: var(--accent-gold);"><i class="fas fa-coffee me-2"></i>Rincian Pesanan</h6>
                    
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($items as $item): 
                            $harga    = isset($item['harga']) ? floatval($item['harga']) : 0;
                            $jumlah   = isset($item['jumlah']) ? intval($item['jumlah']) : 1;
                            $subtotal = isset($item['subtotal']) && $item['subtotal'] !== null ? floatval($item['subtotal']) : ($harga * $jumlah);
                            $gula     = !empty($item['level_gula']) ? $item['level_gula'] : 'Normal';
                        ?>
                            <div class="item-card p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="uploads/menu/<?= !empty($item['gambar']) ? $item['gambar'] : 'kopi.png'; ?>" class="rounded-3" style="width: 55px; height: 55px; object-fit: cover; border: 1px solid rgba(196, 154, 108, 0.3);" onerror="this.src='uploads/menu/kopi.png'">
                                    <div>
                                        <h6 class="fw-semibold text-white mb-1"><?= htmlspecialchars($item['nama_menu'] ?? 'Menu Kopi'); ?></h6>
                                        <div class="small text-white-50">
                                            <span><?= $jumlah; ?>x @ <?= rupiah($harga); ?></span>
                                            <span class="badge bg-secondary bg-opacity-25 text-warning border border-secondary border-opacity-50 ms-1 px-2 py-0.5" style="font-size: 0.68rem;">
                                                <?= htmlspecialchars($gula); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="fw-bold fs-6 ms-auto" style="color: var(--accent-gold-light);">
                                    <?= rupiah($subtotal); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- TOTAL PEMBAYARAN -->
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-4 bg-dark border border-secondary border-opacity-25 mb-4">
                        <span class="fw-bold text-white">Total Pembayaran</span>
                        <span class="fw-bold fs-4" style="color: var(--accent-gold);"><?= rupiah($detail['total_harga'] ?? 0); ?></span>
                    </div>

                    <!-- FOOTER ACTIONS -->
                    <div class="row g-2">
                        <div class="col-12 col-sm-6">
                            <a href="index.php" class="btn btn-outline-light rounded-3 py-2.5 w-100 fw-medium">
                                <i class="fas fa-arrow-left me-2"></i> Kembali ke Katalog
                            </a>
                        </div>
                        <div class="col-12 col-sm-6">
                            <a href="<?= $url_wa_pembeli; ?>" target="_blank" class="btn btn-success rounded-3 py-2.5 w-100 fw-bold">
                                <i class="fab fa-whatsapp me-2"></i> Hubungi Admin
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ENGINE REALTIME POLLING NOTIFIKASI -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const idPesanan = <?= json_encode($id_pesanan_actual); ?>;
            let currentStatus = <?= json_encode(strtolower($detail['status'])); ?>;
            let sudahAlert = false;

            if (!idPesanan) return;

            function checkStatusLive() {
                fetch(`api-cek-status-pelanggan.php?id=${idPesanan}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const newStatus = (data.order_status || '').toLowerCase();

                            if (newStatus === 'dibatalkan') {
                                const banner = document.getElementById('bannerDibatalkan');
                                const textAlasan = document.getElementById('textAlasanBatal');
                                if (textAlasan) textAlasan.textContent = data.alasan_batal || 'Tidak ada alasan khusus dari outlet.';
                                if (banner) banner.classList.remove('d-none');

                                const badge = document.getElementById('badgeStatus');
                                if (badge) {
                                    badge.className = 'badge badge-dibatalkan px-3 py-2 rounded-3 text-uppercase fw-bold';
                                    badge.textContent = 'Dibatalkan';
                                }

                                if (currentStatus !== 'dibatalkan' && !sudahAlert) {
                                    sudahAlert = true;
                                    currentStatus = 'dibatalkan';

                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Pesanan Dibatalkan!',
                                        html: `Mohon maaf, pesanan Anda telah dibatalkan oleh outlet.<br><br><strong>Alasan Pembatalan:</strong><br><div class="p-2.5 mt-2 bg-dark text-danger border border-danger rounded-3 fw-semibold">${data.alasan_batal || 'Tidak ada alasan khusus'}</div>`,
                                        confirmButtonText: 'Saya Mengerti',
                                        confirmButtonColor: '#dc3545',
                                        background: '#141414',
                                        color: '#ffffff'
                                    });
                                }
                            }
                        }
                    })
                    .catch(err => console.error("Error Checking Live Status:", err));
            }

            setInterval(checkStatusLive, 3000);
        });
    </script>
</body>

</html>