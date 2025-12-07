<?php
// FILE: Halaman Daftar Lomba (Bug Fix: Removed stretched-link)

$tab = $_GET['tab'] ?? 'active';
$filterKategori = $_GET['cat'] ?? '';
$filterTahun = $_GET['year'] ?? '';

// 1. VALIDASI TAB & INPUT
$allowedTabs = ['active', 'upcoming', 'archive'];
$rawTab = $_GET['tab'] ?? 'active';
$tab = in_array($rawTab, $allowedTabs) ? $rawTab : 'active';

$filterKategori = $_GET['cat'] ?? '';
$filterTahun = $_GET['year'] ?? '';
$today = date('Y-m-d'); 

// --- LOGIC TAMBAH LOMBA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_lomba') {
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'dosen')) {
        try {
            $pdo->beginTransaction();

            $sqlInsert = "INSERT INTO Lomba (Nama_Lomba, Deskripsi, ID_Jenis_Penyelenggara, ID_Tingkatan, Tanggal_Mulai, Tanggal_Selesai, Lokasi, Link_Web) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sqlInsert);
            $stmt->execute([
                $_POST['nama'], $_POST['deskripsi'], $_POST['penyelenggara'], 
                $_POST['tingkatan'], $_POST['tgl_mulai'], $_POST['tgl_selesai'], 
                $_POST['lokasi'], $_POST['link']
            ]);
            $idLomba = $pdo->lastInsertId();

            if (!empty($_POST['kategori_tags'])) {
                $tags = explode(',', $_POST['kategori_tags']);
                $stmtCheck = $pdo->prepare("SELECT ID_Kategori FROM Kategori_Lomba WHERE Nama_Kategori LIKE ?");
                $stmtInsKat = $pdo->prepare("INSERT INTO Kategori_Lomba (Nama_Kategori) VALUES (?)");
                $stmtLink = $pdo->prepare("INSERT INTO Lomba_Kategori (ID_Lomba, ID_Kategori) VALUES (?, ?)");

                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (empty($tag)) continue;
                    
                    $stmtCheck->execute([$tag]);
                    $idKat = $stmtCheck->fetchColumn();
                    if (!$idKat) {
                        $stmtInsKat->execute([ucwords($tag)]);
                        $idKat = $pdo->lastInsertId();
                    }
                    $stmtLink->execute([$idLomba, $idKat]);
                }
            }
            $pdo->commit();
            header("Location: index.php?page=lomba&tab=$tab&status=success");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = $e->getMessage();
        }
    }
}

// --- 2. QUERY DATA ---
$sql = "SELECT l.*, 
               GROUP_CONCAT(k.Nama_Kategori SEPARATOR '||') as Kategori_List,
               p.Nama_Jenis, p.Bobot_Poin, t.Nama_Tingkatan, t.Poin_Dasar 
        FROM Lomba l
        LEFT JOIN Lomba_Kategori lk ON l.ID_Lomba = lk.ID_Lomba
        LEFT JOIN Kategori_Lomba k ON lk.ID_Kategori = k.ID_Kategori
        LEFT JOIN Jenis_Penyelenggara p ON l.ID_Jenis_Penyelenggara = p.ID_Jenis
        LEFT JOIN Tingkatan_Lomba t ON l.ID_Tingkatan = t.ID_Tingkatan
        WHERE 1=1";

$params = [];

if ($tab === 'active') {
    $sql .= " AND ? BETWEEN l.Tanggal_Mulai AND l.Tanggal_Selesai";
    $params[] = $today;
} elseif ($tab === 'upcoming') {
    $sql .= " AND l.Tanggal_Mulai > ?";
    $params[] = $today;
} elseif ($tab === 'archive') {
    $sql .= " AND l.Tanggal_Selesai < ?";
    $params[] = $today;
    
    if ($filterTahun) {
        $sql .= " AND YEAR(l.Tanggal_Selesai) = ?";
        $params[] = $filterTahun;
    }
}

if ($filterKategori) {
    $sql .= " AND l.ID_Lomba IN (SELECT ID_Lomba FROM Lomba_Kategori WHERE ID_Kategori = ?)";
    $params[] = $filterKategori;
}

