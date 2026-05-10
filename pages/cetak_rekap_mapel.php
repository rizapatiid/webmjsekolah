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

if (!$tahun || !$semester || !$jurusan || !$kelas || !$mapel_id) {
    echo "Lengkapi filter terlebih dahulu.";
    exit;
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
          AND s.tahun_ajaran = '$tahun' 
          AND s.semester = '$semester'
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
    <title>Daftar Nilai Mapel - <?php echo $mapel['nama_mapel']; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; margin: 0; padding: 20px; background: #f4f7f6; }
        .page { background: white; padding: 40px; width: 210mm; min-height: 297mm; margin: auto; box-shadow: 0 0 20px rgba(0,0,0,0.1); border-radius: 8px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #0f172a; padding-bottom: 15px; }
        .header h2 { margin: 0; color: #0f172a; text-transform: uppercase; font-size: 20px; }
        .header p { margin: 5px 0; color: #64748b; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .info-item { display: flex; margin-bottom: 5px; }
        .info-label { width: 100px; font-weight: bold; color: #475569; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px 8px; text-align: center; }
        th { background: #0f172a; color: white; text-transform: uppercase; font-size: 10px; letter-spacing: 1px; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .text-left { text-align: left; padding-left: 15px; }
        .fw-bold { font-weight: bold; }
        .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 10px; }
        .bg-success-lite { background: #dcfce7; color: #166534; }
        .bg-danger-lite { background: #fee2e2; color: #991b1b; }

        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-print { padding: 12px 25px; background: #0f172a; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s; }
        .btn-print:hover { background: #3b82f6; transform: translateY(-2px); }

        @media print {
            body { background: white; padding: 0; }
            .page { width: 100%; box-shadow: none; border-radius: 0; padding: 10mm; }
            .no-print { display: none; }
            th { background: #0f172a !important; color: white !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print"><i class='bx bx-printer'></i> Cetak Laporan Mapel (PDF)</button>
    </div>

    <div class="page">
        <div class="header">
            <h2>DAFTAR NILAI MATA PELAJARAN</h2>
            <p><?php echo strtoupper($pengaturan['nama_sekolah']); ?></p>
        </div>

        <div class="info-grid">
            <div>
                <div class="info-item"><div class="info-label">Mata Pelajaran</div><div>: <?php echo $mapel['nama_mapel']; ?></div></div>
                <div class="info-item"><div class="info-label">Kelas / Jurusan</div><div>: <?php echo $kelas; ?> / <?php echo $jurusan; ?></div></div>
            </div>
            <div>
                <div class="info-item"><div class="info-label">Tahun Ajaran</div><div>: <?php echo $tahun; ?></div></div>
                <div class="info-item"><div class="info-label">Semester</div><div>: <?php echo $semester; ?></div></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Nama Siswa</th>
                    <th style="width: 50px;">T1</th>
                    <th style="width: 50px;">T2</th>
                    <th style="width: 50px;">T3</th>
                    <th style="width: 50px;">T4</th>
                    <th style="width: 50px;">UTS</th>
                    <th style="width: 50px;">UAS</th>
                    <th style="width: 60px;">AKHIR</th>
                    <th style="width: 80px;">STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($row = $result->fetch_assoc()): 
                    $avg_tugas = ($row['tugas1']+$row['tugas2']+$row['tugas3']+$row['tugas4'])/4;
                    $final = ($avg_tugas * 0.3) + ($row['uts']*0.3) + ($row['uas']*0.4);
                    $status = $final >= 75 ? 'LULUS' : 'REMIDI';
                    $status_class = $final >= 75 ? 'bg-success-lite' : 'bg-danger-lite';
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td class="text-left fw-bold"><?php echo strtoupper($row['nama_siswa']); ?></td>
                    <td><?php echo number_format($row['tugas1'], 0); ?></td>
                    <td><?php echo number_format($row['tugas2'], 0); ?></td>
                    <td><?php echo number_format($row['tugas3'], 0); ?></td>
                    <td><?php echo number_format($row['tugas4'], 0); ?></td>
                    <td><?php echo number_format($row['uts'], 0); ?></td>
                    <td><?php echo number_format($row['uas'], 0); ?></td>
                    <td class="fw-bold"><?php echo number_format($final, 1); ?></td>
                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                </tr>
                <?php endwhile; ?>
                <?php if($result->num_rows == 0): ?>
                <tr>
                    <td colspan="10" class="py-4 text-muted small">Tidak ada data nilai untuk filter ini.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 50px; display: flex; justify-content: flex-end;">
            <div style="text-align: center; width: 250px;">
                <p><?php echo $pengaturan['alamat'] ? explode(',', $pengaturan['alamat'])[0] : 'Semarang'; ?>, <?php echo date('d F Y'); ?></p>
                <p>Guru Mata Pelajaran,</p>
                <br><br><br><br>
                <p><strong><u><?php echo $nama_guru; ?></u></strong></p>
                <p>NIP. <?php echo $nip_guru; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
