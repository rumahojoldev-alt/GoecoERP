<?php
// 1. PAKSA TIMEZONE DI PALING ATAS
date_default_timezone_set('Asia/Jakarta');

$tgl_hari_ini = date('Y-m-d'); 
$nama_user_aktif = $_SESSION['nama_user'];
$role_user_aktif = $_SESSION['role_user'];

// 2. CEK STATUS ABSEN HARI INI
$q_absen = mysqli_query($koneksi, "SELECT * FROM data_absensi WHERE nama_user = '$nama_user_aktif' AND DATE(waktu_masuk) = '$tgl_hari_ini' AND is_koreksi = 0 LIMIT 1");
$data_absen_hari_ini = mysqli_fetch_assoc($q_absen);

$sudah_absen_masuk = ($data_absen_hari_ini) ? true : false;
$sudah_absen_pulang = ($data_absen_hari_ini && $data_absen_hari_ini['waktu_pulang'] != NULL) ? true : false;

// 3. TENTUKAN JAM PULANG STANDAR (LOGIKA BARU)
$hari_ini_nama = date('l');
$jam_pulang_standar = '18:00:00'; // Default Sales (09:00 - 18:00)

if ($role_user_aktif == 'Mekanik') {
    $jam_pulang_standar = ($hari_ini_nama == 'Saturday') ? '15:00:00' : '17:00:00';
} elseif ($role_user_aktif == 'CS' && $sudah_absen_masuk) {
    // Jika CS, ambil jam pulang berdasarkan teks shift di status_lokasi
    if (strpos($data_absen_hari_ini['status_lokasi'], 'Pagi') !== false) $jam_pulang_standar = '15:00:00';
    elseif (strpos($data_absen_hari_ini['status_lokasi'], 'Sore') !== false) $jam_pulang_standar = '23:00:00';
    elseif (strpos($data_absen_hari_ini['status_lokasi'], 'Subuh') !== false) $jam_pulang_standar = '07:00:00';
}

