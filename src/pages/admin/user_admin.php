<?php
// FILE: Manajemen Admin (Modern UI - Super Admin Only)

// KEAMANAN SUPER KETAT:
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || 
    !isset($_SESSION['admin_level']) || $_SESSION['admin_level'] !== 'superadmin') {
    echo "<div class='alert alert-danger border-0 shadow-sm'><i class='fas fa-user-shield me-2'></i>Akses Ditolak! Halaman ini khusus Super Admin.</div>";
    exit;
}

// --- LOGIC PHP ---
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. TAMBAH ADMIN MANUAL (Username & Password)
        if ($action === 'add') {
            $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO Admin (Username, Password_Hash, Nama_Lengkap, Level) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['username'], $passHash, $_POST['nama'], $_POST['level']]);
            echo "<div class='alert alert-success border-0 shadow-sm rounded-pill'><i class='fas fa-check-circle me-2'></i>Admin baru berhasil ditambahkan!</div>";
        } 
        
        // 2. ANGKAT DOSEN JADI ADMIN (FITUR BARU)
        elseif ($action === 'add_dosen_admin') {
            $emailDosen = $_POST['email_dosen'];
            
            // Cek apakah email ada di tabel Dosen
            $cek = $pdo->prepare("SELECT ID_Dosen, Nama_Dosen FROM Dosen_Pembimbing WHERE Email = ?");
            $cek->execute([$emailDosen]);
            $dosen = $cek->fetch();

            if ($dosen) {
                // Update status Is_Admin jadi 1
                $upd = $pdo->prepare("UPDATE Dosen_Pembimbing SET Is_Admin = 1 WHERE ID_Dosen = ?");
                $upd->execute([$dosen['ID_Dosen']]);
                echo "<div class='alert alert-success border-0 shadow-sm rounded-pill'><i class='fas fa-check-circle me-2'></i>Dosen <b>{$dosen['Nama_Dosen']}</b> berhasil dijadikan Admin!</div>";
            } else {
                echo "<div class='alert alert-danger border-0 shadow-sm rounded-pill'><i class='fas fa-times-circle me-2'></i>Email dosen tidak ditemukan di database.</div>";
            }
        }

        // 3. CABUT AKSES ADMIN DARI DOSEN
        elseif ($action === 'revoke_dosen') {
            $upd = $pdo->prepare("UPDATE Dosen_Pembimbing SET Is_Admin = 0 WHERE ID_Dosen = ?");
            $upd->execute([$_POST['id']]);
            echo "<div class='alert alert-warning border-0 shadow-sm rounded-pill'><i class='fas fa-check-circle me-2'></i>Akses admin dicabut dari dosen tersebut.</div>";
        }

        // 4. HAPUS ADMIN BIASA
        elseif ($action === 'delete') {
            if ($_POST['id'] == $_SESSION['user_id']) {
                echo "<div class='alert alert-danger border-0 shadow-sm rounded-pill'><i class='fas fa-times-circle me-2'></i>Anda tidak bisa menghapus akun sendiri!</div>";
            } else {
                $stmt = $pdo->prepare("DELETE FROM Admin WHERE ID_Admin = ?");
                $stmt->execute([$_POST['id']]);
                echo "<div class='alert alert-success border-0 shadow-sm rounded-pill'><i class='fas fa-check-circle me-2'></i>Data admin berhasil dihapus.</div>";
            }
        }

        // 5. RESET PASSWORD ADMIN BIASA
        elseif ($action === 'reset_pass') {
            $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE Admin SET Password_Hash = ? WHERE ID_Admin = ?");
            $stmt->execute([$passHash, $_POST['id']]);
            echo "<div class='alert alert-success border-0 shadow-sm rounded-pill'><i class='fas fa-key me-2'></i>Password berhasil direset!</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger border-0 shadow-sm'>Error: " . $e->getMessage() . "</div>";
    }
}

// AMBIL DATA
// 1. Admin Murni (Tabel Admin)
$admins = $pdo->query("SELECT * FROM Admin ORDER BY Level DESC, Nama_Lengkap ASC")->fetchAll();

// 2. Dosen Admin (Tabel Dosen_Pembimbing WHERE Is_Admin = 1)
$dosenAdmins = $pdo->query("SELECT * FROM Dosen_Pembimbing WHERE Is_Admin = 1 ORDER BY Nama_Dosen ASC")->fetchAll();
?>

