<?php
require_once 'db_connect.php';

$database = new Database();
$db = $database->connect();

echo "Starting database migration to make projects.department_id nullable...\n";

// Array of queries to run
$queries = [
    // 1. Drop existing foreign key
    "ALTER TABLE `projects` DROP FOREIGN KEY `fk_proj_dept`",
    
    // 2. Modify column to be nullable
    "ALTER TABLE `projects` MODIFY `department_id` INT(11) NULL DEFAULT NULL",
    
    // 3. Re-add foreign key constraint with ON DELETE SET NULL
    "ALTER TABLE `projects` ADD CONSTRAINT `fk_proj_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL"
];

$success = true;

foreach ($queries as $index => $query) {
    echo "Running query " . ($index + 1) . ": $query\n";
    if ($db->query($query) === TRUE) {
        echo "Success.\n";
    } else {
        echo "Error: " . $db->error . "\n";
        // It's possible the FK was already dropped or altered in a previous run, so we don't strictly halt on some errors,
        // but it's good to note them.
        if (strpos($db->error, "check that column/key exists") === false && strpos($db->error, "Duplicate key") === false) {
             $success = false;
        }
    }
}

if ($success) {
    echo "\nMigration completed successfully.\n";
} else {
    echo "\nMigration finished with some errors.\n";
}

$db->close();
?>
