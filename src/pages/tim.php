<?php
// FILE: Halaman Cari Tim (Public Marketplace) - Modern UI

// Initial Load
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

<style>
    .team-card {
        border: none;
        border-radius: 15px;
        transition: transform 0.2s, box-shadow 0.2s;
        overflow: hidden;
    }
    .team-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
    }
    .leader-avatar {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
    }
    .search-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: none;
    }
</style>

<div class="row align-items-end mb-4">
    <div class="col-md-6">
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Cari Tim</h3>
        <p class="text-muted mb-0">Temukan tim hebat untuk berkolaborasi dan berkompetisi.</p>
    </div>
    <div class="col-md-6 mt-3 mt-md-0">
        <div class="search-card p-2">
            <div class="row g-2">
                <div class="col-7 col-lg-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0 text-muted ps-3"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchInput" class="form-control border-0 shadow-none" placeholder="Cari nama tim..." autocomplete="off">
                    </div>
                </div>
                <div class="col-5 col-lg-4 border-start">
                    <select id="lombaSelect" class="form-select border-0 shadow-none text-muted" style="cursor: pointer;">
                        <option value="">Semua Lomba</option>
                        <?php foreach($lombaList as $l): ?>
                            <option value="<?= $l['ID_Lomba'] ?>"><?= htmlspecialchars(substr($l['Nama_Lomba'], 0, 15)) ?>...</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4" id="timContainer">
    <?php if(empty($timList)): ?>
        <div class="col-12 text-center py-5 text-muted">
            <div class="opacity-50 mb-3"><i class="fas fa-users-slash fa-4x"></i></div>
            <h5>Belum ada tim yang membuka pendaftaran.</h5>
            <p>Jadilah yang pertama membuat tim!</p>
            <a href="?page=manajemen_tim" class="btn btn-primary rounded-pill mt-2">Buat Tim</a>
        </div>
    <?php endif; ?>

    <?php foreach($timList as $t): ?>
    <div class="col-md-6 col-lg-4 fade-in">
        <div class="card team-card h-100 shadow-sm">
            <div class="card-body p-4 d-flex flex-column">
                
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3">OPEN</span>
                    <?php if($t['Nama_Peringkat']): ?>
                        <span class="badge bg-warning text-dark rounded-pill"><i class="fas fa-trophy me-1"></i> <?= $t['Nama_Peringkat'] ?></span>
                    <?php endif; ?>
                </div>
                
                <h5 class="fw-bold text-dark mb-1 text-truncate"><?= htmlspecialchars($t['Nama_Tim']) ?></h5>
                <small class="text-primary fw-bold text-uppercase mb-3 d-block text-truncate" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <?= htmlspecialchars($t['Nama_Lomba']) ?>
                </small>
                
                <p class="text-muted small mb-4 flex-grow-1" style="line-height: 1.6;">
                    <?= $t['Deskripsi_Tim'] ? htmlspecialchars(substr($t['Deskripsi_Tim'], 0, 90)).(strlen($t['Deskripsi_Tim'])>90?'...':'') : 'Tidak ada deskripsi.' ?>
                </p>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                    <div class="d-flex align-items-center">
                        <div class="leader-avatar me-2 shadow-sm">
                            <?= strtoupper(substr($t['Ketua'], 0, 1)) ?>
                        </div>
                        <div class="lh-1">
                            <small class="text-muted d-block" style="font-size: 0.65rem;">Team Leader</small>
                            <span class="fw-bold text-dark small"><?= htmlspecialchars($t['Ketua']) ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-light text-primary rounded-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#detailModalInitial<?= $t['ID_Tim'] ?>" title="Lihat Detail">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModalInitial<?= $t['ID_Tim'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-primary"><?= htmlspecialchars($t['Nama_Tim']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase">Target Kompetisi</label>
                        <div class="fw-bold text-dark"><?= $t['Nama_Lomba'] ?></div>
                    </div>
                    <div class="mb-4">
                        <label class="small fw-bold text-muted text-uppercase">Deskripsi & Kebutuhan</label>
                        <div class="bg-light p-3 rounded text-muted mt-1">
                            <?= nl2br(htmlspecialchars($t['Deskripsi_Tim'])) ?>
                        </div>
                    </div>
                    
                    <div class="alert alert-info border-0 d-flex align-items-center mb-0">
                        <i class="fas fa-info-circle fa-lg me-3"></i>
                        <div>
                            <strong>Tertarik bergabung?</strong><br>
                            <small>Silakan hubungi ketua tim secara langsung.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const lombaSelect = document.getElementById('lombaSelect');
    const container = document.getElementById('timContainer');
    let timeout = null;

    function fetchTim() {
        const q = searchInput.value;
        const lomba = lombaSelect.value;
        
        container.style.opacity = '0.5'; // Visual feedback

        fetch(`fetch_tim.php?q=${encodeURIComponent(q)}&lomba=${encodeURIComponent(lomba)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                container.style.opacity = '1';
            })
            .catch(err => {
                console.error('Error:', err);
                container.style.opacity = '1';
            });
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(fetchTim, 300);
    });

    lombaSelect.addEventListener('change', fetchTim);
});
</script>