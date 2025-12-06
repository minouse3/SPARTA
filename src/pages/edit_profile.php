<?php
// FILE: Edit Profil dengan Split Skill (Tools) & Role (Profesi) + Tag Input
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    echo "<script>window.location='login.php';</script>"; exit;
}

$idMhs = $_SESSION['user_id'];
$message = "";

// 1. PROSES SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // A. Update Biodata
        $stmt = $pdo->prepare("UPDATE Mahasiswa SET Tempat_Lahir=?, Tanggal_Lahir=?, Bio=?, No_HP=?, LinkedIn=? WHERE ID_Mahasiswa=?");
        $stmt->execute([$_POST['tmp_lahir'], $_POST['tgl_lahir'], $_POST['bio'], $_POST['no_hp'], $_POST['linkedin'], $idMhs]);

        // B. Upload Foto (Logic Overwrite NIM)
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['foto']['tmp_name'];
            $fileExt = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                $nim = $pdo->query("SELECT NIM FROM Mahasiswa WHERE ID_Mahasiswa=$idMhs")->fetchColumn();
                $uploadDir = 'uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $destPath = $uploadDir . $nim . '.' . $fileExt;
                
                // Hapus file lama jika ada (termasuk beda ekstensi)
                $oldFoto = $pdo->query("SELECT Foto_Profil FROM Mahasiswa WHERE ID_Mahasiswa=$idMhs")->fetchColumn();
                if ($oldFoto && file_exists($oldFoto)) unlink($oldFoto);

                if (move_uploaded_file($fileTmp, $destPath)) {
                    $pdo->prepare("UPDATE Mahasiswa SET Foto_Profil=? WHERE ID_Mahasiswa=?")->execute([$destPath, $idMhs]);
                }
            }
        }

        // C. Update SKILL (Tools)
        $pdo->prepare("DELETE FROM Mahasiswa_Skill WHERE ID_Mahasiswa = ?")->execute([$idMhs]);
        if (!empty($_POST['skills'])) {
            $skills = explode(',', $_POST['skills']); // Terima string dipisah koma "Python,Figma"
            $stmtCheck = $pdo->prepare("SELECT ID_Skill FROM Skill WHERE Nama_Skill LIKE ?");
            $stmtInsMaster = $pdo->prepare("INSERT INTO Skill (Nama_Skill) VALUES (?)");
            $stmtLink = $pdo->prepare("INSERT INTO Mahasiswa_Skill (ID_Mahasiswa, ID_Skill) VALUES (?, ?)");

            foreach ($skills as $s) {
                $s = trim($s);
                if (empty($s)) continue;
                
                // Cek Master
                $stmtCheck->execute([$s]);
                $idSkill = $stmtCheck->fetchColumn();
                if (!$idSkill) {
                    $stmtInsMaster->execute([ucwords($s)]);
                    $idSkill = $pdo->lastInsertId();
                }
                $stmtLink->execute([$idMhs, $idSkill]);
            }
        }

        // D. Update ROLE (Profesi)
        $pdo->prepare("DELETE FROM Mahasiswa_Role WHERE ID_Mahasiswa = ?")->execute([$idMhs]);
        if (!empty($_POST['roles'])) {
            $roles = explode(',', $_POST['roles']);
            $stmtCheck = $pdo->prepare("SELECT ID_Role FROM Role_Tim WHERE Nama_Role LIKE ?");
            $stmtInsMaster = $pdo->prepare("INSERT INTO Role_Tim (Nama_Role) VALUES (?)");
            $stmtLink = $pdo->prepare("INSERT INTO Mahasiswa_Role (ID_Mahasiswa, ID_Role) VALUES (?, ?)");

            foreach ($roles as $r) {
                $r = trim($r);
                if (empty($r)) continue;
                
                $stmtCheck->execute([$r]);
                $idRole = $stmtCheck->fetchColumn();
                if (!$idRole) {
                    $stmtInsMaster->execute([ucwords($r)]);
                    $idRole = $pdo->lastInsertId();
                }
                $stmtLink->execute([$idMhs, $idRole]);
            }
        }

        $message = "<div class='alert alert-success'>Profil berhasil disimpan!</div>";

    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// 2. FETCH DATA
