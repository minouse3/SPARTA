<?php
// Ambil kode error dari URL, default ke 404 jika tidak ada
$code = isset($_GET['code']) ? intval($_GET['code']) : 404;

// Definisi Pesan Error Modern
$errors = [
    400 => [
        'title' => 'Permintaan Tidak Valid',
        'desc'  => 'Server bingung dengan permintaan Anda. Mungkin ada kesalahan ketik atau link rusak.',
        'icon'  => 'fas fa-exclamation-circle',
        'color' => 'text-warning'
    ],
    401 => [
        'title' => 'Butuh Autentikasi',
        'desc'  => 'Anda harus login terlebih dahulu untuk masuk ke area ini.',
        'icon'  => 'fas fa-user-lock',
        'color' => 'text-primary',
        'btn'   => ['link' => 'login.php', 'text' => 'Login Sekarang']
    ],
    403 => [
        'title' => 'Akses Ditolak',
        'desc'  => 'Maaf, Anda tidak memiliki izin untuk melihat halaman ini. Ini area terlarang.',
        'icon'  => 'fas fa-hand-paper',
        'color' => 'text-danger'
    ],
    404 => [
        'title' => 'Halaman Tidak Ditemukan',
        'desc'  => 'Oops! Halaman yang Anda cari mungkin telah dihapus, dipindahkan, atau tidak pernah ada.',
        'icon'  => 'fas fa-search-location',
        'color' => 'text-info'
    ],
    410 => [
        'title' => 'Konten Telah Dihapus',
        'desc'  => 'Halaman ini sudah pensiun dan tidak tersedia lagi secara permanen.',
        'icon'  => 'fas fa-trash-alt',
        'color' => 'text-secondary'
    ],
    500 => [
        'title' => 'Kesalahan Internal Server',
        'desc'  => 'Ada masalah di dapur kami (server). Tim teknis sedang memperbaikinya.',
        'icon'  => 'fas fa-cogs',
        'color' => 'text-danger'
    ]
];

// Fallback
if (!array_key_exists($code, $errors)) {
    $code = 'Error';
    $currentError = [
        'title' => 'Terjadi Kesalahan',
        'desc'  => 'Terjadi kesalahan yang tidak diketahui.',
        'icon'  => 'fas fa-bug',
        'color' => 'text-dark'
    ];
} else {
    $currentError = $errors[$code];
}

http_response_code($code == 'Error' ? 500 : $code);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $code ?> - SPARTA</title>
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

        .error-card {
            max-width: 500px;
            width: 90%;
            text-align: center;
            padding: 3rem 2rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.5);
            animation: popIn 0.5s ease-out;
        }

        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1); }
        }

        .error-code {
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            font-family: 'Roboto Slab', serif;
            margin-bottom: 0;
            position: relative;
            display: inline-block;
        }
        
        /* Gradient Text for Error Code */
        .text-gradient {
            background: linear-gradient(135deg, #212529, #6c757d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .icon-container {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .btn-home {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            color: white;
            border: none;
            padding: 10px 30px;
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

    <div class="error-card">
        <div class="icon-container <?= $currentError['color'] ?>">
            <i class="<?= $currentError['icon'] ?>"></i>
        </div>

        <div class="error-code text-gradient"><?= $code ?></div>
        
        <h2 class="fw-bold text-dark mt-2 mb-3"><?= $currentError['title'] ?></h2>
        <p class="text-muted mb-4 fs-6 px-4"><?= $currentError['desc'] ?></p>

        <div class="d-flex justify-content-center gap-3">
            <a href="javascript:history.back()" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            
            <?php if(isset($currentError['btn'])): ?>
                <a href="<?= $currentError['btn']['link'] ?>" class="btn btn-home rounded-pill fw-bold shadow-sm">
                    <?= $currentError['btn']['text'] ?> <i class="fas fa-arrow-right ms-2"></i>
                </a>
            <?php else: ?>
                <a href="index.php" class="btn btn-home rounded-pill fw-bold shadow-sm">
                    Ke Dashboard <i class="fas fa-home ms-2"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>