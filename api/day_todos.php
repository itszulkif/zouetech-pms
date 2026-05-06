<?php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin']);
header('Content-Type: application/json');

$db = (new Database())->connect();
mysqli_report(MYSQLI_REPORT_OFF);

function respond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function ensure_daily_todo_schema(mysqli $db): void
{
    // Keep table creation resilient across environments.
    // Some legacy MySQL setups fail FK creation and previously caused HTML fatal output.
    @$db->query("CREATE TABLE IF NOT EXISTS daily_todos (
        id INT(11) NOT NULL AUTO_INCREMENT,
        todo_date DATE NOT NULL,
        title VARCHAR(255) NOT NULL,
        details TEXT DEFAULT NULL,
        is_completed TINYINT(1) NOT NULL DEFAULT 0,
        created_by INT(11) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_daily_todo_date (todo_date),
        KEY idx_daily_todo_creator (created_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Backward-compatible column healing for older pre-existing tables.
    $requiredColumns = [
        'id' => "ADD COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST",
        'todo_date' => "ADD COLUMN todo_date DATE NOT NULL",
        'title' => "ADD COLUMN title VARCHAR(255) NOT NULL",
        'details' => "ADD COLUMN details TEXT DEFAULT NULL",
        'is_completed' => "ADD COLUMN is_completed TINYINT(1) NOT NULL DEFAULT 0",
        'created_by' => "ADD COLUMN created_by INT(11) DEFAULT NULL",
        'created_at' => "ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];

    foreach ($requiredColumns as $columnName => $alterSql) {
        $safeCol = $db->real_escape_string($columnName);
        $colRes = @$db->query("SELECT COUNT(*) AS cnt
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'daily_todos'
              AND COLUMN_NAME = '{$safeCol}'");
        $hasColumn = false;
        if ($colRes) {
            $row = $colRes->fetch_assoc();
            $hasColumn = ((int)($row['cnt'] ?? 0) > 0);
            $colRes->close();
        }
        if (!$hasColumn) {
            @$db->query("ALTER TABLE daily_todos {$alterSql}");
        }
    }

    @$db->query("ALTER TABLE daily_todos ADD KEY idx_daily_todo_date (todo_date)");
    @$db->query("ALTER TABLE daily_todos ADD KEY idx_daily_todo_creator (created_by)");

    // Remove legacy FK if present; created_by is metadata and should not block task creation.
    $fkRes = @$db->query("SELECT CONSTRAINT_NAME
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'daily_todos'
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
          AND CONSTRAINT_NAME = 'fk_daily_todo_creator'
        LIMIT 1");
    if ($fkRes && ($fkRow = $fkRes->fetch_assoc())) {
        @$db->query("ALTER TABLE daily_todos DROP FOREIGN KEY fk_daily_todo_creator");
    }
    if ($fkRes) {
        $fkRes->close();
    }
}

function parse_date_value(?string $value): ?string
{
    $v = trim((string)$value);
    if ($v === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        return null;
    }
    return $v;
}

function prepare_or_fail(mysqli $db, string $sql): mysqli_stmt
{
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        respond(['status' => 'error', 'message' => 'Database prepare failed: ' . $db->error], 500);
    }
    return $stmt;
}

function execute_or_fail(mysqli_stmt $stmt): void
{
    if (!$stmt->execute()) {
        respond(['status' => 'error', 'message' => 'Database execute failed: ' . $stmt->error], 500);
    }
}

function get_super_admin_map(mysqli $db): array
{
    $admins = [];
    $res = $db->query("SELECT id, full_name FROM users WHERE role = 'Super Admin' ORDER BY full_name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $id = (int)($row['id'] ?? 0);
            if ($id > 0) {
                $admins[$id] = [
                    'id' => $id,
                    'full_name' => (string)($row['full_name'] ?? 'Super Admin'),
                ];
            }
        }
        $res->close();
    }
    return $admins;
}

ensure_daily_todo_schema($db);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
if ($method === 'GET') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        respond(['status' => 'error', 'message' => 'Invalid session user.'], 401);
    }

    $superAdminMap = get_super_admin_map($db);
    if (!isset($superAdminMap[$userId])) {
        respond(['status' => 'error', 'message' => 'Access denied.'], 403);
    }

    if (isset($_GET['super_admins'])) {
        respond([
            'status' => 'success',
            'super_admins' => array_values($superAdminMap),
            'current_user_id' => $userId,
        ]);
    }

    $ownerId = (int)($_GET['owner_id'] ?? $userId);
    if ($ownerId <= 0 || !isset($superAdminMap[$ownerId])) {
        respond(['status' => 'error', 'message' => 'Invalid Super Admin owner.'], 400);
    }

    $date = parse_date_value($_GET['date'] ?? null);
    $start = parse_date_value($_GET['start'] ?? null);
    $end = parse_date_value($_GET['end'] ?? null);

    if ($date !== null) {
        $stmt = prepare_or_fail($db, "SELECT id, title, details, is_completed, created_at
            FROM daily_todos
            WHERE todo_date = ? AND created_by = ?
            ORDER BY is_completed ASC, id DESC");
        $stmt->bind_param("si", $date, $ownerId);
        execute_or_fail($stmt);
        $res = $stmt->get_result();
        $tasks = [];
        $completed = 0;
        while ($row = $res->fetch_assoc()) {
            $isDone = (int)$row['is_completed'] === 1;
            if ($isDone) {
                $completed++;
            }
            $tasks[] = [
                'id' => (int)$row['id'],
                'title' => (string)$row['title'],
                'details' => (string)($row['details'] ?? ''),
                'is_completed' => $isDone,
                'created_at' => (string)$row['created_at'],
            ];
        }
        $stmt->close();

        $total = count($tasks);
        $progress = $total > 0 ? (int)round(($completed / $total) * 100) : 0;
        respond([
            'status' => 'success',
            'date' => $date,
            'owner_id' => $ownerId,
            'tasks' => $tasks,
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'progress_percent' => $progress
            ]
        ]);
    }

    if ($start !== null && $end !== null) {
        $stmt = prepare_or_fail($db, "SELECT
                todo_date,
                COUNT(*) AS total_count,
                SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) AS completed_count
            FROM daily_todos
            WHERE todo_date >= ? AND todo_date <= ? AND created_by = ?
            GROUP BY todo_date");
        $stmt->bind_param("ssi", $start, $end, $ownerId);
        execute_or_fail($stmt);
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $total = (int)$row['total_count'];
            $completed = (int)$row['completed_count'];
            $rows[] = [
                'date' => (string)$row['todo_date'],
                'total' => $total,
                'completed' => $completed,
                'progress_percent' => $total > 0 ? (int)round(($completed / $total) * 100) : 0
            ];
        }
        $stmt->close();
        respond(['status' => 'success', 'owner_id' => $ownerId, 'data' => $rows]);
    }

    respond(['status' => 'error', 'message' => 'Provide valid date OR start+end.'], 400);
}

