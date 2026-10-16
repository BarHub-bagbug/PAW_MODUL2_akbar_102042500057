<?php
require 'koneksi.php';

// Mengecek koneksi dan menghitung jumlah mata kuliah pada database Jurnal LMS
$hasil = mysqli_query($conn, 'SELECT COUNT(*) AS jumlah FROM mata_kuliah');

if ($hasil) {
    $baris = mysqli_fetch_assoc($hasil);
    echo 'Koneksi database Jurnal berhasil! Jumlah mata kuliah: ' . $baris['jumlah'];
} else {
    echo 'Koneksi gagal: ' . mysqli_error($conn);
}
?>