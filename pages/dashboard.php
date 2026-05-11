<?php
/**
 * Copyright 2026 RIZAPATIID - PROJECT
 * Email : rizapatiid@gmail.com
 */
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}
include '../config/db.php';

// Role-based data fetching
$role = $_SESSION['user']['role'];
$username = $_SESSION['user']['username'];

if ($role == 'admin') {
    // Admin: Get global counts
    $siswa_count = $conn->query("SELECT COUNT(*) as total FROM siswa")->fetch_assoc()['total'];
    $guru_count = $conn->query("SELECT COUNT(*) as total FROM guru")->fetch_assoc()['total'];
    $mapel_count = $conn->query("SELECT COUNT(*) as total FROM mapel")->fetch_assoc()['total'];

    // Fetch Chart Data: Siswa per Jurusan
    $jurusan_data = $conn->query("SELECT jurusan, COUNT(*) as count FROM siswa GROUP BY jurusan");
    $chart_labels = [];
    $chart_counts = [];
    if ($jurusan_data) {
        while($row = $jurusan_data->fetch_assoc()) {
            $chart_labels[] = empty($row['jurusan']) ? 'Umum' : $row['jurusan'];
            $chart_counts[] = $row['count'];
        }
    }
} elseif ($role == 'guru') {
    // Guru: Get specific data for this teacher
    $guru_info = $conn->query("SELECT * FROM guru WHERE nip = '$username'")->fetch_assoc();
    $wali_kelas = $conn->query("SELECT * FROM kelas WHERE wali_guru = '$username'")->fetch_assoc();
    $mapel_guru = $conn->query("SELECT COUNT(*) as total FROM mapel WHERE nip_guru = '$username'")->fetch_assoc()['total'];
    
    $total_siswa_wali = 0;
    if ($wali_kelas) {
        $nk = $wali_kelas['nama_kelas'];
        $total_siswa_wali = $conn->query("SELECT COUNT(*) as total FROM siswa WHERE kelas = '$nk'")->fetch_assoc()['total'];
    }
} elseif ($role == 'siswa') {
    // Siswa: Get specific data for this student
    $siswa_info = $conn->query("SELECT * FROM siswa WHERE nis = '$username'")->fetch_assoc();
    $kelas_siswa = $siswa_info['kelas'];
    
    // Get subjects for this student's class
    $mapel_count = 0;
    if ($kelas_siswa) {
        $k_res = $conn->query("SELECT id FROM kelas WHERE nama_kelas = '$kelas_siswa'");
        if ($k_res->num_rows > 0) {
            $kid = $k_res->fetch_assoc()['id'];
            $mapel_count = $conn->query("SELECT COUNT(*) as total FROM kelas_mapel WHERE id_kelas = $kid")->fetch_assoc()['total'];
        }
    }
}

// Fetch Latest 5 Siswa
$latest_siswa = $conn->query("SELECT nis, nama, kelas, jurusan FROM siswa ORDER BY nis DESC LIMIT 5");

// Generate localized date safely
$days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$date_str = $days[date('w')] . ', ' . date('j') . ' ' . $months[date('n')] . ' ' . date('Y');

