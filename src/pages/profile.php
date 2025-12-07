<?php
// FILE: Halaman Profil Publik Mahasiswa (Riwayat Tim Fixed & Poin Calculation Fixed)

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>"; exit;
}

$idMhs = $_GET['id'] ?? ($_SESSION['user_id'] ?? 0);

if (!$idMhs) {
    echo "<div class='alert alert-danger'>ID Mahasiswa tidak ditemukan.</div>";
    exit;
}

// 1. Ambil Data Mahasiswa
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

// 2. Ambil Skill (Tools)
$stmtSkill = $pdo->prepare("SELECT s.Nama_Skill FROM Mahasiswa_Skill ms 
                            JOIN Skill s ON ms.ID_Skill = s.ID_Skill 
                            WHERE ms.ID_Mahasiswa = ?");
$stmtSkill->execute([$idMhs]);
$skills = $stmtSkill->fetchAll(PDO::FETCH_COLUMN);

// 3. Ambil Role (Minat Profesi)
$stmtRole = $pdo->prepare("SELECT r.Nama_Role FROM Mahasiswa_Role mr 
                           JOIN Role_Tim r ON mr.ID_Role = r.ID_Role 
                           WHERE mr.ID_Mahasiswa = ?");
$stmtRole->execute([$idMhs]);
$roles = $stmtRole->fetchAll(PDO::FETCH_COLUMN);

// 4. Ambil Tim yang Diikuti (FIXED: Menggunakan JOIN Tingkatan_Lomba & SELECT tl.Poin_Dasar)
$sqlTim = "
    (SELECT 
        t.ID_Tim, t.Nama_Tim, t.ID_Lomba, l.Nama_Lomba, 
        kt.Peran AS RolePeran,
        k.Nama_Kategori, pj.Nama_Peringkat, pj.Multiplier_Poin,
        tl.Poin_Dasar, p.Bobot_Poin, /* FIX: Menggunakan alias tl */
        'Member' as RoleType
     FROM Keanggotaan_Tim kt
     JOIN Tim t ON kt.ID_Tim = t.ID_Tim
     LEFT JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba
     LEFT JOIN Kategori_Lomba k ON t.ID_Kategori = k.ID_Kategori
     LEFT JOIN Peringkat_Juara pj ON t.ID_Peringkat = pj.ID_Peringkat
     LEFT JOIN Jenis_Penyelenggara p ON l.ID_Jenis_Penyelenggara = p.ID_Jenis
     LEFT JOIN Tingkatan_Lomba tl ON l.ID_Tingkatan = tl.ID_Tingkatan /* FIX: Tambah Join Tingkatan_Lomba */
     WHERE kt.ID_Mahasiswa = ?)
    UNION
    (SELECT 
        t.ID_Tim, t.Nama_Tim, t.ID_Lomba, l.Nama_Lomba, 
        'Leader' AS RolePeran,
        k.Nama_Kategori, pj.Nama_Peringkat, pj.Multiplier_Poin,
        tl.Poin_Dasar, p.Bobot_Poin, /* FIX: Menggunakan alias tl */
        'Leader' as RoleType
     FROM Tim t
     LEFT JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba
     LEFT JOIN Kategori_Lomba k ON t.ID_Kategori = k.ID_Kategori
     LEFT JOIN Peringkat_Juara pj ON t.ID_Peringkat = pj.ID_Peringkat
     LEFT JOIN Jenis_Penyelenggara p ON l.ID_Jenis_Penyelenggara = p.ID_Jenis
     LEFT JOIN Tingkatan_Lomba tl ON l.ID_Tingkatan = tl.ID_Tingkatan /* FIX: Tambah Join Tingkatan_Lomba */
     WHERE t.ID_Mahasiswa_Ketua = ?)
    ORDER BY ID_Tim DESC
";
// Kueri UNION membutuhkan parameter $idMhs dua kali
$stmtTim = $pdo->prepare($sqlTim);
$stmtTim->execute([$idMhs, $idMhs]);
$teams = $stmtTim->fetchAll();

// Cek apakah ini profil saya sendiri?
$isMe = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $idMhs && $_SESSION['role'] == 'mahasiswa');
?>

<style>
    .profile-cover {
        height: 180px;
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        border-radius: 15px 15px 0 0;
        position: relative;
    }
    .profile-avatar {
        width: 160px;
        height: 160px;
        object-fit: cover;
        border: 5px solid #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        background-color: #fff;
        margin-top: -90px;
        position: relative; /* Ensures image sits on top of the banner */
        z-index: 10;        /* Extra safety to ensure it's the top layer */
    }
    .stat-card {
        border-left: 4px solid #0d6efd;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
    }
</style>

<div class="mb-3">
    <a href="javascript:history.back()" class="btn btn-light btn-sm shadow-sm text-secondary fw-bold"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
</div>

<div class="card border-0 shadow-sm mb-4 overflow-hidden rounded-3">
    <div class="profile-cover"></div>
    
    <div class="card-body pt-0">
        <div class="row">
            <div class="col-md-4">
                <div class="profile-avatar-container mb-3 text-center">
                    <?php 
                        $foto = getFotoMhs($mhs['NIM'], $mhs['Foto_Profil']);
                    ?>
                    <?php if($foto): ?>
                        <img src="<?= $foto ?>?t=<?= time() ?>" class="rounded-circle profile-avatar">
                    <?php else: ?>
                        <div class="rounded-circle profile-avatar d-inline-flex align-items-center justify-content-center mx-auto bg-primary text-white fs-1 fw-bold">
                            <?= strtoupper(substr($mhs['Nama_Mahasiswa'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-center px-md-2">
                    <h4 class="fw-bold text-dark mb-0 text-center"><?= htmlspecialchars($mhs['Nama_Mahasiswa']) ?></h4>
                    <p class="text-muted small mb-2 text-center"><?= htmlspecialchars($mhs['NIM']) ?></p>
                    
                    <div class="d-flex justify-content-center mb-3">
                         <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2 rounded-pill">
                            <?= htmlspecialchars($mhs['Nama_Prodi']) ?>
                         </span>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <?php if(!empty($mhs['LinkedIn'])): ?>
                            <a href="<?= htmlspecialchars($mhs['LinkedIn']) ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle" style="width:38px; height:38px; padding-top:8px"><i class="fab fa-linkedin-in"></i></a>
                        <?php endif; ?>
                        <?php if(!empty($mhs['No_HP'])): ?>
                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $mhs['No_HP'])) ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-circle" style="width:38px; height:38px; padding-top:8px"><i class="fab fa-whatsapp"></i></a>
                        <?php endif; ?>
                        <a href="mailto:<?= htmlspecialchars($mhs['Email']) ?>" class="btn btn-outline-danger btn-sm rounded-circle" style="width:38px; height:38px; padding-top:8px"><i class="fas fa-envelope"></i></a>
                    </div>

                    <?php if($isMe): ?>
                        <a href="?page=edit_profile" class="btn btn-dark w-100 fw-bold rounded-pill shadow-sm"><i class="fas fa-cog me-2"></i>Edit Profil</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-8 pt-md-4 mt-3">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 h-100 stat-card p-3">
                            <small class="text-muted fw-bold text-uppercase">Total Poin</small>
                            <h3 class="fw-bold text-warning mb-0"><i class="fas fa-star me-2"></i><?= number_format($mhs['Total_Poin']) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 h-100 stat-card p-3" style="border-color: #198754 !important;">
                            <small class="text-muted fw-bold text-uppercase">Tim Diikuti</small>
                            <h3 class="fw-bold text-success mb-0"><i class="fas fa-users me-2"></i><?= count($teams) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 h-100 stat-card p-3" style="border-color: #6c757d !important;">
                            <small class="text-muted fw-bold text-uppercase">Fakultas</small>
                            <div class="fw-bold text-secondary text-truncate" title="<?= htmlspecialchars($mhs['Nama_Fakultas']) ?>">
                                <?= htmlspecialchars($mhs['Nama_Fakultas'] ?? '-') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-secondary text-uppercase border-bottom pb-2 mb-3"><i class="fas fa-align-left me-2"></i>Tentang Saya</h6>
                    <p class="text-muted" style="line-height: 1.6;">
                        <?= !empty($mhs['Bio']) ? nl2br(htmlspecialchars($mhs['Bio'])) : '<span class="fst-italic opacity-50">Belum ada deskripsi diri.</span>' ?>
                    </p>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="fw-bold text-secondary text-uppercase border-bottom pb-2 mb-3"><i class="fas fa-tools me-2"></i>Keahlian (Tools)</h6>
                        <?php if(!empty($skills)): ?>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach($skills as $s): ?>
                                    <span class="badge bg-success rounded-pill px-3 py-2 fw-normal fs-6" style="background-color: rgba(16, 185, 129, 0.1) !important; color: #10b981 !important;">
                                        <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($s) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <small class="text-muted fst-italic">Belum menambahkan skill.</small>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6 mb-4">
                        <h6 class="fw-bold text-secondary text-uppercase border-bottom pb-2 mb-3"><i class="fas fa-briefcase me-2"></i>Minat Role (Peran)</h6>
                        <?php if(!empty($roles)): ?>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach($roles as $r): ?>
                                    <span class="badge bg-info rounded-pill px-3 py-2 fw-normal fs-6" style="background-color: rgba(59, 130, 246, 0.1) !important; color: #3b82f6 !important;">
                                        <i class="fas fa-user-tag me-1"></i> <?= htmlspecialchars($r) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <small class="text-muted fst-italic">Belum memilih role.</small>
                        <?php endif; ?>
                    </div>
                </div>

                <h6 class="fw-bold text-secondary text-uppercase border-bottom pb-2 mb-3"><i class="fas fa-users me-2"></i>Riwayat Tim & Kompetisi</h6>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if(empty($teams)): ?>
                                <li class="list-group-item text-muted text-center py-3">Belum bergabung dengan tim manapun.</li>
                            <?php else: ?>
                                <?php foreach($teams as $t): ?>
                                <?php 
                                    $category = $t['Nama_Kategori'] ?? 'Umum';
                                    $peringkat = $t['Nama_Peringkat'];
                                    
                                    // Hitung Poin Estimasi: (Poin Dasar * Bobot * Multiplier)
                                    $poin = ($t['Poin_Dasar'] ?? 0) * ($t['Bobot_Poin'] ?? 0) * ($t['Multiplier_Poin'] ?? 0);
                                    
                                    $poinBadgeClass = ($poin > 0) ? 'bg-success text-white' : 'bg-light text-muted';
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark">
                                            <?= htmlspecialchars($t['Nama_Tim']) ?> 
                                            <span class="badge bg-light text-dark ms-2 border" style="font-size: 0.75rem;"><?= $t['RoleType'] ?></span>
                                        </div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($t['Nama_Lomba']) ?> 
                                            <?php if($category): ?> &bull; <span class="text-secondary"><?= $category ?></span><?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <div class="text-end">
                                        <?php if($peringkat): ?>
                                            <span class="badge bg-warning text-dark fw-bold mb-1" style="font-size: 0.7rem;"><?= $peringkat ?></span>
                                        <?php endif; ?>
                                        <div class="badge <?= $poinBadgeClass ?> fw-bold small" title="Poin yang dihasilkan" style="font-size: 0.75rem;">
                                            <?= number_format($poin) ?> pts
                                        </div>
                                    </div>

                                    <a href="?page=kelola_tim&id=<?= $t['ID_Tim'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-right"></i></a>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>