<?php
// admin/laporan.php
session_start();


if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../config/koneksi.php';

// Menghitung pesanan pending untuk notifikasi lonceng di header
$query_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status_pembayaran = 'pending'");
$data_pending = mysqli_fetch_assoc($query_pending);
$jumlah_pending = $data_pending['total'];

// Menghitung Ringkasan Pendapatan
$query_pendapatan = mysqli_query($conn, "SELECT SUM(total_harga) as total_uang FROM transaksi WHERE status_pembayaran = 'settlement' OR status_pembayaran = 'pending'");
$data_pendapatan = mysqli_fetch_assoc($query_pendapatan);
$total_uang = $data_pendapatan['total_uang'] ? $data_pendapatan['total_uang'] : 0;

$query_total_trx = mysqli_query($conn, "SELECT COUNT(id_transaksi) as total_trx FROM transaksi");
$data_total_trx = mysqli_fetch_assoc($query_total_trx);
$total_trx = $data_total_trx['total_trx'];

// Menarik Semua Data Transaksi untuk Tabel Laporan
$query_laporan = "SELECT t.*, p.nama_lengkap, l.nama_layanan 
                  FROM transaksi t 
                  JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan 
                  JOIN layanan l ON t.id_layanan = l.id_layanan 
                  ORDER BY t.tanggal_transaksi DESC";
