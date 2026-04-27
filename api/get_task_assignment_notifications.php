<?php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Department Head', 'Team Member']);
header('Content-Type: application/json');

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
if ($limit <= 0) {
    $limit = 20;
}
if ($limit > 50) {
    $limit = 50;
}

$database = new Database();
$db = $database->connect();

// Fail-soft compatibility: return empty payload if schema has not been created yet.
$tableCheck = $db->query("SHOW TABLES LIKE 'task_assignment_notifications'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    echo json_encode([
        'success' => true,
        'notifications' => [],
        'unread_count' => 0,
        'latest_id' => 0
    ]);
    $db->close();
    exit;
}

$unread_count = 0;
$stmtUnread = $db->prepare("SELECT COUNT(*) AS unread_count FROM task_assignment_notifications WHERE recipient_user_id = ? AND is_read = 0");
$stmtUnread->bind_param("i", $user_id);
$stmtUnread->execute();
$unreadRow = $stmtUnread->get_result()->fetch_assoc();
$unread_count = (int)($unreadRow['unread_count'] ?? 0);
$stmtUnread->close();

$notifications = [];
$latest_id = 0;

$stmt = $db->prepare("
    SELECT n.id, n.task_id, n.sender_user_id, n.notification_type, n.title_snapshot, n.message, n.is_read, n.created_at,
           u.full_name AS sender_name,
           t.project_id
    FROM task_assignment_notifications n
    LEFT JOIN users u ON u.id = n.sender_user_id
    LEFT JOIN tasks t ON t.id = n.task_id
    WHERE n.recipient_user_id = ?
    ORDER BY n.id DESC
    LIMIT ?
");
$stmt->bind_param("ii", $user_id, $limit);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['task_id'] = (int)$row['task_id'];
    $row['sender_user_id'] = (int)$row['sender_user_id'];
    $row['is_read'] = (int)$row['is_read'];
    $row['project_id'] = $row['project_id'] !== null ? (int)$row['project_id'] : null;
    $notifications[] = $row;
    if ($row['id'] > $latest_id) {
        $latest_id = $row['id'];
    }
}
$stmt->close();
$db->close();

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unread_count,
    'latest_id' => $latest_id
]);
?>
