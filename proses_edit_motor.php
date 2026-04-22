<?php
include 'koneksi_db.php';
if(isset($_POST['edit_motor'])) {
    // Tangkap semua data dari form modal
    $id     = $_POST['id'];
    $nopol  = $_POST['nopol'];
    $vin    = $_POST['vin'];
    $dinamo = $_POST['dinamo'];
    $make   = $_POST['make'];
    $model  = $_POST['model'];
    $tahun  = $_POST['tahun'];
    $warna  = $_POST['warna'];

    // Lakukan UPDATE ke tabel master_motor berdasarkan ID
    $query = "UPDATE master_motor SET 
              nopol = '$nopol',
              vin = '$vin',
              dinamo_motor = '$dinamo',
              make = '$make',
              model = '$model',
              tahun = '$tahun',
              warna = '$warna'
              WHERE id = '$id'";

    if(mysqli_query($koneksi, $query)) {
        // Balik ke halaman master motor jika sukses
        header("Location: /rumahojol/index.php?asset=master_motor");
        exit;
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>