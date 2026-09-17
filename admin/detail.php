<?php
// Pastikan path memuat '/' separator dengan benar
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

/** @var mysqli $conn */
global $conn;

$id_pesanan = (int)($_GET['id'] ?? 0);
$pesanan = query("SELECT * FROM pesanan WHERE id_pesanan = $id_pesanan");

if (empty($pesanan)) {
    header("Location: ../dashboard.php#view-pesanan");
    exit;
}

$p = $pesanan[0];
$pesan_sukses = '';

// Update Status Pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status_baru = mysqli_real_escape_string($conn, $_POST['status']);
    $alasan = isset($_POST['alasan_admin']) ? mysqli_real_escape_string($conn, $_POST['alasan_admin']) : '';
    
    if (!empty($alasan)) {
        mysqli_query($conn, "UPDATE pesanan SET status = '$status_baru', alasan_batal_admin = '$alasan' WHERE id_pesanan = $id_pesanan");
    } else {
        mysqli_query($conn, "UPDATE pesanan SET status = '$status_baru' WHERE id_pesanan = $id_pesanan");
    }
    
    $pesan_sukses = "Status pesanan berhasil diperbarui menjadi " . strtoupper($status_baru);
    
    // Refresh Data
    $pesanan = query("SELECT * FROM pesanan WHERE id_pesanan = $id_pesanan");
    $p = $pesanan[0];
}

$detail_items = query("SELECT d.*, m.nama_menu, m.gambar FROM detail_pesanan d JOIN menu m ON d.id_menu = m.id_menu WHERE d.id_pesanan = $id_pesanan");

// Helper Badge Status Dinamis
$st = strtolower($p['status']);
$badgeColor = 'secondary';
$badgeIcon = 'fa-info-circle';

if ($st == 'pending') {
    $badgeColor = 'warning';
    $badgeIcon = 'fa-clock';
} elseif ($st == 'diproses') {
    $badgeColor = 'info';
    $badgeIcon = 'fa-fire';
} elseif ($st == 'batal_pending') {
    $badgeColor = 'danger';
    $badgeIcon = 'fa-exclamation-triangle';
} elseif ($st == 'selesai') {
    $badgeColor = 'success';
    $badgeIcon = 'fa-check-circle';
} elseif ($st == 'dibatalkan') {
    $badgeColor = 'danger';
    $badgeIcon = 'fa-times-circle';
}

