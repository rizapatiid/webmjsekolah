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

if (!$tahun || !$jurusan || !$kelas) {
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

// Ambil daftar siswa di kelas tersebut
$s_query = $conn->query("SELECT * FROM siswa WHERE kelas = '$kelas' AND jurusan = '$jurusan' AND tahun_ajaran = '$tahun' AND semester = '$semester' ORDER BY nama ASC");
$students = [];
while($s = $s_query->fetch_assoc()) {
    $students[] = $s;
}

if (empty($students)) {
    echo "Tidak ada siswa ditemukan untuk filter tersebut.";
    exit;
}

// Ambil semua mapel sesuai jurusan
$mapel_res = $conn->query("SELECT * FROM mapel WHERE jurusan = '$jurusan' ORDER BY nama_mapel ASC");
$all_mapel = [];
while($m = $mapel_res->fetch_assoc()) {
    $all_mapel[] = $m;
}

// Ambil semua nilai untuk kelas ini
$nis_list = "'" . implode("','", array_column($students, 'nis')) . "'";
$nilai_res = $conn->query("SELECT * FROM nilai WHERE nis_siswa IN ($nis_list) AND tahun_ajaran = '$tahun' AND semester = '$semester'");
$nilai_matrix = [];
while($n = $nilai_res->fetch_assoc()) {
    $nilai_matrix[$n['nis_siswa']][$n['id_mapel']] = $n;
}

// Ambil Nama Wali Kelas
$wk_query = $conn->query("SELECT g.nama, g.nip FROM kelas k JOIN guru g ON k.wali_guru = g.nip WHERE k.nama_kelas = '$kelas'");
$wali_kelas = $wk_query->fetch_assoc();
$nama_wali = $wali_kelas['nama'] ?? '...........................................';
$nip_wali = $wali_kelas['nip'] ?? '...................................';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai Kelas - <?php echo $kelas; ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11px; line-height: 1.4; color: #000; margin: 0; padding: 10px; background: #f1f5f9; }
        
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
        
        /* Subtle Decorative Frame */
        .page::before {
            content: "";
            position: absolute;
            top: 10px; left: 10px; right: 10px; bottom: 10px;
            border: 1px solid #cbd5e1;
            pointer-events: none;
            z-index: 10;
        }

        .ung-header { text-align: center; border-bottom: 3px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
        .ung-logo { width: 80px; height: auto; }
        .ung-header-text { flex: 1; text-align: center; }
        .ung-header h1 { font-size: 24px; margin: 0; font-weight: 900; color: #0f172a; line-height: 1.1; }
        .ung-header h2 { font-size: 14px; margin: 5px 0 0; font-weight: 700; color: #334155; }
        .ung-header p { font-size: 10px; margin: 5px 0 0; color: #64748b; }
        
        .ung-title { text-align: center; margin-bottom: 20px; }
        .ung-title h3 { font-size: 16px; font-weight: 900; border-bottom: 2px solid #0f172a; display: inline-block; padding: 5px 40px; color: #0f172a; text-transform: uppercase; }

        .info-table { width: 100%; margin-bottom: 15px; font-size: 11px; }
        .info-table td { padding: 2px 0; }
        .info-table td:first-child { width: 100px; font-weight: bold; }
        .info-table td:nth-child(2) { width: 10px; }

        .ung-table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        .ung-table th, .ung-table td { border: 1px solid #000; padding: 5px 2px; font-size: 9px; word-wrap: break-word; }
        .ung-table th { background: #f1f5f9; font-weight: 800; text-align: center; text-transform: uppercase; color: #0f172a; }
        .text-center { text-align: center; }
        .text-left { text-align: left; padding-left: 5px; }
        .fw-bold { font-weight: bold; }

        .ung-footer { margin-top: 30px; display: flex; justify-content: space-between; }
        .ung-sign-box { text-align: center; width: 250px; }
        .ung-sign-box p { margin: 0; }

        @media print {
            body { background: white; padding: 0; }
            .page { border: none; box-shadow: none; margin: 0; width: 100%; }
            .no-print { display: none; }
            .ung-header { border-bottom-color: #000; }
        }
        
        .no-print { text-align: center; margin: 20px 0; }
        .btn-print { padding: 10px 25px; background: #0f172a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.3s; }
        .btn-print:hover { background: #334155; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">🖨️ CETAK REKAP NILAI KELAS</button>
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
            <h3>REKAPITULASI NILAI SEMESTER</h3>
        </div>

        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <table>
                        <tr><td>Tingkat / Kelas</td><td>:</td><td><strong><?php echo $kelas; ?></strong></td></tr>
                        <tr><td>Program Studi</td><td>:</td><td><strong><?php echo $jurusan; ?></strong></td></tr>
                    </table>
                </td>
                <td style="width: 50%;">
                    <table>
                        <tr><td>Tahun Ajaran</td><td>:</td><td><strong><?php echo $tahun; ?></strong></td></tr>
                        <tr><td>Periode</td><td>:</td><td><strong><?php echo $semester; ?></strong></td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="ung-table">
            <thead>
                <tr>
                    <th style="width: 25px;">NO</th>
                    <th style="width: 130px;">NAMA MAHASISWA</th>
                    <?php foreach($all_mapel as $m): ?>
                        <th><?php echo strtoupper($m['kode_mapel'] ?: substr($m['nama_mapel'], 0, 5)); ?></th>
                    <?php endforeach; ?>
                    <th style="width: 40px;">RATA</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach($students as $s): 
                    $total_score = 0;
                    $mapel_count = 0;
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-left fw-bold"><?php echo strtoupper($s['nama']); ?></td>
                    <?php foreach($all_mapel as $m): 
                        $n = $nilai_matrix[$s['nis']][$m['id']] ?? null;
                        $final = 0;
                        if($n) {
                            $avg_tugas = ($n['tugas1']+$n['tugas2']+$n['tugas3']+$n['tugas4'])/4;
                            $final = ($avg_tugas * 0.3) + ($n['uts']*0.3) + ($n['uas']*0.4);
                        }
                        $total_score += $final;
                        if($final > 0) $mapel_count++;
                    ?>
                        <td class="text-center <?php echo ($final > 0 && $final < 70) ? 'text-danger' : ''; ?>">
                            <?php echo $final > 0 ? number_format($final, 0) : '-'; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="text-center fw-bold" style="background: #f8fafc;">
                        <?php echo count($all_mapel) > 0 ? number_format($total_score / count($all_mapel), 1) : '0'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="ung-footer">
            <div class="ung-sign-box">
                <p>Mengetahui,</p>
                <p><strong>Ketua Program Studi</strong></p>
                <br><br><br><br>
                <?php
                // Get Kaprodi info
                $kp_q = $conn->query("SELECT kaprodi, nip_kaprodi FROM jurusan WHERE nama_jurusan = '$jurusan'");
                $kp = $kp_q->fetch_assoc();
                ?>
                <p><strong><u><?php echo $kp['kaprodi'] ?? '..................................'; ?></u></strong></p>
                <p>NIP. <?php echo $kp['nip_kaprodi'] ?? '..................................'; ?></p>
            </div>
            <div class="ung-sign-box">
                <p><?php echo $pengaturan['alamat'] ? explode(',', $pengaturan['alamat'])[0] : 'Pati'; ?>, <?php echo date('d F Y'); ?></p>
                <p><strong>Wali Akademik,</strong></p>
                <br><br><br><br>
                <p><strong><u><?php echo $nama_wali; ?></u></strong></p>
                <p>NIP. <?php echo $nip_wali; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
