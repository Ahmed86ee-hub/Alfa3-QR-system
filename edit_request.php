<?php
// edit_request.php
require 'auth.php'; // حماية الصفحة
require 'com_config.php';

$message = ''; $messageType = '';
$order_number = trim($_GET['order'] ?? '');
if (empty($order_number)) die("<h2 style='text-align:center; margin-top:50px;'>لم يتم توفير رقم الطلب.</h2>");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');
    $order_status = $_POST['order_status'] ?? 'pending';
    $page_type = $_POST['page_type'] ?? 'profile';
    $theme_color = $_POST['theme_color'] ?? 'theme-glass-iphone';
    $icon_style = $_POST['icon_style'] ?? 'icon-vibrant';
    $layout_style = $_POST['layout_style'] ?? 'layout-squares'; 
    $uploaded_image_path = $_POST['current_image'] ?? '';

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $destination = $upload_dir . $order_number . '_' . time() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) { $uploaded_image_path = $destination; }
        }
    }

    $links = [
        'phone' => trim($_POST['phone'] ?? ''), 'whatsapp' => trim($_POST['whatsapp'] ?? ''),
        'facebook' => trim($_POST['facebook'] ?? ''), 'instagram' => trim($_POST['instagram'] ?? ''),
        'tiktok' => trim($_POST['tiktok'] ?? ''), 'website' => trim($_POST['website'] ?? ''),
        'other_name' => trim($_POST['other_name'] ?? ''), 'other_url' => trim($_POST['other_url'] ?? '')
    ];
    
    $page_content = json_encode(['links' => $links, 'profile_image' => $uploaded_image_path, 'layout_style' => $layout_style], JSON_UNESCAPED_UNICODE);

    if (!empty($client_name)) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE orders SET client_name = ?, client_email = ?, order_status = ?, page_type = ? WHERE order_number = ?")
                ->execute([$client_name, $client_email, $order_status, $page_type, $order_number]);
            
            $order_id = $pdo->query("SELECT id FROM orders WHERE order_number = '$order_number'")->fetchColumn();
            
            $pdo->prepare("UPDATE theme_profiles SET theme_color = ?, icon_style = ?, page_content = ? WHERE order_id = ?")
                ->execute([$theme_color, $icon_style, $page_content, $order_id]);
            
            $pdo->commit();
            $message = "تم التحديث بنجاح!"; $messageType = "success";
        } catch (\PDOException $e) { $pdo->rollBack(); $message = "خطأ: " . $e->getMessage(); $messageType = "error"; }
    }
}

