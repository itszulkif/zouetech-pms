<?php
// api/get_workload_overview.php - Workload for Team Members, Team Leads, and Department Heads (for Performance Overview page)
require_once '../includes/auth_middleware.php';
require_once '../db_connect.php';
header('Content-Type: application/json');

check_login();
require_role(['Super Admin', 'Department Head']);

$database = new Database();
$db = $database->connect();

$user_role = $_SESSION['role'] ?? '';
$dept_id = (int)($_SESSION['department_id'] ?? 0);
$filter_dept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$period = isset($_GET['period']) ? strtolower(trim((string)$_GET['period'])) : 'weekly';
if (!in_array($period, ['weekly', 'monthly'], true)) {
    $period = 'weekly';
}
$period_days = ($period === 'monthly') ? 30 : 7;
$month = isset($_GET['month']) ? trim((string)$_GET['month']) : date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}
$month_start = $month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

// Department scoping: Department Head sees only their department; Super Admin sees all (or filtered by ?department_id=)
$dept_filter = "";
if ($user_role === 'Department Head' && $dept_id > 0) {
    $dept_filter = " AND u.department_id = $dept_id";
} elseif ($user_role === 'Super Admin' && $filter_dept > 0) {
    $dept_filter = " AND u.department_id = $filter_dept";
}

// All Team Members, Team Leads, and Department Heads (with department filter for Dept Head)
$users = [];
$stmt = $db->prepare("
    SELECT
        u.id,
        u.full_name,
        u.role,
        u.avatar_url,
        CASE
            WHEN COALESCE(pl.valid_log_count, 0) = 0 THEN 0
            ELSE COALESCE(pl.dynamic_score, 0)
        END AS performance_score,
        u.department_id,
        d.name AS department_name
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    LEFT JOIN (
        SELECT
            pl.user_id,
            COUNT(*) AS valid_log_count,
            COALESCE(SUM(CASE WHEN pl.score_change > 0 THEN pl.score_change ELSE 0 END), 0) AS dynamic_score
        FROM performance_logs pl
        INNER JOIN tasks t ON t.id = pl.task_id
        WHERE t.status IN ('Completed', 'Missed')
        GROUP BY user_id
    ) pl ON pl.user_id = u.id
    WHERE u.role IN ('Department Head', 'Team Lead', 'Team Member') $dept_filter
    ORDER BY d.name ASC, u.role ASC, u.full_name ASC
");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $row['tasks'] = [];
    $row['pending_count'] = 0;
    $row['total_active'] = 0;
    $row['overdue_count'] = 0;
    $row['project_count'] = 0;
    $row['direct_count'] = 0;
    $row['completed_count'] = 0;
    $row['weekly_hours'] = 0;
    $row['monthly_hours'] = 0;
    $row['activity_entries_30d'] = 0;
    $row['last_summary'] = null;
    $row['performance_score'] = (int)($row['performance_score'] ?? 0);
    $users[$row['id']] = $row;
}
$stmt->close();

if (empty($users)) {
    echo json_encode([
        'status' => 'success',
        'data' => [],
        'summary' => ['total_members' => 0, 'total_active_tasks' => 0, 'overloaded_count' => 0, 'avg_performance_score' => 0],
        'period' => $period,
        'period_days' => $period_days
    ]);
    exit;
}

$user_ids = array_keys($users);
$placeholders = implode(',', array_fill(0, count($user_ids), '?'));
$types = str_repeat('i', count($user_ids));

// Keep both project/direct tasks dynamically marked missed before workload aggregation.
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

// Active tasks: not Completed, not Missed (so we show current workload)
$active_statuses = "'Pending','Pending Approval','In Progress','Review'";

// Tasks from task_assignments
$sql = "
    SELECT ta.user_id, t.id AS task_id, t.title, t.status, t.due_date, t.specific_time, t.priority, t.project_id
    FROM task_assignments ta
    INNER JOIN tasks t ON t.id = ta.task_id
    WHERE ta.user_id IN ($placeholders)
    AND t.status IN ($active_statuses)
