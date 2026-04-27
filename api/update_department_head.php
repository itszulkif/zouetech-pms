<?php
// api/update_department_head.php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$department_id = isset($data['department_id']) ? intval($data['department_id']) : 0;
$head_id = isset($data['head_id']) ? intval($data['head_id']) : null;

if ($department_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid department ID']);
    exit;
}

$database = new Database();
$db = $database->connect();

// If head_id is 0 or 'null' string, treat as null
if ($head_id === 0) $head_id = null;

$stmt = $db->prepare("UPDATE departments SET head_id = ? WHERE id = ?");
$stmt->bind_param("ii", $head_id, $department_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Department Head updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

$stmt->close();
$db->close();
?>
