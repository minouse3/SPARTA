<?php
// FILE: src/pages/kelola_tim.php (Final Fix: Admin Access & CRUD)

// Cek Login
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>"; exit;
}

$userId = $_SESSION['user_id'];
$roleUser = $_SESSION['role'] ?? '';
$isAdmin = ($roleUser === 'admin'); // Cek apakah user adalah admin

$idTim = $_GET['id'] ?? 0;
$message = "";

// 1. Validasi Akses
$stmtTim = $pdo->prepare("SELECT * FROM Tim WHERE ID_Tim = ?");
$stmtTim->execute([$idTim]);
$timData = $stmtTim->fetch();

if (!$timData) {
    echo "<div class='alert alert-danger m-4 border-0 shadow-sm'>Tim tidak ditemukan.</div>";
    return;
}

$isLeader = ($timData['ID_Mahasiswa_Ketua'] == $userId);
// Admin memiliki hak akses penuh (Super Leader)
$canEdit = ($isLeader || $isAdmin); 
$isMember = false;

if ($canEdit) {
    // Jika Leader atau Admin, berikan akses penuh data tim
    $userAccess = $timData;
} else {
    // Cek apakah user adalah anggota biasa
    $stmtMember = $pdo->prepare("SELECT * FROM Keanggotaan_Tim WHERE ID_Tim = ? AND ID_Mahasiswa = ?");
    $stmtMember->execute([$idTim, $userId]);
    $memberData = $stmtMember->fetch();

    if ($memberData) {
        $isMember = true;
        $userAccess = array_merge($timData, $memberData);
    } else {
        echo "<div class='alert alert-danger m-4'>Akses ditolak.</div>";
        return;
    }
}

