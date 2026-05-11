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

// Hanya Admin yang boleh akses manajemen siswa
if ($_SESSION['user']['role'] != 'admin') {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini!';
    header("Location: dashboard.php");
    exit;
}

$user = $_SESSION['user'];
$role = $user['role'];
$username = $user['username'];

$action = $_GET['action'] ?? 'list';

// Fix database columns and lengths
$conn->query("ALTER TABLE siswa MODIFY COLUMN kelas VARCHAR(50)");
$conn->query("ALTER TABLE siswa MODIFY COLUMN jurusan VARCHAR(100)");
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tahun_masuk VARCHAR(10) AFTER semester");
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tempat_lahir VARCHAR(100) AFTER tahun_masuk");
$conn->query("ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tanggal_lahir DATE AFTER tempat_lahir");
$label_id = LBL_INSTANSI == 'Kampus' ? 'NIM' : 'NIS';

// Load Pengaturan Sekolah
$pengaturan_query = $conn->query("SELECT * FROM pengaturan_sekolah WHERE id=1");
$pengaturan = $pengaturan_query->fetch_assoc();

// Load Daftar Jurusan dari tabel jurusan
$jurusan_res = $conn->query("SELECT nama_jurusan FROM jurusan ORDER BY nama_jurusan ASC");
$daftar_jurusan = [];
while($j_row = $jurusan_res->fetch_assoc()) {
    $daftar_jurusan[] = $j_row['nama_jurusan'];
}

// Load Daftar Kelas
$kelas_list_res = $conn->query("SELECT nama_kelas, jurusan FROM kelas ORDER BY nama_kelas ASC");
$daftar_kelas = [];
while($k_row = $kelas_list_res->fetch_assoc()) {
    $daftar_kelas[] = $k_row;
}

if ($action == 'delete' && isset($_GET['nis'])) {
    $nis = $conn->real_escape_string($_GET['nis']);
    $conn->query("DELETE FROM users WHERE username='$nis' AND role='siswa'");
    $conn->query("DELETE FROM siswa WHERE nis='$nis'");
    $_SESSION['success'] = 'Data ' . LBL_SISWA . ' Berhasil Dihapus!';
    header("Location: siswa.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $conn->real_escape_string($_POST['nis']);
    $nama = $conn->real_escape_string($_POST['nama']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $jurusan = $conn->real_escape_string($_POST['jurusan']);
    $agama = $conn->real_escape_string($_POST['agama']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    $no_hp = $conn->real_escape_string($_POST['no_hp']);
    $status = $conn->real_escape_string($_POST['status'] ?? 'Aktif');
    $tahun_ajaran = $conn->real_escape_string($_POST['tahun_ajaran'] ?? $pengaturan['tahun_ajaran']);
    $semester = $conn->real_escape_string($_POST['semester'] ?? $pengaturan['semester']);
    $tahun_masuk = $conn->real_escape_string($_POST['tahun_masuk']);
    $tempat_lahir = $conn->real_escape_string($_POST['tempat_lahir']);
    $tanggal_lahir = $conn->real_escape_string($_POST['tanggal_lahir']);

    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_ext)) {
            $_SESSION['error'] = 'Format file tidak diizinkan! Gunakan JPG, PNG, atau WEBP.';
            header("Location: siswa.php?action=" . $action);
            exit;
        }

        if (!is_dir('../uploads')) mkdir('../uploads');
        $foto = 'uploads/siswa_' . $nis . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], '../' . $foto);
    }

    if ($action == 'add') {
        $query = "INSERT INTO siswa (nis, nama, kelas, jurusan, agama, alamat, no_hp, foto, status, tahun_ajaran, semester, tahun_masuk, tempat_lahir, tanggal_lahir) 
                  VALUES ('$nis', '$nama', '$kelas', '$jurusan', '$agama', '$alamat', '$no_hp', '$foto', '$status', '$tahun_ajaran', '$semester', '$tahun_masuk', '$tempat_lahir', '$tanggal_lahir')";
        if ($conn->query($query)) {
            // Create user for this student
            $conn->query("INSERT INTO users (username, password, role) VALUES ('$nis', MD5('12345'), 'siswa')");
            $_SESSION['success'] = 'Data ' . LBL_SISWA . ' Berhasil Ditambahkan!';
        }
        header("Location: siswa.php");
        exit;
    } elseif ($action == 'edit') {
        $old_nis = $conn->real_escape_string($_POST['old_nis']);
        $query = "UPDATE siswa SET nis='$nis', nama='$nama', kelas='$kelas', jurusan='$jurusan', 
                  agama='$agama', alamat='$alamat', no_hp='$no_hp', status='$status', tahun_ajaran='$tahun_ajaran', semester='$semester',
                  tahun_masuk='$tahun_masuk', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir'";
        if ($foto != '') {
            $query .= ", foto='$foto'";
        }
        $query .= " WHERE nis='$old_nis'";
        if ($conn->query($query)) {
            // Update username if NIS changed
            if ($nis != $old_nis) {
                $conn->query("UPDATE users SET username='$nis' WHERE username='$old_nis' AND role='siswa'");
            }
            $_SESSION['success'] = 'Data ' . LBL_SISWA . ' Berhasil Diperbarui!';
        }
        header("Location: siswa.php");
        exit;
    }
}