// 4. HITUNG SISA KOREKSI
$bln_ini = date('m');
$q_koreksi = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM data_absensi WHERE nama_user = '$nama_user_aktif' AND is_koreksi = 1 AND MONTH(waktu_masuk) = '$bln_ini'");
$total_koreksi = mysqli_fetch_assoc($q_koreksi)['total'] ?? 0;
$sisa_koreksi = 3 - $total_koreksi;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">Absensi Karyawan</h3>
        <p class="text-muted small">Catat kehadiran harian Anda dengan akurat.</p>
    </div>
    <button class="btn btn-outline-warning shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalKoreksi">
        <i class="bi bi-clock-history me-1"></i> Koreksi Absen <span class="badge bg-warning text-dark ms-1"><?= $sisa_koreksi ?></span>
    </button>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-5">
        <div class="card card-custom border-0 shadow-sm overflow-hidden h-100">
            <div class="bg-dark text-white p-4 text-center">
                <h5 class="fw-bold mb-0"><?= $nama_user_aktif ?></h5>
                <span class="badge bg-success mt-2 px-3"><?= $role_user_aktif ?></span>
            </div>
            <div class="p-4 text-center">
                <div class="text-muted small mb-1"><?= date('l, d F Y') ?></div>
                <div class="display-3 fw-bold text-dark font-monospace" id="jamRealtime">00:00:00</div>
                <hr class="my-4 border-secondary opacity-25">
                <div class="text-start small">
                    <p class="fw-bold mb-2">Aturan Jam Kerja Anda:</p>
                    <ul class="text-muted ps-3">
                        <?php if($role_user_aktif == 'Mekanik'): ?>
                            <li>Senin - Jumat: 09:00 - 17:00</li>
                            <li>Sabtu: 09:00 - 15:00</li>
                            <li class="text-danger small">Minggu Libur</li>
                        <?php elseif($role_user_aktif == 'CS'): ?>
                            <li>Shift Pagi: 07:00 - 15:00</li>
                            <li>Shift Sore: 15:00 - 23:00</li>
                            <li>Shift Subuh: 23:00 - 07:00</li>
                        <?php else: ?>
                            <li>Senin - Sabtu: 09:00 - 18:00</li>
                            <li class="text-danger small">Minggu Libur</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card card-custom p-4 border-0 shadow-sm h-100">
            <?php if(!$sudah_absen_masuk): ?>
                <h5 class="fw-bold text-primary mb-4"><i class="bi bi-box-arrow-in-right me-2"></i>Absen Masuk</h5>
                <form action="/rumahojol/proses_absensi.php?aksi=masuk" method="POST" enctype="multipart/form-data">
                    
                    <?php if($role_user_aktif == 'CS'): ?>
                    <div class="mb-3">
                        <label class="small fw-bold text-primary">Pilih Shift Hari Ini*</label>
                        <select name="shift_cs" class="form-select border-primary" required>
                            <option value="">-- Pilih Shift --</option>
                            <option value="Pagi">Pagi (07:00 - 15:00)</option>
                            <option value="Sore">Sore (15:00 - 23:00)</option>
                            <option value="Subuh">Subuh (23:00 - 07:00)</option>
                        </select>                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="small fw-bold">Status Kehadiran*</label>
                        <select name="kehadiran" id="kehadiranMasuk" class="form-select" required>
                            <option value="Hadir">Hadir</option>
                            <option value="Izin">Izin</option>
                            <option value="Sakit">Sakit</option>
                        </select>
                    </div>

                    <div id="areaHadirMasuk">
                        <div class="mb-3">
                            <label class="small fw-bold d-block mb-2">Foto Selfie Wajib*</label>
                            <input type="file" name="foto_selfie" class="form-control" accept="image/*" capture="user" id="inputSelfie" required>
                        </div>
                        <div class="p-3 bg-light rounded border border-warning mb-3">
                            <div id="statusLokasiUI" class="small text-muted">Mendeteksi lokasi...</div>
                            <input type="hidden" name="latlong" id="latlongMasuk">
                            <input type="hidden" name="status_lokasi" id="teksLokasiMasuk">
                            <div id="areaKeteranganLuar" class="mt-2 d-none">
                                <label class="small fw-bold text-danger italic">Di luar area! Wajib isi alasan:</label>
                                <textarea name="keterangan_luar" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" id="btnSubmitMasuk" disabled>Rekam Absen Masuk</button>
                </form>

            <?php elseif($sudah_absen_masuk && !$sudah_absen_pulang): ?>
                <div class="text-center mb-4">
                    <div class="d-inline-block p-3 rounded-circle bg-success bg-opacity-10 mb-3">
                        <i class="bi bi-check2-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Sudah Absen Masuk</h5>
                    <p class="text-muted small">Pukul: <?= date('H:i:s', strtotime($data_absen_hari_ini['waktu_masuk'])) ?></p>
                </div>
                <hr class="border-secondary opacity-25 mb-4">
                <form action="/rumahojol/proses_absensi.php?aksi=pulang" method="POST">
                    <input type="hidden" name="id_absensi" value="<?= $data_absen_hari_ini['id_absensi'] ?>">
                    <div class="p-3 bg-light rounded border mb-4 text-center">
                        <div id="statusLokasiPulangUI" class="small text-muted">Mengecek lokasi pulang...</div>
                        <input type="hidden" name="latlong_pulang" id="latlongPulang">
                        <input type="hidden" name="status_lokasi_pulang" id="teksLokasiPulang">
                    </div>
                    <button type="submit" class="btn btn-danger w-100 py-2 fw-bold" id="btnSubmitPulang" disabled>Rekam Absen Pulang</button>
                    <div id="pesanPulang" class="text-center mt-2 small text-danger fw-bold">Belum waktunya pulang (Target: <?= $jam_pulang_standar ?>).</div>
                </form>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-emoji-smile text-success" style="font-size: 4rem;"></i>
                    <h4 class="fw-bold text-dark mt-3">Absensi Selesai!</h4>
                    <p class="text-muted">Terima kasih atas kerja kerasnya hari ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card card-custom p-0 overflow-hidden border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover m-0 align-middle small">
            <thead class="text-center bg-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Foto</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Lembur</th>
                    <th>Lokasi / Shift</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php 
                $q_riwayat = mysqli_query($koneksi, "SELECT * FROM data_absensi WHERE nama_user = '$nama_user_aktif' ORDER BY waktu_masuk DESC LIMIT 15");
                while($r = mysqli_fetch_assoc($q_riwayat)): 
                    $jam_m = date('H:i', strtotime($r['waktu_masuk']));
                    $jam_p = ($r['waktu_pulang']) ? date('H:i', strtotime($r['waktu_pulang'])) : '-';
                ?>
                <tr>
                    <td><?= date('d M y', strtotime($r['waktu_masuk'])) ?> <?= ($r['is_koreksi']) ? '<br><span class="badge bg-warning text-dark" style="font-size:8px">Koreksi</span>' : '' ?></td>
                    <td><span class="badge bg-success"><?= $r['kehadiran'] ?></span></td>
                    <td>
                        <?php if($r['foto_selfie']): ?>
                            <img src="uploads/<?= $r['foto_selfie'] ?>" class="rounded border" style="width: 40px; height: 40px; object-fit: cover; cursor:pointer;" onclick="window.open(this.src)">
                        <?php else: ?> - <?php endif; ?>
                    </td>
                    <td class="text-primary fw-bold"><?= $jam_m ?></td>
                    <td class="text-danger fw-bold"><?= $jam_p ?></td>
                    <td><span class="text-danger small"><?= ($r['menit_lembur'] > 0) ? '+'.$r['menit_lembur'].'m' : '-' ?></span></td>
                    <td class="text-start" style="font-size: 10px;"><?= $r['status_lokasi'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalKoreksi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="/rumahojol/proses_koreksi.php" method="POST" enctype="multipart/form-data" class="w-100">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning border-0"><h5 class="modal-title fw-bold">Pengajuan Koreksi Absen</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <?php if($sisa_koreksi > 0): ?>
                        <div class="alert alert-info small border-0">Sisa kuota koreksi: <b><?= $sisa_koreksi ?>x</b> bulan ini.</div>
                        <div class="mb-3"><label class="small fw-bold">Tanggal*</label><input type="date" name="tanggal_koreksi" class="form-control" max="<?= date('Y-m-d') ?>" required></div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label class="small fw-bold">Jam Masuk*</label><input type="time" name="jam_masuk" class="form-control" required></div>
                            <div class="col-6"><label class="small fw-bold">Jam Pulang</label><input type="time" name="jam_pulang" class="form-control"></div>
                        </div>
                        <div class="mb-3"><label class="small fw-bold">Alasan*</label><textarea name="alasan" class="form-control" rows="2" required></textarea></div>
                    <?php else: ?>
                        <div class="text-center py-3"><i class="bi bi-x-octagon text-danger fs-1"></i><p class="fw-bold mt-2">Jatah Koreksi Habis</p></div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <?php if($sisa_koreksi > 0): ?> <button type="submit" class="btn btn-warning fw-bold px-4 shadow-sm">Ajukan</button> <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Jam Realtime
setInterval(() => { document.getElementById('jamRealtime').innerText = new Date().toLocaleTimeString('id-ID', { hour12: false }); }, 1000);

// Mesin Geofencing
function hitungJarak(lat1, lon1, lat2, lon2) {
    var R = 6371e3; 
    var dLat = (lat2-lat1) * Math.PI/180; var dLon = (lon2-lon1) * Math.PI/180;
    var a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLon/2) * Math.sin(dLon/2);
    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
}