<style>
    .btn-gradient-dark {
        background: linear-gradient(135deg, #212529, #343a40);
        color: white; border: none;
    }
    .btn-gradient-dark:hover {
        background: linear-gradient(135deg, #1c1f23, #212529);
        color: white; transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(33, 37, 41, 0.3);
    }
    .avatar-admin {
        width: 40px; height: 40px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: bold;
        font-size: 1rem;
    }
    .bg-super { background: linear-gradient(135deg, #dc3545, #e02d3d); color: white; }
    .bg-normal { background-color: #f8f9fa; color: #495057; border: 1px solid #dee2e6; }
    .bg-dosen { background: linear-gradient(135deg, #0d6efd, #0dcaf0); color: white; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Manajemen Admin</h3>
        <p class="text-muted mb-0">Kelola Admin Staff dan Akses Dosen.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addDosenAdminModal">
            <i class="fas fa-user-graduate me-2"></i>Angkat Dosen
        </button>
        <button class="btn btn-gradient-dark rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="fas fa-plus me-2"></i>Admin Manual
        </button>
    </div>
</div>

<h6 class="fw-bold text-dark mb-3"><i class="fas fa-users-cog me-2"></i>Akun Admin (Staff/Superadmin)</h6>
<div class="card shadow-sm border-0 rounded-3 overflow-hidden mb-5">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4 py-3">User</th>
                        <th>Username</th>
                        <th>Level Akses</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($admins as $a): ?>
                    <?php 
                        $initial = strtoupper(substr($a['Nama_Lengkap'], 0, 1));
                        $isMe = ($a['ID_Admin'] == $_SESSION['user_id']);
                        $avatarClass = ($a['Level'] == 'superadmin') ? 'bg-super shadow-sm' : 'bg-normal';
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-admin <?= $avatarClass ?> me-3">
                                    <?= $initial ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">
                                        <?= htmlspecialchars($a['Nama_Lengkap']) ?>
                                        <?php if($isMe): ?>
                                            <span class="badge bg-success ms-2 rounded-pill" style="font-size: 0.6rem;">ANDA</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">ID: #<?= $a['ID_Admin'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="fw-bold text-secondary"><?= htmlspecialchars($a['Username']) ?></td>
                        <td>
                            <?php if($a['Level'] == 'superadmin'): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3">
                                    <i class="fas fa-shield-alt me-1"></i> SUPER ADMIN
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3">
                                    <i class="fas fa-user-cog me-1"></i> Admin Biasa
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light text-warning border rounded-circle shadow-sm me-1" onclick="resetPass(<?= $a['ID_Admin'] ?>, '<?= addslashes($a['Username']) ?>')" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            
                            <?php if(!$isMe): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Hapus admin ini secara permanen?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $a['ID_Admin'] ?>">
                                <button class="btn btn-sm btn-light text-danger border rounded-circle shadow-sm" title="Hapus User">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php else: ?>
                                <button class="btn btn-sm btn-light text-muted border rounded-circle shadow-sm" disabled><i class="fas fa-ban"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<h6 class="fw-bold text-primary mb-3"><i class="fas fa-chalkboard-teacher me-2"></i>Dosen dengan Akses Admin</h6>
<div class="card shadow-sm border-0 rounded-3 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4 py-3">Nama Dosen</th>
                        <th>Email / NIDN</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($dosenAdmins)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada dosen yang diangkat menjadi admin.</td></tr>
                    <?php else: ?>
                        <?php foreach($dosenAdmins as $da): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-admin bg-dosen me-3 shadow-sm">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($da['Nama_Dosen']) ?></div>
                                </div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($da['Email']) ?></div>
                                <small class="text-muted">NIDN: <?= htmlspecialchars($da['NIDN']) ?></small>
                            </td>
                            <td><span class="badge bg-primary">Admin Access</span></td>
                            <td class="text-end pe-4">
                                <form method="POST" onsubmit="return confirm('Cabut akses admin dari dosen ini? (Akun dosen tidak akan terhapus)')">
                                    <input type="hidden" name="action" value="revoke_dosen">
                                    <input type="hidden" name="id" value="<?= $da['ID_Dosen'] ?>">
                                    <button class="btn btn-sm btn-outline-danger rounded-pill fw-bold">
                                        Cabut Akses
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Admin Manual Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" placeholder="Nama Admin" required>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="username_login" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Level Akses</label>
                        <select name="level" class="form-select" required>
                            <option value="admin">Admin Biasa</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="small fw-bold text-muted">Password Awal</label>
                    <input type="password" name="password" class="form-control" placeholder="******" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-dark fw-bold rounded-pill">Simpan Data</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addDosenAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-graduate me-2"></i>Angkat Dosen jadi Admin</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add_dosen_admin">
                <div class="alert alert-light border small text-muted">
                    <i class="fas fa-info-circle me-1"></i> Dosen yang ditambahkan akan bisa login ke halaman Admin menggunakan akun Dosen mereka.
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Email Dosen (UNNES)</label>
                    <input type="email" name="email_dosen" class="form-control" placeholder="contoh: dosen@mail.unnes.ac.id" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill">Berikan Akses Admin</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="resetPassModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-key me-2"></i>Reset Password</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <input type="hidden" name="action" value="reset_pass">
                <input type="hidden" name="id" id="resetId">
                
                <div class="mb-3">
                    <span class="avatar-admin bg-warning bg-opacity-25 text-warning mx-auto mb-2" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="fas fa-lock"></i>
                    </span>
                    <p class="small text-muted mb-0">Set password baru untuk:</p>
                    <h6 class="fw-bold text-dark" id="resetUser">User</h6>
                </div>

                <input type="password" name="password" class="form-control text-center mb-3" placeholder="Password Baru" required>
                
                <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetPass(id, user) {
    document.getElementById('resetId').value = id;
    document.getElementById('resetUser').textContent = user;
    new bootstrap.Modal(document.getElementById('resetPassModal')).show();
}
</script>