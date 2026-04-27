<?php
// api/get_dept_head_stats.php
// Returns department-scoped stats for the Department Head My Desk page
require_once '../includes/auth_middleware.php';
require_role(['Department Head']);
header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

$dept_id = $_SESSION['department_id'] ?? 0;

if ($dept_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'No department assigned to this account.']);
    exit;
}

@$db->query("CREATE TABLE IF NOT EXISTS task_assignment_pending (
    task_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    requested_by INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Keep task statuses dynamic before aggregating all department metrics.
$db->query("
    UPDATE tasks
    SET status = 'Missed'
    WHERE status NOT IN ('Completed', 'Missed', 'Pending Approval')
      AND (
        (
          (project_id IS NULL OR project_id = 0)
          AND COALESCE(end_date, DATE(due_date)) IS NOT NULL
          AND (
            (specific_time IS NOT NULL AND NOW() > TIMESTAMP(COALESCE(end_date, DATE(due_date)), specific_time))
            OR
            (specific_time IS NULL AND NOW() >= DATE_ADD(COALESCE(end_date, DATE(due_date)), INTERVAL 1 DAY))
          )
        )
        OR
        (
          (project_id IS NOT NULL AND project_id <> 0)
          AND due_date IS NOT NULL
          AND (
            (specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(due_date), specific_time))
            OR
            (specific_time IS NULL AND NOW() >= DATE_ADD(DATE(due_date), INTERVAL 1 DAY))
          )
        )
      )
");

// ── 1. Project Stats (Major projects only) ──────────────────────────────────
$project_stats = ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'planned' => 0, 'on_hold' => 0];
$res = $db->query("SELECT status, COUNT(*) as cnt FROM projects
                   WHERE department_id = $dept_id AND project_type = 'Major'
                   GROUP BY status");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $project_stats['total'] += $row['cnt'];
        $key = strtolower(str_replace(' ', '_', $row['status']));
        if (isset($project_stats[$key])) {
            $project_stats[$key] = (int)$row['cnt'];
        }
    }
}
$project_stats['pending'] = $project_stats['total'] - $project_stats['completed'];

// ── 2. Sub-project count ────────────────────────────────────────────────────
$sub_total = 0;
$res2 = $db->query("SELECT COUNT(*) as cnt FROM projects WHERE department_id = $dept_id AND project_type = 'Sub'");
if ($res2) $sub_total = (int)$res2->fetch_assoc()['cnt'];