";
$st = $db->prepare($sql);
$st->bind_param($types, ...$user_ids);
$st->execute();
$rs = $st->get_result();
while ($r = $rs->fetch_assoc()) {
    $uid = (int)$r['user_id'];
    if (isset($users[$uid])) {
        $task = [
            'id' => (int)$r['task_id'],
            'title' => $r['title'],
            'status' => $r['status'],
            'due_date' => $r['due_date'],
            'priority' => $r['priority'] ?: 'Medium',
            'project_id' => (int)($r['project_id'] ?? 0),
        ];
        $users[$uid]['tasks'][$r['task_id']] = $task;
    }
}
$st->close();

// Tasks from tasks.assigned_to (project tasks often use this)
$sql2 = "
    SELECT t.assigned_to AS user_id, t.id AS task_id, t.title, t.status, t.due_date, t.specific_time, t.priority, t.project_id
    FROM tasks t
    WHERE t.assigned_to IN ($placeholders)
    AND t.status IN ($active_statuses)
";
$st2 = $db->prepare($sql2);
$st2->bind_param($types, ...$user_ids);
$st2->execute();
$rs2 = $st2->get_result();
while ($r = $rs2->fetch_assoc()) {
    $uid = (int)$r['user_id'];
    if (isset($users[$uid]) && !isset($users[$uid]['tasks'][$r['task_id']])) {
        $task = [
            'id' => (int)$r['task_id'],
            'title' => $r['title'],
            'status' => $r['status'],
            'due_date' => $r['due_date'],
            'priority' => $r['priority'] ?: 'Medium',
            'project_id' => (int)($r['project_id'] ?? 0),
        ];
        $users[$uid]['tasks'][$r['task_id']] = $task;
    }
}
$st2->close();

$OVERLOAD_THRESHOLD = 5;

// Convert to indexed array and add counts
$out = [];
$total_active_tasks = 0;
$overloaded_count = 0;
$score_sum = 0;
$score_count = 0;

foreach ($users as $uid => $u) {
    $tasks = array_values($u['tasks']);
    usort($tasks, function ($a, $b) {
        $da = $a['due_date'] ?? '';
        $db = $b['due_date'] ?? '';
        if ($da !== $db) return strcmp($da, $db);
        return ($a['id'] <=> $b['id']);
    });
    $u['tasks'] = $tasks;
    $u['total_active'] = count($tasks);
    $u['pending_count'] = count(array_filter($tasks, function ($t) {
        return in_array($t['status'], ['Pending', 'Pending Approval']);
    }));

    $nowTs = time();
    $u['overdue_count'] = count(array_filter($tasks, function ($t) use ($nowTs) {
        if (empty($t['due_date'])) {
            return false;
        }
        $dateOnly = date('Y-m-d', strtotime((string)$t['due_date']));
        $deadline = !empty($t['specific_time'])
            ? strtotime($dateOnly . ' ' . $t['specific_time'])
            : strtotime($dateOnly . ' 23:59:59');
        return $deadline !== false && $nowTs > $deadline;
    }));

    $u['project_count'] = count(array_filter($tasks, function ($t) {
        return !empty($t['project_id']);
    }));
    $u['direct_count'] = $u['total_active'] - $u['project_count'];

    if ($u['total_active'] === 0) {
        $u['overload_status'] = 'Free';
    } elseif ($u['total_active'] < $OVERLOAD_THRESHOLD) {
        $u['overload_status'] = 'Moderate';
    } else {
        $u['overload_status'] = 'Overloaded';
        $overloaded_count++;
    }

    $total_active_tasks += $u['total_active'];
    $score_sum += $u['performance_score'];
    $score_count++;

    $out[] = $u;
}

