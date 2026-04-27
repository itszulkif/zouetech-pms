<?php
require_once 'db_connect.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$database = new Database();
$db = $database->connect();

$email = getenv('ADMIN_EMAIL') ?: 'admin@zouetech.org';
$password = getenv('ADMIN_PASSWORD') ?: '';
if ($password === '') {
    exit("Set ADMIN_PASSWORD environment variable before running this script.\n");
}
$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'Super Admin';
$name = 'Super Admin';

// Check if email exists
$check = $db->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
if($check->get_result()->num_rows > 0) {
    // Update existing
    $stmt = $db->prepare("UPDATE users SET password_hash = ?, role = ? WHERE email = ?");
    $stmt->bind_param("sss", $hash, $role, $email);
    echo "Updated existing user $email.\n";
} else {
    // Insert new
    $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, performance_score) VALUES (?, ?, ?, ?, 100)");
    $stmt->bind_param("ssss", $name, $email, $hash, $role);
    echo "Created new user $email.\n";
}
$stmt->execute();
echo "Password updated for: $email\n";
?>
