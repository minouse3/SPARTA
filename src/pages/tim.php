<?php
// --- LOGIC PHP ---
$message = "";

// Handle Actions (Add Tim & Add Member)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // TAMBAH TIM BARU
        if ($_POST['action'] === 'add_tim') {
            $sql = "INSERT INTO Tim (Nama_Tim, Status_Pencarian, ID_Lomba, ID_Mahasiswa_Ketua, ID_Dosen_Pembimbing) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['nama_tim'], 
                $_POST['status'], 
                $_POST['lomba'], 
                $_POST['ketua'], 
                !empty($_POST['dosen']) ? $_POST['dosen'] : NULL
            ]);
            $message = "<div class='alert alert-success alert-dismissible fade show'>Tim berhasil dibentuk!</div>";
        }
        // TAMBAH ANGGOTA (Detail View)
        elseif ($_POST['action'] === 'add_member') {
            $sql = "INSERT INTO Keanggotaan_Tim (ID_Tim, ID_Mahasiswa, Peran, Status) VALUES (?, ?, ?, 'Diterima')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['id_tim'], $_POST['mahasiswa'], $_POST['peran']]);
            $message = "<div class='alert alert-success alert-dismissible fade show'>Anggota berhasil ditambahkan!</div>";
        }
        // HAPUS ANGGOTA
        elseif ($_POST['action'] === 'delete_member') {
            $stmt = $pdo->prepare("DELETE FROM Keanggotaan_Tim WHERE ID_Keanggotaan = ?");
            $stmt->execute([$_POST['id_keanggotaan']]);
            $message = "<div class='alert alert-danger alert-dismissible fade show'>Anggota dihapus dari tim.</div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// --- LOGIC VIEW SWITCHER ---
$view = isset($_GET['view']) ? $_GET['view'] : 'list';
$detailTim = null;

if ($view == 'detail' && isset($_GET['id'])) {
    // Ambil Data Detail Tim
    $stmt = $pdo->prepare("SELECT t.*, l.Nama_Lomba, m.Nama_Mahasiswa as Ketua, d.Nama_Dosen 
                           FROM Tim t 
                           JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba
                           JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa
                           LEFT JOIN Dosen_Pembimbing d ON t.ID_Dosen_Pembimbing = d.ID_Dosen
                           WHERE t.ID_Tim = ?");
    $stmt->execute([$_GET['id']]);
    $detailTim = $stmt->fetch();

    // Ambil Anggota Tim
    $stmtMembers = $pdo->prepare("SELECT k.*, m.Nama_Mahasiswa, m.NIM, p.Nama_Prodi 
                                  FROM Keanggotaan_Tim k
                                  JOIN Mahasiswa m ON k.ID_Mahasiswa = m.ID_Mahasiswa
                                  LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi
                                  WHERE k.ID_Tim = ?");
    $stmtMembers->execute([$_GET['id']]);
    $members = $stmtMembers->fetchAll();
} else {
    // Ambil List Semua Tim
    $query = "SELECT t.*, l.Nama_Lomba, m.Nama_Mahasiswa as Ketua 
              FROM Tim t 
              JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba 
              JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa 
              ORDER BY t.ID_Tim DESC";
    $timList = $pdo->query($query)->fetchAll();
}

// Data Master untuk Dropdown
$mahasiswaList = $pdo->query("SELECT * FROM Mahasiswa ORDER BY Nama_Mahasiswa ASC")->fetchAll();
$dosenList = $pdo->query("SELECT * FROM Dosen_Pembimbing ORDER BY Nama_Dosen ASC")->fetchAll();
$lombaList = $pdo->query("SELECT * FROM Lomba WHERE Tanggal_Selesai >= CURDATE() ORDER BY Nama_Lomba ASC")->fetchAll();
?>

<?= $message ?>

<!-- ================= TAMPILAN DETAIL TIM ================= -->
<?php if ($view == 'detail' && $detailTim): ?>
    <div class="mb-3">
        <a href="?page=tim" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Tim</a>
    </div>

    <div class="row">
        <!-- Informasi Tim -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Info Tim</h5>
                </div>
                <div class="card-body">
                    <h3 class="fw-bold text-primary"><?= htmlspecialchars($detailTim['Nama_Tim']) ?></h3>
                    <span class="badge bg-<?= $detailTim['Status_Pencarian'] == 'Terbuka' ? 'success' : 'secondary' ?> mb-3">
                        Status: <?= $detailTim['Status_Pencarian'] ?>
                    </span>
                    
                    <p class="mb-1 text-muted small">LOMBA</p>
                    <p class="fw-bold"><?= htmlspecialchars($detailTim['Nama_Lomba']) ?></p>
                    
                    <p class="mb-1 text-muted small">KETUA TIM</p>
                    <p class="fw-bold"><?= htmlspecialchars($detailTim['Ketua']) ?></p>

                    <p class="mb-1 text-muted small">PEMBIMBING</p>
                    <p class="fw-bold"><?= htmlspecialchars($detailTim['Nama_Dosen'] ?? 'Belum ada') ?></p>
                </div>
            </div>
        </div>

        <!-- Daftar Anggota -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="fas fa-users me-2"></i>Anggota Tim</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                        <i class="fas fa-user-plus me-1"></i> Tambah
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Nama Mahasiswa</th>
                                <th>Prodi</th>
                                <th>Peran</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($members)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada anggota tambahan.</td></tr>
                            <?php endif; ?>

                            <?php foreach($members as $member): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= htmlspecialchars($member['Nama_Mahasiswa']) ?></div>
                                    <small class="text-muted"><?= $member['NIM'] ?></small>
                                </td>
                                <td><span class="badge bg-info text-dark bg-opacity-10 border border-info"><?= $member['Nama_Prodi'] ?></span></td>
                                <td><span class="badge bg-secondary"><?= $member['Peran'] ?></span></td>
                                <td class="text-end pe-4">
                                    <form method="POST" onsubmit="return confirm('Hapus anggota ini?');">
                                        <input type="hidden" name="action" value="delete_member">
                                        <input type="hidden" name="id_keanggotaan" value="<?= $member['ID_Keanggotaan'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Anggota -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Tambah Anggota Tim</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_member">
                        <input type="hidden" name="id_tim" value="<?= $detailTim['ID_Tim'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Pilih Mahasiswa</label>
                            <select name="mahasiswa" class="form-select" required>
                                <option value="">-- Cari Mahasiswa --</option>
                                <?php foreach($mahasiswaList as $m): ?>
                                    <option value="<?= $m['ID_Mahasiswa'] ?>"><?= $m['Nama_Mahasiswa'] ?> (<?= $m['NIM'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Peran (Role)</label>
                            <input type="text" name="peran" class="form-control" placeholder="Contoh: UI Designer, Data Analyst" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<!-- ================= TAMPILAN LIST TIM UTAMA ================= -->
<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary"><i class="fas fa-users me-2"></i> Data Tim Kompetisi</h2>
            <p class="text-muted">Kelola tim yang terbentuk untuk lomba.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createTeamModal">
            <i class="fas fa-plus me-2"></i> Bentuk Tim Baru
        </button>
    </div>

    <div class="row">
        <?php foreach($timList as $tim): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 shadow-sm border-0 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-<?= $tim['Status_Pencarian'] == 'Terbuka' ? 'success' : 'secondary' ?>">
                            <?= $tim['Status_Pencarian'] ?>
                        </span>
                        <small class="text-muted"><i class="fas fa-hashtag me-1"></i><?= $tim['ID_Tim'] ?></small>
                    </div>
                    <h4 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($tim['Nama_Tim']) ?></h4>
                    <p class="text-primary small mb-3"><i class="fas fa-trophy me-1"></i> <?= htmlspecialchars($tim['Nama_Lomba']) ?></p>
                    
                    <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                        <div class="me-2 text-center" style="width: 40px;">
                            <i class="fas fa-user-circle fa-2x text-secondary"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">KETUA TIM</small>
                            <span class="fw-bold"><?= htmlspecialchars($tim['Ketua']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 d-grid pb-3">
                    <a href="?page=tim&view=detail&id=<?= $tim['ID_Tim'] ?>" class="btn btn-outline-primary">
                        <i class="fas fa-cog me-2"></i> Kelola Anggota
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal Buat Tim -->
    <div class="modal fade" id="createTeamModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Bentuk Tim Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_tim">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Tim</label>
                            <input type="text" name="nama_tim" class="form-control" placeholder="Contoh: Garuda Cyber Team" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ikut Lomba</label>
                            <select name="lomba" class="form-select" required>
                                <option value="">-- Pilih Lomba --</option>
                                <?php foreach($lombaList as $l): ?>
                                    <option value="<?= $l['ID_Lomba'] ?>"><?= $l['Nama_Lomba'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ketua Tim (Mahasiswa)</label>
                            <select name="ketua" class="form-select" required>
                                <option value="">-- Pilih Ketua --</option>
                                <?php foreach($mahasiswaList as $m): ?>
                                    <option value="<?= $m['ID_Mahasiswa'] ?>"><?= $m['Nama_Mahasiswa'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dosen Pembimbing (Opsional)</label>
                            <select name="dosen" class="form-select">
                                <option value="">-- Belum Ada --</option>
                                <?php foreach($dosenList as $d): ?>
                                    <option value="<?= $d['ID_Dosen'] ?>"><?= $d['Nama_Dosen'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status Pencarian Anggota</label>
                            <select name="status" class="form-select">
                                <option value="Terbuka">Terbuka (Open Recruitment)</option>
                                <option value="Tertutup">Tertutup (Full Team)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Buat Tim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>