<?php
$search = $_GET['q'] ?? '';

$sql = "SELECT m.*, p.Nama_Prodi, f.Nama_Fakultas 
        FROM Mahasiswa m 
        LEFT JOIN Prodi p ON m.ID_Prodi = p.ID_Prodi 
        LEFT JOIN Fakultas f ON p.ID_Fakultas = f.ID_Fakultas
        WHERE m.Nama_Mahasiswa LIKE ? OR m.NIM LIKE ?
        ORDER BY m.Total_Poin DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$ranks = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-primary" style="font-family: 'Roboto Slab', serif;">
            <i class="fas fa-trophy text-warning me-2"></i>Leaderboard
        </h2>
        <p class="text-muted">Peringkat mahasiswa berdasarkan akumulasi pencapaian lomba.</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="GET" class="mb-4">
            <input type="hidden" name="page" value="ranking">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Cari Nama atau NIM..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary" type="submit">Cari</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4">Rank</th>
                        <th>Mahasiswa</th>
                        <th>Fakultas</th>
                        <th>Prodi</th>
                        <th class="text-end pe-4">Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; foreach($ranks as $r): ?>
                    <tr onclick="window.location='?page=profile&id=<?= $r['ID_Mahasiswa'] ?>'" style="cursor: pointer;" class="hover-shadow transition">
                        <td class="ps-4" style="width: 60px;">
                            <?php if($no==1): ?><i class="fas fa-crown text-warning fa-lg"></i>
                            <?php elseif($no==2): ?><i class="fas fa-medal text-secondary fa-lg"></i>
                            <?php elseif($no==3): ?><i class="fas fa-medal text-danger fa-lg"></i>
                            <?php else: ?>
                                <span class="fw-bold text-muted ms-1">#<?= $no ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <?php if(!empty($r['Foto_Profil']) && file_exists($r['Foto_Profil'])): ?>
                                        <img src="<?= $r['Foto_Profil'] ?>" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center border" style="width: 40px; height: 40px; font-weight: bold;">
                                            <?= strtoupper(substr($r['Nama_Mahasiswa'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($r['Nama_Mahasiswa']) ?></div>
                                    <small class="text-muted"><?= $r['NIM'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($r['Nama_Fakultas'] ?? '-') ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $r['Nama_Prodi'] ?></span></td>
                        <td class="text-end pe-4">
                            <h5 class="mb-0 fw-bold text-primary"><?= number_format($r['Total_Poin']) ?></h5>
                        </td>
                    </tr>
                    <?php $no++; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>