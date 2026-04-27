<?php
// api/create_task.php
require_once '../includes/auth_middleware.php';
ob_start();
header('Content-Type: application/json');

/**
 * Bind params with references for dynamic mysqli prepared statements.
 */
function bind_params_dynamic($stmt, $types, array &$params)
{
    if (empty($params)) {
        return $stmt->bind_param($types);
    }
    $refs = [];
    foreach ($params as $k => $v) {
        $refs[$k] = &$params[$k];
    }
    array_unshift($refs, $types);
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

function task_create_log($message, array $context = [])
{
    $logFile = __DIR__ . '/../logs/task_create_debug.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    $line .= PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND);
}

function json_response(array $payload)
{
    $buffer = ob_get_clean();
    if ($buffer !== '') {
        task_create_log('Unexpected output buffer content before JSON response', ['buffer' => $buffer]);
        $payload['_debug_buffer'] = trim($buffer);
    }
    echo json_encode($payload);
    exit;
}

function run_schema_query($db, $sql, array $ignoreErrorCodes = [])
{
    try {
        return $db->query($sql);
    } catch (mysqli_sql_exception $e) {
        if (in_array((int)$e->getCode(), $ignoreErrorCodes, true)) {
            task_create_log('Ignored schema query error', [
                'code' => (int)$e->getCode(),
                'error' => $e->getMessage(),
                'sql' => $sql
            ]);
            return false;
        }
        throw $e;
    }
}

$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

$allowed_roles = ['Super Admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    json_response(['status' => 'error', 'message' => 'Unauthorized']);
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Collect assignee IDs
$assigned_to_ids = [];
if (!empty($_POST['assigned_to'])) {
    if (is_array($_POST['assigned_to'])) {
        $assigned_to_ids = array_map('intval', $_POST['assigned_to']);
    }
    else {
        $assigned_to_ids = [intval($_POST['assigned_to'])];
    }
}

// Date & Time fields
$start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
$due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
$end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
$specific_time = !empty($_POST['specific_time']) ? $_POST['specific_time'] : null;
$priority = isset($_POST['priority']) ? $_POST['priority'] : 'Medium';

// Direct Task flag
$is_standalone = ($project_id <= 0);

// Validation
if (empty($title)) {
    json_response(['status' => 'error', 'message' => 'Title is required.']);
}

if ($is_standalone) {
    if (empty($start_date)) {
        json_response(['status' => 'error', 'message' => 'Start Date is required for Direct Tasks.']);
    }
    if (empty($end_date)) {
        json_response(['status' => 'error', 'message' => 'End Date is required for Direct Tasks.']);
    }
    if (empty($assigned_to_ids)) {
        json_response(['status' => 'error', 'message' => 'Please select at least one assignee.']);
    }
    if (!empty($start_date) && !empty($end_date) && $start_date > $end_date) {
        json_response(['status' => 'error', 'message' => 'End Date cannot be earlier than Start Date.']);
    }
    // Keep timeline checks consistent by treating End Date as Due Date for direct tasks.
    if (empty($due_date) && !empty($end_date)) {
        $due_date = $end_date;
    }
}
$database = new Database();
$db = $database->connect();
$allow_runtime_migrations = defined('APP_ALLOW_RUNTIME_MIGRATIONS') && APP_ALLOW_RUNTIME_MIGRATIONS;

// Runtime schema changes are disabled in production unless explicitly enabled.
if ($allow_runtime_migrations) {
    try {
        $existingTaskCols = [];
        $existingTaskColsRes = $db->query("SHOW COLUMNS FROM tasks");
        if ($existingTaskColsRes) {
            while ($col = $existingTaskColsRes->fetch_assoc()) {
                $existingTaskCols[] = $col['Field'];
            }
        }
        if (!in_array('start_date', $existingTaskCols, true)) {
            run_schema_query($db, "ALTER TABLE tasks ADD COLUMN start_date DATE NULL", [1060]);
        }
        if (!in_array('end_date', $existingTaskCols, true)) {
            run_schema_query($db, "ALTER TABLE tasks ADD COLUMN end_date DATE NULL", [1060]);
        }
        if (!in_array('specific_time', $existingTaskCols, true)) {
            run_schema_query($db, "ALTER TABLE tasks ADD COLUMN specific_time TIME NULL", [1060]);
        }
        if (!in_array('due_date', $existingTaskCols, true)) {
            run_schema_query($db, "ALTER TABLE tasks ADD COLUMN due_date DATE NULL", [1060]);
        }
        run_schema_query($db, "ALTER TABLE tasks MODIFY COLUMN project_id INT(11) NULL");
        run_schema_query($db, "ALTER TABLE tasks MODIFY COLUMN priority ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium'");
        run_schema_query($db, "ALTER TABLE tasks MODIFY COLUMN status ENUM('Pending','Pending Approval','In Progress','Completed','Review','Missed') NOT NULL DEFAULT 'Pending'");
    } catch (Exception $e) {
        task_create_log('Schema sync failed for tasks table', ['error' => $e->getMessage()]);
    }
}

// Ensure assignment table exists for both Project and Direct task flows.
if ($allow_runtime_migrations) {
    run_schema_query($db, "CREATE TABLE IF NOT EXISTS task_assignments (
    task_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id, user_id),
    KEY idx_ta_user (user_id),
    CONSTRAINT fk_ta_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", [1050]);
}

// Dedicated in-app assignment notifications (separate from mentions/push subscriptions).
if ($allow_runtime_migrations) {
    run_schema_query($db, "CREATE TABLE IF NOT EXISTS task_assignment_notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    task_id INT(11) NOT NULL,
    recipient_user_id INT(11) NOT NULL,
    sender_user_id INT(11) NOT NULL,
    notification_type ENUM('direct_task','project_task') NOT NULL,
    title_snapshot VARCHAR(255) NOT NULL,
    message VARCHAR(500) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tan_task_recipient_type (task_id, recipient_user_id, notification_type),
    KEY idx_tan_recipient (recipient_user_id, is_read, created_at),
    KEY idx_tan_task (task_id),
    CONSTRAINT fk_tan_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_tan_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tan_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", [1050]);
}

// Backward compatibility: if table existed before this feature, add missing columns/indexes safely.
$notifCols = [];
$notifColsRes = $db->query("SHOW COLUMNS FROM task_assignment_notifications");
if ($notifColsRes) {
    while ($col = $notifColsRes->fetch_assoc()) {
        $notifCols[] = $col['Field'];
    }
}
if ($allow_runtime_migrations && !in_array('recipient_user_id', $notifCols, true)) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications ADD COLUMN recipient_user_id INT(11) NOT NULL AFTER task_id", [1060]);
}
if ($allow_runtime_migrations && !in_array('sender_user_id', $notifCols, true)) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications ADD COLUMN sender_user_id INT(11) NOT NULL AFTER recipient_user_id", [1060]);
}
if ($allow_runtime_migrations && !in_array('notification_type', $notifCols, true)) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications ADD COLUMN notification_type ENUM('direct_task','project_task') NOT NULL DEFAULT 'direct_task' AFTER sender_user_id", [1060]);
}
if ($allow_runtime_migrations && !in_array('title_snapshot', $notifCols, true)) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications ADD COLUMN title_snapshot VARCHAR(255) NOT NULL DEFAULT '' AFTER notification_type", [1060]);
}
if ($allow_runtime_migrations && !in_array('message', $notifCols, true)) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications ADD COLUMN message VARCHAR(500) NOT NULL DEFAULT '' AFTER title_snapshot", [1060]);
}
if ($allow_runtime_migrations && !in_array('is_read', $notifCols, true)) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER message", [1060]);
}
if ($allow_runtime_migrations && !in_array('created_at', $notifCols, true)) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER is_read", [1060]);
}

