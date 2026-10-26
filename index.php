<?php
session_start();
require 'config/koneksi.php';

$error = '';

// Proses pengecekan login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        if ($password === $row['password']) {
    $_SESSION['id_user'] = $row['id_user'];
    $_SESSION['role'] = $row['role'];
            
            if ($row['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else if ($row['role'] == 'pelanggan') {
                header("Location: pelanggan/index.php");
            }
            exit;
        } else {
            $error = "Password yang Anda masukkan salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Canvas Cloud Laundry</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            background-color: #1a202c; /* Background luar gelap agar card menonjol */
            background-image: radial-gradient(circle at top right, #08fce8, #0b2c6e);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Container Utama */
        .login-wrapper {
            display: flex;
            width: 900px;
            height: 550px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            position: relative;
        }

        /* Sisi Kiri: Foto dengan Masking Organik */
        .split-left {
            flex: 1.1;
            position: relative;
            background-color: #0145F2; 
            clip-path: url(#fluid-mask);
            -webkit-clip-path: url(#fluid-mask);
            overflow: hidden;
        }

        .img-bg {
            width: 100%;
            height: 110%;
            object-fit: cover;
            display: block;
        }

        .brand-overlay {
            position: absolute;
            bottom: 40px;
            left: 40px;
            color: white;
            z-index: 2;
            pointer-events: none;
            text-shadow: 0 4px 15px rgba(0,0,0,0.6);
        }
        .brand-overlay h1 { font-size: 2.2rem; font-weight: 700; line-height: 1.1; }

        /* Sisi Kanan: Form Login */
        .split-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 50px 60px;
            background-color: #ffffff;
        }

        .split-right h2 {
            font-size: 1.8rem;
            color: #111;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: #0145F2; /* Electric Blue */
            box-shadow: 0 0 0 3px rgba(1, 69, 242, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: #062b55;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background-color: #0be0d6;
        }

        /* Divider & Social Login */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0 20px;
            color: #a0aec0;
            font-size: 0.8rem;
        }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #e2e8f0; }
        .divider:not(:empty)::before { margin-right: .5em; }
        .divider:not(:empty)::after { margin-left: .5em; }

        .social-login { display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; }
        .btn-social {
            width: 45px; height: 45px; border: 1px solid #e2e8f0; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; background: white; cursor: pointer;
        }

        .forgot-link { display: block; text-align: center; color: #0145F2; text-decoration: none; font-size: 0.85rem; }

        .error-msg {
            background: #fff5f5; color: #c53030; padding: 10px; border-radius: 8px;
            margin-bottom: 15px; font-size: 0.85rem; text-align: center; border: 1px solid #fed7d7;
        }

        @media (max-width: 850px) {
            .login-wrapper { width: 95%; flex-direction: column; height: auto; }
            .split-left { height: 200px; clip-path: none; -webkit-clip-path: none; }
        }
    </style>
</head>
<body>

    <svg style="width:0; height:0; position:absolute;">
      <defs>
        <clipPath id="fluid-mask" clipPathUnits="objectBoundingBox">
          <path d="M 0 0 L 0.85 0 C 0.6 0.35, 1 0.65, 0.75 1 L 0 1 Z"></path>
        </clipPath>
      </defs>
    </svg>

    <div class="login-wrapper">
        <div class="split-left">
            <img src="assets/img/bg.jpg" class="img-bg" alt="Rumah Laundry">
            <div class="brand-overlay gsap-left">
                <h1>Rumah<br>Laundry</h1>
            </div>
        </div>

        <div class="split-right">
            <h2 class="gsap-right">Log in</h2>
            
            <?php if($error): ?>
                <div class="error-msg gsap-right"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-group gsap-right">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="admin / pelanggan">
                </div>
                <div class="input-group gsap-right">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-login gsap-right">Log in</button>
            </form>

            <div class="divider gsap-right">or log in with</div>
            
            <div class="social-login gsap-right">
                <button class="btn-social">
                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/google/google-original.svg" width="20">
                </button>
            </div>

            <a href="#" class="forgot-link gsap-right">Forgot login or password?</a>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            gsap.from(".login-wrapper", { duration: 1, opacity: 0, scale: 0.9, ease: "power3.out" });
            gsap.from(".gsap-left", { duration: 1, opacity: 0, x: -30, delay: 0.5, ease: "power2.out" });
            gsap.from(".gsap-right", { duration: 0.8, opacity: 0, y: 20, stagger: 0.1, delay: 0.4, ease: "power2.out" });
        });
    </script>
</body>
</html>