<?php
// client_request.php
require 'auth.php'; // حماية الصفحة
require 'com_config.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');
    $page_type = $_POST['page_type'] ?? 'profile';
    $theme_color = $_POST['theme_color'] ?? 'theme-glass-iphone';
    $icon_style = $_POST['icon_style'] ?? 'icon-vibrant';
    $layout_style = $_POST['layout_style'] ?? 'layout-squares'; 

    $uploaded_image_path = '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $destination = $upload_dir . 'profile_' . time() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                $uploaded_image_path = $destination;
            }
        }
    }

    $links = [
        'phone'      => trim($_POST['phone'] ?? ''),
        'whatsapp'   => trim($_POST['whatsapp'] ?? ''),
        'facebook'   => trim($_POST['facebook'] ?? ''),
        'instagram'  => trim($_POST['instagram'] ?? ''),
        'tiktok'     => trim($_POST['tiktok'] ?? ''),
        'website'    => trim($_POST['website'] ?? ''),
        'other_name' => trim($_POST['other_name'] ?? ''),
        'other_url'  => trim($_POST['other_url'] ?? '')
    ];
    
    $page_content = json_encode([
        'links' => $links,
        'layout_style' => $layout_style,
        'profile_image' => $uploaded_image_path
    ], JSON_UNESCAPED_UNICODE);
    
    $order_number = 'QR-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

    if (!empty($client_name)) {
        try {
            $pdo->beginTransaction();
            $stmt_order = $pdo->prepare("INSERT INTO orders (order_number, client_name, client_email, order_status, page_type) VALUES (?, ?, ?, 'pending', ?)");
            $stmt_order->execute([$order_number, $client_name, $client_email, $page_type]);
            $order_id = $pdo->lastInsertId();

            $stmt_theme = $pdo->prepare("INSERT INTO theme_profiles (order_id, theme_color, icon_style, page_content) VALUES (?, ?, ?, ?)");
            $stmt_theme->execute([$order_id, $theme_color, $icon_style, $page_content]);

            $pdo->commit();
            header("Location: qr_generator.php?order=" . $order_number);
            exit;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $message = "حدث خطأ أثناء الحفظ: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "الرجاء إدخال اسم الزبون.";
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة طلب | QR System Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: var(--text-color); }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .alert.error { background:#f8d7da; color:#721c24; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .card { background: var(--card-bg, #fff); padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color, #eee); }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <main class="container">
        <div style="margin-bottom: 20px;">
            <h2><i class="fas fa-plus-circle" style="color: #d4af37;"></i> إضافة طلب زبون جديد</h2>
        </div>
        
        <?php if (!empty($message)): ?><div class="alert <?= $messageType ?>"><?= $message ?></div><?php endif; ?>
        
        <div class="card">
            <form action="client_request.php" method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <div class="form-group"><label>اسم الزبون / الماركة *</label><input type="text" name="client_name" class="form-control" required></div>
                        <div class="form-group"><label>البريد الإلكتروني</label><input type="email" name="client_email" class="form-control"></div>
                        <div class="form-group"><label>أيقونة/صورة الزبون</label><input type="file" name="profile_image" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>نوع الصفحة</label><select name="page_type" class="form-control"><option value="profile">بطاقة تعريفية (Profile)</option><option value="menu">قائمة طعام (Menu)</option></select></div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label>اللون الأساسي (الثيم)</label>
                            <select name="theme_color" class="form-control">
                                <option value="theme-glass-iphone">شفاف آيفون</option>
                                <option value="theme-black-luxury">رصاصي كلاسيكي</option>
                                <option value="theme-gold-radiant">ذهبي أنيق</option>
                                <option value="theme-silver-shiny">فضي لامع</option>
                                <option value="theme-classic-blue">أزرق كلاسيكي</option>
                                <option value="theme-emerald-green">أخضر زمردي</option>
                            </select>
                        </div>
                        <div class="form-group"><label>تنسيق الأيقونات</label><select name="icon_style" class="form-control">
                            <option value="icon-vibrant">ألوان زاهية</option>
                            <option value="icon-bold">ألوان جريئة</option>
                            <option value="icon-iphone">أزرار آيفون</option>
                            <option value="icon-ios-glass">آيفون (شفاف 25%)</option>
                            <option value="icon-ice">قطع ثلج</option>
                            <option value="icon-wood">قطع خشبية</option>
                        </select></div>
                        <div class="form-group"><label>تخطيط وعرض الروابط</label><select name="layout_style" class="form-control">
                            <option value="layout-squares">شبكة مربعات</option>
                            <option value="layout-circles">شبكة دوائر</option>
                            <option value="layout-rectangles">مستطيلات</option>
                            <option value="layout-balloons">بالونات طائرة 🎈</option>
                        </select></div>
                    </div>
                </div>
                <hr style="border: 1px solid var(--border-color); margin: 20px 0;">
                <h3>أرقام وروابط التواصل</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                    <div class="form-group"><label>رقم الهاتف (للاتصال المباشر والحفظ)</label><input type="text" name="phone" class="form-control" placeholder="07..."></div>
                    <div class="form-group"><label>رقم الواتساب</label><input type="text" name="whatsapp" class="form-control" placeholder="+964..."></div>
                    <div class="form-group"><label>رابط فيسبوك</label><input type="url" name="facebook" class="form-control"></div>
                    <div class="form-group"><label>رابط انستغرام</label><input type="url" name="instagram" class="form-control"></div>
                    <div class="form-group"><label>رابط تيك توك</label><input type="url" name="tiktok" class="form-control"></div>
                    <div class="form-group"><label>الموقع الإلكتروني</label><input type="url" name="website" class="form-control"></div>
                </div>
                <hr style="border: 1px dashed var(--border-color); margin: 20px 0;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group"><label>رابط إضافي (الاسم)</label><input type="text" name="other_name" class="form-control"></div>
                    <div class="form-group"><label>الرابط (URL)</label><input type="url" name="other_url" class="form-control"></div>
                </div>
                <div style="text-align: left; margin-top: 20px;"><button type="submit" class="btn btn-primary" style="font-size: 1.1rem; padding: 12px 30px; background: #d4af37; color: #1a1a1a; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">💾 حفظ وتوليد الرمز</button></div>
            </form>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>