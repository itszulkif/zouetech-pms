<?php
// api/get_projects.php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Team Member']);
header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

$is_super = ($_SESSION['role'] === 'Super Admin');
$dept_id = $_SESSION['department_id'] ?? 0;

// Optional filter by project type
$project_type = isset($_GET['type']) ? $_GET['type'] : 'Major';
$where_clause = "WHERE 1=1";
if ($project_type) {
    $where_clause .= " AND p.project_type = '$project_type'";
}

if (!$is_super && $project_type !== 'Major') {
    $where_clause .= " AND p.department_id = $dept_id";
}

if (!$is_super && $project_type === 'Major') {
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $db->query("CREATE TABLE IF NOT EXISTS major_project_members (
        major_project_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        PRIMARY KEY (major_project_id, user_id),
        CONSTRAINT fk_mpm_project FOREIGN KEY (major_project_id) REFERENCES projects(id) ON DELETE CASCADE,
        CONSTRAINT fk_mpm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $where_clause .= " AND EXISTS (
        SELECT 1 FROM major_project_members mpm
        WHERE mpm.major_project_id = p.id AND mpm.user_id = $uid
    )";
}

$sql = "SELECT 
            p.id, 
            p.name, 
            p.department_id, 
            d.name as department_name,
            p.project_head_id, 
            u.full_name as project_head_name,
            u.avatar_url as project_head_avatar,
            p.parent_project_id,
            p.project_type,
            p.progress_percentage,
            p.status, 
            p.start_date, 
            p.end_date, 
            p.kpi_target,
            p.created_at,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as task_count
        FROM projects p 
        LEFT JOIN departments d ON p.department_id = d.id
        LEFT JOIN users u ON p.project_head_id = u.id 
        $where_clause
        ORDER BY p.created_at DESC";

$result = $db->query($sql);

$projects = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

echo json_encode($projects);
$db->close();
?>
