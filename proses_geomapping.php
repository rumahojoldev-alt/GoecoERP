<?php
session_start();
// Wajib set timezone agar waktu input akurat
date_default_timezone_set('Asia/Jakarta');

$host = "localhost"; $user = "root"; $pass = ""; $db = "db_rumahojol";
$koneksi = mysqli_connect($host, $user, $pass, $db);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Tangkap Data Utama & Status Baru
    $jenis_lokasi      = mysqli_real_escape_string($koneksi, $_POST['jenis_lokasi']);
    $status_keputusan  = mysqli_real_escape_string($koneksi, $_POST['status_keputusan'] ?? 'Survey');
    $area              = mysqli_real_escape_string($koneksi, $_POST['area']);
    $nama_tempat       = mysqli_real_escape_string($koneksi, $_POST['nama_tempat']);
    $alamat_lengkap    = mysqli_real_escape_string($koneksi, $_POST['alamat_lengkap']);
    $koordinat_peta    = mysqli_real_escape_string($koneksi, $_POST['koordinat_peta']);
    
    // Data Kepemilikan & Referensi
    $nama_pemilik      = mysqli_real_escape_string($koneksi, $_POST['nama_pemilik'] ?? '');
    $no_hp_pemilik     = mysqli_real_escape_string($koneksi, $_POST['no_hp_pemilik'] ?? '');
    $referensi         = mysqli_real_escape_string($koneksi, $_POST['referensi'] ?? '');
    $persetujuan_keluarga = mysqli_real_escape_string($koneksi, $_POST['persetujuan_keluarga'] ?? '');
    $kepemilikan_lokasi = mysqli_real_escape_string($koneksi, $_POST['kepemilikan_lokasi'] ?? '');

    // Logika Tanggal Akhir Sewa (Hanya jika Sewa, kalau tidak maka NULL)
    $tgl_akhir_sewa = "NULL";
    if ($kepemilikan_lokasi == 'Sewa / Kontrak' && !empty($_POST['tgl_akhir_sewa'])) {
        $tgl_sewa_val = mysqli_real_escape_string($koneksi, $_POST['tgl_akhir_sewa']);
        $tgl_akhir_sewa = "'$tgl_sewa_val'";
    }

    // Data Teknis PLN & Lingkungan
    $bebas_banjir      = mysqli_real_escape_string($koneksi, $_POST['bebas_banjir'] ?? 'Tidak');
    $idpel_pln1        = mysqli_real_escape_string($koneksi, $_POST['idpel_pln1'] ?? '');
    $catatan           = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');

    // Identitas Sales Input
    $sales_input = $_SESSION['nama_user'] ?? 'Sistem';
    $waktu_sekarang = date('Y-m-d H:i:s');

    // 2. Persiapan Upload (Foto & Video)
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $nama_bersih = preg_replace('/[^A-Za-z0-9\-]/', '', $nama_tempat);
    $prefix = time() . "_" . $nama_bersih;

    function uploadFileGeomap($input_name, $prefix_name, $dir) {
        if (isset($_FILES[$input_name]['name']) && $_FILES[$input_name]['name'] != "") {
            $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
            $new_filename = $prefix_name . "_" . $input_name . "." . $ext;
            if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $dir . $new_filename)) {
                return $new_filename;
            }
        }
        return ""; 
    }

    $foto_1 = uploadFileGeomap('foto_1', $prefix, $target_dir);
    $foto_2 = uploadFileGeomap('foto_2', $prefix, $target_dir);
    $foto_3 = uploadFileGeomap('foto_3', $prefix, $target_dir);
    $foto_4 = uploadFileGeomap('foto_4', $prefix, $target_dir);
    $foto_5 = uploadFileGeomap('foto_5', $prefix, $target_dir);
    $video_lokasi = uploadFileGeomap('video_lokasi', $prefix, $target_dir);

    // 3. Query INSERT Lengkap
    $query_insert = "INSERT INTO data_geomapping (
        tanggal_input, jenis_lokasi, status_keputusan, area, nama_tempat, alamat_lengkap, koordinat_peta, 
        sales_input, nama_pemilik, no_hp_pemilik, referensi, persetujuan_keluarga, kepemilikan_lokasi, tgl_akhir_sewa, 
        bebas_banjir, idpel_pln1, foto_1, foto_2, foto_3, foto_4, foto_5, video_lokasi, catatan
    ) VALUES (
        '$waktu_sekarang', '$jenis_lokasi', '$status_keputusan', '$area', '$nama_tempat', '$alamat_lengkap', '$koordinat_peta',
        '$sales_input', '$nama_pemilik', '$no_hp_pemilik', '$referensi', '$persetujuan_keluarga', '$kepemilikan_lokasi', $tgl_akhir_sewa,
        '$bebas_banjir', '$idpel_pln1', '$foto_1', '$foto_2', '$foto_3', '$foto_4', '$foto_5', '$video_lokasi', '$catatan'
    )";

    $eksekusi = mysqli_query($koneksi, $query_insert);

    if ($eksekusi) {
        header("Location: index.php?asset=geomapping&status=sukses");
        exit;
    } else {
        die("<div style='padding: 20px; font-family: sans-serif; color: red; background-color: #ffe6e6; border: 2px solid red; border-radius: 10px;'>
                <h2>🚨 Gagal Menyimpan!</h2>
                <p><b>Pesan Error:</b> " . mysqli_error($koneksi) . "</p>
                <a href='index.php?asset=geomapping'>Kembali</a>
             </div>");
    }
} else {
    header("Location: index.php?asset=geomapping");
    exit;
}
?>