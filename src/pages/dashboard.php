<?php
// FILE: Dashboard Utama (Updated for Multi-Category & Role Tags)

// --- QUERIES ---

// 1. Lomba Sedang Aktif (Top 3)
// Updated: Menggunakan GROUP_CONCAT untuk multi-kategori
$sqlActive = "SELECT l.*, 
                     GROUP_CONCAT(k.Nama_Kategori SEPARATOR ', ') as Kategori_List, 
                     t.Nama_Tingkatan 
              FROM Lomba l 
              LEFT JOIN Lomba_Kategori lk ON l.ID_Lomba = lk.ID_Lomba
              LEFT JOIN Kategori_Lomba k ON lk.ID_Kategori = k.ID_Kategori
              JOIN Tingkatan_Lomba t ON l.ID_Tingkatan = t.ID_Tingkatan
              WHERE CURDATE() BETWEEN l.Tanggal_Mulai AND l.Tanggal_Selesai
              GROUP BY l.ID_Lomba
              LIMIT 3";
$activeLombas = $pdo->query($sqlActive)->fetchAll();

// 2. Lomba Akan Datang (Top 3)
// Updated: Menggunakan GROUP_CONCAT untuk multi-kategori
$sqlUpcoming = "SELECT l.*, 
                       GROUP_CONCAT(k.Nama_Kategori SEPARATOR ', ') as Kategori_List, 
                       t.Nama_Tingkatan 
                FROM Lomba l 
                LEFT JOIN Lomba_Kategori lk ON l.ID_Lomba = lk.ID_Lomba
                LEFT JOIN Kategori_Lomba k ON lk.ID_Kategori = k.ID_Kategori
                JOIN Tingkatan_Lomba t ON l.ID_Tingkatan = t.ID_Tingkatan
                WHERE l.Tanggal_Mulai > CURDATE()
                GROUP BY l.ID_Lomba
                ORDER BY l.Tanggal_Mulai ASC
                LIMIT 3";
$upcomingLombas = $pdo->query($sqlUpcoming)->fetchAll();

// 3. Tim Open Recruitment (Top 3)
// Updated: Fetch kolom Kebutuhan_Role (sudah ada di t.*)
$sqlTeams = "SELECT t.*, l.Nama_Lomba, m.Nama_Mahasiswa 
             FROM Tim t
             JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba
             JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa
             WHERE t.Status_Pencarian = 'Terbuka'
             LIMIT 3";
$openTeams = $pdo->query($sqlTeams)->fetchAll();

// 4. Top 5 Ranking Mahasiswa
$sqlRank = "SELECT m.*, p.Nama_Prodi 
            FROM Mahasiswa m 
            LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
            ORDER BY m.Total_Poin DESC LIMIT 5";
$topRanks = $pdo->query($sqlRank)->fetchAll();

// 5. Statistik Cepat
$totalMhs = $pdo->query("SELECT COUNT(*) FROM Mahasiswa")->fetchColumn();
$totalLomba = $pdo->query("SELECT COUNT(*) FROM Lomba")->fetchColumn();
?>

