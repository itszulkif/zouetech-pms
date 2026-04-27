<?php
// api/get_project_tasks.php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Department Head', 'Team Member']);
header('Content-Type: application/json');

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;


if ($project_id <= 0) {
    echo json_encode(['error' => 'Invalid Project ID']);
    exit;
}

$database = new Database();
$db = $database->connect();

$role = $_SESSION['role'];
$user_dept = $_SESSION['department_id'] ?? 0;
$user_id = (int)($_SESSION['user_id'] ?? 0);

$where_clause = "WHERE t.project_id = ?";
$joins = "";

if ($role === 'Department Head') {
    $where_clause .= " AND (
        t.assigned_to = $user_id
        OR EXISTS (
            SELECT 1
            FROM task_assignments ta_self
            WHERE ta_self.task_id = t.id
              AND ta_self.user_id = $user_id
        )
    ) ";
}

if ($role === 'Team Member') {
    $where_clause .= " AND (
        t.assigned_to = $user_id
        OR EXISTS (
            SELECT 1
            FROM task_assignments ta_self
            WHERE ta_self.task_id = t.id
              AND ta_self.user_id = $user_id
        )
    ) ";
}

$tasks = [];
$query = "SELECT t.id, t.title, t.status, t.due_date, u.full_name as assignee 
          FROM tasks t 
          LEFT JOIN users u ON t.assigned_to = u.id 
          $joins
          $where_clause 
          ORDER BY t.due_date ASC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode([
    'tasks' => $tasks
]);
?>
