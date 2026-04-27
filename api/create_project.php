<?php
// api/create_project.php
require_once '../includes/auth_middleware.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

// RBAC: only Super Admin can create projects
$allowed_roles = ['Super Admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$name         = isset($data['name']) ? trim($data['name']) : '';
$project_type = isset($data['project_type']) ? trim($data['project_type']) : 'Major';
$status       = isset($data['status']) ? trim($data['status']) : 'Planned';
$start_date   = isset($data['start_date']) && !empty($data['start_date']) ? $data['start_date'] : null;
$end_date     = isset($data['end_date'])   && !empty($data['end_date'])   ? $data['end_date']   : null;
$kpi_target   = isset($data['kpi_target']) ? trim($data['kpi_target']) : null;

$project_head_id  = isset($data['project_head_id'])  && $data['project_head_id']  > 0 ? intval($data['project_head_id'])  : null;
$parent_project_id = isset($data['parent_project_id']) && $data['parent_project_id'] > 0 ? intval($data['parent_project_id']) : null;

$department_id = null;
$department_ids = [];
$member_ids = [];
$super_admin_ids = [];
if ($project_type === 'Major') {
    $rawMembers = isset($data['member_ids']) ? $data['member_ids'] : [];
    if (!is_array($rawMembers)) {
        $rawMembers = json_decode($rawMembers, true) ?? [];
    }
    $member_ids = array_values(array_unique(array_filter(array_map('intval', $rawMembers))));

    $rawSuperAdmins = isset($data['super_admin_ids']) ? $data['super_admin_ids'] : [];
    if (!is_array($rawSuperAdmins)) {
        $rawSuperAdmins = json_decode($rawSuperAdmins, true) ?? [];
    }
    $super_admin_ids = array_values(array_unique(array_filter(array_map('intval', $rawSuperAdmins))));
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Project name is required']);
    exit;
}
$major_assignee_ids = array_values(array_unique(array_merge($member_ids, $super_admin_ids)));

if ($project_type === 'Major' && empty($major_assignee_ids)) {
    echo json_encode(['success' => false, 'message' => 'At least one assignee (Team Member or Super Admin) must be assigned to every Major Project']);
    exit;
}

if (empty($start_date) || empty($end_date)) {
    echo json_encode(['success' => false, 'message' => 'Start date and end date are required']);
    exit;
}

if ($start_date > $end_date) {
    echo json_encode(['success' => false, 'message' => 'Start date cannot be after end date']);
    exit;
}

$allowed_statuses = ['Planned', 'In Progress', 'Completed', 'On Hold'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$allowed_types = ['Major', 'Sub'];
if (!in_array($project_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid project type']);
    exit;
}

$database = new Database();
$db = $database->connect();

// Validate parent for Sub-projects
if ($project_type === 'Sub') {
    if (!$parent_project_id) {
        echo json_encode(['success' => false, 'message' => 'Sub-Projects must have a parent Major Project']);
        exit;
    }
    $stmt = $db->prepare("SELECT id, project_type FROM projects WHERE id = ?");
    $stmt->bind_param("i", $parent_project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Parent project does not exist']);
        exit;
    }
    $parent = $result->fetch_assoc();
    if ($parent['project_type'] !== 'Major') {
        echo json_encode(['success' => false, 'message' => 'Parent must be a Major Project']);
        exit;
    }
    $stmt->close();
}

// Verify selected team members exist
foreach ($member_ids as $member_id) {
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'Team Member'");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => "Team Member ID $member_id does not exist"]);
        exit;
    }
    $stmt->close();
}

// Verify selected Super Admin assignees exist
foreach ($super_admin_ids as $sa_id) {
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'Super Admin'");
    $stmt->bind_param("i", $sa_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => "Super Admin ID $sa_id does not exist"]);
        exit;
    }
    $stmt->close();
}

// Insert Project row
// IMPORTANT: nullable integer fields must be inserted as SQL NULL (not bound as int=0),
// otherwise FK checks may fail unexpectedly.
$columns = "name, department_id, project_head_id, parent_project_id, project_type, status, start_date, end_date, kpi_target";
$values = "?, " .
    ($department_id === null ? "NULL" : "?") . ", " .
    ($project_head_id === null ? "NULL" : "?") . ", " .
    ($parent_project_id === null ? "NULL" : "?") . ", ?, ?, ?, ?, ?";
$sql = "INSERT INTO projects ($columns) VALUES ($values)";
$stmt = $db->prepare($sql);

$types = "s";
$params = [$name];
if ($department_id !== null) { $types .= "i"; $params[] = $department_id; }
if ($project_head_id !== null) { $types .= "i"; $params[] = $project_head_id; }
if ($parent_project_id !== null) { $types .= "i"; $params[] = $parent_project_id; }
$types .= "sssss";
$params[] = $project_type;
$params[] = $status;
$params[] = $start_date;
$params[] = $end_date;
$params[] = $kpi_target;

$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    $stmt->close();
    $db->close();
    exit;
}

$new_project_id = $stmt->insert_id;
$stmt->close();

// Insert pivot rows for sub-projects
if ($project_type === 'Sub') {
    $stmt = $db->prepare("INSERT IGNORE INTO sub_project_departments (sub_project_id, department_id) VALUES (?, ?)");
    foreach ($department_ids as $did) {
        $stmt->bind_param("ii", $new_project_id, $did);
        $stmt->execute();
    }
    $stmt->close();
}

// Insert pivot rows for major projects (team-member access scope)
if ($project_type === 'Major') {
    $db->query("CREATE TABLE IF NOT EXISTS major_project_members (
        major_project_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        PRIMARY KEY (major_project_id, user_id),
        CONSTRAINT fk_mpm_project FOREIGN KEY (major_project_id) REFERENCES projects(id) ON DELETE CASCADE,
        CONSTRAINT fk_mpm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("INSERT IGNORE INTO major_project_members (major_project_id, user_id) VALUES (?, ?)");
    foreach ($major_assignee_ids as $member_id) {
        $stmt->bind_param("ii", $new_project_id, $member_id);
        $stmt->execute();
    }
    $stmt->close();
}

// Recalculate parent progress
if ($project_type === 'Sub' && $parent_project_id) {
    session_write_close();
    $ch = curl_init();
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/calculate_progress.php';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['project_id' => $parent_project_id]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000);
    curl_exec($ch);
    curl_close($ch);
}

echo json_encode(['success' => true, 'message' => 'Project created successfully', 'project_id' => $new_project_id]);
$db->close();
?>
