<style>
    /* Footer Global Container */
    .footer-section {
        background: linear-gradient(180deg, #050505 0%, #000000 100%);
        border-top: 1px solid rgba(196, 154, 108, 0.2);
        position: relative;
        overflow: hidden;
    }

    /* Ambient Glow Background Effect */
    .footer-section::before {
        content: '';
        position: absolute;
        top: -100px;
        left: 50%;
        transform: translateX(-50%);
        width: 600px;
        height: 200px;
        background: radial-gradient(ellipse at center, rgba(196, 154, 108, 0.08) 0%, transparent 70%);
        pointer-events: none;
    }

    .footer-brand-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 800;
        letter-spacing: -0.5px;
        background: linear-gradient(135deg, #ffffff 50%, var(--accent-gold));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Profile / Logo Bulat Modern (Circle Avatar) */
    .footer-profile-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%; /* Membuat gambar profile/logo jadi bulat sempurna */
        object-fit: cover;
        border: 2px solid var(--accent-gold);
        box-shadow: 0 0 15px rgba(196, 154, 108, 0.35);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .footer-profile-avatar:hover {
        transform: scale(1.08) rotate(5deg);
        box-shadow: 0 0 25px rgba(196, 154, 108, 0.6);
    }

    /* Modern Card Layout inside Footer */
    .footer-card {
        background: rgba(20, 20, 20, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 20px;
        padding: 24px;
        height: 100%;
        backdrop-filter: blur(10px);
        transition: border-color 0.3s ease, transform 0.3s ease;
    }

    .footer-card:hover {
        border-color: rgba(196, 154, 108, 0.3);
        transform: translateY(-3px);
    }

    /* Social Media Buttons */
    .social-btn {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #b0b0b0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        text-decoration: none;
    }

    .social-btn:hover {
        background: linear-gradient(135deg, var(--accent-gold), #a87d52);
        color: #000000;
        transform: translateY(-4px);
        border-color: var(--accent-gold);
        box-shadow: 0 8px 20px rgba(196, 154, 108, 0.4);
    }

    .social-btn.wa:hover {
        background: #25D366;
        color: #ffffff;
        border-color: #25D366;
        box-shadow: 0 8px 20px rgba(37, 211, 102, 0.4);
    }

    /* Google Maps Responsive Wrapper */
    .footer-map-container {
        position: relative;
        width: 100%;
        height: 160px;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(196, 154, 108, 0.25);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.6);
    }

    .footer-map-container iframe {
        width: 100%;
        height: 100%;
        border: 0;
    }

    .footer-heading {
        color: var(--accent-gold);
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
</style>

<footer class="footer-section mt-5 pt-5 pb-3">
    <div class="container px-3 px-md-4">
        
        <!-- Top Tagline Header (Matching Hero Style) -->
        <div class="text-center mb-5">
            <h2 class="footer-brand-title m-0 fs-2 fw-bold">Pesan & Nikmati Kopi Terbaik</h2>
        </div>

        <!-- 3 Modern Card Grid -->
        <div class="row g-4 text-start mb-5">
            
            <!-- Card 1: Branding & Profile Bulat -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <!-- Profile Logo Bulat Sempurna -->
                        <img src="assets/img/logo.png" alt="Ranger Coffee Logo" class="footer-profile-avatar" onerror="this.src='uploads/menu/logo.jpeg'">
                        <div>
                            <h5 class="fw-bold text-white m-0" style="letter-spacing: 0.5px;">RANGER COFFEE</h5>
                            <span class="small text-white-50" style="font-size: 0.78rem;">Bekasi Utara</span>
                        </div>
                    </div>
                    
                    <p class="small text-white-50 mb-4" style="line-height: 1.6; font-size: 0.85rem;">
                        Kedai kopi pilihan yang menyajikan varian espresso, fresh brew, dan cemilan lezat dengan tempat yang nyaman untuk bersantai.
                    </p>

                    <div class="d-flex align-items-center gap-2">
                        <a href="https://wa.me/6281780532551" target="_blank" class="social-btn wa" title="WhatsApp">
                            <i class="fab fa-whatsapp fs-5"></i>
                        </a>
                        <a href="#" target="_blank" class="social-btn" title="Instagram">
                            <i class="fab fa-instagram fs-5"></i>
                        </a>
                        <a href="#" target="_blank" class="social-btn" title="TikTok">
                            <i class="fab fa-tiktok fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 2: Jam Operasional & Informasi Layanan -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-card">
                    <h6 class="footer-heading mb-3">
                        <i class="far fa-clock me-2" style="color: var(--accent-orange);"></i>Jam Operasional
                    </h6>

                    <ul class="list-unstyled small text-white-50 mb-3" style="line-height: 2.2; font-size: 0.85rem;">
                        <li class="d-flex justify-content-between border-bottom border-secondary border-opacity-10 pb-1">
                            <span>Senin - Jumat:</span>
                            <strong class="text-white">19.00 - 22.00 WIB</strong>
                        </li>
                        <li class="d-flex justify-content-between border-bottom border-secondary border-opacity-10 pb-1 pt-1">
                            <span>Sabtu - Minggu:</span>
                            <strong class="text-white">08.00 - 23.00 WIB</strong>
                        </li>
                    </ul>

                </div>
            </div>

            <!-- Card 3: Lokasi & Maps Responsif -->
            <div class="col-lg-4 col-md-12">
                <div class="footer-card">
                    <h6 class="footer-heading mb-2">
                        <i class="fas fa-map-marker-alt me-2" style="color: var(--accent-orange);"></i>Lokasi Outlet
                    </h6>
                    <p class="small text-white-50 mb-3" style="line-height: 1.5; font-size: 0.82rem;">
                        Jl. Bumi Alinda Kencana No.21, Blok C3, Kaliabang Tengah, Bekasi Utara, Kota Bekasi, Jawa Barat 17123
                    </p>

                    <div class="footer-map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.477231764113!2d107.0044793750376!3d-6.2005979937871505!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e698be8312011ff%3A0xaf21ffb71b20db0e!2sRanger%20coffee!5e0!3m2!1sid!2sid!4v1786009659720!5m2!1sid!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                </div>
            </div>

        </div>

        <!-- Copyright Bottom Bar -->
        <div class="pt-4 border-top border-secondary border-opacity-15 text-center">
            <p class="small text-white-50 m-0" style="font-size: 0.78rem;">
                &copy; <?= date('Y'); ?> <strong class="text-white">Ranger Coffee Bekasi</strong>. All Rights Reserved.
            </p>
        </div>

    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>