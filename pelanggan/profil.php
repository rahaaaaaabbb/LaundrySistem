<?php
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pelanggan') { header("Location: ../login.php"); exit; }
require '../config/koneksi.php';

$id_user = $_SESSION['id_user'];
$pesan = "";
$tipe = "";


$u_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, u.username FROM pelanggan p JOIN users u ON p.id_user = u.id_user WHERE p.id_user = '$id_user'"));


if (isset($_POST['update_password'])) {
    $pass_lama = $_POST['pass_lama'];
    $pass_baru = $_POST['pass_baru'];
    
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM users WHERE id_user = '$id_user'"));
    if (password_verify($pass_lama, $check['password'])) {
        $hash = password_hash($pass_baru, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password = '$hash' WHERE id_user = '$id_user'");
        $pesan = "Password berhasil diperbarui!";
        $tipe = "success";
    } else {
        $pesan = "Password lama tidak sesuai!";
        $tipe = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Profil Saya - Canvas Laundry</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root { --primary: #125270; --accent: #0b9fb3; --bg: #f4f7f8; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg); display: flex; justify-content: center; }
        .app-container { width: 100%; max-width: 420px; background: var(--bg); min-height: 100vh; padding-bottom: 100px; }
        
        .profile-header { background: var(--primary); padding: 50px 25px; color: white; border-radius: 0 0 40px 40px; text-align: center; }
        .avatar-lg { width: 90px; height: 90px; background: rgba(255,255,255,0.2); border-radius: 25px; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; border: 2px solid var(--accent); }
        
        .content { padding: 25px; }
        .info-card { background: white; padding: 20px; border-radius: 25px; margin-top: -30px; box-shadow: 0 10px 25px rgba(18, 82, 112, 0.05); }
        
        .form-group { margin-top: 25px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--primary); margin-bottom: 8px; }
        .form-control { width: 100%; padding: 16px; border-radius: 16px; border: 1px solid #e2e8f0; background: #f8fafc; outline: none; transition: 0.3s; }
        .form-control:focus { border-color: var(--accent); background: white; }
        
        .btn-save { width: 100%; padding: 18px; background: var(--primary); color: white; border: none; border-radius: 16px; font-weight: 700; margin-top: 20px; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { background: var(--accent); transform: translateY(-3px); }
        
        .alert { padding: 15px; border-radius: 15px; font-size: 0.9rem; font-weight: 600; margin-bottom: 20px; text-align: center; }
        .success { background: #eaf6f8; color: var(--accent); }
        .error { background: #fef2f2; color: #ef4444; }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="profile-header">
            <div class="avatar-lg"><?php echo strtoupper(substr($u_data['nama_lengkap'], 0, 1)); ?></div>
            <h2><?php echo htmlspecialchars($u_data['nama_lengkap']); ?></h2>
            <p style="opacity: 0.7;">@<?php echo $u_data['username']; ?></p>
        </div>

        <div class="content">
            <div class="info-card">
                <?php if($pesan): ?>
                    <div class="alert <?php echo $tipe; ?>"><?php echo $pesan; ?></div>
                <?php endif; ?>

                <h3 style="color: var(--primary);"><i class="ph-bold ph-shield-check"></i> Keamanan Akun</h3>
                
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Password Lama</label>
                        <input type="password" name="pass_lama" class="form-control" required placeholder="Masukkan password saat ini">
                    </div>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="pass_baru" class="form-control" required placeholder="Buat password baru">
                    </div>
                    <button type="submit" name="update_password" class="btn-save">Update Password</button>
                </form>

                <hr style="margin: 30px 0; border: none; border-top: 1px dashed #e2e8f0;">
                
                <a href="../logout.php" style="display: flex; align-items: center; justify-content: center; gap: 10px; color: #ef4444; text-decoration: none; font-weight: 700;">
                    <i class="ph-bold ph-sign-out"></i> Keluar Aplikasi
                </a>
            </div>
        </div>

        <?php include 'nav_include.php'; ?>
    </div>

    <script>
        gsap.from(".info-card", { y: 50, opacity: 0, duration: 0.8, ease: "power3.out" });
    </script>
</body>
</html>