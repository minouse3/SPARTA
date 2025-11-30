<?php
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID Lomba tidak ditemukan.</div>";
    exit;
}

$idLomba = $_GET['id'];

// Ambil Detail Lomba
$stmt = $pdo->prepare("SELECT l.*, k.Nama_Kategori, p.Nama_Jenis, p.Bobot_Poin, t.Nama_Tingkatan, t.Poin_Dasar 
                       FROM Lomba l
                       JOIN Kategori_Lomba k ON l.ID_Kategori = k.ID_Kategori
                       JOIN Jenis_Penyelenggara p ON l.ID_Jenis_Penyelenggara = p.ID_Jenis
                       JOIN Tingkatan_Lomba t ON l.ID_Tingkatan = t.ID_Tingkatan
                       WHERE l.ID_Lomba = ?");
$stmt->execute([$idLomba]);
$lomba = $stmt->fetch();

if (!$lomba) {
    echo "<div class='alert alert-danger'>Lomba tidak ditemukan.</div>";
    exit;
}

// Ambil Tim Peserta
$stmtTim = $pdo->prepare("SELECT t.*, m.Nama_Mahasiswa as Ketua, m.NIM, d.Nama_Dosen, 
                          (SELECT COUNT(*) FROM Keanggotaan_Tim WHERE ID_Tim = t.ID_Tim) as Jml_Anggota
                          FROM Tim t 
                          JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa
                          LEFT JOIN Dosen_Pembimbing d ON t.ID_Dosen_Pembimbing = d.ID_Dosen
                          WHERE t.ID_Lomba = ?");
$stmtTim->execute([$idLomba]);
$timList = $stmtTim->fetchAll();

$maxPoints = $lomba['Poin_Dasar'] * $lomba['Bobot_Poin'];
$status = (date('Y-m-d') > $lomba['Tanggal_Selesai']) ? 'Selesai' : ((date('Y-m-d') >= $lomba['Tanggal_Mulai']) ? 'Berlangsung' : 'Akan Datang');
$statusColor = ($status == 'Selesai') ? 'secondary' : (($status == 'Berlangsung') ? 'danger' : 'primary');
?>

<div class="mb-3">
    <a href="?page=lomba" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
</div>

<div class="row">
    <!-- Info Lomba -->
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h2 class="fw-bold text-primary mb-0"><?= htmlspecialchars($lomba['Nama_Lomba']) ?></h2>
                    <span class="badge bg-<?= $statusColor ?> fs-6"><?= $status ?></span>
                </div>
                
                <p class="lead"><?= nl2br(htmlspecialchars($lomba['Deskripsi'])) ?></p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <small class="text-muted text-uppercase fw-bold">Kategori</small>
                        <div class="fw-bold fs-5"><?= $lomba['Nama_Kategori'] ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <small class="text-muted text-uppercase fw-bold">Penyelenggara</small>
                        <div class="fw-bold fs-5"><?= $lomba['Nama_Jenis'] ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <small class="text-muted text-uppercase fw-bold">Tingkatan</small>
                        <div class="fw-bold fs-5"><?= $lomba['Nama_Tingkatan'] ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <small class="text-muted text-uppercase fw-bold">Max Points</small>
                        <div class="fw-bold text-warning fs-5"><i class="fas fa-star me-1"></i> <?= number_format($maxPoints) ?></div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between text-muted">
                    <span><i class="fas fa-calendar-day me-2"></i>Mulai: <b><?= $lomba['Tanggal_Mulai'] ?></b></span>
                    <span><i class="fas fa-flag-checkered me-2"></i>Selesai: <b><?= $lomba['Tanggal_Selesai'] ?></b></span>
                </div>
            </div>
        </div>

        <!-- Daftar Tim Peserta -->
        <h4 class="fw-bold mb-3"><i class="fas fa-users me-2"></i>Tim Peserta (<?= count($timList) ?>)</h4>
        
        <?php if(empty($timList)): ?>
            <div class="alert alert-info">Belum ada tim yang mendaftar di lomba ini.</div>
        <?php endif; ?>

        <div class="row">
            <?php foreach($timList as $tim): ?>
            <div class="col-md-6 mb-3">
                <div class="card h-100 hover-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($tim['Nama_Tim']) ?></h5>
                            <span class="badge bg-<?= $tim['Status_Pencarian']=='Terbuka'?'success':'secondary' ?>"><?= $tim['Status_Pencarian'] ?></span>
                        </div>
                        <p class="text-muted small mb-2"><?= $tim['Deskripsi_Tim'] ?? 'Tidak ada deskripsi tim.' ?></p>
                        
                        <div class="d-flex align-items-center mt-3 bg-light p-2 rounded">
                            <i class="fas fa-user-circle fa-2x text-secondary me-2"></i>
                            <div class="lh-1">
                                <small class="text-muted">Ketua</small>
                                <div class="fw-bold"><?= htmlspecialchars($tim['Ketua']) ?></div>
                            </div>
                        </div>
                        <div class="mt-2 text-end">
                            <a href="?page=tim&view=detail&id=<?= $tim['ID_Tim'] ?>" class="btn btn-sm btn-primary">Lihat Anggota</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold">Lokasi / Tautan</div>
            <div class="card-body">
                <?php if($lomba['Link_Web']): ?>
                    <a href="<?= $lomba['Link_Web'] ?>" target="_blank" class="btn btn-outline-primary w-100 mb-2"><i class="fas fa-globe me-2"></i>Website Lomba</a>
                <?php endif; ?>
                <div class="text-muted"><i class="fas fa-map-marker-alt me-2"></i> <?= $lomba['Lokasi'] ?? 'Online' ?></div>
            </div>
        </div>
    </div>
</div>