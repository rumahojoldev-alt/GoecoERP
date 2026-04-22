<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi_db.php';

// --- 1. TANGKAP PARAMETER PENCARIAN & FILTER ---
$search_nama  = $_GET['search'] ?? '';
$filter_jenis = $_GET['jenis'] ?? '';
$filter_area  = $_GET['area'] ?? ''; 

$where_clauses = ["1=1"];
if (!empty($search_nama)) {
    $where_clauses[] = "(nama_tempat LIKE '%$search_nama%' OR area LIKE '%$search_nama%')";
}
if (!empty($filter_jenis)) {
    $where_clauses[] = "jenis_lokasi = '$filter_jenis'";
}
if (!empty($filter_area)) {
    $where_clauses[] = "area = '$filter_area'"; 
}

$where_sql = implode(" AND ", $where_clauses);

// --- 2. LOGIKA KARTU RINGKASAN ---
$q_tot_semua = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_geomapping WHERE $where_sql");
$tot_semua = mysqli_fetch_assoc($q_tot_semua)['tot'] ?? 0;

$q_tot_kabinet = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_geomapping WHERE jenis_lokasi = 'Kabinet' AND $where_sql");
$tot_kabinet = mysqli_fetch_assoc($q_tot_kabinet)['tot'] ?? 0;

$q_tot_komunitas = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_geomapping WHERE jenis_lokasi = 'Komunitas' AND $where_sql");
$tot_komunitas = mysqli_fetch_assoc($q_tot_komunitas)['tot'] ?? 0;

$q_tot_deal = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_geomapping WHERE status_keputusan = 'Deal' AND $where_sql");
$tot_deal = mysqli_fetch_assoc($q_tot_deal)['tot'] ?? 0;


// --- 3. LOGIKA GRAFIK GEO-MAPPING (Pemisahan Akurat) ---
$label_kabinet = []; $data_kabinet = [];
$label_komunitas = []; $data_komunitas = [];

$q_kabinet = mysqli_query($koneksi, "SELECT area, COUNT(*) as total FROM data_geomapping WHERE jenis_lokasi = 'Kabinet' AND $where_sql GROUP BY area ORDER BY total DESC");
if($q_kabinet) {
    while($row = mysqli_fetch_assoc($q_kabinet)) {
        $label_kabinet[] = $row['area'];
        $data_kabinet[] = $row['total'];
    }
}

$q_komunitas = mysqli_query($koneksi, "SELECT area, COUNT(*) as total FROM data_geomapping WHERE jenis_lokasi = 'Komunitas' AND $where_sql GROUP BY area ORDER BY total DESC");
if($q_komunitas) {
    while($row = mysqli_fetch_assoc($q_komunitas)) {
        $label_komunitas[] = $row['area'];
        $data_komunitas[] = $row['total'];
    }
}

// --- 4. QUERY TABEL UTAMA ---
$q_mapping = mysqli_query($koneksi, "SELECT * FROM data_geomapping WHERE $where_sql ORDER BY tanggal_input DESC");
$jumlah_tampil = mysqli_num_rows($q_mapping);
?>

