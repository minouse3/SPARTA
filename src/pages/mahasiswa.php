<?php
// --- LOGIC PHP ---
$action = $_POST['action'] ?? '';
$view = $_GET['view'] ?? 'list';
$idEdit = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'save_profile') {
            // Update Data
            $sql = "UPDATE Mahasiswa SET Nama_Mahasiswa=?, Email=?, Tempat_Lahir=?, Tanggal_Lahir=?, Bio=?, ID_Prodi=? WHERE ID_Mahasiswa=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['nama'], $_POST['email'], $_POST['tempat_lahir'], $_POST['tgl_lahir'], $_POST['bio'], $_POST['prodi'], $_POST['id_mahasiswa']]);
            
            // Update Skill (Delete all then insert new)
            $pdo->prepare("DELETE FROM Mahasiswa_Keahlian WHERE ID_Mahasiswa=?")->execute([$_POST['id_mahasiswa']]);
            if (!empty($_POST['skills'])) {
                $stmtSkill = $pdo->prepare("INSERT INTO Mahasiswa_Keahlian (ID_Mahasiswa, ID_Keahlian) VALUES (?, ?)");
                foreach($_POST['skills'] as $idSkill) {
                    $stmtSkill->execute([$_POST['id_mahasiswa'], $idSkill]);
                }
            }
            echo "<script>alert('Profil berhasil diupdate!'); window.location.href='?page=mahasiswa&view=detail&id=".$_POST['id_mahasiswa']."';</script>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Data Master Dropdown
$prodiList = $pdo->query("SELECT * FROM Prodi")->fetchAll();
$keahlianList = $pdo->query("SELECT * FROM Keahlian")->fetchAll();

// --- VIEW DETAIL / EDIT ---
if ($view == 'detail' && $idEdit) {
    // Ambil Data Mahasiswa
    $stmt = $pdo->prepare("SELECT m.*, p.Nama_Prodi, p.Fakultas FROM Mahasiswa m LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi WHERE m.ID_Mahasiswa = ?");
    $stmt->execute([$idEdit]);
    $mhs = $stmt->fetch();
    
    // Ambil Skill Mahasiswa
    $stmtSkill = $pdo->prepare("SELECT ID_Keahlian FROM Mahasiswa_Keahlian WHERE ID_Mahasiswa = ?");
    $stmtSkill->execute([$idEdit]);
    $mySkills = $stmtSkill->fetchAll(PDO::FETCH_COLUMN);
    ?>
    
    <div class="mb-3"><a href="?page=mahasiswa" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali ke Data</a></div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-4">
                <div class="mx-auto mb-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 2.5rem;">
                    <?= substr($mhs['Nama_Mahasiswa'], 0, 1) ?>
                </div>
                <h4><?= htmlspecialchars($mhs['Nama_Mahasiswa']) ?></h4>
                <p class="text-muted mb-1"><?= $mhs['NIM'] ?></p>
                <span class="badge bg-info text-dark mb-3"><?= $mhs['Nama_Prodi'] ?></span>
                
                <div class="border-top pt-3 text-start">
                    <small class="text-muted text-uppercase fw-bold">Total Points</small>
                    <h3 class="text-warning fw-bold"><i class="fas fa-trophy me-2"></i><?= number_format($mhs['Total_Poin']) ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="fas fa-user-edit me-2"></i>Edit Biodata Lengkap</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_profile">
                        <input type="hidden" name="id_mahasiswa" value="<?= $mhs['ID_Mahasiswa'] ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($mhs['Nama_Mahasiswa']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($mhs['Email']) ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" class="form-control" value="<?= htmlspecialchars($mhs['Tempat_Lahir'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="tgl_lahir" class="form-control" value="<?= $mhs['Tanggal_Lahir'] ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Program Studi</label>
                            <select name="prodi" class="form-select">
                                <?php foreach($prodiList as $p): ?>
                                    <option value="<?= $p['ID_Prodi'] ?>" <?= $p['ID_Prodi'] == $mhs['ID_Prodi'] ? 'selected' : '' ?>>
                                        <?= $p['Nama_Prodi'] ?> (<?= $p['Fakultas'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bio Singkat</label>
                            <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($mhs['Bio'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Keahlian / Skill</label>
                            <div class="row g-2">
                                <?php foreach($keahlianList as $k): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="skills[]" value="<?= $k['ID_Keahlian'] ?>" id="skill<?= $k['ID_Keahlian'] ?>" <?= in_array($k['ID_Keahlian'], $mySkills) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="skill<?= $k['ID_Keahlian'] ?>"><?= $k['Nama_Keahlian'] ?></label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    // --- VIEW LIST (DENGAN FILTER) ---
    $filterProdi = $_GET['prodi'] ?? '';
    $filterSkill = $_GET['skill'] ?? '';
    $search = $_GET['q'] ?? '';
    
    $sql = "SELECT DISTINCT m.*, p.Nama_Prodi 
            FROM Mahasiswa m 
            LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
            LEFT JOIN Mahasiswa_Keahlian mk ON m.ID_Mahasiswa = mk.ID_Mahasiswa
            WHERE 1=1";
    $params = [];
    
    if ($filterProdi) { $sql .= " AND m.ID_Prodi = ?"; $params[] = $filterProdi; }
    if ($filterSkill) { $sql .= " AND mk.ID_Keahlian = ?"; $params[] = $filterSkill; }
    if ($search) { $sql .= " AND (m.Nama_Mahasiswa LIKE ? OR m.NIM LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    
    $sql .= " ORDER BY m.Nama_Mahasiswa ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $mahasiswaList = $stmt->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="fas fa-user-graduate me-2"></i> Data Mahasiswa</h2>
    </div>

    <div class="row">
        <!-- Sidebar Filter -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-bold"><i class="fas fa-filter me-2"></i>Filter Data</div>
                <div class="card-body">
                    <form method="GET">
                        <input type="hidden" name="page" value="mahasiswa">
                        
                        <div class="mb-3">
                            <label class="form-label small text-muted text-uppercase fw-bold">Pencarian</label>
                            <input type="text" name="q" class="form-control form-control-sm" placeholder="Nama / NIM" value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted text-uppercase fw-bold">Program Studi</label>
                            <select name="prodi" class="form-select form-select-sm">
                                <option value="">Semua Prodi</option>
                                <?php foreach($prodiList as $p): ?>
                                    <option value="<?= $p['ID_Prodi'] ?>" <?= $filterProdi == $p['ID_Prodi'] ? 'selected' : '' ?>><?= $p['Nama_Prodi'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted text-uppercase fw-bold">Keahlian</label>
                            <select name="skill" class="form-select form-select-sm">
                                <option value="">Semua Keahlian</option>
                                <?php foreach($keahlianList as $k): ?>
                                    <option value="<?= $k['ID_Keahlian'] ?>" <?= $filterSkill == $k['ID_Keahlian'] ? 'selected' : '' ?>><?= $k['Nama_Keahlian'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">Terapkan Filter</button>
                        <a href="?page=mahasiswa" class="btn btn-outline-secondary btn-sm w-100 mt-2">Reset</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="col-md-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">NIM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Prodi</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($mahasiswaList)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>
                                <?php endif; ?>
                                
                                <?php foreach($mahasiswaList as $m): ?>
                                <tr onclick="window.location='?page=mahasiswa&view=detail&id=<?= $m['ID_Mahasiswa'] ?>'" style="cursor: pointer;">
                                    <td class="ps-4 fw-bold"><?= $m['NIM'] ?></td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($m['Nama_Mahasiswa']) ?></div>
                                        <small class="text-muted"><?= $m['Email'] ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= $m['Nama_Prodi'] ?></span></td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i> Edit</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>