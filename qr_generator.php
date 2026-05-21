<?php
// qr_generator.php
require 'auth.php'; // حماية الصفحة
require 'com_config.php';

$order_number = isset($_GET['order']) ? trim($_GET['order']) : '';

if (empty($order_number)) {
    die("<h2 style='text-align:center; margin-top:50px;'>لم يتم توفير رقم الطلب.</h2>");
}

// 1. جلب بيانات الطلب لمعرفة الرابط الصحيح وجلب صورة الشعار
try {
    $stmt = $pdo->prepare("
        SELECT o.page_type, t.page_content 
        FROM orders o 
        LEFT JOIN theme_profiles t ON o.id = t.order_id 
        WHERE o.order_number = ?
    ");
    $stmt->execute([$order_number]);
    $orderData = $stmt->fetch();

    if (!$orderData) {
        die("<h2 style='text-align:center; margin-top:50px;'>الطلب غير موجود.</h2>");
    }

    $page_type = $orderData['page_type'] ?? 'profile';
    $target_page = ($page_type == 'menu') ? 'menu_view.php' : 'profile.php';

    // بناء الرابط الفعلي للصفحة
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $base_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $final_url = $protocol . $domain . $base_dir . "/" . $target_page . "?order=" . urlencode($order_number);

    // استخراج الصورة المرفوعة لاستخدامها كشعار في الـ QR
    $content = json_decode($orderData['page_content'], true);
    $logo_url = !empty($content['profile_image']) ? $protocol . $domain . $base_dir . "/" . $content['profile_image'] : '';

} catch (\PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المولد الاحترافي | <?= htmlspecialchars($order_number) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="text/javascript" src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
    <style>
        .builder-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; max-width: 1000px; margin: 30px auto; }
        @media (max-width: 768px) { .builder-layout { grid-template-columns: 1fr; } }
        
        .settings-panel, .preview-panel {
            background: var(--card-bg, #fff); padding: 25px; border-radius: 15px;
            border: 1px solid var(--border-color, #eee); box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .form-group { margin-bottom: 20px; text-align: right; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; }
        .color-picker { width: 100%; height: 50px; padding: 0; border: none; cursor: pointer; border-radius: 8px; }
        
        #canvas-container { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        .btn-download {
            background: #28a745; color: #fff; padding: 15px 30px; font-size: 1.1rem; width: 100%;
            text-align: center; border-radius: 10px; text-decoration: none; display: inline-block;
            cursor: pointer; border: none; font-weight: bold; transition: 0.3s;
        }
        .btn-download:hover { background: #218838; transform: translateY(-2px); }
        .section-title { margin-bottom: 25px; color: var(--text-color, #333); display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <main class="container">
        <div class="builder-layout">
            
            <div class="settings-panel">
                <h3 class="section-title"><i class="fas fa-cog"></i> إعدادات الرمز المتقدمة</h3>
                
                <div class="form-group">
                    <label>الرابط (URL)</label>
                    <input type="text" id="qrData" class="form-control" value="<?= htmlspecialchars($final_url) ?>" readonly style="background: #f8f9fa;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>لون الرمز</label>
                        <input type="color" id="qrColor" class="color-picker" value="#000000">
                    </div>
                    <div class="form-group">
                        <label>لون الخلفية</label>
                        <input type="color" id="qrBgColor" class="color-picker" value="#ffffff">
                    </div>
                </div>

                <div class="form-group">
                    <label>شكل النقاط</label>
                    <select id="dotsStyle" class="form-control">
                        <option value="square">مربعات</option>
                        <option value="dots">دوائر</option>
                        <option value="rounded">انسيابي</option>
                        <option value="extra-rounded">دائري كامل</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>شكل الزوايا (الإطار)</label>
                    <select id="cornersStyle" class="form-control">
                        <option value="square">مربع</option>
                        <option value="dot">دائري</option>
                        <option value="extra-rounded">دائري ممتد</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>رابط الشعار (اللوجو)</label>
                    <input type="text" id="logoUrl" class="form-control" value="<?= htmlspecialchars($logo_url) ?>" placeholder="ضع رابط الصورة هنا...">
                    <small style="color: #666;">يمكنك وضع رابط صورة (.png) هنا لتغيير اللوجو في منتصف الـ QR.</small>
                </div>
            </div>

            <div class="preview-panel">
                <h3 class="section-title"><i class="fas fa-eye"></i> المعاينة الحية</h3>
                <div id="canvas-container"></div>
                <button id="btn-download" class="btn-download"><i class="fas fa-download"></i> تحميل الرمز (PNG)</button>
            </div>

        </div>
    </main>

    <script>
        // تهيئة محرك QR
        const qrCode = new QRCodeStyling({
            width: 300,
            height: 300,
            type: "canvas",
            data: document.getElementById('qrData').value,
            image: document.getElementById('logoUrl').value,
            dotsOptions: { color: "#000000", type: "square" },
            backgroundOptions: { color: "#ffffff" },
            cornersSquareOptions: { type: "square", color: "#000000" },
            imageOptions: { crossOrigin: "anonymous", margin: 5, imageSize: 0.4 }
        });

        qrCode.append(document.getElementById("canvas-container"));

        function updateQR() {
            qrCode.update({
                data: document.getElementById('qrData').value,
                image: document.getElementById('logoUrl').value,
                dotsOptions: {
                    color: document.getElementById('qrColor').value,
                    type: document.getElementById('dotsStyle').value
                },
                backgroundOptions: { color: document.getElementById('qrBgColor').value },
                cornersSquareOptions: {
                    type: document.getElementById('cornersStyle').value,
                    color: document.getElementById('qrColor').value
                }
            });
        }

        // الاستماع لكل التغيرات
        ['input', 'change'].forEach(evt => {
            document.getElementById('qrColor').addEventListener(evt, updateQR);
            document.getElementById('qrBgColor').addEventListener(evt, updateQR);
            document.getElementById('dotsStyle').addEventListener(evt, updateQR);
            document.getElementById('cornersStyle').addEventListener(evt, updateQR);
            document.getElementById('logoUrl').addEventListener(evt, updateQR);
        });

        document.getElementById('btn-download').addEventListener('click', () => {
            qrCode.download({ name: "QR_<?= htmlspecialchars($order_number) ?>", extension: "png" });
        });
    </script>
</body>
</html>