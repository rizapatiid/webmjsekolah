<?php
include 'config/db.php';
$conn->query("ALTER TABLE siswa MODIFY COLUMN kelas VARCHAR(50)");
$conn->query("ALTER TABLE siswa MODIFY COLUMN jurusan VARCHAR(100)");
echo "Database updated successfully";
?>