$result_laporan = mysqli_query($conn, $query_laporan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Canvas Clean UI</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>

    <style>
        :root {
            --bg-body: #eff3f1;
            --bg-card: #ffffff;
            --sidebar-color: #125270;
            --accent-green: #0b9fb3;
            --text-dark: #1e293b;
            --text-muted: #94a3b8;
            --border-light: #f1f5f9;
            --danger-red: #ef4444;
        }

        /* Lenis CSS */
        html.lenis { height: auto; }
        .lenis.lenis-smooth { scroll-behavior: auto; }
        .lenis.lenis-smooth [data-lenis-prevent] { overscroll-behavior: contain; }
        .lenis.lenis-stopped { overflow: hidden; }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); padding: 15px 25px 30px 130px; }

        /* --- SIDEBAR FIXED --- */
        .sidebar {
            position: fixed; top: 15px; bottom: 15px; left: 15px; width: 90px;
            background-color: var(--sidebar-color); border-radius: 30px; display: flex;
            flex-direction: column; align-items: center; padding: 30px 0;
            box-shadow: 4px 0 20px rgba(66, 120, 102, 0.15); z-index: 100;
        }
        .brand-icon { color: #ffffff; font-size: 2rem; margin-bottom: 50px; }
        .nav-menu { display: flex; flex-direction: column; gap: 20px; width: 100%; align-items: center; }
        .nav-link {
            width: 50px; height: 50px; display: flex; justify-content: center; align-items: center;
            border-radius: 16px; color: rgba(255,255,255,0.7); font-size: 1.4rem; text-decoration: none; transition: 0.3s;
        }
        .nav-link:hover { color: white; transform: translateY(-3px); }
        .nav-link.active { background-color: white; color: var(--sidebar-color); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .logout-btn { margin-top: auto; color: #ff8b8b; }
        .logout-btn:hover { background-color: rgba(255, 139, 139, 0.1); color: #ff5252; }

        /* --- HEADER --- */
        .main-wrapper { display: flex; flex-direction: column; max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-top: 10px; }
        .header-actions { display: flex; align-items: center; gap: 25px; }
        .action-wrapper { position: relative; display: flex; align-items: center; justify-content: center; }
        .action-icon { color: var(--text-dark); font-size: 1.3rem; cursor: pointer; transition: 0.2s; }
        .notif-dot {
            position: absolute; top: -2px; right: -2px; width: 10px; height: 10px;
            background-color: var(--danger-red); border-radius: 50%; border: 2px solid var(--bg-body);
            animation: pulse-red 2s infinite;
        }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--text-dark); color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; }

        /* --- CARDS & GRID --- */
        .card { background-color: var(--bg-card); border-radius: 24px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); margin-bottom: 25px;}
        .card-header { margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;}
        .card-title { font-size: 1.2rem; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
        .card-subtitle { font-size: 0.85rem; color: var(--text-muted); margin-top: 5px; }
        
        .summary-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 30px; }
        .summary-box { background: #f8fafc; padding: 25px; border-radius: 20px; border: 1px solid var(--border-light); display: flex; align-items: center; gap: 20px; }
        .summary-box .icon { width: 60px; height: 60px; border-radius: 16px; display: flex; justify-content: center; align-items: center; font-size: 2rem; }
        .summary-box h4 { font-size: 0.95rem; color: var(--text-muted); font-weight: 500; margin-bottom: 5px;}
        .summary-box h2 { font-size: 2.2rem; color: var(--text-dark); font-weight: 700; }

        /* --- TABLE STYLING --- */
        .table-responsive { overflow-x: auto; }
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { text-align: left; padding: 15px 10px; color: var(--text-muted); font-weight: 500; font-size: 0.85rem; border-bottom: 1px solid var(--border-light); }
        .custom-table td { padding: 18px 10px; border-bottom: 1px solid var(--border-light); font-size: 0.95rem; color: var(--text-dark); vertical-align: middle; }
        .custom-table tr:last-child td { border-bottom: none; }
        .custom-table tr:hover td { background-color: #f8fafc; }
        
        .status-badge { padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .status-pending { background-color: #fef3c7; color: #d97706; }
        .status-settlement { background-color: #eaf6f0; color: var(--accent-green); }

        .btn-print { 
            padding: 12px 20px; background-color: var(--text-dark); color: white; 
            border: none; border-radius: 12px; cursor: pointer; font-size: 0.95rem; 
            font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px;
        }
        .btn-print:hover { background-color: var(--accent-green); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(47, 166, 116, 0.2); }

        /* --- MODE CETAK (PRINT) --- */
        @media print {
            body { background-color: white; padding: 0; color: black; }
            .sidebar, .header, .btn-print, .summary-grid { display: none !important; }
            .main-wrapper { margin: 0; padding: 0; max-width: 100%; }
            .card { box-shadow: none; border: none; padding: 0; margin: 0;}
            .card-header { margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .card-title { font-size: 1.5rem; color: black; }
            .card-subtitle { display: block; color: black; }
            .custom-table th { color: black; border-bottom: 2px solid #000; }
            .custom-table td { color: black; border-bottom: 1px solid #ccc; }
            .status-badge { background: none !important; padding: 0; border: none !important; color: black !important;}
        }

    </style>
</head>
<body>

    <aside class="sidebar gs-reveal">
        <div class="brand-icon"><i class="ph-fill ph-infinity"></i></div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link" title="Dashboard"><i class="ph-fill ph-squares-four"></i></a>
            <a href="data_pelanggan.php" class="nav-link" title="Pelanggan"><i class="ph-fill ph-users"></i></a>
            <a href="transaksi.php" class="nav-link" title="Kasir & Transaksi"><i class="ph-fill ph-wallet"></i></a>
            <a href="status_laundry.php" class="nav-link" title="Antrean"><i class="ph-fill ph-list-numbers"></i></a>
            <a href="laporan.php" class="nav-link active" title="Laporan"><i class="ph-fill ph-chart-line-up"></i></a>
        </nav>
        <a href="../logout.php" class="nav-link logout-btn" title="Keluar"><i class="ph-bold ph-sign-out"></i></a>
    </aside>

    <div class="main-wrapper">
        <header class="header gs-reveal">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 700;">Laporan Keuangan</h1>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Rekapitulasi transaksi dan pendapatan</p>
            </div>
            
            <div class="header-actions">
                <i class="ph ph-gear action-icon"></i>
                <div class="action-wrapper" title="<?php echo $jumlah_pending; ?> Pesanan Pending">
                    <i class="ph ph-bell action-icon"></i>
                    <?php if($jumlah_pending > 0): ?>
                        <span class="notif-dot"></span>
                    <?php endif; ?>
                </div>
                <div class="user-profile">
                    <span class="user-name">Admin <strong><?php echo $_SESSION['id_user']; ?></strong></span>
                    <div class="avatar"><?php echo strtoupper(substr($_SESSION['id_user'], 0, 1)); ?></div>
                </div>
            </div>
        </header>

        <div class="summary-grid gs-item">
            <div class="summary-box">
                <div class="icon" style="background: #e0f2fe; color: #0284c7;"><i class="ph-fill ph-receipt"></i></div>
                <div>
                    <h4>Total Pesanan Diproses</h4>
                    <h2><?php echo $total_trx; ?> <span style="font-size: 1rem; color: var(--text-muted); font-weight: 500;">Transaksi</span></h2>
                </div>
            </div>
            <div class="summary-box">
                <div class="icon" style="background: #eaf6f0; color: var(--accent-green);"><i class="ph-fill ph-coins"></i></div>
                <div>
                    <h4>Proyeksi Pendapatan Kotor</h4>
                    <h2>Rp <?php echo number_format($total_uang, 0, ',', '.'); ?></h2>
                </div>
            </div>
        </div>

        <div class="card gs-item">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Detail Riwayat Transaksi</h3>
                    <p class="card-subtitle">Menampilkan seluruh data transaksi dari awal hingga hari ini.</p>
                </div>
                <button onclick="window.print()" class="btn-print">
                    <i class="ph-bold ph-printer"></i> Cetak PDF
                </button>
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>TANGGAL & WAKTU</th>
                            <th>NO. INVOICE</th>
                            <th>PELANGGAN</th>
                            <th>LAYANAN</th>
                            <th>TOTAL TAGIHAN</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result_laporan) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result_laporan)): ?>
                            <tr>
                                <td>
                                    <strong style="display:block; color: var(--text-dark);"><?php echo date('d M Y', strtotime($row['tanggal_transaksi'])); ?></strong>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo date('H:i A', strtotime($row['tanggal_transaksi'])); ?></span>
                                </td>
                                <td><strong style="color: var(--text-dark);"><?php echo $row['no_invoice']; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_layanan']); ?></td>
                                <td><strong>Rp <?php echo number_format($row['total_harga'],0,',','.'); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['status_pembayaran']); ?>">
                                        <?php if($row['status_pembayaran'] == 'pending'): ?>
                                            <i class="ph-fill ph-clock-countdown"></i>
                                        <?php else: ?>
                                            <i class="ph-fill ph-check-circle"></i>
                                        <?php endif; ?>
                                        <?php echo strtoupper($row['status_pembayaran']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding: 50px 10px; color: var(--text-muted);">
                                    <i class="ph-duotone ph-folder-open" style="font-size: 3rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                    <p>Belum ada riwayat transaksi yang tersimpan.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const lenis = new Lenis({
                duration: 1.2,
                easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
                direction: 'vertical',
                gestureDirection: 'vertical',
                smooth: true,
            });

            function raf(time) {
                lenis.raf(time);
                requestAnimationFrame(raf);
            }
            requestAnimationFrame(raf);

            gsap.from(".gs-reveal", { y: -20, opacity: 0, duration: 0.6, stagger: 0.1, ease: "power2.out" });
            gsap.from(".gs-item", { y: 30, opacity: 0, duration: 0.8, stagger: 0.15, delay: 0.2, ease: "power3.out" });
        });
    </script>
</body>
</html>