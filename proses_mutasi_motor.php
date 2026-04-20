<?php
$host = "localhost"; $user = "root"; $pass = ""; $db = "db_rumahojol";
$koneksi = mysqli_connect($host, $user, $pass, $db);

if(isset($_POST['simpan_mutasi'])) {
    $nopol      = $_POST['nopol'];
    $tanggal    = $_POST['tanggal'];
    $lokasi     = $_POST['lokasi'];
    $status     = $_POST['status'];
    $keterangan = $_POST['keterangan'];

    // TUGAS 1: Simpan ke tabel riwayat (mutasi_motor)
    $query_log = "INSERT INTO mutasi_motor (nopol, tanggal, lokasi_tujuan, status_update, keterangan) 
                  VALUES ('$nopol', '$tanggal', '$lokasi', '$status', '$keterangan')";
    
    // TUGAS 2: Update status & lokasi terakhir di tabel induk (master_motor)
    $query_update_master = "UPDATE master_motor SET 
                            lokasi_terkini = '$lokasi', 
                            status_terkini = '$status' 
                            WHERE nopol = '$nopol'";

    // Jalankan keduanya
    if(mysqli_query($koneksi, $query_log) && mysqli_query($koneksi, $query_update_master)) {
        header("Location: /rumahojol/index.php?asset=mutasi_motor");
        exit;
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>