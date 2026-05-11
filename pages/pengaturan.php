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

// Hanya Admin yang boleh akses pengaturan (opsional jika ada role)
if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] != 'admin') {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini!';
    header("Location: dashboard.php");
    exit;
}

include '../config/db.php';

// AJAX Handler for Jurusan
if (isset($_POST['ajax_add_jurusan'])) {
    $nama = $conn->real_escape_string($_POST['nama_jurusan']);
    $kaprodi = $conn->real_escape_string($_POST['kaprodi']);
    $conn->query("INSERT INTO jurusan (nama_jurusan, kaprodi) VALUES ('$nama', '$kaprodi')");
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    exit;
}
if (isset($_POST['ajax_delete_jurusan'])) {
    $id = (int)$_POST['id'];
    $conn->query("DELETE FROM jurusan WHERE id=$id");
    echo json_encode(['success' => true]);
    exit;
}

// Ensure column exists
$conn->query("ALTER TABLE pengaturan_sekolah ADD COLUMN IF NOT EXISTS daftar_tahun_ajaran TEXT");
$conn->query("ALTER TABLE pengaturan_sekolah ADD COLUMN IF NOT EXISTS logo VARCHAR(255)");
$conn->query("ALTER TABLE pengaturan_sekolah ADD COLUMN IF NOT EXISTS nip_kepala VARCHAR(50)");
$conn->query("ALTER TABLE pengaturan_sekolah ADD COLUMN IF NOT EXISTS nama_dekan VARCHAR(100)");
$conn->query("ALTER TABLE pengaturan_sekolah ADD COLUMN IF NOT EXISTS nip_dekan VARCHAR(50)");
$conn->query("ALTER TABLE pengaturan_sekolah ADD COLUMN IF NOT EXISTS nama_yayasan VARCHAR(255) DEFAULT 'YAYASAN PENDIDIKAN DAN SOSIAL RMP GROUP INDONESIA'");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_sekolah = $conn->real_escape_string($_POST['nama_sekolah']);
    $tahun_ajaran = $conn->real_escape_string($_POST['tahun_ajaran']);
    $daftar_tahun_ajaran = $conn->real_escape_string($_POST['daftar_tahun_ajaran']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $kepala_sekolah = $conn->real_escape_string($_POST['kepala_sekolah']);
    $email = $conn->real_escape_string($_POST['email']);
    $telepon = $conn->real_escape_string($_POST['telepon']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    $nip_kepala = $conn->real_escape_string($_POST['nip_kepala']);
    $nama_yayasan = $conn->real_escape_string($_POST['nama_yayasan']);
    $tipe_instansi = $conn->real_escape_string($_POST['tipe_instansi'] ?? 'Sekolah');

    // Sync daftar_jurusan from the new table for backward compatibility
    $j_res = $conn->query("SELECT nama_jurusan FROM jurusan ORDER BY nama_jurusan ASC");
    $j_names = [];
    while($jr = $j_res->fetch_assoc()) $j_names[] = $jr['nama_jurusan'];
    $daftar_jurusan = $conn->real_escape_string(implode(', ', $j_names));
    
    // Handle Logo Upload
    $logo_query = "";
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "../assets/img/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION);
        $new_logo_name = "logo_sekolah_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_logo_name;
        
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_path = "assets/img/" . $new_logo_name;
            $logo_query = ", logo='$logo_path'";
        }
    }

    $query = "UPDATE pengaturan_sekolah SET 
              tipe_instansi='$tipe_instansi',
              nama_sekolah='$nama_sekolah', 
              tahun_ajaran='$tahun_ajaran', 
              daftar_tahun_ajaran='$daftar_tahun_ajaran', 
              semester='$semester', 
              daftar_jurusan='$daftar_jurusan', 
              kepala_sekolah='$kepala_sekolah', 
              nip_kepala='$nip_kepala',
              email='$email', 
              telepon='$telepon', 
              alamat='$alamat',
              nama_yayasan='$nama_yayasan' 
              $logo_query
              WHERE id=1";
              
    if ($conn->query($query)) {
        $_SESSION['success'] = 'Pengaturan ' . LBL_INSTANSI . ' berhasil diperbarui!';
    } else {
        $_SESSION['error'] = 'Gagal menyimpan pengaturan.';
    }
    header("Location: pengaturan.php");
    exit;
}

