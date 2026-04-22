<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

include 'koneksi_db.php';
if(isset($_POST['simpan_sparepart'])) {
    
    // 1. BUAT ID ITEM OTOMATIS (Format: SP-TahunBulanTanggalJamMenitDetik)
    // Contoh Hasil: SP-260416214500
    $item_id = "SP-" . date('ymdHis');

    // 2. Tangkap isian form lainnya
    $nama          = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $nama_mandarin = mysqli_real_escape_string($koneksi, $_POST['nama_mandarin']);
    $tipe          = mysqli_real_escape_string($koneksi, $_POST['tipe']);
    $warna         = mysqli_real_escape_string($koneksi, $_POST['warna']);
    $satuan        = mysqli_real_escape_string($koneksi, $_POST['satuan']);
    $harga         = mysqli_real_escape_string($koneksi, $_POST['harga']);
    $stok_awal     = (int)$_POST['stok_awal'];

    // 3. Persiapan Upload Foto
    $nama_file_foto = "";
    if(isset($_FILES['foto']['name']) && $_FILES['foto']['name'] != "") {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); } 
        
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_file_foto = "sp_" . time() . "_" . rand(100,999) . "." . $ext;
        
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $nama_file_foto);
    }

    // 4. Masukkan ke Tabel Master
    $insert_master = mysqli_query($koneksi, "INSERT INTO master_sparepart 
        (item_id, tipe_produk, name_standard, color, satuan, retail_price, foto) 
        VALUES ('$item_id', '$tipe', '$nama', '$warna', '$satuan', '$harga', '$nama_file_foto')");

    if($insert_master) {
        
        // 5. Masukkan Nama Mandarin (Jika Diisi) ke tabel terjemahan
        if(!empty($nama_mandarin)) {
            mysqli_query($koneksi, "INSERT INTO sys_translations (item_id, language_code, translated_name) 
                                    VALUES ('$item_id', 'cn', '$nama_mandarin')");
        }

        // 6. Masukkan Stok Awal ke Gudang Pusat
        mysqli_query($koneksi, "INSERT INTO stok_sparepart (item_id, lokasi, total_good) 
                                VALUES ('$item_id', 'Warehouse/BBI', '$stok_awal')");

        // Sukses! Kembalikan ke halaman katalog
        header("Location: index.php?asset=master_sparepart&status=sukses_tambah");
        exit;
    } else {
        // Jika ada error database
        echo "<script>alert('Gagal menyimpan ke database! Error: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    }
} else {
    // Tendang kalau bukan dari form POST
    header("Location: index.php?asset=master_sparepart");
    exit;
}
?>