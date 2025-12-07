<?php
// FILE: Halaman Profil Publik Dosen (Modern UI - Lengkap)

$idDosen = $_GET['id'] ?? 0;

// 1. Ambil Data Dosen Utama
$stmt = $pdo->prepare("SELECT d.*, p.Nama_Prodi, f.Nama_Fakultas 
                       FROM Dosen_Pembimbing d 
                       LEFT JOIN Prodi p ON d.ID_Prodi = p.ID_Prodi 
                       LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
                       WHERE d.ID_Dosen = ?");
$stmt->execute([$idDosen]);
$dosen = $stmt->fetch();

if (!$dosen) { 
    echo "<div class='alert alert-danger border-0 shadow-sm'>Data dosen tidak ditemukan.</div>"; 
    exit; 
}

// 2. Ambil Skill (Keahlian)
$stmtSkill = $pdo->prepare("SELECT s.Nama_Skill FROM Dosen_Keahlian dk 
                            JOIN Skill s ON dk.ID_Skill = s.ID_Skill 
                            WHERE dk.ID_Dosen = ?");
$stmtSkill->execute([$idDosen]);
$skills = $stmtSkill->fetchAll(PDO::FETCH_COLUMN);

// 3. Ambil Role (Minat)
$roles = [];
try {
    $stmtRole = $pdo->prepare("SELECT r.Nama_Role FROM Dosen_Role dr 
                               JOIN Role_Tim r ON dr.ID_Role = r.ID_Role 
                               WHERE dr.ID_Dosen = ?");
    $stmtRole->execute([$idDosen]);
    $roles = $stmtRole->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Silent fail jika tabel belum ada
}

// 4. Cek Akses (Untuk tombol Edit)
$isMe = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $idDosen && $_SESSION['role'] == 'dosen');
?>

<style>
    .profile-cover {
        height: 180px;
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        border-radius: 15px 15px 0 0;
        position: relative;
    }
    .profile-avatar {
        width: 160px; height: 160px;
        object-fit: cover;
        border: 5px solid #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        background-color: #fff;
        margin-top: -80px;
    }
    .stat-card {
        transition: transform 0.2s;
        border-left: 4px solid #0d6efd;
    }
    .stat-card:hover { transform: translateY(-3px); }
</style>

<div class="mb-3">
    <a href="javascript:history.back()" class="btn btn-light btn-sm shadow-sm text-secondary fw-bold rounded-pill px-3">
        <i class="fas fa-arrow-left me-2"></i>Kembali
    </a>
</div>

<div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden">
    <div class="profile-cover"></div>
    
    <div class="card-body pt-0">
        <div class="row">
            <div class="col-md-4 text-center">
                <div class="mb-3 position-relative">
                    <?php if (!empty($dosen['Foto_Profil']) && file_exists($dosen['Foto_Profil'])): ?>
                        <img src="<?= $dosen['Foto_Profil'] ?>?t=<?= time() ?>" class="rounded-circle profile-avatar">
                    <?php else: ?>
                        <div class="rounded-circle profile-avatar d-flex align-items-center justify-content-center mx-auto text-secondary bg-light fs-1">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($dosen['Nama_Dosen']) ?></h4>
                <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill mb-3">
                    NIDN: <?= htmlspecialchars($dosen['NIDN']) ?>
                </div>

                <div class="d-flex justify-content-center gap-2 mb-4">
                    <?php if(!empty($dosen['Email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($dosen['Email']) ?>" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" style="width: 40px; height: 40px; display:flex; align-items:center; justify-content:center;" title="Kirim Email">
                            <i class="fas fa-envelope"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if(!empty($dosen['No_HP'])): ?>
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $dosen['No_HP'])) ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-circle shadow-sm" style="width: 40px; height: 40px; display:flex; align-items:center; justify-content:center;" title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if(!empty($dosen['LinkedIn'])): ?>
                        <a href="<?= htmlspecialchars($dosen['LinkedIn']) ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" style="width: 40px; height: 40px; display:flex; align-items:center; justify-content:center;" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if($isMe): ?>
                    <a href="?page=edit_profile_dosen" class="btn btn-dark w-100 fw-bold rounded-pill shadow-sm"><i class="fas fa-user-edit me-2"></i>Edit Profil Saya</a>
                <?php endif; ?>
            </div>

            <div class="col-md-8 mt-4 mt-md-0 pt-md-4">
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100 stat-card p-3">
                            <small class="text-muted fw-bold text-uppercase">Program Studi</small>
                            <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($dosen['Nama_Prodi'] ?? '-') ?></h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100 stat-card p-3" style="border-left-color: #6c757d;">
                            <small class="text-muted fw-bold text-uppercase">Fakultas</small>
                            <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($dosen['Nama_Fakultas'] ?? '-') ?></h5>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-secondary text-uppercase border-bottom pb-2 mb-3">
                        <i class="fas fa-align-left me-2"></i>Tentang / Minat Riset
                    </h6>
                    <div class="text-muted" style="line-height: 1.8;">
                        <?= !empty($dosen['Bio']) ? nl2br(htmlspecialchars($dosen['Bio'])) : '<span class="fst-italic opacity-50">Belum ada informasi biografi atau minat riset.</span>' ?>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <h6 class="fw-bold text-success text-uppercase border-bottom pb-2 mb-3">
                            <i class="fas fa-tools me-2"></i>Bidang Keahlian
                        </h6>
                        <?php if(!empty($skills)): ?>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach($skills as $s): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 fw-normal">
                                        <?= htmlspecialchars($s) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <small class="text-muted fst-italic">Belum menambahkan keahlian.</small>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6 mb-3">
                        <h6 class="fw-bold text-info text-uppercase border-bottom pb-2 mb-3">
                            <i class="fas fa-user-tag me-2"></i>Fokus Peran
                        </h6>
                        <?php if(!empty($roles)): ?>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach($roles as $r): ?>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-2 fw-normal">
                                        <?= htmlspecialchars($r) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <small class="text-muted fst-italic">Belum menambahkan peran.</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row border-top pt-3">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-birthday-cake text-warning fa-lg me-3 opacity-50"></i>
                            <div>
                                <small class="text-muted d-block">Tempat, Tanggal Lahir</small>
                                <span class="fw-bold text-dark">
                                    <?= htmlspecialchars($dosen['Tempat_Lahir'] ?? '-') ?>, 
                                    <?= !empty($dosen['Tanggal_Lahir']) ? date('d F Y', strtotime($dosen['Tanggal_Lahir'])) : '-' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-success fa-lg me-3 opacity-50"></i>
                            <div>
                                <small class="text-muted d-block">Status Dosen</small>
                                <span class="fw-bold text-success">Aktif / Terverifikasi</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>