$sqlMe = "SELECT m.*, p.Nama_Prodi, f.Nama_Fakultas 
          FROM Mahasiswa m 
          LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
          LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas 
          WHERE m.ID_Mahasiswa = ?";
$stmtMe = $pdo->prepare($sqlMe);
$stmtMe->execute([$idMhs]);
$me = $stmtMe->fetch();

// Fetch Skills (Comma Separated for Input Value)
$mySkills = $pdo->query("SELECT s.Nama_Skill FROM Mahasiswa_Skill ms JOIN Skill s ON ms.ID_Skill = s.ID_Skill WHERE ms.ID_Mahasiswa = $idMhs")->fetchAll(PDO::FETCH_COLUMN);
$skillString = implode(',', $mySkills);

// Fetch Roles
$myRoles = $pdo->query("SELECT r.Nama_Role FROM Mahasiswa_Role mr JOIN Role_Tim r ON mr.ID_Role = r.ID_Role WHERE mr.ID_Mahasiswa = $idMhs")->fetchAll(PDO::FETCH_COLUMN);
$roleString = implode(',', $myRoles);
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
                                <img src="<?= $me['Foto_Profil'] ?>?t=<?= time() ?>" class="rounded-circle img-thumbnail shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto border" style="width: 120px; height: 120px; font-size: 3rem; color: #ccc;"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-bold">Ganti Foto</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
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
                        <div class="col-md-6"><label class="small fw-bold">Tempat Lahir</label><input type="text" name="tmp_lahir" class="form-control" value="<?= htmlspecialchars($me['Tempat_Lahir']??'') ?>"></div>
                        <div class="col-md-6"><label class="small fw-bold">Tgl Lahir</label><input type="date" name="tgl_lahir" class="form-control" value="<?= $me['Tanggal_Lahir'] ?>"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="small fw-bold">WhatsApp</label><input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($me['No_HP']??'') ?>"></div>
                        <div class="col-md-6"><label class="small fw-bold">LinkedIn</label><input type="text" name="linkedin" class="form-control" value="<?= htmlspecialchars($me['LinkedIn']??'') ?>"></div>
                    </div>
                    <div class="mb-3"><label class="small fw-bold">Bio</label><textarea name="bio" class="form-control" rows="2"><?= htmlspecialchars($me['Bio']??'') ?></textarea></div>

                    <hr>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary"><i class="fas fa-briefcase me-2"></i>Minat Profesi / Role</label>
                        <p class="small text-muted mb-1">Posisi apa yang Anda incar di tim? (Contoh: Frontend, UI/UX)</p>
                        <div id="role-tags" class="d-flex flex-wrap gap-2 mb-2 p-2 border rounded bg-light" style="min-height: 45px;"></div>
                        <input type="text" id="role-input" class="form-control" placeholder="Ketik lalu pilih/enter...">
                        <div id="role-suggestions" class="list-group position-absolute shadow w-50" style="z-index: 1000; display:none;"></div>
                        <input type="hidden" name="roles" id="role-hidden" value="<?= $roleString ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-success"><i class="fas fa-tools me-2"></i>Skill / Tools</label>
                        <p class="small text-muted mb-1">Alat atau bahasa yang Anda kuasai? (Contoh: Python, Figma)</p>
                        <div id="skill-tags" class="d-flex flex-wrap gap-2 mb-2 p-2 border rounded bg-light" style="min-height: 45px;"></div>
                        <input type="text" id="skill-input" class="form-control" placeholder="Ketik lalu pilih/enter...">
                        <div id="skill-suggestions" class="list-group position-absolute shadow w-50" style="z-index: 1000; display:none;"></div>
                        <input type="hidden" name="skills" id="skill-hidden" value="<?= $skillString ?>">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Profil</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function setupTagInput(inputId, containerId, hiddenId, suggestId, type) {
    const input = document.getElementById(inputId);
    const container = document.getElementById(containerId);
    const hidden = document.getElementById(hiddenId);
    const suggestionBox = document.getElementById(suggestId);
    let tags = hidden.value ? hidden.value.split(',').filter(x => x) : [];

    function renderTags() {
        container.innerHTML = '';
        tags.forEach((tag, index) => {
            const badge = document.createElement('span');
            badge.className = `badge ${type === 'skill' ? 'bg-success' : 'bg-primary'} d-flex align-items-center`;
            badge.innerHTML = `${tag} <i class="fas fa-times ms-2 cursor-pointer" onclick="removeTag('${hiddenId}', ${index})"></i>`;
            container.appendChild(badge);
        });
        hidden.value = tags.join(',');
    }

    // Fungsi global untuk remove (karena onclick di string HTML)
    window.removeTag = function(targetHiddenId, index) {
        if(targetHiddenId === 'role-hidden') { 
            // Refresh array dari DOM atau variable scope? 
            // Karena scope masalah, kita ambil dari value hidden input ulang
            let current = document.getElementById('role-hidden').value.split(',').filter(x=>x);
            current.splice(index, 1);
            tags = current; // update local scope (sedikit hacky tapi jalan di simple JS)
            // Re-render role
            const con = document.getElementById('role-tags');
            const hid = document.getElementById('role-hidden');
            con.innerHTML = '';
            tags.forEach((tag, idx) => {
                const b = document.createElement('span');
                b.className = 'badge bg-primary d-flex align-items-center';
                b.innerHTML = `${tag} <i class="fas fa-times ms-2 cursor-pointer" onclick="removeTag('role-hidden', ${idx})"></i>`;
                con.appendChild(b);
            });
            hid.value = tags.join(',');
        } else {
            // Logic sama untuk skill
            let current = document.getElementById('skill-hidden').value.split(',').filter(x=>x);
            current.splice(index, 1);
            const con = document.getElementById('skill-tags');
            const hid = document.getElementById('skill-hidden');
            con.innerHTML = '';
            current.forEach((tag, idx) => {
                const b = document.createElement('span');
                b.className = 'badge bg-success d-flex align-items-center';
                b.innerHTML = `${tag} <i class="fas fa-times ms-2 cursor-pointer" onclick="removeTag('skill-hidden', ${idx})"></i>`;
                con.appendChild(b);
            });
            hid.value = current.join(',');
        }
    };
    
    // Initial Render
    renderTags();

    // Event Typing (Autocomplete)
    input.addEventListener('input', function() {
        const val = this.value.trim();
        if (val.length < 1) {
            suggestionBox.style.display = 'none';
            return;
        }

        fetch(`fetch_tags.php?type=${type}&q=${val}`)
            .then(res => res.json())
            .then(data => {
                suggestionBox.innerHTML = '';
                if (data.length > 0) {
                    suggestionBox.style.display = 'block';
                    data.forEach(item => {
                        const a = document.createElement('a');
                        a.className = 'list-group-item list-group-item-action cursor-pointer';
                        a.textContent = item.name;
                        a.onclick = () => {
                            addTag(item.name);
                            suggestionBox.style.display = 'none';
                        };
                        suggestionBox.appendChild(a);
                    });
                } else {
                    suggestionBox.style.display = 'none';
                }
            });
    });

    // Enter Key to Add New
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = this.value.trim();
            if (val) addTag(val);
        }
    });

    function addTag(text) {
        // Cek duplikat (ambil fresh dari hidden value agar sinkron)
        let currentTags = hidden.value ? hidden.value.split(',').filter(x=>x) : [];
        
        // Case insensitive check
        if (!currentTags.some(t => t.toLowerCase() === text.toLowerCase())) {
            currentTags.push(text);
            hidden.value = currentTags.join(',');
            
            // Render ulang manual
            const badge = document.createElement('span');
            badge.className = `badge ${type === 'skill' ? 'bg-success' : 'bg-primary'} d-flex align-items-center`;
            // Index terakhir
            let idx = currentTags.length - 1;
            badge.innerHTML = `${text} <i class="fas fa-times ms-2 cursor-pointer" onclick="removeTag('${hiddenId}', ${idx})"></i>`;
            container.appendChild(badge);
        }
        input.value = '';
        suggestionBox.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setupTagInput('role-input', 'role-tags', 'role-hidden', 'role-suggestions', 'role');
    setupTagInput('skill-input', 'skill-tags', 'skill-hidden', 'skill-suggestions', 'skill');
});
</script>

<style>
    .cursor-pointer { cursor: pointer; }
</style>