<?php
// KEAMANAN SUPER KETAT:
// 1. Cek apakah role utamanya Admin
// 2. Cek apakah level-nya SUPERADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || 
    !isset($_SESSION['admin_level']) || $_SESSION['admin_level'] !== 'superadmin') {
    
    // Jika Admin Biasa mencoba masuk, tendang ke Dashboard
    echo "<script>alert('Akses Ditolak! Halaman ini khusus Super Admin.'); window.location='?page=dashboard';</script>";
    exit;
}

// --- LOGIC PHP ---
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'add') {
            // Tambah Admin Baru dengan Level
            $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO Admin (Username, Password_Hash, Nama_Lengkap, Level) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['username'], $passHash, $_POST['nama'], $_POST['level']]); // Simpan Level
            echo "<div class='alert alert-success'>Admin baru berhasil ditambahkan!</div>";
        } 
        elseif ($action === 'delete') {
            // Hapus Admin
            if ($_POST['id'] == $_SESSION['user_id']) {
                echo "<div class='alert alert-danger'>Anda tidak bisa menghapus akun sendiri!</div>";
            } else {
                $stmt = $pdo->prepare("DELETE FROM Admin WHERE ID_Admin = ?");
                $stmt->execute([$_POST['id']]);
                echo "<div class='alert alert-success'>Data admin berhasil dihapus.</div>";
            }
        }
        elseif ($action === 'reset_pass') {
            // Reset Password
            $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE Admin SET Password_Hash = ? WHERE ID_Admin = ?");
            $stmt->execute([$passHash, $_POST['id']]);
            echo "<div class='alert alert-success'>Password berhasil direset!</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$admins = $pdo->query("SELECT * FROM Admin ORDER BY Level DESC, Nama_Lengkap ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark"><i class="fas fa-user-shield me-2"></i> Manajemen Admin</h2>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addAdminModal"><i class="fas fa-plus"></i> Admin Baru</button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">No</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Level</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; foreach($admins as $a): ?>
                    <tr>
                        <td class="ps-4"><?= $no++ ?></td>
                        <td class="fw-bold">
                            <?= htmlspecialchars($a['Nama_Lengkap']) ?>
                            <?php if($a['ID_Admin'] == $_SESSION['user_id']): ?>
                                <span class="badge bg-success ms-2">Anda</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($a['Username']) ?></td>
                        <td>
                            <?php if($a['Level'] == 'superadmin'): ?>
                                <span class="badge bg-danger">SUPER ADMIN</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Admin Biasa</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="resetPass(<?= $a['ID_Admin'] ?>, '<?= addslashes($a['Username']) ?>')">
                                <i class="fas fa-key"></i>
                            </button>
                            <?php if($a['ID_Admin'] != $_SESSION['user_id']): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Hapus admin ini?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $a['ID_Admin'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
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

<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title">Tambah Admin</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="mb-3"><label>Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" required></div>
                
                <div class="mb-3">
                    <label>Level Akses</label>
                    <select name="level" class="form-select" required>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                
                <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-dark">Simpan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="resetPassModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-warning text-dark"><h5 class="modal-title">Reset Password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="reset_pass">
                <input type="hidden" name="id" id="resetId">
                <p>Set password baru untuk <b id="resetUser"></b>:</p>
                <input type="password" name="password" class="form-control" placeholder="Password Baru" required>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning">Update Password</button></div>
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