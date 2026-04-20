<?php
// 1. Tangkap data filter dari URL
$search_m = $_GET['search_m'] ?? '';
$lokasi_m = $_GET['lokasi_m'] ?? '';
$status_m = $_GET['status_m'] ?? '';

// 2. Ambil daftar motor untuk modal
$list_motor = mysqli_query($koneksi, "SELECT nopol, model FROM master_motor ORDER BY nopol ASC");

// 3. Merakit query mutasi dengan logika filter pencarian
$query_mutasi = "SELECT * FROM mutasi_motor WHERE 1=1";
if ($search_m != '') {
    $query_mutasi .= " AND nopol LIKE '%$search_m%'";
}
if ($lokasi_m != '') {
    $query_mutasi .= " AND lokasi_tujuan = '$lokasi_m'";
}
if ($status_m != '') {
    $query_mutasi .= " AND status_update = '$status_m'";
}
$query_mutasi .= " ORDER BY tanggal DESC, id DESC";

$tampil_mutasi = mysqli_query($koneksi, $query_mutasi);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Riwayat Mutasi Motor</h3>
        <p class="text-muted small mt-1">Log pergerakan lokasi dan perubahan status unit motor.</p>
    </div>
    <button class="btn btn-theme shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMutasiMotor">
        <i class="bi bi-plus-lg me-1"></i> Catat Mutasi
    </button>
</div>

<form method="GET" action="index.php" class="filter-bar mb-4 p-3 bg-white rounded shadow-sm">
    <input type="hidden" name="asset" value="mutasi_motor">
    <div class="row g-2">
        <div class="col-md-4">
            <input type="text" name="search_m" class="form-control" placeholder="Cari No. Polisi..." value="<?= $search_m ?>">
        </div>
        <div class="col-md-3">
            <select name="lokasi_m" class="form-select">
                <option value="">Semua Lokasi Tujuan</option>
                <option value="Warehouse/BBI" <?= ($lokasi_m == 'Warehouse/BBI')?'selected':'' ?>>Warehouse/BBI</option>
                <option value="Tandur" <?= ($lokasi_m == 'Tandur')?'selected':'' ?>>Tandur</option>
                <option value="Pengumben" <?= ($lokasi_m == 'Pengumben')?'selected':'' ?>>Pengumben</option>
                <option value="Serang" <?= ($lokasi_m == 'Serang')?'selected':'' ?>>Serang</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status_m" class="form-select">
                <option value="">Semua Status Baru</option>
                <option value="Ready" <?= ($status_m == 'Ready')?'selected':'' ?>>Ready</option>
                <option value="Disewa" <?= ($status_m == 'Disewa')?'selected':'' ?>>Disewa</option>
                <option value="Service/Rusak" <?= ($status_m == 'Service/Rusak')?'selected':'' ?>>Service/Rusak</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel"></i> Filter</button>
            <a href="?asset=mutasi_motor" class="btn btn-light border" title="Reset Filter"><i class="bi bi-arrow-clockwise"></i></a>
        </div>
    </div>
</form>

