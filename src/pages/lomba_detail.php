<?php
// FILE: Halaman Detail Lomba (Premium UI - Multi-Category Support)

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger border-0 shadow-sm m-4'>ID Lomba tidak ditemukan.</div>";
    exit;
}

$idLomba = $_GET['id'];

// 1. AMBIL DETAIL LOMBA
$stmt = $pdo->prepare("SELECT l.*, p.Nama_Jenis, p.Bobot_Poin, t.Nama_Tingkatan, t.Poin_Dasar 
                       FROM Lomba l
                       LEFT JOIN Jenis_Penyelenggara p ON l.ID_Jenis_Penyelenggara = p.ID_Jenis
                       LEFT JOIN Tingkatan_Lomba t ON l.ID_Tingkatan = t.ID_Tingkatan
                       WHERE l.ID_Lomba = ?");
$stmt->execute([$idLomba]);
$lomba = $stmt->fetch();

if (!$lomba) {
    echo "<div class='alert alert-danger border-0 shadow-sm m-4'>Lomba tidak ditemukan.</div>";
    exit;
}

// 2. AMBIL KATEGORI (Multi-Categories)
$stmtKat = $pdo->prepare("SELECT k.Nama_Kategori 
                          FROM Lomba_Kategori lk
                          JOIN Kategori_Lomba k ON lk.ID_Kategori = k.ID_Kategori
                          WHERE lk.ID_Lomba = ?");
$stmtKat->execute([$idLomba]);
$kategoriList = $stmtKat->fetchAll(PDO::FETCH_COLUMN);

if (empty($kategoriList)) $kategoriList[] = 'Umum';

// 3. AMBIL TIM PESERTA
$stmtTim = $pdo->prepare("SELECT t.*, m.Nama_Mahasiswa as Ketua, m.NIM, d.Nama_Dosen, 
                          (SELECT COUNT(*) FROM Keanggotaan_Tim WHERE ID_Tim = t.ID_Tim) as Jml_Anggota
                          FROM Tim t 
                          JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa
                          LEFT JOIN Dosen_Pembimbing d ON t.ID_Dosen_Pembimbing = d.ID_Dosen
                          WHERE t.ID_Lomba = ?
                          ORDER BY t.ID_Tim DESC");
$stmtTim->execute([$idLomba]);
$timList = $stmtTim->fetchAll();

// 4. LOGIC STATUS
$maxPoints = ($lomba['Poin_Dasar'] ?? 0) * ($lomba['Bobot_Poin'] ?? 0);
$today = date('Y-m-d');
if ($today > $lomba['Tanggal_Selesai']) {
    $status = 'Selesai';
    $statusClass = 'bg-secondary';
    $statusIcon = 'fa-flag-checkered';
} elseif ($today >= $lomba['Tanggal_Mulai']) {
    $status = 'Berlangsung';
    $statusClass = 'bg-danger';
    $statusIcon = 'fa-fire';
} else {
    $status = 'Akan Datang';
    $statusClass = 'bg-info text-dark';
    $statusIcon = 'fa-calendar-alt';
}
?>

<style>
    /* Hero Header */
    .lomba-header {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
        border-radius: 20px;
        padding: 3rem 2.5rem;
        position: relative;
        overflow: hidden;
        margin-bottom: -50px; 
        box-shadow: 0 10px 30px rgba(13, 110, 253, 0.2);
    }
    .lomba-header::after {
        content: ''; position: absolute; top: 0; right: 0; bottom: 0; left: 0;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.6;
    }
    
    /* Info Cards */
    .metric-card {
        background: white; border-radius: 15px; border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.2s; height: 100%;
        display: flex; flex-direction: column; justify-content: center;
        padding: 1.5rem;
    }
    .metric-card:hover { transform: translateY(-5px); }
    .metric-icon {
        width: 45px; height: 45px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; margin-bottom: 10px;
    }

    /* Content Card */
    .content-card {
        background: white; border-radius: 15px; border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05); position: relative; z-index: 2;
    }

    /* Team Card */
    .team-card {
        border: 1px solid #f0f0f0; border-radius: 12px;
        transition: all 0.2s; border-left: 4px solid transparent;
    }
    .team-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .team-card.open { border-left-color: #198754; }
    .team-card.closed { border-left-color: #6c757d; }

    /* Custom Badge for Categories */
    .category-badge-header {
        background: rgba(255, 255, 255, 0.2); 
        border: 1px solid rgba(255, 255, 255, 0.4); 
        color: white;
        padding: 6px 12px;
        font-size: 0.8rem;
        font-weight: 500;
        border-radius: 50px;
    }
</style>

<div class="mb-4">
    <a href="?page=lomba" class="btn btn-light btn-sm shadow-sm text-secondary fw-bold rounded-pill px-3">
        <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar
    </a>
</div>

<div class="lomba-header">
    <div class="position-relative z-index-2">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="badge <?= $statusClass ?> rounded-pill px-3 py-2 fw-normal shadow-sm">
                <i class="fas <?= $statusIcon ?> me-1"></i> <?= $status ?>
            </span>
            <?php foreach($kategoriList as $kat): ?>
                <span class="category-badge-header">
                    <?= htmlspecialchars($kat) ?>
                </span>
            <?php endforeach; ?>
        </div>
        
        <h2 class="fw-bold mb-2 text-white" style="font-family: 'Roboto Slab', serif; letter-spacing: 0.5px;">
            <?= htmlspecialchars($lomba['Nama_Lomba']) ?>
        </h2>
        
        <div class="d-flex align-items-center text-white text-opacity-75 small mt-2">
            <span class="me-3"><i class="fas fa-map-marker-alt me-1"></i> <?= $lomba['Lokasi'] ?? 'Online' ?></span>
        </div>
    </div>
</div>

<div class="row g-4 px-2">
    <div class="col-lg-8">
        
        <div class="row g-3 mb-4 pt-5 mt-2">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-star"></i>
                    </div>
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Max Poin</small>
                    <h4 class="fw-bold text-dark mb-0"><?= number_format($maxPoints) ?></h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Tingkatan</small>
                    <h5 class="fw-bold text-dark mb-0"><?= $lomba['Nama_Tingkatan'] ?? '-' ?></h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-building"></i>
                    </div>
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Penyelenggara</small>
                    <h5 class="fw-bold text-dark mb-0 text-truncate" title="<?= $lomba['Nama_Jenis'] ?? '-' ?>"><?= $lomba['Nama_Jenis'] ?? '-' ?></h5>
                </div>
            </div>
        </div>

        <div class="content-card p-4 mb-5">
            <h5 class="fw-bold text-dark mb-3"><i class="fas fa-align-left me-2 text-secondary"></i>Deskripsi Kompetisi</h5>
            <div class="text-muted mb-4" style="line-height: 1.8;">
                <?= nl2br(htmlspecialchars($lomba['Deskripsi'])) ?>
            </div>
            
            <div class="bg-light rounded-3 p-4 border border-dashed">
                <h6 class="fw-bold text-dark mb-3 small text-uppercase"><i class="far fa-clock me-2"></i>Jadwal Pelaksanaan</h6>
                <div class="d-flex flex-wrap gap-4 align-items-center">
                    <div>
                        <small class="text-muted d-block mb-1">Tanggal Mulai</small>
                        <span class="fw-bold text-primary fs-5"><?= date('d M Y', strtotime($lomba['Tanggal_Mulai'])) ?></span>
                    </div>
                    <div class="d-none d-sm-block text-muted"><i class="fas fa-arrow-right"></i></div>
                    <div>
                        <small class="text-muted d-block mb-1">Tanggal Selesai</small>
                        <span class="fw-bold text-danger fs-5"><?= date('d M Y', strtotime($lomba['Tanggal_Selesai'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0">
                Tim Terdaftar <span class="badge bg-light text-dark border ms-1 rounded-pill"><?= count($timList) ?></span>
            </h5>
            <a href="?page=tim&lomba=<?= $idLomba ?>" class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                Cari Lowongan <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <?php if(empty($timList)): ?>
            <div class="alert alert-light border text-center py-4 text-muted">
                <i class="fas fa-users-slash me-2"></i>Belum ada tim yang terdaftar di kompetisi ini.
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <?php foreach($timList as $tim): ?>
            <div class="col-md-6">
                <div class="card h-100 team-card p-3 shadow-sm <?= $tim['Status_Pencarian']=='Terbuka'?'open':'closed' ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 70%;">
                            <?= htmlspecialchars($tim['Nama_Tim']) ?>
                        </h6>
                        <span class="badge <?= $tim['Status_Pencarian']=='Terbuka'?'bg-success':'bg-secondary' ?> bg-opacity-10 <?= $tim['Status_Pencarian']=='Terbuka'?'text-success':'text-secondary' ?> border rounded-pill" style="font-size: 0.6rem;">
                            <?= $tim['Status_Pencarian'] ?>
                        </span>
                    </div>
                    
                    <p class="text-muted small mb-3 text-truncate">
                        <?= $tim['Deskripsi_Tim'] ? htmlspecialchars($tim['Deskripsi_Tim']) : 'Tidak ada deskripsi.' ?>
                    </p>
                    
                    <div class="d-flex align-items-center justify-content-between mt-auto border-top pt-2">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-light border text-secondary d-flex align-items-center justify-content-center fw-bold me-2" style="width: 25px; height: 25px; font-size: 0.7rem;">
                                <?= strtoupper(substr($tim['Ketua'], 0, 1)) ?>
                            </div>
                            <small class="text-dark fw-bold"><?= htmlspecialchars($tim['Ketua']) ?></small>
                        </div>
                        <a href="?page=kelola_tim&id=<?= $tim['ID_Tim'] ?>" class="text-decoration-none small fw-bold text-primary">
                            Lihat Tim <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <div class="col-lg-4 mt-5 mt-lg-0">
        <div class="sticky-top" style="top: 100px; z-index: 1;">
            
            <?php if($lomba['Link_Web']): ?>
            <div class="content-card p-4 mb-4 text-center">
                <h6 class="fw-bold text-dark mb-3">Tautan Kompetisi</h6>
                <a href="<?= $lomba['Link_Web'] ?>" target="_blank" class="btn btn-outline-primary w-100 rounded-pill fw-bold mb-3 shadow-sm">
                    <i class="fas fa-globe me-2"></i>Kunjungi Website Resmi
                </a>
            </div>
            <?php endif; ?>

            <div class="content-card p-4 mb-4 text-center">
                <h6 class="fw-bold text-dark mb-3">Aksi Cepat</h6>
                <button class="btn btn-gradient-primary w-100 rounded-pill fw-bold mb-3 shadow-sm" onclick="window.location.href='?page=manajemen_tim'">
                    <i class="fas fa-users-cog me-2"></i>Buat Tim Baru
                </button>
                <a href="?page=tim&lomba=<?= $idLomba ?>" class="btn btn-outline-secondary w-100 rounded-pill fw-bold">
                    <i class="fas fa-search me-2"></i>Cari Lowongan Tim
                </a>
            </div>

            <div class="alert alert-info border-0 shadow-sm rounded-3">
                <div class="d-flex">
                    <i class="fas fa-lightbulb fs-4 me-3 mt-1"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Butuh Pembimbing?</h6>
                        <p class="small mb-0 opacity-75">Gunakan menu **Cari Dosen** untuk menemukan dosen dengan minat riset yang sesuai dengan kategori lomba ini.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>