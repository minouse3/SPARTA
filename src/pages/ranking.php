<?php
// Load Data Master untuk Filter
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$skillList = $pdo->query("SELECT * FROM Skill ORDER BY Nama_Skill ASC")->fetchAll();
$roleList = $pdo->query("SELECT * FROM Role_Tim ORDER BY Nama_Role ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary"><i class="fas fa-trophy text-warning me-2"></i>Leaderboard</h2>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light">
        <form id="filterRank" class="row g-2">
            <div class="col-md-4">
                <input type="text" id="qRank" class="form-control" placeholder="Cari Nama / NIM...">
            </div>
            <div class="col-md-3">
                <select id="fakRank" class="form-select">
                    <option value="">Semua Fakultas</option>
                    <?php foreach($fakultasList as $f): ?><option value="<?= $f['ID_Fakultas'] ?>"><?= $f['Nama_Fakultas'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="skillRank" class="form-select">
                    <option value="">Skill</option>
                    <?php foreach($skillList as $s): ?><option value="<?= $s['ID_Skill'] ?>"><?= $s['Nama_Skill'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="roleRank" class="form-select">
                    <option value="">Minat Role</option>
                    <?php foreach($roleList as $r): ?><option value="<?= $r['ID_Role'] ?>"><?= $r['Nama_Role'] ?></option><?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4">Rank</th>
                        <th>Mahasiswa</th>
                        <th>Fakultas</th>
                        <th>Prodi</th>
                        <th class="text-end pe-4">Total Points</th>
                    </tr>
                </thead>
                <tbody id="rankBody">
                    <tr><td colspan="5" class="text-center py-4">Memuat...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    loadRank();
    const inputs = document.querySelectorAll('#filterRank input, #filterRank select');
    inputs.forEach(input => { input.addEventListener('input', debounce(loadRank, 300)); });
});

function loadRank() {
    const q = document.getElementById('qRank').value;
    const fak = document.getElementById('fakRank').value;
    const skill = document.getElementById('skillRank').value;
    const role = document.getElementById('roleRank').value;
    const tbody = document.getElementById('rankBody');

    fetch(`fetch_data.php?page=ranking&q=${encodeURIComponent(q)}&fakultas=${fak}&skill=${skill}&role=${role}`)
        .then(response => response.text())
        .then(html => { tbody.innerHTML = html; });
}
function debounce(func, wait) { let timeout; return function(...args) { clearTimeout(timeout); timeout = setTimeout(() => func(...args), wait); }; }
</script>