<?php
/**
 * Copyright 2026 RIZAPATIID - PROJECT
 * Email : rizapatiid@gmail.com
 */
session_start();
include 'config/db.php';

if (isset($_SESSION['user'])) {
    header("Location: pages/dashboard.php");
    exit;
}

if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password_raw = $_POST['password'];
    $password_md5 = md5($password_raw);

    // 1. Check existing account in users table (Lowercase table names for Linux compatibility)
    $result = $conn->query("SELECT * FROM users WHERE username='$username' AND password='$password_md5'");

    if ($result && $result->num_rows > 0) {
        $_SESSION['user'] = $result->fetch_assoc();
        header("Location: pages/dashboard.php");
        exit;
    } else {
        // 2. If not found, check if it's a Guru or Siswa trying to login with default password '12345'
        if ($password_raw === '12345') {
            // Check Guru (Ensure lowercase table name 'guru')
            $check_guru = $conn->query("SELECT * FROM guru WHERE nip='$username'");
            if ($check_guru && $check_guru->num_rows > 0) {
                // Auto create Guru account if not exists
                $conn->query("INSERT INTO users (username, password, role) VALUES ('$username', '$password_md5', 'guru')");
                
                // Fetch the newly created user
                $new_res = $conn->query("SELECT * FROM users WHERE username='$username'");
                if ($new_res && $new_res->num_rows > 0) {
                    $_SESSION['user'] = $new_res->fetch_assoc();
                    header("Location: pages/dashboard.php");
                    exit;
                }
            }

            // Check Siswa (Ensure lowercase table name 'siswa')
            $check_siswa = $conn->query("SELECT * FROM siswa WHERE nis='$username'");
            if ($check_siswa && $check_siswa->num_rows > 0) {
                // Auto create Siswa account if not exists
                $conn->query("INSERT INTO users (username, password, role) VALUES ('$username', '$password_md5', 'siswa')");
                
                // Fetch the newly created user
                $new_res = $conn->query("SELECT * FROM users WHERE username='$username'");
                if ($new_res && $new_res->num_rows > 0) {
                    $_SESSION['user'] = $new_res->fetch_assoc();
                    header("Location: pages/dashboard.php");
                    exit;
                }
            }
        }
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            overflow-x: hidden;
            background-color: #f8fafc;
        }

        .login-container {
            display: flex;
            min-height: 100vh;
        }

        /* Left Brand Side */
        .brand-section {
            flex: 1.2;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        /* Decorative Background Elements */
        .brand-section::before {
            content: '';
            position: absolute;
            top: -10%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .brand-section::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1;
        }

        .brand-content {
            z-index: 1;
            max-width: 480px;
        }

        .brand-footer {
            z-index: 1;
            font-size: 0.9rem;
            opacity: 0.7;
        }

        /* Right Form Side */
        .form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #ffffff;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.03);
            z-index: 2;
        }

        .form-wrapper {
            width: 100%;
            max-width: 400px;
        }

        .form-title {
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 0.25rem;
        }

        .form-subtitle {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        /* Inputs */
        .form-floating>.form-control {
            border: 2px solid #f1f5f9;
            background-color: #f8fafc;
            border-radius: 12px;
            padding-left: 1.2rem;
            font-weight: 500;
            color: #334155;
            transition: all 0.3s ease;
        }

        .form-floating>.form-control:focus {
            background-color: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-floating>label {
            padding-left: 1.2rem;
            color: #94a3b8;
            font-weight: 500;
        }

        /* Button */
        .btn-login {
            background-color: #0f172a;
            color: white;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .btn-login:hover {
            background-color: #3b82f6;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.25);
        }

        @media (max-width: 991.98px) {
            .brand-section {
                display: none;
            }

            .form-section {
                background: #ffffff;
                padding: 1rem 0.5rem;
                align-items: flex-start;
                /* Menggeser form ke atas agar tidak tertutup keyboard */
            }

            .form-wrapper {
                background: transparent;
                padding: 3rem 1.5rem;
                border-radius: 0;
                box-shadow: none;
                width: 100%;
                max-width: 100%;
                margin-top: 1rem;
            }

            .form-title {
                font-size: 1.75rem;
            }

            .btn-login {
                padding: 1.2rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <!-- Left Visual Section (Desktop Only) -->
        <div class="brand-section d-none d-lg-flex">
            <div class="brand-logo">
                <div class="bg-white p-2 rounded-3 text-primary d-flex align-items-center justify-content-center"
                    style="width:48px; height:48px;">
                    <i class='bx bxs-graduation fs-2'></i>
                </div>
                <h3 class="mb-0 fw-bold tracking-tight">SIAKAD<span style="color:#3b82f6;">.</span></h3>
            </div>

            <div class="brand-content">
                <h1 class="display-5 fw-bold mb-4">Kelola Data Pendidikan dengan Cerdas.</h1>
                <p class="fs-5 opacity-75 lh-base">Sistem Manajemen Akademik modern, cepat, dan responsif.
                    Mengotomatisasi manajemen siswa, guru, hingga perekapan nilai dalam satu portal terpadu.</p>
                <div class="mt-5 d-flex gap-3">
                    <div class="d-flex align-items-center gap-2 text-white opacity-75">
                        <i class='bx bx-check-circle fs-4 text-success'></i> Terintegrasi
                    </div>
                    <div class="d-flex align-items-center gap-2 text-white opacity-75">
                        <i class='bx bx-check-circle fs-4 text-success'></i> Keamanan Tinggi
                    </div>
                </div>
            </div>

            <div class="brand-footer">
                &copy; <?php echo date('Y'); ?> RIZA PATIID - PROJECT.
            </div>
        </div>

        <!-- Right Form Section -->
        <div class="form-section">
            <div class="form-wrapper">
                <!-- Mobile Logo -->
                <div class="d-flex d-lg-none align-items-center gap-2 mb-4 justify-content-center">
                    <div class="bg-dark p-2 rounded-3 text-white d-flex align-items-center justify-content-center"
                        style="width:42px; height:42px;">
                        <i class='bx bxs-graduation fs-3'></i>
                    </div>
                    <h3 class="mb-0 fw-bold text-dark">SIAKAD<span style="color:#3b82f6;">.</span></h3>
                </div>

                <div class="text-center text-lg-start mb-4">
                    <h2 class="form-title">Selamat Datang</h2>
                    <p class="form-subtitle">Masuk ke portal administrasi Anda</p>
                </div>

                <?php if (isset($error))
                    echo "<div class='alert alert-danger p-3 mb-4 rounded-3 d-flex align-items-center gap-2 border-0 bg-danger bg-opacity-10 text-danger fw-medium'><i class='bx bx-error-circle fs-5'></i> $error</div>"; ?>

                <form method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" name="username" class="form-control" id="floatingUsername" required autofocus
                            placeholder="Username">
                        <label for="floatingUsername">Username Anda</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" name="password" class="form-control" id="floatingPassword" required
                            placeholder="Password">
                        <label for="floatingPassword">Kata Sandi</label>
                    </div>

                    <button type="submit" name="login" class="btn btn-login w-100 mt-2">
                        Masuk Dasbor <i class='bx bx-right-arrow-alt fs-4 ms-1'></i>
                    </button>

                    <div class="text-center mt-4">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#helpModal"
                            class="text-decoration-none fw-medium"
                            style="color: #64748b; font-size: 0.95rem; transition: color 0.2s;"
                            onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='#64748b'">
                            <i class='bx bx-help-circle me-1'></i> Butuh Bantuan?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-bottom-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pt-2 pb-5 px-4">
                    <div class="mb-4">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width: 80px; height: 80px; background-color: #f1f5f9; border: 1px solid #e2e8f0;">
                            <i class='bx bx-support' style="font-size: 3rem; color: #0f172a;"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Tidak bisa masuk portal?</h5>
                    <p class="text-muted mb-4" style="font-size: 0.95rem;">Jika Anda lupa kata sandi atau mengalami
                        kendala saat masuk, silakan hubungi administrator kami untuk memulihkan akses Anda.</p>

                    <a href="mailto:rizapatiid@gmail.com" class="btn rounded-3 px-4 py-2 fw-medium shadow-sm w-100 mb-3"
                        style="background-color: #0f172a; color: white; border: none; transition: all 0.3s ease;"
                        onmouseover="this.style.backgroundColor='#3b82f6'"
                        onmouseout="this.style.backgroundColor='#0f172a'">
                        <i class='bx bx-envelope me-1'></i> Hubungi Kami
                    </a>
                    <p class="mb-0 small text-muted">Atau kirim email ke: <strong
                            class="text-dark">rizapatiid@gmail.com</strong></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS (includes Popper for modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>