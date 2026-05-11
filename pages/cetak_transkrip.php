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
    echo "Pilih " . strtolower(LBL_SISWA) . " terlebih dahulu.";
    exit;
}

// Ambil data siswa
$s_query = $conn->query("SELECT * FROM siswa WHERE nis = '$nis'");
$siswa = $s_query->fetch_assoc();

// Ambil data sekolah
$p_query = $conn->query("SELECT * FROM pengaturan_sekolah WHERE id = 1");
$pengaturan = $p_query->fetch_assoc();

// Ambil semua mata kuliah yang sudah ada nilainya untuk siswa ini (Kumulatif)
$mapel_nilai_res = $conn->query("SELECT m.*, n.tugas1, n.tugas2, n.tugas3, n.tugas4, n.uts, n.uas, n.semester as n_semester 
                                 FROM nilai n 
                                 JOIN mapel m ON n.id_mapel = m.id 
                                 WHERE n.nis_siswa = '$nis' 
                                 ORDER BY m.semester ASC, m.nama_mapel ASC");
$transkrip_data = [];
while($row = $mapel_nilai_res->fetch_assoc()) {
    $transkrip_data[] = $row;
}

// Ambil Nama Wali Kelas siswa
$nama_k_siswa = $siswa['kelas'];
$wk_query = $conn->query("SELECT g.nama, g.nip FROM kelas k JOIN guru g ON k.wali_guru = g.nip WHERE k.nama_kelas = '$nama_k_siswa'");
$wali = $wk_query->fetch_assoc();
$nama_wali = $wali['nama'] ?? '...........................................';
$nip_wali = $wali['nip'] ?? '...................................';

// Ambil data Kaprodi berdasarkan Jurusan
$j_query = $conn->query("SELECT kaprodi, nip_kaprodi FROM jurusan WHERE nama_jurusan = '{$siswa['jurusan']}'");
$jurusan_info = $j_query ? $j_query->fetch_assoc() : null;
$nama_kaprodi = $jurusan_info['kaprodi'] ?? '...........................................';
$nip_kaprodi = $jurusan_info['nip_kaprodi'] ?? '...................................';
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

        /* Premium UNG Style Polish */
        .page { 
            width: 210mm; 
            min-height: 297mm; 
            padding: 15mm; 
            margin: auto; 
            background: white; 
            border: 1px solid #e2e8f0; 
            position: relative; 
            box-sizing: border-box; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        /* Subtle Decorative Frame */
        .page::before {
            content: "";
            position: absolute;
            top: 10px; left: 10px; right: 10px; bottom: 10px;
            border: 1px solid #cbd5e1;
            pointer-events: none;
            z-index: 10;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(0,0,0,0.03);
            font-weight: 800;
            pointer-events: none;
            text-transform: uppercase;
            letter-spacing: 10px;
            white-space: nowrap;
            z-index: 0;
        }

        .ung-header { text-align: center; border-bottom: 3px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
        .ung-logo { width: 90px; height: auto; }
        .ung-header-text { flex: 1; text-align: center; }
        .ung-header h1 { font-size: 26px; margin: 0; font-weight: 900; color: #0f172a; line-height: 1; }
        .ung-header h2 { font-size: 15px; margin: 5px 0 0; font-weight: 700; color: #334155; }
        .ung-header p { font-size: 10px; margin: 5px 0 0; color: #64748b; font-style: normal; }
        
        .ung-title { text-align: center; margin-bottom: 25px; }
        .ung-title h3 { font-size: 18px; font-weight: 900; border-bottom: 2px solid #0f172a; display: inline-block; padding: 5px 40px; color: #0f172a; }

        .ung-bio { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 11.5px; z-index: 1; position: relative; }
        .ung-bio td { padding: 3px 0; vertical-align: top; }
        .ung-bio .label { width: 110px; color: #475569; }
        .ung-bio .separator { width: 15px; text-align: center; color: #475569; }
        .ung-bio .value { color: #0f172a; }
        
        .ung-table { width: 100%; border-collapse: collapse; font-size: 10.5px; z-index: 1; position: relative; }
        .ung-table th, .ung-table td { border: 1px solid #475569; padding: 4px 6px; }
        .ung-table th { background: #f1f5f9; color: #0f172a; font-weight: 800; }
        .ung-table tr:nth-child(even) { background: #f8fafc; }
        
        .ung-footer { display: flex; justify-content: space-between; margin-top: 20px; font-size: 11.5px; border-top: 1px dashed #cbd5e1; padding-top: 15px; }
        .ung-stats { width: 48%; }
        
        .ung-sign-container { display: flex; justify-content: space-between; margin-top: 40px; position: relative; }
        .ung-sign-box { text-align: center; width: 260px; font-size: 12px; }
        .ung-qrcode { width: 75px; height: 75px; border: 1px solid #e2e8f0; padding: 5px; margin-top: 10px; }
        .ung-stamp { position: absolute; left: 20px; top: -5px; width: 110px; opacity: 0.4; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Cetak Transkrip (PDF)</button>
        <p><small>*Gunakan Chrome/Edge "Save as PDF" untuk hasil terbaik</small></p>
    </div>

    <div class="page">
        <div class="watermark">ORIGINAL - TRANSKRIP</div>
        
        <?php if (LBL_INSTANSI == 'Kampus'): ?>
            <div class="ung-header">
                <?php if (!empty($pengaturan['logo'])): ?>
                    <img src="../<?php echo $pengaturan['logo']; ?>" alt="Logo" class="ung-logo">
                <?php endif; ?>
                <div class="ung-header-text">
                    <h1><?php echo strtoupper($pengaturan['nama_sekolah']); ?></h1>
                    <h2><?php echo strtoupper($pengaturan['nama_yayasan'] ?? 'FAKULTAS ' . $siswa['jurusan']); ?></h2>
                    <p><?php echo $pengaturan['alamat']; ?> | Telp: <?php echo $pengaturan['telepon']; ?></p>
                </div>
            </div>
            
            <div class="ung-title">
                <h3>TRANSKRIP NILAI AKADEMIK</h3>
            </div>

            <table class="ung-bio">
                <tr>
                    <td style="width: 50%;">
                        <table style="width: 100%;">
                            <tr><td class="label">Nomor Ijazah</td><td class="separator">:</td><td class="value">421/<?php echo strtoupper(LBL_INSTANSI); ?>/S/TF/2026</td></tr>
                            <tr><td class="label">NIM</td><td class="separator">:</td><td class="value"><strong><?php echo $siswa['nis']; ?></strong></td></tr>
                            <tr><td class="label">Nama</td><td class="separator">:</td><td class="value"><strong><?php echo strtoupper($siswa['nama']); ?></strong></td></tr>
                            <tr><td class="label">Tmp Lahir</td><td class="separator">:</td><td class="value"><?php echo strtoupper($siswa['tempat_lahir'] ?: '-'); ?></td></tr>
                            <tr><td class="label">Tgl Lahir</td><td class="separator">:</td><td class="value"><?php echo $siswa['tanggal_lahir'] ? date('d F Y', strtotime($siswa['tanggal_lahir'])) : '-'; ?></td></tr>
                        </table>
                    </td>
                    <td style="width: 50%; padding-left: 30px;">
                        <table style="width: 100%;">
                            <tr><td class="label">Prog. Studi</td><td class="separator">:</td><td class="value"><?php echo $siswa['jurusan']; ?> (S1)</td></tr>
                            <tr><td class="label">Jurusan</td><td class="separator">:</td><td class="value"><?php echo $siswa['jurusan']; ?></td></tr>
                            <tr><td class="label">Fakultas</td><td class="separator">:</td><td class="value"><?php echo $siswa['jurusan']; ?></td></tr>
                            <tr><td class="label">Masuk</td><td class="separator">:</td><td class="value"><?php echo $siswa['tahun_masuk'] ?: '-'; ?></td></tr>
                            <tr><td class="label">Lulus</td><td class="separator">:</td><td class="value">-</td></tr>
                            <tr><td class="label">Status</td><td class="separator">:</td><td class="value"><span style="color: #059669; font-weight: bold;">Terakreditasi A</span></td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        <?php else: ?>
            <div class="header">
                <?php if (!empty($pengaturan['logo'])): ?>
                    <div class="header-logo">
                        <img src="../<?php echo $pengaturan['logo']; ?>" alt="Logo">
                    </div>
                <?php endif; ?>
                <div class="header-text">
                    <h1><?php echo strtoupper($pengaturan['nama_yayasan'] ?? 'PEMERINTAH PROVINSI JAWA TENGAH'); ?></h1>
                    <h2><?php echo strtoupper($pengaturan['nama_sekolah']); ?></h2>
                    <h1 style="font-size: 20px; margin-top: 5px;"><?php echo strtoupper($pengaturan['nama_sekolah']); ?></h1>
                    <p><?php echo $pengaturan['alamat']; ?> | Telp: <?php echo $pengaturan['telepon']; ?></p>
                </div>
            </div>
            <div class="line"></div>

            <div class="title">
                <h3>TRANSKRIP NILAI</h3>
                <p>TAHUN PELAJARAN <?php echo $pengaturan['tahun_ajaran']; ?></p>
                <p style="font-size: 10px; font-weight: normal;">( Kutipan Dari Buku Induk <?php echo LBL_SISWA; ?> )</p>
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
                <td><?php echo ($siswa['tempat_lahir'] ?: '-') . ', ' . ($siswa['tanggal_lahir'] ? date('d F Y', strtotime($siswa['tanggal_lahir'])) : '-'); ?></td>
            </tr>
            <tr>
                <td>3.</td>
                <td>Nomor Induk <?php echo LBL_SISWA; ?> (<?php echo LBL_INSTANSI == 'Kampus' ? 'NIM' : 'NIS'; ?>)</td>
                <td>:</td>
                <td><?php echo $siswa['nis']; ?></td>
            </tr>
            <tr>
                <td>4.</td>
                <td>Nomor Induk <?php echo LBL_SISWA; ?> Nasional (NISN)</td>
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
        <?php endif; ?>

        <?php if (LBL_INSTANSI == 'Kampus'): ?>
        <table class="ung-table">
            <thead>
                <tr>
                    <th style="width: 30px;">NO</th>
                    <th style="width: 75px;">KODE MK</th>
                    <th>NAMA MATA KULIAH</th>
                    <th style="width: 40px;">SKS</th>
                    <th style="width: 45px;">ANGKA</th>
                    <th style="width: 45px;">BOBOT</th>
                    <th style="width: 50px;">BOBOT (B)</th>
                </tr>

            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total_sks = 0;
                $total_sks_mutu = 0;
                $total_angka = 0;
                $count_mk = count($transkrip_data);
                
                foreach($transkrip_data as $m): 
                    $sks = $m['sks'] ?? 0;
                    $score_raw = (($m['tugas1']+$m['tugas2']+$m['tugas3']+$m['tugas4'])/4 * 0.3) + ($m['uts']*0.3) + ($m['uas']*0.4);
                    
                    if ($score_raw >= 85) { $huruf = 'A'; $mutu = 4; }
                    else if ($score_raw >= 75) { $huruf = 'B'; $mutu = 3; }
                    else if ($score_raw >= 65) { $huruf = 'C'; $mutu = 2; }
                    else if ($score_raw >= 50) { $huruf = 'D'; $mutu = 1; }
                    else { $huruf = 'E'; $mutu = 0; }
                    
                    $sks_mutu = $sks * $mutu;
                    $total_sks += $sks;
                    $total_sks_mutu += $sks_mutu;
                    $total_angka += $score_raw;
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-center"><?php echo $m['kode_mapel'] ?? '-'; ?></td>
                    <td><?php echo $m['nama_mapel']; ?></td>
                    <td class="text-center"><?php echo $sks; ?></td>
                    <td class="text-center"><?php echo number_format($score_raw, 0); ?></td>
                    <td class="text-center"><?php echo $huruf; ?></td>
                    <td class="text-center"><?php echo number_format($mutu, 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="3" class="text-center">JUMLAH</th>
                    <th class="text-center"><?php echo $total_sks; ?></th>
                    <th class="text-center"><?php echo number_format($total_angka, 0); ?></th>
                    <th colspan="2"></th>
                </tr>
            </tbody>
        </table>

        <div class="ung-footer">
            <div class="ung-stats">
                <table style="width: 100%;">
                    <tr><td style="width: 220px;">Jumlah Mata Kuliah yang telah diambil</td><td style="width: 15px;">:</td><td><strong><?php echo $count_mk; ?></strong></td></tr>
                    <tr><td>Jumlah SKS Kumulatif</td><td>:</td><td><strong><?php echo $total_sks; ?></strong></td></tr>
                </table>
            </div>
            <div class="ung-stats" style="padding-left: 40px;">
                <table style="width: 100%;">
                    <tr><td style="width: 220px;">Indeks Prestasi Kumulatif (IPK)</td><td style="width: 15px;">:</td><td><strong style="font-size: 14px;"><?php echo $total_sks > 0 ? number_format($total_sks_mutu / $total_sks, 2) : '0.00'; ?></strong></td></tr>
                    <tr><td>Predikat Yudisium</td><td>:</td><td><strong><?php 
                        $ipk = $total_sks > 0 ? ($total_sks_mutu / $total_sks) : 0;
                        if ($ipk >= 3.75) echo "Dengan Pujian Tertinggi (Summa Cum Laude)";
                        else if ($ipk >= 3.51) echo "Dengan Pujian (Cum Laude)";
                        else if ($ipk >= 3.01) echo "Sangat Memuaskan";
                        else if ($ipk >= 2.76) echo "Memuaskan";
                        else echo "Cukup";
                    ?></strong></td></tr>
                </table>
            </div>
        </div>

        <div class="ung-sign-container">
            <div class="ung-sign-box">
                <p><?php echo LBL_WALI; ?> Akademik,</p>
                <br><br><br><br>
                <p><strong><u><?php echo $nama_wali; ?></u></strong></p>
                <p style="font-size: 10px; margin-top: 5px;">NIP. <?php echo $nip_wali; ?></p>
            </div>
            
            <div style="text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 150px;">
                <p style="font-size: 10px; margin-bottom: 5px;">VERIFIKASI DIGITAL</p>
                <div class="ung-qrcode">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=VERIFIED-TRANSCRIPT-<?php echo $siswa['nis']; ?>" alt="QR Verification" style="width: 100%;">
                </div>
            </div>

            <div class="ung-sign-box">
                <p>Ketua Program Studi (KAPRODI),</p>
                <br><br><br><br>
                <p><strong><u><?php echo $nama_kaprodi; ?></u></strong></p>
                <p style="font-size: 10px; margin-top: 5px;">NIP. <?php echo $nip_kaprodi; ?></p>
            </div>
        </div>

        <div style="margin-top: 40px; text-align: center; clear: both;">
            <p><?php echo $pengaturan['alamat'] ? explode(',', $pengaturan['alamat'])[0] : 'Pati'; ?>, <?php echo date('d F Y'); ?></p>
            <p><?php echo LBL_INSTANSI == 'Kampus' ? 'Rektor' : 'Kepala Sekolah'; ?>,</p>
            <br><br><br><br>
            <p><strong><u><?php echo $pengaturan['kepala_sekolah']; ?></u></strong></p>
            <p style="font-size: 10px; margin-top: 5px;">NIDN/NIP. <?php echo $pengaturan['nip_kepala'] ?? '-'; ?></p>
        </div>
        <?php else: ?>
        <table class="grade-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30px;">No.</th>
                    <th rowspan="2"><?php echo LBL_MAPEL; ?></th>
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
            <tbody>
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
                <p><?php echo LBL_WALI; ?> <?php echo LBL_KELAS; ?></p>
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
            <p>NIP. <?php echo $pengaturan['nip_kepala'] ?? '-'; ?></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
