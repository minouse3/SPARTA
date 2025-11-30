<?php
// --- LOGIC PHP ---
$message = "";

// HELPER: Fungsi Hitung Ulang Poin Mahasiswa
function recalculatePoints($pdo, $idMahasiswa) {
    // Ambil semua lomba yang diikuti tim dimana mahasiswa ini menjadi anggota/ketua
    // Rumus: (Poin_Dasar * Bobot_Penyelenggara) * Multiplier_Peringkat
    $sql = "SELECT SUM(t_lomba.Poin_Dasar * t_lomba.Bobot_Poin * t_lomba.Multiplier_Poin) as Total
            FROM (
                SELECT DISTINCT t.ID_Tim, tl.Poin_Dasar, jp.Bobot_Poin, pj.Multiplier_Poin
                FROM Tim t
                JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba
                JOIN Tingkatan_Lomba tl ON l.ID_Tingkatan = tl.ID_Tingkatan
                JOIN Jenis_Penyelenggara jp ON l.ID_Jenis_Penyelenggara = jp.ID_Jenis
                JOIN Peringkat_Juara pj ON t.ID_Peringkat = pj.ID_Peringkat
                LEFT JOIN Keanggotaan_Tim kt ON t.ID_Tim = kt.ID_Tim
                WHERE (t.ID_Mahasiswa_Ketua = ? OR kt.ID_Mahasiswa = ?)
                  AND t.ID_Peringkat IS NOT NULL
            ) as t_lomba";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idMahasiswa, $idMahasiswa]);
    $newTotal = $stmt->fetchColumn() ?: 0;

    // Update Tabel Mahasiswa
    $pdo->prepare("UPDATE Mahasiswa SET Total_Poin = ? WHERE ID_Mahasiswa = ?")
        ->execute([$newTotal, $idMahasiswa]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_POST['action'] === 'add_tim') {
            $stmt = $pdo->prepare("INSERT INTO Tim (Nama_Tim, Status_Pencarian, ID_Lomba, ID_Mahasiswa_Ketua, ID_Dosen_Pembimbing) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['nama_tim'], $_POST['status'], $_POST['lomba'], $_POST['ketua'], !empty($_POST['dosen']) ? $_POST['dosen'] : NULL]);
            $message = "<div class='alert alert-success'>Tim berhasil dibentuk!</div>";
        }
        elseif ($_POST['action'] === 'add_member') {
            $stmt = $pdo->prepare("INSERT INTO Keanggotaan_Tim (ID_Tim, ID_Mahasiswa, Peran) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['id_tim'], $_POST['mahasiswa'], $_POST['peran']]);
            $message = "<div class='alert alert-success'>Anggota berhasil ditambahkan!</div>";
        }
        elseif ($_POST['action'] === 'update_result') {
            // Update Peringkat Tim
            $stmt = $pdo->prepare("UPDATE Tim SET ID_Peringkat = ? WHERE ID_Tim = ?");
            $stmt->execute([$_POST['id_peringkat'], $_POST['id_tim']]);
            
            // Trigger Hitung Ulang Poin untuk Ketua & Semua Anggota
            // 1. Ambil ID Ketua
            $stmtKetua = $pdo->prepare("SELECT ID_Mahasiswa_Ketua FROM Tim WHERE ID_Tim = ?");
            $stmtKetua->execute([$_POST['id_tim']]);
            $ketua = $stmtKetua->fetchColumn();
            recalculatePoints($pdo, $ketua);

            // 2. Ambil ID Anggota
            $stmtAnggota = $pdo->prepare("SELECT ID_Mahasiswa FROM Keanggotaan_Tim WHERE ID_Tim = ?");
            $stmtAnggota->execute([$_POST['id_tim']]);
            while($row = $stmtAnggota->fetch()) {
                recalculatePoints($pdo, $row['ID_Mahasiswa']);
            }

            $message = "<div class='alert alert-success'>Hasil lomba disimpan & Poin anggota diperbarui!</div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$view = isset($_GET['view']) ? $_GET['view'] : 'list';
$detailTim = null;

if ($view == 'detail' && isset($_GET['id'])) {
    // Detail Tim Query
    $stmt = $pdo->prepare("SELECT t.*, l.Nama_Lomba, m.Nama_Mahasiswa as Ketua, d.Nama_Dosen, pj.Nama_Peringkat 
                           FROM Tim t 
                           JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba
                           JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa
                           LEFT JOIN Dosen_Pembimbing d ON t.ID_Dosen_Pembimbing = d.ID_Dosen
                           LEFT JOIN Peringkat_Juara pj ON t.ID_Peringkat = pj.ID_Peringkat
                           WHERE t.ID_Tim = ?");
    $stmt->execute([$_GET['id']]);
    $detailTim = $stmt->fetch();

    $stmtMembers = $pdo->prepare("SELECT k.*, m.Nama_Mahasiswa, m.NIM, p.Nama_Prodi 
                                  FROM Keanggotaan_Tim k
                                  JOIN Mahasiswa m ON k.ID_Mahasiswa = m.ID_Mahasiswa
                                  LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi
                                  WHERE k.ID_Tim = ?");
    $stmtMembers->execute([$_GET['id']]);
    $members = $stmtMembers->fetchAll();
    
    // List Peringkat untuk Dropdown
    $peringkatList = $pdo->query("SELECT * FROM Peringkat_Juara")->fetchAll();
} else {
    $query = "SELECT t.*, l.Nama_Lomba, m.Nama_Mahasiswa as Ketua, pj.Nama_Peringkat 
              FROM Tim t 
              JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba 
              JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa 
              LEFT JOIN Peringkat_Juara pj ON t.ID_Peringkat = pj.ID_Peringkat
              ORDER BY t.ID_Tim DESC";
    $timList = $pdo->query($query)->fetchAll();
}

// Master Data Dropdowns
$mahasiswaList = $pdo->query("SELECT * FROM Mahasiswa ORDER BY Nama_Mahasiswa ASC")->fetchAll();
$dosenList = $pdo->query("SELECT * FROM Dosen_Pembimbing ORDER BY Nama_Dosen ASC")->fetchAll();
$lombaList = $pdo->query("SELECT * FROM Lomba WHERE Tanggal_Selesai >= CURDATE() ORDER BY Nama_Lomba ASC")->fetchAll();
?>

<?= $message ?>

<!-- VIEW DETAIL -->
<?php if ($view == 'detail' && $detailTim): ?>
    <div class="mb-3 d-flex justify-content-between">
        <a href="?page=tim" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <!-- Card Info Tim -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white"><h5 class="mb-0">Info Tim</h5></div>
                <div class="card-body">
                    <h3 class="fw-bold text-primary"><?= htmlspecialchars($detailTim['Nama_Tim']) ?></h3>
                    
                    <!-- Badge Status Pencarian -->
                    <span class="badge bg-<?= $detailTim['Status_Pencarian'] == 'Terbuka' ? 'success' : 'secondary' ?> mb-2">
                        <?= $detailTim['Status_Pencarian'] ?> Recruitment
                    </span>
                    
                    <!-- Badge Hasil Lomba -->
                    <?php if($detailTim['Nama_Peringkat']): ?>
                        <div class="alert alert-warning py-2 mt-2 mb-2">
                            <small class="fw-bold text-uppercase">Hasil Lomba:</small><br>
                            <i class="fas fa-trophy me-2"></i><?= $detailTim['Nama_Peringkat'] ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border py-2 mt-2 mb-2 text-muted small">
                            Hasil lomba belum diupdate.
                        </div>
                    <?php endif; ?>

                    <hr>
                    <p class="mb-1 text-muted small">LOMBA</p>
                    <p class="fw-bold"><?= htmlspecialchars($detailTim['Nama_Lomba']) ?></p>
                    <p class="mb-1 text-muted small">KETUA TIM</p>
                    <p class="fw-bold"><?= htmlspecialchars($detailTim['Ketua']) ?></p>
                </div>
                <div class="card-footer bg-white">
                    <button class="btn btn-warning btn-sm w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#updateResultModal">
                        <i class="fas fa-award me-2"></i>Update Hasil Lomba
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">Anggota Tim</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal"><i class="fas fa-plus"></i></button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light"><tr><th class="ps-4">Nama</th><th>Peran</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach($members as $m): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= $m['Nama_Mahasiswa'] ?></div>
                                    <small><?= $m['NIM'] ?></small>
                                </td>
                                <td><?= $m['Peran'] ?></td>
                                <td><button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Update Result -->
    <div class="modal fade" id="updateResultModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">Update Hasil Lomba</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_result">
                        <input type="hidden" name="id_tim" value="<?= $detailTim['ID_Tim'] ?>">
                        
                        <p class="small text-muted">Pilih pencapaian tim ini. Poin anggota akan dihitung otomatis berdasarkan: (Poin Lomba x Bobot Penyelenggara) x Multiplier Peringkat.</p>
                        
                        <label class="form-label fw-bold">Peringkat / Capaian</label>
                        <select name="id_peringkat" class="form-select" required>
                            <option value="">-- Pilih Hasil --</option>
                            <?php foreach($peringkatList as $p): ?>
                                <option value="<?= $p['ID_Peringkat'] ?>" <?= $detailTim['ID_Peringkat'] == $p['ID_Peringkat'] ? 'selected' : '' ?>>
                                    <?= $p['Nama_Peringkat'] ?> (x<?= $p['Multiplier_Poin'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-warning fw-bold">Simpan & Hitung Poin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Add Member (Same as before) -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white"><h5 class="modal-title">Tambah Anggota</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_member">
                        <input type="hidden" name="id_tim" value="<?= $detailTim['ID_Tim'] ?>">
                        <div class="mb-3">
                            <select name="mahasiswa" class="form-select" required>
                                <?php foreach($mahasiswaList as $m): ?><option value="<?= $m['ID_Mahasiswa'] ?>"><?= $m['Nama_Mahasiswa'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3"><input type="text" name="peran" class="form-control" placeholder="Peran..." required></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- LIST VIEW (Sama seperti sebelumnya, ditambah kolom Peringkat) -->
    <div class="d-flex justify-content-between mb-4">
        <h2 class="fw-bold text-primary">Data Tim</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTeamModal"><i class="fas fa-plus"></i> Tim Baru</button>
    </div>
    
    <div class="row">
        <?php foreach($timList as $t): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-<?= $t['Status_Pencarian']=='Terbuka'?'success':'secondary' ?>"><?= $t['Status_Pencarian'] ?></span>
                        <?php if($t['Nama_Peringkat']): ?>
                            <span class="badge bg-warning text-dark"><i class="fas fa-trophy me-1"></i><?= $t['Nama_Peringkat'] ?></span>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold"><?= $t['Nama_Tim'] ?></h5>
                    <p class="small text-muted mb-2"><?= $t['Nama_Lomba'] ?></p>
                    <a href="?page=tim&view=detail&id=<?= $t['ID_Tim'] ?>" class="btn btn-outline-primary btn-sm w-100">Kelola</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Modal Create Team (Sama seperti sebelumnya) -->
    <div class="modal fade" id="createTeamModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white"><h5 class="modal-title">Bentuk Tim</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_tim">
                        <div class="mb-3"><input type="text" name="nama_tim" class="form-control" placeholder="Nama Tim" required></div>
                        <div class="mb-3"><select name="lomba" class="form-select"><?php foreach($lombaList as $l): ?><option value="<?= $l['ID_Lomba'] ?>"><?= $l['Nama_Lomba'] ?></option><?php endforeach; ?></select></div>
                        <div class="mb-3"><select name="ketua" class="form-select"><?php foreach($mahasiswaList as $m): ?><option value="<?= $m['ID_Mahasiswa'] ?>"><?= $m['Nama_Mahasiswa'] ?></option><?php endforeach; ?></select></div>
                        <div class="mb-3"><select name="status" class="form-select"><option value="Terbuka">Terbuka</option><option value="Tertutup">Tertutup</option></select></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Buat</button></div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>