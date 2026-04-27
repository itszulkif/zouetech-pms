-- Table to track when each user last read each task's chat (for unread badge).
-- Run this once to add the table.

CREATE TABLE IF NOT EXISTS `task_chat_read` (
  `user_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`, `task_id`),
  KEY `task_id` (`task_id`),
  CONSTRAINT `task_chat_read_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_chat_read_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
