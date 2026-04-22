<?php
date_default_timezone_set('Asia/Jakarta');
include 'koneksi_db.php';
session_start();

// Cek Cookie & Session
if (!isset($_SESSION['user_login']) && isset($_COOKIE['cookie_email'])) {
    $email_cookie = $_COOKIE['cookie_email'];
    $cek_cookie = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email_cookie'");
    if (mysqli_num_rows($cek_cookie) > 0) {
        $data_cookie = mysqli_fetch_assoc($cek_cookie);
        $_SESSION['user_login'] = true;
        $_SESSION['nama_user'] = $data_cookie['nama'];
        $_SESSION['role_user'] = $data_cookie['role'];
    }
}

// Jika setelah dicek tidak ada session DAN tidak ada cookie, lempar ke login
if (!isset($_SESSION['user_login'])) {
    header("Location: login.php");
    exit;
}

// Variabel data user yang sedang aktif
$nama_user_aktif = $_SESSION['nama_user'];
$role_user_aktif = $_SESSION['role_user'];

if(isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'id'; 
$asset_aktif = $_GET['asset'] ?? 'dashboard';

// Global Parameter untuk Filter (Baterai & Sparepart)
$search_f   = $_GET['search'] ?? '';
$kategori_f = $_GET['kategori'] ?? ''; 
$lokasi_f   = $_GET['lokasi'] ?? '';
$tipe_f     = $_GET['tipe'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>GOECO - Assets Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        :root {
            --primary-green: #10b981; 
            --soft-green: #ecfdf5;    
            --dark-slate: #0f172a;    
            --text-main: #334155;     
            --bg-light: #f8fafc;      
        }
        
        /* Sidebar default untuk Desktop */
        @media (min-width: 992px) {
            .sidebar {
                position: sticky;
                top: 0;
                height: 100vh;
                overflow-y: auto;
            }
            .mobile-nav { display: none; } /* Sembunyikan navbar mobile di desktop */
        }

        /* Pengaturan untuk Layar HP (Mobile) */
        @media (max-width: 991px) {
            .sidebar {
                position: fixed !important;
                top: 0 !important;
                left: -300px !important; /* Paksa sembunyi ke luar kiri layar */
                width: 260px !important;
                height: 100vh !important;
                z-index: 1050 !important;
                transition: left 0.3s ease !important;
            }
            .sidebar.show {
                left: 0 !important; /* Paksa muncul saat tombol garis tiga diklik */
            }
            .main-content {
                width: 100% !important;
                padding-top: 80px !important; /* Beri jarak agar konten tidak tertutup navbar atas */
            }
            .mobile-nav {
                display: flex;
                position: fixed;
                top: 0;
                width: 100%;
                z-index: 1040;
                background-color: #111827; /* Warna gelap GOECO */
            }
        }        
        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; color: var(--text-main); }
        
        .sidebar { background: var(--dark-slate); padding-top: 24px; box-shadow: 4px 0 20px rgba(0,0,0,0.05); position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .sidebar h4 { color: #ffffff; letter-spacing: -0.5px; }
        
        .nav-link { color: #94a3b8; margin: 4px 12px; border-radius: 8px; padding: 10px 16px; font-weight: 500; transition: all 0.2s ease; }
        .nav-link:hover { color: var(--primary-green) !important; background: rgba(16, 185, 129, 0.05); transform: translateX(5px); }
        .nav-link.active-main { background: rgba(16, 185, 129, 0.1) !important; color: var(--primary-green) !important; font-weight: 600; }
        
        .submenu { border-left: 1px solid #334155; margin-left: 24px; padding-left: 8px; margin-top: 4px; }
        
        .nav-link.active-sub { color: var(--primary-green) !important; font-weight: 700; background: rgba(16, 185, 129, 0.1) !important; border-radius: 8px; margin-left: 10px; position: relative;}
        .nav-link.active-sub::before { content: ""; position: absolute; left: 0; top: 50%; transform: translateY(-50%); height: 20px; width: 3px; background-color: var(--primary-green); border-radius: 0 4px 4px 0; }
        
        .main-content { padding: 40px; animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.card-custom {
    background: #ffffff;
    border: none;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 5px 10px rgba(0,0,0,0.05);
}        
.table thead th { background-color: #fcfcfc; border-bottom: 1px solid #f1f5f9; text-transform: uppercase; font-size: 0.75rem; font-weight: 700; color: #64748b; letter-spacing: 0.05em; padding: 14px 16px; }
        .table td { vertical-align: middle; padding: 16px; border-bottom: 1px solid #f1f5f9; }
        
        .btn-theme { background-color: var(--primary-green); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.2s ease; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
        .btn-theme:hover { background-color: #059669; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3); color: white; }
        
        .filter-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .sidebar-heading { color: #475569; font-size: 0.75rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-top: 20px; margin-bottom: 10px; padding-left: 16px; }
        .badge-soft-theme { background-color: var(--soft-green); color: #065f46; border: 1px solid #a7f3d0; font-weight: 500; border-radius: 6px;}
        
        .btn-outline-primary { color: var(--primary-green); border-color: var(--primary-green); }
        .btn-outline-primary:hover, .btn-outline-primary.active { background-color: var(--primary-green) !important; border-color: var(--primary-green) !important; color: white !important; }
        
        .spin-icon { display: inline-block; animation: gear-spin 2s linear infinite; color: #f59e0b; }
        @keyframes gear-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        
        .italic { font-style: italic; }
        .img-upload-label { background-color: #f8f9fa; transition: all 0.2s; }
        .img-upload-label:hover { background-color: #e9ecef; border-color: #10b981 !important; }
        .img-upload-label img { object-fit: cover; width: 100%; height: 100%; }
    </style>
</head>

<body>
    <nav class="mobile-nav p-3 d-lg-none align-items-center justify-content-between shadow">
        <h5 class="text-white m-0 fw-bold">🚴 GOECO</h5>
        <button class="btn btn-outline-light btn-sm" id="btnToggle">
            <i class="bi bi-list fs-4"></i>
        </button>
    </nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar px-3">
                <h4 class="fw-bold mb-4 ms-2 mt-2"><i class="bi bi-bicycle" style="color: #10b981;"></i> GOECO</h4>
            
            <div class="px-3 mb-3 mt-3">
                <div class="btn-group w-100 shadow-sm">
                    <a href="?asset=<?= $asset_aktif ?>&lang=id" class="btn btn-sm <?= ($lang=='id')?'btn-success':'btn-light' ?>">Indonesia</a>
                    <a href="?asset=<?= $asset_aktif ?>&lang=cn" class="btn btn-sm <?= ($lang=='cn')?'btn-success':'btn-light' ?>">中文 (CN)</a>
                </div>
            </div>

            <div class="px-3 mt-4 mb-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="small text-secondary mb-1" style="font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;">
                        Login sebagai:
                    </div>
                    <div class="fw-bold text-white mb-3" style="font-size: 14px; line-height: 1.4;">
                        <?= $nama_user_aktif ?>
                    </div>
                    <a href="/rumahojol/logout.php" class="btn btn-sm btn-outline-danger w-100 py-2 shadow-sm" style="border-radius: 8px; font-weight: 600;">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </div>
            </div>            
            <nav class="nav flex-column">
                <a class="nav-link <?= ($asset_aktif == 'dashboard') ? 'active-main' : '' ?>" href="index.php"><i class="bi bi-grid me-2"></i> Dashboard</a>
                <a class="nav-link <?= $asset_aktif == 'tiketing' ? 'active-main' : '' ?>" href="?asset=tiketing"><i class="bi bi-ticket-perforated me-2"></i> Support Tickets</a>
                <a class="nav-link <?= ($asset_aktif == 'pipeline') ? 'active-main' : '' ?>" href="?asset=pipeline"><i class="bi bi-graph-up-arrow me-2"></i> Pipeline Sales</a>
                <a class="nav-link <?= ($asset_aktif == 'geomapping') ? 'active-main' : '' ?>" href="?asset=geomapping"><i class="bi bi-geo-alt me-2"></i> Geo-Mapping</a>
                <a class="nav-link <?= ($asset_aktif == 'absensi') ? 'active-main' : '' ?>" href="?asset=absensi"><i class="bi bi-person-check me-2"></i> Absensi Karyawan</a>
                <?php if($role_user_aktif == 'Admin'): ?>
                <a class="nav-link <?= ($asset_aktif == 'rekap_absensi') ? 'active-main' : '' ?>" href="?asset=rekap_absensi"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Rekap Absensi HR</a>
                

                <div class="sidebar-heading">Data Induk</div>                
                <a class="nav-link <?= in_array($asset_aktif, ['master_motor', 'master_baterai', 'master_sparepart']) ? 'active-main' : '' ?>" data-bs-toggle="collapse" href="#masterSubmenu">
                    <span><i class="bi bi-database me-2"></i> Master Data</span>
                    <i class="bi bi-chevron-down float-end mt-1" style="font-size: 12px;"></i>
                </a>
                <div class="collapse <?= in_array($asset_aktif, ['master_motor', 'master_baterai', 'master_sparepart']) ? 'show' : '' ?>" id="masterSubmenu">
                    <div class="submenu">
                        <a class="nav-link <?= $asset_aktif == 'master_motor' ? 'active-sub' : '' ?> py-2" href="?asset=master_motor">Katalog Motor</a>
                        <a class="nav-link <?= $asset_aktif == 'master_baterai' ? 'active-sub' : '' ?> py-2" href="?asset=master_baterai">Katalog Baterai</a>
                        <a class="nav-link <?= $asset_aktif == 'master_sparepart' ? 'active-sub' : '' ?> py-2" href="?asset=master_sparepart">Katalog Sparepart</a>
                    </div>
                </div>

                <div class="sidebar-heading">Transaksi</div>
                <a class="nav-link <?= in_array($asset_aktif, ['mutasi_baterai', 'mutasi_motor', 'mutasi_sparepart']) ? 'active-main' : '' ?>" data-bs-toggle="collapse" href="#mutasiSubmenu">
                    <span><i class="bi bi-arrow-left-right me-2"></i> Mutasi Aset</span>
                    <i class="bi bi-chevron-down float-end mt-1" style="font-size: 12px;"></i>
                </a>
                <div class="collapse <?= in_array($asset_aktif, ['mutasi_baterai', 'mutasi_motor', 'mutasi_sparepart']) ? 'show' : '' ?>" id="mutasiSubmenu">
                    <div class="submenu">
                        <a class="nav-link <?= $asset_aktif == 'mutasi_baterai' ? 'active-sub' : '' ?> py-2" href="?asset=mutasi_baterai">Kartu Stok Baterai</a>
                        <a class="nav-link <?= $asset_aktif == 'mutasi_motor' ? 'active-sub' : '' ?> py-2" href="?asset=mutasi_motor">Riwayat Motor</a>
                        <a class="nav-link <?= $asset_aktif == 'mutasi_sparepart' ? 'active-sub' : '' ?> py-2" href="?asset=mutasi_sparepart">Riwayat Mutasi Sparepart</a>
                    </div>
                </div>
                <?php endif; ?>
            </nav>
        </div>

        
        <div class="col-md-10 main-content">
            <?php 
            // Sistem Routing
            if (isset($_GET['asset']) && $_GET['asset'] == 'master_motor') {
                include 'master_motor.php';
            } elseif ($asset_aktif == 'pipeline') {
                include 'pipeline.php';
            } elseif ($asset_aktif == 'geomapping') {
                include 'geomapping.php';
            } elseif ($asset_aktif == 'absensi') {
                include 'absensi.php';
            } elseif ($asset_aktif == 'rekap_absensi') {
                include 'rekap_absensi.php';
            } elseif (isset($_GET['asset']) && $_GET['asset'] == 'master_sparepart') {                
                include 'master_sparepart.php';
            } elseif (isset($_GET['asset']) && $_GET['asset'] == 'master_baterai') {
                include 'master_baterai.php';
            } elseif (isset($_GET['asset']) && $_GET['asset'] == 'mutasi_motor') {
                include 'mutasi_motor.php';
            } elseif (isset($_GET['asset']) && $_GET['asset'] == 'mutasi_sparepart') {
                include 'mutasi_sparepart.php';
            } elseif (isset($_GET['asset']) && $_GET['asset'] == 'mutasi_baterai') {
                include 'mutasi_baterai.php';
            } elseif (isset($_GET['asset']) && $_GET['asset'] == 'tiketing') {
                include 'tiketing.php';
            } else { 
                // Jika tidak ada parameter asset yang cocok (atau baru buka web), langsung buka dashboard
                include 'dashboard.php';
            }
            ?> 
        </div>
    </div> 
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // -----------------------------------------------------
    // 1. DATA PHP UNTUK CHART TIKET (DIAMBIL DARI DATABASE)
    // -----------------------------------------------------
    <?php 
    $data_grafik = array_fill(1, 12, 0); 
    $q_grafik = mysqli_query($koneksi, "SELECT MONTH(created_at) as bln, COUNT(*) as jumlah FROM data_tiketing WHERE YEAR(created_at) = '$thn_ini' GROUP BY MONTH(created_at)");
    while($g = mysqli_fetch_assoc($q_grafik)) {
        $data_grafik[$g['bln']] = $g['jumlah'];
    }
    $data_json = json_encode(array_values($data_grafik));
    ?>

    // Render Chart Tiket
    if(document.getElementById('tiketChart')) {
        var ctxTiket = document.getElementById('tiketChart').getContext('2d');
        new Chart(ctxTiket, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Jumlah Tiket',
                    data: <?= $data_json ?>,
                    backgroundColor: '#10b981',
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // -----------------------------------------------------
    // 2. DATA PHP UNTUK CHART PIPELINE 
    // -----------------------------------------------------
    <?php 
    $label_alasan = []; $data_alasan = [];
    $q_grafik_alasan = mysqli_query($koneksi, "SELECT alasan_singkat, COUNT(*) as jumlah FROM data_pipeline WHERE alasan_singkat != '' AND alasan_singkat IS NOT NULL GROUP BY alasan_singkat ORDER BY jumlah ASC");
    while($a = mysqli_fetch_assoc($q_grafik_alasan)) {
        $label_alasan[] = $a['alasan_singkat'];
        $data_alasan[] = $a['jumlah'];
    }

    $label_status = []; $data_status = []; $warna_status = [];
    $q_grafik_status = mysqli_query($koneksi, "SELECT status, COUNT(*) as jumlah FROM data_pipeline GROUP BY status");
    while($s = mysqli_fetch_assoc($q_grafik_status)) {
        $label_status[] = $s['status'];
        $data_status[] = $s['jumlah'];
        
        if($s['status'] == 'Deal') $warna_status[] = '#10b981';
        elseif($s['status'] == 'Lost') $warna_status[] = '#343a40';
        elseif($s['status'] == 'Survey') $warna_status[] = '#0dcaf0';
        elseif($s['status'] == 'Hold') $warna_status[] = '#ffc107';
        else $warna_status[] = '#adb5bd';
    }
    ?>

    // Render Grafik Alasan (Horizontal Bar Chart)
    if(document.getElementById('alasanChart')) {
        var ctxAlasan = document.getElementById('alasanChart').getContext('2d');
        new Chart(ctxAlasan, {
            type: 'bar',
            data: {
                labels: <?= json_encode($label_alasan) ?>,
                datasets: [{
                    label: 'Jumlah Prospek',
                    data: <?= json_encode($data_alasan) ?>,
                    backgroundColor: '#0d6efd',
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y', // Kunci untuk horizontal
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    // Render Grafik Komposisi Status (Donut Chart)
    if(document.getElementById('statusChart')) {
        var ctxStatus = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($label_status) ?>,
                datasets: [{
                    data: <?= json_encode($data_status) ?>,
                    backgroundColor: <?= json_encode($warna_status) ?>,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } }
                },
                cutout: '70%'
            }
        });
    }
});
</script>

<script>
$(document).ready(function() {

// Script untuk Mobile Menu Toggle
if(document.getElementById('btnToggle')) {
    document.getElementById('btnToggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show');
    });
}
if(document.querySelector('.main-content')) {
    document.querySelector('.main-content').addEventListener('click', function() {
        var sidebar = document.querySelector('.sidebar');
        if(sidebar && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    });
}
});
</script>

</body>