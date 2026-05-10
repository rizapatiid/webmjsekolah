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

$user = $_SESSION['user'];
$role = $user['role'];
$username = $user['username'];
$user_id = $user['id'];

// Get detailed data based on role
$detail = null;
if ($role == 'siswa') {
    $res = $conn->query("SELECT * FROM siswa WHERE nis = '$username'");
    $detail = $res->fetch_assoc();
} elseif ($role == 'guru') {
    $res = $conn->query("SELECT * FROM guru WHERE nip = '$username'");
    $detail = $res->fetch_assoc();
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['change_password']) || isset($_POST['change_password_direct']))) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $is_direct = isset($_POST['change_password_direct']);

    $can_update = false;
    
    if ($is_direct) {
        // From Popup: We already know they have default password
        if ($new_pass === $confirm_pass) {
            $can_update = true;
        } else {
            $_SESSION['error'] = 'Konfirmasi password tidak cocok!';
        }
    } else {
        // From Manual Form: Must check current password
        $current_pass = MD5($_POST['current_password']);
        $check = $conn->query("SELECT id FROM users WHERE id = $user_id AND password = '$current_pass'");
        if ($check->num_rows > 0) {
            if ($new_pass === $confirm_pass) {
                $can_update = true;
            } else {
                $_SESSION['error'] = 'Konfirmasi password tidak cocok!';
            }
        } else {
            $_SESSION['error'] = 'Password saat ini salah!';
        }
    }

    if ($can_update) {
        $hashed_new = MD5($new_pass);
        $conn->query("UPDATE users SET password = '$hashed_new' WHERE id = $user_id");
        
        // Update session so checks pass
        $_SESSION['user']['password'] = $hashed_new;
        
        $_SESSION['success'] = 'Password berhasil diperbarui! Keamanan akun Anda kini lebih terjaga.';
        header("Location: profil.php");
        exit;
    }
}

$page_title = 'Profil Saya';
include '../layouts/header.php';
?>

