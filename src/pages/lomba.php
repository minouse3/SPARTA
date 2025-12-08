<?php
// FILE: src/pages/lomba.php (Updated: Tabs, Date Range, Smart Tags, SweetAlert2)

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>"; exit;
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? ''; 
$canManage = ($role === 'dosen' || $role === 'admin'); 

// --- 1. LOGIKA CRUD (SAVE & DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    try {
        $action = $_POST['action'];

        if ($action === 'save_lomba') {
            // Tangkap Data
            $nama = $_POST['nama_lomba'];
            $deskripsi = $_POST['deskripsi'];
            $lokasi = $_POST['lokasi'];
            $link = $_POST['link_web'];
            $tglMulai = $_POST['tgl_mulai'];
            $tglSelesai = $_POST['tgl_selesai'];
            $idJenis = $_POST['id_jenis'];
            $namaPenyelenggara = $_POST['nama_penyelenggara'];
            $idTingkatan = $_POST['id_tingkatan'];
            $idLomba = $_POST['id_lomba'] ?? null;

            // Handle Kategori (Tag System)
            $kategoriString = $_POST['kategori_tags'] ?? '';
            $kategoriArray = array_filter(array_map('trim', explode(',', $kategoriString)));
            $finalKategoriIds = [];

            if (!empty($kategoriArray)) {
                $stmtCheckKat = $pdo->prepare("SELECT ID_Kategori FROM Kategori_Lomba WHERE Nama_Kategori LIKE ?");
                $stmtInsertKat = $pdo->prepare("INSERT INTO Kategori_Lomba (Nama_Kategori) VALUES (?)");

                foreach ($kategoriArray as $katName) {
                    $stmtCheckKat->execute([$katName]);
                    $existingId = $stmtCheckKat->fetchColumn();
                    if ($existingId) {
                        $finalKategoriIds[] = $existingId;
                    } else {
                        $stmtInsertKat->execute([ucwords($katName)]);
                        $finalKategoriIds[] = $pdo->lastInsertId();
                    }
                }
            }

            // Handle Icon
            $iconPath = null;
            if (!empty($_FILES['icon']['name'])) {
                $maxSize = 1 * 1024 * 1024; // 1 MB
                if ($_FILES['icon']['size'] > $maxSize) { throw new Exception("Ukuran file max 1MB."); }

                $targetDir = "uploads/kompetisi/"; 
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                
                $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) { throw new Exception("Format harus JPG/PNG/WEBP."); }

                $fileName = time() . "_" . rand(100,999) . "." . $ext;
                $targetFile = $targetDir . $fileName;
                
                if (move_uploaded_file($_FILES['icon']['tmp_name'], $targetFile)) {
                    $iconPath = $targetFile;
                }
            }

            // Query Database
            if ($idLomba) {
                $sql = "UPDATE Lomba SET Nama_Lomba=?, Deskripsi=?, Lokasi=?, Link_Web=?, Tanggal_Mulai=?, Tanggal_Selesai=?, ID_Jenis_Penyelenggara=?, Nama_Penyelenggara=?, ID_Tingkatan=?";
                $params = [$nama, $deskripsi, $lokasi, $link, $tglMulai, $tglSelesai, $idJenis, $namaPenyelenggara, $idTingkatan];
                if ($iconPath) { $sql .= ", Foto_Lomba=?"; $params[] = $iconPath; }
                $sql .= " WHERE ID_Lomba=?"; $params[] = $idLomba;
                $pdo->prepare($sql)->execute($params);
                $pdo->prepare("DELETE FROM Lomba_Kategori WHERE ID_Lomba = ?")->execute([$idLomba]);
                $msg = "Kompetisi berhasil diperbarui!";
            } else {
                $sql = "INSERT INTO Lomba (Nama_Lomba, Deskripsi, Foto_Lomba, Lokasi, Link_Web, Tanggal_Mulai, Tanggal_Selesai, ID_Jenis_Penyelenggara, Nama_Penyelenggara, ID_Tingkatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$nama, $deskripsi, $iconPath, $lokasi, $link, $tglMulai, $tglSelesai, $idJenis, $namaPenyelenggara, $idTingkatan]);
                $idLomba = $pdo->lastInsertId();
                $msg = "Kompetisi baru berhasil ditambahkan!";
            }

            if (!empty($finalKategoriIds)) {
                $stmtRel = $pdo->prepare("INSERT INTO Lomba_Kategori (ID_Lomba, ID_Kategori) VALUES (?, ?)");
                foreach ($finalKategoriIds as $kId) { $stmtRel->execute([$idLomba, $kId]); }
            }

            echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Berhasil!', text: '$msg', confirmButtonColor: '#0d6efd' }).then(() => { window.location='?page=lomba'; }); });</script>";

        } elseif ($action === 'delete_lomba') {
            $stmtGetImg = $pdo->prepare("SELECT Foto_Lomba FROM Lomba WHERE ID_Lomba = ?");
            $stmtGetImg->execute([$_POST['id_lomba']]);
            $img = $stmtGetImg->fetchColumn();
            if ($img && file_exists($img)) unlink($img);

            $pdo->prepare("DELETE FROM Lomba WHERE ID_Lomba = ?")->execute([$_POST['id_lomba']]);
            echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Dihapus!', text: 'Data lomba dihapus.', confirmButtonColor: '#0d6efd' }).then(() => { window.location='?page=lomba'; }); });</script>";
        }
    } catch (Exception $e) {
        $errMsg = $e->getMessage();
        echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'error', title: 'Oops...', text: '$errMsg', confirmButtonColor: '#0d6efd' }); });</script>";
    }
}

