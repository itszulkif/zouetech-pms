<?php
require_once '../includes/auth_middleware.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
if ($task_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid task id.']);
    exit;
}

$database = new Database();
$db = $database->connect();

$stmt = $db->prepare("SELECT id, project_id, title, description, status, assigned_to, start_date, end_date, due_date, specific_time, priority FROM tasks WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();
$stmt->close();

if (!$task) {
    echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
    $db->close();
    exit;
}

$assigned_user_ids = [];
$assignRes = $db->query("SELECT user_id FROM task_assignments WHERE task_id = " . (int)$task_id . " ORDER BY user_id ASC");
if ($assignRes) {
    while ($row = $assignRes->fetch_assoc()) {
        $assigned_user_ids[] = (int)$row['user_id'];
    }
}
if (empty($assigned_user_ids) && !empty($task['assigned_to'])) {
    $assigned_user_ids[] = (int)$task['assigned_to'];
}

$task['assigned_user_ids'] = $assigned_user_ids;
echo json_encode(['status' => 'success', 'data' => $task]);
$db->close();
?>
