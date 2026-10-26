<?php

session_start();


if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../login.php");
    exit;
}

require '../config/koneksi.php';

if (!isset($_GET['invoice'])) {
    header("Location: index.php");
    exit;
}

$no_invoice = mysqli_real_escape_string($conn, $_GET['invoice']);


$query = mysqli_query($conn, "
    SELECT t.*, l.nama_layanan, l.harga as harga_per_kg, p.nama_lengkap, p.alamat 
    FROM transaksi t 
    JOIN layanan l ON t.id_layanan = l.id_layanan 
    JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan 
    WHERE t.no_invoice = '$no_invoice'
");
$data = mysqli_fetch_assoc($query);


if (!$data || $data['status_pembayaran'] !== 'settlement') {
    die("<h3 style='text-align:center; margin-top:50px;'>Nota belum tersedia atau transaksi belum lunas.</h3>");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>E-Nota: <?php echo $no_invoice; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <style>
        :root {
            --primary: #125270;
            --accent: #0b9fb3;
            --bg-body: #f4f7f8;
            --text-dark: #0f172a;
            --text-gray: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background-color: var(--bg-body); display: flex; justify-content: center; min-height: 100vh; }

        .mobile-container {
            width: 100%; max-width: 420px; background: white;
            min-height: 100vh; position: relative; display: flex; flex-direction: column;
        }

        /* Navigasi Atas */
        .app-header {
            padding: 20px 25px; display: flex; align-items: center; justify-content: space-between;
            background: white; border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; z-index: 10;
        }
        .btn-circle { width: 40px; height: 40px; border-radius: 50%; background: #f8fafc; border: none; display: flex; align-items: center; justify-content: center; color: var(--text-dark); cursor: pointer; text-decoration: none; }

        /* Area Nota */
        .receipt-area { padding: 30px 25px; flex: 1; }
        
        .brand-section { text-align: center; margin-bottom: 30px; }
        .brand-logo { width: 60px; height: 60px; background: var(--primary); color: white; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 10px auto; }
        .brand-name { font-weight: 800; font-size: 1.3rem; color: var(--primary); }
        
        .invoice-pill { background: #eaf6f8; color: var(--accent); padding: 6px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; display: inline-block; margin-top: 5px; }

        .info-grid { margin: 25px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; border-top: 1px dashed #e2e8f0; border-bottom: 1px dashed #e2e8f0; padding: 20px 0; }
        .info-item label { display: block; font-size: 0.75rem; color: var(--text-gray); font-weight: 600; text-transform: uppercase; margin-bottom: 3px; }
        .info-item span { font-size: 0.9rem; font-weight: 700; color: var(--text-dark); }

        /* Tabel Rincian */
        .table-receipt { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-receipt th { text-align: left; font-size: 0.8rem; color: var(--text-gray); padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; }
        .table-receipt td { padding: 15px 0; vertical-align: top; }
        .item-name { font-weight: 700; color: var(--text-dark); display: block; }
        .item-sub { font-size: 0.8rem; color: var(--text-gray); }

        .total-section { margin-top: 20px; background: #f8fafc; padding: 20px; border-radius: 20px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; }
        .total-row.grand { margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-weight: 800; font-size: 1.2rem; color: var(--primary); }

        .status-stamp { margin: 30px auto; border: 3px solid #10b981; color: #10b981; width: fit-content; padding: 5px 20px; border-radius: 10px; font-weight: 900; font-size: 1.5rem; transform: rotate(-10deg); opacity: 0.6; }

        /* Tombol Aksi */
        .footer-actions { padding: 20px 25px 40px 25px; display: grid; grid-template-columns: 1fr; gap: 15px; }
        .btn-print { background: var(--primary); color: white; border: none; padding: 16px; border-radius: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 1rem; }

        @media print {
            body { background: white; }
            .app-header, .footer-actions, .btn-circle { display: none !important; }
            .mobile-container { box-shadow: none; max-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="mobile-container">
        <header class="app-header">
            <a href="index.php" class="btn-circle"><i class="ph-bold ph-caret-left"></i></a>
            <span style="font-weight: 700; font-size: 1rem;">E-Nota Transaksi</span>
            <button class="btn-circle" onclick="window.print()"><i class="ph-bold ph-printer"></i></button>
        </header>

        <div class="receipt-area gs-item">
            <div class="brand-section">
                <div class="brand-logo"><i class="ph-fill ph-washing-machine"></i></div>
                <div class="brand-name">Canvas Laundry</div>
                <div class="invoice-pill"><?php echo $data['no_invoice']; ?></div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label>Nama Pelanggan</label>
                    <span><?php echo htmlspecialchars($data['nama_lengkap']); ?></span>
                </div>
                <div class="info-item" style="text-align: right;">
                    <label>Tanggal</label>
                    <span><?php echo date('d M Y', strtotime($data['tanggal_transaksi'])); ?></span>
                </div>
            </div>

            <table class="table-receipt">
                <thead>
                    <tr>
                        <th>DESKRIPSI LAYANAN</th>
                        <th style="text-align: right;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <span class="item-name"><?php echo $data['nama_layanan']; ?></span>
                            <span class="item-sub"><?php echo $data['berat']; ?> Kg x Rp <?php echo number_format($data['harga_per_kg'], 0, ',', '.'); ?></span>
                        </td>
                        <td style="text-align: right; font-weight: 700;">
                            Rp <?php echo number_format($data['total_harga'], 0, ',', '.'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    <span style="color: var(--text-gray);">Subtotal</span>
                    <span style="font-weight: 600;">
                        Rp <?php echo number_format($data['total_harga'] + (isset($data['diskon']) ? $data['diskon'] : 0), 0, ',', '.'); ?>
                    </span>
                </div>
                
                <?php if(isset($data['diskon']) && $data['diskon'] > 0): ?>
                <div class="total-row">
                    <span style="color: #10b981; font-weight: 700;"><i class="ph-fill ph-ticket"></i> Diskon Loyalitas</span>
                    <span style="font-weight: 700; color: #10b981;">- Rp <?php echo number_format($data['diskon'], 0, ',', '.'); ?></span>
                </div>
                <?php endif; ?>

                <div class="total-row">
                    <span style="color: var(--text-gray);">Pajak (0%)</span>
                    <span style="font-weight: 600;">Rp 0</span>
                </div>
                <div class="total-row grand">
                    <span>Total Lunas</span>
                    <span>Rp <?php echo number_format($data['total_harga'], 0, ',', '.'); ?></span>
                </div>
            </div>

            <?php if(isset($data['diskon']) && $data['diskon'] > 0): ?>
            <div style="background: #ecfdf5; border: 1px dashed #10b981; padding: 12px; border-radius: 12px; margin-top: 15px; text-align: center;">
                <span style="color: #059669; font-size: 0.85rem; font-weight: 700;">
                     Yeay! Anda mendapatkan potongan harga spesial karena telah sering mencuci di Canvas Laundry.
                </span>
            </div>
            <?php endif; ?>

            <div class="status-stamp">PAID / LUNAS</div>

            <p style="text-align: center; color: var(--text-gray); font-size: 0.8rem; margin-top: 20px;">
                Terima kasih telah mempercayakan pakaian Anda kepada kami. <br> Simpan nota ini sebagai bukti pengambilan.
            </p>
        </div>

        <div class="footer-actions">
            <button class="btn-print" onclick="window.print()">
                <i class="ph-bold ph-file-pdf"></i> Download PDF / Cetak
            </button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            gsap.from(".gs-item", { y: 20, opacity: 0, duration: 0.8, ease: "power3.out" });
        });
    </script>
</body>
</html>