<?php
// admin/transaksi.php
session_start();

// Validasi sesi admin
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

// Proses Pembuatan Transaksi Baru
if (isset($_POST['buat_transaksi'])) {
    $id_pelanggan = mysqli_real_escape_string($conn, $_POST['id_pelanggan']);
    $id_layanan = mysqli_real_escape_string($conn, $_POST['id_layanan']);
    $berat = mysqli_real_escape_string($conn, $_POST['berat']);
    
    // 1. Ambil detail harga dan jenis layanan
    $query_layanan = mysqli_query($conn, "SELECT harga, jenis_layanan FROM layanan WHERE id_layanan = '$id_layanan'");
    $data_layanan = mysqli_fetch_assoc($query_layanan);
    
   // 2. Kalkulasi harga awal
        $harga_normal = $berat * $data_layanan['harga'];
        $no_invoice = "INV-" . date('ymdHis');
        
        // --- LOGIKA DISKON LOYALITAS ---
        // Hitung total transaksi yang sudah pernah dilakukan pelanggan ini
        $query_cek_trx = mysqli_query($conn, "SELECT COUNT(*) as total_trx FROM transaksi WHERE id_pelanggan = '$id_pelanggan'");
        $data_trx = mysqli_fetch_assoc($query_cek_trx);
        
        // Transaksi saat ini adalah transaksi ke-berapa?
        $transaksi_ke = $data_trx['total_trx'] + 1; 
        
        $diskon = 0;
        // Jika ini adalah kelipatan 5 (ke-5, ke-10, ke-15, dst), berikan diskon
        if ($transaksi_ke % 5 == 0) {
            $diskon = $harga_normal * 0.10; // Memberikan diskon 10%
        }

        // Hitung total akhir setelah diskon
        $total_harga = $harga_normal - $diskon;
        
        // 3. Simpan ke tabel Transaksi 
        $query_transaksi = "INSERT INTO transaksi (id_pelanggan, id_layanan, no_invoice, berat, diskon, total_harga, status_pembayaran)
                             VALUES ('$id_pelanggan', '$id_layanan', '$no_invoice', '$berat', '$diskon', '$total_harga', 'pending')"; 
    
    // 3. Simpan ke tabel Transaksi
    $query_transaksi = "INSERT INTO transaksi (id_pelanggan, id_layanan, no_invoice, berat, total_harga, status_pembayaran) 
                        VALUES ('$id_pelanggan', '$id_layanan', '$no_invoice', '$berat', '$total_harga', 'pending')";
                        
    if (mysqli_query($conn, $query_transaksi)) {
        $id_transaksi = mysqli_insert_id($conn); 
        
        // 4. Masukkan ke tabel Antrean
        // Express = Prioritas 1 (High), Reguler = Prioritas 2 
        $prioritas = ($data_layanan['jenis_layanan'] == 'express') ? 1 : 2;
        
        $query_antrean = "INSERT INTO status_laundry (id_transaksi, prioritas, tahapan_sekarang) 
                          VALUES ('$id_transaksi', '$prioritas', 'menunggu')";
        mysqli_query($conn, $query_antrean);
        
        $pesan = "Transaksi <b>$no_invoice</b> berhasil diproses dan masuk antrean!";
        $tipe_pesan = "success";
    } else {
        $pesan = "Terjadi kesalahan: " . mysqli_error($conn);
        $tipe_pesan = "error";
    }
}


$pelanggan_list = mysqli_query($conn, "SELECT id_pelanggan, nama_lengkap, no_telp FROM pelanggan ORDER BY nama_lengkap ASC");
$layanan_list = mysqli_query($conn, "SELECT * FROM layanan ORDER BY jenis_layanan ASC");

// Ambil Data Transaksi Hari Ini untuk di Tabel
$hari_ini = date('Y-m-d');
$query_tabel = "SELECT t.*, p.nama_lengkap, l.nama_layanan, l.jenis_layanan 
                FROM transaksi t 
                JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan 
                JOIN layanan l ON t.id_layanan = l.id_layanan 
                WHERE DATE(t.tanggal_transaksi) = '$hari_ini'
                ORDER BY t.id_transaksi DESC";
