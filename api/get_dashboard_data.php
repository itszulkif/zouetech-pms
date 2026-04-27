<?php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Team Member']);
header('Content-Type: application/json');

/**
 * Wrapper to run a query and terminate with a JSON error on failure.
 */
function q($db, $sql) {
    $result = $db->query($sql);
    if ($result === false) {
        echo json_encode(['success' => false, 'error' => $db->error, 'sql' => $sql]);
        exit;
    }
    return $result;
}

$database = new Database();
$db = $database->connect();

// Keep direct-task missed counts dynamic on dashboard load.
// If specific_time exists, use end_date + specific_time as the exact deadline.
// If specific_time is missing, keep legacy day-based fallback.
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

// Keep project-task Missed status dynamic with the same due-date + time logic.
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

// ─── 1. STATS BAR ────────────────────────────────────────────────────
$stats = [];

$r = q($db, "SELECT COUNT(*) c FROM projects WHERE project_type = 'Major'");
$stats['total_projects'] = (int)$r->fetch_assoc()['c'];

$stats['total_departments'] = 0;

$r = q($db, "SELECT COUNT(*) c FROM users WHERE role != 'Super Admin'");
$stats['total_members'] = (int)$r->fetch_assoc()['c'];

$r = q($db, "SELECT COUNT(*) c FROM projects WHERE project_type = 'Major' AND status IN ('Planned','In Progress','On Hold')");
$stats['active_projects'] = (int)$r->fetch_assoc()['c'];

$r = q($db, "SELECT COUNT(*) c FROM tasks WHERE project_id != 0 AND project_id IS NOT NULL AND status IN ('Pending','In Progress','Review')");
$stats['active_project_tasks'] = (int)$r->fetch_assoc()['c'];

