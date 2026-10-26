<?php

session_start();


if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require '../config/koneksi.php';

// 1. Ambil Data Dasar dari Database
$query_pelanggan = mysqli_query($conn, "SELECT COUNT(*) as total FROM pelanggan");
$data_pelanggan = mysqli_fetch_assoc($query_pelanggan);

$query_transaksi = mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi");
$data_transaksi = mysqli_fetch_assoc($query_transaksi);

// 2. Hitung Omset Hari Ini
$query_omset = mysqli_query($conn, "SELECT SUM(total_harga) as total FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE()");
$data_omset = mysqli_fetch_assoc($query_omset);
$omset_hari_ini = $data_omset['total'] ? $data_omset['total'] : 0;

// 3. Notifikasi Pesanan Pending
$query_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status_pembayaran = 'pending'");
$data_pending = mysqli_fetch_assoc($query_pending);
$jumlah_pending = $data_pending['total'];

// 4. Statistik Antrean Operasional
$stats_antrean = ['menunggu' => 0, 'pencucian' => 0, 'pengeringan' => 0, 'penyetrikaan' => 0, 'siap_diambil' => 0];
$query_stats = mysqli_query($conn, "SELECT tahapan_sekarang, COUNT(*) as jumlah FROM status_laundry WHERE tahapan_sekarang != 'selesai' GROUP BY tahapan_sekarang");
while($s = mysqli_fetch_assoc($query_stats)) {
    $stats_antrean[$s['tahapan_sekarang']] = $s['jumlah'];
}


$tahapan_visual = [
    ['key' => 'menunggu', 'label' => 'Tunggu', 'color' => '#94a3b8'],
    ['key' => 'pencucian', 'label' => 'Cuci', 'color' => '#0b9fb3'],
    ['key' => 'pengeringan', 'label' => 'Kering', 'color' => '#125270'],
    ['key' => 'penyetrikaan', 'label' => 'Setrika', 'color' => '#088395'],
    ['key' => 'siap_diambil', 'label' => 'Siap', 'color' => '#10b981']
];

