<?php
// api/get_users.php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Team Member']);
header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

$is_super = ($_SESSION['role'] === 'Super Admin');
$session_user_id = (int)($_SESSION['user_id'] ?? 0);
$dept_id = (int)($_SESSION['department_id'] ?? 0);

// Ensure backwards-compatible column for edit-time password visibility.
$has_password_plain = false;
$col_res = $db->query("SHOW COLUMNS FROM users LIKE 'password_plain'");
if ($col_res && $col_res->num_rows > 0) {
    $has_password_plain = true;
} else {
    // Best-effort migration; if it fails, endpoint still works without this field.
    $db->query("ALTER TABLE users ADD COLUMN password_plain VARCHAR(255) NULL AFTER password_hash");
    $col_res_retry = $db->query("SHOW COLUMNS FROM users LIKE 'password_plain'");
    $has_password_plain = $col_res_retry && $col_res_retry->num_rows > 0;
}

$password_select = $has_password_plain ? ", u.password_plain" : ", '' AS password_plain";

if ($is_super) {
    $where_clause = "";
} else {
    // Team Member can only fetch own profile row.
    $where_clause = " WHERE u.id = $session_user_id";
}

// Perform Left Join to get department name
$sql = "SELECT u.id, u.full_name, u.email, u.phone_number, u.role, u.department_id, u.avatar_url, d.name as department_name
        $password_select
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.id
        $where_clause
        ORDER BY u.created_at DESC";

$result = $db->query($sql);

$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode($users);
$db->close();
?>
