<?php
// api/get_canvas_data.php
require_once '../includes/auth_middleware.php';
require_role(['Super Admin', 'Department Head']);
header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

$response = [
    'stats' => [],
    'department_progress' => [],
    'department_scores' => [],
    'project_health' => [],
    'proximity_data' => [],
    'proximity_alerts' => []
];

$is_super = ($_SESSION['role'] === 'Super Admin');
$dept_id = $_SESSION['department_id'] ?? 0;

// Helper for filtering
$dept_filter = $is_super ? "" : " AND department_id = $dept_id";
$dept_filter_where = $is_super ? "" : " WHERE id = $dept_id";
$project_filter = $is_super ? "" : " AND department_id = $dept_id";

// Keep task statuses dynamic before canvas aggregations.
$db->query("
    UPDATE tasks
    SET status = 'Missed'
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
      )
");

// 1. Overall Stats
$stats = [];
// Total Projects
$res = $db->query("SELECT COUNT(*) as count FROM projects WHERE 1=1 $project_filter");
$stats['total_projects'] = $res->fetch_assoc()['count'];

// Active Tasks (Joined with projects to filter by dept if needed)
$task_join = $is_super ? "" : " JOIN projects p ON t.project_id = p.id ";
$task_filter = $is_super ? "" : " AND p.department_id = $dept_id ";
$res = $db->query("SELECT COUNT(*) as count FROM tasks t $task_join WHERE t.status IN ('Pending', 'In Progress', 'Review') $task_filter");
$stats['active_tasks'] = $res->fetch_assoc()['count'];

// Overall Completion Rate
$res = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed
    FROM tasks t $task_join WHERE 1=1 $task_filter");
$row = $res->fetch_assoc();
$stats['completion_rate'] = ($row['total'] > 0) ? round(($row['completed'] / $row['total']) * 100) : 0;

// Total Departments
$res = $db->query("SELECT COUNT(*) as count FROM departments $dept_filter_where");
$stats['total_departments'] = $res->fetch_assoc()['count'];

// Avg Performance Score (Dept specific or global)
$user_filter = $is_super ? "" : " WHERE department_id = $dept_id";
$res = $db->query("SELECT AVG(performance_score) as avg FROM users $user_filter");
$stats['avg_performance'] = round($res->fetch_assoc()['avg']);

$response['stats'] = $stats;

// 2. Departmental Progress (Charts)
// 2. Departmental Progress
$query = "SELECT d.name, 
    IFNULL(AVG(p.progress_percentage), 0) as avg_progress,
    COUNT(p.id) as project_count
    FROM departments d
    LEFT JOIN projects p ON d.id = p.department_id
    $dept_filter_where
    GROUP BY d.id";
$result = $db->query($query);
while($row = $result->fetch_assoc()) {
    $response['department_progress'][] = [
        'name' => $row['name'],
        'progress' => round($row['avg_progress']),
        'projects' => $row['project_count']
    ];
}

// 2.1 Department Scores (for Heatmap)
// 2.1 Department Scores
$query = "SELECT d.name, 
    IFNULL((SELECT AVG(performance_score) FROM users u WHERE u.department_id = d.id), 0) as avg_score
    FROM departments d
    $dept_filter_where";
$result = $db->query($query);
while($row = $result->fetch_assoc()) {
    $response['department_scores'][] = [
        'name' => $row['name'],
        'score' => round($row['avg_score'])
    ];
}

// 3. Project Health (At Risk detection)
// 3. Project Health
$health_where = $is_super ? "" : " AND p.department_id = $dept_id";
$query = "SELECT p.name, d.name as department_name, p.progress_percentage, p.end_date,
    (SELECT COUNT(*) FROM tasks t
     WHERE t.project_id = p.id
       AND t.status != 'Completed'
       AND t.due_date IS NOT NULL
       AND (
         (t.specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(t.due_date), t.specific_time))
         OR
         (t.specific_time IS NULL AND NOW() >= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
       )) as delayed_tasks
    FROM projects p
    JOIN departments d ON p.department_id = d.id
    WHERE p.status = 'In Progress' $health_where
    HAVING delayed_tasks > 0 OR (p.end_date < NOW() AND p.progress_percentage < 100)
    ORDER BY delayed_tasks DESC
    LIMIT 10";
$result = $db->query($query);
while($row = $result->fetch_assoc()) {
    $response['project_health'][] = $row;
}

// 4. Project Deadline Proximity (Categorized)
$response['proximity_data'] = [
    'Overdue' => 0,
    'Critical' => 0, // < 7 days
    'Active' => 0,   // < 30 days
    'Future' => 0    // > 30 days
];

// 4. Proximity Data
$query = "SELECT 
    CASE 
        WHEN end_date < NOW() AND progress_percentage < 100 THEN 'Overdue'
        WHEN DATEDIFF(end_date, NOW()) <= 7 THEN 'Critical'
        WHEN DATEDIFF(end_date, NOW()) <= 30 THEN 'Active'
        ELSE 'Future'
    END as proximity,
    COUNT(*) as count
    FROM projects 
    WHERE status != 'Completed' $project_filter
    GROUP BY proximity";

$result = $db->query($query);
while($row = $result->fetch_assoc()) {
    $response['proximity_data'][$row['proximity']] = (int)$row['count'];
}

// 5. Specific Proximity Alerts (Due soon)
// 5. Proximity Alerts
$query = "SELECT name, project_type, end_date, DATEDIFF(end_date, NOW()) as days_left
          FROM projects 
          WHERE status != 'Completed' AND end_date >= NOW() $project_filter
          ORDER BY end_date ASC
          LIMIT 10";
$result = $db->query($query);
while($row = $result->fetch_assoc()) {
    $response['proximity_alerts'][] = $row;
}

echo json_encode(['success' => true, 'data' => $response]);
$db->close();
?>