// 2. LOGIC POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'];

        // A. UPDATE INFO TIM
        if ($action === 'update_tim' && $canEdit) {
            $stmt = $pdo->prepare("UPDATE Tim SET Nama_Tim=?, Deskripsi_Tim=?, Status_Pencarian=?, Kebutuhan_Role=? WHERE ID_Tim=?");
            $stmt->execute([$_POST['nama'], $_POST['deskripsi'], $_POST['status'], $_POST['roles_needed'], $idTim]);
            $message = "<div class='alert alert-success border-0 shadow-sm'>Info tim berhasil diperbarui.</div>";
            
            // Refresh Data
            $stmtTim->execute([$idTim]);
            $timData = $stmtTim->fetch();
            $userAccess = array_merge($userAccess, $timData);
        }
        // B. HAPUS TIM
        elseif ($action === 'delete_team' && $canEdit) {
            // Hapus tim (Cascade delete akan menangani child records di DB biasanya, tapi query ini aman)
            $stmt = $pdo->prepare("DELETE FROM Tim WHERE ID_Tim = ?");
            $stmt->execute([$idTim]);
            
            // Redirect sesuai role
            $redirectUrl = $isAdmin ? 'index.php?page=manajemen_tim' : 'index.php?page=manajemen_tim&status=deleted';
            echo "<script>window.location='$redirectUrl';</script>"; exit;
        }
        // C. HAPUS ANGGOTA
        elseif ($action === 'delete_member' && $canEdit) {
            $targetId = $_POST['id_mhs_target'];
            // Jangan biarkan menghapus diri sendiri lewat tombol ini (opsional, tapi logic UI biasanya sudah handle)
            $pdo->prepare("DELETE FROM Keanggotaan_Tim WHERE ID_Keanggotaan = ? AND ID_Tim = ?")->execute([$_POST['id_member'], $idTim]);
            $message = "<div class='alert alert-info border-0 shadow-sm'>Anggota telah dikeluarkan.</div>";
        }
        // D. EDIT PERAN
        elseif ($action === 'edit_role') {
            $targetId = $_POST['id_mhs_target'];
            // Izinkan jika user adalah Leader/Admin, atau user mengedit dirinya sendiri
            if ($canEdit || $targetId == $userId) {
                $stmt = $pdo->prepare("UPDATE Keanggotaan_Tim SET Peran = ? WHERE ID_Keanggotaan = ?");
                $stmt->execute([$_POST['peran_baru'], $_POST['id_member']]);
                $message = "<div class='alert alert-success border-0 shadow-sm'>Peran berhasil diperbarui.</div>";
            }
        }
        // E. INVITE MEMBER
        elseif ($action === 'invite_member' && $canEdit) {
            $emailTarget = trim($_POST['email_invite']);
            $foundUser = null; $tipeUser = '';

            // Cek Email
            if (str_ends_with($emailTarget, '@students.unnes.ac.id')) {
                $stmt = $pdo->prepare("SELECT ID_Mahasiswa FROM Mahasiswa WHERE Email = ?");
                $stmt->execute([$emailTarget]);
                $res = $stmt->fetch();
                if ($res) { $foundUser = $res['ID_Mahasiswa']; $tipeUser = 'mahasiswa'; }
            } elseif (str_ends_with($emailTarget, '@mail.unnes.ac.id')) {
                $stmt = $pdo->prepare("SELECT ID_Dosen FROM Dosen_Pembimbing WHERE Email = ?");
                $stmt->execute([$emailTarget]);
                $res = $stmt->fetch();
                if ($res) { $foundUser = $res['ID_Dosen']; $tipeUser = 'dosen'; }
            }

            if (!$foundUser) {
                $message = "<div class='alert alert-danger border-0 shadow-sm'>Email tidak ditemukan!</div>";
            } else {
                // Validasi existing member
                $cek = $pdo->prepare("SELECT COUNT(*) FROM Keanggotaan_Tim WHERE ID_Tim = ? AND ID_Mahasiswa = ?");
                $cek->execute([$idTim, $foundUser]);
                $isExist = $cek->fetchColumn() > 0;
                
                if($tipeUser == 'dosen' && $timData['ID_Dosen_Pembimbing'] == $foundUser) $isExist = true;

                $cekPending = $pdo->prepare("SELECT COUNT(*) FROM Invitasi WHERE ID_Tim = ? AND ID_Penerima = ? AND Status = 'Pending'");
                $cekPending->execute([$idTim, $foundUser]);

                if ($isExist) {
                    $message = "<div class='alert alert-warning border-0 shadow-sm'>User sudah ada di tim.</div>";
                } elseif ($cekPending->fetchColumn() > 0) {
                    $message = "<div class='alert alert-warning border-0 shadow-sm'>Undangan sudah terkirim.</div>";
                } else {
                    // IMPERSONATION LOGIC:
                    // Jika Admin yang invite, gunakan ID Ketua Tim sebagai pengirim agar tidak konflik ID
                    $senderId = $isAdmin ? $timData['ID_Mahasiswa_Ketua'] : $_SESSION['user_id'];

                    $pdo->prepare("INSERT INTO Invitasi (ID_Tim, ID_Pengirim, ID_Penerima, Tipe_Penerima) VALUES (?, ?, ?, ?)")
                        ->execute([$idTim, $senderId, $foundUser, $tipeUser]);
                    $message = "<div class='alert alert-success border-0 shadow-sm'>Undangan dikirim ke <b>$emailTarget</b></div>";
                }
            }
        }
        // F. CANCEL INVITE
        elseif ($action === 'cancel_invite' && $canEdit) {
            $pdo->prepare("DELETE FROM Invitasi WHERE ID_Invitasi = ? AND ID_Tim = ?")->execute([$_POST['id_invitasi'], $idTim]);
            $message = "<div class='alert alert-info border-0 shadow-sm'>Undangan dibatalkan.</div>";
        }

    } catch (Exception $e) {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>Error: " . $e->getMessage() . "</div>";
    }
}

// 3. AMBIL DATA PENDUKUNG

// A. Ambil Data Spesifik Keanggotaan Ketua (Untuk Role Teknikal)
$stmtLeaderMem = $pdo->prepare("SELECT * FROM Keanggotaan_Tim WHERE ID_Tim = ? AND ID_Mahasiswa = ?");
$stmtLeaderMem->execute([$idTim, $timData['ID_Mahasiswa_Ketua']]);
$leaderMemberData = $stmtLeaderMem->fetch(); 
$leaderRole = $leaderMemberData ? $leaderMemberData['Peran'] : 'Ketua Tim'; 
$leaderMemId = $leaderMemberData ? $leaderMemberData['ID_Keanggotaan'] : 0;