<div class="container-fluid p-0">
    <!-- Profile Header / Cover -->
    <div class="position-relative mb-5">
        <div class="rounded-4 shadow-sm overflow-hidden" style="height: 180px; background: linear-gradient(135deg, #0f172a, #3b82f6);">
            <div class="h-100 w-100 opacity-25" style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png');"></div>
        </div>
        
        <!-- Profile Info Overlap -->
        <div class="position-absolute start-0 bottom-0 w-100 px-4 translate-middle-y" style="margin-bottom: -50px;">
            <div class="d-flex align-items-end">
                <div class="position-relative">
                    <?php if (isset($detail['foto']) && $detail['foto']): ?>
                        <img src="../<?php echo $detail['foto']; ?>" class="rounded-circle border border-4 border-white shadow-lg" style="width: 120px; height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle border border-4 border-white shadow-lg bg-white d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 120px; height: 120px; font-size: 3rem;">
                            <?php echo strtoupper(substr($detail['nama'] ?? $username, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-2 shadow-sm" title="Online"></span>
                </div>
                <div class="ms-4 mb-2 pb-1">
                    <h3 class="fw-bold text-dark mb-0"><?php echo $detail['nama'] ?? ucfirst($username); ?></h3>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill small fw-bold">
                            <i class='bx bxs-badge-check me-1'></i> <?php echo strtoupper($role); ?>
                        </span>
                        <span class="text-muted small fw-medium"><i class='bx bx-id-card me-1'></i> ID: <?php echo $username; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5 pt-3">
        <div class="col-lg-8">
            <!-- Tabs Navigation -->
            <div class="bg-white rounded-4 shadow-sm border p-2 mb-4">
                <ul class="nav nav-pills nav-fill" id="profileTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-3 py-2 fw-bold" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                            <i class='bx bx-user me-2'></i> Informasi Personal
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-3 py-2 fw-bold" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                            <i class='bx bx-shield-quarter me-2'></i> Keamanan Akun
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content" id="profileTabContent">
                <!-- Info Tab -->
                <div class="tab-pane fade show active" id="info" role="tabpanel">
                    <div class="bg-white rounded-4 shadow-sm border p-4 p-lg-5">
                        <h5 class="fw-bold text-dark mb-4 border-bottom pb-3"><i class='bx bx-detail text-primary me-2'></i> Detail Informasi</h5>
                        
                        <div class="row g-4">
                            <?php if ($role == 'siswa'): ?>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">NIS (Nomor Induk Siswa)</label>
                                    <div class="p-3 bg-light rounded-3 border-start border-4 border-primary"><?php echo $detail['nis']; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Nama Lengkap</label>
                                    <div class="p-3 bg-light rounded-3 border-start border-4 border-primary"><?php echo $detail['nama']; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Kelas & Jurusan</label>
                                    <div class="p-3 bg-light rounded-3"><?php echo $detail['kelas']; ?> - <?php echo $detail['jurusan']; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Agama</label>
                                    <div class="p-3 bg-light rounded-3"><?php echo $detail['agama']; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Nomor HP</label>
                                    <div class="p-3 bg-light rounded-3"><?php echo $detail['no_hp']; ?></div>
                                </div>
                                <div class="col-12">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Alamat</label>
                                    <div class="p-3 bg-light rounded-3"><?php echo $detail['alamat']; ?></div>
                                </div>
                            <?php elseif ($role == 'guru'): ?>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">NIP (Nomor Induk Pegawai)</label>
                                    <div class="p-3 bg-light rounded-3 border-start border-4 border-success"><?php echo $detail['nip']; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Nama Lengkap</label>
                                    <div class="p-3 bg-light rounded-3 border-start border-4 border-success"><?php echo $detail['nama']; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Telepon</label>
                                    <div class="p-3 bg-light rounded-3"><?php echo $detail['telp']; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Agama</label>
                                    <div class="p-3 bg-light rounded-3"><?php echo $detail['agama']; ?></div>
                                </div>
                                <div class="col-12">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Alamat</label>
                                    <div class="p-3 bg-light rounded-3"><?php echo $detail['alamat']; ?></div>
                                </div>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info border-0 shadow-sm rounded-4">
                                        <i class='bx bx-info-circle me-2'></i> Anda login sebagai Administrator. Informasi detail terbatas pada data akun pengguna.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Username</label>
                                    <div class="p-3 bg-light rounded-3"><?php echo $username; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Role Akses</label>
                                    <div class="p-3 bg-light rounded-3 fw-bold text-primary"><?php echo strtoupper($role); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Security Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="bg-white rounded-4 shadow-sm border p-4 p-lg-5">
                        <div class="d-flex align-items-center mb-4 border-bottom pb-3">
                            <div class="bg-danger bg-opacity-10 p-2 rounded-3 me-3">
                                <i class='bx bx-lock-alt text-danger fs-4'></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Ganti Password</h5>
                                <small class="text-muted">Pastikan password Anda kuat dan rahasia.</small>
                            </div>
                        </div>

                        <form method="POST" class="row g-4">
                            <div class="col-12">
                                <label class="form-label fw-bold text-muted small">Password Saat Ini</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class='bx bx-key'></i></span>
                                    <input type="password" name="current_password" class="form-control border-start-0" placeholder="Masukkan password lama" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Password Baru</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class='bx bx-lock'></i></span>
                                    <input type="password" name="new_password" class="form-control border-start-0" placeholder="Min. 6 karakter" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class='bx bx-check-shield'></i></span>
                                    <input type="password" name="confirm_password" class="form-control border-start-0" placeholder="Ulangi password baru" required>
                                </div>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" name="change_password" class="btn btn-primary fw-bold px-4 py-2 rounded-3 shadow-sm transition-all hover-scale">
                                    <i class='bx bx-save me-1'></i> Perbarui Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Summary Card -->
            <div class="bg-white rounded-4 shadow-sm border p-4 mb-4">
                <h6 class="fw-bold text-dark mb-4">Ringkasan Aktivitas</h6>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class='bx bx-log-in-circle fs-5'></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Login Terakhir</small>
                        <span class="fw-medium text-dark small"><?php echo date('d M Y, H:i'); ?></span>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class='bx bx-shield-alt-2 fs-5'></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Status Akun</small>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-2 py-0 x-small">Terverifikasi</span>
                    </div>
                </div>
                <div class="pt-3 border-top mt-3">
                    <p class="text-muted small mb-0">Butuh bantuan terkait akun Anda? Hubungi Administrator melalui menu bantuan.</p>
                </div>
            </div>

            <!-- Notice Card -->
            <div class="bg-dark rounded-4 shadow-lg p-4 text-white position-relative overflow-hidden">
                <i class='bx bxs-shield-crown position-absolute' style="font-size: 8rem; color: rgba(255,255,255,0.05); right: -20px; bottom: -20px;"></i>
                <h6 class="fw-bold mb-2">Tips Keamanan</h6>
                <p class="small opacity-75 mb-0">Jangan pernah membagikan password Anda kepada siapa pun, termasuk staf sekolah. Gantilah password Anda secara berkala untuk menjaga keamanan data akademik Anda.</p>
            </div>
        </div>
    </div>
</div>

<style>
.nav-pills .nav-link {
    color: #64748b;
    background: transparent;
    transition: all 0.3s;
}
.nav-pills .nav-link.active {
    background-color: #0f172a;
    color: #fff;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
}
.hover-scale:hover {
    transform: scale(1.02);
}
.transition-all {
    transition: all 0.3s ease;
}
.shadow-hover:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}
.x-small {
    font-size: 0.7rem;
}
</style>

<?php include '../layouts/footer.php'; ?>
