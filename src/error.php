<?php
// Ambil kode error dari URL, default ke 404 jika tidak ada
$code = isset($_GET['code']) ? intval($_GET['code']) : 404;

// Definisi Pesan Error (Bisa ditambah sesuai kebutuhan)
$errors = [
    400 => [
        'title' => 'Permintaan Tidak Valid',
        'desc'  => 'Server tidak dapat memproses permintaan Anda karena kesalahan sintaks atau input.',
        'icon'  => 'fas fa-exclamation-circle'
    ],
    401 => [
        'title' => 'Butuh Autentikasi',
        'desc'  => 'Anda harus login terlebih dahulu untuk mengakses sumber daya ini.',
        'icon'  => 'fas fa-user-lock',
        'btn'   => ['link' => 'login.php', 'text' => 'Login Sekarang']
    ],
    403 => [
        'title' => 'Akses Ditolak',
        'desc'  => 'Maaf, Anda tidak memiliki izin untuk mengakses halaman atau folder ini.',
        'icon'  => 'fas fa-hand-paper'
    ],
    404 => [
        'title' => 'Halaman Tidak Ditemukan',
        'desc'  => 'Oops! Halaman yang Anda cari mungkin telah dihapus, dipindahkan, atau link-nya salah.',
        'icon'  => 'fas fa-search-location'
    ],
    410 => [
        'title' => 'Konten Telah Dihapus',
        'desc'  => 'Halaman ini sudah tidak tersedia secara permanen dan tidak akan kembali lagi.',
        'icon'  => 'fas fa-trash-alt'
    ],
    500 => [
        'title' => 'Kesalahan Internal Server',
        'desc'  => 'Terjadi masalah di sisi server kami. Silakan coba beberapa saat lagi.',
        'icon'  => 'fas fa-cogs'
    ]
];

// Fallback jika kode tidak dikenali
if (!array_key_exists($code, $errors)) {
    $code = 'Error';
    $currentError = [
        'title' => 'Terjadi Kesalahan',
        'desc'  => 'Terjadi kesalahan yang tidak diketahui.',
        'icon'  => 'fas fa-bug'
    ];
} else {
    $currentError = $errors[$code];
}

// Set Header HTTP Response
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
        }
        .error-card {
            max-width: 600px;
            width: 90%;
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            background: -webkit-linear-gradient(#c0392b, #e74c3c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-family: 'Roboto Slab', serif;
            margin-bottom: 1rem;
        }
        .icon-bg {
            position: absolute;
            top: -20px;
            right: -20px;
            font-size: 15rem;
            color: rgba(0,0,0,0.03);
            z-index: 0;
            transform: rotate(15deg);
        }
        .content { position: relative; z-index: 1; }
        .btn-home {
            background-color: #2c3e50;
            color: white;
            padding: 10px 25px;
            border-radius: 50px;
            transition: 0.3s;
        }
        .btn-home:hover { background-color: #1a252f; color: white; transform: translateY(-3px); }
    </style>
</head>
<body>
    <div class="error-card">
        <i class="<?= $currentError['icon'] ?> icon-bg"></i>

        <div class="content">
            <div class="error-code"><?= $code ?></div>
            <h2 class="fw-bold text-dark mb-3"><?= $currentError['title'] ?></h2>
            <p class="text-muted mb-4 lead fs-6"><?= $currentError['desc'] ?></p>

            <div class="d-flex justify-content-center gap-3">
                <a href="javascript:history.back()" class="btn btn-outline-secondary rounded-pill px-4">Kembali</a>
                
                <?php if(isset($currentError['btn'])): ?>
                    <a href="<?= $currentError['btn']['link'] ?>" class="btn btn-home px-4">
                        <?= $currentError['btn']['text'] ?> <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-home px-4">
                        Ke Dashboard <i class="fas fa-home ms-2"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>