<?php
// --- KONEKSI DATABASE ---
$host = 'db'; 
$db   = 'db_lomba';
$user = 'user';
$pass = 'userpass';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Jika database mati total, paksa ke maintenance
    header("Location: maintenance.php");
    exit;
}

// --- PENGATURAN MAINTENANCE MODE ---

// Ubah ke TRUE untuk mengaktifkan Maintenance
// Ubah ke FALSE untuk mematikan (Web Live)
$is_maintenance = false; // Coba set true untuk testing
$enable_google_login = true; 

if ($is_maintenance) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // CEK PENANDA HALAMAN (Metode Konstanta - Anti Loop)
    $is_on_maintenance = defined('IS_MAINTENANCE_PAGE');
    $is_on_login       = defined('IS_LOGIN_PAGE');
    
    // Jika BUKAN di halaman maintenance DAN BUKAN di halaman login
    if (!$is_on_maintenance && !$is_on_login) {
        
        // Cek apakah Admin? (Admin boleh lewat)
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            // Admin bebas akses (Do nothing)
        } else {
            // User biasa dilempar ke maintenance
            header("Location: maintenance.php");
            exit;
        }
    }
}
?>