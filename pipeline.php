<style>
@media (max-width: 768px) {
    .table-responsive thead { display: none; }
    .table-responsive tr { display: block; margin-bottom: 15px; border: 1px solid #dee2e6; border-radius: 10px; background: #fff; padding: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .table-responsive td { display: flex; justify-content: space-between; align-items: center; border: none; padding: 5px 0; text-align: right; }
    .table-responsive td::before { content: attr(data-label); font-weight: bold; text-align: left; color: #6b7280; }
}
</style>

<?php
// 1. Ambil Parameter & Data Dasar
$filter_status = $_GET['filter'] ?? 'Semua';
$search_nama   = $_GET['search'] ?? '';
$filter_sales  = $_GET['sales'] ?? '';
$tgl_awal      = $_GET['tgl_awal'] ?? '';
$tgl_akhir     = $_GET['tgl_akhir'] ?? '';
$thn_ini       = date('Y');

// Ambil data Sales & Surveyor untuk Dropdown
$q_sales = mysqli_query($koneksi, "SELECT nama FROM users WHERE role IN ('Sales', 'Surveyor')");

// ========================================================
// 2. LOGIKA FILTER QUERY (SINKRON UNTUK TABEL & COUNTER)
// ========================================================
$where_clauses = ["1=1"];
if ($filter_status == 'Pipelineku') {
    $where_clauses[] = "sales_ditugaskan = '$nama_user_aktif'";
} elseif ($filter_status != 'Semua') {
    $where_clauses[] = "status = '$filter_status'";
}

if (!empty($search_nama)) $where_clauses[] = "(nama_prospek LIKE '%$search_nama%' OR no_hp LIKE '%$search_nama%')";
if (!empty($filter_sales)) $where_clauses[] = "sales_ditugaskan = '$filter_sales'";
if (!empty($tgl_awal)) $where_clauses[] = "DATE(waktu_input) >= '$tgl_awal'";
if (!empty($tgl_akhir)) $where_clauses[] = "DATE(waktu_input) <= '$tgl_akhir'";

$where_sql = implode(" AND ", $where_clauses);

// Query Utama untuk Tabel
$q_pipeline = mysqli_query($koneksi, "SELECT * FROM data_pipeline WHERE $where_sql ORDER BY waktu_input DESC");
// PENGHITUNG OTOMATIS: Menghitung jumlah baris hasil filter saat ini
$jumlah_tampil = mysqli_num_rows($q_pipeline);

// ========================================================
// 3. QUERY UNTUK KARTU DASHBOARD (GLOBAL)
// ========================================================
$tot_semua = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_pipeline"))['tot'] ?? 0;
$tot_deal  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_pipeline WHERE status = 'Deal'"))['tot'] ?? 0;
$tot_survey = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_pipeline WHERE status = 'Survey'"))['tot'] ?? 0;
$tot_lost  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_pipeline WHERE status IN ('Lost', 'Terminate')"))['tot'] ?? 0;
$tot_belum = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_pipeline WHERE status = 'Belum Dihubungi'"))['tot'] ?? 0;

// ========================================================
// 4. LOGIKA DATA GRAFIK (CHART.JS)
// ========================================================
$data_sales_deal = [];
$q_grafik = mysqli_query($koneksi, "SELECT sales_ditugaskan, MONTH(waktu_input) as bln, COUNT(*) as jumlah FROM data_pipeline WHERE YEAR(waktu_input) = '$thn_ini' AND status = 'Deal' GROUP BY sales_ditugaskan, MONTH(waktu_input)");
while($gs = mysqli_fetch_assoc($q_grafik)) {
    $s_name = $gs['sales_ditugaskan'];
    $s_bln  = (int)$gs['bln'];
    if(!isset($data_sales_deal[$s_name])) { $data_sales_deal[$s_name] = array_fill(0, 12, 0); }
    $data_sales_deal[$s_name][$s_bln - 1] = (int)$gs['jumlah'];
}
$warna_line = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Pipeline Sales</h3>
        <p class="text-muted small">Lacak prospek dan performa konversi tim lapangan.</p>
    </div>
    <button class="btn btn-theme shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPipelineBaru">
        <i class="bi bi-person-plus me-1"></i> Prospek Baru
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-primary h-100">
            <div class="text-muted small fw-bold mb-2">TOTAL PROSPEK</div>
            <div class="h3 fw-bold mb-1 text-dark"><?= $tot_semua ?></div>
        </div>
    </div>
    <div class="col-md">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-success h-100">
            <div class="text-muted small fw-bold mb-2">BERHASIL DEAL</div>
            <div class="h3 fw-bold mb-1 text-success"><?= $tot_deal ?></div>
        </div>
    </div>
    <div class="col-md">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-info h-100">
            <div class="text-muted small fw-bold mb-2">SEDANG SURVEY</div>
            <div class="h3 fw-bold mb-1 text-info"><?= $tot_survey ?></div>
        </div>
    </div>
    <div class="col-md">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-dark h-100">
            <div class="text-muted small fw-bold mb-2">BELUM DIHUBUNGI</div>
            <div class="h3 fw-bold mb-1 text-dark"><?= $tot_belum ?></div>
        </div>
    </div>
    <div class="col-md">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-danger h-100">
            <div class="text-muted small fw-bold mb-2">LOST / TERMINATE</div>
            <div class="h3 fw-bold mb-1 text-danger"><?= $tot_lost ?></div>
        </div>
    </div>
</div>

<div class="card card-custom border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h6 class="fw-bold text-dark"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Trend Perolehan Deal per Sales (<?= $thn_ini ?>)</h6>
        <div style="height: 300px;"><canvas id="chartTrendSales"></canvas></div>
    </div>
</div>

<div class="card card-custom p-3 border-0 shadow-sm mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="asset" value="pipeline">
        <input type="hidden" name="filter" value="<?= $filter_status ?>">
        <div class="col-md-3">
            <label class="small fw-bold text-muted">Cari Nama/HP</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik di sini..." value="<?= $search_nama ?>">
        </div>
        <div class="col-md-2">
            <label class="small fw-bold text-muted">Dari Tanggal</label>
            <input type="date" name="tgl_awal" class="form-control form-control-sm" value="<?= $tgl_awal ?>">
        </div>
        <div class="col-md-2">
            <label class="small fw-bold text-muted">Sampai Tanggal</label>
            <input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= $tgl_akhir ?>">
        </div>
        <div class="col-md-2">
            <label class="small fw-bold text-muted">Sales</label>
            <select name="sales" class="form-select form-select-sm">
                <option value="">-- Semua --</option>
                <?php mysqli_data_seek($q_sales, 0); while($s = mysqli_fetch_assoc($q_sales)): ?>
                    <option value="<?= $s['nama'] ?>" <?= ($filter_sales == $s['nama']) ? 'selected' : '' ?>><?= $s['nama'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-secondary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
            <a href="?asset=pipeline" class="btn btn-sm btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<div class="card card-custom p-0 overflow-hidden mb-4 border-0 shadow-sm">
    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
        <div class="btn-group shadow-sm">
            <a href="?asset=pipeline&filter=Pipelineku" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Pipelineku') ? 'active' : '' ?>">Pipelineku</a>
            <a href="?asset=pipeline&filter=Semua" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Semua') ? 'active' : '' ?>">Semua</a>
            <a href="?asset=pipeline&filter=Belum Dihubungi" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Belum Dihubungi') ? 'active' : '' ?>">Belum Dihubungi</a>
            <a href="?asset=pipeline&filter=Survey" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Survey') ? 'active' : '' ?>">Survey</a>
            <a href="?asset=pipeline&filter=Deal" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Deal') ? 'active' : '' ?>">Deal</a>
            <a href="?asset=pipeline&filter=Lost" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Lost') ? 'active' : '' ?>">Lost</a>
            <a href="?asset=pipeline&filter=Terminate" class="btn btn-sm btn-outline-danger <?= ($filter_status == 'Terminate') ? 'active' : '' ?>">Terminate</a>
        </div>
        <div class="fw-bold text-primary pe-2">
            Total: <span class="badge bg-primary rounded-pill"><?= $jumlah_tampil ?></span>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover m-0 align-middle">
            <thead class="text-center bg-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Prospek</th>
                    <th>Sales/Surveyor</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php 
    // Ambil semua motor dari master yang statusnya Ready
    $q_motor_ready = mysqli_query($koneksi, "SELECT nopol FROM master_motor WHERE status_terkini = 'Ready'");
    $list_motor = [];
    while($m = mysqli_fetch_assoc($q_motor_ready)){
        $list_motor[] = $m['nopol'];
    }
?>
                <?php if($jumlah_tampil > 0): ?>
                    <?php while($p = mysqli_fetch_assoc($q_pipeline)): 
                        $bg = 'bg-secondary';
                        if($p['status'] == 'Deal') $bg = 'bg-success';
                        elseif($p['status'] == 'Survey') $bg = 'bg-info';
                        elseif($p['status'] == 'Terminate') $bg = 'bg-danger';
                        elseif($p['status'] == 'Belum Dihubungi') $bg = 'bg-dark';
                    ?>
                    <tr>
                        <td data-label="Tanggal"><?= date('d/m/y', strtotime($p['waktu_input'])) ?></td>
                        <td data-label="Prospek" class="text-start"><b><?= $p['nama_prospek'] ?></b></td>
                        <td data-label="Sales"><?= $p['sales_ditugaskan'] ?></td>
                        <td data-label="Status"><span class="badge <?= $bg ?>"><?= $p['status'] ?></span></td>
<td data-label="AKSI">
    <div class="d-flex gap-2 justify-content-center">
        <?php 
            $no_wa = $p['no_hp']; 
            if(substr($no_wa, 0, 1) == '0') { $no_wa = '62' . substr($no_wa, 1); }
        ?>
        <a href="https://wa.me/<?= $no_wa ?>" target="_blank" class="btn btn-sm btn-success shadow-sm" title="Hubungi via WA">
            <i class="bi bi-whatsapp"></i> Hubungi
        </a>                            
        
        <?php if($p['status'] == 'Deal'): ?>
            <button class="btn btn-sm btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUpdate<?= $p['id_pipeline'] ?>"><i class="bi bi-check-circle"></i> Berkas Deal</button>
        <?php else: ?>
            <button class="btn btn-sm btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUpdate<?= $p['id_pipeline'] ?>">Update</button>
        <?php endif; ?>
    </div>

<div class="modal fade text-start" id="modalUpdate<?= $p['id_pipeline'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-scrollable">
                                <form action="proses_update_pipeline.php" method="POST" enctype="multipart/form-data">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header border-0 bg-light">
                                            <h6 class="modal-title fw-bold">Update Status Pipeline</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        
                                        <div class="modal-body p-4 text-start">
                                            <input type="hidden" name="id_pipeline" value="<?= $p['id_pipeline'] ?>">

                                            <div class="mb-4 p-3 bg-light rounded border border-secondary shadow-sm">
                                                <h6 class="fw-bold border-bottom border-secondary pb-2 mb-2"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Detail Prospek</h6>
                                                <div class="row small g-2">
                                                    <div class="col-4 text-muted">Nama Prospek</div><div class="col-8 fw-bold">: <?= $p['nama_prospek'] ?></div>
                                                    <div class="col-4 text-muted">Nomor WA</div><div class="col-8 fw-bold text-success">: <?= $p['no_hp'] ?></div>
                                                    <div class="col-4 text-muted">Sales Assign</div><div class="col-8 fw-bold">: <?= $p['sales_ditugaskan'] ?></div>
                                                    <div class="col-4 text-muted">Tgl Input</div><div class="col-8 fw-bold">: <?= date('d M Y, H:i', strtotime($p['waktu_input'])) ?></div>
                                                </div>
                                            </div>

                                            <?php $current_status = $p['status'] ?: 'Belum Dihubungi'; ?>
                                            <div class="mb-3">
                                                <label class="small fw-bold mb-1">Pindah ke Status*</label>
                                                <select name="status_baru" class="form-select border-primary fw-bold text-primary" onchange="toggleFormUpdate(this, <?= $p['id_pipeline'] ?>)" <?= ($current_status == 'Lost' || $current_status == 'Terminate') ? 'disabled' : 'required' ?>>
                                                    
                                                    <option value="<?= $current_status ?>" selected>-- Tetap di: <?= $current_status ?> --</option>

                                                    <?php if ($current_status == 'Belum Dihubungi'): ?>
                                                        <option value="Survey">Lanjut ke: Survey</option>
                                                        <option value="Hold">Hold (Ditunda)</option>
                                                        <option value="Lost">Lost (Batal/Gagal)</option>

                                                    <?php elseif ($current_status == 'Survey' || $current_status == 'Hold'): ?>
                                                        <option value="Deal">Lanjut ke: Deal (Berhasil)</option>
                                                        <option value="Hold">Hold (Ditunda)</option>
                                                        <option value="Lost">Lost (Batal/Gagal)</option>

                                                    <?php elseif ($current_status == 'Deal'): ?>
                                                        <option value="Terminate">Terminate (Berhenti Sewa)</option>
                                                    <?php endif; ?>

                                                </select>

                                                <?php if ($current_status == 'Lost' || $current_status == 'Terminate'): ?>
                                                    <input type="hidden" name="status_baru" value="<?= $current_status ?>">
                                                    <small class="text-danger d-block mt-1"><i class="bi bi-lock-fill"></i> Status ini sudah final dan dikunci.</small>
                                                <?php endif; ?>
                                            </div>

                                            <div class="mb-3">
                                                <label class="small fw-bold mb-1">Alasan Singkat</label>
                                                <select name="alasan_singkat" class="form-select">
                                                    <option value="">-- Pilih Alasan Singkat --</option>
                                                    <option value="Harga/DP Tidak Cocok" <?= ($p['alasan_singkat'] ?? '') == 'Harga/DP Tidak Cocok' ? 'selected' : '' ?>>Harga/DP Tidak Cocok</option>
                                                    <option value="Jarak Terlalu Jauh" <?= ($p['alasan_singkat'] ?? '') == 'Jarak Terlalu Jauh' ? 'selected' : '' ?>>Jarak Terlalu Jauh</option>
                                                    <option value="Tidak Lolos BI Checking" <?= ($p['alasan_singkat'] ?? '') == 'Tidak Lolos BI Checking' ? 'selected' : '' ?>>Tidak Lolos BI Checking</option>
                                                    <option value="Sudah Dapat Unit Lain" <?= ($p['alasan_singkat'] ?? '') == 'Sudah Dapat Unit Lain' ? 'selected' : '' ?>>Sudah Dapat Unit Lain</option>
                                                    <option value="Lainnya" <?= ($p['alasan_singkat'] ?? '') == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="small fw-bold mb-1">Catatan Hasil Survey/Follow Up*</label>
                                                <textarea name="keterangan" class="form-control" rows="3" required><?= $p['keterangan_status'] ?? '' ?></textarea>
                                            </div>
                                            
                                            <div id="wrap_lost_<?= $p['id_pipeline'] ?>" class="p-3 bg-light rounded border mb-3 <?= $current_status == 'Lost' ? '' : 'd-none' ?>">
                                                <label class="small fw-bold mb-1 text-danger"><i class="bi bi-x-circle me-1"></i>Bukti Chat Penolakan (Lost)</label>
                                                <input type="file" name="foto_bukti" class="form-control form-control-sm" accept="image/*">
                                            </div>

                                            <div id="wrap_survey_<?= $p['id_pipeline'] ?>" class="p-3 bg-light rounded border mb-3 <?= in_array($current_status, ['Survey', 'Deal']) ? '' : 'd-none' ?>">
                                                <label class="small fw-bold mb-1 text-info"><i class="bi bi-house me-1"></i>Foto Lingkungan</label>
                                                <input type="file" name="foto_lingkungan" class="form-control form-control-sm" accept="image/*" multiple>
                                            </div>

                                            <div id="wrap_deal_<?= $p['id_pipeline'] ?>" class="p-3 bg-light border border-success rounded <?= $current_status == 'Deal' ? '' : 'd-none' ?>">
                                                <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i class="bi bi-folder-check me-2"></i>Berkas Administrasi Deal</h6>
                                                
                                                <label class="small fw-bold mb-1">Nomor Plat Unit</label>
                                                <input type="text" list="dataMotor<?= $p['id_pipeline'] ?>" name="nomor_plat" class="form-control form-control-sm mb-3 font-monospace" placeholder="Ketik atau pilih plat nomor..." value="<?= $p['nomor_plat'] ?? '' ?>" autocomplete="off">

                                                <datalist id="dataMotor<?= $p['id_pipeline'] ?>">
                                                    <?php foreach($list_motor as $plat): ?>
                                                        <option value="<?= $plat ?>">Tersedia (Ready)</option>
                                                    <?php endforeach; ?>
                                                    
                                                    <?php if(!empty($p['nomor_plat']) && !in_array($p['nomor_plat'], $list_motor)): ?>
                                                        <option value="<?= $p['nomor_plat'] ?>">Terpakai Saat Ini</option>
                                                    <?php endif; ?>
                                                </datalist>
                                                <label class="small fw-bold mb-1">Foto KTP</label>
                                                <input type="file" name="foto_ktp" class="form-control form-control-sm mb-2" accept="image/*">

                                                <label class="small fw-bold mb-1">Foto SIM</label>
                                                <input type="file" name="foto_sim" class="form-control form-control-sm mb-2" accept="image/*">

                                                <label class="small fw-bold mb-1">Foto KK</label>
                                                <input type="file" name="foto_kk" class="form-control form-control-sm mb-2" accept="image/*">
                                            </div>
                                        </div>
                                        
                                        <div class="modal-footer border-0 bg-light">
                                            <?php if ($current_status != 'Lost' && $current_status != 'Terminate'): ?>
                                                <button type="submit" name="update_status" class="btn btn-primary w-100 fw-bold">Simpan Perubahan</button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-secondary w-100 fw-bold" data-bs-dismiss="modal">Tutup</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                                        <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="py-5 text-muted fst-italic">Data tidak ditemukan untuk filter ini.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalPipelineBaru" tabindex="-1">
    <div class="modal-dialog">
        <form action="/rumahojol/proses_pipeline.php" method="POST" enctype="multipart/form-data">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Tambah Prospek Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Nama Prospek*</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">No. HP (WhatsApp)*</label>
                        <input type="number" name="hp" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">Channel</label>
                            <select name="channel" class="form-select" onchange="toggleFotoMedsos(this)">
                                <option value="Brosur">Brosur</option>
                                <option value="Instagram">Instagram</option>
                                <option value="Facebook">Facebook</option>
                                <option value="TikTok">TikTok</option> </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">Sales*</label>
                            <select name="sales" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <?php mysqli_data_seek($q_sales, 0); while($s = mysqli_fetch_assoc($q_sales)): ?>
                                    <option value="<?= $s['nama'] ?>"><?= $s['nama'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div id="wrap_foto_medsos" class="mb-3 p-3 bg-light rounded border d-none">
                        <label class="small fw-bold mb-1 text-primary"><i class="bi bi-camera me-1"></i>Bukti Chat / Referensi Medsos</label>
                        <input type="file" name="foto_chat[]" class="form-control form-control-sm border-primary" accept="image/*" multiple>
                        <small class="text-muted d-block mt-1">Bisa pilih lebih dari 1 foto sekaligus.</small>
                    </div>

                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="submit" class="btn btn-theme w-100 py-2 fw-bold">Simpan Prospek</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    // ==========================================
    // 1. FUNGSI UNTUK MODAL (Harus di luar agar terbaca HTML)
    // ==========================================
    
    // Cek Channel Medsos
    function toggleFotoMedsos(selectObj) {
        var wrapFoto = document.getElementById('wrap_foto_medsos');
        if(wrapFoto) {
            if(selectObj.value === 'Instagram' || selectObj.value === 'Facebook') {
                wrapFoto.classList.remove('d-none');
            } else {
                wrapFoto.classList.add('d-none');
            }
        }
    }

    // Cek Status Survey/Deal/Lost
    function toggleFormUpdate(selectObj, idPipeline) {
        var val = selectObj.value;
        
        // Sembunyikan semua dulu
        document.getElementById('wrap_lost_' + idPipeline).classList.add('d-none');
        document.getElementById('wrap_survey_' + idPipeline).classList.add('d-none');
        document.getElementById('wrap_deal_' + idPipeline).classList.add('d-none');

        // Munculkan sesuai pilihan
        if (val === 'Lost') {
            document.getElementById('wrap_lost_' + idPipeline).classList.remove('d-none');
        } else if (val === 'Survey') {
            document.getElementById('wrap_survey_' + idPipeline).classList.remove('d-none');
        } else if (val === 'Deal') {
            document.getElementById('wrap_survey_' + idPipeline).classList.remove('d-none');
            document.getElementById('wrap_deal_' + idPipeline).classList.remove('d-none');
        }
    }

    // ==========================================
    // 2. LOGIKA GRAFIK (Harus nunggu halaman selesai dimuat)
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() {
        var canvasTrend = document.getElementById('chartTrendSales');
        if (!canvasTrend) return;
        
        var ctx = canvasTrend.getContext('2d');
        var datasets = [];
        
        <?php 
        $color_index = 0;
        if(!empty($data_sales_deal) && !empty($warna_line)):
            foreach($data_sales_deal as $nama => $data_per_bulan): 
                $warna = $warna_line[$color_index % count($warna_line)];
        ?>
        datasets.push({
            label: '<?= $nama ?>',
            data: <?= json_encode(array_values($data_per_bulan)) ?>,
            borderColor: '<?= $warna ?>',
            backgroundColor: '<?= $warna ?>',
            borderWidth: 3, tension: 0.3, pointRadius: 4
        });
        <?php 
            $color_index++; 
            endforeach; 
        endif;
        ?>

        new Chart(ctx, {
            type: 'line',
            data: { 
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], 
                datasets: datasets 
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { grid: { display: false } } }
            }
        });
    });
</script>