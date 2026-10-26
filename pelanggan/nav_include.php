<style>
    .bottom-nav {
        position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
        width: 100%; max-width: 420px; background: white;
        display: flex; justify-content: space-around; padding: 12px 5px 25px 5px;
        box-shadow: 0 -10px 30px rgba(18, 82, 112, 0.08); border-top: 1px solid #f1f5f9;
        z-index: 100; border-radius: 25px 25px 0 0;
    }
    .nav-item {
        display: flex; flex-direction: column; align-items: center; gap: 4px;
        color: #94a3b8; text-decoration: none; font-size: 0.7rem; font-weight: 700; transition: 0.3s;
    }
    .nav-item i { font-size: 1.4rem; transition: 0.3s; }
    .nav-item.active { color: #0b9fb3; } 
    .nav-item.active i { color: #125270; transform: translateY(-3px); } /* Deep Navy */
    .nav-logout { color: #f43f5e; } 
</style>

<nav class="bottom-nav">
    <?php $current = basename($_SERVER['PHP_SELF']); ?>
    <a href="index.php" class="nav-item <?php echo ($current == 'index.php') ? 'active' : ''; ?>">
        <i class="ph-fill ph-house"></i><span>Beranda</span>
    </a>
    <a href="pesanan.php" class="nav-item <?php echo ($current == 'pesanan.php') ? 'active' : ''; ?>">
        <i class="ph-fill ph-shopping-bag"></i><span>Pesanan</span>
    </a>
    <a href="riwayat.php" class="nav-item <?php echo ($current == 'riwayat.php') ? 'active' : ''; ?>">
        <i class="ph-fill ph-clock-counter-clockwise"></i><span>Riwayat</span>
    </a>
    <a href="profil.php" class="nav-item <?php echo ($current == 'profil.php') ? 'active' : ''; ?>">
        <i class="ph-fill ph-user"></i><span>Profil</span>
    </a>
    <a href="../logout.php" class="nav-item nav-logout">
        <i class="ph-bold ph-power"></i><span>Keluar</span>
    </a>
</nav>