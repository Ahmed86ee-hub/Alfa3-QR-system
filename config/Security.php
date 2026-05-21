<?php
/**
 * فئة الأمان الشاملة - Security Class
 * حماية من جميع المتجهات الأمنية
 */

class Security {
    private static $rateLimits = [];

    /**
     * توليد CSRF Token
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * التحقق من CSRF Token
     */
    public static function validateCSRFToken($token) {
        // التحقق من وجود الـ token
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        // التحقق من تطابق الـ token
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }

        // التحقق من انتهاء صلاحية الـ token (ساعة واحدة)
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            return false;
        }

        return true;
    }

    /**
     * تنظيف المدخلات (Sanitization)
     */
    public static function sanitizeInput($value, $type = 'string') {
        $value = trim($value);
        
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
            
            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);
            
            case 'phone':
                return preg_replace('/[^0-9+\-\s]/', '', $value);
            
            case 'number':
                return preg_replace('/[^0-9.]/', '', $value);
            
            case 'alpha':
                return preg_replace('/[^a-zA-Z]/', '', $value);
            
            case 'alphanumeric':
                return preg_replace('/[^a-zA-Z0-9]/', '', $value);
            
            default:
                // إزالة الوسوم والأحرف الخاصة
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * التحقق من صحة المدخلات (Validation)
     */
    public static function validateInput($value, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            
            case 'phone':
                return preg_match('/^(\+\d{1,3}[- ]?)?\d{7,}$/', $value);
            
            case 'number':
                return is_numeric($value);
            
            case 'arabic':
                return preg_match('/[\u0600-\u06FF]/', $value);
            
            default:
                return true;
        }
    }

    /**
     * معالجة آمنة لتحميل الملفات
     */
    public static function handleFileUpload($file, $directory, $prefix = '') {
        $maxSize = Env::get('MAX_UPLOAD_SIZE', 5242880); // 5MB
        $allowedExts = explode(',', Env::get('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,webp,pdf'));
        $allowedExts = array_map('trim', $allowedExts);

        // التحقق من الأخطاء
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => self::getUploadErrorMessage($file['error'])
            ];
        }

        // التحقق من الحجم
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'error' => 'حجم الملف أكبر من المسموح'
            ];
        }

        // التحقق من نوع الملف (الامتداد)
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            return [
                'success' => false,
                'error' => 'نوع الملف غير مسموح'
            ];
        }

        // التحقق من نوع الملف الفعلي (MIME Type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf'
        ];

        if (!isset($allowedMimes[$ext]) || $allowedMimes[$ext] !== $mimeType) {
            return [
                'success' => false,
                'error' => 'نوع الملف لا يتطابق مع الامتداد'
            ];
        }

        // إنشاء اسم عشوائي للملف
        $newName = $prefix . '_' . uniqid() . '.' . $ext;
        $uploadPath = $directory . $newName;

        // التأكد من وجود المجلد
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // تحميل الملف
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // تعيين الصلاحيات الآمنة
            chmod($uploadPath, 0644);
            
            return [
                'success' => true,
                'path' => $uploadPath,
                'name' => $newName
            ];
        }

        return [
            'success' => false,
            'error' => 'فشل تحميل الملف'
        ];
    }

    /**
     * الحصول على رسالة خطأ التحميل
     */
    private static function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من المسموح (ini_max_file_size)',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من المسموح (form MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'لم يتم تحميل الملف بالكامل',
            UPLOAD_ERR_NO_FILE => 'لم يتم اختيار ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'المجلد المؤقت غير موجود',
            UPLOAD_ERR_CANT_WRITE => 'لا يمكن كتابة الملف',
            UPLOAD_ERR_EXTENSION => 'امتداد PHP منع التحميل'
        ];
        
        return $errors[$errorCode] ?? 'خطأ غير معروف في التحميل';
    }

    /**
     * تشفير كلمة المرور (Hashing)
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * التحقق من كلمة المرور
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * تحديد معدل الطلبات (Rate Limiting)
     */
    public static function enforceRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
        $key = md5($_SERVER['REMOTE_ADDR'] . $action);
        $file = sys_get_temp_dir() . '/ratelimit_' . $key;

        $attempts = 0;
        $firstAttempt = time();

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $attempts = $data['attempts'] ?? 0;
            $firstAttempt = $data['time'] ?? time();

            // إعادة تعيين إذا انقضى الإطار الزمني
            if (time() - $firstAttempt > $timeWindow) {
                $attempts = 0;
                $firstAttempt = time();
            }
        }

        $attempts++;

        // حفظ المحاولة
        file_put_contents($file, json_encode([
            'attempts' => $attempts,
            'time' => $firstAttempt
        ]));

        // التحقق من تجاوز الحد الأقصى
        if ($attempts > $maxAttempts) {
            http_response_code(429);
            die('عدد محاولات مفرط. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تعيين رؤوس الأمان
     */
    public static function setSecurityHeaders() {
        // منع تخمين نوع المحتوى
        header('X-Content-Type-Options: nosniff');

        // منع الضغط داخل Iframes
        header('X-Frame-Options: DENY');

        // حماية من XSS
        header('X-XSS-Protection: 1; mode=block');

        // CSP - سياسة أمان المحتوى
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com");

        // HSTS - إجبار HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // إزالة معلومات الخادم
        header_remove('Server');
        header_remove('X-Powered-By');
        header('Server: WebServer');
    }

    /**
     * فحص أمان الجلسة
     */
    public static function validateSessionSecurity() {
        // التحقق من IP
        if (isset($_SESSION['ip']) && $_SESSION['ip'] !== self::getClientIP()) {
            session_destroy();
            die('جلسة غير صالحة');
        }

        // التحقق من User Agent
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            session_destroy();
            die('جلسة غير صالحة');
        }

        // تعيين IP و User Agent
        $_SESSION['ip'] = self::getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * الحصول على IP العميل
     */
    private static function getClientIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
}

?>
