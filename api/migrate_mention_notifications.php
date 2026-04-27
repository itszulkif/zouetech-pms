<?php
require_once dirname(__DIR__) . '/db_connect.php';

$database = new Database();
$db = $database->connect();

$sql = "CREATE TABLE IF NOT EXISTS `mention_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `mentioned_user_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `sender_id` (`sender_id`),
  KEY `mentioned_user_id` (`mentioned_user_id`),
  KEY `message_id` (`message_id`),
  CONSTRAINT `fk_mn_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mn_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mn_user` FOREIGN KEY (`mentioned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mn_msg` FOREIGN KEY (`message_id`) REFERENCES `task_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($sql) === TRUE) {
    echo "Table mention_notifications created successfully\n";
}
else {
    echo "Error creating table: " . $db->error . "\n";
}

$db->close();
?>
