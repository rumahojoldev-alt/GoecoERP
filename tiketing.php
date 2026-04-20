<?php
// Tangkap filter status dari URL (Default: Semua)
$filter_status  = $_GET['filter'] ?? 'Semua';
$search_tiket   = $_GET['search'] ?? '';
$filter_mekanik = $_GET['mekanik'] ?? '';
$tgl_awal       = $_GET['tgl_awal'] ?? '';
$tgl_akhir      = $_GET['tgl_akhir'] ?? '';

// Asumsi ada variabel user yang login (sesuaikan dengan session kamu jika perlu)
$nama_user_aktif = $_SESSION['nama_user'] ?? 'Superadmin'; 

// 1. Ambil data Nopol & Part
$q_nopol = mysqli_query($koneksi, "SELECT * FROM master_motor");
$q_part = mysqli_query($koneksi, "SELECT * FROM master_sparepart");
$q_mekanik_dropdown = mysqli_query($koneksi, "SELECT nama FROM users WHERE role = 'Mekanik'");

// 2. Hitung Data Ringkasan (Metrics Card)
$q_open = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_tiketing WHERE status != 'Completed'");
$tot_open = mysqli_fetch_assoc($q_open)['tot'] ?? 0;

$bln_ini = date('m'); $thn_ini = date('Y');
$q_done = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM data_tiketing WHERE status = 'Completed' AND MONTH(waktu_selesai) = '$bln_ini' AND YEAR(waktu_selesai) = '$thn_ini'");
$tot_done = mysqli_fetch_assoc($q_done)['tot'] ?? 0;

$q_aset = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM master_motor");
$tot_aset = mysqli_fetch_assoc($q_aset)['tot'] ?? 0;

$q_mekanik_count = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM users WHERE role = 'Mekanik'");
$tot_mekanik = mysqli_fetch_assoc($q_mekanik_count)['tot'] ?? 0;

// 3. Siapkan Data Grafik (Bulanan)
$data_grafik = array_fill(1, 12, 0); 
$q_g = mysqli_query($koneksi, "SELECT MONTH(created_at) as bln, COUNT(*) as jumlah FROM data_tiketing WHERE YEAR(created_at) = '$thn_ini' GROUP BY MONTH(created_at)");
while($g = mysqli_fetch_assoc($q_g)) { $data_grafik[$g['bln']] = $g['jumlah']; }
$data_json_tiket = json_encode(array_values($data_grafik));

// 4. Bangun Syarat Pencarian (WHERE) untuk Tabel Utama
$where_clauses = ["1=1"];

// Logika Tab Status
if ($filter_status == 'Tiketku') {
    $where_clauses[] = "nama_mekanik = '$nama_user_aktif'"; 
} elseif ($filter_status == 'Dikerjakan') {
    $where_clauses[] = "status = 'In Progress'";
} elseif ($filter_status == 'Selesai') {
    $where_clauses[] = "status = 'Completed'";
} elseif ($filter_status == 'Terbuka') {
    $where_clauses[] = "status = 'Open'";
}

// Logika Form Pencarian
if (!empty($search_tiket)) $where_clauses[] = "(nopol LIKE '%$search_tiket%' OR no_tiket LIKE '%$search_tiket%')";
if (!empty($filter_mekanik)) $where_clauses[] = "nama_mekanik = '$filter_mekanik'";
if (!empty($tgl_awal)) $where_clauses[] = "DATE(created_at) >= '$tgl_awal'";
if (!empty($tgl_akhir)) $where_clauses[] = "DATE(created_at) <= '$tgl_akhir'";

$where_sql = implode(" AND ", $where_clauses);

