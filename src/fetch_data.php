<?php
// FILE: API Fetch Data Universal (Updated Layout Dosen & Teams Popup)
require_once 'config.php';

$page   = $_GET['page'] ?? ''; 
$search = $_GET['q'] ?? '';
$fak    = $_GET['fakultas'] ?? '';
$prodi  = $_GET['prodi'] ?? '';
$skill  = $_GET['skill'] ?? '';
$role   = $_GET['role'] ?? '';

// === 1. FETCH DATA MAHASISWA ===
if ($page === 'mahasiswa') {
    $sql = "SELECT m.*, p.Nama_Prodi, f.Nama_Fakultas 
            FROM Mahasiswa m 
            LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
            LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
            WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND (m.Nama_Mahasiswa LIKE ? OR m.NIM LIKE ? OR m.Email LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
    }
    if ($fak) { $sql .= " AND f.ID_Fakultas = ?"; $params[] = $fak; }
    if ($prodi) { $sql .= " AND m.ID_Prodi = ?"; $params[] = $prodi; }
    
    if ($skill) {
        $sql .= " AND m.ID_Mahasiswa IN (SELECT ID_Mahasiswa FROM Mahasiswa_Skill WHERE ID_Skill = ?)";
        $params[] = $skill;
    }
    if ($role) {
        $sql .= " AND m.ID_Mahasiswa IN (SELECT ID_Mahasiswa FROM Mahasiswa_Role WHERE ID_Role = ?)";
        $params[] = $role;
    }

    $sql .= " ORDER BY m.Nama_Mahasiswa ASC LIMIT 50"; 
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    if (empty($data)) { 
        echo '<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-search me-2"></i>Tidak ada data ditemukan.</td></tr>'; 
        exit; 
    }

    foreach($data as $m) {
        $foto = getFotoMhs($m['NIM'], $m['Foto_Profil']);
        
        if ($foto) {
            $imgTag = "<img src='$foto?t=".time()."' class='rounded-circle border shadow-sm' style='width: 35px; height: 35px; object-fit: cover;'>";
        } else {
            $initial = strtoupper(substr($m['Nama_Mahasiswa'], 0, 1));
            $imgTag = "<div class='rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold border border-primary border-opacity-25' style='width:35px; height:35px; font-size:0.9rem'>$initial</div>";
        }
        
        echo "<tr>
            <td class='ps-3 align-middle text-center' style='width: 40px;'>
                <div class='form-check d-flex justify-content-center'>
                    <input type='checkbox' name='ids[]' value='{$m['ID_Mahasiswa']}' class='form-check-input'>
                </div>
            </td>
            <td class='align-middle'>
                <div class='d-flex align-items-center'>
                    <div class='me-3'>$imgTag</div>
                    <div>
                        <div class='fw-bold text-dark'>".htmlspecialchars($m['Nama_Mahasiswa'])."</div>
                        <small class='text-muted'>".htmlspecialchars($m['NIM'])."</small>
                    </div>
                </div>
            </td>
            <td class='align-middle text-muted small'>".htmlspecialchars($m['Email'])."</td>
            <td class='align-middle'>
                <span class='badge bg-light text-dark border fw-normal'>".htmlspecialchars($m['Nama_Fakultas']??'-')."</span>
            </td>
            <td class='align-middle'>
                <span class='badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-normal'>".htmlspecialchars($m['Nama_Prodi']??'-')."</span>
            </td>
            <td class='text-end pe-4 align-middle'>
                <div class='btn-group'>
                    <a href='?page=profile&id={$m['ID_Mahasiswa']}' class='btn btn-sm btn-light text-primary' title='Lihat Profil'><i class='fas fa-eye'></i></a>
                    <button type='button' class='btn btn-sm btn-light text-warning' 
                        data-id='{$m['ID_Mahasiswa']}'
                        data-nim='{$m['NIM']}'
                        data-nama='".htmlspecialchars($m['Nama_Mahasiswa'], ENT_QUOTES)."'
                        data-email='{$m['Email']}'
                        data-prodi='{$m['ID_Prodi']}'
                        data-tmplahir='".htmlspecialchars($m['Tempat_Lahir']??'', ENT_QUOTES)."'
                        data-tgllahir='{$m['Tanggal_Lahir']}'
                        data-bio='".htmlspecialchars($m['Bio']??'', ENT_QUOTES)."'
                        onclick='editMhs(this)' title='Edit'>
                        <i class='fas fa-edit'></i>
                    </button>
                    <button type='button' class='btn btn-sm btn-light text-danger' onclick='deleteSingle({$m['ID_Mahasiswa']})' title='Hapus'><i class='fas fa-trash'></i></button>
                </div>
            </td>
        </tr>";
    }
}

