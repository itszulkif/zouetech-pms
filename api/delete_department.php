<?php
// api/delete_department.php
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

    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Department ID is required']);
        exit;
    }

    $id = intval($data['id']);

    $database = new Database();
    $db = $database->connect();

    // 1. Unassign users from this department
    $stmt_users = $db->prepare("UPDATE users SET department_id = NULL WHERE department_id = ?");
    $stmt_users->bind_param("i", $id);
    $stmt_users->execute();
    $unassigned_count = $stmt_users->affected_rows;
    $stmt_users->close();

    // 2. Delete Department
    // Note: Projects/Tasks cascaded via DB foreign key if setup, or else they become orphans or restricted.
    // Assuming DB has ON DELETE CASCADE for projects.
    $stmt = $db->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Department deleted. $unassigned_count users unassigned."
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Department not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }

    $stmt->close();
    $db->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
