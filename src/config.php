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
    
    // FIX: Define $is_on_login before checking it
    $is_on_login = defined('IS_LOGIN_PAGE'); 
    
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
            // PERBAIKAN: Tambahkan , Tempat_Lahir, Tanggal_Lahir ke dalam SELECT
            $stmtCheck = $pdo->prepare("SELECT NIM, ID_Prodi, Tempat_Lahir, Tanggal_Lahir FROM Mahasiswa WHERE ID_Mahasiswa = ?");
            $stmtCheck->execute([$_SESSION['user_id']]);
            $userData = $stmtCheck->fetch();

            // PERBAIKAN: Cek juga Tanggal_Lahir
            if ($userData && (empty($userData['NIM']) || empty($userData['ID_Prodi']) || empty($userData['Tanggal_Lahir']))) {
                // JIKA KOSONG -> TENDANG KE HALAMAN COMPLETE PROFILE
                header("Location: complete_profile.php");
                exit;
            }
        } catch (Exception $e) {
            // Silent fail
        }
    }
}

function getFotoMhs($nim, $dbPath) {
    global $pdo;

    // 1. Cek Data dari Database (Prioritas Utama)
    if (!empty($dbPath) && file_exists($dbPath)) {
        return $dbPath;
    }

    // 2. Jika DB Kosong, Cari Manual di Folder (Auto-Discovery)
    if (!empty($nim)) {
        // Cari file dengan nama NIM.* (jpg, png, jpeg, dll)
        $files = glob("uploads/avatars/$nim.*"); 
        
        if (!empty($files)) {
            $foundFile = $files[0]; // Ambil file pertama yang ketemu
            
            // 3. FITUR SELF-HEALING (Opsional)
            // Otomatis update database biar besok nggak perlu nyari lagi
            try {
                $stmt = $pdo->prepare("UPDATE Mahasiswa SET Foto_Profil = ? WHERE NIM = ?");
                $stmt->execute([$foundFile, $nim]);
            } catch (Exception $e) {
                // Ignore error (cuma update background)
            }

            return $foundFile;
        }
    }

    // 3. Menyerah (Tidak ada foto)
    return null;
}