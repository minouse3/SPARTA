<?php
// FILE: API Fetch Tim (Untuk Pencarian Realtime)
require_once 'config.php';

$search = $_GET['q'] ?? '';
$filterLomba = $_GET['lomba'] ?? '';

// Query Sama Persis dengan tim.php
$sql = "SELECT t.*, l.Nama_Lomba, m.Nama_Mahasiswa as Ketua, pj.Nama_Peringkat 
        FROM Tim t 
        JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba 
        JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa 
        LEFT JOIN Peringkat_Juara pj ON t.ID_Peringkat = pj.ID_Peringkat
        WHERE t.Status_Pencarian = 'Terbuka'";

$params = [];
if ($search) {
    $sql .= " AND t.Nama_Tim LIKE ?";
    $params[] = "%$search%";
}
if ($filterLomba) {
    $sql .= " AND t.ID_Lomba = ?";
    $params[] = $filterLomba;
}
$sql .= " ORDER BY t.ID_Tim DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$timList = $stmt->fetchAll();

if (empty($timList)) {
    echo '<div class="col-12 text-center py-5 text-muted">
            <i class="fas fa-users-slash fa-3x mb-3"></i>
            <p>Tidak ada tim yang ditemukan.</p>
          </div>';
    exit;
}

// Render Ulang Kartu Tim
foreach($timList as $t): ?>
<div class="col-md-4 mb-4 fade-in"> <div class="card h-100 shadow-sm hover-card border-0">
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

            <a href="?page=detail_tim&id=<?= $t['ID_Tim'] ?>" class="btn btn-outline-primary w-100 btn-sm rounded-pill fw-bold">
                Lihat Profil Tim &rarr;
            </a>
        </div>
    </div>
</div>

<?php endforeach; ?>