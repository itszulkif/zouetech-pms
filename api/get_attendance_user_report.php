<?php
require_once '../includes/auth_middleware.php';
require_once __DIR__ . '/attendance_reconciliation.php';
require_role(['Super Admin', 'Department Head', 'Team Lead']);
header('Content-Type: application/json');

$target_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$month = trim($_GET['month'] ?? '');
if ($target_user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user id']);
    exit;
}
if ($month !== '' && !preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = '';
}

$db = (new Database())->connect();
$role = $_SESSION['role'] ?? '';
$dept_id = (int)($_SESSION['department_id'] ?? 0);

ensure_attendance_sheet_schema($db);
reconcile_attendance_auto_sign_out($db, $target_user_id);

$userStmt = $db->prepare("SELECT u.id, u.full_name, u.role, u.department_id, d.name AS department_name
    FROM users u LEFT JOIN departments d ON d.id = u.department_id
    WHERE u.id = ? LIMIT 1");
$userStmt->bind_param("i", $target_user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

if ($role === 'Department Head' && (int)($user['department_id'] ?? 0) !== $dept_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized for selected user']);
    exit;
}

$rows = [];
$sql = "SELECT attendance_date, sign_in_at, sign_out_at, sign_out_method, activity_report,
    CASE
        WHEN sign_out_method = 'Automatic' THEN 'Absent'
        WHEN sign_in_at IS NULL THEN 'Not Signed In'
        WHEN sign_out_at IS NULL THEN 'Signed In'
        ELSE 'Signed Out'
    END AS attendance_status,
    ROUND(
        CASE
            WHEN sign_out_method = 'Automatic' THEN 0
            ELSE TIMESTAMPDIFF(SECOND, sign_in_at, COALESCE(sign_out_at, NOW()))
        END / 3600,
        2
    ) AS hours
    FROM attendance_sheet_logs
    WHERE user_id = ?";
$types = "i";
$params = [$target_user_id];
if ($month !== '') {
    $monthStart = $month . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $sql .= " AND attendance_date BETWEEN ? AND ?";
    $types .= "ss";
    $params[] = $monthStart;
    $params[] = $monthEnd;
}
$sql .= " ORDER BY attendance_date DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}
$stmt->close();

echo json_encode(['status' => 'success', 'user' => $user, 'entries' => $rows, 'month' => $month]);
?>
