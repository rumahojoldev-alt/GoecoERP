<?php
// Pastikan hanya Superadmin / HR yang bisa akses halaman ini
if ($role_user_aktif != 'Admin') {
    echo "<div class='alert alert-danger m-4'>Akses Ditolak. Halaman khusus HR & Manajemen.</div>";
    exit;
}

// 1. TANGKAP FILTER PENCARIAN
$filter_tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01'); // Default awal bulan ini
$filter_tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t'); // Default akhir bulan ini
$filter_role = $_GET['role'] ?? '';
$filter_nama = $_GET['nama'] ?? '';

$where_clauses = ["DATE(waktu_masuk) BETWEEN '$filter_tgl_awal' AND '$filter_tgl_akhir'"];

if (!empty($filter_role)) {
    $where_clauses[] = "role = '$filter_role'";
}
if (!empty($filter_nama)) {
    $where_clauses[] = "nama_user LIKE '%$filter_nama%'";
}

$where_sql = implode(" AND ", $where_clauses);

// 2. QUERY KARTU RINGKASAN (Berdasarkan filter tanggal)
$q_hadir = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_absensi WHERE $where_sql AND kehadiran = 'Hadir'");
$tot_hadir = mysqli_fetch_assoc($q_hadir)['tot'] ?? 0;

$q_absen = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_absensi WHERE $where_sql AND kehadiran IN ('Izin', 'Sakit', 'Alpa')");
$tot_absen = mysqli_fetch_assoc($q_absen)['tot'] ?? 0;

$q_lembur = mysqli_query($koneksi, "SELECT SUM(menit_lembur) as tot_menit FROM data_absensi WHERE $where_sql");
$tot_menit_lembur = mysqli_fetch_assoc($q_lembur)['tot_menit'] ?? 0;
$jam_lembur = floor($tot_menit_lembur / 60);
$sisa_menit_lembur = $tot_menit_lembur % 60;

$q_koreksi = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_absensi WHERE $where_sql AND is_koreksi = 1");
$tot_koreksi = mysqli_fetch_assoc($q_koreksi)['tot'] ?? 0;

// 3. QUERY TABEL UTAMA
$q_data = mysqli_query($koneksi, "SELECT * FROM data_absensi WHERE $where_sql ORDER BY waktu_masuk DESC");
$total_data = mysqli_num_rows($q_data);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Dashboard Rekap Absensi</h3>
        <p class="text-muted small">Pantau kehadiran, lembur, dan validasi lokasi karyawan.</p>
    </div>
<button type="button" class="btn btn-success shadow-sm" onclick="exportKeExcel()">
    <i class="bi bi-file-earmark-excel me-1"></i> Export ke Excel
</button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-success">
            <div class="text-muted small fw-bold mb-2">TOTAL HADIR</div>
            <div class="h3 fw-bold mb-1 text-dark"><?= $tot_hadir ?> <span class="fs-6 text-muted fw-normal">Shift</span></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-warning">
            <div class="text-muted small fw-bold mb-2">IZIN / SAKIT / ALPA</div>
            <div class="h3 fw-bold mb-1 text-dark"><?= $tot_absen ?> <span class="fs-6 text-muted fw-normal">Catatan</span></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-danger">
            <div class="text-muted small fw-bold mb-2">TOTAL JAM LEMBUR</div>
            <div class="h3 fw-bold mb-1 text-danger"><?= $jam_lembur ?>j <?= $sisa_menit_lembur ?>m</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-info">
            <div class="text-muted small fw-bold mb-2">PENGGUNAAN KOREKSI</div>
            <div class="h3 fw-bold mb-1 text-dark"><?= $tot_koreksi ?> <span class="fs-6 text-muted fw-normal">Kali</span></div>
        </div>
    </div>
</div>

