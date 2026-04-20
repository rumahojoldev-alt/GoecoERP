<?php
// Menangkap data filter
$search_m = $_GET['search_m'] ?? '';
$lokasi_m = $_GET['lokasi_m'] ?? '';
$status_m = $_GET['status_m'] ?? '';

// Merakit logika pencarian database
$query = "SELECT * FROM master_motor WHERE 1=1";
if ($search_m != '') {
    $query .= " AND (nopol LIKE '%$search_m%' OR vin LIKE '%$search_m%' OR dinamo_motor LIKE '%$search_m%')";
}
if ($lokasi_m != '') {
    $query .= " AND lokasi_terkini = '$lokasi_m'";
}
if ($status_m != '') {
    $query .= " AND status_terkini = '$status_m'";
}
$query .= " ORDER BY id DESC";

$result = mysqli_query($koneksi, $query);

// SIAPKAN WADAH KOSONG UNTUK MENAMPUNG KODE MODAL
$modals_html = ''; 
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Katalog Data Motor</h3>
        <p class="text-muted small mt-1">Daftar inventaris induk (Master Data) untuk aset unit motor.</p>
    </div>
    <button class="btn btn-theme" data-bs-toggle="modal" data-bs-target="#modalImportCSV">
        <i class="bi bi-upload me-1"></i> Import CSV
    </button>
</div>

<form method="GET" action="index.php" class="filter-bar mb-4">
    <input type="hidden" name="asset" value="master_motor">
    <div class="row g-2">
        <div class="col-md-4">
            <input type="text" name="search_m" class="form-control" placeholder="Cari Nopol, VIN, atau Dinamo..." value="<?= $search_m ?>">
        </div>
        <div class="col-md-3">
            <select name="lokasi_m" class="form-select">
                <option value="">Semua Lokasi</option>
                <option value="Warehouse/BBI" <?= ($lokasi_m == 'Warehouse/BBI')?'selected':'' ?>>Warehouse/BBI</option>
                <option value="Tandur" <?= ($lokasi_m == 'Tandur')?'selected':'' ?>>Tandur</option>
                <option value="Pengumben" <?= ($lokasi_m == 'Pengumben')?'selected':'' ?>>Pengumben</option>
                <option value="Serang" <?= ($lokasi_m == 'Serang')?'selected':'' ?>>Serang</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status_m" class="form-select">
                <option value="">Semua Status</option>
                <option value="Ready" <?= ($status_m == 'Ready')?'selected':'' ?>>Ready</option>
                <option value="Disewa" <?= ($status_m == 'Disewa')?'selected':'' ?>>Disewa</option>
                <option value="Service/Rusak" <?= ($status_m == 'Service/Rusak')?'selected':'' ?>>Service/Rusak</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-filter w-100">Filter</button>
            <a href="?asset=master_motor" class="btn btn-light border"><i class="bi bi-arrow-clockwise"></i></a>
        </div>
    </div>
</form>

<div class="card card-custom p-0 overflow-hidden">
    <table class="table table-hover m-0 align-middle">
        <thead class="text-center">
            <tr>
                <th>ID</th>
                <th>VIN</th>
                <th>Dinamo Motor</th>
                <th>Merk/Tipe</th>
                <th>Thn</th>
                <th>Warna</th>
                <th>No. Polisi</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody class="text-center">
            <?php if($result && mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $badge_status = 'bg-secondary';
                    if($row['status_terkini'] == 'Ready') $badge_status = 'bg-success';
                    if($row['status_terkini'] == 'Disewa') $badge_status = 'bg-primary';
                    if($row['status_terkini'] == 'Service/Rusak') $badge_status = 'bg-danger';
                ?>
                <tr>
                    <td class="text-muted"><?= $row['id'] ?></td>
                    <td class="text-start small text-muted font-monospace"><?= $row['vin'] ?></td>
                    <td class="small text-muted font-monospace"><?= $row['dinamo_motor'] ?></td>
                    <td><?= $row['make'] ?> <?= $row['model'] ?></td>
                    <td><?= $row['tahun'] ?></td>
                    <td><?= $row['warna'] ?></td>
                    <td><span class="fw-bold text-dark border px-2 py-1 rounded bg-light"><?= $row['nopol'] ?></span></td>
                    <td><span class="badge badge-soft-secondary px-2 py-1"><?= $row['lokasi_terkini'] ?></span></td>
                    <td><span class="badge <?= $badge_status ?> px-2 py-1"><?= $row['status_terkini'] ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-warning text-dark fw-bold border-0" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </td>
                </tr>

                <?php 
                // SIMPAN KODE MODAL KE DALAM WADAH VARIABEL (JANGAN DICETAK DI DALAM TABEL)
                $modals_html .= '
                <div class="modal fade text-start" id="modalEdit'.$row['id'].'" tabindex="-1">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                      <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark">Edit Data Motor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form action="/rumahojol/proses_edit_motor.php" method="POST">
                          <div class="modal-body">
                              <input type="hidden" name="id" value="'.$row['id'].'">
                              <div class="mb-3"><label class="form-label small fw-semibold">NOMOR POLISI</label><input type="text" name="nopol" class="form-control" value="'.$row['nopol'].'" required></div>
                              <div class="mb-3"><label class="form-label small fw-semibold">NOMOR RANGKA (VIN)</label><input type="text" name="vin" class="form-control" value="'.$row['vin'].'" required></div>
                              <div class="mb-3"><label class="form-label small fw-semibold">NOMOR DINAMO</label><input type="text" name="dinamo" class="form-control" value="'.$row['dinamo_motor'].'" required></div>
                              <div class="row">
                                  <div class="col-6 mb-3"><label class="form-label small fw-semibold">MERK</label><input type="text" name="make" class="form-control" value="'.$row['make'].'" required></div>
                                  <div class="col-6 mb-3"><label class="form-label small fw-semibold">MODEL</label><input type="text" name="model" class="form-control" value="'.$row['model'].'" required></div>
                              </div>
                              <div class="row">
                                  <div class="col-6 mb-3"><label class="form-label small fw-semibold">TAHUN</label><input type="number" name="tahun" class="form-control" value="'.$row['tahun'].'" required></div>
                                  <div class="col-6 mb-3"><label class="form-label small fw-semibold">WARNA</label><input type="text" name="warna" class="form-control" value="'.$row['warna'].'" required></div>
                              </div>
                          </div>
                          <div class="modal-footer border-top-0 pt-0"><button type="submit" name="edit_motor" class="btn btn-warning fw-bold w-100">Update Data</button></div>
                      </form>
                    </div>
                  </div>
                </div>';
                ?>

                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan='10' class='text-muted py-5'>Belum ada data motor. Silakan Import CSV.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $modals_html ?>

<div class="modal fade" id="modalImportCSV" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Import Data CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/rumahojol/proses_import.php" method="POST" enctype="multipart/form-data">
          <div class="modal-body">
              <div class="alert alert-info small border-0 bg-light">
                  <i class="bi bi-info-circle-fill me-1 text-primary"></i> Pastikan urutan kolom di file CSV kamu: <br><b>VIN | Dinamo | Make | Model | Tahun | Warna | No. Polisi</b>
              </div>
              <div class="mb-3">
                  <label class="form-label small fw-semibold">PILIH FILE .CSV</label>
                  <input type="file" name="file_csv" class="form-control" accept=".csv" required>
              </div>
          </div>
          <div class="modal-footer border-top-0 pt-0"><button type="submit" name="import" class="btn btn-theme w-100 fw-bold">Upload & Proses Data</button></div>
      </form>
    </div>
  </div>
</div>