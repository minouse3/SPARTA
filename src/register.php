<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_pass = trim($_POST['confirm_password']);

    if (empty($nama) || empty($email) || empty($password)) {
        $message = "<div class='alert alert-danger'>Semua kolom wajib diisi!</div>";
    } elseif ($password !== $confirm_pass) {
        $message = "<div class='alert alert-danger'>Konfirmasi password tidak cocok!</div>";
    } elseif (!str_ends_with($email, '@students.unnes.ac.id')) {
        $message = "<div class='alert alert-danger'>Wajib gunakan email <b>@students.unnes.ac.id</b></div>";
    } else {
        try {
            // Cek Email Saja (NIM belum ada)
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Mahasiswa WHERE Email = ?");
            $cek->execute([$email]);
            
            if ($cek->fetchColumn() > 0) {
                $message = "<div class='alert alert-danger'>Email sudah terdaftar!</div>";
            } else {
                $token = bin2hex(random_bytes(32)); 
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert TANPA NIM & Prodi
                $sql = "INSERT INTO Mahasiswa (Nama_Mahasiswa, Email, Password_Hash, Is_Verified, Verification_Token) 
                        VALUES (?, ?, ?, 0, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nama, $email, $hash, $token]);

                // Simulasi Link Verifikasi
                $verifyLink = "http://localhost:8080/verify.php?email=" . urlencode($email) . "&token=" . $token . "&role=mahasiswa";

                $message = "<div class='alert alert-success'>
                                <b>Registrasi Berhasil!</b><br>
                                Cek email untuk verifikasi.<br><br>
                                <div class='p-2 bg-white border rounded text-break text-start small'>
                                    <span class='text-muted fw-bold'>[SIMULASI]:</span> <a href='$verifyLink'>$verifyLink</a>
                                </div>
                            </div>";
            }
        } catch (PDOException $e) {
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
    <title>Daftar Cepat - SPARTA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }</style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="card border-0 shadow-sm" style="max-width: 450px; width: 100%;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary">Buat Akun Baru</h3>
                    <p class="text-muted small">Cukup isi data dasar untuk memulai.</p>
                </div>
                
                <?= $message ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama Anda" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email UNNES</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@students.unnes.ac.id" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Konfirmasi</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold mb-3">Daftar Sekarang</button>
                    <div class="text-center">
                        <small>Sudah punya akun? <a href="login.php" class="text-decoration-none">Masuk</a></small>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>