$transaksi_hari_ini = mysqli_query($conn, $query_tabel);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir & Transaksi - Canvas Clean UI</title>
    
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

        /* --- LAYOUT GRID --- */
        .layout-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; align-items: start; }
        .card { background-color: var(--bg-card); border-radius: 24px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); }
        .card-header { margin-bottom: 25px; }
        .card-title { font-size: 1.2rem; font-weight: 600; color: var(--text-dark); }
        .card-subtitle { font-size: 0.85rem; color: var(--text-muted); margin-top: 5px; }

        /* --- FORM --- */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 8px; }
        .form-control {
            width: 100%; padding: 14px 16px; border: 1px solid var(--border-light);
            border-radius: 14px; font-size: 0.95rem; outline: none; transition: 0.3s;
            background-color: #f8fafc;
        }
        .form-control:focus { border-color: var(--accent-green); background-color: white; box-shadow: 0 0 0 4px rgba(47, 166, 116, 0.1); }
        .btn-submit {
            width: 100%; padding: 15px; background-color: var(--accent-green); color: white;
            border: none; border-radius: 14px; font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-submit:hover { background-color: #248f62; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(47, 166, 116, 0.2); }

        /* --- TABLE --- */
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { text-align: left; padding: 15px 10px; color: var(--text-muted); font-size: 0.85rem; border-bottom: 1px solid var(--border-light); }
        .custom-table td { padding: 18px 10px; border-bottom: 1px solid var(--border-light); font-size: 0.95rem; }
        .badge-type { padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .type-express { background-color: #fef2f2; color: #ef4444; }
        .type-reguler { background-color: #f1f5f9; color: #64748b; }
        .status-pending { color: #f59e0b; font-weight: 600; }
        
        /* --- ALERTS --- */
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 0.9rem; }
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
            <a href="transaksi.php" class="nav-link active" title="Kasir & Transaksi"><i class="ph-fill ph-wallet"></i></a>
            <a href="status_laundry.php" class="nav-link" title="Status Laundry"><i class="ph-fill ph-list-numbers"></i></a>
            <a href="laporan.php" class="nav-link" title="Laporan"><i class="ph-fill ph-chart-line-up"></i></a>
        </nav>
        <a href="../logout.php" class="nav-link logout-btn" title="Keluar"><i class="ph-bold ph-sign-out"></i></a>
    </aside>

    <div class="main-wrapper">
        <header class="header gs-reveal">
            <h1 style="font-size: 1.5rem; font-weight: 700;">Kasir & Transaksi</h1>
            <div class="header-actions">
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
                <i class="ph-fill <?php echo ($tipe_pesan == 'success') ? 'ph-check-circle' : 'ph-warning-circle'; ?>" style="font-size: 1.2rem;"></i>
                <span><?php echo $pesan; ?></span>
            </div>
        <?php endif; ?>

        <div class="layout-grid">
            <div class="card gs-item">
                <div class="card-header">
                    <h3 class="card-title">Buat Pesanan Baru</h3>
                    <p class="card-subtitle">Input data cucian pelanggan hari ini.</p>
                </div>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Pilih Pelanggan</label>
                        <select name="id_pelanggan" class="form-control" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php while($p = mysqli_fetch_assoc($pelanggan_list)): ?>
                                <option value="<?php echo $p['id_pelanggan']; ?>">
                                    <?php echo htmlspecialchars($p['nama_lengkap']); ?> (<?php echo $p['no_telp']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Layanan</label>
                        <select name="id_layanan" class="form-control" required>
                            <option value="">-- Pilih Layanan --</option>
                            <?php while($l = mysqli_fetch_assoc($layanan_list)): ?>
                                <option value="<?php echo $l['id_layanan']; ?>">
                                    [<?php echo strtoupper($l['jenis_layanan']); ?>] <?php echo htmlspecialchars($l['nama_layanan']); ?> - Rp <?php echo number_format($l['harga'],0,',','.'); ?>/Kg
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Berat Cucian (Kg)</label>
                        <input type="number" step="0.1" min="0.1" name="berat" class="form-control" required placeholder="Cth: 2.5">
                    </div>
                    <button type="submit" name="buat_transaksi" class="btn-submit">
                        <i class="ph-bold ph-receipt"></i> Proses & Antrekan
                    </button>
                </form>
            </div>

            <div class="card gs-item">
                <div class="card-header">
                    <h3 class="card-title">Transaksi Hari Ini</h3>
                    <p class="card-subtitle">Monitoring pesanan yang masuk tanggal <?php echo date('d M Y'); ?>.</p>
                </div>
                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>INVOICE</th>
                                <th>PELANGGAN</th>
                                <th>LAYANAN</th>
                                <th>TOTAL</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($transaksi_hari_ini) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($transaksi_hari_ini)): ?>
                                <tr>
                                    <td><strong><?php echo $row['no_invoice']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td>
                                        <span class="badge-type type-<?php echo $row['jenis_layanan']; ?>">
                                            <?php echo $row['jenis_layanan']; ?>
                                        </span>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                            <?php echo htmlspecialchars($row['nama_layanan']); ?> (<?php echo $row['berat']; ?> Kg)
                                        </div>
                                    </td>
                                    <td><strong>Rp <?php echo number_format($row['total_harga'],0,',','.'); ?></strong></td>
                                    <td class="status-<?php echo strtolower($row['status_pembayaran']); ?>">
                                        <?php echo strtoupper($row['status_pembayaran']); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">Belum ada transaksi hari ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const lenis = new Lenis({ duration: 1.2, smooth: true });
            function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
            requestAnimationFrame(raf);

            gsap.from(".gs-reveal", { y: -20, opacity: 0, duration: 0.6, stagger: 0.1, ease: "power2.out" });
            gsap.from(".gs-item", { y: 30, opacity: 0, duration: 0.8, stagger: 0.15, delay: 0.2, ease: "power3.out" });
        });
    </script>
</body>
</html>