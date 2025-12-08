<?php
// FILE: src/pages/dosen.php (Fixed: Access Rights & JSON Escaping)

// 1. CEK HAK AKSES (PERBAIKAN: Mengizinkan Admin Murni DAN Dosen Admin)
$isPureAdmin  = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$isDosenAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'dosen' && isset($_SESSION['dosen_is_admin']) && $_SESSION['dosen_is_admin'] == 1);
$canEdit      = ($isPureAdmin || $isDosenAdmin); // Variabel gabungan untuk hak akses

// --- HANDLE POST ACTION (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    try {
        $action = $_POST['action'];

        // A. TAMBAH DOSEN
        if ($action === 'add_dosen') {
            $defaultPass = password_hash('123456', PASSWORD_DEFAULT);
            
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Dosen_Pembimbing WHERE Email = ? OR NIDN = ?");
            $cek->execute([$_POST['email'], $_POST['nidn']]);
            
            if ($cek->fetchColumn() > 0) {
                echo "<script>alert('Gagal: Email atau NIDN sudah terdaftar!');</script>";
            } else {
                $stmt = $pdo->prepare("INSERT INTO Dosen_Pembimbing (Nama_Dosen, NIDN, ID_Prodi, Email, No_HP, Bio, Password_Hash, Need_Reset) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([
                    $_POST['nama'], $_POST['nidn'], $_POST['id_prodi'], 
                    $_POST['email'], $_POST['no_hp'], $_POST['bio'], $defaultPass
                ]);
                echo "<script>alert('Dosen berhasil ditambahkan!'); window.location='?page=dosen';</script>";
            }
        }

        // B. EDIT DOSEN
        elseif ($action === 'edit_dosen') {
            $stmt = $pdo->prepare("UPDATE Dosen_Pembimbing SET Nama_Dosen=?, Email=?, NIDN=?, No_HP=?, Bio=?, ID_Prodi=? WHERE ID_Dosen=?");
            $stmt->execute([
                $_POST['nama'], $_POST['email'], $_POST['nidn'], 
                $_POST['no_hp'], $_POST['bio'], $_POST['id_prodi'], $_POST['id_dosen']
            ]);
            echo "<script>alert('Data diperbarui!'); window.location='?page=dosen';</script>";
        } 
        
        // C. HAPUS DOSEN
        elseif ($action === 'delete_dosen') {
            $pdo->prepare("DELETE FROM Dosen_Pembimbing WHERE ID_Dosen=?")->execute([$_POST['id_dosen']]);
            echo "<script>alert('Dosen dihapus.'); window.location='?page=dosen';</script>";
        }

    } catch (Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

// --- AMBIL DATA MASTER UNTUK FILTER ---
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiList = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();
$skillList = $pdo->query("SELECT * FROM Skill ORDER BY Nama_Skill ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Data Dosen</h3>
        <p class="text-muted mb-0">Database Dosen dan Tenaga Pengajar.</p>
    </div>
    <?php if($canEdit): ?>
    <button class="btn btn-primary fw-bold shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addDosenModal">
        <i class="fas fa-plus me-2"></i> Tambah
    </button>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light p-3">
        <form id="filterDosenForm" class="row g-2">
            <div class="col-md-4">
                <label class="small fw-bold text-muted mb-1">Pencarian</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="q" class="form-control border-start-0 ps-0" placeholder="Nama, NIDN, atau Email...">
                </div>
            </div>
            
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Fakultas</label>
                <select id="fakultas" class="form-select">
                    <option value="">Semua Fakultas</option>
                    <?php foreach($fakultasList as $f): ?>
                        <option value="<?= $f['ID_Fakultas'] ?>"><?= htmlspecialchars($f['Nama_Fakultas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Program Studi</label>
                <select id="prodi" class="form-select">
                    <option value="">Semua Prodi</option>
                    <?php foreach($prodiList as $p): ?>
                        <option value="<?= $p['ID_Prodi'] ?>"><?= htmlspecialchars($p['Nama_Prodi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Skill (Keahlian)</label>
                <select id="skill" class="form-select">
                    <option value="">Semua Skill</option>
                    <?php foreach($skillList as $s): ?>
                        <option value="<?= $s['ID_Skill'] ?>"><?= htmlspecialchars($s['Nama_Skill']) ?></option>
                    <?php endforeach; ?>
                </select>
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
                        <th>Akademik</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableDosenBody">
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <span class="spinner-border spinner-border-sm text-primary me-2"></span> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete_dosen">
    <input type="hidden" name="id_dosen" id="deleteId">
</form>

<?php if($canEdit): ?>
<div class="modal fade" id="addDosenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Tambah Dosen Baru</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add_dosen">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" placeholder="Gelar Lengkap" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold text-muted">NIDN</label>
                        <input type="text" name="nidn" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold text-muted">Program Studi</label>
                        <select name="id_prodi" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <?php foreach($prodiList as $p): ?>
                                <option value="<?= $p['ID_Prodi'] ?>"><?= htmlspecialchars($p['Nama_Prodi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold text-muted">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="@mail.unnes.ac.id" required>
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">No HP</label>
                    <input type="text" name="no_hp" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Bio Singkat</label>
                    <textarea name="bio" class="form-control" rows="2"></textarea>
                </div>

                <div class="alert alert-light border small text-muted mb-3">
                    <i class="fas fa-key me-1"></i> Password default: <b>123456</b>
                </div>
                
                <button class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editDosenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Data Dosen</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="edit_dosen">
                <input type="hidden" name="id_dosen" id="editIdDosen">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Nama Lengkap</label>
                    <input type="text" name="nama" id="editNama" class="form-control" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold text-muted">NIDN</label>
                        <input type="text" name="nidn" id="editNidn" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold text-muted">Program Studi</label>
                        <select name="id_prodi" id="editProdi" class="form-select">
                            <?php foreach($prodiList as $p): ?>
                                <option value="<?= $p['ID_Prodi'] ?>"><?= htmlspecialchars($p['Nama_Prodi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold text-muted">Email</label>
                    <input type="email" name="email" id="editEmail" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">No HP</label>
                    <input type="text" name="no_hp" id="editHp" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Bio Singkat</label>
                    <textarea name="bio" id="editBio" class="form-control" rows="3"></textarea>
                </div>
                
                <button class="btn btn-dark w-100 rounded-pill fw-bold shadow-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    loadDosenData();
    const inputs = document.querySelectorAll('#filterDosenForm input, #filterDosenForm select');
    inputs.forEach(input => {
        input.addEventListener('input', debounce(loadDosenData, 300));
    });
});

function loadDosenData() {
    const q = document.getElementById('q').value;
    const fak = document.getElementById('fakultas').value;
    const prodi = document.getElementById('prodi').value;
    const skill = document.getElementById('skill').value;
    const tbody = document.getElementById('tableDosenBody');
    tbody.style.opacity = '0.5';

    const url = `fetch_data.php?page=dosen&q=${encodeURIComponent(q)}&fakultas=${fak}&prodi=${prodi}&skill=${skill}`;

    fetch(url)
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

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

// PERBAIKAN: Fungsi ini sekarang aman dari error kutip karena data dikirim via fetch_data.php dengan escaping yang benar
function editDosen(data) {
    document.getElementById('editIdDosen').value = data.ID_Dosen;
    document.getElementById('editNama').value = data.Nama_Dosen;
    document.getElementById('editNidn').value = data.NIDN;
    document.getElementById('editEmail').value = data.Email;
    document.getElementById('editHp').value = data.No_HP;
    document.getElementById('editBio').value = data.Bio;
    document.getElementById('editProdi').value = data.ID_Prodi;
    new bootstrap.Modal(document.getElementById('editDosenModal')).show();
}

function deleteSingle(id) {
    if(confirm('Apakah Anda yakin ingin menghapus data dosen ini?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>