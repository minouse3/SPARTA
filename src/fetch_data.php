<?php
// FILE: API Fetch Data Universal (Mahasiswa, Dosen, Ranking)
require_once 'config.php';

$page   = $_GET['page'] ?? ''; // 'mahasiswa', 'dosen', 'ranking'
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
    
    // Filter by Skill (Subquery)
    if ($skill) {
        $sql .= " AND m.ID_Mahasiswa IN (SELECT ID_Mahasiswa FROM Mahasiswa_Skill WHERE ID_Skill = ?)";
        $params[] = $skill;
    }
    // Filter by Role (Subquery)
    if ($role) {
        $sql .= " AND m.ID_Mahasiswa IN (SELECT ID_Mahasiswa FROM Mahasiswa_Role WHERE ID_Role = ?)";
        $params[] = $role;
    }

    $sql .= " ORDER BY m.Nama_Mahasiswa ASC LIMIT 50"; // Limit agar ringan
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    if (empty($data)) { echo '<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada data ditemukan.</td></tr>'; exit; }

    foreach($data as $m) {
        $foto = getFotoMhs($m['NIM'], $m['Foto_Profil']);
        $imgTag = $foto ? "<img src='$foto?t=".time()."' class='rounded-circle' width='30' height='30'>" 
                        : "<div class='rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center' style='width:30px;height:30px;font-size:0.8rem'>".substr($m['Nama_Mahasiswa'],0,1)."</div>";
        
        echo "<tr>
            <td class='ps-3'><input type='checkbox' name='ids[]' value='{$m['ID_Mahasiswa']}' class='form-check-input'></td>
            <td>
                <div class='d-flex align-items-center'>
                    <div class='me-2'>$imgTag</div>
                    <div>
                        <div class='fw-bold'>".htmlspecialchars($m['Nama_Mahasiswa'])."</div>
                        <small class='text-muted'>".htmlspecialchars($m['NIM'])."</small>
                    </div>
                </div>
            </td>
            <td>".htmlspecialchars($m['Email'])."</td>
            <td>".htmlspecialchars($m['Nama_Fakultas']??'-')."</td>
            <td>".htmlspecialchars($m['Nama_Prodi']??'-')."</td>
            <td class='text-end pe-4'>
                <a href='?page=profile&id={$m['ID_Mahasiswa']}' class='btn btn-sm btn-outline-info' title='Lihat'><i class='fas fa-eye'></i></a>
                <a href='?page=mahasiswa&view=detail&id={$m['ID_Mahasiswa']}' class='btn btn-sm btn-outline-warning' title='Edit'><i class='fas fa-edit'></i></a>
                <button type='button' class='btn btn-sm btn-outline-danger' onclick='deleteSingle({$m['ID_Mahasiswa']})'><i class='fas fa-trash'></i></button>
            </td>
        </tr>";
    }
}

// === 2. FETCH DATA DOSEN ===
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
    
    // Filter Skill untuk Dosen (Jika ada tabel Dosen_Keahlian)
    if ($skill) {
        $sql .= " AND d.ID_Dosen IN (SELECT ID_Dosen FROM Dosen_Keahlian WHERE ID_Skill = ?)";
        $params[] = $skill;
    }

    $sql .= " ORDER BY d.Nama_Dosen ASC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    if (empty($data)) { echo '<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada data ditemukan.</td></tr>'; exit; }

    foreach($data as $d) {
        echo "<tr>
            <td class='ps-3'><input type='checkbox' name='ids[]' value='{$d['ID_Dosen']}' class='form-check-input'></td>
            <td class='fw-bold'>{$d['NIDN']}</td>
            <td>
                <div class='fw-bold'>".htmlspecialchars($d['Nama_Dosen'])."</div>
                <small class='text-muted'>".htmlspecialchars($d['Email'])."</small>
            </td>
            <td>".htmlspecialchars($d['Nama_Fakultas']??'-')."</td>
            <td><span class='badge bg-info bg-opacity-10 text-info border border-info'>".htmlspecialchars($d['Nama_Prodi']??'-')."</span></td>
            <td class='text-end pe-4'>
                <a href='?page=profile_dosen&id={$d['ID_Dosen']}' class='btn btn-sm btn-outline-info'><i class='fas fa-eye'></i></a>
                <button type='button' class='btn btn-sm btn-outline-danger' onclick='deleteSingle({$d['ID_Dosen']})'><i class='fas fa-trash'></i></button>
            </td>
        </tr>";
    }
}

// === 3. FETCH LEADERBOARD ===
elseif ($page === 'ranking') {
    $sql = "SELECT m.*, p.Nama_Prodi, f.Nama_Fakultas 
            FROM Mahasiswa m 
            LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
            LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
            WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND (m.Nama_Mahasiswa LIKE ? OR m.NIM LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%"]);
    }
    if ($fak) { $sql .= " AND f.ID_Fakultas = ?"; $params[] = $fak; }
    if ($prodi) { $sql .= " AND m.ID_Prodi = ?"; $params[] = $prodi; }
    if ($skill) { $sql .= " AND m.ID_Mahasiswa IN (SELECT ID_Mahasiswa FROM Mahasiswa_Skill WHERE ID_Skill = ?)"; $params[] = $skill; }
    if ($role) { $sql .= " AND m.ID_Mahasiswa IN (SELECT ID_Mahasiswa FROM Mahasiswa_Role WHERE ID_Role = ?)"; $params[] = $role; }

    $sql .= " ORDER BY m.Total_Poin DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    if (empty($data)) { echo '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data.</td></tr>'; exit; }

    $no = 1;
    foreach($data as $r) {
        $foto = getFotoMhs($r['NIM'], $r['Foto_Profil']);
        $img = $foto ? "<img src='$foto?t=".time()."' class='rounded-circle border' width='40' height='40'>" 
                     : "<div class='rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center border' style='width:40px;height:40px;font-weight:bold'>".substr($r['Nama_Mahasiswa'],0,1)."</div>";
        
        $medal = ($no==1) ? '<i class="fas fa-crown text-warning fa-lg"></i>' : (($no==2) ? '<i class="fas fa-medal text-secondary fa-lg"></i>' : (($no==3) ? '<i class="fas fa-medal text-danger fa-lg"></i>' : "<span class='fw-bold text-muted ms-1'>#$no</span>"));

        echo "<tr onclick=\"window.location='?page=profile&id={$r['ID_Mahasiswa']}'\" style='cursor:pointer' class='hover-shadow transition'>
            <td class='ps-4' style='width:60px'>$medal</td>
            <td>
                <div class='d-flex align-items-center'>
                    <div class='me-3'>$img</div>
                    <div>
                        <div class='fw-bold text-dark'>".htmlspecialchars($r['Nama_Mahasiswa'])."</div>
                        <small class='text-muted'>".htmlspecialchars($r['NIM'])."</small>
                    </div>
                </div>
            </td>
            <td>".htmlspecialchars($r['Nama_Fakultas']??'-')."</td>
            <td><span class='badge bg-light text-dark border'>".htmlspecialchars($r['Nama_Prodi']??'-')."</span></td>
            <td class='text-end pe-4'><h5 class='mb-0 fw-bold text-primary'>".number_format($r['Total_Poin'])."</h5></td>
        </tr>";
        $no++;
    }
}
?>