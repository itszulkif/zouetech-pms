<?php
// api/get_sub_projects.php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Department Head']);
header('Content-Type: application/json');

$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

if ($parent_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parent project ID']);
    exit;
}

$database = new Database();
$db = $database->connect();

$is_super = ($_SESSION['role'] === 'Super Admin');
$dept_id  = intval($_SESSION['department_id'] ?? 0);

// For Dept Head: only return sub-projects where their dept is in the pivot table
if ($is_super) {
    $sql = "SELECT 
                p.id, 
                p.name, 
                p.department_id, 
                p.project_head_id, 
                u.full_name as project_head_name,
                p.parent_project_id,
                p.project_type,
                p.progress_percentage,
                p.status, 
                p.start_date, 
                p.end_date, 
                p.kpi_target,
                p.created_at,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'Completed') as completed_tasks
            FROM projects p 
            LEFT JOIN users u ON p.project_head_id = u.id 
            WHERE p.parent_project_id = ?
            ORDER BY p.created_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $parent_id);
} else {
    $sql = "SELECT 
                p.id, 
                p.name, 
                p.department_id, 
                p.project_head_id, 
                u.full_name as project_head_name,
                p.parent_project_id,
                p.project_type,
                p.progress_percentage,
                p.status, 
                p.start_date, 
                p.end_date, 
                p.kpi_target,
                p.created_at,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'Completed') as completed_tasks
            FROM projects p 
            INNER JOIN sub_project_departments spd ON spd.sub_project_id = p.id AND spd.department_id = ?
            LEFT JOIN users u ON p.project_head_id = u.id 
            WHERE p.parent_project_id = ?
            ORDER BY p.created_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $dept_id, $parent_id);
}

$stmt->execute();
$result = $stmt->get_result();

$sub_projects = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Attach list of assigned department names
        $dept_stmt = $db->prepare(
            "SELECT d.name, spd.department_id FROM departments d 
             INNER JOIN sub_project_departments spd ON spd.department_id = d.id 
             WHERE spd.sub_project_id = ?
             ORDER BY d.name ASC"
        );
        $dept_stmt->bind_param("i", $row['id']);
        $dept_stmt->execute();
        $dept_result = $dept_stmt->get_result();
        $dept_names = [];
        $dept_ids = [];
        while ($dr = $dept_result->fetch_assoc()) {
            $dept_names[] = $dr['name'];
            $dept_ids[] = $dr['department_id'];
        }
        $dept_stmt->close();

        $row['departments']      = $dept_names;
        $row['departments_raw']  = $dept_ids;
        $row['departments_list'] = implode(', ', $dept_names);
        $sub_projects[] = $row;
    }
}

$stmt->close();
echo json_encode($sub_projects);
$db->close();
?>
