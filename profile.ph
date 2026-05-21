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
    // صورة افتراضية أنيقة في حال عدم الرفع
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
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        
        /* 🎨 الثيمات (Themes) */
        .theme-silver-shiny { background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%); color: #333; }
        .theme-gold-radiant { background: radial-gradient(circle, #FFDF00 0%, #D4AF37 50%, #996515 100%); color: #fff; }
        .theme-black-luxury { background: linear-gradient(145deg, #1a1a1a, #000000); color: #d4af37; }
        .theme-glass-iphone { background: url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1000&auto=format&fit=crop') center/cover fixed; color: #fff; }
        
        /* حاوية البروفايل */
        .profile-container { 
            width: 100%; max-width: 420px; text-align: center; padding: 30px 20px; border-radius: 30px; position: relative; z-index: 2;
        }
        /* تطبيق الزجاج لثيم الآيفون */
        .theme-glass-iphone .profile-container {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .theme-silver-shiny .profile-container, .theme-gold-radiant .profile-container, .theme-black-luxury .profile-container {
            background: rgba(0,0,0,0.05); backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* 👤 قسم اسم وصورة الزبون بشكل أنيق */
        .avatar-ring {
            display: inline-block; padding: 5px; border-radius: 50%;
            background: linear-gradient(45deg, #ff007f, #7928ca, #ff007f);
            margin-bottom: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .profile-img { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; display: block; }
        .profile-name { margin: 0; font-size: 1.6rem; font-weight: 800; letter-spacing: 0.5px; }
        .profile-job { margin: 5px 0 25px; opacity: 0.8; font-size: 0.95rem; }

        /* 🖼️ التخطيطات (Layouts) */
        .social-btn { 
            display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; transition: all 0.2s; position: relative;
        }
        .social-btn:active { transform: scale(0.95); }

        /* المستطيلات */
        .layout-rectangles { display: flex; flex-direction: column; gap: 12px; }
        .layout-rectangles .social-btn { padding: 15px; border-radius: 15px; gap: 15px; width: 100%; justify-content: flex-start; font-size: 1.1rem; }
        .layout-rectangles i { font-size: 1.5rem; }

        /* شبكة مربعات + شبكة دوائر (Grid) */
        .layout-squares, .layout-circles { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 15px; }
        .layout-squares .social-btn, .layout-circles .social-btn { 
            aspect-ratio: 1; flex-direction: column; gap: 10px; text-align: center; font-size: 0.9rem; padding: 10px;
        }
        .layout-squares .social-btn { border-radius: 20px; }
        .layout-circles .social-btn { border-radius: 50%; }
        .layout-squares i, .layout-circles i { font-size: 2rem; }

        /* بالونات طائرة */
        .layout-balloons { position: relative; height: 350px; display: block; margin-top: 20px; }
        .layout-balloons .social-btn {
            position: absolute; width: 85px; height: 85px; border-radius: 50%; flex-direction: column; gap: 5px; font-size: 0.75rem;
            animation: floatBalloon 4s infinite ease-in-out alternate; box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .layout-balloons .social-btn::after { content: ''; position: absolute; bottom: -35px; left: 50%; width: 2px; height: 35px; background: rgba(255,255,255,0.4); transform: translateX(-50%); z-index: -1; }
        .layout-balloons i { font-size: 1.8rem; }
        
        .layout-balloons .social-btn:nth-child(1) { left: 5%; bottom: 10%; animation-delay: 0s; }
        .layout-balloons .social-btn:nth-child(2) { right: 5%; bottom: 30%; animation-delay: 1s; }
        .layout-balloons .social-btn:nth-child(3) { left: 35%; bottom: 50%; animation-delay: 2s; }
        .layout-balloons .social-btn:nth-child(4) { right: 35%; bottom: 10%; animation-delay: 1.5s; }
        .layout-balloons .social-btn:nth-child(5) { left: 20%; bottom: 70%; animation-delay: 0.5s; }

        @keyframes floatBalloon { 0% { transform: translateY(0) rotate(-3deg); } 100% { transform: translateY(-20px) rotate(3deg); } }

        /* ✨ ستايل الأيقونات (Icon Styles) */
        .icon-iphone { background: #fff !important; color: #000 !important; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: none; }
        .theme-black-luxury .icon-iphone { background: #222 !important; color: #fff !important; border: 1px solid #444; }
        
        .icon-ice { background: rgba(255, 255, 255, 0.25) !important; border: 1px solid rgba(255, 255, 255, 0.5); color: inherit; backdrop-filter: blur(10px); box-shadow: inset 0 0 10px rgba(255,255,255,0.4); }
        .theme-silver-shiny .icon-ice { background: rgba(0,0,0,0.05)!important; border-color: rgba(0,0,0,0.1); }
        
        .icon-wood { background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-color: #8B5A2B !important; color: #fff !important; border: 2px solid #5C3A21; box-shadow: inset 0 0 10px rgba(0,0,0,0.5); }
        
        .vcard-btn { width: 100%; padding: 18px; border-radius: 20px; border: none; cursor: pointer; background: #000; color: white; font-weight: bold; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(0,0,0,0.2); transition: 0.3s; }
        .theme-black-luxury .vcard-btn { background: #d4af37; color: #000; }
        .vcard-btn:active { transform: scale(0.95); }
        .footer-text { margin-top: 40px; font-size: 0.8rem; opacity: 0.7; }
    </style>
</head>
<body class="<?= htmlspecialchars($theme_color) ?>">

    <div class="profile-container">
        
        <!-- اسم وصورة الزبون -->
        <div class="avatar-ring">
            <img src="<?= $profile_image ?>" alt="Profile" class="profile-img">
        </div>
        <h1 class="profile-name"><?= htmlspecialchars($profileData['client_name']) ?></h1>
        <p class="profile-job"><i class="fas fa-check-circle" style="color: #4CAF50;"></i> حساب أعمال موثق</p>
        
        <!-- الروابط -->
        <div class="<?= htmlspecialchars($layout_style) ?>">
            <?php 
            // إعداد ألوان السوشيال ميديا الرسمية (لخيارات الزاهية والجريئة)
            $social_colors = [
                'whatsapp'  => ['label' => 'واتساب', 'icon' => 'fab fa-whatsapp', 'prefix' => 'https://wa.me/', 'bg' => '#25D366', 'color' => '#fff'],
                'facebook'  => ['label' => 'فيسبوك', 'icon' => 'fab fa-facebook-f', 'prefix' => '', 'bg' => '#1877F2', 'color' => '#fff'],
                'instagram' => ['label' => 'انستغرام', 'icon' => 'fab fa-instagram', 'prefix' => '', 'bg' => 'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)', 'color' => '#fff'],
                'tiktok'    => ['label' => 'تيك توك', 'icon' => 'fab fa-tiktok', 'prefix' => '', 'bg' => '#010101', 'color' => '#fff'],
                'website'   => ['label' => 'الموقع', 'icon' => 'fas fa-globe', 'prefix' => '', 'bg' => '#007bff', 'color' => '#fff'],
            ];

            foreach ($social_colors as $key => $data) {
                if (!empty($links[$key])) {
                    $url = ($key === 'whatsapp') ? $data['prefix'] . str_replace(['+', ' '], '', $links[$key]) : $links[$key];
                    if ($key === 'facebook') { $url = (strpos($url, '?') !== false) ? $url . '&app=browser' : $url . '?app=browser'; }

                    // تطبيق الألوان الزاهية والجريئة
                    $inline_style = "";
                    if ($icon_style === 'icon-vibrant') {
                        $inline_style = "background: {$data['bg']}; color: {$data['color']}; border: none;";
                    } elseif ($icon_style === 'icon-bold') {
                        $inline_style = "background: {$data['bg']}; color: {$data['color']}; border: 3px solid #000; box-shadow: 4px 4px 0px #000;";
                    }

                    // البالونات الشفافة تجبر الستايل على تجاهل الألوان لتظل شفافة
                    if ($layout_style === 'layout-balloons') {
                        $inline_style = "background: rgba(255,255,255,0.2) !important; color: inherit;";
                    }

                    echo '<a href="'.htmlspecialchars($url).'" class="social-btn '.htmlspecialchars($icon_style).'" style="'.$inline_style.'" target="_blank">
                            <i class="'.$data['icon'].'"></i> <span>'.$data['label'].'</span>
                          </a>';
                }
            }

            if (!empty($links['other_name']) && !empty($links['other_url'])): 
                $other_style = ($icon_style === 'icon-vibrant' || $icon_style === 'icon-bold') ? "background: #555; color: #fff;" : "";
                if ($layout_style === 'layout-balloons') $other_style = "background: rgba(255,255,255,0.2) !important; color: inherit;";
            ?>
                <a href="<?= htmlspecialchars($links['other_url']) ?>" class="social-btn <?= htmlspecialchars($icon_style) ?>" style="<?= $other_style ?>" target="_blank">
                    <i class="fas fa-link"></i> <span><?= htmlspecialchars($links['other_name']) ?></span>
                </a>
            <?php endif; ?>
        </div>
        
        <?php if($layout_style !== 'layout-balloons'): ?>
        <div style="margin-top: 35px;">
            <button class="vcard-btn" onclick="alert('سيتم إضافة كود vCard قريباً')">حفظ في جهات الاتصال</button>
        </div>
        <?php endif; ?>

        <div class="footer-text">
            مدعوم بواسطة Alpha3 Smart Solutions
        </div>
    </div>

</body>
</html>