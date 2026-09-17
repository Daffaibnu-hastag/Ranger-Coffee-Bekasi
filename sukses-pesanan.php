<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$kode_pesanan = mysqli_real_escape_string($conn, $_GET['kode'] ?? '');
$pesanan = query("SELECT * FROM pesanan WHERE kode_pesanan = '$kode_pesanan'");

if (empty($pesanan)) {
    header("Location: index.php");
    exit;
}

$p = $pesanan[0];
$id_p = $p['id_pesanan'];
$detail = query("SELECT d.*, m.nama_menu FROM detail_pesanan d JOIN menu m ON d.id_menu = m.id_menu WHERE d.id_pesanan = $id_p");

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-5 text-center px-3" style="max-width: 550px;">
    <div class="card bg-dark text-white border-secondary rounded-4 p-4 shadow-lg">
        <div class="mb-3">
            <i class="fas fa-check-circle fa-4x text-success"></i>
        </div>
        <h4 class="fw-bold mb-1">Pesanan Diterima!</h4>
        <p class="text-white-50 small mb-3">Terima kasih sudah memesan kopi di Ranger Coffee</p>

        <div class="p-3 rounded-3 mb-3 text-start" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);">
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-white-50">Kode Pesanan:</span>
                <strong class="text-warning"><?= $p['kode_pesanan']; ?></strong>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-white-50">Nama Pemesan:</span>
                <span><?= htmlspecialchars($p['nama_pelanggan']); ?></span>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-white-50">Status:</span>
                <span class="badge bg-warning text-dark"><?= strtoupper($p['status']); ?></span>
            </div>
        </div>

        <h6 class="fw-bold text-start text-warning mb-2 small">Rincian Item:</h6>
        <div class="text-start mb-4">
            <?php foreach ($detail as $d): ?>
                <div class="d-flex justify-content-between small py-1 border-bottom border-secondary border-opacity-25">
                    <span><?= $d['jumlah']; ?>x <?= htmlspecialchars($d['nama_menu']); ?> (<?= $d['level_gula']; ?>)</span>
                    <span><?= rupiah($d['harga'] * $d['jumlah']); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="d-flex justify-content-between fw-bold mt-2 pt-1 fs-6" style="color: var(--accent-gold);">
                <span>Total:</span>
                <span><?= rupiah($p['total_harga']); ?></span>
            </div>
        </div>

        <a href="index.php" class="btn btn-outline-light w-100 py-2 rounded-3">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Menu
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>