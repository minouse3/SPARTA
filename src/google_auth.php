<?php
require_once 'config.php';

// Jika fitur Google Login dimatikan dari config, tolak akses!
if ($enable_google_login === true) {
    // Bisa redirect ke login
    header("Location: login.php");
    exit;
    // Atau tampilkan pesan error sederhana
    // die("Fitur Google Login sedang dinonaktifkan oleh Administrator.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Login - Development</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { max-width: 400px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="card p-4 text-center">
        <div class="mb-3">
            <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="60" alt="Google">
        </div>
        <h4 class="fw-bold">Fitur Belum Tersedia</h4>
        <p class="text-muted">
            Di mode <b>Development</b>, integrasi Google OAuth belum dikonfigurasi. Silakan gunakan Login Manual (Email & Password).
        </p>
        <a href="login.php" class="btn btn-primary w-100">Kembali ke Login</a>
    </div>
</body>
</html>