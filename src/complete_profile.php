<?php
// FILE: Halaman Wajib Isi Biodata (Cascading Dropdown)
define('IS_COMPLETE_PROFILE_PAGE', true);
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: login.php"); exit;
}

$idMhs = $_SESSION['user_id'];
$message = "";

// Ambil Data Master (Fakultas & Prodi untuk JS)
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiListAll = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();
$prodiJson = json_encode($prodiListAll); // Kirim ke JS

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim']);
    $prodi = $_POST['prodi'];

    if (empty($nim) || empty($prodi)) {
        $message = "<div class='alert alert-danger'>Semua data wajib diisi!</div>";
    } else {
        try {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Mahasiswa WHERE NIM = ? AND ID_Mahasiswa != ?");
            $cek->execute([$nim, $idMhs]);
            
            if ($cek->fetchColumn() > 0) {
                $message = "<div class='alert alert-danger'>NIM sudah digunakan!</div>";
            } else {
                $stmt = $pdo->prepare("UPDATE Mahasiswa SET NIM = ?, ID_Prodi = ? WHERE ID_Mahasiswa = ?");
                $stmt->execute([$nim, $prodi, $idMhs]);
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
                <small>Lengkapi data akademik Anda.</small>
            </div>
            <div class="card-body p-4">
                <?= $message ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">NIM</label>
                        <input type="text" name="nim" class="form-control" placeholder="A11.202X.XXXXX" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Fakultas</label>
                        <select id="fakultas" class="form-select" onchange="updateProdi()" required>
                            <option value="">-- Pilih Fakultas --</option>
                            <?php foreach($fakultasList as $f): ?>
                                <option value="<?= $f['ID_Fakultas'] ?>"><?= $f['Nama_Fakultas'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Program Studi</label>
                        <select name="prodi" id="prodi" class="form-select" disabled required>
                            <option value="">-- Pilih Fakultas Dahulu --</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 py-2 fw-bold">Simpan & Lanjutkan &rarr;</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const allProdi = <?= $prodiJson ?>;
        
        function updateProdi() {
            const fakId = document.getElementById('fakultas').value;
            const prodiSelect = document.getElementById('prodi');
            
            prodiSelect.innerHTML = '<option value="">-- Pilih Prodi --</option>';
            prodiSelect.disabled = true;

            if (fakId) {
                const filtered = allProdi.filter(p => p.ID_Fakultas == fakId);
                if (filtered.length > 0) {
                    prodiSelect.disabled = false;
                    filtered.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.ID_Prodi;
                        opt.text = p.Nama_Prodi;
                        prodiSelect.add(opt);
                    });
                } else {
                    const opt = document.createElement('option');
                    opt.text = "Tidak ada prodi di fakultas ini";
                    prodiSelect.add(opt);
                }
            }
        }
    </script>
</body>
</html>