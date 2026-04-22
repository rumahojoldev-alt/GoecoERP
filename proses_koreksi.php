<?php
// proses_koreksi.php
include 'koneksi_db.php';
session_start();

// Pastikan hanya yang login yang bisa akses
if (!isset($_SESSION['user_login'])) {
    header("Location: login.php");
    exit;
}

$nama_user_aktif = $_SESSION['nama_user'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Tangkap Data dari Form
    $tanggal_koreksi = mysqli_real_escape_string($koneksi, $_POST['tanggal_koreksi']);
    $jam_masuk       = mysqli_real_escape_string($koneksi, $_POST['jam_masuk']);
    $jam_pulang      = !empty($_POST['jam_pulang']) ? mysqli_real_escape_string($koneksi, $_POST['jam_pulang']) : NULL;
    $alasan          = mysqli_real_escape_string($koneksi, $_POST['alasan']);

    // Gabungkan tanggal dan jam untuk format Datetime MySQL
    $waktu_masuk_full = $tanggal_koreksi . " " . $jam_masuk . ":00";
    $waktu_pulang_full = ($jam_pulang) ? $tanggal_koreksi . " " . $jam_pulang . ":00" : NULL;

    // 2. Handle Upload File Bukti (Jika Ada)
    $nama_file_bukti = "";
    if (!empty($_FILES['bukti_koreksi']['name'])) {
        $ekstensi_boleh = ['jpg', 'jpeg', 'png'];
        $nama_ori       = $_FILES['bukti_koreksi']['name'];
        $ekstensi       = strtolower(pathinfo($nama_ori, PATHINFO_EXTENSION));
        $ukuran         = $_FILES['bukti_koreksi']['size'];
        $file_tmp       = $_FILES['bukti_koreksi']['tmp_name'];

        // Cek ekstensi
        if (in_array($ekstensi, $ekstensi_boleh)) {
            $nama_file_baru = "KOREKSI_" . date('YmdHis') . "_" . $nama_user_aktif . "." . $ekstensi;
            $path_tujuan    = "uploads/" . $nama_file_baru;

            if (move_uploaded_file($file_tmp, $path_tujuan)) {
                $nama_file_bukti = $nama_file_baru;
            }
        }
    }

    // 3. Query Insert ke Tabel data_absensi
    // Kita set is_koreksi = 1 agar sistem tahu ini hasil perbaikan manual
    // Kita set status_lokasi = "Hasil Koreksi Manual"
    if ($waktu_pulang_full) {
        $query = "INSERT INTO data_absensi (nama_user, waktu_masuk, waktu_pulang, kehadiran, status_lokasi, keterangan, foto_selfie, is_koreksi) 
                  VALUES ('$nama_user_aktif', '$waktu_masuk_full', '$waktu_pulang_full', 'Hadir', 'Hasil Koreksi Manual', '$alasan', '$nama_file_bukti', 1)";
    } else {
        $query = "INSERT INTO data_absensi (nama_user, waktu_masuk, kehadiran, status_lokasi, keterangan, foto_selfie, is_koreksi) 
                  VALUES ('$nama_user_aktif', '$waktu_masuk_full', 'Hadir', 'Hasil Koreksi Manual', '$alasan', '$nama_file_bukti', 1)";
    }

    if (mysqli_query($koneksi, $query)) {
        // Berhasil! Lempar balik ke halaman absensi dengan pesan sukses
        echo "<script>
                alert('Pengajuan koreksi berhasil disimpan!');
                window.location.href = 'index.php?asset=absensi';
              </script>";
    } else {
        // Gagal
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>