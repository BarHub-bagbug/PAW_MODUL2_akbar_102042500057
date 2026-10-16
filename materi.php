<?php
require 'koneksi.php';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF Token Invalid');
    }

    $act = $_POST['act'] ?? '';

    if ($act === 'save') {
        $id             = (int)($_POST['id'] ?? 0);
        $mata_kuliah_id = (int)($_POST['mata_kuliah_id'] ?? 0);
        $judul          = trim($_POST['judul'] ?? '');
        $isi            = trim($_POST['isi'] ?? '');
        $tautan         = trim($_POST['tautan'] ?? '');

        // Validasi
        if ($mata_kuliah_id <= 0) {
            $error = 'Pilih mata kuliah yang valid.';
        } elseif (strlen($judul) < 3 || strlen($judul) > 150) {
            $error = 'Judul materi harus 3-150 karakter.';
        } elseif (strlen($isi) < 3 || strlen($isi) > 2000) {
            $error = 'Isi materi harus 3-2000 karakter.';
        } elseif (!preg_match('/^https?:\/\/.+$/i', $tautan)) {
            $error = 'Gunakan tautan http:// atau https:// yang valid.';
        } else {
            if ($id === 0) {
                $stmt = $conn->prepare("INSERT INTO materi (mata_kuliah_id, judul, isi, tautan) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $mata_kuliah_id, $judul, $isi, $tautan);
                $stmt->execute();
                $_SESSION['flash_msg'] = 'Materi berhasil ditambahkan.';
            } else {
                $stmt = $conn->prepare("UPDATE materi SET mata_kuliah_id=?, judul=?, isi=?, tautan=? WHERE id=?");
                $stmt->bind_param("isssi", $mata_kuliah_id, $judul, $isi, $tautan, $id);
                $stmt->execute();
                if ($stmt->affected_rows === 0) {
                    $error = 'Tidak ada perubahan data yang disimpan.';
                } else {
                    $_SESSION['flash_msg'] = 'Materi berhasil diperbarui.';
                }
            }
            if (!$error) {
                header('Location: materi.php');
                exit;
            }
        }
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM materi WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['flash_msg'] = 'Materi berhasil dihapus.';
        header('Location: materi.php');
        exit;
    }
}

if (isset($_SESSION['flash_msg'])) {
    $success = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

$mk_options = $conn->query("SELECT id, kode, nama FROM mata_kuliah ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Materi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Kelola Materi</h1>
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
        $data = ['id' => 0, 'mata_kuliah_id' => '', 'judul' => '', 'isi' => '', 'tautan' => ''];
        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $conn->prepare("SELECT * FROM materi WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc() ?? $data;
        }
    ?>
        <h3><?= $action === 'add' ? 'Tambah' : 'Edit' ?> Materi</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="act" value="save">
            <input type="hidden" name="id" value="<?= $data['id'] ?>">

            <div class="form-group">
                <label>Mata Kuliah</label>
                <select name="mata_kuliah_id" required>
                    <option value="">-- Pilih Mata Kuliah --</option>
                    <?php while($mk = $mk_options->fetch_assoc()): ?>
                        <option value="<?= $mk['id'] ?>" <?= $data['mata_kuliah_id'] == $mk['id'] ? 'selected' : '' ?>>
                            <?= e($mk['kode']) ?> - <?= e($mk['nama']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Judul Materi</label>
                <input type="text" name="judul" value="<?= e($data['judul']) ?>" required>
            </div>
            <div class="form-group">
                <label>Isi Materi</label>
                <textarea name="isi" rows="5" required><?= e($data['isi']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Tautan Referensi (Harus diawali http:// atau https://)</label>
                <input type="text" name="tautan" value="<?= e($data['tautan']) ?>" required>
            </div>
            <button class="btn" type="submit">Simpan Data</button>
            <a href="materi.php" class="btn btn-secondary">Batal</a>
        </form>

    <?php elseif ($action === 'detail'): 
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conn->prepare("SELECT m.*, mk.kode, mk.nama as nama_mk FROM materi m JOIN mata_kuliah mk ON m.mata_kuliah_id = mk.id WHERE m.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $detail = $stmt->get_result()->fetch_assoc();
    ?>
        <h3>Detail Materi</h3>
        <p><strong>Mata Kuliah:</strong> <?= e($detail['kode']) ?> - <?= e($detail['nama_mk']) ?></p>
        <p><strong>Judul:</strong> <?= e($detail['judul']) ?></p>
        <p><strong>Isi:</strong></p>
        <p><?= nl2br(e($detail['isi'])) ?></p>
        <p><strong>Tautan:</strong> <a href="<?= e($detail['tautan']) ?>" target="_blank"><?= e($detail['tautan']) ?></a></p>
        <a href="materi.php" class="btn btn-secondary">Kembali</a>

    <?php elseif ($action === 'delete_confirm'): 
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conn->prepare("SELECT judul FROM materi WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $mat = $stmt->get_result()->fetch_assoc();
    ?>
        <h3>Konfirmasi Hapus</h3>
        <p>Apakah Anda yakin ingin menghapus materi <strong><?= e($mat['judul'] ?? '') ?></strong>?</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="act" value="delete">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-danger">Ya, Hapus Data</button>
            <a href="materi.php" class="btn btn-secondary">Batal</a>
        </form>

    <?php else: 
        $q = trim($_GET['q'] ?? '');
        $filter_mk = (int)($_GET['filter_mk'] ?? 0);

        $sql = "SELECT m.*, mk.kode, mk.nama as nama_mk FROM materi m JOIN mata_kuliah mk ON m.mata_kuliah_id = mk.id WHERE 1=1";
        $params = [];
        $types = "";

        if ($q !== '') {
            $sql .= " AND m.judul LIKE ?";
            $params[] = "%$q%";
            $types .= "s";
        }
        if ($filter_mk > 0) {
            $sql .= " AND m.mata_kuliah_id = ?";
            $params[] = $filter_mk;
            $types .= "i";
        }
        $sql .= " ORDER BY m.id DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
    ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <a href="materi.php?action=add" class="btn">Tambah Materi</a>
            <form method="GET" class="filter-box">
                <select name="filter_mk">
                    <option value="">Semua Mata Kuliah</option>
                    <?php while($mk = $mk_options->fetch_assoc()): ?>
                        <option value="<?= $mk['id'] ?>" <?= $filter_mk == $mk['id'] ? 'selected' : '' ?>>
                            <?= e($mk['kode']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="q" placeholder="Cari Judul..." value="<?= e($q) ?>">
                <button type="submit" class="btn">Cari</button>
                <a href="materi.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Mata Kuliah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res->num_rows === 0): ?>
                    <tr><td colspan="3" style="text-align:center;">Data tidak ditemukan.</td></tr>
                <?php else: ?>
                    <?php while($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($row['judul']) ?></td>
                            <td><?= e($row['kode']) ?> - <?= e($row['nama_mk']) ?></td>
                            <td>
                                <a href="materi.php?action=detail&id=<?= $row['id'] ?>" class="btn btn-secondary">Detail</a>
                                <a href="materi.php?action=edit&id=<?= $row['id'] ?>" class="btn">Edit</a>
                                <a href="materi.php?action=delete_confirm&id=<?= $row['id'] ?>" class="btn btn-danger">Hapus</a>
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