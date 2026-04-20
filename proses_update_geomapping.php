<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$host = "localhost"; $user = "root"; $pass = ""; $db = "db_rumahojol";
$koneksi = mysqli_connect($host, $user, $pass, $db);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tangkap Semua Data dari Form Full Edit
    $id_mapping       = mysqli_real_escape_string($koneksi, $_POST['id_mapping']);
    $status_keputusan = mysqli_real_escape_string($koneksi, $_POST['status_keputusan']);
    $jenis_lokasi     = mysqli_real_escape_string($koneksi, $_POST['jenis_lokasi']);
    $area             = mysqli_real_escape_string($koneksi, $_POST['area']);
    $nama_tempat      = mysqli_real_escape_string($koneksi, $_POST['nama_tempat']);
    $alamat_lengkap   = mysqli_real_escape_string($koneksi, $_POST['alamat_lengkap']);
    $koordinat_peta   = mysqli_real_escape_string($koneksi, $_POST['koordinat_peta']);
    $nama_pemilik     = mysqli_real_escape_string($koneksi, $_POST['nama_pemilik']);
    $no_hp_pemilik    = mysqli_real_escape_string($koneksi, $_POST['no_hp_pemilik']);
    $idpel_pln1       = mysqli_real_escape_string($koneksi, $_POST['idpel_pln1'] ?? '');
    $idpel_pln2       = mysqli_real_escape_string($koneksi, $_POST['idpel_pln2'] ?? '');
    $idpel_pln3       = mysqli_real_escape_string($koneksi, $_POST['idpel_pln3'] ?? '');

    // Persiapan Folder Upload Khusus Kabinet
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $query_foto = ""; 
    $dokumen_kabinet = [
        'doc_nidi', 'doc_slo', 'doc_sip', 'doc_spk', 
        'doc_permohonan_pln', 'doc_bayar_pln', 'doc_bayar_sewa'
    ];

    // Looping Upload File (Jika ada file baru yang dipilih)
    foreach ($dokumen_kabinet as $doc) {
        if (isset($_FILES[$doc]['name']) && $_FILES[$doc]['name'] != "") {
            if ($_FILES[$doc]['error'] == 0) {
                $ext = pathinfo($_FILES[$doc]['name'], PATHINFO_EXTENSION);
                $new_filename = $doc . "_" . $id_mapping . "_" . time() . "." . $ext;
                
                if (move_uploaded_file($_FILES[$doc]['tmp_name'], $target_dir . $new_filename)) {
                    $query_foto .= ", $doc = '$new_filename'";
                }
            }
        }
    }

    // Update Seluruh Kolom ke Database
    $query_update = "UPDATE data_geomapping SET 
                        status_keputusan = '$status_keputusan',
                        jenis_lokasi = '$jenis_lokasi',
                        area = '$area',
                        nama_tempat = '$nama_tempat',
                        alamat_lengkap = '$alamat_lengkap',
                        koordinat_peta = '$koordinat_peta',
                        nama_pemilik = '$nama_pemilik',
                        no_hp_pemilik = '$no_hp_pemilik',
                        idpel_pln1 = '$idpel_pln1',
                        idpel_pln2 = '$idpel_pln2',
                        idpel_pln3 = '$idpel_pln3'
                        $query_foto
                     WHERE id_mapping = '$id_mapping'";

    $eksekusi = mysqli_query($koneksi, $query_update);

    if ($eksekusi) {
        header("Location: index.php?asset=geomapping&status=sukses_update");
        exit;
    } else {
        die("<div style='padding: 20px; color: red;'>
                <h2>🚨 Gagal Update Database!</h2>
                <p><b>Error:</b> " . mysqli_error($koneksi) . "</p>
                <a href='index.php?asset=geomapping'>Kembali</a>
             </div>");
    }
} else {
    header("Location: index.php?asset=geomapping");
    exit;
}
?>