<?php
session_start();
// Paksa PHP untuk teriak kalau ada error
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

$host = "localhost"; $user = "root"; $pass = ""; $db = "db_rumahojol";
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Pengecekan paling aman: Apakah ada data yang dikirim via form?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Tangkap data dengan aman
    $item_id      = mysqli_real_escape_string($koneksi, $_POST['item_id']);
    $dari_lokasi  = mysqli_real_escape_string($koneksi, $_POST['dari_lokasi']);
    $ke_lokasi    = mysqli_real_escape_string($koneksi, $_POST['ke_lokasi']);
    $jumlah       = (int)$_POST['jumlah'];
    $keterangan   = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    // 1. Kurangi Stok Asal
    $q_kurang = "UPDATE stok_sparepart SET total_good = total_good - $jumlah 
                 WHERE item_id = '$item_id' AND lokasi = '$dari_lokasi'";
    $cek1 = mysqli_query($koneksi, $q_kurang);
    if(!$cek1) die("Error 1 (Kurang Stok): " . mysqli_error($koneksi));

    // 2. Tambah Stok Tujuan (Jika sudah ada, di-update/ditambah)
    $q_tambah = "INSERT INTO stok_sparepart (item_id, lokasi, total_good) 
                 VALUES ('$item_id', '$ke_lokasi', $jumlah)
                 ON DUPLICATE KEY UPDATE total_good = total_good + $jumlah";
    $cek2 = mysqli_query($koneksi, $q_tambah);
    if(!$cek2) die("Error 2 (Tambah Stok): " . mysqli_error($koneksi));

    // 3. Catat Riwayat (Tambahkan fungsi NOW() agar tanggal otomatis terisi)
    $q_log = "INSERT INTO mutasi_sparepart (item_id, dari_lokasi, ke_lokasi, jumlah, keterangan, tanggal) 
              VALUES ('$item_id', '$dari_lokasi', '$ke_lokasi', $jumlah, '$keterangan', NOW())";
    $cek3 = mysqli_query($koneksi, $q_log);
    if(!$cek3) die("Error 3 (Catat Log): " . mysqli_error($koneksi));

    // Kalau sukses, balik ke halaman Riwayat
    header("Location: index.php?asset=mutasi_sparepart&status=sukses");
    exit;

} else {
    echo "<h1>Akses Ditolak!</h1><p>Halaman ini hanya bisa diakses dengan menekan tombol dari Form Mutasi.</p>";
}
?>