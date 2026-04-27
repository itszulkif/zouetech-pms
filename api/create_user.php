<?php
// api/create_user.php
require_once '../db_connect.php';
require_once '../includes/auth_middleware.php';
header('Content-Type: application/json');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$caller_role = $_SESSION['role'] ?? '';
$caller_dept = $_SESSION['department_id'] ?? null;
if (!in_array($caller_role, ['Super Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Support both JSON input (old) and multipart/form-data (new, with file)
    $data = $_POST;
    if (empty($data)) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
    }

    $full_name = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone_number = trim($data['phone_number'] ?? '');
    $role = trim($data['role'] ?? 'Team Member');

    if ($role === 'Super Admin') {
        $department_id = null;
    }
    else {
        $department_id = !empty($data['department_id']) ? intval($data['department_id']) : null;
    }

    $password = !empty($data['password']) ? (string)$data['password'] : '';

    if (empty($full_name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Name and Email are required']);
        exit;
    }
    if (empty($password) || strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password is required and must be at least 8 characters']);
        exit;
    }

    $allowed_roles = ['Super Admin', 'Team Member', 'Team Lead'];
    if (!in_array($role, $allowed_roles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }

    // Handle optional avatar upload
    $avatar_url = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $ftype = mime_content_type($_FILES['avatar']['tmp_name']);
        if (!in_array($ftype, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type. Use JPG, PNG, GIF, or WEBP.']);
            exit;
        }
        if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Avatar must be under 2 MB.']);
            exit;
        }
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $fname = 'avatar_' . uniqid() . '.' . $ext;
        $target = '../uploads/avatars/' . $fname;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
            $avatar_url = 'uploads/avatars/' . $fname;
        }
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $database = new Database();
    $db = $database->connect();

    // Ensure role storage supports Team Lead even on older enum-based schemas.
    $role_col_res = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($role_col_res && $role_col_res->num_rows > 0) {
        $role_col = $role_col_res->fetch_assoc();
        $role_type = strtolower((string)($role_col['Type'] ?? ''));
        if (strpos($role_type, 'enum(') === 0 && strpos($role_type, "'team lead'") === false) {
            $db->query("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'Team Member'");
        }
    }

    // Ensure password_plain exists for edit-time password visibility flows.
    $col_res = $db->query("SHOW COLUMNS FROM users LIKE 'password_plain'");
    if (!$col_res || $col_res->num_rows === 0) {
        $db->query("ALTER TABLE users ADD COLUMN password_plain VARCHAR(255) NULL AFTER password_hash");
    }

    // Check for duplicate email
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    $stmt->close();

    // Insert the new user
    if ($department_id !== null) {
        $sql = "INSERT INTO users (full_name, email, phone_number, password_hash, password_plain, role, department_id, avatar_url, performance_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssssssis", $full_name, $email, $phone_number, $password_hash, $password, $role, $department_id, $avatar_url);
    }
    else {
        $sql = "INSERT INTO users (full_name, email, phone_number, password_hash, password_plain, role, department_id, avatar_url, performance_score) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, 0)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("sssssss", $full_name, $email, $phone_number, $password_hash, $password, $role, $avatar_url);
    }

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        exit;
    }
    $new_user_id = $db->insert_id;
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'User created successfully', 'user_id' => $new_user_id]);

    $db->close();

}
catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>