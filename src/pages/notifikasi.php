<?php
// FILE: src/pages/notifikasi.php (Dual Mode: Undangan & Permintaan)

if (!isset($_SESSION['user_id'])) exit;

$userId = $_SESSION['user_id'];
$roleUser = $_SESSION['role']; // 'mahasiswa' atau 'dosen'

// --- LOGIKA PROSES (TERIMA/TOLAK) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $idInv = $_POST['id_invitasi'];
    $keputusan = $_POST['action']; // 'accept' or 'reject'
    $tipeNotif = $_POST['tipe_notif']; // 'incoming_invite' (Saya diundang) atau 'incoming_request' (Orang request ke tim saya)

    try {
        $pdo->beginTransaction();

        // Ambil Data Invitasi
        $stmt = $pdo->prepare("SELECT * FROM Invitasi WHERE ID_Invitasi = ? AND Status = 'Pending'");
        $stmt->execute([$idInv]);
        $inv = $stmt->fetch();

        if ($inv) {
            // Validasi Hak Akses
            // Jika Incoming Invite: Penerima harus SAYA
            // Jika Incoming Request: Penerima harus SAYA (Ketua)
            if ($inv['ID_Penerima'] != $userId) {
                throw new Exception("Akses tidak valid.");
            }

            if ($keputusan === 'accept') {
                $pdo->prepare("UPDATE Invitasi SET Status = 'Diterima' WHERE ID_Invitasi = ?")->execute([$idInv]);

                // LOGIKA MASUK TIM
                // 1. Jika ini 'incoming_invite' (Saya diundang ke tim) -> Masukkan SAYA ke tim
                // 2. Jika ini 'incoming_request' (Orang lain request ke tim saya) -> Masukkan DIA (Pengirim) ke tim
                
                $idMemberBaru = ($tipeNotif == 'incoming_invite') ? $inv['ID_Penerima'] : $inv['ID_Pengirim'];
                $roleMember = ($tipeNotif == 'incoming_invite') ? $roleUser : 'mahasiswa'; // Pengirim request pasti mahasiswa

                if ($roleMember === 'mahasiswa') {
                    $pdo->prepare("INSERT INTO Keanggotaan_Tim (ID_Tim, ID_Mahasiswa, Peran, Status) VALUES (?, ?, 'Anggota', 'Diterima')")
                        ->execute([$inv['ID_Tim'], $idMemberBaru]);
                } elseif ($roleMember === 'dosen') {
                    $pdo->prepare("UPDATE Tim SET ID_Dosen_Pembimbing = ? WHERE ID_Tim = ?")
                        ->execute([$idMemberBaru, $inv['ID_Tim']]);
                }
                $msg = "Berhasil menerima!";
            } else {
                // Reject
                $pdo->prepare("UPDATE Invitasi SET Status = 'Ditolak' WHERE ID_Invitasi = ?")->execute([$idInv]);
                $msg = "Permintaan ditolak.";
            }
            $pdo->commit();
            echo "<div class='alert alert-success border-0 shadow-sm'>$msg</div>";
        } else {
            $pdo->rollBack();
            echo "<div class='alert alert-danger'>Data tidak valid.</div>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// --- AMBIL DATA NOTIFIKASI ---

// 1. UNDANGAN MASUK (Saya diundang orang lain)
// Kondisi: Penerima = Saya, Pengirim != Saya (Logic standar)
$sqlInvite = "SELECT i.*, t.Nama_Tim, m.Nama_Mahasiswa as PengirimName, 'incoming_invite' as Tipe
              FROM Invitasi i
              JOIN Tim t ON i.ID_Tim = t.ID_Tim
              JOIN Mahasiswa m ON i.ID_Pengirim = m.ID_Mahasiswa
              WHERE i.ID_Penerima = ? AND i.Status = 'Pending'
              AND t.ID_Mahasiswa_Ketua != ? -- Pastikan bukan request tim sendiri
              ORDER BY i.ID_Invitasi DESC";
$stmtInv = $pdo->prepare($sqlInvite);
$stmtInv->execute([$userId, $userId]);
$myInvites = $stmtInv->fetchAll();

// 2. PERMINTAAN BERGABUNG (Orang lain ingin masuk tim yang SAYA ketuai)
// Kondisi: Penerima = Saya (Sebagai Ketua), tapi Pengirim bukan Saya.
// (Secara teknis sama dengan query di atas karena schema kita 'ID_Penerima' selalu target action)
// TAPI: Kita bedakan query untuk konteks UI yang lebih jelas.
// Request Join: Pengirim = Mahasiswa A, Penerima = Ketua B. Ketua B melihat ini.
$sqlReq = "SELECT i.*, t.Nama_Tim, m.Nama_Mahasiswa as PengirimName, m.Foto_Profil, m.NIM, 'incoming_request' as Tipe
           FROM Invitasi i
           JOIN Tim t ON i.ID_Tim = t.ID_Tim
           JOIN Mahasiswa m ON i.ID_Pengirim = m.ID_Mahasiswa
           WHERE i.ID_Penerima = ? AND i.Status = 'Pending'
           AND i.Tipe_Penerima = 'mahasiswa' -- Ketua pasti mahasiswa
           AND t.ID_Mahasiswa_Ketua = ? -- Validasi double check saya ketua
           ORDER BY i.ID_Invitasi DESC";
$stmtReq = $pdo->prepare($sqlReq);
$stmtReq->execute([$userId, $userId]);
$myRequests = $stmtReq->fetchAll();

// Hapus duplikasi tampilan (karena query 1 bisa mengambil query 2 secara teknis jika logic database invitasi request join menggunakan id penerima = ketua)
// Solusi: Filter array $myInvites agar tidak memuat item yang ada di $myRequests
$reqIds = array_column($myRequests, 'ID_Invitasi');
$myInvites = array_filter($myInvites, function($val) use ($reqIds) {
    return !in_array($val['ID_Invitasi'], $reqIds);
});
?>

<div class="d-flex align-items-center mb-4">
    <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Notifikasi</h3>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-envelope-open-text me-2"></i>Undangan Masuk</h6>
                <small class="text-muted">Tim yang mengundang Anda bergabung.</small>
            </div>
            <div class="list-group list-group-flush">
                <?php if(empty($myInvites)): ?>
                    <div class="list-group-item text-center text-muted py-5 border-0">
                        <i class="far fa-bell-slash fa-2x mb-2 opacity-25"></i><br>Tidak ada undangan baru.
                    </div>
                <?php else: ?>
                    <?php foreach($myInvites as $inv): ?>
                    <div class="list-group-item p-3 border-light">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-primary bg-opacity-10 text-primary mb-1">Invitation</span>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($inv['Nama_Tim']) ?></h6>
                                <small class="text-muted">Dari: <?= htmlspecialchars($inv['PengirimName']) ?></small>
                            </div>
                            <small class="text-muted" style="font-size:0.7rem"><?= date('d M', strtotime($inv['Tanggal_Kirim'])) ?></small>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <form method="POST" class="w-100">
                                <input type="hidden" name="id_invitasi" value="<?= $inv['ID_Invitasi'] ?>">
                                <input type="hidden" name="tipe_notif" value="incoming_invite">
                                <button type="submit" name="action" value="accept" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold">Terima</button>
                            </form>
                            <form method="POST" class="w-100">
                                <input type="hidden" name="id_invitasi" value="<?= $inv['ID_Invitasi'] ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm w-100 rounded-pill">Tolak</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h6 class="mb-0 fw-bold text-success"><i class="fas fa-user-plus me-2"></i>Permintaan Masuk</h6>
                <small class="text-muted">Mahasiswa yang ingin bergabung ke tim Anda.</small>
            </div>
            <div class="list-group list-group-flush">
                <?php if(empty($myRequests)): ?>
                    <div class="list-group-item text-center text-muted py-5 border-0">
                        <i class="fas fa-inbox fa-2x mb-2 opacity-25"></i><br>Belum ada permintaan join.
                    </div>
                <?php else: ?>
                    <?php foreach($myRequests as $req): ?>
                    <div class="list-group-item p-3 border-light">
                        <div class="d-flex align-items-center mb-3">
                            <?php 
                                $foto = getFotoMhs($req['NIM'], $req['Foto_Profil']);
                                if($foto) echo "<img src='$foto' class='rounded-circle me-3' style='width:45px;height:45px;object-fit:cover'>";
                                else echo "<div class='rounded-circle bg-light d-flex align-items-center justify-content-center me-3 fw-bold text-secondary' style='width:45px;height:45px'>".substr($req['PengirimName'],0,1)."</div>";
                            ?>
                            <div>
                                <h6 class="fw-bold mb-0"><?= htmlspecialchars($req['PengirimName']) ?></h6>
                                <small class="text-muted">Ingin join ke: <span class="fw-bold text-dark"><?= htmlspecialchars($req['Nama_Tim']) ?></span></small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" class="flex-grow-1">
                                <input type="hidden" name="id_invitasi" value="<?= $req['ID_Invitasi'] ?>">
                                <input type="hidden" name="tipe_notif" value="incoming_request">
                                <button type="submit" name="action" value="accept" class="btn btn-success btn-sm w-100 rounded-pill fw-bold">Terima</button>
                            </form>
                            <form method="POST" class="flex-grow-1">
                                <input type="hidden" name="id_invitasi" value="<?= $req['ID_Invitasi'] ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-outline-secondary btn-sm w-100 rounded-pill">Tolak</button>
                            </form>
                            <a href="?page=profile&id=<?= $req['ID_Pengirim'] ?>" class="btn btn-light btn-sm rounded-circle" title="Lihat Profil"><i class="fas fa-eye"></i></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>