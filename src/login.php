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

// --- LOGIKA LOGIN MANUAL ---
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
                            $_SESSION['dosen_is_admin'] = $user['Is_Admin']; 
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
                    
                    // 2. CEK STATUS VERIFIKASI EMAIL
                    if (isset($user['Is_Verified']) && $user['Is_Verified'] == 0) {
                        sleep(1);
                        $error = "Akun belum diverifikasi! Silakan cek email Anda.";
                    } else {
                        // Lolos Login
                        session_regenerate_id(true); 
                        $_SESSION['role'] = $roleName;
                        
                        // [UPDATE PENTING] Simpan status Need_Reset ke session
                        // Jika kolom Need_Reset belum ada (misal di Admin), default ke 0 (False)
                        $_SESSION['need_reset'] = $user['Need_Reset'] ?? 0;

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
    <title>Login - SPARTA UNNES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0d6efd, #0dcaf0);
        }
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; height: 100vh; overflow: hidden; }
        .login-container { height: 100vh; }
        
        /* Left Side - Brand */
        .brand-section {
            background: var(--primary-gradient);
            position: relative;
            color: white; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            padding: 4rem;
            overflow: hidden;
        }
        .brand-section::before {
            content: '';
            position: absolute;
            top: -50px; right: -50px;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .brand-section::after {
            content: '';
            position: absolute;
            bottom: -50px; left: -50px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .brand-logo { font-family: 'Roboto Slab', serif; font-size: 5rem; line-height: 1; margin-bottom: 1rem; position: relative; z-index: 2; }
        .brand-text { position: relative; z-index: 2; }
        
        /* Right Side - Form */
        .form-section { background: white; display: flex; align-items: center; justify-content: center; padding: 2rem; position: relative; }
        .login-form-wrapper { width: 100%; max-width: 400px; position: relative; z-index: 5; }
        
        /* Components */
        .btn-gradient {
            background: var(--primary-gradient);
            border: none; color: white; padding: 12px; font-weight: 600; transition: all 0.3s;
        }
        .btn-gradient:hover { background: linear-gradient(135deg, #0b5ed7, #0aa2c0); transform: translateY(-2px); color: white; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3); }
        
        .btn-google {
            background-color: #fff; border: 1px solid #dee2e6; color: #495057; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: all 0.2s;
        }
        .btn-google:hover { background-color: #f8f9fa; border-color: #cdd4da; }

        .nav-pills .nav-link { 
            color: #6c757d; border: 1px solid #e9ecef; margin: 0 4px; border-radius: 50px; 
            padding: 8px 20px; font-size: 0.85rem; font-weight: 500; transition: 0.3s;
        }
        .nav-pills .nav-link.active { 
            background: var(--primary-gradient); border-color: transparent; color: white; box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }
        
        .form-control:focus { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15); border-color: #86b7fe; }
        
        .divider { display: flex; align-items: center; text-align: center; color: #adb5bd; margin: 1.5rem 0; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #e9ecef; }
        .divider:not(:empty)::before { margin-right: 1em; }
        .divider:not(:empty)::after { margin-left: 1em; }

        @media (max-width: 768px) { .brand-section { display: none; } }
    </style>
</head>
<body>
    <div class="row g-0 login-container">
        <div class="col-md-7 col-lg-8 brand-section">
            <div class="brand-logo">&Lambda;</div>
            <div class="brand-text">
                <h1 class="display-5 fw-bold mb-3">SPARTA UNNES</h1>
                <p class="lead opacity-75 mb-0">Sistem Pencarian Tim Akademik</p>
                <small class="opacity-50 mt-2 d-block">Temukan partner lomba terbaikmu di sini.</small>
            </div>
        </div>

        <div class="col-md-5 col-lg-4 form-section">
            <div class="login-form-wrapper">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark mb-1">Selamat Datang!</h3>
                    <p class="text-muted small">Silakan masuk untuk melanjutkan.</p>
                </div>

                <?php if($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm small py-2 d-flex align-items-center mb-4">
                        <i class="fas fa-exclamation-circle me-2 fs-5"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>

                <ul class="nav nav-pills mb-4 justify-content-center" id="pills-tab">
                    <li class="nav-item"><button class="nav-link active" data-role="mahasiswa" type="button">Mahasiswa</button></li>
                    <li class="nav-item"><button class="nav-link" data-role="dosen" type="button">Dosen</button></li>
                    <li class="nav-item"><button class="nav-link" data-role="admin" type="button">Admin</button></li>
                </ul>

                <div id="google-login-area">
                    <a href="google_auth.php" class="btn btn-google w-100 py-2 rounded-3 mb-3 shadow-sm">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18" alt="G">
                        Masuk dengan Google
                    </a>
                    <div class="divider">ATAU EMAIL</div>
                </div>

                <form method="POST" id="loginForm">
                    <input type="hidden" name="role" id="selectedRole" value="mahasiswa">

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="identity" name="identity" placeholder="Email" required>
                        <label for="identity" id="labelIdentity" class="text-muted">Email Students</label>
                    </div>
                    
                    <div class="form-floating mb-4 position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password" class="text-muted">Password</label>
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-muted opacity-50" onclick="togglePass()" style="cursor: pointer;">
                            <i class="far fa-eye" id="iconPass"></i>
                        </span>
                    </div>

                    <button type="submit" class="btn btn-gradient w-100 py-3 rounded-3 shadow-sm mb-4">
                        MASUK <i class="fas fa-arrow-right ms-2"></i>
                    </button>

                    <div class="text-center" id="register-link-area">
                        <small class="text-muted">Mahasiswa Baru?</small> 
                        <a href="register.php" class="text-primary fw-bold text-decoration-none ms-1">Daftar Akun</a>
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
                // UI Toggle
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Logic Role
                const role = this.getAttribute('data-role');
                roleInput.value = role;

                // UI Changes based on Role
                if (role === 'admin') {
                    googleArea.style.display = 'none';
                    registerArea.style.display = 'none';
                    labelIdentity.textContent = 'Username Admin';
                    identityInput.placeholder = 'Username';
                } else {
                    googleArea.style.display = 'block'; 
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