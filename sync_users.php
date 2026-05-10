<?php
include 'config/db.php';

// Sync Siswa
$siswa = $conn->query("SELECT nis FROM siswa");
while ($s = $siswa->fetch_assoc()) {
    $nis = $s['nis'];
    $check = $conn->query("SELECT id FROM users WHERE username = '$nis'");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO users (username, password, role) VALUES ('$nis', MD5('12345'), 'siswa')");
        echo "Created user for Siswa: $nis\n";
    }
}

// Sync Guru
$guru = $conn->query("SELECT nip FROM guru");
while ($g = $guru->fetch_assoc()) {
    $nip = $g['nip'];
    $check = $conn->query("SELECT id FROM users WHERE username = '$nip'");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO users (username, password, role) VALUES ('$nip', MD5('12345'), 'guru')");
        echo "Created user for Guru: $nip\n";
    }
}

echo "Sync completed.";
?>
