<?php
// FILE: src/pages/tim.php (Marketplace Tim & Join Request)

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>"; exit;
}

$userId = $_SESSION['user_id'];

// --- 1. LOGIKA REQUEST JOIN (MAHASISWA -> KETUA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_join') {
    try {
        $idTim = $_POST['id_tim'];
        $idKetua = $_POST['id_ketua'];

        // Cek Validasi Ganda
        $cek = $pdo->prepare("SELECT COUNT(*) FROM Keanggotaan_Tim WHERE ID_Tim = ? AND ID_Mahasiswa = ?");
        $cek->execute([$idTim, $userId]);
        
        $cekPending = $pdo->prepare("SELECT COUNT(*) FROM Invitasi WHERE ID_Tim = ? AND ID_Pengirim = ? AND Status = 'Pending'");
        $cekPending->execute([$idTim, $userId]);

        if ($cek->fetchColumn() > 0) {
            echo "<script>alert('Anda sudah menjadi anggota tim ini.');</script>";
        } elseif ($cekPending->fetchColumn() > 0) {
            echo "<script>alert('Permintaan gabung sudah dikirim, mohon tunggu ketua menerima.');</script>";
        } else {
            // Insert Invitasi (Pengirim = Saya/Member, Penerima = Ketua Tim)
            $stmt = $pdo->prepare("INSERT INTO Invitasi (ID_Tim, ID_Pengirim, ID_Penerima, Tipe_Penerima, Status) VALUES (?, ?, ?, 'mahasiswa', 'Pending')");
            $stmt->execute([$idTim, $userId, $idKetua]);
            echo "<script>alert('Permintaan bergabung berhasil dikirim ke Ketua Tim!'); window.location='?page=tim';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

// --- 2. AMBIL DATA ---
// Ambil daftar request saya yang masih pending (untuk disable tombol)
$myRequests = $pdo->query("SELECT ID_Tim FROM Invitasi WHERE ID_Pengirim = $userId AND Status = 'Pending'")->fetchAll(PDO::FETCH_COLUMN);
$myTeams = $pdo->query("SELECT ID_Tim FROM Keanggotaan_Tim WHERE ID_Mahasiswa = $userId")->fetchAll(PDO::FETCH_COLUMN);

// Filter Search
$keyword = $_GET['q'] ?? '';
$lombaFilter = $_GET['lomba'] ?? '';

$sql = "SELECT t.*, l.Nama_Lomba, l.Tanggal_Selesai, l.Lokasi, 
        m.Nama_Mahasiswa as Ketua, m.NIM as NIM_Ketua, m.Foto_Profil as Foto_Ketua, m.ID_Mahasiswa as ID_Ketua,
        pj.Nama_Peringkat, d.Nama_Dosen, k.Nama_Kategori,
        (SELECT COUNT(*) FROM Keanggotaan_Tim WHERE ID_Tim = t.ID_Tim) as Jml_Anggota
        FROM Tim t 
        JOIN Lomba l ON t.ID_Lomba = l.ID_Lomba 
        JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa 
        LEFT JOIN Peringkat_Juara pj ON t.ID_Peringkat = pj.ID_Peringkat
        LEFT JOIN Dosen_Pembimbing d ON t.ID_Dosen_Pembimbing = d.ID_Dosen
        LEFT JOIN Kategori_Lomba k ON t.ID_Kategori = k.ID_Kategori
        WHERE t.Status_Pencarian = 'Terbuka'";

$params = [];
if ($keyword) {
    $sql .= " AND t.Nama_Tim LIKE ?";
    $params[] = "%$keyword%";
}
if ($lombaFilter) {
    $sql .= " AND t.ID_Lomba = ?";
    $params[] = $lombaFilter;
}

$sql .= " ORDER BY t.ID_Tim DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$timList = $stmt->fetchAll();

$lombaList = $pdo->query("SELECT * FROM Lomba WHERE Tanggal_Selesai >= CURDATE() ORDER BY Nama_Lomba ASC")->fetchAll();
?>

<style>
    .team-card { border: none; border-radius: 15px; transition: all 0.2s; background: #fff; }
    .team-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
    .role-badge { font-size: 0.75rem; background: #eef2f6; color: #4b5563; padding: 4px 10px; border-radius: 50px; border: 1px solid #e5e7eb; display: inline-block; margin-right: 4px; margin-bottom: 4px; }
    .leader-img { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .leader-initial { width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
</style>

<div class="row align-items-end mb-4">
    <div class="col-md-6">
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Cari Tim</h3>
        <p class="text-muted mb-0">Temukan tim yang sesuai dengan skill dan minatmu.</p>
    </div>
    <div class="col-md-6 mt-3 mt-md-0">
        <form method="GET" class="card border-0 shadow-sm p-2">
            <input type="hidden" name="page" value="tim">
            <div class="input-group">
                <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-0 shadow-none" placeholder="Cari nama tim..." value="<?= htmlspecialchars($keyword) ?>">
                <select name="lomba" class="form-select border-0 shadow-none" style="max-width: 150px; cursor: pointer;" onchange="this.form.submit()">
                    <option value="">Semua Lomba</option>
                    <?php foreach($lombaList as $l): ?>
                        <option value="<?= $l['ID_Lomba'] ?>" <?= $lombaFilter==$l['ID_Lomba']?'selected':'' ?>>
                            <?= htmlspecialchars(substr($l['Nama_Lomba'], 0, 15)) ?>...
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <?php if(empty($timList)): ?>
        <div class="col-12 text-center py-5">
            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486747.png" width="100" class="mb-3 opacity-50">
            <h5 class="text-muted">Belum ada tim yang membuka pendaftaran.</h5>
        </div>
    <?php endif; ?>

    <?php foreach($timList as $t): ?>
    <?php 
        $roles = $t['Kebutuhan_Role'] ? explode(',', $t['Kebutuhan_Role']) : [];
        $isMyTeam = ($t['ID_Ketua'] == $userId || in_array($t['ID_Tim'], $myTeams));
        $isPending = in_array($t['ID_Tim'], $myRequests);
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="card team-card h-100 p-3 shadow-sm">
            <div class="d-flex justify-content-between mb-2">
                <small class="text-primary fw-bold text-uppercase"><?= htmlspecialchars(substr($t['Nama_Lomba'], 0, 25)) ?>...</small>
                <?php if($t['Nama_Kategori']): ?>
                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($t['Nama_Kategori']) ?></span>
                <?php endif; ?>
            </div>

            <h5 class="fw-bold text-dark mb-1 text-truncate"><?= htmlspecialchars($t['Nama_Tim']) ?></h5>
            
            <div class="mb-3">
                <?php if(!empty($roles)): ?>
                    <?php foreach(array_slice($roles, 0, 3) as $r): ?>
                        <span class="role-badge"><?= trim($r) ?></span>
                    <?php endforeach; ?>
                    <?php if(count($roles) > 3): ?><span class="role-badge">+<?= count($roles)-3 ?></span><?php endif; ?>
                <?php else: ?>
                    <small class="text-muted fst-italic">Open for all roles</small>
                <?php endif; ?>
            </div>

            <div class="mt-auto d-flex align-items-center justify-content-between pt-3 border-top">
                <div class="d-flex align-items-center">
                    <?php 
                        $foto = getFotoMhs($t['NIM_Ketua'], $t['Foto_Ketua']);
                        if($foto) echo "<img src='$foto' class='leader-img me-2'>";
                        else echo "<div class='leader-initial me-2 shadow-sm'>".substr($t['Ketua'],0,1)."</div>";
                    ?>
                    <div class="lh-1">
                        <small class="text-muted d-block" style="font-size:0.7rem">Leader</small>
                        <span class="fw-bold small"><?= htmlspecialchars($t['Ketua']) ?></span>
                    </div>
                </div>
                
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $t['ID_Tim'] ?>">
                    Detail
                </button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetail<?= $t['ID_Tim'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-gradient bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-users me-2"></i>Detail Tim</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-7 p-4">
                            <h4 class="fw-bold text-primary mb-1"><?= htmlspecialchars($t['Nama_Tim']) ?></h4>
                            <p class="text-muted small mb-3">
                                <i class="fas fa-trophy me-1 text-warning"></i> <?= htmlspecialchars($t['Nama_Lomba']) ?>
                            </p>

                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">Deskripsi Tim</h6>
                            <p class="text-muted small" style="line-height: 1.6;">
                                <?= $t['Deskripsi_Tim'] ? nl2br(htmlspecialchars($t['Deskripsi_Tim'])) : '<em class="text-muted">Tidak ada deskripsi.</em>' ?>
                            </p>

                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-2 mt-4">Posisi Dicari</h6>
                            <div class="mb-3">
                                <?php if(!empty($roles)): ?>
                                    <?php foreach($roles as $r): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 me-1 mb-1 fw-normal">
                                            <i class="fas fa-check-circle me-1"></i><?= trim($r) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small">Mencari semua posisi potensial.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-5 bg-light p-4 border-start">
                            <div class="mb-4">
                                <label class="small fw-bold text-muted text-uppercase mb-2">Ketua Tim</label>
                                <div class="d-flex align-items-center bg-white p-2 rounded shadow-sm border">
                                    <?php 
                                        if($foto) echo "<img src='$foto' class='leader-img me-2' style='width:40px;height:40px'>";
                                        else echo "<div class='leader-initial me-2' style='width:40px;height:40px;font-size:0.9rem'>".substr($t['Ketua'],0,1)."</div>";
                                    ?>
                                    <div>
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($t['Ketua']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($t['NIM_Ketua']) ?></div>
                                    </div>
                                    <a href="?page=profile&id=<?= $t['ID_Ketua'] ?>" class="ms-auto btn btn-light btn-sm text-primary"><i class="fas fa-external-link-alt"></i></a>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="small fw-bold text-muted text-uppercase mb-1">Dosen Pembimbing</label>
                                <div class="fw-bold text-dark small">
                                    <?= $t['Nama_Dosen'] ? '<i class="fas fa-user-tie me-1 text-secondary"></i> '.htmlspecialchars($t['Nama_Dosen']) : '<span class="text-muted">- Belum ada -</span>' ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="small fw-bold text-muted text-uppercase mb-1">Deadline Lomba</label>
                                <div class="fw-bold text-danger small">
                                    <i class="far fa-calendar-alt me-1"></i> <?= date('d M Y', strtotime($t['Tanggal_Selesai'])) ?>
                                </div>
                            </div>

                            <hr>

                            <?php if($isMyTeam): ?>
                                <button class="btn btn-secondary w-100 disabled rounded-pill">
                                    <i class="fas fa-check me-2"></i>Anggota Tim
                                </button>
                            <?php elseif($isPending): ?>
                                <button class="btn btn-warning w-100 disabled rounded-pill text-white">
                                    <i class="fas fa-clock me-2"></i>Menunggu Konfirmasi
                                </button>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="request_join">
                                    <input type="hidden" name="id_tim" value="<?= $t['ID_Tim'] ?>">
                                    <input type="hidden" name="id_ketua" value="<?= $t['ID_Ketua'] ?>">
                                    <button class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm">
                                        <i class="fas fa-paper-plane me-2"></i>Ajukan Bergabung
                                    </button>
                                </form>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>