// === 2. FETCH DATA DOSEN (ADMIN VIEW - TABEL) ===
elseif ($page === 'dosen') {
    $sql = "SELECT d.*, p.Nama_Prodi, f.Nama_Fakultas 
            FROM Dosen_Pembimbing d 
            LEFT JOIN Prodi p ON d.ID_Prodi = p.ID_Prodi 
            LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
            WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND (d.Nama_Dosen LIKE ? OR d.NIDN LIKE ? OR d.Email LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
    }
    if ($fak) { $sql .= " AND f.ID_Fakultas = ?"; $params[] = $fak; }
    if ($prodi) { $sql .= " AND d.ID_Prodi = ?"; $params[] = $prodi; }
    if ($skill) { $sql .= " AND d.ID_Dosen IN (SELECT ID_Dosen FROM Dosen_Keahlian WHERE ID_Skill = ?)"; $params[] = $skill; }

    $sql .= " ORDER BY d.Nama_Dosen ASC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    if (empty($data)) { 
        echo '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-search me-2"></i>Tidak ada data ditemukan.</td></tr>'; 
        exit; 
    }

    foreach($data as $d) {
        $initial = strtoupper(substr($d['Nama_Dosen'], 0, 1));
        
        // Gunakan getFotoMhs untuk konsistensi auto-discovery
        $fotoPath = getFotoMhs('DSN_' . $d['NIDN'], $d['Foto_Profil']);

        if ($fotoPath) {
            $imgTag = "<img src='$fotoPath?t=".time()."' class='rounded-circle border shadow-sm' style='width: 35px; height: 35px; object-fit: cover;'>";
        } else {
            $imgTag = "<div class='rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center fw-bold border border-success border-opacity-25' style='width:35px; height:35px; font-size:0.9rem'>$initial</div>";
        }

        // FIX: Encode JSON dengan benar untuk JavaScript onclick
        $jsonDosen = htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8');

        echo "<tr>
            <td class='ps-4 align-middle text-center' style='width: 50px;'>
                <div class='form-check d-flex justify-content-center'>
                    <input type='checkbox' name='ids[]' value='{$d['ID_Dosen']}' class='form-check-input'>
                </div>
            </td>
            <td class='align-middle'>
                <div class='d-flex align-items-center'>
                    <div class='me-3'>$imgTag</div>
                    <div>
                        <div class='fw-bold text-dark'>".htmlspecialchars($d['Nama_Dosen'])."</div>
                        <small class='text-muted'>".htmlspecialchars($d['Email'])."</small>
                    </div>
                </div>
            </td>
            <td class='align-middle fw-bold text-secondary'>{$d['NIDN']}</td>
            <td class='align-middle'>
                <div class='small text-dark'>".htmlspecialchars($d['Nama_Prodi']??'-')."</div>
                <div class='small text-muted'>".htmlspecialchars($d['Nama_Fakultas']??'-')."</div>
            </td>
            <td class='text-end pe-4 align-middle'>
                <div class='btn-group'>
                    <a href='?page=profile_dosen&id={$d['ID_Dosen']}' class='btn btn-sm btn-light text-primary'><i class='fas fa-eye'></i></a>
                    
                    <button type='button' class='btn btn-sm btn-light text-primary' 
                            onclick='editDosen($jsonDosen)'>
                        <i class='fas fa-pencil-alt'></i>
                    </button>

                    <button type='button' class='btn btn-sm btn-light text-danger' onclick='deleteSingle({$d['ID_Dosen']})'><i class='fas fa-trash'></i></button>
                </div>
            </td>
        </tr>";
    }
}

