<?php
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

// --- LOGIC BARU: AMBIL FOTO PROFIL TERBARU ---
// Kita query langsung ke DB agar saat user ganti foto, header langsung berubah (Realtime)
$userFoto = null;
try {
    if ($roleUser === 'mahasiswa') {
        $stmt = $pdo->prepare("SELECT Foto_Profil FROM Mahasiswa WHERE ID_Mahasiswa = ?");
    } elseif ($roleUser === 'dosen') {
        $stmt = $pdo->prepare("SELECT Foto_Profil FROM Dosen_Pembimbing WHERE ID_Dosen = ?");
    } elseif ($roleUser === 'admin') {
        $stmt = $pdo->prepare("SELECT Foto_Profil FROM Admin WHERE ID_Admin = ?");
    }
    
    if (isset($stmt)) {
        $stmt->execute([$userId]);
        $userFoto = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    // Silent error jika gagal ambil foto
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPARTA - Sistem Pencarian Tim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        
        /* SIDEBAR STYLE */
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; transition: all 0.3s; }
        .sparta-logo { font-family: 'Roboto Slab', serif; font-size: 2rem; line-height: 1; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 20px; display: flex; align-items: center; transition: 0.2s; border-radius: 8px; margin: 2px 10px; font-size: 0.9rem; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: #fff; transform: translateX(3px); }
        .sidebar a.active { background-color: #c0392b; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        
        /* NAVBAR DROPDOWN STYLE */
        .dropdown-toggle::after { display: none; }
        .user-dropdown:hover { background-color: #f8f9fa; cursor: pointer; border-radius: 8px; }
        .content { padding: 30px; animation: fadeIn 0.4s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar d-flex flex-column flex-shrink-0 py-3" style="width: 260px;">
            <a href="/" class="d-flex align-items-center mb-4 text-white text-decoration-none px-4">
                <span class="sparta-logo text-warning me-3">&Lambda;</span>
                <div class="d-flex flex-column">
                    <span class="fs-5 fw-bold" style="letter-spacing: 1px;">SPARTA</span>
                    <small style="font-size: 0.65rem; color: #bdc3c7;">TEAM MATCHING SYSTEM</small>
                </div>
            </a>
            
            <div class="px-4 mb-2"><small class="text-uppercase fw-bold" style="color: #ffffff; font-size: 0.7rem;">Menu Utama</small></div>
            <ul class="nav nav-pills flex-column mb-auto gap-1">
                <li class="nav-item"><a href="?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home me-3 text-center" style="width: 20px;"></i> Dashboard</a></li>
                <li class="nav-item"><a href="?page=ranking" class="<?= $page == 'ranking' ? 'active' : '' ?>"><i class="fas fa-trophy me-3 text-center" style="width: 20px;"></i> Leaderboard</a></li>
                <li class="nav-item"><a href="?page=lomba" class="<?= $page == 'lomba' ? 'active' : '' ?>"><i class="fas fa-scroll me-3 text-center" style="width: 20px;"></i> Kompetisi</a></li>
                
                <li class="nav-item"><a href="?page=tim" class="<?= $page == 'tim' ? 'active' : '' ?>"><i class="fas fa-search me-3 text-center" style="width: 20px;"></i> Cari Tim</a></li>

                <?php if($roleUser === 'mahasiswa'): ?>
                    <li class="nav-item"><a href="?page=manajemen_tim" class="<?= $page == 'manajemen_tim' ? 'active' : '' ?> text-warning"><i class="fas fa-briefcase me-3 text-center" style="width: 20px;"></i> Tim Saya</a></li>
                <?php endif; ?>
            </ul>

            <?php if($roleUser === 'admin' || $roleUser === 'dosen'): ?>
            <hr class="border-secondary mx-3 opacity-50">
            <div class="px-4 mb-2"><small class="text-info text-uppercase fw-bold" style="font-size: 0.7rem;">Manajemen</small></div>
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item"><a href="?page=mahasiswa" class="<?= $page == 'mahasiswa' ? 'active' : '' ?>"><i class="fas fa-user-graduate me-3 text-center" style="width: 20px;"></i> Data Mahasiswa</a></li>
                <li class="nav-item"><a href="?page=dosen" class="<?= $page == 'dosen' ? 'active' : '' ?>"><i class="fas fa-chalkboard-teacher me-3 text-center" style="width: 20px;"></i> Data Dosen</a></li>
                
                <?php if($roleUser === 'admin'): ?>
                    <?php if(isset($_SESSION['admin_level']) && $_SESSION['admin_level'] === 'superadmin'): ?>
                        <div class="px-4 mt-2 mb-1"><small class="text-danger fw-bold" style="font-size: 0.6rem;">SUPER ACCESS</small></div>
                        <li class="nav-item"><a href="?page=user_admin" class="<?= $page == 'user_admin' ? 'active' : '' ?>"><i class="fas fa-user-shield me-3 text-center" style="width: 20px;"></i> Kelola Admin</a></li>
                        <li class="nav-item"><a href="?page=master" class="<?= $page == 'master' ? 'active' : '' ?>"><i class="fas fa-database me-3 text-center" style="width: 20px;"></i> Master Data</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <?php endif; ?>
            
            <div class="mt-auto px-3 pt-3 pb-3">
                <small class="text-muted d-block text-center" style="font-size: 0.65rem;">&copy; 2025 SPARTA v1.0</small>
            </div>
        </div>

        <div class="w-100 bg-light" style="height: 100vh; overflow-y: auto;">
            <nav class="navbar navbar-expand-lg navbar-white bg-white shadow-sm px-4 py-2 sticky-top">
                <div class="container-fluid">
                    <h5 class="mb-0 text-secondary" style="font-family: 'Roboto Slab', serif;">
                        <span class="text-danger fw-bold">&Lambda;</span> SPARTA
                    </h5>
                    
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none user-dropdown p-2" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="me-3 text-end lh-1 d-none d-md-block">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($namaUser) ?></div>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <?php 
                                    if ($roleUser === 'admin') {
                                        $level = $_SESSION['admin_level'] ?? 'admin'; 
                                        echo ($level === 'superadmin') ? '<span class="text-danger fw-bold">Super Admin</span>' : 'Administrator';
                                    } elseif ($roleUser === 'dosen') {
                                        echo "Dosen Pembimbing";
                                    } else {
                                        echo "Mahasiswa";
                                    }
                                    ?>
                                </small>
                            </div>
                            
                            <?php if(!empty($userFoto) && file_exists($userFoto)): ?>
                                <img src="<?= $userFoto ?>" class="rounded-circle shadow-sm border border-2 border-white" style="width: 40px; height: 40px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; font-size: 1.2rem;">
                                    <?= strtoupper(substr($namaUser, 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                        </a>
                        
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="dropdownUser1" style="min-width: 200px;">
                            <li class="px-3 py-2 d-md-none border-bottom mb-2">
                                <div class="fw-bold"><?= htmlspecialchars($namaUser) ?></div>
                                <small class="text-muted"><?= ucfirst($roleUser) ?></small>
                            </li>
                            
                            <?php if($roleUser === 'mahasiswa'): ?>
                                <li><a class="dropdown-item py-2" href="?page=profile&id=<?= $_SESSION['user_id'] ?>"><i class="fas fa-user me-2 text-secondary" style="width: 20px;"></i> Lihat Profil</a></li>
                                <li><a class="dropdown-item py-2" href="?page=edit_profile"><i class="fas fa-user-edit me-2 text-secondary" style="width: 20px;"></i> Edit Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            
                            <?php if($roleUser === 'admin'): ?>
                                <li><a class="dropdown-item py-2" href="?page=user_admin"><i class="fas fa-cog me-2 text-secondary" style="width: 20px;"></i> Pengaturan</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>

                            <li><a class="dropdown-item py-2 text-danger fw-bold" href="logout.php"><i class="fas fa-sign-out-alt me-2" style="width: 20px;"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <div class="content">
                <?php
                if (file_exists("pages/admin/$page.php")) {
                    if ($roleUser !== 'admin') {
                        if (file_exists('error.php')) { header("Location: error.php?code=403"); }
                        else { echo "Akses Ditolak!"; }
                        exit;
                    }
                    include "pages/admin/$page.php";
                } 
                elseif (file_exists("pages/$page.php")) {
                    include "pages/$page.php";
                } 
                else {
                    if (file_exists('error.php')) { header("Location: error.php?code=404"); }
                    else { echo "<div class='alert alert-danger'>Halaman tidak ditemukan!</div>"; }
                }
                ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>