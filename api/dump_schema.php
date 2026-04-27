<?php
// api/dump_schema.php
// Use __DIR__ to correctly locate db_connect.php regardless of execution context
require_once __DIR__ . '/../db_connect.php';

$database = new Database();
$db = $database->connect();

$tables = ['users', 'departments'];
$output = "";

foreach ($tables as $table) {
    echo "Table: $table\n";
    $result = $db->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo implode("\t", $row) . "\n";
        }
    } else {
        echo "Error describing $table: " . $db->error . "\n";
    }
    echo "\n-----------------------------------\n";
}
?>
