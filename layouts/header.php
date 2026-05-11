<?php
/**
 * Copyright 2026 RIZAPATIID - PROJECT
 * Email : rizapatiid@gmail.com
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch Display Name logic
$user_display_name = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'];
$username = $_SESSION['user']['username'];

if ($role == 'siswa') {
    $res_n = $conn->query("SELECT nama FROM siswa WHERE nis = '$username'");
    if ($res_n && $row_n = $res_n->fetch_assoc()) $user_display_name = $row_n['nama'];
} elseif ($role == 'guru') {
    $res_n = $conn->query("SELECT nama FROM guru WHERE nip = '$username'");
    if ($res_n && $row_n = $res_n->fetch_assoc()) $user_display_name = $row_n['nama'];
}

// Mandatory Password Change Check
$default_password_md5 = md5('12345');
$is_default_password = ($_SESSION['user']['password'] === $default_password_md5);
$current_filename = basename($_SERVER['PHP_SELF']);

// We will handle the popup in footer.php, but we keep this check 
// to ensure they can't access other pages if they try to bypass.
// Actually, let's let them see the page but with a persistent popup.

$page_title = $page_title ?? 'SIAKAD Profesional';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- BoxIcons -->
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php
    $fav_q = $conn->query("SELECT logo FROM pengaturan_sekolah WHERE id=1");
    $fav_p = $fav_q ? $fav_q->fetch_assoc() : null;
    $favicon_path = $fav_p['logo'] ?? '';
    if (!empty($favicon_path) && file_exists('../' . $favicon_path)): ?>
        <link rel="icon" type="image/x-icon" href="../<?php echo $favicon_path; ?>">
    <?php endif; ?>
    <style>
        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content {
            flex: 1;
            padding: 1rem;
            background-color: var(--bg-color);
            transition: all 0.3s;
        }
        @media (min-width: 992px) {
            .main-wrapper {
                flex-direction: row;
            }
            .main-content {
                padding: 2rem;
                margin-left: 260px; /* Same as sidebar width */
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include 'navbar.php'; ?>
        <div class="main-content w-100">
