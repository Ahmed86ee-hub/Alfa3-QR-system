<?php
// login.php
session_start();

// إذا كان مسجل الدخول بالفعل، وجهه للداشبورد
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // التحقق من بيانات الدخول (اسم المستخدم الافتراضي: admin)
    if ($username === 'admin' && $password === '1199@alfa3A') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | QR System Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { 
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; 
            background: linear-gradient(135deg, #1a1a1a 0%, #3a3f47 100%); color: #fff;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1); padding: 40px; border-radius: 20px;
            width: 100%; max-width: 400px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); text-align: center;
        }
        .login-card h2 { margin-top: 0; font-size: 1.8rem; color: #d4af37; }
        .login-card p { opacity: 0.7; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; text-align: right; }
        .form-control {
            width: 100%; padding: 15px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2);
            background: rgba(0,0,0,0.2); color: #fff; font-size: 1rem; outline: none; transition: 0.3s;
        }
        .form-control:focus { border-color: #d4af37; background: rgba(0,0,0,0.4); }
        .btn-login {
            width: 100%; padding: 15px; border-radius: 10px; border: none; font-size: 1.1rem; font-weight: bold;
            background: #d4af37; color: #1a1a1a; cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        .btn-login:hover { background: #e5c158; transform: translateY(-2px); }
        .alert { background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; color: #ff6b6b; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-card">
        <i class="fas fa-qrcode" style="font-size: 3rem; color: #d4af37; margin-bottom: 15px;"></i>
        <h2>Alpha3 Smart Solutions</h2>
        <p>بوابة إدارة النظام والطلبات</p>
        
        <?php if($error): ?>
            <div class="alert"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="اسم المستخدم" required autocomplete="off">
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="كلمة المرور" required>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>