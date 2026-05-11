<?php
/**
 * Copyright 2026 RIZAPATIID - PROJECT
 * Email : rizapatiid@gmail.com
 */
$current_page = basename($_SERVER['PHP_SELF']); 

$nav_pengaturan_res = $conn->query("SELECT logo FROM pengaturan_sekolah WHERE id=1");
$nav_pengaturan = $nav_pengaturan_res ? $nav_pengaturan_res->fetch_assoc() : null;
$nav_logo = $nav_pengaturan['logo'] ?? '';
?>
<style>
@media (min-width: 992px) {
    .sidebar-nav {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 260px;
        z-index: 1040;
        flex-direction: column;
        align-items: stretch;
        padding: 1.5rem 1rem;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
    }
    .sidebar-nav .navbar-collapse {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        height: 100%;
    }
    .sidebar-nav .navbar-nav {
        flex-direction: column;
        width: 100%;
    }
    .sidebar-nav .nav-item { margin-bottom: 0.5rem; }
    .sidebar-nav .nav-link { 
        padding: 0.75rem 1rem !important; 
        border-radius: 10px;
        color: rgba(255,255,255,0.8);
        font-size: 0.9rem;
    }
    .sidebar-nav .nav-link.active {
        background: rgba(255,255,255,0.2) !important;
        color: #fff !important;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .sidebar-nav .nav-link:hover {
        background: rgba(255,255,255,0.15);
        color: #fff;
    }
    .sidebar-nav .navbar-brand {
        margin-bottom: 1.5rem;
        text-align: center;
        padding: 0;
    }
}
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sidebar-nav mb-4 mb-lg-0 sticky-top">
  <div class="container-fluid flex-lg-column align-items-stretch h-100">
    <a class="navbar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <?php if (!empty($nav_logo) && file_exists('../' . $nav_logo)): ?>
            <img src="../<?php echo $nav_logo; ?>" alt="Logo Sekolah" class="me-2" style="max-height: 35px; width: auto; object-fit: contain; border-radius: 4px;">
        <?php else: ?>
            <i class='bx bxs-graduation me-2 fs-2'></i>
        <?php endif; ?>
        <span class="fw-bold fs-4">SIAKAD</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto w-100">
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php"><i class='bx bxs-dashboard me-2'></i> Dashboard</a>
        </li>
        
        <?php if ($_SESSION['user']['role'] == 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'siswa.php' ? 'active' : ''; ?>" href="siswa.php"><i class='bx bxs-user-detail me-2'></i> Data <?php echo LBL_SISWA; ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'guru.php' ? 'active' : ''; ?>" href="guru.php"><i class='bx bxs-chalkboard me-2'></i> Data <?php echo LBL_GURU; ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'kelas.php' ? 'active' : ''; ?>" href="kelas.php"><i class='bx bxs-building-house me-2'></i> Data <?php echo LBL_KELAS; ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'mapel.php' ? 'active' : ''; ?>" href="mapel.php"><i class='bx bxs-book-alt me-2'></i> <?php echo LBL_MAPEL; ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'jurusan.php' ? 'active' : ''; ?>" href="jurusan.php"><i class='bx bxs-book-bookmark me-2'></i> Manajemen Jurusan</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'nilai.php' ? 'active' : ''; ?>" href="nilai.php"><i class='bx bxs-bar-chart-alt-2 me-2'></i> Nilai</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'admin.php' ? 'active' : ''; ?>" href="admin.php"><i class='bx bxs-user-badge me-2'></i> Pengelola Pengguna</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'pengaturan.php' ? 'active' : ''; ?>" href="pengaturan.php"><i class='bx bx-cog me-2'></i> Pengaturan Instansi</a>
            </li>
        <?php elseif ($_SESSION['user']['role'] == 'guru'): ?>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'kelas.php' ? 'active' : ''; ?>" href="kelas.php"><i class='bx bxs-building-house me-2'></i> <?php echo LBL_KELAS; ?> & <?php echo LBL_SISWA; ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'nilai.php' ? 'active' : ''; ?>" href="nilai.php"><i class='bx bxs-bar-chart-alt-2 me-2'></i> Input Nilai</a>
            </li>
        <?php elseif ($_SESSION['user']['role'] == 'siswa'): ?>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page == 'nilai.php' ? 'active' : ''; ?>" href="nilai.php"><i class='bx bxs-bar-chart-alt-2 me-2'></i> Raport Saya</a>
            </li>
        <?php endif; ?>
      </ul>
      
      <!-- User Profile Dropdown in Sidebar -->
      <div class="mt-auto pt-3 border-top border-light border-opacity-25 w-100 pb-2 pb-lg-0">
          <ul class="navbar-nav w-100">
              <li class="nav-item dropup w-100">
                  <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-between w-100 px-3 py-2 text-white bg-white bg-opacity-10 rounded" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <div class="d-flex align-items-center">
                          <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_display_name); ?>&background=random&color=fff" class="rounded-circle me-2 shadow-sm" width="35" height="35" alt="Profile">
                          <span class="fw-bold text-capitalize fs-6"><?php echo htmlspecialchars($user_display_name); ?></span>
                      </div>
                  </a>
                  <ul class="dropdown-menu shadow-sm border-0 w-100 mb-2 rounded-3">
                      <li>
                          <div class="dropdown-header d-flex align-items-center px-3 py-2">
                              <div>
                                  <h6 class="mb-0 fw-bold text-dark text-capitalize"><?php echo htmlspecialchars($user_display_name); ?></h6>
                                  <small class="text-muted text-capitalize"><i class='bx bxs-badge-check text-primary me-1'></i> <?php echo htmlspecialchars($_SESSION['user']['role']); ?></small>
                              </div>
                          </div>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li><a class="dropdown-item py-2 px-3 fw-medium" href="profil.php"><i class='bx bx-user-circle me-2 text-primary fs-5'></i> Profil Saya</a></li>
                      <li><hr class="dropdown-divider"></li>
                      <li><a class="dropdown-item py-2 px-3 text-danger fw-bold" href="../logout.php"><i class='bx bx-log-out-circle me-2 fs-5'></i> Logout</a></li>
                  </ul>
              </li>
          </ul>
      </div>

    </div>
  </div>
</nav>
