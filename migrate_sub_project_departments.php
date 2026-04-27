<?php
// migrate_sub_project_departments.php
// Run once to create the pivot table and backfill existing sub-project data.
require_once 'db_connect.php';

$database = new Database();
$db = $database->connect();

$results = [];

// 1. Create the pivot table
$sql_create = "
CREATE TABLE IF NOT EXISTS `sub_project_departments` (
  `sub_project_id` int(11) NOT NULL,
  `department_id`  int(11) NOT NULL,
  PRIMARY KEY (`sub_project_id`, `department_id`),
  CONSTRAINT `fk_spd_project`    FOREIGN KEY (`sub_project_id`) REFERENCES `projects`(`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_spd_department` FOREIGN KEY (`department_id`)  REFERENCES `departments`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($db->query($sql_create)) {
    $results[] = "✅ Table `sub_project_departments` created (or already exists).";
} else {
    $results[] = "❌ Failed to create table: " . $db->error;
}

// 2. Backfill existing sub-projects using their current department_id
$existing = $db->query("SELECT id, department_id FROM projects WHERE project_type = 'Sub' AND department_id > 0");
$backfilled = 0;
if ($existing) {
    $stmt = $db->prepare("INSERT IGNORE INTO sub_project_departments (sub_project_id, department_id) VALUES (?, ?)");
    while ($row = $existing->fetch_assoc()) {
        $stmt->bind_param("ii", $row['id'], $row['department_id']);
        $stmt->execute();
        $backfilled++;
    }
    $stmt->close();
    $results[] = "✅ Backfilled $backfilled existing sub-project(s) into the pivot table.";
}

$db->close();

echo "<pre style='font-family:monospace;font-size:14px;padding:20px;'>";
echo "=== Migration: sub_project_departments ===\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n✅ Migration complete. You can delete this file now.";
echo "</pre>";
?>
