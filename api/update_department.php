<?php
// api/update_department.php
require_once '../db_connect.php';
header('Content-Type: application/json');

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id']) || !isset($data['name'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields (id, name)']);
        exit;
    }

    $id = intval($data['id']);
    $name = trim($data['name']);
    $head_id = !empty($data['head_id']) ? intval($data['head_id']) : null;

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Department Name is required']);
        exit;
    }

    $database = new Database();
    $db = $database->connect();

    // Update Department - Branching for NULL head_id
    if ($head_id !== null) {
        $sql = "UPDATE departments SET name = ?, head_id = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("sii", $name, $head_id, $id);
    } else {
        // Use literal NULL in SQL
        $sql = "UPDATE departments SET name = ?, head_id = NULL WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("si", $name, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Department updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }

    $stmt->close();
    $db->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
