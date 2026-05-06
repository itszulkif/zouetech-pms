<?php
require_once '../includes/auth_middleware.php';
require_once __DIR__ . '/attendance_reconciliation.php';
require_role(['Super Admin', 'Department Head', 'Team Lead']);
header('Content-Type: application/json');

$db = (new Database())->connect();
$role = $_SESSION['role'] ?? '';
$dept_id = (int)($_SESSION['department_id'] ?? 0);

ensure_attendance_sheet_schema($db);
reconcile_attendance_auto_sign_out($db);

function report_for_range($db, $startDate, $endDate, $expectedDays, $role, $dept_id, $filterDeptId, $filterRole, $searchTerm)
{
    $scopeWhere = "";
    $bindTypes = "ss";
    $bindValues = [$startDate, $endDate];

    if ($role === 'Department Head') {
        $scopeWhere = " AND u.department_id = " . (int)$dept_id;
    } elseif ($filterDeptId > 0) {
        $scopeWhere .= " AND u.department_id = ?";
        $bindTypes .= "i";
        $bindValues[] = $filterDeptId;
    }

    if ($filterRole !== '') {
        $scopeWhere .= " AND u.role = ?";
        $bindTypes .= "s";
        $bindValues[] = $filterRole;
    }

    if ($searchTerm !== '') {
        $scopeWhere .= " AND (u.full_name LIKE ? OR d.name LIKE ?)";
        $bindTypes .= "ss";
        $like = '%' . $searchTerm . '%';
        $bindValues[] = $like;
        $bindValues[] = $like;
    }

    $sql = "SELECT
            u.id AS user_id,
            u.full_name,
            u.role,
            d.name AS department_name,
            SUM(CASE WHEN l.id IS NOT NULL AND l.sign_out_method <> 'Automatic' THEN 1 ELSE 0 END) AS present_days,
            SUM(CASE WHEN l.sign_out_at IS NOT NULL AND l.sign_out_method <> 'Automatic' THEN 1 ELSE 0 END) AS completed_days,
            SUM(CASE WHEN l.sign_out_method = 'Automatic' THEN 1 ELSE 0 END) AS auto_signed_out_days,
            ROUND(SUM(
                CASE
                    WHEN l.sign_out_method = 'Automatic' THEN 0
                    ELSE TIMESTAMPDIFF(SECOND, l.sign_in_at, COALESCE(l.sign_out_at, NOW()))
                END
            ) / 3600, 2) AS total_hours
        FROM users u
        LEFT JOIN departments d ON d.id = u.department_id
        LEFT JOIN attendance_sheet_logs l
            ON l.user_id = u.id
           AND l.attendance_date BETWEEN ? AND ?
        WHERE u.role IN ('Department Head', 'Team Member', 'Team Lead')
          $scopeWhere
        GROUP BY u.id, u.full_name, u.role, d.name
        ORDER BY d.name ASC, u.role ASC, u.full_name ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($bindTypes, ...$bindValues);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $r['present_days'] = (int)$r['present_days'];
        $r['completed_days'] = (int)$r['completed_days'];
        $r['auto_signed_out_days'] = (int)$r['auto_signed_out_days'];
        $r['total_hours'] = (float)$r['total_hours'];
        $r['expected_days'] = (int)$expectedDays;
        $r['absent_days'] = max(0, (int)$expectedDays - (int)$r['present_days']);
        $rows[] = $r;
    }
    $stmt->close();
    return $rows;
}

$today = date('Y-m-d');
$filterDeptId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$filterRole = trim((string)($_GET['role'] ?? ''));
$allowedRoles = ['Department Head', 'Team Lead', 'Team Member'];
if (!in_array($filterRole, $allowedRoles, true)) {
    $filterRole = '';
}
$searchTerm = trim((string)($_GET['search'] ?? ''));

$weekParam = trim((string)($_GET['week'] ?? ''));
if (!preg_match('/^\d{4}-W\d{2}$/', $weekParam)) {
    $weekParam = date('o-\WW');
}
$weekParts = explode('-W', $weekParam);
$isoYear = (int)($weekParts[0] ?? date('o'));
$isoWeek = (int)($weekParts[1] ?? date('W'));
if ($isoWeek < 1 || $isoWeek > 53) {
    $isoWeek = (int)date('W');
}
$weekDate = new DateTime();
$weekDate->setISODate($isoYear, $isoWeek, 1);
$weeklyStart = $weekDate->format('Y-m-d');
$weekDate->setISODate($isoYear, $isoWeek, 7);
$weeklyEnd = $weekDate->format('Y-m-d');

// Absent is shown only for completed (past) weeks.
$weeklyExpected = (strtotime($weeklyEnd) < strtotime($today)) ? 7 : 0;
$weekly = report_for_range($db, $weeklyStart, $weeklyEnd, $weeklyExpected, $role, $dept_id, $filterDeptId, $filterRole, $searchTerm);

$monthParam = trim((string)($_GET['month'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = date('Y-m');
}
$monthStart = $monthParam . '-01';
$monthLastDay = date('Y-m-t', strtotime($monthStart));

// Absent is shown only for completed (past) months.
$monthlyEnd = $monthLastDay;
$monthlyExpected = (strtotime($monthLastDay) < strtotime($today))
    ? (int)date('t', strtotime($monthStart))
    : 0;
$monthly = report_for_range($db, $monthStart, $monthlyEnd, $monthlyExpected, $role, $dept_id, $filterDeptId, $filterRole, $searchTerm);

echo json_encode([
    'status' => 'success',
    'weekly' => $weekly,
    'monthly' => $monthly,
    'month' => $monthParam,
    'week' => $weekParam,
    'filters' => [
        'department_id' => $filterDeptId,
        'role' => $filterRole,
        'search' => $searchTerm
    ]
]);
?>
