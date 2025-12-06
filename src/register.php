<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = "";

// Ambil Data Prodi
try {
    $stmtProdi = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC");
    $prodiList = $stmtProdi->fetchAll();
} catch (Exception $e) {
    $prodiList = [];
}

// Proses Register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim']);
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_pass = trim($_POST['confirm_password']);
    $id_prodi = $_POST['prodi'];

    if (empty($nim) || empty($nama) || empty($email) || empty($password) || empty($id_prodi)) {
        $message = "<div class='alert alert-danger'>Semua kolom wajib diisi!</div>";
    } elseif ($password !== $confirm_pass) {
        $message = "<div class='alert alert-danger'>Konfirmasi password tidak cocok!</div>";
    } 
    // --- VALIDASI DOMAIN UNNES (Tetap ada agar aman) ---
    elseif (!str_ends_with($email, '@students.unnes.ac.id')) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle me-2'></i>Registrasi khusus mahasiswa UNNES! Wajib gunakan email <b>@students.unnes.ac.id</b></div>";
    } 
    // ---------------------------------------------------
    else {
        try {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Mahasiswa WHERE NIM = ? OR Email = ?");
            $cek->execute([$nim, $email]);
            
            if ($cek->fetchColumn() > 0) {
                $message = "<div class='alert alert-danger'>NIM atau Email sudah terdaftar!</div>";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO Mahasiswa (NIM, Nama_Mahasiswa, Email, Password_Hash, ID_Prodi) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nim, $nama, $email, $hash, $id_prodi]);

                $message = "<div class='alert alert-success'>Registrasi Berhasil! Silakan <a href='login.php' class='alert-link'>Login disini</a>.</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Error Database: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun UNNES - SPARTA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
        .register-card { max-width: 500px; margin: 50px auto; border: none; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #f39c12 0%, #d35400 100%); color: white; text-align: center; padding: 30px 20px; }
        .sparta-logo { font-family: 'Roboto Slab', serif; font-size: 2.5rem; }
        .btn-sparta { background-color: #d35400; color: white; font-weight: bold; }
        .btn-sparta:hover { background-color: #e67e22; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card register-card">
            <div class="card-header">
                <div class="sparta-logo">&Lambda;</div>
                <h4 class="mb-0 fw-bold">Student Registration</h4>
                <small class="opacity-75">Gunakan akun @students.unnes.ac.id</small>
            </div>
            <div class="card-body p-4">
                <?= $message ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NIM</label>
                        <input type="text" name="nim" class="form-control" placeholder="Nomor Induk Mahasiswa" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama sesuai KTM" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email UNNES</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@students.unnes.ac.id" required>
                        <div class="form-text small text-danger">*Wajib menggunakan email institusi</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Program Studi</label>
                        <select name="prodi" class="form-select" required>
                            <option value="">-- Pilih Prodi --</option>
                            <?php foreach($prodiList as $p): ?>
                                <option value="<?= $p['ID_Prodi'] ?>"><?= $p['Nama_Prodi'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label small fw-bold">Konfirmasi</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-sparta w-100 py-2 rounded-3 mb-3">DAFTAR SEKARANG</button>
                    
                    <div class="text-center">
                        <small>Sudah punya akun? <a href="login.php" class="text-danger fw-bold text-decoration-none">Login disini</a></small>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>