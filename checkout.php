<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika keranjang kosong
if (empty($_SESSION['keranjang'])) {
    header("Location: index.php");
    exit;
}

$total_bayar = 0;
foreach ($_SESSION['keranjang'] as $item) {
    $id_m = (int)$item['id_menu'];
    $m_data = query("SELECT harga FROM menu WHERE id_menu = $id_m");
    if (!empty($m_data)) {
        $total_bayar += ($m_data[0]['harga'] * $item['jumlah']);
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Pesanan - Ranger Coffee</title>
    
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

        .checkout-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.25rem;
        }

        @media (min-width: 768px) {
            .checkout-card {
                padding: 1.75rem;
            }
        }

        .form-control-custom {
            background-color: #1a1a1a;
            border: 1px solid var(--border-color);
            color: #ffffff;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .form-control-custom:focus {
            background-color: #222;
            border-color: var(--accent-gold);
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(196, 154, 108, 0.15);
        }

        /* Card List Item Keranjang */
        .cart-item-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 14px;
            padding: 10px 12px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }

        .cart-item-card:last-child {
            margin-bottom: 0;
        }

        .cart-item-img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid rgba(196, 154, 108, 0.2);
        }

        @media (min-width: 768px) {
            .cart-item-img {
                width: 60px;
                height: 60px;
            }
        }

        .sugar-badge {
            background: rgba(196, 154, 108, 0.15);
            color: var(--accent-gold);
            border: 1px solid rgba(196, 154, 108, 0.3);
            font-size: 0.7rem;
            padding: 2px 7px;
            border-radius: 6px;
            font-weight: 600;
        }

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

        /* Badge Notif Pemesan Lama */
        .badge-returning-user {
            background: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
            font-size: 0.75rem;
            border-radius: 8px;
            padding: 6px 12px;
        }
    </style>
</head>
<body>