// --- 2. LOGIKA TAB & PENCARIAN ---
$keyword = $_GET['q'] ?? '';
$activeTab = $_GET['tab'] ?? 'running'; // Default: Now Running

// Base Query
$sql = "SELECT l.*, j.Nama_Jenis, t.Nama_Tingkatan,
        (SELECT COUNT(*) FROM Tim WHERE ID_Lomba = l.ID_Lomba) as Jml_Tim,
        GROUP_CONCAT(k.Nama_Kategori SEPARATOR ',') as Kategori_List
        FROM Lomba l
        LEFT JOIN Jenis_Penyelenggara j ON l.ID_Jenis_Penyelenggara = j.ID_Jenis
        LEFT JOIN Tingkatan_Lomba t ON l.ID_Tingkatan = t.ID_Tingkatan
        LEFT JOIN Lomba_Kategori lk ON l.ID_Lomba = lk.ID_Lomba
        LEFT JOIN Kategori_Lomba k ON lk.ID_Kategori = k.ID_Kategori
        WHERE 1=1";

// Filter Pencarian
if ($keyword) {
    $sql .= " AND (l.Nama_Lomba LIKE '%$keyword%' OR l.Nama_Penyelenggara LIKE '%$keyword%')";
}

// Filter Tab
if ($activeTab === 'upcoming') {
    $sql .= " AND l.Tanggal_Mulai > CURDATE()";
} elseif ($activeTab === 'archive') {
    $sql .= " AND l.Tanggal_Selesai < CURDATE()";
} else {
    // Default: Running (Sedang berlangsung)
    $sql .= " AND CURDATE() BETWEEN l.Tanggal_Mulai AND l.Tanggal_Selesai";
}

$sql .= " GROUP BY l.ID_Lomba ORDER BY l.Tanggal_Selesai ASC";
$lombaList = $pdo->query($sql)->fetchAll();

