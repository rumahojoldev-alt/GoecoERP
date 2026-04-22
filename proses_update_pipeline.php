<?php
session_start();
include 'koneksi_db.php';
// Aktifkan mode pelaporan error biar tidak blank putih
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // =========================================================================
    // 1. JIKA KLIK TOMBOL TERMINATE DARI TABEL (AKSI CEPAT)
    // =========================================================================
    if (isset($_GET['action']) && $_GET['action'] == 'terminate') {
        $id = $_GET['id'];
        
        // A. Update Pipeline
        mysqli_query($koneksi, "UPDATE data_pipeline SET status = 'Terminate' WHERE id_pipeline = '$id'");

        // B. Aksi Berantai: Ambil Nopol, Update Master Motor, & Buat Tiket
        $q_p = mysqli_query($koneksi, "SELECT nomor_plat FROM data_pipeline WHERE id_pipeline = '$id'");
        $d_p = mysqli_fetch_assoc($q_p);
        $nopol = $d_p['nomor_plat'];

        if (!empty($nopol)) {
            // Update Master Motor
            mysqli_query($koneksi, "UPDATE master_motor SET status_terkini = 'Service/Rusak', nama_driver_aktif = NULL, no_hp_aktif = NULL WHERE nopol = '$nopol'");
            
        // Buat Tiket Mekanik
            $kategori = "Inspeksi Terminate";
            $deskripsi = "Inspeksi Unit Selesai Sewa (Otomatis dari tombol Terminate)";
            mysqli_query($koneksi, "INSERT INTO data_tiketing (nopol, kategori_masalah, deskripsi, status) VALUES ('$nopol', '$kategori', '$deskripsi', 'Open')");
            }

        header("Location: index.php?asset=pipeline&status=sukses");
        exit;
    }

    // =========================================================================
    // 2. JIKA ADA DATA FORM YANG DIKIRIM (Metode POST dari Modal)
    // =========================================================================
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id_pipeline    = $_POST['id_pipeline'] ?? '';
        $status_baru    = mysqli_real_escape_string($koneksi, $_POST['status_baru'] ?? '');
        $alasan_singkat = mysqli_real_escape_string($koneksi, $_POST['alasan_singkat'] ?? '');
        $keterangan     = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');
        
        // Khusus jika status Deal, biasanya mereka menginputkan nomor plat di form
        $nopol_input    = mysqli_real_escape_string($koneksi, $_POST['nomor_plat'] ?? '');
        
        // A. Eksekusi Update Teks di Pipeline
        if ($status_baru == 'Deal' && !empty($nopol_input)) {
            // Jika Deal dan plat diisi, simpan platnya juga ke pipeline
            $query_update = "UPDATE data_pipeline SET status = '$status_baru', alasan_singkat = '$alasan_singkat', keterangan_status = '$keterangan', nomor_plat = '$nopol_input' WHERE id_pipeline = '$id_pipeline'";
        } else {
            $query_update = "UPDATE data_pipeline SET status = '$status_baru', alasan_singkat = '$alasan_singkat', keterangan_status = '$keterangan' WHERE id_pipeline = '$id_pipeline'";
        }
        mysqli_query($koneksi, $query_update);

        // B. AKSI BERANTAI MASTER MOTOR & TIKET
        if ($status_baru == 'Deal') {
            $q_prospek = mysqli_query($koneksi, "SELECT nama_prospek, no_hp, nomor_plat FROM data_pipeline WHERE id_pipeline = '$id_pipeline'");
            $d_prospek = mysqli_fetch_assoc($q_prospek);
            
            $nama_driver = $d_prospek['nama_prospek'];
            $no_hp_driver = $d_prospek['no_hp'];
            $nopol_final = !empty($nopol_input) ? $nopol_input : $d_prospek['nomor_plat']; // Gunakan input baru, atau data plat lama

            if (!empty($nopol_final)) {
                mysqli_query($koneksi, "UPDATE master_motor SET status_terkini = 'Disewa', nama_driver_aktif = '$nama_driver', no_hp_aktif = '$no_hp_driver' WHERE nopol = '$nopol_final'");
            }
        } 
        elseif ($status_baru == 'Terminate') {
            $q_p = mysqli_query($koneksi, "SELECT nomor_plat FROM data_pipeline WHERE id_pipeline = '$id_pipeline'");
            $d_p = mysqli_fetch_assoc($q_p);
            $nopol = $d_p['nomor_plat'];

            if (!empty($nopol)) {
                // Update Master
                mysqli_query($koneksi, "UPDATE master_motor SET status_terkini = 'Service/Rusak', nama_driver_aktif = NULL, no_hp_aktif = NULL WHERE nopol = '$nopol'");
                // Buat Tiket
                $kategori = "Inspeksi Terminate";
                $deskripsi = "Inspeksi Unit Selesai Sewa (Otomatis dari Form Modal)";
                mysqli_query($koneksi, "INSERT INTO data_tiketing (nopol, kategori_masalah, deskripsi, status) VALUES ('$nopol', '$kategori', '$deskripsi', 'Open')");
                }
        }

        // C. Proses Upload Foto (Sesuai aslinya, tidak saya ubah)
        if (isset($_FILES['foto_bukti']['name']) && $_FILES['foto_bukti']['name'] != "") {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true); 
            
            $file_ext = pathinfo($_FILES['foto_bukti']['name'], PATHINFO_EXTENSION);
            $file_name = "Update_" . time() . "_" . $id_pipeline . "." . $file_ext;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['foto_bukti']['tmp_name'], $target_file)) {
                // Tambahkan foto ke kolom foto_bukti (menggunakan IFNULL supaya aman)
                mysqli_query($koneksi, "UPDATE data_pipeline SET foto_bukti = CONCAT(IFNULL(foto_bukti,''), ',', '$file_name') WHERE id_pipeline = '$id_pipeline'");
            }
        }

        // D. Kembali ke pipeline dengan pesan sukses
        header("Location: index.php?asset=pipeline&status=sukses");
        exit;
    } else {
        // JIKA NYASAR KE SINI TANPA LEWAT FORM
        echo "<div style='padding:20px; font-family:sans-serif; text-align:center;'>";
        echo "<h2>Akses Tidak Valid!</h2>";
        echo "<p>Sistem tidak mendeteksi adanya data yang dikirim dari form.</p>";
        echo "<a href='index.php?asset=pipeline' style='padding:10px 20px; background:#10b981; color:#fff; text-decoration:none; border-radius:5px;'>Kembali ke Pipeline</a>";
        echo "</div>";
    }
    
} catch (mysqli_sql_exception $e) {
    // JIKA GAGAL QUERY DATABASE
    die("<div style='padding: 20px; font-family: sans-serif; color: red; background-color: #ffe6e6; border: 2px solid red; border-radius: 10px;'>
            <h2>🚨 Terjadi Kesalahan Database Saat Update!</h2>
            <p><b>Pesan Error dari MySQL:</b> " . $e->getMessage() . "</p>
            <hr>
            <p><i>Hint: Pastikan tabel master_motor dan data_ticket sudah dibuat dengan benar.</i></p>
            <a href='index.php?asset=pipeline'>[ Kembali ke Halaman Pipeline ]</a>
         </div>");
}
?>