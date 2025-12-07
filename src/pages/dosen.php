<?php
// FILE: src/pages/dosen.php (Full CRUD Features)

// 1. CEK HAK AKSES ADMIN (Untuk Fitur Tambah/Edit/Hapus)
// Hanya Admin atau Superadmin yang bisa melakukan perubahan data
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// --- HANDLE POST ACTION (Hanya Diproses jika user adalah Admin) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    try {
        $action = $_POST['action'];

        // A. TAMBAH DOSEN BARU (CREATE)
        if ($action === 'add_dosen') {
            // Password default untuk dosen baru: 123456
            $defaultPass = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO Dosen_Pembimbing (..., Password_Hash, Need_Reset) VALUES (..., ?, 1)");
            
            // Cek Email/NIDN Duplikat
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Dosen_Pembimbing WHERE Email = ? OR NIDN = ?");
            $cek->execute([$_POST['email'], $_POST['nidn']]);
            
            if ($cek->fetchColumn() > 0) {
                echo "<script>alert('Gagal: Email atau NIDN sudah terdaftar!');</script>";
            } else {
                $stmt = $pdo->prepare("INSERT INTO Dosen_Pembimbing (Nama_Dosen, NIDN, ID_Prodi, Email, No_HP, Bio, Password_Hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nama'], 
                    $_POST['nidn'], 
                    $_POST['id_prodi'], 
                    $_POST['email'], 
                    $_POST['no_hp'], 
                    $_POST['bio'],
                    $defaultPass
                ]);
                echo "<script>alert('Dosen berhasil ditambahkan! Password default: 123456'); window.location='?page=dosen';</script>";
            }
        }

        // B. EDIT DATA DOSEN (UPDATE)
        elseif ($action === 'edit_dosen') {
            $stmt = $pdo->prepare("UPDATE Dosen_Pembimbing SET Nama_Dosen=?, Email=?, NIDN=?, No_HP=?, Bio=?, ID_Prodi=? WHERE ID_Dosen=?");
            $stmt->execute([
                $_POST['nama'], 
                $_POST['email'], 
                $_POST['nidn'], 
                $_POST['no_hp'], 
                $_POST['bio'], 
                $_POST['id_prodi'], 
                $_POST['id_dosen']
            ]);
            echo "<script>alert('Data dosen berhasil diperbarui!'); window.location='?page=dosen';</script>";
        } 
        
        // C. HAPUS DATA DOSEN (DELETE)
        elseif ($action === 'delete_dosen') {
            $pdo->prepare("DELETE FROM Dosen_Pembimbing WHERE ID_Dosen=?")->execute([$_POST['id_dosen']]);
            echo "<script>alert('Data dosen berhasil dihapus.'); window.location='?page=dosen';</script>";
        }

    } catch (Exception $e) {
        echo "<script>alert('Gagal: " . $e->getMessage() . "');</script>";
    }
}

// 2. SEARCH & FILTER LOGIC (READ)
$keyword = $_GET['q'] ?? '';
$prodiFilter = $_GET['prodi'] ?? '';

// Query data dosen join dengan nama Prodi
$sql = "SELECT d.*, p.Nama_Prodi 
        FROM Dosen_Pembimbing d
        LEFT JOIN Prodi p ON d.ID_Prodi = p.ID_Prodi
        WHERE 1=1";

