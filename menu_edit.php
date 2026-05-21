<?php
// menu_edit.php
require 'auth.php'; // الحماية
require 'com_config.php';

$order_number = trim($_GET['order'] ?? '');
if (empty($order_number)) die("خطأ: لم يتم تحديد رقم الطلب.");

// الحصول على الـ order_id
$stmt_info = $pdo->prepare("SELECT id FROM orders WHERE order_number = ?");
$stmt_info->execute([$order_number]);
$order_id = $stmt_info->fetchColumn();

// 1. معالجة تحديث ثيم المنيو
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_theme'])) {
    $theme = $_POST['menu_theme'];
    $stmt = $pdo->prepare("UPDATE theme_profiles SET theme_color = ? WHERE order_id = ?");
    $stmt->execute([$theme, $order_id]);
    $message = "تم تحديث ثيم المنيو!";
}

// 2. معالجة رفع صورة منتج فردي
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['item_image'])) {
    $item_id = $_POST['item_id'];
    $upload_dir = 'uploads/menu/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file_ext = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
    $new_filename = 'item_' . $item_id . '_' . time() . '.' . $file_ext;
    
    if (move_uploaded_file($_FILES['item_image']['tmp_name'], $upload_dir . $new_filename)) {
        $stmt = $pdo->prepare("UPDATE menu_items SET image_path = ? WHERE id = ?");
        $stmt->execute([$upload_dir . $new_filename, $item_id]);
    }
}

// جلب البيانات
$stmt_theme = $pdo->prepare("SELECT theme_color FROM theme_profiles WHERE order_id = ?");
$stmt_theme->execute([$order_id]);
$current_theme = $stmt_theme->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM menu_items WHERE order_id = ? ORDER BY category");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>تحرير المنيو | <?= htmlspecialchars($order_number) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card { background: var(--card-bg); padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .item-card { display: flex; align-items: center; background: #f8f9fa; padding: 10px; margin-bottom: 8px; border-radius: 8px; }
        .item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; margin-left: 15px; }
        .compact-form { display: flex; gap: 10px; align-items: center; }
        .btn-sm { padding: 5px 15px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container" style="max-width: 900px; margin: 20px auto; padding: 0 20px;">
        
        <div class="card">
            <h3><i class="fas fa-palette"></i> تخصيص ثيم المنيو</h3>
            <form method="POST" class="compact-form">
                <select name="menu_theme" class="form-control" style="max-width: 300px;">
                    <option value="theme-red" <?= $current_theme == 'theme-red' ? 'selected' : '' ?>>أحمر ناري</option>
                    <option value="theme-silver" <?= $current_theme == 'theme-silver' ? 'selected' : '' ?>>فضي لامع</option>
                    <option value="theme-dark-gold" <?= $current_theme == 'theme-dark-gold' ? 'selected' : '' ?>>ذهبي أنيق</option>
                    <option value="theme-black-luxury" <?= $current_theme == 'theme-black-luxury' ? 'selected' : '' ?>>أسود فخم</option>
                </select>
                <button type="submit" name="update_theme" class="btn btn-primary">حفظ الثيم</button>
                <a href="upload_menu.php?order=<?= urlencode($order_number) ?>" class="btn" style="background:#6f42c1; color:white;">رفع ملف Mnu جديد</a>
            </form>
        </div>

        <div class="card">
            <h3>منتجات المنيو (<?= count($items) ?>)</h3>
            <?php foreach ($items as $item): ?>
            <div class="item-card">
                <?php if($item['image_path']): ?>
                    <img src="<?= htmlspecialchars($item['image_path']) ?>" class="item-img">
                <?php else: ?>
                    <div class="item-img" style="background:#ddd; display:flex; align-items:center; justify-content:center;">?</div>
                <?php endif; ?>
                
                <div style="flex-grow: 1;">
                    <h4 style="margin:0;"><?= htmlspecialchars($item['item_name']) ?></h4>
                    <small><?= htmlspecialchars($item['category']) ?> | <?= htmlspecialchars($item['price']) ?></small>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="compact-form">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    <input type="file" name="item_image" accept="image/*" required style="width: 150px;">
                    <button type="submit" class="btn btn-primary btn-sm">تغيير</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        
        <a href="index.php" class="btn"><i class="fas fa-home"></i> العودة للداشبورد</a>
    </div>
</body>
</html>