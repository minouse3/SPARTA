<?php
// FILE: Halaman Detail Pengelolaan Tim (Updated: Delete Team Feature)

// Cek Login
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>";
    exit;
}

$userId = $_SESSION['user_id'];
$idTim = $_GET['id'] ?? 0;
$message = "";

// 1. Validasi Akses (Ketua ATAU Anggota)
$stmtTim = $pdo->prepare("SELECT * FROM Tim WHERE ID_Tim = ?");
$stmtTim->execute([$idTim]);
$timData = $stmtTim->fetch();

if (!$timData) {
    echo "<div class='alert alert-danger m-4 border-0 shadow-sm'>Tim tidak ditemukan.</div>";
    echo "<div class='m-4'><a href='?page=manajemen_tim' class='btn btn-light'>Kembali</a></div>";
    return;
}

$isLeader = ($timData['ID_Mahasiswa_Ketua'] == $userId);
$isMember = false;

if ($isLeader) {
    $userAccess = $timData;
} else {
    $stmtMember = $pdo->prepare("SELECT * FROM Keanggotaan_Tim WHERE ID_Tim = ? AND ID_Mahasiswa = ?");
    $stmtMember->execute([$idTim, $userId]);
    $memberData = $stmtMember->fetch();

    if ($memberData) {
        $isMember = true;
        $userAccess = array_merge($timData, $memberData);
    } else {
        echo "<div class='alert alert-danger m-4 border-0 shadow-sm'>Akses ditolak. Anda bukan anggota tim ini.</div>";
        echo "<div class='m-4'><a href='?page=manajemen_tim' class='btn btn-light'>Kembali</a></div>";
        return;
    }
}

// 2. LOGIC POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'];

        // A. UPDATE INFO TIM (Hanya Leader)
        if ($action === 'update_tim' && $isLeader) {
            $dosenId = empty($_POST['dosen']) ? NULL : $_POST['dosen'];
            $stmt = $pdo->prepare("UPDATE Tim SET Nama_Tim=?, Deskripsi_Tim=?, Status_Pencarian=?, Kebutuhan_Role=?, ID_Dosen_Pembimbing=? WHERE ID_Tim=?");
            $stmt->execute([$_POST['nama'], $_POST['deskripsi'], $_POST['status'], $_POST['roles_needed'], $dosenId, $idTim]);
            $message = "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-check-circle me-2'></i>Info tim berhasil diperbarui.</div>";
            
            $stmtTim->execute([$idTim]);
            $timData = $stmtTim->fetch();
            $userAccess = array_merge($userAccess, $timData);
        }
        // B. HAPUS TIM (Action Baru - Hanya Leader)
        elseif ($action === 'delete_team' && $isLeader) {
            $stmt = $pdo->prepare("DELETE FROM Tim WHERE ID_Tim = ? AND ID_Mahasiswa_Ketua = ?");
            $stmt->execute([$idTim, $userId]);
            
            // Redirect setelah sukses hapus
            header("Location: index.php?page=manajemen_tim&status_tim=deleted");
            exit;
        }
        // C. TAMBAH ANGGOTA (Hanya Leader)
        elseif ($action === 'add_member' && $isLeader) {
            if ($_POST['mahasiswa'] == $userId) {
                $message = "<div class='alert alert-warning border-0 shadow-sm'>Anda adalah ketua tim ini.</div>";
            } else {
                $cek = $pdo->prepare("SELECT COUNT(*) FROM Keanggotaan_Tim WHERE ID_Tim = ? AND ID_Mahasiswa = ?");
                $cek->execute([$idTim, $_POST['mahasiswa']]);
                if ($cek->fetchColumn() > 0) {
                    $message = "<div class='alert alert-warning border-0 shadow-sm'>Mahasiswa tersebut sudah ada di tim.</div>";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO Keanggotaan_Tim (ID_Tim, ID_Mahasiswa, Peran) VALUES (?, ?, ?)");
                    $stmt->execute([$idTim, $_POST['mahasiswa'], $_POST['peran']]);
                    $message = "<div class='alert alert-success border-0 shadow-sm'>Anggota berhasil ditambahkan.</div>";
                }
            }
        }
        // D. HAPUS ANGGOTA / KICK (Hanya Leader)
        elseif ($action === 'delete_member' && $isLeader) {
            if ($_POST['id_mhs_target'] != $userId) {
                $pdo->prepare("DELETE FROM Keanggotaan_Tim WHERE ID_Keanggotaan = ? AND ID_Tim = ?")->execute([$_POST['id_member'], $idTim]);
                $message = "<div class='alert alert-info border-0 shadow-sm'>Anggota telah dikeluarkan.</div>";
            }
        }
        // E. EDIT PERAN (Leader OR Self)
        elseif ($action === 'edit_role') {
            $targetId = $_POST['id_mhs_target'];
            if ($isLeader || $targetId == $userId) {
                if ($targetId == $timData['ID_Mahasiswa_Ketua']) {
                     $message = "<div class='alert alert-info border-0 shadow-sm'>Peran Ketua adalah tetap (Leader).</div>";
                } else {
                    $stmt = $pdo->prepare("UPDATE Keanggotaan_Tim SET Peran = ? WHERE ID_Keanggotaan = ?");
                    $stmt->execute([$_POST['peran_baru'], $_POST['id_member']]);
                    $message = "<div class='alert alert-success border-0 shadow-sm'>Peran berhasil diperbarui.</div>";
                }
            } else {
                $message = "<div class='alert alert-danger border-0 shadow-sm'>Anda tidak memiliki izin mengubah peran anggota lain.</div>";
            }
        }

    } catch (Exception $e) {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>Error: " . $e->getMessage() . "</div>";
    }
}

