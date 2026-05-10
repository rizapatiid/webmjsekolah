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

$action = $_GET['action'] ?? 'list';

if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $conn->query("DELETE FROM nilai WHERE id=$id");
    $_SESSION['success'] = 'Hasil Nilai Berhasil Dihapus!';
    header("Location: nilai.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis_siswa = $conn->real_escape_string($_POST['nis_siswa']);
    $id_mapel = (int) $_POST['id_mapel'];
    $tugas1 = (float) $_POST['tugas1'];
    $tugas2 = (float) $_POST['tugas2'];
    $tugas3 = (float) $_POST['tugas3'];
    $tugas4 = (float) $_POST['tugas4'];
    $uts = (float) $_POST['uts'];
    $uas = (float) $_POST['uas'];

    // Smart Upsert Check: Always check if record exists regardless of action
    $check = $conn->query("SELECT id FROM nilai WHERE nis_siswa = '$nis_siswa' AND id_mapel = $id_mapel");
    
    if ($check->num_rows > 0) {
        // Record exists -> UPDATE
        $existing_id = $check->fetch_assoc()['id'];
        $conn->query("UPDATE nilai SET tugas1=$tugas1, tugas2=$tugas2, tugas3=$tugas3, tugas4=$tugas4, uts=$uts, uas=$uas WHERE id=$existing_id");
        $_SESSION['success'] = 'Data Nilai Berhasil Diperbarui!';
    } else {
        // Record doesn't exist -> INSERT
        $conn->query("INSERT INTO nilai (nis_siswa, id_mapel, tugas1, tugas2, tugas3, tugas4, uts, uas) 
                      VALUES ('$nis_siswa', $id_mapel, $tugas1, $tugas2, $tugas3, $tugas4, $uts, $uas)");
        $_SESSION['success'] = 'Hasil Nilai Berhasil Ditambahkan!';
    }
    header("Location: nilai.php");
    exit;
}

$page_title = 'Hasil Nilai';
include '../layouts/header.php';

// Load Pengaturan Sekolah for filters
$pengaturan_query = $conn->query("SELECT * FROM pengaturan_sekolah WHERE id=1");
// Load Pengaturan Sekolah for filters
$pengaturan_query = $conn->query("SELECT * FROM pengaturan_sekolah WHERE id=1");
$pengaturan = $pengaturan_query->fetch_assoc();
$daftar_jurusan = array_map('trim', explode(',', $pengaturan['daftar_jurusan'] ?? ''));
$daftar_tahun = array_map('trim', explode(',', $pengaturan['daftar_tahun_ajaran'] ?? $pengaturan['tahun_ajaran']));

// Role-based filter logic
$role = $_SESSION['user']['role'];
$username = $_SESSION['user']['username'];

// Get filter values from GET
$f_tahun = $_GET['tahun'] ?? '';
$f_semester = $_GET['semester'] ?? '';
$f_jurusan = $_GET['jurusan'] ?? '';
$f_kelas = $_GET['kelas'] ?? '';
$f_mapel = $_GET['mapel'] ?? '';

if ($role == 'siswa') {
    $f_nis = $username;
    $filters_selected = true; // Always show for student
} elseif ($role == 'guru') {
    // For Guru, we show the list if any filter is clicked or just show what they teach
    $filters_selected = ($f_tahun || $f_semester || $f_jurusan || $f_kelas || $f_mapel);
    if (!isset($_GET['tahun'])) $filters_selected = false; // Initial state: wait for search
} else {
    $filters_selected = ($f_tahun || $f_semester || $f_jurusan || $f_kelas || $f_mapel);
    if (!isset($_GET['tahun'])) $filters_selected = false; // Initial state: wait for search
}
?>

<style>
    .filter-card {
        background: #ffffff;
        border-radius: 1.25rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: all 0.3s ease;
    }

    .filter-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        color: #64748b;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }

    .filter-label i {
        font-size: 1rem;
        margin-right: 0.4rem;
        color: #0f172a;
    }

    .form-select-custom {
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
        color: #1e293b;
        background-color: #f8fafc;
        transition: all 0.2s;
    }

    .form-select-custom:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        background-color: #fff;
    }

    .btn-filter {
        height: 45px;
        border-radius: 0.75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s;
        background-color: #0f172a;
        border: none;
        color: white;
    }
    .btn-filter:hover {
        background-color: #3b82f6;
        transform: translateY(-2px);
        color: white;
    }

    .empty-filter-state {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 2px dashed #e2e8f0;
        border-radius: 2rem;
    }

    @media (max-width: 768px) {
        .filter-card {
            padding: 1.5rem !important;
        }
    }
</style>

