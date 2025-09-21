<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('private');
    session_start();
}

// Kiểm tra timeout (15 phút = 900 giây) - nhưng không redirect cho AJAX requests
$timeout = 900;
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    if (!$isAjax) {
        // Only redirect for regular page requests, not AJAX
        session_unset();
        session_destroy();
        header("Location: /");
        exit;
    }
    // For AJAX requests, just unset the admin status but don't redirect
    unset($_SESSION['is_admin']);
}

// Cập nhật thời gian hoạt động
$_SESSION['last_activity'] = time();

// Try MySQL first, fallback to SQLite for demo/local development
$conn = null;
$db_available = false;
$db_type = 'mysql'; // or 'sqlite'

// MySQL configuration (for production)
$DB_HOST = "217.21.74.1"; // Thay bằng hostname từ hosting (ví dụ: mysqlXX.000webhost.com)
$DB_USER = "u324425198_khanhtg";
$DB_PASS = "WT/GiaK@123";
$DB_NAME = "u324425198_Photos";

// Try MySQL connection
try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn && !$conn->connect_error) {
        $db_available = true;
        $db_type = 'mysql';
    } else {
        throw new Exception("MySQL connection failed: " . ($conn ? $conn->connect_error : "Connection object is null"));
    }
} catch (Exception $e) {
    // MySQL not available - try SQLite as fallback
    try {
        $sqlite_file = __DIR__ . '/photos.db';
        $conn = new PDO('sqlite:' . $sqlite_file);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create tables if they don't exist
        $tables = [
            "CREATE TABLE IF NOT EXISTS photos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL,
                uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                uploader TEXT,
                ip_address TEXT,
                album_id INTEGER
            )",
            "CREATE TABLE IF NOT EXISTS albums (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                password TEXT,
                cover_image TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS photo_feedback (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                photo_filename TEXT NOT NULL,
                rating INTEGER NOT NULL,
                comment TEXT,
                user_ip TEXT,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE INDEX IF NOT EXISTS idx_photo_filename ON photo_feedback(photo_filename)",
            "CREATE INDEX IF NOT EXISTS idx_created_at ON photo_feedback(created_at)"
        ];

        foreach ($tables as $sql) {
            $conn->exec($sql);
        }

        $db_available = true;
        $db_type = 'sqlite';
        error_log("Using SQLite database as fallback - MySQL connection failed: " . $e->getMessage());
    } catch (Exception $sqlite_error) {
        // Both MySQL and SQLite failed
        $conn = null;
        $db_available = false;
        $db_type = 'none';
        error_log("Both MySQL and SQLite failed. MySQL: " . $e->getMessage() . ", SQLite: " . $sqlite_error->getMessage() . " - Running in demo mode without database.");
    }
}

// Global variable to check if database is available
define('DB_AVAILABLE', $db_available);
define('DB_TYPE', $db_type);

// Database wrapper class to handle both MySQL and SQLite
class Database {
    private $connection;
    private $type;

    public function __construct($conn, $type) {
        $this->connection = $conn;
        $this->type = $type;
    }

    public function query($sql) {
        if ($this->type === 'sqlite') {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            return $stmt;
        } else {
            return $this->connection->query($sql);
        }
    }

    public function prepare($sql) {
        if ($this->type === 'sqlite') {
            return new SQLiteStatementWrapper($this->connection->prepare($sql));
        } else {
            return $this->connection->prepare($sql);
        }
    }

    public function real_escape_string($string) {
        if ($this->type === 'sqlite') {
            return SQLite3::escapeString($string);
        } else {
            return $this->connection->real_escape_string($string);
        }
    }

    public function insert_id() {
        if ($this->type === 'sqlite') {
            return $this->connection->lastInsertId();
        } else {
            return $this->connection->insert_id;
        }
    }

    public function close() {
        if ($this->type === 'sqlite') {
            $this->connection = null;
        } else {
            $this->connection->close();
        }
    }
}

// Wrapper for SQLite PDOStatement to mimic mysqli_stmt behavior
class SQLiteStatementWrapper {
    private $stmt;

    public function __construct($pdoStmt) {
        $this->stmt = $pdoStmt;
    }

    public function bind_param($types, ...$params) {
        // PDO doesn't need type hints like mysqli, just bind the values
        foreach ($params as $index => $param) {
            $this->stmt->bindValue($index + 1, $param);
        }
        return true;
    }

    public function execute() {
        return $this->stmt->execute();
    }

    public function get_result() {
        // Return a wrapper that mimics mysqli_result
        return new SQLiteResultWrapper($this->stmt);
    }

    public function close() {
        $this->stmt->closeCursor();
    }

    public function affected_rows() {
        return $this->stmt->rowCount();
    }
}

// Wrapper for SQLite results to mimic mysqli_result
class SQLiteResultWrapper {
    private $stmt;

    public function __construct($pdoStmt) {
        $this->stmt = $pdoStmt;
    }

    public function fetch_assoc() {
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function num_rows() {
        // For SELECT queries, we can't get num_rows easily with PDO
        // This is a limitation, but we'll handle it in the calling code
        return $this->stmt->rowCount();
    }

    public function fetchColumn() {
        $row = $this->stmt->fetch(PDO::FETCH_NUM);
        return $row ? $row[0] : false;
    }
}

// Create database wrapper instance
$db = $db_available ? new Database($conn, $db_type) : null;

// File-based storage for demo mode
class FileStorage {
    private $dataDir;

    public function __construct() {
        $this->dataDir = __DIR__ . '/data';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
    }

    public function saveAlbums($albums) {
        file_put_contents($this->dataDir . '/albums.json', json_encode($albums));
    }

    public function loadAlbums() {
        $file = $this->dataDir . '/albums.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }

    public function saveFeedback($feedback) {
        file_put_contents($this->dataDir . '/feedback.json', json_encode($feedback));
    }

    public function loadFeedback() {
        $file = $this->dataDir . '/feedback.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }
}

// Create file storage instance for demo mode
$fileStorage = new FileStorage();

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
function csrf_token() {
    return $_SESSION['csrf_token'];
}
?>