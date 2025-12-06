<?php
require_once 'config.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$role  = $_GET['role'] ?? 'mahasiswa'; // Default cek mahasiswa

$message = '';
$messageType = ''; // success / danger

if (empty($email) || empty($token)) {
    $message = "Link verifikasi tidak valid.";
    $messageType = "danger";
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
                $message = "Akun sudah terverifikasi sebelumnya. Silakan login.";
                $messageType = "info";
            } else {
                $update = $pdo->prepare("UPDATE $table SET Is_Verified = 1, Verification_Token = NULL WHERE $idCol = ?");
                $update->execute([$user[$idCol]]);
                
                $message = "Selamat! Akun Anda berhasil diverifikasi. Silakan login.";
                $messageType = "success";
            }
        } else {
            $message = "Token verifikasi salah atau sudah kadaluarsa.";
            $messageType = "danger";
        }

    } catch (Exception $e) {
        $message = "Terjadi kesalahan: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun - SPARTA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow-sm p-4 text-center" style="max-width: 400px; width: 90%;">
        <div class="mb-3">
            <?php if($messageType == 'success'): ?>
                <h1 class="display-1 text-success">✔</h1>
            <?php else: ?>
                <h1 class="display-1 text-danger">✖</h1>
            <?php endif; ?>
        </div>
        <h4 class="mb-3">Status Verifikasi</h4>
        
        <div class="alert alert-<?= $messageType ?>">
            <?= $message ?>
        </div>

        <a href="login.php" class="btn btn-primary w-100 mt-3">Ke Halaman Login</a>
    </div>
</body>
</html>