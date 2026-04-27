<?php
require_once '../includes/auth_middleware.php';
require_once __DIR__ . '/attendance_reconciliation.php';
require_role(['Super Admin', 'Department Head', 'Team Member', 'Team Lead']);
header('Content-Type: application/json');

$db = (new Database())->connect();
$role = $_SESSION['role'] ?? '';
$dept_id = (int)($_SESSION['department_id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);
$date = trim($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
$isFutureDate = ($date > date('Y-m-d'));

ensure_attendance_sheet_schema($db);
reconcile_attendance_auto_sign_out($db);

$scopeJoin = "";
$scopeWhere = "";
if ($role === 'Department Head') {
    $scopeWhere = " AND (u.department_id = $dept_id OR u.id = $user_id)";
} elseif ($role === 'Team Member') {
    $scopeWhere = " AND u.id = $user_id";
} elseif ($role === 'Team Lead') {
    // Team Lead gets the same full attendance scope as Super Admin.
    $scopeWhere = "";
}

$sql = "SELECT
        u.id AS user_id,
        u.full_name,
        u.role,
        d.name AS department_name,
        l.sign_in_at,
        l.sign_out_at,
        l.sign_out_method,
        l.activity_report,
        CASE
            WHEN l.sign_in_at IS NULL THEN 'Not Signed In'
            WHEN l.sign_out_at IS NULL THEN 'Signed In'
            ELSE 'Signed Out'
        END AS attendance_status
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    LEFT JOIN attendance_sheet_logs l
        ON l.user_id = u.id AND l.attendance_date = ?
    WHERE u.role IN ('Department Head', 'Team Member', 'Team Lead')
    $scopeWhere
    ORDER BY d.name ASC, u.role ASC, u.full_name ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("s", $date);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    if ($isFutureDate) {
        $r['attendance_status'] = 'Future Date';
    }
    $rows[] = $r;
}
$stmt->close();

$self = null;
foreach ($rows as $r) {
    if ((int)$r['user_id'] === $user_id) {
        $self = $r;
        break;
    }
}

echo json_encode(['status' => 'success', 'data' => $rows, 'self' => $self, 'date' => $date]);
?>