// === 3. FETCH LEADERBOARD ===
elseif ($page === 'ranking') {
    // (Kode ranking tetap sama)
    $sql = "SELECT m.*, p.Nama_Prodi, f.Nama_Fakultas 
            FROM Mahasiswa m 
            LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
            LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
            WHERE 1=1";
    $params = [];

    if ($search) { $sql .= " AND (m.Nama_Mahasiswa LIKE ? OR m.NIM LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%"]); }
    if ($fak) { $sql .= " AND f.ID_Fakultas = ?"; $params[] = $fak; }
    if ($prodi) { $sql .= " AND m.ID_Prodi = ?"; $params[] = $prodi; }
    if ($skill) { $sql .= " AND m.ID_Mahasiswa IN (SELECT ID_Mahasiswa FROM Mahasiswa_Skill WHERE ID_Skill = ?)"; $params[] = $skill; }
    if ($role) { $sql .= " AND m.ID_Mahasiswa IN (SELECT ID_Mahasiswa FROM Mahasiswa_Role WHERE ID_Role = ?)"; $params[] = $role; }

    $sql .= " ORDER BY m.Total_Poin DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    if (empty($data)) { echo '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-trophy me-2"></i>Belum ada data peringkat.</td></tr>'; exit; }

    $no = 1;
    foreach($data as $r) {
        $foto = getFotoMhs($r['NIM'], $r['Foto_Profil']);
        $img = $foto ? "<img src='$foto?t=".time()."' class='rounded-circle border shadow-sm' style='width: 40px; height: 40px; object-fit: cover;'>" : 
                       "<div class='rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center border fw-bold' style='width:40px; height:40px; font-size:1rem'>".substr($r['Nama_Mahasiswa'],0,1)."</div>";
        
        $medal = ($no==1)?'<i class="fas fa-crown text-warning fa-lg"></i>':(($no==2)?'<i class="fas fa-medal text-secondary fa-lg"></i>':(($no==3)?'<i class="fas fa-medal text-danger fa-lg"></i>':"<span class='fw-bold text-muted bg-light rounded px-2 py-1 small'>#$no</span>"));

        echo "<tr onclick=\"window.location='?page=profile&id={$r['ID_Mahasiswa']}'\" style='cursor:pointer' class='hover-shadow transition'>
            <td class='ps-4 align-middle text-center' style='width: 80px;'>$medal</td>
            <td class='align-middle'><div class='d-flex align-items-center'><div class='me-3'>$img</div><div><div class='fw-bold text-dark'>".htmlspecialchars($r['Nama_Mahasiswa'])."</div><small class='text-muted'>".htmlspecialchars($r['NIM'])."</small></div></div></td>
            <td class='align-middle'><span class='text-dark small'>".htmlspecialchars($r['Nama_Fakultas']??'-')."</span></td>
            <td class='align-middle'><span class='badge bg-light text-dark border fw-normal'>".htmlspecialchars($r['Nama_Prodi']??'-')."</span></td>
            <td class='text-end pe-4 align-middle'><h5 class='mb-0 fw-bold text-primary'>".number_format($r['Total_Poin'])." <small class='fs-6 text-muted'>pts</small></h5></td>
        </tr>";
        $no++;
    }
}

