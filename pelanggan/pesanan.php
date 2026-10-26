<?php
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pelanggan') { header("Location: ../login.php"); exit; }
require '../config/koneksi.php';

$id_user = $_SESSION['id_user'];
$p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_pelanggan FROM pelanggan WHERE id_user = '$id_user'"));
$id_pelanggan = $p['id_pelanggan'];

// 1. Query Transaksi PENDING
$query_tagihan = mysqli_query($conn, "
    SELECT t.*, l.nama_layanan FROM transaksi t 
    JOIN layanan l ON t.id_layanan = l.id_layanan 
    WHERE t.id_pelanggan = '$id_pelanggan' AND t.status_pembayaran = 'pending'
    ORDER BY t.tanggal_transaksi DESC
");

// 2. Query Transaksi AKTIF
$query_tracking = mysqli_query($conn, "
    SELECT t.*, l.nama_layanan, a.tahapan_sekarang 
    FROM transaksi t 
    JOIN layanan l ON t.id_layanan = l.id_layanan 
    JOIN status_laundry a ON t.id_transaksi = a.id_transaksi
    WHERE t.id_pelanggan = '$id_pelanggan' 
    AND t.status_pembayaran = 'settlement' 
    AND a.tahapan_sekarang != 'selesai'
    ORDER BY a.waktu_masuk DESC
");


$progress_map = [
    'menunggu' => ['label' => 'Menunggu Antrean', 'persen' => 20, 'icon' => 'ph-hourglass-high'],
    'pencucian' => ['label' => 'Proses Pencucian', 'persen' => 40, 'icon' => 'ph-drop'],
    'pengeringan' => ['label' => 'Proses Pengeringan', 'persen' => 60, 'icon' => 'ph-sun'],
    'penyetrikaan' => ['label' => 'Proses Setrika', 'persen' => 80, 'icon' => 'ph-magic-wand'],
    'siap_diambil' => ['label' => 'Siap Diambil', 'persen' => 100, 'icon' => 'ph-package']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Pesanan & Tracking - Canvas Laundry</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root { --primary: #125270; --accent: #0b9fb3; --bg: #f8fafc; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg); display: flex; justify-content: center; }
        .app-container { width: 100%; max-width: 420px; min-height: 100vh; padding-bottom: 110px; position: relative; }
        
        .header { background: var(--primary); padding: 50px 25px 30px; color: white; border-radius: 0 0 35px 35px; }
        .content { padding: 20px; }
        
        .section-title { font-size: 1.05rem; font-weight: 800; color: var(--primary); margin: 10px 0 15px 5px; display: flex; align-items: center; gap: 8px;}

        /* Kartu Tagihan  */
        .bill-card { 
            background: white; border-radius: 24px; padding: 20px; margin-bottom: 20px; 
            border: 1px solid #f1f5f9; box-shadow: 0 10px 25px rgba(18, 82, 112, 0.05);
        }
        .status-tag { background: #fff7ed; color: #c2410c; padding: 4px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; }
        .btn-pay { 
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; background: var(--primary); color: white; padding: 16px; 
            border-radius: 16px; text-decoration: none; font-weight: 700; margin-top: 15px;
        }

        /* Kartu Tracking */
        .track-card {
            background: white; border-radius: 24px; padding: 20px; margin-bottom: 20px;
            border: 2px solid #eaf6f8; box-shadow: 0 10px 25px rgba(11, 159, 179, 0.08);
        }
        .track-header { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .track-icon { width: 45px; height: 45px; background: #eaf6f8; color: var(--accent); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        
        /* Progress Bar Dinamis */
        .progress-box { background: #f8fafc; padding: 15px; border-radius: 16px; margin-top: 15px; }
        .progress-text { display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; color: var(--primary); margin-bottom: 8px; }
        .progress-bg { width: 100%; height: 8px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, var(--primary), var(--accent)); border-radius: 10px; transition: 1.5s ease; }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="header gs-reveal">
            <h1 style="font-size: 1.6rem; font-weight: 800;">Pesanan Saya</h1>
            <p style="opacity: 0.8; font-size: 0.9rem;">Pantau dan selesaikan tagihan Anda</p>
        </div>
        
        <div class="content">
            
            <?php if(mysqli_num_rows($query_tagihan) > 0): ?>
                <div class="section-title gs-item"><i class="ph-fill ph-warning-circle" style="color: #f59e0b;"></i> Perlu Dibayar</div>
                <?php while($row = mysqli_fetch_assoc($query_tagihan)): ?>
                    <div class="bill-card gs-item">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-family:monospace; color:#94a3b8; font-size:0.8rem;"><?php echo $row['no_invoice']; ?></span>
                            <span class="status-tag">MENUNGGU</span>
                        </div>
                        <h3 style="color:var(--primary); font-size:1.1rem;"><?php echo $row['nama_layanan']; ?></h3>
                        <div style="margin-top:10px; display:flex; justify-content:space-between; align-items:flex-end;">
                            <div>
                                <p style="font-size:0.8rem; color:#64748b;">Total Tagihan</p>
                                <h2 style="color:var(--accent); font-weight:800;">Rp <?php echo number_format($row['total_harga'],0,',','.'); ?></h2>
                            </div>
                            <a href="bayar.php?invoice=<?php echo $row['no_invoice']; ?>" class="btn-pay" style="width: 120px;">Bayar</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <div class="section-title gs-item" style="margin-top: 25px;"><i class="ph-fill ph-washing-machine" style="color: var(--accent);"></i> Sedang Diproses</div>
            
            <?php if(mysqli_num_rows($query_tracking) > 0): ?>
                <?php while($track = mysqli_fetch_assoc($query_tracking)): 
                    $tahapan = $progress_map[$track['tahapan_sekarang']];
                ?>
                    <div class="track-card gs-item">
                        <div class="track-header">
                            <div style="display:flex; gap:12px; align-items:center;">
                                <div class="track-icon"><i class="ph-duotone ph-t-shirt"></i></div>
                                <div>
                                    <h4 style="color:var(--primary); font-size:1rem;"><?php echo $track['nama_layanan']; ?></h4>
                                    <p style="font-size:0.75rem; color:#94a3b8; font-family:monospace;"><?php echo $track['no_invoice']; ?></p>
                                </div>
                            </div>
                            <span style="background:#ecfdf5; color:#10b981; font-size:0.7rem; padding:4px 8px; border-radius:8px; font-weight:800; height:fit-content;">LUNAS</span>
                        </div>

                        <div class="progress-box">
                            <div class="progress-text">
                                <span style="display:flex; align-items:center; gap:5px;"><i class="ph-bold <?php echo $tahapan['icon']; ?>"></i> <?php echo $tahapan['label']; ?></span>
                                <span><?php echo $tahapan['persen']; ?>%</span>
                            </div>
                            <div class="progress-bg">
                                <div class="progress-fill" style="width: <?php echo $tahapan['persen']; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="gs-item" style="text-align:center; padding:30px 20px; color:#94a3b8; background: white; border-radius: 20px; border: 1px dashed #e2e8f0;">
                    <i class="ph-duotone ph-wind" style="font-size:3rem; color:var(--accent); opacity:0.5;"></i>
                    <p style="font-size:0.85rem; margin-top:10px;">Tidak ada cucian yang sedang diproses saat ini.</p>
                </div>
            <?php endif; ?>

        </div>
        <?php include 'nav_include.php'; ?>
    </div>
    <script>
        gsap.from(".gs-reveal", { y: -20, opacity: 0, duration: 0.8, ease: "power2.out" });
        gsap.from(".gs-item", { y: 20, opacity: 0, duration: 0.8, stagger: 0.15, ease: "power3.out" });
    </script>
</body>
</html>