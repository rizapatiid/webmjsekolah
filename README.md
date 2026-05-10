# 🎓 SIAKAD - Sistem Informasi Akademik (Enterprise Native Edition)
**Dikembangkan oleh RIZA PATIID - PROJECT**

**SIAKAD** adalah platform manajemen sekolah berbasis web yang dibangun dengan arsitektur **PHP Native** murni dan **MySQL**. Dikembangkan dengan filosofi "Premium UI & Smart Logic", aplikasi ini menawarkan pengalaman pengguna yang setara dengan framework modern namun tetap ringan dan mudah dikelola di lingkungan server standar seperti XAMPP.

---

## 🌟 Fitur Unggulan & Logika Sistem

### 1. 🔐 Arsitektur Keamanan & Autentikasi
Aplikasi ini tidak hanya sekadar login, tetapi memiliki sistem proteksi akun yang proaktif:
*   **Auto-Provisioning Account:** Sistem secara cerdas melakukan sinkronisasi antara tabel Master (Guru/Siswa) dengan tabel Akun. Pengguna dapat langsung login menggunakan NIP/NIS sebagai username tanpa perlu registrasi manual oleh Admin.
*   **Mandatory Security Popup:** Akun baru dengan password default (`12345`) akan "terkunci" secara sistem. Muncul popup SweetAlert2 yang persisten dan tidak dapat ditutup, mewajibkan pengguna mengganti password sebelum diizinkan mengakses menu apapun.
*   **Encrypted Data:** Seluruh password disimpan dalam format hash MD5 di database untuk memastikan kerahasiaan data pengguna.
*   **Role Management (RBAC):** Pemisahan hak akses yang rigid antara **ADMIN** (Kendali Penuh), **PENGAJAR** (Input Nilai & Kelas), dan **SISWA** (Lihat Raport).

### 2. 🧠 Smart Grading Engine (Mesin Penilaian)
Logika penilaian dirancang untuk meminimalisir kesalahan input dan redundansi data:
*   **Smart Upsert (Merge Insert/Update):** Tidak ada lagi tombol "Tambah" dan "Edit" yang terpisah secara manual. Saat memilih siswa, sistem melakukan pengecekan keberadaan data di database. Jika ditemukan, form otomatis memuat data lama dan beralih ke mode **UPDATE**. Jika tidak, form akan dalam mode **INSERT**.
*   **Chained Dropdown Selection:** Menggunakan AJAX/Reload logic untuk memastikan integritas data. Guru memilih Mata Pelajaran -> Kelas -> baru kemudian daftar Siswa yang relevan akan muncul.
*   **Automated Weighted Calculation:** Sistem secara otomatis menghitung nilai akhir dengan bobot:
    *   `Tugas (30%)` + `UTS (30%)` + `UAS (40%)` = `Nilai Akhir (100%)`.

### 3. 🖼️ Branding & Personalization
*   **Institution Branding:** Admin dapat mengunggah logo sekolah yang akan otomatis diintegrasikan ke dalam seluruh laporan resmi (Kop Surat).
*   **Profile Management:** Setiap pengguna memiliki laman profil untuk mengelola data pribadi, foto profil, dan kredensial keamanan secara mandiri.

---

## 📖 Penjelasan Modul & Laman

### 📂 Modul Administrasi (Hanya Admin)
*   **Pengelola Pengguna (`admin.php`):** Manajemen akun login seluruh staf dan siswa.
*   **Data Master Siswa & Guru:** Kelola biodata lengkap, alamat, kontak, dan foto formal.
*   **Data Kelas:** Pengaturan rombongan belajar dan pemetaan Wali Kelas.
*   **Mata Pelajaran:** Definisi kurikulum dan penunjukan guru pengampu per mapel.
*   **Pengaturan Sekolah:** Konfigurasi global nama sekolah, alamat, logo, serta Tahun Ajaran/Semester aktif.

### 📂 Modul Akademik (Admin & Pengajar)
*   **Input Nilai (`nilai.php`):** Antarmuka utama untuk perekapan nilai harian hingga ujian semester.
*   **Transkrip Nilai (`cetak_transkrip.php`):** Laman generate laporan yang dirancang dengan presisi tinggi untuk pencetakan, lengkap dengan kalkulasi statistik (Total & Rata-rata) otomatis.

