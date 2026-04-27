<?php
require_once '../includes/auth_middleware.php';
require_once __DIR__ . '/export_xlsx_helper.php';
require_once __DIR__ . '/attendance_reconciliation.php';
require_role(['Super Admin', 'Department Head', 'Team Lead']);

$db = (new Database())->connect();
$role = $_SESSION['role'] ?? '';
$dept_id = (int)($_SESSION['department_id'] ?? 0);

ensure_attendance_sheet_schema($db);

$target_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$month = trim($_GET['month'] ?? '');
if ($target_user_id <= 0) {
    http_response_code(400);
    exit('Invalid user id');
}
if ($month !== '' && !preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = '';
}

$userStmt = $db->prepare("SELECT u.id, u.full_name, u.role, u.department_id, d.name AS department_name
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE u.id = ?
    LIMIT 1");
$userStmt->bind_param("i", $target_user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    http_response_code(404);
    exit('User not found');
}

if ($role === 'Department Head' && (int)($user['department_id'] ?? 0) !== $dept_id) {
    http_response_code(403);
    exit('Unauthorized for selected user');
}
reconcile_attendance_auto_sign_out($db, $target_user_id);

$sql = "SELECT attendance_date, sign_in_at, sign_out_at, sign_out_method, activity_report,
    ROUND(TIMESTAMPDIFF(SECOND, sign_in_at, COALESCE(sign_out_at, NOW())) / 3600, 2) AS hours
    FROM attendance_sheet_logs
    WHERE user_id = ?";
$types = "i";
$params = [$target_user_id];
$label = 'all-time';
if ($month !== '') {
    $monthStart = $month . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $sql .= " AND attendance_date BETWEEN ? AND ?";
    $types .= "ss";
    $params[] = $monthStart;
    $params[] = $monthEnd;
    $label = $month;
}
$sql .= " ORDER BY attendance_date DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}
$stmt->close();

$safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string)($user['full_name'] ?? 'user'));

$excelRows = [];
$excelRows[] = ['Attendance Report - ' . ($user['full_name'] ?? 'User') . ' (' . $label . ')'];
$excelRows[] = ['Name', 'Department', 'Role', 'Date', 'Sign-In', 'Sign-Out', 'Sign-Out Method', 'Hours', 'Activity Report'];
foreach ($rows as $r) {
    $excelRows[] = [
        $user['full_name'] ?? '',
        $user['department_name'] ?? '-',
        $user['role'] ?? '',
        $r['attendance_date'] ?? '',
        $r['sign_in_at'] ?? '',
        $r['sign_out_at'] ?? '',
        $r['sign_out_method'] ?? 'Manual',
        (float)($r['hours'] ?? 0),
        $r['activity_report'] ?? ''
    ];
}

try {
    pms_stream_xlsx('attendance_user_' . $safeName . '_' . $label . '.xlsx', 'User Export', $excelRows, [2]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to generate Excel export: ' . $e->getMessage();
    exit;
}
?>