// Query Utama Tabel Tiket
$q_tiket = mysqli_query($koneksi, "SELECT * FROM data_tiketing WHERE $where_sql ORDER BY created_at DESC");
$jumlah_tampil = mysqli_num_rows($q_tiket);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Manajemen Tiket Layanan</h3>
        <p class="text-muted small">Kelola perbaikan unit dan pemantauan durasi kerja mekanik.</p>
    </div>
    <button class="btn btn-theme shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#modalNewTicket">
        <i class="bi bi-plus-lg me-1"></i> Buat Tiket Baru
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-warning h-100">
            <div class="text-muted small fw-bold mb-2">TIKET TERBUKA</div>
            <div class="h3 fw-bold mb-1 text-dark"><?= $tot_open ?></div>
            <div class="small text-warning"><i class="bi bi-gear-fill spin-icon me-1"></i> Sedang proses</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-success h-100">
            <div class="text-muted small fw-bold mb-2">SELESAI BULAN INI</div>
            <div class="h3 fw-bold mb-1 text-dark"><?= $tot_done ?></div>
            <div class="small text-success"><i class="bi bi-check-circle me-1"></i> Kinerja Mekanik</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-primary h-100">
            <div class="text-muted small fw-bold mb-2">TOTAL ASET</div>
            <div class="h3 fw-bold mb-1 text-dark"><?= $tot_aset ?></div>
            <div class="small text-muted">Unit terdaftar</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm border-start border-4 border-info h-100">
            <div class="text-muted small fw-bold mb-2">TIM MEKANIK</div>
            <div class="h3 fw-bold mb-1 text-dark"><?= $tot_mekanik ?></div>
            <div class="small text-muted">Staf Aftersales</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card card-custom p-4 shadow-sm border-0">
            <h6 class="fw-bold mb-4"><i class="bi bi-graph-up me-2 text-success"></i>Tren Tiket Servis (Tahun <?= $thn_ini ?>)</h6>
            <div style="height: 250px;">
                <canvas id="tiketChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom p-3 border-0 shadow-sm mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="asset" value="tiketing">
        <input type="hidden" name="filter" value="<?= $filter_status ?>">
        
        <div class="col-md-3">
            <label class="small fw-bold text-muted">Cari Nopol / No Tiket</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik di sini..." value="<?= $search_tiket ?>">
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
            <label class="small fw-bold text-muted">Mekanik</label>
            <select name="mekanik" class="form-select form-select-sm">
                <option value="">-- Semua Mekanik --</option>
                <?php if($q_mekanik_dropdown) { mysqli_data_seek($q_mekanik_dropdown, 0); while($m = mysqli_fetch_assoc($q_mekanik_dropdown)): ?>
                    <option value="<?= $m['nama'] ?>" <?= ($filter_mekanik == $m['nama']) ? 'selected' : '' ?>><?= $m['nama'] ?></option>
                <?php endwhile; } ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-secondary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
            <a href="?asset=tiketing" class="btn btn-sm btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<div class="card card-custom p-0 overflow-hidden mb-4 border-0 shadow-sm">
    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
        <div class="btn-group shadow-sm">
            <a href="?asset=tiketing&filter=Semua" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Semua') ? 'active' : '' ?>">Semua</a>
            <a href="?asset=tiketing&filter=Tiketku" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Tiketku') ? 'active' : '' ?>">Tiketku</a>
            <a href="?asset=tiketing&filter=Terbuka" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Terbuka') ? 'active' : '' ?>">Terbuka</a>
            <a href="?asset=tiketing&filter=Dikerjakan" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Dikerjakan') ? 'active' : '' ?>">Dikerjakan</a>
            <a href="?asset=tiketing&filter=Selesai" class="btn btn-sm btn-outline-primary <?= ($filter_status == 'Selesai') ? 'active' : '' ?>">Selesai</a>
        </div>
        
        <div class="fw-bold text-primary pe-2">
            Total: <span class="badge bg-primary rounded-pill"><?= $jumlah_tampil ?></span>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover m-0 align-middle">
            <thead class="text-center bg-light">
                <tr>
                    <th>Info Tiket</th>
                    <th>Status & Durasi</th>
                    <th>Unit</th>
                    <th>Mekanik</th>
                    <th>Total Biaya</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php if(mysqli_num_rows($q_tiket) > 0): ?>
                    <?php while($t = mysqli_fetch_assoc($q_tiket)): ?>
                    <tr>
                        <td class="text-start ps-4">
                            <div class="fw-bold text-dark"><?= $t['no_tiket'] ?></div>
                            <div class="small text-muted"><?= substr($t['deskripsi'] ?? '', 0, 30) ?>...</div>
                        </td>
                        <td>
                            <?php if($t['status'] == 'Completed'): ?>
                                <span class="badge bg-success mb-1">Selesai</span><br>
                                <small class="text-muted"><i class="bi bi-clock-history me-1"></i><?= $t['durasi_menit'] ?? 0 ?>m</small>
                            <?php else: ?>
                                <span class="badge bg-light text-warning border border-warning shadow-sm mb-1">
                                    <i class="bi bi-gear-fill spin-icon me-1"></i> Dikerjakan
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-soft-theme text-dark"><?= $t['nopol'] ?></span></td>
                        <td class="fw-medium text-secondary"><?= $t['nama_mekanik'] ?></td>
                        <td class="fw-bold text-primary">Rp <?= number_format($t['total_biaya'] ?? 0, 0, ',', '.') ?></td>
                        <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalEditTiket<?= $t['id_tiket'] ?>">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                        </div> 
                       </td>
                    </tr>
