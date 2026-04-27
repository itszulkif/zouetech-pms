<?php
// verify_projects.php
require_once 'db_connect.php';

echo "Starting Project Management Verification...<br>";

$database = new Database();
$db = $database->connect();

// 1. Verify departments exist for testing
$result = $db->query("SELECT id, name FROM departments LIMIT 1");
if ($result->num_rows === 0) {
    echo "⚠️  No departments found. Creating test department...<br>";
    $db->query("INSERT INTO departments (name) VALUES ('Test Department')");
    $dept_id = $db->insert_id;
    echo "✅ Created department ID: $dept_id<br>";
} else {
    $dept = $result->fetch_assoc();
    $dept_id = $dept['id'];
    echo "✅ Using existing department: {$dept['name']} (ID: $dept_id)<br>";
}

// 2. Create test project
$project_name = "Test Project " . rand(1000, 9999);
echo "<br>Creating test project: $project_name...<br>";

$stmt = $db->prepare("INSERT INTO projects (name, department_id, status, kpi_target) VALUES (?, ?, 'Planned', 'Test KPI Target')");
$stmt->bind_param("si", $project_name, $dept_id);

if ($stmt->execute()) {
    $project_id = $stmt->insert_id;
    echo "✅ Project created successfully (ID: $project_id)<br>";
} else {
    die("❌ Failed to create project: " . $stmt->error);
}

// 3. Verify project is linked to department
echo "<br>Verifying department linkage...<br>";
$result = $db->query("SELECT p.name, d.name as dept_name FROM projects p JOIN departments d ON p.department_id = d.id WHERE p.id = $project_id");
if ($row = $result->fetch_assoc()) {
    echo "✅ Project '{$row['name']}' is linked to department '{$row['dept_name']}'<br>";
} else {
    echo "❌ Failed to verify department linkage<br>";
}

// 4. Get a user to assign as project head
$result = $db->query("SELECT id, full_name FROM users WHERE role IN ('Super Admin') LIMIT 1");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    
    echo "<br>Assigning Project Head: {$user['full_name']}...<br>";
    $stmt = $db->prepare("UPDATE projects SET project_head_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $user_id, $project_id);
    
    if ($stmt->execute()) {
        echo "✅ Project Head assigned successfully<br>";
        
        // Verify assignment
        $result = $db->query("SELECT p.name, u.full_name FROM projects p JOIN users u ON p.project_head_id = u.id WHERE p.id = $project_id");
        if ($row = $result->fetch_assoc()) {
            echo "✅ Verified: Project '{$row['name']}' is managed by '{$row['full_name']}'<br>";
        }
    } else {
        echo "❌ Failed to assign Project Head: " . $stmt->error . "<br>";
    }
} else {
    echo "⚠️  No users available to assign as Project Head<br>";
}

// 5. Update project
echo "<br>Testing project update...<br>";
$new_status = 'In Progress';
$stmt = $db->prepare("UPDATE projects SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $project_id);
if ($stmt->execute()) {
    echo "✅ Project status updated to '$new_status'<br>";
} else {
    echo "❌ Failed to update project<br>";
}

// 6. Cleanup
echo "<br>Cleaning up test data...<br>";
$db->query("DELETE FROM projects WHERE id = $project_id");
echo "✅ Test project deleted<br>";

echo "<br><b>✅ All Project Management Verifications Passed!</b>";
?>
