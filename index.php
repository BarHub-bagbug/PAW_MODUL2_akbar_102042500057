<?php
require 'koneksi.php';

// Hitung Ringkasan Data
$q1 = $conn->query("SELECT COUNT(*) as total FROM mata_kuliah");
$total_mk = $q1->fetch_assoc()['total'];

$q2 = $conn->query("SELECT COUNT(*) as total FROM materi");
$total_materi = $q2->fetch_assoc()['total'];

$q3 = $conn->query("SELECT COUNT(*) as total FROM tugas");
$total_tugas = $q3->fetch_assoc()['total'];

// Ambil Daftar Mata Kuliah
$stmt = $conn->prepare("SELECT id, kode, nama, dosen, semester FROM mata_kuliah ORDER BY kode ASC");
$stmt->execute();
$mk_list = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>LMS EAD - Beranda</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Learning Management System</h1>
    <nav>
        <a href="index.php">Beranda</a>
        <a href="mata_kuliah.php">Mata Kuliah</a>
        <a href="materi.php">Materi</a>
        <a href="tugas.php">Tugas</a>
    </nav>
</header>
<main>
    <h2>Ringkasan LMS</h2>
    <div class="grid-stats">
        <div class="card-stat">
            <h3><?= $total_mk ?></h3>
            <p>Mata Kuliah</p>
        </div>
        <div class="card-stat">
            <h3><?= $total_materi ?></h3>
            <p>Materi</p>
        </div>
        <div class="card-stat">
            <h3><?= $total_tugas ?></h3>
            <p>Tugas</p>
        </div>
    </div>

    <h3>Mata Kuliah Tersedia</h3>
    <ul>
        <?php while($row = $mk_list->fetch_assoc()): ?>
            <li><strong><?= e($row['kode']) ?></strong> - <?= e($row['nama']) ?> (Semester <?= e($row['semester']) ?>)</li>
        <?php endwhile; ?>
    </ul>
</main>
</body>
</html>