$page_title = 'Data ' . LBL_SISWA;
include '../layouts/header.php';
?>
<div class="container-fluid bg-transparent p-0">
    <?php if ($action == 'list'): ?>
        <!-- Header List -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bxs-user-detail text-primary me-2'></i> Manajemen Data <?php echo LBL_SISWA; ?>
                </h3>
                <p class="text-muted mb-0">Kelola seluruh data <?php echo strtolower(LBL_SISWA); ?>, kelas, jurusan, dan status keaktifan.</p>
            </div>
            <a href="siswa.php?action=add" class="btn fw-bold rounded-3 px-4 shadow-sm text-white d-flex align-items-center justify-content-center"
                style="background-color: #0f172a; border: none; transition: all 0.3s ease; height: 45px;"
                onmouseover="this.style.backgroundColor='#3b82f6'; this.style.transform='translateY(-2px)'" 
                onmouseout="this.style.backgroundColor='#0f172a'; this.style.transform='translateY(0)'">
                <i class='bx bx-plus-circle me-2 fs-5'></i> <?php echo LBL_SISWA; ?> Baru
            </a>
        </div>

        <!-- Table Panel -->
        <div class="bg-white p-4 rounded-4 border shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="fw-semibold pb-3">Profil <?php echo LBL_SISWA; ?></th>
                            <th class="fw-semibold pb-3">Data Akademik</th>
                            <th class="fw-semibold pb-3">Status</th>
                            <th class="fw-semibold pb-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM siswa");
                        while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <!-- Profil Siswa Column -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($row['foto']): ?>
                                            <img src="../<?php echo $row['foto']; ?>" width="48" height="48"
                                                class="rounded-circle shadow-sm me-3"
                                                style="object-fit:cover; border: 2px solid #fff;">
                                        <?php else: ?>
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm border border-white"
                                                style="width:48px;height:48px; font-size:1.2rem;">
                                                <?php echo strtoupper(substr($row['nama'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <span class="fw-bold text-dark d-block"
                                                style="font-size: 0.95rem;"><?php echo $row['nama']; ?></span>
                                            <small class="text-muted d-block mt-1"><?php echo $label_id; ?>: <span
                                                    class="fw-medium text-secondary">#<?php echo $row['nis']; ?></span> &bull;
                                                <i class='bx bx-phone'></i> <?php echo $row['no_hp']; ?></small>
                                        </div>
                                    </div>
                                </td>

                                <!-- Data Akademik Column -->
                                <td>
                                    <div class="mb-1">
                                        <span class="fw-bold text-dark me-2">Kelas <?php echo $row['kelas']; ?></span>
                                        <span
                                            class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1 rounded-pill"
                                            style="font-size: 0.7rem;"><?php echo $row['jurusan']; ?></span>
                                    </div>
                                    <small class="text-muted d-block"><i class='bx bx-calendar-event'></i> Thn:
                                        <?php echo $row['tahun_ajaran']; ?> - Smt: <?php echo $row['semester']; ?></small>
                                </td>

                                <!-- Status Column -->
                                <td>
                                    <?php if ($row['status'] == 'Aktif'): ?>
                                        <span
                                            class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill"><i
                                                class='bx bx-check-circle me-1'></i> Aktif</span>
                                    <?php else: ?>
                                        <span
                                            class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 rounded-pill"><i
                                                class='bx bx-x-circle me-1'></i> Keluar</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi Column -->
                                <td class="text-end">
                                    <?php $siswa_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>
                                    <div class="d-flex justify-content-end gap-3 align-items-center">
                                        <button type="button" class="btn btn-link text-info p-0 text-decoration-none"
                                            title="Lihat Detail" data-siswa='<?php echo $siswa_json; ?>'
                                            onclick="showDetailSiswa(this)">
                                            <i class='bx bx-show fs-4'></i>
                                        </button>
                                        <a href="siswa.php?action=edit&nis=<?php echo $row['nis']; ?>"
                                            class="btn btn-link text-warning p-0 text-decoration-none" title="Edit Data">
                                            <i class='bx bx-edit-alt fs-4'></i>
                                        </a>
                                        <a href="siswa.php?action=delete&nis=<?php echo $row['nis']; ?>"
                                            class="btn btn-link text-danger p-0 text-decoration-none btn-delete"
                                            title="Hapus Data">
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

        <style>
            @media (min-width: 992px) {
                .modal-detail-custom {
                    max-width: 420px;
                }
            }
        </style>
        <!-- Modal Detail Siswa -->
        <div class="modal fade" id="modalDetailSiswa" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-detail-custom modal-dialog-centered">
                <div class="modal-content overflow-hidden border-0 shadow-lg rounded-4">

                    <!-- Banner Background -->
                    <div class="position-relative"
                        style="height: 90px; background: linear-gradient(135deg, #1e293b, #3b82f6);">
                        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                        <i class='bx bxs-quote-right position-absolute'
                            style="font-size: 5rem; color: rgba(255,255,255,0.05); right: -10px; bottom: -10px;"></i>
                    </div>

                    <div class="modal-body px-4 pb-3 pt-0 text-center">
                        <!-- Profile Picture overlapping banner -->
                        <div class="position-relative mx-auto mb-2"
                            style="width: 80px; height: 80px; margin-top: -40px; z-index: 2;">
                            <img id="detailFoto" src="" class="rounded-circle shadow-sm bg-white p-1"
                                style="width: 100%; height: 100%; object-fit: cover; display:none;">
                            <div id="detailFotoPlaceholder"
                                class="rounded-circle shadow-sm bg-white p-1 flex-column align-items-center justify-content-center text-primary fw-bold"
                                style="width: 100%; height: 100%; font-size: 3rem; display:none;">
                                <span id="detailInitial">A</span>
                            </div>
                        </div>

                        <h5 class="fw-bold text-dark mb-1" id="detailNama">Nama Lengkap <?php echo LBL_SISWA; ?></h5>
                        <p class="text-muted mb-2" style="font-size: 0.85rem;">
                            <i class='bx bx-id-card me-1'></i> <?php echo $label_id; ?>: <span class="fw-bold text-dark"
                                id="detailNis">12345</span>
                            <button class="btn btn-link p-0 ms-1 text-primary" onclick="copyNis()" title="Salin <?php echo $label_id; ?>">
                                <i class='bx bx-copy'></i>
                            </button>
                        </p>

                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <span
                                class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2 rounded-pill"><i
                                    class='bx bxs-graduation me-1'></i> <span id="detailJurusan">Jurusan</span></span>
                            <span
                                class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-2 rounded-pill"><i
                                    class='bx bx-building-house me-1'></i> Kelas <span id="detailKelas">X-1</span></span>
                        </div>

                        <!-- Information Grid -->
                        <div class="row g-2 text-start mb-3">
                            <div class="col-6">
                                <div class="p-2 bg-light rounded-4 border border-light-subtle h-100 transition-hover">
                                    <small class="text-muted d-block mb-0" style="font-size: 0.75rem;"><i
                                            class='bx bx-phone text-primary me-1'></i> Telepon</small>
                                    <span class="fw-medium text-dark" style="font-size: 0.85rem;"
                                        id="detailNoHp">081234</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded-4 border border-light-subtle h-100 transition-hover">
                                    <small class="text-muted d-block mb-0" style="font-size: 0.75rem;"><i
                                            class='bx bx-moon text-primary me-1'></i> Agama</small>
                                    <span class="fw-medium text-dark" style="font-size: 0.85rem;"
                                        id="detailAgama">Islam</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded-4 border border-light-subtle h-100 transition-hover">
                                    <small class="text-muted d-block mb-0" style="font-size: 0.75rem;"><i
                                            class='bx bx-map text-primary me-1'></i> Tempat/Tgl Lahir</small>
                                    <span class="fw-medium text-dark" style="font-size: 0.85rem;"
                                        id="detailTTL">-</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded-4 border border-light-subtle h-100 transition-hover">
                                    <small class="text-muted d-block mb-0" style="font-size: 0.75rem;"><i
                                            class='bx bx-calendar-star text-primary me-1'></i> Tahun Masuk</small>
                                    <span class="fw-medium text-dark" style="font-size: 0.85rem;"
                                        id="detailTahunMasuk">-</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-2 bg-light rounded-4 border border-light-subtle transition-hover">
                                    <small class="text-muted d-block mb-0" style="font-size: 0.75rem;"><i
                                            class='bx bx-map-pin text-primary me-1'></i> Alamat</small>
                                    <span class="fw-medium text-dark" style="font-size: 0.85rem;" id="detailAlamat">Jl.
                                        Merdeka</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Academic -->
                        <div class="d-flex justify-content-between align-items-center p-3 rounded-4 shadow-sm"
                            style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="text-start">
                                <small class="text-muted d-block fw-medium mb-1">Tahun Akademik</small>
                                <span class="fw-bold text-dark"><i class='bx bx-calendar text-primary me-1'></i> <span
                                        id="detailAngkatan">2023/2024 - Ganjil</span></span>
                            </div>
                            <div id="detailStatus">
                                <!-- Status Badge here -->
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <script>
            function showDetailSiswa(btn) {
                const data = JSON.parse(btn.getAttribute('data-siswa'));

                document.getElementById('detailNama').innerText = data.nama;
                document.getElementById('detailNis').innerText = data.nis;
                document.getElementById('detailKelas').innerText = data.kelas;
                document.getElementById('detailJurusan').innerText = data.jurusan;
                document.getElementById('detailAgama').innerText = data.agama;
                document.getElementById('detailNoHp').innerText = data.no_hp;
                document.getElementById('detailAlamat').innerText = data.alamat;
                document.getElementById('detailAngkatan').innerText = data.tahun_ajaran + ' - ' + data.semester;
                document.getElementById('detailTahunMasuk').innerText = data.tahun_masuk || '-';
                document.getElementById('detailTTL').innerText = (data.tempat_lahir || '-') + ', ' + (data.tanggal_lahir || '-');

                // Status Badge
                const statusContainer = document.getElementById('detailStatus');
                if (data.status === 'Aktif') {
                    statusContainer.innerHTML = `<span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm"><i class='bx bx-check-circle me-1'></i> Aktif Belajar</span>`;
                } else {
                    statusContainer.innerHTML = `<span class="badge bg-danger text-white px-3 py-2 rounded-pill shadow-sm"><i class='bx bx-x-circle me-1'></i> Dikeluarkan</span>`;
                }

                // Foto
                const imgEl = document.getElementById('detailFoto');
                const placeholderEl = document.getElementById('detailFotoPlaceholder');
                if (data.foto) {
                    imgEl.src = '../' + data.foto;
                    imgEl.style.display = 'block';
                    placeholderEl.style.display = 'none';
                    placeholderEl.classList.remove('d-flex');
                } else {
                    imgEl.style.display = 'none';
                    document.getElementById('detailInitial').innerText = data.nama.charAt(0).toUpperCase();
                    placeholderEl.style.display = 'flex';
                    placeholderEl.classList.add('d-flex');
                }

                // Show Modal
                const modal = new bootstrap.Modal(document.getElementById('modalDetailSiswa'));
                modal.show();
            }

            function copyNis() {
                const nis = document.getElementById('detailNis').innerText;
                navigator.clipboard.writeText(nis).then(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: '<?php echo $label_id; ?> berhasil disalin!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                });
            }
        </script>

    <?php elseif ($action == 'add' || $action == 'edit'):
        $row = null;
        if ($action == 'edit' && isset($_GET['nis'])) {
            $nis = $conn->real_escape_string($_GET['nis']);
            $result = $conn->query("SELECT * FROM siswa WHERE nis='$nis'");
            $row = $result->fetch_assoc();
        }
        ?>
        <!-- Header Form -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bx-edit text-primary me-2'></i>
                    <?php echo $action == 'add' ? 'Registrasi ' . LBL_SISWA . ' Baru' : 'Perbarui Data ' . LBL_SISWA; ?></h3>
                <p class="text-muted mb-0">Lengkapi formulir di bawah ini dengan informasi yang valid.</p>
            </div>
            <a href="siswa.php" class="btn btn-outline-secondary fw-medium rounded-pill px-4 shadow-sm">
                <i class='bx bx-arrow-back me-1'></i> Kembali
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="old_nis" value="<?php echo $row['nis']; ?>">
            <?php endif; ?>

            <div class="bg-white p-4 p-lg-5 rounded-4 border shadow-sm">

                <!-- Section: Informasi Pribadi -->
                <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-id-card text-primary me-2'></i>
                    Informasi Pribadi <?php echo LBL_SISWA; ?></h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-medium text-muted">Nomor Induk <?php echo LBL_SISWA; ?> (<?php echo $label_id; ?>)</label>
                        <input type="text" name="nis"
                            class="form-control <?php echo $action == 'edit' ? 'bg-light' : ''; ?>"
                            value="<?php echo $row['nis'] ?? ''; ?>" required <?php echo $action == 'edit' ? 'readonly' : ''; ?>>
                        <?php if ($action == 'edit'): ?>
                            <small class="text-danger mt-1 d-block"><i class='bx bx-lock-alt'></i> NIS tidak dapat
                                diubah.</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 col-lg-8">
                        <label class="form-label fw-medium text-muted">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?php echo $row['nama'] ?? ''; ?>"
                            required>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-muted">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control" value="<?php echo $row['tempat_lahir'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-muted">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control" value="<?php echo $row['tanggal_lahir'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-muted">Tahun Masuk</label>
                        <input type="text" name="tahun_masuk" class="form-control" placeholder="Contoh: 2022" value="<?php echo $row['tahun_masuk'] ?? ''; ?>" required>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-medium text-muted">Agama</label>
                        <select name="agama" class="form-select" required>
                            <option value="">-- Pilih Agama --</option>
                            <?php
                            $agama_list = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
                            foreach ($agama_list as $a):
                                $selected = (isset($row['agama']) && $row['agama'] == $a) ? 'selected' : '';
                                echo "<option value=\"$a\" $selected>$a</option>";
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-medium text-muted">Nomor HP / WhatsApp</label>
                        <input type="text" name="no_hp" class="form-control" value="<?php echo $row['no_hp'] ?? ''; ?>"
                            required>
                    </div>
                    <div class="col-md-12 col-lg-4">
                        <label class="form-label fw-medium text-muted">Pas Foto <small>(Opsional)</small></label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <?php if (isset($row['foto']) && $row['foto']): ?>
                            <small class="text-muted mt-1 d-block"><a href="../<?php echo $row['foto']; ?>" target="_blank"
                                    class="text-decoration-none"><i class='bx bx-link-external'></i> Lihat Foto Saat
                                    Ini</a></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium text-muted">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="2"
                            required><?php echo $row['alamat'] ?? ''; ?></textarea>
                    </div>
                </div>

                <!-- Section: Data Akademik -->
                <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-book-bookmark text-success me-2'></i>
                    Data Akademik & Penempatan</h5>
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-muted">Jurusan</label>
                        <select name="jurusan" id="selectJurusanSiswa" class="form-select" required onchange="filterKelasSiswa()">
                            <option value="">-- Pilih Jurusan --</option>
                            <?php foreach ($daftar_jurusan as $j): ?>
                                <?php if ($j != ''): ?>
                                    <option value="<?php echo htmlspecialchars($j); ?>" <?php echo (isset($row['jurusan']) && $row['jurusan'] == $j) ? 'selected' : ''; ?>><?php echo htmlspecialchars($j); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-muted">Kelas</label>
                        <select name="kelas" id="selectKelasSiswa" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($daftar_kelas as $k_item): ?>
                                <option value="<?php echo htmlspecialchars($k_item['nama_kelas']); ?>" data-jurusan="<?php echo htmlspecialchars($k_item['jurusan'] ?? ''); ?>" <?php echo (isset($row['kelas']) && $row['kelas'] == $k_item['nama_kelas']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($k_item['nama_kelas']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-muted">Status Keaktifan</label>
                        <select name="status" class="form-select" required>
                            <option value="Aktif" <?php echo (isset($row['status']) && $row['status'] == 'Aktif') ? 'selected' : ''; ?>>Aktif (Belajar)</option>
                            <option value="Dikeluarkan" <?php echo (isset($row['status']) && $row['status'] == 'Dikeluarkan') ? 'selected' : ''; ?>>Dikeluarkan / Pindah</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-muted">Tahun Ajaran <i class='bx bx-lock-alt text-warning'
                                title="Otomatis dari pengaturan"></i></label>
                        <input type="text" name="tahun_ajaran" class="form-control bg-light"
                            value="<?php echo $row['tahun_ajaran'] ?? $pengaturan['tahun_ajaran']; ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-muted">Semester <i class='bx bx-lock-alt text-warning'
                                title="Otomatis dari pengaturan"></i></label>
                        <input type="text" name="semester" class="form-control bg-light"
                            value="<?php echo $row['semester'] ?? $pengaturan['semester']; ?>" readonly>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-3 border-top">
                    <a href="siswa.php" class="btn btn-light border px-4 py-2 fw-medium text-muted hover-dark">Batal</a>
                    <button type="submit" class="btn px-4 py-2 fw-medium text-white shadow-sm"
                        style="background-color: #0f172a; transition: all 0.3s;"
                        onmouseover="this.style.backgroundColor='#3b82f6'"
                        onmouseout="this.style.backgroundColor='#0f172a'">
                        <i class='bx bx-save me-1'></i> Simpan Data <?php echo LBL_SISWA; ?>
                    </button>
                </div>

            </div>
        </form>

        <script>
        function filterKelasSiswa() {
            var jurusan = document.getElementById('selectJurusanSiswa').value;
            var selectKelas = document.getElementById('selectKelasSiswa');
            var options = selectKelas.options;
            
            selectKelas.disabled = (jurusan === '');
            var validSelection = false;

            for (var i = 1; i < options.length; i++) {
                var optJurusan = options[i].getAttribute('data-jurusan');
                if (optJurusan === jurusan || jurusan === '') {
                    options[i].style.display = '';
                    options[i].disabled = false;
                    if(options[i].selected) validSelection = true;
                } else {
                    options[i].style.display = 'none';
                    options[i].disabled = true;
                    if(options[i].selected) options[i].selected = false;
                }
            }
            
            if(!validSelection && jurusan !== '') {
                selectKelas.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('selectJurusanSiswa')) {
                filterKelasSiswa();
            }
        });
        </script>
    <?php endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>