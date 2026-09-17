<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Jika admin sudah login sebelumnya, langsung lempar ke dashboard admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $username_clean = mysqli_real_escape_string($conn, $username);
        
        // QUERY DISESUAIKAN DENGAN TABEL 'admin' DASAR KAMU
        $query  = "SELECT * FROM admin WHERE username = '$username_clean' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $admin = mysqli_fetch_assoc($result);
            
            // Cek Password (support password_verify hash, md5, atau plain text)
            if (password_verify($password, $admin['password']) || md5($password) === $admin['password'] || $password === $admin['password']) {
                
                // SET SESSION ADMIN DENGAN KOLOM id_admin & nama SESUAI STRUKTUR DB
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = $admin['id_admin'];
                $_SESSION['admin_nama']      = $admin['nama'];
                $_SESSION['admin_username']  = $admin['username'];

                header("Location: admin/index.php");
                exit();
            } else {
                $error = "Password yang kamu masukkan salah!";
            }
        } else {
            $error = "Username admin tidak ditemukan!";
        }
    } else {
        $error = "Silakan isi Username dan Password!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator - Ranger Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #000000;
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-card {
            background: #141414;
            border: 1px solid rgba(196, 154, 108, 0.3);
            border-radius: 20px;
            padding: 35px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }
        .form-control-custom {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff !important;
            border-radius: 12px;
            padding: 12px 15px;
        }
        .form-control-custom:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #c49a6c;
            box-shadow: 0 0 10px rgba(196, 154, 108, 0.3);
        }
        .btn-gold {
            background: linear-gradient(135deg, #c49a6c, #a87d52);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            padding: 12px;
            transition: all 0.3s;
        }
        .btn-gold:hover {
            background: linear-gradient(135deg, #d4a778, #b88b5d);
            color: #000;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <img src="assets/img/logo.png" alt="Logo" style="height: 60px; border-radius: 50%; border: 2px solid #c49a6c;" onerror="this.src='uploads/menu/logo.jpeg'">
        <h5 class="fw-bold mt-3 text-white mb-0">RANGER COFFEE</h5>
        <small class="text-white-50">Masuk ke Panel Administrator</small>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 small rounded-3 text-center" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?= $error; ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="mb-3">
            <label class="form-label small text-white-50">Username</label>
            <input type="text" name="username" class="form-control form-control-custom" placeholder="Masukkan username admin" required autocomplete="off">
        </div>
        <div class="mb-4">
            <label class="form-label small text-white-50">Password</label>
            <input type="password" name="password" class="form-control form-control-custom" placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn btn-gold w-100">
            <i class="fas fa-sign-in-alt me-1"></i> Masuk Sekarang
        </button>
    </form>
    
    <div class="text-center mt-4">
        <a href="index.php" class="text-white-50 small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Kembali ke Menu Utama</a>
    </div>
</div>

</body>
</html>