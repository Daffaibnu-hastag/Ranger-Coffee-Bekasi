<?php
// Otomatis jalankan pembersihan pesanan kedaluwarsa di background tanpa menghentikan halaman
@include_once __DIR__ . '/cron-cleanup.php';

// Shortcut URL Rahasia: Akses index.php?admin untuk langsung ke Login Admin
if (isset($_GET['admin'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$kategori = query("SELECT * FROM kategori");
$kat_selected = isset($_GET['kat']) ? (int)$_GET['kat'] : null;

if ($kat_selected) {
    $menu = query("SELECT * FROM menu WHERE id_kategori = '$kat_selected'");
} else {
    $menu = query("SELECT * FROM menu");
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    :root {
        --bg-black: #0a0a0a;
        --bg-card: #141414;
        --bg-card-hover: #1c1c1c;
        --accent-gold: #c49a6c;
        --accent-gold-light: #e0b88f;
        --accent-orange: #d96b27;
        --border-color: rgba(255, 255, 255, 0.08);
    }

    body {
        background-color: var(--bg-black);
        color: #ffffff;
        font-family: 'Poppins', sans-serif;
        min-height: 100vh;
        overflow-x: hidden;
    }

    a[href*="login"],
    .btn-login,
    #btnLogin,
    nav a[href*="login"] {
        display: none !important;
    }

    .welcome-overlay {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100vh;
        background: radial-gradient(circle at 50% 45%, #2a1f17 0%, #120e0a 50%, var(--bg-black) 90%);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 99999;
        opacity: 1;
        visibility: visible;
        overflow: hidden;
        transition: transform 0.9s cubic-bezier(0.77, 0, 0.175, 1), opacity 0.7s ease, visibility 0.9s ease;
    }

    .welcome-overlay.fade-out {
        opacity: 0;
        visibility: hidden;
        transform: translateY(-100%);
    }

    .splash-glow-bg {
        position: absolute;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(196, 154, 108, 0.22) 0%, rgba(0, 0, 0, 0) 70%);
        border-radius: 50%;
        filter: blur(40px);
        animation: pulseGlow 3s ease-in-out infinite alternate;
    }

    .splash-particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: var(--accent-gold);
        border-radius: 50%;
        box-shadow: 0 0 10px var(--accent-gold);
        opacity: 0;
        animation: particleFloat 4s ease-in infinite;
    }
    .splash-p1 { top: 70%; left: 20%; animation-delay: 0s; }
    .splash-p2 { top: 60%; left: 80%; animation-delay: 1.2s; }
    .splash-p3 { top: 80%; left: 50%; animation-delay: 2.1s; }

    .welcome-content {
        text-align: center;
        color: #ffffff;
        position: relative;
        z-index: 2;
        padding: 20px;
        max-width: 90%;
        transition: transform 0.8s ease, opacity 0.8s ease;
    }

    .welcome-overlay.fade-out .welcome-content {
        transform: scale(0.9) translateY(-30px);
        opacity: 0;
    }

    .coffee-art-container {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto 25px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .coffee-ring-aura {
        position: absolute;
        inset: -10px;
        border-radius: 50%;
        border: 1.5px solid transparent;
        border-top-color: var(--accent-gold);
        border-right-color: rgba(196, 154, 108, 0.3);
        animation: spinRing 3.5s linear infinite;
    }

    .coffee-ring-aura-inner {
        position: absolute;
        inset: -2px;
        border-radius: 50%;
        border: 1px dashed rgba(196, 154, 108, 0.4);
        animation: spinRingReverse 6s linear infinite;
    }

    .coffee-steam-wrapper {
        position: absolute;
        top: -12px;
        display: flex;
        gap: 6px;
        justify-content: center;
    }

    .steam-line {
        width: 3px;
        height: 18px;
        background: linear-gradient(to top, rgba(196, 154, 108, 0.8), transparent);
        border-radius: 3px;
        animation: steamRise 2s ease-in-out infinite;
    }
    .steam-line:nth-child(1) { animation-delay: 0s; }
    .steam-line:nth-child(2) { animation-delay: 0.4s; height: 22px; }
    .steam-line:nth-child(3) { animation-delay: 0.8s; }

    .welcome-icon {
        font-size: 3.5rem;
        background: linear-gradient(135deg, #ffffff 20%, var(--accent-gold) 80%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(0 0 20px rgba(196, 154, 108, 0.7));
    }

    .welcome-title {
        font-family: 'Poppins', sans-serif;
        font-size: 2.6rem;
        font-weight: 800;
        margin: 0;
        line-height: 1.1;
        background: linear-gradient(110deg, #ffffff 35%, var(--accent-gold-light) 50%, #ffffff 65%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: 1px;
        animation: shineText 2.8s linear infinite, fadeInUp 0.8s ease forwards;
    }

    @media (min-width: 768px) {
        .welcome-title { font-size: 3.4rem; }
    }

    .welcome-subtitle {
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.7);
        margin-top: 10px;
        letter-spacing: 4px;
        text-transform: uppercase;
        font-weight: 400;
        animation: fadeInUp 0.8s ease 0.2s forwards;
        opacity: 0;
    }

    .welcome-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 25px;
    }

    .welcome-divider-line {
        width: 50px;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--accent-gold));
    }
    .welcome-divider-line.right {
        background: linear-gradient(270deg, transparent, var(--accent-gold));
    }

    .welcome-divider-dot {
        width: 6px;
        height: 6px;
        background: var(--accent-gold);
        border-radius: 50%;
        box-shadow: 0 0 8px var(--accent-gold);
    }

    @keyframes pulseGlow {
        0% { transform: scale(0.9); opacity: 0.15; }
        100% { transform: scale(1.25); opacity: 0.35; }
    }

    @keyframes spinRing {
        100% { transform: rotate(360deg); }
    }

    @keyframes spinRingReverse {
        100% { transform: rotate(-360deg); }
    }

    @keyframes steamRise {
        0% { transform: translateY(0) scaleX(1); opacity: 0; }
        50% { opacity: 0.8; }
        100% { transform: translateY(-16px) scaleX(1.5); opacity: 0; }
    }

    @keyframes particleFloat {
        0% { transform: translateY(0) scale(0.5); opacity: 0; }
        50% { opacity: 0.8; }
        100% { transform: translateY(-120px) scale(1.2); opacity: 0; }
    }

    @keyframes shineText {
        to { background-position: 200% center; }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-enter {
        opacity: 0;
        transform: translateY(28px);
        animation: fadeInUpSmooth 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .delay-1 { animation-delay: 0.15s; }
    .delay-2 { animation-delay: 0.3s; }
    .delay-3 { animation-delay: 0.45s; }

    @keyframes fadeInUpSmooth {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero-section-box {
        position: relative;
        background: radial-gradient(circle at 80% 20%, rgba(196, 154, 108, 0.14) 0%, transparent 65%), #121212;
        border: 1px solid var(--border-color);
        border-radius: 28px;
        padding: 35px 24px;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }

    .hero-title {
        font-weight: 800;
        line-height: 1.25;
        font-size: 1.85rem;
        letter-spacing: -0.5px;
        background: linear-gradient(135deg, #ffffff 60%, var(--accent-gold));
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-subtitle {
        font-size: 0.92rem;
        color: #b0b0b0;
        line-height: 1.6;
    }

    @media (min-width: 768px) {
        .hero-section-box { padding: 50px 40px; }
        .hero-title { font-size: 2.8rem; }
        .hero-subtitle { font-size: 1.05rem; }
    }

    .hero-img-glow {
        filter: drop-shadow(0 15px 35px rgba(196, 154, 108, 0.25));
        transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .hero-img-glow:hover {
        transform: scale(1.05) rotate(2deg);
    }

.category-container {
    position: sticky;
    top: 0;
    z-index: 1020;
    padding: 0 !important;
    margin-top: 0px !important;
    background: rgba(10, 10, 10, 0.85) !important;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    /* Hapus border-bottom & box-shadow supaya clean */
}

.category-wrapper {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    white-space: nowrap;
    padding: 12px 16px; /* beri ruang di sekitar tombol */
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    margin: 0 !important;
    /* Hapus background, border, border-radius, dan box-shadow */
}

.category-wrapper .btn {
    color: #a0a0a0;
    background: transparent;
    border: 1px solid transparent;
    font-weight: 500;
    border-radius: 30px;
    padding: 8px 20px;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    font-size: 0.85rem;
}

.category-wrapper .btn.active,
.category-wrapper .btn:hover {
    background: linear-gradient(135deg, var(--accent-gold), #a87d52) !important;
    color: #000000 !important;
    border-color: var(--accent-gold) !important;
    box-shadow: 0 4px 15px rgba(196, 154, 108, 0.35);
    font-weight: 700;
    transform: translateY(-1px);
}
    .card-menu {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .card-menu:hover:not(.menu-disabled) {
        background: var(--bg-card-hover);
        transform: translateY(-8px) scale(1.015);
        border-color: rgba(196, 154, 108, 0.45);
        box-shadow: 0 16px 32px rgba(0, 0, 0, 0.7), 0 0 20px rgba(196, 154, 108, 0.15);
    }

    .card-menu.menu-disabled {
        opacity: 0.55;
        filter: grayscale(0.9);
    }

    .badge-habis-box {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        z-index: 3;
    }

    .badge-habis {
        background: rgba(220, 53, 69, 0.95);
        color: #ffffff;
        font-size: 0.75rem;
        font-weight: 800;
        padding: 8px 16px;
        border-radius: 30px;
        letter-spacing: 0.8px;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin: auto;
    }

    .img-wrapper {
        position: relative;
        border-radius: 14px;
        overflow: hidden;
        background-color: #050505;
        aspect-ratio: 1 / 1;
        width: 100%;
    }

    .img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .card-menu:hover:not(.menu-disabled) .img-wrapper img {
        transform: scale(1.1);
    }

    .btn-favorite {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(8px);
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        cursor: pointer;
        transition: all 0.25s ease;
        z-index: 2;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .btn-favorite:hover {
        background: var(--accent-orange);
        color: #fff;
        transform: scale(1.15);
        border-color: var(--accent-orange);
    }

    .menu-title {
        font-size: 0.98rem;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: -0.2px;
        line-height: 1.3;
    }

    @media (min-width: 768px) {
        .menu-title { font-size: 1.1rem; }
    }

    .menu-desc {
        font-size: 0.78rem;
        color: #9e9e9e;
        line-height: 1.4;
        min-height: 36px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .price-tag {
        font-size: 1rem;
        font-weight: 800;
        color: var(--accent-gold);
    }

    @media (min-width: 768px) {
        .price-tag { font-size: 1.12rem; }
    }

    .btn-pesan-custom {
        background: linear-gradient(135deg, var(--accent-orange), #c25310);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 0.82rem;
        font-weight: 700;
        padding: 7px 16px;
        box-shadow: 0 4px 12px rgba(217, 107, 39, 0.3);
        transition: all 0.25s ease;
    }

    .btn-pesan-custom:hover:not(:disabled) {
        background: linear-gradient(135deg, #e87a36, #d96b27);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(217, 107, 39, 0.45);
    }

    .btn-pesan-disabled {
        background: #242424 !important;
        color: #666666 !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
    }

    .btn-outline-custom {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #a0a0a0;
        border-radius: 12px;
        transition: all 0.2s ease;
        min-height: 62px;           /* <-- TINGGI SERAGAM */
        display: flex;              /* <-- FLEXBOX */
        flex-direction: column;     /* <-- SUSUN VERTIKAL */
        align-items: center;        /* <-- CENTER HORIZONTAL */
        justify-content: center;    /* <-- CENTER VERTIKAL */
        padding: 8px 4px;           /* <-- PADDING KONSISTEN */
        line-height: 1.2;
        white-space: nowrap;
    }

    .btn-check:checked + .btn-outline-custom {
        background: rgba(196, 154, 108, 0.18) !important;
        border-color: var(--accent-gold) !important;
        color: var(--accent-gold-light) !important;
        box-shadow: 0 0 12px rgba(196, 154, 108, 0.25);
    }

    .btn-qty-counter {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .btn-qty-counter:hover {
        background: var(--accent-gold);
        color: #000;
        border-color: var(--accent-gold);
    }

    .btn-gold-modal {
        background: linear-gradient(135deg, var(--accent-gold), #a87d52);
        color: #000;
        border: none;
        transition: all 0.25s ease;
    }

    .floating-cart {
        position: fixed;
        bottom: 25px;
        right: 25px;
        background: linear-gradient(135deg, var(--accent-gold), #a87d52);
        color: #000000;
        font-weight: 800;
        padding: 12px 24px;
        border-radius: 50px;
        box-shadow: 0 10px 30px rgba(196, 154, 108, 0.45);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 999;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .floating-cart:hover {
        transform: translateY(-4px) scale(1.03);
        color: #000000;
        box-shadow: 0 15px 35px rgba(196, 154, 108, 0.6);
    }

    .cart-badge {
        background: #000000;
        color: var(--accent-gold);
        font-size: 0.8rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 800;
    }

    .btn-riwayat-trigger {
        background: rgba(196, 154, 108, 0.12);
        color: var(--accent-gold-light);
        border: 1px solid rgba(196, 154, 108, 0.35);
        transition: all 0.25s ease;
    }

    .btn-riwayat-trigger:hover {
        background: var(--accent-gold);
        color: #000000;
        box-shadow: 0 4px 15px rgba(196, 154, 108, 0.4);
        transform: translateY(-2px);
    }

    .history-drawer {
        position: fixed;
        top: 0;
        left: -380px;
        width: 360px;
        max-width: 88vw;
        height: 100vh;
        background-color: #121212;
        border-right: 1px solid rgba(196, 154, 108, 0.25);
        box-shadow: 15px 0 40px rgba(0, 0, 0, 0.85);
        z-index: 1060;
        display: flex;
        flex-direction: column;
        transition: left 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .history-drawer.show {
        left: 0;
    }

    .history-drawer-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        z-index: 1055;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .history-drawer-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .history-card {
        background: #181818;
        border: 1px solid rgba(196, 154, 108, 0.3);
        border-radius: 18px;
        padding: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        transition: all 0.25s ease;
    }

    .history-card:hover {
        border-color: var(--accent-gold);
        box-shadow: 0 10px 25px rgba(196, 154, 108, 0.2);
    }

    .btn-reorder-full {
        background: linear-gradient(135deg, var(--accent-orange), #c25310);
        color: #ffffff;
        font-weight: 700;
        border: none;
        border-radius: 12px;
        font-size: 0.88rem;
        padding: 10px 16px;
        box-shadow: 0 4px 15px rgba(217, 107, 39, 0.4);
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
    }

    .btn-reorder-full:hover {
        background: linear-gradient(135deg, #e87a36, #d96b27);
        color: #ffffff;
        box-shadow: 0 6px 20px rgba(217, 107, 39, 0.6);
        transform: translateY(-2px);
    }

    /* ====== CSS PREMIUM UNTUK POP UP NOTIFIKASI ====== */
    .swal2-popup-premium {
        background: linear-gradient(145deg, #181818, #0e0e0e) !important;
        border: 2px solid var(--accent-gold) !important;
        border-radius: 24px !important;
        box-shadow: 0 0 40px rgba(196, 154, 108, 0.3) !important;
        padding: 30px !important;
    }

    .swal2-confirm-premium {
        background: linear-gradient(135deg, var(--accent-gold), #a87d52) !important;
        color: #000 !important;
        font-weight: 700 !important;
        border-radius: 12px !important;
        padding: 12px 24px !important;
        box-shadow: 0 4px 15px rgba(196, 154, 108, 0.4) !important;
    }

    .swal2-cancel-premium {
        background: rgba(255, 255, 255, 0.08) !important;
        color: #ccc !important;
        border-radius: 12px !important;
        padding: 12px 20px !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
    }

    /* Tombol Aktifkan Suara */
    #btnEnableSound {
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 9999;
        background: rgba(196, 154, 108, 0.2);
        color: #c49a6c;
        border: 1px solid #c49a6c;
        border-radius: 50px;
        padding: 10px 20px;
        font-size: 0.85rem;
        cursor: pointer;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        backdrop-filter: blur(10px);
        transition: all 0.3s;
    }
    #btnEnableSound:hover {
        background: #c49a6c;
        color: #000;
    }
    #btnEnableSound.hidden {
        display: none;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- SPLASH SCREEN OVERLAY -->
<div id="welcomeOverlay" class="welcome-overlay">
    <div class="splash-glow-bg"></div>
    <div class="splash-particle splash-p1"></div>
    <div class="splash-particle splash-p2"></div>
    <div class="splash-particle splash-p3"></div>

    <div class="welcome-content">
        <div class="coffee-art-container">
            <div class="coffee-ring-aura"></div>
            <div class="coffee-ring-aura-inner"></div>
            <div class="coffee-steam-wrapper">
                <span class="steam-line"></span>
                <span class="steam-line"></span>
                <span class="steam-line"></span>
            </div>
            <i class="fas fa-mug-hot welcome-icon"></i>
        </div>
        <h1 class="welcome-title">Selamat Datang</h1>
        <p class="welcome-subtitle">Ranger Coffee Shop</p>
        <div class="welcome-divider">
            <div class="welcome-divider-line"></div>
            <div class="welcome-divider-dot"></div>
            <div class="welcome-divider-line right"></div>
        </div>
    </div>
</div>

<div class="container my-3 my-md-4 px-3">
    <div class="hero-section-box my-3 my-md-4 animate-enter">
        <div class="row align-items-center">
            <div class="col-md-7 text-center text-md-start mb-4 mb-md-0">
                <span class="badge px-3 py-2 rounded-pill mb-3" style="background: rgba(196, 154, 108, 0.15); color: var(--accent-gold); border: 1px solid rgba(196, 154, 108, 0.3); font-size: 0.78rem; font-weight: 600;">
                    <i class="fas fa-certificate me-1"></i> Biji Kopi Pilihan & Fresh Brew
                </span>
                <h1 class="hero-title">Selamat Datang!<br>Kami menyajikan kopi terbaik di Bekasi dan Sekitarnya!</h1>
                <p class="hero-subtitle my-3">Nikmati aroma khas biji kopi pilihan dengan seduhan terbaik untuk menemani harimu.</p>
                <div class="d-flex align-items-center gap-2 justify-content-center justify-content-md-start flex-wrap mt-2">
                    <a href="#menu" class="btn btn-gold px-4 py-2.5" style="background: linear-gradient(135deg, var(--accent-gold), #a87d52); color: #000; font-weight: 700; border-radius: 12px; box-shadow: 0 6px 20px rgba(196, 154, 108, 0.3);">
                        <i class="fas fa-coffee me-2"></i>Pesan Sekarang
                    </a>
                    <button type="button" class="btn btn-riwayat-trigger px-3 py-2.5 rounded-3 fw-bold d-flex align-items-center gap-2" id="btnOpenRiwayatDrawer">
                        <i class="fas fa-history text-warning"></i>
                        <span>Riwayat Pesanan</span>
                    </button>
                </div>
            </div>
            <div class="col-md-5 text-center">
                <img src="assets/img/logo.png" alt="Ranger Coffee Logo" class="img-fluid hero-img-glow" style="max-height: 350px;" onerror="this.src='uploads/menu/logo.png'">
            </div>
        </div>
    </div>

    <div id="customerStatusWidget" class="d-none mb-4 animate-enter delay-1">
        <div class="card border-0 rounded-4 p-3 shadow-lg" style="background: linear-gradient(135deg, #1e1e1e, #141414); border: 1px solid rgba(196, 154, 108, 0.4) !important;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div id="statusIconBox" class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(196,154,108,0.15);">
                        <i id="statusIcon" class="fas fa-spinner fa-spin text-warning fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-white-50 small">Pesanan Aktif Kamu:</span>
                            <strong id="widgetKode" class="text-warning small">RNG-xxx</strong>
                        </div>
                        <h6 id="widgetStatusText" class="fw-bold text-white mb-0">Mengecek status pesanan...</h6>
                        <div id="widgetAlasanBox" class="small text-danger mt-1 d-none">
                            <i class="fas fa-info-circle me-1"></i>
                            Alasan: <span id="widgetAlasanText" class="text-white fw-medium"></span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto ms-md-0 flex-wrap">
                    <button type="button" id="btnAjukanBatalPelanggan" class="btn btn-sm btn-outline-danger rounded-3 px-3 d-none fw-semibold" onclick="ajukanPembatalan()">
                        <i class="fas fa-ban me-1"></i> Batalkan
                    </button>
                    <a id="btnHubungiAdmin" href="#" target="_blank" class="btn btn-sm btn-success rounded-3 px-3 d-none fw-semibold">
                        <i class="fab fa-whatsapp me-1"></i> Hubungi Admin
                    </a>
                    <a id="btnDetailPesanan" href="#" class="btn btn-sm btn-outline-light rounded-3 px-3">
                        <i class="fas fa-receipt me-1"></i> Rincian Nota
                    </a>
                    <button type="button" class="btn btn-sm btn-link text-white-50 p-0 ms-2" id="btnCloseWidget" title="Sembunyikan Tracker">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if (!isTokoOpen()): ?>
    <div class="alert alert-warning text-center" style="background: rgba(255,193,7,0.1); border: 1px solid #ffc107; color: #ffc107; border-radius: 12px;">
        <i class="fas fa-clock me-2"></i> 
        <strong>Toko Sedang Tutup.</strong> 
        Jam operasional: Senin-Jumat 19.00-22.00 WIB, Sabtu-Minggu 08.00-22.00 WIB.
    </div>
    <?php endif; ?>

    <div class="category-container mb-4 animate-enter delay-2" id="menu">
        <div class="d-flex justify-content-center w-100">
            <div class="category-wrapper align-items-center">
                <a href="index.php#menu" class="btn btn-sm px-3 <?= !$kat_selected ? 'active' : '' ?>">
                    <i class="fas fa-list me-1"></i> Semua
                </a>
                <?php foreach ($kategori as $k): ?>
                    <a href="index.php?kat=<?= $k['id_kategori']; ?>#menu" class="btn btn-sm px-3 <?= $kat_selected == $k['id_kategori'] ? 'active' : '' ?>">
                        <i class="fas <?= !empty($k['icon']) ? $k['icon'] : 'fa-coffee'; ?> me-1"></i> <?= htmlspecialchars($k['nama_kategori']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-center gap-3 mb-4 animate-enter delay-3">
        <div style="height: 1px; width: 50px; background: linear-gradient(to right, transparent, var(--accent-gold));"></div>
        <h3 class="fw-bold m-0" style="color: var(--accent-gold); letter-spacing: 1.5px; font-size: 1.3rem;">KATALOG MENU KOPI</h3>
        <div style="height: 1px; width: 50px; background: linear-gradient(to left, transparent, var(--accent-gold));"></div>
    </div>

    <div class="row g-3 g-md-4 animate-enter delay-3" id="menuGridContainer">
        <?php if (empty($menu)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-white-50 fs-6">Belum ada menu yang tersedia untuk kategori ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($menu as $m):
                $gambar_menu = !empty($m['gambar']) ? $m['gambar'] : 'kopi.png';
                $is_habis    = (strtolower($m['status'] ?? '') === 'habis');
            ?>
                <div class="col-6 col-md-4 col-lg-3 menu-item-col" data-id="<?= $m['id_menu']; ?>">
                    <div class="card card-menu p-2 p-md-3 <?= $is_habis ? 'menu-disabled' : ''; ?>" id="cardMenu_<?= $m['id_menu']; ?>">
                        <div class="img-wrapper mb-2 mb-md-3">
                            <div class="badge-habis-box" id="badgeHabis_<?= $m['id_menu']; ?>" style="<?= $is_habis ? '' : 'display: none;'; ?>">
                                <span class="badge-habis"><i class="fas fa-ban me-1"></i> Stok Habis</span>
                            </div>
                            <div class="btn-favorite" id="btnFav_<?= $m['id_menu']; ?>" style="<?= $is_habis ? 'display: none;' : ''; ?>">
                                <i class="far fa-heart fs-6"></i>
                            </div>
                            <img src="uploads/menu/<?= $gambar_menu; ?>" class="img-fluid" alt="<?= htmlspecialchars($m['nama_menu']); ?>" onerror="this.src='uploads/menu/kopi.png'">
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <h5 class="menu-title mb-1 text-truncate"><?= htmlspecialchars($m['nama_menu']); ?></h5>
                            <p class="menu-desc mb-2 mb-md-3"><?= htmlspecialchars($m['deskripsi'] ?? ''); ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2 pt-md-3 border-top border-secondary border-opacity-25">
                                <span class="price-tag" id="priceTag_<?= $m['id_menu']; ?>" style="<?= $is_habis ? 'color: #666 !important;' : ''; ?>">
                                    <?= rupiah($m['harga']); ?>
                                </span>
                                <div id="btnActionBox_<?= $m['id_menu']; ?>">
                                    <?php if ($is_habis): ?>
                                        <button type="button" class="btn btn-pesan-custom btn-pesan-disabled" disabled>Habis</button>
                                    <?php elseif (!isTokoOpen()): ?>
                                        <button type="button" class="btn btn-pesan-custom btn-pesan-disabled" disabled>
                                            <i class="fas fa-lock me-1"></i> Tutup
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-pesan-custom" data-bs-toggle="modal" data-bs-target="#modalPesan<?= $m['id_menu']; ?>">Pesan</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="modalContainerGlobal">
    <?php if (!empty($menu)): ?>
        <?php foreach ($menu as $m):
            $gambar_menu = !empty($m['gambar']) ? $m['gambar'] : 'kopi.png';
        ?>
            <div class="modal fade" id="modalPesan<?= $m['id_menu']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm modal-md-md">
                    <div class="modal-content border-0 rounded-4 overflow-hidden" style="background-color: #141414; border: 1px solid rgba(255, 255, 255, 0.12) !important;">
                        <div class="modal-header border-bottom border-secondary border-opacity-25 pb-2 pt-3 px-3">
                            <h6 class="modal-title fw-bold text-white small"><i class="fas fa-sliders-h text-warning me-2"></i>Detail Pesanan</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="keranjang.php" method="POST" class="form-tambah-keranjang">
                            <div class="modal-body p-3">
                                <input type="hidden" name="id_menu" value="<?= $m['id_menu']; ?>">
                                <input type="hidden" name="nama_menu" value="<?= htmlspecialchars($m['nama_menu']); ?>">
                                <input type="hidden" name="harga" value="<?= $m['harga']; ?>">
                                <input type="hidden" name="action" value="tambah">
                                <div class="d-flex align-items-center gap-3 p-2 rounded-3 mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                    <img src="uploads/menu/<?= $gambar_menu; ?>" class="rounded-3" style="width: 60px; height: 60px; object-fit: cover; border: 1px solid rgba(196, 154, 108, 0.3);" onerror="this.src='uploads/menu/kopi.png'">
                                    <div>
                                        <h6 class="fw-bold text-white mb-1"><?= htmlspecialchars($m['nama_menu']); ?></h6>
                                        <span class="fw-bold fs-6" style="color: var(--accent-gold);"><?= rupiah($m['harga']); ?></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-white-50 small mb-2 d-block">Pilih Level Gula / Sweetness:</label>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" name="level_gula" id="sugar1_<?= $m['id_menu']; ?>" value="Normal Sugar" checked>
                                            <label class="btn btn-outline-custom w-100 py-2 text-center" for="sugar1_<?= $m['id_menu']; ?>">
                                                <span class="d-block fw-bold" style="font-size: 0.78rem;">Normal</span>
                                                <small class="text-white-50" style="font-size: 0.65rem;">100%</small>
                                            </label>
                                        </div>
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" name="level_gula" id="sugar2_<?= $m['id_menu']; ?>" value="Less Sugar">
                                            <label class="btn btn-outline-custom w-100 py-2 text-center" for="sugar2_<?= $m['id_menu']; ?>">
                                                <span class="d-block fw-bold" style="font-size: 0.78rem;">Less</span>
                                                <small class="text-white-50" style="font-size: 0.65rem;">50%</small>
                                            </label>
                                        </div>
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" name="level_gula" id="sugar3_<?= $m['id_menu']; ?>" value="No Sugar">
                                            <label class="btn btn-outline-custom w-100 py-2 text-center" for="sugar3_<?= $m['id_menu']; ?>">
                                                <span class="d-block fw-bold" style="font-size: 0.78rem;">No Sugar</span>
                                                <small class="text-white-50" style="font-size: 0.65rem;">0%</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold text-white-50 small mb-2 d-block">Jumlah Pesanan:</label>
                                    <div class="d-flex align-items-center justify-content-center gap-3 p-2 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                                        <button type="button" class="btn btn-qty-counter btn-minus" data-target="qty_<?= $m['id_menu']; ?>">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" name="jumlah" id="qty_<?= $m['id_menu']; ?>" class="form-control text-center bg-transparent border-0 text-white fw-bold fs-5 p-0" value="1" min="1" readonly style="width: 50px;">
                                        <button type="button" class="btn btn-qty-counter btn-plus" data-target="qty_<?= $m['id_menu']; ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-top border-secondary border-opacity-25 p-3">
                                <button type="submit" class="btn btn-gold-modal w-100 py-2 fw-bold rounded-3">
                                    <i class="fas fa-shopping-basket me-2"></i>Tambah Ke Keranjang
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="history-drawer-overlay" id="historyDrawerOverlay"></div>
<div class="history-drawer" id="historyDrawer">
    <div class="history-drawer-header d-flex align-items-center justify-content-between p-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-redo-alt text-warning fs-5"></i>
            <h6 class="fw-bold m-0 text-white">Riwayat Pesanan</h6>
        </div>
        <button type="button" class="btn-close btn-close-white" id="btnCloseRiwayatDrawer"></button>
    </div>
    <div class="history-drawer-body p-3 overflow-y-auto flex-grow-1" id="historyDrawerContent"></div>
</div>

<?php $total_cart_item = total_item_keranjang(); ?>
<a href="keranjang.php" class="floating-cart" id="floatingCartBtn" style="<?= $total_cart_item == 0 ? 'display: none;' : ''; ?>">
    <i class="fas fa-shopping-cart fs-5"></i>
    <span>Keranjang</span>
    <span class="cart-badge" id="cartBadgeCount"><?= $total_cart_item; ?></span>
</a>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;">
    <div id="cartToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastMessage">
                <i class="fas fa-check-circle me-2"></i> Menu berhasil ditambahkan!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
    if ('serviceWorker' in navigator && 'Notification' in window) {
        navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('Service Worker Terdaftar:', reg))
            .catch(err => console.error('Gagal Registrasi Service Worker:', err));
    }

    function mintaIzinNotifikasi() {
        if ('Notification' in window && Notification.permission !== 'granted') {
            Notification.requestPermission();
        }
    }

    function ajukanPembatalan() {
        const activeOrder = localStorage.getItem('ranger_active_order');
        if (!activeOrder) return;

        Swal.fire({
            title: 'Ajukan Pembatalan Pesanan?',
            text: 'Pengajuan akan dikirim ke Admin. Jika admin menerima, pesanan batal. Jika ditolak, pesanan tetap diproses.',
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Tuliskan alasan pembatalan di sini...',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Kirim Pengajuan',
            cancelButtonText: 'Kembali',
            background: '#141414',
            color: '#fff',
            inputValidator: (value) => {
                if (!value) return 'Alasan pembatalan wajib diisi!';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let formData = new FormData();
                formData.append('kode', activeOrder);
                formData.append('alasan', result.value);

                fetch('api-pengajuan-batal.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Diajukan',
                            text: data.message,
                            background: '#141414',
                            color: '#fff',
                            confirmButtonColor: '#c49a6c'
                        });
                        if (typeof window.checkMyOrderGlobal === 'function') {
                            window.checkMyOrderGlobal();
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: data.message,
                            background: '#141414',
                            color: '#fff'
                        });
                    }
                })
                .catch(err => {
                    console.error('Error Pengajuan Batal:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Sistem',
                        text: 'Gagal terhubung ke server.',
                        background: '#141414',
                        color: '#fff'
                    });
                });
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        mintaIzinNotifikasi();

        let audioCtx = null;
        let soundEnabled = false;

        function initAudio() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            soundEnabled = true;
            document.getElementById('btnEnableSound')?.classList.add('hidden');
        }

        document.addEventListener('click', initAudio);
        document.addEventListener('scroll', initAudio);
        document.addEventListener('touchstart', initAudio);
        document.addEventListener('keydown', initAudio);

        // Tombol manual jika audio belum aktif
        const btnSound = document.createElement('button');
        btnSound.id = 'btnEnableSound';
        btnSound.innerHTML = '<i class="fas fa-bell"></i> Aktifkan Suara';
        btnSound.style.position = 'fixed';
        btnSound.style.bottom = '20px';
        btnSound.style.left = '20px';
        btnSound.style.zIndex = '9999';
        btnSound.style.background = 'rgba(196, 154, 108, 0.2)';
        btnSound.style.color = '#c49a6c';
        btnSound.style.border = '1px solid #c49a6c';
        btnSound.style.borderRadius = '50px';
        btnSound.style.padding = '10px 20px';
        btnSound.style.fontSize = '0.85rem';
        btnSound.style.fontWeight = '600';
        btnSound.style.cursor = 'pointer';
        btnSound.style.boxShadow = '0 4px 15px rgba(0,0,0,0.3)';
        btnSound.style.backdropFilter = 'blur(10px)';
        btnSound.onclick = initAudio;
        document.body.appendChild(btnSound);

        // Sembunyikan tombol jika audio sudah aktif (setelah interaksi pertama)
        const checkSound = () => {
            if (soundEnabled && btnSound) {
                btnSound.classList.add('hidden');
                btnSound.style.display = 'none';
            }
        };
        setInterval(checkSound, 1000);

        function playCompletionSound() {
            try {
                if (!audioCtx) return; // audio belum aktif
                if (audioCtx.state === 'suspended') audioCtx.resume();
                const osc1 = audioCtx.createOscillator();
                const gain1 = audioCtx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(520, audioCtx.currentTime);
                gain1.gain.setValueAtTime(0.4, audioCtx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
                osc1.connect(gain1);
                gain1.connect(audioCtx.destination);
                osc1.start();
                osc1.stop(audioCtx.currentTime + 0.35);

                setTimeout(() => {
                    if (!audioCtx) return;
                    const osc2 = audioCtx.createOscillator();
                    const gain2 = audioCtx.createGain();
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(880, audioCtx.currentTime);
                    gain2.gain.setValueAtTime(0.5, audioCtx.currentTime);
                    gain2.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.65);
                    osc2.connect(gain2);
                    gain2.connect(audioCtx.destination);
                    osc2.start();
                    osc2.stop(audioCtx.currentTime + 0.65);
                }, 160);
            } catch(e) {
                console.error("Audio Error:", e);
            }
        }

        const katSelected = <?= json_encode($kat_selected); ?>;

        const overlay = document.getElementById("welcomeOverlay");
        if (!sessionStorage.getItem("hasSeenWelcome")) {
            setTimeout(function () {
                if (overlay) overlay.classList.add("fade-out");
            }, 2600);
            sessionStorage.setItem("hasSeenWelcome", "true");
        } else {
            if (overlay) overlay.style.display = "none";
        }

        document.addEventListener('keydown', function(e) {
            if (e.shiftKey && (e.key === 'L' || e.key === 'l')) {
                window.location.href = 'login.php';
            }
        });

        const btnOpenDrawer = document.getElementById('btnOpenRiwayatDrawer');
        const btnCloseDrawer = document.getElementById('btnCloseRiwayatDrawer');
        const drawer = document.getElementById('historyDrawer');
        const drawerOverlay = document.getElementById('historyDrawerOverlay');
        const drawerContent = document.getElementById('historyDrawerContent');

        function openDrawer() {
            renderRiwayat();
            if (drawer) drawer.classList.add('show');
            if (drawerOverlay) drawerOverlay.classList.add('show');
        }
        function closeDrawer() {
            if (drawer) drawer.classList.remove('show');
            if (drawerOverlay) drawerOverlay.classList.remove('show');
        }
        if (btnOpenDrawer) btnOpenDrawer.addEventListener('click', openDrawer);
        if (btnCloseDrawer) btnCloseDrawer.addEventListener('click', closeDrawer);
        if (drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);

        function renderRiwayat() {
            let history = JSON.parse(localStorage.getItem('ranger_order_history') || '[]');
            if (!drawerContent) return;
            if (history.length === 0) {
                drawerContent.innerHTML = `<div class="text-center py-5 text-white-50">
                    <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                    <h6>Belum Ada Riwayat</h6>
                    <small class="d-block text-white-50">Pesanan kamu akan tersimpan di sini untuk dipesan ulang secara cepat.</small>
                </div>`;
                return;
            }
            let html = '<div class="d-flex flex-column gap-3">';
            history.forEach((ord, idx) => {
                let itemsArr = ord.items || [];
                let totalNominal = parseFloat(ord.total || 0);
                let itemListHtml = '';
                if (Array.isArray(itemsArr) && itemsArr.length > 0) {
                    itemsArr.forEach(it => {
                        let nama = it.nama_menu || 'Menu Kopi';
                        let qty = parseInt(it.jumlah || 1);
                        let harga = parseFloat(it.harga || 0);
                        let subtotal = qty * harga;
                        itemListHtml += `<div class="d-flex justify-content-between text-white-50 align-items-center my-1" style="font-size: 0.82rem;"><span><strong class="text-warning">${qty}x</strong> ${nama}</span><span class="fw-semibold text-white-50">${subtotal > 0 ? 'Rp ' + subtotal.toLocaleString('id-ID') : '-'}</span></div>`;
                    });
                } else {
                    itemListHtml = `<div class="small text-warning">Pesanan Menu Outlet Ranger Coffee #${ord.kode}</div>`;
                }
                let badgeStatus = '<span class="badge bg-warning text-dark fw-bold">Pending</span>';
                let stLC = (ord.status || 'pending').toLowerCase().trim();
                if (stLC === 'diproses') badgeStatus = '<span class="badge bg-info text-dark fw-bold">Diproses</span>';
                else if (stLC === 'selesai') badgeStatus = '<span class="badge bg-success fw-bold">Selesai</span>';
                else if (stLC === 'dibatalkan') badgeStatus = '<span class="badge bg-danger fw-bold">Dibatalkan</span>';
                else if (stLC === 'batal_pending') badgeStatus = '<span class="badge bg-warning text-dark fw-bold">Pengajuan Batal</span>';
                html += `<div class="history-card">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                        <div><span class="fw-bold text-warning fs-6 me-1">#${ord.kode}</span>${badgeStatus}</div>
                        <small class="text-white-50" style="font-size: 0.72rem;">${ord.waktu || ''}</small>
                    </div>
                    <div class="my-2 p-2 rounded-3" style="background: rgba(255,255,255,0.03);">${itemListHtml}</div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25 mb-3">
                        <span class="small text-white-50 fw-medium">Total Pesanan</span>
                        <span class="fw-extrabold text-warning fs-6">Rp ${totalNominal.toLocaleString('id-ID')}</span>
                    </div>
                    <button type="button" class="btn btn-reorder-full" onclick="pesanLagi(${idx})"><i class="fas fa-redo-alt"></i> Pesan Lagi Menu Ini</button>
                </div>`;
            });
            html += '</div>';
            drawerContent.innerHTML = html;
        }

        window.pesanLagi = function(index) {
            let history = JSON.parse(localStorage.getItem('ranger_order_history') || '[]');
            let selectedOrder = history[index];
            let itemsArr = selectedOrder ? selectedOrder.items || [] : [];
            if (!selectedOrder || itemsArr.length === 0) {
                Swal.fire({ icon: 'info', title: 'Memproses Pesanan', text: 'Menambahkan item ke keranjang...', background: '#141414', color: '#fff' });
                return;
            }
            let promises = itemsArr.map(item => {
                let formData = new FormData();
                if (item.id_menu) formData.append('id_menu', item.id_menu);
                formData.append('jumlah', item.jumlah || 1);
                formData.append('level_gula', item.level_gula || 'Normal Sugar');
                formData.append('action', 'tambah');
                return fetch('keranjang.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(res => res.json());
            });
            Promise.all(promises).then(results => {
                closeDrawer();
                const lastResult = results[results.length - 1];
                if (lastResult && lastResult.status === 'success') {
                    const cartBadge = document.getElementById('cartBadgeCount');
                    const floatingCart = document.getElementById('floatingCartBtn');
                    if (cartBadge) cartBadge.textContent = lastResult.total_item;
                    if (floatingCart) floatingCart.style.display = 'flex';
                    Swal.fire({ icon: 'success', title: 'Berhasil Repeat Order!', text: 'Menu telah dimasukkan ke keranjang.', confirmButtonColor: '#c49a6c', background: '#141414', color: '#fff' });
                }
            }).catch(err => console.error('Error Reorder:', err));
        };

        const activeOrder = localStorage.getItem('ranger_active_order');
        const widget = document.getElementById('customerStatusWidget');
        const widgetKode = document.getElementById('widgetKode');
        const widgetStatusText = document.getElementById('widgetStatusText');
        const widgetAlasanBox = document.getElementById('widgetAlasanBox');
        const widgetAlasanText = document.getElementById('widgetAlasanText');
        const statusIcon = document.getElementById('statusIcon');
        const btnDetail = document.getElementById('btnDetailPesanan');
        const btnHubungiAdmin = document.getElementById('btnHubungiAdmin');
        const btnAjukanBatal = document.getElementById('btnAjukanBatalPelanggan');
        const btnClose = document.getElementById('btnCloseWidget');

        let lastStatusTracked = null;
        let orderTrackerInterval = null;
        let lastSelesaiNotifTime = 0;

        // Fungsi Popup Premium
        function tampilkanPopUpNotif(judul, deskripsiHtml, kodePesanan) {
            Swal.fire({
                title: `<span style="font-size: 2rem; font-weight: 800; color: #c49a6c;">${judul}</span>`,
                html: `
                    <div style="padding: 10px 0;">
                        <div style="background: linear-gradient(135deg, #c49a6c, #a87d52); border-radius: 50%; width: 100px; height: 100px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(196,154,108,0.5);">
                            <i class="fas fa-check-circle" style="font-size: 55px; color: #000;"></i>
                        </div>
                        <p style="color: #fff; font-size: 1.05rem; line-height: 1.6;">${deskripsiHtml}</p>
                        <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(196,154,108,0.3); border-radius: 12px; padding: 12px; margin-top: 10px; display: inline-block; min-width: 200px;">
                            <span style="color: #c49a6c; font-weight: bold;">Kode Pesanan:</span><br>
                            <span style="color: #fff; font-weight: 700; font-size: 1.2rem; letter-spacing: 1px;">${kodePesanan}</span>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<span style="font-weight: 700;">Lihat Nota</span>',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#c49a6c',
                cancelButtonColor: '#444',
                background: '#141414',
                color: '#fff',
                customClass: {
                    popup: 'swal2-popup-premium',
                    confirmButton: 'swal2-confirm-premium',
                    cancelButton: 'swal2-cancel-premium'
                },
                showClass: { popup: 'animate__animated animate__zoomIn' },
                hideClass: { popup: 'animate__animated animate__zoomOut' },
                buttonsStyling: false,
                didOpen: () => {
                    playCompletionSound();
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "pesanan_detail.php?kode=" + kodePesanan;
                }
            });
        }

        if (activeOrder && widget) {
            widget.classList.remove('d-none');
            if (widgetKode) widgetKode.textContent = activeOrder;
            if (btnDetail) btnDetail.href = "pesanan_detail.php?kode=" + activeOrder;

            function checkMyOrder() {
                fetch('api-cek-status-pelanggan.php?kode=' + activeOrder)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const st = (data.status_pesanan || data.status_order || data.order_status || '').toLowerCase().trim();
                            const alasanBatal = data.alasan_batal || 'Tidak ada alasan khusus dari outlet.';
                            if (st === 'selesai') {
                                widgetStatusText.textContent = "Pesanan Selesai. Terima Kasih! 🙏";
                                statusIcon.className = "fas fa-check-circle text-success fs-5";
                                if (widgetAlasanBox) widgetAlasanBox.classList.add('d-none');
                                if (btnHubungiAdmin) btnHubungiAdmin.classList.add('d-none');
                                if (btnAjukanBatal) btnAjukanBatal.classList.add('d-none');
                                if (lastStatusTracked !== 'selesai') {
                                    const now = Date.now();
                                    if (now - lastSelesaiNotifTime > 15000) {
                                        lastSelesaiNotifTime = now;
                                        tampilkanPopUpNotif(
                                            "Pesanan Selesai! 🎉",
                                            "Terima kasih sudah memesan di Ranger Coffee. Pesanan <strong class='text-warning'>" + activeOrder + "</strong> telah selesai dan siap dinikmati.",
                                            activeOrder
                                        );
                                    }
                                }
                                if (orderTrackerInterval) clearInterval(orderTrackerInterval);
                                localStorage.removeItem('ranger_active_order');
                            } else if (st === 'diproses') {
                                widgetStatusText.textContent = "Kopi Sedang Diseduh Barista! ☕";
                                statusIcon.className = "fas fa-fire text-info fs-5";
                                if (widgetAlasanBox) widgetAlasanBox.classList.add('d-none');
                                if (btnHubungiAdmin) btnHubungiAdmin.classList.add('d-none');
                                if (btnAjukanBatal) btnAjukanBatal.classList.remove('d-none');
                            } else if (st === 'pending') {
                                widgetStatusText.textContent = "Menunggu Konfirmasi Pembayaran Admin";
                                statusIcon.className = "fas fa-clock text-warning fs-5";
                                if (widgetAlasanBox) widgetAlasanBox.classList.add('d-none');
                                if (btnHubungiAdmin) btnHubungiAdmin.classList.add('d-none');
                                if (btnAjukanBatal) btnAjukanBatal.classList.remove('d-none');
                            } else if (st === 'batal_pending') {
                                widgetStatusText.textContent = "Pengajuan Pembatalan Menunggu Konfirmasi Admin ⏳";
                                statusIcon.className = "fas fa-hourglass-half text-warning fs-5";
                                if (widgetAlasanBox) widgetAlasanBox.classList.add('d-none');
                                if (btnHubungiAdmin) btnHubungiAdmin.classList.add('d-none');
                                if (btnAjukanBatal) btnAjukanBatal.classList.add('d-none');
                            } else if (st === 'dibatalkan') {
                                widgetStatusText.textContent = "Pesanan Dibatalkan oleh Outlet";
                                statusIcon.className = "fas fa-times-circle text-danger fs-5";
                                if (btnAjukanBatal) btnAjukanBatal.classList.add('d-none');
                                if (widgetAlasanBox && widgetAlasanText) {
                                    widgetAlasanText.textContent = alasanBatal;
                                    widgetAlasanBox.classList.remove('d-none');
                                }
                                if (btnHubungiAdmin) {
                                    const pesanWA = encodeURIComponent("Halo Admin Ranger Coffee, pesanan saya dengan Kode: " + activeOrder + " dibatalkan dengan alasan: " + alasanBatal);
                                    btnHubungiAdmin.href = "https://wa.me/62895366798531?text=" + pesanWA;
                                    btnHubungiAdmin.classList.remove('d-none');
                                }
                                if (orderTrackerInterval) clearInterval(orderTrackerInterval);
                                localStorage.removeItem('ranger_active_order');
                            }
                            lastStatusTracked = st;
                        } else if (data.status === 'not_found') {
                            localStorage.removeItem('ranger_active_order');
                            widget.classList.add('d-none');
                            if (orderTrackerInterval) clearInterval(orderTrackerInterval);
                        }
                    })
                    .catch(err => console.error('Error Status Tracker:', err));
            }
            window.checkMyOrderGlobal = checkMyOrder;
            checkMyOrder();
            orderTrackerInterval = setInterval(checkMyOrder, 3000);
            if (btnClose) {
                btnClose.addEventListener('click', function() {
                    widget.classList.add('d-none');
                });
            }
        }

        // Polling status toko
        function cekStatusTokoRealtime() {
            fetch('api-cek-status-toko.php')
                .then(res => res.json())
                .then(data => {
                    const banner = document.querySelector('.alert-warning.text-center');
                    const buttons = document.querySelectorAll('.btn-pesan-custom:not(.btn-pesan-disabled)');
                    if (data.toko_open) {
                        if (banner) banner.style.display = 'none';
                        document.querySelectorAll('.btn-pesan-custom[disabled]').forEach(btn => {
                            if (!btn.classList.contains('stok-habis')) {
                                btn.disabled = false;
                                btn.classList.remove('btn-pesan-disabled');
                                btn.innerHTML = 'Pesan';
                            }
                        });
                    } else {
                        if (banner) banner.style.display = 'block';
                        document.querySelectorAll('.btn-pesan-custom:not([disabled])').forEach(btn => {
                            if (!btn.classList.contains('stok-habis')) {
                                btn.disabled = true;
                                btn.classList.add('btn-pesan-disabled');
                                btn.innerHTML = '<i class="fas fa-lock me-1"></i> Tutup';
                            }
                        });
                    }
                })
                .catch(err => console.error('Error cek status toko:', err));
        }
        cekStatusTokoRealtime();
        setInterval(cekStatusTokoRealtime, 10000);

        document.addEventListener('click', function(e) {
            const plusBtn = e.target.closest('.btn-plus');
            const minusBtn = e.target.closest('.btn-minus');
            if (plusBtn) {
                const targetId = plusBtn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (input) input.value = parseInt(input.value) + 1;
            }
            if (minusBtn) {
                const targetId = minusBtn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (input && parseInt(input.value) > 1) {
                    input.value = parseInt(input.value) - 1;
                }
            }
        });

        const toastEl = document.getElementById('cartToast');
        const toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 2500 }) : null;
        const cartBadge = document.getElementById('cartBadgeCount');
        const floatingCart = document.getElementById('floatingCartBtn');

        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form && (form.classList.contains('form-tambah-keranjang') || form.getAttribute('action') === 'keranjang.php')) {
                const idMenu = form.querySelector('[name="id_menu"]')?.value;
                const namaMenu = form.querySelector('[name="nama_menu"]')?.value || 'Menu Kopi';
                const harga = parseFloat(form.querySelector('[name="harga"]')?.value || 0);
                const jumlah = parseInt(form.querySelector('[name="jumlah"]')?.value || 1);
                const levelGula = form.querySelector('[name="level_gula"]:checked')?.value || 'Normal Sugar';
                let history = JSON.parse(localStorage.getItem('ranger_order_history') || '[]');
                let activeOrder = localStorage.getItem('ranger_active_order') || ('RNG-' + Date.now().toString().slice(-6));
                let existingIdx = history.findIndex(h => h.kode === activeOrder);
                let newItem = { id_menu: idMenu, nama_menu: namaMenu, harga: harga, jumlah: jumlah, level_gula: levelGula };
                if (existingIdx !== -1) {
                    history[existingIdx].items.push(newItem);
                    history[existingIdx].total = (history[existingIdx].total || 0) + (harga * jumlah);
                } else {
                    history.unshift({ kode: activeOrder, waktu: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }), total: harga * jumlah, status: 'pending', items: [newItem] });
                }
                localStorage.setItem('ranger_order_history', JSON.stringify(history));
                localStorage.setItem('ranger_active_order', activeOrder);
                e.preventDefault();
                const formData = new FormData(form);
                fetch('keranjang.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(response => { if (!response.ok) throw new Error('Network response not ok'); return response.json(); })
                .then(data => {
                    if (data.status === 'success') {
                        if (cartBadge) cartBadge.textContent = data.total_item;
                        if (floatingCart) floatingCart.style.display = 'flex';
                        const modalElement = form.closest('.modal');
                        if (modalElement) {
                            const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                            if (modalInstance) modalInstance.hide();
                        }
                        const toastMsg = document.getElementById('toastMessage');
                        if (toastMsg) toastMsg.innerHTML = `<i class="fas fa-check-circle me-2"></i> ${data.message}`;
                        if (toast) toast.show();
                    } else {
                        form.submit();
                    }
                })
                .catch(error => { console.error('Error Submit Keranjang:', error); form.submit(); });
            }
        });
    });
</script>