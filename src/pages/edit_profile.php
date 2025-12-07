<?php
// FILE: Edit Profil Mahasiswa (Modern UI)

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

        // B. Upload Foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['foto']['tmp_name'];
            $fileExt = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                $nim = $pdo->query("SELECT NIM FROM Mahasiswa WHERE ID_Mahasiswa=$idMhs")->fetchColumn();
                $uploadDir = 'uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $destPath = $uploadDir . $nim . '.' . $fileExt;
                
                $oldFoto = $pdo->query("SELECT Foto_Profil FROM Mahasiswa WHERE ID_Mahasiswa=$idMhs")->fetchColumn();
                if ($oldFoto && file_exists($oldFoto)) unlink($oldFoto);

                if (move_uploaded_file($fileTmp, $destPath)) {
                    $pdo->prepare("UPDATE Mahasiswa SET Foto_Profil=? WHERE ID_Mahasiswa=?")->execute([$destPath, $idMhs]);
                }
            }
        }

        // C. Update SKILL
        $pdo->prepare("DELETE FROM Mahasiswa_Skill WHERE ID_Mahasiswa = ?")->execute([$idMhs]);
        if (!empty($_POST['skills'])) {
            $skills = explode(',', $_POST['skills']); 
            $stmtCheck = $pdo->prepare("SELECT ID_Skill FROM Skill WHERE Nama_Skill LIKE ?");
            $stmtInsMaster = $pdo->prepare("INSERT INTO Skill (Nama_Skill) VALUES (?)");
            $stmtLink = $pdo->prepare("INSERT INTO Mahasiswa_Skill (ID_Mahasiswa, ID_Skill) VALUES (?, ?)");

            foreach ($skills as $s) {
                $s = trim($s);
                if (empty($s)) continue;
                $stmtCheck->execute([$s]);
                $idSkill = $stmtCheck->fetchColumn();
                if (!$idSkill) {
                    $stmtInsMaster->execute([ucwords($s)]);
                    $idSkill = $pdo->lastInsertId();
                }
                $stmtLink->execute([$idMhs, $idSkill]);
            }
        }

        // D. Update ROLE
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

        $message = "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-check-circle me-2'></i>Profil berhasil diperbarui!</div>";

    } catch (Exception $e) {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>Error: " . $e->getMessage() . "</div>";
    }
}

