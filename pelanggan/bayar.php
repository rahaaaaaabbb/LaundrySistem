<?php
// pelanggan/bayar.php
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

// Ambil detail transaksi
$query = mysqli_query($conn, "
    SELECT t.*, l.nama_layanan, p.nama_lengkap, p.no_telp 
    FROM transaksi t 
    JOIN layanan l ON t.id_layanan = l.id_layanan 
    JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
    WHERE t.no_invoice = '$no_invoice'
");
$transaksi = mysqli_fetch_assoc($query);

if (!$transaksi || $transaksi['status_pembayaran'] == 'settlement') {
    header("Location: index.php"); 
    exit;
}


if (isset($_POST['payment_success'])) {
    $query_update = "UPDATE transaksi SET status_pembayaran = 'settlement' WHERE no_invoice = '$no_invoice'";
    mysqli_query($conn, $query_update);
    // Redirect ke index beranda dengan parameter sukses
    header("Location: index.php?status=success");
    exit;
}

// ==========================================
// KONFIGURASI API MIDTRANS
// ==========================================
$server_key = ''; 
$client_key = '';

// Payload Data Pesanan untuk Midtrans
$order_data = array(
    'transaction_details' => array(
        'order_id' => $transaksi['no_invoice'], 
        'gross_amount' => (int) $transaksi['total_harga']
    ),
    'customer_details' => array(
        'first_name' => $transaksi['nama_lengkap'],
        'phone' => $transaksi['no_telp']
    )
);

// Request Token ke Midtrans via cURL
$ch = curl_init('https://app.sandbox.midtrans.com/snap/v1/transactions');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($server_key . ':')
));

$response = curl_exec($ch);
curl_close($ch);

