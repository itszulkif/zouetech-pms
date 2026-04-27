<?php
// api/approve_task_assignment.php - Super Admin: approve pending assignment; task becomes officially assigned
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

$task = $db->query("SELECT id, project_id, title FROM tasks WHERE id = $task_id AND status = 'Pending Approval'")->fetch_assoc();
if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task not found or not pending approval']);
    $db->close();
    exit;
}

// Get requested_by (creator) before deleting pending requests.
$pending_row = $db->query("SELECT requested_by FROM task_assignment_pending WHERE task_id = $task_id LIMIT 1")->fetch_assoc();
$requested_by = $pending_row ? (int) $pending_row['requested_by'] : 0;
$project_id = (int) ($task['project_id'] ?? 0);

$db->begin_transaction();
try {
    $first_uid = null;
    $sel = $db->query("SELECT user_id FROM task_assignment_pending WHERE task_id = $task_id ORDER BY user_id ASC");
    while ($row = $sel->fetch_assoc()) {
        $uid = (int) $row['user_id'];
        if ($first_uid === null) $first_uid = $uid;
        $db->query("INSERT IGNORE INTO task_assignments (task_id, user_id) VALUES ($task_id, $uid)");
    }

    if ($project_id) {
        $db->query("UPDATE tasks SET status = 'Pending', assigned_to = " . ($first_uid ?? 'NULL') . " WHERE id = $task_id");
    } else {
        $db->query("UPDATE tasks SET status = 'Pending' WHERE id = $task_id");
    }

    // Log assignment as creator for audit/performance history.
    if ($requested_by > 0) {
        $log_stmt = $db->prepare("INSERT INTO performance_logs (user_id, task_id, score_change, reason) VALUES (?, ?, 0, ?)");
        $sel2 = $db->query("SELECT user_id FROM task_assignments WHERE task_id = $task_id");
        while ($r = $sel2->fetch_assoc()) {
            $uid = (int) $r['user_id'];
            if ($project_id) {
                $reason = "assigned task '" . $task['title'] . "' to user ID $uid (Project ID: $project_id)";
            } else {
                $reason = "assigned Direct Task '" . $task['title'] . "' to user ID $uid";
            }
            $log_stmt->bind_param("iis", $requested_by, $task_id, $reason);
            $log_stmt->execute();
        }
        $log_stmt->close();
    }

    $db->query("DELETE FROM task_assignment_pending WHERE task_id = $task_id");
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Assignment approved. Task is now assigned.']);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$db->close();
