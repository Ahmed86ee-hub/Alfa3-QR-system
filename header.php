<?php
// header.php
?>
<style>
    .unified-navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--card-bg, #ffffff);
        padding: 15px 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border-bottom: 2px solid var(--primary-color, #d4af37);
        position: sticky;
        top: 0;
        z-index: 1000;
        gap: 15px;
        flex-wrap: wrap;
    }
    .unified-navbar .logo h1 {
        margin: 0;
        font-size: 1.5rem;
        color: var(--text-color, #333);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .unified-navbar .center-controls {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .unified-navbar .left-controls {
        /* يدفع العنصر لأقصى اليسار في اتجاه RTL */
        margin-right: auto; 
    }
    .header-btn {
        background: rgba(0,0,0,0.05);
        border: none;
        padding: 8px 15px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        color: var(--text-color, #333);
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        font-size: 0.9rem;
    }
    .header-btn:hover {
        background: rgba(0,0,0,0.1);
    }
    .btn-logout {
        background: #dc3545;
        color: white;
    }
    .btn-logout:hover {
        background: #c82333;
        color: white;
    }
    
    /* للوضع الليلي في لوحة التحكم */
    [data-theme="dark"] .unified-navbar {
        background: #1a1a1a;
        color: #fff;
    }
    [data-theme="dark"] .header-btn {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
</style>

<header class="unified-navbar">
    <div class="logo">
        <a href="index.php" style="text-decoration: none; color: inherit;">
            <h1><i class="fas fa-qrcode" style="color: #d4af37;"></i> QR System Pro</h1>
        </a>
    </div>

    <div class="center-controls">
        <a href="index.php" class="header-btn"><i class="fas fa-home"></i> الرئيسية</a>
        <button class="header-btn" id="langToggle" title="تغيير اللغة"><i class="fas fa-globe"></i> AR/EN</button>
        <button class="header-btn" id="themeToggle" title="تبديل الثيم الداشبورد"><i class="fas fa-moon"></i></button>
    </div>

    <div class="left-controls">
        <a href="logout.php" class="header-btn btn-logout"><i class="fas fa-power-off"></i> تسجيل الخروج</a>
    </div>
</header>