<?php
// FILE: Halaman Leaderboard (Modern UI)

// Load Data Master untuk Filter
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$skillList = $pdo->query("SELECT * FROM Skill ORDER BY Nama_Skill ASC")->fetchAll();
$roleList = $pdo->query("SELECT * FROM Role_Tim ORDER BY Nama_Role ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Leaderboard</h3>
        <p class="text-muted mb-0">Peringkat mahasiswa berdasarkan poin keaktifan dan prestasi.</p>
    </div>
    <div class="d-none d-md-block pe-3">
        <i class="fas fa-trophy fa-3x text-warning opacity-50 transform-rotate"></i>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 rounded-3">
    <div class="card-body p-4 bg-white rounded-3">
        <form id="filterRank" class="row g-3">
            <div class="col-md-4">
                <label class="small fw-bold text-muted mb-1">Pencarian</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="qRank" class="form-control border-start-0 ps-0" placeholder="Cari Nama atau NIM...">
                </div>
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Fakultas</label>
                <select id="fakRank" class="form-select">
                    <option value="">-- Semua Fakultas --</option>
                    <?php foreach($fakultasList as $f): ?>
                        <option value="<?= $f['ID_Fakultas'] ?>"><?= htmlspecialchars($f['Nama_Fakultas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Skill (Tools)</label>
                <select id="skillRank" class="form-select">
                    <option value="">-- Semua --</option>
                    <?php foreach($skillList as $s): ?>
                        <option value="<?= $s['ID_Skill'] ?>"><?= htmlspecialchars($s['Nama_Skill']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Minat Role</label>
                <select id="roleRank" class="form-select">
                    <option value="">-- Semua --</option>
                    <?php foreach($roleList as $r): ?>
                        <option value="<?= $r['ID_Role'] ?>"><?= htmlspecialchars($r['Nama_Role']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-3 overflow-hidden">
    <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list-ol me-2"></i>Daftar Peringkat Tertinggi</h6>
        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Klik baris untuk detail</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4" style="width: 80px;">Rank</th>
                        <th>Mahasiswa</th>
                        <th>Fakultas</th>
                        <th>Prodi</th>
                        <th class="text-end pe-4">Total Poin</th>
                    </tr>
                </thead>
                <tbody id="rankBody">
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <span class="spinner-border spinner-border-sm text-primary me-2" role="status"></span>
                            <span>Sedang memuat data leaderboard...</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-light text-center border-0 py-3">
        <small class="text-muted opacity-75">Menampilkan maksimal 50 mahasiswa teratas sesuai filter.</small>
    </div>
</div>

<style>
    .transform-rotate { transform: rotate(15deg); }
    .table-hover tbody tr:hover { background-color: rgba(13, 110, 253, 0.03); }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    loadRank();
    
    // Event Listener dengan Debounce
    const inputs = document.querySelectorAll('#filterRank input, #filterRank select');
    inputs.forEach(input => { 
        input.addEventListener('input', debounce(loadRank, 300)); 
    });
});

function loadRank() {
    const q = document.getElementById('qRank').value;
    const fak = document.getElementById('fakRank').value;
    const skill = document.getElementById('skillRank').value;
    const role = document.getElementById('roleRank').value;
    
    const tbody = document.getElementById('rankBody');
    tbody.style.opacity = '0.6'; // Visual feedback loading

    fetch(`fetch_data.php?page=ranking&q=${encodeURIComponent(q)}&fakultas=${fak}&skill=${skill}&role=${role}`)
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

// Fungsi Delay agar tidak spam request saat mengetik
function debounce(func, wait) { 
    let timeout; 
    return function(...args) { 
        clearTimeout(timeout); 
        timeout = setTimeout(() => func(...args), wait); 
    }; 
}
</script>