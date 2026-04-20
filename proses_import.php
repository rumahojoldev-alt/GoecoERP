<?php
$host = "localhost"; $user = "root"; $pass = ""; $db = "db_rumahojol";
$koneksi = mysqli_connect($host, $user, $pass, $db);

if(isset($_POST['import'])) {
    $fileName = $_FILES['file_csv']['tmp_name'];
    
    // Pastikan file tidak kosong
    if($_FILES['file_csv']['size'] > 0) {
        $file = fopen($fileName, "r");
        
        // Lewati baris pertama (Karena baris pertama biasanya berisi judul/header: VIN, Make, Model, dll)
        fgetcsv($file, 10000, ","); 
        
        // Looping: Baca baris per baris sampai habis
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            
            // Mengambil data berdasarkan urutan kolom (Dimulai dari 0)
            $vin    = mysqli_real_escape_string($koneksi, $column[0]);
            $dinamo = mysqli_real_escape_string($koneksi, $column[1]);
            $make   = mysqli_real_escape_string($koneksi, $column[2]);
            $model  = mysqli_real_escape_string($koneksi, $column[3]);
            $tahun  = mysqli_real_escape_string($koneksi, $column[4]);
            $warna  = mysqli_real_escape_string($koneksi, $column[5]);
            $nopol  = mysqli_real_escape_string($koneksi, $column[6]);
            
            // Otomatisasi Status & Lokasi Awal
            $lokasi = 'Warehouse/BBI';
            $status = 'Ready';
            
            // Kita pakai INSERT IGNORE agar jika ada plat nomor atau VIN yang sama (duplikat), 
            // sistem tidak error dan langsung melompat ke baris CSV berikutnya.
            $query = "INSERT IGNORE INTO master_motor 
                      (vin, dinamo_motor, make, model, tahun, warna, nopol, lokasi_terkini, status_terkini) 
                      VALUES ('$vin', '$dinamo', '$make', '$model', '$tahun', '$warna', '$nopol', '$lokasi', '$status')";
            
            mysqli_query($koneksi, $query);
        }
        
        // Jika sudah selesai, kembali ke halaman Katalog
        header("Location: /rumahojol/index.php?asset=master_motor");
        exit;
    }
}
?>