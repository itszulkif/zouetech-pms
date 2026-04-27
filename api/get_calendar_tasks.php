<?php
// api/get_calendar_tasks.php - Read-only API for Calendar page. Fetches Project + Direct tasks by deadline.
// Role-based: Team Member = own tasks; Dept Head = scope filter (mine | team).
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Department Head', 'Team Member']);
header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

$role = $_SESSION['role'];
$dept_id = (int)($_SESSION['department_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];
$scope = isset($_GET['scope']) ? trim($_GET['scope']) : 'team'; // Department Head/Super Admin: mine | team
if (!in_array($scope, ['mine', 'team'], true)) {
    $scope = 'team';
}
$filter_dept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0; // Super Admin: filter by department/team

$start = isset($_GET['start']) ? $db->real_escape_string($_GET['start']) : date('Y-m-01');
$end   = isset($_GET['end'])   ? $db->real_escape_string($_GET['end'])   : date('Y-m-t', strtotime('+2 months'));

$tasks = [];

// ─── PROJECT TASKS ─────────────────────────────────────────────────────────
$proj_where = "t.project_id IS NOT NULL AND t.project_id > 0";
$proj_join  = "";
$proj_extra = "";

if ($role === 'Team Member') {
    $proj_join  = "LEFT JOIN task_assignments ta_proj ON ta_proj.task_id = t.id";
    $proj_extra = "AND (t.assigned_to = $user_id OR ta_proj.user_id = $user_id)";
} elseif ($role === 'Department Head' && $dept_id > 0) {
    $proj_join = "JOIN projects p ON p.id = t.project_id";
    if ($scope === 'mine') {
        $proj_extra = "AND p.department_id = $dept_id AND p.project_type = 'Major' AND (
            t.assigned_to = $user_id
            OR EXISTS (SELECT 1 FROM task_assignments ta_mine WHERE ta_mine.task_id = t.id AND ta_mine.user_id = $user_id)
        )";
    } else {
        $proj_extra = "AND p.department_id = $dept_id AND p.project_type = 'Major' AND (
            EXISTS (SELECT 1 FROM task_assignments ta_t JOIN users u_t ON u_t.id = ta_t.user_id WHERE ta_t.task_id = t.id AND u_t.department_id = $dept_id)
            OR (t.assigned_to IS NOT NULL AND t.assigned_to > 0 AND EXISTS (SELECT 1 FROM users ua WHERE ua.id = t.assigned_to AND ua.department_id = $dept_id))
        )
        AND t.id NOT IN (SELECT task_id FROM task_assignments WHERE user_id = $user_id)
        AND (t.assigned_to IS NULL OR t.assigned_to <> $user_id)";
    }
} elseif ($role === 'Department Head' && $dept_id <= 0) {
    $proj_join  = "";
    $proj_extra = "AND 1=0";
} elseif ($role === 'Super Admin') {
    if ($scope === 'mine') {
        $proj_extra = "AND (
            t.assigned_to = $user_id
            OR EXISTS (SELECT 1 FROM task_assignments ta_my WHERE ta_my.task_id = t.id AND ta_my.user_id = $user_id)
        )";
    } else {
        // Super Admin team scope: tasks assigned to others only, optional department filter.
        $proj_extra = "AND (
            EXISTS (SELECT 1 FROM task_assignments ta_any WHERE ta_any.task_id = t.id)
            OR (t.assigned_to IS NOT NULL AND t.assigned_to > 0)
        )
        AND t.id NOT IN (SELECT task_id FROM task_assignments WHERE user_id = $user_id)
        AND (t.assigned_to IS NULL OR t.assigned_to <> $user_id)";
        if ($filter_dept > 0) {
            $proj_extra .= " AND (
                EXISTS (SELECT 1 FROM task_assignments ta_sa JOIN users u_sa ON u_sa.id = ta_sa.user_id WHERE ta_sa.task_id = t.id AND u_sa.department_id = $filter_dept)
                OR (t.assigned_to IS NOT NULL AND t.assigned_to > 0 AND EXISTS (SELECT 1 FROM users ua WHERE ua.id = t.assigned_to AND ua.department_id = $filter_dept))
            )";
        }
    }
}

