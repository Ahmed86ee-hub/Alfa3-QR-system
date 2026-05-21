<?php
// menu_view.php
require 'com_config.php';

$order_number = isset($_GET['order']) ? trim($_GET['order']) : '';

if (empty($order_number)) {
    die("<div style='text-align:center; padding:50px;'><h2>رابط القائمة غير صالح</h2></div>");
}

try {
    // 1. جلب معلومات المطعم/العميل وروابط التواصل
    $stmt_order = $pdo->prepare("
        SELECT o.client_name, t.page_content, t.theme_color 
        FROM orders o
        LEFT JOIN theme_profiles t ON o.id = t.order_id
        WHERE o.order_number = ?
    ");
    $stmt_order->execute([$order_number]);
    $orderData = $stmt_order->fetch();

    if (!$orderData) {
        die("<div style='text-align:center; padding:50px;'><h2>المنيو غير موجود</h2></div>");
    }

    $client_name = $orderData['client_name'];
    $theme_color = $orderData['theme_color'] ?? 'theme-silver-shiny';
    $content = json_decode($orderData['page_content'], true);
    $links = $content['links'] ?? [];

    // 2. جلب المنتجات وتجميعها
    $stmt = $pdo->prepare("
        SELECT m.* FROM menu_items m 
        JOIN orders o ON m.order_id = o.id 
        WHERE o.order_number = ? 
        ORDER BY m.category ASC
    ");
    $stmt->execute([$order_number]);
    $items = $stmt->fetchAll();

    $menu = [];
    foreach ($items as $item) {
        $menu[$item['category']][] = $item;
    }

} catch (\PDOException $e) {
    die("خطأ في الاتصال: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>منيو | <?= htmlspecialchars($client_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #607d8b; 
            --primary-rgb: 96, 125, 139;
            --bg: #f8f9fa; 
            --text: #333; 
            --card-bg: #ffffff; 
        }
        
        /* 🎨 الثيمات المحدثة للتوافق مع التعديلات الأخيرة */
        .theme-silver-shiny { --primary: #607d8b; --primary-rgb: 96, 125, 139; --bg: #f5f7fa; }
        .theme-glass-iphone { --primary: #007aff; --primary-rgb: 0, 122, 255; --bg: #f2f2f7; }
        .theme-gold-radiant { --primary: #b8860b; --primary-rgb: 184, 134, 11; --bg: #fffdf5; }
        .theme-black-luxury { --primary: #d4af37; --primary-rgb: 212, 175, 55; --bg: #1a1a1a; --text: #f1f1f1; --card-bg: #2d2d2d; }
        
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg); color: var(--text); margin: 0; padding-bottom: 80px; transition: 0.3s; }
        
        /* ترويسة المنيو */
        .header { background: var(--card-bg); padding: 30px 20px 20px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-bottom: 3px solid var(--primary); }
        .header h1 { margin: 0; font-size: 1.8rem; color: var(--text); }
        .header p { margin: 5px 0 0; opacity: 0.7; font-size: 1rem; }
        
        /* شريط التحكم بحجم البطاقات */
        .view-controls {
            display: flex; justify-content: center; gap: 15px; margin-top: 15px;
        }
        .view-btn {
            background: rgba(0,0,0,0.05); border: none; padding: 8px 15px; border-radius: 20px;
            color: var(--text); cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 5px; font-size: 0.9rem;
        }
        .view-btn.active { background: var(--primary); color: #fff; box-shadow: 0 4px 10px rgba(var(--primary-rgb), 0.3); }
        .theme-black-luxury .view-btn { background: rgba(255,255,255,0.1); }
        .theme-black-luxury .view-btn.active { background: var(--primary); color: #000; }

        .menu-container { max-width: 1000px; margin: 20px auto; padding: 0 15px; }
        
        /* عناوين الفئات */
        .category-title { 
            color: var(--text); margin-top: 30px; margin-bottom: 20px; font-size: 1.4rem;
            display: flex; align-items: center; gap: 10px;
        }
        .category-title::before { content: ''; display: block; width: 8px; height: 25px; background-color: var(--primary); border-radius: 4px; }

        /* ======== شبكة البطاقات الديناميكية ======== */
        .menu-grid { display: grid; gap: 15px; transition: 0.3s; }
        
        /* وضع الشبكة الصغيرة (الافتراضي) */
        .menu-grid.view-small { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
        
        /* وضع الشبكة الكبيرة */
        .menu-grid.view-large { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); }
        
        /* وضع القائمة الطولية (List View) */
        .menu-grid.view-list { grid-template-columns: 1fr; }
        .menu-grid.view-list .menu-card { flex-direction: row; align-items: center; height: 120px; }
        .menu-grid.view-list .img-wrapper { width: 120px; height: 100%; border-left: 1px solid rgba(0,0,0,0.05); }
        .menu-grid.view-list .card-body { text-align: right; justify-content: center; }
        .menu-grid.view-list .price { margin-top: 5px; }
        
        /* تصميم البطاقة المصغرة المشترك */
        .menu-card { 
            background: var(--card-bg); display: flex; flex-direction: column;
            border-radius: 15px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.04); 
            transition: transform 0.2s, box-shadow 0.2s; border: 1px solid rgba(0,0,0,0.05); cursor: pointer;
        }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .theme-black-luxury .menu-card { border-color: rgba(255,255,255,0.05); }
        
        /* صورة المنتج */
        .menu-card .img-wrapper {
            width: 100%; height: 130px; background-color: rgba(0,0,0,0.03);
            display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 2rem; overflow: hidden;
        }
        .menu-card img { width: 100%; height: 100%; object-fit: cover; }
        
        /* تفاصيل المنتج */
        .card-body { padding: 12px; display: flex; flex-direction: column; flex-grow: 1; text-align: center; }
        .card-body h3 { margin: 0 0 5px; font-size: 1rem; color: var(--text); }
        .card-body p { 
            margin: 0; opacity: 0.7; font-size: 0.8rem; line-height: 1.4; flex-grow: 1;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .price { 
            font-weight: bold; margin-top: 10px; font-size: 0.95rem; 
            background: var(--primary); padding: 5px 10px; border-radius: 8px; display: inline-block; color: #fff;
        }
        .theme-black-luxury .price { color: #000; }
        
        .footer { text-align: center; margin-top: 50px; opacity: 0.5; font-size: 0.8rem; }

        /* ======== الزر العائم النابض (Pulsating FAB) ======== */
        .fab-container { position: fixed; bottom: 25px; right: 25px; z-index: 999; }
        .fab-btn {
            width: 65px; height: 65px; border-radius: 50%; background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 2rem;
            border: none; cursor: pointer; transition: 0.3s;
            animation: pulse-animation 2s infinite;
        }
        .theme-black-luxury .fab-btn { color: #000; }
        .fab-btn:hover { animation: none; transform: scale(1.1); box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
        .fab-btn:active { transform: scale(0.95); }

        @keyframes pulse-animation {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(var(--primary-rgb), 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 15px rgba(var(--primary-rgb), 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(var(--primary-rgb), 0); }
        }

        /* ======== النوافذ المنبثقة (Modals) ======== */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            align-items: center; justify-content: center; padding: 20px;
        }
        .modal-content {
            background-color: var(--card-bg); border-radius: 20px; padding: 25px;
            max-width: 400px; width: 100%; text-align: center; position: relative;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .close-btn { position: absolute; top: 15px; left: 20px; font-size: 1.8rem; color: #999; cursor: pointer; transition: 0.2s; }
        .close-btn:hover { color: var(--text); }
        
        .modal-img-container {
            width: 100%; height: 250px; border-radius: 15px; overflow: hidden; margin-bottom: 20px;
            background-color: rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: center;
        }
        .modal-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .modal-img-container i { font-size: 4rem; color: #ccc; }
        
        .modal-content h3 { font-size: 1.4rem; color: var(--text); margin-bottom: 10px; }
        .modal-content p { color: var(--text); opacity: 0.8; line-height: 1.6; font-size: 1rem; margin-bottom: 20px; }
        .modal-price { font-size: 1.3rem; color: #fff; background: var(--primary); padding: 10px 25px; border-radius: 30px; display: inline-block; font-weight: bold; }
        .theme-black-luxury .modal-price { color: #000; }

        /* شبكة التواصل */
        .contact-links { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
        .contact-btn {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            padding: 14px; border-radius: 12px; text-decoration: none; color: #fff; font-weight: bold;
            background: #333; transition: 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.1); font-size: 1.05rem;
        }
        .contact-btn:hover { opacity: 0.9; transform: translateY(-2px); }
        .contact-btn:active { transform: translateY(0); }
        .btn-phone { background: #34c759; }
        .btn-whatsapp { background: #25D366; }
        .btn-facebook { background: #1877F2; }
        .btn-instagram { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
        .btn-website { background: #555; }
    </style>
</head>
<body class="<?= htmlspecialchars($theme_color) ?>">

    <div class="header">
        <h1><?= htmlspecialchars($client_name) ?></h1>
        <p>قائمة الطعام الرقمية</p>
        
        <!-- أزرار التحكم بحجم وشكل عرض البطاقات -->
        <div class="view-controls">
            <button class="view-btn active" onclick="changeView('small', this)"><i class="fas fa-th"></i> </button>
            <button class="view-btn" onclick="changeView('large', this)"><i class="fas fa-th-large"></i> </button>
            <button class="view-btn" onclick="changeView('list', this)"><i class="fas fa-list"></i> </button>
        </div>
    </div>

    <div class="menu-container">
        <?php if (empty($menu)): ?>
            <div style="text-align:center; padding:50px; opacity: 0.5;">
                <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom:10px;"></i>
                <p>لم يتم إضافة أي منتجات حتى الآن.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($menu as $category => $items): ?>
            <h2 class="category-title"><?= htmlspecialchars($category) ?></h2>
            <div class="menu-grid view-small">
                <?php foreach ($items as $item): 
                    $img = !empty($item['image_path']) ? htmlspecialchars($item['image_path'], ENT_QUOTES) : '';
                    $name = htmlspecialchars($item['item_name'], ENT_QUOTES);
                    $notes = htmlspecialchars($item['notes'], ENT_QUOTES);
                    $price = htmlspecialchars($item['price'], ENT_QUOTES);
                ?>
                    <div class="menu-card" onclick="openItemModal('<?= $img ?>', '<?= $name ?>', '<?= $notes ?>', '<?= $price ?>')">
                        <div class="img-wrapper">
                            <?php if($img): ?>
                                <img src="<?= $img ?>" alt="<?= $name ?>" loading="lazy">
                            <?php else: ?>
                                <i class="fas fa-utensils"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3><?= $name ?></h3>
                            <?php if($notes): ?>
                                <p title="<?= $notes ?>"><?= $notes ?></p>
                            <?php endif; ?>
                            <div><span class="price"><?= $price ?></span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="footer">
        تم التصميم بواسطة Alpha3 Smart Solutions
    </div>

    <!-- ======== الزر العائم النابض للتواصل ======== -->
    <div class="fab-container">
        <button class="fab-btn" onclick="openContactModal()">
            <i class="fas fa-comment-dots"></i>
        </button>
    </div>

    <!-- ======== نافذة تفاصيل المنتج ======== -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('itemModal')">&times;</span>
            <div class="modal-img-container" id="modalImgContainer"></div>
            <h3 id="modalTitle"></h3>
            <p id="modalDesc"></p>
            <div class="modal-price" id="modalPrice"></div>
        </div>
    </div>

    <!-- ======== نافذة معلومات التواصل ======== -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('contactModal')">&times;</span>
            <h3 style="margin-top:0;">تواصل معنا</h3>
            <p>لتقديم طلبك أو للاستفسار، اختر إحدى الطرق التالية:</p>
            
            <div class="contact-links">
                <!-- زر الاتصال المباشر -->
                <?php if(!empty($links['phone'])): ?>
                    <a href="tel:<?= htmlspecialchars(str_replace(['+', ' '], '', $links['phone'])) ?>" class="contact-btn btn-phone">
                        <i class="fas fa-phone-alt"></i> اتصال مباشر
                    </a>
                <?php endif; ?>

                <?php if(!empty($links['whatsapp'])): ?>
                    <a href="https://wa.me/<?= htmlspecialchars(str_replace(['+', ' '], '', $links['whatsapp'])) ?>" class="contact-btn btn-whatsapp" target="_blank">
                        <i class="fab fa-whatsapp"></i> تواصل عبر الواتساب
                    </a>
                <?php endif; ?>

                <?php if(!empty($links['facebook'])): ?>
                    <a href="<?= htmlspecialchars($links['facebook']) ?>&app=browser" class="contact-btn btn-facebook" target="_blank">
                        <i class="fab fa-facebook"></i> صفحتنا على فيسبوك
                    </a>
                <?php endif; ?>

                <?php if(!empty($links['instagram'])): ?>
                    <a href="<?= htmlspecialchars($links['instagram']) ?>" class="contact-btn btn-instagram" target="_blank">
                        <i class="fab fa-instagram"></i> تابعنا على انستغرام
                    </a>
                <?php endif; ?>

                <?php if(!empty($links['website'])): ?>
                    <a href="<?= htmlspecialchars($links['website']) ?>" class="contact-btn btn-website" target="_blank">
                        <i class="fas fa-globe"></i> زيارة الموقع الإلكتروني
                    </a>
                <?php endif; ?>

                <?php if(empty($links['phone']) && empty($links['whatsapp']) && empty($links['facebook']) && empty($links['instagram']) && empty($links['website'])): ?>
                    <p style="color: red;">لم يتم إضافة بيانات تواصل لهذا المطعم.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ======== السكريبت ======== -->
    <script>
        // دالة تغيير طريقة عرض البطاقات
        function changeView(viewType, btnElement) {
            // تحديث الأزرار
            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');

            // تحديث جميع شبكات المنيو
            document.querySelectorAll('.menu-grid').forEach(grid => {
                grid.className = 'menu-grid'; // مسح الكلاسات القديمة
                grid.classList.add('view-' + viewType);
            });
        }

        // دوال النوافذ المنبثقة
        function openItemModal(imgSrc, name, notes, price) {
            document.getElementById('modalTitle').innerText = name;
            document.getElementById('modalDesc').innerText = notes ? notes : 'لا يوجد تفاصيل إضافية لهذا المنتج.';
            document.getElementById('modalPrice').innerText = price;
            
            const imgContainer = document.getElementById('modalImgContainer');
            if (imgSrc) {
                imgContainer.innerHTML = `<img src="${imgSrc}" alt="${name}">`;
            } else {
                imgContainer.innerHTML = `<i class="fas fa-utensils"></i>`;
            }
            document.getElementById('itemModal').style.display = 'flex';
        }

        function openContactModal() {
            document.getElementById('contactModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // إغلاق النافذة عند النقر في المساحة الفارغة
        window.onclick = function(event) {
            let itemModal = document.getElementById('itemModal');
            let contactModal = document.getElementById('contactModal');
            if (event.target == itemModal) { closeModal('itemModal'); }
            if (event.target == contactModal) { closeModal('contactModal'); }
        }
    </script>
</body>
</html>