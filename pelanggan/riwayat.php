<?php
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pelanggan') { header("Location: ../login.php"); exit; }
require '../config/koneksi.php';

$id_user = $_SESSION['id_user'];
$p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_pelanggan FROM pelanggan WHERE id_user = '$id_user'"));
$id_pelanggan = $p['id_pelanggan'];


$query_riwayat = mysqli_query($conn, "
    SELECT t.*, l.nama_layanan FROM transaksi t 
    JOIN layanan l ON t.id_layanan = l.id_layanan 
    JOIN status_laundry a ON t.id_transaksi = a.id_transaksi 
    WHERE t.id_pelanggan = '$id_pelanggan' AND a.tahapan_sekarang = 'selesai'
    ORDER BY t.tanggal_transaksi DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Riwayat Selesai - Canvas Laundry</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root { --primary: #125270; --accent: #0b9fb3; --bg: #f8fafc; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg); display: flex; justify-content: center; }
        .app-container { width: 100%; max-width: 420px; min-height: 100vh; padding-bottom: 110px; }
        .header { background: var(--primary); padding: 50px 25px 30px; color: white; border-radius: 0 0 35px 35px; }
        .content { padding: 20px; }
        .history-card { 
            background: white; border-radius: 20px; padding: 18px; margin-bottom: 15px; 
            display: flex; align-items: center; gap: 15px; border: 1px solid #f1f5f9;
        }
        .icon-circle { width: 45px; height: 45px; background: #ecfdf5; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .btn-nota-sm { padding: 6px 12px; background: #f1f5f9; color: var(--primary); border-radius: 8px; text-decoration: none; font-size: 0.75rem; font-weight: 700; }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="header">
            <h1 style="font-size: 1.6rem; font-weight: 800;">Riwayat Pesanan</h1>
            <p style="opacity: 0.8; font-size: 0.9rem;">Daftar cucian yang sudah selesai</p>
        </div>
        <div class="content">
            <?php if(mysqli_num_rows($query_riwayat) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($query_riwayat)): ?>
                    <div class="history-card">
                        <div class="icon-circle"><i class="ph-bold ph-check"></i></div>
                        <div style="flex:1;">
                            <h4 style="font-size:0.95rem; color:var(--primary);"><?php echo $row['nama_layanan']; ?></h4>
                            <p style="font-size:0.75rem; color:#94a3b8;"><?php echo date('d M Y', strtotime($row['tanggal_transaksi'])); ?></p>
                        </div>
                        <div style="text-align:right;">
                            <p style="font-size:0.85rem; font-weight:700; color:var(--text-dark); margin-bottom:5px;">Rp <?php echo number_format($row['total_harga'],0,',','.'); ?></p>
                            <a href="nota.php?invoice=<?php echo $row['no_invoice']; ?>" class="btn-nota-sm">E-Nota</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                    <i class="ph-duotone ph-clock-counter-clockwise" style="font-size:4rem; opacity:0.2;"></i>
                    <p style="margin-top:10px;">Belum ada riwayat transaksi.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php include 'nav_include.php'; ?>
    </div>
</body>
</html>