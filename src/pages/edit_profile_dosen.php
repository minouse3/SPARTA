<?php
// FILE: src/pages/edit_profile_dosen.php (Fixed: Profile Picture Auto-Discovery)

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    echo "<script>window.location='login.php';</script>"; exit;
}

$idDosen = $_SESSION['user_id'];
$message = "";

// Ambil Data Master untuk Dropdown
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiListAll = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();
$prodiJson = json_encode($prodiListAll); 

// 1. PROSES SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // A. Update Biodata & Akademik
        $stmt = $pdo->prepare("UPDATE Dosen_Pembimbing SET Tempat_Lahir=?, Tanggal_Lahir=?, Bio=?, No_HP=?, LinkedIn=?, ID_Prodi=?, Email=? WHERE ID_Dosen=?");
        $stmt->execute([
            $_POST['tmp_lahir'], 
            $_POST['tgl_lahir'], 
            $_POST['bio'], 
            $_POST['no_hp'], 
            $_POST['linkedin'], 
            $_POST['prodi'], 
            $_POST['email'],
            $idDosen
        ]);

        // B. Upload Foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['foto']['tmp_name'];
            $fileExt = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                $nidn = $pdo->query("SELECT NIDN FROM Dosen_Pembimbing WHERE ID_Dosen=$idDosen")->fetchColumn();
                $uploadDir = 'uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                // Format nama file: DSN_NIDN.ext
                $destPath = $uploadDir . 'DSN_' . $nidn . '.' . $fileExt;
                
                // Hapus foto lama jika ada (dan beda ekstensi/nama)
                $oldFoto = $pdo->query("SELECT Foto_Profil FROM Dosen_Pembimbing WHERE ID_Dosen=$idDosen")->fetchColumn();
                if ($oldFoto && file_exists($oldFoto) && $oldFoto !== $destPath) {
                    unlink($oldFoto);
                }

                if (move_uploaded_file($fileTmp, $destPath)) {
                    $pdo->prepare("UPDATE Dosen_Pembimbing SET Foto_Profil=? WHERE ID_Dosen=?")->execute([$destPath, $idDosen]);
                }
            }
        }

        // C. Update SKILL (Keahlian)
        $pdo->prepare("DELETE FROM Dosen_Keahlian WHERE ID_Dosen = ?")->execute([$idDosen]);
        if (!empty($_POST['skills'])) {
            $skills = explode(',', $_POST['skills']); 
            $stmtCheck = $pdo->prepare("SELECT ID_Skill FROM Skill WHERE Nama_Skill LIKE ?");
            $stmtInsMaster = $pdo->prepare("INSERT INTO Skill (Nama_Skill) VALUES (?)");
            $stmtLink = $pdo->prepare("INSERT INTO Dosen_Keahlian (ID_Dosen, ID_Skill) VALUES (?, ?)");

            foreach ($skills as $s) {
                $s = trim($s);
                if (empty($s)) continue;
                $stmtCheck->execute([$s]);
                $idSkill = $stmtCheck->fetchColumn();
                if (!$idSkill) {
                    $stmtInsMaster->execute([ucwords($s)]);
                    $idSkill = $pdo->lastInsertId();
                }
                $stmtLink->execute([$idDosen, $idSkill]);
            }
        }

        // D. Update ROLE (Minat/Fokus)
        $pdo->prepare("DELETE FROM Dosen_Role WHERE ID_Dosen = ?")->execute([$idDosen]);
        if (!empty($_POST['roles'])) {
            $roles = explode(',', $_POST['roles']);
            $stmtCheck = $pdo->prepare("SELECT ID_Role FROM Role_Tim WHERE Nama_Role LIKE ?");
            $stmtInsMaster = $pdo->prepare("INSERT INTO Role_Tim (Nama_Role) VALUES (?)");
            $stmtLink = $pdo->prepare("INSERT INTO Dosen_Role (ID_Dosen, ID_Role) VALUES (?, ?)");

            foreach ($roles as $r) {
                $r = trim($r);
                if (empty($r)) continue;
                $stmtCheck->execute([$r]);
                $idRole = $stmtCheck->fetchColumn();
                if (!$idRole) {
                    $stmtInsMaster->execute([ucwords($r)]);
                    $idRole = $pdo->lastInsertId();
                }
                $stmtLink->execute([$idDosen, $idRole]);
            }
        }

        $message = "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-check-circle me-2'></i>Profil berhasil diperbarui!</div>";

    } catch (Exception $e) {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>Error: " . $e->getMessage() . "</div>";
    }
}

// 2. FETCH DATA LENGKAP
$stmtMe = $pdo->prepare("SELECT d.*, p.ID_Fakultas FROM Dosen_Pembimbing d LEFT JOIN Prodi p ON d.ID_Prodi = p.ID_Prodi WHERE d.ID_Dosen = ?");
$stmtMe->execute([$idDosen]);
$dosen = $stmtMe->fetch();

