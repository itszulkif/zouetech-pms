<?php
require_once __DIR__ . '/../db_connect.php';
$db = (new Database())->connect();
$sql = "CREATE TABLE IF NOT EXISTS task_chat_read (
  user_id int(11) NOT NULL,
  task_id int(11) NOT NULL,
  last_read_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (user_id, task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if ($db->query($sql)) {
    echo "Table task_chat_read created or exists.\n";
} else {
    echo "Error: " . $db->error . "\n";
}
