<?php
require_once '../includes/auth_middleware.php';
require_once '../db_connect.php';
require_once __DIR__ . '/export_xlsx_helper.php';
check_login();
require_role(['Super Admin']);

$db = (new Database())->connect();

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

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$search = trim($_GET['search'] ?? '');
$sort = trim($_GET['sort'] ?? 'workload');
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

$where = "WHERE u.role IN ('Department Head','Team Member')";
if ($department_id > 0) {
    $where .= " AND u.department_id = " . $department_id;
}
if ($search !== '') {
    $esc = $db->real_escape_string($search);
    $where .= " AND (u.full_name LIKE '%$esc%' OR d.name LIKE '%$esc%')";
}

$rows = [];
$sql = "SELECT
        u.id,
        u.full_name,
        u.role,
        d.name AS department_name,
        u.performance_score,
        ROUND((
            SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, l.login_at, COALESCE(l.logout_at, NOW()))), 0)
            FROM user_attendance_logs l
            WHERE l.user_id = u.id AND l.login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ) / 3600, 2) AS weekly_hours,
        ROUND((
            SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, l.login_at, COALESCE(l.logout_at, NOW()))), 0)
            FROM user_attendance_logs l
            WHERE l.user_id = u.id AND l.login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) / 3600, 2) AS monthly_hours,
        (
            SELECT COUNT(*) FROM tasks t
            WHERE t.status IN ('Pending','Pending Approval','In Progress','Review')
            AND (
                t.assigned_to = u.id
                OR EXISTS (SELECT 1 FROM task_assignments ta WHERE ta.task_id = t.id AND ta.user_id = u.id)
            )
        ) AS active_tasks,
        (
            SELECT COUNT(*) FROM tasks t
            WHERE t.status IN ('Pending','Pending Approval')
            AND (
                t.assigned_to = u.id
                OR EXISTS (SELECT 1 FROM task_assignments ta WHERE ta.task_id = t.id AND ta.user_id = u.id)
            )
        ) AS pending_tasks,
        (
            SELECT COUNT(*) FROM tasks t
            WHERE t.status IN ('Pending','Pending Approval','In Progress','Review')
              AND t.due_date IS NOT NULL
              AND (
                (t.specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(t.due_date), t.specific_time))
                OR
                (t.specific_time IS NULL AND NOW() >= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
              )
              AND (
                t.assigned_to = u.id
                OR EXISTS (SELECT 1 FROM task_assignments ta WHERE ta.task_id = t.id AND ta.user_id = u.id)
            )
        ) AS overdue_tasks,
        (
            SELECT COUNT(*) FROM tasks t
            WHERE t.status = 'Completed'
              AND t.completed_at >= DATE_SUB(NOW(), INTERVAL " . (int)$period_days . " DAY)
              AND (
                t.assigned_to = u.id
                OR EXISTS (SELECT 1 FROM task_assignments ta WHERE ta.task_id = t.id AND ta.user_id = u.id)
            )
        ) AS completed_period,
        (
            SELECT l.daily_summary
            FROM user_attendance_logs l
            WHERE l.user_id = u.id AND l.daily_summary IS NOT NULL AND l.daily_summary <> ''
            ORDER BY l.id DESC LIMIT 1
        ) AS latest_summary
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    $where";

if ($sort === 'name') {
    $sql .= " ORDER BY u.full_name ASC";
} elseif ($sort === 'overdue') {
    $sql .= " ORDER BY overdue_tasks DESC, u.full_name ASC";
} elseif ($sort === 'score') {
    $sql .= " ORDER BY u.performance_score DESC, u.full_name ASC";
} else {
    $sql .= " ORDER BY active_tasks DESC, u.full_name ASC";
}

$result = $db->query($sql);
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
}

