<?php
// Host tetap menggunakan localhost atau 127.0.0.1
$host = "localhost"; 
$user = "root";
$pass = "";
$db   = "rangercoffee";
$port = 3307; // Pindahkan port ke variabel tersendiri

// Masukkan variabel port ke parameter ke-5 di mysqli_connect
$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
?>