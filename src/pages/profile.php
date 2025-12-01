<?php
$idMhs = $_GET['id'] ?? 0;

if (!$idMhs) {
    echo "<div class='alert alert-danger'>Mahasiswa tidak ditemukan.</div>";
    exit;
}

// Ambil Profil
$stmt = $pdo->prepare("SELECT m.*, p.Nama_Prodi, f.Nama_Fakultas 
                       FROM Mahasiswa m 
                       LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
                       LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
                       WHERE m.ID_Mahasiswa = ?");
$stmt->execute([$idMhs]);
$mhs = $stmt->fetch();

// Ambil Keahlian
$stmtSkill = $pdo->prepare("SELECT k.Nama_Keahlian FROM Mahasiswa_Keahlian mk JOIN Keahlian k ON mk.ID_Keahlian = k.ID_Keahlian WHERE mk.ID_Mahasiswa = ?");
$stmtSkill->execute([$idMhs]);
$skills = $stmtSkill->fetchAll(PDO::FETCH_COLUMN);

// Ambil Riwayat Lomba LENGKAP dengan Poin dan Peringkat
$sqlHistory = "SELECT l.Nama_Lomba, l.Tanggal_Selesai, 
                      t.Nama_Tim, t.ID_Tim, k.Nama_Kategori,
                      pj.Nama_Peringkat, pj.Multiplier_Poin,
                      tl.Poin_Dasar, jp.Bobot_Poin
               FROM (
                   SELECT ID_Tim, ID_Mahasiswa FROM Keanggotaan_Tim WHERE ID_Mahasiswa = ?
                   UNION
                   SELECT ID_Tim, ID_Mahasiswa_Ketua as ID_Mahasiswa FROM Tim WHERE ID_Mahasiswa_Ketua = ?
               ) as participation
               JOIN Tim t ON participation.ID_Tim = t.ID_Tim
               JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba
               JOIN Kategori_Lomba k ON l.ID_Kategori = k.ID_Kategori
               JOIN Tingkatan_Lomba tl ON l.ID_Tingkatan = tl.ID_Tingkatan
               JOIN Jenis_Penyelenggara jp ON l.ID_Jenis_Penyelenggara = jp.ID_Jenis
               LEFT JOIN Peringkat_Juara pj ON t.ID_Peringkat = pj.ID_Peringkat
               ORDER BY l.Tanggal_Selesai DESC";

$stmtHistory = $pdo->prepare($sqlHistory);
$stmtHistory->execute([$idMhs, $idMhs]);
$history = $stmtHistory->fetchAll();
?>

<div class="mb-3">
    <a href="?page=ranking" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali ke Leaderboard</a>
</div>

<div class="row">
    <!-- Bio Card -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center pt-5 pb-4">
                <div class="mx-auto mb-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 120px; height: 120px; font-size: 3.5rem;">
                    <?= substr($mhs['Nama_Mahasiswa'], 0, 1) ?>
                </div>
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($mhs['Nama_Mahasiswa']) ?></h3>
                <p class="text-muted"><?= $mhs['NIM'] ?></p>
                
                <span class="badge bg-primary fs-6 mb-3"><?= $mhs['Nama_Prodi'] ?></span>
                
                <?php if($mhs['Bio']): ?>
                    <p class="text-muted fst-italic px-3">"<?= htmlspecialchars($mhs['Bio']) ?>"</p>
                <?php endif; ?>

                <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
                    <?php foreach($skills as $skill): ?>
                        <span class="badge bg-light text-dark border"><?= $skill ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer bg-light p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-uppercase small fw-bold text-muted">Total Rating Points</span>
                    <span class="fs-4 fw-bold text-warning"><i class="fas fa-star me-2"></i><?= number_format($mhs['Total_Poin']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- History Card -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 fw-bold"><i class="fas fa-history me-2"></i>Riwayat Kompetisi & Poin</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if(empty($history)): ?>
                        <div class="p-4 text-center text-muted">Belum ada riwayat kompetisi.</div>
                    <?php endif; ?>

                    <?php foreach($history as $h): ?>
                    <?php 
                        // Hitung Poin Per Lomba
                        $poinDidapat = 0;
                        if ($h['Nama_Peringkat']) {
                            $poinDidapat = $h['Poin_Dasar'] * $h['Bobot_Poin'] * $h['Multiplier_Poin'];
                        }
                    ?>
                    <div class="list-group-item p-3 hover-bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($h['Nama_Lomba']) ?></h5>
                                <div class="mb-1">
                                    <span class="badge bg-secondary me-2"><?= $h['Nama_Kategori'] ?></span>
                                    <small class="text-muted"><i class="fas fa-users me-1"></i> Tim: <?= htmlspecialchars($h['Nama_Tim']) ?></small>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <!-- Status Juara & Poin -->
                                <?php if($h['Nama_Peringkat']): ?>
                                    <div class="d-flex flex-column align-items-end">
                                        <span class="badge bg-warning text-dark mb-1">
                                            <i class="fas fa-trophy me-1"></i><?= $h['Nama_Peringkat'] ?>
                                        </span>
                                        <span class="fw-bold text-success">+<?= number_format($poinDidapat) ?> Pts</span>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Belum ada hasil</span>
                                <?php endif; ?>
                                <small class="d-block text-muted mt-1"><?= $h['Tanggal_Selesai'] ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>