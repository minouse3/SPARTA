<?php
// FILE: src/pages/force_change_password.php
if (!isset($_SESSION['user_id'])) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if (strlen($pass1) < 6) {
        echo "<div class='alert alert-danger'>Password minimal 6 karakter.</div>";
    } elseif ($pass1 !== $pass2) {
        echo "<div class='alert alert-danger'>Konfirmasi password tidak cocok.</div>";
    } else {
        $newHash = password_hash($pass1, PASSWORD_DEFAULT);
        $id = $_SESSION['user_id'];
        $role = $_SESSION['role'];

        try {
            if ($role === 'mahasiswa') {
                $stmt = $pdo->prepare("UPDATE Mahasiswa SET Password_Hash = ?, Need_Reset = 0 WHERE ID_Mahasiswa = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE Dosen_Pembimbing SET Password_Hash = ?, Need_Reset = 0 WHERE ID_Dosen = ?");
            }
            $stmt->execute([$newHash, $id]);

            // Update Session
            $_SESSION['need_reset'] = 0;
            
            echo "<script>alert('Password berhasil diubah! Silakan lanjutkan.'); window.location='index.php?page=dashboard';</script>";
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-warning text-dark border-0 py-3 text-center">
                <h4 class="fw-bold mb-0"><i class="fas fa-lock me-2"></i>Keamanan Akun</h4>
            </div>
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <span class="fa-stack fa-2x text-warning">
                            <i class="fas fa-circle fa-stack-2x opacity-25"></i>
                            <i class="fas fa-key fa-stack-1x"></i>
                        </span>
                    </div>
                    <h5>Ganti Password Diperlukan</h5>
                    <p class="text-muted small">Akun Anda baru saja dibuat oleh Admin. Demi keamanan, Anda wajib mengganti password default sebelum melanjutkan.</p>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="fw-bold small">Password Baru</label>
                        <input type="password" name="pass1" class="form-control" required placeholder="Minimal 6 karakter">
                    </div>
                    <div class="mb-4">
                        <label class="fw-bold small">Konfirmasi Password</label>
                        <input type="password" name="pass2" class="form-control" required placeholder="Ulangi password baru">
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-warning fw-bold rounded-pill">Simpan Password Baru</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>