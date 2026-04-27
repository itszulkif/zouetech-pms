<?php
// api/calculate_progress.php
require_once '../db_connect.php';
header('Content-Type: application/json');

$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit;
}

$database = new Database();
$db = $database->connect();

// Get project type
$stmt = $db->prepare("SELECT id, project_type, parent_project_id FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

$project = $result->fetch_assoc();

if ($project['project_type'] === 'Sub') {
    // Calculate Sub-Project progress based on tasks
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM tasks WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task_stats = $result->fetch_assoc();
    
    $progress = 0;
    if ($task_stats['total_tasks'] > 0) {
        $progress = round(($task_stats['completed_tasks'] / $task_stats['total_tasks']) * 100);
    }
    
    // Update Sub-Project progress
    $stmt = $db->prepare("UPDATE projects SET progress_percentage = ? WHERE id = ?");
    $stmt->bind_param("ii", $progress, $project_id);
    $stmt->execute();
    
    // If this Sub-Project has a parent, recalculate parent progress
    if ($project['parent_project_id']) {
        recalculate_major_project_progress($db, $project['parent_project_id']);
    }
    
    echo json_encode(['success' => true, 'progress' => $progress, 'type' => 'Sub']);
    
} else {
    // Calculate Major Project progress based on Sub-Projects
    $progress = recalculate_major_project_progress($db, $project_id);
    echo json_encode(['success' => true, 'progress' => $progress, 'type' => 'Major']);
}

$db->close();

function recalculate_major_project_progress($db, $major_project_id) {
    // Get average progress of all Sub-Projects
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_subs,
        AVG(progress_percentage) as avg_progress
        FROM projects WHERE parent_project_id = ?");
    $stmt->bind_param("i", $major_project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sub_stats = $result->fetch_assoc();
    
    $progress = 0;
    if ($sub_stats['total_subs'] > 0) {
        $progress = round($sub_stats['avg_progress']);
    }
    
    // Update Major Project progress
    $stmt = $db->prepare("UPDATE projects SET progress_percentage = ? WHERE id = ?");
    $stmt->bind_param("ii", $progress, $major_project_id);
    $stmt->execute();
    
    return $progress;
}
?>
