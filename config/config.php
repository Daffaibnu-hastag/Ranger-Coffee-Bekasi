<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ubah URL sesuai dengan path project kamu
define('BASE_URL', 'http://localhost/kopi-online/');

// Nomor WhatsApp Admin (Gunakan format 62...)
define('WA_ADMIN', '6281780532551');
?>