<?php
// profile.php
require 'com_config.php';

$order_number = isset($_GET['order']) ? trim($_GET['order']) : '';

if (empty($order_number)) die("<div style='text-align:center; padding:50px;'><h2>الرابط غير صالح</h2></div>");

try {
    $stmt = $pdo->prepare("SELECT o.client_name, t.theme_color, t.icon_style, t.page_content FROM orders o JOIN theme_profiles t ON o.id = t.order_id WHERE o.order_number = ?");
    $stmt->execute([$order_number]);
    $profileData = $stmt->fetch();
    if (!$profileData) die("<div style='text-align:center; padding:50px;'><h2>الصفحة غير موجودة</h2></div>");

    $content = json_decode($profileData['page_content'], true);
    $links = $content['links'] ?? [];
    $profile_image = !empty($content['profile_image']) ? htmlspecialchars($content['profile_image']) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
    $layout_style = $content['layout_style'] ?? 'layout-squares'; 
    $theme_color = $profileData['theme_color'];
    $icon_style = $profileData['icon_style'];

} catch (\PDOException $e) { die("خطأ: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($profileData['client_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        
        /* 1. الخلفية العامة (رصاصي إلى أرجواني) */
        body { 
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
            background: linear-gradient(135deg, #6e7378, #8e44ad);
            background-attachment: fixed; color: #fff;
        }
        
        /* 2. حاوية المعلومات (رصاصي وسط ثابت) */
        .profile-container { 
            width: 100%; max-width: 420px; text-align: center; padding: 40px 20px; 
            border-radius: 30px; position: relative; z-index: 2;
            background: rgba(110, 115, 120, 0.95) !important; 
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        /* لمسات الثيم على الحاوية */
        .theme-gold-radiant { border-top: 5px solid #D4AF37; }
        .theme-silver-shiny { border-top: 5px solid #C0C0C0; }
        .theme-black-luxury { border-top: 5px solid #333; }

        /* 👤 قسم الصورة والاسم */
        .avatar-ring { display: inline-block; padding: 5px; border-radius: 50%; background: rgba(255,255,255,0.1); margin-bottom: 15px; }
        .profile-img { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; display: block; }
        .profile-name { margin: 0; font-size: 1.6rem; font-weight: 800; color: #fff; }
        .profile-job { margin: 5px 0 25px; opacity: 0.8; font-size: 0.95rem; }

        /* 🖼️ التخطيطات (Layouts) */
        .social-btn { display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; transition: all 0.3s; color: #fff; }
        
        /* تأثيرات التخطيط */
        .layout-rectangles { display: flex; flex-direction: column; gap: 12px; }
        .layout-rectangles .social-btn { padding: 15px; border-radius: 15px; gap: 15px; width: 100%; justify-content: flex-start; }

        .layout-squares, .layout-circles { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 15px; }
        .layout-squares .social-btn, .layout-circles .social-btn { aspect-ratio: 1; flex-direction: column; gap: 8px; text-align: center; font-size: 0.8rem; padding: 10px; }
        .layout-squares .social-btn { border-radius: 20px; }
        .layout-circles .social-btn { border-radius: 50%; }

        /* 3. تأثير iOS 25% Glass Icons */
        .icon-ios-glass {
            background: rgba(255, 255, 255, 0.25) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff !important;
        }
        
        .vcard-btn { width: 100%; padding: 18px; border-radius: 20px; border: none; cursor: pointer; background: rgba(255,255,255,0.1); color: white; font-weight: bold; margin-top: 30px; transition: 0.3s; }
        .vcard-btn:hover { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body class="<?= htmlspecialchars($theme_color) ?>">

    <div class="profile-container">
        <div class="avatar-ring">
            <img src="<?= $profile_image ?>" alt="Profile" class="profile-img">
        </div>
        <h1 class="profile-name"><?= htmlspecialchars($profileData['client_name']) ?></h1>
        <p class="profile-job">حساب أعمال موثق</p>
        
        <div class="<?= htmlspecialchars($layout_style) ?>">
            <?php 
            $social_list = [
                'phone'     => ['label' => 'اتصال', 'icon' => 'fas fa-phone-alt', 'prefix' => 'tel:'],
                'whatsapp'  => ['label' => 'واتساب', 'icon' => 'fab fa-whatsapp', 'prefix' => 'https://wa.me/'],
                'facebook'  => ['label' => 'فيسبوك', 'icon' => 'fab fa-facebook-f', 'prefix' => ''],
                'instagram' => ['label' => 'انستغرام', 'icon' => 'fab fa-instagram', 'prefix' => ''],
                'website'   => ['label' => 'الموقع', 'icon' => 'fas fa-globe', 'prefix' => '']
            ];

            foreach ($social_list as $key => $data) {
                if (!empty($links[$key])) {
                    $url = ($key === 'whatsapp' || $key === 'phone') ? $data['prefix'] . str_replace(['+', ' '], '', $links[$key]) : $links[$key];
                    echo '<a href="'.htmlspecialchars($url).'" class="social-btn icon-ios-glass" target="_blank">
                            <i class="'.$data['icon'].'"></i> <span>'.$data['label'].'</span>
                          </a>';
                }
            }
            ?>
        </div>
        
        <button class="vcard-btn" onclick="downloadVCard()">حفظ في جهات الاتصال</button>
        <div style="margin-top: 20px; font-size: 0.8rem; opacity: 0.6;">Alpha3 Smart Solutions</div>
    </div>

    <script>
        function downloadVCard() {
            const vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:<?= htmlspecialchars($profileData['client_name']) ?>\nTEL:<?= htmlspecialchars($links['phone'] ?? '') ?>\nURL:<?= htmlspecialchars($links['website'] ?? '') ?>\nEND:VCARD";
            const blob = new Blob([vcard], { type: 'text/vcard' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'contact.vcf'; a.click();
        }
    </script>
</body>
</html>