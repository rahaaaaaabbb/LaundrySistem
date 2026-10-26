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

// Proses Tambah Data Pelanggan
if (isset($_POST['tambah_pelanggan'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    

    $username = strtolower(str_replace(' ', '', $nama_lengkap)) . rand(10, 99);
    $password_default = password_hash('pelanggan123', PASSWORD_DEFAULT);

    // 1. Insert ke tabel users terlebih dahulu
    $query_user = "INSERT INTO users (username, password, role) VALUES ('$username', '$password_default', 'pelanggan')";
    
    if (mysqli_query($conn, $query_user)) {
        $id_user = mysqli_insert_id($conn); 
        
        // 2. Insert detailnya ke tabel pelanggan
        $query_pelanggan = "INSERT INTO pelanggan (id_user, nama_lengkap, no_telp, alamat) VALUES ('$id_user', '$nama_lengkap', '$no_telp', '$alamat')";
        mysqli_query($conn, $query_pelanggan);
        
        $pesan = "Data berhasil disimpan! Akun: <b>$username</b> | Pass: <b>pelanggan123</b>";
        $tipe_pesan = "success";
    } else {
        $pesan = "Gagal menambahkan data sistem user: " . mysqli_error($conn);
        $tipe_pesan = "error";
    }
}

$result_pelanggan = mysqli_query($conn, "SELECT p.*, u.username FROM pelanggan p JOIN users u ON p.id_user = u.id_user ORDER BY p.id_pelanggan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelanggan - Canvas Clean UI</title>
    
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

        /* --- MAIN CONTENT & HEADER --- */
        .main-wrapper { display: flex; flex-direction: column; max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-top: 10px; }
        .search-bar { display: flex; align-items: center; gap: 10px; color: var(--text-muted); font-size: 1rem; }
        .search-bar input { border: none; background: transparent; outline: none; font-size: 0.95rem; width: 250px; color: var(--text-dark); }
        .header-actions { display: flex; align-items: center; gap: 25px; }
        .action-wrapper { position: relative; display: flex; align-items: center; justify-content: center; }
        .action-icon { color: var(--text-dark); font-size: 1.3rem; cursor: pointer; transition: 0.2s; }
        .action-icon:hover { color: var(--accent-green); }
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
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-name { font-weight: 500; font-size: 0.95rem; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--text-dark); color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; }

        /* --- CARDS & GRID --- */
        .layout-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; align-items: start; }
        .card { background-color: var(--bg-card); border-radius: 24px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); }
        .card-header { margin-bottom: 25px; }
        .card-title { font-size: 1.2rem; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
        .card-subtitle { font-size: 0.85rem; color: var(--text-muted); margin-top: 5px; }

        /* --- ALERTS --- */
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .alert-success { background-color: #eaf6f0; color: var(--accent-green); border: 1px solid #c6ebd9; }
        .alert-error { background-color: #fef2f2; color: var(--danger-red); border: 1px solid #fecaca; }

        /* --- FORM STYLING --- */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 8px; color: var(--text-dark); }
        .form-control {
            width: 100%; padding: 14px 16px; border: 1px solid var(--border-light);
            border-radius: 14px; font-size: 0.95rem; outline: none; transition: 0.3s;
            background-color: #f8fafc;
        }
        .form-control:focus { border-color: var(--accent-green); background-color: white; box-shadow: 0 0 0 4px rgba(47, 166, 116, 0.1); }
        textarea.form-control { resize: vertical; min-height: 100px; }
        
        .btn-submit {
            width: 100%; padding: 15px; background-color: var(--accent-green); color: white;
            border: none; border-radius: 14px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px;
        }
        .btn-submit:hover { background-color: #248f62; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(47, 166, 116, 0.2); }

        /* --- TABLE STYLING --- */
        .table-responsive { overflow-x: auto; }
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th {
            text-align: left; padding: 15px 10px; color: var(--text-muted);
            font-weight: 500; font-size: 0.85rem; border-bottom: 1px solid var(--border-light);
        }
        .custom-table td {
            padding: 18px 10px; border-bottom: 1px solid var(--border-light);
            font-size: 0.95rem; color: var(--text-dark); vertical-align: middle;
        }
        .custom-table tr:last-child td { border-bottom: none; }
        .custom-table tr:hover td { background-color: #f8fafc; }
        
        .user-badge {
            background-color: #f1f5f9; padding: 6px 12px; border-radius: 8px;
            font-size: 0.85rem; font-weight: 600; color: var(--sidebar-color);
            display: inline-flex; align-items: center; gap: 5px;
        }

        @media (max-width: 992px) {
            .layout-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <aside class="sidebar gs-reveal">
        <div class="brand-icon"><i class="ph-fill ph-infinity"></i></div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link" title="Dashboard"><i class="ph-fill ph-squares-four"></i></a>
            <a href="data_pelanggan.php" class="nav-link active" title="Pelanggan"><i class="ph-fill ph-users"></i></a>
            <a href="transaksi.php" class="nav-link" title="Kasir & Transaksi"><i class="ph-fill ph-wallet"></i></a>
            <a href="status_laundry.php" class="nav-link" title="Antrean"><i class="ph-fill ph-list-numbers"></i></a>
            <a href="laporan.php" class="nav-link" title="Laporan"><i class="ph-fill ph-chart-line-up"></i></a>
        </nav>
        <a href="../logout.php" class="nav-link logout-btn" title="Keluar"><i class="ph-bold ph-sign-out"></i></a>
    </aside>

    <div class="main-wrapper">
        
        <header class="header gs-reveal">
            <div class="search-bar">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" placeholder="Cari pelanggan...">
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

        <div class="layout-grid">
            
            <div class="card gs-item">
                <div class="card-header">
                    <h3 class="card-title"><i class="ph-duotone ph-user-plus" style="color: var(--accent-green); font-size: 1.5rem;"></i> Tambah Pelanggan</h3>
                    <p class="card-subtitle">Buat profil dan akun otomatis</p>
                </div>

                <form action="" method="POST">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" required placeholder="Cth: Budi Santoso">
                    </div>
                    <div class="form-group">
                        <label>Nomor WhatsApp</label>
                        <input type="tel" name="no_telp" class="form-control" required placeholder="Cth: 08123456789">
                    </div>
                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" required placeholder="Masukkan alamat lengkap pengiriman..."></textarea>
                    </div>
                    <button type="submit" name="tambah_pelanggan" class="btn-submit">
                        <i class="ph-bold ph-plus"></i> Simpan Pelanggan
                    </button>
                </form>
            </div>

            <div class="card gs-item">
                <div class="card-header">
                    <h3 class="card-title">Daftar Pelanggan Terdaftar</h3>
                    <p class="card-subtitle">Semua data pelanggan yang memiliki akses ke sistem.</p>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>CUSTOMER</th>
                                <th>KONTAK</th>
                                <th>ALAMAT</th>
                                <th>AKUN AKSES</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result_pelanggan) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result_pelanggan)): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 35px; height: 35px; border-radius: 10px; background: #eaf6f0; color: var(--accent-green); display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                <?php echo strtoupper(substr($row['nama_lengkap'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong style="display: block; color: var(--text-dark);"><?php echo $row['nama_lengkap']; ?></strong>
                                                <span style="font-size: 0.8rem; color: var(--text-muted);">ID: #<?php echo str_pad($row['id_pelanggan'], 4, '0', STR_PAD_LEFT); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $row['no_telp']; ?></td>
                                    <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo $row['alamat']; ?>">
                                        <?php echo $row['alamat']; ?>
                                    </td>
                                    <td>
                                        <span class="user-badge"><i class="ph-fill ph-user-circle"></i> @<?php echo $row['username']; ?></span>
                                    </td>
                                    <td>
                                        <i class="ph-bold ph-dots-three" style="cursor: pointer; font-size: 1.2rem; color: var(--text-muted);"></i>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px 10px; color: var(--text-muted);">
                                        Belum ada data pelanggan yang terdaftar.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            
            // Inisialisasi Lenis Smooth Scroll
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

            // Animasi GSAP
            gsap.from(".gs-reveal", { y: -20, opacity: 0, duration: 0.6, stagger: 0.1, ease: "power2.out" });
            gsap.from(".gs-item", { y: 30, opacity: 0, duration: 0.8, stagger: 0.15, delay: 0.2, ease: "power3.out" });
        });
    </script>
</body>
</html>