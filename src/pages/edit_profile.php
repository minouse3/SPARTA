<?php
// FILE: Edit Profil Lengkap (Kontak & Readonly Info)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    echo "<script>window.location='login.php';</script>";
    exit;
}

$idMhs = $_SESSION['user_id'];
$message = "";

// --- 1. PROSES UPDATE DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // A. Update Biodata & Kontak
        $stmt = $pdo->prepare("UPDATE Mahasiswa SET Tempat_Lahir=?, Tanggal_Lahir=?, Bio=?, No_HP=?, LinkedIn=? WHERE ID_Mahasiswa=?");
        $stmt->execute([
            $_POST['tmp_lahir'], 
            $_POST['tgl_lahir'], 
            $_POST['bio'], 
            $_POST['no_hp'], 
            $_POST['linkedin'], 
            $idMhs
        ]);

        // B. PROSES UPLOAD FOTO (Sama seperti sebelumnya)
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['foto']['tmp_name'];
            $fileName = $_FILES['foto']['name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExt, $allowed)) {
                $nim = $pdo->query("SELECT NIM FROM Mahasiswa WHERE ID_Mahasiswa=$idMhs")->fetchColumn();
                $newFileName = $nim . '_' . time() . '.' . $fileExt;
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmp, $destPath)) {
                    $oldFoto = $pdo->query("SELECT Foto_Profil FROM Mahasiswa WHERE ID_Mahasiswa=$idMhs")->fetchColumn();
                    if ($oldFoto && file_exists($oldFoto)) unlink($oldFoto);
                    $stmtFoto = $pdo->prepare("UPDATE Mahasiswa SET Foto_Profil=? WHERE ID_Mahasiswa=?");
                    $stmtFoto->execute([$destPath, $idMhs]);
                }
            } else {
                $message .= "<div class='alert alert-warning'>Format foto harus JPG, JPEG, atau PNG.</div>";
            }
        }

        // C. Update Skill (Logic Hybrid)
        $pdo->prepare("DELETE FROM Mahasiswa_Keahlian WHERE ID_Mahasiswa = ?")->execute([$idMhs]);
        $allInputs = $_POST['skills'] ?? []; 
        if (!empty($_POST['manual_skill'])) {
            $manuals = explode(',', $_POST['manual_skill']);
            $allInputs = array_merge($allInputs, $manuals);
        }

        if (!empty($allInputs)) {
            $stmtInsert = $pdo->prepare("INSERT INTO Mahasiswa_Keahlian (ID_Mahasiswa, ID_Keahlian) VALUES (?, ?)");
            $stmtCheck = $pdo->prepare("SELECT ID_Keahlian FROM Keahlian WHERE Nama_Keahlian LIKE ?");
            $stmtNew = $pdo->prepare("INSERT INTO Keahlian (Nama_Keahlian) VALUES (?)");
            $processedIDs = [];

            foreach ($allInputs as $input) {
                $clean = trim($input);
                if (empty($clean)) continue;
                
                $finalID = 0;
                if (is_numeric($clean)) {
                    $finalID = $clean;
                } else {
                    $stmtCheck->execute([$clean]);
                    $exist = $stmtCheck->fetchColumn();
                    if ($exist) {
                        $finalID = $exist;
                    } else {
                        $stmtNew->execute([ucwords(strtolower($clean))]);
                        $finalID = $pdo->lastInsertId();
                    }
                }
                
                if ($finalID > 0 && !in_array($finalID, $processedIDs)) {
                    $stmtInsert->execute([$idMhs, $finalID]);
                    $processedIDs[] = $finalID;
                }
            }
        }

        if (empty($message)) {
            $message = "<div class='alert alert-success'>Profil berhasil diperbarui!</div>";
        }

    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// --- 2. AMBIL DATA USER LENGKAP ---
// Join dengan Prodi & Fakultas untuk ditampilkan (Readonly)
$sqlMe = "SELECT m.*, p.Nama_Prodi, f.Nama_Fakultas 
          FROM Mahasiswa m 
          LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
          LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas 
          WHERE m.ID_Mahasiswa = ?";
$stmtMe = $pdo->prepare($sqlMe);
$stmtMe->execute([$idMhs]);
$me = $stmtMe->fetch();

// Ambil Skill
$mySkillIDs = $pdo->query("SELECT ID_Keahlian FROM Mahasiswa_Keahlian WHERE ID_Mahasiswa = $idMhs")->fetchAll(PDO::FETCH_COLUMN);
$allSkills = $pdo->query("SELECT * FROM Keahlian ORDER BY Nama_Keahlian ASC")->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><i class="fas fa-user-edit me-2"></i>Edit Profil</h2>
            <a href="?page=profile&id=<?= $idMhs ?>" class="btn btn-outline-secondary btn-sm">Lihat Tampilan Publik</a>
        </div>

        <?= $message ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-3 text-center">
                            <?php if (!empty($me['Foto_Profil']) && file_exists($me['Foto_Profil'])): ?>
                                <img src="<?= $me['Foto_Profil'] ?>" class="rounded-circle img-thumbnail shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto border" style="width: 120px; height: 120px; font-size: 3rem; color: #ccc;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-bold">Ganti Foto Profil</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                            <div class="form-text small">Format: JPG, JPEG, PNG. Maksimal 2MB.</div>
                        </div>
                    </div>

                    <hr>

                    <h5 class="fw-bold text-secondary mb-3"><i class="fas fa-university me-2"></i>Info Akademik</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Nama Lengkap</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($me['Nama_Mahasiswa']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">NIM</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($me['NIM']) ?>" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Fakultas</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($me['Nama_Fakultas'] ?? '-') ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Program Studi</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($me['Nama_Prodi'] ?? '-') ?>" readonly>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Email Institusi</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($me['Email']) ?>" readonly>
                        <div class="form-text x-small text-danger"><i class="fas fa-lock me-1"></i> Data akademik & email tidak dapat diubah. Hubungi Admin jika ada kesalahan.</div>
                    </div>

                    <hr>

                    <h5 class="fw-bold text-primary mb-3"><i class="fas fa-address-card me-2"></i>Biodata & Kontak</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fab fa-whatsapp text-success"></i></span>
                                <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($me['No_HP'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">LinkedIn URL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fab fa-linkedin text-primary"></i></span>
                                <input type="text" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/..." value="<?= htmlspecialchars($me['LinkedIn'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tempat Lahir</label>
                            <input type="text" name="tmp_lahir" class="form-control" value="<?= htmlspecialchars($me['Tempat_Lahir'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" class="form-control" value="<?= $me['Tanggal_Lahir'] ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Bio / Deskripsi Diri</label>
                        <textarea name="bio" class="form-control" rows="3" placeholder="Ceritakan keahlian dan minatmu..."><?= htmlspecialchars($me['Bio'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary">Keahlian / Skill</label>
                        <div class="card bg-light border-0 p-3 mb-2" style="max-height: 150px; overflow-y: auto;">
                            <div class="row g-2">
                                <?php foreach($allSkills as $s): ?>
                                <div class="col-md-4 col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="skills[]" value="<?= $s['ID_Keahlian'] ?>" <?= in_array($s['ID_Keahlian'], $mySkillIDs) ? 'checked' : '' ?>>
                                        <label class="form-check-label small"><?= htmlspecialchars($s['Nama_Keahlian']) ?></label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <input type="text" name="manual_skill" class="form-control" placeholder="Tambah skill lain (pisahkan koma)...">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>