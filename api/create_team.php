<?php
// api/create_team.php
require_once '../db_connect.php';
header('Content-Type: application/json');

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
$created_by = 1; // Default to Super Admin (ID 1) for now

if (empty($name)) {
    echo json_encode(['status' => 'error', 'message' => 'Team Name is required.']);
    exit;
}

$database = new Database();
$db = $database->connect();

try {
    // Check if team already exists
    $check = $db->prepare("SELECT id FROM teams WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Team name already exists.']);
        exit;
    }
    $check->close();

    // Insert Team
    $stmt = $db->prepare("INSERT INTO teams (name, project_id, created_by) VALUES (?, ?, ?)");
    // Use NULL for project_id if 0
    $pid = ($project_id > 0) ? $project_id : NULL;
    $stmt->bind_param("sii", $name, $pid, $created_by);
    
    if ($stmt->execute()) {
        $team_id = $db->insert_id;
        echo json_encode(['status' => 'success', 'message' => 'Team created successfully!', 'team_id' => $team_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $db->error]);
    }
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$db->close();
?>
