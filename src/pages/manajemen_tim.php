<?php
// FILE: Manajemen Tim Saya (Grid View + Modal Detail)
// Fungsi: Dashboard pengelolaan tim dengan tampilan kartu interaktif

// Cek Login
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>";
    exit;
}

$userId = $_SESSION['user_id'];
$message = "";

// --- LOGIC CRUD (Tetap Sama, hanya penyesuaian redirect/pesan) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. BUAT TIM BARU
        if ($_POST['action'] === 'create_tim') {
            $stmt = $pdo->prepare("INSERT INTO Tim (Nama_Tim, Status_Pencarian, ID_Lomba, ID_Mahasiswa_Ketua, Deskripsi_Tim) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['nama'], 'Terbuka', $_POST['lomba'], $userId, $_POST['deskripsi']]);
            $message = "<div class='alert alert-success'>Tim berhasil dibuat!</div>";
        }
        // 2. UPDATE INFO TIM
        elseif ($_POST['action'] === 'update_tim') {
            $stmt = $pdo->prepare("UPDATE Tim SET Nama_Tim=?, Deskripsi_Tim=?, Status_Pencarian=? WHERE ID_Tim=? AND ID_Mahasiswa_Ketua=?");
            $stmt->execute([$_POST['nama'], $_POST['deskripsi'], $_POST['status'], $_POST['id_tim'], $userId]);
            $message = "<div class='alert alert-success'>Info tim diperbarui.</div>";
        }
        // 3. TAMBAH ANGGOTA
        elseif ($_POST['action'] === 'add_member') {
            // Cek duplikasi
            $cek = $pdo->prepare("SELECT COUNT(*) FROM Keanggotaan_Tim WHERE ID_Tim = ? AND ID_Mahasiswa = ?");
            $cek->execute([$_POST['id_tim'], $_POST['mahasiswa']]);
            if ($cek->fetchColumn() > 0) {
                $message = "<div class='alert alert-warning'>Mahasiswa tersebut sudah ada di tim.</div>";
            } else {
                $stmt = $pdo->prepare("INSERT INTO Keanggotaan_Tim (ID_Tim, ID_Mahasiswa, Peran) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['id_tim'], $_POST['mahasiswa'], $_POST['peran']]);
                $message = "<div class='alert alert-success'>Anggota berhasil ditambahkan.</div>";
            }
        }
        // 4. HAPUS ANGGOTA (KICK)
        elseif ($_POST['action'] === 'delete_member') {
            $cekOwner = $pdo->prepare("SELECT ID_Mahasiswa_Ketua FROM Tim WHERE ID_Tim = ?");
            $cekOwner->execute([$_POST['id_tim']]);
            if ($cekOwner->fetchColumn() == $userId) {
                $pdo->prepare("DELETE FROM Keanggotaan_Tim WHERE ID_Keanggotaan = ?")->execute([$_POST['id_member']]);
                $message = "<div class='alert alert-warning'>Anggota telah dikeluarkan.</div>";
            }
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// AMBIL TIM MILIK USER
$stmtTim = $pdo->prepare("SELECT t.*, l.Nama_Lomba, 
                          (SELECT COUNT(*) FROM Keanggotaan_Tim WHERE ID_Tim = t.ID_Tim) as Jml_Anggota
                          FROM Tim t 
                          JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba 
                          WHERE t.ID_Mahasiswa_Ketua = ? 
                          ORDER BY t.ID_Tim DESC");
$stmtTim->execute([$userId]);
$myTeams = $stmtTim->fetchAll();

// DATA MASTER
$lombaList = $pdo->query("SELECT * FROM Lomba WHERE Tanggal_Selesai >= CURDATE()")->fetchAll();
$mahasiswaList = $pdo->query("SELECT * FROM Mahasiswa ORDER BY Nama_Mahasiswa ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark"><i class="fas fa-th-large me-2"></i>Tim Saya</h2>
        <p class="text-muted mb-0">Kelola tim, edit info, dan atur anggota.</p>
    </div>
    <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#createTeamModal">
        <i class="fas fa-plus me-2"></i>Buat Tim Baru
    </button>
</div>

<?= $message ?>

<?php if(empty($myTeams)): ?>
    <div class="text-center py-5">
        <div class="mb-3 text-muted opacity-25"><i class="fas fa-folder-open fa-4x"></i></div>
        <h5>Belum ada tim</h5>
        <p class="text-muted">Ayo buat tim pertamamu sekarang!</p>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach($myTeams as $tim): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 hover-lift cursor-pointer" data-bs-toggle="modal" data-bs-target="#manageModal<?= $tim['ID_Tim'] ?>">
            <div class="position-absolute top-0 end-0 m-3">
                <span class="badge bg-<?= $tim['Status_Pencarian']=='Terbuka'?'success':'secondary' ?> shadow-sm">
                    <?= $tim['Status_Pencarian'] ?>
                </span>
            </div>

            <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                <div class="mb-3">
                    <div class="avatar-circle mx-auto bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 60px; height: 60px; border-radius: 50%;">
                        <?= strtoupper(substr($tim['Nama_Tim'], 0, 1)) ?>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($tim['Nama_Tim']) ?></h5>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;"><?= htmlspecialchars($tim['Nama_Lomba']) ?></small>
                
                <hr class="w-50 mx-auto my-3 opacity-25">
                
                <div class="d-flex justify-content-center gap-3 text-secondary">
                    <div class="d-flex align-items-center" title="Jumlah Anggota (Termasuk Ketua)">
                        <i class="fas fa-users me-2"></i> <?= $tim['Jml_Anggota'] + 1 ?> Member
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light text-center border-0 py-3">
                <small class="fw-bold text-primary">Klik untuk Mengelola <i class="fas fa-arrow-right ms-1"></i></small>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manageModal<?= $tim['ID_Tim'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-cog me-2"></i>Kelola Tim: <?= htmlspecialchars($tim['Nama_Tim']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body bg-light">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold text-primary"><i class="fas fa-edit me-2"></i>Informasi Tim</div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_tim">
                                <input type="hidden" name="id_tim" value="<?= $tim['ID_Tim'] ?>">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="small fw-bold">Nama Tim</label>
                                        <input type="text" name="nama" class="form-control form-control-sm" value="<?= htmlspecialchars($tim['Nama_Tim']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-bold">Status</label>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="Terbuka" <?= $tim['Status_Pencarian']=='Terbuka'?'selected':''?>>Terbuka</option>
                                            <option value="Tertutup" <?= $tim['Status_Pencarian']=='Tertutup'?'selected':''?>>Tertutup</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="small fw-bold">Deskripsi / Kebutuhan</label>
                                        <textarea name="deskripsi" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($tim['Deskripsi_Tim']) ?></textarea>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button class="btn btn-primary btn-sm">Simpan Perubahan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php
                        // Query Anggota (Dalam Loop Modal)
                        $stmtMem = $pdo->prepare("SELECT k.*, m.Nama_Mahasiswa, m.NIM FROM Keanggotaan_Tim k JOIN Mahasiswa m ON k.ID_Mahasiswa = m.ID_Mahasiswa WHERE k.ID_Tim = ?");
                        $stmtMem->execute([$tim['ID_Tim']]);
                        $members = $stmtMem->fetchAll();
                    ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold text-success"><i class="fas fa-users me-2"></i>Daftar Anggota</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-muted small text-uppercase">
                                        <tr>
                                            <th class="ps-3">Nama</th>
                                            <th>Peran</th>
                                            <th class="text-end pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold">Anda (Ketua)</div>
                                            </td>
                                            <td>Leader</td>
                                            <td class="text-end pe-3"><span class="badge bg-secondary">Owner</span></td>
                                        </tr>
                                        <?php foreach($members as $m): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold"><?= htmlspecialchars($m['Nama_Mahasiswa']) ?></div>
                                                <small class="text-muted"><?= $m['NIM'] ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($m['Peran']) ?></td>
                                            <td class="text-end pe-3">
                                                <form method="POST" onsubmit="return confirm('Keluarkan anggota ini?')">
                                                    <input type="hidden" name="action" value="delete_member">
                                                    <input type="hidden" name="id_tim" value="<?= $tim['ID_Tim'] ?>">
                                                    <input type="hidden" name="id_member" value="<?= $m['ID_Keanggotaan'] ?>">
                                                    <button class="btn btn-outline-danger btn-sm py-0 px-2" title="Keluarkan">
                                                        <i class="fas fa-user-times"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold text-dark"><i class="fas fa-user-plus me-2"></i>Tambah Anggota Manual</div>
                        <div class="card-body">
                            <form method="POST" class="row g-2 align-items-end">
                                <input type="hidden" name="action" value="add_member">
                                <input type="hidden" name="id_tim" value="<?= $tim['ID_Tim'] ?>">
                                <div class="col-md-6">
                                    <label class="small fw-bold">Pilih Mahasiswa</label>
                                    <select name="mahasiswa" class="form-select form-select-sm" required>
                                        <option value="">-- Cari Nama --</option>
                                        <?php foreach($mahasiswaList as $mhs): ?>
                                            <?php if($mhs['ID_Mahasiswa'] != $userId): ?>
                                                <option value="<?= $mhs['ID_Mahasiswa'] ?>"><?= $mhs['Nama_Mahasiswa'] ?> (<?= $mhs['NIM'] ?>)</option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold">Peran</label>
                                    <input type="text" name="peran" class="form-control form-control-sm" placeholder="Contoh: Frontend" required>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-success btn-sm w-100">Tambah</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div> <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="createTeamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white"><h5 class="modal-title">Buat Tim Baru</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_tim">
                    <div class="mb-3"><label class="fw-bold small">Nama Tim</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="fw-bold small">Target Lomba</label>
                        <select name="lomba" class="form-select" required>
                            <?php foreach($lombaList as $l): ?>
                                <option value="<?= $l['ID_Lomba'] ?>"><?= $l['Nama_Lomba'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="fw-bold small">Deskripsi</label><textarea name="deskripsi" class="form-control" placeholder="Kami mencari..." required></textarea></div>
                </div>
                <div class="modal-footer"><button class="btn btn-success w-100">Buat Tim</button></div>
            </form>
        </div>
    </div>
</div>

<style>
    .hover-lift { transition: transform 0.2s, box-shadow 0.2s; }
    .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    .cursor-pointer { cursor: pointer; }
</style>