<?php
define('IS_LOGIN_PAGE', true);
session_start();
require_once 'config.php';

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

// --- LOGIKA LOGIN MANUAL (TETAP ADA SEBAGAI CADANGAN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity']);
    $password = trim($_POST['password']);
    $role     = trim($_POST['role']); 

    if (empty($identity) || empty($password)) {
        $error = "Silakan isi email dan password.";
    } else {
        try {
            $user = null;
            $roleName = '';

            switch ($role) {
                case 'admin':
                    $stmt = $pdo->prepare("SELECT * FROM Admin WHERE Username = ?");
                    $stmt->execute([$identity]);
                    $user = $stmt->fetch();
                    if ($user) {
                        $roleName = 'admin'; 
                        $_SESSION['user_id'] = $user['ID_Admin'];
                        $_SESSION['nama'] = $user['Nama_Lengkap'];
                        // BARU: Simpan Level Admin (superadmin / admin)
                        $_SESSION['admin_level'] = $user['Level']; 
                    }
                    break;

                case 'dosen':
                    if (!str_ends_with($identity, '@mail.unnes.ac.id')) {
                        $error = "Dosen wajib menggunakan email @mail.unnes.ac.id";
                    } else {
                        $stmt = $pdo->prepare("SELECT * FROM Dosen_Pembimbing WHERE Email = ?");
                        $stmt->execute([$identity]);
                        $user = $stmt->fetch();
                        if ($user) {
                            $roleName = 'dosen';
                            $_SESSION['user_id'] = $user['ID_Dosen'];
                            $_SESSION['nama'] = $user['Nama_Dosen'];
                        }
                    }
                    break;

                case 'mahasiswa':
                default:
                    if (!str_ends_with($identity, '@students.unnes.ac.id')) {
                        $error = "Mahasiswa wajib menggunakan email @students.unnes.ac.id";
                    } else {
                        $stmt = $pdo->prepare("SELECT * FROM Mahasiswa WHERE Email = ?");
                        $stmt->execute([$identity]);
                        $user = $stmt->fetch();
                        if ($user) {
                            $roleName = 'mahasiswa';
                            $_SESSION['user_id'] = $user['ID_Mahasiswa'];
                            $_SESSION['nama'] = $user['Nama_Mahasiswa'];
                        }
                    }
                    break;
            }

            if ($user && empty($error)) {
                // 1. Validasi Password
                if (isset($user['Password_Hash']) && password_verify($password, $user['Password_Hash'])) {
                    
                    // 2. CEK STATUS VERIFIKASI EMAIL (Fitur Baru)
                    // Admin biasanya auto-verified (karena insert manual), jadi cek key Is_Verified dulu
                    if (isset($user['Is_Verified']) && $user['Is_Verified'] == 0) {
                        // Cegah Login
                        sleep(1);
                        $error = "Akun belum diverifikasi! Silakan cek email Anda.";
                    } else {
                        // Lolos Login
                        session_regenerate_id(true); 
                        $_SESSION['role'] = $roleName;
                        header("Location: index.php");
                        exit;
                    }

                } else {
                    sleep(1); 
                    $error = "Password salah.";
                }
            } elseif (empty($error)) {
                sleep(1);
                $error = "Email tidak ditemukan / belum terdaftar.";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login UNNES - SPARTA System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; height: 100vh; overflow: hidden; }
        .login-container { height: 100vh; }
        .brand-section {
            background: linear-gradient(135deg, #f39c12 0%, #d35400 100%);
            color: white; display: flex; flex-direction: column; justify-content: center; padding: 4rem;
        }
        .brand-logo { font-family: 'Roboto Slab', serif; font-size: 5rem; line-height: 1; margin-bottom: 1rem; }
        .form-section { background: white; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .login-form-wrapper { width: 100%; max-width: 420px; }
        
        /* Google Button Style */
        .btn-google {
            background-color: #fff; border: 1px solid #ddd; color: #333; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: all 0.2s;
        }
        .btn-google:hover { background-color: #f8f9fa; transform: translateY(-1px); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-sparta { background-color: #d35400; color: white; padding: 12px; font-weight: 600; transition: all 0.3s; }
        .btn-sparta:hover { background-color: #e67e22; color: white; transform: translateY(-2px); }
        
        .nav-pills .nav-link { color: #6c757d; border: 1px solid #dee2e6; margin-right: 5px; border-radius: 50px; padding: 8px 20px; font-size: 0.85rem; }
        .nav-pills .nav-link.active { background-color: #d35400; color: white; border-color: #d35400; font-weight: bold; }
        .divider { display: flex; align-items: center; text-align: center; color: #aaa; margin: 1.5rem 0; font-size: 0.8rem; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #eee; }
        .divider:not(:empty)::before { margin-right: .5em; }
        .divider:not(:empty)::after { margin-left: .5em; }

        @media (max-width: 768px) { .brand-section { display: none; } }
    </style>
</head>
<body>
    <div class="row g-0 login-container">
        <div class="col-md-7 col-lg-8 brand-section">
            <div class="brand-logo text-white">&Lambda;</div>
            <h1 class="display-4 fw-bold mb-3">SPARTA UNNES</h1>
            <p class="lead opacity-75">Sistem Pencarian Tim Akademik</p>
        </div>

        <div class="col-md-5 col-lg-4 form-section shadow-lg">
            <div class="login-form-wrapper">
                <h3 class="fw-bold mb-1">Masuk Sistem</h3>
                <p class="text-muted mb-4">Pilih metode masuk yang Anda inginkan</p>

                <?php if($error): ?>
                    <div class="alert alert-danger small py-2"><i class="fas fa-exclamation-triangle me-2"></i><?= $error ?></div>
                <?php endif; ?>

                <ul class="nav nav-pills mb-4 justify-content-center" id="pills-tab">
                    <li class="nav-item"><button class="nav-link active" data-role="mahasiswa" type="button">Mahasiswa</button></li>
                    <li class="nav-item"><button class="nav-link" data-role="dosen" type="button">Dosen</button></li>
                    <li class="nav-item"><button class="nav-link" data-role="admin" type="button">Admin</button></li>
                </ul>

                <div id="google-login-area">
                    <a href="google_auth.php" class="btn btn-google w-100 py-3 rounded-3 mb-3">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="20" alt="G">
                        Masuk dengan Google UNNES
                    </a>
                    <div class="divider">ATAU GUNAKAN PASSWORD</div>
                </div>

                <form method="POST" id="loginForm">
                    <input type="hidden" name="role" id="selectedRole" value="mahasiswa">

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="identity" name="identity" placeholder="Email" required>
                        <label for="identity" id="labelIdentity">Email Mahasiswa</label>
                    </div>
                    
                    <div class="form-floating mb-4 position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-muted" onclick="togglePass()">
                            <i class="far fa-eye" id="iconPass"></i>
                        </span>
                    </div>

                    <button type="submit" class="btn btn-sparta w-100 py-3 rounded-3 shadow-sm mb-3">
                        MASUK <i class="fas fa-arrow-right ms-2"></i>
                    </button>

                    <div class="text-center" id="register-link-area">
                        <small class="text-muted">Mahasiswa Baru?</small> 
                        <a href="register.php" class="text-danger fw-bold text-decoration-none ms-1">Daftar Akun UNNES</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const tabs = document.querySelectorAll('.nav-link');
        const roleInput = document.getElementById('selectedRole');
        const labelIdentity = document.getElementById('labelIdentity');
        const identityInput = document.getElementById('identity');
        const googleArea = document.getElementById('google-login-area');
        const registerArea = document.getElementById('register-link-area');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const role = this.getAttribute('data-role');
                roleInput.value = role;

                // LOGIKA UI: Tampilkan/Sembunyikan Tombol Google
                if (role === 'admin') {
                    googleArea.style.display = 'none'; // Admin tidak pakai Google
                    registerArea.style.display = 'none'; // Admin tidak bisa daftar sendiri
                    labelIdentity.textContent = 'Username Admin';
                    identityInput.placeholder = 'Username';
                } else {
                    googleArea.style.display = 'block'; // Mhs & Dosen pakai Google
                    registerArea.style.display = (role === 'mahasiswa') ? 'block' : 'none';
                    
                    if(role === 'mahasiswa') {
                        labelIdentity.textContent = 'Email Students';
                        identityInput.placeholder = 'nama@students.unnes.ac.id';
                    } else {
                        labelIdentity.textContent = 'Email Staff/Dosen';
                        identityInput.placeholder = 'nama@mail.unnes.ac.id';
                    }
                }
                identityInput.focus();
            });
        });

        function togglePass() {
            const pass = document.getElementById('password');
            const icon = document.getElementById('iconPass');
            if (pass.type === 'password') {
                pass.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pass.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>