$result = $conn->query("SELECT * FROM pengaturan_sekolah WHERE id=1");
$p = $result->fetch_assoc();

// If empty, initialize with current year
if(empty($p['daftar_tahun_ajaran'])) {
    $initial_year = $p['tahun_ajaran'] ?? date('Y').'/'.(date('Y')+1);
    $conn->query("UPDATE pengaturan_sekolah SET daftar_tahun_ajaran='$initial_year' WHERE id=1");
    $p['daftar_tahun_ajaran'] = $initial_year;
}

$tahun_list = array_map('trim', explode(',', $p['daftar_tahun_ajaran']));

$page_title = 'Pengaturan Instansi';
include '../layouts/header.php'; 
?>

<div class="container-fluid bg-transparent p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><i class='bx bx-cog text-secondary me-2'></i> Pengaturan Instansi</h3>
            <p class="text-muted mb-0">Kelola informasi dasar, tahun akademik, program studi, dan jenis instansi.</p>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <!-- Kolom Kiri: Akademik & Profil -->
            <div class="col-12 col-lg-8">
                <!-- Panel Akademik -->
                <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
                    <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-book-bookmark text-primary me-2'></i> Pengaturan Akademik</h5>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-medium text-muted">Daftar Tahun Ajaran</label>
                            <!-- Hidden input -->
                            <input type="hidden" name="daftar_tahun_ajaran" id="hiddenTahun" value="<?php echo htmlspecialchars($p['daftar_tahun_ajaran']); ?>">
                            
                            <div class="input-group mb-3">
                                <input type="text" id="inputNewTahun" class="form-control" placeholder="Contoh: 2024/2025..." onkeypress="if(event.key === 'Enter') { event.preventDefault(); addTahun(); }">
                                <button type="button" class="btn btn-outline-primary fw-medium px-4" onclick="addTahun()"><i class='bx bx-plus me-1'></i> Tambah</button>
                            </div>
                            
                            <ul class="list-group mb-3" id="listTahun" style="max-height: 150px; overflow-y: auto;">
                                <!-- Rendered by JS -->
                            </ul>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium text-muted">Tahun Ajaran Aktif</label>
                            <select name="tahun_ajaran" id="selectTahunAktif" class="form-select" required>
                                <!-- Rendered by JS based on list -->
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium text-muted">Semester Berjalan</label>
                            <select name="semester" class="form-select" required>
                                <option value="Ganjil" <?php echo $p['semester'] == 'Ganjil' ? 'selected' : ''; ?>>Semester Ganjil</option>
                                <option value="Genap" <?php echo $p['semester'] == 'Genap' ? 'selected' : ''; ?>>Semester Genap</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-medium text-muted">Daftar Jurusan / Program Studi</label>
                            
                            <!-- Input Add -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <input type="text" id="inputNewJurusan" class="form-control shadow-sm" placeholder="Nama Program Studi (misal: TEKNIK INFORMATIKA)">
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group shadow-sm">
                                        <input type="text" id="inputNewKaprodi" class="form-control" placeholder="Nama KAPRODI">
                                        <button type="button" class="btn btn-primary fw-medium px-4" onclick="addJurusan()"><i class='bx bx-plus me-1'></i> Tambah</button>
                                    </div>
                                </div>
                            </div>

                            <!-- List -->
                            <ul class="list-group shadow-sm" id="listJurusan" style="max-height: 300px; overflow-y: auto;">
                                <!-- Item list will be rendered by JS -->
                            </ul>
                            <small class="text-muted d-block mt-2"><i class='bx bx-info-circle'></i> Jurusan yang ada di daftar ini akan muncul saat registrasi <?php echo LBL_SISWA; ?>.</small>
                        </div>
                    </div>
                </div>

                <!-- Panel Profil Institusi -->
                <div class="bg-white p-4 rounded-4 border shadow-sm">
                    <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-buildings text-primary me-2'></i> Identitas Institusi</h5>
                    
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <div class="mb-2">
                                <label class="small fw-bold text-muted d-block mb-2">Logo Saat Ini</label>
                                <?php if (!empty($p['logo'])): ?>
                                    <img src="../<?php echo $p['logo']; ?>" class="img-thumbnail shadow-sm rounded-3" style="max-height: 120px; object-fit: contain;">
                                <?php else: ?>
                                    <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center text-muted mx-auto" style="width: 120px; height: 120px;">
                                        <i class='bx bx-image-alt fs-1'></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-medium text-muted">Ganti Logo <?php echo LBL_INSTANSI; ?></label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <small class="text-muted mt-1 d-block"><i class='bx bx-info-circle'></i> Gunakan file gambar (PNG/JPG) dengan latar belakang transparan/putih untuk hasil terbaik di laporan.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Tipe Instansi</label>
                        <select name="tipe_instansi" class="form-select" required>
                            <option value="Sekolah" <?php echo ($p['tipe_instansi'] == 'Sekolah') ? 'selected' : ''; ?>>Sekolah (Siswa & Guru)</option>
                            <option value="Kampus" <?php echo ($p['tipe_instansi'] == 'Kampus') ? 'selected' : ''; ?>>Kampus (Mahasiswa & Dosen)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Nama Instansi (KAMPUS / SEKOLAH)</label>
                        <input type="text" name="nama_sekolah" class="form-control" value="<?php echo $p['nama_sekolah']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Nama Yayasan / Kementerian / Dinas (KOP Baris 2)</label>
                        <input type="text" name="nama_yayasan" class="form-control" value="<?php echo $p['nama_yayasan'] ?? 'YAYASAN PENDIDIKAN DAN SOSIAL RMP GROUP INDONESIA'; ?>" placeholder="Contoh: YAYASAN PENDIDIKAN ...">
                    </div>
                    <div class="row g-4 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-muted">Nama <?php echo LBL_INSTANSI == 'Kampus' ? 'Rektor' : 'Kepala Sekolah'; ?></label>
                            <input type="text" name="kepala_sekolah" class="form-control" value="<?php echo $p['kepala_sekolah']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-muted">NIP / NIDN</label>
                            <input type="text" name="nip_kepala" class="form-control" value="<?php echo $p['nip_kepala'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="3" required><?php echo $p['alamat']; ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Kontak & Simpan -->
            <div class="col-12 col-lg-4">
                <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
                    <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-phone-call text-success me-2'></i> Kontak <?php echo LBL_INSTANSI; ?></h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Email Resmi</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $p['email']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Nomor Telepon</label>
                        <input type="text" name="telepon" class="form-control" value="<?php echo $p['telepon']; ?>" required>
                    </div>
                </div>

                <!-- Action Card -->
                <div class="bg-light p-4 rounded-4 border">
                    <h6 class="fw-bold mb-3"><i class='bx bx-check-shield text-primary me-2'></i> Konfirmasi Simpan</h6>
                    <p class="text-muted small mb-4">Pastikan data tahun ajaran dan semester sudah benar karena akan mempengaruhi rekapitulasi data akademik.</p>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm mb-2"><i class='bx bx-save me-1'></i> Simpan Pengaturan</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary w-100 py-2 fw-medium">Batal</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const tahunArray = <?php echo json_encode($tahun_list); ?>;