<div class="container-fluid bg-transparent p-0">
    <?php if ($action == 'list'): ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bxs-bar-chart-alt-2 text-primary me-2'></i> Manajemen Nilai</h3>
                <p class="text-muted mb-0 small">Rekapitulasi nilai tugas, UTS, UAS, dan nilai akhir siswa.</p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($role == 'siswa'): ?>
                    <a href="cetak_transkrip.php?nis=<?php echo $username; ?>" target="_blank"
                        class="btn btn-outline-dark fw-bold rounded-3 px-4 shadow-sm d-flex align-items-center justify-content-center"
                        style="height: 45px; border-width: 2px;">
                        <i class='bx bx-printer me-2 fs-5'></i> Cetak Transkrip Saya
                    </a>
                <?php elseif ($role == 'guru'): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-dark fw-bold rounded-3 px-4 shadow-sm d-flex align-items-center justify-content-center dropdown-toggle" 
                                type="button" data-bs-toggle="dropdown" aria-expanded="false" style="height: 45px; border-width: 2px;">
                            <i class='bx bx-printer me-2 fs-5'></i> Cetak Laporan
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 overflow-hidden">
                            <li><a class="dropdown-item py-2 px-3 fw-medium" href="#" onclick="generateReport('mapel')"><i class='bx bx-book me-2'></i> Rekap Nilai Mapel</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-dark fw-bold rounded-3 px-4 shadow-sm d-flex align-items-center justify-content-center dropdown-toggle" 
                                type="button" data-bs-toggle="dropdown" aria-expanded="false" style="height: 45px; border-width: 2px;">
                            <i class='bx bx-printer me-2 fs-5'></i> Transkrip Nilai
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 overflow-hidden">
                            <li><a class="dropdown-item py-2 px-3 fw-medium" href="#" onclick="generateReport('kelas')"><i class='bx bx-building-house me-2'></i> Nilai Kelas</a></li>
                            <li><a class="dropdown-item py-2 px-3 fw-medium" href="#" onclick="generateReport('mapel')"><i class='bx bx-book me-2'></i> Nilai Mapel</a></li>
                            <li><hr class="dropdown-divider m-0 opacity-50"></li>
                            <li><a class="dropdown-item py-2 px-3 fw-medium" href="#" onclick="generateReport('siswa')"><i class='bx bx-user me-2'></i> Nilai Siswa</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if ($role != 'siswa'): ?>
                <a href="nilai.php?action=add"
                    class="btn fw-bold rounded-3 px-4 shadow-sm text-white d-flex align-items-center justify-content-center"
                    style="background-color: #0f172a; border: none; transition: all 0.3s ease; height: 45px;"
                    onmouseover="this.style.backgroundColor='#3b82f6'; this.style.transform='translateY(-2px)'"
                    onmouseout="this.style.backgroundColor='#0f172a'; this.style.transform='translateY(0)'">
                    <i class='bx bx-plus-circle me-2 fs-5'></i> Input Nilai
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($role != 'siswa'): ?>
        <!-- Filter Panel -->
        <div class="filter-card p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-dark bg-opacity-10 p-2 rounded-3 me-3">
                    <i class='bx bx-filter-alt text-dark fs-4'></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Penyaringan Data</h6>
                    <small class="text-muted">Tentukan kriteria untuk menampilkan nilai siswa</small>
                </div>
            </div>

            <form method="GET" action="nilai.php" class="row g-3">
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="filter-label"><i class='bx bx-calendar'></i> Thn Ajaran</label>
                    <select name="tahun" class="form-select form-select-custom">
                        <option value="">-- Semua --</option>
                        <?php foreach ($daftar_tahun as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo ($f_tahun ?: $pengaturan['tahun_ajaran']) == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="filter-label"><i class='bx bx-time-five'></i> Semester</label>
                    <select name="semester" class="form-select form-select-custom">
                        <option value="">-- Semua --</option>
                        <option value="Ganjil" <?php echo ($f_semester ?: $pengaturan['semester']) == 'Ganjil' ? 'selected' : ''; ?>>Ganjil</option>
                        <option value="Genap" <?php echo ($f_semester ?: $pengaturan['semester']) == 'Genap' ? 'selected' : ''; ?>>Genap</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="filter-label"><i class='bx bx-git-branch'></i> Jurusan</label>
                    <select name="jurusan" class="form-select form-select-custom">
                        <option value="">-- Semua --</option>
                        <?php foreach ($daftar_jurusan as $j): ?>
                            <option value="<?php echo $j; ?>" <?php echo $f_jurusan == $j ? 'selected' : ''; ?>><?php echo $j; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="filter-label"><i class='bx bx-building-house'></i> Kelas</label>
                    <select name="kelas" class="form-select form-select-custom">
                        <option value="">-- Semua --</option>
                        <?php
                        $kelas_q = "SELECT DISTINCT k.nama_kelas FROM kelas k";
                        if ($role == 'guru') {
                            $kelas_q .= " LEFT JOIN kelas_mapel km ON k.id = km.id_kelas
                                         LEFT JOIN mapel m ON km.id_mapel = m.id
                                         WHERE k.wali_guru = '$username' OR m.nip_guru = '$username'";
                        }
                        $kelas_q .= " ORDER BY k.nama_kelas ASC";
                        $kelas_res = $conn->query($kelas_q);
                        while ($k = $kelas_res->fetch_assoc()): ?>
                            <option value="<?php echo $k['nama_kelas']; ?>" <?php echo $f_kelas == $k['nama_kelas'] ? 'selected' : ''; ?>><?php echo $k['nama_kelas']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="filter-label"><i class='bx bx-book'></i> MAPEL</label>
                    <select name="mapel" class="form-select form-select-custom">
                        <option value="">-- Semua --</option>
                        <?php
                        $mapel_q = "SELECT id, nama_mapel FROM mapel";
                        if ($role == 'guru') {
                            $mapel_q .= " WHERE nip_guru = '$username'";
                        }
                        $mapel_q .= " ORDER BY nama_mapel ASC";
                        $mapel_res = $conn->query($mapel_q);
                        while ($m = $mapel_res->fetch_assoc()): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo $f_mapel == $m['id'] ? 'selected' : ''; ?>>
                                <?php echo $m['nama_mapel']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-filter w-100 shadow-sm">
                        <i class='bx bx-search-alt'></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($filters_selected && $role != 'siswa'): 
            // Calculate Statistics for the filtered data
            $stats_q = "SELECT AVG(( (tugas1+tugas2+tugas3+tugas4)/4 * 0.3) + (uts * 0.3) + (uas * 0.4)) as avg_akhir,
                               COUNT(*) as total_siswa
                        FROM nilai n 
                        JOIN siswa s ON n.nis_siswa = s.nis 
                        WHERE 1=1";
            if ($f_tahun) $stats_q .= " AND s.tahun_ajaran = '$f_tahun'";
            if ($f_semester) $stats_q .= " AND s.semester = '$f_semester'";
            if ($f_jurusan) $stats_q .= " AND s.jurusan = '$f_jurusan'";
            if ($f_kelas) $stats_q .= " AND s.kelas = '$f_kelas'";
            if ($f_mapel) $stats_q .= " AND n.id_mapel = " . (int)$f_mapel;
            
            $stats_res = $conn->query($stats_q);
            $stats = $stats_res->fetch_assoc();
        ?>
            <!-- Stats Summary Bar -->
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="bg-white p-3 rounded-4 shadow-sm border border-start border-4 border-primary">
                        <small class="text-muted fw-bold d-block mb-1">RATA-RATA NILAI</small>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo number_format($stats['avg_akhir'] ?? 0, 1); ?></h4>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="bg-white p-3 rounded-4 shadow-sm border border-start border-4 border-success">
                        <small class="text-muted fw-bold d-block mb-1">TOTAL DATA</small>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $stats['total_siswa']; ?> Siswa</h4>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($filters_selected): ?>

            <!-- Header List -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h3 class="fw-bold mb-1 text-dark"><i class='bx bxs-bar-chart-alt-2 text-primary me-2'></i> 
                        <?php echo ($role == 'siswa') ? 'Raport Akademik Saya' : 'Hasil Nilai: ' . htmlspecialchars($f_kelas); ?>
                    </h3>
                    <p class="text-muted mb-0 small">
                        <?php echo ($role == 'siswa') ? 'Berikut adalah rincian capaian nilai akademik Anda.' : 'Menampilkan rekapitulasi nilai untuk filter yang dipilih.'; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$filters_selected): ?>
            <div class="empty-filter-state p-5 text-center shadow-sm">
                <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4 shadow-sm"
                    style="width: 100px; height: 100px;">
                    <i class='bx bx-spreadsheet fs-1 text-primary'></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Siap Menampilkan Data Nilai?</h4>
                <p class="text-muted mx-auto" style="max-width: 500px;">Gunakan panel filter di atas untuk memilih kriteria
                    akademik yang spesifik. Pilih Tahun Ajaran, Semester, Jurusan, Kelas, dan Mata Pelajaran untuk mulai
                    mengelola nilai.</p>
                <div class="d-flex justify-content-center gap-2 mt-4">
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">#Akademik</span>
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">#HasilBelajar</span>
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">#SIAKAD</span>
                </div>
            </div>
        <?php else: ?>
            <!-- Table Panel -->
            <div class="bg-white p-4 rounded-4 border shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="fw-semibold pb-3">Siswa & Mapel</th>
                                <th class="fw-semibold pb-3 text-center">T1</th>
                                <th class="fw-semibold pb-3 text-center">T2</th>
                                <th class="fw-semibold pb-3 text-center">T3</th>
                                <th class="fw-semibold pb-3 text-center">T4</th>
                                <th class="fw-semibold pb-3 text-center">UTS</th>
                                <th class="fw-semibold pb-3 text-center">UAS</th>
                                <th class="fw-semibold pb-3 text-center">Akhir</th>
                                <?php if ($role != 'siswa'): ?>
                                <th class="fw-semibold pb-3 text-end">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($role == 'siswa') {
                                $query = "SELECT n.*, s.nama as nama_siswa, s.foto as foto_siswa, m.nama_mapel 
                                          FROM nilai n 
                                          JOIN siswa s ON n.nis_siswa = s.nis 
                                          JOIN mapel m ON n.id_mapel = m.id
                                          WHERE n.nis_siswa = '$username'";
                            } else {
                                $query = "SELECT n.*, s.nama as nama_siswa, s.foto as foto_siswa, m.nama_mapel 
                                      FROM nilai n 
                                      JOIN siswa s ON n.nis_siswa = s.nis 
                                      JOIN mapel m ON n.id_mapel = m.id
                                      WHERE 1=1";
                                
                                if ($f_tahun) $query .= " AND s.tahun_ajaran = '$f_tahun'";
                                if ($f_semester) $query .= " AND s.semester = '$f_semester'";
                                if ($f_jurusan) $query .= " AND s.jurusan = '$f_jurusan'";
                                if ($f_kelas) $query .= " AND s.kelas = '$f_kelas'";
                                if ($f_mapel) $query .= " AND n.id_mapel = " . (int)$f_mapel;
                                
                                if ($role == 'guru') {
                                    $query .= " AND (n.id_mapel IN (SELECT id FROM mapel WHERE nip_guru = '$username')
                                                OR s.kelas IN (SELECT nama_kelas FROM kelas WHERE wali_guru = '$username'))";
                                }
                            }
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                                $avg_tugas = ($row['tugas1'] + $row['tugas2'] + $row['tugas3'] + $row['tugas4']) / 4;
                                $akhir = ($avg_tugas * 0.3) + ($row['uts'] * 0.3) + ($row['uas'] * 0.4);

                                // Color based on grade
                                $grade_color = 'primary';
                                if ($akhir >= 80)
                                    $grade_color = 'success';
                                else if ($akhir < 70)
                                    $grade_color = 'danger';
                                else if ($akhir < 80)
                                    $grade_color = 'warning';
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($row['foto_siswa']): ?>
                                                <img src="../<?php echo $row['foto_siswa']; ?>" width="35" height="35"
                                                    class="rounded-circle me-2" style="object-fit:cover;">
                                            <?php else: ?>
                                                <div class="bg-light text-muted rounded-circle d-flex align-items-center justify-content-center me-2"
                                                    style="width:35px;height:35px; font-size: 0.75rem;">
                                                    <i class='bx bx-user'></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <span class="fw-bold text-dark d-block mb-0"
                                                    style="font-size: 0.9rem;"><?php echo $row['nama_siswa']; ?></span>
                                                <small class="text-primary fw-medium"
                                                    style="font-size: 0.75rem;"><?php echo $row['nama_mapel']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium text-dark small"><?php echo $row['tugas1']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium text-dark small"><?php echo $row['tugas2']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium text-dark small"><?php echo $row['tugas3']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium text-dark small"><?php echo $row['tugas4']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium text-dark small"><?php echo $row['uts']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium text-dark small"><?php echo $row['uas']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div
                                            class="badge bg-<?php echo $grade_color; ?> bg-opacity-10 text-<?php echo $grade_color; ?> border border-<?php echo $grade_color; ?> px-2 py-1 rounded-2">
                                            <span class="fw-bold"><?php echo number_format($akhir, 1); ?></span>
                                        </div>
                                    </td>
                                    <?php if ($role != 'siswa'): ?>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-3 align-items-center">
                                            <a href="nilai.php?action=edit&id=<?php echo $row['id']; ?>"
                                                class="btn btn-link text-warning p-0 text-decoration-none" title="Edit Data">
                                                <i class='bx bx-edit-alt fs-4'></i>
                                            </a>
                                            <a href="nilai.php?action=delete&id=<?php echo $row['id']; ?>"
                                                class="btn btn-link text-danger p-0 text-decoration-none btn-delete"
                                                title="Hapus Data">
                                                <i class='bx bx-trash fs-4'></i>
                                            </a>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($action == 'add' || $action == 'edit'):
        if ($role == 'siswa') {
            echo "<script>window.location='nilai.php';</script>";
            exit;
        }
        $row = null;
        if ($action == 'edit' && isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $result = $conn->query("SELECT * FROM nilai WHERE id=$id");
            $row = $result->fetch_assoc();
        } elseif ($action == 'add' && isset($_GET['mapel']) && isset($_GET['nis'])) {
            // Smart Auto-load: If student and mapel are selected, check for existing data
            $mid = (int)$_GET['mapel'];
            $nis = $conn->real_escape_string($_GET['nis']);
            $existing = $conn->query("SELECT * FROM nilai WHERE nis_siswa = '$nis' AND id_mapel = $mid");
            if ($existing->num_rows > 0) {
                $row = $existing->fetch_assoc();
            }
        }
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bx-edit text-primary me-2'></i>
                    <?php echo $action == 'add' ? 'Input Nilai Baru' : 'Perbarui Capaian Nilai'; ?></h3>
                <p class="text-muted mb-0 small">Gunakan formulir di bawah untuk mencatat hasil belajar siswa.</p>
            </div>
            <a href="nilai.php" class="btn btn-outline-secondary fw-medium rounded-3 px-4 shadow-sm">
                <i class='bx bx-arrow-back me-1'></i> Kembali
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <form method="POST">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <?php endif; ?>

                    <div class="bg-white p-4 p-lg-5 rounded-4 border shadow-sm mb-4">
                        <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bxs-user-check text-primary me-2'></i>
                            Identitas & Mata Pelajaran</h5>
                        <div class="row g-4 mb-5">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted small">Mata Pelajaran</label>
                                <select name="id_mapel" id="form-mapel" class="form-select form-select-lg border-2 shadow-xs" required onchange="updateFormChain('mapel')">
                                    <option value="">-- Pilih Mapel --</option>
                                    <?php
                                    $m_id = $_GET['mapel'] ?? ($row['id_mapel'] ?? '');
                                    $m_q = "SELECT * FROM mapel";
                                    if ($role == 'guru') {
                                        $m_q .= " WHERE nip_guru = '$username'";
                                    }
                                    $m_q .= " ORDER BY nama_mapel ASC";
                                    $mapel_result = $conn->query($m_q);
                                    while ($m = $mapel_result->fetch_assoc()): ?>
                                        <option value="<?php echo $m['id']; ?>" <?php echo $m_id == $m['id'] ? 'selected' : ''; ?>>
                                            <?php echo $m['nama_mapel']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted small">Pilih Kelas</label>
                                <select id="form-kelas" class="form-select form-select-lg border-2 shadow-xs" required onchange="updateFormChain('kelas')" <?php echo !$m_id ? 'disabled' : ''; ?>>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php
                                    $k_nama = $_GET['kelas'] ?? '';
                                    if ($m_id) {
                                        $k_q = "SELECT k.nama_kelas FROM kelas k JOIN kelas_mapel km ON k.id = km.id_kelas WHERE km.id_mapel = $m_id ORDER BY k.nama_kelas ASC";
                                        $kelas_result = $conn->query($k_q);
                                        while ($k = $kelas_result->fetch_assoc()): ?>
                                            <option value="<?php echo $k['nama_kelas']; ?>" <?php echo $k_nama == $k['nama_kelas'] ? 'selected' : ''; ?>>
                                                <?php echo $k['nama_kelas']; ?>
                                            </option>
                                        <?php endwhile;
                                    } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted small">Pilih Siswa</label>
                                <select name="nis_siswa" id="form-siswa" class="form-select form-select-lg border-2 shadow-xs" required onchange="updateFormChain('siswa')" <?php echo !$k_nama ? 'disabled' : ''; ?>>
                                    <option value="">-- Pilih Siswa --</option>
                                    <?php
                                    $s_nis_active = $_GET['nis'] ?? ($row['nis_siswa'] ?? '');
                                    if ($k_nama) {
                                        $s_q = "SELECT nis, nama FROM siswa WHERE kelas = '$k_nama' ORDER BY nama ASC";
                                        $siswa_result = $conn->query($s_q);
                                        while ($s = $siswa_result->fetch_assoc()): ?>
                                            <option value="<?php echo $s['nis']; ?>" <?php echo $s_nis_active == $s['nis'] ? 'selected' : ''; ?>>
                                                <?php echo $s['nama']; ?> (<?php echo $s['nis']; ?>)
                                            </option>
                                        <?php endwhile;
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <script>
                        function updateFormChain(step) {
                            const mapel = document.getElementById('form-mapel').value;
                            const kelas = document.getElementById('form-kelas').value;
                            const nis = document.getElementById('form-siswa').value;
                            let url = 'nilai.php?action=<?php echo $action; ?>';
                            if (mapel) url += '&mapel=' + mapel;
                            if (step === 'mapel') {
                                window.location.href = url;
                            } else if (step === 'kelas') {
                                if (kelas) url += '&kelas=' + encodeURIComponent(kelas);
                                window.location.href = url;
                            } else if (step === 'siswa') {
                                if (kelas) url += '&kelas=' + encodeURIComponent(kelas);
                                if (nis) url += '&nis=' + encodeURIComponent(nis);
                                window.location.href = url;
                            }
                        }
                        </script>

                        <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-list-check text-success me-2'></i>
                            Entri Komponen Nilai</h5>
                        
                        <div class="p-4 bg-light rounded-4 border border-light-subtle mb-4">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Tugas 1</label>
                                    <input type="number" step="0.01" name="tugas1" class="form-control grade-input"
                                        value="<?php echo $row['tugas1'] ?? 0; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Tugas 2</label>
                                    <input type="number" step="0.01" name="tugas2" class="form-control grade-input"
                                        value="<?php echo $row['tugas2'] ?? 0; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Tugas 3</label>
                                    <input type="number" step="0.01" name="tugas3" class="form-control grade-input"
                                        value="<?php echo $row['tugas3'] ?? 0; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Tugas 4</label>
                                    <input type="number" step="0.01" name="tugas4" class="form-control grade-input"
                                        value="<?php echo $row['tugas4'] ?? 0; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <div class="p-4 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 shadow-xs">
                                    <label class="form-label fw-bold text-primary"><i class='bx bx-edit-alt me-1'></i> Nilai UTS</label>
                                    <input type="number" step="0.01" name="uts"
                                        class="form-control form-control-lg border-primary border-opacity-25 grade-input"
                                        value="<?php echo $row['uts'] ?? 0; ?>" required>
                                    <small class="text-primary opacity-75 mt-1 d-block" style="font-size: 0.7rem;">Bobot Penilaian: 30%</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-4 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-25 shadow-xs">
                                    <label class="form-label fw-bold text-info"><i class='bx bx-edit-alt me-1'></i> Nilai UAS</label>
                                    <input type="number" step="0.01" name="uas"
                                        class="form-control form-control-lg border-info border-opacity-25 grade-input"
                                        value="<?php echo $row['uas'] ?? 0; ?>" required>
                                    <small class="text-info opacity-75 mt-1 d-block" style="font-size: 0.7rem;">Bobot Penilaian: 40%</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-4 border-top">
                            <a href="nilai.php" class="btn btn-light border px-4 py-2 fw-medium text-muted">Batal</a>
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm transition-all hover-scale">
                                <i class='bx bx-save me-1'></i> Simpan Hasil Nilai
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <!-- Live Calculation Card -->
                <div class="bg-white rounded-4 shadow-sm border p-4 mb-4 sticky-top" style="top: 2rem; z-index: 10;">
                    <h6 class="fw-bold text-dark mb-4 d-flex align-items-center">
                        <i class='bx bx-calculator text-primary me-2 fs-4'></i> Kalkulasi Nilai Akhir
                    </h6>
                    
                    <div class="text-center py-4 mb-4 bg-light rounded-4 border position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100 h-100 opacity-05" style="background-image: radial-gradient(#3b82f6 1px, transparent 1px); background-size: 20px 20px;"></div>
                        <h1 class="fw-black text-dark mb-0 position-relative" id="liveAkhir" style="font-size: 3.5rem;">0.0</h1>
                        <div class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-1 rounded-pill fw-bold mb-2 position-relative" id="liveGrade">GRADE -</div>
                        <br>
                        <small class="text-muted text-uppercase fw-bold position-relative" style="letter-spacing: 0.1em; font-size: 0.7rem;">Prediksi Nilai Akhir</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                        <small class="text-muted fw-bold small">Status:</small>
                        <span id="liveStatus" class="badge bg-secondary px-3 py-2 rounded-pill fw-bold">-</span>
                    </div>

                    <div class="mt-4 pt-4 border-top">
                        <h6 class="fw-bold text-dark small mb-3">Distribusi Kontribusi:</h6>
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted extra-small fw-bold">RATA-RATA TUGAS (30%)</small>
                                    <small class="fw-bold text-dark" id="valTugas">0</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div id="barTugas" class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted extra-small fw-bold">NILAI UTS (30%)</small>
                                    <small class="fw-bold text-dark" id="valUTS">0</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div id="barUTS" class="progress-bar bg-info rounded-pill" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted extra-small fw-bold">NILAI UAS (40%)</small>
                                    <small class="fw-bold text-dark" id="valUAS">0</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div id="barUAS" class="progress-bar bg-dark rounded-pill" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-3 bg-warning bg-opacity-10 border border-warning border-opacity-25">
                        <p class="mb-0 small text-warning-emphasis fw-medium">
                            <i class='bx bx-info-circle me-1'></i> Nilai akan disimpan permanen setelah Anda menekan tombol simpan.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .extra-small { font-size: 0.65rem; }
        .fw-black { font-weight: 900; }
        .opacity-05 { opacity: 0.05; }
        
        @keyframes pulse-blue {
            0% { transform: scale(1); text-shadow: 0 0 0 rgba(59, 130, 246, 0); }
            50% { transform: scale(1.05); text-shadow: 0 0 15px rgba(59, 130, 246, 0.3); }
            100% { transform: scale(1); text-shadow: 0 0 0 rgba(59, 130, 246, 0); }
        }
        .pulse-active {
            animation: pulse-blue 0.4s ease-out;
        }
        </style>

        <script>
        (function() {
            function calculateLive() {
                const getVal = (name) => {
                    const el = document.querySelector(`input[name="${name}"]`);
                    if (!el) return 0;
                    // Handle comma as decimal separator
                    let val = el.value.replace(',', '.');
                    return parseFloat(val) || 0;
                };

                let t1 = getVal('tugas1');
                let t2 = getVal('tugas2');
                let t3 = getVal('tugas3');
                let t4 = getVal('tugas4');
                let uts = getVal('uts');
                let uas = getVal('uas');

                // Ensure max 100
                t1 = Math.min(100, t1); t2 = Math.min(100, t2); t3 = Math.min(100, t3); t4 = Math.min(100, t4);
                uts = Math.min(100, uts); uas = Math.min(100, uas);

                let avgTugas = (t1 + t2 + t3 + t4) / 4;
                let akhir = (avgTugas * 0.3) + (uts * 0.3) + (uas * 0.4);
                
                const scoreEl = document.getElementById('liveAkhir');
                if (scoreEl) {
                    let oldVal = scoreEl.innerText;
                    if (oldVal !== akhir.toFixed(1)) {
                        scoreEl.classList.add('pulse-active');
                        setTimeout(() => scoreEl.classList.remove('pulse-active'), 400);
                    }
                    scoreEl.innerText = akhir.toFixed(1);
                }

                const updateText = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.innerText = val.toFixed(1);
                };

                updateText('valTugas', avgTugas);
                updateText('valUTS', uts);
                updateText('valUAS', uas);

                const updateBar = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.style.width = val + '%';
                };

                updateBar('barTugas', avgTugas);
                updateBar('barUTS', uts);
                updateBar('barUAS', uas);

                // Determine Grade Letter
                let grade = '-';
                let gradeColor = 'primary';
                if (akhir >= 85) { grade = 'A'; gradeColor = 'success'; }
                else if (akhir >= 75) { grade = 'B'; gradeColor = 'info'; }
                else if (akhir >= 65) { grade = 'C'; gradeColor = 'warning'; }
                else if (akhir >= 50) { grade = 'D'; gradeColor = 'danger'; }
                else if (akhir > 0) { grade = 'E'; gradeColor = 'danger'; }
                
                const gradeEl = document.getElementById('liveGrade');
                if (gradeEl) {
                    gradeEl.innerText = 'GRADE ' + grade;
                    gradeEl.className = `badge bg-${gradeColor} bg-opacity-10 text-${gradeColor} border border-${gradeColor} px-3 py-1 rounded-pill fw-bold mb-2 position-relative`;
                }

                // Update Status
                const statusEl = document.getElementById('liveStatus');
                if (statusEl) {
                    if (akhir >= 75) {
                        statusEl.innerText = 'LULUS';
                        statusEl.className = 'badge bg-success px-3 py-2 rounded-pill fw-bold';
                        scoreEl.className = 'fw-black text-success mb-0 position-relative';
                    } else if (akhir >= 60) {
                        statusEl.innerText = 'REMEDIAL';
                        statusEl.className = 'badge bg-warning px-3 py-2 rounded-pill fw-bold';
                        scoreEl.className = 'fw-black text-warning mb-0 position-relative';
                    } else {
                        statusEl.innerText = 'TIDAK LULUS';
                        statusEl.className = 'badge bg-danger px-3 py-2 rounded-pill fw-bold';
                        scoreEl.className = 'fw-black text-danger mb-0 position-relative';
                    }
                }
            }

            // Attach listeners
            document.addEventListener('DOMContentLoaded', function() {
                const inputs = document.querySelectorAll('.grade-input');
                inputs.forEach(input => {
                    input.addEventListener('input', calculateLive);
                    input.addEventListener('keyup', calculateLive);
                    input.addEventListener('change', calculateLive);
                });
                calculateLive(); // Initial run
            });

            // Fallback in case DOMContentLoaded already fired
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                calculateLive();
                const inputs = document.querySelectorAll('.grade-input');
                inputs.forEach(input => {
                    input.addEventListener('input', calculateLive);
                });
            }
        })();
        </script>
    <?php endif; ?>
