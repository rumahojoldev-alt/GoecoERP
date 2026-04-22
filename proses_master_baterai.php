<?php
// Paksa PHP memunculkan semua error
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'koneksi_db.php';
if (!$koneksi) { die("Koneksi database putus: " . mysqli_connect_error()); }

if (isset($_POST['simpan_baterai'])) {
    
    $baterai_code = mysqli_real_escape_string($koneksi, $_POST['baterai_code']);
    $power        = mysqli_real_escape_string($koneksi, $_POST['power']);
    $status       = mysqli_real_escape_string($koneksi, $_POST['status']);
    
    $nama_file = "";

    // 1. MESIN X-RAY UPLOAD FOTO
    if (isset($_FILES['foto_baterai']) && $_FILES['foto_baterai']['name'] != '') {
        
        $file_error = $_FILES['foto_baterai']['error'];
        
        // Kalau errornya 0 (Artinya tidak ada error dari foto)
        if ($file_error === 0) {
            
            // Gunakan __DIR__ untuk mendapatkan alamat mutlak/pasti dari folder XAMPP kamu
            $target_dir = __DIR__ . "/uploads/baterai/";
            
            // Kalau foldernya belum ada, PHP akan bikin secara paksa
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Bersihkan nama file dari spasi (penting biar nggak error di HTML)
            $nama_asli = str_replace(" ", "_", basename($_FILES['foto_baterai']['name']));
            $nama_file = time() . "_" . $nama_asli;
            $target_file = $target_dir . $nama_file;
            
            // Coba pindahkan fotonya
            if (!move_uploaded_file($_FILES['foto_baterai']['tmp_name'], $target_file)) {
                die("<h3>GAGAL MEMINDAHKAN FOTO!</h3> <p>Sistem tidak dapat menulis ke folder: <b>$target_dir</b></p>");
            }

        } else {
            // TERJEMAHAN ERROR FOTO
            $pesan = "Alasan tidak diketahui.";
            if ($file_error == 1 || $file_error == 2) $pesan = "Ukuran foto TERLALU BESAR! (Maksimal bawaan XAMPP adalah 2MB. Coba cari foto yang sizenya kecil/di-compress dulu).";
            if ($file_error == 3) $pesan = "Foto hanya terupload sebagian (koneksi terputus).";
            if ($file_error == 4) $pesan = "Tidak ada foto yang dikirim.";
            
            die("<h3>FOTO DITOLAK OLEH SISTEM!</h3><p>Alasan: <b>$pesan</b></p>");
        }
    }

    // 2. SIMPAN KE DATABASE (Pakai fitur Update kalau ID-nya sama)
    $query = "INSERT INTO master_baterai (Baterai_Code, Power, Status, foto) 
              VALUES ('$baterai_code', '$power', '$status', '$nama_file')
              ON DUPLICATE KEY UPDATE Power='$power', Status='$status', foto=IF('$nama_file' != '', '$nama_file', foto)";
    
    $eksekusi = mysqli_query($koneksi, $query);

    if ($eksekusi) {
        header("Location: index.php?asset=master_baterai");
        exit;
    } else {
        die("<h3>ERROR DATABASE!</h3>" . mysqli_error($koneksi));
    }
} else {
    die("Akses langsung ditolak!");
}
?>