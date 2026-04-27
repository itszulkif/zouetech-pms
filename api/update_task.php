<?php
require_once '../includes/auth_middleware.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
$description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
$priority = isset($_POST['priority']) ? trim((string)$_POST['priority']) : 'Medium';
$start_date = !empty($_POST['start_date']) ? trim((string)$_POST['start_date']) : null;
$end_date = !empty($_POST['end_date']) ? trim((string)$_POST['end_date']) : null;
$due_date = !empty($_POST['due_date']) ? trim((string)$_POST['due_date']) : null;
$specific_time = !empty($_POST['specific_time']) ? trim((string)$_POST['specific_time']) : null;

$assigned_to_ids = [];
if (!empty($_POST['assigned_to'])) {
    if (is_array($_POST['assigned_to'])) {
        $assigned_to_ids = array_values(array_unique(array_filter(array_map('intval', $_POST['assigned_to']))));
    } else {
        $single = (int)$_POST['assigned_to'];
        if ($single > 0) $assigned_to_ids = [$single];
    }
}

if ($task_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid task id.']);
    exit;
}
if ($title === '') {
    echo json_encode(['status' => 'error', 'message' => 'Title is required.']);
    exit;
}
if (!in_array($priority, ['Low', 'Medium', 'High', 'Urgent'], true)) {
    $priority = 'Medium';
}

$database = new Database();
$db = $database->connect();

$taskRow = $db->query("SELECT id, project_id, status FROM tasks WHERE id = " . (int)$task_id)->fetch_assoc();
if (!$taskRow) {
    echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
    $db->close();
    exit;
}

$is_standalone = ($taskRow['project_id'] === null || (int)$taskRow['project_id'] === 0);
if ($is_standalone) {
    if (empty($start_date) || empty($end_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Start Date and End Date are required for Direct Tasks.']);
        $db->close();
        exit;
    }
    if (empty($assigned_to_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select at least one assignee for Direct Tasks.']);
        $db->close();
        exit;
    }
    if ($start_date > $end_date) {
        echo json_encode(['status' => 'error', 'message' => 'End Date cannot be earlier than Start Date.']);
        $db->close();
        exit;
    }
    if (empty($due_date)) {
        $due_date = $end_date;
    }
} else {
    if (empty($due_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Due Date is required for Project Tasks.']);
        $db->close();
        exit;
    }
}

$db->begin_transaction();
try {
    $primary_assignee = !empty($assigned_to_ids) ? (int)$assigned_to_ids[0] : null;
    $assignedSql = ($primary_assignee === null) ? "NULL" : "?";
    $stmt = $db->prepare("UPDATE tasks
        SET title = ?, description = ?, assigned_to = $assignedSql, start_date = ?, end_date = ?, specific_time = ?, due_date = ?, priority = ?
        WHERE id = ?");
    if ($primary_assignee === null) {
        $stmt->bind_param(
            "sssssssi",
            $title,
            $description,
            $start_date,
            $end_date,
            $specific_time,
            $due_date,
            $priority,
            $task_id
        );
    } else {
        $stmt->bind_param(
            "ssisssssi",
            $title,
            $description,
            $primary_assignee,
            $start_date,
            $end_date,
            $specific_time,
            $due_date,
            $priority,
            $task_id
        );
    }
    $stmt->execute();
    $stmt->close();

    $db->query("DELETE FROM task_assignments WHERE task_id = " . (int)$task_id);
    if (!empty($assigned_to_ids)) {
        $assignStmt = $db->prepare("INSERT IGNORE INTO task_assignments (task_id, user_id) VALUES (?, ?)");
        foreach ($assigned_to_ids as $uid) {
            $uid = (int)$uid;
            $assignStmt->bind_param("ii", $task_id, $uid);
            $assignStmt->execute();
        }
        $assignStmt->close();
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => 'Task updated successfully.']);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$db->close();
?>