$params = [];
if ($keyword) {
    $sql .= " AND (d.Nama_Dosen LIKE ? OR d.Bio LIKE ? OR d.NIDN LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}
if ($prodiFilter) {
    $sql .= " AND d.ID_Prodi = ?";
    $params[] = $prodiFilter;
}

$sql .= " ORDER BY d.Nama_Dosen ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dosenList = $stmt->fetchAll();

// Ambil Data Prodi untuk Dropdown Filter & Modal
$prodiList = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();
?>

<div class="row align-items-center mb-4">
    <div class="col-md-5">
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Dosen Pembimbing</h3>
        <p class="text-muted mb-0">Cari dosen pembimbing untuk tim Anda.</p>
    </div>
    
    <div class="col-md-7 mt-3 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
        <form method="GET" class="card border-0 shadow-sm p-1 flex-grow-1" style="max-width: 400px;">
            <input type="hidden" name="page" value="dosen">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-0 shadow-none" placeholder="Nama / NIDN..." value="<?= htmlspecialchars($keyword) ?>">
                <select name="prodi" class="form-select border-0 shadow-none bg-light" style="max-width: 140px;" onchange="this.form.submit()">
                    <option value="">Semua Prodi</option>
                    <?php foreach($prodiList as $p): ?>
                        <option value="<?= $p['ID_Prodi'] ?>" <?= $prodiFilter==$p['ID_Prodi']?'selected':'' ?>>
                            <?= htmlspecialchars($p['Nama_Prodi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if($isAdmin): ?>
            <button class="btn btn-primary fw-bold shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addDosenModal">
                <i class="fas fa-plus me-1"></i> Tambah
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <?php if(empty($dosenList)): ?>
        <div class="col-12 text-center py-5">
            <div class="text-muted opacity-50"><i class="fas fa-user-slash fa-3x mb-3"></i></div>
            <h5>Data Dosen Tidak Ditemukan</h5>
            <p class="small text-muted">Coba kata kunci lain atau reset filter.</p>
        </div>
    <?php endif; ?>

    <?php foreach($dosenList as $d): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4 hover-card position-relative">
            <span class="badge bg-primary position-absolute top-0 end-0 m-3 shadow-sm bg-opacity-75">
                <?= htmlspecialchars($d['Nama_Prodi'] ?? 'Umum') ?>
            </span>

            <div class="card-body text-center p-4">
                <?php 
                    $foto = !empty($d['Foto_Profil']) && file_exists($d['Foto_Profil']) ? $d['Foto_Profil'] : null;
                    if($foto): 
                ?>
                    <img src="<?= $foto ?>" class="rounded-circle mb-3 shadow-sm object-fit-cover" style="width: 90px; height: 90px; border: 3px solid #fff;">
                <?php else: ?>
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3 text-secondary fw-bold fs-3 shadow-sm border" style="width: 90px; height: 90px;">
                        <?= substr($d['Nama_Dosen'], 0, 1) ?>
                    </div>
                <?php endif; ?>
                
                <h6 class="fw-bold text-dark mb-1 text-truncate"><?= htmlspecialchars($d['Nama_Dosen']) ?></h6>
                <div class="text-muted small mb-2"><i class="fas fa-id-card me-1"></i><?= htmlspecialchars($d['NIDN']) ?></div>
                
                <p class="text-muted small text-truncate-3 mb-4 bg-light p-2 rounded" style="min-height: 50px; font-size: 0.85rem;">
                    <?= $d['Bio'] ? htmlspecialchars(substr($d['Bio'], 0, 80)).(strlen($d['Bio'])>80?'...':'') : '<em class="text-muted opacity-50">Belum ada bio singkat.</em>' ?>
                </p>

                <div class="d-grid gap-2">
                    <a href="?page=profile_dosen&id=<?= $d['ID_Dosen'] ?>" class="btn btn-outline-primary rounded-pill btn-sm fw-bold">Lihat Profil</a>
                    
                    <?php if($isAdmin): ?>
                    <div class="d-flex gap-2 justify-content-center mt-2 border-top pt-3">
                        <button class="btn btn-sm btn-light text-primary border shadow-sm w-100" onclick='editDosen(<?= json_encode($d) ?>)' title="Edit Data">
                            <i class="fas fa-pencil-alt"></i> Edit
                        </button>
                        
                        <form method="POST" class="w-100" onsubmit="return confirm('Yakin ingin menghapus <?= addslashes($d['Nama_Dosen']) ?>? Data tidak bisa dikembalikan.')">
                            <input type="hidden" name="action" value="delete_dosen">
                            <input type="hidden" name="id_dosen" value="<?= $d['ID_Dosen'] ?>">
                            <button class="btn btn-sm btn-light text-danger border shadow-sm w-100" title="Hapus Dosen">
                                <i class="fas fa-trash-alt"></i> Hapus
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if($isAdmin): ?>

<div class="modal fade" id="addDosenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Tambah Dosen Baru</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add_dosen">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Nama Lengkap & Gelar</label>
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: Dr. Budi Santoso, M.Kom" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold text-muted">NIDN</label>
                        <input type="number" name="nidn" class="form-control" placeholder="Nomor Induk" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold text-muted">Program Studi</label>
                        <select name="id_prodi" class="form-select" required>
                            <option value="">-- Pilih Prodi --</option>
                            <?php foreach($prodiList as $p): ?>
                                <option value="<?= $p['ID_Prodi'] ?>"><?= htmlspecialchars($p['Nama_Prodi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold text-muted">Email UNNES</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@mail.unnes.ac.id" required>
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">No HP (Opsional)</label>
                    <input type="text" name="no_hp" class="form-control" placeholder="08...">
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Bio Singkat</label>
                    <textarea name="bio" class="form-control" rows="2" placeholder="Keahlian: AI, Web Dev..."></textarea>
                </div>

                <div class="alert alert-light border small text-muted mb-3">
                    <i class="fas fa-key me-1"></i> Password default akun baru adalah: <b>123456</b>
                </div>
                
                <button class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editDosenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Data Dosen</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="edit_dosen">
                <input type="hidden" name="id_dosen" id="editIdDosen">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Nama Lengkap & Gelar</label>
                    <input type="text" name="nama" id="editNama" class="form-control" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold text-muted">NIDN</label>
                        <input type="text" name="nidn" id="editNidn" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold text-muted">Program Studi</label>
                        <select name="id_prodi" id="editProdi" class="form-select">
                            <option value="">-- Pilih Prodi --</option>
                            <?php foreach($prodiList as $p): ?>
                                <option value="<?= $p['ID_Prodi'] ?>"><?= htmlspecialchars($p['Nama_Prodi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold text-muted">Email</label>
                    <input type="email" name="email" id="editEmail" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">No HP</label>
                    <input type="text" name="no_hp" id="editHp" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Bio Singkat</label>
                    <textarea name="bio" id="editBio" class="form-control" rows="3"></textarea>
                </div>
                
                <button class="btn btn-dark w-100 rounded-pill fw-bold shadow-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editDosen(data) {
    document.getElementById('editIdDosen').value = data.ID_Dosen;
    document.getElementById('editNama').value = data.Nama_Dosen;
    document.getElementById('editNidn').value = data.NIDN;
    document.getElementById('editEmail').value = data.Email;
    document.getElementById('editHp').value = data.No_HP;
    document.getElementById('editBio').value = data.Bio;
    document.getElementById('editProdi').value = data.ID_Prodi;
    
    // Tampilkan Modal
    new bootstrap.Modal(document.getElementById('editDosenModal')).show();
}
</script>
<?php endif; ?>