// 5. Transaksi Terbaru
$query_transaksi_terbaru = mysqli_query($conn, "
    SELECT t.*, p.nama_lengkap, l.nama_layanan 
    FROM transaksi t 
    JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan 
    JOIN layanan l ON t.id_layanan = l.id_layanan 
    ORDER BY t.tanggal_transaksi DESC LIMIT 4
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laundry</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>

    <style>
        :root {
            --primary: #125270; 
            --accent: #0b9fb3;  
            --bg-body: #f4f7f8;
            --bg-card: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        html.lenis { height: auto; }
        .lenis.lenis-smooth { scroll-behavior: auto; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        body { background-color: var(--bg-body); color: var(--text-dark); padding: 20px 30px 40px 130px; }

        /* --- SIDEBAR --- */
        .sidebar {
            position: fixed; top: 20px; bottom: 20px; left: 20px; width: 90px;
            background-color: var(--primary); border-radius: 30px; display: flex;
            flex-direction: column; align-items: center; padding: 40px 0;
            box-shadow: 10px 0 30px rgba(18, 82, 112, 0.1); z-index: 100;
        }
        .brand-icon { color: #ffffff; font-size: 2.2rem; margin-bottom: 60px; }
        .nav-menu { display: flex; flex-direction: column; gap: 25px; width: 100%; align-items: center; }
        .nav-link {
            width: 54px; height: 54px; display: flex; justify-content: center; align-items: center;
            border-radius: 18px; color: rgba(255,255,255,0.5); font-size: 1.5rem; text-decoration: none; transition: 0.4s;
        }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-link.active { background-color: var(--accent); color: white; box-shadow: 0 10px 20px rgba(11, 159, 179, 0.3); }
        .logout-btn { margin-top: auto; color: #ff8b8b; font-size: 1.6rem; }

        /* --- MAIN WRAPPER --- */
        .main-wrapper { max-width: 1300px; margin: 0 auto; display: flex; flex-direction: column; gap: 30px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; }
        .header h1 { font-size: 1.8rem; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
        
        .user-widget { display: flex; align-items: center; gap: 20px; }
        .notif-btn { position: relative; background: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03); cursor: pointer; }
        .notif-dot { position: absolute; top: 12px; right: 12px; width: 10px; height: 10px; background: #ef4444; border-radius: 50%; border: 2px solid white; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(0.9); opacity: 1; } 70% { transform: scale(1.5); opacity: 0; } 100% { transform: scale(0.9); opacity: 0; } }

        .profile-card { background: white; padding: 6px 20px 6px 6px; border-radius: 40px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .profile-card .avatar { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }

        /* --- BENTO LAYOUT --- */
        .bento-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
        .card { background: var(--bg-card); border-radius: 32px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid rgba(255,255,255,0.8); }
        
        /* Premium Omset Card */
        .card-omset { grid-column: span 2; background: linear-gradient(135deg, var(--primary) 0%, #08405a 100%); color: white; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; }
        .card-omset::before { content: ''; position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.03); border-radius: 50%; }
        .card-omset h4 { font-size: 0.95rem; font-weight: 400; opacity: 0.8; margin-bottom: 10px; }
        .card-omset h1 { font-size: 3.2rem; font-weight: 800; letter-spacing: -1px; }
        .omset-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 40px; }
        .chip-modern { background: rgba(255,255,255,0.1); padding: 8px 18px; border-radius: 12px; font-size: 0.85rem; backdrop-filter: blur(10px); }

        /* Quick Shortcuts */
        .shortcuts { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn-shortcut { background: #f8fafc; border-radius: 20px; padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 10px; text-decoration: none; transition: 0.3s; border: 1px solid var(--border); }
        .btn-shortcut:hover { background: white; transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 10px 20px rgba(11, 159, 179, 0.1); }
        .btn-shortcut i { font-size: 1.8rem; color: var(--accent); }
        .btn-shortcut span { font-weight: 600; font-size: 0.85rem; color: var(--text-dark); }

        /* Queue Chart */
        .queue-chart { display: flex; align-items: flex-end; justify-content: space-between; height: 140px; gap: 15px; margin-top: 20px; }
        .bar-wrap { flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; position: relative; }
        .bar-bg { width: 100%; height: 100%; background: #f1f5f9; border-radius: 10px; position: absolute; bottom: 0; z-index: 0; }
        .bar-fill { width: 100%; border-radius: 10px; position: relative; z-index: 1; transition: 1.5s cubic-bezier(0.17, 0.67, 0.83, 0.67); }
        .bar-label { margin-top: 15px; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-align: center; }

        /* Transactions List */
        .trx-list { display: flex; flex-direction: column; gap: 20px; margin-top: 10px; }
        .trx-row { display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; border-bottom: 1px solid var(--border); }
        .trx-row:last-child { border: none; }
        .trx-info { display: flex; align-items: center; gap: 15px; }
        .trx-id { width: 45px; height: 45px; background: #eaf6f8; color: var(--accent); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; }
        .status-pill { padding: 4px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .pending { background: #fff7ed; color: #c2410c; }
        .settlement { background: #ecfdf5; color: #059669; }

        /* Counters */
        .stat-val { font-size: 2.2rem; font-weight: 800; margin: 10px 0; color: var(--primary); }
    </style>
</head>
<body>

    <aside class="sidebar gs-reveal">
        <div class="brand-icon"><i class="ph-fill ph-infinity"></i></div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link active"><i class="ph-fill ph-squares-four"></i></a>
            <a href="data_pelanggan.php" class="nav-link"><i class="ph-fill ph-users"></i></a>
            <a href="transaksi.php" class="nav-link"><i class="ph-fill ph-wallet"></i></a>
            <a href="status_laundry.php" class="nav-link"><i class="ph-fill ph-list-numbers"></i></a>
            <a href="laporan.php" class="nav-link"><i class="ph-fill ph-chart-line-up"></i></a>
        </nav>
        <a href="../logout.php" class="nav-link logout-btn"><i class="ph-bold ph-power"></i></a>
    </aside>

    <div class="main-wrapper">
        
        <header class="header gs-reveal">
            <h1>Workspace Overview</h1>
            <div class="user-widget">
                <div class="notif-btn" title="<?php echo $jumlah_pending; ?> Pending">
                    <i class="ph ph-bell"></i>
                    <?php if($jumlah_pending > 0): ?><span class="notif-dot"></span><?php endif; ?>
                </div>
                <div class="profile-card">
                    <div class="avatar"><?php echo strtoupper(substr($_SESSION['id_user'], 0, 1)); ?></div>
                    <span style="font-weight: 600; font-size: 0.9rem;">Administrator</span>
                </div>
            </div>
        </header>

        <div class="bento-grid">
            <div class="card card-omset gs-item">
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
                        <span style="font-weight:700; letter-spacing: 2px;">LAUNDRY.</span>
                        <i class="ph-bold ph-lightning-z" style="font-size: 1.5rem; color: var(--accent);"></i>
                    </div>
                    <h4>Estimasi Omset Hari Ini</h4>
                    <h1>Rp <?php echo number_format($omset_hari_ini, 0, ',', '.'); ?></h1>
                </div>
                <div class="omset-footer">
                    <div class="chip-modern"><i class="ph ph-calendar"></i> <?php echo date('d F Y'); ?></div>
                    <span style="font-size: 0.8rem; opacity: 0.7;">Verified by System</span>
                </div>
            </div>

            <div class="card gs-item">
                <p style="font-size: 0.9rem; font-weight: 600; margin-bottom: 20px; color: var(--text-muted);">Quick Actions</p>
                <div class="shortcuts">
                    <a href="transaksi.php" class="btn-shortcut"><i class="ph-fill ph-plus-circle"></i><span>Pesanan</span></a>
                    <a href="status_laundry.php" class="btn-shortcut"><i class="ph-fill ph-washing-machine"></i><span>Status Laundry</span></a>
                    <a href="data_pelanggan.php" class="btn-shortcut"><i class="ph-fill ph-user-plus"></i><span>Customer</span></a>
                    <a href="laporan.php" class="btn-shortcut"><i class="ph-fill ph-printer"></i><span>Cetak</span></a>
                </div>
            </div>

            <div class="card gs-item">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;">Total Pelanggan</span>
                    <i class="ph ph-users" style="color: var(--accent); font-size: 1.4rem;"></i>
                </div>
                <div class="stat-val"><?php echo $data_pelanggan['total']; ?></div>
                <span style="background: #eaf6f8; color: var(--accent); padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;">+ Aktif</span>
            </div>

            <div class="card gs-item" style="grid-column: span 2;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;">Real-time Queue Monitor</span>
                    <span style="font-size: 0.75rem; color: var(--accent); font-weight: 700;">Active Tasks</span>
                </div>
                <div class="queue-chart">
                    <?php 
                    $max_val = max($stats_antrean) > 0 ? max($stats_antrean) : 1; 
                    foreach($tahapan_visual as $t): 
                        $jml = $stats_antrean[$t['key']];
                        $height = ($jml / $max_val) * 100;
                    ?>
                        <div class="bar-wrap" title="<?php echo $jml; ?> Pesanan">
                            <div class="bar-bg"></div>
                            <div class="bar-fill" style="height: <?php echo max($height, 5); ?>%; background: <?php echo $t['color']; ?>;"></div>
                            <div class="bar-label"><?php echo $t['label']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card gs-item">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;">Total Transaksi</span>
                    <i class="ph ph-receipt" style="color: var(--accent); font-size: 1.4rem;"></i>
                </div>
                <div class="stat-val"><?php echo $data_transaksi['total']; ?></div>
                <span style="background: #f1f5f9; color: var(--text-muted); padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;">Cumulative</span>
            </div>

            <div class="card gs-item" style="grid-column: span 3; margin-bottom: 30px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
                    <span style="color: var(--primary); font-size: 1.1rem; font-weight: 700;">Latest Operations</span>
                    <a href="laporan.php" style="color: var(--accent); text-decoration: none; font-size: 0.85rem; font-weight: 700;">View All Activity &rarr;</a>
                </div>
                <div class="trx-list">
                    <?php while($row = mysqli_fetch_assoc($query_transaksi_terbaru)): ?>
                        <div class="trx-row">
                            <div class="trx-info">
                                <div class="trx-id">#<?php echo $row['id_transaksi']; ?></div>
                                <div>
                                    <h4 style="font-size:0.95rem; font-weight: 700;"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h4>
                                    <p style="font-size:0.8rem; color:var(--text-muted);"><?php echo $row['no_invoice']; ?> • <?php echo $row['nama_layanan']; ?></p>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <h4 style="font-size:1rem; font-weight: 800; color: var(--primary);">Rp <?php echo number_format($row['total_harga'],0,',','.'); ?></h4>
                                <span class="status-pill <?php echo strtolower($row['status_pembayaran']); ?>"><?php echo strtoupper($row['status_pembayaran']); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const lenis = new Lenis({ duration: 1.2, smoothWheel: true });
            function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
            requestAnimationFrame(raf);

            gsap.from(".gs-reveal", { y: -20, opacity: 0, duration: 1, ease: "power4.out" });
            gsap.from(".gs-item", { 
                scale: 0.9, 
                opacity: 0, 
                duration: 0.8, 
                stagger: 0.1, 
                delay: 0.2, 
                ease: "expo.out" 
            });
        });
    </script>
</body>
</html>