<?php
// FIX: Define this constant BEFORE including config.php
define('IS_MAINTENANCE_PAGE', true);

require_once 'config.php'; 

// LOGIKA PENCEGAHAN AKSES LANGSUNG:
// Jika maintenance mode sedang MATI (false), tapi user mencoba buka halaman ini,
// maka lempar mereka kembali ke Dashboard/Login.
if ($is_maintenance === false) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - SPARTA</title>
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
        }
        .maintenance-card {
            max-width: 600px;
            width: 90%;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            position: relative;
        }
        .maintenance-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 5px;
            background: linear-gradient(90deg, #c0392b, #2c3e50);
            border-radius: 15px 15px 0 0;
        }
        .icon-wrapper {
            width: 100px;
            height: 100px;
            background: #fdf2f1;
            color: #c0392b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem auto;
            font-size: 3rem;
            animation: pulse 2s infinite;
        }
        .sparta-logo {
            font-family: 'Roboto Slab', serif;
            color: #2c3e50;
            font-weight: bold;
            letter-spacing: 2px;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(192, 57, 43, 0.4); }
            70% { box-shadow: 0 0 0 20px rgba(192, 57, 43, 0); }
            100% { box-shadow: 0 0 0 0 rgba(192, 57, 43, 0); }
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="icon-wrapper">
            <i class="fas fa-tools"></i>
        </div>
        
        <h2 class="fw-bold mb-2">Sistem Sedang Dalam Perbaikan</h2>
        <p class="text-muted mb-4">Kami sedang meningkatkan performa SPARTA.<br> Sistem dikunci sementara untuk umum.</p>
        
        <div class="text-center mb-5">
            <div class="d-inline-flex align-items-center justify-content-center bg-light border rounded-pill px-4 py-2 mb-3">
                <span class="spinner-border spinner-border-sm text-secondary me-2" role="status"></span>
                <span class="fw-bold text-secondary small">MAINTENANCE IN PROGRESS</span>
            </div>
            
            <p class="text-muted mb-0 mx-auto" style="max-width: 80%;">
                Akses publik ditutup sementara untuk optimasi database.
            </p>
        </div>
        
        <div class="mt-4 pt-3 border-top d-flex justify-content-center gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4">Refresh</a>
            <a href="login.php" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold"><i class="fas fa-lock me-2"></i>Login Admin</a>
        </div>
    </div>
</body>
</html>