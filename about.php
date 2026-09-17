<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// =====================================================
// KONFIGURASI HALAMAN ABOUT (EDIT DI SINI SAJA)
// =====================================================

$about_title = "Tentang Ranger Coffee";
$about_tagline = "Secangkir kopi, sejuta cerita.";

$video_type = "local"; // pilihan: "youtube" atau "local"
$youtube_id = "";
$local_video = "uploads/video/abtkopi.mp4";

$paragraphs = [
    [
        'heading' => 'Salam Ranger',
        'text'    => 'Ranger Coffee lahir dari passion saya dalam mengolah biji kopi berkualitas menjadi racikan ala kafe yang autentik. Setiap menu kami buat dengan resep presisi dan penuh perhatian, seperti menyajikannya untuk keluarga sendiri. Harapan saya sederhana: semoga setiap seruput Ranger Coffee bisa menghadirkan senyum dan energi positif untuk hari Anda.'
    ],
];

// =====================================================
// END OF KONFIGURASI
// =====================================================

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    :root {
        --bg-black: #0a0a0a;
        --bg-card: #141414;
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

    .about-container {
        position: relative;
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px 80px;
    }

    /* ==== HEADER ==== */
    .about-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .about-header .badge-about {
        background: rgba(196, 154, 108, 0.15);
        color: var(--accent-gold);
        border: 1px solid rgba(196, 154, 108, 0.3);
        font-size: 0.78rem;
        font-weight: 600;
        padding: 8px 18px;
        border-radius: 30px;
        display: inline-block;
        margin-bottom: 18px;
    }

    .about-header h1 {
        font-size: 2.2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #ffffff 50%, var(--accent-gold));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: -0.5px;
        margin-bottom: 12px;
    }

    @media (min-width: 768px) {
        .about-header h1 { font-size: 3rem; }
    }

    .about-header .tagline {
        color: #a0a0a0;
        font-size: 1rem;
        font-style: italic;
    }

    .about-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 20px;
    }

    .about-divider-line {
        width: 60px;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--accent-gold));
    }

    .about-divider-line.right {
        background: linear-gradient(270deg, transparent, var(--accent-gold));
    }

    .about-divider-dot {
        width: 6px;
        height: 6px;
        background: var(--accent-gold);
        border-radius: 50%;
        box-shadow: 0 0 8px var(--accent-gold);
    }

    /* ==== GRID LAYOUT ==== */
    .about-grid {
        position: relative;
        display: grid;
        grid-template-columns: 1fr;
        gap: 40px;
        align-items: center;
    }

    .about-grid::before {
        content: '';
        position: absolute;
        inset: -60px -40px -60px -40px;
        background: radial-gradient(circle at 80% 50%, rgba(196, 154, 108, 0.08) 0%, transparent 55%);
        pointer-events: none;
        z-index: 0;
    }

    @media (min-width: 992px) {
        .about-grid {
            grid-template-columns: 1fr 1.1fr;
            gap: 0px;
        }
    }

    /* ==== KOLOM KIRI: TEKS ==== */
    .about-text-col {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        gap: 28px;
        padding-right: 40px;
    }

    .about-text-block {
        position: relative;
        padding-left: 24px;
        border-left: 2px solid rgba(196, 154, 108, 0.25);
        transition: border-color 0.3s ease;
    }

    .about-text-block:hover {
        border-left-color: var(--accent-gold);
    }

    .about-text-block h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--accent-gold);
        margin-bottom: 10px;
        letter-spacing: -0.2px;
    }

    .about-text-block p {
        color: #c0c0c0;
        font-size: 0.92rem;
        line-height: 1.75;
        margin: 0;
    }

    /* ==== KOLOM KANAN: VIDEO (DINAMIS) ==== */
    .about-video-col {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .video-gradient-blend {
        position: absolute;
        top: 0;
        bottom: 0;
        left: -120px;
        width: 200px;
        background: linear-gradient(90deg, 
            var(--bg-black) 0%, 
            rgba(10, 10, 10, 0.95) 30%,
            rgba(10, 10, 10, 0.6) 60%,
            transparent 100%);
        pointer-events: none;
        z-index: 5;
    }

    .video-wrapper {
        position: relative;
        width: 100%;
        max-width: 560px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 
            0 20px 50px rgba(0, 0, 0, 0.6),
            0 0 60px rgba(196, 154, 108, 0.15);
        transition: max-width 0.5s ease;
    }

    /* Jika video potrait, perkecil max-width biar gak terlalu besar */
    .video-wrapper.is-portrait {
        max-width: 360px;
    }

    /* Jika video landscape lebar */
    .video-wrapper.is-wide {
        max-width: 100%;
    }

    .video-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--accent-gold), transparent);
        z-index: 6;
        pointer-events: none;
    }

    .video-wrapper::after {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        width: 120px;
        background: linear-gradient(90deg, 
            rgba(10, 10, 10, 0.85) 0%, 
            rgba(10, 10, 10, 0.4) 50%,
            transparent 100%);
        pointer-events: none;
        z-index: 3;
    }

    /* FRAME VIDEO - ratio dinamis di-set oleh JavaScript */
    .video-frame {
        position: relative;
        width: 100%;
        background: #050505;
        overflow: hidden;
        aspect-ratio: 16 / 9; /* fallback default */
        transition: aspect-ratio 0.4s ease;
    }

    .video-frame iframe,
    .video-frame video {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
        object-fit: cover;
    }

    .video-caption {
        position: relative;
        z-index: 6;
        text-align: center;
        color: #a0a0a0;
        font-size: 0.82rem;
        padding: 14px 16px;
        border-top: 1px solid var(--border-color);
        background: linear-gradient(180deg, rgba(20, 20, 20, 0.6), rgba(20, 20, 20, 0.95));
    }

    .video-caption i {
        color: var(--accent-gold);
        margin-right: 6px;
        animation: pulsePlay 2s ease-in-out infinite;
    }

    @keyframes pulsePlay {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.6; transform: scale(1.1); }
    }

    /* Loading state sebelum metadata video siap */
    .video-frame.loading::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, #141414 0%, #1c1c1c 50%, #141414 100%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        z-index: 4;
    }

    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* ==== RESPONSIVE ==== */
    @media (max-width: 991px) {
        .about-text-col { padding-right: 0; }
        .video-gradient-blend { display: none; }
        .video-wrapper::after { width: 60px; }
        .video-wrapper.is-portrait { max-width: 320px; }
    }

    @media (max-width: 576px) {
        .about-header h1 { font-size: 1.7rem; }
        .about-text-block { padding-left: 18px; }
        .about-container { padding: 25px 16px 60px; }
        .video-wrapper.is-portrait { max-width: 100%; }
    }
