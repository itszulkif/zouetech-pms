<?php
// api/reject_task_assignment.php - Super Admin: reject pending assignment; task remains unassigned (status Pending)
require_once '../includes/auth_middleware.php';
require_once '../db_connect.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    echo json_encode(['success' => false, 'message' => 'Super Admin only']);
    exit;
}

$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
if ($task_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
    exit;
}

$db = (new Database())->connect();

$task = $db->query("SELECT id FROM tasks WHERE id = $task_id AND status = 'Pending Approval'")->fetch_assoc();
if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task not found or not pending approval']);
    $db->close();
    exit;
}

$db->query("DELETE FROM task_assignment_pending WHERE task_id = $task_id");
$db->query("UPDATE tasks SET status = 'Pending', assigned_to = NULL WHERE id = $task_id");
echo json_encode(['success' => true, 'message' => 'Assignment rejected. Task is unassigned.']);
$db->close();