<div class="card card-custom p-0 overflow-hidden shadow-sm border-0">
    <table class="table table-hover m-0 align-middle">
        <thead class="text-center bg-light">
            <tr>
                <th>Tanggal</th>
                <th>No. Polisi</th>
                <th>Lokasi Tujuan</th>
                <th>Status Baru</th>
                <th>Keterangan / User</th>
            </tr>
        </thead>
        <tbody class="text-center">
            <?php if(mysqli_num_rows($tampil_mutasi) > 0): ?>
                <?php while($m = mysqli_fetch_assoc($tampil_mutasi)): 
                     $bg = ($m['status_update'] == 'Ready') ? 'bg-success' : (($m['status_update'] == 'Disewa') ? 'bg-primary' : 'bg-danger');
                ?>
                <tr>
                    <td><?= date('d M Y', strtotime($m['tanggal'])) ?></td>
                    <td><span class="fw-bold text-dark border px-2 py-1 rounded bg-light"><?= $m['nopol'] ?></span></td>
                    <td><span class="badge bg-light text-dark border px-2 py-1"><?= $m['lokasi_tujuan'] ?></span></td>
                    <td><span class="badge <?= $bg ?> px-2 py-1"><?= $m['status_update'] ?></span></td>
                    <td class="text-start"><?= $m['keterangan'] ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted py-5 fst-italic">Belum ada riwayat mutasi yang sesuai filter.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalMutasiMotor">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Update Pergerakan Motor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      
      <form action="/rumahojol/proses_mutasi_motor.php" method="POST" onsubmit="this.btnSimpan.disabled=true; return true;">
          <div class="modal-body">
              
              <div class="mb-3">
                  <label class="form-label small fw-semibold">PILIH MOTOR (NOPOL)</label>
                  <select name="nopol" class="form-select select2-nopol" required>
                      <option value="">-- Pilih Unit --</option>
                      <?php 
                      mysqli_data_seek($list_motor, 0); 
                      while($mot = mysqli_fetch_assoc($list_motor)): 
                      ?>
                          <option value="<?= $mot['nopol'] ?>"><?= $mot['nopol'] ?> - <?= $mot['model'] ?></option>
                      <?php endwhile; ?>
                  </select>
              </div>
              
              <div class="mb-3">
                  <label class="form-label small fw-semibold">TANGGAL PINDAH</label>
                  <input type="date" name="tanggal" class="form-control" required>
              </div>
              
              <div class="row">
                  <div class="col-6 mb-3">
                      <label class="form-label small fw-semibold">LOKASI TUJUAN</label>
                      <select name="lokasi" class="form-select">
                          <option value="Warehouse/BBI">Warehouse/BBI</option>
                          <option value="Tandur">Tandur</option>
                          <option value="Pengumben">Pengumben</option>
                          <option value="Serang">Serang</option>
                      </select>
                  </div>
                  <div class="col-6 mb-3">
                      <label class="form-label small fw-semibold">STATUS BARU</label>
                      <select name="status" class="form-select">
                          <option value="Ready">Ready</option>
                          <option value="Disewa">Disewa</option>
                          <option value="Service/Rusak">Service/Rusak</option>
                      </select>
                  </div>
              </div>
              
              <div class="mb-3">
                  <label class="form-label small fw-semibold">KETERANGAN / NAMA USER</label>
                  <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Disewa Pak Budi" required>
              </div>
          </div>
          
          <div class="modal-footer border-top-0 pt-0">
              <button type="submit" name="btnSimpan" class="btn btn-theme w-100 fw-bold">Update & Simpan Riwayat</button>
          </div>
      </form>

    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Styling agar kotak rapi dan tidak tenggelam di belakang Pop-up */
    .select2-container--open { z-index: 9999999 !important; }
    .select2-container .select2-selection--single {
        height: 38px !important;
        padding: 5px 12px !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 0.375rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .select2-search__field {
        pointer-events: auto !important;
    }
</style>

<script>
$(document).ready(function() {
    
    // Matikan paksa event focus bawaan Bootstrap agar keyboard tidak terkunci
    $(document).off('focusin.modal');

    // Aktifkan Select2 tepat saat modal selesai terbuka
    $('#modalMutasiMotor').on('shown.bs.modal', function () {        
        
        // Bersihkan Select2 yang nyangkut (jika ada)
        if ($('.select2-nopol').hasClass("select2-hidden-accessible")) {
            $('.select2-nopol').select2('destroy');
        }

        // Terapkan Select2 dan ikat ke dalam badan modal
        $('.select2-nopol').select2({ 
            dropdownParent: $('#modalMutasiMotor .modal-body'),
            width: '100%',
            placeholder: '-- Ketik Plat Nomor --'
        });
    });

});
</script>