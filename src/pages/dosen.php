<?php
// --- LOGIC PHP ---
$action = $_POST['action'] ?? '';

// HANDLE POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'add_dosen') {
            $stmt = $pdo->prepare("INSERT INTO Dosen_Pembimbing (NIDN, Nama_Dosen, Email, ID_Prodi) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['nidn'], $_POST['nama'], $_POST['email'], $_POST['prodi']]);
            echo "<div class='alert alert-success'>Dosen berhasil ditambahkan!</div>";
        }
        elseif ($action === 'edit_dosen') {
            $stmt = $pdo->prepare("UPDATE Dosen_Pembimbing SET NIDN=?, Nama_Dosen=?, Email=?, ID_Prodi=? WHERE ID_Dosen=?");
            $stmt->execute([$_POST['nidn'], $_POST['nama'], $_POST['email'], $_POST['prodi'], $_POST['id_dosen']]);
            echo "<div class='alert alert-success'>Data Dosen berhasil diupdate!</div>";
        }
        elseif ($action === 'delete_single') {
            $stmt = $pdo->prepare("DELETE FROM Dosen_Pembimbing WHERE ID_Dosen = ?");
            $stmt->execute([$_POST['id']]);
            echo "<div class='alert alert-success'>Data Dosen berhasil dihapus.</div>";
        }
        elseif ($action === 'delete_bulk') {
            if (!empty($_POST['ids'])) {
                $ids = implode(',', array_map('intval', $_POST['ids']));
                $pdo->exec("DELETE FROM Dosen_Pembimbing WHERE ID_Dosen IN ($ids)");
                echo "<div class='alert alert-success'>".count($_POST['ids'])." data dosen berhasil dihapus.</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// QUERY DATA & MASTER DATA
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiAll = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();
$jsonProdi = json_encode($prodiAll);

$filterProdi = $_GET['prodi'] ?? '';
$search = $_GET['q'] ?? '';

$sql = "SELECT d.*, p.Nama_Prodi, p.ID_Fakultas, f.Nama_Fakultas 
        FROM Dosen_Pembimbing d 
        LEFT JOIN Prodi p ON d.ID_Prodi = p.ID_Prodi 
        LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
        WHERE 1=1";
$params = [];

if ($filterProdi) { $sql .= " AND d.ID_Prodi = ?"; $params[] = $filterProdi; }
if ($search) { $sql .= " AND (d.Nama_Dosen LIKE ? OR d.NIDN LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql .= " ORDER BY d.Nama_Dosen ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dosenList = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary"><i class="fas fa-chalkboard-teacher me-2"></i> Data Dosen</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDosenModal"><i class="fas fa-plus"></i> Dosen Baru</button>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <form method="GET">
                    <input type="hidden" name="page" value="dosen">
                    <div class="mb-3"><label class="small fw-bold">Cari</label><input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($search) ?>"></div>
                    <div class="mb-3">
                        <label class="small fw-bold">Filter Prodi</label>
                        <select name="prodi" class="form-select form-select-sm">
                            <option value="">Semua Prodi</option>
                            <?php foreach($prodiAll as $p): ?>
                                <option value="<?= $p['ID_Prodi'] ?>" <?= $filterProdi == $p['ID_Prodi'] ? 'selected' : '' ?>><?= $p['Nama_Prodi'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                    <a href="?page=dosen" class="btn btn-outline-secondary btn-sm w-100 mt-2">Reset</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <form method="POST" id="bulkFormDosen">
            <input type="hidden" name="action" value="delete_bulk">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">List Dosen Pembimbing</span>
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus dosen terpilih?')">
                        <i class="fas fa-trash-alt me-1"></i> Hapus Terpilih
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3" style="width: 40px;"><input type="checkbox" class="form-check-input" onclick="toggleAll(this)"></th>
                                <th>NIDN</th>
                                <th>Nama Dosen</th>
                                <th>Fakultas</th> <th>Prodi</th>    <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($dosenList as $d): ?>
                            <tr>
                                <td class="ps-3"><input type="checkbox" name="ids[]" value="<?= $d['ID_Dosen'] ?>" class="form-check-input"></td>
                                <td class="fw-bold"><?= $d['NIDN'] ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($d['Nama_Dosen']) ?></div>
                                    <small class="text-muted"><?= $d['Email'] ?></small>
                                </td>
                                <td><?= htmlspecialchars($d['Nama_Fakultas']) ?></td>
                                <td><span class="badge bg-info bg-opacity-10 text-info border border-info"><?= $d['Nama_Prodi'] ?></span></td>
                                
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                            onclick="editDosen(<?= $d['ID_Dosen'] ?>, '<?= $d['NIDN'] ?>', '<?= addslashes($d['Nama_Dosen']) ?>', '<?= $d['Email'] ?>', <?= $d['ID_Fakultas'] ?>, <?= $d['ID_Prodi'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSingle(<?= $d['ID_Dosen'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>

<form id="deleteSingleForm" method="POST" style="display:none;"><input type="hidden" name="action" value="delete_single"><input type="hidden" name="id" id="deleteId"></form>

<div class="modal fade" id="addDosenModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Tambah Dosen</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_dosen">
                <div class="mb-3"><label>NIDN</label><input type="text" name="nidn" class="form-control" required></div>
                <div class="mb-3"><label>Nama</label><input type="text" name="nama" class="form-control" required></div>
                <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control"></div>
                
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
                    <label>Prodi</label>
                    <select name="prodi" id="addProdi" class="form-select" required>
                        <option value="">-- Pilih Fakultas Dahulu --</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editDosenModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-warning text-dark"><h5 class="modal-title">Edit Dosen</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_dosen">
                <input type="hidden" name="id_dosen" id="editId">
                
                <div class="mb-3"><label>NIDN</label><input type="text" name="nidn" id="editNidn" class="form-control" required></div>
                <div class="mb-3"><label>Nama</label><input type="text" name="nama" id="editNama" class="form-control" required></div>
                <div class="mb-3"><label>Email</label><input type="email" name="email" id="editEmail" class="form-control"></div>
                
                <div class="mb-3">
                    <label>Fakultas</label>
                    <select id="editFakultas" class="form-select" onchange="populateProdi('editFakultas', 'editProdi')" required>
                        <option value="">-- Pilih Fakultas --</option>
                        <?php foreach($fakultasList as $f): ?>
                            <option value="<?= $f['ID_Fakultas'] ?>"><?= $f['Nama_Fakultas'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Prodi</label>
                    <select name="prodi" id="editProdi" class="form-select" required>
                        </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning">Update</button></div>
        </form>
    </div>
</div>

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
    if(confirm('Hapus dosen ini?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteSingleForm').submit();
    }
}
function editDosen(id, nidn, nama, email, fakId, prodiId) {
    document.getElementById('editId').value = id;
    document.getElementById('editNidn').value = nidn;
    document.getElementById('editNama').value = nama;
    document.getElementById('editEmail').value = email;
    document.getElementById('editFakultas').value = fakId;
    populateProdi('editFakultas', 'editProdi', prodiId);
    new bootstrap.Modal(document.getElementById('editDosenModal')).show();
}
</script>