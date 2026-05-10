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

$nis = $_GET['nis'] ?? '';
if (!$nis) {
    echo "Pilih siswa terlebih dahulu.";
    exit;
}

// Ambil data siswa
$s_query = $conn->query("SELECT * FROM siswa WHERE nis = '$nis'");
$siswa = $s_query->fetch_assoc();

// Ambil data sekolah
$p_query = $conn->query("SELECT * FROM pengaturan_sekolah WHERE id = 1");
$pengaturan = $p_query->fetch_assoc();

// Ambil semua mapel
$mapel_res = $conn->query("SELECT * FROM mapel ORDER BY nama_mapel ASC");
$all_mapel = [];
while($m = $mapel_res->fetch_assoc()) {
    $all_mapel[] = $m;
}

// Ambil semua nilai siswa ini
$nilai_res = $conn->query("SELECT * FROM nilai WHERE nis_siswa = '$nis'");
$nilai_data = [];
while($n = $nilai_res->fetch_assoc()) {
    $nilai_data[$n['id_mapel']][$pengaturan['semester']] = $n;
}

// Ambil Nama Wali Kelas siswa
$nama_k_siswa = $siswa['kelas'];
$wk_query = $conn->query("SELECT g.nama, g.nip FROM kelas k JOIN guru g ON k.wali_guru = g.nip WHERE k.nama_kelas = '$nama_k_siswa'");
$wali = $wk_query->fetch_assoc();
$nama_wali = $wali['nama'] ?? '...........................................';
$nip_wali = $wali['nip'] ?? '...................................';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transkrip Nilai - <?php echo $siswa['nama']; ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12px; line-height: 1.4; color: #000; margin: 0; padding: 20px; }
        .page { width: 210mm; min-height: 297mm; padding: 10mm; margin: auto; background: white; border: 1px solid #ddd; position: relative; box-sizing: border-box; }
        
        /* Batik Border effect */
        .page::before {
            content: "";
            position: absolute;
            top: 5px; left: 5px; right: 5px; bottom: 5px;
            border: 2px solid #000;
            pointer-events: none;
        }

        .header { display: flex; align-items: center; justify-content: center; margin-bottom: 20px; position: relative; }
        .header-logo { position: absolute; left: 0; top: 0; }
        .header-logo img { max-height: 80px; width: auto; }
        .header-text { text-align: center; width: 100%; padding: 0 90px; }
        .header h1 { font-size: 16px; margin: 0; text-transform: uppercase; }
        .header h2 { font-size: 14px; margin: 5px 0; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 11px; }
        .line { border-bottom: 3px double #000; margin: 10px 0; }

        .title { text-align: center; margin-bottom: 20px; }
        .title h3 { font-size: 16px; margin: 0; text-decoration: underline; }
        .title p { margin: 5px 0; font-weight: bold; }

        .bio-table { width: 100%; margin-bottom: 20px; }
        .bio-table td { padding: 2px 0; vertical-align: top; }
        .bio-table td:first-child { width: 30px; }
        .bio-table td:nth-child(2) { width: 200px; }
        .bio-table td:nth-child(3) { width: 10px; }

        .grade-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grade-table th, .grade-table td { border: 1px solid #000; padding: 5px; }
        .grade-table th { background-color: #f2f2f2; text-transform: uppercase; font-size: 10px; }
        .grade-table .group-title { background-color: #e9e9e9; font-weight: bold; text-align: left; }
        .text-center { text-align: center; }

        @media print {
            body { padding: 0; }
            .page { border: none; margin: 0; width: 100%; box-shadow: none; }
            .no-print { display: none; }
        }
        
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-print { padding: 10px 20px; background: #0f172a; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Cetak Transkrip (PDF)</button>
        <p><small>*Gunakan Chrome/Edge "Save as PDF" untuk hasil terbaik</small></p>
    </div>

    <div class="page">
        <div class="header">
            <?php if (!empty($pengaturan['logo'])): ?>
                <div class="header-logo">
                    <img src="../<?php echo $pengaturan['logo']; ?>" alt="Logo">
                </div>
            <?php endif; ?>
            <div class="header-text">
                <h1>Pemerintah Provinsi <?php echo $pengaturan['alamat'] ? 'Jawa Tengah' : 'Nama Provinsi'; ?></h1>
                <h2>Dinas Pendidikan dan Kebudayaan</h2>
                <h1 style="font-size: 20px; margin-top: 5px;"><?php echo $pengaturan['nama_sekolah']; ?></h1>
                <p><?php echo $pengaturan['alamat']; ?> | Telp: <?php echo $pengaturan['telepon']; ?></p>
            </div>
        </div>
        
        <div class="line"></div>

        <div class="title">
            <h3>TRANSKRIP NILAI</h3>
            <p>TAHUN PELAJARAN <?php echo $pengaturan['tahun_ajaran']; ?></p>
            <p style="font-size: 10px; font-weight: normal;">( Kutipan Dari Buku Induk Siswa )</p>
        </div>

        <table class="bio-table">
            <tr>
                <td>1.</td>
                <td>Nama</td>
                <td>:</td>
                <td><strong><?php echo strtoupper($siswa['nama']); ?></strong></td>
            </tr>
            <tr>
                <td>2.</td>
                <td>Tempat dan Tanggal Lahir</td>
                <td>:</td>
                <td><?php echo $siswa['alamat']; // Menggunakan alamat sebagai placeholder tempat lahir ?></td>
            </tr>
            <tr>
                <td>3.</td>
                <td>Nomor Induk Siswa (NIS)</td>
                <td>:</td>
                <td><?php echo $siswa['nis']; ?></td>
            </tr>
            <tr>
                <td>4.</td>
                <td>Nomor Induk Siswa Nasional (NISN)</td>
                <td>:</td>
                <td><?php echo $siswa['nis']; ?>0001 (Contoh)</td>
            </tr>
            <tr>
                <td>5.</td>
                <td>Kompetensi Keahlian</td>
                <td>:</td>
                <td><?php echo $siswa['jurusan']; ?></td>
            </tr>
        </table>

        <table class="grade-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30px;">No.</th>
                    <th rowspan="2">Mata Pelajaran</th>
                    <th colspan="6">Nilai Perolehan Semester</th>
                </tr>
                <tr>
                    <th style="width: 30px;">1</th>
                    <th style="width: 30px;">2</th>
                    <th style="width: 30px;">3</th>
                    <th style="width: 30px;">4</th>
                    <th style="width: 30px;">5</th>
                    <th style="width: 30px;">6</th>
                </tr>
            </thead>
                <?php 
                $no = 1;
                $total_akhir = 0;
                $count_nilai = 0;
                foreach($all_mapel as $m): 
                    $n_current = $nilai_data[$m['id']][$pengaturan['semester']] ?? null;
                    $final_grade = 0;
                    $display_grade = '-';
                    
                    if ($n_current) {
                        $final_grade = (($n_current['tugas1']+$n_current['tugas2']+$n_current['tugas3']+$n_current['tugas4'])/4 * 0.3) + ($n_current['uts']*0.3) + ($n_current['uas']*0.4);
                        $display_grade = number_format($final_grade, 0);
                        $total_akhir += $final_grade;
                        $count_nilai++;
                    }
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo $m['nama_mapel']; ?></td>
                    <td class="text-center"><?php echo ($pengaturan['semester'] == 'Ganjil' && $pengaturan['tahun_ajaran'] == $siswa['tahun_ajaran']) ? $display_grade : '-'; ?></td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">JUMLAH NILAI</th>
                    <th class="text-center"><?php echo $total_akhir > 0 ? number_format($total_akhir, 0) : '-'; ?></th>
                    <th colspan="5" style="background: #f2f2f2;"></th>
                </tr>
                <tr>
                    <th colspan="2" class="text-end">RATA-RATA</th>
                    <th class="text-center"><?php echo $count_nilai > 0 ? number_format($total_akhir / $count_nilai, 1) : '-'; ?></th>
                    <th colspan="5" style="background: #f2f2f2;"></th>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="text-align: center; width: 200px;">
                <p>Mengetahui,</p>
                <p>Orang Tua/Wali Murid</p>
                <br><br><br>
                <p>...........................................</p>
            </div>
            <div style="text-align: center; width: 200px;">
                <p><?php echo $pengaturan['alamat'] ? explode(',', $pengaturan['alamat'])[0] : 'Semarang'; ?>, <?php echo date('d F Y'); ?></p>
                <p>Wali Kelas</p>
                <br><br><br>
                <p><strong><u><?php echo $nama_wali; ?></u></strong></p>
                <p>NIP. <?php echo $nip_wali; ?></p>
            </div>
        </div>

        <div style="margin-top: 40px; text-align: center; clear: both;">
            <p>Mengetahui,</p>
            <p>Kepala Sekolah</p>
            <br><br><br>
            <p><strong><u><?php echo $pengaturan['kepala_sekolah']; ?></u></strong></p>
            <p>NIP. 19720101 199803 1 002</p>
        </div>
    </div>
</body>
</html>
