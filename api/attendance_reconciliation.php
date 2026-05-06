<?php

function ensure_attendance_sheet_schema(mysqli $db): void
{
    @$db->query("CREATE TABLE IF NOT EXISTS attendance_sheet_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        role_snapshot VARCHAR(50) NOT NULL,
        department_id_snapshot INT(11) DEFAULT NULL,
        attendance_date DATE NOT NULL,
        sign_in_at DATETIME NOT NULL,
        sign_out_at DATETIME DEFAULT NULL,
        sign_out_method ENUM('Manual', 'Automatic') NOT NULL DEFAULT 'Manual',
        activity_report TEXT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_user_day (user_id, attendance_date),
        KEY idx_att_sheet_user (user_id),
        KEY idx_att_sheet_date (attendance_date),
        CONSTRAINT fk_att_sheet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columnRes = $db->query("SELECT COUNT(*) AS cnt
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'attendance_sheet_logs'
          AND COLUMN_NAME = 'sign_out_method'");
    $columnCount = 0;
    if ($columnRes) {
        $columnRow = $columnRes->fetch_assoc();
        $columnCount = (int)($columnRow['cnt'] ?? 0);
        $columnRes->close();
    }

    if ($columnCount === 0) {
        @$db->query("ALTER TABLE attendance_sheet_logs
            ADD COLUMN sign_out_method ENUM('Manual', 'Automatic') NOT NULL DEFAULT 'Manual' AFTER sign_out_at");
    }
}

function reconcile_attendance_auto_sign_out(mysqli $db, ?int $userId = null): int
{
    $today = date('Y-m-d');
    $cutoffReached = (date('H:i:s') >= '23:00:00') ? 1 : 0;
    $autoNote = 'Auto sign-out at 11:00 PM: missed manual sign-out, attendance marked Absent.';

    $sql = "UPDATE attendance_sheet_logs
        SET sign_out_at = sign_in_at,
            sign_out_method = 'Automatic',
            activity_report = ?
        WHERE sign_in_at IS NOT NULL
          AND sign_out_at IS NULL
          AND (
                attendance_date < ?
                OR (attendance_date = ? AND ? = 1)
          )";

    $types = "sssi";
    $params = [$autoNote, $today, $today, $cutoffReached];

    if ($userId !== null && $userId > 0) {
        $sql .= " AND user_id = ?";
        $types .= "i";
        $params[] = $userId;
    }

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    return max(0, (int)$affected);
}
?>
