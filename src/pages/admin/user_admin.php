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
        if ($action === 'add') {
            // Tambah Admin Baru
            $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO Admin (Username, Password_Hash, Nama_Lengkap, Level) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['username'], $passHash, $_POST['nama'], $_POST['level']]);
            echo "<div class='alert alert-success border-0 shadow-sm rounded-pill'><i class='fas fa-check-circle me-2'></i>Admin baru berhasil ditambahkan!</div>";
        } 
        elseif ($action === 'delete') {
            // Hapus Admin
            if ($_POST['id'] == $_SESSION['user_id']) {
                echo "<div class='alert alert-danger border-0 shadow-sm rounded-pill'><i class='fas fa-times-circle me-2'></i>Anda tidak bisa menghapus akun sendiri!</div>";
            } else {
                $stmt = $pdo->prepare("DELETE FROM Admin WHERE ID_Admin = ?");
                $stmt->execute([$_POST['id']]);
                echo "<div class='alert alert-success border-0 shadow-sm rounded-pill'><i class='fas fa-check-circle me-2'></i>Data admin berhasil dihapus.</div>";
            }
        }
        elseif ($action === 'reset_pass') {
            // Reset Password
            $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE Admin SET Password_Hash = ? WHERE ID_Admin = ?");
            $stmt->execute([$passHash, $_POST['id']]);
            echo "<div class='alert alert-success border-0 shadow-sm rounded-pill'><i class='fas fa-key me-2'></i>Password berhasil direset!</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger border-0 shadow-sm'>Error: " . $e->getMessage() . "</div>";
    }
}

$admins = $pdo->query("SELECT * FROM Admin ORDER BY Level DESC, Nama_Lengkap ASC")->fetchAll();
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
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Manajemen Admin</h3>
        <p class="text-muted mb-0">Kelola hak akses dan pengguna sistem.</p>
    </div>
    <button class="btn btn-gradient-dark rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addAdminModal">
        <i class="fas fa-plus me-2"></i>Admin Baru
    </button>
</div>

<div class="card shadow-sm border-0 rounded-3 overflow-hidden">
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

<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Tambah Admin</h5>
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