<?php
// Parameter Filter
$search_f   = $_GET['search'] ?? '';
$kategori_f = $_GET['kategori'] ?? ''; 
$lokasi_f   = $_GET['lokasi'] ?? '';
$tipe_f     = $_GET['tipe'] ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Kartu Stok Baterai</h3>
        <p class="text-muted small mt-1">Pencatatan akumulasi keluar-masuk (In/Out) baterai.</p>
    </div>
    <button class="btn btn-theme shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBaterai">
        <i class="bi bi-plus-lg me-1"></i> Add Record
    </button>
</div>

<form method="GET" action="index.php" class="filter-bar mb-4 card-custom">
    <input type="hidden" name="asset" value="mutasi_baterai">
    <div class="row g-2 p-3">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Cari keterangan..." value="<?= $search_f ?>">
        </div>
        <div class="col-md-2">
            <select name="kategori" class="form-select text-muted">
                <option value="">Kategori</option>
                <option value="BBI" <?= ($kategori_f == 'BBI')?'selected':'' ?>>BBI</option>
                <option value="Motor" <?= ($kategori_f == 'Motor')?'selected':'' ?>>Motor</option>
                <option value="Kabinet" <?= ($kategori_f == 'Kabinet')?'selected':'' ?>>Kabinet</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="lokasi" class="form-select text-muted">
                <option value="">Semua Lokasi</option>
                <option value="Tandur" <?= ($lokasi_f == 'Tandur')?'selected':'' ?>>Tandur</option>
                <option value="Pengumben" <?= ($lokasi_f == 'Pengumben')?'selected':'' ?>>Pengumben</option>
                <option value="BBI" <?= ($lokasi_f == 'BBI')?'selected':'' ?>>BBI</option>
                <option value="Serang" <?= ($lokasi_f == 'Serang')?'selected':'' ?>>Serang</option>
            </select>
        </div>
        <div class="col-md-2">
                <select name="tipe" class="form-select text-muted">
                <option value="">Semua Tipe</option>
                <option value="72V 25Ah" <?= ($tipe_f == '72V 25Ah')?'selected':'' ?>>72V 25Ah</option>
                <option value="72V 20Ah" <?= ($tipe_f == '72V 20Ah')?'selected':'' ?>>72V 20Ah</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-theme w-100">Filter</button>
            <a href="?asset=mutasi_baterai" class="btn btn-light border"><i class="bi bi-arrow-clockwise"></i></a>
        </div>
    </div>
</form>

<div class="card card-custom p-0 overflow-hidden shadow-sm">
    <table class="table table-hover m-0 align-middle">
        <thead class="text-center">
            <tr>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Tipe</th>
                <th class="text-success">In</th>
                <th class="text-danger">Out</th>
                <th class="bg-light">Saldo</th>
            </tr>
        </thead>
        <tbody class="text-center">
            <?php
            $query = "SELECT * FROM stok_baterai WHERE 1=1";
            if ($search_f != '') $query .= " AND keterangan LIKE '%$search_f%'";
            if ($lokasi_f != '') $query .= " AND lokasi = '$lokasi_f'";
            if ($kategori_f != '') $query .= " AND kategori = '$kategori_f'";
            if ($tipe_f != '') $query .= " AND tipe = '$tipe_f'";
            $query .= " ORDER BY tanggal ASC";
            
            $ambil = mysqli_query($koneksi, $query);
            $saldo = 0;
            
            if($ambil && mysqli_num_rows($ambil) > 0) {
                while($row = mysqli_fetch_array($ambil)): 
                    $saldo += $row['in'] - $row['out'];
            ?>
            <tr>
                <td class="small"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                <td class="text-start fw-medium"><?= $row['keterangan'] ?></td>
                <td><span class="badge bg-light text-dark border"><?= $row['kategori'] ?></span></td>
                <td><span class="badge badge-soft-theme"><?= $row['lokasi'] ?></span></td>
                <td><small class="text-muted"><?= $row['tipe'] ?></small></td>
                <td class="text-success fw-bold"><?= ($row['in'] > 0) ? '+'.$row['in'] : '-' ?></td>
                <td class="text-danger fw-bold"><?= ($row['out'] > 0) ? '-'.$row['out'] : '-' ?></td>
                <td class="fw-bold text-theme bg-light"><?= $saldo ?></td>
            </tr>
            <?php endwhile; } else { ?>
                <tr><td colspan='8' class='text-muted py-5'>Belum ada transaksi dicatat.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalBaterai" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Catat Kartu Stok Baterai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="proses_mutasi_baterai.php" method="POST">
                <div class="modal-body">
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">Tanggal Transaksi</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">Tipe Baterai</label>
                            <select name="tipe" class="form-select" required>
                                <option value="72V 25Ah" selected>72V 25Ah</option>
                                <option value="72V 20Ah">72V 20Ah</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Keterangan / Aktivitas</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Stok masuk dari pusat..." required>
                    </div>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">Kategori</label>
                            <select name="kategori" class="form-select" required>
                                <option value="BBI">BBI</option>
                                <option value="Motor" selected>Motor</option>
                                <option value="Kabinet">Kabinet</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">Lokasi</label>
                            <select name="lokasi" class="form-select" required>
                                <option value="BBI">Warehouse/BBI</option>
                                <option value="Tandur">Tandur</option>
                                <option value="Pengumben">Pengumben</option>
                                <option value="Serang">Serang</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row border-top pt-3 mt-1">
                        <div class="col-6 mb-2">
                            <label class="small fw-bold text-success mb-1">Jumlah Masuk (IN)</label>
                            <input type="number" name="in" class="form-control border-success" value="0" min="0" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="small fw-bold text-danger mb-1">Jumlah Keluar (OUT)</label>
                            <input type="number" name="out" class="form-control border-danger" value="0" min="0" required>
                        </div>
                    </div>

                </div>
                
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-theme w-100">Simpan Transaksi</button>
                </div>
            </form>
            </div>
    </div>
</div>