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

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'mahasiswa') {
    
    // Cek apakah kita sedang tidak di halaman complete_profile atau logout
    // (Agar tidak terjadi infinite loop redirection)
    $is_on_complete_page = defined('IS_COMPLETE_PROFILE_PAGE');
    $current_script = basename($_SERVER['PHP_SELF']);
    
    if (!$is_on_complete_page && $current_script !== 'logout.php') {
        
        // Cek ke Database: Apakah NIM atau Prodi masih kosong?
        // Kita query ringan setiap page load (aman untuk skala ini)
        try {
            $stmtCheck = $pdo->prepare("SELECT NIM, ID_Prodi FROM Mahasiswa WHERE ID_Mahasiswa = ?");
            $stmtCheck->execute([$_SESSION['user_id']]);
            $userData = $stmtCheck->fetch();

            if ($userData && (empty($userData['NIM']) || empty($userData['ID_Prodi']))) {
                // JIKA KOSONG -> TENDANG KE HALAMAN COMPLETE PROFILE
                header("Location: complete_profile.php");
                exit;
            }
        } catch (Exception $e) {
            // Silent fail
        }
    }
}
?>