// Data Master
$jenisList = $pdo->query("SELECT * FROM Jenis_Penyelenggara")->fetchAll();
$tingkatanList = $pdo->query("SELECT * FROM Tingkatan_Lomba")->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .cursor-pointer { cursor: pointer; }
    .hover-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1)!important; }
    .detail-teams-scroll { max-height: 300px; overflow-y: auto; padding-right: 5px; }
    .truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    
    /* Nav Pills Custom */
    .tab-lomba-custom .nav-link { 
        color: #6c757d; 
        font-weight: 600; 
        padding: 0.5rem 1.2rem; 
        border-radius: 50px; 
        transition: all 0.3s; 
    }
    .tab-lomba-custom .nav-link.active { 
        background-color: #0d6efd; 
        color: white; 
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); 
    }
    .tab-lomba-custom .nav-link:hover:not(.active) { 
        background-color: #e9ecef; 
    }
    
    /* Tags on Card */
    .card-tag { font-size: 0.65rem; padding: 4px 8px; border-radius: 4px; background: #f8f9fa; color: #495057; border: 1px solid #dee2e6; display: inline-block; margin-right: 4px; }
    .card-tag-more { font-size: 0.65rem; padding: 4px 6px; border-radius: 4px; background: #e9ecef; color: #6c757d; font-weight: bold; display: inline-block; }

    /* Tag Input Styles */
    .tag-container { min-height: 48px; padding: 6px; border: 1px solid #ced4da; border-radius: 0.375rem; background-color: #fff; display: flex; flex-wrap: wrap; gap: 6px; }
    .tag-container:focus-within { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
    .tag-item { background: #e9ecef; color: #495057; padding: 4px 10px; border-radius: 50px; font-size: 0.85rem; display: flex; align-items: center; }
    .tag-item i { margin-left: 8px; cursor: pointer; opacity: 0.6; }
    .tag-item i:hover { opacity: 1; color: #dc3545; }
    .tag-input { border: none; outline: none; flex-grow: 1; min-width: 120px; font-size: 0.9rem; }
    .suggestion-box { position: absolute; width: 100%; z-index: 1050; background: white; border: 1px solid #dee2e6; border-radius: 0 0 8px 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto; }
    .hover-bg-light:hover { background-color: #f8f9fa; cursor: pointer; }
    .img-preview-box { width: 100%; height: 150px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-top: 10px; }
    .img-preview-box img { width: 100%; height: 100%; object-fit: cover; }
</style>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Kompetisi</h3>
        <p class="text-muted mb-0">Temukan panggung untuk bersinar.</p>
    </div>
    
    <div class="d-flex gap-2 align-items-center">
        <ul class="nav nav-pills tab-lomba-custom bg-white p-1 rounded-pill border shadow-sm">
            <li class="nav-item">
                <a class="nav-link <?= $activeTab=='running'?'active':'' ?>" href="?page=lomba&tab=running">Now Running</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab=='upcoming'?'active':'' ?>" href="?page=lomba&tab=upcoming">Upcoming</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab=='archive'?'active':'' ?>" href="?page=lomba&tab=archive">Archive</a>
            </li>
        </ul>

        <?php if ($canManage): ?>
        <button class="btn btn-primary rounded-circle shadow-sm p-2 ms-2" onclick="openModal()" title="Tambah Kompetisi" style="width: 42px; height: 42px;">
            <i class="fas fa-plus"></i>
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-2">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="page" value="lomba">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
            <input type="text" name="q" class="form-control border-0 shadow-none" placeholder="Cari nama kompetisi atau penyelenggara..." value="<?= htmlspecialchars($keyword) ?>">
            <button class="btn btn-light border text-secondary px-3 rounded-pill"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($lombaList)): ?>
        <div class="col-12 text-center py-5">
            <div class="text-muted opacity-50 mb-2"><i class="fas fa-box-open fa-3x"></i></div>
            <h6 class="text-muted">Tidak ada kompetisi di kategori ini.</h6>
        </div>
    <?php endif; ?>

    <?php foreach ($lombaList as $l): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-card cursor-pointer" 
             onclick="viewDetail(<?= htmlspecialchars(json_encode($l), ENT_QUOTES, 'UTF-8') ?>)">
            
            <div class="position-relative">
                <?php if (!empty($l['Foto_Lomba']) && file_exists($l['Foto_Lomba'])): ?>
                    <div style="height: 160px; background: url('<?= $l['Foto_Lomba'] ?>') center/cover no-repeat;"></div>
                <?php else: ?>
                    <div style="height: 160px;" class="bg-gradient bg-primary d-flex align-items-center justify-content-center text-white">
                        <i class="fas fa-trophy fa-4x opacity-50"></i>
                    </div>
                <?php endif; ?>
                
                <span class="badge bg-white text-dark position-absolute top-0 end-0 m-3 shadow-sm border">
                    <?= htmlspecialchars($l['Nama_Tingkatan'] ?? '-') ?>
                </span>
            </div>
            
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center mb-2">
                    <small class="text-muted text-truncate w-100">
                        <i class="far fa-building me-1"></i> <?= htmlspecialchars($l['Nama_Penyelenggara'] ?? '-') ?>
                    </small>
                </div>

                <h5 class="fw-bold text-dark mb-2 text-truncate" title="<?= htmlspecialchars($l['Nama_Lomba'] ?? '') ?>">
                    <?= htmlspecialchars($l['Nama_Lomba'] ?? 'Tanpa Nama') ?>
                </h5>
                
                <div class="mb-3" style="min-height: 26px;">
                    <?php 
                    if (!empty($l['Kategori_List'])) {
                        $tags = explode(',', $l['Kategori_List']);
                        $limit = 2; 
                        $count = count($tags);
                        
                        foreach (array_slice($tags, 0, $limit) as $tag) {
                            echo '<span class="card-tag">' . htmlspecialchars(trim($tag)) . '</span>';
                        }
                        if ($count > $limit) {
                            echo '<span class="card-tag-more">+' . ($count - $limit) . '</span>';
                        }
                    } else {
                        echo '<small class="text-muted fst-italic" style="font-size: 0.75rem;">Umum</small>';
                    }
                    ?>
                </div>

                <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-auto">
                    <div>
                        <small class="text-secondary d-block" style="font-size: 0.75rem; font-weight: 500;">
                            <i class="far fa-calendar-alt me-1 text-primary"></i> 
                            <?= date('d M', strtotime($l['Tanggal_Mulai'])) ?> - <?= date('d M Y', strtotime($l['Tanggal_Selesai'])) ?>
                        </small>
                    </div>
                    
                    <div>
                        <?php if ($canManage): ?>
                        <button class="btn btn-light btn-sm text-warning rounded-circle shadow-sm me-1" 
                                onclick="event.stopPropagation(); editLomba(<?= htmlspecialchars(json_encode($l), ENT_QUOTES, 'UTF-8') ?>)" 
                                title="Edit">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="event.stopPropagation(); confirmDelete('<?= $l['ID_Lomba'] ?>'); return false;" id="delForm<?= $l['ID_Lomba'] ?>">
                            <input type="hidden" name="action" value="delete_lomba">
                            <input type="hidden" name="id_lomba" value="<?= $l['ID_Lomba'] ?>">
                            <button class="btn btn-light btn-sm text-danger rounded-circle shadow-sm" 
                                    onclick="event.stopPropagation(); confirmDelete('<?= $l['ID_Lomba'] ?>')" type="button" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
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
                    <div class="col-md-7">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Kompetisi</label>
                            <input type="text" name="nama_lomba" id="namaLomba" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Jenis</label>
                                <select name="id_jenis" id="idJenis" class="form-select" required>
                                    <?php foreach($jenisList as $j): ?>
                                        <option value="<?= $j['ID_Jenis'] ?>"><?= $j['Nama_Jenis'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Tingkatan</label>
                                <select name="id_tingkatan" id="idTingkatan" class="form-select" required>
                                    <?php foreach($tingkatanList as $t): ?>
                                        <option value="<?= $t['ID_Tingkatan'] ?>"><?= $t['Nama_Tingkatan'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Penyelenggara</label>
                            <input type="text" name="nama_penyelenggara" id="namaPenyelenggara" class="form-control" required>
                        </div>
                        
                        <div class="mb-3 position-relative">
                            <label class="form-label small fw-bold">Kategori / Tags (Ketik & Enter)</label>
                            <div id="kat-container" class="tag-container" onclick="document.getElementById('kat-input').focus()">
                                <input type="text" id="kat-input" class="tag-input" placeholder="Contoh: UI/UX, Bisnis...">
                            </div>
                            <input type="hidden" name="kategori_tags" id="kat-hidden">
                            <div id="kat-suggestions" class="suggestion-box" style="display:none;"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Deskripsi</label>
                            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4"></textarea>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Icon / Poster (Max 1MB)</label>
                            <input type="file" name="icon" class="form-control" accept="image/*" onchange="previewImage(this)">
                            <div class="img-preview-box">
                                <img id="previewImg" src="" style="display:none;">
                                <div id="previewText" class="text-muted small text-center px-2">Preview Gambar</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Lokasi</label>
                            <input type="text" name="lokasi" id="lokasi" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Link Website</label>
                            <input type="url" name="link_web" id="linkWeb" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Mulai</label>
                                <input type="date" name="tgl_mulai" id="tglMulai" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Selesai</label>
                                <input type="date" name="tgl_selesai" id="tglSelesai" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="detailLombaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-white border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary">Detail Kompetisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-7 border-end">
                        <div id="detailImageContainer" class="rounded-3 overflow-hidden shadow-sm mb-3" style="max-height: 200px;">
                            <img src="" id="detailImage" class="img-fluid w-100" style="object-fit: cover; display: none;">
                            <div id="detailImageDefault" class="bg-light d-flex align-items-center justify-content-center text-secondary" style="height: 150px; display: none;">
                                <i class="fas fa-trophy fa-3x opacity-25"></i>
                            </div>
                        </div>

                        <h4 class="fw-bold text-dark mb-1" id="detailNamaLomba">Nama Lomba</h4>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-building me-1"></i> <span id="detailPenyelenggara">-</span> 
                            &nbsp;|&nbsp; <i class="fas fa-map-marker-alt me-1"></i> <span id="detailLokasi">-</span>
                        </p>
                        
                        <div class="mb-3">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info" id="detailJenis">-</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary" id="detailTingkatan">-</span>
                        </div>

                        <div id="detailTags" class="mb-3"></div>

                        <div class="alert alert-light border small text-muted p-2 mb-3">
                            <div class="d-flex justify-content-between">
                                <span><i class="far fa-calendar-alt me-1"></i> Mulai: <b id="detailTglMulai" class="text-dark">-</b></span>
                                <span><i class="far fa-calendar-check me-1"></i> Selesai: <b id="detailTglSelesai" class="text-danger">-</b></span>
                            </div>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2 mb-2">Deskripsi</h6>
                        <p class="text-secondary small" id="detailDeskripsi" style="white-space: pre-line; line-height: 1.6;">-</p>
                        
                        <a href="#" id="detailLinkWeb" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill w-100 mt-2">
                            <i class="fas fa-globe me-2"></i>Kunjungi Website Resmi
                        </a>
                    </div>

                    <div class="col-md-5">
                        <h6 class="fw-bold text-success mb-3"><i class="fas fa-users me-2"></i>Tim Terdaftar</h6>
                        <div id="detailTeamList" class="detail-teams-scroll">
                            <div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Memuat...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// --- LOGIC TAG INPUT SYSTEM ---
function setupTagSystem(containerId, inputId, hiddenId, suggestId, type) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const suggestionBox = document.getElementById(suggestId);
    
    let tags = hidden.value ? hidden.value.split(',').filter(t => t) : [];
    renderTags();

    function renderTags() {
        container.querySelectorAll('.tag-item').forEach(i => i.remove());
        tags.forEach((tag, index) => {
            const span = document.createElement('span');
            span.className = 'tag-item shadow-sm border';
            span.innerHTML = `${tag} <i class="fas fa-times ms-2" onclick="removeTag('${hiddenId}', ${index})"></i>`;
            container.insertBefore(span, input);
        });
        hidden.value = tags.join(',');
    }

    window.removeTag = function(targetId, idx) {
        if(targetId === hiddenId) { tags.splice(idx, 1); renderTags(); }
    };

    window.resetTags = function(targetId) {
        if(targetId === hiddenId) { tags = []; renderTags(); }
    };
    
    window.setTags = function(targetId, tagString) {
        if(targetId === hiddenId) { 
            tags = tagString ? tagString.split(',').filter(t=>t) : []; 
            renderTags(); 
        }
    };

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addTag(this.value);
        } else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) {
            tags.pop();
            renderTags();
        }
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
                        div.onclick = () => { addTag(item.name); };
                        suggestionBox.appendChild(div);
                    });
                } else { suggestionBox.style.display = 'none'; }
            });
    });

    function addTag(text) {
        const cleanText = text.trim();
        if (cleanText && !tags.some(t => t.toLowerCase() === cleanText.toLowerCase())) {
            tags.push(cleanText);
            renderTags();
        }
        input.value = '';
        suggestionBox.style.display = 'none';
        input.focus();
    }
    
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target) && !suggestionBox.contains(e.target)) {
            suggestionBox.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if(document.getElementById('kat-container')) {
        setupTagSystem('kat-container', 'kat-input', 'kat-hidden', 'kat-suggestions', 'kategori');
    }
});

