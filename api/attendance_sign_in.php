<?php
require_once '../includes/auth_middleware.php';
require_once __DIR__ . '/attendance_reconciliation.php';
require_role(['Department Head', 'Team Member', 'Team Lead']);
header('Content-Type: application/json');

$db = (new Database())->connect();
$user_id = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? '';
$dept_id = (int)($_SESSION['department_id'] ?? 0);

ensure_attendance_sheet_schema($db);
reconcile_attendance_auto_sign_out($db, $user_id);

$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

$stmt = $db->prepare("SELECT id, sign_in_at, sign_out_at FROM attendance_sheet_logs WHERE user_id = ? AND attendance_date = ? LIMIT 1");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing && !empty($existing['sign_in_at'])) {
    echo json_encode(['status' => 'error', 'message' => 'You are already signed in for today.']);
    exit;
}

if ($existing) {
    $up = $db->prepare("UPDATE attendance_sheet_logs SET sign_in_at = ?, sign_out_at = NULL, sign_out_method = 'Manual', activity_report = NULL WHERE id = ?");
    $eid = (int)$existing['id'];
    $up->bind_param("si", $now, $eid);
    $up->execute();
    $up->close();
} else {
    $ins = $db->prepare("INSERT INTO attendance_sheet_logs (user_id, role_snapshot, department_id_snapshot, attendance_date, sign_in_at) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param("isiss", $user_id, $role, $dept_id, $today, $now);
    $ins->execute();
    $ins->close();
}

echo json_encode(['status' => 'success', 'message' => 'Sign-in recorded successfully.', 'sign_in_at' => $now]);
?>
