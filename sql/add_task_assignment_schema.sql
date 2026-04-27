-- Required schema for task assignment and pending approval flows.
-- Run once during deployment (before serving traffic).

ALTER TABLE tasks
    ADD COLUMN IF NOT EXISTS start_date DATE NULL,
    ADD COLUMN IF NOT EXISTS end_date DATE NULL,
    ADD COLUMN IF NOT EXISTS specific_time TIME NULL,
    ADD COLUMN IF NOT EXISTS due_date DATE NULL;

ALTER TABLE tasks
    MODIFY COLUMN project_id INT(11) NULL,
    MODIFY COLUMN priority ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
    MODIFY COLUMN status ENUM('Pending','Pending Approval','In Progress','Completed','Review','Missed') NOT NULL DEFAULT 'Pending';

CREATE TABLE IF NOT EXISTS task_assignments (
    task_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id, user_id),
    KEY idx_ta_user (user_id),
    CONSTRAINT fk_ta_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS task_assignment_pending (
    task_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    requested_by INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS task_assignment_notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    task_id INT(11) NOT NULL,
    recipient_user_id INT(11) NOT NULL,
    sender_user_id INT(11) NOT NULL,
    notification_type ENUM('direct_task','project_task') NOT NULL,
    title_snapshot VARCHAR(255) NOT NULL,
    message VARCHAR(500) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tan_task_recipient_type (task_id, recipient_user_id, notification_type),
    KEY idx_tan_recipient (recipient_user_id, is_read, created_at),
    KEY idx_tan_task (task_id),
    CONSTRAINT fk_tan_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_tan_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tan_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS major_project_departments (
    major_project_id INT(11) NOT NULL,
    department_id INT(11) NOT NULL,
    PRIMARY KEY (major_project_id, department_id),
    CONSTRAINT fk_mpd_project FOREIGN KEY (major_project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_mpd_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
