<?php
// classes/PerformanceEngine.php

class PerformanceEngine {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Calculate and update score when a task is completed.
     * 
     * @param int $userId The ID of the user.
     * @param int $taskId The ID of the task.
     * @param string $taskStatus 'Completed' or 'Missed'.
     * @return bool True on success, False on failure.
     */
    public function updateScore($userId, $taskId, $taskStatus) {
        // Fetch task details (Deadline)
        $query = "SELECT due_date FROM tasks WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false; // Task not found
        }

        $task = $result->fetch_assoc();
        $dueDate = new DateTime($task['due_date']);
        $now = new DateTime(); // Current Time

        $scoreChange = 0;
        $reason = "";

        if ($taskStatus === 'Completed') {
            if ($now < $dueDate) {
                // Before deadline
                $scoreChange = 5;
                $reason = "Early Submission";
            } elseif ($now == $dueDate) {
                // EXACT match (unlikely but possible if day-based) or slightly buffered
                // For simplicity, let's say "same day" or within a small window. 
                // But strict comparison:
                 $scoreChange = 2;
                 $reason = "On-Time Completion";
            } else {
                // Delayed
                $scoreChange = -5;
                $reason = "Late Submission";
            }
        } elseif ($taskStatus === 'Missed') {
            $scoreChange = -10;
            $reason = "Missed Deadline";
        }

        // Update User Score
        return $this->applyScoreChange($userId, $taskId, $scoreChange, $reason);
    }

    private function applyScoreChange($userId, $taskId, $scoreChange, $reason) {
        // Get current score
        $query = "SELECT performance_score FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $currentScore = $user['performance_score'];
        $newScore = $currentScore + $scoreChange;

        // Cap score between 0 and 120
        if ($newScore > 120) $newScore = 120;
        if ($newScore < 0) $newScore = 0;

        // Transaction Start
        $this->conn->begin_transaction();

        try {
            // 1. Update User Table
            $updateQuery = "UPDATE users SET performance_score = ? WHERE id = ?";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bind_param("ii", $newScore, $userId);
            $updateStmt->execute();

            // 2. Insert into Performance Logs
            $logQuery = "INSERT INTO performance_logs (user_id, task_id, score_change, reason) VALUES (?, ?, ?, ?)";
            $logStmt = $this->conn->prepare($logQuery);
            $logStmt->bind_param("iiis", $userId, $taskId, $scoreChange, $reason);
            $logStmt->execute();

            // Commit
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}
?>