</style>

<div class="about-container">

    <!-- ===== HEADER ===== -->
    <div class="about-header">
        <h1><?= htmlspecialchars($about_title); ?></h1>
        <p class="tagline">"<?= htmlspecialchars($about_tagline); ?>"</p>

        <div class="about-divider">
            <div class="about-divider-line"></div>
            <div class="about-divider-dot"></div>
            <div class="about-divider-line right"></div>
        </div>
    </div>

    <!-- ===== GRID ===== -->
    <div class="about-grid">

        <!-- KOLOM KIRI: TEKS -->
        <div class="about-text-col">
            <?php foreach ($paragraphs as $p): ?>
                <div class="about-text-block">
                    <h3><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($p['heading']); ?></h3>
                    <p><?= htmlspecialchars($p['text']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- KOLOM KANAN: VIDEO -->
        <div class="about-video-col">
            <div class="video-gradient-blend"></div>

            <div class="video-wrapper" id="videoWrapper">
                <div class="video-frame loading" id="videoFrame">
                    <?php if ($video_type === 'youtube' && !empty($youtube_id)): ?>
                        <iframe
                            id="aboutVideoIframe"
                            src="https://www.youtube.com/embed/<?= htmlspecialchars($youtube_id); ?>?rel=0&autoplay=1&mute=1&loop=1&playlist=<?= htmlspecialchars($youtube_id); ?>"
                            title="Video Ranger Coffee"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                    <?php elseif ($video_type === 'local' && !empty($local_video)): ?>
                        <video 
                            id="aboutVideo"
                            autoplay 
                            muted 
                            loop 
                            playsinline 
                            preload="auto"
                            disablepictureinpicture
                            controlsList="nodownload"
                            src="<?= htmlspecialchars($local_video); ?>">
                            Browser kamu tidak mendukung video HTML5.
                        </video>
                    <?php else: ?>
                        <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#666;">
                            <div style="text-align:center;">
                                <i class="fas fa-video-slash" style="font-size: 2.5rem; margin-bottom: 10px;"></i>
                                <p style="margin:0;font-size:0.85rem;">Video belum dikonfigurasi</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="video-caption">
                    <i class="fas fa-play-circle"></i> Cerita di balik secangkir kopi Ranger
                </div>
            </div>
        </div>

    </div>
</div>

<script>
/**
 * Dynamic Aspect Ratio untuk Video
 * Mengatur rasio frame video agar menyesuaikan dengan rasio video asli.
 */
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('aboutVideo');
    const frame = document.getElementById('videoFrame');
    const wrapper = document.getElementById('videoWrapper');

    if (!video || !frame) return;

    /**
     * Fungsi untuk set aspect ratio dari video
     */
    function applyVideoAspectRatio() {
        const w = video.videoWidth;
        const h = video.videoHeight;

        if (w && h) {
            // Set aspect-ratio pada frame agar sesuai dengan video
            frame.style.aspectRatio = w + ' / ' + h;

            // Hitung rasio untuk menentukan max-width wrapper
            const ratio = w / h;

            // Reset class
            wrapper.classList.remove('is-portrait', 'is-wide');

            // Video potrait (rasio < 1) → kurangi lebar maksimum
            if (ratio < 1) {
                wrapper.classList.add('is-portrait');
            } 
            // Video ultra-wide (rasio > 2) → beri lebar maksimum
            else if (ratio > 2) {
                wrapper.classList.add('is-wide');
            }

            // Hilangkan loading state setelah video siap
            frame.classList.remove('loading');

            console.log('✅ Video aspect ratio applied:', w + 'x' + h + ' (ratio: ' + ratio.toFixed(2) + ')');
        }
    }

    // Cek jika video sudah load (kadang metadata sudah ada sebelum JS jalan)
    if (video.readyState >= 1) { // HAVE_METADATA
        applyVideoAspectRatio();
    }

    // Pasang event listener untuk load metadata
    video.addEventListener('loadedmetadata', applyVideoAspectRatio);

    // Fallback jika video gagal load → tampilkan fallback 16:9
    video.addEventListener('error', function(e) {
        console.warn('⚠️ Video gagal dimuat:', e);
        frame.classList.remove('loading');
        frame.style.aspectRatio = '16 / 9';
    });

    // Fallback dengan timeout (kalau metadata ga kunjung load dalam 5 detik)
    setTimeout(function() {
        if (frame.classList.contains('loading')) {
            applyVideoAspectRatio();
        }
    }, 5000);
});

document.addEventListener('DOMContentLoaded', function() {
    const iframe = document.getElementById('aboutVideoIframe');
    const frame = document.getElementById('videoFrame');

    if (iframe && frame) {
        // Kalau kamu mau pakai ratio custom untuk YouTube, bisa set di sini:
        // frame.style.aspectRatio = '16 / 9'; // atau '9 / 16' untuk shorts
        frame.classList.remove('loading');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>