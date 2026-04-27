<?php
require_once '../includes/auth_middleware.php';
require_once __DIR__ . '/attendance_reconciliation.php';
require_role(['Department Head', 'Team Member', 'Team Lead']);
header('Content-Type: application/json');

$report = trim($_POST['activity_report'] ?? '');
if ($report === '') {
    echo json_encode(['status' => 'error', 'message' => 'Activity report is required for sign-out.']);
    exit;
}

$db = (new Database())->connect();
$user_id = (int)($_SESSION['user_id'] ?? 0);

ensure_attendance_sheet_schema($db);
reconcile_attendance_auto_sign_out($db, $user_id);

$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

$stmt = $db->prepare("SELECT id, sign_in_at, sign_out_at FROM attendance_sheet_logs WHERE user_id = ? AND attendance_date = ? LIMIT 1");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || empty($row['sign_in_at'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please sign in first before signing out.']);
    exit;
}
if (!empty($row['sign_out_at'])) {
    echo json_encode(['status' => 'error', 'message' => 'You already signed out for today.']);
    exit;
}

$id = (int)$row['id'];
$up = $db->prepare("UPDATE attendance_sheet_logs SET sign_out_at = ?, sign_out_method = 'Manual', activity_report = ? WHERE id = ?");
$up->bind_param("ssi", $now, $report, $id);
$up->execute();
$up->close();

echo json_encode(['status' => 'success', 'message' => 'Sign-out recorded successfully.', 'sign_out_at' => $now]);
?>