// --- FUNGSI MODAL & UTILS ---

function openModal() {
    document.getElementById('modalTitle').innerText = 'Tambah Kompetisi';
    document.getElementById('idLomba').value = '';
    document.querySelector('form').reset();
    resetTags('kat-hidden');
    document.getElementById('previewImg').style.display = 'none';
    document.getElementById('previewImg').src = '';
    document.getElementById('previewText').style.display = 'block';
    new bootstrap.Modal(document.getElementById('lombaModal')).show();
}

function editLomba(data) {
    document.getElementById('modalTitle').innerText = 'Edit Kompetisi';
    document.getElementById('idLomba').value = data.ID_Lomba;
    document.getElementById('namaLomba').value = data.Nama_Lomba;
    document.getElementById('idJenis').value = data.ID_Jenis_Penyelenggara;
    document.getElementById('namaPenyelenggara').value = data.Nama_Penyelenggara; 
    document.getElementById('idTingkatan').value = data.ID_Tingkatan;
    document.getElementById('tglMulai').value = data.Tanggal_Mulai;
    document.getElementById('tglSelesai').value = data.Tanggal_Selesai;
    document.getElementById('lokasi').value = data.Lokasi;
    document.getElementById('linkWeb').value = data.Link_Web;
    document.getElementById('deskripsi').value = data.Deskripsi;
    
    setTags('kat-hidden', data.Kategori_List || '');

    const imgEl = document.getElementById('previewImg');
    const txtEl = document.getElementById('previewText');
    if (data.Foto_Lomba) {
        imgEl.src = data.Foto_Lomba;
        imgEl.style.display = 'block';
        txtEl.style.display = 'none';
    } else {
        imgEl.style.display = 'none';
        txtEl.style.display = 'block';
    }
    
    new bootstrap.Modal(document.getElementById('lombaModal')).show();
}

