<?php
$tab = $_GET['tab'] ?? 'active';
$filterKategori = $_GET['cat'] ?? '';
$filterTahun = $_GET['year'] ?? '';

$sql = "SELECT l.*, k.Nama_Kategori, p.Nama_Jenis, p.Bobot_Poin, t.Nama_Tingkatan, t.Poin_Dasar 
        FROM Lomba l
        JOIN Kategori_Lomba k ON l.ID_Kategori = k.ID_Kategori
        JOIN Jenis_Penyelenggara p ON l.ID_Jenis_Penyelenggara = p.ID_Jenis
        JOIN Tingkatan_Lomba t ON l.ID_Tingkatan = t.ID_Tingkatan
        WHERE 1=1";

$params = [];

if ($tab == 'active') {
    $sql .= " AND CURDATE() BETWEEN l.Tanggal_Mulai AND l.Tanggal_Selesai";
} elseif ($tab == 'upcoming') {
    $sql .= " AND l.Tanggal_Mulai > CURDATE()";
} elseif ($tab == 'archive') {
    $sql .= " AND l.Tanggal_Selesai < CURDATE()";
    if ($filterTahun) {
        $sql .= " AND YEAR(l.Tanggal_Selesai) = ?";
        $params[] = $filterTahun;
    }
}

if ($filterKategori) {
    $sql .= " AND l.ID_Kategori = ?";
    $params[] = $filterKategori;
}

$sql .= " ORDER BY l.Tanggal_Mulai " . ($tab == 'archive' ? 'DESC' : 'ASC');
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lombas = $stmt->fetchAll();

$kategoris = $pdo->query("SELECT * FROM Kategori_Lomba")->fetchAll();
$years = $pdo->query("SELECT DISTINCT YEAR(Tanggal_Selesai) as y FROM Lomba ORDER BY y DESC")->fetchAll();
?>

<!-- HEADER & TABS -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary"><i class="fas fa-scroll me-2"></i> Data Lomba</h2>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white p-0">
        <ul class="nav nav-tabs card-header-tabs m-0">
            <li class="nav-item">
                <a class="nav-link py-3 <?= $tab=='active'?'active fw-bold border-top-0 border-start-0 border-end-0 border-bottom-3 border-danger text-danger':'' ?>" href="?page=lomba&tab=active">
                    <i class="fas fa-fire me-2"></i>Sedang Berlangsung
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 <?= $tab=='upcoming'?'active fw-bold border-bottom-3 border-primary text-primary':'' ?>" href="?page=lomba&tab=upcoming">
                    <i class="fas fa-calendar-plus me-2"></i>Akan Datang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 <?= $tab=='archive'?'active fw-bold border-bottom-3 border-secondary text-secondary':'' ?>" href="?page=lomba&tab=archive">
                    <i class="fas fa-archive me-2"></i>Arsip / Selesai
                </a>
            </li>
        </ul>
    </div>
    
    <!-- FILTER BAR -->
    <div class="card-body bg-light border-bottom">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="lomba">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            
            <div class="col-auto"><span class="fw-bold text-muted"><i class="fas fa-filter me-1"></i> Filter:</span></div>
            
            <div class="col-md-3">
                <select name="cat" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach($kategoris as $k): ?>
                        <option value="<?= $k['ID_Kategori'] ?>" <?= $filterKategori == $k['ID_Kategori'] ? 'selected' : '' ?>>
                            <?= $k['Nama_Kategori'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if($tab == 'archive'): ?>
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Tahun --</option>
                    <?php foreach($years as $y): ?>
                        <option value="<?= $y['y'] ?>" <?= $filterTahun == $y['y'] ? 'selected' : '' ?>>
                            <?= $y['y'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- LIST LOMBA -->
    <div class="list-group list-group-flush">
        <?php if(empty($lombas)): ?>
            <div class="p-5 text-center text-muted">
                <i class="far fa-folder-open fa-3x mb-3"></i><br>Tidak ada data lomba pada kategori ini.
            </div>
        <?php endif; ?>

        <?php foreach($lombas as $l): ?>
        <?php 
            $maxPoints = $l['Poin_Dasar'] * $l['Bobot_Poin'];
        ?>
        <div class="list-group-item p-4 hover-shadow position-relative">
            <div class="row align-items-center">
                <div class="col-md-1 text-center">
                    <div class="display-6 fw-bold text-secondary"><?= date('d', strtotime($l['Tanggal_Mulai'])) ?></div>
                    <small class="text-uppercase fw-bold"><?= date('M', strtotime($l['Tanggal_Mulai'])) ?></small>
                </div>
                <div class="col-md-7">
                    <h5 class="fw-bold mb-1 text-primary">
                        <a href="?page=lomba_detail&id=<?= $l['ID_Lomba'] ?>" class="text-decoration-none stretched-link"><?= htmlspecialchars($l['Nama_Lomba']) ?></a>
                    </h5>
                    <div class="mb-2">
                        <span class="badge bg-light text-dark border me-1"><?= $l['Nama_Kategori'] ?></span>
                        <span class="badge bg-light text-dark border me-1"><?= $l['Nama_Jenis'] ?></span>
                        <span class="badge bg-<?= $tab=='active'?'danger':($tab=='upcoming'?'primary':'secondary') ?>">
                            <?= $l['Nama_Tingkatan'] ?>
                        </span>
                    </div>
                    <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?= $l['Tanggal_Mulai'] ?> s.d <?= $l['Tanggal_Selesai'] ?></small>
                </div>
                <div class="col-md-4 text-end">
                    <div class="mb-2">
                        <small class="text-muted d-block">Max Rating Points:</small>
                        <span class="fs-5 fw-bold text-warning"><i class="fas fa-star me-1"></i><?= number_format($maxPoints) ?></span>
                    </div>
                    <a href="?page=lomba_detail&id=<?= $l['ID_Lomba'] ?>" class="btn btn-sm btn-outline-primary rounded-pill position-relative z-index-2">Lihat Detail & Tim</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>