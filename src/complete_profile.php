<?php
// FILE: Halaman Wajib Isi Biodata (NIM & Prodi)
define('IS_COMPLETE_PROFILE_PAGE', true); // Penanda agar tidak loop di config
session_start();
require_once 'config.php';

// Hanya untuk Mahasiswa yang sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: login.php");
    exit;
}

$idMhs = $_SESSION['user_id'];
$message = "";

// Ambil Data Prodi
$prodiList = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();

// PROSES SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim']);
    $prodi = $_POST['prodi'];

    if (empty($nim) || empty($prodi)) {
        $message = "<div class='alert alert-danger'>NIM dan Prodi wajib diisi!</div>";
    } else {
        try {
            // Cek Unik NIM (karena sekarang baru diinput)
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Mahasiswa WHERE NIM = ? AND ID_Mahasiswa != ?");
            $cek->execute([$nim, $idMhs]);
            
            if ($cek->fetchColumn() > 0) {
                $message = "<div class='alert alert-danger'>NIM sudah digunakan akun lain!</div>";
            } else {
                // Update Data
                $stmt = $pdo->prepare("UPDATE Mahasiswa SET NIM = ?, ID_Prodi = ? WHERE ID_Mahasiswa = ?");
                $stmt->execute([$nim, $prodi, $idMhs]);
                
                // Redirect ke Dashboard setelah sukses
                header("Location: index.php");
                exit;
            }
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lengkapi Profil - SPARTA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-color: #eef2f5; font-family: 'Inter', sans-serif; }</style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="card shadow-lg border-0" style="max-width: 500px; width: 100%;">
            <div class="card-header bg-warning text-dark text-center py-3">
                <h5 class="mb-0 fw-bold">👋 Selamat Datang!</h5>
                <small>Satu langkah lagi untuk masuk ke SPARTA.</small>
            </div>
            <div class="card-body p-4">
                <?= $message ?>
                <div class="alert alert-info small mb-4">
                    <i class="fas fa-info-circle me-1"></i>
                    Silakan lengkapi <b>NIM</b> dan <b>Program Studi</b> Anda agar dapat menggunakan fitur pencarian tim dan mengikuti lomba.
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nomor Induk Mahasiswa (NIM)</label>
                        <input type="text" name="nim" class="form-control form-control-lg" placeholder="Contoh: A11.2023.12345" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Program Studi</label>
                        <select name="prodi" class="form-select form-select-lg" required>
                            <option value="">-- Pilih Prodi --</option>
                            <?php foreach($prodiList as $p): ?>
                                <option value="<?= $p['ID_Prodi'] ?>"><?= $p['Nama_Prodi'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 py-2 fw-bold">Simpan & Lanjutkan &rarr;</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>