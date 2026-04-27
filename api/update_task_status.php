<?php
// api/update_task_status.php
require_once '../db_connect.php';
header('Content-Type: application/json');

$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$new_status = isset($_POST['status']) ? $_POST['status'] : '';

if ($task_id <= 0 || empty($new_status)) {
    echo json_encode(['status' => 'error', 'message' => 'Task ID and Status are required.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$database = new Database();
$db = $database->connect();

// Team Members cannot mark tasks as Completed (project or direct tasks).
if ($user_role === 'Team Member' && $new_status === 'Completed') {
    echo json_encode(['status' => 'error', 'message' => 'Team Members cannot mark tasks as Completed.']);
    $db->close();
    exit;
}

// Mention-Only Check: Users who only have access via @mention cannot change status
$task_row = $db->query("SELECT project_id, assigned_to, title, due_date, specific_time, status FROM tasks WHERE id = $task_id")->fetch_assoc();
if (!$task_row) {
    echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
    $db->close();
    exit;
}

// Direct tasks: Team Member assignees can set In Progress or Review only.
$is_direct = ($task_row['project_id'] === null || (int)$task_row['project_id'] === 0);
$old_status = (string)($task_row['status'] ?? '');
if ($is_direct && $user_role === 'Team Member') {
    $is_assignee = $db->query("SELECT 1 FROM task_assignments WHERE task_id = $task_id AND user_id = $user_id")->num_rows > 0;
    if ($is_assignee && !in_array($new_status, ['In Progress', 'Review'])) {
        echo json_encode(['status' => 'error', 'message' => 'As an assignee you can only set status to In Progress or Review.']);
        $db->close();
        exit;
    }
}
if ($task_row) {
    $user_dept = $_SESSION['department_id'] ?? 0;
    $has_full_access = false;
    if ($user_role === 'Super Admin') $has_full_access = true;
    elseif ($task_row['assigned_to'] && (int)$task_row['assigned_to'] === (int)$user_id) $has_full_access = true;
    else {
        $ta = $db->query("SELECT 1 FROM task_assignments WHERE task_id = $task_id AND user_id = $user_id");
        if ($ta && $ta->num_rows > 0) $has_full_access = true;
    }
    if (!$has_full_access && $task_row['project_id']) {
        $spd = $db->prepare("SELECT 1 FROM sub_project_departments spd JOIN users u ON u.department_id = spd.department_id WHERE spd.sub_project_id = ? AND u.id = ?");
        $spd->bind_param("ii", $task_row['project_id'], $user_id);
        $spd->execute();
        if ($spd->get_result()->num_rows > 0) $has_full_access = true;
        $spd->close();
    }
    if (!$has_full_access) {
        $mn = $db->prepare("SELECT 1 FROM mention_notifications WHERE task_id = ? AND mentioned_user_id = ?");
        $mn->bind_param("ii", $task_id, $user_id);
        $mn->execute();
        if ($mn->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'You were mentioned in this task and do not have permission to change the task status.']);
            $mn->close();
            $db->close();
            exit;
        }
        $mn->close();
        // Assigning side fallback access checks.
        if ($user_dept > 0) {
            $creator = $db->prepare("SELECT 1 FROM performance_logs pl JOIN users u ON u.id = pl.user_id WHERE pl.task_id = ? AND (pl.reason LIKE 'assigned Direct Task%' OR pl.reason LIKE 'assigned task%') AND u.department_id = ? LIMIT 1");
            $creator->bind_param("ii", $task_id, $user_dept);
            $creator->execute();
            if ($creator->get_result()->num_rows > 0) {
                $creator->close();
                // Only block for inter-department: if assignees are in a different department. For same-department (own team), allow.
                $is_direct_for_creator = ($task_row['project_id'] === null || (int)$task_row['project_id'] === 0);
                if ($is_direct_for_creator) {
                    $assignee_depts = [];
                    $ad = $db->query("SELECT DISTINCT u.department_id FROM task_assignments ta JOIN users u ON u.id = ta.user_id WHERE ta.task_id = $task_id");
                    if ($ad) {
                        while ($ar = $ad->fetch_assoc()) {
                            $assignee_depts[] = $ar['department_id'] !== null ? (int)$ar['department_id'] : null;
                        }
                    }
                    $all_assignees_same_dept = (count($assignee_depts) > 0 && count(array_unique($assignee_depts)) === 1 && (int)($assignee_depts[0] ?? 0) === (int)$user_dept);
                    if ($all_assignees_same_dept) {
                        $has_full_access = true;
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'You have view-only access. Only the assigned user can change the task status.']);
                        $db->close();
                        exit;
                    }
                } else {
                    // Project task: allow if any assignee is in creator's department (own team)
                    $assignee_depts = [];
                    $ad2 = $db->query("SELECT DISTINCT u.department_id FROM task_assignments ta JOIN users u ON u.id = ta.user_id WHERE ta.task_id = $task_id");
                    if ($ad2) {
                        while ($ar2 = $ad2->fetch_assoc()) {
                            $assignee_depts[] = $ar2['department_id'] !== null ? (int)$ar2['department_id'] : null;
                        }
                    }
                    if ($task_row['assigned_to']) {
                        $u = $db->query("SELECT department_id FROM users WHERE id = " . (int)$task_row['assigned_to'])->fetch_assoc();
                        if ($u && !in_array($u['department_id'] !== null ? (int)$u['department_id'] : null, $assignee_depts)) {
                            $assignee_depts[] = $u['department_id'] !== null ? (int)$u['department_id'] : null;
                        }
                    }
                    $any_assignee_in_my_dept = in_array((int)$user_dept, $assignee_depts);
                    if ($any_assignee_in_my_dept) {
                        $has_full_access = true;
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'You have view-only access. Only the assigned user can change the task status.']);
                        $db->close();
                        exit;
                    }
                }
            } else {
                $creator->close();
            }
        }
        if (!$has_full_access) {
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to change the task status.']);
            $db->close();
            exit;
        }
    }
}

