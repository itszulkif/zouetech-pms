<?php
// db_connect.php
require_once __DIR__ . '/includes/app_env.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'dosti_pms';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS');
        $this->password = ($this->password === false) ? '' : $this->password;

        // In production, require explicit secrets and do not allow local defaults.
        if (defined('APP_IS_PRODUCTION') && APP_IS_PRODUCTION) {
            $missing = [];
            foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $env_key) {
                $value = getenv($env_key);
                if ($value === false || $value === '') {
                    $missing[] = $env_key;
                }
            }
            if (!empty($missing)) {
                $safeMessage = 'Missing required database environment variables: ' . implode(', ', $missing);
                if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $safeMessage]);
                    exit;
                }
                die($safeMessage);
            }
        }
    }

    public function connect() {
        $this->conn = null;

        try {
            // Using 127.0.0.1 can be more reliable on Windows than localhost
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);

            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }

            // Set charset to utf8mb4
            $this->conn->set_charset("utf8mb4");

            $db_timezone = getenv('DB_TIMEZONE') ?: '+05:00';
            $tz_escaped = $this->conn->real_escape_string($db_timezone);
            if (!$this->conn->query("SET time_zone = '{$tz_escaped}'")) {
                throw new Exception("Failed to set DB session timezone: " . $this->conn->error);
            }

        } catch (Exception $e) {
            // Log error to file (logs/ if present and writable, else project root)
            $log_message = "[" . date("Y-m-d H:i:s") . "] DB Connection Error: " . $e->getMessage() . "\n";
            $log_dir = __DIR__ . '/logs';
            $log_file = is_dir($log_dir) && is_writable($log_dir) ? $log_dir . '/debug_db.log' : __DIR__ . '/debug_db.log';
            @file_put_contents($log_file, $log_message, FILE_APPEND);
            
            // Return JSON error if this is an API call
            if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Database Connection Error: " . $e->getMessage()]);
                exit;
            } else {
                die("Database Error: " . $e->getMessage());
            }
        }

        return $this->conn;
    }
}
?>
