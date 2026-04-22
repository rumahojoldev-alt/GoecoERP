<?php
include 'koneksi_db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal  = $_POST['tanggal'];
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $ket      = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $lokasi   = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $tipe     = mysqli_real_escape_string($koneksi, $_POST['tipe']);
    $in       = (int)$_POST['in'];
    $out      = (int)$_POST['out'];

    // Query bersih tanpa kolom sisa
    $query = "INSERT INTO stok_baterai (tanggal, lokasi, tipe, keterangan, kategori, `in`, `out`) 
              VALUES ('$tanggal', '$lokasi', '$tipe', '$ket', '$kategori', '$in', '$out')";

    if(mysqli_query($koneksi, $query)) {
        header("Location: index.php?asset=mutasi_baterai&status=sukses");
        exit;
    } else {
        die("Gagal Simpan: " . mysqli_error($koneksi));
    }
}
?>