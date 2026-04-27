<?php
// api/get_all_mentions.php - Returns all mention notifications (read + unread) for history
require_once '../db_connect.php';
require_once '../includes/auth_middleware.php';
header('Content-Type: application/json');

check_login();

$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->connect();

$sql = "SELECT n.id, n.task_id, n.is_read, t.project_id, t.title as task_title, u.full_name as sender_name, m.message, n.created_at 
        FROM mention_notifications n
        JOIN tasks t ON n.task_id = t.id
        JOIN users u ON n.sender_id = u.id
        JOIN task_messages m ON n.message_id = m.id
        WHERE n.mentioned_user_id = ?
        ORDER BY n.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$mentions = [];
while ($row = $result->fetch_assoc()) {
    $mentions[] = $row;
}

$unread_count = 0;
foreach ($mentions as $m) {
    if (!$m['is_read']) $unread_count++;
}

echo json_encode(['success' => true, 'mentions' => $mentions, 'unread_count' => $unread_count]);

$stmt->close();
$db->close();
?>
