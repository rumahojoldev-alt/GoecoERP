<?php
session_start();
// Tampilkan error jika ada masalah
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'koneksi_db.php';
if(isset($_POST['import_sp'])) {
    // Pastikan file yang diupload adalah CSV
    $file_mimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel');

    if(isset($_FILES['file_csv']['name']) && in_array($_FILES['file_csv']['type'], $file_mimes)) {
        
        $arr_file = explode('.', $_FILES['file_csv']['name']);
        $extension = end($arr_file);

        if('csv' == $extension) {
            $reader = fopen($_FILES['file_csv']['tmp_name'], 'r');
            $berhasil = 0;

            // Looping baca isi file CSV baris per baris
            while (($row = fgetcsv($reader, 1000, ",")) !== FALSE) {
                
                // Kalau baris pertama adalah judul kolom (Header), lewati saja
                if(strtolower($row[0]) == 'id item' || strtolower($row[0]) == 'item_id') continue;

                // Ambil data sesuai urutan kolom CSV yang kita tentukan di Modal
                $item_id   = mysqli_real_escape_string($koneksi, $row[0]);
                $nama      = mysqli_real_escape_string($koneksi, $row[1]);
                $tipe      = mysqli_real_escape_string($koneksi, $row[2]);
                $warna     = mysqli_real_escape_string($koneksi, $row[3]);
                $satuan    = mysqli_real_escape_string($koneksi, $row[4]);
                $harga     = (float)$row[5];
                $stok_awal = (int)$row[6];

                // 1. Simpan ke Katalog Master
                $query_master = "INSERT INTO master_sparepart (item_id, tipe_produk, name_standard, color, satuan, retail_price) 
                                 VALUES ('$item_id', '$tipe', '$nama', '$warna', '$satuan', '$harga')
                                 ON DUPLICATE KEY UPDATE name_standard='$nama', retail_price='$harga', tipe_produk='$tipe'";
                
                if(mysqli_query($koneksi, $query_master)) {
                    // 2. Simpan / Tambah Stok ke Gudang Pusat
                    mysqli_query($koneksi, "INSERT INTO stok_sparepart (item_id, lokasi, total_good) 
                                            VALUES ('$item_id', 'Warehouse/BBI', '$stok_awal')
                                            ON DUPLICATE KEY UPDATE total_good = total_good + '$stok_awal'");
                    $berhasil++;
                }
            }
            fclose($reader);
            
            // Berhasil, kembali ke halaman
            echo "<script>alert('Mantap! Berhasil import $berhasil data sparepart!'); window.location.href='index.php?asset=master_sparepart';</script>";
            exit;
        }
    }
    // Jika file bukan CSV
    echo "<script>alert('Error: File yang diupload harus berformat .csv!'); window.history.back();</script>";
} else {
    header("Location: index.php?asset=master_sparepart");
}
?>