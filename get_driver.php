<?php
// get_driver.php
include 'koneksi_db.php';

if(isset($_POST['nopol'])) {
    $nopol = mysqli_real_escape_string($koneksi, $_POST['nopol']);
    
    // CATATAN PENTING: 
    // Sesuaikan nama kolom 'nama_driver' dan 'no_hp' dengan nama kolom yang ada di tabel master_motor kamu ya!
    $query = mysqli_query($koneksi, "SELECT nama_driver, no_hp FROM master_motor WHERE nopol = '$nopol'");
    
    if(mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        echo json_encode($data); // Kembalikan data dalam format JSON
    } else {
        echo json_encode(['nama_driver' => '', 'no_hp' => '']);
    }
}
?>