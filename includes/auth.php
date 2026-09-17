<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah session penanda admin sudah di-set dan bernilai true
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    
    // Tentukan URL redirect kembali ke landing page root
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host     = $_SERVER['HTTP_HOST'];
    
    // Menghitung path root projek secara otomatis
    $redirect_url = $protocol . "://" . $host . "/rangercoffee/index.php";
    
    header("Location: " . $redirect_url);
    exit(); // PENTING: Mencegah eksekusi script selanjutnya
}
?>