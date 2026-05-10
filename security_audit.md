# 🛡️ Laporan Audit Keamanan Sistem - SIAKAD Profesional

Berdasarkan analisis kode sumber, ditemukan beberapa celah keamanan yang perlu segera diperbaiki untuk menjaga integritas data dan keamanan server.

## 🔴 Temuan Kritis (High Priority)

### 1. Authorization Bypass (Vertical Privilege Escalation)
- **Celah:** Laman administratif seperti `siswa.php`, `guru.php`, dan `admin.php` hanya memeriksa apakah user sudah login, namun tidak memeriksa **Role** (Hak Akses).
- **Dampak:** Siswa yang login dapat mengakses laman Admin dengan mengetikkan URL secara manual, bahkan dapat menghapus data atau mengubah nilai mereka sendiri.
- **Solusi:** Menambahkan pengecekan `if ($role != 'admin')` di bagian atas setiap laman sensitif.

### 2. Remote Code Execution (RCE) via File Upload
- **Celah:** Fitur upload foto siswa dan guru tidak memvalidasi ekstensi file.
- **Dampak:** Penyerang dapat mengunggah file `.php` (webshell) yang memungkinkan mereka mengambil alih server sepenuhnya.
- **Solusi:** Membatasi ekstensi file hanya untuk `jpg`, `jpeg`, `png`, dan `webp`, serta melakukan validasi MIME type.

### 3. Cross-Site Scripting (XSS) - Stored
- **Celah:** Data yang ditampilkan dari database (nama siswa, alamat, dll) langsung di-output tanpa fungsi sanitasi.
- **Dampak:** Penyerang dapat memasukkan skrip JavaScript jahat ke dalam nama siswa yang akan dieksekusi di browser Admin (pencurian cookie session).
- **Solusi:** Menggunakan fungsi `htmlspecialchars()` pada setiap output data dinamis.

---

## 🟡 Temuan Menengah (Medium Priority)

### 1. Insecure Password Hashing
- **Celah:** Sistem menggunakan `MD5` yang sudah dianggap usang dan mudah di-crack.
- **Dampak:** Jika database bocor, password pengguna dapat diketahui dengan cepat.
- **Solusi:** Migrasi ke `password_hash()` dan `password_verify()` (bcrypt).

### 2. SQL Injection Risk
- **Celah:** Meskipun sudah menggunakan `real_escape_string()`, penggunaan query manual masih berisiko jika ada yang terlewat.
- **Solusi:** Migrasi ke **Prepared Statements** (PDO atau MySQLi Prepared).

### 3. Cross-Site Request Forgery (CSRF)
- **Celah:** Tidak ada token CSRF pada formulir input/update.
- **Dampak:** Penyerang dapat membuat form palsu di website lain yang mengeksekusi aksi di SIAKAD saat admin sedang login.
- **Solusi:** Implementasi CSRF Token pada setiap form `POST`.

---

## 📋 Rencana Perbaikan Segera

1.  **Penguatan Role Check:** Memperketat akses laman berdasarkan role di `siswa.php`, `guru.php`, `admin.php`, dan `mapel.php`.
2.  **Validasi Upload:** Memperbaiki fungsi upload foto agar hanya menerima gambar.
3.  **Output Sanitizing:** Menerapkan `htmlspecialchars` secara konsisten di seluruh aplikasi.
