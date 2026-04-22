<?php
session_start();
include 'koneksi_db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_tiket     = $_POST['id_tiket'];
    $nama_mekanik = mysqli_real_escape_string($koneksi, $_POST['nama_mekanik']);
    
    // INI DIA TERSANGKANYA YANG HILANG KEMARIN! 😂
    $status       = mysqli_real_escape_string($koneksi, $_POST['status']); 
    
    $total_biaya  = !empty($_POST['total_biaya']) ? $_POST['total_biaya'] : 0; 

    // 1. AMBIL DATA AWAL TIKET
    $query_awal = mysqli_query($koneksi, "SELECT created_at, nopol FROM data_tiketing WHERE id_tiket = '$id_tiket'");
    $data_awal = mysqli_fetch_assoc($query_awal);
    $nopol = $data_awal['nopol'];

    // 2. LOGIKA ARGO WAKTU (Hanya Dihitung Jika Status = Completed)
    $set_waktu = "";
    if ($status == 'Completed') {
        $waktu_selesai = date("Y-m-d H:i:s");
        $awal = new DateTime($data_awal['created_at']);
        $akhir = new DateTime($waktu_selesai);
        $diff = $awal->diff($akhir);
        $total_menit = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        
        $set_waktu = ", waktu_selesai = '$waktu_selesai', durasi_menit = '$total_menit'";
    }

    // 3. PROSES CATAT MUTASI SPAREPART (ANTI-DOUBLE)
    $sparepart_ids = isset($_POST['sparepart']) ? $_POST['sparepart'] : [];
    $nama_sparepart_arr = [];

    if (!empty($sparepart_ids)) {
        foreach ($sparepart_ids as $item_id) {
            $item_id = mysqli_real_escape_string($koneksi, $item_id);

            $q_nama = mysqli_query($koneksi, "SELECT name_standard FROM master_sparepart WHERE item_id = '$item_id'");
            if ($d_nama = mysqli_fetch_assoc($q_nama)) {
                $nama_sparepart_arr[] = $d_nama['name_standard'];
            }

            $keterangan = "Otomatis: Terpakai di Tiket #" . $id_tiket . " (Mekanik: " . $nama_mekanik . ")";
            $q_cek_mutasi = mysqli_query($koneksi, "SELECT id FROM mutasi_sparepart WHERE item_id = '$item_id' AND keterangan = '$keterangan'");
            
            if (mysqli_num_rows($q_cek_mutasi) == 0) {
                $ke_lokasi = "Unit " . $nopol;
                mysqli_query($koneksi, "INSERT INTO mutasi_sparepart (item_id, dari_lokasi, ke_lokasi, jumlah, keterangan) 
                                        VALUES ('$item_id', 'Gudang Pusat', '$ke_lokasi', 1, '$keterangan')");
            }
        }
    }
    $teks_sparepart_dipakai = mysqli_real_escape_string($koneksi, implode(", ", $nama_sparepart_arr));

    // 4. PROSES UPLOAD FOTO MULTIPLE
    $foto_tambahan = "";
    $arr_nama_foto = [];
    
    if (isset($_FILES['foto_kerusakan']['name'][0]) && $_FILES['foto_kerusakan']['name'][0] != "") {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true); 
        
        $jumlah_file = count($_FILES['foto_kerusakan']['name']);
        for ($i = 0; $i < $jumlah_file; $i++) {
            $tmp_name = $_FILES['foto_kerusakan']['tmp_name'][$i];
            if ($tmp_name != "") {
                $ext = pathinfo($_FILES['foto_kerusakan']['name'][$i], PATHINFO_EXTENSION);
                $file_name = "Tiket_" . $id_tiket . "_" . time() . "_" . $i . "." . $ext;
                if (move_uploaded_file($tmp_name, $target_dir . $file_name)) {
                    $arr_nama_foto[] = $file_name;
                }
            }
        }
    }
    
    if (!empty($arr_nama_foto)) {
        $string_foto_baru = implode(",", $arr_nama_foto);
        $foto_tambahan = ", foto_kerusakan = TRIM(BOTH ',' FROM CONCAT_WS(',', IFNULL(foto_kerusakan, ''), '$string_foto_baru'))";
    }

    // 5. SIMPAN KE DATABASE TIKETING
    $query_update = "UPDATE data_tiketing SET 
                     nama_mekanik = '$nama_mekanik', 
                     status = '$status', 
                     total_biaya = '$total_biaya',
                     sparepart_dipakai = '$teks_sparepart_dipakai'
                     $set_waktu
                     $foto_tambahan
                     WHERE id_tiket = '$id_tiket'";
    mysqli_query($koneksi, $query_update);

    // 6. KEMBALIKAN MOTOR JADI READY (Hanya jika tiketnya beneran Completed)
    if ($status == 'Completed' && !empty($nopol)) {
        mysqli_query($koneksi, "UPDATE master_motor SET status_terkini = 'Ready' WHERE nopol = '$nopol'");
    }

    // Arahkan kembali ke halaman awal
    header("Location: index.php?asset=tiketing&status=finish");
    exit;
}
?>