</div>

        <script>
        function generateReport(type) {
            if(type === 'siswa') {
                <?php if ($role == 'siswa'): ?>
                    window.open('cetak_transkrip.php?nis=<?php echo $username; ?>', '_blank');
                <?php else: ?>
                    Swal.fire({
                        title: 'Pilih Siswa',
                        html: `
                            <select id="swal-nis" class="form-select mt-3">
                                <option value="">-- Pilih Siswa --</option>
                                <?php 
                                $s_list = $conn->query("SELECT nis, nama FROM siswa ORDER BY nama ASC");
                                while($sl = $s_list->fetch_assoc()): ?>
                                    <option value="<?php echo $sl['nis']; ?>"><?php echo $sl['nama']; ?> (<?php echo $sl['nis']; ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Cetak Transkrip',
                        confirmButtonColor: '#0f172a',
                        preConfirm: () => {
                            const nis = document.getElementById('swal-nis').value;
                            if (!nis) {
                                Swal.showValidationMessage('Silakan pilih siswa terlebih dahulu');
                            }
                            return nis;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open('cetak_transkrip.php?nis=' + result.value, '_blank');
                        }
                    });
                <?php endif; ?>
            } else if(type === 'kelas') {
                Swal.fire({
                    title: 'Cetak Rekap Nilai Kelas',
                    html: `
                        <div class="text-start">
                            <label class="small fw-bold text-muted">Tahun Ajaran</label>
                            <select id="swal-tahun" class="form-select mb-2">
                                <?php foreach($daftar_tahun as $t): ?>
                                    <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="small fw-bold text-muted">Semester</label>
                            <select id="swal-semester" class="form-select mb-2">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                            <label class="small fw-bold text-muted">Jurusan</label>
                            <select id="swal-jurusan" class="form-select mb-2">
                                <?php foreach($daftar_jurusan as $j): ?>
                                    <option value="<?php echo $j; ?>"><?php echo $j; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="small fw-bold text-muted">Kelas</label>
                            <select id="swal-kelas" class="form-select">
                                <?php 
                                $k_list = $conn->query("SELECT nama_kelas FROM kelas ORDER BY nama_kelas ASC");
                                while($kl = $k_list->fetch_assoc()): ?>
                                    <option value="<?php echo $kl['nama_kelas']; ?>"><?php echo $kl['nama_kelas']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Cetak Rekap',
                    confirmButtonColor: '#0f172a',
                    preConfirm: () => {
                        return {
                            tahun: document.getElementById('swal-tahun').value,
                            semester: document.getElementById('swal-semester').value,
                            jurusan: document.getElementById('swal-jurusan').value,
                            kelas: document.getElementById('swal-kelas').value
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const p = result.value;
                        window.open(`cetak_rekap_kelas.php?tahun=${p.tahun}&semester=${p.semester}&jurusan=${p.jurusan}&kelas=${p.kelas}`, '_blank');
                    }
                });
            } else if(type === 'mapel') {
                Swal.fire({
                    title: 'Cetak Rekap Nilai Mapel',
                    html: `
                        <div class="text-start">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted">Tahun Ajaran</label>
                                    <select id="swal-tahun" class="form-select mb-2">
                                        <?php foreach($daftar_tahun as $t): ?>
                                            <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted">Semester</label>
                                    <select id="swal-semester" class="form-select mb-2">
                                        <option value="Ganjil">Ganjil</option>
                                        <option value="Genap">Genap</option>
                                    </select>
                                </div>
                            </div>
                            <label class="small fw-bold text-muted">Jurusan</label>
                            <select id="swal-jurusan" class="form-select mb-2">
                                <?php foreach($daftar_jurusan as $j): ?>
                                    <option value="<?php echo $j; ?>"><?php echo $j; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="small fw-bold text-muted">Kelas</label>
                            <select id="swal-kelas" class="form-select mb-2">
                                <?php 
                                $k_list = $conn->query("SELECT nama_kelas FROM kelas ORDER BY nama_kelas ASC");
                                while($kl = $k_list->fetch_assoc()): ?>
                                    <option value="<?php echo $kl['nama_kelas']; ?>"><?php echo $kl['nama_kelas']; ?></option>
                                <?php endwhile; ?>
                            </select>
                            <label class="small fw-bold text-muted">Mata Pelajaran</label>
                            <select id="swal-mapel" class="form-select">
                                <?php 
                                $m_q = "SELECT id, nama_mapel FROM mapel";
                                if ($role == 'guru') {
                                    $m_q .= " WHERE nip_guru = '$username'";
                                }
                                $m_q .= " ORDER BY nama_mapel ASC";
                                $m_list = $conn->query($m_q);
                                while($ml = $m_list->fetch_assoc()): ?>
                                    <option value="<?php echo $ml['id']; ?>"><?php echo $ml['nama_mapel']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Cetak Laporan',
                    confirmButtonColor: '#0f172a',
                    preConfirm: () => {
                        return {
                            tahun: document.getElementById('swal-tahun').value,
                            semester: document.getElementById('swal-semester').value,
                            jurusan: document.getElementById('swal-jurusan').value,
                            kelas: document.getElementById('swal-kelas').value,
                            mapel: document.getElementById('swal-mapel').value
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const p = result.value;
                        window.open(`cetak_rekap_mapel.php?tahun=${p.tahun}&semester=${p.semester}&jurusan=${p.jurusan}&kelas=${p.kelas}&mapel=${p.mapel}`, '_blank');
                    }
                });
            } else {
                Swal.fire({
                    title: 'Cetak Transkrip ' + type.charAt(0).toUpperCase() + type.slice(1),
                    text: "Fitur cetak laporan sedang dalam pengembangan untuk format PDF profesional.",
                    icon: 'info',
                    confirmButtonColor: '#0f172a',
                    confirmButtonText: 'Tutup'
                });
            }
        }
        </script>
        <?php include '../layouts/footer.php'; ?>