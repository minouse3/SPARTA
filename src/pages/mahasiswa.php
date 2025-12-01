<?php
// --- LOGIC PHP ---
$action = $_POST['action'] ?? '';
$view = $_GET['view'] ?? 'list';
$idEdit = $_GET['id'] ?? 0;

// HANDLE POST REQUESTS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. TAMBAH MAHASISWA
        if ($action === 'add_mahasiswa') {
            $passHash = password_hash($_POST['nim'], PASSWORD_DEFAULT); 
            $stmt = $pdo->prepare("INSERT INTO Mahasiswa (NIM, Nama_Mahasiswa, Email, Password_Hash, ID_Prodi) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['nim'], $_POST['nama'], $_POST['email'], $passHash, $_POST['prodi']]);
            echo "<div class='alert alert-success'>Mahasiswa berhasil ditambahkan!</div>";
        } 
        // 2. SAVE PROFILE (EDIT)
        elseif ($action === 'save_profile') {
            $sql = "UPDATE Mahasiswa SET Nama_Mahasiswa=?, Email=?, Tempat_Lahir=?, Tanggal_Lahir=?, Bio=?, ID_Prodi=? WHERE ID_Mahasiswa=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['nama'], $_POST['email'], $_POST['tempat_lahir'], $_POST['tgl_lahir'], $_POST['bio'], $_POST['prodi'], $_POST['id_mahasiswa']]);
            
            // Update Skill
            $pdo->prepare("DELETE FROM Mahasiswa_Keahlian WHERE ID_Mahasiswa=?")->execute([$_POST['id_mahasiswa']]);
            if (!empty($_POST['skills'])) {
                $stmtSkill = $pdo->prepare("INSERT INTO Mahasiswa_Keahlian (ID_Mahasiswa, ID_Keahlian) VALUES (?, ?)");
                foreach($_POST['skills'] as $idSkill) {
                    $stmtSkill->execute([$_POST['id_mahasiswa'], $idSkill]);
                }
            }
            echo "<script>alert('Profil berhasil diupdate!'); window.location.href='?page=mahasiswa&view=detail&id=".$_POST['id_mahasiswa']."';</script>";
        }
        // 3. SINGLE DELETE
        elseif ($action === 'delete_single') {
            $stmt = $pdo->prepare("DELETE FROM Mahasiswa WHERE ID_Mahasiswa = ?");
            $stmt->execute([$_POST['id']]);
            echo "<div class='alert alert-success'>Data mahasiswa berhasil dihapus.</div>";
        }
        // 4. BULK DELETE
        elseif ($action === 'delete_bulk') {
            if (!empty($_POST['ids'])) {
                $ids = implode(',', array_map('intval', $_POST['ids']));
                $pdo->exec("DELETE FROM Mahasiswa WHERE ID_Mahasiswa IN ($ids)");
                echo "<div class='alert alert-success'>".count($_POST['ids'])." data mahasiswa berhasil dihapus.</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// AMBIL DATA MASTER UNTUK JS & DROPDOWN
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiAll = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();
$keahlianList = $pdo->query("SELECT * FROM Keahlian")->fetchAll();
$jsonProdi = json_encode($prodiAll);

// --- VIEW DETAIL / EDIT ---
if ($view == 'detail' && $idEdit) {
    // Logic Detail (Tidak berubah)
    $sqlDetail = "SELECT m.*, p.Nama_Prodi, p.ID_Fakultas, f.Nama_Fakultas 
                  FROM Mahasiswa m 
                  LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
                  LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas 
                  WHERE m.ID_Mahasiswa = ?";
    $stmt = $pdo->prepare($sqlDetail);
    $stmt->execute([$idEdit]);
    $mhs = $stmt->fetch();
    
    $stmtSkill = $pdo->prepare("SELECT ID_Keahlian FROM Mahasiswa_Keahlian WHERE ID_Mahasiswa = ?");
    $stmtSkill->execute([$idEdit]);
    $mySkills = $stmtSkill->fetchAll(PDO::FETCH_COLUMN);
    ?>
    
    <div class="mb-3"><a href="?page=mahasiswa" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali</a></div>
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-4">
                <div class="mx-auto mb-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 2.5rem;"><?= substr($mhs['Nama_Mahasiswa'], 0, 1) ?></div>
                <h4><?= htmlspecialchars($mhs['Nama_Mahasiswa']) ?></h4>
                <p class="text-muted mb-1"><?= $mhs['NIM'] ?></p>
                <span class="badge bg-info text-dark"><?= $mhs['Nama_Prodi'] ?></span>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Edit Biodata</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_profile">
                        <input type="hidden" name="id_mahasiswa" value="<?= $mhs['ID_Mahasiswa'] ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6"><label>Nama</label><input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($mhs['Nama_Mahasiswa']) ?>" required></div>
                            <div class="col-md-6"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($mhs['Email']) ?>" required></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><label>Tempat Lahir</label><input type="text" name="tempat_lahir" class="form-control" value="<?= htmlspecialchars($mhs['Tempat_Lahir'] ?? '') ?>"></div>
                            <div class="col-md-6"><label>Tgl Lahir</label><input type="date" name="tgl_lahir" class="form-control" value="<?= $mhs['Tanggal_Lahir'] ?>"></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Fakultas</label>
                                <select id="editFakultas" class="form-select" onchange="populateProdi('editFakultas', 'editProdi')">
                                    <option value="">-- Pilih Fakultas --</option>
                                    <?php foreach($fakultasList as $f): ?>
                                        <option value="<?= $f['ID_Fakultas'] ?>" <?= ($mhs['ID_Fakultas'] == $f['ID_Fakultas']) ? 'selected' : '' ?>>
                                            <?= $f['Nama_Fakultas'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Program Studi</label>
                                <select name="prodi" id="editProdi" class="form-select" required>
                                    <option value="">-- Pilih Fakultas Dahulu --</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3"><label>Bio</label><textarea name="bio" class="form-control"><?= htmlspecialchars($mhs['Bio'] ?? '') ?></textarea></div>
                        <div class="mb-3">
                            <label class="d-block">Skill</label>
                            <div class="row g-2">
                                <?php foreach($keahlianList as $k): ?>
                                <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="<?= $k['ID_Keahlian'] ?>" <?= in_array($k['ID_Keahlian'], $mySkills) ? 'checked' : '' ?>><label class="form-check-label"><?= $k['Nama_Keahlian'] ?></label></div></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="text-end"><button type="submit" class="btn btn-primary">Simpan</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            populateProdi('editFakultas', 'editProdi', <?= $mhs['ID_Prodi'] ?>);
        });
    </script>

<?php } else { 
    // --- VIEW LIST ---
    $filterProdi = $_GET['prodi'] ?? '';
    $search = $_GET['q'] ?? '';
    
    $sql = "SELECT m.*, p.Nama_Prodi, f.Nama_Fakultas 
            FROM Mahasiswa m 
            LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
            LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
            WHERE 1=1";
    $params = [];
    if ($filterProdi) { $sql .= " AND m.ID_Prodi = ?"; $params[] = $filterProdi; }
    if ($search) { $sql .= " AND (m.Nama_Mahasiswa LIKE ? OR m.NIM LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    $sql .= " ORDER BY m.Nama_Mahasiswa ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $mahasiswaList = $stmt->fetchAll();
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="fas fa-user-graduate me-2"></i> Data Mahasiswa</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMhsModal"><i class="fas fa-plus"></i> Baru</button>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <form method="GET">
                        <input type="hidden" name="page" value="mahasiswa">
                        <div class="mb-3"><label class="small fw-bold">Cari</label><input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($search) ?>"></div>
                        <div class="mb-3">
                            <label class="small fw-bold">Filter Prodi</label>
                            <select name="prodi" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <?php foreach($prodiAll as $p): ?>
                                    <option value="<?= $p['ID_Prodi'] ?>" <?= $filterProdi == $p['ID_Prodi'] ? 'selected' : '' ?>><?= $p['Nama_Prodi'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <form method="POST" id="bulkForm">
                <input type="hidden" name="action" value="delete_bulk">
                
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                        <span class="fw-bold">List Mahasiswa</span>
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data yang dipilih?')">
                            <i class="fas fa-trash-alt me-1"></i> Hapus Terpilih
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3" style="width: 40px;"><input type="checkbox" class="form-check-input" onclick="toggleAll(this)"></th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <th>Fakultas</th> <th>Prodi</th>    <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($mahasiswaList as $m): ?>
                                    <tr>
                                        <td class="ps-3"><input type="checkbox" name="ids[]" value="<?= $m['ID_Mahasiswa'] ?>" class="form-check-input"></td>
                                        <td class="fw-bold"><?= $m['NIM'] ?></td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($m['Nama_Mahasiswa']) ?></div>
                                            <small class="text-muted"><?= $m['Email'] ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($m['Nama_Fakultas']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= $m['Nama_Prodi'] ?></span></td>
                                        
                                        <td class="text-end pe-4">
                                            <a href="?page=mahasiswa&view=detail&id=<?= $m['ID_Mahasiswa'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSingle(<?= $m['ID_Mahasiswa'] ?>)"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <form id="deleteSingleForm" method="POST" style="display:none;"><input type="hidden" name="action" value="delete_single"><input type="hidden" name="id" id="deleteId"></form>

    <div class="modal fade" id="addMhsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white"><h5 class="modal-title">Tambah Mahasiswa</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_mahasiswa">
                        <div class="mb-3"><label>NIM</label><input type="text" name="nim" class="form-control" required></div>
                        <div class="mb-3"><label>Nama</label><input type="text" name="nama" class="form-control" required></div>
                        <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                        
                        <div class="mb-3">
                            <label>Fakultas</label>
                            <select id="addFakultas" class="form-select" onchange="populateProdi('addFakultas', 'addProdi')" required>
                                <option value="">-- Pilih Fakultas --</option>
                                <?php foreach($fakultasList as $f): ?>
                                    <option value="<?= $f['ID_Fakultas'] ?>"><?= $f['Nama_Fakultas'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Program Studi</label>
                            <select name="prodi" id="addProdi" class="form-select" required>
                                <option value="">-- Pilih Fakultas Dahulu --</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

<?php } ?>

<script>
const prodiData = <?= $jsonProdi ?>;
function populateProdi(fakultasSelectId, prodiSelectId, selectedValue = null) {
    const fakId = document.getElementById(fakultasSelectId).value;
    const prodiSelect = document.getElementById(prodiSelectId);
    prodiSelect.innerHTML = '<option value="">-- Pilih Prodi --</option>';
    if (fakId) {
        const filtered = prodiData.filter(p => p.ID_Fakultas == fakId);
        filtered.forEach(p => {
            const option = document.createElement('option');
            option.value = p.ID_Prodi;
            option.text = p.Nama_Prodi;
            if (selectedValue && p.ID_Prodi == selectedValue) option.selected = true;
            prodiSelect.appendChild(option);
        });
        if (filtered.length === 0) prodiSelect.innerHTML = '<option value="">Tidak ada prodi di fakultas ini</option>';
    } else {
        prodiSelect.innerHTML = '<option value="">-- Pilih Fakultas Dahulu --</option>';
    }
}
function toggleAll(source) {
    checkboxes = document.getElementsByName('ids[]');
    for(var i=0, n=checkboxes.length;i<n;i++) { checkboxes[i].checked = source.checked; }
}
function deleteSingle(id) {
    if(confirm('Hapus data ini secara permanen?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteSingleForm').submit();
    }
}
</script>