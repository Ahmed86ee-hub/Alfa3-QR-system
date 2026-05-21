<?php
// index.php

// 1. استدعاء ملف الحماية أولاً لمنع الدخول غير المصرح به
require 'auth.php';

// 2. الاتصال بقاعدة البيانات
require 'com_config.php';

try {
    // إحصائيات النظام السريعة للداشبورد
    $pending_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
    $completed_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'completed'")->fetchColumn();
    $archived_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'archived'")->fetchColumn();
    
    // عدد الطلبات الكلي
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    // جلب جميع الطلبات لعرضها في الجدول
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $all_orders = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم | QR System Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* تنسيقات الداشبورد الأساسية */
        body { margin: 0; background-color: var(--bg-color, #f4f7f6); font-family: 'Segoe UI', Tahoma, sans-serif;}
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .action-bar h2 { margin: 0; color: var(--text-color, #333); font-size: 1.8rem; }
        .btn-add { background-color: #d4af37; color: #1a1a1a; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(212, 175, 55, 0.3); transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;}
        .btn-add:hover { background-color: #e5c158; transform: translateY(-2px); }

        /* شبكة الإحصائيات */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background-color: var(--card-bg, #fff); border: 1px solid var(--border-color, #eaeaea); border-radius: 12px; padding: 25px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .stat-card h3 { margin: 0 0 10px 0; color: var(--text-color, #555); font-size: 1.1rem; }
        .stat-value { font-size: 2.8rem; font-weight: bold; color: #d4af37; }

        /* جدول البيانات الاحترافي */
        .table-wrapper { background-color: var(--card-bg, #fff); border-radius: 12px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow-x: auto; border: 1px solid var(--border-color, #eaeaea); }
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 900px; }
        .data-table th, .data-table td { padding: 16px; text-align: right; border-bottom: 1px solid var(--border-color, #eaeaea); color: var(--text-color, #333); vertical-align: middle;}
        .data-table th { background-color: rgba(0,0,0,0.02); font-weight: bold; color: #555; text-transform: uppercase; font-size: 0.9rem;}
        .data-table tbody tr:hover { background-color: rgba(0,0,0,0.01); }
        
        /* الشارات (Badges) */
        .badge { padding: 6px 12px; border-radius: 6px; font-family: monospace; font-weight: bold; font-size: 0.9rem;}
        .badge-id { background-color: #f8f9fa; color: #333; border: 1px solid #ddd; }
        .badge-menu { background-color: #ffe8cc; color: #e67e22; border: 1px solid #ffd8a8; }
        .badge-profile { background-color: #cce5ff; color: #0056b3; border: 1px solid #b8daff; }
        
        .status-completed { color: #28a745; font-weight: bold; background: rgba(40,167,69,0.1); padding: 5px 10px; border-radius: 5px;}
        .status-archived { color: #6c757d; font-weight: bold; background: rgba(108,117,125,0.1); padding: 5px 10px; border-radius: 5px;}
        .status-pending { color: #ffc107; font-weight: bold; background: rgba(255,193,7,0.1); padding: 5px 10px; border-radius: 5px;}
        
        /* أزرار الإجراءات */
        .btn-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-sm { padding: 8px 12px; font-size: 0.85rem; border-radius: 6px; text-decoration: none; font-weight: bold; text-align: center; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-view { background-color: #17a2b8; color: white; border: 1px solid #138496;}
        .btn-qr { background-color: #28a745; color: white; border: 1px solid #218838;}
        .btn-edit { background-color: #ffc107; color: #1a1a1a; border: 1px solid #e0a800;}
        .btn-menu-edit { background-color: #6f42c1; color: white; border: 1px solid #59339d; } /* لون مخصص لزر المنيو */
        
        .btn-sm:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }

        /* توافق الوضع الليلي */
        [data-theme="dark"] .badge-id { background-color: #333; color: #fff; border-color: #555; }
        [data-theme="dark"] .data-table th { background-color: rgba(255,255,255,0.05); color: #ddd; }
        [data-theme="dark"] .data-table tbody tr:hover { background-color: rgba(255,255,255,0.02); }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <main class="container">
        
        <div class="action-bar">
            <h2><i class="fas fa-chart-line" style="color: #d4af37;"></i> نظرة عامة على النظام</h2>
            <a href="client_request.php" class="btn-add"><i class="fas fa-plus"></i> إضافة طلب جديد</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-hourglass-half" style="color: #ffc107;"></i> قيد التنفيذ</h3>
                <div class="stat-value"><?= $pending_count ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-check-circle" style="color: #28a745;"></i> المشاريع المكتملة</h3>
                <div class="stat-value"><?= $completed_count ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-archive" style="color: #6c757d;"></i> أرشيف النظام</h3>
                <div class="stat-value"><?= $archived_count ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-layer-group" style="color: #17a2b8;"></i> إجمالي الطلبات</h3>
                <div class="stat-value"><?= $total_orders ?></div>
            </div>
        </div>

        <div class="table-wrapper">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--text-color, #333);"><i class="fas fa-list-alt"></i> قائمة المشاريع والطلبات السجلية</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>نوع المشروع</th>
                        <th>اسم العميل / الماركة</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($all_orders) > 0): ?>
                        <?php foreach ($all_orders as $order): 
                            // التحقق من نوع الصفحة
                            $is_menu = ($order['page_type'] == 'menu');
                            
                            // التوجيه الديناميكي للزر "عرض"
                            $target_page = $is_menu ? 'menu_view.php' : 'profile.php';
                            
                            // تنسيق أيقونة وشارة النوع
                            $type_icon = $is_menu ? '<i class="fas fa-utensils"></i> منيو' : '<i class="fas fa-id-badge"></i> بروفايل';
                            $type_badge = $is_menu ? 'badge-menu' : 'badge-profile';
                        ?>
                            <tr>
                                <td><span class="badge badge-id"><?= htmlspecialchars($order['order_number']) ?></span></td>
                                <td><span class="badge <?= $type_badge ?>"><?= $type_icon ?></span></td>
                                <td style="font-weight: bold; font-size: 1.05rem;"><?= htmlspecialchars($order['client_name']) ?></td>
                                <td>
                                    <?php 
                                        if($order['order_status'] == 'completed') echo '<span class="status-completed"><i class="fas fa-check"></i> مكتمل</span>';
                                        elseif($order['order_status'] == 'archived') echo '<span class="status-archived"><i class="fas fa-archive"></i> مؤرشف</span>';
                                        else echo '<span class="status-pending"><i class="fas fa-spinner fa-spin"></i> قيد التنفيذ</span>';
                                    ?>
                                </td>
                                <td dir="ltr" style="font-size: 0.9rem; color: gray;"><i class="far fa-clock"></i> <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= $target_page ?>?order=<?= $order['order_number'] ?>" class="btn-sm btn-view" target="_blank"><i class="fas fa-eye"></i> عرض</a>
                                        
                                        <a href="qr_generator.php?order=<?= $order['order_number'] ?>" class="btn-sm btn-qr" target="_blank"><i class="fas fa-qrcode"></i> الرمز</a>
                                        
                                        <a href="edit_request.php?order=<?= $order['order_number'] ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> إعدادات</a>

                                        <?php if($is_menu): ?>
                                            <a href="menu_edit.php?order=<?= $order['order_number'] ?>" class="btn-sm btn-menu-edit"><i class="fas fa-list-ul"></i> تحرير المنيو</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 50px; color: gray;">
                                <i class="fas fa-folder-open" style="font-size: 4rem; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                                لا توجد طلبات مسجلة في النظام حتى الآن.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="script.js"></script>
    <script>
        // سكريبت تفعيل زر الثيم من الهيدر
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('themeToggle');
            if (themeBtn) {
                themeBtn.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                });
            }
            
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        });
    </script>
</body>
</html>