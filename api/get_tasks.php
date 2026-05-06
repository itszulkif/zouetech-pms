<?php
// api/get_tasks.php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Team Member']);
header('Content-Type: application/json');

$project_id_raw = isset($_GET['project_id']) ? trim((string)$_GET['project_id']) : '';
$all_projects = ($project_id_raw === 'all');
$project_id = $all_projects ? -2 : (isset($_GET['project_id']) ? intval($_GET['project_id']) : -1);
$scope = isset($_GET['scope']) ? trim($_GET['scope']) : 'team'; // 'mine' | 'team'
if (!in_array($scope, ['mine', 'team'], true)) {
    $scope = 'team';
}
$filter_user = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0; // Super Admin: filter by team member

if (!$all_projects && $project_id < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Project ID']);
    exit;
}

$database = new Database();
$db = $database->connect();
$allow_runtime_migrations = defined('APP_ALLOW_RUNTIME_MIGRATIONS') && APP_ALLOW_RUNTIME_MIGRATIONS;
$role = $_SESSION['role'];
$dept_id = $_SESSION['department_id'] ?? 0;
$user_id = $_SESSION['user_id'];

$tasks = [];

if ($project_id === 0) {
    // Runtime schema writes are disabled in production by default.
    if ($allow_runtime_migrations) {
        @$db->query("CREATE TABLE IF NOT EXISTS task_assignment_pending (
            task_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            requested_by INT(11) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (task_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    // Auto-miss sweep for direct tasks.
    // If a specific time is set, treat it as the exact deadline timestamp.
    // Without specific time, keep legacy day-based behavior (missed after end-date day ends).
    $db->query("
        UPDATE tasks
        SET status = 'Missed'
        WHERE (project_id IS NULL OR project_id = 0)
          AND status NOT IN ('Completed', 'Missed', 'Pending Approval')
          AND COALESCE(end_date, DATE(due_date)) IS NOT NULL
          AND (
                (specific_time IS NOT NULL AND NOW() > TIMESTAMP(COALESCE(end_date, DATE(due_date)), specific_time))
                OR
                (specific_time IS NULL AND NOW() >= DATE_ADD(COALESCE(end_date, DATE(due_date)), INTERVAL 1 DAY))
          )
    ");

    // ── DIRECT TASKS ──────────────────────────────────────────────────────────
    // One row per task; assignee names aggregated via task_assignments.
    // scope=mine: only tasks assigned to current user.
    // scope=team: tasks assigned to other team members only (exclude tasks where current user is assignee).
    $visibility = "";
    $exclusions = "";

    // Super Admin: support own/team tabs, with optional member filter.
    if ($role === 'Super Admin') {
        if ($scope === 'mine') {
            $join_clause = "JOIN task_assignments ta ON ta.task_id = t.id\n        JOIN users u2 ON u2.id = ta.user_id";
            $visibility = "AND ta.user_id = $user_id";
            $exclusions = "";
        } else {
            $join_clause = "JOIN task_assignments ta ON ta.task_id = t.id\n        JOIN users u2 ON u2.id = ta.user_id";
            $visibility = ($filter_user > 0) ? "AND u2.id = $filter_user" : "";
            $exclusions = "AND t.id NOT IN (SELECT task_id FROM task_assignments WHERE user_id = $user_id)";
        }
    }
    // Team Members: show direct tasks assigned to them.
    else if ($role === 'Team Member') {
        $visibility = "AND ta.user_id = $user_id";
    }
    else if ($scope === 'mine') {
        $visibility = "AND ta.user_id = $user_id";
    } else {
        // Team Tasks: only tasks where at least one assignee is not me.
        $exclude_mine = "AND t.id NOT IN (SELECT task_id FROM task_assignments WHERE user_id = $user_id)";
        $visibility = $exclude_mine;
    }

    // For team scope use JOIN (only tasks with in-dept assignees); for mine use LEFT JOIN.
    // Super Admin already has join_clause set above.
    if ($role !== 'Super Admin') {
        $join_clause = ($scope === 'team') ? "JOIN task_assignments ta ON ta.task_id = t.id\n        JOIN users u2 ON u2.id = ta.user_id" : "LEFT JOIN task_assignments ta ON ta.task_id = t.id\n        LEFT JOIN users u2 ON u2.id = ta.user_id";
    }
    $query = "
        SELECT
            t.id, t.title, t.description, t.status, t.assigned_to, t.due_date,
            t.start_date, t.end_date, t.specific_time, t.priority, t.project_id,
            COALESCE(NULLIF(TRIM(GROUP_CONCAT(DISTINCT u2.full_name ORDER BY u2.full_name SEPARATOR ', ')), ''),
                (SELECT GROUP_CONCAT(u_p.full_name ORDER BY u_p.full_name SEPARATOR ', ') FROM task_assignment_pending tap2 JOIN users u_p ON u_p.id = tap2.user_id WHERE tap2.task_id = t.id)
            ) AS assigned_name,
            (
                SELECT u3.full_name
                FROM performance_logs pl
                JOIN users u3 ON u3.id = pl.user_id
                WHERE pl.task_id = t.id
                  AND pl.reason LIKE 'assigned Direct Task%'
                ORDER BY pl.timestamp ASC
                LIMIT 1
            ) AS created_by_name
        FROM tasks t
        $join_clause
        WHERE t.project_id IS NULL
        $visibility
        $exclusions
        GROUP BY t.id
        ORDER BY t.created_at DESC, t.id DESC
    ";

    $result = $db->query($query);
    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch direct tasks', 'error' => $db->error]);
        exit;
    }
    while ($row = $result->fetch_assoc()) {
        $is_my_assignment = (
            (!empty($row['assigned_to']) && (int)$row['assigned_to'] === (int)$user_id) ||
            ($db->query("SELECT 1 FROM task_assignments WHERE task_id = " . (int)$row['id'] . " AND user_id = " . (int)$user_id . " LIMIT 1")->num_rows > 0)
        );
        $row['is_my_assignment'] = $is_my_assignment ? 1 : 0;
        if ($role === 'Team Member' && $is_my_assignment) {
            if (($row['status'] ?? '') === 'Completed') {
                $row['allowed_statuses'] = ['Completed'];
            } else {
            $row['allowed_statuses'] = ['In Progress', 'Review'];
            }
        } else {
            $row['allowed_statuses'] = ['Pending', 'In Progress', 'Review', 'Completed'];
        }
        $tasks[] = $row;
    }

}
else {
    // ── PROJECT TASKS ─────────────────────────────────────────────────────────
    $extra_join = "";
    $extra_where = "";
    $project_filter = $all_projects ? "IS NOT NULL" : "= ?";

    if ($role === 'Team Member') {
        // Major project tasks are visible only to assigned users.
        $uid = (int)$user_id;
        $extra_where = "AND (
            t.assigned_to = $uid
            OR EXISTS (
                SELECT 1
                FROM task_assignments ta_self
                WHERE ta_self.task_id = t.id
                  AND ta_self.user_id = $uid
            )
        )";
    } else if ($role === 'Super Admin') {
        if ($scope === 'mine') {
            $extra_where = "AND (
                t.assigned_to = $user_id
                OR EXISTS (
                    SELECT 1
                    FROM task_assignments ta_self
                    WHERE ta_self.task_id = t.id
                      AND ta_self.user_id = $user_id
                )
            )";
        } else {
            $extra_where = "AND (
                EXISTS (SELECT 1 FROM task_assignments ta_any WHERE ta_any.task_id = t.id)
                OR (t.assigned_to IS NOT NULL AND t.assigned_to > 0)
            )
            AND t.id NOT IN (
                SELECT ta_my.task_id
                FROM task_assignments ta_my
                WHERE ta_my.user_id = $user_id
            )
            AND (t.assigned_to IS NULL OR t.assigned_to <> $user_id)";
            if ($filter_user > 0) {
                $extra_where .= " AND (
                    t.assigned_to = $filter_user
                    OR EXISTS (
                        SELECT 1
                        FROM task_assignments ta_filter
                        WHERE ta_filter.task_id = t.id
                          AND ta_filter.user_id = $filter_user
                    )
                )";
            }
        }
    }

    // assigned_name/avatar: prefer multi-assignee from task_assignments; fallback to tasks.assigned_to
    $query = "
        SELECT t.id, t.title, t.description, t.status, t.assigned_to, t.due_date,
               t.start_date, t.end_date, t.specific_time, t.priority, t.project_id,
               p.name AS project_name,
               COALESCE(
                   (SELECT GROUP_CONCAT(u2.full_name ORDER BY u2.full_name SEPARATOR ', ')
                    FROM task_assignments ta2
                    JOIN users u2 ON u2.id = ta2.user_id
                    WHERE ta2.task_id = t.id),
                   u.full_name
               ) AS assigned_name,
               u.avatar_url AS assigned_avatar
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        LEFT JOIN projects p ON p.id = t.project_id
        $extra_join
        WHERE t.project_id $project_filter
        $extra_where
        ORDER BY t.created_at DESC, t.id DESC
    ";

    // ── AUTO-MISS SWEEP: Mark overdue tasks as Missed ────────────────────────
    // Runs on every load so no cron job is needed.
    // Project tasks now follow the same deadline logic as direct tasks:
    // - with specific_time: missed after due_date + specific_time
    // - without specific_time: missed after due date day ends
    if ($all_projects) {
        $db->query("
            UPDATE tasks
            SET status = 'Missed'
            WHERE project_id IS NOT NULL
              AND status NOT IN ('Completed', 'Missed', 'Pending Approval')
              AND due_date IS NOT NULL
              AND (
                    (specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(due_date), specific_time))
                    OR
                    (specific_time IS NULL AND NOW() >= DATE_ADD(DATE(due_date), INTERVAL 1 DAY))
              )
        ");
    } else {
        $sweep = $db->prepare("
            UPDATE tasks
            SET status = 'Missed'
            WHERE project_id = ?
              AND status NOT IN ('Completed', 'Missed', 'Pending Approval')
              AND due_date IS NOT NULL
              AND (
                    (specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(due_date), specific_time))
                    OR
                    (specific_time IS NULL AND NOW() >= DATE_ADD(DATE(due_date), INTERVAL 1 DAY))
              )
        ");
        $sweep->bind_param("i", $project_id);
        $sweep->execute();
        $sweep->close();
    }
    // ─────────────────────────────────────────────────────────────────────────

    if ($all_projects) {
        $result = $db->query($query);
        if (!$result) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch all project tasks', 'error' => $db->error]);
            exit;
        }
    } else {
        $stmt = $db->prepare($query);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare project tasks query', 'error' => $db->error]);
            exit;
        }
        $stmt->bind_param("i", $project_id);
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to execute project tasks query', 'error' => $stmt->error]);
            $stmt->close();
            exit;
        }
        $result = $stmt->get_result();
    }
    while ($row = $result->fetch_assoc()) {
        $is_my_assignment = (
            (!empty($row['assigned_to']) && (int)$row['assigned_to'] === (int)$user_id) ||
            ($db->query("SELECT 1 FROM task_assignments WHERE task_id = " . (int)$row['id'] . " AND user_id = " . (int)$user_id . " LIMIT 1")->num_rows > 0)
        );
        $row['is_my_assignment'] = $is_my_assignment ? 1 : 0;
        if ($role === 'Team Member' && $is_my_assignment) {
            if (($row['status'] ?? '') === 'Completed') {
                $row['allowed_statuses'] = ['Completed'];
            } else {
            $row['allowed_statuses'] = ['In Progress', 'Review'];
            }
        } else {
            $row['allowed_statuses'] = ['Pending', 'In Progress', 'Review', 'Completed'];
        }
        $tasks[] = $row;
    }
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
}

echo json_encode([
    'status' => 'success',
    'data' => $tasks
]);
?>
