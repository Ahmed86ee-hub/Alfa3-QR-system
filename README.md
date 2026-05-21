# 📖 دليل تطوير نظام Alfa3 QR System

## 🎯 نظرة عامة على المشروع

نظام إدارة رموز QR احترافي مع واجهة قوية للتحكم بالطلبات وإنشاء صفحات شخصية وقوائم رقمية.

---

## 📁 هيكل المشروع

```
Alfa3-QR-system/
├── bootstrap.php              # ملف التحميل الموحد (START HERE)
├── config/
│   ├── Database.php          # إدارة قاعدة البيانات
│   ├── Security.php          # فئات الأمان
│   ├── Logger.php            # نظام التسجيل
│   └── Env.php               # إدارة متغيرات البيئة
├── .env.example              # مثال متغيرات البيئة
├── .gitignore               # ملفات Git المتجاهلة
├── auth.php                 # المصادقة والتفويض
├── com_config.php           # اتصال قاعدة البيانات (استخدم bootstrap.php)
├── client_request.php       # صفحة إضافة طلب
├── edit_request.php         # تعديل الطلبات
├── index.php                # لوحة التحكم
├── profile.php              # صفحة البروفايل العامة
├── qr_generator.php         # مولد رموز QR
├── menu.php                 # قالب القائمة
├── menu_edit.php            # تحرير القوائم
├── upload_menu.php          # رفع ملفات القائمة
├── header.php               # رأس الصفحة
├── style.css                # أنماط CSS
├── products_style.css       # أنماط البروفايل والقائمة
├── script.js                # سكريبتات JavaScript
├── logs/                    # ملفات السجلات
└── uploads/                 # ملفات مرفوعة
```

---

## 🚀 البدء السريع

### 1️⃣ التثبيت والإعداد

```bash
# 1. استنساخ المشروع
git clone https://github.com/Ahmed86ee-hub/Alfa3-QR-system.git
cd Alfa3-QR-system

# 2. إنشاء ملف .env
cp .env.example .env

# 3. تعديل .env بـ بيانات قاعدة البيانات الفعلية
nano .env

# 4. إنشاء المجلدات المطلوبة
mkdir -p logs
mkdir -p uploads/menus
mkdir -p uploads/menu
```

### 2️⃣ إنشاء قاعدة البيانات

```sql
CREATE DATABASE IF NOT EXISTS a3itssco_alfa3 CHARACTER SET utf8mb4;
USE a3itssco_alfa3;

-- جدول الطلبات
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    client_email VARCHAR(255),
    page_type ENUM('profile', 'menu') DEFAULT 'profile',
    order_status ENUM('pending', 'completed', 'archived') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول ملفات الثيمات
CREATE TABLE theme_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    theme_color VARCHAR(50),
    icon_style VARCHAR(50),
    page_content LONGTEXT,
    menu_file_path VARCHAR(255),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- جدول عناصر القائمة
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    category VARCHAR(100),
    item_name VARCHAR(255),
    price VARCHAR(50),
    notes TEXT,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

### 3️⃣ اختبار التثبيت

```php
<?php
require_once 'bootstrap.php';

echo "✅ النظام جاهز!";
echo "📊 العاملون: " . $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
?>
```

---

## 💻 المتطلبات التقنية

| المتطلب | الإصدار الأدنى | الملاحظات |
|---------|----------------|----------|
| PHP | 7.4+ | يفضل 8.0+ |
| MySQL | 5.7+ | يفضل 8.0+ |
| OpenSSL | مفعّل | لـ HTTPS |
| GD Library | مفعّلة | لمعالجة الصور |

---

## 🔧 استخدام الفئات الأساسية

### فئة قاعدة البيانات
```php
require 'bootstrap.php';

// الطريقة الأولى - الـ Singleton
$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$db->execute($stmt, [1]);

// الطريقة الثانية - المتغير العام
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([1]);
```

### فئة الأمان
```php
// تنظيف مدخلات
$email = Security::sanitizeInput($_POST['email'], 'email');
$phone = Security::sanitizeInput($_POST['phone'], 'phone');