// === 4. FETCH DOSEN PUBLIC (Untuk Halaman Cari Dosen - Grid View) ===
elseif ($page === 'cari_dosen') {
    $sql = "SELECT d.*, p.Nama_Prodi, f.Nama_Fakultas 
            FROM Dosen_Pembimbing d 
            LEFT JOIN Prodi p ON d.ID_Prodi = p.ID_Prodi 
            LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
            WHERE 1=1";
    $params = [];

    if ($search) { $sql .= " AND (d.Nama_Dosen LIKE ? OR d.NIDN LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%"]); }
    if ($fak) { $sql .= " AND f.ID_Fakultas = ?"; $params[] = $fak; }
    if ($prodi) { $sql .= " AND d.ID_Prodi = ?"; $params[] = $prodi; }
    if ($skill) { $sql .= " AND d.ID_Dosen IN (SELECT ID_Dosen FROM Dosen_Keahlian WHERE ID_Skill = ?)"; $params[] = $skill; }
    if ($role) { $sql .= " AND d.ID_Dosen IN (SELECT ID_Dosen FROM Dosen_Role WHERE ID_Role = ?)"; $params[] = $role; }

    $sql .= " ORDER BY d.Nama_Dosen ASC LIMIT 30";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    if (empty($data)) { 
        echo '<div class="col-12 text-center py-5">
                <div class="opacity-25 mb-3 text-secondary"><i class="fas fa-search fa-4x"></i></div>
                <h5 class="text-muted fw-bold">Dosen tidak ditemukan</h5>
                <p class="text-muted small">Coba ubah filter atau kata kunci pencarian Anda.</p>
              </div>'; 
        exit; 
    }

    foreach($data as $d) {
        // Avatar Logic
        if (!empty($d['Foto_Profil']) && file_exists($d['Foto_Profil'])) {
            $avatar = "<img src='{$d['Foto_Profil']}?t=".time()."' class='dosen-avatar-img'>";
        } else {
            $initial = strtoupper(substr($d['Nama_Dosen'], 0, 1));
            $avatar = "<div class='dosen-initial'>$initial</div>";
        }

        // Fetch Skills & Roles
        $stmtSkill = $pdo->prepare("SELECT s.Nama_Skill FROM Dosen_Keahlian dk JOIN Skill s ON dk.ID_Skill = s.ID_Skill WHERE dk.ID_Dosen = ? LIMIT 6");
        $stmtSkill->execute([$d['ID_Dosen']]);
        $skills = $stmtSkill->fetchAll(PDO::FETCH_COLUMN);

        $stmtRole = $pdo->prepare("SELECT r.Nama_Role FROM Dosen_Role dr JOIN Role_Tim r ON dr.ID_Role = r.ID_Role WHERE dr.ID_Dosen = ? LIMIT 6");
        $stmtRole->execute([$d['ID_Dosen']]);
        $roles = $stmtRole->fetchAll(PDO::FETCH_COLUMN);

        echo '<div class="col-md-6 col-lg-4 fade-in">
            <div class="dosen-card-wrapper">
                <div class="card-banner-top"></div>
                
                <div class="card-body pt-0 px-4 pb-4 d-flex flex-column">
                    <div class="dosen-avatar-box">
                        '.$avatar.'
                    </div>
                    
                    <div class="text-center mb-3">
                        <h5 class="fw-bold text-dark mb-1">'.htmlspecialchars($d['Nama_Dosen']).'</h5>
                        <div class="text-muted small">
                            <i class="fas fa-graduation-cap me-1 text-primary"></i> '.htmlspecialchars($d['Nama_Prodi'] ?? '-').'
                        </div>
                    </div>

                    <div class="info-box flex-grow-1 d-flex gap-3">
                        <div class="w-50 d-flex flex-column">
                            <small class="text-uppercase text-secondary fw-bold x-small mb-2" style="font-size:0.65rem; letter-spacing:0.5px;">Skills</small>
                            <div class="tag-scroll-area flex-grow-1">';
                                if($skills) {
                                    foreach($skills as $s) {
                                        echo '<span class="badge badge-skill rounded-pill fw-normal me-1 mb-1" style="font-size:0.65rem">'.htmlspecialchars($s).'</span>';
                                    }
                                } else { echo '<small class="text-muted fst-italic" style="font-size:0.7rem">-</small>'; }
        echo '              </div>
                        </div>

                        <div class="w-50 d-flex flex-column border-start ps-3">
                            <small class="text-uppercase text-secondary fw-bold x-small mb-2" style="font-size:0.65rem; letter-spacing:0.5px;">Minat</small>
                            <div class="tag-scroll-area flex-grow-1">';
                                if($roles) {
                                    foreach($roles as $r) {
                                        echo '<span class="badge badge-role rounded-pill fw-normal me-1 mb-1" style="font-size:0.65rem">'.htmlspecialchars($r).'</span>';
                                    }
                                } else { echo '<small class="text-muted fst-italic" style="font-size:0.7rem">-</small>'; }
        echo '              </div>
                        </div>
                    </div>

                    <div class="mt-auto pt-2">
                        <a href="?page=profile_dosen&id='.$d['ID_Dosen'].'" class="btn btn-outline-primary w-100 rounded-pill fw-bold btn-sm py-2">
                            Lihat Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>';
    }
}

// === 5. FETCH TEAMS BY LOMBA (UNTUK POPUP DETAIL) ===
elseif ($page === 'teams_by_lomba') {
    $idLomba = $_GET['id'] ?? 0;
    
    // Ambil tim yang terdaftar di lomba ini
    $sql = "SELECT t.Nama_Tim, m.Nama_Mahasiswa as Ketua, m.NIM 
            FROM Tim t
            JOIN Mahasiswa m ON t.ID_Mahasiswa_Ketua = m.ID_Mahasiswa
            WHERE t.ID_Lomba = ?
            ORDER BY t.ID_Tim DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idLomba]);
    $teams = $stmt->fetchAll();

    if (empty($teams)) {
        echo '<div class="text-center text-muted py-4 small">
                <i class="fas fa-users-slash mb-2 text-secondary opacity-50" style="font-size: 1.5rem;"></i><br>
                Belum ada tim yang terdaftar.
              </div>';
    } else {
        echo '<ul class="list-group list-group-flush">';
        foreach ($teams as $t) {
            echo '<li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom-dashed">
                    <div>
                        <div class="fw-bold text-dark small" style="font-size:0.9rem;">' . htmlspecialchars($t['Nama_Tim']) . '</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-user-circle me-1 text-primary"></i>' . htmlspecialchars($t['Ketua']) . '
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border" style="font-size:0.7rem;">' . htmlspecialchars($t['NIM']) . '</span>
                  </li>';
        }
        echo '</ul>';
    }
}
?>