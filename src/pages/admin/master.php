<?php
// Letakkan di paling atas master.php dan sql_console.php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || 
    !isset($_SESSION['admin_level']) || $_SESSION['admin_level'] !== 'superadmin') {
    header("Location: error.php?code=403"); // Atau redirect ke dashboard
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $type = $_POST['type'];
        $action = $_POST['action']; // add, edit, delete

        // 1. LOGIC FAKULTAS
        if ($type === 'fakultas') {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO Fakultas (Nama_Fakultas) VALUES (?)");
                $stmt->execute([$_POST['nama']]);
                echo "<div class='alert alert-success'>Fakultas berhasil ditambahkan!</div>";
            } elseif ($action === 'edit') {
                $stmt = $pdo->prepare("UPDATE Fakultas SET Nama_Fakultas = ? WHERE ID_Fakultas = ?");
                $stmt->execute([$_POST['nama'], $_POST['id']]);
                echo "<div class='alert alert-success'>Nama Fakultas berhasil diupdate!</div>";
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM Fakultas WHERE ID_Fakultas = ?");
                $stmt->execute([$_POST['id']]);
                echo "<div class='alert alert-success'>Fakultas berhasil dihapus!</div>";
            }
        } 
        // 2. LOGIC PRODI
        elseif ($type === 'prodi') {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO Prodi (Nama_Prodi, ID_Fakultas) VALUES (?, ?)");
                $stmt->execute([$_POST['nama'], $_POST['id_fakultas']]);
                echo "<div class='alert alert-success'>Prodi berhasil ditambahkan!</div>";
            } elseif ($action === 'edit') {
                $stmt = $pdo->prepare("UPDATE Prodi SET Nama_Prodi = ?, ID_Fakultas = ? WHERE ID_Prodi = ?");
                $stmt->execute([$_POST['nama'], $_POST['id_fakultas'], $_POST['id']]);
                echo "<div class='alert alert-success'>Prodi berhasil diupdate!</div>";
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM Prodi WHERE ID_Prodi = ?");
                $stmt->execute([$_POST['id']]);
                echo "<div class='alert alert-success'>Prodi berhasil dihapus!</div>";
            }
        }
        // 3. LOGIC LAINNYA (Kategori, Penyelenggara)
        elseif ($type === 'kategori') {
            $stmt = $pdo->prepare("INSERT INTO Kategori_Lomba (Nama_Kategori) VALUES (?)");
            $stmt->execute([$_POST['nama']]);
            echo "<div class='alert alert-success'>Kategori berhasil ditambahkan!</div>";
        } elseif ($type === 'penyelenggara') {
            $stmt = $pdo->prepare("INSERT INTO Jenis_Penyelenggara (Nama_Jenis, Bobot_Poin) VALUES (?, ?)");
            $stmt->execute([$_POST['nama'], $_POST['bobot']]);
            echo "<div class='alert alert-success'>Penyelenggara berhasil ditambahkan!</div>";
        }
        // 4. LOGIC BARU: SKILL (TOOLS)
        elseif ($type === 'skill') {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Skill WHERE Nama_Skill = ?");
            $cek->execute([trim($_POST['nama'])]);
            if ($cek->fetchColumn() == 0) {
                $stmt = $pdo->prepare("INSERT INTO Skill (Nama_Skill) VALUES (?)");
                $stmt->execute([trim($_POST['nama'])]);
                echo "<div class='alert alert-success'>Skill (Tool) berhasil ditambahkan!</div>";
            }
        }
        // 5. LOGIC BARU: ROLE (PROFESI)
        elseif ($type === 'role') {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Role_Tim WHERE Nama_Role = ?");
            $cek->execute([trim($_POST['nama'])]);
            if ($cek->fetchColumn() == 0) {
                $stmt = $pdo->prepare("INSERT INTO Role_Tim (Nama_Role) VALUES (?)");
                $stmt->execute([trim($_POST['nama'])]);
                echo "<div class='alert alert-success'>Role (Profesi) berhasil ditambahkan!</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// --- QUERY DATA ---
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiList = $pdo->query("SELECT p.*, f.Nama_Fakultas FROM Prodi p LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas ORDER BY p.Nama_Prodi ASC")->fetchAll();
$kategoris = $pdo->query("SELECT * FROM Kategori_Lomba ORDER BY Nama_Kategori ASC")->fetchAll();
$penyelenggaras = $pdo->query("SELECT * FROM Jenis_Penyelenggara")->fetchAll();

// DATA BARU
$skills = $pdo->query("SELECT * FROM Skill ORDER BY Nama_Skill ASC")->fetchAll();
$roles = $pdo->query("SELECT * FROM Role_Tim ORDER BY Nama_Role ASC")->fetchAll();

// Grouping Prodi by Fakultas ID for Accordion
$prodiGrouped = [];
foreach ($prodiList as $p) {
    $fId = $p['ID_Fakultas'] ?? 0; // 0 for No Fakultas
    $prodiGrouped[$fId][] = $p;
}
?>

<h2 class="mb-4 fw-bold text-dark"><i class="fas fa-cogs me-2"></i> Master Data System</h2>

<div class="row mb-4">
    <div class="col-md-5">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-university me-2"></i>Data Fakultas</span>
                <button class="btn btn-sm btn-light text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#addFakultasModal"><i class="fas fa-plus"></i></button>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach($fakultasList as $f): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><?= htmlspecialchars($f['Nama_Fakultas']) ?></span>
                            <div>
                                <button class="btn btn-sm btn-outline-warning border-0" 
                                        onclick="editFakultas(<?= $f['ID_Fakultas'] ?>, '<?= addslashes($f['Nama_Fakultas']) ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus Fakultas ini? Prodi didalamnya akan kehilangan induk fakultas.')">
                                    <input type="hidden" name="type" value="fakultas">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $f['ID_Fakultas'] ?>">
                                    <button class="btn btn-sm btn-outline-danger border-0"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if(empty($fakultasList)): ?>
                    <div class="p-3 text-muted text-center small">Belum ada data fakultas.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-secondary text-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-graduation-cap me-2"></i>Data Program Studi</span>
                <button class="btn btn-sm btn-light text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#addProdiModal"><i class="fas fa-plus"></i> Tambah Prodi</button>
            </div>
            <div class="card-body p-3 bg-light">
                
                <div class="accordion" id="accordionProdi">
                    
                    <?php foreach($fakultasList as $f): ?>
                    <div class="accordion-item shadow-sm mb-2 border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $f['ID_Fakultas'] ?>">
                                <i class="fas fa-building me-2 text-primary"></i> <?= $f['Nama_Fakultas'] ?>
                                <span class="badge bg-light text-dark border ms-2"><?= isset($prodiGrouped[$f['ID_Fakultas']]) ? count($prodiGrouped[$f['ID_Fakultas']]) : 0 ?></span>
                            </button>
                        </h2>
                        <div id="collapse<?= $f['ID_Fakultas'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionProdi">
                            <div class="accordion-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php if(isset($prodiGrouped[$f['ID_Fakultas']])): ?>
                                        <?php foreach($prodiGrouped[$f['ID_Fakultas']] as $p): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center bg-white ps-4">
                                                <span><i class="fas fa-angle-right me-2 text-muted"></i><?= htmlspecialchars($p['Nama_Prodi']) ?></span>
                                                <div>
                                                    <button class="btn btn-sm btn-link text-warning p-0 me-2" 
                                                            onclick="editProdi(<?= $p['ID_Prodi'] ?>, '<?= addslashes($p['Nama_Prodi']) ?>', <?= $f['ID_Fakultas'] ?>)">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus Prodi ini?')">
                                                        <input type="hidden" name="type" value="prodi">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $p['ID_Prodi'] ?>">
                                                        <button class="btn btn-sm btn-link text-danger p-0"><i class="fas fa-times"></i></button>
                                                    </form>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-muted small fst-italic ps-4">Kosong</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if(isset($prodiGrouped[0])): ?>
                    <div class="accordion-item shadow-sm border-danger">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold text-danger" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUnassigned">
                                <i class="fas fa-exclamation-circle me-2"></i> Belum Ada Fakultas
                                <span class="badge bg-danger ms-2"><?= count($prodiGrouped[0]) ?></span>
                            </button>
                        </h2>
                        <div id="collapseUnassigned" class="accordion-collapse collapse" data-bs-parent="#accordionProdi">
                            <div class="accordion-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php foreach($prodiGrouped[0] as $p): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center ps-4">
                                            <?= htmlspecialchars($p['Nama_Prodi']) ?>
                                            <button class="btn btn-sm btn-link text-warning p-0" onclick="editProdi(<?= $p['ID_Prodi'] ?>, '<?= addslashes($p['Nama_Prodi']) ?>', '')">Edit</button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
                </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-primary text-white">Kategori Lomba</div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3" style="max-height: 200px; overflow-y:auto;">
                    <?php foreach($kategoris as $k): ?>
                        <li class="list-group-item py-1"><?= $k['Nama_Kategori'] ?></li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" class="d-flex">
                    <input type="hidden" name="type" value="kategori">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="nama" class="form-control form-control-sm me-2" placeholder="Baru..." required>
                    <button class="btn btn-sm btn-primary"><i class="fas fa-plus"></i></button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-warning text-dark">Penyelenggara</div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3" style="max-height: 200px; overflow-y:auto;">
                    <?php foreach($penyelenggaras as $p): ?>
                        <li class="list-group-item py-1 d-flex justify-content-between">
                            <span><?= $p['Nama_Jenis'] ?></span>
                            <span class="badge bg-secondary">x<?= $p['Bobot_Poin'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST">
                    <input type="hidden" name="type" value="penyelenggara">
                    <input type="hidden" name="action" value="add">
                    <div class="input-group input-group-sm">
                        <input type="text" name="nama" class="form-control" placeholder="Jenis..." required>
                        <input type="number" step="0.1" name="bobot" class="form-control" placeholder="Bobot" style="max-width: 50px;" required>
                        <button class="btn btn-warning"><i class="fas fa-plus"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-success text-white">Skill (Tools)</div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3" style="max-height: 200px; overflow-y:auto;">
                    <?php foreach($skills as $s): ?>
                        <li class="list-group-item py-1"><?= $s['Nama_Skill'] ?></li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" class="d-flex">
                    <input type="hidden" name="type" value="skill">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="nama" class="form-control form-control-sm me-2" placeholder="Cth: Python..." required>
                    <button class="btn btn-sm btn-success"><i class="fas fa-plus"></i></button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-info text-white">Role (Profesi)</div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3" style="max-height: 200px; overflow-y:auto;">
                    <?php foreach($roles as $r): ?>
                        <li class="list-group-item py-1"><?= $r['Nama_Role'] ?></li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" class="d-flex">
                    <input type="hidden" name="type" value="role">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="nama" class="form-control form-control-sm me-2" placeholder="Cth: Designer..." required>
                    <button class="btn btn-sm btn-info text-white"><i class="fas fa-plus"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addFakultasModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-dark text-white"><h6 class="modal-title">Tambah Fakultas</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="type" value="fakultas">
                <input type="hidden" name="action" value="add">
                <label class="form-label small">Nama Fakultas</label>
                <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="modal-footer"><button class="btn btn-dark btn-sm w-100">Simpan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editFakultasModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-warning text-dark"><h6 class="modal-title">Edit Fakultas</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="type" value="fakultas">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editFakultasId">
                <label class="form-label small">Nama Fakultas</label>
                <input type="text" name="nama" id="editFakultasName" class="form-control" required>
            </div>
            <div class="modal-footer"><button class="btn btn-warning btn-sm w-100 fw-bold">Update</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="addProdiModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-secondary text-white"><h6 class="modal-title">Tambah Prodi</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="type" value="prodi">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label small">Fakultas</label>
                    <select name="id_fakultas" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach($fakultasList as $f): ?>
                            <option value="<?= $f['ID_Fakultas'] ?>"><?= $f['Nama_Fakultas'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Nama Prodi</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary btn-sm w-100">Simpan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editProdiModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-warning text-dark"><h6 class="modal-title">Edit Prodi</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="type" value="prodi">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editProdiId">
                
                <div class="mb-3">
                    <label class="form-label small">Fakultas</label>
                    <select name="id_fakultas" id="editProdiFakultas" class="form-select" required>
                        <?php foreach($fakultasList as $f): ?>
                            <option value="<?= $f['ID_Fakultas'] ?>"><?= $f['Nama_Fakultas'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Nama Prodi</label>
                    <input type="text" name="nama" id="editProdiName" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-warning btn-sm w-100 fw-bold">Update</button></div>
        </form>
    </div>
</div>

<script>
function editFakultas(id, nama) {
    document.getElementById('editFakultasId').value = id;
    document.getElementById('editFakultasName').value = nama;
    new bootstrap.Modal(document.getElementById('editFakultasModal')).show();
}

function editProdi(id, nama, idFakultas) {
    document.getElementById('editProdiId').value = id;
    document.getElementById('editProdiName').value = nama;
    document.getElementById('editProdiFakultas').value = idFakultas;
    new bootstrap.Modal(document.getElementById('editProdiModal')).show();
}
</script>