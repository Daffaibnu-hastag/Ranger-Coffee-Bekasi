<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$kode = isset($_GET['kode']) ? mysqli_real_escape_string($conn, $_GET['kode']) : '';
$pesanan = query("SELECT * FROM pesanan WHERE kode_pesanan = '$kode'");

if (empty($pesanan)) {
    header("Location: index.php");
    exit;
}

$p = $pesanan[0];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Ranger Coffee</title>
    
    <style>
        :root {
            --bg-black: #080808;
            --bg-card: #141414;
            --accent-gold: #c49a6c;
            --border-color: rgba(255, 255, 255, 0.08);
        }

        body {
            background-color: var(--bg-black);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
        }

        .sukses-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 1.5rem;
            max-width: 520px;
            margin: 0 auto;
        }

        @media (min-width: 768px) {
            .sukses-card {
                padding: 2.5rem;
            }
        }

        .btn-gold-home {
            background: linear-gradient(135deg, var(--accent-gold), #a87d52);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            padding: 12px;
            transition: all 0.2s;
        }

        .btn-gold-home:hover {
            background: linear-gradient(135deg, #d6ab7d, var(--accent-gold));
            color: #000;
        }
    </style>
</head>
<body>

<div class="container my-4 my-md-5 px-3 text-center">
    <div class="sukses-card shadow-lg">
        
        <div class="mb-3">
            <i class="fas fa-check-circle fa-4x" style="color: var(--accent-gold);"></i>
        </div>
        
        <h4 class="fw-bold text-white mb-2">Pesanan Berhasil Dibuat!</h4>
        <p class="text-white-50 small mb-4">Terima kasih sudah memesan di Ranger Coffee. Pesanan kamu telah masuk ke antrean kami.</p>

        <!-- Rincian Nota Ringkas -->
        <div class="p-3 rounded-3 text-start mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
            <div class="d-flex justify-content-between mb-2 small text-white-50">
                <span>Kode Pesanan:</span>
                <strong style="color: var(--accent-gold);"><?= $p['kode_pesanan']; ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2 small text-white-50">
                <span>Nama Pemesan:</span>
                <strong class="text-white"><?= htmlspecialchars($p['nama_pelanggan']); ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2 small text-white-50">
                <span>No. WhatsApp:</span>
                <strong class="text-white"><?= $p['no_hp']; ?></strong>
            </div>
            <div class="d-flex justify-content-between border-top border-secondary border-opacity-25 pt-2 mt-2 small text-white-50">
                <span class="fw-bold text-white">Total Pembayaran:</span>
                <strong class="fw-bold fs-6" style="color: var(--accent-gold);"><?= rupiah($p['total_harga']); ?></strong>
            </div>
        </div>

        <a href="index.php" class="btn btn-gold-home w-100">
            <i class="fas fa-coffee me-2"></i>Kembali ke Halaman Utama
        </a>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>