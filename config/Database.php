<?php
/**
 * فئة إدارة قاعدة البيانات الآمنة
 * Database Management Class with Security Best Practices
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $logger;

    private function __construct() {
        $this->initialize();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initialize() {
        try {
            // استخدام متغيرات البيئة بدلاً من الثوابت المباشرة
            $host = getenv('DB_HOST') ?: 'localhost';
            $db = getenv('DB_NAME') ?: '';
            $user = getenv('DB_USER') ?: '';
            $pass = getenv('DB_PASS') ?: '';
            $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

            if (empty($db) || empty($user)) {
                throw new Exception('متغيرات قاعدة البيانات غير محددة بشكل صحيح');
            }

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ];

            $this->pdo = new PDO($dsn, $user, $pass, $options);
            $this->logger = Logger::getInstance();
            
        } catch (PDOException $e) {
            $this->handleDatabaseError($e);
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function prepare($query) {
        try {
            return $this->pdo->prepare($query);
        } catch (PDOException $e) {
            $this->logger->error('خطأ في تحضير الاستعلام: ' . $e->getMessage());
            throw new Exception('حدث خطأ في قاعدة البيانات');
        }
    }

    public function execute($stmt, $params = []) {
        try {
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logger->error('خطأ في تنفيذ الاستعلام: ' . $e->getMessage());
            throw new Exception('حدث خطأ في تنفيذ العملية');
        }
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }

    private function handleDatabaseError(PDOException $e) {
        $this->logger?->error('خطأ قاعدة البيانات: ' . $e->getMessage());
        
        if (getenv('DEBUG_MODE')) {
            die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
        } else {
            die('حدث خطأ في النظام. يرجى المحاولة لاحقاً.');
        }
    }
}
?>
