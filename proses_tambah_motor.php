<?php
$host = "localhost"; $user = "operator"; $pass = "p2p!erp#260ft0"; $db = "db_rumahojol";
$koneksi = mysqli_connect($host, $user, $pass, $db);

$plat = $_POST['plat_nomor'];
$merk = $_POST['merk_tipe'];
$tahun = $_POST['tahun'];
$lokasi = $_POST['lokasi'];
$status = $_POST['status'];
$ket = $_POST['keterangan'];

$query = "INSERT INTO data_motor (plat_nomor, merk_tipe, tahun, lokasi, status, keterangan) 
          VALUES ('$plat', '$merk', '$tahun', '$lokasi', '$status', '$ket')";

if(mysqli_query($koneksi, $query)) {
    header("Location: index.php?asset=motor"); // Kembali ke halaman motor
    exit;
} else {
    echo "Error: " . mysqli_error($koneksi);
}
?>