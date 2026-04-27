<?php
// classes/TaskController.php

class TaskController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Calculate score impact based on completion time vs due date.
     * @param string $dueDateStr
     * @param string $completedDateStr
     * @return array ['score' => int, 'reason' => string]
     */
    public function calculateScoreImpact($dueDateStr, $completedDateStr = 'now') {
        $dueDate = new DateTime($dueDateStr);
        $completedDate = new DateTime($completedDateStr);
        
        // Clone dates to avoid modifying originals if needed later
        $due = clone $dueDate;
        $done = clone $completedDate;

        // Calculate difference immediately
        $interval = $done->diff($due);
        // $interval->invert is 1 if $done > $due (Late)

        // 1. Check for Early Completion (More than 24 hours before deadline)
        // We modify due date by subtracting 24 hours to check against completion
        $earlyThreshold = clone $dueDate;
        $earlyThreshold->modify('-24 hours');

        if ($done <= $earlyThreshold) {
            return ['score' => 5, 'reason' => 'Early Completion'];
        }

        // 2. Check for On-Time Completion (Before or exactly at deadline)
        if ($done <= $due) {
            return ['score' => 2, 'reason' => 'On-Time Completion'];
        }

        // 3. Delayed Completion (After deadline)
        // No negative points: only award points on completion.
        return ['score' => 0, 'reason' => 'Delayed Completion (No Points)'];
    }

    /**
     * Update user's performance score and log the change.
     * @param int $userId
     * @param int $taskId
     * @param int $scoreChange
     * @param string $reason
     * @return bool
     */
    public function updateUserScore($userId, $taskId, $scoreChange, $reason) {
        if ($scoreChange == 0) return true;

        try {
            // 1. Fetch current score
            $stmt = $this->db->prepare("SELECT performance_score FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $currentScore = $row['performance_score'];
            } else {
                return false; // User not found
            }
            $stmt->close();

            // 2. Calculate New Score with Caps (0 - 120)
            $newScore = $currentScore + $scoreChange;
            if ($newScore > 120) $newScore = 120;
            if ($newScore < 0) $newScore = 0;

            // 3. Update User Table
            $updateStmt = $this->db->prepare("UPDATE users SET performance_score = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $newScore, $userId);
            $updateStmt->execute();
            $updateStmt->close();

            // 4. Insert Log
            $logStmt = $this->db->prepare("INSERT INTO performance_logs (user_id, task_id, score_change, reason) VALUES (?, ?, ?, ?)");
            $logStmt->bind_param("iiis", $userId, $taskId, $scoreChange, $reason);
            $logStmt->execute();
            $logStmt->close();

            return true;

        } catch (Exception $e) {
            // Log error
            return false;
        }
    }
}
?>
