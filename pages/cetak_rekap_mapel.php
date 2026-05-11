<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}
include '../config/db.php';

$tahun = $_GET['tahun'] ?? '';
$semester = $_GET['semester'] ?? '';
$jurusan = $_GET['jurusan'] ?? '';
$kelas = $_GET['kelas'] ?? '';
$mapel_id = $_GET['mapel'] ?? '';

if (!$tahun || !$jurusan || !$kelas || !$mapel_id) {
    echo "Lengkapi filter terlebih dahulu.";
    exit;
}

// Infer semester from kelas if not provided
if (!$semester) {
    preg_match('/\d+/', $kelas, $matches);
    $sem_num = $matches[0] ?? '';
    if ($sem_num) $semester = "Semester " . $sem_num;
}

// Ambil data sekolah
$p_query = $conn->query("SELECT * FROM pengaturan_sekolah WHERE id = 1");
$pengaturan = $p_query->fetch_assoc();

// Ambil data mapel
$m_query = $conn->query("SELECT * FROM mapel WHERE id = $mapel_id");
$mapel = $m_query->fetch_assoc();

// Ambil data nilai per mata pelajaran untuk kelas tersebut
$query = "SELECT n.*, s.nama as nama_siswa, s.nis
          FROM nilai n 
          JOIN siswa s ON n.nis_siswa = s.nis 
          WHERE s.kelas = '$kelas' 
          AND s.jurusan = '$jurusan' 
          AND n.tahun_ajaran = '$tahun' 
          AND n.id_mapel = $mapel_id
          ORDER BY s.nama ASC";
$result = $conn->query($query);

