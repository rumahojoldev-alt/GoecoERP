<?php
// 1. QUERY HITUNG ASSET (Kartu Atas)
$q_motor = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM master_motor");
$tot_motor = mysqli_fetch_assoc($q_motor)['total'] ?? 0;

$q_bat = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM master_baterai WHERE Status='OK'");
$tot_bat = mysqli_fetch_assoc($q_bat)['total'] ?? 0;

// 2. LOGIKA LEADERBOARD MEKANIK (Top 3 - Bulan Ini)
$list_mekanik = mysqli_query($koneksi, "SELECT nama_mekanik, COUNT(*) as jml FROM data_tiketing 
    WHERE status = 'Completed' AND MONTH(waktu_selesai) = MONTH(CURRENT_DATE) 
    GROUP BY nama_mekanik ORDER BY jml DESC LIMIT 3");
$top_mekanik = [];
while($row = mysqli_fetch_assoc($list_mekanik)) { $top_mekanik[] = $row; }

// 3. LOGIKA LEADERBOARD SALES (Top 3 - Bulan Ini)
$list_sales = mysqli_query($koneksi, "SELECT sales_ditugaskan, COUNT(*) as jml FROM data_pipeline 
    WHERE status = 'Deal' AND MONTH(waktu_input) = MONTH(CURRENT_DATE) 
    GROUP BY sales_ditugaskan ORDER BY jml DESC LIMIT 3");
$top_sales = [];
while($row = mysqli_fetch_assoc($list_sales)) { $top_sales[] = $row; }

// 4. LOGIKA LEADERBOARD SURVEYOR (Top 3 - Bulan Ini)
$list_surveyor = mysqli_query($koneksi, "SELECT sales_ditugaskan, COUNT(*) as jml FROM data_pipeline 
    WHERE status = 'Survey' AND MONTH(waktu_input) = MONTH(CURRENT_DATE) 
    GROUP BY sales_ditugaskan ORDER BY jml DESC LIMIT 3");
$top_surveyor = [];
while($row = mysqli_fetch_assoc($list_surveyor)) { $top_surveyor[] = $row; }

// 5. LOGIKA LEADERBOARD CS (Top 3 Input Terbanyak - Bulan Ini)
// Mencocokkan nama di tabel users dengan kolom created_by di data_pipeline
$list_cs = mysqli_query($koneksi, "
    SELECT u.nama, COUNT(p.id_pipeline) as jml 
    FROM users u
    LEFT JOIN data_pipeline p ON u.nama = p.created_by 
        AND MONTH(p.waktu_input) = MONTH(CURRENT_DATE) 
        AND YEAR(p.waktu_input) = YEAR(CURRENT_DATE)
    WHERE u.role = 'CS'
    GROUP BY u.nama 
    ORDER BY jml DESC 
    LIMIT 3
");

$top_cs = [];
while($row = mysqli_fetch_assoc($list_cs)) { 
    $top_cs[] = $row; 
}

// 6. DATA CHART BOS
$labels = []; $counts = []; $colors = [];
$q_chart = mysqli_query($koneksi, "SELECT status, COUNT(*) as jml FROM data_pipeline GROUP BY status");
while($row = mysqli_fetch_assoc($q_chart)) {
    $labels[] = $row['status'];
    $counts[] = $row['jml'];
    $colors[] = ($row['status']=='Deal') ? '#10b981' : (($row['status']=='Lost') ? '#ef4444' : '#f59e0b');
}
// --- TAMBAHAN LOGIKA ANALISA BARU ---

// A. Query Analisa Kerusakan (Berdasarkan keluhan di tiket)
$label_rusak = []; $data_rusak = [];
$q_rusak = mysqli_query($koneksi, "SELECT kategori_masalah, COUNT(*) as jml FROM data_tiketing GROUP BY kategori_masalah ORDER BY jml DESC LIMIT 5");

// Jangan lupa update loop while-nya juga di bawahnya
while($r = mysqli_fetch_assoc($q_rusak)) {
    $label_rusak[] = $r['kategori_masalah']; // Pakai kategori_masalah
    $data_rusak[] = $r['jml'];
}

// B. Query Analisa Alasan Lost (Berdasarkan alasan_singkat di pipeline)
$label_lost = []; $data_lost = [];
$q_lost = mysqli_query($koneksi, "SELECT alasan_singkat, COUNT(*) as jml FROM data_pipeline WHERE status IN ('Lost', 'Terminate') AND alasan_singkat IS NOT NULL AND alasan_singkat != '' GROUP BY alasan_singkat ORDER BY jml DESC LIMIT 5");
while($l = mysqli_fetch_assoc($q_lost)) {
    // Dipotong sedikit biar nggak kepanjangan di grafik
    $label_lost[] = (strlen($l['alasan_singkat']) > 20) ? substr($l['alasan_singkat'],0,20).'...' : $l['alasan_singkat'];
    $data_lost[] = $l['jml'];
}
?>

<style>
/* 1. CSS DASAR KARTU */
.card-custom {
    background: #ffffff;
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

/* 2. CSS PREMIUM KHUSUS THE CHAMPIONS */
.card-champion {
    background: #ffffff;
    border: none;
    border-radius: 20px;
    transition: all 0.3s ease;
    /* Bayangan tebal & berlapis agar terlihat timbul */
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1), 0 4px 8px rgba(0, 0, 0, 0.04);
}

.card-champion:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.icon-circle-premium {
    padding: 25px;
    border-radius: 50%;
    display: inline-block;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}
</style>

<div class="mb-4">
    <h3 class="fw-bold m-0 text-dark">Dashboard Overview</h3>
    <p class="text-muted small">Selamat datang, <b class="text-primary"><?= $nama_user_aktif ?></b>! Pantau aset dan prestasi tim hari ini.</p>
</div>

<div class="row g-3 mb-5">
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-5 border-success h-100">
            <div class="text-muted small fw-bold text-uppercase">Total Unit Motor</div>
            <div class="h3 fw-bold mb-0"><?= $tot_motor ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-5 border-primary h-100">
            <div class="text-muted small fw-bold text-uppercase">Baterai Ready</div>
            <div class="h3 fw-bold mb-0"><?= $tot_bat ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-5 border-warning h-100">
            <div class="text-muted small fw-bold text-uppercase">Stok Kritis</div>
            <div class="h3 fw-bold mb-0">3</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-5 border-danger h-100">
            <div class="text-muted small fw-bold text-uppercase">Cabang Aktif</div>
            <div class="h3 fw-bold mb-0">4</div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark m-0"><i class="bi bi-trophy-fill me-2 text-warning fs-3"></i>The Champions</h4>
    <span class="badge bg-light text-muted border px-3 py-2 shadow-sm">Bulan Ini</span>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card card-custom py-5 px-3 text-center shadow-sm border-0 h-100 border-bottom border-5 border-success">
            <div class="mb-4">
                <span class="badge bg-success-subtle text-success p-4 rounded-circle shadow-sm">
                    <i class="bi bi-tools" style="font-size: 2.5rem;"></i>
                </span>
            </div>
            <div class="text-success fw-bold small text-uppercase mb-2">Mekanik Ter-Gercep</div>
            <h4 class="fw-bolder text-dark mb-3 text-truncate" title="<?= $top_mekanik[0]['nama_mekanik'] ?? '-' ?>"><?= $top_mekanik[0]['nama_mekanik'] ?? '-' ?></h4>
            <div>
                <span class="badge bg-success rounded-pill py-2 px-4 fs-6 shadow-sm"><?= $top_mekanik[0]['jml'] ?? 0 ?> Unit</span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-custom py-5 px-3 text-center shadow-sm border-0 h-100 border-bottom border-5 border-primary">
            <div class="mb-4">
                <span class="badge bg-primary-subtle text-primary p-4 rounded-circle shadow-sm">
                    <i class="bi bi-megaphone" style="font-size: 2.5rem;"></i>
                </span>
            </div>
            <div class="text-primary fw-bold small text-uppercase mb-2">Sales Ter-Gacor</div>
            <h4 class="fw-bolder text-dark mb-3 text-truncate" title="<?= $top_sales[0]['sales_ditugaskan'] ?? '-' ?>"><?= $top_sales[0]['sales_ditugaskan'] ?? '-' ?></h4>
            <div>
                <span class="badge bg-primary rounded-pill py-2 px-4 fs-6 shadow-sm"><?= $top_sales[0]['jml'] ?? 0 ?> Deal</span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-custom py-5 px-3 text-center shadow-sm border-0 h-100 border-bottom border-5 border-warning">
            <div class="mb-4">
                <span class="badge bg-warning-subtle text-warning p-4 rounded-circle shadow-sm">
                    <i class="bi bi-lightning-charge" style="font-size: 2.5rem;"></i>
                </span>
            </div>
            <div class="fw-bold small text-uppercase mb-2" style="color: #d97706;">Surveyor Ter-Lincah</div>
            <h4 class="fw-bolder text-dark mb-3 text-truncate" title="<?= $top_surveyor[0]['sales_ditugaskan'] ?? '-' ?>"><?= $top_surveyor[0]['sales_ditugaskan'] ?? '-' ?></h4>
            <div>
                <span class="badge bg-warning text-dark rounded-pill py-2 px-4 fs-6 shadow-sm"><?= $top_surveyor[0]['jml'] ?? 0 ?> Lokasi</span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-custom py-5 px-3 text-center shadow-sm border-0 h-100 border-bottom border-5 border-info">
            <div class="mb-4">
                <span class="badge bg-info-subtle text-info p-4 rounded-circle shadow-sm">
                    <i class="bi bi-headset" style="font-size: 2.5rem;"></i>
                </span>
            </div>
            <div class="fw-bold small text-uppercase mb-2" style="color: #0891b2;">CS Ter-Satset</div>
            <h4 class="fw-bolder text-dark mb-3 text-truncate" title="<?= $top_cs[0]['nama'] ?? '-' ?>"><?= $top_cs[0]['nama'] ?? '-' ?></h4>
            <div>
                <span class="badge bg-info text-dark rounded-pill py-2 px-4 fs-6 shadow-sm"><?= $top_cs[0]['jml'] ?? 0 ?> Input</span>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-5">
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm h-100">
            <h6 class="fw-bold mb-3 small text-uppercase text-muted border-bottom pb-2">Top 3 Mekanik</h6>
            <?php foreach($top_mekanik as $index => $m): ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-dark"><?= ($index+1) ?>. <?= $m['nama_mekanik'] ?></span>
                <span class="badge bg-light text-success border border-success small"><?= $m['jml'] ?> unit</span>
            </div>
            <?php endforeach; if(empty($top_mekanik)) echo "<p class='small text-muted'>Data belum tersedia</p>"; ?>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm h-100">
            <h6 class="fw-bold mb-3 small text-uppercase text-muted border-bottom pb-2">Top 3 Sales</h6>
            <?php foreach($top_sales as $index => $s): ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-dark"><?= ($index+1) ?>. <?= $s['sales_ditugaskan'] ?></span>
                <span class="badge bg-light text-primary border border-primary small"><?= $s['jml'] ?> deal</span>
            </div>
            <?php endforeach; if(empty($top_sales)) echo "<p class='small text-muted'>Data belum tersedia</p>"; ?>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm h-100">
            <h6 class="fw-bold mb-3 small text-uppercase text-muted border-bottom pb-2">Top 3 Surveyor</h6>
            <?php foreach($top_surveyor as $index => $sv): ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-dark"><?= ($index+1) ?>. <?= $sv['sales_ditugaskan'] ?></span>
                <span class="badge bg-light text-warning border border-warning text-dark small"><?= $sv['jml'] ?> lokasi</span>
            </div>
            <?php endforeach; if(empty($top_surveyor)) echo "<p class='small text-muted'>Data belum tersedia</p>"; ?>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-0 shadow-sm h-100">
            <h6 class="fw-bold mb-3 small text-uppercase text-muted border-bottom pb-2">Top 3 CS</h6>
            <?php foreach($top_cs as $index => $cs): ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-dark"><?= ($index+1) ?>. <?= $cs['nama'] ?></span>
                <span class="badge bg-light text-info border border-info text-dark small"><?= $cs['jml'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    
    <div class="col-md-6">
        <div class="card card-custom p-4 border-0 shadow-sm h-100">
            <h6 class="fw-bold mb-1 text-dark">
                <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>Top 5 Kerusakan Motor
            </h6>
            <p class="small text-muted mb-4">Jenis keluhan yang paling sering dilaporkan.</p>
            
            <div style="height: 300px;">
                <canvas id="chartKerusakan"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-1">
                    <i class="bi bi-person-x-fill text-danger me-2"></i>Top 5 Alasan Prospek Lost
                </h6>
                <p class="text-muted small mb-3">Penyebab utama prospek tidak lanjut (Deal).</p>

                <?php
                // Mesin pencari: Hitung alasan lost, urutkan dari yang terbanyak, ambil 5 teratas
                $q_alasan_lost = mysqli_query($koneksi, "
                    SELECT alasan_singkat, COUNT(*) as total_alasan 
                    FROM data_pipeline 
                    WHERE status = 'Lost' AND alasan_singkat IS NOT NULL AND alasan_singkat != '' 
                    GROUP BY alasan_singkat 
                    ORDER BY total_alasan DESC 
                    LIMIT 5
                ");

                if(mysqli_num_rows($q_alasan_lost) > 0): ?>
                    <div class="mt-3">
                        <?php while($lost = mysqli_fetch_assoc($q_alasan_lost)): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-light">
                                <span class="small fw-semibold text-secondary"><?= $lost['alasan_singkat'] ?></span>
                                <span class="badge bg-danger rounded-pill px-2"><?= $lost['total_alasan'] ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted fst-italic small">
                        Belum ada data alasan prospek lost.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
<?php
// B. Ambil data Kehadiran Karyawan Hari Ini
$hadir_hari_ini = 0;
try {
    // Menghitung jumlah nama_user unik yang absen (waktu_masuk) hari ini
    $q_absen = mysqli_query($koneksi, "SELECT COUNT(DISTINCT nama_user) as hadir FROM data_absensi WHERE DATE(waktu_masuk) = CURRENT_DATE()");
    
    if($q_absen) {
        $hadir_hari_ini = mysqli_fetch_assoc($q_absen)['hadir'] ?? 0;
    }
} catch (mysqli_sql_exception $e) {
    $hadir_hari_ini = 0; 
}

// C. Hitung Total Karyawan (Dinamis / Otomatis)
$q_total_karyawan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users");
if($q_total_karyawan) {
    $total_karyawan = mysqli_fetch_assoc($q_total_karyawan)['total'] ?? 0;
} else {
    $total_karyawan = 22; // Fallback jika error
}
?>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card card-custom p-4 h-100 shadow-sm border-0">
            <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Rasio Pipeline</h6>
            <p class="small text-muted mb-4">Perbandingan status prospek.</p>
            <div style="height: 220px;">
                <canvas id="bosDonutChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card card-custom p-4 h-100 shadow-sm border-0">
            <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Tren Deal (<?= date('Y') ?>)</h6>
            <p class="small text-muted mb-4">Grafik pertumbuhan konversi berhasil per bulan.</p>
            <div style="height: 220px;">
                <canvas id="bosLineChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-custom p-3 mb-3 shadow-sm border-0 bg-dark text-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold m-0"><i class="bi bi-person-check me-2 text-info"></i>Kehadiran Tim</h6>
                <span class="badge bg-light text-dark small">Hari Ini</span>
            </div>
            <div class="mt-3 text-center">
                <h2 class="fw-bolder text-info mb-0"><?= $hadir_hari_ini ?> <span class="fs-6 text-muted fw-normal">/ <?= $total_karyawan ?> org</span></h2>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-info" style="width: <?= ($total_karyawan > 0) ? ($hadir_hari_ini/$total_karyawan)*100 : 0 ?>%"></div>
                </div>
            </div>
        </div>

        <div class="card card-custom p-3 shadow-sm border-0">
            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-shield-check me-2 text-success"></i>Kondisi Aset</h6>
            <div class="d-flex justify-content-between align-items-center mb-2 small">
                <span class="text-muted">Motor Siap / Sewa</span>
                <span class="fw-bold text-success">90%</span>
            </div>
            <div class="progress mb-3" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: 90%"></div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-2 small">
                <span class="text-muted">Motor Rusak / Bengkel</span>
                <span class="fw-bold text-danger">10%</span>
            </div>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-danger" style="width: 10%"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Render Donut Chart (Tetap dipertahankan)
    if(document.getElementById('bosDonutChart')) {
        new Chart(document.getElementById('bosDonutChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    data: <?= json_encode($counts) ?>,
                    backgroundColor: <?= json_encode($colors) ?>,
                    borderWidth: 0
                }]
            },
            options: { 
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
                cutout: '75%'
            }
        });
    }

    // 2. Render Line Chart (BARU: Tren Deal)
    if(document.getElementById('bosLineChart')) {
        new Chart(document.getElementById('bosLineChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($data_bulan) ?>,
                datasets: [{
                    label: 'Total Deal',
                    data: <?= json_encode($trend_deal) ?>,
                    borderColor: '#10b981', // Warna Hijau GOECO
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.4, // Bikin garisnya melengkung halus (smooth)
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { borderDash: [4, 4] } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
    // --- SCRIPT UNTUK GRAFIK ANALISA BARU ---

    // 1. Inisialisasi Grafik Kerusakan (Bar Chart Horizontal)
    const ctxRusak = document.getElementById('chartKerusakan');
    if(ctxRusak) {
        new Chart(ctxRusak, {
            type: 'bar',
            data: {
                labels: <?= json_encode($label_rusak) ?>,
                datasets: [{
                    label: 'Jumlah Kejadian',
                    data: <?= json_encode($data_rusak) ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y', // Membuat bar menjadi horizontal
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // 2. Inisialisasi Grafik Alasan Lost (Doughnut Chart)
    const ctxLost = document.getElementById('chartLostReason');
    if(ctxLost) {
        new Chart(ctxLost, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($label_lost) ?>,
                datasets: [{
                    data: <?= json_encode($data_lost) ?>,
                    backgroundColor: ['#4b5563', '#6b7280', '#9ca3af', '#d1d5db', '#e5e7eb'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } 
                },
                cutout: '65%' // Membuat lubang di tengah
            }
        });
    }
});
</script>