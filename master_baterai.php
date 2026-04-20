<?php
// 1. Tangkap data dari Form Filter
$search_b = $_GET['search_b'] ?? '';
$power_b  = $_GET['power_b'] ?? '';
$status_b = $_GET['status_b'] ?? '';

// 2. Racik Query Pencarian
$query = "SELECT * FROM master_baterai WHERE 1=1";

if ($search_b != '') {
    $query .= " AND Baterai_Code LIKE '%$search_b%'";
}
if ($power_b != '') {
    $query .= " AND Power = '$power_b'";
}
if ($status_b != '') {
    $query .= " AND Status = '$status_b'";
}

$query .= " ORDER BY Baterai_Code ASC";
$result = mysqli_query($koneksi, $query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Katalog Baterai</h3>
        <p class="text-muted small mt-1">Daftar unit fisik baterai dan kondisinya.</p>
    </div>
    <button class="btn btn-theme shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddBaterai">
        <i class="bi bi-camera me-1"></i> Registrasi Unit
    </button>
</div>

<form method="GET" action="index.php" class="filter-bar mb-4 p-3 bg-white rounded shadow-sm">
    <input type="hidden" name="asset" value="master_baterai">
    <div class="row g-2">
        <div class="col-md-5">
            <input type="text" name="search_b" class="form-control" placeholder="Cari Baterai Code..." value="<?= $search_b ?>">
        </div>
        <div class="col-md-3">
            <select name="power_b" class="form-select">
                <option value="">Semua Power</option>
                <option value="72V 20Ah" <?= ($power_b == '72V 20Ah') ? 'selected' : '' ?>>72V 20Ah</option>
                <option value="72V 25Ah" <?= ($power_b == '72V 25Ah') ? 'selected' : '' ?>>72V 25Ah</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status_b" class="form-select">
                <option value="">Semua Status</option>
                <option value="OK" <?= ($status_b == 'OK') ? 'selected' : '' ?>>READY</option>
                <option value="NG" <?= ($status_b == 'NG') ? 'selected' : '' ?>>FAULTY</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel"></i> Filter</button>
            <a href="?asset=master_baterai" class="btn btn-light border" title="Reset Filter"><i class="bi bi-arrow-clockwise"></i></a>
        </div>
    </div>
</form>

<div class="card card-custom p-0 overflow-hidden shadow-sm border-0">
    <table class="table table-hover m-0 align-middle">
        <thead class="text-center bg-light">
            <tr>
                <th>Foto Unit</th>
                <th>Baterai Code</th>
                <th>Power</th>
                <th>Status</th>
                <th>Terakhir Terlihat</th>
                <th>Keterangan Lokasi</th> </tr>
        </thead>
        <tbody class="text-center">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>
                        <div class="d-flex justify-content-center gap-1">
                            <?php if(!empty($row['foto'])): ?>
                                <img src="uploads/baterai/<?= $row['foto'] ?>" class="rounded shadow-sm" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #eee;" title="Foto 1">
                            <?php endif; ?>
                            
                            <?php if(!empty($row['foto2'])): ?>
                                <img src="uploads/baterai/<?= $row['foto2'] ?>" class="rounded shadow-sm" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #eee;" title="Foto 2">
                            <?php endif; ?>
                            
                            <?php if(empty($row['foto']) && empty($row['foto2'])): ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center mx-auto" style="width: 40px; height: 40px; color: #cbd5e1; border: 1px dashed #e2e8f0;">
                                    <i class="bi bi-camera"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="fw-bold text-dark fs-6"><?= $row['Baterai_Code'] ?></td>
                    <td><span class="badge badge-soft-theme text-dark px-2 py-1"><?= $row['Power'] ?></span></td>
                    <td>
                        <span class="badge <?= ($row['Status'] == 'OK') ? 'bg-success' : 'bg-danger' ?> px-3 py-1">
                            <?= ($row['Status'] == 'OK') ? 'READY' : 'FAULTY' ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($row['DateTime'] ?? 'now')) ?></td>
                    
                    <td><span class="badge bg-light text-dark border px-3 py-1"><?= !empty($row['Lokasi']) ? $row['Lokasi'] : 'Belum Diketahui' ?></span></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="py-5 text-muted fst-italic">Data baterai tidak ditemukan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalAddBaterai" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-theme border-0">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-battery-charging me-2"></i>Tambah Unit Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_master_baterai.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">Foto 1 (Unit Depan)</label>
                            <input type="file" name="foto_baterai" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">Foto 2 (Barcode/Label)</label>
                            <input type="file" name="foto_baterai_2" class="form-control form-control-sm" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="small fw-bold mb-1 d-flex justify-content-between align-items-center">
                            Baterai Code 
                            <button type="button" class="btn btn-sm btn-outline-success border-0 py-0 fw-bold" onclick="startScan()">
                                <i class="bi bi-qr-code-scan"></i> Scan QR
                            </button>
                        </label>
                        <div id="reader" style="width: 100%; display:none;" class="mb-2 rounded overflow-hidden border"></div>
                        <input type="text" name="baterai_code" id="baterai_code_input" class="form-control font-monospace" placeholder="Contoh: 1080967" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">Power Specs</label>
                            <select name="power" class="form-select" required>
                                <option value="72V 20Ah">72V 20Ah</option>
                                <option value="72V 25Ah">72V 25Ah</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">Status</label>
                            <select name="status" class="form-select">
                                <option value="OK">READY (OK)</option>
                                <option value="NG">FAULTY (NG)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Lokasi Awal</label>
                        <select name="lokasi" class="form-select" required>
                            <option value="Warehouse/BBI">Warehouse/BBI</option>
                            <option value="Tandur">Tandur</option>
                            <option value="Pengumben">Pengumben</option>
                            <option value="Serang">Serang</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="submit" name="simpan_baterai" class="btn btn-theme w-100 fw-bold">Simpan Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
function startScan() {
    const readerElement = document.getElementById('reader');
    readerElement.style.display = 'block';

    const html5QrCode = new Html5Qrcode("reader");
    const qrCodeSuccessCallback = (decodedText, decodedResult) => {
        // Masukkan hasil ke input text
        document.getElementById('baterai_code_input').value = decodedText;
        
        // Matikan kamera setelah berhasil
        html5QrCode.stop().then((ignore) => {
            readerElement.style.display = 'none';
        });
    };

    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
    html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback);
}
</script>