// Attendance-derived weekly/monthly hours + latest daily summary
if (!empty($user_ids)) {
    @$db->query("CREATE TABLE IF NOT EXISTS user_attendance_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        role_snapshot VARCHAR(50) NOT NULL,
        department_id_snapshot INT(11) DEFAULT NULL,
        login_at DATETIME NOT NULL,
        logout_at DATETIME DEFAULT NULL,
        daily_summary TEXT DEFAULT NULL,
        login_ip VARCHAR(64) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_att_user (user_id),
        KEY idx_att_login (login_at),
        KEY idx_att_logout (logout_at),
        CONSTRAINT fk_att_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ph2 = implode(',', array_fill(0, count($user_ids), '?'));
    $ty2 = str_repeat('i', count($user_ids));

    $hoursSql = "SELECT
            l.user_id,
            ROUND(SUM(CASE
                WHEN l.login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                THEN TIMESTAMPDIFF(SECOND, l.login_at, COALESCE(l.logout_at, NOW()))
                ELSE 0 END) / 3600, 2) AS weekly_hours,
            ROUND(SUM(CASE
                WHEN l.login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                THEN TIMESTAMPDIFF(SECOND, l.login_at, COALESCE(l.logout_at, NOW()))
                ELSE 0 END) / 3600, 2) AS monthly_hours,
            SUM(CASE WHEN l.login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS entries_30d
        FROM user_attendance_logs l
        WHERE l.user_id IN ($ph2)
        GROUP BY l.user_id";
    $hoursStmt = $db->prepare($hoursSql);
    $hoursStmt->bind_param($ty2, ...$user_ids);
    $hoursStmt->execute();
    $hoursRes = $hoursStmt->get_result();
    $hoursMap = [];
    while ($r = $hoursRes->fetch_assoc()) {
        $hoursMap[(int)$r['user_id']] = [
            'weekly_hours' => (float)$r['weekly_hours'],
            'monthly_hours' => (float)$r['monthly_hours'],
            'entries_30d' => (int)$r['entries_30d'],
        ];
    }
    $hoursStmt->close();

    $summarySql = "SELECT l1.user_id, l1.daily_summary
        FROM user_attendance_logs l1
        INNER JOIN (
            SELECT user_id, MAX(id) AS max_id
            FROM user_attendance_logs
            WHERE user_id IN ($ph2) AND daily_summary IS NOT NULL AND daily_summary <> ''
            GROUP BY user_id
        ) l2 ON l1.user_id = l2.user_id AND l1.id = l2.max_id";
    $sumStmt = $db->prepare($summarySql);
    $sumStmt->bind_param($ty2, ...$user_ids);
    $sumStmt->execute();
    $sumRes = $sumStmt->get_result();
    $summaryMap = [];
    while ($r = $sumRes->fetch_assoc()) {
        $summaryMap[(int)$r['user_id']] = $r['daily_summary'];
    }
    $sumStmt->close();

    foreach ($out as &$u) {
        $uid = (int)$u['id'];
        if (isset($hoursMap[$uid])) {
            $u['weekly_hours'] = $hoursMap[$uid]['weekly_hours'];
            $u['monthly_hours'] = $hoursMap[$uid]['monthly_hours'];
            $u['period_hours'] = ($period === 'monthly') ? $u['monthly_hours'] : $u['weekly_hours'];
            $u['activity_entries_30d'] = $hoursMap[$uid]['entries_30d'];
        } else {
            $u['period_hours'] = 0;
        }
        if (isset($summaryMap[$uid])) {
            $u['last_summary'] = $summaryMap[$uid];
        }
    }
    unset($u);
}

// Completed count (last 30 days) - batch query
if (!empty($user_ids)) {
    $ph = implode(',', array_fill(0, count($user_ids), '?'));
    $ty = str_repeat('i', count($user_ids));
    $since = date('Y-m-d H:i:s', strtotime('-' . $period_days . ' days'));
    $sql3 = "SELECT ta.user_id, COUNT(DISTINCT t.id) as cnt FROM task_assignments ta
             INNER JOIN tasks t ON t.id = ta.task_id
             WHERE ta.user_id IN ($ph) AND t.status = 'Completed' AND t.completed_at >= ?
             GROUP BY ta.user_id";
    $st3 = $db->prepare($sql3);
    $params = array_merge($user_ids, [$since]);
    $st3->bind_param($ty . 's', ...$params);
    $st3->execute();
    $rs3 = $st3->get_result();
    $completed_by_user = [];
    while ($r = $rs3->fetch_assoc()) {
        $completed_by_user[(int)$r['user_id']] = (int)$r['cnt'];
    }
    $st3->close();

    $sql4 = "SELECT t.assigned_to as user_id, COUNT(*) as cnt FROM tasks t
             WHERE t.assigned_to IN ($ph) AND t.status = 'Completed' AND t.completed_at >= ?
             GROUP BY t.assigned_to";
    $st4 = $db->prepare($sql4);
    $st4->bind_param($ty . 's', ...array_merge($user_ids, [$since]));
    $st4->execute();
    $rs4 = $st4->get_result();
    while ($r = $rs4->fetch_assoc()) {
        $uid = (int)$r['user_id'];
        $completed_by_user[$uid] = ($completed_by_user[$uid] ?? 0) + (int)$r['cnt'];
    }
    $st4->close();

    foreach ($out as &$u) {
        $u['completed_count'] = $completed_by_user[$u['id']] ?? 0;
        $u['completed_period_days'] = $period_days;
    }
    unset($u);
}

// Monthly historical record for selected month (date-wise filter support)
if (!empty($user_ids)) {
    $ph = implode(',', array_fill(0, count($user_ids), '?'));
    $ty = str_repeat('i', count($user_ids));

    $monthTaskSql = "SELECT
            m.user_id,
            COUNT(DISTINCT CASE WHEN DATE(t.created_at) BETWEEN ? AND ? THEN t.id END) AS total_tasks_month,
            COUNT(DISTINCT CASE WHEN t.status = 'Completed' AND t.completed_at IS NOT NULL AND DATE(t.completed_at) BETWEEN ? AND ? THEN t.id END) AS completed_tasks_month,
            COUNT(DISTINCT CASE WHEN t.status = 'Missed' AND t.due_date IS NOT NULL AND DATE(t.due_date) BETWEEN ? AND ? THEN t.id END) AS missed_tasks_month,
            COUNT(DISTINCT CASE WHEN t.status = 'Completed' AND t.completed_at IS NOT NULL AND DATE(t.completed_at) BETWEEN ? AND ?
                AND (
                    (t.specific_time IS NOT NULL AND t.completed_at <= TIMESTAMP(DATE(t.due_date), t.specific_time))
                    OR
                    (t.specific_time IS NULL AND t.completed_at <= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
                ) THEN t.id END) AS on_time_completed_month,
            COUNT(DISTINCT CASE WHEN t.status = 'Completed' AND t.completed_at IS NOT NULL AND DATE(t.completed_at) BETWEEN ? AND ?
                AND (
                    (t.specific_time IS NOT NULL AND t.completed_at > TIMESTAMP(DATE(t.due_date), t.specific_time))
                    OR
                    (t.specific_time IS NULL AND t.completed_at > DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
                ) THEN t.id END) AS late_completed_month,
            ROUND(AVG(CASE WHEN t.status = 'Completed' AND t.completed_at IS NOT NULL AND DATE(t.completed_at) BETWEEN ? AND ?
                THEN TIMESTAMPDIFF(HOUR, t.created_at, t.completed_at) END), 2) AS avg_completion_hours_month
        FROM (
            SELECT ta.user_id, ta.task_id FROM task_assignments ta
            UNION
            SELECT t.assigned_to AS user_id, t.id AS task_id FROM tasks t WHERE t.assigned_to IS NOT NULL
        ) m
        INNER JOIN tasks t ON t.id = m.task_id
        WHERE m.user_id IN ($ph)
        GROUP BY m.user_id";
    $monthTaskStmt = $db->prepare($monthTaskSql);
    $monthTaskParams = array_merge(
        [$month_start, $month_end, $month_start, $month_end, $month_start, $month_end, $month_start, $month_end, $month_start, $month_end, $month_start, $month_end],
        $user_ids
    );
    $monthTaskStmt->bind_param('ssssssssssss' . $ty, ...$monthTaskParams);
    $monthTaskStmt->execute();
    $monthTaskRes = $monthTaskStmt->get_result();
    $monthTaskMap = [];
    while ($r = $monthTaskRes->fetch_assoc()) {
        $monthTaskMap[(int)$r['user_id']] = [
            'total_tasks_month' => (int)($r['total_tasks_month'] ?? 0),
            'completed_tasks_month' => (int)($r['completed_tasks_month'] ?? 0),
            'missed_tasks_month' => (int)($r['missed_tasks_month'] ?? 0),
            'on_time_completed_month' => (int)($r['on_time_completed_month'] ?? 0),
            'late_completed_month' => (int)($r['late_completed_month'] ?? 0),
            'avg_completion_hours_month' => (float)($r['avg_completion_hours_month'] ?? 0),
        ];
    }
    $monthTaskStmt->close();

    $pointsSql = "SELECT
            pl.user_id,
            COALESCE(SUM(CASE WHEN pl.score_change > 0 THEN pl.score_change ELSE 0 END), 0) AS net_points_month,
            COALESCE(SUM(CASE WHEN pl.score_change > 0 THEN pl.score_change ELSE 0 END), 0) AS points_earned_month
        FROM performance_logs pl
        INNER JOIN tasks t ON t.id = pl.task_id
        WHERE pl.user_id IN ($ph)
          AND t.status IN ('Completed', 'Missed')
          AND DATE(pl.timestamp) BETWEEN ? AND ?
        GROUP BY pl.user_id";
    $pointsStmt = $db->prepare($pointsSql);
    $pointsParams = array_merge($user_ids, [$month_start, $month_end]);
    $pointsStmt->bind_param($ty . 'ss', ...$pointsParams);
    $pointsStmt->execute();
    $pointsRes = $pointsStmt->get_result();
    $pointsMap = [];
    while ($r = $pointsRes->fetch_assoc()) {
        $pointsMap[(int)$r['user_id']] = [
            'net_points_month' => (int)($r['net_points_month'] ?? 0),
            'points_earned_month' => (int)($r['points_earned_month'] ?? 0),
        ];
    }
    $pointsStmt->close();

    foreach ($out as &$u) {
        $uid = (int)$u['id'];
        $m = $monthTaskMap[$uid] ?? null;
        $p = $pointsMap[$uid] ?? null;
        $u['month'] = $month;
        $u['month_total_tasks'] = $m['total_tasks_month'] ?? 0;
        $u['month_completed_tasks'] = $m['completed_tasks_month'] ?? 0;
        $u['month_missed_tasks'] = $m['missed_tasks_month'] ?? 0;
        $u['month_on_time_completed'] = $m['on_time_completed_month'] ?? 0;
        $u['month_late_completed'] = $m['late_completed_month'] ?? 0;
        $u['month_avg_completion_hours'] = $m['avg_completion_hours_month'] ?? 0;
        $u['month_points_earned'] = $p['points_earned_month'] ?? 0;
        $u['month_net_points'] = $p['net_points_month'] ?? 0;
    }
    unset($u);
}

$summary = [
    'total_members' => count($out),
    'total_active_tasks' => $total_active_tasks,
    'overloaded_count' => $overloaded_count,
    'avg_performance_score' => $score_count > 0 ? round($score_sum / $score_count, 1) : 0,
];

echo json_encode([
    'status' => 'success',
    'data' => $out,
    'summary' => $summary,
    'period' => $period,
    'period_days' => $period_days,
    'month' => $month
]);
