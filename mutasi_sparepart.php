<?php
// 1. Tangkap data dari Form Filter
$search_mutasi = $_GET['search_m'] ?? '';
$dari_lokasi   = $_GET['dari_m'] ?? '';
$ke_lokasi     = $_GET['ke_m'] ?? '';

// 2. Racik Query Pencarian
$query = "SELECT m.*, s.name_standard, s.tipe_produk, s.color 
          FROM mutasi_sparepart m
          JOIN master_sparepart s ON m.item_id = s.item_id
          WHERE 1=1";

if ($search_mutasi != '') {
    $query .= " AND s.name_standard LIKE '%$search_mutasi%'";
}
if ($dari_lokasi != '') {
    $query .= " AND m.dari_lokasi = '$dari_lokasi'";
}
if ($ke_lokasi != '') {
    $query .= " AND m.ke_lokasi = '$ke_lokasi'";
}

$query .= " ORDER BY m.tanggal DESC";
$result = mysqli_query($koneksi, $query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Riwayat Mutasi Sparepart</h3>
        <p class="text-muted small mt-1">Laporan perpindahan stok antar gudang/cabang.</p>
    </div>
    <button class="btn btn-theme shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMutasiBaru">
        <i class="bi bi-plus-lg me-1"></i> Add Mutasi
    </button>
</div>

<form method="GET" action="index.php" class="filter-bar mb-4 p-3 bg-white rounded shadow-sm">
    <input type="hidden" name="asset" value="mutasi_sparepart">
    <div class="row g-2">
        <div class="col-md-4">
            <input type="text" name="search_m" class="form-control" placeholder="Cari Nama Produk..." value="<?= $search_mutasi ?>">
        </div>
        <div class="col-md-3">
            <select name="dari_m" class="form-select">
                <option value="">Semua Lokasi Asal</option>
                <option value="Warehouse/BBI" <?= ($dari_lokasi == 'Warehouse/BBI') ? 'selected' : '' ?>>Warehouse/BBI</option>
                <option value="Tandur" <?= ($dari_lokasi == 'Tandur') ? 'selected' : '' ?>>Tandur</option>
                <option value="Pengumben" <?= ($dari_lokasi == 'Pengumben') ? 'selected' : '' ?>>Pengumben</option>
                <option value="Serang" <?= ($dari_lokasi == 'Serang') ? 'selected' : '' ?>>Serang</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="ke_m" class="form-select">
                <option value="">Semua Lokasi Tujuan</option>
                <option value="Warehouse/BBI" <?= ($ke_lokasi == 'Warehouse/BBI') ? 'selected' : '' ?>>Warehouse/BBI</option>
                <option value="Tandur" <?= ($ke_lokasi == 'Tandur') ? 'selected' : '' ?>>Tandur</option>
                <option value="Pengumben" <?= ($ke_lokasi == 'Pengumben') ? 'selected' : '' ?>>Pengumben</option>
                <option value="Serang" <?= ($ke_lokasi == 'Serang') ? 'selected' : '' ?>>Serang</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel"></i> Filter</button>
            <a href="?asset=mutasi_sparepart" class="btn btn-light border" title="Reset Filter"><i class="bi bi-arrow-clockwise"></i></a>
        </div>
    </div>
</form>

<div class="card card-custom p-0 overflow-hidden shadow-sm border-0">
    <table class="table table-hover m-0 align-middle">
        <thead class="bg-light text-center">
            <tr>
                <th>Tanggal</th>
                <th>Nama Produk</th>
                <th>Tipe</th>  
                <th>Warna</th> 
                <th>Dari</th>
                <th>Ke</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody class="text-center">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td class="small"><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                    <td class="text-start fw-bold">
                        <?= $row['name_standard'] ?>
                    </td>
                    
                    <td><span class="badge badge-soft-theme text-dark"><?= $row['tipe_produk'] ?: '-' ?></span></td>
                    
                    <td><span class="badge bg-secondary px-2 py-1"><?= $row['color'] ?: '-' ?></span></td>
                    
                    <td><span class="badge bg-light text-dark border px-2 py-1"><?= $row['dari_lokasi'] ?></span></td>
                    <td><span class="badge bg-success shadow-sm px-2 py-1"><?= $row['ke_lokasi'] ?></span></td>
                    
                    <td class="fw-bold text-primary"><?= $row['jumlah'] ?></td>
                    <td class="small text-muted text-start"><?= $row['keterangan'] ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" class="py-5 text-muted fst-italic">Belum ada riwayat mutasi yang sesuai filter.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalMutasiBaru">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Catat Mutasi Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="proses_mutasi_sparepart.php" method="POST">
          <div class="modal-body">
              <div class="mb-3">
                  <label class="small fw-bold">PILIH SPAREPART</label>
                  <select name="item_id" class="form-select select2-sparepart" required>
                      <option value="">-- Pilih Barang yang dipindah --</option>
                      <?php
                      $sp_query = mysqli_query($koneksi, "SELECT item_id, name_standard, tipe_produk, color FROM master_sparepart ORDER BY name_standard ASC");
                      while($sp = mysqli_fetch_assoc($sp_query)):
                      ?>
                          <option value="<?= $sp['item_id'] ?>"><?= $sp['item_id'] ?> - <?= $sp['name_standard'] ?> [<?= $sp['tipe_produk'] ?>] (<?= $sp['color'] ?>)</option>
                      <?php endwhile; ?>
                  </select>
              </div>
              <div class="row">
                  <div class="col-6 mb-3">
                      <label class="small fw-bold">DARI LOKASI</label>
                      <select name="dari_lokasi" class="form-select" required>
                          <option value="Warehouse/BBI">Warehouse/BBI</option>
                          <option value="Tandur">Tandur</option>
                          <option value="Pengumben">Pengumben</option>
                          <option value="Serang">Serang</option>
                      </select>
                  </div>
                  <div class="col-6 mb-3">
                      <label class="small fw-bold">KE LOKASI</label>
                      <select name="ke_lokasi" class="form-select" required>
                          <option value="Tandur">Tandur</option>
                          <option value="Warehouse/BBI">Warehouse/BBI</option>
                          <option value="Pengumben">Pengumben</option>
                          <option value="Serang">Serang</option>
                      </select>
                  </div>
              </div>
              <div class="mb-3">
                  <label class="small fw-bold">JUMLAH BARANG</label>
                  <input type="number" name="jumlah" class="form-control" required min="1">
              </div>
              <div class="mb-3">
                  <label class="small fw-bold">KETERANGAN</label>
                  <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Kirim stok mingguan"></textarea>
              </div>
          </div>
          <div class="modal-footer">
              <button type="submit" name="proses_mutasi" class="btn btn-theme w-100 fw-bold">Simpan Mutasi</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
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
    
    // Matikan paksa event focus bawaan Bootstrap
    $(document).off('focusin.modal');

    // Trik Otomatis: Jalankan Select2 di modal Mutasi Sparepart
    $('#modalMutasiBaru').on('shown.bs.modal', function () {        
        
        // Bersihkan jika ada yang nyangkut
        if ($('.select2-sparepart').hasClass("select2-hidden-accessible")) {
            $('.select2-sparepart').select2('destroy');
        }

        // Terapkan Select2 dan ikat ke dalam badan modal
        $('.select2-sparepart').select2({ 
            dropdownParent: $('#modalMutasiBaru .modal-body'),
            width: '100%',
            placeholder: '-- Pilih Barang yang dipindah --'
        });
    });

});
</script>