---

## 🛡️ Keamanan & Proteksi (Security First)
Aplikasi ini telah melalui audit keamanan dan dilengkapi dengan proteksi terhadap serangan web umum:
*   **RBAC Enforcement:** Pengecekan role yang ketat di setiap laman administratif untuk mencegah akses ilegal.
*   **Secure Uploads:** Validasi ekstensi file (`jpg`, `png`, `webp`) untuk mencegah eksekusi skrip jahat (*Anti-Shell/RCE*).
*   **XSS Protection:** Sanitasi output menggunakan `htmlspecialchars` untuk mencegah pencurian sesi.
*   **SQL Injection Prevention:** Penggunaan `real_escape_string` dan validasi tipe data pada seluruh input pengguna.

---

## 🛠️ Arsitektur & Teknologi

| Komponen | Teknologi | Keterangan |
| :--- | :--- | :--- |
| **Backend** | PHP 8.x | Native procedure, teroptimasi untuk PHP 8.1 ke atas. |
| **Database** | MySQL | Skema relasional terindeks untuk kecepatan query data besar. |
| **Frontend** | Bootstrap 5.3 | Framework CSS untuk responsivitas di semua perangkat. |
| **Icons** | BoxIcons 2.1 | Library icon vektor yang tajam dan modern. |
| **Popups** | SweetAlert2 | Pengganti alert standar browser untuk UX yang lebih premium. |
| **Typography** | Plus Jakarta Sans | Google Fonts pilihan untuk tampilan korporat/profesional. |
| **Data Grid** | DataTables | Fitur pencarian, filtering, dan paging otomatis pada setiap tabel. |

---

## 📂 Struktur Direktori Proyek
```text
sekolah_php/
├── assets/                 # Aset Statis
│   ├── css/                # Custom Stylesheets (Vibe Modern)
│   ├── js/                 # Logika JavaScript & AJAX
│   └── img/                # Media: Foto Siswa, Guru, & Logo Sekolah
├── config/                 # Inti Konfigurasi
│   └── db.php              # Koneksi Database MySQLi
├── layouts/                # Modular UI Components
│   ├── header.php          # Meta tags & Global Assets
│   ├── navbar.php          # Sidebar dinamis berdasarkan Role
│   └── footer.php          # Global Scripts & Flash Messages
├── pages/                  # Modul Fungsional Utama
│   ├── dashboard.php       # Ringkasan Statistik
│   ├── nilai.php           # Logika Smart Grading
│   ├── cetak_transkrip.php # Report Engine
│   └── ...                 # File CRUD lainnya
├── index.php               # Portal Autentikasi
└── sekolah.sql             # Skema & Dump Database Lengkap
```

---

## ⚙️ Panduan Instalasi (XAMPP Environment)

1.  **Deployment:**
    Salin atau pindahkan seluruh isi folder `sekolah_php` ke folder `htdocs` XAMPP Anda (biasanya di `C:\xampp\htdocs\`).
2.  **Web Server:**
    Buka Control Panel XAMPP, jalankan modul **Apache** dan **MySQL**.
3.  **Database Migration:**
    - Buka `http://localhost/phpmyadmin/`.
    - Buat database baru dengan nama `sekolah_db`.
    - Pilih database tersebut, masuk ke tab **Import**, pilih file `sekolah.sql`, lalu klik **Go**.
4.  **Konfigurasi Koneksi:**
    Buka file `config/db.php` jika Anda menggunakan kredensial database yang berbeda dari default (root, no password).
5.  **Akses Browser:**
    Kunjungi `http://localhost/sekolah_php/`.

---

## 🔑 Hak Akses Pengguna

Coba Demo : https://websekolahriza.unaux.com/

| Role | Username | Password | Deskripsi Akses |
| :--- | :--- | :--- | :--- |
| **ADMIN** | `admin` | `admin123` | Akses penuh ke seluruh menu dan sistem. |
| **PENGAJAR** | [NIP GURU] | `12345` | Akses manajemen nilai dan daftar siswa. |
| **SISWA** | [NIS SISWA] | `12345` | Akses lihat dan cetak transkrip nilai pribadi. |

---

&copy; 2026 **RIZA PATIID - PROJECT**. Dikembangkan dengan dedikasi untuk efisiensi pendidikan digital.
