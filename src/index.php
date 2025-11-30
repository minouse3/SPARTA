<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$namaUser = $_SESSION['nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPARTA - Sistem Pencarian Tim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@700&display=swap" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; transition: all 0.3s; }
        .sparta-logo { font-family: 'Roboto Slab', serif; font-size: 2.5rem; line-height: 1; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; transition: 0.2s; border-radius: 5px; margin: 0 10px; white-space: nowrap; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: #fff; transform: translateX(5px); }
        .sidebar a.active { background-color: #c0392b; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        .content { padding: 30px; animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        /* Utility Classes */
        .hover-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: all 0.2s; }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column flex-shrink-0 py-3" style="width: 280px;">
            <a href="/" class="d-flex align-items-center mb-4 text-white text-decoration-none px-4">
                <span class="sparta-logo text-warning me-3">&Lambda;</span>
                <div class="d-flex flex-column">
                    <span class="fs-4 fw-bold" style="font-family: 'Roboto Slab', serif; letter-spacing: 2px;">SPARTA</span>
                    <small style="font-size: 0.6rem; color: #bdc3c7; letter-spacing: 1px;">TEAM MATCHING SYSTEM</small>
                </div>
            </a>
            
            <div class="px-4 mb-4"><hr class="border-secondary mt-0 mb-3"><small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Main Menu</small></div>

            <ul class="nav nav-pills flex-column mb-auto gap-1">
                <li class="nav-item"><a href="?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home me-3 text-center" style="width: 25px;"></i> Dashboard</a></li>
                <li class="nav-item"><a href="?page=ranking" class="<?= $page == 'ranking' || $page == 'profile' ? 'active' : '' ?>"><i class="fas fa-trophy me-3 text-center" style="width: 25px;"></i> Leaderboard</a></li>
                <li class="nav-item"><a href="?page=lomba" class="<?= $page == 'lomba' || $page == 'lomba_detail' ? 'active' : '' ?>"><i class="fas fa-scroll me-3 text-center" style="width: 25px;"></i> Data Lomba</a></li>
                <li class="nav-item"><a href="?page=tim" class="<?= $page == 'tim' ? 'active' : '' ?>"><i class="fas fa-users me-3 text-center" style="width: 25px;"></i> Data Tim</a></li>
                <li class="nav-item"><a href="?page=mahasiswa" class="<?= $page == 'mahasiswa' ? 'active' : '' ?>"><i class="fas fa-user-shield me-3 text-center" style="width: 25px;"></i> Data Mahasiswa</a></li>
                <li class="nav-item"><a href="?page=dosen" class="<?= $page == 'dosen' ? 'active' : '' ?>"><i class="fas fa-chalkboard-teacher me-3 text-center" style="width: 25px;"></i> Data Dosen</a></li>
            </ul>

            <div class="px-4 mt-4 mb-2"><small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Admin</small></div>
            <ul class="nav nav-pills flex-column gap-1">
                 <li class="nav-item"><a href="?page=master" class="<?= $page == 'master' ? 'active' : '' ?>"><i class="fas fa-cogs me-3 text-center" style="width: 25px;"></i> Master Data</a></li>
            </ul>

            <div class="mt-auto px-3">
                <hr class="border-secondary">
                <a href="logout.php" class="text-danger bg-dark bg-opacity-25 mb-3"><i class="fas fa-sign-out-alt me-3 text-center" style="width: 25px;"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="w-100 bg-light" style="height: 100vh; overflow-y: auto;">
            <nav class="navbar navbar-expand-lg navbar-white bg-white shadow-sm px-4 py-3 sticky-top">
                <div class="container-fluid">
                    <h5 class="mb-0 text-secondary" style="font-family: 'Roboto Slab', serif;"><span class="text-danger fw-bold">&Lambda;</span> Sistem Pencarian Tim Kompetisi</h5>
                    <div class="d-flex align-items-center">
                        <div class="me-3 text-end"><div class="fw-bold text-dark"><?= htmlspecialchars($namaUser) ?></div><small class="text-muted">Administrator</small></div>
                        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;"><i class="fas fa-user"></i></div>
                    </div>
                </div>
            </nav>
            <div class="content">
                <?php
                $filename = "pages/$page.php";
                if (file_exists($filename)) {
                    include $filename;
                } else {
                    echo "<div class='alert alert-danger shadow-sm'>Halaman tidak ditemukan!</div>";
                }
                ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>