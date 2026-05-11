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

// Hanya Admin yang boleh akses manajemen mapel
if ($_SESSION['user']['role'] != 'admin') {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini!';
    header("Location: dashboard.php");
    exit;
}

$user = $_SESSION['user'];
$role = $user['role'];
$username = $user['username'];

$action = $_GET['action'] ?? 'list';

// Check if SKS and Kode column exists
$check_sks = $conn->query("SHOW COLUMNS FROM mapel LIKE 'sks'");
if ($check_sks && $check_sks->num_rows == 0) {
    $conn->query("ALTER TABLE mapel ADD COLUMN sks INT DEFAULT 0");
}
$check_kode = $conn->query("SHOW COLUMNS FROM mapel LIKE 'kode_mapel'");
if ($check_kode && $check_kode->num_rows == 0) {
    $conn->query("ALTER TABLE mapel ADD COLUMN kode_mapel VARCHAR(20) NULL AFTER id");
}

if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM mapel WHERE id=$id");
    $_SESSION['success'] = LBL_MAPEL . ' Berhasil Dihapus!';
    header("Location: mapel.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_mapel = $conn->real_escape_string($_POST['kode_mapel'] ?? '');
    $nama_mapel = $conn->real_escape_string($_POST['nama_mapel']);
    $jurusan = $conn->real_escape_string($_POST['jurusan']);
    $nip_guru = $conn->real_escape_string($_POST['nip_guru']);
    $sks = (int)($_POST['sks'] ?? 0);

    if ($action == 'add') {
        $conn->query("INSERT INTO mapel (kode_mapel, nama_mapel, jurusan, nip_guru, sks) VALUES ('$kode_mapel', '$nama_mapel', '$jurusan', '$nip_guru', $sks)");
        $_SESSION['success'] = LBL_MAPEL . ' Berhasil Ditambahkan!';
        header("Location: mapel.php");
        exit;
    } elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE mapel SET kode_mapel='$kode_mapel', nama_mapel='$nama_mapel', jurusan='$jurusan', nip_guru='$nip_guru', sks=$sks WHERE id=$id");
        $_SESSION['success'] = LBL_MAPEL . ' Berhasil Diperbarui!';
        header("Location: mapel.php");
        exit;
    }
}

$page_title = LBL_MAPEL;
include '../layouts/header.php'; 

// Ambil data pengaturan sekolah untuk daftar jurusan
$config_res = $conn->query("SELECT daftar_jurusan FROM pengaturan_sekolah WHERE id=1");
$config_data = $config_res->fetch_assoc();
$jurusan_list = array_map('trim', explode(',', $config_data['daftar_jurusan']));
?>

