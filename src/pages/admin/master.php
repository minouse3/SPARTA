<?php
// FILE: Master Data Management (Modern UI)

// Cek Akses Superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || 
    !isset($_SESSION['admin_level']) || $_SESSION['admin_level'] !== 'superadmin') {
    echo "<div class='alert alert-danger'>Akses Ditolak! Halaman ini khusus Super Admin.</div>";
    exit;
}

// LOGIC CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $type = $_POST['type'];
        $action = $_POST['action']; 

        // 1. FAKULTAS
        if ($type === 'fakultas') {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO Fakultas (Nama_Fakultas) VALUES (?)");
                $stmt->execute([$_POST['nama']]);
            } elseif ($action === 'edit') {
                $stmt = $pdo->prepare("UPDATE Fakultas SET Nama_Fakultas = ? WHERE ID_Fakultas = ?");
                $stmt->execute([$_POST['nama'], $_POST['id']]);
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM Fakultas WHERE ID_Fakultas = ?");
                $stmt->execute([$_POST['id']]);
            }
        } 
        // 2. PRODI
        elseif ($type === 'prodi') {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO Prodi (Nama_Prodi, ID_Fakultas) VALUES (?, ?)");
                $stmt->execute([$_POST['nama'], $_POST['id_fakultas']]);
            } elseif ($action === 'edit') {
                $stmt = $pdo->prepare("UPDATE Prodi SET Nama_Prodi = ?, ID_Fakultas = ? WHERE ID_Prodi = ?");
                $stmt->execute([$_POST['nama'], $_POST['id_fakultas'], $_POST['id']]);
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM Prodi WHERE ID_Prodi = ?");
                $stmt->execute([$_POST['id']]);
            }
        }
        // 3. LAINNYA
        elseif ($type === 'kategori') {
            $pdo->prepare("INSERT INTO Kategori_Lomba (Nama_Kategori) VALUES (?)")->execute([$_POST['nama']]);
        } elseif ($type === 'penyelenggara') {
            $pdo->prepare("INSERT INTO Jenis_Penyelenggara (Nama_Jenis, Bobot_Poin) VALUES (?, ?)")->execute([$_POST['nama'], $_POST['bobot']]);
        } elseif ($type === 'skill') {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Skill WHERE Nama_Skill = ?");
            $cek->execute([trim($_POST['nama'])]);
            if ($cek->fetchColumn() == 0) $pdo->prepare("INSERT INTO Skill (Nama_Skill) VALUES (?)")->execute([trim($_POST['nama'])]);
        } elseif ($type === 'role') {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Role_Tim WHERE Nama_Role = ?");
            $cek->execute([trim($_POST['nama'])]);
            if ($cek->fetchColumn() == 0) $pdo->prepare("INSERT INTO Role_Tim (Nama_Role) VALUES (?)")->execute([trim($_POST['nama'])]);
        }
        
        echo "<script>window.location.href='?page=master&status=success';</script>";
        exit;

    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// QUERY DATA
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiList = $pdo->query("SELECT p.*, f.Nama_Fakultas FROM Prodi p LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas ORDER BY p.Nama_Prodi ASC")->fetchAll();
$kategoris = $pdo->query("SELECT * FROM Kategori_Lomba ORDER BY Nama_Kategori ASC")->fetchAll();
$penyelenggaras = $pdo->query("SELECT * FROM Jenis_Penyelenggara")->fetchAll();
$skills = $pdo->query("SELECT * FROM Skill ORDER BY Nama_Skill ASC")->fetchAll();
$roles = $pdo->query("SELECT * FROM Role_Tim ORDER BY Nama_Role ASC")->fetchAll();

// Grouping Prodi
$prodiGrouped = [];
foreach ($prodiList as $p) {
    $fId = $p['ID_Fakultas'] ?? 0;
    $prodiGrouped[$fId][] = $p;
}
?>

