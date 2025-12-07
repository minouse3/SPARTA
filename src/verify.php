<?php
require_once 'config.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$role  = $_GET['role'] ?? 'mahasiswa'; // Default cek mahasiswa

$message = '';
$status = ''; // success, info, danger
$icon = '';
$title = '';

if (empty($email) || empty($token)) {
    $title = "Link Invalid";
    $message = "Link verifikasi tidak lengkap atau rusak.";
    $status = "danger";
    $icon = "fas fa-link";
} else {
    try {
        // Tentukan Tabel berdasarkan Role
        $table = ($role === 'dosen') ? 'Dosen_Pembimbing' : 'Mahasiswa';
        $idCol = ($role === 'dosen') ? 'ID_Dosen' : 'ID_Mahasiswa';

        // 1. Cek Token di Database
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE Email = ? AND Verification_Token = ?");
        $stmt->execute([$email, $token]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. Jika Cocok, Aktifkan Akun
            if ($user['Is_Verified'] == 1) {
                $title = "Akun Sudah Aktif";
                $message = "Akun ini sudah terverifikasi sebelumnya. Anda bisa langsung login.";
                $status = "info";
                $icon = "fas fa-info-circle";
            } else {
                $update = $pdo->prepare("UPDATE $table SET Is_Verified = 1, Verification_Token = NULL WHERE $idCol = ?");
                $update->execute([$user[$idCol]]);
                
                $title = "Verifikasi Berhasil!";
                $message = "Selamat! Akun Anda kini sudah aktif. Silakan masuk untuk memulai.";
                $status = "success";
                $icon = "fas fa-check-circle";
            }
        } else {
            $title = "Gagal Verifikasi";
            $message = "Token verifikasi salah atau sudah kadaluarsa.";
            $status = "danger";
            $icon = "fas fa-times-circle";
        }

    } catch (Exception $e) {
        $title = "System Error";
        $message = "Terjadi kesalahan: " . $e->getMessage();
        $status = "danger";
        $icon = "fas fa-bug";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Verifikasi - SPARTA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            position: relative;
        }
        /* Background Decoration */
        .bg-deco {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
        }
        .bg-deco-1 { width: 300px; height: 300px; background: #0d6efd; top: -10%; left: -10%; opacity: 0.1; }
        .bg-deco-2 { width: 400px; height: 400px; background: #0dcaf0; bottom: -10%; right: -10%; opacity: 0.1; }

        .verify-card {
            max-width: 450px;
            width: 90%;
            text-align: center;
            padding: 3rem 2rem;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.6);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .icon-wrapper {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .text-success { color: #198754 !important; }
        .text-danger { color: #dc3545 !important; }
        .text-info { color: #0dcaf0 !important; }
        
        .btn-home {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            color: white;
            border: none;
            padding: 12px 40px;
            transition: transform 0.2s;
        }
        .btn-home:hover {
            transform: translateY(-3px);
            color: white;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
    </style>
</head>
<body>
    
    <div class="bg-deco bg-deco-1"></div>
    <div class="bg-deco bg-deco-2"></div>

    <div class="verify-card">
        <div class="icon-wrapper text-<?= $status ?>">
            <i class="<?= $icon ?>"></i>
        </div>
        
        <h2 class="fw-bold text-dark mb-3" style="font-family: 'Roboto Slab', serif;"><?= $title ?></h2>
        
        <p class="text-muted mb-5 fs-6 px-3">
            <?= $message ?>
        </p>

        <a href="login.php" class="btn btn-home rounded-pill fw-bold shadow-sm">
            <i class="fas fa-sign-in-alt me-2"></i> Ke Halaman Login
        </a>
    </div>

</body>
</html>