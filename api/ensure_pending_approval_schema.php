<?php
/**
 * One-time / auto: Add 'Pending Approval' to tasks.status and create task_assignment_pending table.
 * Safe to call multiple times (ALTER only adds value if missing; CREATE TABLE IF NOT EXISTS).
 */
require_once __DIR__ . '/../db_connect.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    echo json_encode(['success' => false, 'message' => 'Super Admin only']);
    exit;
}

$db = (new Database())->connect();

// 1. Alter tasks.status enum to include 'Pending Approval'
$alter = "ALTER TABLE tasks MODIFY COLUMN status ENUM('Pending','Pending Approval','In Progress','Completed','Review','Missed') NOT NULL DEFAULT 'Pending'";
if (!$db->query($alter)) {
    echo json_encode(['success' => false, 'error' => $db->error]);
    exit;
}

// 2. Create task_assignment_pending if not exists
$create = "CREATE TABLE IF NOT EXISTS task_assignment_pending (
    task_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    requested_by INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$db->query($create)) {
    echo json_encode(['success' => false, 'error' => $db->error]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Schema updated']);
$db->close();
