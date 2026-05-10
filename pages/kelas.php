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

// Auto create tables if not exists
$conn->query("CREATE TABLE IF NOT EXISTS kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(50) NOT NULL,
    wali_guru VARCHAR(30),
    FOREIGN KEY (wali_guru) REFERENCES guru(nip) ON DELETE SET NULL ON UPDATE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS kelas_mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas INT,
    id_mapel INT,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_mapel) REFERENCES mapel(id) ON DELETE CASCADE
)");

$action = $_GET['action'] ?? 'list';

if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM kelas WHERE id=$id");
    $_SESSION['success'] = 'Data Kelas Berhasil Dihapus!';
    header("Location: kelas.php");
    exit;
}

if ($action == 'remove_siswa' && isset($_GET['nis']) && isset($_GET['nama_kelas'])) {
    $nis = $conn->real_escape_string($_GET['nis']);
    $nama_kelas = $conn->real_escape_string($_GET['nama_kelas']);
    $conn->query("UPDATE siswa SET kelas = '' WHERE nis = '$nis'");
    $_SESSION['success'] = 'Siswa berhasil dikeluarkan dari kelas!';
    header("Location: kelas.php?action=view&nama=" . urlencode($nama_kelas));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kelas = $conn->real_escape_string($_POST['nama_kelas']);
    $wali_guru = $conn->real_escape_string($_POST['wali_guru']);
    $mapel_ids = $_POST['mapel_ids'] ?? [];

    if ($action == 'add') {
        $conn->query("INSERT INTO kelas (nama_kelas, wali_guru) VALUES ('$nama_kelas', '$wali_guru')");
        $new_id = $conn->insert_id;
        
        // Simpan Mapel
        foreach($mapel_ids as $m_id) {
            $m_id = (int)$m_id;
            $conn->query("INSERT INTO kelas_mapel (id_kelas, id_mapel) VALUES ($new_id, $m_id)");
        }
        
        $_SESSION['success'] = 'Data Kelas Berhasil Ditambahkan!';
        header("Location: kelas.php");
        exit;
    } elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE kelas SET nama_kelas='$nama_kelas', wali_guru='$wali_guru' WHERE id=$id");
        
        // Update Mapel: Hapus dulu yang lama, baru insert yang baru
        $conn->query("DELETE FROM kelas_mapel WHERE id_kelas=$id");
        foreach($mapel_ids as $m_id) {
            $m_id = (int)$m_id;
            $conn->query("INSERT INTO kelas_mapel (id_kelas, id_mapel) VALUES ($id, $m_id)");
        }
        
        $_SESSION['success'] = 'Data Kelas Berhasil Diperbarui!';
        header("Location: kelas.php");
        exit;
    }
}

$page_title = 'Manajemen Kelas';
include '../layouts/header.php'; 
?>