<div class="container-fluid bg-transparent p-0">
    <?php if ($action == 'list'): ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bxs-book-alt text-primary me-2'></i> <?php echo LBL_MAPEL; ?></h3>
                <p class="text-muted mb-0 small">Kelola kurikulum <?php echo strtolower(LBL_MAPEL); ?> dan <?php echo strtolower(LBL_GURU); ?> pengampu.</p>
            </div>
            <a href="mapel.php?action=add" class="btn fw-bold rounded-3 px-4 shadow-sm text-white d-flex align-items-center justify-content-center" 
               style="background-color: #0f172a; border: none; transition: all 0.3s ease; height: 45px;" 
               onmouseover="this.style.backgroundColor='#3b82f6'; this.style.transform='translateY(-2px)'" 
               onmouseout="this.style.backgroundColor='#0f172a'; this.style.transform='translateY(0)'">
                <i class='bx bx-plus-circle me-2 fs-5'></i> Tambah Baru
            </a>
        </div>

        <!-- Table Panel -->
        <div class="bg-white p-4 rounded-4 border shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="fw-semibold pb-3">Kode</th>
                            <th class="fw-semibold pb-3"><?php echo LBL_MAPEL; ?></th>
                            <?php if(LBL_INSTANSI == 'Kampus'): ?>
                            <th class="fw-semibold pb-3 text-center">SKS</th>
                            <?php endif; ?>
                            <th class="fw-semibold pb-3 d-none d-md-table-cell">Jurusan</th>
                            <th class="fw-semibold pb-3"><?php echo LBL_GURU; ?> Pengampu</th>
                            <th class="fw-semibold pb-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT m.*, g.nama as nama_guru, g.foto as foto_guru FROM mapel m LEFT JOIN guru g ON m.nip_guru = g.nip";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small"><?php echo $row['kode_mapel'] ?? '-'; ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm" style="width:40px;height:40px;">
                                        <i class='bx bx-book' style="font-size: 1.2rem;"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block" style="font-size: 0.95rem;"><?php echo $row['nama_mapel']; ?></span>
                                        <small class="text-muted d-md-none"><?php echo $row['jurusan']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <?php if(LBL_INSTANSI == 'Kampus'): ?>
                            <td class="text-center">
                                <span class="badge bg-secondary text-white"><?php echo $row['sks']; ?> SKS</span>
                            </td>
                            <?php endif; ?>
                            <td class="d-none d-md-table-cell">
                                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small"><?php echo $row['jurusan']; ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if($row['foto_guru']): ?>
                                        <img src="../<?php echo $row['foto_guru']; ?>" width="30" height="30" class="rounded-circle me-2" style="object-fit:cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center me-2" style="width:30px;height:30px; font-size: 0.8rem;">
                                            <i class='bx bx-user'></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="fw-medium text-dark small"><?php echo $row['nama_guru'] ?? 'Belum Ditentukan'; ?></span>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-3 align-items-center">
                                    <a href="mapel.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-link text-warning p-0 text-decoration-none" title="Edit Data">
                                        <i class='bx bx-edit-alt fs-4'></i>
                                    </a>
                                    <a href="mapel.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-link text-danger p-0 text-decoration-none btn-delete" title="Hapus Data">
                                        <i class='bx bx-trash fs-4'></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($action == 'add' || $action == 'edit'): 
        $row = null;
        if ($action == 'edit' && isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $result = $conn->query("SELECT * FROM mapel WHERE id=$id");
            $row = $result->fetch_assoc();
        }
        $guru_result = $conn->query("SELECT * FROM guru ORDER BY nama ASC");
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bx-edit text-primary me-2'></i> <?php echo $action == 'add' ? 'Tambah ' . LBL_MAPEL : 'Edit ' . LBL_MAPEL; ?></h3>
                <p class="text-muted mb-0 small">Tentukan <?php echo strtolower(LBL_MAPEL); ?> dan pilih <?php echo strtolower(LBL_GURU); ?> yang mengampu.</p>
            </div>
            <a href="mapel.php" class="btn btn-outline-secondary fw-medium rounded-3 px-4 shadow-sm">
                <i class='bx bx-arrow-back me-1'></i> Kembali
            </a>
        </div>

        <form method="POST">
            <?php if($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
            <?php endif; ?>
            
            <div class="bg-white p-4 p-lg-5 rounded-4 border shadow-sm">
                <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-book-content text-primary me-2'></i> Detail Kurikulum</h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-medium text-muted">Kode</label>
                        <input type="text" name="kode_mapel" class="form-control form-control-lg" value="<?php echo $row['kode_mapel'] ?? ''; ?>" placeholder="Contoh: KED112" required>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label fw-medium text-muted">Nama <?php echo LBL_MAPEL; ?></label>
                        <input type="text" name="nama_mapel" class="form-control form-control-lg" value="<?php echo $row['nama_mapel'] ?? ''; ?>" placeholder="Masukkan nama..." required>
                    </div>
                    <?php if(LBL_INSTANSI == 'Kampus'): ?>
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-muted">Jumlah SKS</label>
                        <input type="number" name="sks" class="form-control" value="<?php echo $row['sks'] ?? 0; ?>" min="0" required>
                    </div>
                    <div class="col-md-4">
                    <?php else: ?>
                    <div class="col-md-6">
                    <?php endif; ?>
                        <label class="form-label fw-medium text-muted">Ditujukan Untuk Jurusan</label>
                        <select name="jurusan" class="form-select" required>
                            <option value="">-- Pilih Jurusan --</option>
                            <?php foreach($jurusan_list as $jur): ?>
                                <option value="<?php echo $jur; ?>" <?php echo (isset($row['jurusan']) && $row['jurusan'] == $jur) ? 'selected' : ''; ?>><?php echo $jur; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if(LBL_INSTANSI == 'Kampus'): ?>
                    <div class="col-md-4">
                    <?php else: ?>
                    <div class="col-md-6">
                    <?php endif; ?>
                        <label class="form-label fw-medium text-muted"><?php echo LBL_GURU; ?> Pengampu</label>
                        <select name="nip_guru" class="form-select" required>
                            <option value="">-- Pilih <?php echo LBL_GURU; ?> --</option>
                            <?php while ($g = $guru_result->fetch_assoc()): ?>
                                <option value="<?php echo $g['nip']; ?>" <?php echo (isset($row['nip_guru']) && $row['nip_guru'] == $g['nip']) ? 'selected' : ''; ?>>
                                    <?php echo $g['nama']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-3 border-top">
                    <a href="mapel.php" class="btn btn-light border px-4 py-2 fw-medium text-muted">Batal</a>
                    <button type="submit" class="btn px-4 py-2 fw-medium text-white shadow-sm" style="background-color: #0f172a; transition: all 0.3s;" onmouseover="this.style.backgroundColor='#3b82f6'" onmouseout="this.style.backgroundColor='#0f172a'">
                        <i class='bx bx-save me-1'></i> Simpan <?php echo LBL_MAPEL; ?>
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>