$proj_sql = "
    SELECT t.id, t.title, t.status, t.due_date, t.start_date, t.end_date, t.project_id,
           COALESCE(
               (SELECT GROUP_CONCAT(u2.full_name SEPARATOR ', ') FROM task_assignments ta2 JOIN users u2 ON u2.id = ta2.user_id WHERE ta2.task_id = t.id),
               u.full_name
           ) AS assigned_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    $proj_join
    WHERE $proj_where $proj_extra
    AND (t.due_date IS NOT NULL OR t.start_date IS NOT NULL OR t.end_date IS NOT NULL)
    AND (
        (t.due_date >= '$start' AND t.due_date <= '$end')
        OR (t.due_date IS NULL AND t.start_date >= '$start' AND t.start_date <= '$end')
        OR (t.due_date IS NULL AND t.start_date IS NULL AND t.end_date >= '$start' AND t.end_date <= '$end')
    )
    GROUP BY t.id
";

$res = $db->query($proj_sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $date = $row['due_date'] ?: $row['start_date'] ?: $row['end_date'];
        $tasks[] = [
            'id'           => (int)$row['id'],
            'title'        => $row['title'],
            'status'       => $row['status'],
            'date'         => $date,
            'project_id'   => (int)$row['project_id'],
            'assigned_name'=> $row['assigned_name'],
            'type'         => 'project',
        ];
    }
}

// ─── DIRECT TASKS ──────────────────────────────────────────────────────────
$dir_where = "(t.project_id IS NULL OR t.project_id = 0)";
$dir_join  = "JOIN task_assignments ta_dir ON ta_dir.task_id = t.id";
$dir_extra = "";

if ($role === 'Team Member') {
    $dir_extra = "AND ta_dir.user_id = $user_id
        AND t.id NOT IN (
            SELECT ta3.task_id FROM task_assignments ta3
            JOIN users u3 ON u3.id = ta3.user_id
            WHERE u3.role = 'Department Head'
        )";
} elseif ($role === 'Department Head' && $dept_id > 0) {
    if ($scope === 'mine') {
        $dir_extra = "AND ta_dir.user_id = $user_id";
    } else {
        // team: at least one assignee in my dept, exclude my own
        $dir_extra = "AND EXISTS (SELECT 1 FROM users u_dir WHERE u_dir.id = ta_dir.user_id AND u_dir.department_id = $dept_id)
            AND t.id NOT IN (SELECT task_id FROM task_assignments WHERE user_id = $user_id)";
    }
} elseif ($role === 'Department Head' && $dept_id <= 0) {
    if ($scope === 'mine') {
        $dir_extra = "AND ta_dir.user_id = $user_id";
    } else {
        $dir_extra = "AND 1=0";
    }
} elseif ($role === 'Super Admin') {
    if ($scope === 'mine') {
        $dir_extra = "AND ta_dir.user_id = $user_id";
    } else {
        $dir_extra = "AND t.id NOT IN (SELECT task_id FROM task_assignments WHERE user_id = $user_id)";
        if ($filter_dept > 0) {
            $dir_extra .= " AND EXISTS (SELECT 1 FROM users u_dir WHERE u_dir.id = ta_dir.user_id AND u_dir.department_id = $filter_dept)";
        }
    }
}

$dir_sql = "
    SELECT t.id, t.title, t.status, t.due_date, t.start_date, t.end_date,
           (SELECT GROUP_CONCAT(u2.full_name SEPARATOR ', ') FROM task_assignments ta2 JOIN users u2 ON u2.id = ta2.user_id WHERE ta2.task_id = t.id) AS assigned_name
    FROM tasks t
    $dir_join
    WHERE $dir_where $dir_extra
    AND (t.due_date IS NOT NULL OR t.start_date IS NOT NULL OR t.end_date IS NOT NULL)
    AND (
        (t.due_date >= '$start' AND t.due_date <= '$end')
        OR (t.due_date IS NULL AND t.start_date >= '$start' AND t.start_date <= '$end')
        OR (t.due_date IS NULL AND t.start_date IS NULL AND t.end_date >= '$start' AND t.end_date <= '$end')
    )
    GROUP BY t.id
";

$res2 = $db->query($dir_sql);
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $date = $row['due_date'] ?: $row['start_date'] ?: $row['end_date'];
        $tasks[] = [
            'id'           => (int)$row['id'],
            'title'        => $row['title'],
            'status'       => $row['status'],
            'date'         => $date,
            'project_id'   => 0,
            'assigned_name'=> $row['assigned_name'],
            'type'         => 'direct',
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'data'   => $tasks,
]);