// 2. FETCH DATA
$stmtMe = $pdo->prepare("SELECT m.*, p.Nama_Prodi, f.Nama_Fakultas 
                         FROM Mahasiswa m 
                         LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
                         LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas 
                         WHERE m.ID_Mahasiswa = ?");
$stmtMe->execute([$idMhs]);
$me = $stmtMe->fetch();

$mySkills = $pdo->query("SELECT s.Nama_Skill FROM Mahasiswa_Skill ms JOIN Skill s ON ms.ID_Skill = s.ID_Skill WHERE ms.ID_Mahasiswa = $idMhs")->fetchAll(PDO::FETCH_COLUMN);
$skillString = implode(',', $mySkills);

$myRoles = $pdo->query("SELECT r.Nama_Role FROM Mahasiswa_Role mr JOIN Role_Tim r ON mr.ID_Role = r.ID_Role WHERE mr.ID_Mahasiswa = $idMhs")->fetchAll(PDO::FETCH_COLUMN);
$roleString = implode(',', $myRoles);
?>

<style>
    .btn-gradient-dark {
        background: linear-gradient(135deg, #212529, #343a40);
        color: white; border: none;
    }
    .btn-gradient-dark:hover {
        background: linear-gradient(135deg, #1c1f23, #212529);
        color: white; transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(33, 37, 41, 0.3);
    }
    .profile-img-edit {
        width: 180px; height: 180px;
        object-fit: cover;
        border: 5px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .tag-container {
        min-height: 48px;
        padding: 8px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        background-color: #fff;
        display: flex; flex-wrap: wrap; gap: 8px;
    }
    .tag-container:focus-within {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .tag-item {
        background: #e9ecef;
        color: #495057;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 0.85rem;
        display: flex; align-items: center;
        transition: all 0.2s;
    }
    .tag-item i { margin-left: 8px; cursor: pointer; opacity: 0.6; }
    .tag-item i:hover { opacity: 1; color: #dc3545; }
    .tag-input {
        border: none; outline: none; flex-grow: 1; min-width: 120px; font-size: 0.9rem;
    }
    .suggestion-box {
        position: absolute; width: 100%; z-index: 1000;
        background: white; border: 1px solid #dee2e6;
        border-radius: 0 0 8px 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        max-height: 200px; overflow-y: auto;
    }
</style>

<div class="row justify-content-center">
    <div class="col-lg-10">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Edit Profil</h3>
                <p class="text-muted mb-0">Update skill, minat, dan portofolio Anda.</p>
            </div>
            <a href="?page=profile&id=<?= $idMhs ?>" class="btn btn-light shadow-sm text-secondary fw-bold rounded-pill px-4">
                <i class="fas fa-eye me-2"></i>Lihat Profil
            </a>
        </div>

        <?= $message ?>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-0">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-0">
                        
                        <div class="col-md-4 bg-light border-end p-4 text-center d-flex flex-column justify-content-center align-items-center">
                            <div class="mb-3 position-relative">
                                <?php if (!empty($me['Foto_Profil']) && file_exists($me['Foto_Profil'])): ?>
                                    <img src="<?= $me['Foto_Profil'] ?>?t=<?= time() ?>" class="rounded-circle profile-img-edit">
                                <?php else: ?>
                                    <div class="rounded-circle profile-img-edit bg-white d-flex align-items-center justify-content-center text-secondary fs-1">
                                        <?= strtoupper(substr($me['Nama_Mahasiswa'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="position-absolute bottom-0 end-0">
                                    <label class="btn btn-sm btn-dark rounded-circle shadow-sm" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                        <i class="fas fa-camera"></i>
                                        <input type="file" name="foto" class="d-none" accept="image/*" onchange="this.form.submit()">
                                    </label>
                                </div>
                            </div>
                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($me['Nama_Mahasiswa']) ?></h5>
                            <small class="text-muted mb-2 d-block"><?= htmlspecialchars($me['NIM']) ?></small>
                            <span class="badge bg-white border text-dark rounded-pill px-3"><?= htmlspecialchars($me['Nama_Prodi']) ?></span>
                        </div>

                        <div class="col-md-8 p-4 bg-white">
                            
                            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2"><i class="fas fa-graduation-cap me-2"></i>Data Akademik</h6>
                            <div class="alert alert-light border small text-muted mb-3">
                                <i class="fas fa-lock me-2"></i>Data berikut diambil dari sistem akademik dan tidak dapat diubah.
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted">Fakultas</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($me['Nama_Fakultas'] ?? '-') ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted">Program Studi</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($me['Nama_Prodi'] ?? '-') ?>" readonly>
                                </div>
                            </div>

                            <h6 class="text-success fw-bold mb-3 border-bottom pb-2"><i class="fas fa-user me-2"></i>Biodata & Kontak</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted">Tempat Lahir</label>
                                    <input type="text" name="tmp_lahir" class="form-control" value="<?= htmlspecialchars($me['Tempat_Lahir']??'') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted">Tanggal Lahir</label>
                                    <input type="date" name="tgl_lahir" class="form-control" value="<?= $me['Tanggal_Lahir'] ?>">
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted">WhatsApp</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                        <input type="text" name="no_hp" class="form-control" placeholder="08..." value="<?= htmlspecialchars($me['No_HP']??'') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted">LinkedIn</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-primary"><i class="fab fa-linkedin"></i></span>
                                        <input type="text" name="linkedin" class="form-control" placeholder="URL..." value="<?= htmlspecialchars($me['LinkedIn']??'') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="small fw-bold text-muted">Bio Singkat</label>
                                <textarea name="bio" class="form-control" rows="3" placeholder="Ceritakan sedikit tentang diri Anda..."><?= htmlspecialchars($me['Bio']??'') ?></textarea>
                            </div>

                            <h6 class="text-info fw-bold mb-3 border-bottom pb-2"><i class="fas fa-tools me-2"></i>Skill & Minat</h6>
                            
                            <div class="mb-3 position-relative">
                                <label class="small fw-bold text-muted">Minat Role (Profesi)</label>
                                <div id="role-container" class="tag-container" onclick="document.getElementById('role-input').focus()">
                                    <input type="text" id="role-input" class="tag-input" placeholder="Ketik role (ex: Frontend)...">
                                </div>
                                <div id="role-suggestions" class="suggestion-box" style="display:none;"></div>
                                <input type="hidden" name="roles" id="role-hidden" value="<?= $roleString ?>">
                            </div>

                            <div class="mb-4 position-relative">
                                <label class="small fw-bold text-muted">Skill / Tools (Keahlian)</label>
                                <div id="skill-container" class="tag-container" onclick="document.getElementById('skill-input').focus()">
                                    <input type="text" id="skill-input" class="tag-input" placeholder="Ketik skill (ex: PHP, Figma)...">
                                </div>
                                <div id="skill-suggestions" class="suggestion-box" style="display:none;"></div>
                                <input type="hidden" name="skills" id="skill-hidden" value="<?= $skillString ?>">
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-gradient-dark px-5 rounded-pill fw-bold shadow-sm">
                                    <i class="fas fa-save me-2"></i>Simpan Profil
                                </button>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Reusable Tag Input Logic
function setupTagSystem(containerId, inputId, hiddenId, suggestId, type) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const suggestionBox = document.getElementById(suggestId);
    
    // Load initial tags
    let tags = hidden.value ? hidden.value.split(',').filter(t => t) : [];
    renderTags();

    function renderTags() {
        // Clear current tags visually (keep input)
        const items = container.querySelectorAll('.tag-item');
        items.forEach(i => i.remove());

        // Re-add tags before input
        tags.forEach((tag, index) => {
            const span = document.createElement('span');
            span.className = `tag-item shadow-sm border ${type === 'role' ? 'bg-info bg-opacity-10 text-info' : 'bg-success bg-opacity-10 text-success'}`;
            span.innerHTML = `${tag} <i class="fas fa-times ms-2" onclick="removeTag('${hiddenId}', ${index})"></i>`;
            container.insertBefore(span, input);
        });
        hidden.value = tags.join(',');
    }

    // Global Remove Function (Attached to window for inline onclick)
    window.removeTag = function(targetId, idx) {
        if(targetId === hiddenId) {
            tags.splice(idx, 1);
            renderTags();
        }
    };

    // Input Handling
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addTag(this.value);
        } else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) {
            tags.pop();
            renderTags();
        }
    });

    // Suggestion Handling
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
                        const div = document.createElement('div');
                        div.className = 'p-2 border-bottom cursor-pointer hover-bg-light';
                        div.textContent = item.name;
                        div.onclick = () => {
                            addTag(item.name);
                            suggestionBox.style.display = 'none';
                        };
                        suggestionBox.appendChild(div);
                    });
                } else {
                    suggestionBox.style.display = 'none';
                }
            });
    });

    function addTag(text) {
        const cleanText = text.trim();
        // Cek duplikat (case insensitive)
        if (cleanText && !tags.some(t => t.toLowerCase() === cleanText.toLowerCase())) {
            tags.push(cleanText);
            renderTags();
        }
        input.value = '';
        suggestionBox.style.display = 'none';
        input.focus();
    }
    
    // Close suggestions on outside click
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target) && !suggestionBox.contains(e.target)) {
            suggestionBox.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setupTagSystem('role-container', 'role-input', 'role-hidden', 'role-suggestions', 'role');
    setupTagSystem('skill-container', 'skill-input', 'skill-hidden', 'skill-suggestions', 'skill');
});
</script>

<style>
    .hover-bg-light:hover { background-color: #f8f9fa; cursor: pointer; }
</style>