<div class="modal fade" id="modalEditTiket<?= $t['id_tiket'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
        <form action="proses_edit_tiket.php" method="POST" enctype="multipart/form-data">           
        <div class="modal-header">
          <h5 class="modal-title">Edit Pekerjaan Mekanik</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
<div class="modal-body">
          <input type="hidden" name="id_tiket" value="<?= $t['id_tiket'] ?>">
          
          <?php
            // Cari driver terakhir
            $q_driver = mysqli_query($koneksi, "SELECT nama_prospek, no_hp FROM data_pipeline WHERE nomor_plat = '".$t['nopol']."' ORDER BY id_pipeline DESC LIMIT 1");
            $d_driver = mysqli_fetch_assoc($q_driver);
          ?>

          <div class="mb-3">
            <label class="form-label small fw-bold text-danger"><i class="bi bi-exclamation-triangle"></i> Keluhan / Keterangan Masalah</label>
            <textarea class="form-control bg-light" rows="2" readonly><?= $t['deskripsi'] ?></textarea>
          </div>

          <div class="row mb-3">
              <div class="col-md-4">
                  <label class="form-label small fw-bold">Nomor Plat</label>
                  <input type="text" class="form-control bg-light" value="<?= $t['nopol'] ?>" readonly>
              </div>
              <div class="col-md-4">
                  <label class="form-label small fw-bold">Eks-Driver</label>
                  <input type="text" class="form-control bg-light" value="<?= $d_driver ? $d_driver['nama_prospek'] : '-' ?>" readonly>
              </div>
              <div class="col-md-4">
                  <label class="form-label small fw-bold">No. HP</label>
                  <input type="text" class="form-control bg-light" value="<?= $d_driver ? $d_driver['no_hp'] : '-' ?>" readonly>
              </div>
          </div>

          <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Pilih Mekanik</label>
                <select name="nama_mekanik" class="form-select" required>
                    <option value="">-- Pilih --</option>
                    <option value="Alfin" <?= ($t['nama_mekanik'] == 'Alfin') ? 'selected' : '' ?>>Alfin</option>
                    <option value="Rahmat" <?= ($t['nama_mekanik'] == 'Rahmat') ? 'selected' : '' ?>>Rahmat</option>
                    <option value="Kiki" <?= ($t['nama_mekanik'] == 'Kiki') ? 'selected' : '' ?>>Kiki</option>
                    <option value="Iksan" <?= ($t['nama_mekanik'] == 'Iksan') ? 'selected' : '' ?>>Iksan</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Status Pekerjaan</label>
                <select name="status" class="form-select">
                    <option value="In Progress" <?= ($t['status'] == 'In Progress') ? 'selected' : '' ?>>In Progress (Dikerjakan)</option>
                    <option value="Completed" <?= ($t['status'] == 'Completed') ? 'selected' : '' ?>>Completed (Selesai)</option>
                </select>
              </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Pilih Sparepart yang Diganti</label>
            <input type="text" class="form-control form-control-sm mb-2 cari-sparepart" placeholder="🔍 Cari nama/tipe...">

            <div class="border rounded p-2 bg-light kotak-daftar-sparepart" style="max-height: 150px; overflow-y: auto;">
                <?php 
                $q_sp = mysqli_query($koneksi, "SELECT item_id, name_standard, retail_price, tipe_produk, color FROM master_sparepart ORDER BY name_standard ASC");
                while($sp = mysqli_fetch_assoc($q_sp)): 
                    
                    // JURUS INGATAN: Cek apakah nama sparepart ini ada di data yang sudah tersimpan sebelumnya
                    $is_checked = '';
                    if (!empty($t['sparepart_dipakai']) && strpos($t['sparepart_dipakai'], $sp['name_standard']) !== false) {
                        $is_checked = 'checked';
                    }
                ?>
                <div class="form-check mb-2 item-sparepart pb-2 border-bottom">
                    <input class="form-check-input hitung-sparepart" type="checkbox" name="sparepart[]" 
                           value="<?= $sp['item_id'] ?>" data-harga="<?= $sp['retail_price'] ?>" 
                           id="sp_<?= $sp['item_id'] ?>_<?= $t['id_tiket'] ?>" <?= $is_checked ?>> <label class="form-check-label small w-100" style="cursor:pointer;" for="sp_<?= $sp['item_id'] ?>_<?= $t['id_tiket'] ?>">
                        <span class="fw-bold text-dark"><?= $sp['name_standard'] ?></span> 
                        
                        <span class="badge bg-secondary ms-1"><?= !empty($sp['tipe_produk']) ? $sp['tipe_produk'] : 'Umum' ?></span>
                        <?php if(!empty($sp['color'])): ?>
                            <span class="badge bg-info text-dark ms-1"><i class="bi bi-palette"></i> <?= $sp['color'] ?></span>
                        <?php endif; ?>
                        
                        <br><span class="text-muted">Rp <?= number_format($sp['retail_price'], 0, ',', '.') ?></span>
                    </label>
                </div>
                <?php endwhile; ?>
            </div>
          </div>

          <div class="row">
              <div class="col-md-7">
                  <label class="form-label small fw-bold">Foto Kerusakan <small>(Opsional)</small></label>
                    <input type="file" name="foto_kerusakan[]" class="form-control" accept="image/*" multiple>
                  <?php if(!empty($t['foto_kerusakan']) || !empty($t['foto_kerusakan'])): ?>
                      <small class="text-success"><i class="bi bi-image"></i> Foto tersimpan.</small>
                  <?php endif; ?>
              </div>
              <div class="col-md-5">
                  <label class="form-label small fw-bold">Total Biaya (Rp)</label>
                  <input type="number" name="total_biaya" class="form-control total-biaya-input bg-warning text-dark fw-bold fs-6" value="<?= $t['total_biaya'] ?>" readonly>
              </div>
          </div>

        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Pekerjaan</button>
        </div>
        
      </form>
    </div> </div> </div>
                        <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-5 text-muted fst-italic">Belum ada data tiket yang sesuai filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalNewTicket">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="/rumahojol/proses_tiket.php" method="POST" enctype="multipart/form-data" class="w-100">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold">Buat Tiket Layanan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Plat Nomor (Nopol)</label>
                        <select class="form-select select2-nopol" name="nopol" id="nopol_tiket" required>
                            <option value="">-- Pilih Kendaraan --</option>
                            <?php 
                            if($q_nopol) {
                                mysqli_data_seek($q_nopol, 0); 
                                while($m = mysqli_fetch_assoc($q_nopol)) {
                                    echo "<option value='".$m['nopol']."'>".$m['nopol']."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Driver*</label>
                            <input type="text" class="form-control" name="nama_driver" id="nama_driver" placeholder="Ketik nama driver..." required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">No. HP Driver*</label>
                            <input type="text" class="form-control" name="no_hp_driver" id="no_hp_driver" placeholder="Ketik no hp driver..." required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="small fw-bold">Tipe Unit</label>
                            <select name="tipe_unit" class="form-select" required>
                                <option value="Flash">Flash</option>
                                <option value="Nova">Nova</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold">Mekanik</label>
                            <select name="nama_mekanik" class="form-select" required>
                                <?php if($q_mekanik_dropdown) { mysqli_data_seek($q_mekanik_dropdown, 0); while($m = mysqli_fetch_assoc($q_mekanik_dropdown)): ?>
                                    <option value="<?= $m['nama'] ?>"><?= $m['nama'] ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                    </div>
                    
<div class="mb-3">
            <label class="form-label small fw-bold">Pilih Sparepart (Bisa pilih lebih dari satu)</label>
            <input type="text" class="form-control form-control-sm mb-2 cari-sparepart-baru" placeholder="🔍 Cari nama/tipe...">

            <div class="border rounded p-2 bg-light kotak-daftar-sparepart-baru" style="max-height: 150px; overflow-y: auto;">
                <?php 
                $q_parts_baru = mysqli_query($koneksi, "SELECT item_id, name_standard, retail_price, tipe_produk, color FROM master_sparepart ORDER BY name_standard ASC");
                while($pb = mysqli_fetch_assoc($q_parts_baru)): 
                ?>
                <div class="form-check mb-2 item-sparepart-baru pb-2 border-bottom">
                    <input class="form-check-input hitung-sparepart-baru" type="checkbox" name="sparepart[]" 
                           value="<?= $pb['item_id'] ?>" data-harga="<?= $pb['retail_price'] ?>" 
                           id="sp_baru_<?= $pb['item_id'] ?>">
                    
                    <label class="form-check-label small w-100" style="cursor:pointer;" for="sp_baru_<?= $pb['item_id'] ?>">
                        <span class="fw-bold text-dark"><?= $pb['name_standard'] ?></span> 
                        
                        <span class="badge bg-secondary ms-1"><?= !empty($pb['tipe_produk']) ? $pb['tipe_produk'] : 'Umum' ?></span>
                        <?php if(!empty($pb['color'])): ?>
                            <span class="badge bg-info text-dark ms-1"><i class="bi bi-palette"></i> <?= $pb['color'] ?></span>
                        <?php endif; ?>
                        
                        <br><span class="text-muted">Rp <?= number_format($pb['retail_price'], 0, ',', '.') ?></span>
                    </label>
                </div>
                <?php endwhile; ?>
            </div>
          </div>
                    <div class="text-end bg-light p-3 rounded shadow-sm">
                    <h5 class="fw-bold mb-0">Total Estimasi: <span id="text-total-baru" class="text-success">Rp 0</span></h5>
                    <input type="hidden" name="total_biaya" id="input-total-baru" value="0">
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* CSS Select2 aslimu (Tidak diubah) */
    .select2-container--open { z-index: 9999999 !important; }
    .select2-container .select2-selection--single { height: 38px !important; padding: 5px 12px !important; border: 1px solid #dee2e6 !important; border-radius: 0.375rem !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; }
    .select2-search__field { pointer-events: auto !important; }
</style>

<script>
// ==============================================================
// 1. KODINGAN JQUERY (SELECT2 & GRAFIK)
// ==============================================================
$(document).ready(function() {
    
    // Inisialisasi Grafik
    var canvasTiket = document.getElementById('tiketChart');
    if(canvasTiket) {
        var ctxTiket = canvasTiket.getContext('2d');
        new Chart(ctxTiket, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    data: <?= isset($data_json_tiket) ? $data_json_tiket : '[]' ?>,
                    backgroundColor: '#10b981',
                    borderRadius: 5,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { grid: { display: false } } }
            }
        });
    }

    // Modal Select2 Fix
    $(document).off('focusin.modal');
    $('#modalNewTicket').on('shown.bs.modal', function () {        
        if ($('#nopol_tiket').hasClass("select2-hidden-accessible")) { $('#nopol_tiket').select2('destroy'); }
        if ($('#select_parts').hasClass("select2-hidden-accessible")) { $('#select_parts').select2('destroy'); }

        $('#nopol_tiket').select2({ dropdownParent: $('#modalNewTicket .modal-body'), width: '100%', placeholder: '-- Ketik Plat Nomor untuk mencari --' });
        $('#select_parts').select2({ dropdownParent: $('#modalNewTicket .modal-body'), width: '100%', placeholder: "-- Ketik nama sparepart --" });
    });
}); // <--- INI PENUTUP JQUERY YANG TADI TERHAPUS!

// ==============================================================
// 2. KODINGAN VANILLA JS (KALKULATOR & LIVE SEARCH)
// ==============================================================
document.addEventListener('DOMContentLoaded', function() {
    
    // A. KALKULATOR TOTAL BIAYA
    const checkboxes = document.querySelectorAll('.hitung-sparepart');
    checkboxes.forEach(function(box) {
        box.addEventListener('change', function() {
            const modal = this.closest('.modal');
            const inputTotal = modal.querySelector('.total-biaya-input');
            let total = 0;
            const semuaTercentang = modal.querySelectorAll('.hitung-sparepart:checked');
            semuaTercentang.forEach(function(item) {
                total += parseInt(item.getAttribute('data-harga'));
            });
            inputTotal.value = total;
        });
    });

    // B. LIVE SEARCH SPAREPART (ANTI-ENTER & ANTI-ERROR)
    const searchInputs = document.querySelectorAll('.cari-sparepart');
    
    searchInputs.forEach(function(inputBox) {
        
        // JURUS ANTI-ENTER: Cegah form ter-submit saat tekan Enter di kotak pencarian
        inputBox.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Hentikan aksi bawaan HTML!
            }
        });

        // JURUS LIVE SEARCH PENCARIAN
        inputBox.addEventListener('input', function() {
            const keyword = this.value.toLowerCase(); 
            // Cara yang lebih aman untuk mencari bungkus daftarnya
            const container = this.closest('.mb-3').querySelector('.kotak-daftar-sparepart'); 
            
            if (container) {
                const items = container.querySelectorAll('.item-sparepart');
                items.forEach(function(item) {
                    const textBarang = item.textContent.toLowerCase();
                    if (textBarang.includes(keyword)) {
                        item.style.display = ''; // Munculkan
                    } else {
                        item.style.display = 'none'; // Sembunyikan
                    }
                });
            }
        });
    });

});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // 1. KALKULATOR TOTAL ESTIMASI (TIKET BARU)
    // ==========================================
    const checkboxesBaru = document.querySelectorAll('.hitung-sparepart-baru');
    const textTotalBaru = document.getElementById('text-total-baru');
    const inputTotalBaru = document.getElementById('input-total-baru');
    
    checkboxesBaru.forEach(function(box) {
        box.addEventListener('change', function() {
            let total = 0;
            // Hitung semua yang dicentang di modal tiket baru
            const tercentang = document.querySelectorAll('.hitung-sparepart-baru:checked');
            tercentang.forEach(function(item) {
                total += parseInt(item.getAttribute('data-harga')) || 0;
            });
            
            // Ubah tampilan teks jadi format Rupiah (contoh: Rp 150.000)
            textTotalBaru.innerHTML = 'Rp ' + total.toLocaleString('id-ID');
            // Masukkan angka aslinya ke input tersembunyi untuk PHP
            inputTotalBaru.value = total;
        });
    });

    // ==========================================
    // 2. LIVE SEARCH SPAREPART (TIKET BARU)
    // ==========================================
    const cariBaru = document.querySelectorAll('.cari-sparepart-baru');
    
    cariBaru.forEach(function(inputBox) {
        // Cegah Form Submit kalau kepencet Enter
        inputBox.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') e.preventDefault();
        });

        // Filter pencarian
        inputBox.addEventListener('input', function() {
            const keyword = this.value.toLowerCase(); 
            const container = this.closest('.mb-3').querySelector('.kotak-daftar-sparepart-baru'); 
            
            if (container) {
                const items = container.querySelectorAll('.item-sparepart-baru');
                items.forEach(function(item) {
                    const textBarang = item.textContent.toLowerCase();
                    if (textBarang.includes(keyword)) {
                        item.style.display = ''; 
                    } else {
                        item.style.display = 'none'; 
                    }
                });
            }
        });
    });

});
</script>