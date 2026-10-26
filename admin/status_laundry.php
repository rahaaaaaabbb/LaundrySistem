<?php
session_start();


if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../config/koneksi.php';

$pesan = '';
$tipe_pesan = '';

// Menghitung pesanan pending untuk notifikasi lonceng di header
$query_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status_pembayaran = 'pending'");
$data_pending = mysqli_fetch_assoc($query_pending);
$jumlah_pending = $data_pending['total'];

// Proses Update Tahapan Cucian
if (isset($_POST['update_tahapan'])) {
    $id_antrean = mysqli_real_escape_string($conn, $_POST['id_antrean']);
    $tahapan_baru = mysqli_real_escape_string($conn, $_POST['tahapan_baru']);
    
    $query_update = "UPDATE status_laundry SET tahapan_sekarang = '$tahapan_baru' WHERE id_antrean = '$id_antrean'";
    if (mysqli_query($conn, $query_update)) {
        $pesan = "Status antrean berhasil diperbarui!";
        $tipe_pesan = "success";
    } else {
        $pesan = "Gagal memperbarui status: " . mysqli_error($conn);
        $tipe_pesan = "error";
    }
}

// Menarik Data Antrean
$query_antrean = "SELECT a.*, t.no_invoice, p.nama_lengkap, l.jenis_layanan 
                  FROM status_laundry a
                  JOIN transaksi t ON a.id_transaksi = t.id_transaksi
                  JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
                  JOIN layanan l ON t.id_layanan = l.id_layanan
                  WHERE a.tahapan_sekarang != 'selesai'
                  ORDER BY a.prioritas ASC, a.waktu_masuk ASC";
                  
$result_antrean = mysqli_query($conn, $query_antrean);
$total_antrean_aktif = mysqli_num_rows($result_antrean);


