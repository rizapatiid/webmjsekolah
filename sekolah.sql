-- Copyright 2026 RIZAPATIID - PROJECT
-- Email : rizapatiid@gmail.com
CREATE DATABASE IF NOT EXISTS sekolah_db;
USE sekolah_db;

-- 1. Tabel Pengguna (Login)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guru', 'siswa') DEFAULT 'admin'
);

-- Data Pengguna dari Screenshot
INSERT INTO users (id, username, password, role) VALUES 
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'admin'),
(6, 'P098765432', '92afb435ceb16630e9827f54330c59c9', 'guru'),
(7, 'IKP8765678', 'bcd724d15cde8c47650fda962968f102', 'siswa');

-- 2. Tabel Guru
CREATE TABLE IF NOT EXISTS guru (
    nip VARCHAR(20) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telp VARCHAR(20) NOT NULL,
    agama VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Aktif'
);

-- Data Guru dari Screenshot
INSERT INTO guru (nip, nama, telp, agama, alamat, foto, status) VALUES 
('P098765432', 'CITRA CANTIKA', '081234567890', 'Islam', 'DSFHBHB', 'uploads/guru_P098765432.webp', 'Aktif'),
('P098765434', 'USMAN ALIILYAS', '082322691087', 'Islam', 'Ngemplak Kidul Margoyoso', 'uploads/guru_P098765434.avif', 'Aktif');

-- 3. Tabel Kelas
CREATE TABLE IF NOT EXISTS kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(50) NOT NULL,
    wali_guru VARCHAR(20),
    FOREIGN KEY (wali_guru) REFERENCES guru(nip) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Data Kelas dari Screenshot
INSERT INTO kelas (id, nama_kelas, wali_guru) VALUES 
(2, 'X INFORMATIKA', 'P098765432'),
(3, 'X AKUTANSI', 'P098765434');

-- 4. Tabel Siswa
CREATE TABLE IF NOT EXISTS siswa (
    nis VARCHAR(20) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    kelas VARCHAR(50) DEFAULT NULL,
    jurusan VARCHAR(50) NOT NULL,
    agama VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL,
    no_hp VARCHAR(20) NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Aktif',
    tahun_ajaran VARCHAR(20) DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL
);

-- Data Siswa dari Screenshot
INSERT INTO siswa (nis, nama, kelas, jurusan, agama, alamat, no_hp, foto, status, tahun_ajaran, semester) VALUES 
('DM876544', 'DONI SETIAWAN', 'X INFORMATIKA', 'TEKNIK INFORMATIKA', 'Islam', 'Ngemplak Kidul Margoyoso', '75644347687', NULL, 'Aktif', '2025/2026', 'Ganjil'),
('IKP8765678', 'ARMAN MAULANA', 'X INFORMATIKA', 'TEKNIK INFORMATIKA', 'Islam', 'dachb', '876542', 'uploads/siswa_qw34567890..avif', 'Aktif', '2025/2026', 'Ganjil');

-- 5. Tabel Mata Pelajaran
CREATE TABLE IF NOT EXISTS mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_mapel VARCHAR(100) NOT NULL,
    jurusan VARCHAR(50) NOT NULL,
    nip_guru VARCHAR(20) NOT NULL,
    FOREIGN KEY (nip_guru) REFERENCES guru(nip) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Data Mapel dari Screenshot
INSERT INTO mapel (id, nama_mapel, jurusan, nip_guru) VALUES 
(1, 'ILMU PENGETAHUAN ALAM', 'TEKNIK INFORMATIKA', 'P098765432'),
(2, 'MATEMATIKA', 'TEKNIK INFORMATIKA', 'P098765434'),
(3, 'BAHASA INDONESIA', 'TEKNIK INFORMATIKA', 'P098765432');

-- 6. Tabel Pivot Kelas-Mapel
CREATE TABLE IF NOT EXISTS kelas_mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas INT,
    id_mapel INT,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_mapel) REFERENCES mapel(id) ON DELETE CASCADE
);

-- Data Mapping dari Screenshot
INSERT INTO kelas_mapel (id, id_kelas, id_mapel) VALUES 
(5, 3, 3), (6, 3, 1), (7, 3, 2),
(8, 2, 3), (9, 2, 1), (10, 2, 2);

-- 7. Tabel Nilai
CREATE TABLE IF NOT EXISTS nilai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis_siswa VARCHAR(20) NOT NULL,
    id_mapel INT NOT NULL,
    tugas1 DECIMAL(5,2) DEFAULT 0,
    tugas2 DECIMAL(5,2) DEFAULT 0,
    tugas3 DECIMAL(5,2) DEFAULT 0,
    tugas4 DECIMAL(5,2) DEFAULT 0,
    uts DECIMAL(5,2) DEFAULT 0,
    uas DECIMAL(5,2) DEFAULT 0,
    FOREIGN KEY (nis_siswa) REFERENCES siswa(nis) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_mapel) REFERENCES mapel(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Data Nilai dari Screenshot
INSERT INTO nilai (id, nis_siswa, id_mapel, tugas1, tugas2, tugas3, tugas4, uts, uas) VALUES 
(1, 'IKP8765678', 2, 100.00, 60.00, 80.00, 85.00, 70.00, 95.00),
(2, 'DM876544', 1, 100.00, 90.00, 95.00, 100.00, 78.00, 98.00),
(3, 'IKP8765678', 1, 100.00, 70.00, 78.00, 96.00, 0.00, 100.00),
(4, 'IKP8765678', 3, 90.00, 80.00, 0.00, 0.00, 78.00, 100.00);

-- 8. Tabel Pengaturan Sekolah
CREATE TABLE IF NOT EXISTS pengaturan_sekolah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_sekolah VARCHAR(100) NOT NULL,
    tahun_ajaran VARCHAR(20) NOT NULL,
    daftar_tahun_ajaran TEXT,
    semester ENUM('Ganjil', 'Genap') DEFAULT 'Ganjil',
    daftar_jurusan TEXT,
    kepala_sekolah VARCHAR(100),
    email VARCHAR(100),
    telepon VARCHAR(20),
    alamat TEXT,
    logo VARCHAR(255) DEFAULT NULL
);

-- Data Pengaturan dari Screenshot
INSERT INTO pengaturan_sekolah (id, nama_sekolah, tahun_ajaran, semester, daftar_jurusan, email, telepon, alamat, kepala_sekolah, daftar_tahun_ajaran, logo) VALUES 
(1, 'SMK UJICOBA PATI', '2025/2026', 'Ganjil', 'TEKNIK INFORMATIKA, TEKNIK ELEKTRO, AKUTANSI', 'admin@sekolah.com', '021-88888999', 'JL Mekarti Supriadi Ciptaadi Kec. Mantalengka Kab....', 'Budi Santoso, M.Pd', '2025/2026', 'assets/img/logo_sekolah_1778423630.jpg');
