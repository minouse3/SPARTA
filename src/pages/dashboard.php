<?php
// --- QUERIES ---

// 1. Lomba Sedang Aktif (Top 3)
$sqlActive = "SELECT l.*, k.Nama_Kategori, t.Nama_Tingkatan 
              FROM Lomba l 
              JOIN Kategori_Lomba k ON l.ID_Kategori = k.ID_Kategori
              JOIN Tingkatan_Lomba t ON l.ID_Tingkatan = t.ID_Tingkatan
              WHERE CURDATE() BETWEEN l.Tanggal_Mulai AND l.Tanggal_Selesai
              LIMIT 3";
$activeLombas = $pdo->query($sqlActive)->fetchAll();

// 2. Tim Open Recruitment (Top 3)
$sqlTeams = "SELECT t.*, l.Nama_Lomba, m.Nama_Mahasiswa 
             FROM Tim t
             JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba
             JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa
             WHERE t.Status_Pencarian = 'Terbuka'
             LIMIT 3";
$openTeams = $pdo->query($sqlTeams)->fetchAll();

// 3. Top 5 Ranking Mahasiswa
$sqlRank = "SELECT m.*, p.Nama_Prodi 
            FROM Mahasiswa m 
            LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
            ORDER BY m.Total_Poin DESC LIMIT 5";
$topRanks = $pdo->query($sqlRank)->fetchAll();

// 4. Statistik Cepat
$totalMhs = $pdo->query("SELECT COUNT(*) FROM Mahasiswa")->fetchColumn();
$totalLomba = $pdo->query("SELECT COUNT(*) FROM Lomba")->fetchColumn();
?>

<!-- HEADER STATISTICS -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-users fa-3x opacity-50 me-3"></i>
                <div>
                    <h2 class="mb-0 fw-bold"><?= $totalMhs ?></h2>
                    <small>Total Mahasiswa</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-flag-checkered fa-3x opacity-50 me-3"></i>
                <div>
                    <h2 class="mb-0 fw-bold"><?= $totalLomba ?></h2>
                    <small>Total Kompetisi</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-warning text-dark h-100 shadow-sm border-0" style="background: linear-gradient(45deg, #f1c40f, #f39c12);">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fw-bold mb-1">Musim Lomba 2025!</h4>
                    <p class="mb-0 small">Segera bentuk tim dan tingkatkan poin SPARTA Anda.</p>
                </div>
                <i class="fas fa-trophy fa-4x text-white opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- KOLOM KIRI: Lomba & Tim -->
    <div class="col-lg-8">
        <!-- Section Lomba Aktif -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark border-start border-4 border-danger ps-2 mb-0">🔥 Kompetisi Sedang Berlangsung</h5>
            <a href="?page=lomba&tab=active" class="btn btn-sm btn-outline-danger rounded-pill px-3">Lihat Semua</a>
        </div>
        
        <div class="row mb-4">
            <?php if(empty($activeLombas)): ?>
                <div class="col-12"><div class="alert alert-light border">Tidak ada lomba aktif saat ini.</div></div>
            <?php endif; ?>
            
            <?php foreach($activeLombas as $l): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body p-3">
                        <span class="badge bg-danger mb-2">LIVE</span>
                        <h6 class="fw-bold mb-1 text-truncate"><?= htmlspecialchars($l['Nama_Lomba']) ?></h6>
                        <small class="text-muted d-block mb-2"><?= $l['Nama_Kategori'] ?></small>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-light text-dark border"><?= $l['Nama_Tingkatan'] ?></span>
                            <small class="text-primary fw-bold">H-<?= (new DateTime($l['Tanggal_Selesai']))->diff(new DateTime())->days ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Section Tim Open Recruitment -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark border-start border-4 border-primary ps-2 mb-0">🤝 Tim Mencari Anggota</h5>
            <a href="?page=tim" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
        </div>

        <div class="list-group shadow-sm mb-4">
            <?php foreach($openTeams as $t): ?>
            <a href="?page=tim&view=detail&id=<?= $t['ID_Tim'] ?>" class="list-group-item list-group-item-action p-3 d-flex align-items-center">
                <div class="me-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($t['Nama_Tim']) ?></h6>
                    <small class="text-muted">Target: <?= htmlspecialchars($t['Nama_Lomba']) ?></small>
                </div>
                <span class="badge bg-success rounded-pill">OPEN</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- KOLOM KANAN: Top Ranking -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-warning"><i class="fas fa-crown me-2"></i>Top Students</h5>
                    <a href="?page=ranking" class="small text-decoration-none">Lihat Peringkat</a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <tbody>
                        <?php $rank=1; foreach($topRanks as $m): ?>
                        <tr>
                            <td class="ps-3 fw-bold text-muted" style="width: 40px;">#<?= $rank++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($m['Nama_Mahasiswa']) ?></div>
                                <small class="text-muted" style="font-size: 0.75rem;"><?= $m['Nama_Prodi'] ?></small>
                            </td>
                            <td class="text-end pe-3 fw-bold text-primary"><?= number_format($m['Total_Poin']) ?> <small>pts</small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light text-center">
                <small class="text-muted">Update Realtime berdasarkan pencapaian lomba.</small>
            </div>
        </div>
    </div>
</div>