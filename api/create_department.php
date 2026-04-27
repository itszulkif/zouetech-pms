<?php
// api/create_department.php
require_once '../db_connect.php';
require_once '../includes/auth_middleware.php';
require_role(['Super Admin']);
header('Content-Type: application/json');

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data)
        $data = $_POST;

    $name = isset($data['name']) ? trim($data['name']) : '';
    // Fix: Ensure head_id is null if empty/0
    $head_id = !empty($data['head_id']) ? intval($data['head_id']) : null;

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Department Name is required']);
        exit;
    }

    $database = new Database();
    $db = $database->connect();

    // Insert Department - Branching for NULL head_id
    if ($head_id !== null) {
        $stmt = $db->prepare("INSERT INTO departments (name, head_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $head_id);
    }
    else {
        // Use literal NULL in SQL
        $stmt = $db->prepare("INSERT INTO departments (name, head_id) VALUES (?, NULL)");
        $stmt->bind_param("s", $name);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Department created successfully', 'department_id' => $db->insert_id]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
    $db->close();

}
catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
