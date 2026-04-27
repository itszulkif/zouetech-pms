<?php
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
check_login();
require_role(['Super Admin']);
include 'includes/header.php';
include 'includes/sidebar.php';

$database = new Database();
$db = $database->connect();

$userRole = $_SESSION['role'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
$deptId = (int)($_SESSION['department_id'] ?? 0);

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$projectReviewTasks = [];
$directReviewTasks = [];

// Project tasks in Review status
$projectSql = "
    SELECT
        t.id,
        t.title,
        t.description,
        t.priority,
        t.due_date,
        t.specific_time,
        p.name AS project_name,
        COALESCE(
            (SELECT GROUP_CONCAT(u2.full_name ORDER BY u2.full_name SEPARATOR ', ')
             FROM task_assignments ta2
             JOIN users u2 ON u2.id = ta2.user_id
             WHERE ta2.task_id = t.id),
            u.full_name
        ) AS assignee_names
    FROM tasks t
    LEFT JOIN projects p ON p.id = t.project_id
    LEFT JOIN users u ON u.id = t.assigned_to
    WHERE t.project_id IS NOT NULL
      AND t.project_id <> 0
      AND t.status = 'Review'
";

if ($userRole === 'Team Member') {
    $projectSql .= " AND (
        t.assigned_to = $userId
        OR EXISTS (SELECT 1 FROM task_assignments ta_self WHERE ta_self.task_id = t.id AND ta_self.user_id = $userId)
    )";
} elseif ($userRole === 'Department Head' && $deptId > 0) {
    $projectSql .= " AND (
        EXISTS (
            SELECT 1
            FROM task_assignments ta_dh
            JOIN users u_dh ON u_dh.id = ta_dh.user_id
            WHERE ta_dh.task_id = t.id AND u_dh.department_id = $deptId
        )
        OR EXISTS (
            SELECT 1
            FROM users u_primary
            WHERE u_primary.id = t.assigned_to AND u_primary.department_id = $deptId
        )
    )";
}

$projectSql .= " ORDER BY t.due_date ASC, t.id DESC";
$projectRes = $db->query($projectSql);
if ($projectRes) {
    while ($row = $projectRes->fetch_assoc()) {
        $projectReviewTasks[] = $row;
    }
}

// Direct tasks in Review status
$directSql = "
    SELECT
        t.id,
        t.title,
        t.description,
        t.priority,
        COALESCE(t.end_date, DATE(t.due_date)) AS due_date,
        t.specific_time,
        COALESCE(
            (SELECT GROUP_CONCAT(u2.full_name ORDER BY u2.full_name SEPARATOR ', ')
             FROM task_assignments ta2
             JOIN users u2 ON u2.id = ta2.user_id
             WHERE ta2.task_id = t.id),
            u.full_name
        ) AS assignee_names
    FROM tasks t
    LEFT JOIN users u ON u.id = t.assigned_to
    WHERE (t.project_id IS NULL OR t.project_id = 0)
      AND t.status = 'Review'
";

if ($userRole === 'Team Member') {
    $directSql .= " AND (
        t.assigned_to = $userId
        OR EXISTS (SELECT 1 FROM task_assignments ta_self WHERE ta_self.task_id = t.id AND ta_self.user_id = $userId)
    )";
} elseif ($userRole === 'Department Head' && $deptId > 0) {
    $directSql .= " AND (
        EXISTS (
            SELECT 1
            FROM task_assignments ta_dh
            JOIN users u_dh ON u_dh.id = ta_dh.user_id
            WHERE ta_dh.task_id = t.id AND u_dh.department_id = $deptId
        )
        OR EXISTS (
            SELECT 1
            FROM users u_primary
            WHERE u_primary.id = t.assigned_to AND u_primary.department_id = $deptId
        )
    )";
}

