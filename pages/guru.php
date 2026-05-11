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

// Hanya Admin yang boleh akses manajemen guru
if ($_SESSION['user']['role'] != 'admin') {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini!';
    header("Location: dashboard.php");
    exit;
}

$user = $_SESSION['user'];
$role = $user['role'];
$username = $user['username'];

$action = $_GET['action'] ?? 'list';

if ($action == 'delete' && isset($_GET['nip'])) {
    $nip = $conn->real_escape_string($_GET['nip']);
    $conn->query("DELETE FROM users WHERE username='$nip' AND role='guru'");
    $conn->query("DELETE FROM guru WHERE nip='$nip'");
    $_SESSION['success'] = 'Data ' . LBL_GURU . ' Berhasil Dihapus!';
    header("Location: guru.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nip = $conn->real_escape_string($_POST['nip']);
    $nama = $conn->real_escape_string($_POST['nama']);
    $telp = $conn->real_escape_string($_POST['telp']);
    $agama = $conn->real_escape_string($_POST['agama']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    $status = $conn->real_escape_string($_POST['status'] ?? 'Aktif');
    
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_ext)) {
            $_SESSION['error'] = 'Format file tidak diizinkan! Gunakan JPG, PNG, atau WEBP.';
            header("Location: guru.php?action=" . $action);
            exit;
        }

        if (!is_dir('../uploads')) mkdir('../uploads');
        $foto = 'uploads/guru_' . $nip . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], '../' . $foto);
    }

    if ($action == 'add') {
        $query = "INSERT INTO guru (nip, nama, telp, agama, alamat, foto, status) 
                  VALUES ('$nip', '$nama', '$telp', '$agama', '$alamat', '$foto', '$status')";
        if ($conn->query($query)) {
            // Create user for this guru
            $conn->query("INSERT INTO users (username, password, role) VALUES ('$nip', MD5('12345'), 'guru')");
            $_SESSION['success'] = 'Data ' . LBL_GURU . ' Berhasil Ditambahkan!';
        }
        header("Location: guru.php");
        exit;
    } elseif ($action == 'edit') {
        $old_nip = $conn->real_escape_string($_POST['old_nip']);
        $query = "UPDATE guru SET nip='$nip', nama='$nama', telp='$telp', agama='$agama', alamat='$alamat', status='$status'";
        if ($foto != '') {
            $query .= ", foto='$foto'";
        }
        $query .= " WHERE nip='$old_nip'";
        if ($conn->query($query)) {
            // Update username if NIP changed
            if ($nip != $old_nip) {
                $conn->query("UPDATE users SET username='$nip' WHERE username='$old_nip' AND role='guru'");
            }
            $_SESSION['success'] = 'Data ' . LBL_GURU . ' Berhasil Diperbarui!';
        }
        header("Location: guru.php");
        exit;
    }
}

$page_title = 'Data ' . LBL_GURU;
include '../layouts/header.php'; 
?>

