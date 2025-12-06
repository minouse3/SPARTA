<?php
$idMhs = $_GET['id'] ?? 0;

if (!$idMhs) {
    echo "<div class='alert alert-danger'>Mahasiswa tidak ditemukan.</div>";
    exit;
}

// Ambil Profil Lengkap
$stmt = $pdo->prepare("SELECT m.*, p.Nama_Prodi, f.Nama_Fakultas 
                       FROM Mahasiswa m 
                       LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
                       LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
                       WHERE m.ID_Mahasiswa = ?");
$stmt->execute([$idMhs]);
$mhs = $stmt->fetch();

if (!$mhs) {
    echo "<div class='alert alert-danger'>Data mahasiswa tidak ditemukan.</div>";
    exit;
}

// Ambil Keahlian
$stmtSkill = $pdo->prepare("SELECT k.Nama_Keahlian FROM Mahasiswa_Keahlian mk JOIN Keahlian k ON mk.ID_Keahlian = k.ID_Keahlian WHERE mk.ID_Mahasiswa = ?");
$stmtSkill->execute([$idMhs]);
$skills = $stmtSkill->fetchAll(PDO::FETCH_COLUMN);

// Riwayat Lomba
$stmtHistory = $pdo->prepare("SELECT l.Nama_Lomba, l.Tanggal_Selesai, t.Nama_Tim, pj.Nama_Peringkat 
                              FROM (SELECT ID_Tim FROM Keanggotaan_Tim WHERE ID_Mahasiswa = ? UNION SELECT ID_Tim FROM Tim WHERE ID_Mahasiswa_Ketua = ?) as p
                              JOIN Tim t ON p.ID_Tim = t.ID_Tim
                              JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba
                              LEFT JOIN Peringkat_Juara pj ON t.ID_Peringkat = pj.ID_Peringkat
                              ORDER BY l.Tanggal_Selesai DESC");
$stmtHistory->execute([$idMhs, $idMhs]);
$history = $stmtHistory->fetchAll();
?>

<div class="mb-3">
    <a href="?page=ranking" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali ke Leaderboard</a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center pt-5 pb-4">
                <div class="mx-auto mb-3">
                    <?php if (!empty($mhs['Foto_Profil']) && file_exists($mhs['Foto_Profil'])): ?>
                        <img src="<?= $mhs['Foto_Profil'] ?>" class="rounded-circle shadow-sm border border-3 border-white" style="width: 140px; height: 140px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm mx-auto border border-3 border-white" style="width: 140px; height: 140px; font-size: 4rem;">
                            <?= strtoupper(substr($mhs['Nama_Mahasiswa'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <h4 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($mhs['Nama_Mahasiswa']) ?></h4>
                <p class="text-muted mb-2"><?= $mhs['NIM'] ?></p>
                
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2 mb-3">
                    <?= $mhs['Nama_Prodi'] ?>
                </span>
                <div class="small text-muted mb-3"><i class="fas fa-university me-1"></i> <?= $mhs['Nama_Fakultas'] ?></div>

                <?php if($mhs['Bio']): ?>
                    <div class="bg-light p-3 rounded fst-italic text-muted small mb-3">
                        "<?= nl2br(htmlspecialchars($mhs['Bio'])) ?>"
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-center gap-2 mt-2">
                    <?php if(!empty($mhs['Email'])): ?>
                        <a href="mailto:<?= $mhs['Email'] ?>" class="btn btn-outline-danger btn-sm rounded-circle" title="Email" style="width: 38px; height: 38px; padding-top: 8px;"><i class="fas fa-envelope"></i></a>
                    <?php endif; ?>
                    
                    <?php if(!empty($mhs['No_HP'])): ?>
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $mhs['No_HP'])) ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-circle" title="WhatsApp" style="width: 38px; height: 38px; padding-top: 8px;"><i class="fab fa-whatsapp"></i></a>
                    <?php endif; ?>

                    <?php if(!empty($mhs['LinkedIn'])): ?>
                        <a href="<?= $mhs['LinkedIn'] ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle" title="LinkedIn" style="width: 38px; height: 38px; padding-top: 8px;"><i class="fab fa-linkedin-in"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card-footer bg-light p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-uppercase small fw-bold text-muted">Total Poin</span>
                    <span class="fs-4 fw-bold text-warning"><i class="fas fa-star me-1"></i><?= number_format($mhs['Total_Poin']) ?></span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold"><i class="fas fa-tools me-2 text-secondary"></i>Keahlian</div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach($skills as $skill): ?>
                        <span class="badge bg-secondary bg-opacity-10 text-dark border"><?= $skill ?></span>
                    <?php endforeach; ?>
                    <?php if(empty($skills)) echo '<small class="text-muted">Belum ada skill ditambahkan.</small>'; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 fw-bold"><i class="fas fa-trophy me-2 text-warning"></i>Riwayat Kompetisi</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if(empty($history)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-medal fa-3x mb-3 opacity-25"></i>
                            <p>Belum ada riwayat kompetisi.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach($history as $h): ?>
                    <div class="list-group-item p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($h['Nama_Lomba']) ?></h6>
                                <small class="text-muted"><i class="fas fa-users me-1"></i> Tim: <?= htmlspecialchars($h['Nama_Tim']) ?></small>
                            </div>
                            <div class="text-end">
                                <?php if($h['Nama_Peringkat']): ?>
                                    <span class="badge bg-warning text-dark mb-1"><i class="fas fa-crown me-1"></i><?= $h['Nama_Peringkat'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Partisipan</span>
                                <?php endif; ?>
                                <small class="d-block text-muted mt-1" style="font-size: 0.7rem;"><?= date('d M Y', strtotime($h['Tanggal_Selesai'])) ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>