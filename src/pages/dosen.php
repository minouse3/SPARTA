<?php
// FILE: Manajemen Data Dosen (Modern UI)

// 1. CEK AKSES
$isDosenAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'dosen' && isset($_SESSION['dosen_is_admin']) && $_SESSION['dosen_is_admin'] == 1);
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!$isAdmin && !$isDosenAdmin) {
    echo "<div class='alert alert-danger border-0 shadow-sm'><i class='fas fa-lock me-2'></i>Akses Ditolak. Anda tidak memiliki izin mengelola data ini.</div>";
    exit;
}

// 2. LOGIC HAPUS DATA & TAMBAH DATA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'delete_single') {
            $stmt = $pdo->prepare("DELETE FROM Dosen_Pembimbing WHERE ID_Dosen = ?");
            $stmt->execute([$_POST['id']]);
            echo "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-check-circle me-2'></i>Data dosen berhasil dihapus.</div>";
        }
        elseif (isset($_POST['action']) && $_POST['action'] === 'add_dosen') {
            $nidn = trim($_POST['nidn']);
            $nama = trim($_POST['nama']);
            $email = trim($_POST['email']);
            // Default password = NIDN
            $passHash = password_hash($nidn, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO Dosen_Pembimbing (NIDN, Nama_Dosen, Email, Password_Hash, Is_Verified) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$nidn, $nama, $email, $passHash]);
            echo "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-check-circle me-2'></i>Dosen baru berhasil ditambahkan.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger border-0 shadow-sm'>Gagal: " . $e->getMessage() . "</div>";
    }
}

// 3. LOAD MASTER DATA (Untuk Dropdown Filter)
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiListAll = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();
$skillList = $pdo->query("SELECT * FROM Skill ORDER BY Nama_Skill ASC")->fetchAll();
?>

<style>
    .btn-gradient-primary {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white; border: none;
    }
    .btn-gradient-primary:hover {
        background: linear-gradient(135deg, #0b5ed7, #0aa2c0);
        color: white; transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }
    .avatar-sm {
        width: 35px; height: 35px;
        background-color: #e9ecef;
        color: #495057;
        font-weight: bold;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        font-size: 0.85rem;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Data Dosen</h3>
        <p class="text-muted mb-0">Kelola data dosen pembimbing dan staf pengajar.</p>
    </div>
    <button class="btn btn-gradient-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addDosenModal">
        <i class="fas fa-plus me-2"></i>Dosen Baru
    </button>
</div>

<div class="card shadow-sm border-0 mb-4 rounded-3">
    <div class="card-body bg-white p-3 rounded-3">
        <form id="filterFormDosen" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Pencarian</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="qDosen" class="form-control border-start-0 bg-light" placeholder="Nama / NIDN / Email...">
                </div>
            </div>
            
            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Fakultas</label>
                <select id="fakultasDosen" class="form-select form-select-sm bg-light border-0">
                    <option value="">Semua Fakultas</option>
                    <?php foreach($fakultasList as $f): ?>
                        <option value="<?= $f['ID_Fakultas'] ?>"><?= htmlspecialchars($f['Nama_Fakultas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Program Studi</label>
                <select id="prodiDosen" class="form-select form-select-sm bg-light border-0">
                    <option value="">Semua Prodi</option>
                    <?php foreach($prodiListAll as $p): ?>
                        <option value="<?= $p['ID_Prodi'] ?>"><?= htmlspecialchars($p['Nama_Prodi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Keahlian / Skill</label>
                <select id="skillDosen" class="form-select form-select-sm bg-light border-0">
                    <option value="">Semua Keahlian</option>
                    <?php foreach($skillList as $s): ?>
                        <option value="<?= $s['ID_Skill'] ?>"><?= htmlspecialchars($s['Nama_Skill']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-light w-100 border" onclick="resetFilterDosen()" title="Reset Filter">
                    <i class="fas fa-undo"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-3 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>Dosen</th>
                        <th>NIDN</th>
                        <th>Fakultas & Prodi</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBodyDosen">
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
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

<div class="modal fade" id="addDosenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Tambah Dosen Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info small border-0 bg-info bg-opacity-10 text-info mb-4">
                    <i class="fas fa-info-circle me-1"></i> Password default adalah <strong>NIDN</strong>.
                </div>
                <input type="hidden" name="action" value="add_dosen">
                <div class="mb-3">
                    <label class="small fw-bold text-muted">NIDN</label>
                    <input type="text" name="nidn" class="form-control" placeholder="Nomor Induk Dosen Nasional" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" placeholder="Nama beserta gelar" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Email Institusi</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@mail.unnes.ac.id">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light text-muted fw-bold rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    loadDataDosen();

    const inputs = document.querySelectorAll('#filterFormDosen input, #filterFormDosen select');
    inputs.forEach(input => {
        input.addEventListener('input', debounce(loadDataDosen, 300));
    });
});

function loadDataDosen() {
    const q = document.getElementById('qDosen').value;
    const fak = document.getElementById('fakultasDosen').value;
    const prodi = document.getElementById('prodiDosen').value;
    const skill = document.getElementById('skillDosen').value;
    
    const tbody = document.getElementById('tableBodyDosen');
    tbody.style.opacity = '0.5';

    fetch(`fetch_data.php?page=dosen&q=${encodeURIComponent(q)}&fakultas=${fak}&prodi=${prodi}&skill=${skill}`)
        .then(response => response.text())
        .then(html => {
            tbody.innerHTML = html;
            tbody.style.opacity = '1';
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
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