$sql .= " GROUP BY l.ID_Lomba ORDER BY l.Tanggal_Mulai " . ($tab === 'archive' ? 'DESC' : 'ASC');

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lombas = $stmt->fetchAll();

// --- 3. DATA MASTER ---
$kategoris = $pdo->query("SELECT * FROM Kategori_Lomba ORDER BY Nama_Kategori ASC")->fetchAll();
$years = $pdo->query("SELECT DISTINCT YEAR(Tanggal_Selesai) as y FROM Lomba ORDER BY y DESC")->fetchAll();
$penyelenggaras = $pdo->query("SELECT * FROM Jenis_Penyelenggara ORDER BY Nama_Jenis ASC")->fetchAll();
$tingkatans = $pdo->query("SELECT * FROM Tingkatan_Lomba ORDER BY Poin_Dasar ASC")->fetchAll();

$canAdd = (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'dosen'));
?>

<style>
    /* Card Styles */
    .lomba-card-wrapper {
        background: #fff; border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03); transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.03); overflow: hidden; height: 100%;
        display: flex; flex-direction: column;
    }
    .lomba-card-wrapper:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
    
    .status-line { height: 4px; width: 100%; }
    .line-active { background: #dc3545; }
    .line-upcoming { background: #0dcaf0; }
    .line-archive { background: #6c757d; }

    .date-badge {
        background: #f8f9fa; border-radius: 12px; padding: 10px 15px;
        text-align: center; border: 1px solid #e9ecef; min-width: 70px;
    }
    .date-day { font-size: 1.6rem; font-weight: 800; line-height: 1; color: #212529; }
    .date-month { font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6c757d; display: block; margin-top: 2px; }

    .category-badge {
        font-size: 0.7rem; background-color: rgba(13, 110, 253, 0.08); color: #0d6efd;
        padding: 4px 10px; border-radius: 50px; border: 1px solid rgba(13, 110, 253, 0.15);
    }

    .nav-pills-custom .nav-link {
        color: #6c757d; background: #fff; border: 1px solid #e9ecef;
        margin-right: 8px; border-radius: 50px; padding: 8px 20px;
        font-weight: 600; font-size: 0.9rem; transition: all 0.2s;
    }
    .nav-pills-custom .nav-link:hover { background: #f8f9fa; color: #0d6efd; }
    .nav-pills-custom .nav-link.active {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0); color: #fff; border-color: transparent;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
    }
    
    .btn-gradient-primary { background: linear-gradient(135deg, #0d6efd, #0dcaf0); color: white; border: none; }
    .btn-gradient-primary:hover { background: linear-gradient(135deg, #0b5ed7, #0aa2c0); color: white; transform: translateY(-2px); }

    /* Tag Input Styles */
    .tag-container { min-height: 42px; padding: 6px; border: 1px solid #ced4da; border-radius: 0.375rem; background: #fff; display: flex; flex-wrap: wrap; gap: 5px; cursor: text; }
    .tag-container:focus-within { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
    .tag-item { background: #e9ecef; color: #495057; padding: 3px 10px; border-radius: 50px; font-size: 0.85rem; display: flex; align-items: center; }
    .tag-item i { margin-left: 6px; cursor: pointer; color: #dc3545; }
    .tag-input { border: none; outline: none; flex-grow: 1; min-width: 100px; font-size: 0.9rem; }
    .suggestion-box { position: absolute; width: 100%; z-index: 1000; background: white; border: 1px solid #dee2e6; border-radius: 0 0 8px 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto; display: none; }
    .suggestion-item { padding: 8px 12px; cursor: pointer; font-size: 0.9rem; }
    .suggestion-item:hover { background-color: #f8f9fa; color: #0d6efd; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Kompetisi</h3>
        <p class="text-muted mb-0">Jelajahi peluang prestasi yang sesuai dengan tim Anda.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php if($canAdd): ?>
            <button class="btn btn-gradient-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addLombaModal">
                <i class="fas fa-plus me-2"></i>Tambah Lomba
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>Lomba berhasil dipublikasikan!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0 rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            
            <ul class="nav nav-pills nav-pills-custom">
                <li class="nav-item"><a class="nav-link <?= $tab==='active'?'active':'' ?>" href="?page=lomba&tab=active"><i class="fas fa-fire me-2"></i>Berlangsung</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab==='upcoming'?'active':'' ?>" href="?page=lomba&tab=upcoming"><i class="fas fa-calendar-alt me-2"></i>Akan Datang</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab==='archive'?'active':'' ?>" href="?page=lomba&tab=archive"><i class="fas fa-history me-2"></i>Arsip</a></li>
            </ul>

            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="page" value="lomba">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                
                <select name="cat" class="form-select form-select-sm bg-light border-0" style="min-width: 160px; font-weight: 500;" onchange="this.form.submit()">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach($kategoris as $k): ?>
                        <option value="<?= $k['ID_Kategori'] ?>" <?= $filterKategori == $k['ID_Kategori'] ? 'selected' : '' ?>><?= $k['Nama_Kategori'] ?></option>
                    <?php endforeach; ?>
                </select>

                <?php if($tab === 'archive'): ?>
                <select name="year" class="form-select form-select-sm bg-light border-0" style="width: 100px; font-weight: 500;" onchange="this.form.submit()">
                    <option value="">Tahun</option>
                    <?php foreach($years as $y): ?>
                        <option value="<?= $y['y'] ?>" <?= $filterTahun == $y['y'] ? 'selected' : '' ?>><?= $y['y'] ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php if(empty($lombas)): ?>
        <div class="col-12 py-5 text-center">
            <div class="opacity-25 mb-3"><i class="fas fa-folder-open fa-4x text-secondary"></i></div>
            <h5 class="text-muted fw-bold">Belum ada kompetisi pada kategori ini.</h5>
            <p class="text-muted small">Coba tab lain atau reset filter pencarian.</p>
        </div>
    <?php endif; ?>

    <?php foreach($lombas as $l): ?>
    <?php 
        $maxPoints = ($l['Poin_Dasar'] ?? 0) * ($l['Bobot_Poin'] ?? 0);
        $lineClass = ($tab==='active') ? 'line-active' : (($tab==='upcoming') ? 'line-upcoming' : 'line-archive');
        $statusText = ($tab==='active') ? 'LIVE' : (($tab==='upcoming') ? 'SOON' : 'DONE');
        $statusBadgeColor = ($tab==='active') ? 'bg-danger' : (($tab==='upcoming') ? 'bg-info text-dark' : 'bg-secondary');
        
        $kats = !empty($l['Kategori_List']) ? explode('||', $l['Kategori_List']) : ['Umum'];
        $katsDisplay = array_slice($kats, 0, 3);
        $katsMore = count($kats) - 3;
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="lomba-card-wrapper">
            <div class="status-line <?= $lineClass ?>"></div>
            
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="date-badge">
                        <span class="date-day"><?= date('d', strtotime($l['Tanggal_Mulai'])) ?></span>
                        <span class="date-month"><?= date('M', strtotime($l['Tanggal_Mulai'])) ?></span>
                    </div>
                    
                    <div class="text-end">
                        <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Max Poin</small>
                        <span class="text-warning fw-bold fs-5"><i class="fas fa-star me-1"></i><?= number_format($maxPoints) ?></span>
                    </div>
                </div>

                <h5 class="fw-bold text-dark mb-1 lh-sm">
                    <a href="?page=lomba_detail&id=<?= $l['ID_Lomba'] ?>" class="text-decoration-none text-dark">
                        <?= htmlspecialchars($l['Nama_Lomba']) ?>
                    </a>
                </h5>
                <div class="text-muted small mb-3">
                    <?= $l['Nama_Jenis'] ?? '-' ?> &bull; <?= $l['Nama_Tingkatan'] ?? '-' ?>
                </div>

                <div class="mt-auto mb-4">
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach($katsDisplay as $k): ?>
                            <span class="category-badge"><?= htmlspecialchars($k) ?></span>
                        <?php endforeach; ?>
                        <?php if($katsMore > 0): ?>
                            <span class="category-badge text-muted border-secondary bg-light">+<?= $katsMore ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                    <span class="badge <?= $statusBadgeColor ?> rounded-pill px-3 py-1" style="font-size: 0.7rem; letter-spacing: 0.5px;"><?= $statusText ?></span>
                    <a href="?page=lomba_detail&id=<?= $l['ID_Lomba'] ?>" class="text-decoration-none">
                        <small class="text-muted fw-bold" style="font-size: 0.8rem;">
                            <i class="fas fa-chevron-right text-primary"></i> Detail
                        </small>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if($canAdd): ?>
<div class="modal fade" id="addLombaModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4" id="formLomba">
            <div class="modal-header bg-primary text-white border-0 px-4 py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Publikasi Kompetisi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <input type="hidden" name="action" value="add_lomba">
                
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Informasi Utama</h6>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Nama Lomba</label>
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: Hackathon Nasional 2025" required>
                </div>
                
                <div class="mb-4 position-relative">
                    <label class="form-label small fw-bold text-muted">Kategori (Bisa lebih dari satu)</label>
                    <div id="cat-container" class="tag-container" onclick="document.getElementById('cat-input').focus()">
                        <input type="text" id="cat-input" class="tag-input" placeholder="Ketik & Enter...">
                    </div>
                    <div id="cat-suggestions" class="suggestion-box"></div>
                    <input type="hidden" name="kategori_tags" id="cat-hidden">
                </div>

                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 mt-4">Detail Pelaksanaan</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Penyelenggara</label>
                        <select name="penyelenggara" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <?php foreach($penyelenggaras as $p): ?>
                                <option value="<?= $p['ID_Jenis'] ?>"><?= $p['Nama_Jenis'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Tingkatan</label>
                        <select name="tingkatan" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <?php foreach($tingkatans as $t): ?>
                                <option value="<?= $t['ID_Tingkatan'] ?>"><?= $t['Nama_Tingkatan'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Mulai</label>
                        <input type="date" name="tgl_mulai" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Selesai</label>
                        <input type="date" name="tgl_selesai" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted">Deskripsi Lengkap</label>
                    <textarea name="deskripsi" class="form-control" rows="4" placeholder="Jelaskan detail lomba..." required></textarea>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Lokasi</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Online / Jakarta" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Link Website</label>
                        <input type="url" name="link" class="form-control" placeholder="https://...">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light px-4 py-3">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-gradient-primary rounded-pill px-5 fw-bold shadow-sm">Simpan & Publish</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('cat-container');
    const input = document.getElementById('cat-input');
    const hidden = document.getElementById('cat-hidden');
    const suggestionBox = document.getElementById('cat-suggestions');
    let tags = [];

    function renderTags() {
        const items = container.querySelectorAll('.tag-item');
        items.forEach(i => i.remove());
        tags.forEach((tag, index) => {
            const span = document.createElement('span');
            span.className = 'tag-item shadow-sm border border-secondary';
            span.innerHTML = `${tag} <i class="fas fa-times ms-2" onclick="removeTag(${index})"></i>`;
            container.insertBefore(span, input);
        });
        hidden.value = tags.join(',');
    }

    window.removeTag = (idx) => { tags.splice(idx, 1); renderTags(); };

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); addTag(this.value); }
        else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) { tags.pop(); renderTags(); }
    });

    input.addEventListener('blur', function() { if(this.value.trim() !== '') addTag(this.value); });

    input.addEventListener('input', function() {
        const val = this.value.trim();
        if (val.length < 1) { suggestionBox.style.display = 'none'; return; }
        fetch(`fetch_tags.php?type=kategori&q=${val}`).then(res => res.json()).then(data => {
            suggestionBox.innerHTML = '';
            if (data.length > 0) {
                suggestionBox.style.display = 'block';
                data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.textContent = item.name;
                    div.onclick = () => { addTag(item.name); suggestionBox.style.display = 'none'; };
                    suggestionBox.appendChild(div);
                });
            } else { suggestionBox.style.display = 'none'; }
        });
    });

    function addTag(text) {
        const cleanText = text.trim();
        if (cleanText && !tags.includes(cleanText)) { tags.push(cleanText); renderTags(); }
        input.value = ''; suggestionBox.style.display = 'none'; input.focus();
    }
    
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target) && !suggestionBox.contains(e.target)) {
            suggestionBox.style.display = 'none';
        }
    });
});
</script>
<?php endif; ?>