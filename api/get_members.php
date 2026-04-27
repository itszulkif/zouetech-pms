<?php
// api/get_members.php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Team Member']);
header('Content-Type: application/json');

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : -1;
$for_mentions = isset($_GET['for_mentions']) && $_GET['for_mentions'] === '1';

$database = new Database();
$db = $database->connect();

// Mention dropdown: any user can mention any other user — return all users with no restrictions
if ($for_mentions) {
    $res = $db->query("SELECT u.id, u.full_name, u.role, d.name as department_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.role IN ('Super Admin', 'Team Member')
        ORDER BY u.role ASC, d.name ASC, u.full_name ASC");
    $members = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $members[] = $row;
        }
    }
    echo json_encode($members);
    exit;
}

if ($project_id < 0) {
    echo json_encode([]);
    exit;
}

$is_super = ($_SESSION['role'] === 'Super Admin');

if ($project_id === 0) {
    // ---------------------------------------------------------
    // Direct Task Logic
    // ---------------------------------------------------------
    $task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    $members = [];

    if ($task_id > 0) {
        // Chat Sidebar: Assignees + task creator + users from mention_notifications (so mentioned users can @mention participants)
        $query = "SELECT DISTINCT u.id, u.full_name, u.role, d.name as department_name 
                  FROM users u 
                  LEFT JOIN departments d ON u.department_id = d.id 
                  WHERE u.id IN (
                    SELECT ta.user_id FROM task_assignments ta WHERE ta.task_id = ?
                    UNION
                    SELECT pl.user_id FROM performance_logs pl WHERE pl.task_id = ? AND pl.reason LIKE 'assigned%'
                    UNION
                    SELECT mn.mentioned_user_id FROM mention_notifications mn WHERE mn.task_id = ?
                    UNION
                    SELECT mn.sender_id FROM mention_notifications mn WHERE mn.task_id = ?
                  )
                  ORDER BY u.role ASC, d.name ASC, u.full_name ASC";

        $stmt = $db->prepare($query);
        $stmt->bind_param("iiii", $task_id, $task_id, $task_id, $task_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $members[] = $row;
        }
        $stmt->close();
    }
    else {
        // Assign Dropdown: Show all possible assignees based on role
        if ($is_super) {
            // Super Admins can assign direct tasks to team members and other super admins.
            $query = "SELECT u.id, u.full_name, u.role, d.name as department_name 
                      FROM users u 
                      LEFT JOIN departments d ON u.department_id = d.id 
                      WHERE u.role IN ('Team Member', 'Super Admin')
                      ORDER BY d.name ASC, u.full_name ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $members[] = $row;
            }
            $stmt->close();
        }
        else {
            // Team Members can only assign to Team Members
            $query = "SELECT u.id, u.full_name, u.role, u.department_id, d.name as department_name 
                      FROM users u 
                      LEFT JOIN departments d ON u.department_id = d.id 
                      WHERE u.role IN ('Team Member')
                      ORDER BY d.name ASC, u.full_name ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $members[] = $row;
            }
            $stmt->close();
        }
    }
    echo json_encode($members);
}
else {
    // ---------------------------------------------------------
    // Project Task Logic
    // ---------------------------------------------------------
    $db->query("CREATE TABLE IF NOT EXISTS major_project_members (
        major_project_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        PRIMARY KEY (major_project_id, user_id),
        CONSTRAINT fk_mpm_project FOREIGN KEY (major_project_id) REFERENCES projects(id) ON DELETE CASCADE,
        CONSTRAINT fk_mpm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if ($is_super) {
        $stmt = $db->prepare("SELECT DISTINCT u.id, u.full_name, u.role, d.name as department_name
            FROM users u
            LEFT JOIN departments d ON d.id = u.department_id
            LEFT JOIN major_project_members mpm ON mpm.user_id = u.id AND mpm.major_project_id = ?
            WHERE mpm.user_id IS NOT NULL OR u.role = 'Super Admin'
            ORDER BY u.full_name ASC");
    } else {
        $stmt = $db->prepare("SELECT u.id, u.full_name, u.role, d.name as department_name
            FROM major_project_members mpm
            JOIN users u ON u.id = mpm.user_id
            LEFT JOIN departments d ON d.id = u.department_id
            WHERE mpm.major_project_id = ?
            ORDER BY u.full_name ASC");
    }
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    $stmt->close();
    echo json_encode($members);
}
?>
