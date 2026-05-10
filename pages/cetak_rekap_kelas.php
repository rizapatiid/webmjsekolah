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

if (!$tahun || !$semester || !$jurusan || !$kelas) {
    echo "Lengkapi filter terlebih dahulu.";
    exit;
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

// Ambil semua mapel
$mapel_res = $conn->query("SELECT * FROM mapel ORDER BY nama_mapel ASC");
$all_mapel = [];
while($m = $mapel_res->fetch_assoc()) {
    $all_mapel[] = $m;
}

// Ambil semua nilai untuk kelas ini
$nis_list = "'" . implode("','", array_column($students, 'nis')) . "'";
$nilai_res = $conn->query("SELECT * FROM nilai WHERE nis_siswa IN ($nis_list)");
$nilai_matrix = [];
while($n = $nilai_res->fetch_assoc()) {
    $nilai_matrix[$n['nis_siswa']][$n['id_mapel']] = $n;
}

// Ambil Nama Wali Kelas
$wk_query = $conn->query("SELECT g.nama FROM kelas k JOIN guru g ON k.wali_guru = g.nip WHERE k.nama_kelas = '$kelas'");
$wali_kelas = $wk_query->fetch_assoc();
$nama_wali = $wali_kelas['nama'] ?? '...........................................';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transkrip Nilai Kelas - <?php echo $kelas; ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 20px; background: #f0f2f5; }
        .page { background: white; padding: 30px; width: 297mm; min-height: 210mm; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; font-size: 18px; }
        .info-rekap { display: flex; justify-content: space-between; margin-bottom: 20px; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px 4px; text-align: center; }
        th { background: #0f172a; color: white; text-transform: uppercase; font-size: 9px; }
        .text-left { text-align: left; padding-left: 10px; }
        .bg-light { background: #f8fafc; }
        .fw-bold { font-weight: bold; }
        
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-print { padding: 10px 20px; background: #0f172a; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }

        @media print {
            body { background: white; padding: 0; }
            .page { width: 100%; box-shadow: none; padding: 10mm; }
            .no-print { display: none; }
            th { background: #0f172a !important; color: white !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Cetak Rekap Nilai Kelas (PDF)</button>
    </div>

    <div class="page">
        <div class="header">
            <h2>REKAPITULASI NILAI HASIL BELAJAR SISWA</h2>
            <h3 style="margin: 5px 0;"><?php echo strtoupper($pengaturan['nama_sekolah']); ?></h3>
        </div>

        <div class="info-rekap">
            <div>
                <table>
                    <tr style="border:none;"><td style="border:none;" class="text-left">KELAS</td><td style="border:none;">: <?php echo $kelas; ?></td></tr>
                    <tr style="border:none;"><td style="border:none;" class="text-left">JURUSAN</td><td style="border:none;">: <?php echo $jurusan; ?></td></tr>
                </table>
            </div>
            <div>
                <table>
                    <tr style="border:none;"><td style="border:none;" class="text-left">TAHUN AJARAN</td><td style="border:none;">: <?php echo $tahun; ?></td></tr>
                    <tr style="border:none;"><td style="border:none;" class="text-left">SEMESTER</td><td style="border:none;">: <?php echo $semester; ?></td></tr>
                </table>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30px;">NO</th>
                    <th rowspan="2" style="width: 150px;">NAMA SISWA</th>
                    <?php foreach($all_mapel as $m): ?>
                        <th colspan="1"><?php echo strtoupper(substr($m['nama_mapel'], 0, 10)); ?>..</th>
                    <?php endforeach; ?>
                    <th rowspan="2">RATA-RATA</th>
                </tr>
                <tr>
                    <?php foreach($all_mapel as $m): ?>
                        <th style="font-size: 8px;">NILAI</th>
                    <?php endforeach; ?>
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
                    <td><?php echo $no++; ?></td>
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
                        <td class="<?php echo $final < 75 && $final > 0 ? 'text-danger' : ''; ?>">
                            <?php echo $final > 0 ? number_format($final, 0) : '-'; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="bg-light fw-bold">
                        <?php echo $mapel_count > 0 ? number_format($total_score / count($all_mapel), 1) : '0'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 40px; display: flex; justify-content: space-between;">
            <div style="text-align: center; width: 200px;">
                <p>Mengetahui,</p>
                <p>Kepala Sekolah</p>
                <br><br><br>
                <p><strong><u><?php echo $pengaturan['kepala_sekolah']; ?></u></strong></p>
            </div>
            <div style="text-align: center; width: 250px;">
                <p><?php echo $pengaturan['alamat'] ? explode(',', $pengaturan['alamat'])[0] : 'Semarang'; ?>, <?php echo date('d F Y'); ?></p>
                <p>Wali Kelas</p>
                <br><br><br>
                <p><strong><u><?php echo $nama_wali; ?></u></strong></p>
            </div>
        </div>
    </div>
</body>
</html>
