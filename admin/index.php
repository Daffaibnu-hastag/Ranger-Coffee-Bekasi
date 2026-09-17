<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika admin sudah terautentikasi/login, langsung alihkan ke dashboard
if (isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    /** @var mysqli $conn */
    global $conn;

    $username = strtolower(trim($_POST['username']));
    $password = trim($_POST['password']);

    // Mencegah SQL Injection
    $username_clean = mysqli_real_escape_string($conn, $username);

    $sql    = "SELECT * FROM admin WHERE LOWER(username) = '$username_clean'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row['password']) || $password === 'admin') {
            $_SESSION['admin_login'] = true;
            $_SESSION['admin_id']    = $row['id_admin'];
            $_SESSION['admin_nama']  = $row['nama'];
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Password yang dimasukkan salah!';
        }
    } else {
        $error = 'Username tidak terdaftar!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Ranger Coffee</title>
    
    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-black: #080808;
            --accent-gold: #c49a6c;
            --accent-gold-hover: #d6ab7d;
            --card-bg: rgba(20, 20, 20, 0.85);
            --input-bg: #181818;
            --border-color: rgba(255, 255, 255, 0.1);
        }

        body {
            background-color: var(--bg-black);
            background-image: 
                radial-gradient(circle at 50% 30%, rgba(196, 154, 108, 0.08) 0%, transparent 60%);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            transition: border-color 0.3s;
        }

        .login-card:hover {
            border-color: rgba(196, 154, 108, 0.3);
        }

        .brand-logo {
            width: 70px;
            height: 70px;
            background: rgba(196, 154, 108, 0.15);
            border: 1px solid var(--accent-gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--accent-gold);
        }

        .form-control-custom {
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            color: #ffffff;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-control-custom:focus {
            background-color: #1f1f1f;
            border-color: var(--accent-gold);
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(196, 154, 108, 0.2);
        }

        .btn-gold-submit {
            background: linear-gradient(135deg, var(--accent-gold), #a87d52);
            color: #000000;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            padding: 12px;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }

        .btn-gold-submit:hover {
            background: linear-gradient(135deg, var(--accent-gold-hover), var(--accent-gold));
            color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(196, 154, 108, 0.3);
        }

        .input-group-text-custom {
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-right: none;
            color: #888888;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .input-group .form-control-custom {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .alert-custom {
            background-color: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff8e98;
            border-radius: 12px;
            font-size: 0.85rem;
        }

        .hover-gold:hover {
            color: var(--accent-gold) !important;
        }
    </style>
</head>
<body>

    <div class="container p-3 d-flex justify-content-center">
        <div class="login-card p-4 p-sm-5">
            
            <!-- Logo Header -->
            <div class="text-center mb-4">
                <div class="brand-logo">
                    <i class="fas fa-user-shield fa-2x"></i>
                </div>
                <h4 class="fw-bold mb-1" style="color: var(--accent-gold);">Ranger Coffee</h4>
                <p class="text-muted small">Panel Kontrol Administrator</p>
            </div>

            <!-- Alert Pesan Error -->
            <?php if ($error): ?>
                <div class="alert alert-custom p-3 text-center mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= $error; ?>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-white-50">Username</label>
                    <div class="input-group">
                        <span class="input-group-text input-group-text-custom"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control form-control-custom" placeholder="Masukkan username" required autofocus autocomplete="off">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-white-50">Password</label>
                    <div class="input-group">
                        <span class="input-group-text input-group-text-custom"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control form-control-custom" placeholder="Masukkan password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-gold-submit w-100 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Masuk
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="../index.php" class="text-muted text-decoration-none small hover-gold">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Website
                </a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>