<style>
    .welcome-banner {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
        border-radius: 15px;
        position: relative;
        overflow: hidden;
    }
    .welcome-banner::after {
        content: '';
        position: absolute;
        top: -50%; right: -10%;
        width: 300px; height: 300px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }
    .stat-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
    }
    .icon-box {
        width: 50px; height: 50px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
    }
    .section-title {
        font-family: 'Roboto Slab', serif;
        font-weight: 700;
        color: #343a40;
    }
    .lomba-card {
        border-left: 4px solid transparent;
        transition: all 0.2s;
    }
    .lomba-card:hover { transform: translateX(5px); }
    .border-lomba-active { border-left-color: #dc3545; }
    .border-lomba-upcoming { border-left-color: #0dcaf0; }
</style>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card welcome-banner h-100 border-0 shadow-sm p-4 d-flex flex-row align-items-center justify-content-between">
            <div style="z-index: 2;">
                <h3 class="fw-bold mb-2">Musim Kompetisi 2025!</h3>
                <p class="mb-3 opacity-75" style="font-size: 0.95rem;">Bentuk tim impianmu, ikuti lomba bergengsi, dan raih poin SPARTA setinggi mungkin.</p>
                <a href="?page=lomba" class="btn btn-light text-primary fw-bold rounded-pill px-4 shadow-sm">Mulai Sekarang</a>
            </div>
            <div style="z-index: 1;" class="d-none d-sm-block">
                <i class="fas fa-trophy fa-5x text-white opacity-25"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card stat-card h-100 shadow-sm p-3">
            <div class="d-flex align-items-center">
                <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark"><?= number_format($totalMhs) ?></h2>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Mahasiswa</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card stat-card h-100 shadow-sm p-3">
            <div class="d-flex align-items-center">
                <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark"><?= number_format($totalLomba) ?></h2>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Kompetisi</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="section-title mb-0"><i class="fas fa-fire text-danger me-2"></i>Sedang Berlangsung</h5>
            <a href="?page=lomba&tab=active" class="btn btn-sm btn-link text-decoration-none text-muted">Lihat Semua <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        
        <div class="row g-3 mb-4">
            <?php if(empty($activeLombas)): ?>
                <div class="col-12"><div class="alert alert-light border text-muted text-center py-3">Tidak ada lomba aktif saat ini.</div></div>
            <?php endif; ?>
            
            <?php foreach($activeLombas as $l): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 lomba-card border-lomba-active">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger fw-normal">LIVE</span>
                            <small class="text-danger fw-bold">H-<?= (new DateTime($l['Tanggal_Selesai']))->diff(new DateTime())->days ?></small>
                        </div>
                        <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($l['Nama_Lomba']) ?>">
                            <a href="?page=lomba_detail&id=<?= $l['ID_Lomba'] ?>" class="text-decoration-none text-dark stretched-link">
                                <?= htmlspecialchars($l['Nama_Lomba']) ?>
                            </a>
                        </h6>
                        <small class="text-muted d-block mb-2 text-truncate" title="<?= htmlspecialchars($l['Kategori_List']) ?>">
                            <?= htmlspecialchars(!empty($l['Kategori_List']) ? $l['Kategori_List'] : 'Umum') ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="section-title mb-0"><i class="fas fa-calendar-alt text-info me-2"></i>Segera Hadir</h5>
            <a href="?page=lomba&tab=upcoming" class="btn btn-sm btn-link text-decoration-none text-muted">Lihat Semua <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        
        <div class="row g-3 mb-4">
            <?php if(empty($upcomingLombas)): ?>
                <div class="col-12"><div class="alert alert-light border text-muted text-center py-3">Belum ada info lomba mendatang.</div></div>
            <?php endif; ?>
            
            <?php foreach($upcomingLombas as $l): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 lomba-card border-lomba-upcoming">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info fw-normal">SOON</span>
                            <small class="text-muted fw-bold"><?= date('d M', strtotime($l['Tanggal_Mulai'])) ?></small>
                        </div>
                        <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($l['Nama_Lomba']) ?>">
                            <a href="?page=lomba_detail&id=<?= $l['ID_Lomba'] ?>" class="text-decoration-none text-dark stretched-link">
                                <?= htmlspecialchars($l['Nama_Lomba']) ?>
                            </a>
                        </h6>
                        <small class="text-muted d-block mb-2 text-truncate" title="<?= htmlspecialchars($l['Kategori_List']) ?>">
                            <?= htmlspecialchars(!empty($l['Kategori_List']) ? $l['Kategori_List'] : 'Umum') ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="section-title mb-0"><i class="fas fa-briefcase text-primary me-2"></i>Tim Mencari Anggota</h5>
                    <a href="?page=tim" class="btn btn-sm btn-outline-primary rounded-pill px-3">Cari Tim</a>
                </div>
            </div>
            <div class="list-group list-group-flush">
                <?php if(empty($openTeams)): ?>
                    <div class="list-group-item text-center text-muted py-4">Belum ada tim yang membuka rekrutmen.</div>
                <?php endif; ?>

                <?php foreach($openTeams as $t): ?>
                <a href="?page=tim&view=detail&id=<?= $t['ID_Tim'] ?>" class="list-group-item list-group-item-action p-3 d-flex align-items-center border-bottom-0 border-top">
                    <div class="me-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 45px; height: 45px;">
                            <?= strtoupper(substr($t['Nama_Tim'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($t['Nama_Tim']) ?></h6>
                        <small class="text-muted d-block mb-1">Target: <span class="text-primary"><?= htmlspecialchars($t['Nama_Lomba']) ?></span></small>
                        
                        <?php if(!empty($t['Kebutuhan_Role'])): ?>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach(array_slice(explode(',', $t['Kebutuhan_Role']), 0, 3) as $role): ?>
                                    <span class="badge bg-light text-secondary border fw-normal py-0 px-2" style="font-size: 0.65rem;">
                                        <?= htmlspecialchars(trim($role)) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if(count(explode(',', $t['Kebutuhan_Role'])) > 3): ?>
                                    <span class="badge bg-light text-muted border fw-normal py-0 px-1" style="font-size: 0.65rem;">+</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 ms-2">OPEN</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-3 h-100">
            <div class="card-header bg-white py-3 border-bottom-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="section-title mb-0 text-warning"><i class="fas fa-crown me-2"></i>Top Students</h5>
                    <a href="?page=ranking" class="small text-decoration-none">Lihat Semua</a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Rank</th>
                            <th>Mahasiswa</th>
                            <th class="text-end pe-4">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank=1; foreach($topRanks as $m): ?>
                        <tr style="cursor: pointer;" onclick="window.location='?page=profile&id=<?= $m['ID_Mahasiswa'] ?>'">
                            <td class="ps-4">
                                <?php if($rank==1): ?><i class="fas fa-medal text-warning"></i>
                                <?php elseif($rank==2): ?><i class="fas fa-medal text-secondary"></i>
                                <?php elseif($rank==3): ?><i class="fas fa-medal text-danger"></i>
                                <?php else: ?><span class="fw-bold text-muted ms-1 small">#<?= $rank ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= htmlspecialchars($m['Nama_Mahasiswa']) ?></div>
                                <div class="text-muted" style="font-size: 0.7rem;"><?= $m['Nama_Prodi'] ?></div>
                            </td>
                            <td class="text-end pe-4">
                                <span class="badge bg-warning text-dark rounded-pill"><?= number_format($m['Total_Poin']) ?></span>
                            </td>
                        </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light text-center border-0 py-3">
                <small class="text-muted opacity-75"><i class="fas fa-info-circle me-1"></i> Update Realtime</small>
            </div>
        </div>
    </div>
</div>