function previewImage(input) {
    const imgEl = document.getElementById('previewImg');
    const txtEl = document.getElementById('previewText');
    const maxSize = 1 * 1024 * 1024; // 1 MB
    
    if (input.files && input.files[0]) {
        if (input.files[0].size > maxSize) {
            Swal.fire({
                icon: 'warning',
                title: 'Ukuran Terlalu Besar',
                text: 'Maksimal 1MB. Silakan pilih gambar lain.',
                confirmButtonColor: '#0d6efd'
            });
            input.value = ""; 
            imgEl.style.display = 'none';
            imgEl.src = "";
            txtEl.style.display = 'block';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            imgEl.src = e.target.result;
            imgEl.style.display = 'block';
            txtEl.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function confirmDelete(id) {
    Swal.fire({
        title: 'Hapus Kompetisi?',
        text: "Data tidak bisa dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delForm' + id).submit();
        }
    });
}

function viewDetail(data) {
    document.getElementById('detailNamaLomba').innerText = data.Nama_Lomba ?? '-';
    document.getElementById('detailPenyelenggara').innerText = data.Nama_Penyelenggara ?? '-';
    document.getElementById('detailLokasi').innerText = data.Lokasi ?? 'Daring/Online';
    document.getElementById('detailJenis').innerText = data.Nama_Jenis ?? 'Umum';
    document.getElementById('detailTingkatan').innerText = data.Nama_Tingkatan ?? 'Nasional';
    document.getElementById('detailTglMulai').innerText = formatDate(data.Tanggal_Mulai);
    document.getElementById('detailTglSelesai').innerText = formatDate(data.Tanggal_Selesai);
    document.getElementById('detailDeskripsi').innerText = data.Deskripsi ?? 'Tidak ada deskripsi.';
    
    const tagContainer = document.getElementById('detailTags');
    tagContainer.innerHTML = '';
    if (data.Kategori_List) {
        const tags = data.Kategori_List.split(',');
        tags.forEach(tag => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-light text-dark border me-1 mb-1';
            badge.innerText = tag.trim();
            tagContainer.appendChild(badge);
        });
    }

    const btnLink = document.getElementById('detailLinkWeb');
    if (data.Link_Web) {
        btnLink.href = data.Link_Web;
        btnLink.classList.remove('disabled');
        btnLink.style.display = 'block';
    } else {
        btnLink.style.display = 'none';
    }

    const imgEl = document.getElementById('detailImage');
    const defaultEl = document.getElementById('detailImageDefault');
    if (data.Foto_Lomba) {
        imgEl.src = data.Foto_Lomba;
        imgEl.style.display = 'block';
        defaultEl.style.display = 'none';
    } else {
        imgEl.style.display = 'none';
        defaultEl.style.display = 'flex';
    }

    const teamListContainer = document.getElementById('detailTeamList');
    teamListContainer.innerHTML = '<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Memuat...</div>';

    fetch(`fetch_data.php?page=teams_by_lomba&id=${data.ID_Lomba}`)
        .then(response => response.text())
        .then(html => { teamListContainer.innerHTML = html; })
        .catch(err => { teamListContainer.innerHTML = '<div class="text-danger small text-center">Gagal memuat data tim.</div>'; });

    new bootstrap.Modal(document.getElementById('detailLombaModal')).show();
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}
</script>