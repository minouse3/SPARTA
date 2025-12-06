<?php
$idDosen = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT d.*, p.Nama_Prodi, f.Nama_Fakultas 
                       FROM Dosen_Pembimbing d 
                       LEFT JOIN Prodi p ON d.ID_Prodi = p.ID_Prodi 
                       LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
                       WHERE d.ID_Dosen = ?");
$stmt->execute([$idDosen]);
$dosen = $stmt->fetch();

if (!$dosen) { echo "<div class='alert alert-danger'>Data dosen tidak ditemukan.</div>"; exit; }
?>

<div class="mb-3">
    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center pt-5 pb-4">
                <div class="mx-auto mb-3">
                    <?php if (!empty($dosen['Foto_Profil']) && file_exists($dosen['Foto_Profil'])): ?>
                        <img src="<?= $dosen['Foto_Profil'] ?>?t=<?= time() ?>" class="rounded-circle shadow-sm border border-3 border-white" style="width: 140px; height: 140px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm mx-auto border border-3 border-white" style="width: 140px; height: 140px; font-size: 4rem;"><i class="fas fa-chalkboard-teacher text-secondary"></i></div>
                    <?php endif; ?>
                </div>

                <h4 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($dosen['Nama_Dosen']) ?></h4>
                <p class="text-muted mb-2"><?= $dosen['NIDN'] ?></p>
                <span class="badge bg-info bg-opacity-10 text-info border border-info px-3 py-2 mb-3"><?= $dosen['Nama_Prodi'] ?? 'Umum' ?></span>
                
                <div class="small text-muted mb-3"><i class="fas fa-university me-1"></i> <?= $dosen['Nama_Fakultas'] ?? '-' ?></div>

                <?php if($dosen['Bio']): ?>
                    <div class="bg-light p-3 rounded fst-italic text-muted small mb-3">"<?= nl2br(htmlspecialchars($dosen['Bio'])) ?>"</div>
                <?php endif; ?>

                <div class="d-flex justify-content-center gap-2 mt-2">
                    <?php if(!empty($dosen['Email'])): ?>
                        <a href="mailto:<?= $dosen['Email'] ?>" class="btn btn-outline-danger btn-sm rounded-circle" title="Email" style="width: 38px; height: 38px; padding-top: 8px;"><i class="fas fa-envelope"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($dosen['No_HP'])): ?>
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $dosen['No_HP'])) ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-circle" title="WhatsApp" style="width: 38px; height: 38px; padding-top: 8px;"><i class="fab fa-whatsapp"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($dosen['LinkedIn'])): ?>
                        <a href="<?= $dosen['LinkedIn'] ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle" title="LinkedIn" style="width: 38px; height: 38px; padding-top: 8px;"><i class="fab fa-linkedin-in"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>