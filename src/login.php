<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM Admin WHERE Username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password_Hash'])) {
            $_SESSION['user_id'] = $user['ID_Admin'];
            $_SESSION['nama'] = $user['Nama_Lengkap'];
            $_SESSION['role'] = 'admin';
            header("Location: index.php");
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } catch (PDOException $e) {
        $error = "Error Database: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SPARTA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #ecf0f1; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .login-header { background: #c0392b; color: white; padding: 40px 20px; text-align: center; }
        .sparta-logo { font-size: 3rem; line-height: 1; margin-bottom: 10px; display: block; }
        .btn-sparta { background-color: #c0392b; color: white; border: none; }
        .btn-sparta:hover { background-color: #e74c3c; color: white; }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <span class="sparta-logo">&Lambda;</span>
            <h3 class="mb-0 fw-bold" style="font-family: 'Roboto Slab', serif; letter-spacing: 2px;">SPARTA</h3>
            <small class="opacity-75">Sistem Pencarian Tim Akademik</small>
        </div>
        <div class="card-body p-4 bg-white">
            <?php if($error): ?>
                <div class="alert alert-danger text-center p-2 mb-3 small"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USERNAME</label>
                    <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">PASSWORD</label>
                    <input type="password" name="password" class="form-control" placeholder="password" required>
                </div>
                <button type="submit" class="btn btn-sparta w-100 py-2 fw-bold">MASUK</button>
            </form>
        </div>
        <div class="text-center py-3 bg-light">
            <small class="text-muted">&copy; 2025 SPARTA System</small>
        </div>
    </div>
</body>
</html>