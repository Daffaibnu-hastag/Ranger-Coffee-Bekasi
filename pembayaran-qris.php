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
$pesan_error = '';

// Handle Upload Bukti Transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_transfer'])) {
    if ($_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['bukti_transfer']['tmp_name'];
        $file_name = $_FILES['bukti_transfer']['name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $nama_bukti = 'BUKTI_' . $p['kode_pesanan'] . '_' . time() . '.' . $ext;
            $target_dir = __DIR__ . '/uploads/bukti/' . $nama_bukti;

            if (!is_dir(__DIR__ . '/uploads/bukti')) {
                mkdir(__DIR__ . '/uploads/bukti', 0777, true);
            }

            if (move_uploaded_file($file_tmp, $target_dir)) {
                $id_p = $p['id_pesanan'];
                mysqli_query($conn, "UPDATE pesanan SET bukti_transfer = '$nama_bukti' WHERE id_pesanan = $id_p");
                
                header("Location: sukses-pesanan.php?kode=" . $p['kode_pesanan']);
                exit;
            }
        } else {
            $pesan_error = 'Format file harus JPG, PNG, atau WEBP!';
        }
    } else {
        $pesan_error = 'Harap pilih foto bukti transfer!';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-4 my-md-5 px-3" style="max-width: 550px;">
    <div class="card bg-dark text-white border-secondary rounded-4 p-4 shadow-lg text-center">
        
        <h4 class="fw-bold mb-1" style="color: var(--accent-gold);">Pembayaran QRIS</h4>
        <p class="text-white-50 small mb-3">Scan QRIS menggunakan Mobile Banking / GoPay / OVO / Dana / ShopeePay</p>

        <!-- Timer Countdown 15 Menit -->
        <div class="alert alert-warning bg-warning bg-opacity-10 border-warning text-warning py-2 small mb-3">
            <i class="fas fa-clock me-1"></i> Selesaikan Pembayaran Dalam: <strong id="countdownTimer">15:00</strong>
        </div>

        <!-- Gambar QRIS Toko -->
        <div class="bg-white p-3 rounded-4 d-inline-block mx-auto mb-3 shadow">
            <img src="uploads/qris.png" alt="QRIS Ranger Coffee" style="max-width: 220px; width: 100%; border-radius: 8px;" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=RangerCoffeeQRIS'">
        </div>

        <div class="p-3 rounded-3 mb-4 text-start" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);">
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-white-50">Kode Pesanan:</span>
                <strong class="text-warning"><?= $p['kode_pesanan']; ?></strong>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-white-50">Nama Pemesan:</span>
                <span><?= htmlspecialchars($p['nama_pelanggan']); ?></span>
            </div>
            <div class="d-flex justify-content-between fw-bold fs-6 mt-2 pt-2 border-top border-secondary border-opacity-25" style="color: var(--accent-gold);">
                <span>Total Harus Dibayar:</span>
                <span><?= rupiah($p['total_harga']); ?></span>
            </div>
        </div>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger py-2 small"><?= $pesan_error; ?></div>
        <?php endif; ?>

        <!-- Form Upload Bukti Transfer -->
        <form action="" method="POST" enctype="multipart/form-data" class="text-start">
            <div class="mb-3">
                <label class="form-label small text-white-50 fw-semibold">Upload Bukti Transfer / Screenshot Pembayaran:</label>
                <input type="file" name="bukti_transfer" class="form-control bg-secondary bg-opacity-25 text-white border-secondary" accept="image/*" required>
            </div>

            <button type="submit" class="btn btn-gold w-100 py-2.5 fw-bold rounded-3" style="background: linear-gradient(135deg, var(--accent-gold), #a87d52); color: #000; border: none;">
                <i class="fas fa-upload me-2"></i>Kirim Bukti Pembayaran
            </button>
        </form>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// 1. Simpan Kode Pesanan Aktif ke LocalStorage untuk Widget Status di Home
const activeOrderCode = "<?= $p['kode_pesanan']; ?>";
if (activeOrderCode) {
    localStorage.setItem('ranger_active_order', activeOrderCode);
}

// 2. Countdown Timer 15 Menit
let timeInSeconds = 15 * 60;
const timerElement = document.getElementById('countdownTimer');

const countdownInterval = setInterval(() => {
    const minutes = Math.floor(timeInSeconds / 60);
    const seconds = timeInSeconds % 60;
    
    if (timerElement) {
        timerElement.textContent = `${minutes < 10 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    }
    
    if (timeInSeconds <= 0) {
        clearInterval(countdownInterval);
        alert('Waktu pembayaran telah habis. Silakan lakukan pemesanan ulang.');
        window.location.href = 'index.php';
    } else {
        timeInSeconds--;
    }
}, 1000);
</script>