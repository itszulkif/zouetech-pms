<?php
// api/get_pending_mentions.php
require_once '../db_connect.php';
require_once '../includes/auth_middleware.php';
header('Content-Type: application/json');

check_login();

$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->connect();

$sql = "SELECT n.id, n.task_id, t.project_id, t.title as task_title, u.full_name as sender_name, m.message, n.created_at 
        FROM mention_notifications n
        JOIN tasks t ON n.task_id = t.id
        JOIN users u ON n.sender_id = u.id
        JOIN task_messages m ON n.message_id = m.id
        WHERE n.mentioned_user_id = ? AND n.is_read = 0
        ORDER BY n.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$mentions = [];
while ($row = $result->fetch_assoc()) {
    $mentions[] = $row;
}

echo json_encode(['success' => true, 'mentions' => $mentions]);

$stmt->close();
$db->close();
?>
