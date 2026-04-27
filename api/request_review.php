<?php
// api/request_review.php
require_once '../db_connect.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($task_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
    exit;
}

$database = new Database();
$db = $database->connect();

$db->begin_transaction();

try {
    // 1. Update task review status
    $sql = "UPDATE tasks SET review_status = 'Under Review' WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->close();

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Review requested']);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$db->close();
?>