let jurusanArray = <?php 
    $j_res = $conn->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
    $j_list = [];
    while($j_row = $j_res->fetch_assoc()) $j_list[] = $j_row;
    echo json_encode($j_list); 
?>;
let activeTahun = "<?php echo $p['tahun_ajaran']; ?>";
let isModified = false;
let isJurusanModified = false;

function renderTahun() {
    const list = document.getElementById('listTahun');
    const select = document.getElementById('selectTahunAktif');
    list.innerHTML = '';
    select.innerHTML = '';
    
    tahunArray.forEach((tahun, index) => {
        list.innerHTML += `
            <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 mb-1 rounded-3 py-2">
                <span class="small fw-medium text-dark"><i class='bx bx-calendar text-primary me-2'></i> ${tahun}</span>
                <button type="button" class="btn btn-sm text-danger p-0" onclick="removeTahun(${index})">
                    <i class='bx bx-x fs-5'></i>
                </button>
            </li>
        `;
        
        const option = document.createElement('option');
        option.value = tahun;
        option.text = tahun;
        if(tahun === activeTahun) option.selected = true;
        select.appendChild(option);
    });
    
    document.getElementById('hiddenTahun').value = tahunArray.join(', ');
    showSaveWarning();
}

function addTahun() {
    const input = document.getElementById('inputNewTahun');
    const val = input.value.trim();
    if(val !== '' && !tahunArray.includes(val)) {
        tahunArray.push(val);
        input.value = '';
        isModified = true;
        renderTahun();
    }
}

