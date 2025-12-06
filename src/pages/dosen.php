<?php
// FILE: Manajemen Data Dosen (Filter & Search Canggih)

// 1. CEK AKSES
$isDosenAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'dosen' && isset($_SESSION['dosen_is_admin']) && $_SESSION['dosen_is_admin'] == 1);
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!$isAdmin && !$isDosenAdmin) {
    echo "<div class='alert alert-danger'>Akses Ditolak. Anda tidak memiliki izin mengelola data ini.</div>";
    exit;
}

// 2. LOGIC HAPUS DATA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_single') {
    try {
        $stmt = $pdo->prepare("DELETE FROM Dosen_Pembimbing WHERE ID_Dosen = ?");
        $stmt->execute([$_POST['id']]);
        echo "<div class='alert alert-success'>Data dosen berhasil dihapus.</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Gagal menghapus: " . $e->getMessage() . "</div>";
    }
}

// 3. LOAD MASTER DATA (Untuk Dropdown Filter)
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiListAll = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();
$skillList = $pdo->query("SELECT * FROM Skill ORDER BY Nama_Skill ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-primary"><i class="fas fa-chalkboard-teacher me-2"></i> Data Dosen</h2>
        <p class="text-muted mb-0">Kelola data dosen pembimbing dan staf pengajar.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDosenModal">
        <i class="fas fa-plus me-2"></i> Dosen Baru
    </button>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light">
        <form id="filterFormDosen" class="row g-2">
            <div class="col-md-3">
                <label class="small fw-bold text-muted">Pencarian</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="qDosen" class="form-control" placeholder="Cari Nama / NIDN / Email...">
                </div>
            </div>
            
            <div class="col-md-2">
                <label class="small fw-bold text-muted">Fakultas</label>
                <select id="fakultasDosen" class="form-select form-select-sm">
                    <option value="">Semua Fakultas</option>
                    <?php foreach($fakultasList as $f): ?>
                        <option value="<?= $f['ID_Fakultas'] ?>"><?= htmlspecialchars($f['Nama_Fakultas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small fw-bold text-muted">Program Studi</label>
                <select id="prodiDosen" class="form-select form-select-sm">
                    <option value="">Semua Prodi</option>
                    <?php foreach($prodiListAll as $p): ?>
                        <option value="<?= $p['ID_Prodi'] ?>"><?= htmlspecialchars($p['Nama_Prodi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small fw-bold text-muted">Keahlian / Skill</label>
                <select id="skillDosen" class="form-select form-select-sm">
                    <option value="">Semua Keahlian</option>
                    <?php foreach($skillList as $s): ?>
                        <option value="<?= $s['ID_Skill'] ?>"><?= htmlspecialchars($s['Nama_Skill']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetFilterDosen()">
                    <i class="fas fa-undo me-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-3" style="width: 50px;">#</th>
                        <th>NIDN</th>
                        <th>Nama Dosen</th>
                        <th>Fakultas</th>
                        <th>Prodi</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBodyDosen">
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <span class="spinner-border spinner-border-sm text-primary me-2"></span> Memuat data dosen...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="deleteSingleFormDosen" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete_single">
    <input type="hidden" name="id" id="deleteIdDosen">
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    loadDataDosen(); // Load awal

    // Event Listener untuk Search & Filter (Debounce)
    const inputs = document.querySelectorAll('#filterFormDosen input, #filterFormDosen select');
    inputs.forEach(input => {
        input.addEventListener('input', debounce(loadDataDosen, 300));
    });
});

function loadDataDosen() {
    // Ambil value dari form
    const q = document.getElementById('qDosen').value;
    const fak = document.getElementById('fakultasDosen').value;
    const prodi = document.getElementById('prodiDosen').value;
    const skill = document.getElementById('skillDosen').value;
    
    const tbody = document.getElementById('tableBodyDosen');
    tbody.style.opacity = '0.5';

    // Panggil API fetch_data.php
    fetch(`fetch_data.php?page=dosen&q=${encodeURIComponent(q)}&fakultas=${fak}&prodi=${prodi}&skill=${skill}`)
        .then(response => response.text())
        .then(html => {
            tbody.innerHTML = html;
            tbody.style.opacity = '1';
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>';
            tbody.style.opacity = '1';
        });
}

function deleteSingle(id) {
    if(confirm('Apakah Anda yakin ingin menghapus data dosen ini?')) {
        document.getElementById('deleteIdDosen').value = id;
        document.getElementById('deleteSingleFormDosen').submit();
    }
}

function resetFilterDosen() {
    document.getElementById('filterFormDosen').reset();
    loadDataDosen();
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        const later = () => { clearTimeout(timeout); func(...args); };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>

<div class="modal fade" id="addDosenModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Tambah Dosen</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_dosen">
                <div class="mb-3"><label>NIDN</label><input type="text" name="nidn" class="form-control" required></div>
                <div class="mb-3"><label>Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control"></div>
                </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>