<div class="container my-3 my-md-5 px-3">
    
    <!-- Title Section -->
    <div class="text-center mb-4">
        <h3 class="fw-bold text-white mb-1">Konfirmasi Pesanan</h3>
        <p class="text-white-50 small">Periksa kembali pesanan & isi data diri kamu</p>
    </div>

    <div class="row g-4 justify-content-center">
        
        <!-- Kolom Kanan/Atas di HP: Ringkasan Keranjang Belanja -->
        <div class="col-12 col-lg-5 order-1 order-lg-2">
            <div class="checkout-card shadow-lg">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0" style="color: var(--accent-gold);">
                        <i class="fas fa-shopping-basket me-2"></i>Item Dipesan
                    </h5>
                    <a href="keranjang.php" class="text-white-50 small text-decoration-none">
                        <i class="fas fa-edit me-1"></i>Ubah
                    </a>
                </div>

                <!-- List Item Pesanan -->
                <div class="cart-items-wrapper mb-3" style="max-height: 340px; overflow-y: auto; padding-right: 4px;">
                    <?php foreach ($_SESSION['keranjang'] as $item): 
                        $id_m = (int)$item['id_menu'];
                        $m_data = query("SELECT * FROM menu WHERE id_menu = $id_m");
                        if (empty($m_data)) continue;
                        $m = $m_data[0];
                        $subtotal = $m['harga'] * $item['jumlah'];
                        $gambar = !empty($m['gambar']) ? $m['gambar'] : 'kopi.png';
                    ?>
                        <div class="cart-item-card d-flex align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-3 overflow-hidden">
                                <img src="uploads/menu/<?= $gambar; ?>" class="cart-item-img flex-shrink-0" alt="<?= htmlspecialchars($m['nama_menu']); ?>" onerror="this.src='uploads/menu/kopi.png'">
                                <div class="overflow-hidden">
                                    <h6 class="fw-bold text-white mb-1 text-truncate small"><?= htmlspecialchars($m['nama_menu']); ?></h6>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="sugar-badge"><?= htmlspecialchars($item['level_gula']); ?></span>
                                        <small class="text-white-50" style="font-size: 0.75rem;"><?= $item['jumlah']; ?>x @ <?= rupiah($m['harga']); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="fw-bold small" style="color: var(--accent-gold);"><?= rupiah($subtotal); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total Pembayaran Card -->
                <div class="p-3 rounded-3" style="background: rgba(196, 154, 108, 0.08); border: 1px solid rgba(196, 154, 108, 0.2);">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-white small">Total Pembayaran:</span>
                        <span class="fw-bold fs-5" style="color: var(--accent-gold);"><?= rupiah($total_bayar); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kiri: Form Informasi Pemesan -->
        <div class="col-12 col-lg-7 order-2 order-lg-1">
            <div class="checkout-card shadow-lg">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0" style="color: var(--accent-gold);">
                        <i class="fas fa-user-edit me-2"></i>Informasi Pemesan
                    </h5>
                    <!-- Indicator jika data terisi otomatis -->
                    <div id="returningUserBadge" class="badge-returning-user d-none">
                        <i class="fas fa-bolt me-1"></i> Data Terisi Otomatis
                    </div>
                </div>

                <form id="formCheckout" action="proses-pesanan.php" method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label small text-white-50 fw-semibold">Nama Lengkap</label>
                        <input type="text" name="nama_pelanggan" id="inputNama" class="form-control form-control-custom" placeholder="Contoh: Budi Santoso" required autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-white-50 fw-semibold">No. WhatsApp / HP</label>
                        <input type="tel" name="no_hp" id="inputNoHp" class="form-control form-control-custom" placeholder="Contoh: 08123456789" required autocomplete="off">
                        <div class="form-text text-white-50" style="font-size: 0.75rem;">Status pemesanan akan diinfokan pada halaman website.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small text-white-50 fw-semibold">Catatan Tambahan (Opsional)</label>
                        <textarea name="catatan" class="form-control form-control-custom" rows="3" placeholder="Contoh: Diantar ke Meja No. 4 / Less Ice"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-gold-checkout w-100">
                            <i class="fas fa-paper-plane me-2"></i>Konfirmasi & Buat Pesanan
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Script JavaScript Tunggal & Stabil -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const inputNama = document.getElementById('inputNama');
    const inputNoHp = document.getElementById('inputNoHp');
    const formCheckout = document.getElementById('formCheckout');
    const returningBadge = document.getElementById('returningUserBadge');

    // 1. Auto-fill dari LocalStorage
    const savedNama = localStorage.getItem('ranger_customer_nama');
    const savedNoHp = localStorage.getItem('ranger_customer_nohp');

    if (savedNama && savedNoHp) {
        if(inputNama) inputNama.value = savedNama;
        if(inputNoHp) inputNoHp.value = savedNoHp;
        if (returningBadge) returningBadge.classList.remove('d-none');
    }

    // 2. Submit Form via AJAX
    if (formCheckout) {
        formCheckout.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            if(submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Membuat Pesanan...';
            }

            const nama = inputNama.value.trim();
            const nohp = inputNoHp.value.trim();
            if (nama !== '' && nohp !== '') {
                localStorage.setItem('ranger_customer_nama', nama);
                localStorage.setItem('ranger_customer_nohp', nohp);
            }

            const formData = new FormData(this);

            fetch('proses-pesanan.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (err) {
                    throw new Error("Server Response bukan JSON: " + text);
                }
            })
            .then(data => {
                if (data.status === 'success') {
                    // Redirect ke Halaman Scan QRIS & Upload Bukti Transfer
                    window.location.href = "pembayaran-qris.php?kode=" + data.kode_pesanan;
                } else {
                    alert(data.message || 'Terjadi kesalahan saat menyimpan pesanan.');
                    if(submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Konfirmasi & Buat Pesanan';
                    }
                }
            })
            // KODE BARU (UNTUK NAMPILIN PESAN ERROR PHP ASLINYA)
            .catch(error => {
                console.error('Error Checkout:', error);
                alert(error.message); // Menampilkan pesan error PHP langsung ke layar!
                if(submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Konfirmasi & Buat Pesanan';
                }
            });
        });
    }
});
</script>
</body>
</html>