<?php
ob_start(); // Buffer output untuk mencegah header error
session_start();
require_once 'config.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$namaUser = $_SESSION['nama'] ?? 'User';
$roleUser = $_SESSION['role'] ?? 'mahasiswa';
$userId   = $_SESSION['user_id'];
$level    = $_SESSION['level'] ?? ''; // Tambahan untuk cek level admin

// Daftar Halaman Admin (Whitelist) - Agar routing aman
$adminPages = ['user_admin', 'master']; 

// --- 1. LOGIKA HANDLE FORM COMPLETE DATA (KHUSUS MAHASISWA) ---
$showCompleteModal = false;
$modalError = '';
$prodiListModal = [];

// Halaman yang bebas dari pemblokiran modal
$excludedPages = ['edit_profile', 'profile', 'logout', 'edit_profile_dosen', 'profile_dosen', 'complete_profile'];
$is_on_excluded_page = in_array($page, $excludedPages);

if (isset($_SESSION['need_reset']) && $_SESSION['need_reset'] == 1) {
    // Hanya boleh akses halaman force_change_password atau logout
    if ($page !== 'force_change_password' && $page !== 'logout') {
        header("Location: ?page=force_change_password");
        exit;
    }
}

if ($roleUser === 'mahasiswa') {
    // A. Handle Submit Form Modal
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_data') {
        header("Location: complete_profile.php");
        exit;
    }

    // B. Cek Apakah Data Masih Kosong? (Trigger Modal)
    if (!$showCompleteModal) {
        $stmtCheck = $pdo->prepare("SELECT NIM, ID_Prodi FROM Mahasiswa WHERE ID_Mahasiswa = ?");
        $stmtCheck->execute([$userId]);
        $myData = $stmtCheck->fetch();

        if ($myData && (empty($myData['NIM']) || empty($myData['ID_Prodi']))) {
            if (!$is_on_excluded_page) {
                header("Location: complete_profile.php");
                exit;
            }
        }
    }
}

