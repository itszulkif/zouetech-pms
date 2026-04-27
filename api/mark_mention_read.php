<?php
// api/mark_mention_read.php
require_once '../db_connect.php';
require_once '../includes/auth_middleware.php';
header('Content-Type: application/json');

check_login();

$mention_id = isset($_POST['mention_id']) ? intval($_POST['mention_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($mention_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid mention ID']);
    exit;
}

$database = new Database();
$db = $database->connect();

$stmt = $db->prepare("UPDATE mention_notifications SET is_read = 1 WHERE id = ? AND mentioned_user_id = ?");
$stmt->bind_param("ii", $mention_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Mention marked as read']);
}
else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$db->close();
?>
