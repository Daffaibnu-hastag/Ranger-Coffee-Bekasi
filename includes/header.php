<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranger Coffee - Pesan Kopi Online</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #000000;
            --bg-card: #141414;
            --accent-gold: #c49a6c;
            --accent-orange: #d96b27;
            --text-light: #ffffff;
            --text-muted: #b0b0b0; /* Abu-abu terang agar kontras di hitam */
        }
        
        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
        }

        /* Override Bootstrap Text Muted */
        .text-muted {
            color: var(--text-muted) !important;
        }

        .navbar-custom {
            background-color: var(--accent-gold);
            border-radius: 30px;
            margin: 20px auto;
            max-width: 420px;
            box-shadow: 0 4px 15px rgba(196, 154, 108, 0.2);
        }
        
        .navbar-custom .nav-link {
            color: #000000 !important;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 30px;
            transition: all 0.2s ease;
        }

        .navbar-custom .nav-link:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }

        .hero-title {
            font-weight: 700;
            font-size: 2.3rem;
            color: #ffffff;
            line-height: 1.3;
        }

        .hero-subtitle {
            color: #cccccc;
            font-size: 0.95rem;
        }

        .btn-gold {
            background-color: var(--accent-gold);
            color: #000000;
            font-weight: 600;
            border-radius: 20px;
            border: none;
            transition: transform 0.2s;
        }

        .btn-gold:hover {
            background-color: #d6ab7d;
            color: #000000;
            transform: translateY(-2px);
        }

        .btn-orange {
            background-color: var(--accent-orange);
            color: #ffffff;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
        }

        .btn-orange:hover {
            background-color: #e87a36;
            color: #ffffff;
        }

        .card-menu {
            background-color: var(--bg-card);
            border: 1px solid #282828;
            border-radius: 18px;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
        }

        .card-menu:hover {
            transform: translateY(-5px);
            border-color: var(--accent-gold);
        }

/* Sesuaikan atau Hapus .category-container di header.php jika ada */
.category-container {
    background-color: transparent;
    border-radius: 0;
    padding: 0;
}

        .category-container .btn {
            color: #000000;
            font-weight: 600;
        }

        .category-container .btn.active {
            background-color: #000000;
            color: var(--accent-gold);
            border-radius: 10px;
        }

        .floating-cart {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: linear-gradient(135deg, var(--accent-gold), #b3885b);
            color: #000;
            padding: 12px 22px;
            border-radius: 50px;
            box-shadow: 0 8px 25px rgba(196, 154, 108, 0.4);
            text-decoration: none;
            font-weight: 700;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .floating-cart:hover {
            transform: translateY(-4px) scale(1.03);
            color: #000;
            box-shadow: 0 12px 30px rgba(196, 154, 108, 0.6);
        }

        .cart-badge {
            background: #000;
            color: var(--accent-gold);
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.85rem;
        }
    </style>

    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
    window.OneSignalDeferred = window.OneSignalDeferred || [];
    OneSignalDeferred.push(function(OneSignal) {
        OneSignal.init({
            appId: "APP_ID_KAMU_DISINI",
            allowLocalhostAsSecureOrigin: true, // <-- PENTING UNTUK LOCALHOST
            notifyButton: {
                enable: true,
            },
        });
    });
</script></body>
</head>
<body>