if ($method === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        respond(['status' => 'error', 'message' => 'Invalid session user.'], 401);
    }

    $superAdminMap = get_super_admin_map($db);
    if (!isset($superAdminMap[$userId])) {
        respond(['status' => 'error', 'message' => 'Access denied.'], 403);
    }

    if ($action === 'create') {
        $date = parse_date_value($_POST['date'] ?? null);
        $title = trim((string)($_POST['title'] ?? ''));
        $details = trim((string)($_POST['details'] ?? ''));

        if ($date === null || $title === '') {
            respond(['status' => 'error', 'message' => 'Date and title are required.'], 400);
        }

        $stmt = prepare_or_fail($db, "INSERT INTO daily_todos (todo_date, title, details, created_by)
            VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $date, $title, $details, $userId);
        execute_or_fail($stmt);
        $newId = (int)$db->insert_id;
        $stmt->close();
        respond(['status' => 'success', 'message' => 'Task created.', 'id' => $newId]);
    }

    if ($action === 'toggle') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        if ($taskId <= 0) {
            respond(['status' => 'error', 'message' => 'Invalid task id.'], 400);
        }

        $ownerStmt = prepare_or_fail($db, "SELECT created_by FROM daily_todos WHERE id = ? LIMIT 1");
        $ownerStmt->bind_param("i", $taskId);
        execute_or_fail($ownerStmt);
        $ownerRes = $ownerStmt->get_result();
        $ownerRow = $ownerRes ? $ownerRes->fetch_assoc() : null;
        $ownerStmt->close();
        $taskOwnerId = (int)($ownerRow['created_by'] ?? 0);
        if ($taskOwnerId <= 0) {
            respond(['status' => 'error', 'message' => 'Task not found.'], 404);
        }
        if ($taskOwnerId !== $userId) {
            respond(['status' => 'error', 'message' => 'You can only update your own to-do tasks.'], 403);
        }

        $stmt = prepare_or_fail($db, "UPDATE daily_todos
            SET is_completed = CASE WHEN is_completed = 1 THEN 0 ELSE 1 END
            WHERE id = ?");
        $stmt->bind_param("i", $taskId);
        execute_or_fail($stmt);
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected <= 0) {
            respond(['status' => 'error', 'message' => 'Task not found.'], 404);
        }
        respond(['status' => 'success', 'message' => 'Task status updated.']);
    }

    if ($action === 'delete') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        if ($taskId <= 0) {
            respond(['status' => 'error', 'message' => 'Invalid task id.'], 400);
        }

        $ownerStmt = prepare_or_fail($db, "SELECT created_by FROM daily_todos WHERE id = ? LIMIT 1");
        $ownerStmt->bind_param("i", $taskId);
        execute_or_fail($ownerStmt);
        $ownerRes = $ownerStmt->get_result();
        $ownerRow = $ownerRes ? $ownerRes->fetch_assoc() : null;
        $ownerStmt->close();
        $taskOwnerId = (int)($ownerRow['created_by'] ?? 0);
        if ($taskOwnerId <= 0) {
            respond(['status' => 'error', 'message' => 'Task not found.'], 404);
        }
        if ($taskOwnerId !== $userId) {
            respond(['status' => 'error', 'message' => 'You can only delete your own to-do tasks.'], 403);
        }

        $stmt = prepare_or_fail($db, "DELETE FROM daily_todos WHERE id = ?");
        $stmt->bind_param("i", $taskId);
        execute_or_fail($stmt);
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected <= 0) {
            respond(['status' => 'error', 'message' => 'Task not found.'], 404);
        }
        respond(['status' => 'success', 'message' => 'Task deleted.']);
    }

    respond(['status' => 'error', 'message' => 'Unsupported action.'], 400);
}

respond(['status' => 'error', 'message' => 'Method not allowed.'], 405);
} catch (Throwable $e) {
    respond(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
}
?>