<div class="container-fluid bg-transparent p-0">
    <?php if ($action == 'list'): ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bxs-chalkboard text-primary me-2'></i> Manajemen <?php echo LBL_GURU; ?></h3>
                <p class="text-muted mb-0 small">Kelola data <?php echo strtolower(LBL_GURU); ?>, kontak, dan status keaktifan.</p>
            </div>
            <a href="guru.php?action=add" class="btn fw-bold rounded-3 px-4 shadow-sm text-white d-flex align-items-center justify-content-center" 
               style="background-color: #0f172a; border: none; transition: all 0.3s ease; height: 45px;" 
               onmouseover="this.style.backgroundColor='#10b981'; this.style.transform='translateY(-2px)'" 
               onmouseout="this.style.backgroundColor='#0f172a'; this.style.transform='translateY(0)'">
                <i class='bx bx-plus-circle me-2 fs-5'></i> <?php echo LBL_GURU; ?> Baru
            </a>
        </div>

        <!-- Table Panel -->
        <div class="bg-white p-4 rounded-4 border shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="fw-semibold pb-3">Profil <?php echo LBL_GURU; ?></th>
                            <th class="fw-semibold pb-3 d-none d-md-table-cell">Kontak & Agama</th>
                            <th class="fw-semibold pb-3 d-none d-sm-table-cell">Status</th>
                            <th class="fw-semibold pb-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT g.*, 
                                  (SELECT GROUP_CONCAT(nama_mapel SEPARATOR ', ') FROM mapel WHERE nip_guru = g.nip) as list_mapel 
                                  FROM guru g";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <!-- Profil Guru Column -->
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if($row['foto']): ?>
                                        <img src="../<?php echo $row['foto']; ?>" width="40" height="40" class="rounded-circle shadow-sm me-2 me-md-3" style="object-fit:cover; border: 2px solid #fff;">
                                    <?php else: ?>
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold me-2 me-md-3 shadow-sm border border-white" style="width:40px;height:40px; font-size:1rem;">
                                            <?php echo strtoupper(substr($row['nama'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.9rem;"><?php echo $row['nama']; ?></span>
                                        <small class="text-muted" style="font-size: 0.75rem;">NIP: #<?php echo $row['nip']; ?></small>
                                    </div>
                                </div>
                            </td>

                            <!-- Kontak Column -->
                            <td class="d-none d-md-table-cell">
                                <div class="mb-1 fw-medium text-dark" style="font-size: 0.9rem;">
                                    <i class='bx bx-phone me-1'></i> <?php echo $row['telp']; ?>
                                </div>
                                <small class="text-muted d-block"><i class='bx bx-moon me-1'></i> <?php echo $row['agama']; ?></small>
                            </td>

                            <!-- Status Column -->
                            <td class="d-none d-sm-table-cell">
                                <?php if($row['status'] == 'Aktif'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill small"><i class='bx bx-check-circle'></i> Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1 rounded-pill small"><i class='bx bx-x-circle'></i> Berhenti</span>
                                <?php endif; ?>
                            </td>

                            <!-- Aksi Column -->
                            <td class="text-end">
                                <?php $guru_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>
                                <div class="d-flex justify-content-end gap-3 align-items-center">
                                    <button type="button" class="btn btn-link text-info p-0 text-decoration-none" title="Lihat Detail" data-guru='<?php echo $guru_json; ?>' onclick="showDetailGuru(this)">
                                        <i class='bx bx-show fs-4'></i>
                                    </button>
                                    <a href="guru.php?action=edit&nip=<?php echo $row['nip']; ?>" class="btn btn-link text-warning p-0 text-decoration-none" title="Edit Data">
                                        <i class='bx bx-edit-alt fs-4'></i>
                                    </a>
                                    <a href="guru.php?action=delete&nip=<?php echo $row['nip']; ?>" class="btn btn-link text-danger p-0 text-decoration-none btn-delete" title="Hapus Data">
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
        <!-- Modal Detail Guru -->
        <div class="modal fade" id="modalDetailGuru" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-detail-custom modal-dialog-centered">
            <div class="modal-content overflow-hidden border-0 shadow-lg rounded-4">
              <!-- Banner -->
              <div class="position-relative" style="height: 90px; background: linear-gradient(135deg, #1e293b, #10b981);">
                  <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                  <i class='bx bxs-quote-right position-absolute' style="font-size: 5rem; color: rgba(255,255,255,0.05); right: -10px; bottom: -10px;"></i>
              </div>
              <div class="modal-body px-4 pb-3 pt-0 text-center">
                  <!-- Profile Picture overlapping banner -->
                  <div class="position-relative mx-auto mb-2" style="width: 80px; height: 80px; margin-top: -40px; z-index: 2;">
                      <img id="detailFoto" src="" class="rounded-circle shadow-sm bg-white p-1" style="width: 100%; height: 100%; object-fit: cover; display:none;">
                      <div id="detailFotoPlaceholder" class="rounded-circle shadow-sm bg-white p-1 flex-column align-items-center justify-content-center text-success fw-bold" style="width: 100%; height: 100%; font-size: 3rem; display:none;">
                          <span id="detailInitial">G</span>
                      </div>
                  </div>
                  <h5 class="fw-bold text-dark mb-1" id="detailNama">Nama <?php echo LBL_GURU; ?></h5>
                  <p class="text-muted mb-2" style="font-size: 0.85rem;">
                      <i class='bx bx-id-card me-1'></i> NIP: <span class="fw-bold text-dark" id="detailNis">12345</span>
                      <button class="btn btn-link p-0 ms-1 text-primary" onclick="copyNip()" title="Salin NIP">
                          <i class='bx bx-copy'></i>
                      </button>
                  </p>
                  
                  <div class="d-flex justify-content-center gap-2 mb-3">
                      <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill shadow-sm"><i class='bx bxs-briefcase me-1'></i> Tenaga Pengajar</span>
                  </div>

                  <!-- Information Grid -->
                  <div class="row g-2 text-start mb-3">
                      <div class="col-6">
                          <div class="p-2 bg-light rounded-4 border border-light-subtle h-100 transition-hover">
                              <small class="text-muted d-block mb-0" style="font-size: 0.75rem;"><i class='bx bx-phone text-success me-1'></i> Telepon</small>
                              <span class="fw-medium text-dark" style="font-size: 0.85rem;" id="detailNoHp">081234</span>
                          </div>
                      </div>
                      <div class="col-6">
                          <div class="p-2 bg-light rounded-4 border border-light-subtle h-100 transition-hover">
                              <small class="text-muted d-block mb-0" style="font-size: 0.75rem;"><i class='bx bx-moon text-success me-1'></i> Agama</small>
                              <span class="fw-medium text-dark" style="font-size: 0.85rem;" id="detailAgama">Islam</span>
                          </div>
                      </div>
                      <div class="col-12">
                          <div class="p-2 bg-light rounded-4 border border-light-subtle mb-2 transition-hover">
                              <small class="text-muted d-block mb-0" style="font-size: 0.75rem;"><i class='bx bx-book-open text-success me-1'></i> Mengampu Mata Pelajaran</small>
                              <span class="fw-bold text-dark" style="font-size: 0.85rem;" id="detailMapel">Belum ada mapel</span>
                          </div>
                      </div>
                      <div class="col-12">
                          <div class="p-2 bg-light rounded-4 border border-light-subtle transition-hover">
                              <small class="text-muted d-block mb-0" style="font-size: 0.75rem;"><i class='bx bx-map-pin text-success me-1'></i> Alamat</small>
                              <span class="fw-medium text-dark" style="font-size: 0.85rem;" id="detailAlamat">Jl. Merdeka</span>
                          </div>
                      </div>
                  </div>

                  <div class="d-flex justify-content-between align-items-center p-3 rounded-4 shadow-sm" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                      <div class="text-start">
                          <small class="text-muted d-block fw-medium mb-1">Status Kepegawaian</small>
                          <div id="detailStatus"></div>
                      </div>
                  </div>
              </div>
            </div>
          </div>
        </div>

        <script>
        function showDetailGuru(btn) {
            const data = JSON.parse(btn.getAttribute('data-guru'));
            document.getElementById('detailNama').innerText = data.nama;
            document.getElementById('detailNis').innerText = data.nip;
            document.getElementById('detailAgama').innerText = data.agama;
            document.getElementById('detailNoHp').innerText = data.telp;
            document.getElementById('detailAlamat').innerText = data.alamat;
            document.getElementById('detailMapel').innerText = data.list_mapel ? data.list_mapel : 'Belum ada mapel';
            
            const statusContainer = document.getElementById('detailStatus');
            if(data.status === 'Aktif') {
                statusContainer.innerHTML = `<span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm"><i class='bx bx-check-circle me-1'></i> Aktif Mengajar</span>`;
            } else {
                statusContainer.innerHTML = `<span class="badge bg-danger text-white px-3 py-2 rounded-pill shadow-sm"><i class='bx bx-x-circle me-1'></i> Berhenti</span>`;
            }

            const imgEl = document.getElementById('detailFoto');
            const placeholderEl = document.getElementById('detailFotoPlaceholder');
            if(data.foto) {
                imgEl.src = '../' + data.foto;
                imgEl.style.display = 'block';
                placeholderEl.style.display = 'none';
            } else {
                imgEl.style.display = 'none';
                document.getElementById('detailInitial').innerText = data.nama.charAt(0).toUpperCase();
                placeholderEl.style.display = 'flex';
            }
            new bootstrap.Modal(document.getElementById('modalDetailGuru')).show();
        }
        function copyNip() {
            const nip = document.getElementById('detailNis').innerText;
            navigator.clipboard.writeText(nip).then(() => {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'NIP berhasil disalin!', showConfirmButton: false, timer: 2000 });
            });
        }
        </script>

    <?php elseif ($action == 'add' || $action == 'edit'): 
        $row = null;
        if ($action == 'edit' && isset($_GET['nip'])) {
            $nip = $conn->real_escape_string($_GET['nip']);
            $result = $conn->query("SELECT * FROM guru WHERE nip='$nip'");
            $row = $result->fetch_assoc();
        }
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bx-edit text-success me-2'></i> <?php echo $action == 'add' ? 'Registrasi ' . LBL_GURU . ' Baru' : 'Perbarui Data ' . LBL_GURU; ?></h3>
                <p class="text-muted mb-0 small">Lengkapi informasi data <?php echo strtolower(LBL_GURU); ?> di bawah ini.</p>
            </div>
            <a href="guru.php" class="btn btn-outline-secondary fw-medium rounded-3 px-4 shadow-sm">
                <i class='bx bx-arrow-back me-1'></i> Kembali
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <?php if($action == 'edit'): ?>
                <input type="hidden" name="old_nip" value="<?php echo $row['nip']; ?>">
            <?php endif; ?>
            
            <div class="bg-white p-4 p-lg-5 rounded-4 border shadow-sm">
                <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-user-pin text-success me-2'></i> Informasi Identitas</h5>
                <div class="row g-4 mb-5">
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-medium text-muted">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" name="nip" class="form-control <?php echo $action == 'edit' ? 'bg-light' : ''; ?>" value="<?php echo $row['nip'] ?? ''; ?>" required <?php echo $action == 'edit' ? 'readonly' : ''; ?>>
                    </div>
                    <div class="col-md-6 col-lg-8">
                        <label class="form-label fw-medium text-muted">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?php echo $row['nama'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-medium text-muted">Agama</label>
                        <select name="agama" class="form-select" required>
                            <option value="">-- Pilih Agama --</option>
                            <?php 
                            $agama_list = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
                            foreach($agama_list as $a): 
                                $selected = (isset($row['agama']) && $row['agama'] == $a) ? 'selected' : '';
                                echo "<option value=\"$a\" $selected>$a</option>";
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-medium text-muted">Nomor Telepon / WA</label>
                        <input type="text" name="telp" class="form-control" value="<?php echo $row['telp'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-medium text-muted">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="Aktif" <?php echo (isset($row['status']) && $row['status'] == 'Aktif') ? 'selected' : ''; ?>>Aktif Mengajar</option>
                            <option value="Berhenti" <?php echo (isset($row['status']) && $row['status'] == 'Berhenti') ? 'selected' : ''; ?>>Berhenti</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium text-muted">Alamat Domisili</label>
                        <textarea name="alamat" class="form-control" rows="2" required><?php echo $row['alamat'] ?? ''; ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium text-muted">Unggah Foto <small>(Opsional)</small></label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-3 border-top">
                    <a href="guru.php" class="btn btn-light border px-4 py-2 fw-medium text-muted">Batal</a>
                    <button type="submit" class="btn px-4 py-2 fw-medium text-white shadow-sm" style="background-color: #0f172a; transition: all 0.3s;" onmouseover="this.style.backgroundColor='#10b981'" onmouseout="this.style.backgroundColor='#0f172a'">
                        <i class='bx bx-save me-1'></i> Simpan Data <?php echo LBL_GURU; ?>
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>