$midtrans_result = json_decode($response);
$snap_token = isset($midtrans_result->token) ? $midtrans_result->token : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CanvasPay x Midtrans</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?php echo $client_key; ?>"></script>

    <style>
        :root {
            --primary: #0145F2;
            --accent: #08fce8;
            --bg-color: #f1f5f9;
            --text-dark: #1e293b;
            --text-gray: #64748b;
            --success: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        body {
            background-color: var(--bg-color);
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; -webkit-font-smoothing: antialiased;
        }


        .mobile-app-container {
            width: 100%; max-width: 400px; background: #ffffff;
            min-height: 100vh; position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            display: flex; flex-direction: column; overflow: hidden;
        }


        .app-header {
            padding: 20px 25px; display: flex; align-items: center; justify-content: space-between;
            background: white; position: sticky; top: 0; z-index: 10;
        }
        .btn-back {
            background: #f1f5f9; width: 40px; height: 40px; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            color: var(--text-dark); text-decoration: none; font-size: 1.2rem; transition: 0.3s;
        }
        .btn-back:hover { background: #e2e8f0; }
        .app-header h2 { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 5px;}
        .app-header h2 i { color: var(--primary); font-size: 1.4rem; }


        .scroll-content { flex: 1; padding: 10px 25px 120px 25px; overflow-y: auto; background-color: var(--bg-color); }

        .receipt-card {
            background: white; border-radius: 20px; padding: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.03); margin-top: 10px;
            position: relative;
        }
        .receipt-card::before {
            content: ''; position: absolute; top: -10px; left: 0; width: 100%; height: 20px;
            background: radial-gradient(circle, transparent, transparent 50%, var(--bg-color) 50%, var(--bg-color) 100%);
            background-size: 20px 20px;
        }

        .store-info { text-align: center; margin-bottom: 25px; }
        .store-icon { width: 55px; height: 55px; background: rgba(1, 69, 242, 0.1); color: var(--primary); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 10px auto; }
        .store-name { font-weight: 700; font-size: 1.2rem; color: var(--text-dark); }
        .invoice-number { font-size: 0.85rem; color: var(--text-gray); font-family: monospace; letter-spacing: 1px; }

        .dashed-line { border-top: 2px dashed #e2e8f0; margin: 20px 0; }

        .detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.9rem; }
        .detail-row span:first-child { color: var(--text-gray); font-weight: 500; }
        .detail-row span:last-child { color: var(--text-dark); font-weight: 600; text-align: right; max-width: 60%; }

        .total-amount { background: #f8fafc; padding: 20px; border-radius: 16px; text-align: center; margin-top: 20px; border: 1px solid #e2e8f0;}
        .total-amount span { font-size: 0.9rem; color: var(--text-gray); font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        .total-amount h1 { font-size: 2rem; font-weight: 800; color: var(--primary); margin-top: 5px;}

        .bottom-bar {
            position: absolute; bottom: 0; left: 0; width: 100%; background: white; padding: 20px 25px;
            border-top: 1px solid #f1f5f9; box-shadow: 0 -10px 30px rgba(0,0,0,0.05); z-index: 20;
        }

        .btn-pay {
            width: 100%; background-color: var(--primary); color: white; border: none;
            padding: 18px; border-radius: 16px; font-size: 1.1rem; font-weight: 700;
            cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 10px;
            transition: 0.3s; position: relative; overflow: hidden;
        }
        .btn-pay:hover { background-color: #0133b3; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(1, 69, 242, 0.2); }
        
        .simulation-badge { text-align: center; font-size: 0.75rem; color: #94a3b8; margin-top: 15px; font-weight: 500; display: flex; justify-content: center; align-items: center; gap: 5px;}

        .success-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--success); color: white; display: flex; flex-direction: column;
            justify-content: center; align-items: center; z-index: 100;
            opacity: 0; pointer-events: none; visibility: hidden;
        }
        .success-overlay.active { opacity: 1; pointer-events: all; visibility: visible; }
        .success-icon { font-size: 5rem; margin-bottom: 20px; background: rgba(255,255,255,0.2); width: 100px; height: 100px; display: flex; justify-content: center; align-items: center; border-radius: 50%; }
        .success-overlay h2 { font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; }
        .success-overlay p { font-size: 1rem; opacity: 0.9; text-align: center; padding: 0 30px; }

        @media (min-width: 421px) {
            .mobile-app-container { min-height: 85vh; border-radius: 30px; margin: 20px 0; }
        }
    </style>
</head>
<body>

    <div class="mobile-app-container">
        
        <header class="app-header gs-reveal">
            <a href="index.php" class="btn-back"><i class="ph-bold ph-caret-left"></i></a>
            <h2><i class="ph-fill ph-wallet"></i> CanvasPay</h2>
            <div style="width: 40px;"></div> </header>

        <div class="scroll-content">
            <div class="receipt-card gs-item">
                <div class="store-info">
                    <div class="store-icon"><i class="ph-fill ph-storefront"></i></div>
                    <div class="store-name">Canvas Laundry</div>
                    <div class="invoice-number"><?php echo $transaksi['no_invoice']; ?></div>
                </div>

                <div class="dashed-line"></div>

                <div class="detail-row">
                    <span>Pelanggan</span>
                    <span><?php echo htmlspecialchars($transaksi['nama_lengkap']); ?></span>
                </div>
                <div class="detail-row">
                    <span>Layanan</span>
                    <span><?php echo htmlspecialchars($transaksi['nama_layanan']); ?></span>
                </div>
                <div class="detail-row">
                    <span>Berat Cucian</span>
                    <span><?php echo $transaksi['berat']; ?> Kg</span>
                </div>
                <div class="detail-row">
                    <span>Waktu Pesanan</span>
                    <span><?php echo date('d M Y, H:i', strtotime($transaksi['tanggal_transaksi'])); ?></span>
                </div>

                <div class="total-amount">
                    <span>Total Pembayaran</span>
                    <h1>Rp <?php echo number_format($transaksi['total_harga'], 0, ',', '.'); ?></h1>
                </div>
            </div>
        </div>

        <div class="bottom-bar gs-item">
            <button type="button" id="pay-button" class="btn-pay">
                <i class="ph-bold ph-lock-key"></i> Lanjutkan Pembayaran
            </button>
            <div class="simulation-badge">
                <i class="ph-fill ph-shield-check"></i> Aman & Terenkripsi oleh Midtrans
            </div>
            
            <form id="success-form" method="POST" style="display: none;">
                <input type="hidden" name="payment_success" value="1">
            </form>
        </div>

        <div class="success-overlay" id="success-screen">
            <div class="success-icon gsap-success"><i class="ph-bold ph-check"></i></div>
            <h2 class="gsap-success">Pembayaran Lunas!</h2>
            <p class="gsap-success">Sistem sedang memverifikasi invoice <b><?php echo $no_invoice; ?></b>...</p>
        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            
            // Animasi Masuk
            gsap.from(".gs-reveal", { y: -20, opacity: 0, duration: 0.6, ease: "power2.out" });
            gsap.from(".gs-item", { y: 40, opacity: 0, duration: 0.8, stagger: 0.1, delay: 0.2, ease: "power3.out" });

            const payButton = document.getElementById('pay-button');
            const snapToken = '<?php echo $snap_token; ?>';

            payButton.addEventListener('click', function () {
                if(!snapToken) {
                    alert("Token Midtrans gagal dimuat. Harap periksa Server Key Anda.");
                    return;
                }

                window.snap.pay(snapToken, {
                    onSuccess: function(result){

                        document.getElementById('success-screen').classList.add('active');
                        gsap.fromTo(".gsap-success", 
                            { y: 30, opacity: 0, scale: 0.8 }, 
                            { y: 0, opacity: 1, scale: 1, duration: 0.6, stagger: 0.15, ease: "back.out(1.5)" }
                        );

                        setTimeout(() => {
                            document.getElementById('success-form').submit();
                        }, 2500);
                    },
                    onPending: function(result){
                        alert("Silakan selesaikan pembayaran sesuai instruksi.");
                    },
                    onError: function(result){
                        alert("Pembayaran Gagal diproses.");
                    },
                    onClose: function(){
                        console.log('Pop-up ditutup');
                    }
                });
            });

        });
    </script>
</body>
</html>