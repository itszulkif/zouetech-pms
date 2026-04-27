<?php
// update_schema.php
require_once 'db_connect.php';

$database = new Database();
$conn = $database->connect();

echo "Updating database schema...<br>";

// 1. Add phone_number to users
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'phone_number'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE users ADD COLUMN phone_number varchar(20) DEFAULT NULL AFTER email")) {
        echo "Added phone_number column.<br>";
    } else {
        echo "Error adding phone_number: " . $conn->error . "<br>";
    }
} else {
    echo "phone_number column already exists.<br>";
}

// 2. Add department_id to users
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'department_id'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE users ADD COLUMN department_id int(11) DEFAULT NULL AFTER role")) {
        echo "Added department_id column.<br>";
        // Add Index
        $conn->query("ALTER TABLE users ADD INDEX (department_id)");
        // Add FK (assuming departments table exists and has id)
        // We use CREATE TABLE IF NOT EXISTS for departments in db_schema, so it should exist if setup was run.
        // However, if we are editing users, we should check if departments exist to add FK.
        
        // Try adding constraint, ignore if fails (might be due to data inconsistency or table missing)
        $sql = "ALTER TABLE users ADD CONSTRAINT fk_user_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL";
        if ($conn->query($sql)) {
             echo "Added FK constraint for department_id.<br>";
        } else {
             echo "Warning: Could not add FK constraint (departments table might not exist or data mismatch): " . $conn->error . "<br>";
        }

    } else {
        echo "Error adding department_id: " . $conn->error . "<br>";
    }
} else {
    echo "department_id column already exists.<br>";
}


$conn->close();

echo "Schema update check complete.";
?>
