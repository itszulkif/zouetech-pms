<?php
// api/check_missed_tasks.php
require_once '../db_connect.php';
require_once '../classes/TaskController.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();
$controller = new TaskController($db);

$count = 0;

try {
    // Find tasks that are overdue and not yet marked as Missed or Completed.
    // Exclude Pending Approval (awaiting Super Admin approval).
    // Both direct and project tasks use due-date + specific-time deadline logic.
    // Without specific_time, tasks become missed after the due day ends.
    $query = "SELECT id, title, assigned_to, due_date, project_id, end_date, specific_time
              FROM tasks
              WHERE status NOT IN ('Completed', 'Missed', 'Pending Approval')
                AND (
                    (
                        (project_id IS NULL OR project_id = 0)
                        AND COALESCE(end_date, DATE(due_date)) IS NOT NULL
                        AND (
                            (specific_time IS NOT NULL AND NOW() > TIMESTAMP(COALESCE(end_date, DATE(due_date)), specific_time))
                            OR
                            (specific_time IS NULL AND NOW() >= DATE_ADD(COALESCE(end_date, DATE(due_date)), INTERVAL 1 DAY))
                        )
                    )
                    OR
                    (
                        (project_id IS NOT NULL AND project_id <> 0)
                        AND due_date IS NOT NULL
                        AND (
                            (specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(due_date), specific_time))
                            OR
                            (specific_time IS NULL AND NOW() >= DATE_ADD(DATE(due_date), INTERVAL 1 DAY))
                        )
                    )
                )";
    
    $result = $db->query($query);
    
    $missedTasks = [];
    while ($row = $result->fetch_assoc()) {
        $missedTasks[] = $row;
    }

    foreach ($missedTasks as $task) {
        // 1. Mark as Missed
        $updateQuery = "UPDATE tasks SET status = 'Missed' WHERE id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->bind_param("i", $task['id']);
        $stmt->execute();
        $stmt->close();

        // 2. No deduction for missed tasks.
        // Performance points are award-only based on task completion.
        $count++;
    }

    echo json_encode([
        'status' => 'success', 
        'message' => "Processed $count missed tasks.",
        'processed_count' => $count
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$db->close();
?>
