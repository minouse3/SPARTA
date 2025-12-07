<?php
// FILE: src/pages/admin/master.php (Isolasi Style CSS)

// 1. SECURITY CHECK (SUPERADMIN ONLY)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || 
    !isset($_SESSION['admin_level']) || $_SESSION['admin_level'] !== 'superadmin') {
    echo "<div class='alert alert-danger border-0 shadow-sm'>
            <i class='fas fa-user-shield me-2'></i>Akses Ditolak! Halaman ini khusus Super Admin.
          </div>";
    exit;
}

// 2. HANDLE POST REQUEST (CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $type = $_POST['type'];
        $action = $_POST['action']; 

        // --- FAKULTAS ---
        if ($type === 'fakultas') {
            if ($action === 'add') {
                $pdo->prepare("INSERT INTO Fakultas (Nama_Fakultas) VALUES (?)")->execute([$_POST['nama']]);
            } elseif ($action === 'edit') {
                $pdo->prepare("UPDATE Fakultas SET Nama_Fakultas = ? WHERE ID_Fakultas = ?")->execute([$_POST['nama'], $_POST['id']]);
            } elseif ($action === 'delete') {
                $pdo->prepare("DELETE FROM Fakultas WHERE ID_Fakultas = ?")->execute([$_POST['id']]);
            }
        } 
        // --- PRODI ---
        elseif ($type === 'prodi') {
            if ($action === 'add') {
                $pdo->prepare("INSERT INTO Prodi (Nama_Prodi, ID_Fakultas) VALUES (?, ?)")->execute([$_POST['nama'], $_POST['id_fakultas']]);
            } elseif ($action === 'edit') {
                $pdo->prepare("UPDATE Prodi SET Nama_Prodi = ?, ID_Fakultas = ? WHERE ID_Prodi = ?")->execute([$_POST['nama'], $_POST['id_fakultas'], $_POST['id']]);
            } elseif ($action === 'delete') {
                $pdo->prepare("DELETE FROM Prodi WHERE ID_Prodi = ?")->execute([$_POST['id']]);
            }
        }
        // --- SKILL ---
        elseif ($type === 'skill') {
            if ($action === 'add') {
                $pdo->prepare("INSERT INTO Skill (Nama_Skill) VALUES (?)")->execute([trim($_POST['nama'])]);
            } elseif ($action === 'delete') {
                $pdo->prepare("DELETE FROM Skill WHERE ID_Skill = ?")->execute([$_POST['id']]);
            }
        }
        // --- ROLE ---
        elseif ($type === 'role') {
            if ($action === 'add') {
                $pdo->prepare("INSERT INTO Role_Tim (Nama_Role) VALUES (?)")->execute([trim($_POST['nama'])]);
            } elseif ($action === 'delete') {
                $pdo->prepare("DELETE FROM Role_Tim WHERE ID_Role = ?")->execute([$_POST['id']]);
            }
        }
        // --- KATEGORI LOMBA ---
        elseif ($type === 'kategori') {
            if ($action === 'add') {
                $pdo->prepare("INSERT INTO Kategori_Lomba (Nama_Kategori) VALUES (?)")->execute([$_POST['nama']]);
            } elseif ($action === 'delete') {
                $pdo->prepare("DELETE FROM Kategori_Lomba WHERE ID_Kategori = ?")->execute([$_POST['id']]);
            }
        }
        // --- PENYELENGGARA ---
        elseif ($type === 'penyelenggara') {
            if ($action === 'add') {
                $pdo->prepare("INSERT INTO Jenis_Penyelenggara (Nama_Jenis, Bobot_Poin) VALUES (?, ?)")->execute([$_POST['nama'], $_POST['bobot']]);
            } elseif ($action === 'delete') {
                $pdo->prepare("DELETE FROM Jenis_Penyelenggara WHERE ID_Jenis = ?")->execute([$_POST['id']]);
            }
        }
        
        echo "<script>window.location.href='?page=master&status=success';</script>";
        exit;

    } catch (Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

// 3. FETCH DATA
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiList = $pdo->query("SELECT p.*, f.Nama_Fakultas FROM Prodi p LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas ORDER BY f.Nama_Fakultas, p.Nama_Prodi ASC")->fetchAll();
$skills = $pdo->query("SELECT * FROM Skill ORDER BY Nama_Skill ASC")->fetchAll();
$roles = $pdo->query("SELECT * FROM Role_Tim ORDER BY Nama_Role ASC")->fetchAll();
$kategoris = $pdo->query("SELECT * FROM Kategori_Lomba ORDER BY Nama_Kategori ASC")->fetchAll();
$penyelenggaras = $pdo->query("SELECT * FROM Jenis_Penyelenggara")->fetchAll();

// Grouping Prodi by Fakultas for Accordion UI
$prodiGrouped = [];
foreach ($prodiList as $p) {
    $fId = $p['ID_Fakultas'] ?? 0;
    $prodiGrouped[$fId][] = $p;
}
?>

<style>
    /* ISOLASI CSS: Semua style diawali .master-page-wrapper agar tidak bocor ke halaman lain */
    
    .master-page-wrapper .master-card {
        border: none; border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        height: 100%; display: flex; flex-direction: column;
        background-color: white; overflow: hidden;
    }
    .master-page-wrapper .master-header {
        padding: 15px 20px; color: white; font-weight: 600;
        display: flex; justify-content: space-between; align-items: center;
    }
    .master-page-wrapper .scroll-list {
        max-height: 400px; overflow-y: auto; flex-grow: 1;
    }
    .master-page-wrapper .scroll-list::-webkit-scrollbar { width: 5px; }
    .master-page-wrapper .scroll-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 5px; }
    
    .master-page-wrapper .list-group-item { border-left: none; border-right: none; transition: 0.2s; }
    .master-page-wrapper .list-group-item:hover { background-color: #f8f9fa; }
    
    .master-page-wrapper .btn-xs { padding: 2px 6px; font-size: 0.75rem; }
    
    /* Nav Tabs Custom - Hanya di halaman ini */
    .master-page-wrapper .nav-pills .nav-link {
        color: #555; font-weight: 600; border-radius: 50px; padding: 8px 20px;
        background: white; border: 1px solid #eee; margin-right: 8px;
    }
    .master-page-wrapper .nav-pills .nav-link.active {
        background: var(--bs-primary); color: white; 
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); border-color: transparent;
    }