// Format Nomor WA
$raw_hp = preg_replace('/[^0-9]/', '', $p['no_hp'] ?? '');
$wa_num = (substr($raw_hp, 0, 1) === '0') ? '62' . substr($raw_hp, 1) : $raw_hp;
$pesan_wa = urlencode("Halo Kak " . ($p['nama_pelanggan'] ?? 'Pelanggan') . ", mengenai pesanan #" . ($p['kode_pesanan'] ?? '') . " di Ranger Coffee...");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= $p['kode_pesanan']; ?> - Ranger Coffee</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --bg-black: #08080a;
            --bg-card: rgba(20, 20, 24, 0.75);
            --bg-card-solid: #121216;
            --accent-gold: #c49a6c;
            --accent-gold-glow: rgba(196, 154, 108, 0.25);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-muted: #9e9ea7;
        }

        body { 
            background-color: var(--bg-black); 
            color: #f1f1f5; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(196, 154, 108, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(196, 154, 108, 0.03) 0%, transparent 40%);
            background-attachment: fixed;
        }

        .main-wrapper { 
            max-width: 1320px; 
            margin: 0 auto; 
            padding: 40px 20px 80px 20px; 
        }

        /* Glassmorphism Card */
        .card-premium {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .card-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        }

        .card-title-section {
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 18px;
            margin-bottom: 22px;
        }

        .card-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(196, 154, 108, 0.12);
            border: 1px solid rgba(196, 154, 108, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-gold);
            font-size: 1.1rem;
        }

        .card-title-section h5 {
            font-weight: 700;
            margin: 0;
            color: #fff;
            font-size: 1.05rem;
            letter-spacing: -0.2px;
        }

        /* Grid Data Layout */
        .info-tile {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 14px 18px;
            height: 100%;
            transition: background 0.2s ease;
        }

        .info-tile:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        .info-tile .label {
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-tile .value {
            color: #ffffff;
            font-weight: 600;
            font-size: 0.98rem;
        }

        /* Modern Select & Buttons */
        .form-select-premium {
            background-color: var(--bg-card-solid);
            border: 1px solid var(--border-color);
            color: #fff;
            border-radius: 14px;
            padding: 12px 18px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-select-premium:focus {
            background-color: #18181c;
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 4px var(--accent-gold-glow);
            color: #fff;
        }

        .btn-gold-premium {
            background: linear-gradient(135deg, var(--accent-gold), #a87d52);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 14px;
            padding: 12px 26px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(196, 154, 108, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-gold-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(196, 154, 108, 0.4);
            color: #000;
        }

        /* Modern Table Styling */
        .table-modern {
            color: #fff;
            vertical-align: middle;
            margin-bottom: 0;
        }

        .table-modern th {
            border-bottom: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            padding: 14px 12px;
        }

        .table-modern td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            padding: 16px 12px;
        }

        .badge-sugar {
            background: rgba(196, 154, 108, 0.12);
            color: var(--accent-gold);
            border: 1px solid rgba(196, 154, 108, 0.25);
            font-size: 0.72rem;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Total Highlight Box */
        .total-highlight {
            background: linear-gradient(135deg, rgba(196,154,108,0.12) 0%, rgba(196,154,108,0.03) 100%);
            border: 1px solid rgba(196, 154, 108, 0.3);
            padding: 20px 24px;
            border-radius: 18px;
            margin-top: 20px;
        }

        /* Bukti Image Preview */
        .bukti-container {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            background: rgba(0, 0, 0, 0.4);
            group: hover;
        }

        .bukti-img {
            width: 100%;
            max-height: 380px;
            object-fit: contain;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            display: block;
        }

        .bukti-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .bukti-container:hover .bukti-overlay {
            opacity: 1;
        }

        .bukti-container:hover .bukti-img {
            transform: scale(1.05);
        }

        /* Status Timeline Indicator */
        .status-pill {
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .alert-batal-req {
            background: rgba(220, 53, 69, 0.12);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 16px;
            padding: 16px 20px;
        }
    </style>
</head>
<body>

    <div class="main-wrapper">
        
        <!-- Header & Top Navigation -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">

                </nav>
                <div class="d-flex align-items-center gap-3">
                    <h2 class="fw-extrabold m-0 text-white" style="letter-spacing: -0.5px;">Pesanan <span style="color: var(--accent-gold);">#<?= $p['kode_pesanan']; ?></span></h2>
                    <span class="status-pill bg-<?= $badgeColor; ?> bg-opacity-25 text-<?= $badgeColor; ?> border border-<?= $badgeColor; ?>">
                        <i class="fas <?= $badgeIcon; ?>"></i> <?= strtoupper(str_replace('_', ' ', $st)); ?>
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="https://wa.me/<?= $wa_num; ?>?text=<?= $pesan_wa; ?>" target="_blank" class="btn btn-success border-0 rounded-3 px-3 py-2 fw-bold d-inline-flex align-items-center gap-2" style="background: #25D366; color: #000;">
                    <i class="fab fa-whatsapp fs-5"></i> Hubungi Pelanggan
                </a>
                <a href="dashboard.php#view-pesanan" class="btn btn-outline-light border-secondary rounded-3 px-3 py-2 fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        <?php if (!empty($pesan_sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 rounded-4 mb-4 text-white p-3 d-flex align-items-center gap-3" style="background: rgba(25, 135, 84, 0.2); border: 1px solid #198754 !important;">
                <i class="fas fa-check-circle fs-4 text-success"></i>
                <div class="fw-medium"><?= $pesan_sukses; ?></div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Warning Alert Pengajuan Batal -->
        <?php if ($st === 'batal_pending'): ?>
            <div class="alert-batal-req mb-4 d-flex align-items-start gap-3 text-white">
                <i class="fas fa-exclamation-triangle text-danger fs-3 mt-1"></i>
                <div>
                    <h6 class="fw-bold text-danger mb-1">Pengajuan Pembatalan Oleh Pelanggan</h6>
                    <p class="mb-2 small text-white-50">Pelanggan mengajukan pembatalan pesanan ini dengan alasan:</p>
                    <div class="bg-black bg-opacity-40 p-3 rounded-3 border border-danger border-opacity-25 fw-semibold text-warning">
                        "<?= htmlspecialchars($p['alasan_batal_pelanggan'] ?? 'Tidak ada alasan dicantumkan'); ?>"
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- KOLOM KIRI: Informasi Pelanggan & Items -->
            <div class="col-12 col-lg-7">
                
                <!-- Card 1: Data Pemesan & Status Update -->
                <div class="card-premium mb-4">
                    <div class="card-title-section">
                        <div class="card-title-icon"><i class="fas fa-user-check"></i></div>
                        <h5>Informasi Pelanggan</h5>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <div class="info-tile">
                                <div class="label"><i class="far fa-user text-warning"></i> Nama Pelanggan</div>
                                <div class="value"><?= htmlspecialchars($p['nama_pelanggan']); ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="info-tile">
                                <div class="label"><i class="fab fa-whatsapp text-success"></i> No. WhatsApp</div>
                                <div class="value"><?= htmlspecialchars($p['no_hp']); ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="info-tile">
                                <div class="label"><i class="far fa-calendar-alt text-info"></i> Tanggal Transaksi</div>
                                <div class="value"><?= date('d F Y', strtotime($p['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="info-tile">
                                <div class="label"><i class="far fa-clock text-info"></i> Waktu Order</div>
                                <div class="value"><?= date('H:i', strtotime($p['created_at'])); ?> WIB</div>
                            </div>
                        </div>
                        <?php if (!empty($p['catatan'])): ?>
                        <div class="col-12">
                            <div class="info-tile">
                                <div class="label"><i class="far fa-sticky-note text-warning"></i> Catatan Pemesan</div>
                                <div class="value text-warning-50 fst-italic">"<?= htmlspecialchars($p['catatan']); ?>"</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Form Pembaruan Status -->
                    <div class="p-3 rounded-4" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color);">
                        <label class="text-white-50 small fw-bold mb-2 d-block"><i class="fas fa-sliders-h me-1 text-warning"></i> Perbarui Status Pesanan</label>
                        <form action="" method="POST" class="d-flex flex-column flex-sm-row gap-2">
                            <select name="status" class="form-select form-select-premium flex-grow-1">
                                <option value="pending" <?= $st == 'pending' ? 'selected' : ''; ?>>Pending (Menunggu Konfirmasi)</option>
                                <option value="diproses" <?= $st == 'diproses' ? 'selected' : ''; ?>>Diproses (Sedang Diseduh)</option>
                                <option value="selesai" <?= $st == 'selesai' ? 'selected' : ''; ?>>Selesai (Siap / Lunas)</option>
                                <option value="dibatalkan" <?= $st == 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                            <button type="submit" class="btn btn-gold-premium">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Card 2: Rincian Pesanan -->
                <div class="card-premium">
                    <div class="card-title-section">
                        <div class="card-title-icon"><i class="fas fa-mug-hot"></i></div>
                        <h5>Rincian Menu Dipesan</h5>
                    </div>
                    
                    <div class="table-responsive">
                    <table class="table table-dark table-borderless align-middle mb-0">        
                        <thead>
                                <tr>
                                    <th>Item Menu</th>
                                    <th>Level Sugar</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail_items as $d): 
                                    $gambar = !empty($d['gambar']) ? $d['gambar'] : 'kopi.png';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="../uploads/menu/<?= $gambar; ?>" class="rounded-3" style="width: 44px; height: 44px; object-fit: cover; border: 1px solid var(--border-color);" onerror="this.src='../../uploads/menu/kopi.png'">
                                                <span class="fw-semibold text-white"><?= htmlspecialchars($d['nama_menu']); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge-sugar"><?= htmlspecialchars($d['level_gula']); ?></span></td>
                                        <td class="text-center fw-bold text-white-50"><?= $d['jumlah']; ?>x</td>
                                        <td class="text-end fw-bold" style="color: var(--accent-gold);"><?= rupiah($d['harga'] * $d['jumlah']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="total-highlight d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small fw-bold text-uppercase">Total Ringkasan Tagihan</div>
                            <small class="text-white-50">Termasuk Pajak & Layanan</small>
                        </div>
                        <div class="fw-extrabold fs-3" style="color: var(--accent-gold);"><?= rupiah($p['total_harga']); ?></div>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: Bukti Transfer QRIS -->
            <div class="col-12 col-lg-5">
                <div class="card-premium h-100 d-flex flex-column">
                    <div class="card-title-section">
                        <div class="card-title-icon"><i class="fas fa-qrcode"></i></div>
                        <h5>Bukti Pembayaran QRIS</h5>
                    </div>
                    
                    <div class="flex-grow-1 d-flex flex-column justify-content-center">
                        <?php if (!empty($p['bukti_transfer'])): ?>
                            <div class="bukti-container text-center">
                                <img src="../uploads/bukti/<?= $p['bukti_transfer']; ?>" class="bukti-img" alt="Bukti Transfer">
                                <div class="bukti-overlay">
                                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold shadow-lg" onclick="openBuktiModal('../uploads/bukti/<?= $p['bukti_transfer']; ?>')">
                                        <i class="fas fa-search-plus me-1"></i> Perbesar Gambar
                                    </button>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <a href="../uploads/bukti/<?= $p['bukti_transfer']; ?>" download class="btn btn-sm btn-outline-light border-secondary rounded-3 px-3">
                                    <i class="fas fa-download me-1"></i> Unduh Bukti Transaksi
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; background: rgba(255, 255, 255, 0.03); border: 1px dashed rgba(255, 255, 255, 0.15);">
                                    <i class="fas fa-receipt fa-2x text-white-50 opacity-50"></i>
                                </div>
                                <h6 class="text-white-50 fw-bold">Belum Ada Bukti Transfer</h6>
                                <p class="text-white-50 small mb-0">Pelanggan belum mengunggah resi transaksi QRIS.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Lightbox Bukti Transfer -->
    <div class="modal fade" id="modalLightbox" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark text-white border-secondary rounded-4 overflow-hidden">
                <div class="modal-header border-secondary">
                    <h6 class="modal-title fw-bold"><i class="fas fa-receipt text-warning me-2"></i>Bukti Pembayaran QRIS</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2 text-center bg-black">
                    <img id="imgLightbox" src="" class="img-fluid rounded-3" style="max-height: 80vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openBuktiModal(src) {
            document.getElementById('imgLightbox').src = src;
            const modal = new bootstrap.Modal(document.getElementById('modalLightbox'));
            modal.show();
        }
    </script>
</body>
</html>