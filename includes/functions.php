<?php
require_once __DIR__ . '/../config/database.php';

// Tambahkan type hint float|int pada $angka
function rupiah(float|int $angka): string {
    return "Rp. " . number_format($angka, 0, ',', '.');
}

// Tambahkan type hint string pada $sql
function query(string $sql): array {
    /** @var mysqli $conn */
    global $conn;
    
    $result = mysqli_query($conn, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// Fungsi helper untuk mengambil data pengaturan toko
function get_pengaturan(): ?array {
    $data = query("SELECT * FROM pengaturan LIMIT 1");
    return !empty($data) ? $data[0] : null;
}

/**
 * Hitung total jumlah item di keranjang belanja
 */
function total_item_keranjang() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $total = 0;
    if (isset($_SESSION['keranjang']) && is_array($_SESSION['keranjang'])) {
        foreach ($_SESSION['keranjang'] as $item) {
            $total += (int)($item['jumlah'] ?? 0);
        }
    }
    return $total;
}

/**
 * Memotong teks deskripsi jika terlalu panjang
 * 
 * @param string $str
 * @param int $n
 * @param string $end_char
 * @return string
 */
if (!function_exists('character_limiter')) {
    function character_limiter(string $str, int $n = 35, string $end_char = '&#8230;'): string {
        if (mb_strlen($str) <= $n) {
            return $str;
        }
        $out = mb_substr($str, 0, $n);
        return $out . $end_char;
    }
}

/**
 * Fungsi Pengirim Notifikasi WhatsApp (Contoh Menggunakan Fonnte)
 * Silakan sesuaikan $token dan endpoint URL dengan Provider WA Gateway yang kamu pakai
 */
function kirimWA($no_hp, $pesan) {
    // 1. Format Nomor Telepon (Ubah 08xx / +628xx menjadi 628xx)
    $no_hp = preg_replace('/[^0-9]/', '', $no_hp);
    if (substr($no_hp, 0, 1) === '0') {
        $no_hp = '62' . substr($no_hp, 1);
    } elseif (substr($no_hp, 0, 2) !== '62') {
        $no_hp = '62' . $no_hp;
    }

    // 2. Masukkan Token API Gateway WA Kamu
    $token = 'TOKEN_WA_GATEWAY_KAMU_DISINI'; 

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send', // Sesuaikan URL Gateway WA kamu
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $no_hp,
            'message' => $pesan,
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $token
        ),
        // Bypass SSL untuk Localhost Laragon
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return ['status' => false, 'error' => $err];
    }

    return ['status' => true, 'response' => json_decode($response, true)];
}

/**
 * Mengambil Waktu Awal Shift Operasional Toko (Jam 06.00 Pagi)
 */
function getStartOfShift() {
    $current_time = time();
    $today_6am    = strtotime('today 06:00:00');

    // Jika sekarang sebelum jam 6 pagi, shift dimulai dari jam 6 pagi kemarin
    if ($current_time < $today_6am) {
        return date('Y-m-d H:i:s', strtotime('yesterday 06:00:00'));
    }
    
    // Jika sudah jam 6 pagi ke atas, shift dimulai jam 6 pagi hari ini
    return date('Y-m-d 06:00:00', $today_6am);
}

/**
 * Cek apakah toko sedang buka berdasarkan jadwal:
 * - Senin-Jumat: 19:00 - 22:00
 * - Sabtu-Minggu: 08:00 - 22:00
 */
/**
 * Cek apakah toko sedang buka.
 * Untuk testing: tambahkan ?test=open atau ?test=closed di URL.
 * Contoh: index.php?test=open
 */
function isTokoOpen() {
    global $conn;
    
    $settings = get_pengaturan();
    
    if ($settings && isset($settings['manual_override'])) {
        if ((int)$settings['manual_override'] === 1) {
            return (int)$settings['manual_status'] === 1;
        }
    }

    // Hapus / komentari bagian testing ?test= di bawah jika sudah live
    if (isset($_GET['test'])) {
        if ($_GET['test'] === 'open') return true;
        if ($_GET['test'] === 'closed') return false;
    }

    date_default_timezone_set('Asia/Jakarta');
    $day  = (int)date('N');
    $hour = (int)date('G');

    if ($day >= 1 && $day <= 5) {
        return ($hour >= 19 && $hour < 22);
    } else {
        return ($hour >= 8 && $hour < 22);
    }
}
?>