<?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-success" role="alert">
        <strong><i class="bi bi-check-circle-fill me-2"></i> Berhasil!</strong> Data lokasi baru telah tersimpan di sistem.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Geo-Mapping</h3>
        <p class="text-muted small">Data spasial lokasi Komunitas (Basecamp) dan Kabinet SPBKLU.</p>
    </div>
    <button class="btn btn-theme shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahLokasi">
        <i class="bi bi-geo-alt-fill me-1"></i> Tambah Lokasi
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-primary h-100">
            <div class="text-muted small fw-bold mb-2">TOTAL LOKASI</div>
            <div class="h3 fw-bold mb-0 text-dark"><?= $tot_semua ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-warning h-100">
            <div class="text-muted small fw-bold mb-2">KABINET SPBKLU</div>
            <div class="h3 fw-bold mb-0 text-dark"><?= $tot_kabinet ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-info h-100">
            <div class="text-muted small fw-bold mb-2">KOMUNITAS (BC)</div>
            <div class="h3 fw-bold mb-0 text-dark"><?= $tot_komunitas ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-success h-100">
            <div class="text-muted small fw-bold mb-2">LOKASI DEAL / AKTIF</div>
            <div class="h3 fw-bold mb-0 text-success"><?= $tot_deal ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card card-custom p-4 shadow-sm border-0 h-100">
            <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-pie-chart-fill text-warning me-2"></i>Persebaran SPBKLU (Kabinet)</h6>
            <p class="small text-muted mb-4">Distribusi lokasi mesin Kabinet Baterai per area.</p>
            <div style="height: 260px;">
                <canvas id="chartKabinet"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-custom p-4 shadow-sm border-0 h-100">
            <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Persebaran Basecamp (Komunitas)</h6>
            <p class="small text-muted mb-4">Distribusi lokasi kumpul driver/Basecamp per area.</p>
            <div style="height: 260px;">
                <canvas id="chartKomunitas"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom p-3 border-0 shadow-sm mb-4">
    <form method="GET" class="row g-2 align-items-center">
        <input type="hidden" name="asset" value="geomapping">
        
        <div class="col-md-3">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama lokasi atau area..." value="<?= $search_nama ?>">
        </div>
        <div class="col-md-3">
            <select name="jenis" class="form-select form-select-sm">
                <option value="">-- Semua Jenis Lokasi --</option>
                <option value="Komunitas" <?= ($filter_jenis == 'Komunitas') ? 'selected' : '' ?>>Komunitas</option>
                <option value="Kabinet" <?= ($filter_jenis == 'Kabinet') ? 'selected' : '' ?>>Kabinet</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="area" class="form-select form-select-sm">
                <option value="">-- Semua Wilayah --</option>
                <?php 
                $list_wilayah = ['Jakarta Pusat', 'Jakarta Utara', 'Jakarta Barat', 'Jakarta Selatan', 'Jakarta Timur', 'Bogor', 'Bekasi', 'Tangerang', 'Serang', 'Pandeglang', 'Lebak'];
                foreach($list_wilayah as $w) {
                    $selected = ($filter_area == $w) ? 'selected' : '';
                    echo "<option value='$w' $selected>$w</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-secondary flex-grow-1"><i class="bi bi-funnel"></i> Filter Peta</button>
            <a href="?asset=geomapping" class="btn btn-sm btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<div class="card card-custom p-0 overflow-hidden mb-4 border-0 shadow-sm">
    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0 text-dark">Data Spasial Lokasi</h6>
        <div class="fw-bold text-primary pe-2">
            Total: <span class="badge bg-primary rounded-pill"><?= $jumlah_tampil ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover m-0 align-middle">
            <thead class="text-center bg-light">
                <tr>
                    <th>Tgl Input</th>
                    <th>Jenis</th>
                    <th>Nama & Area</th>
                    <th>Koordinat Peta</th>
                    <th>Data Pemilik</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php if($jumlah_tampil > 0): while($m = mysqli_fetch_assoc($q_mapping)): ?>
                <tr>
                    <td data-label="Tgl Input" class="small text-muted"><?= date('d/m/Y', strtotime($m['tanggal_input'])) ?></td>
                    <td data-label="Jenis">
                        <span class="badge <?= $m['jenis_lokasi'] == 'Komunitas' ? 'bg-primary' : 'bg-warning text-dark' ?>">
                            <?= $m['jenis_lokasi'] ?>
                        </span>
                    </td>
                    <td data-label="Nama & Area" class="text-start">
                        <div class="fw-bold text-dark"><?= $m['nama_tempat'] ?></div>
                        <div class="small text-muted"><i class="bi bi-geo me-1"></i><?= $m['area'] ?></div>
                    </td>
                    <td data-label="Koordinat">
                        <div class="small font-monospace text-primary mb-1"><?= $m['koordinat_peta'] ?></div>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($m['koordinat_peta']) ?>" target="_blank" class="btn btn-sm btn-outline-danger" style="font-size: 10px; padding: 2px 6px;">
                            <i class="bi bi-map"></i> Buka Maps
                        </a>
                    </td>
                    <td data-label="Data Pemilik" class="text-start small">
                        <div><i class="bi bi-person me-1"></i><?= $m['nama_pemilik'] ?? '-' ?></div>
                        <div><i class="bi bi-telephone me-1"></i><?= $m['no_hp_pemilik'] ?? '-' ?></div>
                    </td>
<td data-label="Aksi">
                        <button class="btn btn-sm btn-outline-info shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $m['id_mapping'] ?>" title="Lihat Detail">
                            <i class="bi bi-eye"></i> Detail
                        </button>

                        <div class="modal fade text-start" id="modalDetail<?= $m['id_mapping'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content border-0 shadow">
                                    
                                    <div class="modal-header bg-light border-0 d-flex justify-content-between align-items-center">
                                        <h6 class="modal-title fw-bold m-0"><i class="bi bi-info-circle text-primary me-2"></i>Detail Lokasi: <?= $m['nama_tempat'] ?></h6>
                                        
                                        <div class="d-flex align-items-center gap-2">
                                            <button class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUpdate<?= $m['id_mapping'] ?>" data-bs-dismiss="modal" title="Edit Data Ini">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                    </div>                                    
                                    <div class="modal-body p-4">
                                        <div class="row g-3">
                                            
                                            <div class="col-12 mb-1 border-bottom pb-2">
                                                <h6 class="fw-bold text-dark m-0">1. Informasi Dasar</h6>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-muted small">Status Keputusan</div>
                                                <div class="fw-bold text-primary"><?= $m['status_keputusan'] ?? '-' ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-muted small">Area / Wilayah</div>
                                                <div class="fw-bold"><?= $m['area'] ?? '-' ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-muted small">Jenis Lokasi</div>
                                                <div class="fw-bold"><?= $m['jenis_lokasi'] ?? '-' ?></div>
                                            </div>
                                            <div class="col-12">
                                                <div class="text-muted small">Alamat Lengkap</div>
                                                <div class="fw-bold"><?= nl2br($m['alamat_lengkap'] ?? '-') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="text-muted small">Titik Koordinat Peta</div>
                                                <div class="fw-bold font-monospace text-primary"><?= $m['koordinat_peta'] ?? '-' ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="text-muted small">Petugas Survey</div>
                                                <div class="fw-bold"><?= $m['sales_input'] ?? '-' ?></div>
                                            </div>

                                            <div class="col-12 mt-4 mb-1 border-bottom pb-2">
                                                <h6 class="fw-bold text-dark m-0">2. Data Kepemilikan</h6>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-muted small">Nama Pemilik</div>
                                                <div class="fw-bold"><?= $m['nama_pemilik'] ?: '-' ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-muted small">No. HP Pemilik</div>
                                                <div class="fw-bold"><?= $m['no_hp_pemilik'] ?: '-' ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-muted small">Referensi Dari</div>
                                                <div class="fw-bold"><?= $m['referensi'] ?: '-' ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-muted small">Status Kepemilikan</div>
                                                <div class="fw-bold"><?= $m['kepemilikan_lokasi'] ?: '-' ?></div>
                                            </div>
                                            <?php if(!empty($m['tgl_akhir_sewa']) && $m['tgl_akhir_sewa'] != '0000-00-00'): ?>
                                            <div class="col-md-4">
                                                <div class="text-muted small text-danger">Tgl Akhir Sewa</div>
                                                <div class="fw-bold text-danger"><?= date('d M Y', strtotime($m['tgl_akhir_sewa'])) ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="col-md-4">
                                                <div class="text-muted small">Persetujuan Warga</div>
                                                <div class="fw-bold"><?= $m['persetujuan_keluarga'] ?: '-' ?></div>
                                            </div>

                                            <div class="col-12 mt-4 mb-1 border-bottom pb-2">
                                                <h6 class="fw-bold text-dark m-0">3. Kelistrikan & Kondisi Lokasi</h6>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-muted small">Bebas Banjir?</div>
                                                <div class="fw-bold"><?= $m['bebas_banjir'] ?: '-' ?></div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="text-muted small">IDPEL PLN (Nomor Meter)</div>
                                                <div class="fw-bold font-monospace text-success">
                                                    <?php if(!empty($m['idpel_pln1'])) echo "1. " . $m['idpel_pln1'] . "<br>"; ?>
                                                    <?php if(!empty($m['idpel_pln2'])) echo "2. " . $m['idpel_pln2'] . "<br>"; ?>
                                                    <?php if(!empty($m['idpel_pln3'])) echo "3. " . $m['idpel_pln3'] . "<br>"; ?>
                                                    <?php if(empty($m['idpel_pln1']) && empty($m['idpel_pln2']) && empty($m['idpel_pln3'])) echo "-"; ?>
                                                </div>
                                            </div>

                                            <div class="col-12 mt-4 mb-1 border-bottom pb-2">
                                                <h6 class="fw-bold text-dark m-0">4. Lampiran Media & Berkas</h6>
                                            </div>
                                            <div class="col-12">
                                                <div class="text-muted small mb-2">Dokumentasi & File:</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <?php for($i=1; $i<=5; $i++): ?>
                                                        <?php if(!empty($m["foto_$i"])): ?>
                                                            <a href="uploads/<?= $m["foto_$i"] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                                <i class="bi bi-image"></i> Foto <?= $i ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                    
                                                    <?php if(!empty($m['video_lokasi'])): ?>
                                                        <a href="uploads/<?= $m['video_lokasi'] ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-camera-video"></i> Video Lokasi
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php 
                                                    $dokumen_kabinet = [
                                                        'doc_nidi' => 'NIDI', 'doc_slo' => 'SLO', 'doc_sip' => 'SIP', 
                                                        'doc_spk' => 'SPK', 'doc_permohonan_pln' => 'Permohonan PLN', 
                                                        'doc_bayar_pln' => 'Bukti Bayar PLN', 'doc_bayar_sewa' => 'Bukti Sewa'
                                                    ];
                                                    foreach($dokumen_kabinet as $db_field => $label_nama): 
                                                        if(!empty($m[$db_field])): 
                                                    ?>
                                                        <a href="uploads/<?= $m[$db_field] ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-file-earmark-check"></i> <?= $label_nama ?>
                                                        </a>
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    ?>
                                                </div>
                                            </div>
                                            
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 bg-light">
                                        <button type="button" class="btn btn-secondary w-100 fw-bold" data-bs-dismiss="modal">Tutup Detail</button>
                                    </div>
                                </div>
                            </div>
                        </div>

<div class="modal fade text-start" id="modalUpdate<?= $m['id_mapping'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <form action="proses_update_geomapping.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="id_mapping" value="<?= $m['id_mapping'] ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-primary text-white border-0">
                                            <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data: <?= $m['nama_tempat'] ?></h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="small fw-bold mb-1">Status Keputusan</label>
                                                    <select name="status_keputusan" class="form-select border-primary" required>
                                                        <option value="Survey" <?= $m['status_keputusan'] == 'Survey' ? 'selected' : '' ?>>Survey</option>
                                                        <option value="Deal" <?= $m['status_keputusan'] == 'Deal' ? 'selected' : '' ?>>Deal</option>
                                                        <option value="Hold" <?= $m['status_keputusan'] == 'Hold' ? 'selected' : '' ?>>Hold</option>
                                                        <option value="Lost" <?= $m['status_keputusan'] == 'Lost' ? 'selected' : '' ?>>Lost</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="small fw-bold mb-1">Jenis Lokasi</label>
                                                    <select name="jenis_lokasi" class="form-select" required>
                                                        <option value="Komunitas" <?= $m['jenis_lokasi'] == 'Komunitas' ? 'selected' : '' ?>>Komunitas</option>
                                                        <option value="Kabinet" <?= $m['jenis_lokasi'] == 'Kabinet' ? 'selected' : '' ?>>Kabinet</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="small fw-bold mb-1">Area / Wilayah</label>
                                                    <input type="text" name="area" class="form-control" value="<?= $m['area'] ?>" required>
                                                </div>

                                                <div class="col-md-12">
                                                    <label class="small fw-bold mb-1">Nama Tempat</label>
                                                    <input type="text" name="nama_tempat" class="form-control" value="<?= $m['nama_tempat'] ?>" required>
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="small fw-bold mb-1">Alamat Lengkap</label>
                                                    <textarea name="alamat_lengkap" class="form-control" rows="2" required><?= $m['alamat_lengkap'] ?></textarea>
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="small fw-bold mb-1">Koordinat Peta</label>
                                                    <input type="text" name="koordinat_peta" class="form-control font-monospace text-primary" value="<?= $m['koordinat_peta'] ?>">
                                                </div>

                                                <div class="col-12 mt-3 mb-1 border-bottom pb-2">
                                                    <h6 class="fw-bold text-dark m-0">Data Kepemilikan & PLN</h6>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="small fw-bold mb-1">Nama Pemilik</label>
                                                    <input type="text" name="nama_pemilik" class="form-control" value="<?= $m['nama_pemilik'] ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="small fw-bold mb-1">No. HP Pemilik</label>
                                                    <input type="number" name="no_hp_pemilik" class="form-control" value="<?= $m['no_hp_pemilik'] ?>">
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label class="small fw-bold mb-1">IDPEL PLN 1</label>
                                                    <input type="text" name="idpel_pln1" class="form-control font-monospace" value="<?= $m['idpel_pln1'] ?? '' ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="small fw-bold mb-1">IDPEL PLN 2</label>
                                                    <input type="text" name="idpel_pln2" class="form-control font-monospace" value="<?= $m['idpel_pln2'] ?? '' ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="small fw-bold mb-1">IDPEL PLN 3</label>
                                                    <input type="text" name="idpel_pln3" class="form-control font-monospace" value="<?= $m['idpel_pln3'] ?? '' ?>">
                                                </div>

                                                <?php if($m['jenis_lokasi'] == 'Kabinet'): ?>
                                                    <div class="col-12 mt-4 mb-1 border-bottom pb-2">
                                                        <h6 class="fw-bold text-success m-0"><i class="bi bi-folder-check me-2"></i>Berkas Legalitas & PLN (Khusus Kabinet)</h6>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="small fw-bold mb-1">Upload NIDI</label>
                                                        <input type="file" name="doc_nidi" class="form-control form-control-sm" accept="image/*,application/pdf">
                                                        <?= !empty($m['doc_nidi']) ? '<small class="text-success"><i class="bi bi-check"></i> Sudah ada</small>' : '' ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="small fw-bold mb-1">Upload SLO</label>
                                                        <input type="file" name="doc_slo" class="form-control form-control-sm" accept="image/*,application/pdf">
                                                        <?= !empty($m['doc_slo']) ? '<small class="text-success"><i class="bi bi-check"></i> Sudah ada</small>' : '' ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="small fw-bold mb-1">Upload SIP</label>
                                                        <input type="file" name="doc_sip" class="form-control form-control-sm" accept="image/*,application/pdf">
                                                        <?= !empty($m['doc_sip']) ? '<small class="text-success"><i class="bi bi-check"></i> Sudah ada</small>' : '' ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="small fw-bold mb-1">Upload SPK</label>
                                                        <input type="file" name="doc_spk" class="form-control form-control-sm" accept="image/*,application/pdf">
                                                        <?= !empty($m['doc_spk']) ? '<small class="text-success"><i class="bi bi-check"></i> Sudah ada</small>' : '' ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="small fw-bold mb-1">Permohonan Pasang Baru</label>
                                                        <input type="file" name="doc_permohonan_pln" class="form-control form-control-sm" accept="image/*,application/pdf">
                                                        <?= !empty($m['doc_permohonan_pln']) ? '<small class="text-success"><i class="bi bi-check"></i> Sudah ada</small>' : '' ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="small fw-bold mb-1">Bukti Bayar PLN</label>
                                                        <input type="file" name="doc_bayar_pln" class="form-control form-control-sm" accept="image/*,application/pdf">
                                                        <?= !empty($m['doc_bayar_pln']) ? '<small class="text-success"><i class="bi bi-check"></i> Sudah ada</small>' : '' ?>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label class="small fw-bold mb-1">Bukti Bayar Sewa Lokasi</label>
                                                        <input type="file" name="doc_bayar_sewa" class="form-control form-control-sm border-warning" accept="image/*,application/pdf">
                                                        <?= !empty($m['doc_bayar_sewa']) ? '<small class="text-success"><i class="bi bi-check"></i> Sudah ada</small>' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 bg-light">
                                            <button type="button" class="btn btn-secondary fw-bold" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $m['id_mapping'] ?>" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary fw-bold">Simpan Perubahan</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        ```
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="py-5 text-muted text-center fst-italic">Data tidak ditemukan untuk filter ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="modal fade" id="modalTambahLokasi" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form action="/rumahojol/proses_geomapping.php" method="POST" enctype="multipart/form-data" class="w-100">
            <div class="modal-content border-0 shadow">
                
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold">Tambah Data Spasial Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-geo-alt me-2"></i>1. Informasi Lokasi & Status</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1">Jenis Lokasi*</label>
                            <select name="jenis_lokasi" class="form-select border-primary" required>
                                <option value="">-- Pilih --</option>
                                <option value="Komunitas">Komunitas (Basecamp)</option>
                                <option value="Kabinet">Kabinet SPBKLU</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1">Status Keputusan*</label>
                            <select name="status_keputusan" class="form-select border-primary" required>
                                <option value="Survey">Tahap Survey</option>
                                <option value="Deal">Deal (Disetujui)</option>
                                <option value="Hold">Hold (Ditunda)</option>
                                <option value="Lost">Lost (Ditolak/Batal)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1">Area / Wilayah*</label>
                            <select name="area" class="form-select border-primary" required>
                                <option value="">-- Pilih Wilayah --</option>
                                <?php 
                                foreach($list_wilayah as $w) { echo "<option value='$w'>$w</option>"; }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1 text-muted">Petugas Survey</label>
                            <input type="text" class="form-control bg-light text-muted fw-bold" value="<?= $_SESSION['nama_user'] ?? 'Sistem' ?>" readonly>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="small fw-bold mb-1">Nama Tempat / Komunitas*</label>
                            <input type="text" name="nama_tempat" class="form-control" placeholder="Contoh: BC Test, J&T CKR25..." required>
                        </div>
                        <div class="col-md-12">
                            <label class="small fw-bold mb-1">Alamat Lengkap*</label>
                            <textarea name="alamat_lengkap" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-md-12 mt-3">
                            <div class="p-3 bg-light border border-danger rounded">
                                <label class="small fw-bold mb-2 text-danger d-block">Titik Koordinat Peta (GPS)*</label>
                                <div class="input-group">
                                    <input type="text" name="koordinat_peta" id="input_koordinat" class="form-control font-monospace" placeholder="-6.171406, 106.783043" required readonly>
                                    <button type="button" class="btn btn-danger" id="btnDapatkanLokasi">
                                        <i class="bi bi-crosshair me-1"></i> Kunci Lokasi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i class="bi bi-building me-2"></i>2. Kepemilikan & Kelistrikan</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">Nama Pemilik Tempat</label>
                            <input type="text" name="nama_pemilik" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">No. HP Pemilik</label>
                            <input type="number" name="no_hp_pemilik" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">Referensi / Rekomendasi Dari</label>
                            <input type="text" name="referensi" class="form-control" placeholder="Contoh: Budi Sales / Iklan FB">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">Persetujuan Keluarga/Warga</label>
                            <select name="persetujuan_keluarga" class="form-select">
                                <option value="">-- Pilih --</option>
                                <option value="Ya">Ya, Sudah Setuju</option>
                                <option value="Tidak">Belum Setuju</option>
                                <option value="Proses">Sedang Diproses</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">Kepemilikan Lokasi</label>
                            <select name="kepemilikan_lokasi" id="select_kepemilikan" class="form-select">
                                <option value="">-- Pilih --</option>
                                <option value="Milik Sendiri">Milik Sendiri</option>
                                <option value="Sewa / Kontrak">Sewa / Kontrak</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-none" id="wrap_tgl_sewa">
                            <label class="small fw-bold mb-1 text-danger">Tanggal Akhir Sewa*</label>
                            <input type="date" name="tgl_akhir_sewa" id="input_tgl_sewa" class="form-control border-danger">
                        </div>
                        
                        <div class="col-md-12 mt-3 mb-1"><hr class="text-muted m-0"></div>

                        <div class="col-md-3">
                            <label class="small fw-bold mb-1">Bebas Banjir?</label>
                            <select name="bebas_banjir" class="form-select">
                                <option value="Tidak">Tidak</option>
                                <option value="Ya">Ya, Aman</option>
                                <option value="Kurang Tahu">Kurang Tahu</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1">IDPEL PLN 1</label>
                            <input type="text" name="idpel_pln1" class="form-control font-monospace">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1">IDPEL PLN 2</label>
                            <input type="text" name="idpel_pln2" class="form-control font-monospace">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1">IDPEL PLN 3</label>
                            <input type="text" name="idpel_pln3" class="form-control font-monospace">
                        </div>
                    </div>

                    <h6 class="fw-bold text-warning border-bottom pb-2 mb-3"><i class="bi bi-camera me-2"></i>3. Media Lampiran</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12"><small class="text-muted">Upload dokumentasi foto dan video lokasi (Maks 5 Foto, 1 Video):</small></div>
                        <div class="col-4"><input type="file" name="foto_1" class="form-control form-control-sm" accept="image/*"></div>
                        <div class="col-4"><input type="file" name="foto_2" class="form-control form-control-sm" accept="image/*"></div>
                        <div class="col-4"><input type="file" name="foto_3" class="form-control form-control-sm" accept="image/*"></div>
                        <div class="col-4"><input type="file" name="foto_4" class="form-control form-control-sm" accept="image/*"></div>
                        <div class="col-4"><input type="file" name="foto_5" class="form-control form-control-sm" accept="image/*"></div>
                        <div class="col-4">
                            <input type="file" name="video_lokasi" class="form-control form-control-sm border-warning" accept="video/*" title="Upload Video">
                        </div>
                    </div>
                </div> 
                
                <div class="modal-footer border-0 bg-light">
                    <button type="submit" class="btn btn-theme w-100 py-2 fw-bold">Simpan Data Spasial</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. GRAFIK KABINET (SPBKLU)
    const ctxKabinet = document.getElementById('chartKabinet');
    if(ctxKabinet) {
        new Chart(ctxKabinet, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($label_kabinet) ?>,
                datasets: [{
                    data: <?= json_encode($data_kabinet) ?>,
                    backgroundColor: ['#f59e0b', '#d97706', '#fbbf24', '#fcd34d', '#fde68a'],
                    borderWidth: 0, hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } } },
                cutout: '65%'
            }
        });
    }

    // 2. GRAFIK KOMUNITAS (BASECAMP)
    const ctxKomunitas = document.getElementById('chartKomunitas');
    if(ctxKomunitas) {
        new Chart(ctxKomunitas, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($label_komunitas) ?>,
                datasets: [{
                    data: <?= json_encode($data_komunitas) ?>,
                    backgroundColor: ['#3b82f6', '#2563eb', '#60a5fa', '#93c5fd', '#bfdbfe'],
                    borderWidth: 0, hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } } },
                cutout: '65%'
            }
        });
    }

    // 3. LOGIKA FORM TANGGAL SEWA
    var selectKepemilikan = document.getElementById('select_kepemilikan');
    var wrapTglSewa = document.getElementById('wrap_tgl_sewa');
    var inputTglSewa = document.getElementById('input_tgl_sewa');

    if(selectKepemilikan) {
        selectKepemilikan.addEventListener('change', function() {
            if(this.value === 'Sewa / Kontrak') {
                wrapTglSewa.classList.remove('d-none');
                inputTglSewa.setAttribute('required', 'required');
            } else {
                wrapTglSewa.classList.add('d-none');
                inputTglSewa.removeAttribute('required');
                inputTglSewa.value = '';
            }
        });
    }

    // 4. GPS LOKASI
    var btnLokasi = document.getElementById('btnDapatkanLokasi');
    var inputKoordinat = document.getElementById('input_koordinat');

    if(btnLokasi) {
        btnLokasi.addEventListener('click', function() {
            if (navigator.geolocation) {
                btnLokasi.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mencari...';
                btnLokasi.disabled = true;

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        var lat = position.coords.latitude;
                        var lng = position.coords.longitude;
                        inputKoordinat.value = lat + ", " + lng;
                        btnLokasi.innerHTML = '<i class="bi bi-check-circle me-1"></i> Terkunci';
                        btnLokasi.classList.replace('btn-danger', 'btn-success');
                    }, 
                    function(error) {
                        alert("Gagal mendapatkan lokasi GPS. Pastikan GPS HP Anda menyala dan Anda mengizinkan akses lokasi pada browser.");
                        btnLokasi.innerHTML = '<i class="bi bi-crosshair me-1"></i> Kunci Lokasi';
                        btnLokasi.disabled = false;
                        inputKoordinat.removeAttribute('readonly');
                        inputKoordinat.placeholder = "Isi manual: -6.xxx, 106.xxx";
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            } else {
                alert("Browser HP Anda tidak mendukung fitur Geolokasi.");
                inputKoordinat.removeAttribute('readonly');
            }
        });
    }
});
</script>