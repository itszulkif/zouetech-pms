<?php
// api/update_project.php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid role']);
    exit;
}

$id           = isset($data['id'])           ? intval($data['id'])           : 0;
$name         = isset($data['name'])         ? trim($data['name'])           : '';
$project_type = isset($data['project_type']) ? trim($data['project_type'])   : 'Major';
$status       = isset($data['status'])       ? trim($data['status'])         : 'Planned';
$start_date   = isset($data['start_date'])   && !empty($data['start_date'])  ? $data['start_date'] : null;
$end_date     = isset($data['end_date'])     && !empty($data['end_date'])     ? $data['end_date']   : null;
$kpi_target   = isset($data['kpi_target'])   ? trim($data['kpi_target'])     : null;
$project_head_id = isset($data['project_head_id']) && $data['project_head_id'] > 0 ? intval($data['project_head_id']) : null;

$department_id = null;
$member_ids = [];
if ($project_type === 'Major') {
    $raw = isset($data['member_ids']) ? $data['member_ids'] : [];
    if (!is_array($raw)) {
        $raw = json_decode($raw, true) ?? [];
    }
    $member_ids = array_values(array_unique(array_filter(array_map('intval', $raw))));
}

if ($id <= 0 || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Project ID and name are required']);
    exit;
}
if ($project_type === 'Major' && empty($member_ids)) {
    echo json_encode(['success' => false, 'message' => 'Major projects require at least one team member']);
    exit;
}

$database = new Database();
$db = $database->connect();

// Update main project row
$sql = "UPDATE projects SET 
        name = ?, 
        department_id = ?, 
        project_head_id = ?, 
        status = ?, 
        start_date = ?, 
        end_date = ?, 
        kpi_target = ? 
        WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("siissssi", $name, $department_id, $project_head_id, $status, $start_date, $end_date, $kpi_target, $id);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    $stmt->close();
    $db->close();
    exit;
}
$stmt->close();

if ($project_type === 'Major') {
    $db->query("CREATE TABLE IF NOT EXISTS major_project_members (
        major_project_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        PRIMARY KEY (major_project_id, user_id),
        CONSTRAINT fk_mpm_project FOREIGN KEY (major_project_id) REFERENCES projects(id) ON DELETE CASCADE,
        CONSTRAINT fk_mpm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $del = $db->prepare("DELETE FROM major_project_members WHERE major_project_id = ?");
    $del->bind_param("i", $id);
    $del->execute();
    $del->close();

    $ins = $db->prepare("INSERT IGNORE INTO major_project_members (major_project_id, user_id) VALUES (?, ?)");
    foreach ($member_ids as $member_id) {
        $ins->bind_param("ii", $id, $member_id);
        $ins->execute();
    }
    $ins->close();
}

echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
$db->close();
?>
