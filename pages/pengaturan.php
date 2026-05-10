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

// Ensure column exists
$conn->query("ALTER TABLE pengaturan_sekolah ADD COLUMN IF NOT EXISTS daftar_tahun_ajaran TEXT");
$conn->query("ALTER TABLE pengaturan_sekolah ADD COLUMN IF NOT EXISTS logo VARCHAR(255)");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_sekolah = $conn->real_escape_string($_POST['nama_sekolah']);
    $tahun_ajaran = $conn->real_escape_string($_POST['tahun_ajaran']);
    $daftar_tahun_ajaran = $conn->real_escape_string($_POST['daftar_tahun_ajaran']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $daftar_jurusan = $conn->real_escape_string($_POST['daftar_jurusan']);
    $kepala_sekolah = $conn->real_escape_string($_POST['kepala_sekolah']);
    $email = $conn->real_escape_string($_POST['email']);
    $telepon = $conn->real_escape_string($_POST['telepon']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    
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
              nama_sekolah='$nama_sekolah', 
              tahun_ajaran='$tahun_ajaran', 
              daftar_tahun_ajaran='$daftar_tahun_ajaran', 
              semester='$semester', 
              daftar_jurusan='$daftar_jurusan', 
              kepala_sekolah='$kepala_sekolah', 
              email='$email', 
              telepon='$telepon', 
              alamat='$alamat' 
              $logo_query
              WHERE id=1";
              
    if ($conn->query($query)) {
        $_SESSION['success'] = 'Pengaturan Sekolah berhasil diperbarui!';
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

$page_title = 'Pengaturan Sekolah';
include '../layouts/header.php'; 
?>

<div class="container-fluid bg-transparent p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><i class='bx bx-cog text-secondary me-2'></i> Pengaturan Sekolah</h3>
            <p class="text-muted mb-0">Kelola informasi dasar, tahun akademik, dan program studi.</p>
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
                            
                            <!-- Hidden input -->
                            <input type="hidden" name="daftar_jurusan" id="hiddenJurusan" value="<?php echo htmlspecialchars($p['daftar_jurusan']); ?>">
                            
                            <!-- Input Add -->
                            <div class="input-group mb-3">
                                <input type="text" id="inputNewJurusan" class="form-control" placeholder="Ketik nama jurusan baru (misal: IPA)..." onkeypress="if(event.key === 'Enter') { event.preventDefault(); addJurusan(); }">
                                <button type="button" class="btn btn-outline-primary fw-medium px-4" onclick="addJurusan()"><i class='bx bx-plus me-1'></i> Tambah</button>
                            </div>

                            <!-- List -->
                            <ul class="list-group" id="listJurusan" style="max-height: 200px; overflow-y: auto;">
                                <!-- Item list will be rendered by JS -->
                            </ul>
                            <small class="text-muted d-block mt-2"><i class='bx bx-info-circle'></i> Jurusan yang ada di daftar ini bisa dipilih saat menambah data Siswa.</small>
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
                            <label class="form-label fw-medium text-muted">Ganti Logo Sekolah</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <small class="text-muted mt-1 d-block"><i class='bx bx-info-circle'></i> Gunakan file gambar (PNG/JPG) dengan latar belakang transparan/putih untuk hasil terbaik di laporan.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Nama Sekolah / Instansi</label>
                        <input type="text" name="nama_sekolah" class="form-control" value="<?php echo $p['nama_sekolah']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Nama Kepala Sekolah</label>
                        <input type="text" name="kepala_sekolah" class="form-control" value="<?php echo $p['kepala_sekolah']; ?>" required>
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
                    <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-phone-call text-success me-2'></i> Kontak Sekolah</h5>
                    
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
let jurusanArray = document.getElementById('hiddenJurusan').value.split(',').map(j => j.trim()).filter(j => j !== '');
let tahunArray = document.getElementById('hiddenTahun').value.split(',').map(t => t.trim()).filter(t => t !== '');
let activeTahun = "<?php echo $p['tahun_ajaran']; ?>";

let isModified = false;

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
                <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 mb-2 rounded-3">
                    <span class="fw-medium text-dark"><i class='bx bxs-graduation text-primary me-2'></i> ${jurusan}</span>
                    <button type="button" class="btn btn-sm btn-light text-danger border-0 hover-danger" onclick="removeJurusan(${index})" title="Hapus Jurusan">
                        <i class='bx bx-trash fs-5'></i>
                    </button>
                </li>
            `;
        });
    }
    
    // Update hidden input for PHP POST
    document.getElementById('hiddenJurusan').value = jurusanArray.join(', ');

    // Show warning if user modified the list but hasn't saved
    if(isJurusanModified) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Jangan lupa klik tombol "Simpan Pengaturan" di bawah agar jurusan tersimpan!',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }
}

function addJurusan() {
    const input = document.getElementById('inputNewJurusan');
    const val = input.value.trim();
    if(val !== '') {
        // Prevent duplicate
        if (!jurusanArray.map(j => j.toLowerCase()).includes(val.toLowerCase())) {
            jurusanArray.push(val);
            input.value = '';
            isJurusanModified = true;
            renderJurusan();
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Duplikat',
                text: 'Jurusan tersebut sudah ada di daftar!'
            });
        }
    }
}

function removeJurusan(index) {
    Swal.fire({
        title: 'Hapus Jurusan?',
        text: "Data akan dihapus dari daftar ini.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            jurusanArray.splice(index, 1);
            isJurusanModified = true;
            renderJurusan();
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
