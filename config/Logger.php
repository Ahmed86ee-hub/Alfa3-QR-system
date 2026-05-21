<?php
/**
 * فئة تسجيل الأخطاء والعمليات
 * Logging System for Errors and Operations
 */

class Logger {
    private static $instance = null;
    private $logDir = 'logs/';
    private $maxFileSize = 10485760; // 10MB

    private function __construct() {
        $this->ensureLogDirectory();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function ensureLogDirectory() {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }

    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }

    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }

    public function debug($message, $context = []) {
        if (getenv('DEBUG_MODE')) {
            $this->log('DEBUG', $message, $context);
        }
    }

    private function log($level, $message, $context = []) {
        try {
            $timestamp = date('Y-m-d H:i:s');
            $logFile = $this->logDir . $level . '_' . date('Y-m-d') . '.log';

            // فحص حجم الملف
            if (file_exists($logFile) && filesize($logFile) > $this->maxFileSize) {
                rename($logFile, $logFile . '.' . time());
            }

            $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
            $logMessage = "[$timestamp] $level: $message$contextStr" . PHP_EOL;

            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log('خطأ في نظام التسجيل: ' . $e->getMessage());
        }
    }
}
?>
