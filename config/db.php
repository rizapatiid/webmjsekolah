<?php
/**
 * Copyright 2026 RIZAPATIID - PROJECT
 * Email : rizapatiid@gmail.com
 */
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sekolah_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Auto add column tipe_instansi if not exists
$check_ti = $conn->query("SHOW COLUMNS FROM pengaturan_sekolah LIKE 'tipe_instansi'");
if ($check_ti && $check_ti->num_rows == 0) {
    $conn->query("ALTER TABLE pengaturan_sekolah ADD COLUMN tipe_instansi VARCHAR(20) DEFAULT 'Sekolah'");
}

// Load global config for labels
$pengaturan_global_res = $conn->query("SELECT tipe_instansi FROM pengaturan_sekolah WHERE id=1");
$pengaturan_global = $pengaturan_global_res ? $pengaturan_global_res->fetch_assoc() : null;
$tipe_instansi = $pengaturan_global['tipe_instansi'] ?? 'Sekolah';

if ($tipe_instansi == 'Kampus') {
    define('LBL_SISWA', 'Mahasiswa');
    define('LBL_GURU', 'Dosen');
    define('LBL_KELAS', 'Semester');
    define('LBL_WALI', 'Pembimbing');
    define('LBL_INSTANSI', 'Kampus');
    define('LBL_MAPEL', 'Mata Kuliah');
} else {
    define('LBL_SISWA', 'Siswa');
    define('LBL_GURU', 'Guru');
    define('LBL_KELAS', 'Kelas');
    define('LBL_WALI', 'Wali');
    define('LBL_INSTANSI', 'Sekolah');
    define('LBL_MAPEL', 'Mata Pelajaran');
}
?>
