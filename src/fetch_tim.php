<?php
// FILE: API Fetch Tim Realtime (Modern UI)
require_once 'config.php';

$search = $_GET['q'] ?? '';
$filterLomba = $_GET['lomba'] ?? '';

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
    echo '<div class="col-12 text-center py-5 text-muted fade-in">
            <div class="opacity-50 mb-3"><i class="fas fa-search fa-3x"></i></div>
            <p>Tidak ada tim yang cocok dengan pencarian Anda.</p>
          </div>';
    exit;
}

foreach($timList as $t): ?>
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
                <button type="button" class="btn btn-sm btn-light text-primary rounded-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#detailModalSearch<?= $t['ID_Tim'] ?>" title="Lihat Detail">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModalSearch<?= $t['ID_Tim'] ?>" tabindex="-1">
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
                    <?php if(!empty($t['Kebutuhan_Role'])): ?>
                        <div class="mb-3">
                            <?php $roles = explode(',', $t['Kebutuhan_Role']); ?>
                            <?php foreach(array_slice($roles, 0, 3) as $role): ?>
                                <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.65rem;"><?= htmlspecialchars(trim($role)) ?></span>
                            <?php endforeach; ?>
                            <?php if(count($roles) > 3): ?>
                                <span class="badge bg-light text-muted border fw-normal" style="font-size: 0.65rem;">+<?= count($roles)-3 ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
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
</div>
<?php endforeach; ?>