// 3. AMBIL DATA PENDUKUNG
$stmtMem = $pdo->prepare("SELECT k.*, m.Nama_Mahasiswa, m.NIM, m.Foto_Profil, m.ID_Mahasiswa 
                          FROM Keanggotaan_Tim k 
                          JOIN Mahasiswa m ON k.ID_Mahasiswa = m.ID_Mahasiswa 
                          WHERE k.ID_Tim = ?");
$stmtMem->execute([$idTim]);
$members = $stmtMem->fetchAll();

$stmtKetua = $pdo->prepare("SELECT * FROM Mahasiswa WHERE ID_Mahasiswa = ?");
$stmtKetua->execute([$timData['ID_Mahasiswa_Ketua']]);
$ketuaData = $stmtKetua->fetch();

$mahasiswaList = $pdo->query("SELECT * FROM Mahasiswa ORDER BY Nama_Mahasiswa ASC")->fetchAll();
$dosenList = $pdo->query("SELECT * FROM Dosen_Pembimbing ORDER BY Nama_Dosen ASC")->fetchAll();
?>

<style>
    /* Styling Tag Input */
    .tag-container {
        min-height: 40px; padding: 6px; border: 1px solid #ced4da; border-radius: 0.375rem; background-color: #fff; display: flex; flex-wrap: wrap; gap: 6px;
    }
    .tag-container:focus-within { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25); }
    .tag-item {
        background: #e9ecef; color: #495057; padding: 2px 10px; border-radius: 50px; font-size: 0.85rem; display: flex; align-items: center;
    }
    .tag-item i { margin-left: 6px; cursor: pointer; }
    .tag-input { border: none; outline: none; flex-grow: 1; min-width: 100px; font-size: 0.9rem; }
    .suggestion-box {
        position: absolute; width: 100%; z-index: 1000; background: white; border: 1px solid #dee2e6; border-radius: 0 0 8px 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto;
    }
    .cursor-pointer { cursor: pointer; }
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
        <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($userAccess['Nama_Tim']) ?></h3>
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
                <?php if($isLeader): ?>
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
                        <label class="small fw-bold text-muted">Dosen Pendamping (Opsional)</label>
                        <select name="dosen" class="form-select">
                            <option value="">-- Belum Ada --</option>
                            <?php foreach($dosenList as $d): ?>
                                <option value="<?= $d['ID_Dosen'] ?>" <?= $userAccess['ID_Dosen_Pembimbing'] == $d['ID_Dosen'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['Nama_Dosen']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 position-relative">
                        <label class="small fw-bold text-muted">Role yang Dibutuhkan</label>
                        <div id="role-container" class="tag-container" onclick="document.getElementById('role-input').focus()">
                            <input type="text" id="role-input" class="tag-input" placeholder="Ketik role & enter...">
                        </div>
                        <div id="role-suggestions" class="suggestion-box" style="display:none;"></div>
                        <input type="hidden" name="roles_needed" id="role-hidden" value="<?= htmlspecialchars($userAccess['Kebutuhan_Role'] ?? '') ?>">
                        <div class="form-text small">Contoh: Frontend, UI/UX (Gunakan Enter)</div>
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
                        <i class="fas fa-trash-alt me-2"></i> Hapus Tim Permanen
                    </button>
                </div>
                <form id="deleteForm" method="POST" style="display: none;">
                    <input type="hidden" name="action" value="delete_team">
                </form>
                <script>
                function confirmDelete() {
                    if (confirm('PERINGATAN! Menghapus tim akan menghapus semua data anggota dan menghapus semua riwayat lomba tim ini. Apakah Anda yakin ingin menghapus tim ini secara permanen?')) {
                        document.getElementById('deleteForm').submit();
                    }
                }
                </script>

                <?php else: /* Tampilan untuk Anggota Biasa */ ?>
                    <div class="alert alert-light border text-center small text-muted">
                        <i class="fas fa-lock mb-2"></i><br>Hanya Ketua Tim yang dapat mengubah pengaturan.
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Deskripsi</label>
                        <p class="small"><?= nl2br(htmlspecialchars($userAccess['Deskripsi_Tim'])) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Dosen Pendamping</label>
                        <?php 
                            $dosenNama = "Belum Ada";
                            foreach($dosenList as $d) if($d['ID_Dosen'] == $userAccess['ID_Dosen_Pembimbing']) $dosenNama = $d['Nama_Dosen'];
                        ?>
                        <p class="small fw-bold text-dark"><?= $dosenNama ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white fw-bold text-dark border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2 text-success"></i>Daftar Anggota</span>
                <span class="badge bg-light text-dark"><?= count($members) + 1 ?> Orang</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Nama</th>
                                <th>Role / Peran</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <?php 
                                            $fotoKetua = getFotoMhs($ketuaData['NIM'], $ketuaData['Foto_Profil']);
                                            if($fotoKetua) {
                                                echo "<img src='$fotoKetua' class='rounded-circle me-2' style='width:35px;height:35px;object-fit:cover'>";
                                            } else {
                                                echo "<div class='rounded-circle bg-light border d-flex align-items-center justify-content-center me-2 fw-bold text-secondary' style='width: 35px; height: 35px; font-size: 0.8rem;'>".substr($ketuaData['Nama_Mahasiswa'], 0, 1)."</div>";
                                            }
                                        ?>
                                        <div>
                                            <a href="?page=profile&id=<?= $ketuaData['ID_Mahasiswa'] ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($ketuaData['Nama_Mahasiswa']) ?>
                                                <?php if($ketuaData['ID_Mahasiswa'] == $userId): ?><span class="text-muted small fw-normal">(Anda)</span><?php endif; ?>
                                            </a>
                                            <div class="small text-muted">
                                                <?= $ketuaData['NIM'] ?> 
                                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;">LEADER</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Leader</span></td>
                                <td class="text-end pe-4"><i class="fas fa-crown text-warning"></i></td>
                            </tr>
                            
                            <?php foreach($members as $m): ?>
                            <?php $isMe = ($m['ID_Mahasiswa'] == $userId); ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <?php 
                                            $foto = getFotoMhs($m['NIM'], $m['Foto_Profil']);
                                            if($foto) {
                                                echo "<img src='$foto' class='rounded-circle me-2' style='width:35px;height:35px;object-fit:cover'>";
                                            } else {
                                                echo "<div class='rounded-circle bg-light border d-flex align-items-center justify-content-center me-2 fw-bold text-secondary' style='width: 35px; height: 35px; font-size: 0.8rem;'>".substr($m['Nama_Mahasiswa'], 0, 1)."</div>";
                                            }
                                        ?>
                                        <div>
                                            <a href="?page=profile&id=<?= $m['ID_Mahasiswa'] ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($m['Nama_Mahasiswa']) ?>
                                                <?php if($isMe): ?><span class="text-muted small fw-normal">(Anda)</span><?php endif; ?>
                                            </a>
                                            <div class="small text-muted"><?= $m['NIM'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 fw-normal">
                                        <?= htmlspecialchars($m['Peran']) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($isLeader || $isMe): ?>
                                        <button class="btn btn-light text-primary btn-sm rounded-circle shadow-sm me-1" 
                                                onclick="editRole(<?= $m['ID_Keanggotaan'] ?>, '<?= addslashes($m['Peran']) ?>', <?= $m['ID_Mahasiswa'] ?>)" 
                                                title="Edit Peran">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if($isLeader): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengeluarkan anggota ini?');">
                                            <input type="hidden" name="action" value="delete_member">
                                            <input type="hidden" name="id_mhs_target" value="<?= $m['ID_Mahasiswa'] ?>">
                                            <input type="hidden" name="id_member" value="<?= $m['ID_Keanggotaan'] ?>">
                                            <button class="btn btn-light text-danger btn-sm rounded-circle shadow-sm" title="Keluarkan">
                                                <i class="fas fa-times"></i>
                                            </button>
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

        <?php if($isLeader): ?>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white fw-bold text-dark border-bottom-0 py-3">
                <i class="fas fa-user-plus me-2 text-info"></i>Tambah Anggota Manual
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="add_member">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">Pilih Mahasiswa</label>
                        <select name="mahasiswa" class="form-select" required>
                            <option value="">-- Cari Nama / NIM --</option>
                            <?php foreach($mahasiswaList as $mhs): ?>
                                <?php 
                                    $isAlreadyIn = false;
                                    if ($mhs['ID_Mahasiswa'] == $timData['ID_Mahasiswa_Ketua']) $isAlreadyIn = true;
                                    foreach($members as $mem) if($mem['ID_Mahasiswa'] == $mhs['ID_Mahasiswa']) $isAlreadyIn = true;
                                    
                                    if(!$isAlreadyIn): 
                                ?>
                                    <option value="<?= $mhs['ID_Mahasiswa'] ?>"><?= htmlspecialchars($mhs['Nama_Mahasiswa']) ?> (<?= $mhs['NIM'] ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-1">Peran</label>
                        <input type="text" name="peran" class="form-control" placeholder="Contoh: Designer" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-success w-100 fw-bold rounded-pill">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header bg-white border-0 pb-0">
                <h6 class="modal-title fw-bold">Edit Peran Anggota</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_role">
                <input type="hidden" name="id_member" id="editRoleIdMember">
                <input type="hidden" name="id_mhs_target" id="editRoleIdMhs">
                
                <div class="mb-3">
                    <label class="small text-muted fw-bold mb-1">Peran Baru</label>
                    <input type="text" name="peran_baru" id="editRoleInput" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100 rounded-pill fw-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRole(idKeanggotaan, peranLama, idMhs) {
    document.getElementById('editRoleIdMember').value = idKeanggotaan;
    document.getElementById('editRoleIdMhs').value = idMhs;
    document.getElementById('editRoleInput').value = peranLama;
    new bootstrap.Modal(document.getElementById('editRoleModal')).show();
}

// Logic Tag Input (Reusable from other pages)
document.addEventListener('DOMContentLoaded', () => {
    if(document.getElementById('role-input')) {
        setupTagSystem('role-container', 'role-input', 'role-hidden', 'role-suggestions', 'role');
    }
});

function setupTagSystem(containerId, inputId, hiddenId, suggestId, type) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const suggestionBox = document.getElementById(suggestId);
    
    let tags = hidden.value ? hidden.value.split(',').filter(t => t) : [];
    renderTags();

    function renderTags() {
        const items = container.querySelectorAll('.tag-item');
        items.forEach(i => i.remove());
        tags.forEach((tag, index) => {
            const span = document.createElement('span');
            span.className = 'tag-item border bg-light text-dark';
            span.innerHTML = `${tag} <i class="fas fa-times text-danger ms-2" onclick="removeTag('${hiddenId}', ${index})"></i>`;
            container.insertBefore(span, input);
        });
        hidden.value = tags.join(',');
    }

    window.removeTag = function(targetId, idx) {
        if(targetId === hiddenId) { tags.splice(idx, 1); renderTags(); }
    };

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); addTag(this.value); }
        else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) { tags.pop(); renderTags(); }
    });

    input.addEventListener('input', function() {
        const val = this.value.trim();
        if (val.length < 1) { suggestionBox.style.display = 'none'; return; }
        
        fetch(`fetch_tags.php?type=${type}&q=${val}`)
            .then(res => res.json())
            .then(data => {
                suggestionBox.innerHTML = '';
                if (data.length > 0) {
                    suggestionBox.style.display = 'block';
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'p-2 border-bottom cursor-pointer hover-bg-light';
                        div.textContent = item.name;
                        div.onclick = () => { addTag(item.name); suggestionBox.style.display = 'none'; };
                        suggestionBox.appendChild(div);
                    });
                } else { suggestionBox.style.display = 'none'; }
            });
    });

    function addTag(text) {
        const cleanText = text.trim();
        if (cleanText && !tags.some(t => t.toLowerCase() === cleanText.toLowerCase())) {
            tags.push(cleanText); renderTags();
        }
        input.value = ''; suggestionBox.style.display = 'none'; input.focus();
    }
    
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target) && !suggestionBox.contains(e.target)) { suggestionBox.style.display = 'none'; }
    });
}
</script>