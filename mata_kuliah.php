<?php
require 'koneksi.php';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// PROCESS: POST DATA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF Token Invalid');
    }

    $act = $_POST['act'] ?? '';

    // CREATE & UPDATE
    if ($act === 'save') {
        $id       = (int)($_POST['id'] ?? 0);
        $kode     = trim($_POST['kode'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $dosen    = trim($_POST['dosen'] ?? '');
        $semester = (int)($_POST['semester'] ?? 0);

        // Validasi Input
        if (!preg_match('/^[A-Z0-9\-]{3,20}$/', $kode)) {
            $error = 'Kode harus 3-20 karakter (Huruf Kapital, Angka, dan "-").';
        } elseif (strlen($nama) < 3 || strlen($nama) > 100) {
            $error = 'Nama mata kuliah harus 3-100 karakter.';
        } elseif (strlen($dosen) < 3 || strlen($dosen) > 100) {
            $error = 'Nama dosen harus 3-100 karakter.';
        } elseif ($semester < 1 || $semester > 14) {
            $error = 'Semester harus antara 1 - 14.';
        } else {
            if ($id === 0) {
                // Check Unique Kode
                $chk = $conn->prepare("SELECT id FROM mata_kuliah WHERE kode = ?");
                $chk->bind_param("s", $kode);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    $error = 'Kode mata kuliah sudah digunakan.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO mata_kuliah (kode, nama, dosen, semester) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("sssi", $kode, $nama, $dosen, $semester);
                    $stmt->execute();
                    $_SESSION['flash_msg'] = 'Mata kuliah berhasil ditambahkan.';
                    header('Location: mata_kuliah.php');
                    exit;
                }
            } else {
                // Update
                $chk = $conn->prepare("SELECT id FROM mata_kuliah WHERE kode = ? AND id != ?");
                $chk->bind_param("si", $kode, $id);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    $error = 'Kode mata kuliah sudah digunakan oleh data lain.';
                } else {
                    $stmt = $conn->prepare("UPDATE mata_kuliah SET kode=?, nama=?, dosen=?, semester=? WHERE id=?");
                    $stmt->bind_param("sssii", $kode, $nama, $dosen, $semester, $id);
                    $stmt->execute();
                    if ($stmt->affected_rows === 0) {
                        $error = 'Tidak ada perubahan data yang disimpan.';
                    } else {
                        $_SESSION['flash_msg'] = 'Mata kuliah berhasil diperbarui.';
                        header('Location: mata_kuliah.php');
                        exit;
                    }
                }
            }
        }
    }

    // DELETE
    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("DELETE FROM mata_kuliah WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['flash_msg'] = 'Mata kuliah berhasil dihapus.';
            header('Location: mata_kuliah.php');
            exit;
        } catch (mysqli_sql_exception $e) {
            $error = 'Data belum tersimpan. Mata kuliah masih memiliki materi atau tugas terkait.';
        }
    }
}

if (isset($_SESSION['flash_msg'])) {
    $success = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Mata Kuliah</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Kelola Mata Kuliah</h1>
    <nav>
        <a href="index.php">Beranda</a>
        <a href="mata_kuliah.php">Mata Kuliah</a>
        <a href="materi.php">Materi</a>
        <a href="tugas.php">Tugas</a>
    </nav>
</header>
<main>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): 
        $data = ['id' => 0, 'kode' => '', 'nama' => '', 'dosen' => '', 'semester' => ''];
        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc() ?? $data;
        }
    ?>
        <h3><?= $action === 'add' ? 'Tambah' : 'Edit' ?> Mata Kuliah</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="act" value="save">
            <input type="hidden" name="id" value="<?= $data['id'] ?>">

            <div class="form-group">
                <label>Kode Mata Kuliah</label>
                <input type="text" name="kode" value="<?= e($data['kode']) ?>" required>
            </div>
            <div class="form-group">
                <label>Nama Mata Kuliah</label>
                <input type="text" name="nama" value="<?= e($data['nama']) ?>" required>
            </div>
            <div class="form-group">
                <label>Dosen Pengampu</label>
                <input type="text" name="dosen" value="<?= e($data['dosen']) ?>" required>
            </div>
            <div class="form-group">
                <label>Semester</label>
                <input type="number" name="semester" value="<?= e($data['semester']) ?>" min="1" max="14" required>
            </div>
            <button class="btn" type="submit">Simpan Data</button>
            <a href="mata_kuliah.php" class="btn btn-secondary">Batal</a>
        </form>

    <?php elseif ($action === 'delete_confirm'): 
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $mk = $stmt->get_result()->fetch_assoc();
    ?>
        <h3>Konfirmasi Hapus</h3>
        <p>Apakah Anda yakin ingin menghapus mata kuliah <strong><?= e($mk['nama'] ?? '') ?></strong>?</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="act" value="delete">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-danger">Ya, Hapus Data</button>
            <a href="mata_kuliah.php" class="btn btn-secondary">Batal</a>
        </form>

    <?php else: 
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $param = "%$q%";
            $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE kode LIKE ? OR nama LIKE ? ORDER BY kode ASC");
            $stmt->bind_param("ss", $param, $param);
        } else {
            $stmt = $conn->prepare("SELECT * FROM mata_kuliah ORDER BY kode ASC");
        }
        $stmt->execute();
        $res = $stmt->get_result();
    ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <a href="mata_kuliah.php?action=add" class="btn">Tambah Mata Kuliah</a>
            <form method="GET" class="filter-box">
                <input type="text" name="q" placeholder="Cari Kode/Nama..." value="<?= e($q) ?>">
                <button type="submit" class="btn">Cari</button>
                <a href="mata_kuliah.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Dosen</th>
                    <th>Semester</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res->num_rows === 0): ?>
                    <tr><td colspan="5" style="text-align:center;">Data tidak ditemukan.</td></tr>
                <?php else: ?>
                    <?php while($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($row['kode']) ?></td>
                            <td><?= e($row['nama']) ?></td>
                            <td><?= e($row['dosen']) ?></td>
                            <td><?= e($row['semester']) ?></td>
                            <td>
                                <a href="mata_kuliah.php?action=edit&id=<?= $row['id'] ?>" class="btn">Edit</a>
                                <a href="mata_kuliah.php?action=delete_confirm&id=<?= $row['id'] ?>" class="btn btn-danger">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
</body>
</html>