// Ambil Nama Guru Pengampu (berdasarkan mapel)
$g_query = $conn->query("SELECT g.nama, g.nip FROM guru g JOIN mapel m ON g.nip = m.nip_guru WHERE m.id = $mapel_id");
$guru = $g_query->fetch_assoc();
$nama_guru = $guru['nama'] ?? '...........................................';
$nip_guru = $guru['nip'] ?? '...................................';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai Mapel - <?php echo $mapel['nama_mapel']; ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12px; line-height: 1.4; color: #000; margin: 0; padding: 20px; background: #f8fafc; }
        
        .page { 
            width: 297mm; 
            min-height: 210mm; 
            padding: 15mm; 
            margin: auto; 
            background: white; 
            border: 1px solid #e2e8f0; 
            position: relative; 
            box-sizing: border-box; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .page::before {
            content: "";
            position: absolute;
            top: 10px; left: 10px; right: 10px; bottom: 10px;
            border: 1px solid #cbd5e1;
            pointer-events: none;
            z-index: 10;
        }

        .ung-header { text-align: center; border-bottom: 3px solid #0f172a; padding-bottom: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 20px; }
        .ung-logo { width: 90px; height: auto; }
        .ung-header-text { flex: 1; text-align: center; }
        .ung-header h1 { font-size: 26px; margin: 0; font-weight: 900; color: #0f172a; line-height: 1.1; }
        .ung-header h2 { font-size: 15px; margin: 5px 0 0; font-weight: 700; color: #334155; }
        .ung-header p { font-size: 10px; margin: 5px 0 0; color: #64748b; }
        
        .ung-title { text-align: center; margin-bottom: 25px; }
        .ung-title h3 { font-size: 18px; font-weight: 900; border-bottom: 2px solid #0f172a; display: inline-block; padding: 5px 40px; color: #0f172a; text-transform: uppercase; }

        .info-rekap { width: 100%; margin-bottom: 20px; font-size: 12px; }
        .info-rekap td { padding: 3px 0; }
        .info-rekap td:first-child { width: 150px; font-weight: bold; }
        .info-rekap td:nth-child(2) { width: 15px; }

        .ung-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .ung-table th, .ung-table td { border: 1px solid #000; padding: 8px 5px; }
        .ung-table th { background: #f1f5f9; font-weight: 800; text-align: center; text-transform: uppercase; color: #0f172a; font-size: 11px; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }

        .ung-footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .ung-sign-box { text-align: center; width: 250px; }
        .ung-sign-box p { margin: 0; }

        @media print {
            body { background: white; padding: 0; }
            .page { border: none; box-shadow: none; margin: 0; width: 100%; }
            .no-print { display: none; }
            .ung-header { border-bottom-color: #000; }
        }
        
        .no-print { text-align: center; margin: 20px 0; }
        .btn-print { padding: 12px 30px; background: #0f172a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">🖨️ CETAK REKAP NILAI MATA KULIAH</button>
    </div>

    <div class="page">
        <div class="ung-header">
            <img src="../<?php echo $pengaturan['logo']; ?>" class="ung-logo">
            <div class="ung-header-text">
                <h1><?php echo strtoupper($pengaturan['nama_sekolah']); ?></h1>
                <h2><?php echo strtoupper($pengaturan['nama_yayasan'] ?? 'YAYASAN PENDIDIKAN DAN SOSIAL RMP GROUP INDONESIA'); ?></h2>
                <p><?php echo $pengaturan['alamat']; ?> | Telp: <?php echo $pengaturan['telepon']; ?> | Email: <?php echo $pengaturan['email']; ?></p>
            </div>
        </div>

        <div class="ung-title">
            <h3>REKAPITULASI NILAI MATA KULIAH</h3>
        </div>

        <table class="info-rekap">
            <tr><td>Mata Kuliah</td><td>:</td><td><strong><?php echo $mapel['nama_mapel']; ?> (<?php echo $mapel['kode_mapel']; ?>)</strong></td></tr>
            <tr><td>Dosen Pengampu</td><td>:</td><td><strong><?php echo $nama_guru; ?></strong></td></tr>
            <tr><td>Tingkat / Kelas</td><td>:</td><td><strong><?php echo $kelas; ?></strong></td></tr>
            <tr><td>Program Studi</td><td>:</td><td><strong><?php echo $jurusan; ?></strong></td></tr>
            <tr><td>Tahun Ajaran / Periode</td><td>:</td><td><strong><?php echo $tahun; ?> / <?php echo $semester; ?></strong></td></tr>
        </table>

        <table class="ung-table">
            <thead>
                <tr>
                    <th style="width: 35px;">NO</th>
                    <th style="width: 100px;">NIM</th>
                    <th>NAMA MAHASISWA</th>
                    <th style="width: 40px;">T1</th>
                    <th style="width: 40px;">T2</th>
                    <th style="width: 40px;">T3</th>
                    <th style="width: 40px;">T4</th>
                    <th style="width: 50px;">UTS</th>
                    <th style="width: 50px;">UAS</th>
                    <th style="width: 60px;">AKHIR</th>
                    <th style="width: 50px;">HURUF</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($row = $result->fetch_assoc()): 
                    $avg_tugas = ($row['tugas1'] + $row['tugas2'] + $row['tugas3'] + $row['tugas4']) / 4;
                    $akhir = ($avg_tugas * 0.3) + ($row['uts'] * 0.3) + ($row['uas'] * 0.4);
                    
                    if ($akhir >= 85) $huruf = 'A';
                    else if ($akhir >= 75) $huruf = 'B';
                    else if ($akhir >= 65) $huruf = 'C';
                    else if ($akhir >= 50) $huruf = 'D';
                    else $huruf = 'E';
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-center"><?php echo $row['nis']; ?></td>
                    <td class="text-left fw-bold"><?php echo strtoupper($row['nama_siswa']); ?></td>
                    <td class="text-center"><?php echo number_format($row['tugas1'], 0); ?></td>
                    <td class="text-center"><?php echo number_format($row['tugas2'], 0); ?></td>
                    <td class="text-center"><?php echo number_format($row['tugas3'], 0); ?></td>
                    <td class="text-center"><?php echo number_format($row['tugas4'], 0); ?></td>
                    <td class="text-center"><?php echo number_format($row['uts'], 0); ?></td>
                    <td class="text-center"><?php echo number_format($row['uas'], 0); ?></td>
                    <td class="text-center fw-bold" style="background: #f8fafc;"><?php echo number_format($akhir, 1); ?></td>
                    <td class="text-center fw-bold"><?php echo $huruf; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="ung-footer">
            <div class="ung-sign-box">
                <p>Mengetahui,</p>
                <p><strong>Ketua Program Studi</strong></p>
                <br><br><br><br>
                <?php
                $kp_q = $conn->query("SELECT kaprodi, nip_kaprodi FROM jurusan WHERE nama_jurusan = '$jurusan'");
                $kp = $kp_q->fetch_assoc();
                ?>
                <p><strong><u><?php echo $kp['kaprodi'] ?? '..................................'; ?></u></strong></p>
                <p>NIP. <?php echo $kp['nip_kaprodi'] ?? '..................................'; ?></p>
            </div>
            <div class="ung-sign-box">
                <p><?php echo $pengaturan['alamat'] ? explode(',', $pengaturan['alamat'])[0] : 'Pati'; ?>, <?php echo date('d F Y'); ?></p>
                <p><strong>Dosen Pengampu,</strong></p>
                <br><br><br><br>
                <p><strong><u><?php echo $nama_guru; ?></u></strong></p>
                <p>NIP. <?php echo $nip_guru; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
