<?php
session_start();
// 1. WAJIB SET TIMEZONE DI PALING ATAS
date_default_timezone_set('Asia/Jakarta');

$host = "localhost"; $user = "root"; $pass = ""; $db = "db_rumahojol";
$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!isset($_SESSION['user_login'])) { exit; }

$aksi = $_GET['aksi'] ?? '';
$nama_user = $_SESSION['nama_user'];
$role_user = $_SESSION['role_user'];
$waktu_sekarang = date('Y-m-d H:i:s');
$hari_ini_nama = date('l');

// ==========================================
// 1. PROSES ABSEN MASUK
// ==========================================
if ($aksi == 'masuk') {
    $kehadiran = $_POST['kehadiran'];
    $status_lokasi = $_POST['status_lokasi'] ?? 'Diluar Area';
    $latlong = $_POST['latlong'] ?? '';
    $keterangan = ($kehadiran == 'Hadir') ? ($_POST['keterangan_luar'] ?? '') : ($_POST['keterangan_izin'] ?? '');
    
    // Khusus CS, tangkap shiftnya untuk menentukan target pulang nanti
    $shift_info = ($role_user == 'CS') ? $_POST['shift_cs'] : '';
    if($shift_info != '') $status_lokasi .= " (Shift $shift_info)";

    // Olah Foto Selfie
    $nama_file_selfie = "";
    if ($kehadiran == 'Hadir' && !empty($_FILES['foto_selfie']['name'])) {
        $target_dir = "uploads/"; // Sesuaikan dengan folder yang kamu buat
        $ext = pathinfo($_FILES['foto_selfie']['name'], PATHINFO_EXTENSION);
        $nama_file_selfie = time() . "_" . str_replace(' ', '_', $nama_user) . "_masuk." . $ext;
        move_uploaded_file($_FILES['foto_selfie']['tmp_name'], $target_dir . $nama_file_selfie);
    }

    $query = "INSERT INTO data_absensi (nama_user, role, waktu_masuk, foto_selfie, latlong, status_lokasi, kehadiran, keterangan) 
              VALUES ('$nama_user', '$role_user', '$waktu_sekarang', '$nama_file_selfie', '$latlong', '$status_lokasi', '$kehadiran', '$keterangan')";
    
    if (mysqli_query($koneksi, $query)) {
        header("Location: index.php?asset=absensi&status=sukses_masuk");
    }
}

// ==========================================
// 2. PROSES ABSEN PULANG
// ==========================================
if ($aksi == 'pulang') {
    $id_absensi = $_POST['id_absensi'];
    $latlong_pulang = $_POST['latlong_pulang'];
    $status_lokasi_pulang = $_POST['status_lokasi_pulang'];
    $keterangan_luar = $_POST['keterangan_luar_pulang'] ?? '';
    }

    // A. Ambil data masuk untuk cek shift
    $q_data = mysqli_query($koneksi, "SELECT status_lokasi FROM data_absensi WHERE id_absensi = '$id_absensi'");
    $d_awal = mysqli_fetch_assoc($q_data);
    
// B. Tentukan Target Jam Pulang Normal berdasarkan Aturan Baru
    $jam_target = "18:00:00"; // Default Sales
    if ($role_user == 'Mekanik') {
        $jam_target = ($hari_ini_nama == 'Saturday') ? "15:00:00" : "17:00:00";
    } elseif ($role_user == 'CS') {
        // Cek teks shift yang tadi kita "titipkan" di status_lokasi
        if (strpos($d_awal['status_lokasi'], 'Pagi') !== false) $jam_target = "15:00:00";
        elseif (strpos($d_awal['status_lokasi'], 'Sore') !== false) $jam_target = "23:00:00";
        elseif (strpos($d_awal['status_lokasi'], 'Subuh') !== false) $jam_target = "07:00:00";
    }

    // C. Hitung Lembur (Logika Pintar)
    $menit_lembur = 0;
    $skrg_ts = strtotime($waktu_sekarang);
    
    // Untuk shift subuh yang pulang jam 07:00 pagi besoknya
    if ($jam_target == "07:00:00") {
        $target_ts = strtotime(date('Y-m-d', $skrg_ts) . " 07:00:00");
    } else {
        $target_ts = strtotime(date('Y-m-d', $skrg_ts) . " " . $jam_target);
    }

    if ($skrg_ts > $target_ts) {
        $menit_lembur = floor(($skrg_ts - $target_ts) / 60);
    }

    // D. Gabungkan Keterangan Pulang agar Rapi
    $catatan_tambahan = (!empty($keterangan_luar)) ? " | Catatan Pulang: " . $keterangan_luar : "";

    $query_update = "UPDATE data_absensi SET 
                    waktu_pulang = '$waktu_sekarang', 
                    latlong_pulang = '$latlong_pulang', 
                    status_lokasi_pulang = '$status_lokasi_pulang', 
                    menit_lembur = '$menit_lembur',
                    keterangan = CONCAT(keterangan, '$catatan_tambahan')
                    WHERE id_absensi = '$id_absensi'";

    if (mysqli_query($koneksi, $query_update)) {
        header("Location: index.php?asset=absensi&status=sukses_pulang");
    }
?>