</style>

<div class="master-page-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Master Data</h3>
            <p class="text-muted mb-0">Pusat pengaturan data referensi sistem.</p>
        </div>
        <?php if(isset($_GET['status'])): ?>
            <div class="alert alert-success py-2 px-3 mb-0 border-0 shadow-sm rounded-pill animate__animated animate__fadeIn">
                <i class="fas fa-check-circle me-1"></i> Tersimpan
            </div>
        <?php endif; ?>
    </div>

    <ul class="nav nav-pills mb-4" id="masterTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-akademik">
                <i class="fas fa-university me-2"></i>Akademik
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tim">
                <i class="fas fa-users-cog me-2"></i>Atribut Tim
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-lomba">
                <i class="fas fa-trophy me-2"></i>Data Lomba
            </button>
        </li>
    </ul>

    <div class="tab-content" id="masterTabContent">
        
        <div class="tab-pane fade show active" id="tab-akademik">
            <div class="row g-4">
                <div class="col-md-5">
                    <div class="master-card">
                        <div class="master-header bg-dark">
                            <span><i class="fas fa-building me-2"></i>Fakultas</span>
                            <button class="btn btn-sm btn-light text-dark fw-bold rounded-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#addFakultasModal"><i class="fas fa-plus"></i></button>
                        </div>
                        <ul class="list-group list-group-flush scroll-list">
                            <?php foreach($fakultasList as $f): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($f['Nama_Fakultas']) ?></span>
                                <div>
                                    <button class="btn btn-xs btn-outline-warning rounded-circle me-1" onclick="editFakultas(<?= $f['ID_Fakultas'] ?>, '<?= addslashes($f['Nama_Fakultas']) ?>')"><i class="fas fa-pencil-alt"></i></button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus Fakultas ini?')">
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

                <div class="col-md-7">
                    <div class="master-card">
                        <div class="master-header bg-secondary">
                            <span><i class="fas fa-graduation-cap me-2"></i>Program Studi</span>
                            <button class="btn btn-sm btn-light text-dark fw-bold rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addProdiModal"><i class="fas fa-plus me-1"></i> Tambah</button>
                        </div>
                        <div class="card-body p-3 bg-light">
                            <div class="accordion scroll-list" id="accordionProdi">
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
                                                        <li class="list-group-item d-flex justify-content-between align-items-center ps-4">
                                                            <span><i class="fas fa-angle-right me-2 text-muted"></i><?= htmlspecialchars($p['Nama_Prodi']) ?></span>
                                                            <div>
                                                                <button class="btn btn-xs btn-link text-warning p-0 me-2" onclick="editProdi(<?= $p['ID_Prodi'] ?>, '<?= addslashes($p['Nama_Prodi']) ?>', <?= $f['ID_Fakultas'] ?>)"><i class="fas fa-pencil-alt"></i></button>
                                                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus Prodi?')">
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
        </div>

        <div class="tab-pane fade" id="tab-tim">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="master-card">
                        <div class="master-header bg-success">
                            <span><i class="fas fa-code me-2"></i>Skill / Tools</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <ul class="list-group list-group-flush scroll-list mb-3">
                                <?php foreach($skills as $s): ?>
                                <li class="list-group-item d-flex justify-content-between py-2">
                                    <?= htmlspecialchars($s['Nama_Skill']) ?>
                                    <form method="POST">
                                        <input type="hidden" name="type" value="skill">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $s['ID_Skill'] ?>">
                                        <button class="btn btn-xs text-danger"><i class="fas fa-times"></i></button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <form method="POST" class="mt-auto d-flex gap-2">
                                <input type="hidden" name="type" value="skill">
                                <input type="hidden" name="action" value="add">
                                <input type="text" name="nama" class="form-control form-control-sm" placeholder="Tambah Skill..." required>
                                <button class="btn btn-sm btn-success"><i class="fas fa-plus"></i></button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="master-card">
                        <div class="master-header bg-info text-white">
                            <span><i class="fas fa-user-tag me-2"></i>Role / Profesi</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <ul class="list-group list-group-flush scroll-list mb-3">
                                <?php foreach($roles as $r): ?>
                                <li class="list-group-item d-flex justify-content-between py-2">
                                    <?= htmlspecialchars($r['Nama_Role']) ?>
                                    <form method="POST">
                                        <input type="hidden" name="type" value="role">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $r['ID_Role'] ?>">
                                        <button class="btn btn-xs text-danger"><i class="fas fa-times"></i></button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <form method="POST" class="mt-auto d-flex gap-2">
                                <input type="hidden" name="type" value="role">
                                <input type="hidden" name="action" value="add">
                                <input type="text" name="nama" class="form-control form-control-sm" placeholder="Tambah Role..." required>
                                <button class="btn btn-sm btn-info text-white"><i class="fas fa-plus"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-lomba">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="master-card">
                        <div class="master-header bg-primary">
                            <span><i class="fas fa-tags me-2"></i>Kategori Lomba</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="scroll-list mb-3">
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach($kategoris as $k): ?>
                                    <span class="badge bg-light text-dark border p-2 d-flex align-items-center gap-2">
                                        <?= htmlspecialchars($k['Nama_Kategori']) ?>
                                        <form method="POST" class="m-0 p-0">
                                            <input type="hidden" name="type" value="kategori">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $k['ID_Kategori'] ?>">
                                            <button class="btn p-0 text-danger lh-1 border-0 bg-transparent"><i class="fas fa-times"></i></button>
                                        </form>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <form method="POST" class="mt-auto d-flex gap-2">
                                <input type="hidden" name="type" value="kategori">
                                <input type="hidden" name="action" value="add">
                                <input type="text" name="nama" class="form-control form-control-sm" placeholder="Kategori Baru..." required>
                                <button class="btn btn-sm btn-primary"><i class="fas fa-plus"></i></button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="master-card">
                        <div class="master-header bg-warning text-dark">
                            <span><i class="fas fa-building me-2"></i>Jenis Penyelenggara</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <ul class="list-group list-group-flush scroll-list mb-3">
                                <?php foreach($penyelenggaras as $p): ?>
                                <li class="list-group-item d-flex justify-content-between py-2">
                                    <span><?= htmlspecialchars($p['Nama_Jenis']) ?></span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary">Bobot: <?= $p['Bobot_Poin'] ?></span>
                                        <form method="POST">
                                            <input type="hidden" name="type" value="penyelenggara">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['ID_Jenis'] ?>">
                                            <button class="btn btn-xs text-danger"><i class="fas fa-times"></i></button>
                                        </form>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <form method="POST" class="mt-auto">
                                <input type="hidden" name="type" value="penyelenggara">
                                <input type="hidden" name="action" value="add">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="nama" class="form-control" placeholder="Jenis (Univ, Pemda...)" required>
                                    <input type="number" step="0.1" name="bobot" class="form-control" placeholder="Bobot" style="max-width: 80px;" required>
                                    <button class="btn btn-warning"><i class="fas fa-plus"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div> <div class="modal fade" id="addFakultasModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white border-0 py-2">
                <h6 class="modal-title mb-0">Tambah Fakultas</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="type" value="fakultas">
                <input type="hidden" name="action" value="add">
                <input type="text" name="nama" class="form-control" placeholder="Nama Fakultas" required>
                <button class="btn btn-dark btn-sm w-100 mt-3 rounded-pill">Simpan</button>
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
                <input type="text" name="nama" id="editFakultasName" class="form-control" required>
                <button class="btn btn-warning btn-sm w-100 mt-3 rounded-pill fw-bold">Update</button>
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