$excelRows = [];
$periodHoursLabel = $period === 'monthly' ? 'Monthly Working Hours' : 'Weekly Working Hours';
$periodCompletedLabel = 'Completed (' . $period_days . 'd)';
$excelRows[] = [
    'Name',
    'Department',
    'Role',
    $periodHoursLabel,
    'Performance Score',
    'Active Tasks',
    'Pending Tasks',
    'Overdue Tasks',
    $periodCompletedLabel,
    'Month Total Tasks (' . $month . ')',
    'Month Completed (' . $month . ')',
    'Month Missed (' . $month . ')',
    'Month On-Time (' . $month . ')',
    'Month Late (' . $month . ')',
    'Month Avg Completion Hours (' . $month . ')',
    'Month Points Earned (' . $month . ')',
    'Month Net Points (' . $month . ')',
    'Latest Daily Summary'
];

foreach ($rows as $r) {
    $uid = (int)($r['id'] ?? 0);
    $monthTotalTasks = 0;
    $monthCompleted = 0;
    $monthMissed = 0;
    $monthOnTime = 0;
    $monthLate = 0;
    $monthAvgHours = 0.0;
    $monthPointsEarned = 0;
    $monthNetPoints = 0;

    if ($uid > 0) {
        $metricsSql = "SELECT
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
                SELECT ta.user_id, ta.task_id FROM task_assignments ta WHERE ta.user_id = ?
                UNION
                SELECT t.assigned_to AS user_id, t.id AS task_id FROM tasks t WHERE t.assigned_to = ?
            ) m
            INNER JOIN tasks t ON t.id = m.task_id";
        $metricsStmt = $db->prepare($metricsSql);
        $metricsStmt->bind_param(
            "ssssssssssssii",
            $month_start, $month_end,
            $month_start, $month_end,
            $month_start, $month_end,
            $month_start, $month_end,
            $month_start, $month_end,
            $month_start, $month_end,
            $uid, $uid
        );
        $metricsStmt->execute();
        $m = $metricsStmt->get_result()->fetch_assoc();
        $metricsStmt->close();

        if ($m) {
            $monthTotalTasks = (int)($m['total_tasks_month'] ?? 0);
            $monthCompleted = (int)($m['completed_tasks_month'] ?? 0);
            $monthMissed = (int)($m['missed_tasks_month'] ?? 0);
            $monthOnTime = (int)($m['on_time_completed_month'] ?? 0);
            $monthLate = (int)($m['late_completed_month'] ?? 0);
            $monthAvgHours = (float)($m['avg_completion_hours_month'] ?? 0);
        }

        $pointsStmt = $db->prepare("SELECT
                SUM(score_change) AS net_points_month,
                SUM(CASE WHEN score_change > 0 THEN score_change ELSE 0 END) AS points_earned_month
            FROM performance_logs
            WHERE user_id = ?
              AND DATE(timestamp) BETWEEN ? AND ?");
        $pointsStmt->bind_param("iss", $uid, $month_start, $month_end);
        $pointsStmt->execute();
        $p = $pointsStmt->get_result()->fetch_assoc();
        $pointsStmt->close();
        if ($p) {
            $monthNetPoints = (int)($p['net_points_month'] ?? 0);
            $monthPointsEarned = (int)($p['points_earned_month'] ?? 0);
        }
    }

    $excelRows[] = [
        $r['full_name'] ?? '',
        $r['department_name'] ?? '-',
        $r['role'] ?? '',
        (float)($period === 'monthly' ? ($r['monthly_hours'] ?? 0) : ($r['weekly_hours'] ?? 0)),
        (float)($r['performance_score'] ?? 0),
        (int)($r['active_tasks'] ?? 0),
        (int)($r['pending_tasks'] ?? 0),
        (int)($r['overdue_tasks'] ?? 0),
        (int)($r['completed_period'] ?? 0),
        $monthTotalTasks,
        $monthCompleted,
        $monthMissed,
        $monthOnTime,
        $monthLate,
        $monthAvgHours,
        $monthPointsEarned,
        $monthNetPoints,
        $r['latest_summary'] ?? ''
    ];
}

try {
    pms_stream_xlsx('attendance_activity_report_' . $period . '_' . date('Y-m-d') . '.xlsx', 'Organization Export', $excelRows, [1]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to generate Excel export: ' . $e->getMessage();
    exit;
}
?>
