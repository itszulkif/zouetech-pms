<?php
// api/update_user.php
require_once '../db_connect.php';
require_once '../includes/auth_middleware.php';
header('Content-Type: application/json');

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

require_role(['Super Admin', 'Team Member']);

$caller_role = $_SESSION['role'] ?? '';
$caller_user_id = (int)($_SESSION['user_id'] ?? 0);
$caller_dept = (int)($_SESSION['department_id'] ?? 0);

try {
    // Support both FormData (multipart, for file uploads) and JSON
    $data = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    $id = intval($data['id']);
    $full_name = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone_number = trim($data['phone_number'] ?? '');
    $role = trim($data['role'] ?? '');
    $department_id = !empty($data['department_id']) ? intval($data['department_id']) : null;
    $password = !empty($data['password']) ? $data['password'] : null;

    // Super Admin must have NULL department
    if ($role === 'Super Admin') {
        $department_id = null;
    }

    // Load current avatar from database so we can safely replace/delete it.
    $database = new Database();
    $db = $database->connect();
    $current_avatar_url = '';
    $existing_user_stmt = $db->prepare("SELECT avatar_url FROM users WHERE id = ? LIMIT 1");
    $existing_user_stmt->bind_param("i", $id);
    $existing_user_stmt->execute();
    $existing_user = $existing_user_stmt->get_result()->fetch_assoc();
    $existing_user_stmt->close();
    if (!$existing_user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    $current_avatar_url = trim((string)($existing_user['avatar_url'] ?? ''));
    $avatar_url = $current_avatar_url;
    $avatar_updated = false;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $ftype = mime_content_type($_FILES['avatar']['tmp_name']);
        if (in_array($ftype, $allowed_types) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $fname = 'avatar_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], '../uploads/avatars/' . $fname)) {
                $avatar_url = 'uploads/avatars/' . $fname;
                $avatar_updated = true;
            }
        }
    }

    if (empty($full_name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Name and Email are required']);
        exit;
    }

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

    if ($caller_role === 'Team Member') {
        if ($id !== $caller_user_id) {
            echo json_encode(['success' => false, 'message' => 'You can only edit your own profile']);
            exit;
        }

        // Team Member cannot change role or department.
        $target_stmt = $db->prepare("SELECT role, department_id FROM users WHERE id = ? LIMIT 1");
        $target_stmt->bind_param("i", $id);
        $target_stmt->execute();
        $target = $target_stmt->get_result()->fetch_assoc();
        $target_stmt->close();
        if (!$target) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        $role = $target['role'] ?? 'Team Member';
        $department_id = !empty($target['department_id']) ? (int)$target['department_id'] : null;
    }

    // Build dynamic query
    $set_parts = [];
    $types = "";
    $params = [];

    $set_parts[] = "full_name = ?";
    $types .= "s";
    $params[] = $full_name;

    $set_parts[] = "email = ?";
    $types .= "s";
    $params[] = $email;

    $set_parts[] = "phone_number = ?";
    $types .= "s";
    $params[] = $phone_number;

    $set_parts[] = "role = ?";
    $types .= "s";
    $params[] = $role;

    if ($department_id !== null) {
        $set_parts[] = "department_id = ?";
        $types .= "i";
        $params[] = $department_id;
    }
    else {
        $set_parts[] = "department_id = NULL";
    }

    if ($password) {
        if (strlen($password) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
            exit;
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $set_parts[] = "password_hash = ?";
        $types .= "s";
        $params[] = $password_hash;

        $set_parts[] = "password_plain = ?";
        $types .= "s";
        $params[] = $password;
    }

    // Save avatar_url if one was uploaded
    if ($avatar_updated) {
        $set_parts[] = "avatar_url = ?";
        $types .= "s";
        $params[] = $avatar_url;
    }

    $sql = "UPDATE users SET " . implode(", ", $set_parts) . " WHERE id = ?";
    $types .= "i";
    $params[] = $id;

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // Remove previous local avatar file after successful DB update.
        if ($avatar_updated && $current_avatar_url !== '' && $current_avatar_url !== $avatar_url) {
            $old_avatar_file = realpath(__DIR__ . '/../' . ltrim($current_avatar_url, '/\\'));
            $uploads_root = realpath(__DIR__ . '/../uploads/avatars');
            if ($old_avatar_file && $uploads_root && strpos($old_avatar_file, $uploads_root) === 0 && is_file($old_avatar_file)) {
                @unlink($old_avatar_file);
            }
        }

        if ($id === $caller_user_id) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['role'] = $role;
            $_SESSION['department_id'] = $department_id;
            if ($avatar_updated) {
                $_SESSION['avatar_url'] = $avatar_url;
            }
        }
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }

    $stmt->close();
    $db->close();

}
catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
