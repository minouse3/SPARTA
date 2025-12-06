<?php
// FILE: Edit Profil Dosen (Lengkap)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    echo "<script>window.location='login.php';</script>"; exit;
}

$idDosen = $_SESSION['user_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update Biodata
        $stmt = $pdo->prepare("UPDATE Dosen_Pembimbing SET Tempat_Lahir=?, Tanggal_Lahir=?, Bio=?, No_HP=?, LinkedIn=? WHERE ID_Dosen=?");
        $stmt->execute([
            $_POST['tmp_lahir'], 
            $_POST['tgl_lahir'], 
            $_POST['bio'], 
            $_POST['no_hp'], 
            $_POST['linkedin'], 
            $idDosen
        ]);

        // Upload Foto (Logic NIM/NIDN)
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                $nidn = $pdo->query("SELECT NIDN FROM Dosen_Pembimbing WHERE ID_Dosen=$idDosen")->fetchColumn();
                $uploadDir = 'uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $destPath = $uploadDir . 'DSN_' . $nidn . '.' . $fileExt; // Prefix DSN
                
                $oldFoto = $pdo->query("SELECT Foto_Profil FROM Dosen_Pembimbing WHERE ID_Dosen=$idDosen")->fetchColumn();
                if ($oldFoto && file_exists($oldFoto)) unlink($oldFoto); // Hapus lama

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destPath)) {
                    $pdo->prepare("UPDATE Dosen_Pembimbing SET Foto_Profil=? WHERE ID_Dosen=?")->execute([$destPath, $idDosen]);
                }
            }
        }
        $message = "<div class='alert alert-success'>Profil berhasil disimpan!</div>";
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$dosen = $pdo->query("SELECT * FROM Dosen_Pembimbing WHERE ID_Dosen = $idDosen")->fetch();
?>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><i class="fas fa-user-edit me-2"></i>Edit Profil Dosen</h2>
            <a href="?page=profile_dosen&id=<?= $idDosen ?>" class="btn btn-outline-secondary btn-sm">Lihat Tampilan Publik</a>
        </div>
        <?= $message ?>
        
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-3 text-center">
                            <?php if(!empty($dosen['Foto_Profil']) && file_exists($dosen['Foto_Profil'])): ?>
                                <img src="<?= $dosen['Foto_Profil'] ?>?t=<?= time() ?>" class="rounded-circle img-thumbnail shadow-sm" width="120" height="120" style="object-fit:cover">
                            <?php else: ?>
                                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center border" style="width:120px;height:120px;font-size:3rem"><i class="fas fa-chalkboard-teacher text-muted"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-bold">Ganti Foto</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <h5 class="fw-bold text-secondary mb-3">Info Akademik</h5>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="small fw-bold">Nama Lengkap</label><input type="text" class="form-control bg-light" value="<?= htmlspecialchars($dosen['Nama_Dosen']) ?>" readonly></div>
                        <div class="col-md-6"><label class="small fw-bold">NIDN</label><input type="text" class="form-control bg-light" value="<?= htmlspecialchars($dosen['NIDN']) ?>" readonly></div>
                    </div>

                    <hr>
                    <h5 class="fw-bold text-primary mb-3">Biodata & Kontak</h5>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="small fw-bold">Tempat Lahir</label><input type="text" name="tmp_lahir" class="form-control" value="<?= htmlspecialchars($dosen['Tempat_Lahir']??'') ?>"></div>
                        <div class="col-md-6"><label class="small fw-bold">Tanggal Lahir</label><input type="date" name="tgl_lahir" class="form-control" value="<?= $dosen['Tanggal_Lahir'] ?>"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">WhatsApp</label>
                            <div class="input-group"><span class="input-group-text bg-white"><i class="fab fa-whatsapp text-success"></i></span><input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($dosen['No_HP']??'') ?>"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">LinkedIn</label>
                            <div class="input-group"><span class="input-group-text bg-white"><i class="fab fa-linkedin text-primary"></i></span><input type="text" name="linkedin" class="form-control" value="<?= htmlspecialchars($dosen['LinkedIn']??'') ?>"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Bio / Riset Interest</label>
                        <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($dosen['Bio']??'') ?></textarea>
                    </div>
                    
                    <div class="text-end"><button class="btn btn-primary fw-bold px-4">Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    </div>
</div>