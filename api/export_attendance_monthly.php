<?php
require_once '../includes/auth_middleware.php';
require_once __DIR__ . '/export_xlsx_helper.php';
require_once __DIR__ . '/attendance_reconciliation.php';
require_role(['Super Admin', 'Department Head', 'Team Lead']);

$db = (new Database())->connect();
$role = $_SESSION['role'] ?? '';
$dept_id = (int)($_SESSION['department_id'] ?? 0);

ensure_attendance_sheet_schema($db);
reconcile_attendance_auto_sign_out($db);

$month = trim($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}
$filter_dept_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$filter_role = trim((string)($_GET['role'] ?? ''));
$allowed_roles = ['Department Head', 'Team Lead', 'Team Member'];
if (!in_array($filter_role, $allowed_roles, true)) {
    $filter_role = '';
}
$search = trim((string)($_GET['search'] ?? ''));

$monthStart = $month . '-01';
$today = date('Y-m-d');
$monthLastDay = date('Y-m-t', strtotime($monthStart));
$monthEnd = ($monthLastDay < $today) ? $monthLastDay : $today;
$scopeWhere = "";
$bindTypes = "ss";
$bindValues = [$monthStart, $monthEnd];
if ($role === 'Department Head') {
    $scopeWhere = " AND u.department_id = " . $dept_id;
} elseif ($filter_dept_id > 0) {
    $scopeWhere .= " AND u.department_id = ?";
    $bindTypes .= "i";
    $bindValues[] = $filter_dept_id;
}
if ($filter_role !== '') {
    $scopeWhere .= " AND u.role = ?";
    $bindTypes .= "s";
    $bindValues[] = $filter_role;
}
if ($search !== '') {
    $scopeWhere .= " AND (u.full_name LIKE ? OR d.name LIKE ?)";
    $bindTypes .= "ss";
    $like = '%' . $search . '%';
    $bindValues[] = $like;
    $bindValues[] = $like;
}

$summarySql = "SELECT
        u.id,
        u.full_name,
        u.role,
        d.name AS department_name,
        COUNT(l.id) AS present_days,
        SUM(CASE WHEN l.sign_out_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_days,
        ROUND(SUM(TIMESTAMPDIFF(SECOND, l.sign_in_at, COALESCE(l.sign_out_at, NOW()))) / 3600, 2) AS total_hours
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    LEFT JOIN attendance_sheet_logs l ON l.user_id = u.id
        AND l.attendance_date BETWEEN ? AND ?
    WHERE u.role IN ('Department Head', 'Team Member', 'Team Lead')
      $scopeWhere
    GROUP BY u.id, u.full_name, u.role, d.name
    ORDER BY d.name ASC, u.role ASC, u.full_name ASC";
$stmt = $db->prepare($summarySql);
$stmt->bind_param($bindTypes, ...$bindValues);
$stmt->execute();
$summaryRes = $stmt->get_result();
$summaryRows = [];
while ($r = $summaryRes->fetch_assoc()) {
    $summaryRows[] = $r;
}
$stmt->close();

$detailSql = "SELECT
        DATE_FORMAT(l.attendance_date, '%Y-%m') AS month_key,
        u.full_name,
        u.role,
        d.name AS department_name,
        l.attendance_date,
        l.sign_in_at,
        l.sign_out_at,
        l.sign_out_method,
        ROUND(TIMESTAMPDIFF(SECOND, l.sign_in_at, COALESCE(l.sign_out_at, NOW())) / 3600, 2) AS hours,
        l.activity_report
    FROM attendance_sheet_logs l
    JOIN users u ON u.id = l.user_id
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE l.attendance_date BETWEEN ? AND ?
      AND u.role IN ('Department Head', 'Team Member', 'Team Lead')
      $scopeWhere
    ORDER BY l.attendance_date ASC, d.name ASC, u.full_name ASC";
$dstmt = $db->prepare($detailSql);
$dstmt->bind_param($bindTypes, ...$bindValues);
$dstmt->execute();
$detailRes = $dstmt->get_result();
$detailRows = [];
while ($r = $detailRes->fetch_assoc()) {
    $detailRows[] = $r;
}
$dstmt->close();

$rows = [];
$rows[] = ['Monthly Attendance Summary - ' . $month];
$rows[] = ['Name', 'Department', 'Role', 'Present Days', 'Completed Sign-Out Days', 'Total Hours', 'Month Category'];
foreach ($summaryRows as $r) {
    $rows[] = [
        $r['full_name'] ?? '',
        $r['department_name'] ?? '-',
        $r['role'] ?? '',
        (int)($r['present_days'] ?? 0),
        (int)($r['completed_days'] ?? 0),
        (float)($r['total_hours'] ?? 0),
        $month
    ];
}

$rows[] = [];
$rows[] = ['Daily Attendance Detail - ' . $month];
$rows[] = ['Month Category', 'Name', 'Department', 'Role', 'Date', 'Sign-In', 'Sign-Out', 'Sign-Out Method', 'Hours', 'Activity Report'];
foreach ($detailRows as $r) {
    $rows[] = [
        $r['month_key'] ?? $month,
        $r['full_name'] ?? '',
        $r['department_name'] ?? '-',
        $r['role'] ?? '',
        $r['attendance_date'] ?? '',
        $r['sign_in_at'] ?? '',
        $r['sign_out_at'] ?? '',
        $r['sign_out_method'] ?? 'Manual',
        (float)($r['hours'] ?? 0),
        $r['activity_report'] ?? ''
    ];
}

try {
    pms_stream_xlsx('attendance_monthly_' . $month . '.xlsx', 'Monthly Export', $rows, [2, count($summaryRows) + 5]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to generate Excel export: ' . $e->getMessage();
    exit;
}
?>
