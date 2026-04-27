<?php
// api/get_departments.php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Department Head']);
header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

$is_super = ($_SESSION['role'] === 'Super Admin');
$dept_id = $_SESSION['department_id'] ?? 0;

$where_clause = $is_super ? "" : " WHERE d.id = $dept_id";

$sql = "SELECT d.id, d.name, d.head_id, u.full_name as head_name 
        FROM departments d 
        LEFT JOIN users u ON d.head_id = u.id 
        $where_clause
        ORDER BY d.name ASC";

$result = $db->query($sql);

$departments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

echo json_encode($departments);
$db->close();
?>