// --- 2. LOGIKA AMBIL FOTO PROFIL TERBARU ---
$userFoto = null;
try {
    if ($roleUser === 'mahasiswa') {
        $stmt = $pdo->prepare("SELECT Foto_Profil, NIM FROM Mahasiswa WHERE ID_Mahasiswa = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch();
        if ($data) $userFoto = getFotoMhs($data['NIM'], $data['Foto_Profil']);
    } elseif ($roleUser === 'dosen') {
        $stmt = $pdo->prepare("SELECT Foto_Profil, NIDN FROM Dosen_Pembimbing WHERE ID_Dosen = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch();
        if ($data) $userFoto = getFotoMhs('DSN_'.$data['NIDN'], $data['Foto_Profil']);
    } elseif ($roleUser === 'admin') {
        $stmt = $pdo->prepare("SELECT Foto_Profil FROM Admin WHERE ID_Admin = ?");
        $stmt->execute([$userId]);
        $userFoto = $stmt->fetchColumn();
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPARTA - Sistem Pencarian Tim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0d6efd, #0dcaf0);
            --sidebar-bg: #212529;
            --sidebar-width: 280px;
        }
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }
        
        /* Sidebar Styling */
        .sidebar { 
            width: var(--sidebar-width); 
            background-color: var(--sidebar-bg); 
            min-height: 100vh; 
            color: #fff;
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar-brand {
            display: block;
            text-decoration: none !important;
        }

        .sidebar-brand:hover .brand-wrapper {
            transform: translateX(5px);
        }

        .brand-icon {
            font-family: 'Roboto Slab', serif;
            font-size: 2.8rem; /* Ukuran Besar */
            font-weight: 900;
            line-height: 1;
            /* Gradient Biru Elektrik */
            background: linear-gradient(180deg, #0dcaf0 0%, #0d6efd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            /* Efek Glow/Bersinar */
            filter: drop-shadow(0 0 12px rgba(13, 202, 240, 0.5));
            margin-right: 12px;
        }

        .brand-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-name {
            font-family: 'Roboto Slab', serif;
            font-size: 1.6rem; /* Judul Tebal */
            font-weight: 800;
            line-height: 1;
            letter-spacing: 1px;
            /* Gradient Putih ke Silver */
            background: linear-gradient(90deg, #ffffff 0%, #adb5bd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-tagline {
            font-family: 'Inter', sans-serif;
            font-size: 0.55rem; /* Tagline Kecil */
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 4px;
        }

        .sparta-logo { 
            font-family: 'Roboto Slab', serif; 
            font-size: 1.8rem; 
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            letter-spacing: 1px;
        }
        
        /* Navigation Links */
        .nav-link { 
            color: rgba(255,255,255,0.7); 
            padding: 12px 20px; 
            margin: 4px 15px;
            border-radius: 50px; /* Pill Shape */
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        .nav-link:hover { 
            color: #fff; 
            background-color: rgba(255,255,255,0.1); 
            transform: translateX(5px);
        }
        .nav-link.active { 
            background: var(--primary-gradient); 
            color: #fff; 
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);
        }
        .nav-link i { width: 25px; text-align: center; margin-right: 10px; font-size: 1.1rem; }

        /* Main Content Wrapper */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navbar */
        .top-navbar {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px 30px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        /* Content Area */
        .content { 
            padding: 30px; 
            flex-grow: 1;
            animation: fadeIn 0.5s ease-out; 
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* User Dropdown */
        .user-avatar-nav {
            width: 45px; height: 45px;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .dropdown-menu { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 12px; margin-top: 10px; }
        .dropdown-item { padding: 10px 20px; border-radius: 8px; margin: 2px 5px; width: auto; font-size: 0.9rem; }
        .dropdown-item:hover { background-color: #f8f9fa; color: #0d6efd; }
        .dropdown-item.active, .dropdown-item:active { background-color: #0d6efd; color: #fff; }
    </style>
</head>
<body>

    <div class="sidebar d-flex flex-column flex-shrink-0 py-3">
        <a href="/" class="sidebar-brand mb-4 px-4 mt-2">
            <div class="brand-wrapper">
                <div class="brand-icon">&Lambda;</div>
                
                <div class="brand-info">
                    <span class="brand-name">SPARTA</span>
                    <span class="brand-tagline">Team Matching</span>
                </div>
            </div>
        </a>
        
        <div class="px-4 mb-2 mt-2"><small class="text-white-50 text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Menu Utama</small></div>
        
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href="?page=dashboard" class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="nav-item">
                <a href="?page=notifikasi" class="nav-link <?= $page == 'notifikasi' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i> Notifikasi 
                    <?php
                        // Hitung notif pending
                        $tipe = $_SESSION['role'] ?? 'mahasiswa';
                        $uid = $_SESSION['user_id'];
                        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM Invitasi WHERE ID_Penerima = ? AND Tipe_Penerima = ? AND Status = 'Pending'");
                        $stmtCount->execute([$uid, $tipe]);
                        $countNotif = $stmtCount->fetchColumn();
                        if($countNotif > 0): 
                    ?>
                        <span class="badge bg-danger ms-auto rounded-pill" style="font-size: 0.6rem;"><?= $countNotif ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item"><a href="?page=ranking" class="nav-link <?= $page == 'ranking' ? 'active' : '' ?>"><i class="fas fa-trophy"></i> Leaderboard</a></li>
            <li class="nav-item"><a href="?page=lomba" class="nav-link <?= $page == 'lomba' ? 'active' : '' ?>"><i class="fas fa-rocket"></i> Kompetisi</a></li>
            <li class="nav-item"><a href="?page=tim" class="nav-link <?= $page == 'tim' ? 'active' : '' ?>"><i class="fas fa-search"></i> Cari Tim</a></li>
            <li class="nav-item"><a href="?page=cari_dosen" class="nav-link <?= $page == 'cari_dosen' ? 'active' : '' ?>"><i class="fas fa-chalkboard-teacher"></i> Cari Dosen</a></li>

            <?php if($roleUser === 'mahasiswa'): ?>
                <div class="px-4 mb-2 mt-4"><small class="text-white-50 text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Area Mahasiswa</small></div>
                <li class="nav-item"><a href="?page=manajemen_tim" class="nav-link <?= $page == 'manajemen_tim' ? 'active' : '' ?>"><i class="fas fa-briefcase"></i> Tim Saya</a></li>
            <?php endif; ?>

            <?php 
                $isDosenAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'dosen' && isset($_SESSION['dosen_is_admin']) && $_SESSION['dosen_is_admin'] == 1);
                $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
            ?>

            <?php if($isAdmin || $isDosenAdmin): ?>
            <div class="px-4 mb-2 mt-4"><small class="text-white-50 text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Manajemen Data</small></div>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item"><a href="?page=mahasiswa" class="nav-link <?= $page == 'mahasiswa' ? 'active' : '' ?>"><i class="fas fa-user-graduate"></i> Data Mahasiswa</a></li>
                <li class="nav-item"><a href="?page=dosen" class="nav-link <?= $page == 'dosen' ? 'active' : '' ?>"><i class="fas fa-chalkboard-teacher"></i> Data Dosen</a></li>
                
                <?php if($isAdmin): ?>
                    <?php if(isset($_SESSION['admin_level']) && $_SESSION['admin_level'] === 'superadmin'): ?>
                        <li class="nav-item"><a href="?page=user_admin" class="nav-link <?= $page == 'user_admin' ? 'active' : '' ?>"><i class="fas fa-user-shield"></i> Kelola Admin</a></li>
                        <li class="nav-item"><a href="?page=master" class="nav-link <?= $page == 'master' ? 'active' : '' ?>"><i class="fas fa-database"></i> Master Data</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <?php endif; ?>
        </ul>

        <div class="mt-auto px-4 pb-2 text-center">
            <small class="text-white-50" style="font-size: 0.7rem;">&copy; 2025 SPARTA v2.0</small>
        </div>
    </div>

    <div class="main-wrapper">
        
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h5 class="mb-0 fw-bold text-dark d-none d-md-block" style="font-family: 'Roboto Slab';">
                    Selamat Datang, <span class="text-primary"><?= htmlspecialchars($namaUser) ?></span>!
                </h5>
            </div>
            
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="me-3 text-end lh-1 d-none d-md-block">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($namaUser) ?></div>
                        <small class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            <?php 
                            if ($roleUser === 'admin') echo "Administrator";
                            elseif ($roleUser === 'dosen') echo "Dosen";
                            else echo "Mahasiswa";
                            ?>
                        </small>
                    </div>
                    <?php if(!empty($userFoto) && file_exists($userFoto)): ?>
                        <img src="<?= $userFoto ?>?t=<?= time() ?>" class="rounded-circle user-avatar-nav">
                    <?php else: ?>
                        <div class="rounded-circle bg-gradient bg-primary text-white d-flex align-items-center justify-content-center user-avatar-nav" style="font-size: 1.2rem;">
                            <?= strtoupper(substr($namaUser, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </a>
                
                <ul class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="dropdownUser1" style="min-width: 220px;">
                    <li class="px-2 py-2 d-md-none border-bottom mb-2">
                        <div class="fw-bold"><?= htmlspecialchars($namaUser) ?></div>
                        <small class="text-muted"><?= ucfirst($roleUser) ?></small>
                    </li>
                    
                    <?php if($roleUser === 'mahasiswa'): ?>
                        <li><a class="dropdown-item" href="?page=profile&id=<?= $_SESSION['user_id'] ?>"><i class="fas fa-user-circle me-2 text-secondary"></i> Lihat Profil</a></li>
                        <li><a class="dropdown-item" href="?page=edit_profile"><i class="fas fa-cog me-2 text-secondary"></i> Edit Profil</a></li>
                        <li><hr class="dropdown-divider my-2"></li>
                    <?php endif; ?>
                    
                    <?php if($roleUser === 'dosen'): ?>
                        <li><a class="dropdown-item" href="?page=profile_dosen&id=<?= $_SESSION['user_id'] ?>"><i class="fas fa-user-tie me-2 text-secondary"></i> Lihat Profil</a></li>
                        <li><a class="dropdown-item" href="?page=edit_profile_dosen"><i class="fas fa-cog me-2 text-secondary"></i> Edit Profil</a></li>
                        <li><hr class="dropdown-divider my-2"></li>
                    <?php endif; ?>

                    <li><a class="dropdown-item text-danger fw-bold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="content">
            <?php
            // --- UPDATED ROUTING LOGIC (AMAN & BISA BUKA FOLDER ADMIN) ---
            
            // 1. Cek Apakah Halaman Admin (Whitelist Check)
            if (in_array($page, $adminPages)) {
                $file = "pages/admin/$page.php";
                
                if (file_exists($file)) {
                    // Cek Hak Akses (Harus Admin)
                    if ($roleUser === 'admin') {
                        include $file;
                    } else {
                        // Jika bukan admin, tolak akses
                        if (file_exists('error.php')) { header("Location: error.php?code=403"); }
                        else { echo "<div class='alert alert-danger'>Akses Ditolak! Halaman ini hanya untuk Administrator.</div>"; }
                    }
                } else {
                    echo "<div class='alert alert-warning'>Halaman admin '$page' tidak ditemukan.</div>";
                }
            } 
            // 2. Halaman Biasa (Pages)
            elseif (file_exists("pages/$page.php")) {
                include "pages/$page.php";
            } 
            // 3. 404 Not Found
            else {
                if (file_exists('error.php')) { include('error.php'); } 
                else { 
                    echo "<div class='text-center py-5'>
                            <div class='mb-3'><i class='fas fa-exclamation-triangle fa-3x text-warning'></i></div>
                            <h3>404 - Halaman Tidak Ditemukan</h3>
                            <p>Halaman yang Anda cari tidak tersedia.</p>
                            <a href='index.php' class='btn btn-primary rounded-pill px-4'>Kembali ke Dashboard</a>
                          </div>"; 
                }
            }
            ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>