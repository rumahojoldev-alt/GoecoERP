<?php
session_start();
include 'koneksi_db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // 1. Tangkap data dari form
        $channel = mysqli_real_escape_string($koneksi, $_POST['channel'] ?? '');
        $nama    = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $hp_input= mysqli_real_escape_string($koneksi, $_POST['hp'] ?? ''); // Tangkap input mentah
        $latar   = mysqli_real_escape_string($koneksi, $_POST['latar'] ?? '');
        $sales   = mysqli_real_escape_string($koneksi, $_POST['sales'] ?? '');
        $created_by = $_SESSION['nama_user'] ?? 'Sistem';

        // ==========================================
        // MESIN PENCUCI NOMOR HP (Standarisasi ke 62)
        // ==========================================
        // Bersihkan semua karakter selain angka (misal ada +, strip, atau spasi)
        $hp_bersih = preg_replace('/[^0-9]/', '', $hp_input);
        
        // Jika nomor diawali angka '0', ubah menjadi '62'
        if (substr($hp_bersih, 0, 1) == '0') {
            $hp_bersih = '62' . substr($hp_bersih, 1);
        }

        // ==========================================
        // FITUR CEK DUPLIKAT NOMOR HP (Pakai HP Bersih)
        // ==========================================
        // Cek ke database menggunakan nomor yang sudah distandarisasi
        $cek_hp = mysqli_query($koneksi, "SELECT id_pipeline FROM data_pipeline WHERE no_hp = '$hp_bersih'");
        if (mysqli_num_rows($cek_hp) > 0) {
            header("Location: index.php?asset=pipeline&error=duplikat");
            exit;
        }

        // 2. Persiapan Upload Foto
        $target_dir = "uploads/";
        $foto_tersimpan = []; 
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (isset($_FILES['foto_chat']['name'][0]) && $_FILES['foto_chat']['name'][0] != "") {
            $jumlah_file = count($_FILES['foto_chat']['name']);
            for ($i = 0; $i < $jumlah_file; $i++) {
                if ($_FILES['foto_chat']['error'][$i] == 0) {
                    $file_ext = pathinfo($_FILES['foto_chat']['name'][$i], PATHINFO_EXTENSION);
                    $nama_bersih_file = preg_replace('/[^A-Za-z0-9\-]/', '', $nama); 
                    $file_name = time() . "_" . $nama_bersih_file . "_" . $i . "." . $file_ext;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['foto_chat']['tmp_name'][$i], $target_file)) {
                        $foto_tersimpan[] = $file_name;
                    }
                }
            }
        }

        $string_foto_db = implode(",", $foto_tersimpan);

// 3. Masukkan ke Database (Tambahkan waktu_input agar Dashboard bisa hitung bulanan)
$query_insert = "INSERT INTO data_pipeline 
                (waktu_input, channel, nama_prospek, no_hp, latar_belakang, sales_ditugaskan, status, foto_bukti, created_by) 
                VALUES 
                (NOW(), '$channel', '$nama', '$hp_bersih', '$latar', '$sales', 'Belum Dihubungi', '$string_foto_db', '$created_by')";
                
        mysqli_query($koneksi, $query_insert);
        
        header("Location: index.php?asset=pipeline&status=sukses");
        exit;

    } catch (mysqli_sql_exception $e) {
        die("<div style='padding: 20px; font-family: sans-serif; color: red;'>
                <h3>Terjadi Kesalahan Database:</h3>
                <p><b>Pesan Error:</b> " . $e->getMessage() . "</p>
                <a href='index.php?asset=pipeline'>Kembali ke Halaman Sebelumnya</a>
             </div>");
    }
} else {
    header("Location: index.php?asset=pipeline");
    exit;
}
?>