$page_title = 'Beranda Dasbor';
include '../layouts/header.php'; 
?>
<style>
    /* Premium Dashboard Styles */
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        border-radius: 20px;
        padding: 3rem 2.5rem;
        color: white;
        position: relative;
        overflow: hidden;
        margin-bottom: 2rem;
        box-shadow: 0 15px 30px rgba(15, 23, 42, 0.15);
    }
    .welcome-banner::before, .welcome-banner::after {
        content: '';
        position: absolute;
        border-radius: 50%;
    }
    .welcome-banner::before {
        top: -50px; right: -50px;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, transparent 70%);
    }
    .welcome-banner::after {
        bottom: -80px; right: 150px;
        width: 250px; height: 250px;
        background: radial-gradient(circle, rgba(16,185,129,0.1) 0%, transparent 70%);
    }
    
    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 1.75rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
    }
    .stat-icon {
        width: 70px; height: 70px;
        border-radius: 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 2.5rem; flex-shrink: 0;
    }
    .stat-info h3 {
        font-size: 2.2rem; font-weight: 800; margin-bottom: 0;
        color: #0f172a; line-height: 1; letter-spacing: -1px;
    }
    .stat-info p {
        margin-bottom: 0; color: #64748b; font-weight: 600;
        margin-top: 0.5rem; font-size: 0.95rem;
    }

    .dashboard-panel {
        background: white;
        border-radius: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        padding: 1.75rem;
        height: 100%;
    }

    .quick-action-card {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1rem;
        text-align: center;
        text-decoration: none;
        color: #334155;
        border: 1px solid transparent;
        transition: all 0.3s ease;
        display: block;
        height: 100%;
    }
    .quick-action-card:hover {
        border-color: var(--accent-color);
        background-color: #ffffff;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.1);
    }
    .quick-icon {
        width: 45px; height: 45px;
        border-radius: 50%;
        background: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin: 0 auto 0.75rem auto;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .quick-title {
        font-weight: 700; margin-bottom: 0.2rem; font-size: 0.9rem; color: #0f172a;
    }
    .quick-desc {
        font-size: 0.75rem; color: #94a3b8; margin-bottom: 0; font-weight: 500;
    }
    
    .panel-title {
        font-weight: 800; color: #0f172a; margin-bottom: 1.5rem;
        display: flex; align-items: center; gap: 0.5rem; letter-spacing: -0.5px;
    }
    
    /* Custom Table for Dashboard */
    .table-modern th {
        background-color: #f8fafc; color: #64748b; font-weight: 600;
        text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px;
        padding: 1rem; border-bottom: 2px solid #e2e8f0;
    }
    .table-modern td { padding: 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .table-modern tr:hover td { background-color: #f8fafc; }
</style>

<div class="container-fluid px-0">
    
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="position-relative" style="z-index: 1;">
            <p class="text-white text-opacity-75 fw-medium mb-2" style="font-size: 0.95rem;">
                <i class='bx bx-calendar-event me-1'></i> <?php echo $date_str; ?>
            </p>
            <h2 class="fw-bold mb-3" style="font-size: 2.5rem; letter-spacing: -1px;">
                Halo, <?php echo htmlspecialchars(ucfirst($user_display_name)); ?>! 👋
            </h2>
            <p class="mb-0 fs-6 text-white text-opacity-75" style="max-width: 600px; line-height: 1.6;">
                Selamat datang di portal utama SIAKAD. Pantau perkembangan akademik <?php echo strtolower(LBL_INSTANSI); ?>, analisis data <?php echo strtolower(LBL_SISWA); ?> secara real-time, dan kelola manajemen dengan efisien.</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-4 mb-4">
        <?php if ($role == 'admin'): ?>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #eff6ff; color: #3b82f6;">
                        <i class='bx bxs-group'></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($siswa_count); ?></h3>
                        <p>Total <?php echo LBL_SISWA; ?> Aktif</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #ecfdf5; color: #10b981;">
                        <i class='bx bxs-chalkboard'></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($guru_count); ?></h3>
                        <p>Tenaga Pendidik (<?php echo LBL_GURU; ?>)</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #f5f3ff; color: #8b5cf6;">
                        <i class='bx bxs-book-alt'></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($mapel_count); ?></h3>
                        <p><?php echo LBL_MAPEL; ?></p>
                    </div>
                </div>
            </div>
        <?php elseif ($role == 'guru'): ?>
            <div class="col-12 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #eff6ff; color: #3b82f6;">
                        <i class='bx bxs-graduation'></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $wali_kelas ? $wali_kelas['nama_kelas'] : 'N/A'; ?></h3>
                        <p>Wali <?php echo LBL_KELAS; ?>: <?php echo $total_siswa_wali; ?> <?php echo LBL_SISWA; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #f5f3ff; color: #8b5cf6;">
                        <i class='bx bxs-book-open'></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $mapel_guru; ?></h3>
                        <p><?php echo LBL_MAPEL; ?> Diampu</p>
                    </div>
                </div>
            </div>
        <?php elseif ($role == 'siswa'): ?>
            <div class="col-12 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #eff6ff; color: #3b82f6;">
                        <i class='bx bxs-school'></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $kelas_siswa ?: 'Belum Ada'; ?></h3>
                        <p><?php echo LBL_KELAS; ?> Saat Ini</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #f5f3ff; color: #8b5cf6;">
                        <i class='bx bxs-book'></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $mapel_count; ?></h3>
                        <p>Jumlah <?php echo LBL_MAPEL; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($role == 'admin'): ?>
        <!-- Middle Section: Chart & Quick Access -->
        <div class="row g-4 mb-4">
            <!-- Analytics Chart -->
            <div class="col-12 col-lg-8">
                <div class="dashboard-panel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="panel-title mb-0"><i class='bx bx-bar-chart-alt-2 text-primary fs-4'></i> Statistik Jurusan</h5>
                    </div>
                    <div style="height: 250px;">
                        <canvas id="jurusanChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Quick Access Grid -->
            <div class="col-12 col-lg-4">
                <div class="dashboard-panel">
                    <h5 class="panel-title"><i class='bx bxs-zap text-warning fs-4'></i> Menu Cepat</h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="siswa.php?action=add" class="quick-action-card">
                                <div class="quick-icon" style="color: #3b82f6;"><i class='bx bx-user-plus'></i></div>
                                <h6 class="quick-title"><?php echo LBL_SISWA; ?> Baru</h6>
                                <p class="quick-desc">Daftar <?php echo strtolower(LBL_SISWA); ?></p>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="guru.php?action=add" class="quick-action-card">
                                <div class="quick-icon" style="color: #10b981;"><i class='bx bx-user-voice'></i></div>
                                <h6 class="quick-title"><?php echo LBL_GURU; ?> Baru</h6>
                                <p class="quick-desc">Daftar staf</p>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="nilai.php?action=add" class="quick-action-card">
                                <div class="quick-icon" style="color: #f59e0b;"><i class='bx bx-edit-alt'></i></div>
                                <h6 class="quick-title">Input Nilai</h6>
                                <p class="quick-desc">Rekap ujian</p>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="mapel.php" class="quick-action-card">
                                <div class="quick-icon" style="color: #8b5cf6;"><i class='bx bx-book-add'></i></div>
                                <h6 class="quick-title">Tambah <?php echo LBL_MAPEL; ?></h6>
                                <p class="quick-desc">Kurikulum</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Latest Data Table -->
        <div class="dashboard-panel mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="panel-title mb-0"><i class='bx bx-history text-success fs-4'></i> <?php echo LBL_SISWA; ?> Terdaftar Baru</h5>
                <a href="siswa.php" class="btn btn-sm btn-primary fw-medium px-3 rounded-pill">Lihat Semua Data</a>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-modern w-100">
                    <thead>
                        <tr>
                            <th>NIS</th>
                            <th>Nama <?php echo LBL_SISWA; ?></th>
                            <th><?php echo LBL_KELAS; ?></th>
                            <th>Jurusan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($latest_siswa && $latest_siswa->num_rows > 0): ?>
                            <?php while($row = $latest_siswa->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-dark">#<?php echo htmlspecialchars($row['nis']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;font-weight:700;">
                                            <?php echo strtoupper(substr($row['nama'], 0, 1)); ?>
                                        </div>
                                        <span class="fw-medium text-dark"><?php echo htmlspecialchars($row['nama']); ?></span>
                                    </div>
                                </td>
                                <td><span class="text-muted fw-medium"><?php echo htmlspecialchars($row['kelas']); ?></span></td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded-pill"><?php echo htmlspecialchars($row['jurusan']); ?></span></td>
                                <td><span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill"><i class='bx bx-check-circle me-1'></i>Aktif</span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-4"><i class='bx bx-info-circle me-2'></i>Belum ada data <?php echo strtolower(LBL_SISWA); ?> yang terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($role == 'guru'): ?>
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="dashboard-panel">
                    <h5 class="panel-title"><i class='bx bx-book-open text-primary fs-4'></i> <?php echo LBL_MAPEL; ?> Diampu</h5>
                    <div class="list-group list-group-flush mt-3">
                        <?php 
                        $my_mapel = $conn->query("SELECT * FROM mapel WHERE nip_guru = '$username'");
                        if ($my_mapel->num_rows > 0):
                            while($m = $my_mapel->fetch_assoc()): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 px-0 py-3 border-bottom">
                                    <div>
                                        <h6 class="mb-1 fw-bold text-dark"><?php echo $m['nama_mapel']; ?></h6>
                                        <small class="text-muted">Jurusan: <?php echo $m['jurusan']; ?></small>
                                    </div>
                                    <a href="nilai.php?mapel_id=<?php echo $m['id']; ?>" class="btn btn-sm btn-light border rounded-pill">Input Nilai</a>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <p class="text-muted py-3">Anda belum mengampu <?php echo strtolower(LBL_MAPEL); ?> apapun.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-panel">
                    <h5 class="panel-title"><i class='bx bx-group text-success fs-4'></i> <?php echo LBL_SISWA; ?> Perwalian</h5>
                    <?php if ($wali_kelas): ?>
                        <div class="table-responsive mt-3" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $sw = $conn->query("SELECT * FROM siswa WHERE kelas = '".$wali_kelas['nama_kelas']."' ORDER BY nama ASC");
                                    while($s = $sw->fetch_assoc()): ?>
                                        <tr>
                                            <td><small class="text-muted">#<?php echo $s['nis']; ?></small></td>
                                            <td class="fw-medium small"><?php echo $s['nama']; ?></td>
                                            <td><a href="nilai.php?action=view_siswa&nis=<?php echo $s['nis']; ?>" class="btn btn-sm btn-link p-0 text-info"><i class='bx bx-show'></i></a></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted py-3">Anda bukan Wali <?php echo LBL_KELAS; ?> dari <?php echo strtolower(LBL_KELAS); ?> manapun.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php elseif ($role == 'siswa'): ?>
        <div class="row g-4 mb-4">
            <div class="col-md-7">
                <div class="dashboard-panel">
                    <h5 class="panel-title"><i class='bx bx-book-content text-primary fs-4'></i> <?php echo LBL_MAPEL; ?> <?php echo LBL_KELAS; ?></h5>
                    <div class="row g-3 mt-1">
                        <?php 
                        if ($kelas_siswa):
                            $k_id_res = $conn->query("SELECT id FROM kelas WHERE nama_kelas = '$kelas_siswa'");
                            if ($k_id_res->num_rows > 0):
                                $kid = $k_id_res->fetch_assoc()['id'];
                                $mps = $conn->query("SELECT m.nama_mapel, g.nama as nama_guru FROM kelas_mapel km JOIN mapel m ON km.id_mapel = m.id JOIN guru g ON m.nip_guru = g.nip WHERE km.id_kelas = $kid");
                                while($m = $mps->fetch_assoc()): ?>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-4 border h-100">
                                            <h6 class="fw-bold mb-1 text-dark"><?php echo $m['nama_mapel']; ?></h6>
                                            <small class="text-muted d-block">Guru: <?php echo $m['nama_guru']; ?></small>
                                        </div>
                                    </div>
                                <?php endwhile;
                            endif;
                        else: ?>
                            <div class="col-12"><p class="text-muted">Data <?php echo strtolower(LBL_KELAS); ?> belum tersedia.</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="dashboard-panel">
                    <h5 class="panel-title"><i class='bx bx-stats text-warning fs-4'></i> Nilai Terbaru</h5>
                    <div class="list-group list-group-flush">
                        <?php 
                        $my_grades = $conn->query("SELECT n.*, m.nama_mapel FROM nilai n JOIN mapel m ON n.id_mapel = m.id WHERE n.nis_siswa = '$username' ORDER BY n.id DESC LIMIT 5");
                        if ($my_grades->num_rows > 0):
                            while($ng = $my_grades->fetch_assoc()): 
                                $avg = ($ng['tugas1'] + $ng['tugas2'] + $ng['tugas3'] + $ng['tugas4'] + $ng['uts'] + $ng['uas']) / 6;
                            ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 px-0 py-2 border-bottom">
                                    <span class="small fw-medium"><?php echo $ng['nama_mapel']; ?></span>
                                    <span class="badge <?php echo $avg >= 75 ? 'bg-success' : 'bg-danger'; ?> bg-opacity-10 <?php echo $avg >= 75 ? 'text-success' : 'text-danger'; ?> rounded-pill"><?php echo number_format($avg, 1); ?></span>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <p class="text-muted py-3 small">Belum ada nilai yang diinputkan.</p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="nilai.php" class="btn btn-sm btn-outline-primary rounded-pill w-100">Lihat Raport Lengkap</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php if ($role == 'admin'): ?>
<!-- Chart.js for Analytics (Admin Only) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var ctx = document.getElementById('jurusanChart').getContext('2d');
    var chartLabels = <?php echo json_encode($chart_labels); ?>;
    var chartData = <?php echo json_encode($chart_counts); ?>;
    
    if(chartData.length === 0) {
        chartLabels = ['RPL', 'TKJ', 'Multimedia', 'Akuntansi'];
        chartData = [0, 0, 0, 0];
    }

    var myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Jumlah <?php echo LBL_SISWA; ?>',
                data: chartData,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 6,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 13 },
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 13 },
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [4, 4], color: '#e2e8f0' },
                    ticks: { precision: 0, font: { family: "'Plus Jakarta Sans', sans-serif" } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Plus Jakarta Sans', sans-serif", weight: '500' } }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>
