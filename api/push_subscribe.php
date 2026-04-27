<?php
/**
 * PWA Push Notification - Save subscription
 * Stores the push subscription for the current user.
 */
require_once '../db_connect.php';
require_once '../includes/auth_middleware.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
check_login();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['endpoint']) || empty($input['keys']['p256dh']) || empty($input['keys']['auth'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid subscription data']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$endpoint = $input['endpoint'];
$p256dh = $input['keys']['p256dh'];
$auth = $input['keys']['auth'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$database = new Database();
$db = $database->connect();
$allow_runtime_migrations = defined('APP_ALLOW_RUNTIME_MIGRATIONS') && APP_ALLOW_RUNTIME_MIGRATIONS;

// Ensure table exists (use endpoint_hash for unique key - endpoints can exceed index limit)
if ($allow_runtime_migrations) {
    $db->query("CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        endpoint VARCHAR(500) NOT NULL,
        endpoint_hash VARCHAR(64) NOT NULL,
        p256dh_key VARCHAR(255) NOT NULL,
        auth_key VARCHAR(255) NOT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_endpoint (endpoint_hash),
        KEY idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$table_check = $db->query("SHOW TABLES LIKE 'push_subscriptions'");
if (!$table_check || $table_check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Push subscription table is missing. Run SQL migrations first.']);
    exit;
}

$endpoint_hash = hash('sha256', $endpoint);

// Delete existing subscription for this endpoint, then insert (avoids unique key issues)
$del = $db->prepare("DELETE FROM push_subscriptions WHERE endpoint_hash = ?");
$del->bind_param("s", $endpoint_hash);
$del->execute();
$del->close();

$stmt = $db->prepare("INSERT INTO push_subscriptions (user_id, endpoint, endpoint_hash, p256dh_key, auth_key, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssss", $user_id, $endpoint, $endpoint_hash, $p256dh, $auth, $user_agent);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Push notifications enabled']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save subscription']);
}
$stmt->close();
$db->close();