// Status-Lock: Team Members cannot change status of tasks in 'Review', 'Missed', or 'Completed'
// Tasks in 'Pending Approval' cannot be status-updated; only Super Admin approve/reject APIs can change them.
$lock_check = $db->prepare("SELECT status FROM tasks WHERE id = ?");
$lock_check->bind_param("i", $task_id);
$lock_check->execute();
$lock_row = $lock_check->get_result()->fetch_assoc();
$lock_check->close();
if ($lock_row && $lock_row['status'] === 'Pending Approval') {
    echo json_encode(['status' => 'error', 'message' => 'This task is pending Super Admin approval. Use Approve/Reject on the dashboard.']);
    $db->close();
    exit;
}
if ($user_role === 'Team Member') {
    if ($lock_row && in_array($lock_row['status'], ['Review', 'Missed', 'Completed'])) {
        $locked_status = $lock_row['status'];
        echo json_encode(['status' => 'error', 'message' => "This task is '$locked_status'. Only a Super Admin can change its status."]);
        $db->close();
        exit;
    }
}

try {
    // Check if status is changing to Completed
    if ($new_status === 'Completed') {
        require_once '../classes/TaskController.php';
        $controller = new TaskController($db);

        $scoreRecipients = [];
        $taskDueDate = $task_row['due_date'] ?? null;
        $taskSpecificTime = $task_row['specific_time'] ?? null;
        $taskDueDateTime = null;
        if (!empty($taskDueDate)) {
            $dueDateOnly = date('Y-m-d', strtotime((string)$taskDueDate));
            $taskDueDateTime = $dueDateOnly . ' ' . (!empty($taskSpecificTime) ? $taskSpecificTime : '23:59:59');
        }

        // Apply direct-task points to assigned members so they count in performance overview.
        if ($is_direct) {
            $assigneesRes = $db->query("SELECT DISTINCT user_id FROM task_assignments WHERE task_id = " . (int)$task_id);
            if ($assigneesRes) {
                while ($ar = $assigneesRes->fetch_assoc()) {
                    $assigneeId = (int)($ar['user_id'] ?? 0);
                    if ($assigneeId > 0) {
                        $scoreRecipients[] = $assigneeId;
                    }
                }
            }
            if (empty($scoreRecipients) && !empty($task_row['assigned_to'])) {
                $scoreRecipients[] = (int)$task_row['assigned_to'];
            }
        } else {
            $primaryAssignee = (int)($task_row['assigned_to'] ?? 0);
            if ($primaryAssignee > 0) {
                $scoreRecipients[] = $primaryAssignee;
            }
        }

        $scoreRecipients = array_values(array_unique(array_filter($scoreRecipients)));

        // Only award points on actual transition to Completed.
        if ($old_status !== 'Completed' && !empty($scoreRecipients) && !empty($taskDueDateTime)) {
            $impact = $controller->calculateScoreImpact($taskDueDateTime);
            foreach ($scoreRecipients as $recipientId) {
                $controller->updateUserScore($recipientId, $task_id, $impact['score'], $impact['reason']);
            }
        }

        // Update Task with completed_at timestamp
        $query = "UPDATE tasks SET status = ?, completed_at = NOW() WHERE id = ?";
    }
    else {
        $query = "UPDATE tasks SET status = ? WHERE id = ?";
    }

    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $new_status, $task_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0 || $new_status === 'Completed') {
        // Trigger progress recalculation for the project this task belongs to
        $projectQuery = "SELECT project_id FROM tasks WHERE id = ?";
        $pStmt = $db->prepare($projectQuery);
        $pStmt->bind_param("i", $task_id);
        $pStmt->execute();
        $result = $pStmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $project_id = $row['project_id'];

            if ($project_id && $project_id > 0) {
                // Trigger progress calculation
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/calculate_progress.php';

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['project_id' => $project_id]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000);
                curl_exec($ch);
                curl_close($ch);
            }
        }
        $pStmt->close();

        echo json_encode(['status' => 'success', 'message' => 'Task status updated successfully.']);
    }
    else {
        // It might be that the status was already identical, check if we did the score update at least
        echo json_encode(['status' => 'success', 'message' => 'Task updated.']);
    }

    $stmt->close();
    $db->close();


}
catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
