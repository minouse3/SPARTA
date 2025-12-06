<?php
// FILE: Halaman Cari Tim (Public Marketplace)

// Initial Load (Agar halaman tidak kosong saat pertama dibuka)
$sql = "SELECT t.*, l.Nama_Lomba, m.Nama_Mahasiswa as Ketua, pj.Nama_Peringkat 
        FROM Tim t 
        JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba 
        JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa 
        LEFT JOIN Peringkat_Juara pj ON t.ID_Peringkat = pj.ID_Peringkat
        WHERE t.Status_Pencarian = 'Terbuka'
        ORDER BY t.ID_Tim DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$timList = $stmt->fetchAll();

// List Lomba untuk Filter
$lombaList = $pdo->query("SELECT * FROM Lomba WHERE Tanggal_Selesai >= CURDATE() ORDER BY Nama_Lomba ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-primary"><i class="fas fa-search me-2"></i>Cari Tim</h2>
        <p class="text-muted mb-0">Temukan tim yang sedang membutuhkan keahlianmu.</p>
    </div>
    <a href="?page=manajemen_tim" class="btn btn-outline-primary">
        <i class="fas fa-plus-circle me-2"></i>Buat Tim Sendiri
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light">
        <div class="row g-2">
            <div class="col-md-8">
                <input type="text" id="searchInput" class="form-control" placeholder="Ketik nama tim untuk mencari..." autocomplete="off">
            </div>
            <div class="col-md-4">
                <select id="lombaSelect" class="form-select">
                    <option value="">-- Semua Kompetisi --</option>
                    <?php foreach($lombaList as $l): ?>
                        <option value="<?= $l['ID_Lomba'] ?>"><?= htmlspecialchars($l['Nama_Lomba']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="row" id="timContainer">
    <?php if(empty($timList)): ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="fas fa-users-slash fa-3x mb-3"></i>
            <p>Belum ada tim yang membuka pendaftaran.</p>
        </div>
    <?php endif; ?>

    <?php foreach($timList as $t): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm hover-card border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="badge bg-success">OPEN</span>
                    <?php if($t['Nama_Peringkat']): ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-trophy"></i> <?= $t['Nama_Peringkat'] ?></span>
                    <?php endif; ?>
                </div>
                
                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($t['Nama_Tim']) ?></h5>
                <small class="text-primary fw-bold mb-3 d-block"><?= htmlspecialchars($t['Nama_Lomba']) ?></small>
                
                <p class="small text-muted mb-3" style="min-height: 40px;">
                    <?= $t['Deskripsi_Tim'] ? htmlspecialchars(substr($t['Deskripsi_Tim'], 0, 80)).'...' : 'Belum ada deskripsi.' ?>
                </p>

                <div class="d-flex align-items-center bg-light p-2 rounded mb-3">
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                        <?= substr($t['Ketua'], 0, 1) ?>
                    </div>
                    <div class="lh-1">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Ketua</small>
                        <span class="fw-bold small"><?= htmlspecialchars($t['Ketua']) ?></span>
                    </div>
                </div>

                <button type="button" class="btn btn-primary w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#detailModalInitial<?= $t['ID_Tim'] ?>">
                    Lihat Detail
                </button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModalInitial<?= $t['ID_Tim'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><?= htmlspecialchars($t['Nama_Tim']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Lomba:</strong> <?= $t['Nama_Lomba'] ?></p>
                    <p><strong>Deskripsi:</strong><br><?= nl2br(htmlspecialchars($t['Deskripsi_Tim'])) ?></p>
                    <hr>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-2"></i>Hubungi ketua tim untuk bergabung.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
    /* Animasi halus saat hasil pencarian muncul */
    .fade-in { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const lombaSelect = document.getElementById('lombaSelect');
    const container = document.getElementById('timContainer');
    let timeout = null;

    function fetchTim() {
        const q = searchInput.value;
        const lomba = lombaSelect.value;

        // Efek loading sederhana (opacity)
        container.style.opacity = '0.5';

        // Panggil API fetch_tim.php
        fetch(`fetch_tim.php?q=${encodeURIComponent(q)}&lomba=${encodeURIComponent(lomba)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                container.style.opacity = '1';
            })
            .catch(err => {
                console.error('Error fetching data:', err);
                container.style.opacity = '1';
            });
    }

    // Event Listener untuk Search (Debounce 300ms)
    // Artinya: Tunggu user berhenti mengetik selama 300ms baru kirim request
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(fetchTim, 300);
    });

    // Event Listener untuk Dropdown Lomba
    lombaSelect.addEventListener('change', fetchTim);
});
</script>