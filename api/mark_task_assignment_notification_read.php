<?php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Department Head', 'Team Member']);
header('Content-Type: application/json');

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->connect();

$tableCheck = $db->query("SHOW TABLES LIKE 'task_assignment_notifications'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    echo json_encode(['success' => true, 'updated' => 0, 'unread_count' => 0]);
    $db->close();
    exit;
}

$mark_all = isset($_POST['mark_all']) && $_POST['mark_all'] === '1';
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

if ($mark_all) {
    $stmt = $db->prepare("UPDATE task_assignment_notifications SET is_read = 1 WHERE recipient_user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
} else {
    if ($notification_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid notification_id']);
        $db->close();
        exit;
    }
    $stmt = $db->prepare("UPDATE task_assignment_notifications SET is_read = 1 WHERE id = ? AND recipient_user_id = ? AND is_read = 0");
    $stmt->bind_param("ii", $notification_id, $user_id);
}

$stmt->execute();
$updated = $stmt->affected_rows > 0 ? $stmt->affected_rows : 0;
$stmt->close();

$unread_count = 0;
$stmtUnread = $db->prepare("SELECT COUNT(*) AS unread_count FROM task_assignment_notifications WHERE recipient_user_id = ? AND is_read = 0");
$stmtUnread->bind_param("i", $user_id);
$stmtUnread->execute();
$row = $stmtUnread->get_result()->fetch_assoc();
$unread_count = (int)($row['unread_count'] ?? 0);
$stmtUnread->close();

$db->close();

echo json_encode([
    'success' => true,
    'updated' => $updated,
    'unread_count' => $unread_count
]);
?>