<div class="container-fluid bg-transparent p-0">
    <?php if ($action == 'list'): ?>
        <!-- Header List -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bxs-building-house text-primary me-2'></i> Manajemen Kelas</h3>
                <p class="text-muted mb-0 small">Kelola daftar kelas dan wali kelas masing-masing.</p>
            </div>
            <?php if ($role == 'admin'): ?>
            <a href="kelas.php?action=add" class="btn fw-bold rounded-3 px-4 shadow-sm text-white d-flex align-items-center justify-content-center" 
               style="background-color: #0f172a; border: none; transition: all 0.3s ease; height: 45px;" 
               onmouseover="this.style.backgroundColor='#8b5cf6'; this.style.transform='translateY(-2px)'" 
               onmouseout="this.style.backgroundColor='#0f172a'; this.style.transform='translateY(0)'">
                <i class='bx bx-plus-circle me-2 fs-5'></i> Kelas Baru
            </a>
            <?php endif; ?>
        </div>

        <!-- Table Panel -->
        <div class="bg-white p-4 rounded-4 border shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="fw-semibold pb-3" style="width: 50px;">No</th>
                            <th class="fw-semibold pb-3">Nama Kelas</th>
                            <th class="fw-semibold pb-3">Wali Kelas</th>
                            <th class="fw-semibold pb-3 text-center">Jumlah Siswa</th>
                            <th class="fw-semibold pb-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT k.*, g.nama as nama_wali, g.foto as foto_wali,
                                  (SELECT COUNT(*) FROM siswa WHERE kelas = k.nama_kelas) as total_siswa
                                  FROM kelas k 
                                  LEFT JOIN guru g ON k.wali_guru = g.nip";
                        
                        if ($role == 'guru') {
                            $query .= " WHERE k.wali_guru = '$username'";
                        }
                        
                        $result = $conn->query($query);
                        $no = 1;
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="text-muted small"><?php echo $no++; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-purple bg-opacity-10 text-purple rounded-3 d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm" style="width:40px;height:40px; background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                        <i class='bx bx-door-open' style="font-size: 1.2rem;"></i>
                                    </div>
                                    <span class="fw-bold text-dark" style="font-size: 0.95rem;"><?php echo $row['nama_kelas']; ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if($row['foto_wali']): ?>
                                        <img src="../<?php echo $row['foto_wali']; ?>" width="30" height="30" class="rounded-circle me-2" style="object-fit:cover; border: 1px solid #eee;">
                                    <?php else: ?>
                                        <div class="bg-light text-muted rounded-circle d-flex align-items-center justify-content-center me-2" style="width:30px;height:30px; font-size: 0.8rem;">
                                            <i class='bx bx-user'></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="fw-medium text-dark small"><?php echo $row['nama_wali'] ?? '<span class="text-muted italic">Belum ditentukan</span>'; ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-primary border px-3 py-2 rounded-pill fw-bold">
                                    <?php echo $row['total_siswa']; ?> Siswa
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-3 align-items-center">
                                    <?php 
                                    $guru_json = 'null';
                                    if($row['wali_guru']) {
                                        $nip_guru = $row['wali_guru'];
                                        $g_res = $conn->query("SELECT * FROM guru WHERE nip='$nip_guru'");
                                        $guru_data = $g_res->fetch_assoc();
                                        $guru_json = json_encode($guru_data);
                                    }
                                    
                                    // Ambil Mapel dari tabel relasi kelas_mapel
                                    $id_k = $row['id'];
                                    $mapel_query = "SELECT m.nama_mapel, g.nama as nama_pengampu 
                                                   FROM kelas_mapel km
                                                   JOIN mapel m ON km.id_mapel = m.id 
                                                   JOIN guru g ON m.nip_guru = g.nip
                                                   WHERE km.id_kelas = $id_k";
                                    $mapel_res = $conn->query($mapel_query);
                                    $daftar_mapel = [];
                                    while($m_row = $mapel_res->fetch_assoc()) {
                                        $daftar_mapel[] = $m_row;
                                    }
                                    
                                    $kelas_info = [
                                        'nama_kelas' => $row['nama_kelas'],
                                        'total_siswa' => $row['total_siswa'],
                                        'guru' => ($guru_json != 'null') ? json_decode($guru_json) : null,
                                        'mapel' => $daftar_mapel
                                    ];
                                    $kelas_json = htmlspecialchars(json_encode($kelas_info), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <button type="button" class="btn btn-link text-info p-0 text-decoration-none" title="Detail Kelas" data-kelas='<?php echo $kelas_json; ?>' onclick="showDetailKelas(this)">
                                        <i class='bx bx-show fs-4'></i>
                                    </button>
                                    <a href="kelas.php?action=view&nama=<?php echo urlencode($row['nama_kelas']); ?>" class="btn btn-link text-primary p-0 text-decoration-none" title="Lihat Daftar Siswa">
                                        <i class='bx bx-group fs-4'></i>
                                    </a>
                                    <?php if ($role == 'admin'): ?>
                                    <a href="kelas.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-link text-warning p-0 text-decoration-none" title="Edit Data">
                                        <i class='bx bx-edit-alt fs-4'></i>
                                    </a>
                                    <a href="kelas.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-link text-danger p-0 text-decoration-none btn-delete" title="Hapus Data">
                                        <i class='bx bx-trash fs-4'></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Detail Kelas & Wali Kelas -->
        <div class="modal fade" id="modalDetailKelas" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-detail-custom modal-dialog-centered">
            <div class="modal-content overflow-hidden border-0 shadow-lg rounded-4">
              <!-- Banner -->
              <div class="position-relative" style="height: 100px; background: linear-gradient(135deg, #4f46e5, #8b5cf6);">
                  <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                  <div class="position-absolute bottom-0 start-0 w-100 p-3 text-white">
                      <h4 class="fw-bold mb-0" id="detailNamaKelas">Nama Kelas</h4>
                      <small class="opacity-75" id="detailTotalSiswa">0 Siswa Terdaftar</small>
                  </div>
                  <i class='bx bxs-school position-absolute' style="font-size: 5rem; color: rgba(255,255,255,0.1); right: -10px; bottom: -10px;"></i>
              </div>
              
              <div class="modal-body px-4 pb-4 pt-4">
                  <!-- Section Wali Kelas -->
                  <div id="sectionWaliKelas">
                      <div class="d-flex align-items-center bg-light p-2 rounded-4 border border-light-subtle mb-3">
                          <div class="position-relative me-3" style="width: 50px; height: 50px;">
                              <img id="detailFotoGuru" src="" class="rounded-circle shadow-sm bg-white p-1" style="width: 100%; height: 100%; object-fit: cover; display:none;">
                              <div id="detailFotoPlaceholderGuru" class="rounded-circle shadow-sm bg-white p-1 flex-column align-items-center justify-content-center text-primary fw-bold" style="width: 100%; height: 100%; font-size: 1.5rem; display:none;">
                                  <span id="detailInitialGuru">G</span>
                              </div>
                          </div>
                          <div class="overflow-hidden">
                              <h6 class="fw-bold text-dark mb-0 text-truncate" id="detailNamaGuru">Nama Guru</h6>
                              <small class="text-muted d-block mb-1">
                                  NIP: <span id="detailNipGuru" class="fw-bold text-dark">123</span>
                                  <button class="btn btn-link p-0 ms-1 text-primary" onclick="copyNipGuru()" title="Salin NIP">
                                      <i class='bx bx-copy'></i>
                                  </button>
                              </small>
                              <div id="detailStatusGuru"></div>
                          </div>
                      </div>

                      <!-- Wali Kelas Extra Info -->
                      <div class="bg-light p-2 rounded-3 border mb-3 text-start">
                          <div class="d-flex justify-content-between mb-1">
                              <small class="text-muted"><i class='bx bx-book-alt me-1'></i> Agama:</small>
                              <small class="fw-bold text-dark" id="detailAgamaGuru">Islam</small>
                          </div>
                          <div class="d-flex justify-content-between mb-1">
                              <small class="text-muted"><i class='bx bx-phone me-1'></i> Telepon:</small>
                              <small class="fw-bold text-dark" id="detailNoHpGuru">0812</small>
                          </div>
                          <div class="mt-2 pt-2 border-top">
                              <small class="text-muted d-block mb-1"><i class='bx bx-map me-1'></i> Alamat:</small>
                              <small class="fw-bold text-dark d-block lh-sm" id="detailAlamatGuru">Jl. Contoh No. 123</small>
                          </div>
                      </div>
                  </div>

                  <div id="sectionNoWali" class="text-center py-4 bg-light rounded-4 border border-dashed border-2 mb-2" style="display:none;">
                      <i class='bx bx-user-x fs-1 text-muted mb-1'></i>
                      <p class="text-muted small mb-0">Belum ada Wali Kelas.</p>
                  </div>

                  <!-- Section Mata Pelajaran -->
                  <div class="mt-2">
                      <h6 class="fw-bold text-muted small text-uppercase mb-2 px-1"><i class='bx bx-book-content me-1'></i> Mapel Kelas</h6>
                      <div id="listMapelKelas" class="d-flex flex-column gap-1 overflow-auto" style="max-height: 180px;">
                          <!-- Mapel list will be injected here -->
                      </div>
                      <div id="noMapelKelas" class="text-center py-3 bg-light rounded-3 border border-dashed" style="display:none;">
                          <small class="text-muted" style="font-size: 0.7rem;">Belum ada mata pelajaran.</small>
                      </div>
                  </div>
              </div>
              
              <div class="modal-footer bg-light border-0 justify-content-center p-3">
                  <a id="btnLihatSiswa" href="#" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                      <i class='bx bx-group me-1'></i> Lihat Daftar Siswa
                  </a>
              </div>
            </div>
          </div>
        </div>

        <script>
        function showDetailKelas(btn) {
            const data = JSON.parse(btn.getAttribute('data-kelas'));
            
            // Fill Class Info
            document.getElementById('detailNamaKelas').innerText = data.nama_kelas;
            document.getElementById('detailTotalSiswa').innerText = data.total_siswa + ' Siswa Terdaftar';
            document.getElementById('btnLihatSiswa').href = 'kelas.php?action=view&nama=' + encodeURIComponent(data.nama_kelas);

            if (data.guru) {
                document.getElementById('sectionWaliKelas').style.display = 'block';
                document.getElementById('sectionNoWali').style.display = 'none';
                
                document.getElementById('detailNamaGuru').innerText = data.guru.nama;
                document.getElementById('detailNipGuru').innerText = data.guru.nip;
                document.getElementById('detailAgamaGuru').innerText = data.guru.agama;
                document.getElementById('detailNoHpGuru').innerText = data.guru.telp;
                document.getElementById('detailAlamatGuru').innerText = data.guru.alamat;
                
                const statusContainer = document.getElementById('detailStatusGuru');
                if(data.guru.status === 'Aktif') {
                    statusContainer.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill small"><i class='bx bx-check-circle me-1'></i> Aktif</span>`;
                } else {
                    statusContainer.innerHTML = `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1 rounded-pill small"><i class='bx bx-x-circle me-1'></i> Berhenti</span>`;
                }

                const imgEl = document.getElementById('detailFotoGuru');
                const placeholderEl = document.getElementById('detailFotoPlaceholderGuru');
                if(data.guru.foto) {
                    imgEl.src = '../' + data.guru.foto;
                    imgEl.style.display = 'block';
                    placeholderEl.style.display = 'none';
                } else {
                    imgEl.style.display = 'none';
                    placeholderEl.style.display = 'flex';
                    document.getElementById('detailInitialGuru').innerText = data.guru.nama.charAt(0);
                }
                document.getElementById('sectionWaliKelas').style.display = 'block';
                document.getElementById('sectionNoWali').style.display = 'none';
            } else {
                document.getElementById('sectionWaliKelas').style.display = 'none';
                document.getElementById('sectionNoWali').style.display = 'block';
            }

            // Fill Subjects Info
            const listMapel = document.getElementById('listMapelKelas');
            const noMapel = document.getElementById('noMapelKelas');
            listMapel.innerHTML = '';
            
            if (data.mapel && data.mapel.length > 0) {
                noMapel.style.display = 'none';
                data.mapel.forEach(m => {
                    const item = document.createElement('div');
                    item.className = 'd-flex align-items-center justify-content-between p-2 px-3 bg-white border rounded-3 shadow-xs';
                    item.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class='bx bx-book text-primary me-2'></i>
                            <span class="fw-bold text-dark small">${m.nama_mapel}</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.7rem;">Pengampu: ${m.nama_pengampu}</small>
                    `;
                    listMapel.appendChild(item);
                });
            } else {
                noMapel.style.display = 'block';
            }

            new bootstrap.Modal(document.getElementById('modalDetailKelas')).show();
        }

        function copyNipGuru() {
            const nip = document.getElementById('detailNipGuru').innerText;
            navigator.clipboard.writeText(nip).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'NIP berhasil disalin!',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        }
        </script>

    <?php elseif ($action == 'view' && isset($_GET['nama'])): 
        $nama_kelas = $conn->real_escape_string($_GET['nama']);
        $siswa_query = "SELECT * FROM siswa WHERE kelas='$nama_kelas' ORDER BY nama ASC";
        $siswa_res = $conn->query($siswa_query);
        $total = $siswa_res->num_rows;
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bx-group text-primary me-2'></i> Daftar Siswa Kelas <?php echo htmlspecialchars($nama_kelas); ?></h3>
                <p class="text-muted mb-0 small">Menampilkan <?php echo $total; ?> siswa yang terdaftar di kelas ini.</p>
            </div>
            <a href="kelas.php" class="btn btn-outline-secondary fw-medium rounded-3 px-4 shadow-sm">
                <i class='bx bx-arrow-back me-1'></i> Kembali
            </a>
        </div>

        <div class="bg-white p-4 rounded-4 border shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle <?php echo ($total > 0) ? 'datatable' : ''; ?>">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="fw-semibold pb-3" style="width: 50px;">No</th>
                            <th class="fw-semibold pb-3">Profil Siswa</th>
                            <th class="fw-semibold pb-3">NIS</th>
                            <th class="fw-semibold pb-3 text-center">Status</th>
                            <th class="fw-semibold pb-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no_siswa = 1;
                        while ($s = $siswa_res->fetch_assoc()): ?>
                        <tr>
                            <td class="text-muted small"><?php echo $no_siswa++; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if($s['foto']): ?>
                                        <img src="../<?php echo $s['foto']; ?>" width="35" height="35" class="rounded-circle me-2" style="object-fit:cover;">
                                    <?php else: ?>
                                        <div class="bg-light text-muted rounded-circle d-flex align-items-center justify-content-center me-2" style="width:35px;height:35px; font-size: 0.75rem;">
                                            <i class='bx bx-user'></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?php echo $s['nama']; ?></span>
                                </div>
                            </td>
                            <td><span class="text-secondary fw-medium">#<?php echo $s['nis']; ?></span></td>
                            <td class="text-center">
                                <?php if($s['status'] == 'Aktif'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill small">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1 rounded-pill small">Keluar</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-3 align-items-center">
                                    <?php $siswa_json = htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8'); ?>
                                    <button type="button" class="btn btn-link text-info p-0 text-decoration-none" title="Lihat Detail" data-siswa='<?php echo $siswa_json; ?>' onclick="showDetailSiswa(this)">
                                        <i class='bx bx-show fs-4'></i>
                                    </button>
                                    <a href="kelas.php?action=remove_siswa&nis=<?php echo $s['nis']; ?>&nama_kelas=<?php echo urlencode($nama_kelas); ?>" class="btn btn-link text-danger p-0 text-decoration-none btn-delete" title="Keluarkan dari Kelas">
                                        <i class='bx bx-user-x fs-4'></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($total == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted italic">Belum ada siswa yang terdaftar di kelas ini.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            @media (min-width: 992px) {
                .modal-detail-custom {
                    max-width: 360px;
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

                        <h5 class="fw-bold text-dark mb-1" id="detailNama">Nama Lengkap Siswa</h5>
                        <p class="text-muted mb-2" style="font-size: 0.85rem;">
                            <i class='bx bx-id-card me-1'></i> NIS: <span class="fw-bold text-dark"
                                id="detailNis">12345</span>
                            <button class="btn btn-link p-0 ms-1 text-primary" onclick="copyNis()" title="Salin NIS">
                                <i class='bx bx-copy'></i>
                            </button>
                        </p>

                        <div class="d-flex justify-content-center gap-2 mb-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1 rounded-pill small"><i class='bx bxs-graduation me-1'></i> <span id="detailJurusan">Jurusan</span></span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-2 py-1 rounded-pill small"><i class='bx bx-building-house me-1'></i> <span id="detailKelas">X-1</span></span>
                        </div>

                        <!-- Compact Info Grid -->
                        <div class="bg-light p-2 rounded-3 border mb-2 text-start">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted"><i class='bx bx-phone me-1'></i> Telepon:</small>
                                <small class="fw-bold text-dark" id="detailNoHp">0812</small>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted"><i class='bx bx-book-alt me-1'></i> Agama:</small>
                                <small class="fw-bold text-dark" id="detailAgama">Islam</small>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted"><i class='bx bx-calendar me-1'></i> Tahun:</small>
                                <small class="fw-bold text-dark" id="detailAngkatan">2023/2024</small>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted"><i class='bx bx-check-circle me-1'></i> Status:</small>
                                <div id="detailStatus" class="d-inline-block"></div>
                            </div>
                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted d-block mb-1"><i class='bx bx-map me-1'></i> Alamat:</small>
                                <small class="fw-bold text-dark d-block lh-sm" id="detailAlamat">Jl. Contoh No. 123</small>
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

                const statusContainer = document.getElementById('detailStatus');
                if (data.status === 'Aktif') {
                    statusContainer.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-0 rounded-pill small">Aktif</span>`;
                } else {
                    statusContainer.innerHTML = `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-0 rounded-pill small">Keluar</span>`;
                }

                const imgEl = document.getElementById('detailFoto');
                const placeholderEl = document.getElementById('detailFotoPlaceholder');
                if (data.foto) {
                    imgEl.src = '../' + data.foto;
                    imgEl.style.display = 'block';
                    placeholderEl.style.display = 'none';
                } else {
                    imgEl.style.display = 'none';
                    placeholderEl.style.display = 'flex';
                    document.getElementById('detailInitial').innerText = data.nama.charAt(0);
                }

                new bootstrap.Modal(document.getElementById('modalDetailSiswa')).show();
            }

            function copyNis() {
                const nis = document.getElementById('detailNis').innerText;
                navigator.clipboard.writeText(nis).then(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'NIS berhasil disalin!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                });
            }
        </script>

    <?php elseif ($action == 'add' || $action == 'edit'): 
        $row = null;
        if ($action == 'edit' && isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $result = $conn->query("SELECT * FROM kelas WHERE id=$id");
            $row = $result->fetch_assoc();
        }
        $guru_result = $conn->query("SELECT * FROM guru ORDER BY nama ASC");
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class='bx bx-edit text-primary me-2'></i> <?php echo $action == 'add' ? 'Tambah Kelas Baru' : 'Edit Data Kelas'; ?></h3>
                <p class="text-muted mb-0 small">Masukkan nama kelas dan pilih wali kelas yang bertanggung jawab.</p>
            </div>
            <a href="kelas.php" class="btn btn-outline-secondary fw-medium rounded-3 px-4 shadow-sm">
                <i class='bx bx-arrow-back me-1'></i> Kembali
            </a>
        </div>

        <form method="POST">
            <?php if($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
            <?php endif; ?>
            
            <div class="bg-white p-4 p-lg-5 rounded-4 border shadow-sm">
                <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class='bx bx-home-alt text-primary me-2'></i> Konfigurasi Kelas</h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-muted">Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="form-control form-control-lg shadow-xs" value="<?php echo $row['nama_kelas'] ?? ''; ?>" placeholder="Contoh: X-IPA-1 atau 10-A" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-muted">Wali Kelas</label>
                        <select name="wali_guru" class="form-select form-select-lg shadow-xs" required>
                            <option value="">-- Pilih Wali Kelas --</option>
                            <?php while ($g = $guru_result->fetch_assoc()): ?>
                                <option value="<?php echo $g['nip']; ?>" <?php echo (isset($row['wali_guru']) && $row['wali_guru'] == $g['nip']) ? 'selected' : ''; ?>>
                                    <?php echo $g['nama']; ?> (<?php echo $g['nip']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium text-muted d-block mb-3">
                        <i class='bx bx-book-open text-primary me-1'></i> Pilih Mata Pelajaran untuk Kelas Ini
                    </label>
                    <div class="row g-3">
                        <?php 
                        $all_mapel = $conn->query("SELECT * FROM mapel ORDER BY nama_mapel ASC");
                        
                        // Ambil mapel yang sudah dipilih jika sedang edit
                        $selected_mapels = [];
                        if($action == 'edit') {
                            $sm_res = $conn->query("SELECT id_mapel FROM kelas_mapel WHERE id_kelas = ".$row['id']);
                            while($sm = $sm_res->fetch_assoc()) $selected_mapels[] = $sm['id_mapel'];
                        }
                        
                        while($m = $all_mapel->fetch_assoc()): 
                        ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="p-3 border rounded-4 transition-all shadow-hover h-100 d-flex align-items-center">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="mapel_ids[]" 
                                           value="<?php echo $m['id']; ?>" id="m_<?php echo $m['id']; ?>"
                                           <?php echo in_array($m['id'], $selected_mapels) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold text-dark ms-2" for="m_<?php echo $m['id']; ?>" style="cursor:pointer; font-size: 0.9rem;">
                                        <?php echo $m['nama_mapel']; ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-3 border-top">
                    <a href="kelas.php" class="btn btn-light border px-4 py-2 fw-medium text-muted">Batal</a>
                    <button type="submit" class="btn px-4 py-2 fw-medium text-white shadow-sm" style="background-color: #0f172a; transition: all 0.3s;" onmouseover="this.style.backgroundColor='#8b5cf6'" onmouseout="this.style.backgroundColor='#0f172a'">
                        <i class='bx bx-save me-1'></i> Simpan Data Kelas
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>
