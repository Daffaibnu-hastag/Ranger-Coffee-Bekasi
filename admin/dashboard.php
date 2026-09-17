<?php
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

$start_shift = getStartOfShift();

// Fetch Data Shift Normal (4 Status)
$total_pending  = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending' AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_diproses = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'diproses' AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_selesai  = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'selesai' AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_batal    = query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'dibatalkan' AND created_at >= '$start_shift'")[0]['total'] ?? 0;
$total_omset    = query("SELECT SUM(total_harga) as omset FROM pesanan WHERE status = 'selesai' AND created_at >= '$start_shift'")[0]['omset'] ?? 0;

$list_kategori = query("SELECT * FROM kategori ORDER BY id_kategori DESC");

// Cek kolom harga_modal
$cek_kolom = mysqli_query($conn, "SHOW COLUMNS FROM menu LIKE 'harga_modal'");
$has_harga_modal = ($cek_kolom && mysqli_num_rows($cek_kolom) > 0);

if ($has_harga_modal) {
    $list_menu = query("SELECT m.*, k.nama_kategori FROM menu m LEFT JOIN kategori k ON m.id_kategori = k.id_kategori ORDER BY m.id_menu DESC");
} else {
    $list_menu = query("SELECT m.*, 0 as harga_modal, k.nama_kategori FROM menu m LEFT JOIN kategori k ON m.id_kategori = k.id_kategori ORDER BY m.id_menu DESC");
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ranger Coffee</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-black: #0a0a0a;
            --bg-card: #141414;
            --accent-gold: #c49a6c;
            --border-color: rgba(255, 255, 255, 0.08);
        }

        body {
            background-color: var(--bg-black);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            min-height: 100vh;
            background-color: var(--bg-card);
            border-right: 1px solid var(--border-color);
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 20px 15px;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }

        .sidebar-menu {
            padding: 15px 10px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .sidebar-menu button.nav-link-custom {
            color: #a0a0a0;
            background: none;
            border: none;
            text-align: left;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 500;
            width: 100%;
            transition: all 0.2s ease;
        }

        .sidebar-menu button.nav-link-custom:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }

        .sidebar-menu button.nav-link-custom.active {
            background: linear-gradient(135deg, var(--accent-gold), #a87d52);
            color: #000000;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(196, 154, 108, 0.25);
        }

        .main-wrapper {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }

        @media (max-width: 991px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .main-wrapper {
                margin-left: 0;
                padding: 15px;
            }
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 20px;
        }

        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 18px;
        }

        .nav-tabs-custom {
            border-bottom: 1px solid var(--border-color);
            gap: 8px;
        }

        .nav-tabs-custom .nav-link {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            color: #a0a0a0;
            border-radius: 12px 12px 0 0;
            font-weight: 500;
            font-size: 0.88rem;
            padding: 10px 20px;
        }

        .nav-tabs-custom .nav-link.active {
            background: var(--bg-card) !important;
            color: var(--accent-gold) !important;
            border-color: var(--accent-gold) var(--accent-gold) transparent var(--accent-gold) !important;
            font-weight: 600;
        }

        .btn-gold-modal {
            background: linear-gradient(135deg, var(--accent-gold), #a87d52);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            padding: 10px 18px;
        }

        .form-control-dark {
            background-color: #1a1a1a;
            border: 1px solid var(--border-color);
            color: #fff;
            border-radius: 10px;
        }

        .form-control-dark:focus {
            background-color: #222;
            border-color: var(--accent-gold);
            color: #fff;
            box-shadow: none;
        }

        .swal2-popup-delete-custom {
            background: #141414 !important;
            border: 1px solid rgba(220, 53, 69, 0.4) !important;
            border-radius: 24px !important;
            color: #ffffff !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.85), 0 0 30px rgba(220, 53, 69, 0.15) !important;
            padding: 2rem 1.5rem !important;
        }

        .swal2-title-delete {
            font-weight: 700 !important;
            font-size: 1.35rem !important;
            color: #ffffff !important;
            margin-top: 10px !important;
        }

        .swal2-confirm-btn-delete {
            background: linear-gradient(135deg, #e63946, #b71c1c) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border-radius: 12px !important;
            padding: 10px 24px !important;
            box-shadow: 0 4px 15px rgba(230, 57, 70, 0.4) !important;
            border: none !important;
        }

        .swal2-cancel-btn-delete {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #cccccc !important;
            font-weight: 500 !important;
            border-radius: 12px !important;
            padding: 10px 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
        }

        .swal2-toast-custom {
            background: #181818 !important;
            border: 1px solid rgba(196, 154, 108, 0.3) !important;
            border-radius: 16px !important;
            color: #ffffff !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5) !important;
        }
    </style>
</head>

<body>

    <!-- Sidebar Navigation SPA -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-wrapper">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold m-0" id="currentViewTitle">Dashboard Overview</h3>
                <small class="text-white-50"><i class="fas fa-clock text-warning me-1"></i> Shift Aktif sejak Jam 06.00 WIB Pagi</small>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span id="audioStatusBadge" class="badge bg-danger bg-opacity-25 text-danger border border-danger px-3 py-2 rounded-3" style="font-size: 0.8rem;">
                    <i class="fas fa-volume-mute me-1"></i> Audio Belum Aktif
                </span>
                <a href="../index.php" target="_blank" class="btn btn-outline-light btn-sm rounded-3 px-3">
                    <i class="fas fa-external-link-alt me-1"></i> Buka Toko
                </a>
            </div>
        </div>

        <!-- PANEL KONTROL BUKA/TUTUP TOKO -->
        <div class="card mb-4 p-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 18px;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-store fa-2x" style="color: var(--accent-gold);"></i>
                    <div>
                        <h6 class="fw-bold m-0 text-white">Status Toko</h6>
                        <small class="text-white-50" id="statusTokoLabel">Memuat status...</small>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-success btn-sm rounded-3 px-3" onclick="setTokoStatus('open')">
                        <i class="fas fa-unlock me-1"></i> Buka Toko
                    </button>
                    <button type="button" class="btn btn-danger btn-sm rounded-3 px-3" onclick="setTokoStatus('close')">
                        <i class="fas fa-lock me-1"></i> Tutup Toko
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm rounded-3 px-3" onclick="setTokoStatus('auto')">
                        <i class="fas fa-clock me-1"></i> Mode Otomatis (Jadwal)
                    </button>
                </div>
            </div>
        </div>

        <div class="tab-content" id="mainAdminTabContent">

            <!-- VIEW 1: OVERVIEW DASHBOARD -->
            <div class="tab-pane fade show active" id="view-overview" role="tabpanel">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <span class="text-white-50 small fw-medium">Pesanan Pending</span>
                            <h3 id="statTotalPending" class="fw-bold m-0 text-warning mt-2"><?= number_format($total_pending); ?></h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <span class="text-white-50 small fw-medium">Pesanan Diproses</span>
                            <h3 id="statTotalDiproses" class="fw-bold m-0 text-info mt-2"><?= number_format($total_diproses); ?></h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <span class="text-white-50 small fw-medium">Pesanan Selesai</span>
                            <h3 id="statTotalSelesai" class="fw-bold m-0 text-success mt-2"><?= number_format($total_selesai); ?></h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <span class="text-white-50 small fw-medium">Pendapatan Shift Ini</span>
                            <h4 id="statTotalOmset" class="fw-bold m-0 mt-2" style="color: var(--accent-gold);"><?= rupiah($total_omset); ?></h4>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs nav-tabs-custom mb-0" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pending" type="button">
                            Pending (<span id="cntPending"><?= $total_pending; ?></span>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-diproses" type="button">
                            Diproses (<span id="cntDiproses"><?= $total_diproses; ?></span>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-selesai" type="button">
                            Selesai (<span id="cntSelesai"><?= $total_selesai; ?></span>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-batal" type="button">
                            Dibatalkan (<span id="cntBatal"><?= $total_batal; ?></span>)
                        </button>
                    </li>
                </ul>

                <div class="table-card p-3 shadow-lg" style="border-top-left-radius: 0;">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-pending">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle mb-0">
                                    <thead>
                                        <tr class="text-uppercase small text-white-50">
                                            <th class="ps-3 py-3">Kode</th>
                                            <th class="py-3">Pelanggan</th>
                                            <th class="py-3">No. WA</th>
                                            <th class="py-3">Waktu</th>
                                            <th class="py-3">Total</th>
                                            <th class="py-3 text-center">Bukti QRIS</th>
                                            <th class="py-3 text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyPending">
                                        <tr><td colspan="7" class="text-center py-4 text-white-50">Memuat data...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-diproses">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle mb-0">
                                    <thead>
                                        <tr class="text-uppercase small text-white-50">
                                            <th class="ps-3 py-3">Kode</th>
                                            <th class="py-3">Pelanggan</th>
                                            <th class="py-3">No. WA</th>
                                            <th class="py-3">Waktu</th>
                                            <th class="py-3">Total</th>
                                            <th class="py-3 text-center">Bukti QRIS</th>
                                            <th class="py-3 text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyDiproses">
                                        <tr><td colspan="7" class="text-center py-4 text-white-50">Memuat data...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-selesai">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle mb-0">
                                    <thead>
                                        <tr class="text-uppercase small text-white-50">
                                            <th class="ps-3 py-3">Kode</th>
                                            <th class="py-3">Pelanggan</th>
                                            <th class="py-3">No. WA</th>
                                            <th class="py-3">Waktu</th>
                                            <th class="py-3">Total</th>
                                            <th class="py-3 text-center">Bukti QRIS</th>
                                            <th class="py-3 text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodySelesai">
                                        <tr><td colspan="7" class="text-center py-4 text-white-50">Memuat data...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-batal">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle mb-0">
                                    <thead>
                                        <tr class="text-uppercase small text-white-50">
                                            <th class="ps-3 py-3">Kode</th>
                                            <th class="py-3">Pelanggan</th>
                                            <th class="py-3">No. WA</th>
                                            <th class="py-3">Waktu</th>
                                            <th class="py-3">Total</th>
                                            <th class="py-3 text-center">Bukti QRIS</th>
                                            <th class="py-3 text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyBatal">
                                        <tr><td colspan="7" class="text-center py-4 text-white-50">Memuat data...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 2: KELOLA PESANAN LENGKAP -->
            <div class="tab-pane fade" id="view-pesanan" role="tabpanel">
                <div class="table-card p-4 shadow-lg">
                    <div class="mb-3">
                        <h5 class="fw-bold m-0" style="color: var(--accent-gold);"><i class="fas fa-history me-2"></i>Arsip Riwayat Pesanan Lengkap</h5>
                        <small class="text-white-50">Seluruh riwayat transaksi dikelompokkan secara rapi berdasarkan tanggal pemesanan.</small>
                    </div>

                    <?php
                    $all_orders_grouped = query("SELECT DATE(created_at) as tgl_transaksi, COUNT(*) as total_order, SUM(total_harga) as omset_tgl 
                                                 FROM pesanan 
                                                 GROUP BY DATE(created_at) 
                                                 ORDER BY tgl_transaksi DESC");
                    ?>

                    <?php if (empty($all_orders_grouped)): ?>
                        <div class="text-center py-5 text-white-50">Belum ada riwayat transaksi.</div>
                    <?php else: ?>
                        <div class="accordion accordion-flush" id="accordionHistoryOrders">
                            <?php foreach ($all_orders_grouped as $index => $group):
                                $tgl = $group['tgl_transaksi'];
                                $tgl_formatted = date('d F Y', strtotime($tgl));
                                $orders_on_date = query("SELECT * FROM pesanan WHERE DATE(created_at) = '$tgl' ORDER BY id_pesanan DESC");
                            ?>
                                <div class="accordion-item bg-dark text-white border-secondary mb-3 rounded-3 overflow-hidden">
                                    <h2 class="accordion-header" id="heading_<?= $index; ?>">
                                        <button class="accordion-button bg-secondary bg-opacity-25 text-white fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $index; ?>">
                                            <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                                                <span><i class="fas fa-calendar-alt text-warning me-2"></i> <?= $tgl_formatted; ?></span>
                                                <div>
                                                    <span class="badge bg-primary me-2"><?= $group['total_order']; ?> Transaksi</span>
                                                    <span class="badge bg-success" style="color: #000; font-weight: 700;">Omset: <?= rupiah($group['omset_tgl'] ?? 0); ?></span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse_<?= $index; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionHistoryOrders">
                                        <div class="accordion-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-dark table-hover align-middle mb-0">
                                                    <thead>
                                                        <tr class="text-uppercase small text-white-50">
                                                            <th class="ps-3 py-3">Kode</th>
                                                            <th class="py-3">Pelanggan</th>
                                                            <th class="py-3">No. WA</th>
                                                            <th class="py-3">Jam Masuk</th>
                                                            <th class="py-3">Total</th>
                                                            <th class="py-3">Status</th>
                                                            <th class="py-3 text-center pe-3">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($orders_on_date as $p): 
                                                            $raw_hp = preg_replace('/[^0-9]/', '', $p['no_hp'] ?? '');
                                                            $wa_num = (substr($raw_hp, 0, 1) === '0') ? '62' . substr($raw_hp, 1) : $raw_hp;
                                                            $pesan_wa = urlencode("Halo Kak " . ($p['nama_pelanggan'] ?? 'Pelanggan') . ", mengenai pesanan #" . ($p['kode_pesanan'] ?? '') . " di Ranger Coffee...");
                                                        ?>
                                                            <tr>
                                                                <td class="ps-3 fw-bold" style="color: var(--accent-gold);"><?= $p['kode_pesanan']; ?></td>
                                                                <td class="fw-medium"><?= htmlspecialchars($p['nama_pelanggan']); ?></td>
                                                                <td>
                                                                    <a href="https://wa.me/<?= $wa_num; ?>?text=<?= $pesan_wa; ?>" target="_blank" class="btn btn-sm btn-success border-0 px-2 py-1 fw-bold rounded-2">
                                                                        <i class="fab fa-whatsapp me-1"></i> <?= htmlspecialchars($p['no_hp']); ?>
                                                                    </a>
                                                                </td>
                                                                <td class="text-white-50 small"><?= date('H:i', strtotime($p['created_at'])); ?> WIB</td>
                                                                <td class="fw-semibold text-white"><?= rupiah($p['total_harga']); ?></td>
                                                                <td>
                                                                    <?php
                                                                    $st = strtolower($p['status']);
                                                                    if ($st == 'pending') echo '<span class="badge bg-warning text-dark">Pending</span>';
                                                                    elseif ($st == 'diproses') echo '<span class="badge bg-info text-dark">Diproses</span>';
                                                                    elseif ($st == 'selesai') echo '<span class="badge bg-success">Selesai</span>';
                                                                    else echo '<span class="badge bg-danger">Dibatalkan</span>';
                                                                    ?>
                                                                </td>
                                                                <td class="text-center pe-3">
                                                                    <a href="detail.php?id=<?= $p['id_pesanan']; ?>" class="btn btn-sm btn-outline-light border-secondary me-1">
                                                                        <i class="fas fa-eye me-1"></i> Detail / Ubah
                                                                    </a>
                                                                    <?php if ($st !== 'dibatalkan' && $st !== 'selesai'): ?>
                                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="konfirmasiBatalPesanan(<?= $p['id_pesanan']; ?>, '<?= $p['kode_pesanan']; ?>')">
                                                                            <i class="fas fa-times me-1"></i> Batalkan
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- VIEW 3: KELOLA MENU KOPI -->
            <div class="tab-pane fade" id="view-menu" role="tabpanel">
                <div class="table-card p-4 shadow-lg">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold m-0" style="color: var(--accent-gold);"><i class="fas fa-coffee me-2"></i>Daftar Katalog Menu Kopi</h5>
                            <small class="text-white-50">Tambah, edit harga, atau ubah status ketersediaan kopi secara instan.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-gold-modal px-3" id="btnOpenTambahMenu"><i class="fas fa-plus me-1"></i> Tambah Menu Baru</button>
                    </div>

                    <?php if (!$has_harga_modal): ?>
                        <div class="alert alert-warning mb-3" style="background: rgba(255,193,7,0.1); border: 1px solid #ffc107; color: #ffc107; border-radius: 12px;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Perhatian:</strong> Kolom <code>harga_modal</code> belum ada. Jalankan SQL: 
                            <code>ALTER TABLE menu ADD COLUMN harga_modal DECIMAL(12,2) DEFAULT 0 AFTER harga;</code>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase small text-white-50">
                                    <th class="ps-3 py-3">Menu</th>
                                    <th class="py-3">Kategori</th>
                                    <th class="py-3">Harga Jual</th>
                                    <th class="py-3">HPP</th>
                                    <th class="py-3">Margin</th>
                                    <th class="py-3">Status Stok</th>
                                    <th class="py-3 text-center pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyMenuMaster">
                                <?php foreach ($list_menu as $m): 
                                    $harga_jual  = (float)($m['harga'] ?? 0);
                                    $harga_modal = (float)($m['harga_modal'] ?? 0);
                                    $margin      = $harga_jual - $harga_modal;
                                ?>
                                    <tr id="menuRow_<?= $m['id_menu']; ?>">
                                        <td class="ps-3 fw-bold">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="../uploads/menu/<?= !empty($m['gambar']) ? $m['gambar'] : 'kopi.png'; ?>" class="rounded-3 menu-img-cell" style="width: 45px; height: 45px; object-fit: cover;" onerror="this.src='../uploads/menu/kopi.png'">
                                                <span class="menu-nama-cell"><?= htmlspecialchars($m['nama_menu']); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-white-50 menu-kat-cell"><?= htmlspecialchars($m['nama_kategori'] ?? '-'); ?></td>
                                        <td class="fw-semibold text-warning menu-harga-cell"><?= rupiah($harga_jual); ?></td>
                                        <td class="text-white-50" style="font-size: 0.88rem;"><?= rupiah($harga_modal); ?></td>
                                        <td class="fw-bold" style="color: <?= $margin >= 0 ? '#2ecc71' : '#e63946'; ?>; font-size: 0.88rem;">
                                            <?= rupiah($margin); ?>
                                        </td>
                                        <td class="menu-status-cell">
                                            <?php if (strtolower($m['status']) == 'tersedia'): ?>
                                                <span class="badge bg-success bg-opacity-25 text-success border border-success">Tersedia</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">Habis</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center pe-3">
                                            <button type="button" class="btn btn-sm btn-outline-info me-1 btn-resep-menu"
                                                data-id="<?= $m['id_menu']; ?>"
                                                data-nama="<?= htmlspecialchars($m['nama_menu'], ENT_QUOTES); ?>">
                                                <i class="fas fa-flask"></i> Resep
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-edit-menu"
                                                data-id="<?= $m['id_menu']; ?>"
                                                data-kat="<?= $m['id_kategori']; ?>"
                                                data-nama="<?= htmlspecialchars($m['nama_menu'], ENT_QUOTES); ?>"
                                                data-desc="<?= htmlspecialchars($m['deskripsi'] ?? '', ENT_QUOTES); ?>"
                                                data-harga="<?= $m['harga']; ?>"
                                                data-modal="<?= $m['harga_modal'] ?? 0; ?>"
                                                data-status="<?= $m['status']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-menu" data-id="<?= $m['id_menu']; ?>" data-nama="<?= htmlspecialchars($m['nama_menu'], ENT_QUOTES); ?>">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- VIEW 4: KELOLA KATEGORI -->
            <div class="tab-pane fade" id="view-kategori" role="tabpanel">
                <div class="table-card p-4 shadow-lg">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold m-0" style="color: var(--accent-gold);"><i class="fas fa-tags me-2"></i>Daftar Kategori Menu</h5>
                            <small class="text-white-50">Atur pengelompokan menu kopi dan minuman toko.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-gold-modal px-3" id="btnOpenTambahKat"><i class="fas fa-plus me-1"></i> Tambah Kategori</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase small text-white-50">
                                    <th class="ps-3 py-3">No</th>
                                    <th class="py-3">Nama Kategori</th>
                                    <th class="py-3">Icon FontAwesome</th>
                                    <th class="py-3 text-center pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyKatMaster">
                                <?php $no = 1;
                                foreach ($list_kategori as $k): ?>
                                    <tr id="katRow_<?= $k['id_kategori']; ?>">
                                        <td class="ps-3 text-white-50"><?= $no++; ?></td>
                                        <td class="fw-bold kat-nama"><?= htmlspecialchars($k['nama_kategori']); ?></td>
                                        <td class="kat-icon"><i class="fas <?= !empty($k['icon']) ? $k['icon'] : 'fa-coffee'; ?> me-2 text-warning"></i><span><?= htmlspecialchars($k['icon'] ?? 'fa-coffee'); ?></span></td>
                                        <td class="text-center pe-3">
                                            <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-edit-kategori"
                                                data-id="<?= $k['id_kategori']; ?>"
                                                data-nama="<?= htmlspecialchars($k['nama_kategori'], ENT_QUOTES); ?>"
                                                data-icon="<?= htmlspecialchars($k['icon'] ?? 'fa-coffee', ENT_QUOTES); ?>">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-kategori" data-id="<?= $k['id_kategori']; ?>" data-nama="<?= htmlspecialchars($k['nama_kategori'], ENT_QUOTES); ?>">
                                                <i class="fas fa-trash me-1"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- VIEW 5: BAHAN BAKU -->
            <div class="tab-pane fade" id="view-bahan" role="tabpanel">
                <div class="table-card p-4 shadow-lg">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold m-0" style="color: var(--accent-gold);"><i class="fas fa-boxes-stacked me-2"></i>Daftar Bahan Baku</h5>
                            <small class="text-white-50">Input harga bahan baku (misal: Biji Kopi 1 kg = Rp 50.000). HPP per menu dihitung otomatis dari resep.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-gold-modal px-3" id="btnOpenTambahBahan">
                            <i class="fas fa-plus me-1"></i> Tambah Bahan
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase small text-white-50">
                                    <th class="ps-3 py-3">Nama Bahan</th>
                                    <th class="py-3">Kemasan</th>
                                    <th class="py-3">Harga Beli</th>
                                    <th class="py-3">Harga Satuan Dasar</th>
                                    <th class="py-3 text-center pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyBahanMaster">
                                <tr><td colspan="5" class="text-center py-4 text-white-50">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- VIEW 6: KEUANGAN -->
            <div class="tab-pane fade" id="view-keuangan" role="tabpanel">
                
                <!-- Filter Periode -->
                <div class="table-card p-3 mb-4 shadow-lg">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label small text-white-50 fw-semibold">Dari Tanggal</label>
                            <input type="date" id="keuTanggalDari" class="form-control form-control-dark" value="<?= date('Y-m-01'); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small text-white-50 fw-semibold">Sampai Tanggal</label>
                            <input type="date" id="keuTanggalSampai" class="form-control form-control-dark" value="<?= date('Y-m-d'); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="button" class="btn btn-gold-modal w-100" onclick="loadKeuanganData();">
                                <i class="fas fa-sync-alt me-1"></i> Terapkan Periode
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Kartu Ringkasan -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-white-50 small fw-medium">Total Omzet</span>
                                    <h4 id="keuOmzet" class="fw-bold m-0 mt-2 text-success">Rp 0</h4>
                                </div>
                                <i class="fas fa-cash-register fa-2x opacity-25 text-success"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-white-50 small fw-medium">Modal Barang (HPP)</span>
                                    <h4 id="keuHpp" class="fw-bold m-0 mt-2 text-warning">Rp 0</h4>
                                </div>
                                <i class="fas fa-box fa-2x opacity-25 text-warning"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-white-50 small fw-medium">Modal Operasional</span>
                                    <h4 id="keuModalOp" class="fw-bold m-0 mt-2 text-danger">Rp 0</h4>
                                </div>
                                <i class="fas fa-receipt fa-2x opacity-25 text-danger"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card" style="border: 1px solid var(--accent-gold);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-white-50 small fw-medium">Laba Bersih</span>
                                    <h4 id="keuLabaBersih" class="fw-bold m-0 mt-2" style="color: var(--accent-gold);">Rp 0</h4>
                                </div>
                                <i class="fas fa-chart-line fa-2x opacity-25" style="color: var(--accent-gold);"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Laba Kotor -->
                <div class="alert mb-4 d-flex justify-content-between align-items-center" style="background: rgba(196, 154, 108, 0.1); border: 1px solid rgba(196, 154, 108, 0.35); border-radius: 14px; padding: 14px 20px;">
                    <span class="fw-semibold text-white"><i class="fas fa-info-circle me-2 text-warning"></i>Laba Kotor (Omzet − HPP)</span>
                    <span id="keuLabaKotor" class="fw-bold fs-5" style="color: var(--accent-gold);">Rp 0</span>
                </div>

                <div class="row g-4">
                    <!-- Rincian per Menu -->
                    <div class="col-12 col-lg-7">
                        <div class="table-card p-3 shadow-lg">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold m-0" style="color: var(--accent-gold);">
                                    <i class="fas fa-coffee me-2"></i>Laba / Rugi per Menu
                                </h6>
                                <small class="text-white-50">Hanya pesanan selesai</small>
                            </div>
                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-dark table-hover align-middle mb-0">
                                    <thead style="position: sticky; top: 0; background: #141414; z-index: 2;">
                                        <tr class="text-uppercase small text-white-50">
                                            <th class="ps-3 py-3">Menu</th>
                                            <th class="py-3 text-center">Terjual</th>
                                            <th class="py-3 text-end">Jual</th>
                                            <th class="py-3 text-end">Modal</th>
                                            <th class="py-3 text-end pe-3">Laba</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyKeuanganMenu">
                                        <tr><td colspan="5" class="text-center py-4 text-white-50">Memuat data...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Operasional -->
                    <div class="col-12 col-lg-5">
                        <div class="table-card p-3 shadow-lg mb-4">
                            <h6 class="fw-bold mb-3" style="color: var(--accent-gold);">
                                <i class="fas fa-plus-circle me-2"></i>Tambah Modal Operasional
                            </h6>
                            <form id="formModalOp">
                                <div class="mb-3">
                                    <label class="form-label small text-white-50 fw-semibold">Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control form-control-dark" placeholder="Contoh: Bayar listrik" required>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small text-white-50 fw-semibold">Jumlah (Rp)</label>
                                        <input type="number" name="jumlah" class="form-control form-control-dark" placeholder="500000" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-white-50 fw-semibold">Tanggal</label>
                                        <input type="date" name="tanggal" class="form-control form-control-dark" value="<?= date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-gold-modal w-100">
                                    <i class="fas fa-save me-1"></i>Simpan Modal
                                </button>
                            </form>
                        </div>

                        <div class="table-card p-3 shadow-lg">
                            <h6 class="fw-bold mb-3" style="color: var(--accent-gold);">
                                <i class="fas fa-list me-2"></i>Riwayat Modal Operasional
                            </h6>
                            <div id="listModalOp" style="max-height: 350px; overflow-y: auto;">
                                <div class="text-center py-3 text-white-50 small">Memuat data...</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- END VIEW 6 -->

        </div>
    </div>

    <!-- MODAL PEMBATALAN PESANAN ADMIN -->
    <div class="modal fade" id="modalBatalPesananAdmin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg text-white" style="background-color: #1a1a1a; border: 1px solid rgba(255,255,255,0.12) !important;">
                <div class="modal-header border-bottom border-secondary border-opacity-25 pb-3">
                    <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Konfirmasi Pembatalan Pesanan</span>
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formBatalPesananAdmin">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_pesanan" id="batal_id_pesanan" value="">
                        <div class="mb-3">
                            <label class="form-label text-white-50 small fw-semibold">Kode Pesanan:</label>
                            <input type="text" id="batal_kode_display" class="form-control bg-dark border-secondary text-warning fw-bold" readonly style="letter-spacing: 0.5px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white-50 small fw-semibold">
                                Alasan Pembatalan <span class="text-danger">*</span> 
                            </label>
                            <textarea name="alasan" id="batal_alasan" class="form-control bg-dark border-secondary text-white" rows="3" placeholder="Contoh: Stok bahan baku kopi habis." required style="resize: none;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary border-opacity-25 p-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 text-white-50 rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold rounded-3" id="btnSubmitBatal">
                            <i class="fas fa-ban me-1"></i> Batalkan Pesanan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL FORM TAMBAH / EDIT MENU -->
    <div class="modal fade" id="modalMenuForm" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary rounded-4 shadow-lg">
                <div class="modal-header border-bottom border-secondary">
                    <h6 class="modal-title fw-bold" style="color: var(--accent-gold);" id="modalMenuTitle">Tambah Menu Baru</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formMenuAction" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" id="menuActionType" value="tambah_menu">
                        <input type="hidden" name="id_menu" id="menuIdField">

                        <div class="mb-3">
                            <label class="form-label small text-white-50 fw-semibold">Nama Menu Kopi</label>
                            <input type="text" name="nama_menu" id="menuNamaField" class="form-control form-control-dark" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-white-50 fw-semibold">Kategori Menu</label>
                            <select name="id_kategori" id="menuKatField" class="form-control form-control-dark" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($list_kategori as $k): ?>
                                    <option value="<?= $k['id_kategori']; ?>"><?= htmlspecialchars($k['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-white-50 fw-semibold">Harga Jual (Rp)</label>
                                <input type="number" name="harga" id="menuHargaField" class="form-control form-control-dark" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-white-50 fw-semibold">HPP / Modal (Rp)</label>
                                <input type="number" name="harga_modal" id="menuModalField" class="form-control form-control-dark" value="0" required>
                                <small class="text-white-50" style="font-size: 0.7rem;">Akan otomatis terisi setelah atur resep.</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-white-50 fw-semibold">Deskripsi Singkat</label>
                            <textarea name="deskripsi" id="menuDescField" class="form-control form-control-dark" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-white-50 fw-semibold">Status Stok</label>
                            <select name="status" id="menuStatusField" class="form-control form-control-dark">
                                <option value="Tersedia">Tersedia</option>
                                <option value="Habis">Habis</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-white-50 fw-semibold">Foto Gambar Menu</label>
                            <input type="file" name="gambar" class="form-control form-control-dark" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary p-3">
                        <button type="button" class="btn btn-sm btn-outline-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-gold-modal px-4"><i class="fas fa-save me-1"></i> Simpan Menu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL FORM TAMBAH / EDIT KATEGORI -->
    <div class="modal fade" id="modalKatForm" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary rounded-4 shadow-lg">
                <div class="modal-header border-bottom border-secondary">
                    <h6 class="modal-title fw-bold" style="color: var(--accent-gold);" id="modalKatTitle">Tambah Kategori</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formKatAction">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" id="katActionType" value="tambah_kategori">
                        <input type="hidden" name="id_kategori" id="katIdField">
                        <div class="mb-3">
                            <label class="form-label small text-white-50 fw-semibold">Nama Kategori</label>
                            <input type="text" name="nama_kategori" id="katNamaField" class="form-control form-control-dark" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-white-50 fw-semibold">Ikon FontAwesome (Contoh: fa-coffee)</label>
                            <input type="text" name="icon" id="katIconField" class="form-control form-control-dark" placeholder="fa-coffee" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary p-3">
                        <button type="button" class="btn btn-sm btn-outline-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-gold-modal px-4"><i class="fas fa-save me-1"></i> Simpan Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH/EDIT BAHAN BAKU -->
    <div class="modal fade" id="modalBahanForm" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary rounded-4 shadow-lg">
                <div class="modal-header border-bottom border-secondary">
                    <h6 class="modal-title fw-bold" style="color: var(--accent-gold);" id="modalBahanTitle">Tambah Bahan Baku</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formBahanAction">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" id="bahanActionType" value="tambah">
                        <input type="hidden" name="id_bahan" id="bahanIdField">

                        <div class="mb-3">
                            <label class="form-label small text-white-50 fw-semibold">Nama Bahan</label>
                            <input type="text" name="nama_bahan" id="bahanNamaField" class="form-control form-control-dark" placeholder="Contoh: Biji Kopi Arabika" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-white-50 fw-semibold">Jumlah Kemasan</label>
                                <input type="number" step="0.01" name="kemasan_jumlah" id="bahanJumlahField" class="form-control form-control-dark" value="1" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-white-50 fw-semibold">Satuan Kemasan</label>
                                <select name="kemasan_satuan" id="bahanSatuanField" class="form-control form-control-dark" required>
                                    <option value="kg">Kilogram (kg)</option>
                                    <option value="liter">Liter (L)</option>
                                    <option value="pcs">Pcs / Buah</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-white-50 fw-semibold">Harga Beli Kemasan (Rp)</label>
                            <input type="number" name="harga_beli" id="bahanHargaField" class="form-control form-control-dark" placeholder="50000" required>
                            <small class="text-white-50" style="font-size: 0.72rem;">Contoh: 1 kg = Rp 50.000, isi 50000 di sini.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary p-3">
                        <button type="button" class="btn btn-sm btn-outline-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-gold-modal px-4"><i class="fas fa-save me-1"></i> Simpan Bahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL RESEP MENU -->
    <div class="modal fade" id="modalResepForm" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark text-white border-secondary rounded-4 shadow-lg">
                <div class="modal-header border-bottom border-secondary">
                    <h6 class="modal-title fw-bold" style="color: var(--accent-gold);">
                        <i class="fas fa-flask me-2"></i>Resep Menu: <span id="resepMenuNama" class="text-white"></span>
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="resepIdMenu">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <button type="button" class="btn btn-sm btn-outline-light" onclick="tambahBarisResep()">
                            <i class="fas fa-plus me-1"></i> Tambah Bahan
                        </button>
                        <small class="text-white-50">HPP dihitung otomatis dari harga bahan × jumlah pakai</small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-dark align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase small text-white-50">
                                    <th style="width: 45%;">Bahan</th>
                                    <th style="width: 25%;">Jumlah Pakai</th>
                                    <th style="width: 20%;" class="text-end">Subtotal</th>
                                    <th style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyResepForm">
                            </tbody>
                            <tfoot>
                                <tr class="border-top border-secondary">
                                    <td colspan="2" class="fw-bold text-uppercase">Total HPP</td>
                                    <td colspan="2" class="text-end fw-bold fs-5" style="color: var(--accent-gold);" id="resepTotalHpp">Rp 0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary p-3">
                    <button type="button" class="btn btn-sm btn-outline-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-sm btn-gold-modal px-4" onclick="simpanResep()">
                        <i class="fas fa-save me-1"></i> Simpan Resep
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL UNMUTE AUDIO -->
    <div class="modal fade" id="modalAudioSetup" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary rounded-4 shadow-lg text-center p-4">
                <div class="mb-3 text-warning">
                    <i class="fas fa-bell fa-4x"></i>
                </div>
                <h4 class="fw-bold mb-2" style="color: var(--accent-gold);">Aktifkan Bel Notifikasi Admin</h4>
                <p class="text-white-50 small mb-4">
                    Sistem memerlukan izin audio agar bel kasir otomatis berbunyi saat ada pesanan baru.
                </p>
                <button id="btnStartAdminSystem" type="button" class="btn btn-gold-modal w-100 py-3 text-uppercase">
                    <i class="fas fa-volume-up me-2"></i> Masuk & Aktifkan Notifikasi Suara
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Preview Bukti Transfer -->
    <div class="modal fade" id="modalPreviewBukti" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border border-secondary rounded-4">
                <div class="modal-header border-bottom border-secondary">
                    <h6 class="modal-title fw-bold">Bukti Transfer Pembayaran</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-3">
                    <img id="imgBuktiPreview" src="" class="img-fluid rounded-3 shadow" style="max-height: 450px; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            customClass: { popup: 'swal2-toast-custom' }
        });

        function konfirmasiLogout() {
            Swal.fire({
                title: 'Keluar dari Panel Admin?',
                text: "Sesi kerja Anda akan diakhiri.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt me-1.5"></i> Ya, Keluar',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'swal2-popup-delete-custom',
                    title: 'swal2-title-delete',
                    confirmButton: 'swal2-confirm-btn-delete',
                    cancelButton: 'swal2-cancel-btn-delete'
                },
                showClass: { popup: 'animate__animated animate__zoomIn animate__faster' },
                hideClass: { popup: 'animate__animated animate__zoomOut animate__faster' },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) window.location.href = 'logout.php';
            });
        }

        function konfirmasiBatalPesanan(idPesanan, kodePesanan) {
            const inputId = document.getElementById('batal_id_pesanan');
            const inputKode = document.getElementById('batal_kode_display');
            const inputAlasan = document.getElementById('batal_alasan');
            if (inputId) inputId.value = idPesanan;
            if (inputKode) inputKode.value = kodePesanan;
            if (inputAlasan) inputAlasan.value = '';
            const modalEl = document.getElementById('modalBatalPesananAdmin');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.show();
            }
        }

        function ubahStatusPesanan(idPesanan, statusBaru, kodePesanan = '') {
            let namaStatus = statusBaru.toUpperCase();
            Swal.fire({
                title: 'Ubah Status Pesanan?',
                text: `Ubah status pesanan #${kodePesanan || idPesanan} menjadi ${namaStatus}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Ubah Status',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#c49a6c',
                background: '#141414',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    let formData = new FormData();
                    formData.append('id_pesanan', idPesanan);
                    formData.append('kode', kodePesanan);
                    formData.append('status', statusBaru);
                    fetch('api-update-status.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Toast.fire({ icon: 'success', title: data.message });
                                checkNewOrders();
                            } else {
                                Swal.fire({ icon: 'error', title: 'Gagal', text: data.message, background: '#141414', color: '#fff' });
                            }
                        })
                        .catch(err => {
                            console.error('Error Update Status:', err);
                            Swal.fire({ icon: 'error', title: 'Kesalahan Sistem', text: 'Gagal menghubungi server.', background: '#141414', color: '#fff' });
                        });
                }
            });
        }

        /* =========================================================
           KONTROL TOKO
           ========================================================= */
        function setTokoStatus(status) {
            let formData = new FormData();
            formData.append('action', status);
            fetch('api-kelola-toko.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    Toast.fire({ icon: data.status === 'success' ? 'success' : 'error', title: data.message });
                    const label = document.getElementById('statusTokoLabel');
                    if (status === 'open') label.innerHTML = '<span class="text-success fw-bold">Sedang BUKA (Manual)</span>';
                    else if (status === 'close') label.innerHTML = '<span class="text-danger fw-bold">Sedang TUTUP (Manual)</span>';
                    else label.innerHTML = 'Mengikuti Jadwal Operasional';
                })
                .catch(err => console.error('Error update toko:', err));
        }

        function getTokoStatus() {
            let isOpen = <?= isTokoOpen() ? 'true' : 'false'; ?>;
            const label = document.getElementById('statusTokoLabel');
            if (label) {
                if (isOpen) label.innerHTML = '<span class="text-success fw-bold">Sedang Buka</span>';
                else label.innerHTML = '<span class="text-danger fw-bold">Sedang Tutup</span>';
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            getTokoStatus();

            document.querySelectorAll('#mainSidebarTabs button').forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetHash = this.getAttribute('data-bs-target');
                    if (targetHash) history.replaceState(null, null, targetHash);
                });
            });

            const activeTabHash = window.location.hash;
            if (activeTabHash) {
                const activeTabBtn = document.querySelector(`#mainSidebarTabs button[data-bs-target="${activeTabHash}"]`);
                if (activeTabBtn) {
                    const tabTrigger = new bootstrap.Tab(activeTabBtn);
                    tabTrigger.show();
                    document.getElementById('currentViewTitle').textContent = activeTabBtn.innerText.trim();
                }
            }

            let lastPendingCount = null;
            let lastSelesaiCount = null;
            let audioCtx = null;
            const modalAudio = new bootstrap.Modal(document.getElementById('modalAudioSetup'));
            const btnStart = document.getElementById('btnStartAdminSystem');
            const audioBadge = document.getElementById('audioStatusBadge');

            modalAudio.show();

            function playOrderBellSound() {
                try {
                    if (!audioCtx) return;
                    if (audioCtx.state === 'suspended') audioCtx.resume();
                    const osc1 = audioCtx.createOscillator();
                    const gain1 = audioCtx.createGain();
                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(520, audioCtx.currentTime);
                    gain1.gain.setValueAtTime(0.4, audioCtx.currentTime);
                    gain1.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
                    osc1.connect(gain1); gain1.connect(audioCtx.destination);
                    osc1.start(); osc1.stop(audioCtx.currentTime + 0.35);

                    setTimeout(() => {
                        if (!audioCtx) return;
                        const osc2 = audioCtx.createOscillator();
                        const gain2 = audioCtx.createGain();
                        osc2.type = 'sine';
                        osc2.frequency.setValueAtTime(880, audioCtx.currentTime);
                        gain2.gain.setValueAtTime(0.5, audioCtx.currentTime);
                        gain2.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.65);
                        osc2.connect(gain2); gain2.connect(audioCtx.destination);
                        osc2.start(); osc2.stop(audioCtx.currentTime + 0.65);
                    }, 160);
                } catch (e) { console.error("Audio Error:", e); }
            }

            if (btnStart) {
                btnStart.addEventListener('click', function() {
                    audioCtx = new(window.AudioContext || window.webkitAudioContext)();
                    audioCtx.resume().then(() => {
                        playOrderBellSound();
                        modalAudio.hide();
                        if (audioBadge) {
                            audioBadge.className = "badge bg-success bg-opacity-25 text-success border border-success px-3 py-2 rounded-3";
                            audioBadge.innerHTML = `<i class="fas fa-volume-up me-1"></i> Bel Notifikasi Aktif`;
                        }
                    });
                });
            }

            function renderTableRows(items) {
                if (!items || items.length === 0) {
                    return `<tr><td colspan="7" class="text-center py-4 text-white-50">Tidak ada pesanan pada shift ini.</td></tr>`;
                }
                let html = '';
                items.forEach(p => {
                    let buktiBtn = p.bukti_transfer ?
                        `<button class="btn btn-sm btn-outline-success border-0 bg-success bg-opacity-10 text-success fw-semibold btn-preview-tf" data-img="../uploads/bukti/${p.bukti_transfer}"><i class="fas fa-image me-1"></i> Ada Bukti</button>` :
                        `<span class="badge bg-secondary bg-opacity-25 text-white-50 border border-secondary">Belum TF</span>`;

                    let statusLC = (p.status || '').toLowerCase().trim();
                    let btnStatusHtml = '';
                    if (statusLC === 'pending') {
                        btnStatusHtml = `<button type="button" class="btn btn-sm btn-info me-1 fw-bold" onclick="ubahStatusPesanan(${p.id_pesanan}, 'diproses', '${p.kode_pesanan}')"><i class="fas fa-fire me-1"></i> Proses</button>`;
                    } else if (statusLC === 'diproses') {
                        btnStatusHtml = `<button type="button" class="btn btn-sm btn-success me-1 fw-bold" onclick="ubahStatusPesanan(${p.id_pesanan}, 'selesai', '${p.kode_pesanan}')"><i class="fas fa-check-circle me-1"></i> Selesai</button>`;
                    }

                    let btnBatalHtml = (statusLC !== 'dibatalkan' && statusLC !== 'selesai') ?
                        `<button type="button" class="btn btn-sm btn-outline-danger" onclick="konfirmasiBatalPesanan(${p.id_pesanan}, '${p.kode_pesanan}')"><i class="fas fa-times me-1"></i> Batalkan</button>` : '';

                    let rawHp = (p.no_hp || '').replace(/[^0-9]/g, '');
                    let waNum = rawHp.startsWith('0') ? '62' + rawHp.substring(1) : rawHp;
                    let textWa = encodeURIComponent(`Halo Kak ${p.nama_pelanggan || 'Pelanggan'}, mengenai pesanan #${p.kode_pesanan || ''} di Ranger Coffee...`);
                    let btnWaHtml = `<a href="https://wa.me/${waNum}?text=${textWa}" target="_blank" class="btn btn-sm btn-success border-0 px-2 py-1 fw-bold rounded-2"><i class="fab fa-whatsapp me-1"></i> ${p.no_hp}</a>`;

                    html += `
                    <tr>
                        <td class="ps-3 fw-bold" style="color: var(--accent-gold);">${p.kode_pesanan || '-'}</td>
                        <td class="fw-medium">${p.nama_pelanggan || 'Pelanggan'}</td>
                        <td>${btnWaHtml}</td>
                        <td class="text-white-50 small">${p.formatted_date || '-'}</td>
                        <td class="fw-semibold text-white">${p.total_harga_rp || 'Rp 0'}</td>
                        <td class="text-center">${buktiBtn}</td>
                        <td class="text-center pe-3">
                            ${btnStatusHtml}
                            <a href="detail.php?id=${p.id_pesanan}" class="btn btn-sm btn-outline-light border-secondary me-1">
                                <i class="fas fa-eye me-1"></i> Detail / Ubah
                            </a>
                            ${btnBatalHtml}
                        </td>
                    </tr>`;
                });
                return html;
            }

            function checkNewOrders() {
                fetch('api-cek-pesanan.php')
                    .then(response => {
                        if (!response.ok) throw new Error('HTTP Error Status: ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'unauthorized') { console.warn('Sesi admin berakhir.'); return; }
                        if (data.status === 'error') { console.error('Database Error:', data.message); return; }

                        if (document.getElementById('statTotalPending')) document.getElementById('statTotalPending').textContent = data.total_pending ?? 0;
                        if (document.getElementById('statTotalDiproses')) document.getElementById('statTotalDiproses').textContent = data.total_diproses ?? 0;
                        if (document.getElementById('statTotalSelesai')) document.getElementById('statTotalSelesai').textContent = data.total_selesai ?? 0;
                        if (document.getElementById('statTotalOmset')) document.getElementById('statTotalOmset').textContent = data.total_omset ?? 'Rp 0';

                        if (document.getElementById('cntPending')) document.getElementById('cntPending').textContent = data.total_pending ?? 0;
                        if (document.getElementById('cntDiproses')) document.getElementById('cntDiproses').textContent = data.total_diproses ?? 0;
                        if (document.getElementById('cntSelesai')) document.getElementById('cntSelesai').textContent = data.total_selesai ?? 0;
                        if (document.getElementById('cntBatal')) document.getElementById('cntBatal').textContent = data.total_batal ?? 0;

                        const currentPending = parseInt(data.total_pending) || 0;
                        const currentSelesai = parseInt(data.total_selesai) || 0;

                        if (lastPendingCount !== null && currentPending > lastPendingCount) {
                            playOrderBellSound();
                            Toast.fire({ icon: 'info', title: 'Ada Pesanan Baru Masuk!' });
                        }
                        if (lastSelesaiCount !== null && currentSelesai > lastSelesaiCount) {
                            Toast.fire({ icon: 'success', title: 'Pesanan Telah Selesai!' });
                        }

                        lastPendingCount = currentPending;
                        lastSelesaiCount = currentSelesai;

                        if (document.getElementById('tbodyPending')) document.getElementById('tbodyPending').innerHTML = renderTableRows(data.pesanan_pending || []);
                        if (document.getElementById('tbodyDiproses')) document.getElementById('tbodyDiproses').innerHTML = renderTableRows(data.pesanan_diproses || []);
                        if (document.getElementById('tbodySelesai')) document.getElementById('tbodySelesai').innerHTML = renderTableRows(data.pesanan_selesai || []);
                        if (document.getElementById('tbodyBatal')) document.getElementById('tbodyBatal').innerHTML = renderTableRows(data.pesanan_batal || []);
                    })
                    .catch(error => console.error('Error Polling Dashboard:', error));
            }

            const formBatal = document.getElementById('formBatalPesananAdmin');
            if (formBatal) {
                formBatal.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btnSubmit = document.getElementById('btnSubmitBatal');
                    const originalBtnContent = btnSubmit ? btnSubmit.innerHTML : '';
                    if (btnSubmit) { btnSubmit.disabled = true; btnSubmit.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> Memproses...`; }

                    const formData = new FormData(formBatal);
                    fetch('../api-batal-pesanan.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(res => res.json())
                        .then(data => {
                            if (btnSubmit) { btnSubmit.disabled = false; btnSubmit.innerHTML = originalBtnContent; }
                            if (data.status === 'success') {
                                const modalEl = document.getElementById('modalBatalPesananAdmin');
                                if (modalEl) {
                                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                                    if (modalInstance) modalInstance.hide();
                                }
                                Toast.fire({ icon: 'success', title: 'Pesanan Berhasil Dibatalkan!' });
                                checkNewOrders();
                            } else {
                                Swal.fire({ icon: 'error', title: 'Gagal Membatalkan', text: data.message || 'Terjadi kesalahan pada server.' });
                            }
                        })
                        .catch(err => {
                            console.error('Error Batal Pesanan:', err);
                            if (btnSubmit) { btnSubmit.disabled = false; btnSubmit.innerHTML = originalBtnContent; }
                        });
                });
            }

            /* =========================================================
               KATEGORI
               ========================================================= */
            const modalKat = new bootstrap.Modal(document.getElementById('modalKatForm'));

            document.getElementById('btnOpenTambahKat').addEventListener('click', function() {
                document.getElementById('modalKatTitle').textContent = "Tambah Kategori Baru";
                document.getElementById('katActionType').value = "tambah_kategori";
                document.getElementById('katIdField').value = "";
                document.getElementById('katNamaField').value = "";
                document.getElementById('katIconField').value = "fa-coffee";
                modalKat.show();
            });

            document.addEventListener('click', function(e) {
                const btnEdit = e.target.closest('.btn-edit-kategori');
                if (btnEdit) {
                    document.getElementById('modalKatTitle').textContent = "Edit Kategori";
                    document.getElementById('katActionType').value = "edit_kategori";
                    document.getElementById('katIdField').value = btnEdit.getAttribute('data-id');
                    document.getElementById('katNamaField').value = btnEdit.getAttribute('data-nama');
                    document.getElementById('katIconField').value = btnEdit.getAttribute('data-icon');
                    modalKat.show();
                }

                const btnHapus = e.target.closest('.btn-hapus-kategori');
                if (btnHapus) {
                    const id = btnHapus.getAttribute('data-id');
                    const nama = btnHapus.getAttribute('data-nama');
                    Swal.fire({
                        title: 'Hapus Kategori ini?',
                        html: `Apakah Anda yakin ingin menghapus kategori <br><strong style="color: var(--accent-gold); font-size: 1.1rem;">"${nama}"</strong>?<br><small class="text-white-50 mt-1 d-block">Semua menu di dalam kategori ini juga akan ikut terhapus.</small>`,
                        icon: 'warning',
                        iconColor: '#e63946',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-trash-alt me-1.5"></i> Ya, Hapus Sekarang',
                        cancelButtonText: 'Batal',
                        customClass: {
                            popup: 'swal2-popup-delete-custom',
                            title: 'swal2-title-delete',
                            confirmButton: 'swal2-confirm-btn-delete',
                            cancelButton: 'swal2-cancel-btn-delete'
                        },
                        showClass: { popup: 'animate__animated animate__zoomIn animate__faster' },
                        hideClass: { popup: 'animate__animated animate__zoomOut animate__faster' },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const fd = new window.FormData();
                            fd.append('action', 'hapus_kategori');
                            fd.append('id_kategori', id);
                            fetch('api-kelola.php', { method: 'POST', body: fd })
                                .then(res => res.json())
                                .then(res => {
                                    Toast.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message });
                                    if (res.status === 'success') setTimeout(() => window.location.reload(), 800);
                                });
                        }
                    });
                }
            });

            document.getElementById('formKatAction').addEventListener('submit', function(e) {
                e.preventDefault();
                const fd = new window.FormData(this);
                fetch('api-kelola.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(res => {
                        modalKat.hide();
                        Toast.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message });
                        if (res.status === 'success') setTimeout(() => window.location.reload(), 800);
                    });
            });

            /* =========================================================
               MENU
               ========================================================= */
            const modalMenu = new bootstrap.Modal(document.getElementById('modalMenuForm'));

            document.getElementById('btnOpenTambahMenu').addEventListener('click', function() {
                document.getElementById('modalMenuTitle').textContent = "Tambah Menu Kopi Baru";
                document.getElementById('menuActionType').value = "tambah_menu";
                document.getElementById('menuIdField').value = "";
                document.getElementById('menuNamaField').value = "";
                document.getElementById('menuHargaField').value = "";
                document.getElementById('menuModalField').value = 0;
                document.getElementById('menuDescField').value = "";
                document.getElementById('menuKatField').value = "";
                document.getElementById('menuStatusField').value = "Tersedia";
                modalMenu.show();
            });

            document.addEventListener('click', function(e) {
                const btnEdit = e.target.closest('.btn-edit-menu');
                if (btnEdit) {
                    document.getElementById('modalMenuTitle').textContent = "Edit Menu Kopi";
                    document.getElementById('menuActionType').value = "edit_menu";
                    document.getElementById('menuIdField').value = btnEdit.getAttribute('data-id');
                    document.getElementById('menuNamaField').value = btnEdit.getAttribute('data-nama');
                    document.getElementById('menuHargaField').value = btnEdit.getAttribute('data-harga');
                    document.getElementById('menuModalField').value = btnEdit.getAttribute('data-modal') || 0;
                    document.getElementById('menuDescField').value = btnEdit.getAttribute('data-desc');
                    document.getElementById('menuKatField').value = btnEdit.getAttribute('data-kat');
                    document.getElementById('menuStatusField').value = btnEdit.getAttribute('data-status');
                    modalMenu.show();
                }

                const btnHapus = e.target.closest('.btn-hapus-menu');
                if (btnHapus) {
                    const id = btnHapus.getAttribute('data-id');
                    const nama = btnHapus.getAttribute('data-nama');
                    Swal.fire({
                        title: 'Hapus Menu ini?',
                        html: `Apakah Anda yakin ingin menghapus menu <br><strong style="color: var(--accent-gold); font-size: 1.1rem;">"${nama}"</strong> secara permanen?`,
                        icon: 'warning',
                        iconColor: '#e63946',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-trash-alt me-1.5"></i> Ya, Hapus Menu',
                        cancelButtonText: 'Batal',
                        customClass: {
                            popup: 'swal2-popup-delete-custom',
                            title: 'swal2-title-delete',
                            confirmButton: 'swal2-confirm-btn-delete',
                            cancelButton: 'swal2-cancel-btn-delete'
                        },
                        showClass: { popup: 'animate__animated animate__zoomIn animate__faster' },
                        hideClass: { popup: 'animate__animated animate__zoomOut animate__faster' },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const fd = new window.FormData();
                            fd.append('action', 'hapus_menu');
                            fd.append('id_menu', id);
                            fetch('api-kelola.php', { method: 'POST', body: fd })
                                .then(res => res.json())
                                .then(res => {
                                    Toast.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message });
                                    if (res.status === 'success') setTimeout(() => window.location.reload(), 800);
                                });
                        }
                    });
                }
            });

            document.getElementById('formMenuAction').addEventListener('submit', function(e) {
                e.preventDefault();
                const fd = new window.FormData(this);
                fetch('api-kelola.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(res => {
                        modalMenu.hide();
                        Toast.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message });
                        if (res.status === 'success') setTimeout(() => window.location.reload(), 800);
                    });
            });

            document.querySelectorAll('#mainSidebarTabs button').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.id;
                    let titleMap = {
                        'tab-btn-overview': 'Dashboard Overview',
                        'tab-btn-pesanan': 'Kelola Semua Pesanan',
                        'tab-btn-menu': 'Kelola Menu Kopi',
                        'tab-btn-kategori': 'Kelola Kategori Menu',
                        'tab-btn-bahan': 'Kelola Bahan Baku',
                        'tab-btn-keuangan': 'Keuangan & Laba Rugi'
                    };
                    document.getElementById('currentViewTitle').textContent = titleMap[tabId] || this.innerText.trim();
                });
            });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-preview-tf');
                if (btn) {
                    const imgUrl = btn.getAttribute('data-img');
                    const modalImg = document.getElementById('imgBuktiPreview');
                    if (modalImg && imgUrl) {
                        modalImg.src = imgUrl;
                        const modal = new bootstrap.Modal(document.getElementById('modalPreviewBukti'));
                        modal.show();
                    }
                }
            });

            /* =========================================================
               BAHAN BAKU + RESEP
               ========================================================= */
            function hitungHargaPerBase(kemasan_jumlah, kemasan_satuan, harga_beli) {
                let multiplier = 1, satuanBase = 'pcs';
                if (kemasan_satuan === 'kg') { multiplier = 1000; satuanBase = 'gram'; }
                else if (kemasan_satuan === 'liter') { multiplier = 1000; satuanBase = 'ml'; }
                const totalBase = kemasan_jumlah * multiplier;
                return { satuanBase: satuanBase, hargaPerBase: totalBase > 0 ? (harga_beli / totalBase) : 0 };
            }

            window.masterBahan = [];

            window.loadBahanBaku = function() {
                fetch('api-bahan-baku.php?action=list')
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            window.masterBahan = data.data;
                            const tbody = document.getElementById('tbodyBahanMaster');
                            if (data.data.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-white-50">Belum ada bahan baku. Klik "Tambah Bahan" untuk mulai.</td></tr>';
                                return;
                            }
                            let html = '';
                            data.data.forEach(b => {
                                html += `
                                    <tr>
                                        <td class="ps-3 fw-bold text-white">${b.nama_bahan}</td>
                                        <td class="text-white-50">${b.kemasan_jumlah} ${b.kemasan_satuan}</td>
                                        <td class="fw-semibold text-warning">${b.harga_beli_rp}</td>
                                        <td><span class="badge bg-secondary bg-opacity-25 text-warning border border-secondary">${b.harga_per_base_rp}</span></td>
                                        <td class="text-center pe-3">
                                            <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-edit-bahan"
                                                data-id="${b.id_bahan}"
                                                data-nama="${b.nama_bahan}"
                                                data-jumlah="${b.kemasan_jumlah}"
                                                data-satuan="${b.kemasan_satuan}"
                                                data-harga="${b.harga_beli}">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-bahan"
                                                data-id="${b.id_bahan}"
                                                data-nama="${b.nama_bahan}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>`;
                            });
                            tbody.innerHTML = html;
                        }
                    })
                    .catch(err => console.error('Error load bahan:', err));
            };

            const modalBahan = new bootstrap.Modal(document.getElementById('modalBahanForm'));

            document.getElementById('btnOpenTambahBahan').addEventListener('click', function() {
                document.getElementById('modalBahanTitle').textContent = "Tambah Bahan Baku";
                document.getElementById('bahanActionType').value = "tambah";
                document.getElementById('bahanIdField').value = "";
                document.getElementById('bahanNamaField').value = "";
                document.getElementById('bahanJumlahField').value = 1;
                document.getElementById('bahanSatuanField').value = "kg";
                document.getElementById('bahanHargaField').value = "";
                modalBahan.show();
            });

            document.addEventListener('click', function(e) {
                const btnEdit = e.target.closest('.btn-edit-bahan');
                if (btnEdit) {
                    document.getElementById('modalBahanTitle').textContent = "Edit Bahan Baku";
                    document.getElementById('bahanActionType').value = "edit";
                    document.getElementById('bahanIdField').value = btnEdit.getAttribute('data-id');
                    document.getElementById('bahanNamaField').value = btnEdit.getAttribute('data-nama');
                    document.getElementById('bahanJumlahField').value = btnEdit.getAttribute('data-jumlah');
                    document.getElementById('bahanSatuanField').value = btnEdit.getAttribute('data-satuan');
                    document.getElementById('bahanHargaField').value = btnEdit.getAttribute('data-harga');
                    modalBahan.show();
                }

                const btnHapus = e.target.closest('.btn-hapus-bahan');
                if (btnHapus) {
                    const id = btnHapus.getAttribute('data-id');
                    const nama = btnHapus.getAttribute('data-nama');
                    Swal.fire({
                        title: 'Hapus Bahan Baku?',
                        html: `Yakin ingin menghapus <strong style="color: var(--accent-gold);">"${nama}"</strong>?<br><small class="text-white-50">Resep yang menggunakan bahan ini juga akan dihapus.</small>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#e63946',
                        cancelButtonColor: '#444',
                        background: '#141414',
                        color: '#fff'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const fd = new FormData();
                            fd.append('action', 'hapus');
                            fd.append('id_bahan', id);
                            fetch('api-bahan-baku.php', { method: 'POST', body: fd })
                                .then(res => res.json())
                                .then(data => {
                                    Toast.fire({ icon: data.status === 'success' ? 'success' : 'error', title: data.message });
                                    if (data.status === 'success') window.loadBahanBaku();
                                });
                        }
                    });
                }
            });

            document.getElementById('formBahanAction').addEventListener('submit', function(e) {
                e.preventDefault();
                const fd = new FormData(this);
                fetch('api-bahan-baku.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        Toast.fire({ icon: data.status === 'success' ? 'success' : 'error', title: data.message });
                        if (data.status === 'success') {
                            modalBahan.hide();
                            window.loadBahanBaku();
                        }
                    })
                    .catch(err => Toast.fire({ icon: 'error', title: 'Gagal simpan: ' + err.message }));
            });

            // ============= RESEP =============
            const modalResep = new bootstrap.Modal(document.getElementById('modalResepForm'));

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-resep-menu');
                if (btn) {
                    const idMenu = btn.getAttribute('data-id');
                    const namaMenu = btn.getAttribute('data-nama');
                    document.getElementById('resepIdMenu').value = idMenu;
                    document.getElementById('resepMenuNama').textContent = namaMenu;
                    document.getElementById('tbodyResepForm').innerHTML = '';
                    document.getElementById('resepTotalHpp').textContent = 'Rp 0';
                    modalResep.show();

                    fetch(`api-bahan-baku.php?action=get_resep&id_menu=${idMenu}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success' && data.data.length > 0) {
                                data.data.forEach(r => window.tambahBarisResep(r.id_bahan, r.jumlah_pakai));
                                window.hitungTotalResep();
                            }
                        });
                }
            });

            window.tambahBarisResep = function(idBahan = '', jumlah = '') {
                const tbody = document.getElementById('tbodyResepForm');
                const tr = document.createElement('tr');
                const options = window.masterBahan.map(b => 
                    `<option value="${b.id_bahan}" ${idBahan == b.id_bahan ? 'selected' : ''}>${b.nama_bahan} (${b.harga_per_base_rp})</option>`
                ).join('');

                tr.innerHTML = `
                    <td>
                        <select class="form-control form-control-dark resep-bahan" onchange="hitungTotalResep()" required>
                            <option value="">-- Pilih Bahan --</option>
                            ${options}
                        </select>
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control form-control-dark resep-jumlah" value="${jumlah}" oninput="hitungTotalResep()" required>
                            <span class="input-group-text bg-secondary bg-opacity-25 text-white border-secondary resep-satuan">gram</span>
                        </div>
                    </td>
                    <td class="text-end fw-bold resep-subtotal" style="color: var(--accent-gold);">Rp 0</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="this.closest('tr').remove(); hitungTotalResep();">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);

                if (idBahan) {
                    const bahan = window.masterBahan.find(b => b.id_bahan == idBahan);
                    if (bahan) tr.querySelector('.resep-satuan').textContent = bahan.satuan_base;
                }
            };

            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('resep-bahan')) {
                    const idBahan = e.target.value;
                    const bahan = window.masterBahan.find(b => b.id_bahan == idBahan);
                    const tr = e.target.closest('tr');
                    if (bahan) tr.querySelector('.resep-satuan').textContent = bahan.satuan_base;
                    window.hitungTotalResep();
                }
            });

            window.hitungTotalResep = function() {
                let total = 0;
                document.querySelectorAll('#tbodyResepForm tr').forEach(tr => {
                    const idBahan = tr.querySelector('.resep-bahan').value;
                    const jumlah = parseFloat(tr.querySelector('.resep-jumlah').value) || 0;
                    const bahan = window.masterBahan.find(b => b.id_bahan == idBahan);
                    if (bahan && jumlah > 0) {
                        const subtotal = bahan.harga_per_base * jumlah;
                        tr.querySelector('.resep-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
                        total += subtotal;
                    } else {
                        tr.querySelector('.resep-subtotal').textContent = 'Rp 0';
                    }
                });
                document.getElementById('resepTotalHpp').textContent = 'Rp ' + total.toLocaleString('id-ID');
                return total;
            };

            window.simpanResep = function() {
                const idMenu = document.getElementById('resepIdMenu').value;
                const items = [];
                document.querySelectorAll('#tbodyResepForm tr').forEach(tr => {
                    const idBahan = tr.querySelector('.resep-bahan').value;
                    const jumlah = parseFloat(tr.querySelector('.resep-jumlah').value) || 0;
                    if (idBahan && jumlah > 0) {
                        items.push({ id_bahan: idBahan, jumlah_pakai: jumlah });
                    }
                });

                if (items.length === 0) {
                    Toast.fire({ icon: 'warning', title: 'Tambahkan minimal 1 bahan ke resep!' });
                    return;
                }

                const fd = new FormData();
                fd.append('action', 'simpan_resep');
                fd.append('id_menu', idMenu);
                fd.append('items', JSON.stringify(items));

                fetch('api-bahan-baku.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        Toast.fire({ icon: data.status === 'success' ? 'success' : 'error', title: data.message });
                        if (data.status === 'success') {
                            modalResep.hide();
                            setTimeout(() => window.location.reload(), 800);
                        }
                    })
                    .catch(err => Toast.fire({ icon: 'error', title: 'Gagal simpan resep: ' + err.message }));
            };

            window.loadBahanBaku();

            document.querySelectorAll('#mainSidebarTabs button').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.id === 'tab-btn-bahan') window.loadBahanBaku();
                });
            });

            /* =========================================================
               KEUANGAN - LABA RUGI
               ========================================================= */
            async function fetchKeuangan(url, options = {}) {
                const res = await fetch(url, options);
                if (!res.ok) throw new Error(`HTTP ${res.status} — Pastikan file api-keuangan.php ada & SQL migration sudah jalan.`);
                const text = await res.text();
                try { return JSON.parse(text); }
                catch (e) {
                    console.error('Respons bukan JSON:', text);
                    throw new Error('Respons server bukan JSON. Cek file api-keuangan.php.');
                }
            }

            window.loadKeuanganData = async function() {
                const dari   = document.getElementById('keuTanggalDari').value;
                const sampai = document.getElementById('keuTanggalSampai').value;

                console.log('📊 Loading keuangan:', dari, '→', sampai);

                try {
                    const data = await fetchKeuangan(`api-keuangan.php?action=get_stats&dari=${dari}&sampai=${sampai}`);
                    if (data.status === 'success') {
                        document.getElementById('keuOmzet').textContent      = data.total_omzet_rp;
                        document.getElementById('keuHpp').textContent        = data.total_hpp_rp;
                        document.getElementById('keuModalOp').textContent    = data.total_modal_op_rp;
                        document.getElementById('keuLabaKotor').textContent  = data.laba_kotor_rp;
                        document.getElementById('keuLabaBersih').textContent = data.laba_bersih_rp;
                        const lblLaba = document.getElementById('keuLabaBersih');
                        lblLaba.style.color = data.is_laba ? 'var(--accent-gold)' : '#e63946';
                    } else {
                        Toast.fire({ icon: 'error', title: data.message || 'Gagal memuat statistik' });
                    }
                } catch (err) {
                    console.error('❌ Error stats:', err);
                    Toast.fire({ icon: 'error', title: err.message });
                }

                try {
                    const data = await fetchKeuangan(`api-keuangan.php?action=get_per_menu&dari=${dari}&sampai=${sampai}`);
                    const tbody = document.getElementById('tbodyKeuanganMenu');
                    if (data.status === 'success' && data.data.length > 0) {
                        let html = '';
                        data.data.forEach(m => {
                            const isLaba = m.total_laba >= 0;
                            html += `
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold text-white">${m.nama_menu}</div>
                                        <small class="text-white-50" style="font-size: 0.72rem;">Margin: ${m.margin_unit_rp}/pcs</small>
                                    </td>
                                    <td class="text-center fw-bold text-info">${m.total_terjual}x</td>
                                    <td class="text-end text-white">${m.harga_jual_rp}</td>
                                    <td class="text-end text-white-50">${m.harga_modal_rp}</td>
                                    <td class="text-end pe-3 fw-bold" style="color: ${isLaba ? 'var(--accent-gold)' : '#e63946'};">
                                        ${m.total_laba_rp}
                                    </td>
                                </tr>`;
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-white-50">Belum ada data pada periode ini.</td></tr>';
                    }
                } catch (err) {
                    console.error('❌ Error per menu:', err);
                    document.getElementById('tbodyKeuanganMenu').innerHTML =
                        `<tr><td colspan="5" class="text-center py-4 text-danger">Gagal memuat: ${err.message}</td></tr>`;
                }

                loadListModalOp(dari, sampai);
            };

            window.loadListModalOp = async function(dari, sampai) {
                try {
                    const data = await fetchKeuangan(`api-keuangan.php?action=get_modal_op&dari=${dari}&sampai=${sampai}`);
                    const container = document.getElementById('listModalOp');
                    if (data.status === 'success' && data.data.length > 0) {
                        let html = '';
                        data.data.forEach(m => {
                            html += `
                                <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                    <div class="overflow-hidden me-2">
                                        <div class="fw-semibold text-white small text-truncate">${m.keterangan}</div>
                                        <small class="text-white-50" style="font-size: 0.7rem;"><i class="far fa-calendar me-1"></i>${m.tanggal}</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        <span class="fw-bold text-danger small">${m.jumlah_rp}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="hapusModalOp(${m.id_modal})">
                                            <i class="fas fa-trash" style="font-size: 0.75rem;"></i>
                                        </button>
                                    </div>
                                </div>`;
                        });
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<div class="text-center py-3 text-white-50 small">Belum ada modal operasional.</div>';
                    }
                } catch (err) {
                    console.error('❌ Error modal op:', err);
                    document.getElementById('listModalOp').innerHTML =
                        `<div class="text-center py-3 text-danger small">Gagal memuat: ${err.message}</div>`;
                }
            };

            window.hapusModalOp = function(id) {
                Swal.fire({
                    title: 'Hapus Modal Operasional?',
                    text: 'Data yang dihapus tidak dapat dikembalikan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#e63946',
                    cancelButtonColor: '#444',
                    background: '#141414',
                    color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const fd = new FormData();
                        fd.append('action', 'hapus_modal_op');
                        fd.append('id_modal', id);
                        fetchKeuangan('api-keuangan.php', { method: 'POST', body: fd })
                            .then(data => {
                                Toast.fire({ icon: data.status === 'success' ? 'success' : 'error', title: data.message });
                                if (data.status === 'success') window.loadKeuanganData();
                            })
                            .catch(err => Toast.fire({ icon: 'error', title: err.message }));
                    }
                });
            };

            const formModalOp = document.getElementById('formModalOp');
            if (formModalOp) {
                formModalOp.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btnSubmit = this.querySelector('button[type="submit"]');
                    const originalBtn = btnSubmit ? btnSubmit.innerHTML : '';

                    if (btnSubmit) {
                        btnSubmit.disabled = true;
                        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
                    }

                    const fd = new FormData(this);
                    fd.append('action', 'tambah_modal_op');

                    fetchKeuangan('api-keuangan.php', { method: 'POST', body: fd })
                        .then(data => {
                            Toast.fire({ icon: data.status === 'success' ? 'success' : 'error', title: data.message });
                            if (data.status === 'success') {
                                formModalOp.reset();
                                formModalOp.querySelector('[name="tanggal"]').value = new Date().toISOString().split('T')[0];
                                window.loadKeuanganData();
                            }
                        })
                        .catch(err => Toast.fire({ icon: 'error', title: err.message }))
                        .finally(() => {
                            if (btnSubmit) {
                                btnSubmit.disabled = false;
                                btnSubmit.innerHTML = originalBtn;
                            }
                        });
                });
            }

            document.querySelectorAll('#mainSidebarTabs button').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.id === 'tab-btn-keuangan') setTimeout(window.loadKeuanganData, 300);
                });
            });

            if (window.location.hash === '#view-keuangan') setTimeout(window.loadKeuanganData, 500);
            if (window.location.hash === '#view-bahan') setTimeout(window.loadBahanBaku, 300);

            checkNewOrders();
            setInterval(checkNewOrders, 3000);
        });
    </script>
</body>

</html>