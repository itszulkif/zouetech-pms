<?php
// update_schema_hierarchy.php
require_once 'db_connect.php';

$database = new Database();
$conn = $database->connect();

echo "Updating database schema for hierarchical projects...<br>";

// 1. Add parent_project_id column
$check = $conn->query("SHOW COLUMNS FROM projects LIKE 'parent_project_id'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE projects ADD COLUMN parent_project_id int(11) DEFAULT NULL AFTER project_head_id")) {
        echo "✅ Added parent_project_id column.<br>";
        // Add index
        $conn->query("ALTER TABLE projects ADD INDEX (parent_project_id)");
        // Add foreign key
        $sql = "ALTER TABLE projects ADD CONSTRAINT fk_proj_parent FOREIGN KEY (parent_project_id) REFERENCES projects(id) ON DELETE CASCADE";
        if ($conn->query($sql)) {
            echo "✅ Added FK constraint for parent_project_id.<br>";
        } else {
            echo "⚠️  Warning: Could not add FK constraint: " . $conn->error . "<br>";
        }
    } else {
        echo "❌ Error adding parent_project_id: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️  parent_project_id column already exists.<br>";
}

// 2. Add project_type column
$check = $conn->query("SHOW COLUMNS FROM projects LIKE 'project_type'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE projects ADD COLUMN project_type enum('Major','Sub') NOT NULL DEFAULT 'Major' AFTER parent_project_id")) {
        echo "✅ Added project_type column.<br>";
    } else {
        echo "❌ Error adding project_type: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️  project_type column already exists.<br>";
}

// 3. Add progress_percentage column
$check = $conn->query("SHOW COLUMNS FROM projects LIKE 'progress_percentage'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE projects ADD COLUMN progress_percentage int(11) NOT NULL DEFAULT 0 AFTER project_type")) {
        echo "✅ Added progress_percentage column.<br>";
    } else {
        echo "❌ Error adding progress_percentage: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️  progress_percentage column already exists.<br>";
}

// 4. Set existing projects to 'Major' type
echo "<br>Setting existing projects as 'Major' type...<br>";
if ($conn->query("UPDATE projects SET project_type = 'Major' WHERE parent_project_id IS NULL")) {
    echo "✅ Updated existing projects to Major type.<br>";
}

echo "<br><b>✅ Schema update complete!</b><br>";
echo "<br><a href='projects.php' style='display:inline-block; padding:10px 20px; background: #06b6d4; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Projects</a>";

$conn->close();
?>
