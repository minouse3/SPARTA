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
    $password = $_POST['password']; 
    $confirm_pass = $_POST['confirm_password'];

    // Validasi Dasar
    if (empty($nama) || empty($email) || empty($password)) {
        $message = "<div class='alert alert-danger border-0 shadow-sm small'><i class='fas fa-exclamation-triangle me-2'></i>Semua kolom wajib diisi!</div>";
    } 
    // Validasi Match Password
    elseif ($password !== $confirm_pass) {
        $message = "<div class='alert alert-danger border-0 shadow-sm small'><i class='fas fa-times-circle me-2'></i>Konfirmasi password tidak cocok!</div>";
    } 
    // Validasi Email UNNES
    elseif (!str_ends_with($email, '@students.unnes.ac.id')) {
        $message = "<div class='alert alert-warning border-0 shadow-sm small'><i class='fas fa-university me-2'></i>Wajib gunakan email <b>@students.unnes.ac.id</b></div>";
    } 
    // Validasi Kompleksitas Password (Server Side - Diperbarui)
    // Syarat: > 6 Karakter (Min 7), Kapital, Kecil, Angka, Simbol
    elseif (strlen($password) <= 6 || 
            !preg_match('/[A-Z]/', $password) || 
            !preg_match('/[a-z]/', $password) || 
            !preg_match('/[0-9]/', $password) || 
            !preg_match('/[^a-zA-Z0-9]/', $password)) {
        
        // Tampilan notifikasi detail DIHILANGKAN sesuai permintaan.
        // Diganti pesan umum satu baris (fallback jika JS dimatikan).
        $message = "<div class='alert alert-danger border-0 shadow-sm small'>
                        <i class='fas fa-lock me-2'></i>Password tidak memenuhi standar keamanan.
                    </div>";
    } 
    else {
        try {
            // Cek Email Saja
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Mahasiswa WHERE Email = ?");
            $cek->execute([$email]);
            
            if ($cek->fetchColumn() > 0) {
                $message = "<div class='alert alert-danger border-0 shadow-sm small'>Email sudah terdaftar!</div>";
            } else {
                $token = bin2hex(random_bytes(32)); 
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO Mahasiswa (Nama_Mahasiswa, Email, Password_Hash, Is_Verified, Verification_Token) 
                        VALUES (?, ?, ?, 0, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nama, $email, $hash, $token]);

                $verifyLink = "http://localhost:8080/verify.php?email=" . urlencode($email) . "&token=" . $token . "&role=mahasiswa";

                $message = "<div class='alert alert-success border-0 shadow-sm'>
                                <h6 class='fw-bold'><i class='fas fa-check-circle me-2'></i>Registrasi Berhasil!</h6>
                                <p class='small mb-2'>Silakan cek email Anda untuk verifikasi.</p>
                                <div class='p-2 bg-white border rounded text-break text-start small mt-2'>
                                    <span class='text-muted fw-bold'>[SIMULASI EMAIL]:</span> <a href='$verifyLink' class='text-decoration-none'>$verifyLink</a>
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
    <title>Daftar Akun - SPARTA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0d6efd, #0dcaf0);
        }
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; height: 100vh; overflow: hidden; }
        .login-container { height: 100vh; }
        
        .brand-section {
            background: linear-gradient(135deg, #0dcaf0, #0d6efd);
            position: relative;
            color: white; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            padding: 4rem;
            overflow: hidden;
        }
        .brand-section::before {
            content: ''; position: absolute; bottom: -50px; right: -50px;
            width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        .brand-logo { font-family: 'Roboto Slab', serif; font-size: 5rem; line-height: 1; margin-bottom: 1rem; position: relative; z-index: 2; }
        
        .form-section { background: white; display: flex; align-items: center; justify-content: center; padding: 2rem; overflow-y: auto; }
        .login-form-wrapper { width: 100%; max-width: 450px; padding: 1rem 0; }
        
        .btn-gradient {
            background: var(--primary-gradient);
            border: none; color: white; padding: 12px; font-weight: 600; transition: all 0.3s;
        }
        .btn-gradient:hover { background: linear-gradient(135deg, #0b5ed7, #0aa2c0); transform: translateY(-2px); color: white; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3); }
        
        .form-control:focus { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15); border-color: #86b7fe; }
        
        /* Password Strength Styles */
        .progress-bar-weak { background-color: #dc3545; }
        .progress-bar-medium { background-color: #ffc107; }
        .progress-bar-strong { background-color: #198754; }
        
        .req-item { font-size: 0.75rem; color: #6c757d; margin-right: 10px; display: inline-block; transition: color 0.3s; }
        .req-item.valid { color: #198754; font-weight: 600; }
        .req-item i { margin-right: 3px; font-size: 0.7rem; }

        @media (max-width: 768px) { 
            .brand-section { display: none; } 
            .form-section { height: 100vh; }
        }
    </style>
</head>
<body>
    <div class="row g-0 login-container">
        <div class="col-md-7 col-lg-8 brand-section">
            <div class="brand-logo">&Lambda;</div>
            <div style="position: relative; z-index: 2;">
                <h1 class="display-5 fw-bold mb-3">Bergabunglah Sekarang</h1>
                <p class="lead opacity-75 mb-0">Mulai perjalanan prestasimu bersama SPARTA.</p>
            </div>
        </div>

        <div class="col-md-5 col-lg-4 form-section">
            <div class="login-form-wrapper">
                <div class="mb-4">
                    <h3 class="fw-bold text-dark mb-1">Buat Akun Baru</h3>
                    <p class="text-muted small">Khusus Mahasiswa UNNES.</p>
                </div>

                <?= $message ?>
                
                <form method="POST" id="registerForm" onsubmit="return validateForm()">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="nama" name="nama" placeholder="Nama Lengkap" required>
                        <label for="nama" class="text-muted">Nama Lengkap</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                        <label for="email" class="text-muted">Email UNNES (@students.unnes.ac.id)</label>
                    </div>
                    
                    <div class="mb-2 position-relative">
                        <div class="input-group">
                            <div class="form-floating flex-grow-1">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required style="border-right: 0;">
                                <label for="password" class="text-muted">Password</label>
                            </div>
                            <span class="input-group-text bg-white border-start-0 cursor-pointer" onclick="togglePass('password', 'iconPass1')" style="cursor: pointer;">
                                <i class="far fa-eye text-muted" id="iconPass1"></i>
                            </span>
                        </div>
                    </div>

                    <div class="progress mb-2" style="height: 5px;">
                        <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
                    </div>
                    
                    <div class="mb-3 ps-1">
                        <span class="req-item" id="req-length"><i class="fas fa-circle"></i> > 6 Karakter</span>
                        <span class="req-item" id="req-upper"><i class="fas fa-circle"></i> Kapital</span>
                        <span class="req-item" id="req-lower"><i class="fas fa-circle"></i> Kecil</span>
                        <span class="req-item" id="req-num"><i class="fas fa-circle"></i> Angka</span>
                        <span class="req-item" id="req-sym"><i class="fas fa-circle"></i> Simbol</span>
                    </div>

                    <div class="input-group mb-4">
                        <div class="form-floating flex-grow-1">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm" required style="border-right: 0;">
                            <label for="confirm_password" class="text-muted">Ulangi Password</label>
                        </div>
                        <span class="input-group-text bg-white border-start-0 cursor-pointer" onclick="togglePass('confirm_password', 'iconPass2')" style="cursor: pointer;">
                            <i class="far fa-eye text-muted" id="iconPass2"></i>
                        </span>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label small text-muted" for="terms">
                            Saya menyetujui syarat & ketentuan SPARTA.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-gradient w-100 py-3 rounded-3 shadow-sm mb-4" id="btnSubmit">
                        DAFTAR <i class="fas fa-user-plus ms-2"></i>
                    </button>

                    <div class="text-center">
                        <small class="text-muted">Sudah punya akun?</small> 
                        <a href="login.php" class="text-primary fw-bold text-decoration-none ms-1">Masuk di sini</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // 1. Toggle Show/Hide Password
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // 2. Realtime Password Strength Meter
        const passwordInput = document.getElementById('password');
        const progressBar = document.getElementById('passwordStrengthBar');
        
        const reqLength = document.getElementById('req-length');
        const reqUpper = document.getElementById('req-upper');
        const reqLower = document.getElementById('req-lower');
        const reqNum = document.getElementById('req-num');
        const reqSym = document.getElementById('req-sym');

        passwordInput.addEventListener('input', function() {
            const val = passwordInput.value;
            let strength = 0;

            // Check Length (> 6)
            if (val.length > 6) {
                markValid(reqLength); strength++;
            } else {
                markInvalid(reqLength);
            }

            // Check Uppercase
            if (/[A-Z]/.test(val)) {
                markValid(reqUpper); strength++;
            } else {
                markInvalid(reqUpper);
            }

            // Check Lowercase
            if (/[a-z]/.test(val)) {
                markValid(reqLower); strength++;
            } else {
                markInvalid(reqLower);
            }

            // Check Number
            if (/[0-9]/.test(val)) {
                markValid(reqNum); strength++;
            } else {
                markInvalid(reqNum);
            }

            // Check Symbol (Anything not letter or number)
            if (/[^a-zA-Z0-9]/.test(val)) {
                markValid(reqSym); strength++;
            } else {
                markInvalid(reqSym);
            }

            // Update Bar UI
            let width = (strength / 5) * 100;
            progressBar.style.width = width + '%';
            
            progressBar.classList.remove('bg-danger', 'bg-warning', 'bg-success');

            if (strength <= 2) {
                progressBar.classList.add('bg-danger');
            } else if (strength <= 4) {
                progressBar.classList.add('bg-warning');
            } else {
                progressBar.classList.add('bg-success');
            }
        });

        function markValid(el) {
            el.classList.add('valid');
            el.querySelector('i').classList.remove('fa-circle');
            el.querySelector('i').classList.add('fa-check');
        }

        function markInvalid(el) {
            el.classList.remove('valid');
            el.querySelector('i').classList.add('fa-circle');
            el.querySelector('i').classList.remove('fa-check');
        }

        // 3. Prevent Submit if Weak
        function validateForm() {
            const val = passwordInput.value;
            const isStrong = (
                val.length > 6 &&
                /[A-Z]/.test(val) &&
                /[a-z]/.test(val) &&
                /[0-9]/.test(val) &&
                /[^a-zA-Z0-9]/.test(val)
            );

            if (!isStrong) {
                // Pesan Alert Browser (Fallback)
                alert("Password lemah! Pastikan lebih dari 6 karakter dan mengandung kombinasi huruf besar, kecil, angka, dan simbol.");
                passwordInput.focus();
                return false;
            }
            return true;
        }
    </script>
</body>
</html>