// B. Ambil Anggota Tim (KECUALI KETUA)
$stmtMem = $pdo->prepare("SELECT k.*, m.Nama_Mahasiswa, m.NIM, m.Foto_Profil, m.ID_Mahasiswa 
                          FROM Keanggotaan_Tim k 
                          JOIN Mahasiswa m ON k.ID_Mahasiswa = m.ID_Mahasiswa 
                          WHERE k.ID_Tim = ? AND k.ID_Mahasiswa != ?");
$stmtMem->execute([$idTim, $timData['ID_Mahasiswa_Ketua']]);
$members = $stmtMem->fetchAll();

// C. Info Ketua (Personal Data)
$stmtKetua = $pdo->prepare("SELECT * FROM Mahasiswa WHERE ID_Mahasiswa = ?");
$stmtKetua->execute([$timData['ID_Mahasiswa_Ketua']]);
$ketuaData = $stmtKetua->fetch();

// D. Pending Invitations
$stmtInv = $pdo->prepare("
    SELECT i.*, 
    CASE WHEN i.Tipe_Penerima = 'mahasiswa' THEN m.Nama_Mahasiswa ELSE d.Nama_Dosen END as Nama_Penerima,
    CASE WHEN i.Tipe_Penerima = 'mahasiswa' THEN m.Email ELSE d.Email END as Email_Penerima
    FROM Invitasi i 
    LEFT JOIN Mahasiswa m ON i.ID_Penerima = m.ID_Mahasiswa AND i.Tipe_Penerima = 'mahasiswa'
    LEFT JOIN Dosen_Pembimbing d ON i.ID_Penerima = d.ID_Dosen AND i.Tipe_Penerima = 'dosen'
    WHERE i.ID_Tim = ? AND i.Status = 'Pending'
");
$stmtInv->execute([$idTim]);
$pendingInvites = $stmtInv->fetchAll();

// E. Info Dosen Pembimbing (Jika Ada)
$dosenName = "Belum Ada";
if($timData['ID_Dosen_Pembimbing']) {
    $stmtDsn = $pdo->prepare("SELECT Nama_Dosen FROM Dosen_Pembimbing WHERE ID_Dosen = ?");
    $stmtDsn->execute([$timData['ID_Dosen_Pembimbing']]);
    $dosenName = $stmtDsn->fetchColumn();
}
?>

<style>
    .tag-container {
        min-height: 40px; padding: 6px; border: 1px solid #ced4da; border-radius: 0.375rem; background-color: #fff; display: flex; flex-wrap: wrap; gap: 6px;
    }
    .tag-item { background: #e9ecef; color: #495057; padding: 2px 10px; border-radius: 50px; font-size: 0.85rem; display: flex; align-items: center; }
    .tag-input { border: none; outline: none; flex-grow: 1; min-width: 100px; font-size: 0.9rem; }
</style>

<div class="mb-4">
    <a href="?page=manajemen_tim" class="btn btn-light btn-sm shadow-sm text-secondary fw-bold rounded-pill px-3">
        <i class="fas fa-arrow-left me-2"></i>Kembali
    </a>
</div>

<div class="d-flex align-items-center mb-4">
    <div class="rounded-circle bg-gradient bg-primary text-white d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold;">
        <?= strtoupper(substr($userAccess['Nama_Tim'], 0, 1)) ?>
    </div>
    <div>
        <h3 class="fw-bold text-dark mb-0">
            <?= htmlspecialchars($userAccess['Nama_Tim']) ?>
            <?php if($isAdmin): ?> <span class="badge bg-danger ms-2 fs-6">Admin Mode</span> <?php endif; ?>
        </h3>
        <span class="badge <?= $userAccess['Status_Pencarian']=='Terbuka'?'bg-success':'bg-secondary' ?> bg-opacity-10 <?= $userAccess['Status_Pencarian']=='Terbuka'?'text-success':'text-secondary' ?> border rounded-pill">
            <?= $userAccess['Status_Pencarian'] ?>
        </span>
    </div>
</div>

<?= $message ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white fw-bold text-dark border-bottom-0 py-3">
                <i class="fas fa-cog me-2 text-primary"></i>Pengaturan Tim
            </div>
            <div class="card-body">
                <?php if($canEdit): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="update_tim">
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Nama Tim</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($userAccess['Nama_Tim']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Status Rekrutmen</label>
                        <select name="status" class="form-select">
                            <option value="Terbuka" <?= $userAccess['Status_Pencarian']=='Terbuka'?'selected':''?>>Terbuka (Public)</option>
                            <option value="Tertutup" <?= $userAccess['Status_Pencarian']=='Tertutup'?'selected':''?>>Tertutup (Private)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Dosen Pembimbing</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($dosenName) ?>" disabled readonly>
                        <div class="form-text small">Undang dosen melalui tombol "Undang" di panel kanan.</div>
                    </div>

                    <div class="mb-3 position-relative">
                        <label class="small fw-bold text-muted">Role yang Dibutuhkan</label>
                        <div id="role-container" class="tag-container" onclick="document.getElementById('role-input').focus()">
                            <input type="text" id="role-input" class="tag-input" placeholder="Ketik role & enter...">
                        </div>
                        <input type="hidden" name="roles_needed" id="role-hidden" value="<?= htmlspecialchars($userAccess['Kebutuhan_Role'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-4">
                        <label class="small fw-bold text-muted">Deskripsi Tim</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($userAccess['Deskripsi_Tim']) ?></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button class="btn btn-primary fw-bold rounded-pill">Simpan Perubahan</button>
                    </div>
                </form>
                
                <hr class="my-4">
                <div class="d-grid">
                    <button class="btn btn-outline-danger rounded-pill fw-bold" onclick="confirmDelete()">
                        <i class="fas fa-trash-alt me-2"></i> Hapus Tim
                    </button>
                </div>
                <form id="deleteForm" method="POST" style="display: none;">
                    <input type="hidden" name="action" value="delete_team">
                </form>

                <?php else: ?>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Dosen Pembimbing</label>
                        <p class="fw-bold"><?= htmlspecialchars($dosenName) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Deskripsi</label>
                        <p class="small text-muted"><?= nl2br(htmlspecialchars($userAccess['Deskripsi_Tim'])) ?></p>
                    </div>
                    <div class="alert alert-light border text-center small text-muted">
                        <i class="fas fa-lock mb-2"></i><br>Hanya Ketua Tim yang dapat mengubah pengaturan.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white fw-bold text-dark border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2 text-success"></i>Daftar Anggota</span>
                <div>
                    <span class="badge bg-light text-dark me-2"><?= count($members) + 1 ?> Orang</span>
                    <?php if($canEdit): ?>
                        <button class="btn btn-success btn-sm rounded-pill fw-bold px-3" data-bs-toggle="modal" data-bs-target="#inviteModal">
                            <i class="fas fa-plus me-1"></i> Undang
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Nama</th>
                                <th>Role</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-light bg-opacity-25">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <?php 
                                            $fotoKetua = getFotoMhs($ketuaData['NIM'], $ketuaData['Foto_Profil']);
                                            $imgSrc = $fotoKetua ? "$fotoKetua" : "";
                                            if($imgSrc) echo "<img src='$imgSrc' class='rounded-circle me-2' style='width:35px;height:35px;object-fit:cover'>";
                                            else echo "<div class='rounded-circle bg-white border d-flex align-items-center justify-content-center me-2 fw-bold text-secondary' style='width:35px;height:35px'>".substr($ketuaData['Nama_Mahasiswa'],0,1)."</div>";
                                        ?>
                                        <div>
                                            <a href="?page=profile&id=<?= $ketuaData['ID_Mahasiswa'] ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($ketuaData['Nama_Mahasiswa']) ?>
                                            </a>
                                            <div class="small text-muted"><?= $ketuaData['NIM'] ?> <span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem">LEADER</span></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1 fw-normal">
                                        <?= htmlspecialchars($leaderRole) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($canEdit && $leaderMemId): ?>
                                        <button class="btn btn-light text-primary btn-sm rounded-circle me-1 shadow-sm" 
                                                onclick="editRole(<?= $leaderMemId ?>, '<?= addslashes($leaderRole) ?>', <?= $ketuaData['ID_Mahasiswa'] ?>)"
                                                title="Edit Role Ketua">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                    <i class="fas fa-crown text-warning ms-1"></i>
                                </td>
                            </tr>
                            
                            <?php foreach($members as $m): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <?php 
                                            $foto = getFotoMhs($m['NIM'], $m['Foto_Profil']);
                                            $imgSrc = $foto ? "$foto" : "";
                                            if($imgSrc) echo "<img src='$imgSrc' class='rounded-circle me-2' style='width:35px;height:35px;object-fit:cover'>";
                                            else echo "<div class='rounded-circle bg-light border d-flex align-items-center justify-content-center me-2 fw-bold text-secondary' style='width:35px;height:35px'>".substr($m['Nama_Mahasiswa'],0,1)."</div>";
                                        ?>
                                        <div>
                                            <a href="?page=profile&id=<?= $m['ID_Mahasiswa'] ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($m['Nama_Mahasiswa']) ?>
                                            </a>
                                            <div class="small text-muted"><?= $m['NIM'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 fw-normal"><?= htmlspecialchars($m['Peran']) ?></span></td>
                                <td class="text-end pe-4">
                                    <?php if($canEdit || $m['ID_Mahasiswa'] == $userId): ?>
                                        <button class="btn btn-light text-primary btn-sm rounded-circle me-1" onclick="editRole(<?= $m['ID_Keanggotaan'] ?>, '<?= $m['Peran'] ?>', <?= $m['ID_Mahasiswa'] ?>)"><i class="fas fa-pencil-alt"></i></button>
                                    <?php endif; ?>
                                    <?php if($canEdit): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Keluarkan anggota ini?')">
                                            <input type="hidden" name="action" value="delete_member">
                                            <input type="hidden" name="id_mhs_target" value="<?= $m['ID_Mahasiswa'] ?>">
                                            <input type="hidden" name="id_member" value="<?= $m['ID_Keanggotaan'] ?>">
                                            <button class="btn btn-light text-danger btn-sm rounded-circle"><i class="fas fa-times"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if($canEdit && !empty($pendingInvites)): ?>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-warning bg-opacity-10 fw-bold text-dark border-bottom-0 py-3">
                <i class="fas fa-clock me-2 text-warning"></i>Menunggu Konfirmasi
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach($pendingInvites as $inv): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center me-3 text-secondary fw-bold" style="width: 40px; height: 40px;">
                                <?= strtoupper(substr($inv['Nama_Penerima'], 0, 1)) ?>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($inv['Nama_Penerima']) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($inv['Email_Penerima']) ?></small>
                            </div>
                        </div>
                        <form method="POST" onsubmit="return confirm('Batalkan undangan ini?')">
                            <input type="hidden" name="action" value="cancel_invite">
                            <input type="hidden" name="id_invitasi" value="<?= $inv['ID_Invitasi'] ?>">
                            <button class="btn btn-outline-danger btn-sm rounded-pill px-3">Batal</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<div class="modal fade" id="inviteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h6 class="modal-title fw-bold">Undang Anggota / Dosen</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="invite_member">
                <div class="text-center mb-4">
                    <i class="fas fa-envelope-open-text fa-3x text-success mb-2"></i>
                    <p class="text-muted small">Masukkan email mahasiswa atau dosen UNNES.</p>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Email Penerima</label>
                    <input type="email" name="email_invite" class="form-control" placeholder="nama@students.unnes.ac.id" required>
                </div>
                <button class="btn btn-success w-100 rounded-pill fw-bold">Kirim Undangan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header bg-white border-0 pb-0">
                <h6 class="modal-title fw-bold">Edit Peran</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_role">
                <input type="hidden" name="id_member" id="editRoleIdMember">
                <input type="hidden" name="id_mhs_target" id="editRoleIdMhs">
                <div class="mb-3">
                    <input type="text" name="peran_baru" id="editRoleInput" class="form-control" required placeholder="Contoh: Frontend Lead">
                </div>
                <button class="btn btn-primary w-100 rounded-pill fw-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRole(id, peran, idMhs) {
    document.getElementById('editRoleIdMember').value = id;
    document.getElementById('editRoleIdMhs').value = idMhs;
    document.getElementById('editRoleInput').value = peran;
    new bootstrap.Modal(document.getElementById('editRoleModal')).show();
}

function confirmDelete() {
    if (confirm('PERINGATAN! Menghapus tim akan menghapus semua data anggota. Lanjutkan?')) {
        document.getElementById('deleteForm').submit();
    }
}

// Simple Tag Input Logic
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('role-input');
    const container = document.getElementById('role-container');
    const hidden = document.getElementById('role-hidden');
    
    if(input) {
        let tags = hidden.value ? hidden.value.split(',').filter(t=>t) : [];
        const render = () => {
            container.querySelectorAll('span').forEach(e=>e.remove());
            tags.forEach((tag,i) => {
                const sp = document.createElement('span');
                sp.className = 'tag-item';
                sp.innerHTML = `${tag} <i class="fas fa-times ms-2 cursor-pointer" onclick="removeTag(${i})"></i>`;
                container.insertBefore(sp, input);
            });
            hidden.value = tags.join(',');
        };
        render();

        input.addEventListener('keydown', e => {
            if(e.key === 'Enter') {
                e.preventDefault();
                const v = input.value.trim();
                if(v && !tags.includes(v)) { tags.push(v); render(); }
                input.value = '';
            } else if(e.key === 'Backspace' && !input.value && tags.length) {
                tags.pop(); render();
            }
        });

        window.removeTag = i => { tags.splice(i,1); render(); };
    }
});
</script>