$directSql .= " ORDER BY due_date ASC, t.id DESC";
$directRes = $db->query($directSql);
if ($directRes) {
    while ($row = $directRes->fetch_assoc()) {
        $directReviewTasks[] = $row;
    }
}
?>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">
    <header class="h-16 flex items-center justify-between px-4 md:px-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex items-center space-x-3 md:space-x-4">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <div>
                <h1 class="text-lg md:text-xl font-bold text-white uppercase font-tech tracking-wider">Review Queue</h1>
                <p class="text-[10px] text-amber-400 font-mono hidden sm:block">Tasks waiting for admin review</p>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-x-hidden overflow-y-auto p-3 md:p-6 space-y-6">
        <section class="glass rounded-xl border border-indigo-500/20 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-700 bg-indigo-500/10 flex items-center justify-between">
                <h2 class="text-sm font-bold text-indigo-300 font-tech tracking-wide uppercase">Project Tasks in Review</h2>
                <span id="projectReviewCount" class="text-[11px] font-mono text-indigo-300"><?php echo count($projectReviewTasks); ?> task(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[760px]">
                    <thead class="bg-gray-800/80 text-gray-400 text-xs font-mono uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Task</th>
                            <th class="py-3 px-4">Project</th>
                            <th class="py-3 px-4">Assignees</th>
                            <th class="py-3 px-4">Due</th>
                            <th class="py-3 px-4">Priority</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Update</th>
                        </tr>
                    </thead>
                    <tbody id="projectReviewBody" class="divide-y divide-gray-700/50 text-gray-300">
                        <?php if (empty($projectReviewTasks)): ?>
                            <tr class="empty-row"><td colspan="7" class="py-8 px-4 text-center text-gray-500 font-mono text-xs uppercase">No project tasks currently in review.</td></tr>
                        <?php else: ?>
                            <?php foreach ($projectReviewTasks as $task): ?>
                                <tr class="hover:bg-gray-800/40 transition-colors" data-task-row="project" data-task-id="<?php echo (int)$task['id']; ?>">
                                    <td class="py-3 px-4">
                                        <a href="tasks.php?project_id=all&task_id=<?php echo (int)$task['id']; ?>" class="text-white hover:text-cyan-300 transition-colors"><?php echo e($task['title']); ?></a>
                                        <div class="text-[11px] text-gray-400 mt-1"><?php echo e($task['description'] ?: 'No description'); ?></div>
                                    </td>
                                    <td class="py-3 px-4 text-indigo-300"><?php echo e($task['project_name'] ?: '-'); ?></td>
                                    <td class="py-3 px-4 text-sm"><?php echo e($task['assignee_names'] ?: 'Unassigned'); ?></td>
                                    <td class="py-3 px-4 font-mono text-xs"><?php echo e($task['due_date'] ?: '-'); ?><?php echo !empty($task['specific_time']) ? ' @ ' . e(substr($task['specific_time'], 0, 5)) : ''; ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded border border-gray-600 text-[10px] uppercase tracking-wider"><?php echo e($task['priority'] ?: 'Medium'); ?></span></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded border border-purple-500/40 text-purple-300 text-[10px] uppercase tracking-wider">Review</span></td>
                                    <td class="py-3 px-4">
                                        <select class="review-status-select bg-gray-800 border border-gray-700 rounded px-2 py-1 text-xs text-white" data-task-id="<?php echo (int)$task['id']; ?>" data-table="project">
                                            <option value="Pending">Pending</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Review" selected>Review</option>
                                            <option value="Completed">Completed</option>
                                            <option value="Missed">Missed</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="glass rounded-xl border border-purple-500/20 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-700 bg-purple-500/10 flex items-center justify-between">
                <h2 class="text-sm font-bold text-purple-300 font-tech tracking-wide uppercase">Direct Tasks in Review</h2>
                <span id="directReviewCount" class="text-[11px] font-mono text-purple-300"><?php echo count($directReviewTasks); ?> task(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[760px]">
                    <thead class="bg-gray-800/80 text-gray-400 text-xs font-mono uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Task</th>
                            <th class="py-3 px-4">Assignees</th>
                            <th class="py-3 px-4">Due</th>
                            <th class="py-3 px-4">Priority</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Update</th>
                            <th class="py-3 px-4">Open</th>
                        </tr>
                    </thead>
                    <tbody id="directReviewBody" class="divide-y divide-gray-700/50 text-gray-300">
                        <?php if (empty($directReviewTasks)): ?>
                            <tr class="empty-row"><td colspan="7" class="py-8 px-4 text-center text-gray-500 font-mono text-xs uppercase">No direct tasks currently in review.</td></tr>
                        <?php else: ?>
                            <?php foreach ($directReviewTasks as $task): ?>
                                <tr class="hover:bg-gray-800/40 transition-colors" data-task-row="direct" data-task-id="<?php echo (int)$task['id']; ?>">
                                    <td class="py-3 px-4">
                                        <a href="direct_tasks.php?task_id=<?php echo (int)$task['id']; ?>" class="text-white hover:text-cyan-300 transition-colors"><?php echo e($task['title']); ?></a>
                                        <div class="text-[11px] text-gray-400 mt-1"><?php echo e($task['description'] ?: 'No description'); ?></div>
                                    </td>
                                    <td class="py-3 px-4 text-sm"><?php echo e($task['assignee_names'] ?: 'Unassigned'); ?></td>
                                    <td class="py-3 px-4 font-mono text-xs"><?php echo e($task['due_date'] ?: '-'); ?><?php echo !empty($task['specific_time']) ? ' @ ' . e(substr($task['specific_time'], 0, 5)) : ''; ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded border border-gray-600 text-[10px] uppercase tracking-wider"><?php echo e($task['priority'] ?: 'Medium'); ?></span></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded border border-purple-500/40 text-purple-300 text-[10px] uppercase tracking-wider">Review</span></td>
                                    <td class="py-3 px-4">
                                        <select class="review-status-select bg-gray-800 border border-gray-700 rounded px-2 py-1 text-xs text-white" data-task-id="<?php echo (int)$task['id']; ?>" data-table="direct">
                                            <option value="Pending">Pending</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Review" selected>Review</option>
                                            <option value="Completed">Completed</option>
                                            <option value="Missed">Missed</option>
                                        </select>
                                    </td>
                                    <td class="py-3 px-4"><a href="direct_tasks.php?task_id=<?php echo (int)$task['id']; ?>" class="text-purple-300 hover:text-purple-200 text-xs font-mono">Open Task</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<script>
    $(document).ready(function () {
        function updateSectionCount(section) {
            const tbody = section === 'project' ? $('#projectReviewBody') : $('#directReviewBody');
            const countNode = section === 'project' ? $('#projectReviewCount') : $('#directReviewCount');
            const count = tbody.find('tr[data-task-row="' + section + '"]').length;
            countNode.text(count + ' task(s)');

            if (count === 0 && tbody.find('tr.empty-row').length === 0) {
                const message = section === 'project'
                    ? 'No project tasks currently in review.'
                    : 'No direct tasks currently in review.';
                tbody.html('<tr class="empty-row"><td colspan="7" class="py-8 px-4 text-center text-gray-500 font-mono text-xs uppercase">' + message + '</td></tr>');
            }
        }

        $(document).on('change', '.review-status-select', function () {
            const select = $(this);
            const taskId = parseInt(select.data('task-id'), 10);
            const tableType = String(select.data('table') || '');
            const newStatus = String(select.val() || '');
            const row = select.closest('tr');

            if (!taskId || !newStatus) return;

            select.prop('disabled', true);
            $.ajax({
                url: 'api/update_task_status.php',
                type: 'POST',
                dataType: 'json',
                data: { task_id: taskId, status: newStatus },
                success: function (response) {
                    if (!response || response.status !== 'success') {
                        alert('Failed to update status: ' + ((response && response.message) ? response.message : 'Unknown error'));
                        select.val('Review');
                        return;
                    }

                    // This page shows Review-only queues, so remove rows moved away from Review.
                    if (newStatus !== 'Review') {
                        row.remove();
                        updateSectionCount(tableType);
                    }
                },
                error: function () {
                    alert('Unable to update task status right now.');
                    select.val('Review');
                },
                complete: function () {
                    select.prop('disabled', false);
                }
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
