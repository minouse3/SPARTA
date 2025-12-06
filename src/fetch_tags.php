<?php
require_once 'config.php';

$type = $_GET['type'] ?? 'skill'; // 'skill' atau 'role'
$query = $_GET['q'] ?? '';

if (strlen($query) < 1) { echo json_encode([]); exit; }

$results = [];

if ($type === 'skill') {
    $stmt = $pdo->prepare("SELECT Nama_Skill as name FROM Skill WHERE Nama_Skill LIKE ? LIMIT 10");
} else {
    $stmt = $pdo->prepare("SELECT Nama_Role as name FROM Role_Tim WHERE Nama_Role LIKE ? LIMIT 10");
}

$stmt->execute(["%$query%"]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>