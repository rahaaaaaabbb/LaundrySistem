<?php
// pelanggan/index.php
session_start();

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../login.php");
    exit;
}

require '../config/koneksi.php';

$id_user = $_SESSION['id_user'];

$query_profil = mysqli_query($conn, "SELECT nama_lengkap, id_pelanggan FROM pelanggan WHERE id_user = '$id_user'");
$profil = mysqli_fetch_assoc($query_profil);
$nama_pendek = explode(' ', $profil['nama_lengkap'])[0];
$id_pelanggan = $profil['id_pelanggan'];


$query_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM status_laundry a JOIN transaksi t ON a.id_transaksi = t.id_transaksi WHERE t.id_pelanggan = '$id_pelanggan' AND a.tahapan_sekarang != 'selesai'");
$data_aktif = mysqli_fetch_assoc($query_aktif);


$query_layanan = mysqli_query($conn, "SELECT * FROM layanan ORDER BY harga ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Canvas Laundry - Beranda</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/flickity@2/dist/flickity.min.css">
    <script src="https://unpkg.com/flickity@2/dist/flickity.pkgd.min.js"></script>

    <style>
        :root {
            --primary: #125270; 
            --accent: #0b9fb3;  
            --bg: #f8fafc;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background-color: var(--bg); display: flex; justify-content: center; overflow-x: hidden; }

        .app-container {
            width: 100%; max-width: 420px; min-height: 100vh;
            background: var(--bg); position: relative; padding-bottom: 100px;
        }


        .header-greeting {
            padding: 30px 25px 20px; display: flex; justify-content: space-between; align-items: center;
        }
        .user-meta h3 { font-size: 0.9rem; font-weight: 400; color: #64748b; }
        .user-meta h1 { font-size: 1.5rem; font-weight: 800; color: var(--primary); }
        .notif-badge { width: 45px; height: 45px; background: white; border-radius: 15px; display: flex; justify-content: center; align-items: center; color: var(--primary); font-size: 1.3rem; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }


        .main-carousel { margin-bottom: 25px; }
        .carousel-cell {
            width: 85%; height: 180px; margin-right: 15px;
            border-radius: 25px; overflow: hidden; position: relative;
        }
        .carousel-cell img { width: 100%; height: 100%; object-fit: cover; }
        .carousel-overlay {
            position: absolute; inset: 0; 
            background: linear-gradient(to top, rgba(18, 82, 112, 0.8), transparent);
            display: flex; flex-direction: column; justify-content: flex-end; padding: 20px;
        }
        .carousel-overlay h4 { color: white; font-weight: 700; font-size: 1.1rem; }

        .info-grid { padding: 0 25px 25px; }
        .info-card { 
            background: var(--primary); color: white; border-radius: 24px; padding: 20px 25px; 
            display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 25px rgba(18, 82, 112, 0.2); 
        }
        .info-card i { font-size: 2.5rem; color: var(--accent); }
        .info-card-text span { font-size: 0.9rem; font-weight: 500; opacity: 0.8; display: block; }
        .info-card-text h2 { font-size: 1.8rem; font-weight: 800; }


        .section-title { padding: 0 25px 15px; font-size: 1.1rem; font-weight: 800; color: var(--primary); }
        .benefit-scroll { display: flex; gap: 15px; overflow-x: auto; padding: 0 25px 25px; scrollbar-width: none; }
        .benefit-scroll::-webkit-scrollbar { display: none; }
        .benefit-box {
            min-width: 120px; background: white; padding: 15px; border-radius: 20px;
            text-align: center; border: 1px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        .benefit-box i { font-size: 2rem; color: var(--accent); margin-bottom: 8px; display: block; }
        .benefit-box p { font-size: 0.8rem; font-weight: 700; color: var(--primary); }


        .catalog-container { padding: 0 25px; }
        .service-item {
            background: white; border-radius: 20px; padding: 15px;
            display: flex; align-items: center; gap: 15px; margin-bottom: 15px;
            border: 1px solid #f1f5f9; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .service-item:active { transform: scale(0.96); }
        .service-img { width: 75px; height: 75px; border-radius: 16px; object-fit: cover; background: #e2e8f0; }
        .service-info { flex: 1; }
        .service-info h4 { font-size: 1rem; color: var(--primary); font-weight: 700; margin-bottom: 3px;}
        .service-info p { font-size: 0.75rem; color: #94a3b8; font-weight: 500;}
        .price-tag { font-weight: 800; color: var(--accent); font-size: 1rem; }

    </style>
</head>
<body>

    <div class="app-container">
        
        <header class="header-greeting gs-reveal">
            <div class="user-meta">
                <h3>Selamat Datang,</h3>
                <h1><?php echo $nama_pendek; ?></h1>
            </div>
        </header>

        <div class="main-carousel gs-item" data-flickity='{ "cellAlign": "left", "contain": true, "prevNextButtons": false, "pageDots": false, "autoPlay": 3000 }'>
            <div class="carousel-cell">
                <img src="img/1.jpg" alt="Highlight 1" onerror="this.src='https://images.unsplash.com/photo-1545173153-936c5c62f50b?w=600'">
                <div class="carousel-overlay"><h4>Pakaian Bersih Seperti Baru</h4></div>
            </div>
            <div class="carousel-cell">
                <img src="img/2.jpg" alt="Highlight 2" onerror="this.src='https://images.unsplash.com/photo-1582733711386-410377c08b84?w=600'">
                <div class="carousel-overlay"><h4>Aroma Harum Tahan Lama</h4></div>
            </div>
            <div class="carousel-cell">
                <img src="img/3.jpg" alt="Highlight 3" onerror="this.src='https://images.unsplash.com/photo-1517677208171-0bc6725a3e60?w=600'">
                <div class="carousel-overlay"><h4>Mesin Cuci Higienis Modern</h4></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card gs-item">
                <i class="ph-duotone ph-washing-machine"></i>
                <div class="info-card-text">
                    <span>Cucian Diproses</span>
                    <h2><?php echo $data_aktif['total']; ?> Pesanan</h2>
                </div>
            </div>
        </div>

        <div class="section-title gs-item">Keunggulan Kami</div>
        <div class="benefit-scroll gs-item">
            <div class="benefit-box"><i class="ph-fill ph-shield-check"></i><p>Anti Bakteri</p></div>
            <div class="benefit-box"><i class="ph-fill ph-truck"></i><p>Antar Jemput</p></div>
            <div class="benefit-box"><i class="ph-fill ph-timer"></i><p>Tepat Waktu</p></div>
            <div class="benefit-box"><i class="ph-fill ph-medal"></i><p>Kualitas Premium</p></div>
        </div>

        <div class="section-title gs-item">Katalog Layanan</div>
        <div class="catalog-container gs-item">
            <?php while($l = mysqli_fetch_assoc($query_layanan)):
                $id_layan = $l['id_layanan'];
                $foto_katalog = "img/layanan_" . $id_layan . ".jpg";
            ?>
                <div class="service-item">
                    <img src="<?php echo $foto_katalog; ?>" class="service-img" alt="Foto Layanan" onerror="this.src='https://images.unsplash.com/photo-1582733711386-410377c08b84?w=200'">
                    
                    <div class="service-info">
                        <h4><?php echo $l['nama_layanan']; ?></h4>
                        <p><?php echo $l['estimasi_waktu']; ?> Jam Selesai</p>
                    </div>
                    <div class="price-tag">Rp <?php echo number_format($l['harga'],0,',','.'); ?></div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php include 'nav_include.php'; ?>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            gsap.from(".gs-reveal", { y: -20, opacity: 0, duration: 0.8, ease: "power2.out" });
            gsap.from(".gs-item", { y: 30, opacity: 0, duration: 1, stagger: 0.15, delay: 0.2, ease: "expo.out" });
        });
    </script>
</body>
</html>