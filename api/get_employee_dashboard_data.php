<?php
require_once '../includes/auth_middleware.php';
require_role(['Team Member', 'Team Lead']);
header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session user.']);
    exit;
}

function json_error($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Basic profile
$profile = [
    'full_name' => '',
    'role' => ''
];
$stmt = $db->prepare("SELECT full_name, role FROM users WHERE id = ?");
if (!$stmt) json_error('Failed to prepare profile query.');
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $profile['full_name'] = (string)$row['full_name'];
    $profile['role'] = (string)$row['role'];
}
$stmt->close();

// Completed-task points (earned only) split by task type
$points = [
    'project_completed_points' => 0,
    'direct_completed_points' => 0,
    'total_completed_points' => 0
];

$stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN pl.score_change > 0 THEN pl.score_change ELSE 0 END), 0) AS pts
                      FROM performance_logs pl
                      INNER JOIN tasks t ON t.id = pl.task_id
                      INNER JOIN projects p ON p.id = t.project_id
                      WHERE pl.user_id = ? AND t.assigned_to = ? AND t.status = 'Completed'");
if (!$stmt) json_error('Failed to prepare project points query.');
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$points['project_completed_points'] = (int)$stmt->get_result()->fetch_assoc()['pts'];
$stmt->close();

$stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN pl.score_change > 0 THEN pl.score_change ELSE 0 END), 0) AS pts
                      FROM performance_logs pl
                      INNER JOIN tasks t ON t.id = pl.task_id
                      INNER JOIN task_assignments ta ON ta.task_id = t.id
                      WHERE pl.user_id = ?
                        AND ta.user_id = ?
                        AND t.status = 'Completed'
                        AND (t.project_id IS NULL OR t.project_id = 0)");
if (!$stmt) json_error('Failed to prepare direct points query.');
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$points['direct_completed_points'] = (int)$stmt->get_result()->fetch_assoc()['pts'];
$stmt->close();

$points['total_completed_points'] = $points['project_completed_points'] + $points['direct_completed_points'];

// Project task stats
$project_stats = ['pending' => 0, 'completed' => 0, 'overdue' => 0];
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.assigned_to = ? AND t.status != 'Completed'");
if (!$stmt) json_error('Failed to prepare project pending query.');
$stmt->bind_param("i", $user_id);
$stmt->execute();
$project_stats['pending'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.assigned_to = ? AND t.status = 'Completed'");
if (!$stmt) json_error('Failed to prepare project completed query.');
$stmt->bind_param("i", $user_id);
$stmt->execute();
$project_stats['completed'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as cnt
                      FROM tasks t
                      JOIN projects p ON t.project_id = p.id
                      WHERE t.assigned_to = ?
                        AND t.status != 'Completed'
                        AND t.due_date IS NOT NULL
                        AND (
                            (t.specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(t.due_date), t.specific_time))
                            OR
                            (t.specific_time IS NULL AND NOW() >= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
                        )");
if (!$stmt) json_error('Failed to prepare project overdue query.');
$stmt->bind_param("i", $user_id);
$stmt->execute();
$project_stats['overdue'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Direct task stats
$direct_stats = ['pending' => 0, 'completed' => 0, 'overdue' => 0];
$stmt = $db->prepare("SELECT COUNT(DISTINCT t.id) as cnt FROM tasks t INNER JOIN task_assignments ta ON ta.task_id = t.id WHERE ta.user_id = ? AND (t.project_id IS NULL OR t.project_id = 0) AND t.status != 'Completed'");
if (!$stmt) json_error('Failed to prepare direct pending query.');
$stmt->bind_param("i", $user_id);
$stmt->execute();
$direct_stats['pending'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(DISTINCT t.id) as cnt FROM tasks t INNER JOIN task_assignments ta ON ta.task_id = t.id WHERE ta.user_id = ? AND (t.project_id IS NULL OR t.project_id = 0) AND t.status = 'Completed'");
if (!$stmt) json_error('Failed to prepare direct completed query.');
$stmt->bind_param("i", $user_id);
$stmt->execute();
$direct_stats['completed'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(DISTINCT t.id) as cnt
                      FROM tasks t
                      INNER JOIN task_assignments ta ON ta.task_id = t.id
                      WHERE ta.user_id = ?
                        AND (t.project_id IS NULL OR t.project_id = 0)
                        AND t.status != 'Completed'
                        AND t.due_date IS NOT NULL
                        AND (
                            (t.specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(t.due_date), t.specific_time))
                            OR
                            (t.specific_time IS NULL AND NOW() >= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
                        )");
if (!$stmt) json_error('Failed to prepare direct overdue query.');
$stmt->bind_param("i", $user_id);
$stmt->execute();
$direct_stats['overdue'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Project task list (pending only, visible table)
$project_tasks = [];
$stmt = $db->prepare("SELECT t.id, t.project_id, t.title, t.due_date, t.specific_time, t.priority, t.status, p.name as project_name
                      FROM tasks t
                      JOIN projects p ON t.project_id = p.id
                      WHERE t.assigned_to = ? AND t.status != 'Completed'
                      ORDER BY t.due_date ASC");
if (!$stmt) json_error('Failed to prepare project task list query.');
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $project_tasks[] = $row;
}
$stmt->close();

// Direct task list (pending only, visible table)
$direct_tasks = [];
$stmt = $db->prepare("SELECT t.id, t.title, t.due_date, t.specific_time, t.priority, t.status
                      FROM tasks t
                      INNER JOIN task_assignments ta ON ta.task_id = t.id
                      WHERE ta.user_id = ? AND (t.project_id IS NULL OR t.project_id = 0) AND t.status != 'Completed'
                      ORDER BY t.due_date ASC");
if (!$stmt) json_error('Failed to prepare direct task list query.');
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $direct_tasks[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'profile' => $profile,
    'points' => $points,
    'project_stats' => $project_stats,
    'direct_stats' => $direct_stats,
    'project_tasks' => $project_tasks,
    'direct_tasks' => $direct_tasks
]);

$db->close();
?>