function removeTahun(index) {
    if(tahunArray.length > 1) {
        tahunArray.splice(index, 1);
        isModified = true;
        renderTahun();
    } else {
        Swal.fire('Error', 'Minimal harus ada satu tahun ajaran.', 'error');
    }
}

function showSaveWarning() {
    if(isModified) {
        // Just internal flag, actual warning on renderJurusan or similar
    }
}

function renderJurusan() {
    const list = document.getElementById('listJurusan');
    list.innerHTML = '';
    
    if (jurusanArray.length === 0) {
        list.innerHTML = '<li class="list-group-item text-muted text-center py-3">Belum ada jurusan. Silakan tambahkan.</li>';
    } else {
        jurusanArray.forEach((jurusan, index) => {
            list.innerHTML += `
                <li class="list-group-item d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                    <div>
                        <span class="fw-bold text-dark d-block mb-1"><i class='bx bxs-graduation text-primary me-2'></i> ${jurusan.nama_jurusan}</span>
                        <small class="text-muted"><i class='bx bx-user-circle me-1'></i> KAPRODI: <span class="fw-medium text-secondary">${jurusan.kaprodi || '-'}</span></small>
                    </div>
                    <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="removeJurusan(${index}, ${jurusan.id || 0})" title="Hapus Jurusan">
                        <i class='bx bx-trash fs-5'></i>
                    </button>
                </li>
            `;
        });
    }
}

function addJurusan() {
    const inputJ = document.getElementById('inputNewJurusan');
    const inputK = document.getElementById('inputNewKaprodi');
    const valJ = inputJ.value.trim();
    const valK = inputK.value.trim();
    
    if(valJ !== '') {
        // Use AJAX to save to database immediately to keep it simple and synced
        const formData = new FormData();
        formData.append('nama_jurusan', valJ);
        formData.append('kaprodi', valK);
        formData.append('ajax_add_jurusan', '1');

        fetch('pengaturan.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                jurusanArray.push({ id: data.id, nama_jurusan: valJ, kaprodi: valK });
                inputJ.value = '';
                inputK.value = '';
                renderJurusan();
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Berhasil ditambahkan', showConfirmButton: false, timer: 1500 });
            } else {
                Swal.fire('Error', data.message || 'Gagal menambahkan jurusan', 'error');
            }
        });
    }
}

function removeJurusan(index, id) {
    Swal.fire({
        title: 'Hapus Jurusan?',
        text: "Data akan dihapus permanen.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('ajax_delete_jurusan', '1');

            fetch('pengaturan.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    jurusanArray.splice(index, 1);
                    renderJurusan();
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Berhasil dihapus', showConfirmButton: false, timer: 1500 });
                }
            });
        }
    });
}

// Initial render
document.addEventListener('DOMContentLoaded', () => {
    renderTahun();
    renderJurusan();
});
</script>

<?php include '../layouts/footer.php'; ?>