try {
    $stmt = $pdo->prepare("SELECT o.*, t.theme_color, t.icon_style, t.page_content FROM orders o LEFT JOIN theme_profiles t ON o.id = t.order_id WHERE o.order_number = ?");
    $stmt->execute([$order_number]);
    $orderData = $stmt->fetch();
    if (!$orderData) die("الطلب غير موجود.");
    $content = json_decode($orderData['page_content'], true);
    $links = $content['links'] ?? [];
    $current_image = $content['profile_image'] ?? '';
    $current_layout = $content['layout_style'] ?? 'layout-squares';
    $preview_link = ($orderData['page_type'] == 'menu') ? 'menu_view.php' : 'profile.php';
} catch (\PDOException $e) { die("خطأ: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>تعديل | <?= htmlspecialchars($order_number) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-group{margin-bottom:12px;} 
        .form-group label{display:block;margin-bottom:4px;font-weight:bold;font-size:0.85rem;}
        .form-control { padding: 8px 12px; }
        .alert{padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;font-weight:bold;}
        .alert.success{background:#d4edda;color:#155724;}.alert.error{background:#f8d7da;color:#721c24;}
        .img-preview{width:60px;height:60px;object-fit:cover;border-radius:8px;border:1px solid #ddd;margin-top:5px;}
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <main class="container" style="max-width: 800px; margin: 20px auto; padding: 0 20px;">
        <?php if (!empty($message)): ?><div class="alert <?= $messageType ?>"><?= $message ?></div><?php endif; ?>
        
        <div class="card" style="background: var(--card-bg, #fff); padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid var(--border-color, #eee);">
            <form action="" method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <div class="form-group"><label>الاسم *</label><input type="text" name="client_name" class="form-control" value="<?= htmlspecialchars($orderData['client_name']) ?>" required></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div class="form-group"><label>النوع</label><select name="page_type" class="form-control"><option value="profile" <?= $orderData['page_type'] == 'profile' ? 'selected' : '' ?>>بروفايل</option><option value="menu" <?= $orderData['page_type'] == 'menu' ? 'selected' : '' ?>>منيو</option></select></div>
                            <div class="form-group"><label>الحالة</label><select name="order_status" class="form-control"><option value="pending" <?= $orderData['order_status'] == 'pending' ? 'selected' : '' ?>>قيد التنفيذ</option><option value="completed" <?= $orderData['order_status'] == 'completed' ? 'selected' : '' ?>>مكتمل</option></select></div>
                        </div>
                        <div class="form-group"><label>صورة الشخص / شعار</label><input type="file" name="profile_image" class="form-control" accept="image/*"><input type="hidden" name="current_image" value="<?= htmlspecialchars($current_image) ?>"><?php if(!empty($current_image)): ?><img src="<?= htmlspecialchars($current_image) ?>" class="img-preview" alt="Image"><?php endif; ?></div>
                    </div>
                    <div>
                        <div class="form-group"><label>الثيم</label><select name="theme_color" class="form-control">
                            <option value="theme-glass-iphone" <?= $orderData['theme_color'] == 'theme-glass-iphone' ? 'selected' : '' ?>>شفاف آيفون</option>
                            <option value="theme-black-luxury" <?= $orderData['theme_color'] == 'theme-black-luxury' ? 'selected' : '' ?>>رصاصي كلاسيكي</option>
                            <option value="theme-gold-radiant" <?= $orderData['theme_color'] == 'theme-gold-radiant' ? 'selected' : '' ?>>ذهبي أنيق</option>
                            <option value="theme-silver-shiny" <?= $orderData['theme_color'] == 'theme-silver-shiny' ? 'selected' : '' ?>>فضي لامع</option>
                            <option value="theme-classic-blue" <?= $orderData['theme_color'] == 'theme-classic-blue' ? 'selected' : '' ?>>أزرق كلاسيكي</option>
                            <option value="theme-emerald-green" <?= $orderData['theme_color'] == 'theme-emerald-green' ? 'selected' : '' ?>>أخضر زمردي</option>
                        </select></div>
                        <div class="form-group"><label>شكل الأيقونات</label><select name="icon_style" class="form-control">
                            <option value="icon-vibrant" <?= $orderData['icon_style'] == 'icon-vibrant' ? 'selected' : '' ?>>ألوان زاهية</option>
                            <option value="icon-bold" <?= $orderData['icon_style'] == 'icon-bold' ? 'selected' : '' ?>>ألوان جريئة</option>
                            <option value="icon-iphone" <?= $orderData['icon_style'] == 'icon-iphone' ? 'selected' : '' ?>>أزرار آيفون</option>
                            <option value="icon-ios-glass" <?= $orderData['icon_style'] == 'icon-ios-glass' ? 'selected' : '' ?>>آيفون (شفاف 25%)</option>
                            <option value="icon-ice" <?= $orderData['icon_style'] == 'icon-ice' ? 'selected' : '' ?>>قطع ثلج</option>
                            <option value="icon-wood" <?= $orderData['icon_style'] == 'icon-wood' ? 'selected' : '' ?>>قطع خشبية</option>
                        </select></div>
                        <div class="form-group"><label>تخطيط الروابط</label><select name="layout_style" class="form-control">
                            <option value="layout-squares" <?= $current_layout == 'layout-squares' ? 'selected' : '' ?>>شبكة مربعات</option>
                            <option value="layout-circles" <?= $current_layout == 'layout-circles' ? 'selected' : '' ?>>شبكة دوائر</option>
                            <option value="layout-rectangles" <?= $current_layout == 'layout-rectangles' ? 'selected' : '' ?>>مستطيلات</option>
                            <option value="layout-balloons" <?= $current_layout == 'layout-balloons' ? 'selected' : '' ?>>بالونات طائرة</option>
                        </select></div>
                        
                        <?php if ($orderData['page_type'] == 'menu'): ?>
                            <a href="upload_menu.php?order=<?= urlencode($order_number) ?>" class="btn" style="display:block; text-align:center; background:#6f42c1; color:#fff; padding:10px; border-radius:8px; text-decoration:none; font-weight:bold; margin-top: 15px;">
                                <i class="fas fa-list-ul"></i> إدارة/تحرير المنيو
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <hr style="border: 1px solid var(--border-color); margin: 15px 0;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group"><label>هاتف</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($links['phone'] ?? '') ?>"></div>
                    <div class="form-group"><label>واتساب</label><input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($links['whatsapp'] ?? '') ?>"></div>
                    <div class="form-group"><label>فيسبوك</label><input type="url" name="facebook" class="form-control" value="<?= htmlspecialchars($links['facebook'] ?? '') ?>"></div>
                    <div class="form-group"><label>انستغرام</label><input type="url" name="instagram" class="form-control" value="<?= htmlspecialchars($links['instagram'] ?? '') ?>"></div>
                    <div class="form-group"><label>تيك توك</label><input type="url" name="tiktok" class="form-control" value="<?= htmlspecialchars($links['tiktok'] ?? '') ?>"></div>
                    <div class="form-group"><label>الموقع</label><input type="url" name="website" class="form-control" value="<?= htmlspecialchars($links['website'] ?? '') ?>"></div>
                </div>
                <div style="text-align: left; margin-top: 15px;"><button type="submit" class="btn btn-primary" style="padding: 10px 25px; background: #d4af37; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">💾 حفظ التعديلات</button></div>
            </form>
        </div>
    </main>
</body>
</html>