$list_tahapan = [
    'menunggu' => ' Menunggu Antrean',
    'pencucian' => ' Proses Pencucian',
    'pengeringan' => ' Proses Pengeringan',
    'penyetrikaan' => ' Proses Penyetrikaan',
    'siap_diambil' => ' Siap Diambil'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Antrean - Canvas Clean UI</title>
    
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
        .search-bar { display: flex; align-items: center; gap: 10px; color: var(--text-muted); font-size: 1rem; }
        .search-bar input { border: none; background: transparent; outline: none; font-size: 0.95rem; width: 250px; color: var(--text-dark); }
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
        
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .summary-box { background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid var(--border-light); display: flex; align-items: center; gap: 15px; }
        .summary-box .icon { width: 50px; height: 50px; border-radius: 14px; display: flex; justify-content: center; align-items: center; font-size: 1.5rem; }
        .summary-box h4 { font-size: 0.9rem; color: var(--text-muted); font-weight: 500; }
        .summary-box h2 { font-size: 1.8rem; color: var(--text-dark); font-weight: 700; }

        /* --- TABLE STYLING --- */
        .table-responsive { overflow-x: auto; }
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { text-align: left; padding: 15px 10px; color: var(--text-muted); font-weight: 500; font-size: 0.85rem; border-bottom: 1px solid var(--border-light); }
        .custom-table td { padding: 18px 10px; border-bottom: 1px solid var(--border-light); font-size: 0.95rem; color: var(--text-dark); vertical-align: middle; }
        .custom-table tr:last-child td { border-bottom: none; }
        .custom-table tr:hover td { background-color: #f8fafc; }
        
        /* Badges */
        .badge { padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .badge-reguler { background-color: #f1f5f9; color: #64748b; }
        .badge-express { background-color: #fef2f2; color: var(--danger-red); }
        .badge-status { background-color: #eaf6f0; color: var(--accent-green); font-weight: 500;}

        /* --- FORM UPDATE STYLING --- */
        .form-update { display: flex; gap: 10px; align-items: center; }
        .form-select { 
            padding: 10px 12px; border: 1px solid var(--border-light); border-radius: 10px; 
            font-size: 0.85rem; outline: none; background: white; color: var(--text-dark); 
            font-family: 'Outfit', sans-serif; cursor: pointer;
        }
        .form-select:focus { border-color: var(--accent-green); }
        .btn-update { 
            padding: 10px 15px; background-color: var(--text-dark); color: white; 
            border: none; border-radius: 10px; cursor: pointer; font-size: 0.85rem; 
            font-weight: 600; transition: 0.2s; display: flex; align-items: center; justify-content: center;
        }
        .btn-update:hover { background-color: var(--accent-green); transform: translateY(-2px); }

        /* --- ALERTS --- */
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .alert-success { background-color: #eaf6f0; color: var(--accent-green); border: 1px solid #c6ebd9; }
        .alert-error { background-color: #fef2f2; color: var(--danger-red); border: 1px solid #fecaca; }

    </style>
</head>
<body>

    <aside class="sidebar gs-reveal">
        <div class="brand-icon"><i class="ph-fill ph-infinity"></i></div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link" title="Dashboard"><i class="ph-fill ph-squares-four"></i></a>
            <a href="data_pelanggan.php" class="nav-link" title="Pelanggan"><i class="ph-fill ph-users"></i></a>
            <a href="transaksi.php" class="nav-link" title="Kasir & Transaksi"><i class="ph-fill ph-wallet"></i></a>
            <a href="antrean.php" class="nav-link active" title="Antrean"><i class="ph-fill ph-list-numbers"></i></a>
            <a href="laporan.php" class="nav-link" title="Laporan"><i class="ph-fill ph-chart-line-up"></i></a>
        </nav>
        <a href="../logout.php" class="nav-link logout-btn" title="Keluar"><i class="ph-bold ph-sign-out"></i></a>
    </aside>

    <div class="main-wrapper">
        <header class="header gs-reveal">
            <div class="search-bar">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" placeholder="Cari invoice atau pelanggan...">
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

        <?php if($pesan != ''): ?>
            <div class="alert alert-<?php echo $tipe_pesan; ?> gs-reveal">
                <i class="ph-fill <?php echo ($tipe_pesan == 'success') ? 'ph-check-circle' : 'ph-warning-circle'; ?>" style="font-size: 1.5rem;"></i>
                <span><?php echo $pesan; ?></span>
            </div>
        <?php endif; ?>

        <div class="summary-grid gs-item">
            <div class="summary-box">
                <div class="icon" style="background: #eaf6f0; color: var(--accent-green);"><i class="ph-bold ph-washing-machine"></i></div>
                <div>
                    <h4>Total Mesin Berjalan</h4>
                    <h2><?php echo $total_antrean_aktif; ?> <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">Antrean</span></h2>
                </div>
            </div>
        </div>

        <div class="card gs-item">
            <div class="card-header">
                <div>
                    <h3 class="card-title"><i class="ph-duotone ph-list-magnifying-glass" style="color: var(--accent-green); font-size: 1.5rem;"></i> Monitor Antrean Operasional</h3>
                    <p class="card-subtitle">Manajemen prioritas dan tahapan cucian secara real-time (FIFO).</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>WAKTU MASUK</th>
                            <th>INVOICE / PELANGGAN</th>
                            <th>PRIORITAS LAYANAN</th>
                            <th>TAHAPAN SAAT INI</th>
                            <th>UPDATE TAHAPAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result_antrean) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result_antrean)): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-dark);"><?php echo date('d M', strtotime($row['waktu_masuk'])); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo date('H:i A', strtotime($row['waktu_masuk'])); ?></div>
                                </td>
                                <td>
                                    <strong style="display: block; color: var(--text-dark);"><?php echo $row['no_invoice']; ?></strong>
                                    <span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['nama_lengkap']); ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $row['jenis_layanan']; ?>">
                                        <i class="ph-fill <?php echo ($row['jenis_layanan'] == 'express') ? 'ph-rocket-launch' : 'ph-clock'; ?>"></i>
                                        <?php echo strtoupper($row['jenis_layanan']); ?> (P<?php echo $row['prioritas']; ?>)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-status">
                                        <?php echo $list_tahapan[$row['tahapan_sekarang']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <form action="" method="POST" class="form-update">
                                        <input type="hidden" name="id_antrean" value="<?php echo $row['id_antrean']; ?>">
                                        <select name="tahapan_baru" class="form-select">
                                            <?php foreach($list_tahapan as $key => $val): ?>
                                                <option value="<?php echo $key; ?>" <?php echo ($row['tahapan_sekarang'] == $key) ? 'selected' : ''; ?>>
                                                    <?php echo $val; ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="selesai" style="font-weight: 600; color: var(--accent-green);">✔ Selesai (Diambil)</option>
                                        </select>
                                        <button type="submit" name="update_tahapan" class="btn-update" title="Simpan Perubahan">
                                            <i class="ph-bold ph-check"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 50px 10px; color: var(--text-muted);">
                                    <i class="ph-duotone ph-check-circle" style="font-size: 3rem; margin-bottom: 10px; color: var(--accent-green); opacity: 0.5;"></i>
                                    <p>Tidak ada antrean aktif saat ini. Semua cucian telah selesai!</p>
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