// ── 3. Project Task Stats (tasks linked to dept projects) ───────────────────
$project_tasks = [
    'total'       => 0,
    'pending'     => 0,
    'in_progress' => 0,
    'review'      => 0,
    'completed'   => 0,
    'missed'      => 0,
];
$res3 = $db->query("SELECT t.status, COUNT(*) as cnt
                    FROM tasks t
                    JOIN projects p ON t.project_id = p.id
                    WHERE p.department_id = $dept_id
                    GROUP BY t.status");
if ($res3) {
    while ($row = $res3->fetch_assoc()) {
        $project_tasks['total'] += (int)$row['cnt'];
        $status = $row['status'];
        if ($status === 'Pending')       $project_tasks['pending']     += (int)$row['cnt'];
        elseif ($status === 'In Progress') $project_tasks['in_progress'] += (int)$row['cnt'];
        elseif ($status === 'Review')    $project_tasks['review']      += (int)$row['cnt'];
        elseif ($status === 'Completed') $project_tasks['completed']   += (int)$row['cnt'];
        elseif ($status === 'Missed')    $project_tasks['missed']      += (int)$row['cnt'];
    }
}

// Backwards-compatible alias for older UI pieces (project tasks only)
$task_stats = [
    'total'       => $project_tasks['total'],
    'completed'   => $project_tasks['completed'],
    'pending'     => $project_tasks['pending'],
    'in_progress' => $project_tasks['in_progress'],
];

// ── 3b. Direct Task Stats (standalone tasks for dept members) ───────────────
// Direct tasks use task_assignments (assigned_to is NULL) per create_task.php
$direct_tasks = [
    'total'       => 0,
    'pending'     => 0,
    'in_progress' => 0,
    'review'      => 0,
    'completed'   => 0,
    'missed'      => 0,
];
$res3b = $db->query("SELECT t.status, COUNT(DISTINCT t.id) as cnt
                     FROM tasks t
                     LEFT JOIN users u ON t.assigned_to = u.id
                     LEFT JOIN task_assignments ta ON ta.task_id = t.id
                     LEFT JOIN users u2 ON ta.user_id = u2.id
                     WHERE (t.project_id = 0 OR t.project_id IS NULL)
                       AND (u.department_id = $dept_id OR u2.department_id = $dept_id
                            OR EXISTS (SELECT 1 FROM performance_logs pl JOIN users u_pl ON u_pl.id = pl.user_id WHERE pl.task_id = t.id AND pl.reason LIKE 'assigned Direct Task%' AND u_pl.department_id = $dept_id)
                            OR EXISTS (SELECT 1 FROM task_assignment_pending tap JOIN users u_req ON u_req.id = tap.requested_by WHERE tap.task_id = t.id AND u_req.department_id = $dept_id))
                     GROUP BY t.status");
if ($res3b) {
    while ($row = $res3b->fetch_assoc()) {
        $direct_tasks['total'] += (int)$row['cnt'];
        $status = $row['status'];
        if ($status === 'Pending')        $direct_tasks['pending']     += (int)$row['cnt'];
        elseif ($status === 'In Progress') $direct_tasks['in_progress'] += (int)$row['cnt'];
        elseif ($status === 'Review')     $direct_tasks['review']      += (int)$row['cnt'];
        elseif ($status === 'Completed')  $direct_tasks['completed']   += (int)$row['cnt'];
        elseif ($status === 'Missed')     $direct_tasks['missed']      += (int)$row['cnt'];
    }
}

// ── 4. Assigned Team Members ────────────────────────────────────────────────
$assigned_members = 0;
$res4 = $db->query("SELECT COUNT(DISTINCT t.assigned_to) as cnt
                    FROM tasks t JOIN projects p ON t.project_id = p.id
                    WHERE p.department_id = $dept_id AND t.assigned_to IS NOT NULL AND t.assigned_to > 0");
if ($res4) $assigned_members = (int)$res4->fetch_assoc()['cnt'];

// ── 5. Department name ──────────────────────────────────────────────────────
$dept_name = '';
$res5 = $db->query("SELECT name FROM departments WHERE id = $dept_id");
if ($res5) $dept_name = $res5->fetch_assoc()['name'] ?? '';

// ── 6. Recent sub-projects list ─────────────────────────────────────────────
$sub_projects = [];
$res6 = $db->query("SELECT p.id, p.name, p.status, p.progress_percentage,
                        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
                        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'Completed') as done_tasks,
                        mp.name as major_project_name
                    FROM projects p
                    JOIN projects mp ON p.parent_project_id = mp.id
                    WHERE p.department_id = $dept_id AND p.project_type = 'Sub'
                    ORDER BY p.created_at DESC LIMIT 10");
if ($res6) {
    while ($row = $res6->fetch_assoc()) $sub_projects[] = $row;
}

// ── 7. Active / Incomplete Major Projects ───────────────────────────────────
$active_projects = [];
$res7 = $db->query("SELECT p.id, p.name, p.status, p.start_date, p.end_date,
                        p.progress_percentage,
                        (SELECT COUNT(*) FROM projects WHERE parent_project_id = p.id AND project_type = 'Sub') as sub_count,
                        (SELECT COUNT(*) FROM tasks t2 JOIN projects sp ON t2.project_id = sp.id WHERE sp.parent_project_id = p.id) as total_tasks,
                        (SELECT COUNT(*) FROM tasks t2 JOIN projects sp ON t2.project_id = sp.id WHERE sp.parent_project_id = p.id AND t2.status = 'Completed') as done_tasks
                    FROM projects p
                    WHERE p.department_id = $dept_id AND p.project_type = 'Major' AND p.status != 'Completed'
                    ORDER BY p.start_date ASC");
if ($res7) {
    while ($row = $res7->fetch_assoc()) $active_projects[] = $row;
}

// ── 8. Project Monthly Task Performance (current year, all 12 months) ───────
$current_year = date('Y');
$monthly_labels = [];
$monthly_completed = [];
$monthly_assigned  = [];

// Build full 12-month skeleton
for ($m = 1; $m <= 12; $m++) {
    $monthly_labels[]    = date('M', mktime(0,0,0,$m,1));
    $monthly_completed[] = 0;
    $monthly_assigned[]  = 0;
}

$res8 = $db->query("SELECT MONTH(t.due_date) as mon,
                           COUNT(*) as total,
                           SUM(t.status = 'Completed') as done
                    FROM tasks t
                    JOIN projects p ON t.project_id = p.id
                    WHERE p.department_id = $dept_id
                      AND YEAR(t.due_date) = $current_year
                    GROUP BY mon");
if ($res8) {
    while ($row = $res8->fetch_assoc()) {
        $idx = (int)$row['mon'] - 1;
        $monthly_assigned[$idx]  = (int)$row['total'];
        $monthly_completed[$idx] = (int)$row['done'];
    }
}

// ── 8a. Project Weekly Task Performance (last 8 weeks) ──────────────────────
// Uses ISO weeks (mode 3) for consistent week boundaries (Mon–Sun).
$project_weekly_labels = [];
$project_weekly_assigned = [];
$project_weekly_completed = [];
$project_week_keys = [];

for ($i = 7; $i >= 0; $i--) {
    $monday = new DateTime('monday this week');
    if ($i > 0) $monday->modify("-{$i} week");
    $key = $monday->format('oW'); // ISO year + week, e.g. 202610
    $project_week_keys[] = $key;
    $project_weekly_labels[] = 'W' . $monday->format('W');
    $project_weekly_assigned[] = 0;
    $project_weekly_completed[] = 0;
}

$week_from = (new DateTime('monday this week'))->modify('-7 week')->format('Y-m-d');
$week_to   = (new DateTime('sunday this week'))->format('Y-m-d');

$res8a = $db->query("SELECT YEARWEEK(t.due_date, 3) as yw,
                            COUNT(*) as total,
                            SUM(t.status = 'Completed') as done
                     FROM tasks t
                     JOIN projects p ON t.project_id = p.id
                     WHERE p.department_id = $dept_id
                       AND t.due_date IS NOT NULL
                       AND DATE(t.due_date) BETWEEN '$week_from' AND '$week_to'
                     GROUP BY yw");
if ($res8a) {
    while ($row = $res8a->fetch_assoc()) {
        $yw = strval($row['yw']); // numeric like 202610
        $key = substr($yw, 0, 4) . substr($yw, -2); // align to oW key
        $idx = array_search($key, $project_week_keys, true);
        if ($idx !== false) {
            $project_weekly_assigned[$idx]  = (int)$row['total'];
            $project_weekly_completed[$idx] = (int)$row['done'];
        }
    }
}

// ── 8b. Direct Monthly Task Performance (standalone tasks) ──────────────────
$direct_monthly_assigned  = array_fill(0, 12, 0);
$direct_monthly_completed = array_fill(0, 12, 0);
$res8b = $db->query("SELECT MONTH(t.due_date) as mon,
                            COUNT(*) as total,
                            SUM(t.status = 'Completed') as done
                     FROM tasks t
                     WHERE (t.project_id = 0 OR t.project_id IS NULL)
                       AND t.due_date IS NOT NULL
                       AND YEAR(t.due_date) = $current_year
                       AND (
                         t.assigned_to IN (SELECT id FROM users WHERE department_id = $dept_id)
                         OR t.id IN (SELECT task_id FROM task_assignments ta JOIN users u ON ta.user_id = u.id WHERE u.department_id = $dept_id)
                       )
                     GROUP BY mon");
if ($res8b) {
    while ($row = $res8b->fetch_assoc()) {
        $idx = (int)$row['mon'] - 1;
        $direct_monthly_assigned[$idx]  = (int)$row['total'];
        $direct_monthly_completed[$idx] = (int)$row['done'];
    }
}

// ── 8c. Direct Weekly Task Performance (last 8 weeks) ───────────────────────
// One row per task (distinct) to avoid overcounting multi-assignee tasks.
$direct_weekly_labels = [];
$direct_weekly_assigned = [];
$direct_weekly_completed = [];
$direct_week_keys = [];

for ($i = 7; $i >= 0; $i--) {
    $monday = new DateTime('monday this week');
    if ($i > 0) $monday->modify("-{$i} week");
    $key = $monday->format('oW');
    $direct_week_keys[] = $key;
    $direct_weekly_labels[] = 'W' . $monday->format('W');
    $direct_weekly_assigned[] = 0;
    $direct_weekly_completed[] = 0;
}

$res8c = $db->query("SELECT YEARWEEK(x.due_date, 3) as yw,
                            COUNT(*) as total,
                            SUM(x.status = 'Completed') as done
                     FROM (
                       SELECT DISTINCT t.id, t.status, t.due_date
                       FROM tasks t
                       LEFT JOIN users u1 ON t.assigned_to = u1.id
                       LEFT JOIN task_assignments ta ON ta.task_id = t.id
                       LEFT JOIN users u2 ON ta.user_id = u2.id
                       WHERE (t.project_id = 0 OR t.project_id IS NULL)
                         AND t.due_date IS NOT NULL
                         AND DATE(t.due_date) BETWEEN '$week_from' AND '$week_to'
                         AND (u1.department_id = $dept_id OR u2.department_id = $dept_id)
                     ) x
                     GROUP BY yw");
if ($res8c) {
    while ($row = $res8c->fetch_assoc()) {
        $yw = strval($row['yw']);
        $key = substr($yw, 0, 4) . substr($yw, -2);
        $idx = array_search($key, $direct_week_keys, true);
        if ($idx !== false) {
            $direct_weekly_assigned[$idx]  = (int)$row['total'];
            $direct_weekly_completed[$idx] = (int)$row['done'];
        }
    }
}

// ── 9. Yearly Task Performance (last 5 years) ───────────────────────────────
$yearly_labels    = [];
$yearly_completed = [];
$yearly_total     = [];
$start_year = $current_year - 4;

for ($y = $start_year; $y <= $current_year; $y++) {
    $yearly_labels[]    = (string)$y;
    $yearly_completed[] = 0;
    $yearly_total[]     = 0;
}

$res9 = $db->query("SELECT YEAR(t.due_date) as yr,
                           COUNT(*) as total,
                           SUM(t.status = 'Completed') as done
                    FROM tasks t
                    JOIN projects p ON t.project_id = p.id
                    WHERE p.department_id = $dept_id
                      AND YEAR(t.due_date) BETWEEN $start_year AND $current_year
                    GROUP BY yr");
if ($res9) {
    while ($row = $res9->fetch_assoc()) {
        $idx = (int)$row['yr'] - $start_year;
        $yearly_total[$idx]     = (int)$row['total'];
        $yearly_completed[$idx] = (int)$row['done'];
    }
}

// ── 9b. Direct Yearly Task Performance (standalone tasks) ───────────────────
$direct_yearly_total     = array_fill(0, count($yearly_labels), 0);
$direct_yearly_completed = array_fill(0, count($yearly_labels), 0);
$res9b = $db->query("SELECT YEAR(t.due_date) as yr,
                            COUNT(*) as total,
                            SUM(t.status = 'Completed') as done
                     FROM tasks t
                     WHERE (t.project_id = 0 OR t.project_id IS NULL)
                       AND t.due_date IS NOT NULL
                       AND YEAR(t.due_date) BETWEEN $start_year AND $current_year
                       AND (
                         t.assigned_to IN (SELECT id FROM users WHERE department_id = $dept_id)
                         OR t.id IN (SELECT task_id FROM task_assignments ta JOIN users u ON ta.user_id = u.id WHERE u.department_id = $dept_id)
                       )
                     GROUP BY yr");
if ($res9b) {
    while ($row = $res9b->fetch_assoc()) {
        $idx = (int)$row['yr'] - $start_year;
        if (isset($direct_yearly_total[$idx])) {
            $direct_yearly_total[$idx]     = (int)$row['total'];
            $direct_yearly_completed[$idx] = (int)$row['done'];
        }
    }
}

// ── 10. Overdue Tasks by Team Member ────────────────────────────────────────
$overdue_members = [];
$res10 = $db->query("SELECT u.full_name, u.id as user_id,
                            COUNT(*) as overdue_count,
                            MAX(t.due_date) as latest_due
                     FROM tasks t
                     JOIN projects p ON t.project_id = p.id
                     JOIN users u ON t.assigned_to = u.id
                     WHERE p.department_id = $dept_id
                       AND t.status != 'Completed'
                       AND t.due_date IS NOT NULL
                       AND (
                         (t.specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(t.due_date), t.specific_time))
                         OR
                         (t.specific_time IS NULL AND NOW() >= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
                       )
                     GROUP BY u.id, u.full_name
                     ORDER BY overdue_count DESC");
if ($res10) {
    while ($row = $res10->fetch_assoc()) $overdue_members[] = $row;
}

// ── 11. Recent Direct Tasks (latest 5 for this department) ───────────────────
$recent_direct_tasks = [];
$res11 = $db->query("SELECT t.id, t.title, t.status, t.priority, t.created_at,
                            COALESCE(u1.full_name, (SELECT u.full_name FROM task_assignments ta2 JOIN users u ON ta2.user_id = u.id WHERE ta2.task_id = t.id LIMIT 1)) AS assigned_name,
                            COALESCE(u1.avatar_url, (SELECT u.avatar_url FROM task_assignments ta2 JOIN users u ON ta2.user_id = u.id WHERE ta2.task_id = t.id LIMIT 1)) AS assigned_avatar,
                            COALESCE(d1.name, (SELECT d.name FROM task_assignments ta2 JOIN users u ON ta2.user_id = u.id JOIN departments d ON d.id = u.department_id WHERE ta2.task_id = t.id LIMIT 1)) AS dept_name,
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
                     LEFT JOIN users u1 ON t.assigned_to = u1.id
                     LEFT JOIN departments d1 ON d1.id = u1.department_id
                     LEFT JOIN task_assignments ta ON ta.task_id = t.id
                     LEFT JOIN users u2 ON ta.user_id = u2.id
                     WHERE (t.project_id = 0 OR t.project_id IS NULL)
                       AND (u1.department_id = $dept_id OR u2.department_id = $dept_id
                            OR EXISTS (SELECT 1 FROM performance_logs pl JOIN users u_pl ON u_pl.id = pl.user_id WHERE pl.task_id = t.id AND pl.reason LIKE 'assigned Direct Task%' AND u_pl.department_id = $dept_id)
                            OR EXISTS (SELECT 1 FROM task_assignment_pending tap JOIN users u_req ON u_req.id = tap.requested_by WHERE tap.task_id = t.id AND u_req.department_id = $dept_id))
                     GROUP BY t.id, t.title, t.status, t.priority, t.created_at, u1.full_name, u1.avatar_url, d1.name
                     ORDER BY t.created_at DESC
                     LIMIT 5");
if ($res11) {
    while ($row = $res11->fetch_assoc()) $recent_direct_tasks[] = $row;
}

// ── 12. Department Performance (combined project + direct) ───────────────────
$total_all_tasks = $project_tasks['total'] + $direct_tasks['total'];
$completed_all   = $project_tasks['completed'] + $direct_tasks['completed'];

$overdue_project = 0;
$res_overdue_p = $db->query("SELECT COUNT(*) as c
                             FROM tasks t
                             JOIN projects p ON t.project_id = p.id
                             WHERE p.department_id = $dept_id
                               AND t.status != 'Completed'
                               AND t.due_date IS NOT NULL
                               AND (
                                 (t.specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(t.due_date), t.specific_time))
                                 OR
                                 (t.specific_time IS NULL AND NOW() >= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
                               )");
if ($res_overdue_p) {
    $overdue_project = (int)$res_overdue_p->fetch_assoc()['c'];
}

$overdue_direct = 0;
$res_overdue_d = $db->query("SELECT COUNT(*) as c
                             FROM tasks t
                             WHERE (t.project_id = 0 OR t.project_id IS NULL)
                               AND t.status != 'Completed'
                               AND t.due_date IS NOT NULL
                               AND (
                                 (t.specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(t.due_date), t.specific_time))
                                 OR
                                 (t.specific_time IS NULL AND NOW() >= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
                               )
                               AND (
                                 t.assigned_to IN (SELECT id FROM users WHERE department_id = $dept_id)
                                 OR t.id IN (SELECT task_id FROM task_assignments ta JOIN users u ON ta.user_id = u.id WHERE u.department_id = $dept_id)
                               )");
if ($res_overdue_d) {
    $overdue_direct = (int)$res_overdue_d->fetch_assoc()['c'];
}

$dept_performance = [
    'completion_rate' => $total_all_tasks > 0 ? round(($completed_all / $total_all_tasks) * 100) : 0,
    'total_tasks'     => $total_all_tasks,
    'completed_tasks' => $completed_all,
    'active_members'  => $assigned_members,
    'overdue_count'   => $overdue_project + $overdue_direct,
];

echo json_encode([
    'success'             => true,
    'dept_name'           => $dept_name,
    'projects'            => $project_stats,
    'sub_total'           => $sub_total,
    'tasks'               => $task_stats,       // legacy (project-only) for old UI
    'project_tasks'       => $project_tasks,    // new: project task stats
    'direct_tasks'        => $direct_tasks,     // new: direct task stats
    'assigned_members'    => $assigned_members,
    'sub_projects'        => $sub_projects,
    'active_projects'     => $active_projects,
    // Legacy aggregates for backward compatibility (project-only)
    'monthly'             => [
        'labels'    => $monthly_labels,
        'completed' => $monthly_completed,
        'assigned'  => $monthly_assigned,
        'year'      => $current_year,
    ],
    'yearly'              => [
        'labels'    => $yearly_labels,
        'completed' => $yearly_completed,
        'total'     => $yearly_total,
    ],
    // New split aggregates
    'project_monthly'     => [
        'labels'    => $monthly_labels,
        'completed' => $monthly_completed,
        'assigned'  => $monthly_assigned,
        'year'      => $current_year,
    ],
    'project_weekly'      => [
        'labels'    => $project_weekly_labels,
        'completed' => $project_weekly_completed,
        'assigned'  => $project_weekly_assigned,
        'from'      => $week_from,
        'to'        => $week_to,
    ],
    'direct_monthly'      => [
        'labels'    => $monthly_labels,
        'completed' => $direct_monthly_completed,
        'assigned'  => $direct_monthly_assigned,
        'year'      => $current_year,
    ],
    'direct_weekly'       => [
        'labels'    => $direct_weekly_labels,
        'completed' => $direct_weekly_completed,
        'assigned'  => $direct_weekly_assigned,
        'from'      => $week_from,
        'to'        => $week_to,
    ],
    'project_yearly'      => [
        'labels'    => $yearly_labels,
        'completed' => $yearly_completed,
        'total'     => $yearly_total,
    ],
    'direct_yearly'       => [
        'labels'    => $yearly_labels,
        'completed' => $direct_yearly_completed,
        'total'     => $direct_yearly_total,
    ],
    'overdue_members'     => $overdue_members,
    'recent_direct_tasks' => $recent_direct_tasks,
    'dept_performance'    => $dept_performance,
]);

$db->close();
?>