// التحقق من صحة
if (!Security::validateInput($email, 'email')) {
    echo "بريد إلكتروني غير صحيح";
}

// معالجة الملفات
$upload = Security::handleFileUpload($_FILES['image'], 'uploads/');
if (!$upload['success']) {
    echo $upload['error'];
}

// توليد CSRF token
$token = Security::generateCSRFToken();

// التحقق من CSRF
if (!Security::validateCSRFToken($_POST['csrf_token'])) {
    die('فشل التحقق من الأمان');
}
```

### فئة السجلات
```php
$logger->info('عملية ناجحة', ['data' => 'value']);
$logger->warning('تحذير أمني');
$logger->error('خطأ حرج');

// جلب السجلات
$logs = $logger->getLogs('2024-01-15', 'ERROR');
```

### فئة المصادقة
```php
// التحقق من تسجيل الدخول
Auth::requireLogin();

// الحصول على المستخدم الحالي
$user = Auth::getCurrentUser();

// تسجيل دخول
Auth::login(1, 'admin', 'admin@example.com');

// تسجيل خروج
Auth::logout();
```

---

## 📝 أمثلة الاستخدام

### مثال 1: إضافة طلب جديد
```php
<?php
require 'bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // التحقق من CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'])) {
        die('فشل التحقق من الأمان');
    }
    
    // تنظيف المدخلات
    $name = Security::sanitizeInput($_POST['name']);
    $email = Security::sanitizeInput($_POST['email'], 'email');
    
    // إدراج البيانات
    $stmt = $pdo->prepare("INSERT INTO orders (order_number, client_name, client_email) VALUES (?, ?, ?)");
    $order_num = 'QR-' . uniqid();
    
    try {
        $stmt->execute([$order_num, $name, $email]);
        $logger->info('تم إضافة طلب جديد', ['order_num' => $order_num]);
        json_response(['success' => true, 'order' => $order_num]);
    } catch (PDOException $e) {
        $logger->error('خطأ في إضافة الطلب: ' . $e->getMessage());
        json_response(['error' => 'حدث خطأ'], 500);
    }
}
?>
```

### مثال 2: تحميل ملف بآمان
```php
<?php
require 'bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $upload = Security::handleFileUpload(
        $_FILES['menu'],
        'uploads/menus/',
        'menu'
    );
    
    if ($upload['success']) {
        echo "تم التحميل: " . $upload['path'];
    } else {
        echo "خطأ: " . $upload['error'];
    }
}
?>
```

---

## 🐛 استكشاف الأخطاء

### تفعيل وضع Debug
```php
// في .env
DEBUG_MODE=true

// سيظهر الأخطاء مباشرة في الشاشة
```

### فحص السجلات
```bash
tail -f logs/2024-01-15.log
grep "ERROR" logs/2024-01-15.log
```

---

## 🔒 اختبارات الأمان المقترحة

1. **اختبار SQL Injection**
   - محاولة إدراج `' OR '1'='1`

2. **اختبار XSS**
   - محاولة إدراج `<script>alert('xss')</script>`

3. **اختبار CSRF**
   - إرسال نموذج بدون token

4. **اختبار File Upload**
   - رفع ملف PHP بامتداد .jpg

5. **اختبار Session Hijacking**
   - محاولة تغيير الـ session ID

---

## 📚 معايير الترميز

### قواعد PHP
- استخدم `prepare()` دائماً للاستعلامات
- لا تستخدم المتغيرات العام ($GLOBALS)
- استخدم type hints في الدوال

### قواعس CSS
- استخدم متغيرات CSS (CSS Variables)
- اتبع نظام BEM للتسمية
- اختبر على أجهزة مختلفة

### قواعس JavaScript
- استخدم `const` و `let` بدلاً من `var`
- تجنب JavaScript العام

---

## 🤝 المساهمة

نرحب بالمساهمات! يرجى:
1. فتح Issue للمناقشة
2. عمل Fork للمشروع
3. إرسال Pull Request

---

## 📄 الترخيص

هذا المشروع تحت ترخيص MIT

---

**آخر تحديث:** 2024-01-15  
**الإصدار:** 1.0.0  
**الحالة:** 🟢 آمن وجاهز للإنتاج
