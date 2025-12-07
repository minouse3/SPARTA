<?php
// FILE: src/pages/lomba.php (Full Update: Icon, Split Penyelenggara, Multi-Role Access)

// Cek Login
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>"; exit;
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? ''; // 'mahasiswa', 'dosen', 'admin'
$level = $_SESSION['level'] ?? ''; // 'admin', 'superadmin'

// Cek Hak Akses Mengelola Lomba (Admin, Superadmin, Dosen)
$canManage = ($role === 'dosen' || $role === 'admin'); 

// --- 1. LOGIKA CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    try {
        $action = $_POST['action'];

        if ($action === 'save_lomba') {
            $nama = $_POST['nama_lomba'];
            $deskripsi = $_POST['deskripsi'];
            $lokasi = $_POST['lokasi'];
            $link = $_POST['link_web'];
            $tglMulai = $_POST['tgl_mulai'];
            $tglSelesai = $_POST['tgl_selesai'];
            $idJenis = $_POST['id_jenis'];
            $namaPenyelenggara = $_POST['nama_penyelenggara']; // NEW
            $idTingkatan = $_POST['id_tingkatan'];
            $kategoris = isset($_POST['kategori']) ? $_POST['kategori'] : []; // Array
            $idLomba = $_POST['id_lomba'] ?? null;

            // Handle Upload Icon
            $iconPath = null;
            if (!empty($_FILES['icon']['name'])) {
                $targetDir = "uploads/lomba/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                
                $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
                $fileName = time() . "_" . rand(100,999) . "." . $ext;
                $targetFile = $targetDir . $fileName;
                
                if (move_uploaded_file($_FILES['icon']['tmp_name'], $targetFile)) {
                    $iconPath = $targetFile;
                }
            }

            if ($idLomba) {
                // UPDATE
                $sql = "UPDATE Lomba SET Nama_Lomba=?, Deskripsi=?, Lokasi=?, Link_Web=?, Tanggal_Mulai=?, Tanggal_Selesai=?, ID_Jenis_Penyelenggara=?, Nama_Penyelenggara=?, ID_Tingkatan=?";
                $params = [$nama, $deskripsi, $lokasi, $link, $tglMulai, $tglSelesai, $idJenis, $namaPenyelenggara, $idTingkatan];
                
                if ($iconPath) {
                    $sql .= ", Foto_Lomba=?";
                    $params[] = $iconPath;
                }
                $sql .= " WHERE ID_Lomba=?";
                $params[] = $idLomba;
                
                $pdo->prepare($sql)->execute($params);
                
                // Update Kategori (Reset & Insert)
                $pdo->prepare("DELETE FROM Lomba_Kategori WHERE ID_Lomba = ?")->execute([$idLomba]);
                $msg = "Data lomba berhasil diperbarui!";
            } else {
                // INSERT
                $sql = "INSERT INTO Lomba (Nama_Lomba, Deskripsi, Foto_Lomba, Lokasi, Link_Web, Tanggal_Mulai, Tanggal_Selesai, ID_Jenis_Penyelenggara, Nama_Penyelenggara, ID_Tingkatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$nama, $deskripsi, $iconPath, $lokasi, $link, $tglMulai, $tglSelesai, $idJenis, $namaPenyelenggara, $idTingkatan]);
                $idLomba = $pdo->lastInsertId();
                $msg = "Lomba baru berhasil ditambahkan!";
            }

            // Insert Kategori
            if (!empty($kategoris)) {
                $stmtKat = $pdo->prepare("INSERT INTO Lomba_Kategori (ID_Lomba, ID_Kategori) VALUES (?, ?)");
                foreach ($kategoris as $idK) {
                    $stmtKat->execute([$idLomba, $idK]);
                }
            }

            echo "<script>alert('$msg'); window.location='?page=lomba';</script>";

        } elseif ($action === 'delete_lomba') {
            $pdo->prepare("DELETE FROM Lomba WHERE ID_Lomba = ?")->execute([$_POST['id_lomba']]);
            echo "<script>alert('Lomba berhasil dihapus.'); window.location='?page=lomba';</script>";
        }

    } catch (Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

// --- 2. AMBIL DATA FILTER & LIST ---
$keyword = $_GET['q'] ?? '';
$sql = "SELECT l.*, j.Nama_Jenis, t.Nama_Tingkatan 
        FROM Lomba l
        LEFT JOIN Jenis_Penyelenggara j ON l.ID_Jenis_Penyelenggara = j.ID_Jenis
        LEFT JOIN Tingkatan_Lomba t ON l.ID_Tingkatan = t.ID_Tingkatan
        WHERE 1=1";
if ($keyword) $sql .= " AND l.Nama_Lomba LIKE '%$keyword%'";
$sql .= " ORDER BY l.Tanggal_Selesai DESC";
$lombaList = $pdo->query($sql)->fetchAll();

// Data Master untuk Form
$jenisList = $pdo->query("SELECT * FROM Jenis_Penyelenggara")->fetchAll();
$tingkatanList = $pdo->query("SELECT * FROM Tingkatan_Lomba")->fetchAll();
$kategoriList = $pdo->query("SELECT * FROM Kategori_Lomba")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Informasi Lomba</h3>
        <p class="text-muted mb-0">Daftar kompetisi akademik dan non-akademik terbaru.</p>
    </div>
    <?php if ($canManage): ?>
    <button class="btn btn-primary rounded-pill fw-bold shadow-sm px-4" onclick="openModal()">
        <i class="fas fa-plus-circle me-2"></i>Tambah Lomba
    </button>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-2">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="page" value="lomba">
            <input type="text" name="q" class="form-control border-0" placeholder="Cari nama lomba..." value="<?= htmlspecialchars($keyword) ?>">
            <button class="btn btn-primary px-4 rounded-pill">Cari</button>
        </form>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($lombaList as $l): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-card">
            <div class="position-relative">
                <?php if ($l['Foto_Lomba']): ?>
                    <div style="height: 160px; background: url('<?= $l['Foto_Lomba'] ?>') center/cover no-repeat;"></div>
                <?php else: ?>
                    <div style="height: 160px;" class="bg-gradient bg-primary d-flex align-items-center justify-content-center text-white">
                        <i class="fas fa-trophy fa-4x opacity-50"></i>
                    </div>
                <?php endif; ?>
                
                <span class="badge bg-white text-dark position-absolute top-0 end-0 m-3 shadow-sm">
                    <?= htmlspecialchars($l['Nama_Tingkatan']) ?>
                </span>
            </div>
            
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-info bg-opacity-10 text-info border border-info me-2">
                        <?= htmlspecialchars($l['Nama_Jenis']) ?>
                    </span>
                    <small class="text-muted text-truncate" style="max-width: 150px;">
                        <i class="far fa-building me-1"></i><?= htmlspecialchars($l['Nama_Penyelenggara']) ?>
                    </small>
                </div>

                <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($l['Nama_Lomba']) ?></h5>
                
                <p class="text-muted small mb-3 text-truncate-3">
                    <?= substr(strip_tags($l['Deskripsi']), 0, 100) ?>...
                </p>

                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <small class="text-danger fw-bold">
                        <i class="far fa-clock me-1"></i> DL: <?= date('d M Y', strtotime($l['Tanggal_Selesai'])) ?>
                    </small>
                    <div>
                        <?php if ($canManage): ?>
                        <button class="btn btn-light btn-sm text-primary rounded-circle shadow-sm me-1" 
                                onclick='editLomba(<?= json_encode($l) ?>)'>
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus lomba ini?');">
                            <input type="hidden" name="action" value="delete_lomba">
                            <input type="hidden" name="id_lomba" value="<?= $l['ID_Lomba'] ?>">
                            <button class="btn btn-light btn-sm text-danger rounded-circle shadow-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <a href="<?= $l['Link_Web'] ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill ms-1">Info <i class="fas fa-external-link-alt ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($canManage): ?>
<div class="modal fade" id="lombaModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4" enctype="multipart/form-data">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Kompetisi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="save_lomba">
                <input type="hidden" name="id_lomba" id="idLomba">

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Nama Kompetisi</label>
                        <input type="text" name="nama_lomba" id="namaLomba" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Jenis Penyelenggara</label>
                        <select name="id_jenis" id="idJenis" class="form-select" required>
                            <?php foreach($jenisList as $j): ?>
                                <option value="<?= $j['ID_Jenis'] ?>"><?= $j['Nama_Jenis'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nama Penyelenggara (Instansi)</label>
                        <input type="text" name="nama_penyelenggara" id="namaPenyelenggara" class="form-control" placeholder="Contoh: Universitas Gadjah Mada" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tingkatan</label>
                        <select name="id_tingkatan" id="idTingkatan" class="form-select" required>
                            <?php foreach($tingkatanList as $t): ?>
                                <option value="<?= $t['ID_Tingkatan'] ?>"><?= $t['Nama_Tingkatan'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Icon / Poster Lomba</label>
                        <input type="file" name="icon" class="form-control" accept="image/*">
                        <div class="form-text small">Kosongkan jika tidak ingin mengubah gambar.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tanggal Mulai</label>
                        <input type="date" name="tgl_mulai" id="tglMulai" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tanggal Selesai (Deadline)</label>
                        <input type="date" name="tgl_selesai" id="tglSelesai" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Lokasi</label>
                        <input type="text" name="lokasi" id="lokasi" class="form-control" placeholder="Daring / Nama Kota">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Link Website</label>
                        <input type="url" name="link_web" id="linkWeb" class="form-control" placeholder="https://...">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Kategori Lomba (Bisa pilih > 1)</label>
                        <select name="kategori[]" id="kategori" class="form-select" multiple size="4">
                            <?php foreach($kategoriList as $k): ?>
                                <option value="<?= $k['ID_Kategori'] ?>"><?= $k['Nama_Kategori'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">Tahan tombol CTRL (Windows) atau CMD (Mac) untuk memilih banyak.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Deskripsi Lengkap</label>
                        <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4">Simpan Lomba</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalTitle').innerText = 'Tambah Kompetisi';
    document.getElementById('idLomba').value = '';
    document.querySelector('form').reset();
    new bootstrap.Modal(document.getElementById('lombaModal')).show();
}

function editLomba(data) {
    document.getElementById('modalTitle').innerText = 'Edit Kompetisi';
    document.getElementById('idLomba').value = data.ID_Lomba;
    document.getElementById('namaLomba').value = data.Nama_Lomba;
    document.getElementById('idJenis').value = data.ID_Jenis_Penyelenggara;
    document.getElementById('namaPenyelenggara').value = data.Nama_Penyelenggara; // Populate
    document.getElementById('idTingkatan').value = data.ID_Tingkatan;
    document.getElementById('tglMulai').value = data.Tanggal_Mulai;
    document.getElementById('tglSelesai').value = data.Tanggal_Selesai;
    document.getElementById('lokasi').value = data.Lokasi;
    document.getElementById('linkWeb').value = data.Link_Web;
    document.getElementById('deskripsi').value = data.Deskripsi;
    
    new bootstrap.Modal(document.getElementById('lombaModal')).show();
}
</script>
<?php endif; ?>