// Legacy cleanup: remove old FK/column shape (user_id/sender_id) that conflicts with new recipient/sender columns.
$legacyFkRes = $db->query("SELECT DISTINCT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'task_assignment_notifications'
      AND COLUMN_NAME IN ('user_id', 'sender_id', 'assigned_by_user_id')
      AND REFERENCED_TABLE_NAME IS NOT NULL");
if ($allow_runtime_migrations && $legacyFkRes) {
    while ($legacyFk = $legacyFkRes->fetch_assoc()) {
        $fkName = str_replace('`', '``', $legacyFk['CONSTRAINT_NAME']);
        run_schema_query($db, "ALTER TABLE task_assignment_notifications DROP FOREIGN KEY `$fkName`", [1091]);
    }
}

$notifCols = [];
$notifColsRes = $db->query("SHOW COLUMNS FROM task_assignment_notifications");
if ($notifColsRes) {
    while ($col = $notifColsRes->fetch_assoc()) {
        $notifCols[] = $col['Field'];
    }
}
$hasRecipientCol = in_array('recipient_user_id', $notifCols, true);
$hasSenderCol = in_array('sender_user_id', $notifCols, true);
$hasLegacyUserCol = in_array('user_id', $notifCols, true);
$hasLegacyAssignedByCol = in_array('assigned_by_user_id', $notifCols, true);
$hasLegacySenderCol = in_array('sender_id', $notifCols, true);

if ($hasRecipientCol && $hasLegacyUserCol) {
    run_schema_query($db, "UPDATE task_assignment_notifications
        SET recipient_user_id = user_id
        WHERE (recipient_user_id IS NULL OR recipient_user_id = 0)
          AND user_id IS NOT NULL
          AND user_id <> 0");
}

if ($hasSenderCol && $hasLegacyAssignedByCol) {
    run_schema_query($db, "UPDATE task_assignment_notifications
        SET sender_user_id = assigned_by_user_id
        WHERE (sender_user_id IS NULL OR sender_user_id = 0)
          AND assigned_by_user_id IS NOT NULL
          AND assigned_by_user_id <> 0");
}

if ($hasSenderCol && $hasLegacySenderCol) {
    run_schema_query($db, "UPDATE task_assignment_notifications
        SET sender_user_id = sender_id
        WHERE (sender_user_id IS NULL OR sender_user_id = 0)
          AND sender_id IS NOT NULL
          AND sender_id <> 0");
}

if ($allow_runtime_migrations && in_array('user_id', $notifCols, true)) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications DROP COLUMN user_id", [1091]);
}
if ($allow_runtime_migrations && in_array('assigned_by_user_id', $notifCols, true)) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications DROP COLUMN assigned_by_user_id", [1091]);
}
if ($allow_runtime_migrations && in_array('sender_id', $notifCols, true)) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications DROP COLUMN sender_id", [1091]);
}

$notifIndexes = [];
$notifIdxRes = $db->query("SHOW INDEX FROM task_assignment_notifications");
if ($notifIdxRes) {
    while ($idx = $notifIdxRes->fetch_assoc()) {
        $notifIndexes[$idx['Key_name']] = true;
    }
}
if ($allow_runtime_migrations && !isset($notifIndexes['uq_tan_task_recipient_type'])) {
    // Clean duplicates first so unique key creation does not fail on legacy rows.
    run_schema_query($db, "DELETE n1 FROM task_assignment_notifications n1
        INNER JOIN task_assignment_notifications n2
            ON n1.task_id = n2.task_id
            AND n1.recipient_user_id = n2.recipient_user_id
            AND n1.notification_type = n2.notification_type
            AND n1.id < n2.id");
    try {
        run_schema_query($db, "ALTER TABLE task_assignment_notifications ADD UNIQUE KEY uq_tan_task_recipient_type (task_id, recipient_user_id, notification_type)", [1061, 1062]);
    } catch (Exception $e) {
        // Never block task creation if legacy data prevents adding this optimization key.
        task_create_log('Skipping unique key migration for task_assignment_notifications', ['error' => $e->getMessage()]);
    }
}
if ($allow_runtime_migrations && !isset($notifIndexes['idx_tan_recipient'])) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications ADD KEY idx_tan_recipient (recipient_user_id, is_read, created_at)", [1061]);
}
if ($allow_runtime_migrations && !isset($notifIndexes['idx_tan_task'])) {
    run_schema_query($db, "ALTER TABLE task_assignment_notifications ADD KEY idx_tan_task (task_id)", [1061]);
}


// Verify required columns exist in tasks table (for reliable INSERT mapping).
$taskCols = [];
$taskColsRes = $db->query("SHOW COLUMNS FROM tasks");
if ($taskColsRes) {
    while ($col = $taskColsRes->fetch_assoc()) {
        $taskCols[] = $col['Field'];
    }
}
$requiredCols = ['project_id', 'title', 'description', 'assigned_to', 'start_date', 'end_date', 'specific_time', 'due_date', 'priority', 'status'];
$missingCols = array_values(array_diff($requiredCols, $taskCols));
if (!empty($missingCols)) {
    task_create_log('Missing required columns in tasks table', ['missing' => $missingCols]);
    json_response([
        'status' => 'error',
        'message' => 'Tasks table schema mismatch. Missing columns: ' . implode(', ', $missingCols),
        'debug' => ['missing_columns' => $missingCols]
    ]);
}

$requiredTables = ['task_assignments', 'task_assignment_notifications'];
$missingTables = [];
foreach ($requiredTables as $tableName) {
    $tableNameEscaped = $db->real_escape_string($tableName);
    $res = $db->query("SHOW TABLES LIKE '{$tableNameEscaped}'");
    if (!$res || $res->num_rows === 0) {
        $missingTables[] = $tableName;
    }
}
if (!empty($missingTables)) {
    task_create_log('Missing required tables for task creation', ['missing_tables' => $missingTables]);
    json_response([
        'status' => 'error',
        'message' => 'Database schema mismatch. Run SQL migrations before creating tasks.',
        'debug' => ['missing_tables' => $missingTables]
    ]);
}

// Super Admin project tasks must stay inside selected members of the major project.
if ($role === 'Super Admin' && !$is_standalone && !empty($assigned_to_ids)) {
    $allowedMemberIds = [];
    $hasMembersTable = false;
    $tableRes = $db->query("SHOW TABLES LIKE 'major_project_members'");
    if ($tableRes && $tableRes->num_rows > 0) {
        $hasMembersTable = true;
    }

    if ($hasMembersTable) {
        $stmt = $db->prepare("SELECT user_id FROM major_project_members WHERE major_project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $allowedMemberIds[] = (int)$row['user_id'];
        }
        $stmt->close();
    }

    if (!empty($allowedMemberIds)) {
        $assigneePlaceholders = implode(',', array_fill(0, count($assigned_to_ids), '?'));
        $allowedPlaceholders = implode(',', array_fill(0, count($allowedMemberIds), '?'));
        $types = str_repeat('i', count($assigned_to_ids) + count($allowedMemberIds));
        $params = array_merge($assigned_to_ids, $allowedMemberIds);
        $sql = "SELECT id FROM users
                WHERE id IN ($assigneePlaceholders)
                  AND (id IN ($allowedPlaceholders) OR role = 'Super Admin')";
        $stmt = $db->prepare($sql);
        bind_params_dynamic($stmt, $types, $params);
        $stmt->execute();
        $validCount = $stmt->get_result()->num_rows;
        $stmt->close();
        if ($validCount !== count($assigned_to_ids)) {
            json_response(['status' => 'error', 'message' => 'Assignees must belong to team members selected for this Major Project.']);
        }
    }
}
$db->begin_transaction();

try {
    $db_project_id = $is_standalone ? null : $project_id;
    $initial_status = 'Pending';

    if ($is_standalone) {
        // ── DIRECT TASK ──────────────────────────────────────────────
        $projectValueSql = ($db_project_id === null) ? "NULL" : "?";
        $stmt = $db->prepare(
            "INSERT INTO tasks (project_id, title, description, assigned_to, start_date, end_date, specific_time, due_date, priority, status)
             VALUES ($projectValueSql, ?, ?, NULL, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new Exception('Prepare failed for direct task insert: ' . $db->error);
        }
        if ($db_project_id === null) {
            $stmt->bind_param("ssssssss", $title, $description, $start_date, $end_date, $specific_time, $due_date, $priority, $initial_status);
        } else {
            $stmt->bind_param("issssssss", $db_project_id, $title, $description, $start_date, $end_date, $specific_time, $due_date, $priority, $initial_status);
        }
        if (!$stmt->execute()) {
            throw new Exception('Direct task insert failed: ' . $stmt->error);
        }
        $task_id = $db->insert_id;
        $stmt->close();

        $assign_stmt = $db->prepare("INSERT IGNORE INTO task_assignments (task_id, user_id) VALUES (?, ?)");
        $notif_stmt = $db->prepare("INSERT INTO task_assignment_notifications (task_id, recipient_user_id, sender_user_id, notification_type, title_snapshot, message) VALUES (?, ?, ?, 'direct_task', ?, ?) ON DUPLICATE KEY UPDATE title_snapshot = VALUES(title_snapshot), message = VALUES(message), sender_user_id = VALUES(sender_user_id), is_read = 0, created_at = CURRENT_TIMESTAMP");
        foreach ($assigned_to_ids as $uid) {
            $uid = (int)$uid;
            $assign_stmt->bind_param("ii", $task_id, $uid);
            $assign_stmt->execute();
            $notif_message = "New Direct Task assigned: " . $title;
            $notif_stmt->bind_param("iiiss", $task_id, $uid, $user_id, $title, $notif_message);
            $notif_stmt->execute();
            $reason = "assigned Direct Task '$title' to user ID $uid";
            $log_stmt = $db->prepare("INSERT INTO performance_logs (user_id, task_id, score_change, reason) VALUES (?, ?, 0, ?)");
            $log_stmt->bind_param("iis", $user_id, $task_id, $reason);
            $log_stmt->execute();
            $log_stmt->close();
        }
        $notif_stmt->close();
        $assign_stmt->close();
    }
    else {
        // ── PROJECT TASK ──────────────────────────────────────────────
        $first_assignee = (!empty($assigned_to_ids)) ? $assigned_to_ids[0] : null;
        $projectValueSql = ($db_project_id === null) ? "NULL" : "?";
        $assigneeValueSql = ($first_assignee === null) ? "NULL" : "?";
        $stmt = $db->prepare(
            "INSERT INTO tasks (project_id, title, description, assigned_to, start_date, end_date, specific_time, due_date, priority, status)
             VALUES ($projectValueSql, ?, ?, $assigneeValueSql, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new Exception('Prepare failed for project task insert: ' . $db->error);
        }
        $types = "";
        $params = [];
        if ($db_project_id !== null) { $types .= "i"; $params[] = $db_project_id; }
        $types .= "ss";
        $params[] = $title;
        $params[] = $description;
        if ($first_assignee !== null) { $types .= "i"; $params[] = $first_assignee; }
        $types .= "ssssss";
        $params[] = $start_date;
        $params[] = $end_date;
        $params[] = $specific_time;
        $params[] = $due_date;
        $params[] = $priority;
        $params[] = $initial_status;
        if (!bind_params_dynamic($stmt, $types, $params)) {
            throw new Exception('Bind failed for project task insert: ' . $stmt->error);
        }
        if (!$stmt->execute()) {
            throw new Exception('Project task insert failed: ' . $stmt->error);
        }
        $task_id = $db->insert_id;
        $stmt->close();

        // Assign to multiple users: insert into task_assignments and log each
        if (!empty($assigned_to_ids)) {
            $assign_stmt = $db->prepare("INSERT IGNORE INTO task_assignments (task_id, user_id) VALUES (?, ?)");
            $notif_stmt = $db->prepare("INSERT INTO task_assignment_notifications (task_id, recipient_user_id, sender_user_id, notification_type, title_snapshot, message) VALUES (?, ?, ?, 'project_task', ?, ?) ON DUPLICATE KEY UPDATE title_snapshot = VALUES(title_snapshot), message = VALUES(message), sender_user_id = VALUES(sender_user_id), is_read = 0, created_at = CURRENT_TIMESTAMP");
            foreach ($assigned_to_ids as $uid) {
                $uid = (int)$uid;
                $assign_stmt->bind_param("ii", $task_id, $uid);
                $assign_stmt->execute();
                $notif_message = "New Project Task assigned: " . $title;
                $notif_stmt->bind_param("iiiss", $task_id, $uid, $user_id, $title, $notif_message);
                $notif_stmt->execute();
                $reason = "assigned task '$title' to user ID $uid (Project ID: $project_id)";
                $log_stmt = $db->prepare("INSERT INTO performance_logs (user_id, task_id, score_change, reason) VALUES (?, ?, 0, ?)");
                $log_stmt->bind_param("iis", $user_id, $task_id, $reason);
                $log_stmt->execute();
                $log_stmt->close();
            }
            $notif_stmt->close();
            $assign_stmt->close();
        }

    }

    $db->commit();
    // Trigger Progress Recalculation (project tasks only)
    if (!$is_standalone) {
        session_write_close();
        $ch = curl_init();
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/calculate_progress.php';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['project_id' => $project_id]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000);
        curl_exec($ch);
        curl_close($ch);
    }

    json_response(['status' => 'success', 'message' => 'Task created successfully.', 'pending_approval' => false]);

}
catch (Exception $e) {
    $db->rollback();
    task_create_log('Task creation exception', [
        'error' => $e->getMessage(),
        'project_id' => $project_id,
        'is_standalone' => $is_standalone,
        'user_id' => $user_id,
        'role' => $role,
        'post' => $_POST
    ]);
    json_response([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'db_error' => $db->error,
            'project_id' => $project_id,
            'is_standalone' => $is_standalone
        ]
    ]);
}
?>
