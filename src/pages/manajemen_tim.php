<?php
// FILE: Manajemen Tim (Updated: Admin View vs Student View)

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$userId = $_SESSION['user_id'];
$roleUser = $_SESSION['role'] ?? 'mahasiswa';
$isAdmin = ($roleUser === 'admin'); // Cek apakah user adalah admin
$message = "";

// 1. CREATE TIM LOGIC (Hanya untuk Mahasiswa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_tim') {
    if ($isAdmin) {
        // Admin tidak boleh membuat tim partisipasi
        $message = "<div class='alert alert-warning border-0 shadow-sm'>Admin tidak dapat membuat tim kompetisi.</div>";
    } else {
        try {
            // Ambil ID Kategori
            $kategoriId = !empty($_POST['kategori_id']) ? $_POST['kategori_id'] : NULL;

            $stmt = $pdo->prepare("INSERT INTO Tim (Nama_Tim, Status_Pencarian, ID_Lomba, ID_Kategori, ID_Mahasiswa_Ketua, Deskripsi_Tim) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['nama'], 'Terbuka', $_POST['lomba'], $kategoriId, $userId, $_POST['deskripsi']]);
            
            $newId = $pdo->lastInsertId();
            echo "<script>window.location='?page=kelola_tim&id=$newId';</script>";
            exit;
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger border-0 shadow-sm'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// 2. FETCH DATA TIM (Logic Berbeda untuk Admin & Mahasiswa)
if ($isAdmin) {
    // ADMIN: Lihat SEMUA Tim (Join dengan Mahasiswa untuk dapat nama ketua)
    $pageTitle = "Data Seluruh Tim";
    $stmtTim = $pdo->query("SELECT t.*, l.Nama_Lomba, m.Nama_Mahasiswa as Ketua,
                            (SELECT COUNT(*) FROM Keanggotaan_Tim WHERE ID_Tim = t.ID_Tim) as Jml_Anggota
                            FROM Tim t 
                            JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba 
                            JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa
                            ORDER BY t.ID_Tim DESC");
    $myTeams = $stmtTim->fetchAll();
} else {
    // MAHASISWA: Lihat Tim Sendiri
    $pageTitle = "Manajemen Tim";
    $stmtTim = $pdo->prepare("SELECT t.*, l.Nama_Lomba, 
                              (SELECT COUNT(*) FROM Keanggotaan_Tim WHERE ID_Tim = t.ID_Tim) as Jml_Anggota
                              FROM Tim t JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba 
                              WHERE t.ID_Mahasiswa_Ketua = ? ORDER BY t.ID_Tim DESC");
    $stmtTim->execute([$userId]);
    $myTeams = $stmtTim->fetchAll();
}

// 3. FETCH LOMBA & CATEGORIES MAPPING (Untuk Modal Create - Hanya Mahasiswa)
$lombaJson = [];
if (!$isAdmin) {
    // Kita ambil semua lomba beserta kategorinya untuk disiapkan di JS
    $lombaData = $pdo->query("SELECT l.ID_Lomba, l.Nama_Lomba, k.ID_Kategori, k.Nama_Kategori 
                              FROM Lomba l 
                              LEFT JOIN Lomba_Kategori lk ON l.ID_Lomba = lk.ID_Lomba
                              LEFT JOIN Kategori_Lomba k ON lk.ID_Kategori = k.ID_Kategori
                              WHERE l.Tanggal_Selesai >= CURDATE()
                              ORDER BY l.Nama_Lomba ASC")->fetchAll();

    // Grouping untuk JS
    foreach ($lombaData as $row) {
        $id = $row['ID_Lomba'];
        if (!isset($lombaJson[$id])) {
            $lombaJson[$id] = [
                'nama' => $row['Nama_Lomba'],
                'kategori' => []
            ];
        }
        if ($row['ID_Kategori']) {
            $lombaJson[$id]['kategori'][] = [
                'id' => $row['ID_Kategori'],
                'nama' => $row['Nama_Kategori']
            ];
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;"><?= $pageTitle ?></h3>
    <?php if (!$isAdmin): ?>
    <button class="btn btn-success rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#createTeamModal">
        <i class="fas fa-plus me-2"></i>Buat Tim Baru
    </button>
    <?php endif; ?>
</div>

<?= $message ?>

<div class="row g-4">
    <?php if(empty($myTeams)): ?>
        <div class="col-12 py-5 text-center text-muted">
            <?= $isAdmin ? "Belum ada data tim di sistem." : "Anda belum memiliki tim." ?>
        </div>
    <?php endif; ?>

    <?php foreach($myTeams as $tim): ?>
    <div class="col-md-6 col-lg-4">
        <a href="?page=kelola_tim&id=<?= $tim['ID_Tim'] ?>" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100 p-4 text-center hover-lift position-relative">
                <?php if($isAdmin): ?>
                    <span class="badge bg-light text-secondary border position-absolute top-0 start-0 m-3" style="font-size: 0.7rem;">
                        <i class="fas fa-user me-1"></i> <?= htmlspecialchars($tim['Ketua']) ?>
                    </span>
                <?php endif; ?>

                <h5 class="fw-bold text-dark mb-1 <?= $isAdmin ? 'mt-3' : '' ?>"><?= htmlspecialchars($tim['Nama_Tim']) ?></h5>
                <small class="text-primary fw-bold text-uppercase"><?= htmlspecialchars($tim['Nama_Lomba']) ?></small>
                <div class="mt-3 text-muted small"><i class="fas fa-users me-1"></i> <?= $tim['Jml_Anggota'] + 1 ?> Anggota</div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!$isAdmin): ?>
<div class="modal fade" id="createTeamModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title fw-bold">Buat Tim Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="create_tim">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Nama Tim</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Nama Keren Tim Anda">
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Pilih Kompetisi</label>
                        <select id="selectLomba" name="lomba" class="form-select" required onchange="updateKategori()">
                            <option value="">-- Pilih Lomba --</option>
                            <?php foreach($lombaJson as $id => $data): ?>
                                <option value="<?= $id ?>"><?= htmlspecialchars($data['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Kategori / Cabang</label>
                        <select id="selectKategori" name="kategori_id" class="form-select" disabled required>
                            <option value="">-- Pilih Lomba Dahulu --</option>
                        </select>
                        <div class="form-text small">Pilih cabang lomba spesifik yang akan diikuti.</div>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold">Deskripsi Tim</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Visi misi atau role yang dicari..."></textarea>
                    </div>

                    <button class="btn btn-success w-100 fw-bold rounded-pill">Buat Tim</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const lombaData = <?= json_encode($lombaJson) ?>;

    function updateKategori() {
        const lombaId = document.getElementById('selectLomba').value;
        const katSelect = document.getElementById('selectKategori');
        
        katSelect.innerHTML = '<option value="">-- Pilih Kategori --</option>';
        katSelect.disabled = true;

        if (lombaId && lombaData[lombaId]) {
            const kats = lombaData[lombaId].kategori;
            if (kats.length > 0) {
                katSelect.disabled = false;
                kats.forEach(k => {
                    const opt = document.createElement('option');
                    opt.value = k.id;
                    opt.text = k.nama;
                    katSelect.add(opt);
                });
            } else {
                const opt = document.createElement('option');
                opt.text = "Umum (Tidak ada kategori khusus)";
                opt.value = ""; // NULL
                katSelect.add(opt);
                katSelect.disabled = false; // Tetap enable agar bisa submit
            }
        }
    }
</script>
<?php endif; ?>

<style>
    .hover-lift { transition: transform 0.2s; }
    .hover-lift:hover { transform: translateY(-5px); }
</style>