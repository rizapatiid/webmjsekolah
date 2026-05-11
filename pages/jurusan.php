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

// Hanya Admin yang boleh akses
if ($_SESSION['user']['role'] != 'admin') {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini!';
    header("Location: dashboard.php");
    exit;
}

$action = $_GET['action'] ?? 'list';

if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM jurusan WHERE id=$id");
    $_SESSION['success'] = 'Data Program Studi Berhasil Dihapus!';
    header("Location: jurusan.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_jurusan = $conn->real_escape_string($_POST['nama_jurusan']);
    $kaprodi = $conn->real_escape_string($_POST['kaprodi']);
    $nip_kaprodi = $conn->real_escape_string($_POST['nip_kaprodi']);

    if ($action == 'add') {
        $conn->query("INSERT INTO jurusan (nama_jurusan, kaprodi, nip_kaprodi) VALUES ('$nama_jurusan', '$kaprodi', '$nip_kaprodi')");
        $_SESSION['success'] = 'Data Program Studi Berhasil Ditambahkan!';
        header("Location: jurusan.php");
        exit;
    } elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE jurusan SET nama_jurusan='$nama_jurusan', kaprodi='$kaprodi', nip_kaprodi='$nip_kaprodi' WHERE id=$id");
        $_SESSION['success'] = 'Data Program Studi Berhasil Diperbarui!';
        header("Location: jurusan.php");
        exit;
    }
}

$page_title = 'Manajemen Program Studi';
include '../layouts/header.php'; 
?>

<div class="container-fluid bg-transparent p-0">
    <?php if ($action == 'list'): ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bxs-graduation text-primary me-2'></i> Manajemen Program Studi / Jurusan</h3>
                <p class="text-muted mb-0 small">Kelola daftar program studi beserta data pimpinan (KAPRODI) masing-masing.</p>
            </div>
            <a href="jurusan.php?action=add" class="btn fw-bold rounded-3 px-4 shadow-sm text-white d-flex align-items-center justify-content-center" 
               style="background-color: #0f172a; border: none; transition: all 0.3s ease; height: 45px;" 
               onmouseover="this.style.backgroundColor='#3b82f6'; this.style.transform='translateY(-2px)'" 
               onmouseout="this.style.backgroundColor='#0f172a'; this.style.transform='translateY(0)'">
                <i class='bx bx-plus-circle me-2 fs-5'></i> Tambah Prodi
            </a>
        </div>

        <div class="bg-white p-4 rounded-4 border shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="fw-semibold pb-3" style="width: 60px;">ID</th>
                            <th class="fw-semibold pb-3">Nama Program Studi</th>
                            <th class="fw-semibold pb-3">Kepala Prodi (KAPRODI)</th>
                            <th class="fw-semibold pb-3">NIP / NIDN</th>
                            <th class="fw-semibold pb-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td class="fw-medium">#<?php echo $row['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm" style="width:40px;height:40px;">
                                            <i class='bx bx-book-bookmark' style="font-size: 1.2rem;"></i>
                                        </div>
                                        <span class="fw-bold text-dark" style="font-size: 0.95rem;"><?php echo $row['nama_jurusan']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center me-2" style="width:30px;height:30px; font-size: 0.8rem;">
                                            <i class='bx bx-user'></i>
                                        </div>
                                        <span class="fw-medium text-dark small"><?php echo $row['kaprodi'] ?: 'Belum Diatur'; ?></span>
                                    </div>
                                </td>
                                <td><code class="text-secondary small"><?php echo $row['nip_kaprodi'] ?: '-'; ?></code></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-3 align-items-center">
                                        <a href="jurusan.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-link text-warning p-0 text-decoration-none" title="Edit Data">
                                            <i class='bx bx-edit-alt fs-4'></i>
                                        </a>
                                        <a href="jurusan.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-link text-danger p-0 text-decoration-none" onclick="return confirm('Hapus data ini?')" title="Hapus Data">
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
            $res = $conn->query("SELECT * FROM jurusan WHERE id=$id");
            $row = $res->fetch_assoc();
        }
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-1 text-dark"><?php echo $action == 'add' ? 'Tambah Program Studi' : 'Edit Program Studi'; ?></h3>
            <a href="jurusan.php" class="btn btn-outline-secondary rounded-pill px-4">Kembali</a>
        </div>

        <div class="bg-white p-4 p-lg-5 rounded-4 border shadow-sm">
            <form method="POST">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                <?php endif; ?>
                
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Nama Program Studi / Jurusan</label>
                        <input type="text" name="nama_jurusan" class="form-control" value="<?php echo $row['nama_jurusan'] ?? ''; ?>" placeholder="Contoh: TEKNIK INFORMATIKA" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nama KAPRODI</label>
                        <input type="text" name="kaprodi" class="form-control" value="<?php echo $row['kaprodi'] ?? ''; ?>" placeholder="Nama Lengkap Beserta Gelar">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">NIP / NIDN KAPRODI</label>
                        <input type="text" name="nip_kaprodi" class="form-control" value="<?php echo $row['nip_kaprodi'] ?? ''; ?>" placeholder="Nomor Identitas Pegawai">
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" class="btn btn-primary fw-bold px-5 rounded-pill shadow-sm py-3">Simpan Data Program Studi</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>