function jalankanGeofencing(tipe) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            let lat = pos.coords.latitude; let lng = pos.coords.longitude;
            let dist = hitungJarak(lat, lng, -6.171400, 106.783016); // Titik Tanjung Duren
            let label = (dist <= 50) ? "Di Area Kantor" : "Di Luar Area";
            if(tipe === 'masuk') {
                document.getElementById('latlongMasuk').value = lat + "," + lng;
                document.getElementById('teksLokasiMasuk').value = label;
                document.getElementById('statusLokasiUI').innerHTML = `<span class="text-success fw-bold">${label}</span>`;
                if(dist > 50) document.getElementById('areaKeteranganLuar').classList.remove('d-none');
                document.getElementById('btnSubmitMasuk').disabled = false;
            } else {
                document.getElementById('latlongPulang').value = lat + "," + lng;
                document.getElementById('teksLokasiPulang').value = label;
                document.getElementById('statusLokasiPulangUI').innerHTML = `<span class="text-success fw-bold">${label}</span>`;
            }
        });
    }
}

document.addEventListener("DOMContentLoaded", function() {
    <?php if(!$sudah_absen_masuk): ?> jalankanGeofencing('masuk'); 
    <?php elseif(!$sudah_absen_pulang): ?> jalankanGeofencing('pulang'); 
        setInterval(() => {
            let skrg = new Date().toLocaleTimeString('id-ID', { hour12: false });
            if (skrg >= "<?= $jam_pulang_standar ?>") {
                document.getElementById('btnSubmitPulang').disabled = false;
                document.getElementById('pesanPulang').innerHTML = `<span class="text-success">Sudah waktunya pulang!</span>`;
            }
        }, 1000);
    <?php endif; ?>
});
</script>