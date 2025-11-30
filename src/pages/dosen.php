<?php
$filterProdi = $_GET['prodi'] ?? '';
$search = $_GET['q'] ?? '';

$sql = "SELECT d.*, p.Nama_Prodi FROM Dosen_Pembimbing d 
        LEFT JOIN Prodi p ON d.ID_Prodi = p.ID_Prodi 
        WHERE 1=1";
$params = [];

if ($filterProdi) { $sql .= " AND d.ID_Prodi = ?"; $params[] = $filterProdi; }
if ($search) { $sql .= " AND (d.Nama_Dosen LIKE ? OR d.NIDN LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql .= " ORDER BY d.Nama_Dosen ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dosenList = $stmt->fetchAll();

$prodiList = $pdo->query("SELECT * FROM Prodi")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary"><i class="fas fa-chalkboard-teacher me-2"></i> Data Dosen</h2>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold"><i class="fas fa-filter me-2"></i>Filter</div>
            <div class="card-body">
                <form method="GET">
                    <input type="hidden" name="page" value="dosen">
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Cari Nama/NIDN</label>
                        <input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Prodi</label>
                        <select name="prodi" class="form-select form-select-sm">
                            <option value="">Semua Prodi</option>
                            <?php foreach($prodiList as $p): ?>
                                <option value="<?= $p['ID_Prodi'] ?>" <?= $filterProdi == $p['ID_Prodi'] ? 'selected' : '' ?>><?= $p['Nama_Prodi'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                    <a href="?page=dosen" class="btn btn-outline-secondary btn-sm w-100 mt-2">Reset</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">NIDN</th>
                            <th>Nama Dosen</th>
                            <th>Prodi</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($dosenList as $d): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?= $d['NIDN'] ?></td>
                            <td><?= htmlspecialchars($d['Nama_Dosen']) ?></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info border border-info"><?= $d['Nama_Prodi'] ?></span></td>
                            <td><?= $d['Email'] ?? '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>