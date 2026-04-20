<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 1. Tangkap data pencarian dari form filter
$search_sp = $_GET['search_sp'] ?? '';

// 2. Racik query dengan logika filter pencarian
$query = "SELECT s.*, t.translated_name 
          FROM master_sparepart s 
          LEFT JOIN sys_translations t ON s.item_id = t.item_id AND t.language_code = 'id'
          WHERE 1=1";

if ($search_sp != '') {
    $query .= " AND (s.item_id LIKE '%$search_sp%' OR s.name_standard LIKE '%$search_sp%' OR s.tipe_produk LIKE '%$search_sp%')";
}
$query .= " ORDER BY s.item_id ASC";

$result = mysqli_query($koneksi, $query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Katalog Sparepart</h3>
        <p class="text-muted small mt-1">Daftar komponen dan stok di setiap cabang.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahSP">
            <i class="bi bi-plus-circle me-1"></i> Tambah Sparepart
        </button>
        <button class="btn btn-theme shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImportSP">
            <i class="bi bi-upload me-1"></i> Import
        </button>
    </div>
</div>

<form method="GET" action="index.php" class="filter-bar mb-4 p-3 bg-white rounded shadow-sm">
    <input type="hidden" name="asset" value="master_sparepart">
    <div class="row g-2">
        <div class="col-md-10">
            <input type="text" name="search_sp" class="form-control" placeholder="Cari ID Item, Nama Produk, atau Tipe..." value="<?= $search_sp ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i> Cari</button>
            <a href="?asset=master_sparepart" class="btn btn-light border" title="Reset Filter"><i class="bi bi-arrow-clockwise"></i></a>
        </div>
    </div>
</form>

<?php $modals_html = ''; // Wadah untuk menyimpan semua modal ?>

<div class="card card-custom p-0 overflow-hidden shadow-sm border-0">
    <table class="table table-hover m-0 align-middle">
        <thead class="bg-light text-center">
            <tr>
                <th>TIPE</th>
                <th>NAMA PRODUK</th>
                <th>WARNA</th>
                <th>STOK PER LOKASI</th>
                <th>HARGA RETAIL</th>
            </tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $display_name = !empty($row['translated_name']) ? $row['translated_name'] : $row['name_standard'];
                ?>
                <tr class="text-center">
                    <td><span class="badge badge-soft-theme text-dark px-3 py-2"><?= $row['tipe_produk'] ?: '-' ?></span></td>
                    
                    <td class="text-start">
                        <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                            <?= $display_name ?>
                            
                            <?php if(!empty($row['foto'])): ?>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#modalFoto<?= $row['item_id'] ?>" class="text-primary" title="Lihat Foto">
                                    <i class="bi bi-image"></i>
                                </a>
                            <?php endif; ?>
                            
                        </div>
                        <div class="small text-muted"><?= $row['satuan'] ?? 'PCS' ?></div>
                    </td>
                    <td><span class="form-label small fw-semibold"><?= $row['color'] ?: '-' ?></span></td>
                    
                    <td>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <?php
                            $id_item = $row['item_id'];
                            $stok_sql = mysqli_query($koneksi, "SELECT * FROM stok_sparepart WHERE item_id = '$id_item'");
                            while($st = mysqli_fetch_assoc($stok_sql)): ?>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-light text-dark border shadow-sm">
                                        <?= $st['lokasi'] ?>: <b class="text-primary"><?= $st['total_good'] ?></b>
                                    </span>
                                    <button class="btn btn-sm btn-outline-success py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalMutasi<?= $row['item_id'] ?>" title="Mutasi Stok Ini">
                                        <i class="bi bi-arrow-left-right"></i> Mutasi
                                    </button>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </td>
                    <td class="fw-bold text-success">Rp <?= number_format($row['retail_price'] ?? 0, 0, ',', '.') ?></td>
                </tr>

                <?php 
                // ==========================================
                // MENYIMPAN MODAL MUTASI
                // ==========================================
                $modals_html .= '
                <div class="modal fade text-start" id="modalMutasi'.$row['item_id'].'">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                      <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold">Mutasi: '.$display_name.'</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form action="/rumahojol/proses_mutasi_sparepart.php" method="POST">
                          <input type="hidden" name="item_id" value="'.$row['item_id'].'">
                          <div class="modal-body">
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
                          <div class="modal-footer border-top-0 pt-0">
                              <button type="submit" class="btn btn-theme w-100 fw-bold">Pindahkan Barang</button>
                          </div>
                      </form>
                    </div>
                  </div>
                </div>';

                // ==========================================
                // MENYIMPAN MODAL LIHAT FOTO
                // ==========================================
                if(!empty($row['foto'])) {
                    $modals_html .= '
                    <div class="modal fade" id="modalFoto'.$row['item_id'].'">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg bg-transparent">
                          <div class="modal-header border-0">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body text-center p-0">
                              <img src="uploads/'.$row['foto'].'" class="img-fluid rounded shadow" alt="Foto '.$display_name.'">
                              <div class="bg-dark text-white p-2 mt-2 rounded small fw-bold">'.$display_name.'</div>
                          </div>
                        </div>
                      </div>
                    </div>';
                }
                ?>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted py-5 fst-italic">Data sparepart tidak ditemukan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $modals_html ?>

<div class="modal fade" id="modalTambahSP">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white border-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Sparepart Baru</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/rumahojol/proses_tambah_sparepart.php" method="POST" enctype="multipart/form-data">
          <div class="modal-body p-4">
              <div class="row g-3">
                  <div class="col-md-12">
                      <label class="small fw-bold mb-1">NAMA PRODUK (INDONESIA)*</label>
                      <input type="text" name="nama" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                      <label class="small fw-bold mb-1">NAMA MANDARIN (OPSIONAL)</label>
                      <input type="text" name="nama_mandarin" class="form-control">
                  </div>
                  <div class="col-md-6">
                      <label class="small fw-bold mb-1">TIPE PRODUK*</label>
                      <select name="tipe" class="form-select" required>
                          <option value="Flash">Flash</option>
                          <option value="Nova">Nova</option>
                          <option value="Universal">Universal (Bisa Semua)</option>
                      </select>
                  </div>
                  <div class="col-md-4">
                      <label class="small fw-bold mb-1">WARNA</label>
                      <input type="text" name="warna" class="form-control" placeholder="Contoh: Silver / Hitam">
                  </div>
                  <div class="col-md-4">
                      <label class="small fw-bold mb-1">SATUAN*</label>
                      <input type="text" name="satuan" class="form-control" value="PCS" required>
                  </div>
                  <div class="col-md-4">
                      <label class="small fw-bold mb-1">HARGA RETAIL (RP)*</label>
                      <input type="number" name="harga" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                      <label class="small fw-bold mb-1">STOK AWAL (DI WAREHOUSE/BBI)*</label>
                      <input type="number" name="stok_awal" class="form-control" value="0" required min="0">
                  </div>
                  <div class="col-md-6">
                      <label class="small fw-bold mb-1">UPLOAD FOTO</label>
                      <input type="file" name="foto" class="form-control" accept="image/*">
                  </div>
              </div>
          </div>
          <div class="modal-footer bg-light border-0">
              <button type="submit" name="simpan_sparepart" class="btn btn-success fw-bold w-100">Simpan Sparepart Baru</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalImportSP">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 bg-theme">
        <h5 class="modal-title fw-bold text-white"><i class="bi bi-upload me-2"></i>Import Data CSV</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/rumahojol/proses_import_sparepart.php" method="POST" enctype="multipart/form-data">
          <div class="modal-body p-4">
              <div class="alert alert-info small border-0 bg-light">
                  <i class="bi bi-info-circle-fill me-1 text-primary"></i> <b>Wajib perhatikan urutan kolom di file CSV kamu:</b><br><br>
                  <ol class="mb-0 ps-3">
                      <li><b>ID Item</b> (Contoh: SP-001)</li>
                      <li><b>Nama Produk</b> (Contoh: Speedometer)</li>
                      <li><b>Tipe</b> (Contoh: Flash)</li>
                      <li><b>Warna</b> (Contoh: Hitam)</li>
                      <li><b>Satuan</b> (Contoh: PCS)</li>
                      <li><b>Harga Retail</b> (Contoh: 25000)</li>
                      <li><b>Stok Awal</b> (Contoh: 50)</li>
                  </ol>
              </div>
              <div class="mb-3">
                  <label class="form-label small fw-bold">PILIH FILE .CSV</label>
                  <input type="file" name="file_csv" class="form-control" accept=".csv" required>
              </div>
          </div>
          <div class="modal-footer border-top-0 pt-0">
              <button type="submit" name="import_sp" class="btn btn-theme w-100 fw-bold">Upload & Import Sekarang</button>
          </div>
      </form>
    </div>
  </div>
</div>