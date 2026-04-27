<?php
// api/get_pending_approval_tasks.php - Super Admin: list tasks in Pending Approval with requested assignees
require_once '../includes/auth_middleware.php';
require_once '../db_connect.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    echo json_encode(['success' => false, 'message' => 'Super Admin only']);
    exit;
}

$db = (new Database())->connect();

$tr = @$db->query("SHOW TABLES LIKE 'task_assignment_pending'");
if (!$tr || $tr->num_rows === 0) {
    echo json_encode(['success' => true, 'tasks' => []]);
    $db->close();
    exit;
}

$tasks = [];
$sql = "SELECT t.id, t.title, t.description, t.status, t.project_id, t.priority, t.start_date, t.end_date, t.due_date,
        (SELECT full_name FROM users WHERE id = (SELECT requested_by FROM task_assignment_pending WHERE task_id = t.id LIMIT 1)) AS requested_by_name,
        (SELECT GROUP_CONCAT(u.full_name ORDER BY u.full_name) FROM task_assignment_pending tap JOIN users u ON u.id = tap.user_id WHERE tap.task_id = t.id) AS requested_assignees
        FROM tasks t
        WHERE t.status = 'Pending Approval'
        ORDER BY t.created_at DESC";
$res = $db->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $tasks[] = $row;
    }
}

echo json_encode(['success' => true, 'tasks' => $tasks]);
$db->close();
