<?php
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
require_role(['Super Admin']);

$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
if ($department_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$db = (new Database())->connect();

$dept_stmt = $db->prepare("SELECT id, name FROM departments WHERE id = ? LIMIT 1");
$dept_stmt->bind_param("i", $department_id);
$dept_stmt->execute();
$department = $dept_stmt->get_result()->fetch_assoc();
$dept_stmt->close();

if (!$department) {
    header('Location: dashboard.php');
    exit;
}

$project_tasks = [];
$sql_project_tasks = "
    SELECT
        t.id,
        t.title,
        t.status,
        t.priority,
        t.due_date,
        p.name AS project_name,
        COALESCE(
            (SELECT GROUP_CONCAT(u2.full_name ORDER BY u2.full_name SEPARATOR ', ')
             FROM task_assignments ta2
             JOIN users u2 ON u2.id = ta2.user_id
             WHERE ta2.task_id = t.id),
            u.full_name
        ) AS assigned_name
    FROM tasks t
    JOIN projects p ON p.id = t.project_id
    LEFT JOIN users u ON u.id = t.assigned_to
    WHERE p.department_id = ? AND t.project_id IS NOT NULL AND t.project_id > 0
    ORDER BY t.created_at DESC
";
$stmt = $db->prepare($sql_project_tasks);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $project_tasks[] = $row;
}
$stmt->close();

$direct_tasks = [];
$sql_direct_tasks = "
    SELECT
        t.id,
        t.title,
        t.status,
        t.priority,
        t.due_date,
        COALESCE(
            (SELECT GROUP_CONCAT(u2.full_name ORDER BY u2.full_name SEPARATOR ', ')
             FROM task_assignments ta2
             JOIN users u2 ON u2.id = ta2.user_id
             WHERE ta2.task_id = t.id),
            u.full_name
        ) AS assigned_name
    FROM tasks t
    LEFT JOIN users u ON u.id = t.assigned_to
    WHERE (t.project_id IS NULL OR t.project_id = 0)
      AND (
          EXISTS (
              SELECT 1
              FROM task_assignments ta
              JOIN users ux ON ux.id = ta.user_id
              WHERE ta.task_id = t.id AND ux.department_id = ?
          )
          OR EXISTS (
              SELECT 1
              FROM users ua
              WHERE ua.id = t.assigned_to AND ua.department_id = ?
          )
      )
    GROUP BY t.id
    ORDER BY t.created_at DESC
";
$stmt = $db->prepare($sql_direct_tasks);
$stmt->bind_param("ii", $department_id, $department_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $direct_tasks[] = $row;
}
$stmt->close();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">
    <header class="h-16 flex items-center justify-between gap-2 px-3 sm:px-4 md:px-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
            </button>
            <div class="min-w-0">
                <h1 class="truncate text-base sm:text-lg md:text-xl leading-tight font-bold text-white font-tech tracking-wide sm:tracking-wider uppercase">Department Details</h1>
                <p class="truncate text-[10px] text-cyan-400 font-mono hidden sm:block"><?php echo htmlspecialchars($department['name']); ?> — Project + Direct Tasks</p>
            </div>
        </div>
        <a href="dashboard.php" class="shrink-0 text-[10px] sm:text-xs font-mono text-cyan-400 hover:text-cyan-300 transition-colors whitespace-nowrap">
            <span class="sm:hidden">Back</span>
            <span class="hidden sm:inline">← Back to Dashboard</span>
        </a>
    </header>

    <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6">
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm md:text-base font-bold text-white font-tech uppercase tracking-wide">Project Tasks</h2>
                <span class="text-[10px] font-mono text-cyan-400 bg-cyan-500/10 border border-cyan-500/20 px-2 py-0.5 rounded"><?php echo count($project_tasks); ?> tasks</span>
            </div>
            <div class="glass rounded-xl border border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[700px]">
                        <thead class="bg-gray-800/80 text-gray-400 text-[10px] font-mono uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5 px-4">Task</th>
                                <th class="px-3">Project</th>
                                <th class="px-3">Assignee</th>
                                <th class="px-3">Status</th>
                                <th class="py-2.5 px-4 text-right">Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50 text-gray-300 text-xs">
                            <?php if (empty($project_tasks)): ?>
                                <tr><td colspan="5" class="py-8 text-center text-gray-500 font-mono">No project tasks found for this department.</td></tr>
                            <?php else: ?>
                                <?php foreach ($project_tasks as $t): ?>
                                    <tr class="hover:bg-gray-800/40 transition-colors">
                                        <td class="py-3 px-4 font-semibold text-white"><?php echo htmlspecialchars($t['title']); ?></td>
                                        <td class="px-3 text-gray-400"><?php echo htmlspecialchars($t['project_name'] ?? '-'); ?></td>
                                        <td class="px-3 text-gray-400"><?php echo htmlspecialchars($t['assigned_name'] ?? 'Unassigned'); ?></td>
                                        <td class="px-3"><?php echo htmlspecialchars($t['status']); ?></td>
                                        <td class="px-4 text-right font-mono text-gray-400"><?php echo htmlspecialchars($t['due_date'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm md:text-base font-bold text-white font-tech uppercase tracking-wide">Direct Tasks</h2>
                <span class="text-[10px] font-mono text-purple-400 bg-purple-500/10 border border-purple-500/20 px-2 py-0.5 rounded"><?php echo count($direct_tasks); ?> tasks</span>
            </div>
            <div class="glass rounded-xl border border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[650px]">
                        <thead class="bg-gray-800/80 text-gray-400 text-[10px] font-mono uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5 px-4">Task</th>
                                <th class="px-3">Assignee</th>
                                <th class="px-3">Priority</th>
                                <th class="px-3">Status</th>
                                <th class="py-2.5 px-4 text-right">Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50 text-gray-300 text-xs">
                            <?php if (empty($direct_tasks)): ?>
                                <tr><td colspan="5" class="py-8 text-center text-gray-500 font-mono">No direct tasks found for this department.</td></tr>
                            <?php else: ?>
                                <?php foreach ($direct_tasks as $t): ?>
                                    <tr class="hover:bg-gray-800/40 transition-colors">
                                        <td class="py-3 px-4 font-semibold text-white"><?php echo htmlspecialchars($t['title']); ?></td>
                                        <td class="px-3 text-gray-400"><?php echo htmlspecialchars($t['assigned_name'] ?? 'Unassigned'); ?></td>
                                        <td class="px-3 text-gray-400"><?php echo htmlspecialchars($t['priority'] ?? 'Medium'); ?></td>
                                        <td class="px-3"><?php echo htmlspecialchars($t['status']); ?></td>
                                        <td class="px-4 text-right font-mono text-gray-400"><?php echo htmlspecialchars($t['due_date'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