$r = q($db, "SELECT COUNT(DISTINCT t.id) c
    FROM tasks t
    LEFT JOIN task_assignments ta ON ta.task_id = t.id
    WHERE (t.project_id = 0 OR t.project_id IS NULL)
      AND t.status IN ('Pending','In Progress','Review')
      AND (t.assigned_to IS NOT NULL OR ta.user_id IS NOT NULL)");
$stats['active_direct_tasks'] = (int)$r->fetch_assoc()['c'];

$r = q($db, "SELECT COUNT(*) c
    FROM tasks
    WHERE project_id IS NOT NULL
      AND project_id != 0
      AND status = 'Review'");
$stats['project_review_tasks'] = (int)$r->fetch_assoc()['c'];

$r = q($db, "SELECT COUNT(*) c
    FROM (
        SELECT DISTINCT t.id
        FROM tasks t
        LEFT JOIN task_assignments ta ON ta.task_id = t.id
        WHERE (t.project_id = 0 OR t.project_id IS NULL)
          AND t.status = 'Review'
          AND (t.assigned_to IS NOT NULL OR ta.user_id IS NOT NULL)
    ) x");
$stats['direct_review_tasks'] = (int)$r->fetch_assoc()['c'];

$r = q($db, "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS done,
    SUM(CASE WHEN status='Missed'    THEN 1 ELSE 0 END) AS missed
    FROM tasks
    WHERE project_id != 0 AND project_id IS NOT NULL");
$row = $r->fetch_assoc();
$stats['project_total_tasks']     = (int)$row['total'];
$stats['project_completed']       = (int)$row['done'];
$stats['project_missed']          = (int)$row['missed'];
$stats['project_completion_rate'] = $row['total'] > 0 ? round(($row['done'] / $row['total']) * 100) : 0;

$r = q($db, "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN x.status='Completed' THEN 1 ELSE 0 END) AS done,
    SUM(CASE WHEN x.status='Missed'    THEN 1 ELSE 0 END) AS missed
    FROM (
        SELECT DISTINCT t.id, t.status
        FROM tasks t
        LEFT JOIN task_assignments ta ON ta.task_id = t.id
        WHERE (t.project_id = 0 OR t.project_id IS NULL)
          AND (t.assigned_to IS NOT NULL OR ta.user_id IS NOT NULL)
    ) x");
$row = $r->fetch_assoc();
$stats['direct_total_tasks']     = (int)$row['total'];
$stats['direct_completed']       = (int)$row['done'];
$stats['direct_missed']          = (int)$row['missed'];
$stats['direct_completion_rate'] = $row['total'] > 0 ? round(($row['done'] / $row['total']) * 100) : 0;

// ─── 2. PROJECT TASKS — TEAM MEMBER-WISE ─────────────────────────────
$dept_tasks = [];
$result = q($db, "
    SELECT
        u.id,
        u.full_name AS dept_name,
        COUNT(t.id) AS total,
        SUM(CASE WHEN t.status = 'Pending'     THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN t.status = 'Review'      THEN 1 ELSE 0 END) AS review,
        SUM(CASE WHEN t.status = 'Completed'   THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN t.status = 'Missed'      THEN 1 ELSE 0 END) AS missed
    FROM users u
    LEFT JOIN tasks t ON t.assigned_to = u.id AND t.project_id IS NOT NULL AND t.project_id != 0
    WHERE u.role IN ('Team Member', 'Team Lead')
    GROUP BY u.id, u.full_name
    ORDER BY u.full_name ASC
");
while ($row = $result->fetch_assoc()) {                                                 
    $total = (int)$row['total'];
    $completed = (int)$row['completed'];
    $missed = (int)$row['missed'];
    $pct = $total > 0 ? round(($completed / $total) * 100) : 0;

    // Team member health indicator for Super Admin dashboard.
    // Critical if there are missed tasks or very low completion.
    // Needs Attention for mid-range completion.
    // On Track for strong completion and no misses.
    if ($missed > 0 || $pct < 45) {
        $health = 'Critical';
    } elseif ($pct < 75) {
        $health = 'Needs Attention';
    } else {
        $health = 'On Track';
    }

    $dept_tasks[] = [
        'id'          => (int)$row['id'],
        'dept_name'   => $row['dept_name'],
        'total'       => $total,
        'pending'     => (int)$row['pending'],
        'in_progress' => (int)$row['in_progress'],
        'review'      => (int)$row['review'],
        'completed'   => $completed,
        'missed'      => $missed,
        'pct'         => $pct,
        'health'      => $health,
    ];
}

// ─── 3. DIRECT TASKS — EMPLOYEE-WISE ─────────────────────────────────
$direct_tasks = [];
$result = q($db, "
    SELECT
        u.id,
        u.full_name,
        d.name AS dept_name,
        COUNT(t.id) AS total,
        SUM(CASE WHEN t.status = 'Pending'     THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN t.status = 'Review'      THEN 1 ELSE 0 END) AS review,
        SUM(CASE WHEN t.status = 'Completed'   THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN t.status = 'Missed'      THEN 1 ELSE 0 END) AS missed
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    INNER JOIN tasks t ON t.assigned_to = u.id AND (t.project_id = 0 OR t.project_id IS NULL)
    WHERE u.role != 'Super Admin'
    GROUP BY u.id, u.full_name, d.name
    ORDER BY total DESC
");
while ($row = $result->fetch_assoc()) {
    $total = (int)$row['total'];
    $completed = (int)$row['completed'];
    $direct_tasks[] = [
        'name'        => $row['full_name'],
        'dept'        => $row['dept_name'] ?? '-',
        'total'       => $total,
        'pending'     => (int)$row['pending'],
        'in_progress' => (int)$row['in_progress'],
        'review'      => (int)$row['review'],
        'completed'   => $completed,
        'missed'      => (int)$row['missed'],
        'pct'         => $total > 0 ? round(($completed / $total) * 100) : 0,
    ];
}

// ─── 4. AT-RISK PROJECTS ─────────────────────────────────────────────
$at_risk = [];
$result = q($db, "
    SELECT p.name, d.name AS dept, p.progress_percentage, p.end_date,
        (SELECT COUNT(*) FROM tasks t
         WHERE t.project_id = p.id
           AND t.status NOT IN ('Completed','Missed')
           AND t.due_date IS NOT NULL
           AND (
             (t.specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(t.due_date), t.specific_time))
             OR
             (t.specific_time IS NULL AND NOW() >= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
           )) AS delayed_count
    FROM projects p
    JOIN departments d ON p.department_id = d.id
    WHERE p.status = 'In Progress' AND p.project_type = 'Major'
    ORDER BY delayed_count DESC
    LIMIT 20
");
while ($row = $result->fetch_assoc()) {
    if ($row['delayed_count'] > 0 || ($row['end_date'] && $row['end_date'] < date('Y-m-d') && $row['progress_percentage'] < 100)) {
        $at_risk[] = [
            'name' => $row['name'],
            'dept' => $row['dept'],
            'progress_percentage' => $row['progress_percentage'],
            'end_date' => $row['end_date'],
            'delayed' => $row['delayed_count']
        ];
    }
    if (count($at_risk) >= 8) break;
}

// ─── 5. RECENT DIRECT TASKS (5 newest) ───────────────────────────────
$recent_direct_tasks = [];
$result = q($db, "
    SELECT t.id, t.title, t.status, t.priority, t.created_at,
           u.full_name AS assigned_name,
           u.avatar_url AS assigned_avatar,
           d.name AS dept_name
    FROM tasks t
    LEFT JOIN task_assignments ta ON ta.task_id = t.id
    LEFT JOIN users u ON u.id = ta.user_id
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE (t.project_id = 0 OR t.project_id IS NULL)
    GROUP BY t.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $recent_direct_tasks[] = $row;
}

echo json_encode([
    'success'             => true,
    'stats'               => $stats,
    'dept_tasks'          => $dept_tasks,
    'direct_tasks'        => $direct_tasks,
    'at_risk'             => $at_risk,
    'recent_direct_tasks' => $recent_direct_tasks,
]);
$db->close();
?>
