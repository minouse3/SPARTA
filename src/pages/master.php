<?php
// Handle Tambah Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['type'] === 'kategori') {
        $stmt = $pdo->prepare("INSERT INTO Kategori_Lomba (Nama_Kategori) VALUES (?)");
        $stmt->execute([$_POST['nama']]);
    } elseif ($_POST['type'] === 'keahlian') {
        $stmt = $pdo->prepare("INSERT INTO Keahlian (Nama_Keahlian) VALUES (?)");
        $stmt->execute([$_POST['nama']]);
    } elseif ($_POST['type'] === 'penyelenggara') {
        $stmt = $pdo->prepare("INSERT INTO Jenis_Penyelenggara (Nama_Jenis, Bobot_Poin) VALUES (?, ?)");
        $stmt->execute([$_POST['nama'], $_POST['bobot']]);
    }
    echo "<div class='alert alert-success'>Data berhasil ditambahkan!</div>";
}

$kategoris = $pdo->query("SELECT * FROM Kategori_Lomba")->fetchAll();
$keahlians = $pdo->query("SELECT * FROM Keahlian")->fetchAll();
$penyelenggaras = $pdo->query("SELECT * FROM Jenis_Penyelenggara")->fetchAll();
?>

<h2 class="mb-4 fw-bold text-dark"><i class="fas fa-cogs me-2"></i> Master Data & Filter</h2>

<div class="row">
    <!-- Kategori Lomba -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-primary text-white">Kategori Lomba</div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3" style="max-height: 200px; overflow-y:auto;">
                    <?php foreach($kategoris as $k): ?>
                        <li class="list-group-item py-1"><?= $k['Nama_Kategori'] ?></li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" class="d-flex">
                    <input type="hidden" name="type" value="kategori">
                    <input type="text" name="nama" class="form-control form-control-sm me-2" placeholder="Baru..." required>
                    <button class="btn btn-sm btn-primary"><i class="fas fa-plus"></i></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Keahlian -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-success text-white">Skill / Keahlian</div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3" style="max-height: 200px; overflow-y:auto;">
                    <?php foreach($keahlians as $k): ?>
                        <li class="list-group-item py-1"><?= $k['Nama_Keahlian'] ?></li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" class="d-flex">
                    <input type="hidden" name="type" value="keahlian">
                    <input type="text" name="nama" class="form-control form-control-sm me-2" placeholder="Baru..." required>
                    <button class="btn btn-sm btn-success"><i class="fas fa-plus"></i></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Jenis Penyelenggara (Bobot) -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-warning text-dark">Jenis Penyelenggara</div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3" style="max-height: 200px; overflow-y:auto;">
                    <?php foreach($penyelenggaras as $p): ?>
                        <li class="list-group-item py-1 d-flex justify-content-between">
                            <span><?= $p['Nama_Jenis'] ?></span>
                            <span class="badge bg-secondary">x<?= $p['Bobot_Poin'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST">
                    <input type="hidden" name="type" value="penyelenggara">
                    <div class="input-group input-group-sm">
                        <input type="text" name="nama" class="form-control" placeholder="Jenis..." required>
                        <input type="number" step="0.1" name="bobot" class="form-control" placeholder="Bobot (1.0)" style="max-width: 80px;" required>
                        <button class="btn btn-warning"><i class="fas fa-plus"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>