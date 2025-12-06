<?php
// FILE: Halaman Manajemen Data Mahasiswa (AJAX Search & Filter)

// KEAMANAN: Cek Hak Akses (Admin atau Dosen Admin)
$isDosenAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'dosen' && isset($_SESSION['dosen_is_admin']) && $_SESSION['dosen_is_admin'] == 1);
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!$isAdmin && !$isDosenAdmin) {
    echo "<div class='alert alert-danger'>Akses Ditolak. Anda tidak memiliki izin mengelola data ini.</div>";
    exit;
}

// --- LOGIC POST REQUEST (Tambah/Edit/Hapus) ---
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. TAMBAH MAHASISWA BARU
        if ($action === 'add_mahasiswa') {
            // Cek Duplikasi NIM/Email
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Mahasiswa WHERE NIM = ? OR Email = ?");
            $cek->execute([$_POST['nim'], $_POST['email']]);
            if ($cek->fetchColumn() > 0) {
                echo "<div class='alert alert-warning'>Gagal: NIM atau Email sudah terdaftar.</div>";
            } else {
                $passHash = password_hash($_POST['nim'], PASSWORD_DEFAULT); // Default pass = NIM
                $stmt = $pdo->prepare("INSERT INTO Mahasiswa (NIM, Nama_Mahasiswa, Email, Password_Hash, ID_Prodi, Is_Verified) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$_POST['nim'], $_POST['nama'], $_POST['email'], $passHash, $_POST['prodi']]);
                echo "<div class='alert alert-success'>Mahasiswa berhasil ditambahkan!</div>";
            }
        } 
        // 2. EDIT BIODATA (Admin Mode)
        elseif ($action === 'save_profile') {
            $sql = "UPDATE Mahasiswa SET Nama_Mahasiswa=?, Email=?, Tempat_Lahir=?, Tanggal_Lahir=?, Bio=?, ID_Prodi=? WHERE ID_Mahasiswa=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['nama'], $_POST['email'], $_POST['tempat_lahir'], $_POST['tgl_lahir'], $_POST['bio'], $_POST['prodi'], $_POST['id_mahasiswa']]);
            
            echo "<script>alert('Data mahasiswa berhasil diupdate!'); window.location.href='?page=mahasiswa';</script>";
        }
        // 3. HAPUS SATUAN
        elseif ($action === 'delete_single') {
            $stmt = $pdo->prepare("DELETE FROM Mahasiswa WHERE ID_Mahasiswa = ?");
            $stmt->execute([$_POST['id']]);
            echo "<div class='alert alert-success'>Data mahasiswa berhasil dihapus.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// --- LOAD DATA MASTER UNTUK FILTER & FORM ---
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiListAll = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();
$skillList = $pdo->query("SELECT * FROM Skill ORDER BY Nama_Skill ASC")->fetchAll();
$roleList = $pdo->query("SELECT * FROM Role_Tim ORDER BY Nama_Role ASC")->fetchAll();
$jsonProdi = json_encode($prodiListAll); // Untuk JS Dropdown
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-primary"><i class="fas fa-user-graduate me-2"></i> Data Mahasiswa</h2>
        <p class="text-muted mb-0">Kelola data, filter berdasarkan skill, dan pantau mahasiswa.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addMhsModal">
        <i class="fas fa-plus me-2"></i> Tambah Mahasiswa
    </button>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light">
        <form id="filterForm" class="row g-2">
            <div class="col-md-3">
                <label class="small fw-bold text-muted">Pencarian</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="q" class="form-control" placeholder="Nama, NIM, atau Email...">
                </div>
            </div>
            
            <div class="col-md-2">
                <label class="small fw-bold text-muted">Fakultas</label>
                <select id="fakultas" class="form-select form-select-sm">
                    <option value="">Semua Fakultas</option>
                    <?php foreach($fakultasList as $f): ?>
                        <option value="<?= $f['ID_Fakultas'] ?>"><?= htmlspecialchars($f['Nama_Fakultas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-muted">Program Studi</label>
                <select id="prodi" class="form-select form-select-sm">
                    <option value="">Semua Prodi</option>
                    <?php foreach($prodiListAll as $p): ?>
                        <option value="<?= $p['ID_Prodi'] ?>"><?= htmlspecialchars($p['Nama_Prodi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-muted">Skill / Tools</label>
                <select id="skill" class="form-select form-select-sm">
                    <option value="">Semua Skill</option>
                    <?php foreach($skillList as $s): ?>
                        <option value="<?= $s['ID_Skill'] ?>"><?= htmlspecialchars($s['Nama_Skill']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-muted">Minat Role</label>
                <select id="role" class="form-select form-select-sm">
                    <option value="">Semua Role</option>
                    <?php foreach($roleList as $r): ?>
                        <option value="<?= $r['ID_Role'] ?>"><?= htmlspecialchars($r['Nama_Role']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetFilter()">
                    <i class="fas fa-undo me-1"></i> Reset
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
                        <th>Mahasiswa</th>
                        <th>Email</th>
                        <th>Fakultas</th>
                        <th>Prodi</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <span class="spinner-border spinner-border-sm text-primary me-2"></span> Memuat data mahasiswa...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="deleteSingleForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete_single">
    <input type="hidden" name="id" id="deleteId">
</form>

<div class="modal fade" id="addMhsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Tambah Mahasiswa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_mahasiswa">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NIM</label>
                        <input type="text" name="nim" class="form-control" placeholder="A11.202X.XXXXX" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email UNNES</label>
                        <input type="email" name="email" class="form-control" placeholder="@students.unnes.ac.id" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Fakultas</label>
                        <select id="addFakultas" class="form-select" onchange="populateProdi('addFakultas', 'addProdi')" required>
                            <option value="">-- Pilih Fakultas --</option>
                            <?php foreach($fakultasList as $f): ?>
                                <option value="<?= $f['ID_Fakultas'] ?>"><?= htmlspecialchars($f['Nama_Fakultas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Program Studi</label>
                        <select name="prodi" id="addProdi" class="form-select" required>
                            <option value="">-- Pilih Fakultas Dahulu --</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Data Prodi untuk Dropdown Cascading
const prodiData = <?= $jsonProdi ?>;

// --- 1. POPULATE PRODI ---
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
        if (filtered.length === 0) prodiSelect.innerHTML = '<option value="">Tidak ada prodi</option>';
    } else {
        prodiSelect.innerHTML = '<option value="">-- Pilih Fakultas Dahulu --</option>';
    }
}

// --- 2. AJAX SEARCH & FILTER SYSTEM ---
document.addEventListener("DOMContentLoaded", function() {
    loadData(); // Load data saat halaman pertama kali dibuka

    // Pasang Event Listener (Debounce) ke semua input filter
    const inputs = document.querySelectorAll('#filterForm input, #filterForm select');
    inputs.forEach(input => {
        input.addEventListener('input', debounce(loadData, 300));
    });
});

function loadData() {
    // Ambil value dari semua filter
    const q = document.getElementById('q').value;
    const fak = document.getElementById('fakultas').value;
    const prodi = document.getElementById('prodi').value;
    const skill = document.getElementById('skill').value;
    const role = document.getElementById('role').value;

    const tbody = document.getElementById('tableBody');
    tbody.style.opacity = '0.5'; // Efek loading visual

    // Panggil API fetch_data.php
    // Pastikan path fetch_data.php benar
    const url = `fetch_data.php?page=mahasiswa&q=${encodeURIComponent(q)}&fakultas=${fak}&prodi=${prodi}&skill=${skill}&role=${role}`;

    fetch(url)
        .then(response => response.text())
        .then(html => {
            tbody.innerHTML = html;
            tbody.style.opacity = '1';
        })
        .catch(err => {
            console.error('Error fetching data:', err);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>';
            tbody.style.opacity = '1';
        });
}

function resetFilter() {
    document.getElementById('filterForm').reset();
    loadData(); // Reload data polos
}

// Fungsi Penunda (Debounce) agar tidak spam server saat mengetik
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// --- 3. DELETE FUNCTION ---
function deleteSingle(id) {
    if(confirm('Apakah Anda yakin ingin menghapus data mahasiswa ini secara permanen?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteSingleForm').submit();
    }
}
</script>