<div class="card card-custom p-3 border-0 shadow-sm mb-4 d-print-none">
    <form method="GET" class="row g-2 align-items-center">
        <input type="hidden" name="asset" value="rekap_absensi">
        
        <div class="col-md-3">
            <label class="small text-muted mb-1">Dari Tanggal</label>
            <input type="date" name="tgl_awal" class="form-control" value="<?= $filter_tgl_awal ?>">
        </div>
        <div class="col-md-3">
            <label class="small text-muted mb-1">Sampai Tanggal</label>
            <input type="date" name="tgl_akhir" class="form-control" value="<?= $filter_tgl_akhir ?>">
        </div>
        <div class="col-md-2">
            <label class="small text-muted mb-1">Jabatan</label>
            <select name="role" class="form-select">
                <option value="">-- Semua --</option>
                <option value="Mekanik" <?= ($filter_role == 'Mekanik') ? 'selected' : '' ?>>Mekanik</option>
                <option value="Sales" <?= ($filter_role == 'Sales') ? 'selected' : '' ?>>Sales/Surveyor</option>
                <option value="CS" <?= ($filter_role == 'CS') ? 'selected' : '' ?>>CS</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="small text-muted mb-1">Cari Nama</label>
            <input type="text" name="nama" class="form-control" placeholder="Ketik nama..." value="<?= $filter_nama ?>">
        </div>
        <div class="col-md-1 mt-auto">
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i></button>
        </div>
    </form>
</div>