// Fetch Skills
$mySkills = $pdo->query("SELECT s.Nama_Skill FROM Dosen_Keahlian dk JOIN Skill s ON dk.ID_Skill = s.ID_Skill WHERE dk.ID_Dosen = $idDosen")->fetchAll(PDO::FETCH_COLUMN);
$skillString = implode(',', $mySkills);

// Fetch Roles
try {
    $myRoles = $pdo->query("SELECT r.Nama_Role FROM Dosen_Role dr JOIN Role_Tim r ON dr.ID_Role = r.ID_Role WHERE dr.ID_Dosen = $idDosen")->fetchAll(PDO::FETCH_COLUMN);
    $roleString = implode(',', $myRoles);
} catch (Exception $e) { $roleString = ""; }
?>

<style>
    .btn-gradient-primary {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0); color: white; border: none;
    }
    .btn-gradient-primary:hover {
        background: linear-gradient(135deg, #0b5ed7, #0aa2c0); color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }
    .profile-img-edit { width: 180px; height: 180px; object-fit: cover; border: 5px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .form-label { font-weight: 600; font-size: 0.85rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    
    /* Tag Styles */
    .tag-container {
        min-height: 48px; padding: 8px; border: 1px solid #ced4da; border-radius: 0.375rem; background-color: #fff; display: flex; flex-wrap: wrap; gap: 8px;
    }
    .tag-container:focus-within { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
    .tag-item {
        background: #e9ecef; color: #495057; padding: 4px 10px; border-radius: 50px; font-size: 0.85rem; display: flex; align-items: center;
    }
    .tag-item i { margin-left: 8px; cursor: pointer; opacity: 0.6; }
    .tag-item i:hover { opacity: 1; color: #dc3545; }
    .tag-input { border: none; outline: none; flex-grow: 1; min-width: 120px; font-size: 0.9rem; }
    .suggestion-box {
        position: absolute; width: 100%; z-index: 1000; background: white; border: 1px solid #dee2e6; border-radius: 0 0 8px 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto;
    }
    .hover-bg-light:hover { background-color: #f8f9fa; cursor: pointer; }
</style>

<div class="row justify-content-center">
    <div class="col-lg-10">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Edit Profil</h3>
                <p class="text-muted mb-0">Kelola data diri, keahlian, dan informasi akademik Anda.</p>
            </div>
            <a href="?page=profile_dosen&id=<?= $idDosen ?>" class="btn btn-light shadow-sm text-secondary fw-bold rounded-pill px-4">
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
                                <?php 
                                    // --- PERBAIKAN DI SINI ---
                                    // Gunakan getFotoMhs dengan prefix 'DSN_' agar gambar terdeteksi otomatis
                                    $fotoPath = getFotoMhs('DSN_' . $dosen['NIDN'], $dosen['Foto_Profil']);
                                ?>

                                <?php if ($fotoPath): ?>
                                    <img src="<?= $fotoPath ?>?t=<?= time() ?>" class="rounded-circle profile-img-edit">
                                <?php else: ?>
                                    <div class="rounded-circle profile-img-edit bg-white d-flex align-items-center justify-content-center text-secondary fs-1">
                                        <i class="fas fa-user-tie fa-2x opacity-25"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="position-absolute bottom-0 end-0">
                                    <label class="btn btn-sm btn-primary rounded-circle shadow-sm" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                        <i class="fas fa-camera"></i>
                                        <input type="file" name="foto" class="d-none" accept="image/*" onchange="this.form.submit()">
                                    </label>
                                </div>
                            </div>
                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($dosen['Nama_Dosen']) ?></h5>
                            <small class="text-muted mb-3 d-block"><?= htmlspecialchars($dosen['NIDN']) ?></small>
                        </div>

                        <div class="col-md-8 p-4 bg-white">
                            
                            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2"><i class="fas fa-university me-2"></i>Data Akademik</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($dosen['Nama_Dosen']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">NIDN</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($dosen['NIDN']) ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email Institusi</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($dosen['Email']) ?>" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Fakultas</label>
                                    <select id="fakultas" class="form-select" onchange="updateProdi()">
                                        <option value="">-- Pilih Fakultas --</option>
                                        <?php foreach($fakultasList as $f): ?>
                                            <option value="<?= $f['ID_Fakultas'] ?>" <?= ($dosen['ID_Fakultas'] == $f['ID_Fakultas']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($f['Nama_Fakultas']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Program Studi</label>
                                    <select name="prodi" id="prodi" class="form-select" required>
                                        <option value="">-- Pilih Fakultas Dahulu --</option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="text-success fw-bold mb-3 border-bottom pb-2"><i class="fas fa-user me-2"></i>Biodata & Kontak</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tempat Lahir</label>
                                    <input type="text" name="tmp_lahir" class="form-control" value="<?= htmlspecialchars($dosen['Tempat_Lahir']??'') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" name="tgl_lahir" class="form-control" value="<?= $dosen['Tanggal_Lahir'] ?>">
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                        <input type="text" name="no_hp" class="form-control" placeholder="08..." value="<?= htmlspecialchars($dosen['No_HP']??'') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">LinkedIn URL</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-primary"><i class="fab fa-linkedin"></i></span>
                                        <input type="text" name="linkedin" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($dosen['LinkedIn']??'') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Bio Singkat</label>
                                <textarea name="bio" class="form-control" rows="3" placeholder="Tuliskan ringkasan profil Anda..."><?= htmlspecialchars($dosen['Bio']??'') ?></textarea>
                            </div>

                            <h6 class="text-info fw-bold mb-3 border-bottom pb-2"><i class="fas fa-tools me-2"></i>Keahlian & Minat</h6>
                            
                            <div class="mb-4 position-relative">
                                <label class="form-label text-muted small">Minat</label>
                                <div id="role-container" class="tag-container" onclick="document.getElementById('role-input').focus()">
                                    <input type="text" id="role-input" class="tag-input" placeholder="Ketik peran (ex: Cyber Security, Game Developer, etc.)...">
                                </div>
                                <div id="role-suggestions" class="suggestion-box" style="display:none;"></div>
                                <input type="hidden" name="roles" id="role-hidden" value="<?= $roleString ?>">
                            </div>
                            
                            <div class="mb-3 position-relative">
                                <label class="form-label text-muted small">Keahlian</label>
                                <div id="skill-container" class="tag-container" onclick="document.getElementById('skill-input').focus()">
                                    <input type="text" id="skill-input" class="tag-input" placeholder="Ketik keahlian (ex: Linux, Unity, etc)...">
                                </div>
                                <div id="skill-suggestions" class="suggestion-box" style="display:none;"></div>
                                <input type="hidden" name="skills" id="skill-hidden" value="<?= $skillString ?>">
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-gradient-primary px-5 rounded-pill fw-bold shadow-sm">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
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
    // Logic Dropdown Prodi
    const allProdi = <?= $prodiJson ?>;
    const currentProdiId = "<?= $dosen['ID_Prodi'] ?>";

    function updateProdi() {
        const fakId = document.getElementById('fakultas').value;
        const prodiSelect = document.getElementById('prodi');
        prodiSelect.innerHTML = '<option value="">-- Pilih Prodi --</option>';
        if (fakId) {
            const filtered = allProdi.filter(p => p.ID_Fakultas == fakId);
            filtered.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.ID_Prodi;
                opt.text = p.Nama_Prodi;
                if (p.ID_Prodi == currentProdiId) opt.selected = true;
                prodiSelect.add(opt);
            });
        }
    }
    document.addEventListener("DOMContentLoaded", updateProdi);

    // Logic Tag Input (Reusable)
    function setupTagSystem(containerId, inputId, hiddenId, suggestId, type) {
        const container = document.getElementById(containerId);
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const suggestionBox = document.getElementById(suggestId);
        
        let tags = hidden.value ? hidden.value.split(',').filter(t => t) : [];
        renderTags();

        function renderTags() {
            const items = container.querySelectorAll('.tag-item');
            items.forEach(i => i.remove());
            tags.forEach((tag, index) => {
                const span = document.createElement('span');
                span.className = `tag-item shadow-sm border ${type === 'role' ? 'bg-info bg-opacity-10 text-info' : 'bg-success bg-opacity-10 text-success'}`;
                span.innerHTML = `${tag} <i class="fas fa-times ms-2" onclick="removeTag('${hiddenId}', ${index})"></i>`;
                container.insertBefore(span, input);
            });
            hidden.value = tags.join(',');
        }

        window.removeTag = function(targetId, idx) {
            if(targetId === hiddenId) { tags.splice(idx, 1); renderTags(); }
        };

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); addTag(this.value); }
            else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) { tags.pop(); renderTags(); }
        });

        input.addEventListener('input', function() {
            const val = this.value.trim();
            if (val.length < 1) { suggestionBox.style.display = 'none'; return; }
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
                            div.onclick = () => { addTag(item.name); suggestionBox.style.display = 'none'; };
                            suggestionBox.appendChild(div);
                        });
                    } else { suggestionBox.style.display = 'none'; }
                });
        });

        function addTag(text) {
            const cleanText = text.trim();
            if (cleanText && !tags.some(t => t.toLowerCase() === cleanText.toLowerCase())) {
                tags.push(cleanText); renderTags();
            }
            input.value = ''; suggestionBox.style.display = 'none'; input.focus();
        }
        
        document.addEventListener('click', function(e) {
            if (!container.contains(e.target) && !suggestionBox.contains(e.target)) { suggestionBox.style.display = 'none'; }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupTagSystem('role-container', 'role-input', 'role-hidden', 'role-suggestions', 'role');
        setupTagSystem('skill-container', 'skill-input', 'skill-hidden', 'skill-suggestions', 'skill');
    });
</script>