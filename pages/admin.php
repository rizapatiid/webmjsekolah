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

// Hanya Admin yang boleh akses manajemen pengguna
if ($_SESSION['user']['role'] != 'admin') {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini!';
    header("Location: dashboard.php");
    exit;
}

$user = $_SESSION['user'];
$role = $user['role'];
$username = $user['username'];

$action = $_GET['action'] ?? 'list';

if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM users WHERE id=$id");
    $_SESSION['success'] = 'Pengguna Berhasil Dihapus!';
    header("Location: admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $role = $conn->real_escape_string($_POST['role']);
    
    if ($action == 'add') {
        $password = md5($_POST['password']);
        $query = "INSERT INTO users (username, password, role) 
                  VALUES ('$username', '$password', '$role')";
        $conn->query($query);
        $_SESSION['success'] = 'Pengguna Berhasil Ditambahkan!';
        header("Location: admin.php");
        exit;
    } elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        $query = "UPDATE users SET username='$username', role='$role'";
        if (!empty($_POST['password'])) {
            $password = md5($_POST['password']);
            $query .= ", password='$password'";
        }
        $query .= " WHERE id=$id";
        $conn->query($query);
        $_SESSION['success'] = 'Pengguna Berhasil Diperbarui!';
        header("Location: admin.php");
        exit;
    }
}

$page_title = 'Pengelola Pengguna';
include '../layouts/header.php'; 
?>

<div class="container-fluid bg-transparent p-0">
    <?php if ($action == 'list'): ?>
        <!-- Header List -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bxs-user-badge text-primary me-2'></i> Pengelola Pengguna</h3>
                <p class="text-muted mb-0 small">Manajemen hak akses akun login (Admin, Pengajar, Siswa).</p>
            </div>
            <a href="admin.php?action=add" class="btn fw-bold rounded-3 px-4 shadow-sm text-white d-flex align-items-center justify-content-center" 
               style="background-color: #0f172a; border: none; transition: all 0.3s ease; height: 45px;" 
               onmouseover="this.style.backgroundColor='#0ea5e9'; this.style.transform='translateY(-2px)'" 
               onmouseout="this.style.backgroundColor='#0f172a'; this.style.transform='translateY(0)'">
                <i class='bx bx-plus-circle me-2 fs-5'></i> Tambah Pengguna
            </a>
        </div>

        <!-- Table Panel -->
        <div class="bg-white p-4 rounded-4 border shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="fw-semibold pb-3">Informasi Akun</th>
                            <th class="fw-semibold pb-3">Level Akses (Role)</th>
                            <th class="fw-semibold pb-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM users");
                        while ($row = $result->fetch_assoc()):
                            $role_display = strtoupper($row['role']);
                            $role_class = 'secondary';
                            if ($role_display == 'ADMIN') $role_class = 'primary';
                            if ($role_display == 'GURU' || $role_display == 'PENGAJAR') {
                                $role_display = 'PENGAJAR';
                                $role_class = 'success';
                            }
                            if ($role_display == 'SISWA') $role_class = 'info';
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm border border-white" style="width:40px;height:40px; font-size:1rem;">
                                        <?php echo strtoupper(substr($row['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.95rem;"><?php echo htmlspecialchars($row['username']); ?></span>
                                        <small class="text-muted" style="font-size: 0.75rem;"><i class='bx bx-shield-alt-2 me-1'></i> Secured Account</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $role_class; ?> bg-opacity-10 text-<?php echo $role_class; ?> border border-<?php echo $role_class; ?> px-3 py-1 rounded-pill">
                                    <i class='bx bxs-badge-check me-1'></i> <?php echo $role_display; ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-3 align-items-center">
                                    <a href="admin.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-link text-warning p-0 text-decoration-none" title="Edit Data">
                                        <i class='bx bx-edit-alt fs-4'></i>
                                    </a>
                                    <a href="admin.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-link text-danger p-0 text-decoration-none btn-delete" title="Hapus Data">
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
            $result = $conn->query("SELECT * FROM users WHERE id=$id");
            $row = $result->fetch_assoc();
        }
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bx-edit text-primary me-2'></i> <?php echo $action == 'add' ? 'Registrasi Pengguna Baru' : 'Perbarui Akun Pengguna'; ?></h3>
                <p class="text-muted mb-0 small">Kelola kredensial dan hak akses untuk masuk ke sistem.</p>
            </div>
            <a href="admin.php" class="btn btn-outline-secondary fw-medium rounded-3 px-4 shadow-sm">
                <i class='bx bx-arrow-back me-1'></i> Kembali
            </a>
        </div>

        <form method="POST">
            <?php if($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
            <?php endif; ?>
            
            <div class="bg-white p-4 p-lg-5 rounded-4 border shadow-sm">
                <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-lock-open-alt text-primary me-2'></i> Kredensial Pengguna</h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-muted">Username (NIP/NIS/ADMIN)</label>
                        <input type="text" name="username" class="form-control form-control-lg" value="<?php echo $row['username'] ?? ''; ?>" required placeholder="Masukkan username...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-muted">Level Akses (Role)</label>
                        <select name="role" class="form-select form-select-lg" required>
                            <option value="admin" <?php echo (isset($row['role']) && $row['role'] == 'admin') ? 'selected' : ''; ?>>ADMIN (Akses Penuh)</option>
                            <option value="guru" <?php echo (isset($row['role']) && ($row['role'] == 'guru' || $row['role'] == 'pengajar')) ? 'selected' : ''; ?>>PENGAJAR</option>
                            <option value="siswa" <?php echo (isset($row['role']) && $row['role'] == 'siswa') ? 'selected' : ''; ?>>SISWA</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-medium text-muted">Password <?php echo $action == 'edit' ? '<small>(Kosongkan jika tidak ingin mengubah)</small>' : ''; ?></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class='bx bx-key'></i></span>
                            <input type="password" name="password" class="form-control form-control-lg border-start-0" <?php echo $action == 'add' ? 'required' : ''; ?> placeholder="********">
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-3 border-top">
                    <a href="admin.php" class="btn btn-light border px-4 py-2 fw-medium text-muted">Batal</a>
                    <button type="submit" class="btn px-4 py-2 fw-medium text-white shadow-sm" style="background-color: #0f172a; transition: all 0.3s;" onmouseover="this.style.backgroundColor='#0ea5e9'" onmouseout="this.style.backgroundColor='#0f172a'">
                        <i class='bx bx-save me-1'></i> Simpan Akun Pengguna
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>
