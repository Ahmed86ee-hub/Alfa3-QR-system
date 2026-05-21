<?php
// upload_menu.php
require 'auth.php'; // حماية الصفحة
require 'com_config.php';

$message = '';
$messageType = '';
$order_number = $_GET['order'] ?? '';

if (empty($order_number)) die("خطأ: لم يتم تحديد رقم الطلب.");

// الحصول على الـ order_id
$stmt_id = $pdo->prepare("SELECT id FROM orders WHERE order_number = ?");
$stmt_id->execute([$order_number]);
$order_id = $stmt_id->fetchColumn();

// 1. معالجة رفع ملف القائمة (PDF/Image)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['menu_file'])) {
    $upload_dir = 'uploads/menus/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $file = $_FILES['menu_file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (in_array($file_ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
        $destination = $upload_dir . 'menu_' . $order_number . '_' . time() . '.' . $file_ext;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $stmt = $pdo->prepare("UPDATE theme_profiles SET menu_file_path = ? WHERE order_id = ?");
            $stmt->execute([$destination, $order_id]);
            $message = "تم رفع الملف بنجاح!"; $messageType = "success";
        }
    }
}

// 2. معالجة إضافة منتج فردي
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $category = trim($_POST['category'] ?? 'عام');
    $item_name = trim($_POST['item_name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($item_name)) {
        $stmt = $pdo->prepare("INSERT INTO menu_items (order_id, category, item_name, price, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $category, $item_name, $price, $notes]);
        $message = "تم إضافة المنتج بنجاح!"; $messageType = "success";
    }
}

// 3. جلب المنتجات الحالية
$stmt_items = $pdo->prepare("SELECT * FROM menu_items WHERE order_id = ? ORDER BY category, item_name");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>إدارة المنيو | <?= htmlspecialchars($order_number) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .card { background: var(--card-bg, #fff); padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid var(--border-color, #eee); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: right; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <main class="container">
        
        <div class="card">
            <h3><i class="fas fa-file-pdf"></i> رفع ملف القائمة (جاهز)</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="file" name="menu_file" class="form-control" required accept=".pdf,.jpg,.jpeg,.png">
                    <button type="submit" class="btn btn-primary" style="margin-top:10px;">رفع الملف</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> إضافة منتج فردي للمنيو</h3>
            <form action="" method="POST">
                <input type="hidden" name="add_item" value="1">
                <div class="form-grid">
                    <div class="form-group"><label>الفئة</label><input type="text" name="category" class="form-control" placeholder="مثل: مقبلات" required></div>
                    <div class="form-group"><label>اسم المنتج</label><input type="text" name="item_name" class="form-control" placeholder="مثل: بيتزا" required></div>
                    <div class="form-group"><label>السعر</label><input type="text" name="price" class="form-control" placeholder="1000"></div>
                </div>
                <div class="form-group"><label>ملاحظات/وصف</label><textarea name="notes" class="form-control"></textarea></div>
                <button type="submit" class="btn btn-primary" style="background:#28a745; border:none; padding:10px 20px;">حفظ المنتج</button>
            </form>
        </div>

        <div class="card">
            <h3>المنتجات المضافة حالياً</h3>
            <table class="data-table">
                <thead>
                    <tr><th>الفئة</th><th>الاسم</th><th>السعر</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['category']) ?></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>