<div class="card card-custom p-0 overflow-hidden border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover m-0 align-middle small">
            <table id="tabelAbsensi" class="table table-hover m-0 align-middle">
            <thead class="text-center bg-light">
                <tr>
                    <th>Karyawan</th>
                    <th>Tanggal</th>
                    <th>Kehadiran</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Lembur</th>
                    <th>Validasi Lokasi / Alasan</th>
                    <th>Selfie</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php if($total_data > 0): while($r = mysqli_fetch_assoc($q_data)): 
                    
                    // Pewarnaan Badge Kehadiran
                    $bg_hadir = 'bg-success';
                    if($r['kehadiran'] == 'Izin') $bg_hadir = 'bg-warning text-dark';
                    if($r['kehadiran'] == 'Sakit') $bg_hadir = 'bg-info text-dark';
                    if($r['kehadiran'] == 'Alpa') $bg_hadir = 'bg-danger';

                    // Menit lembur ke Jam & Menit
                    $lembur_text = '-';
                    if($r['menit_lembur'] > 0) {
                        $j_lembur = floor($r['menit_lembur'] / 60);
                        $m_lembur = $r['menit_lembur'] % 60;
                        $lembur_text = "<span class='text-danger fw-bold'>{$j_lembur}j {$m_lembur}m</span>";
                    }
                ?>
                <tr>
                    <td class="text-start">
                        <div class="fw-bold text-dark"><?= $r['nama_user'] ?></div>
                        <div class="text-muted" style="font-size: 10px;"><?= $r['role'] ?></div>
                    </td>
                    <td>
                        <?= date('d/m/Y', strtotime($r['waktu_masuk'])) ?>
                        <?php if($r['is_koreksi'] == 1): ?><br><span class="badge bg-warning text-dark" style="font-size: 8px;">Koreksi</span><?php endif; ?>
                    </td>
                    <td><span class="badge <?= $bg_hadir ?>"><?= $r['kehadiran'] ?></span></td>
                    <td class="font-monospace text-primary fw-bold"><?= date('H:i', strtotime($r['waktu_masuk'])) ?></td>
                    <td class="font-monospace text-danger fw-bold"><?= ($r['waktu_pulang']) ? date('H:i', strtotime($r['waktu_pulang'])) : '-' ?></td>
                    <td><?= $lembur_text ?></td>
                    <td class="text-start" style="max-width: 250px;">
                        <?php if($r['kehadiran'] == 'Hadir'): ?>
                            <div class="mb-1">
                                <?php if(strpos($r['status_lokasi'], 'Diluar Area') !== false): ?>
                                    <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                                <?php else: ?>
                                    <i class="bi bi-geo-alt-fill text-success me-1"></i>
                                <?php endif; ?>
                                <b>Masuk:</b> <?= htmlspecialchars($r['status_lokasi']) ?>
                            </div>
                            <?php if($r['waktu_pulang']): ?>
                                <div class="mb-1">
                                    <i class="bi bi-geo-alt me-1 text-muted"></i>
                                    <b>Pulang:</b> <?= htmlspecialchars($r['status_lokasi_pulang']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if(!empty($r['keterangan'])): ?>
                                <div class="fst-italic text-muted mt-2 border-top pt-1" style="font-size: 11px;">"<?= htmlspecialchars($r['keterangan']) ?>"</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="fst-italic">"<?= htmlspecialchars($r['keterangan']) ?>"</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($r['kehadiran'] == 'Hadir' && !empty($r['foto_selfie'])): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFoto<?= $r['id_absensi'] ?>">
                                <i class="bi bi-camera"></i> Lihat
                            </button>

                            <div class="modal fade" id="modalFoto<?= $r['id_absensi'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm modal-dialog-centered">
                                    <div class="modal-content border-0 bg-transparent">
                                        <div class="modal-body p-0 text-center position-relative">
                                            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2 shadow" data-bs-dismiss="modal"></button>
                                            <img src="uploads/<?= $r['foto_selfie'] ?>" class="img-fluid rounded shadow-lg border border-3 border-white">
                                            <div class="bg-dark text-white p-2 rounded-bottom small">
                                                Selfie: <?= $r['nama_user'] ?><br>
                                                <?= date('d M Y - H:i', strtotime($r['waktu_masuk'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="8" class="py-5 text-muted text-center">Tidak ada data absensi pada periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function exportKeExcel() {
    var table = document.getElementById("tabelAbsensi");
    var cleanHTML = "<table border='1'>";
    var rows = table.querySelectorAll("tr");

    for (var i = 0; i < rows.length; i++) {
        cleanHTML += "<tr>";
        var cols = rows[i].querySelectorAll("th, td");

        // Looping setiap kolom, abaikan kolom Selfie (paling kanan)
        for (var j = 0; j < cols.length - 1; j++) {
            var cellText = cols[j].innerText.trim();

            // ==========================================
            // 1. KHUSUS BARIS JUDUL (HEADER EXCEL)
            // ==========================================
            if (i === 0) {
                var styleHeader = "background-color: #10b981; color: #ffffff; font-weight: bold; text-align: center; padding: 5px;";
                
                if (j === 0) { // Jika ketemu judul "KARYAWAN", pecah jadi 2
                    cleanHTML += "<th style='" + styleHeader + "'>NAMA KARYAWAN</th>";
                    cleanHTML += "<th style='" + styleHeader + "'>JABATAN</th>";
                } else if (j === 6) { // Jika ketemu judul "VALIDASI", pecah jadi 2
                    cleanHTML += "<th style='" + styleHeader + "'>VALIDASI MASUK</th>";
                    cleanHTML += "<th style='" + styleHeader + "'>VALIDASI PULANG</th>";
                } else {
                    cleanHTML += "<th style='" + styleHeader + "'>" + cellText + "</th>";
                }
            } 
            // ==========================================
            // 2. KHUSUS BARIS ISI DATA (BODY EXCEL)
            // ==========================================
            else {
                if (j === 0) { 
                    // Pecah Nama dan Jabatan
                    var parts = cellText.split('\n');
                    var nama = parts[0] ? parts[0].trim() : "-";
                    var jabatan = parts[1] ? parts[1].trim() : "-";
                    cleanHTML += "<td>" + nama + "</td><td>" + jabatan + "</td>";
                } 
                else if (j === 6) { 
                    // Pecah Lokasi Masuk dan Pulang
                    var parts = cellText.split('\n');
                    var masuk = "-";
                    var pulang = "-";
                    
                    // Deteksi kata "Masuk:" dan "Pulang:" biar tidak tertukar
                    for(var p = 0; p < parts.length; p++) {
                        if(parts[p].includes("Masuk:")) {
                            masuk = parts[p].replace("Masuk:", "").trim();
                        }
                        if(parts[p].includes("Pulang:")) {
                            pulang = parts[p].replace("Pulang:", "").trim();
                        }
                    }
                    cleanHTML += "<td>" + masuk + "</td><td>" + pulang + "</td>";
                } 
                else {
                    // Kolom biasa (Tanggal, Jam, Kehadiran, dll)
                    cleanHTML += "<td style='text-align: center;'>" + cellText.replace(/\n/g, " ") + "</td>";
                }
            }
        }
        cleanHTML += "</tr>";
    }
    cleanHTML += "</table>";

    // Bungkus HTML ke format file Excel
    var templateExcel = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"></head><body>' + cleanHTML + '</body></html>';
    
    // Download otomatis
    var url = 'data:application/vnd.ms-excel;charset=utf-8,' + encodeURIComponent(templateExcel);
    var downloadLink = document.createElement("a");
    downloadLink.href = url;
    downloadLink.download = "Rekap_Absensi_HR_Rapi.xls"; // Nama filenya
    
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>