<style>
    .master-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .master-header {
        padding: 15px 20px;
        border-radius: 12px 12px 0 0;
        color: white;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .scroll-list {
        max-height: 250px;
        overflow-y: auto;
        flex-grow: 1;
    }
    /* Scrollbar Tipis */
    .scroll-list::-webkit-scrollbar { width: 5px; }
    .scroll-list::-webkit-scrollbar-track { background: #f1f1f1; }
    .scroll-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 5px; }
    
    .list-group-item { border-left: none; border-right: none; }
    .btn-xs { padding: 2px 6px; font-size: 0.75rem; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Master Data</h3>
        <p class="text-muted mb-0">Pusat pengaturan data referensi sistem.</p>
    </div>
    <?php if(isset($_GET['status'])): ?>
        <div class="alert alert-success py-2 px-3 mb-0 border-0 shadow-sm rounded-pill">
            <i class="fas fa-check-circle me-1"></i> Data berhasil disimpan
        </div>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-5">
        <div class="master-card bg-white">
            <div class="master-header bg-dark">
                <span><i class="fas fa-university me-2"></i>Fakultas</span>
                <button class="btn btn-sm btn-light text-dark fw-bold rounded-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#addFakultasModal" title="Tambah">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush scroll-list">
                    <?php foreach($fakultasList as $f): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="fw-bold text-dark"><?= htmlspecialchars($f['Nama_Fakultas']) ?></span>
                            <div>
                                <button class="btn btn-xs btn-outline-warning rounded-circle me-1" onclick="editFakultas(<?= $f['ID_Fakultas'] ?>, '<?= addslashes($f['Nama_Fakultas']) ?>')"><i class="fas fa-pen"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus Fakultas ini? Data Prodi terkait akan kehilangan induk.')">
                                    <input type="hidden" name="type" value="fakultas">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $f['ID_Fakultas'] ?>">
                                    <button class="btn btn-xs btn-outline-danger rounded-circle"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="master-card bg-white">
            <div class="master-header bg-secondary">
                <span><i class="fas fa-graduation-cap me-2"></i>Program Studi</span>
                <button class="btn btn-sm btn-light text-dark fw-bold rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addProdiModal">
                    <i class="fas fa-plus me-1"></i> Tambah Prodi
                </button>
            </div>
            <div class="card-body p-3 bg-light rounded-bottom">
                <div class="accordion scroll-list" id="accordionProdi" style="max-height: 400px;">
                    <?php foreach($fakultasList as $f): ?>
                    <div class="accordion-item border-0 mb-2 shadow-sm rounded overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-3 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $f['ID_Fakultas'] ?>">
                                <?= $f['Nama_Fakultas'] ?>
                                <span class="badge bg-light text-dark ms-2"><?= isset($prodiGrouped[$f['ID_Fakultas']]) ? count($prodiGrouped[$f['ID_Fakultas']]) : 0 ?></span>
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
                                                    <button class="btn btn-xs btn-link text-warning p-0 me-2" onclick="editProdi(<?= $p['ID_Prodi'] ?>, '<?= addslashes($p['Nama_Prodi']) ?>', <?= $f['ID_Fakultas'] ?>)"><i class="fas fa-pencil-alt"></i></button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus Prodi ini?')">
                                                        <input type="hidden" name="type" value="prodi">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $p['ID_Prodi'] ?>">
                                                        <button class="btn btn-xs btn-link text-danger p-0"><i class="fas fa-times"></i></button>
                                                    </form>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-muted small ps-4">Belum ada prodi.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="master-card bg-white">
            <div class="master-header bg-primary">Lomba</div>
            <div class="card-body p-3 d-flex flex-column">
                <ul class="list-group list-group-flush scroll-list mb-3">
                    <?php foreach($kategoris as $k): ?><li class="list-group-item py-1"><?= $k['Nama_Kategori'] ?></li><?php endforeach; ?>
                </ul>
                <form method="POST" class="mt-auto d-flex">
                    <input type="hidden" name="type" value="kategori">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="nama" class="form-control form-control-sm me-1" placeholder="Baru..." required>
                    <button class="btn btn-sm btn-primary"><i class="fas fa-plus"></i></button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="master-card bg-white">
            <div class="master-header bg-warning text-dark">Penyelenggara</div>
            <div class="card-body p-3 d-flex flex-column">
                <ul class="list-group list-group-flush scroll-list mb-3">
                    <?php foreach($penyelenggaras as $p): ?>
                        <li class="list-group-item py-1 d-flex justify-content-between">
                            <span><?= $p['Nama_Jenis'] ?></span>
                            <span class="badge bg-secondary">x<?= $p['Bobot_Poin'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" class="mt-auto">
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

    <div class="col-md-3">
        <div class="master-card bg-white">
            <div class="master-header bg-success">Skill (Tools)</div>
            <div class="card-body p-3 d-flex flex-column">
                <ul class="list-group list-group-flush scroll-list mb-3">
                    <?php foreach($skills as $s): ?><li class="list-group-item py-1"><?= $s['Nama_Skill'] ?></li><?php endforeach; ?>
                </ul>
                <form method="POST" class="mt-auto d-flex">
                    <input type="hidden" name="type" value="skill">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="nama" class="form-control form-control-sm me-1" placeholder="Cth: Python..." required>
                    <button class="btn btn-sm btn-success"><i class="fas fa-plus"></i></button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="master-card bg-white">
            <div class="master-header bg-info text-white">Role (Profesi)</div>
            <div class="card-body p-3 d-flex flex-column">
                <ul class="list-group list-group-flush scroll-list mb-3">
                    <?php foreach($roles as $r): ?><li class="list-group-item py-1"><?= $r['Nama_Role'] ?></li><?php endforeach; ?>
                </ul>
                <form method="POST" class="mt-auto d-flex">
                    <input type="hidden" name="type" value="role">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="nama" class="form-control form-control-sm me-1" placeholder="Cth: Designer..." required>
                    <button class="btn btn-sm btn-info text-white"><i class="fas fa-plus"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addFakultasModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white border-0 py-2">
                <h6 class="modal-title mb-0">Tambah Fakultas</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="type" value="fakultas">
                <input type="hidden" name="action" value="add">
                <label class="small fw-bold mb-1">Nama Fakultas</label>
                <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-dark btn-sm w-100 rounded-pill">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editFakultasModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0 py-2">
                <h6 class="modal-title mb-0">Edit Fakultas</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="type" value="fakultas">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editFakultasId">
                <label class="small fw-bold mb-1">Nama Fakultas</label>
                <input type="text" name="nama" id="editFakultasName" class="form-control" required>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-warning btn-sm w-100 rounded-pill fw-bold">Update</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addProdiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white border-0">
                <h6 class="modal-title mb-0">Tambah Prodi</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="type" value="prodi">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Fakultas Induk</label>
                    <select name="id_fakultas" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach($fakultasList as $f): ?>
                            <option value="<?= $f['ID_Fakultas'] ?>"><?= $f['Nama_Fakultas'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Nama Prodi</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <button class="btn btn-secondary w-100 rounded-pill">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editProdiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h6 class="modal-title mb-0">Edit Prodi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="type" value="prodi">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editProdiId">
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Fakultas</label>
                    <select name="id_fakultas" id="editProdiFakultas" class="form-select" required>
                        <?php foreach($fakultasList as $f): ?>
                            <option value="<?= $f['ID_Fakultas'] ?>"><?= $f['Nama_Fakultas'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Nama Prodi</label>
                    <input type="text" name="nama" id="editProdiName" class="form-control" required>
                </div>
                <button class="btn btn-warning w-100 rounded-pill fw-bold">Update</button>
            </div>
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