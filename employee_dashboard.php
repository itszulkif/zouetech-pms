<?php
// employee_dashboard.php
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
check_login();

// Super Admins have the Project Canvas (dashboard.php) — My Desk is not for them
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin') {
    header('Location: projects.php');
    exit;
}

include 'includes/header.php';
include 'includes/sidebar.php';

$database = new Database();
$db = $database->connect();

// Placeholder for logged-in user
$user_id = $_SESSION['user_id'];
$isTeamMemberLike = in_array($_SESSION['role'] ?? '', ['Team Member', 'Team Lead'], true);

function task_deadline_timestamp($dueDate, $specificTime = null) {
    if (empty($dueDate)) {
        return null;
    }
    $dateOnly = date('Y-m-d', strtotime((string)$dueDate));
    $deadline = !empty($specificTime)
        ? strtotime($dateOnly . ' ' . $specificTime)
        : strtotime($dateOnly . ' 23:59:59');
    return ($deadline === false) ? null : $deadline;
}

// 1. Fetch User Performance
$employee_name = "Unknown User";
$employee_role = "Team Member";
$current_score = 0;

$stmt = $db->prepare("SELECT full_name, role, performance_score FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if($user = $result->fetch_assoc()) {
    $employee_name = $user['full_name'];
    $employee_role = $user['role'];
    $current_score = $user['performance_score'];
} $stmt->close();

// 2. Fetch Recent Logs for ticker
$recent_logs = [];
$stmt = $db->prepare("SELECT score_change, reason, timestamp FROM performance_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $recent_logs[] = $row;
} $stmt->close();

// 3. Fetch Tasks
$tasks = [];
$query = "SELECT t.id, t.title, t.due_date, t.priority, p.name as project_name, u.full_name as assigned_by 
          FROM tasks t 
          JOIN projects p ON t.project_id = p.id 
          LEFT JOIN users u ON p.project_head_id = u.id 
          WHERE t.assigned_to = ? AND t.status != 'Completed'
          ORDER BY t.due_date ASC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $tasks[] = $row;
} $stmt->close();

// ── Task stats ──────────────────────────────────────────────────────────────
$total_pending = count($tasks); // already fetched above (non-completed)

// Completed tasks
$completed_count = 0;
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM tasks WHERE assigned_to = ? AND status = 'Completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_count = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Overdue tasks (due_date passed, not yet completed)
$overdue_count = 0;
$stmt = $db->prepare("SELECT COUNT(*) as cnt
                      FROM tasks
                      WHERE assigned_to = ?
                        AND status != 'Completed'
                        AND due_date IS NOT NULL
                        AND (
                            (specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(due_date), specific_time))
                            OR
                            (specific_time IS NULL AND NOW() >= DATE_ADD(DATE(due_date), INTERVAL 1 DAY))
                        )");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$overdue_count = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Incomplete = pending - overdue
$incomplete_count = max(0, $total_pending - $overdue_count);

// Total assigned ever
$total_assigned = $total_pending + $completed_count;

// ── Score sparkline trend (last 7 data points) ───────────────────────────────
$trend_scores = [$current_score];
$temp_score = $current_score;

$stmt = $db->prepare("SELECT score_change FROM performance_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 6");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$trend_res = $stmt->get_result();
while($row = $trend_res->fetch_assoc()) {
    $temp_score = $temp_score - $row['score_change'];
    $trend_scores[] = $temp_score;
} $stmt->close();
while(count($trend_scores) < 7) { $trend_scores[] = $temp_score; }
$score_trend = array_reverse($trend_scores);

// ── Monthly performance (last 6 months) ──────────────────────────────────────
$monthly_labels = [];
$monthly_scores = [];
$stmt = $db->prepare("
    SELECT DATE_FORMAT(timestamp, '%b %Y') as month_label,
           DATE_FORMAT(timestamp, '%Y-%m') as month_key,
           SUM(score_change) as net_change
    FROM performance_logs
    WHERE user_id = ?
    GROUP BY month_key
    ORDER BY month_key DESC
    LIMIT 6
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthly_res = $stmt->get_result();
$monthly_data = [];
while($row = $monthly_res->fetch_assoc()) {
    $monthly_data[] = ['label' => $row['month_label'], 'score' => (float)$row['net_change']];
} $stmt->close();
$monthly_data = array_reverse($monthly_data);
foreach($monthly_data as $m) {
    $monthly_labels[] = $m['label'];
    $monthly_scores[] = $m['score'];
}
if (empty($monthly_labels)) {
    $monthly_labels = ['This Month'];
    $monthly_scores = [0];
}

// ── Team Member: Project Tasks vs Direct Tasks (separate) ───────────────────
$project_tasks = [];
$direct_tasks = [];
$project_pending = 0;
$project_completed = 0;
$project_overdue = 0;
$direct_pending = 0;
$direct_completed = 0;
$direct_overdue = 0;
$project_completed_points = 0;
$direct_completed_points = 0;
$total_completed_points = 0;
$daily_labels = [];
$daily_scores = [];
$weekly_labels = [];
$weekly_scores = [];

if ($isTeamMemberLike) {
    // Project tasks: assigned_to = user, project_id IS NOT NULL
    $pq = "SELECT t.id, t.project_id, t.title, t.due_date, t.specific_time, t.priority, t.status, p.name as project_name
           FROM tasks t
           JOIN projects p ON t.project_id = p.id
           WHERE t.assigned_to = ? AND t.status != 'Completed'
           ORDER BY t.due_date ASC";
    $stmt = $db->prepare($pq);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $project_tasks[] = $row;
    $stmt->close();

    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.assigned_to = ? AND t.status != 'Completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $project_pending = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.assigned_to = ? AND t.status = 'Completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $project_completed = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $db->prepare("SELECT COUNT(*) as cnt
                          FROM tasks t
                          JOIN projects p ON t.project_id = p.id
                          WHERE t.assigned_to = ?
                            AND t.status != 'Completed'
                            AND t.due_date IS NOT NULL
                            AND (
                                (t.specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(t.due_date), t.specific_time))
                                OR
                                (t.specific_time IS NULL AND NOW() >= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
                            )");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $project_overdue = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // Direct tasks: via task_assignments, project_id IS NULL
    $dq = "SELECT t.id, t.title, t.due_date, t.specific_time, t.priority, t.status
           FROM tasks t
           INNER JOIN task_assignments ta ON ta.task_id = t.id
           WHERE ta.user_id = ? AND (t.project_id IS NULL OR t.project_id = 0) AND t.status != 'Completed'
           ORDER BY t.due_date ASC";
    $stmt = $db->prepare($dq);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $direct_tasks[] = $row;
    $stmt->close();

    $stmt = $db->prepare("SELECT COUNT(DISTINCT t.id) as cnt FROM tasks t INNER JOIN task_assignments ta ON ta.task_id = t.id WHERE ta.user_id = ? AND (t.project_id IS NULL OR t.project_id = 0) AND t.status != 'Completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $direct_pending = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $db->prepare("SELECT COUNT(DISTINCT t.id) as cnt FROM tasks t INNER JOIN task_assignments ta ON ta.task_id = t.id WHERE ta.user_id = ? AND (t.project_id IS NULL OR t.project_id = 0) AND t.status = 'Completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $direct_completed = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $db->prepare("SELECT COUNT(DISTINCT t.id) as cnt
                          FROM tasks t
                          INNER JOIN task_assignments ta ON ta.task_id = t.id
                          WHERE ta.user_id = ?
                            AND (t.project_id IS NULL OR t.project_id = 0)
                            AND t.status != 'Completed'
                            AND t.due_date IS NOT NULL
                            AND (
                                (t.specific_time IS NOT NULL AND NOW() > TIMESTAMP(DATE(t.due_date), t.specific_time))
                                OR
                                (t.specific_time IS NULL AND NOW() >= DATE_ADD(DATE(t.due_date), INTERVAL 1 DAY))
                            )");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $direct_overdue = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN pl.score_change > 0 THEN pl.score_change ELSE 0 END), 0) AS pts
                          FROM performance_logs pl
                          INNER JOIN tasks t ON t.id = pl.task_id
                          INNER JOIN projects p ON p.id = t.project_id
                          WHERE pl.user_id = ? AND t.assigned_to = ? AND t.status = 'Completed'");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $project_completed_points = (int)$stmt->get_result()->fetch_assoc()['pts'];
    $stmt->close();

    $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN pl.score_change > 0 THEN pl.score_change ELSE 0 END), 0) AS pts
                          FROM performance_logs pl
                          INNER JOIN tasks t ON t.id = pl.task_id
                          INNER JOIN task_assignments ta ON ta.task_id = t.id
                          WHERE pl.user_id = ?
                            AND ta.user_id = ?
                            AND t.status = 'Completed'
                            AND (t.project_id IS NULL OR t.project_id = 0)");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $direct_completed_points = (int)$stmt->get_result()->fetch_assoc()['pts'];
    $stmt->close();

    $total_completed_points = $project_completed_points + $direct_completed_points;

    // Daily scores (last 7 days)
    for ($i = 6; $i >= 0; $i--) {
        $d = (new DateTime('today'))->modify("-{$i} day");
        $daily_labels[] = $d->format('D');
        $daily_scores[] = 0;
    }
    $day_start = (new DateTime('today'))->modify('-6 day')->format('Y-m-d');
    $day_end = (new DateTime('today'))->format('Y-m-d');
    $stmt = $db->prepare("
        SELECT DATE(pl.timestamp) as day_key, SUM(pl.score_change) as net_change
        FROM performance_logs pl
        WHERE pl.user_id = ? AND DATE(pl.timestamp) BETWEEN ? AND ?
        GROUP BY day_key
    ");
    $stmt->bind_param("iss", $user_id, $day_start, $day_end);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $dayKey = date('D', strtotime((string)$row['day_key']));
        $idx = array_search($dayKey, $daily_labels, true);
        if ($idx !== false) $daily_scores[$idx] = (float)$row['net_change'];
    }
    $stmt->close();

    // Weekly scores (last 8 weeks)
    $week_from = (new DateTime('monday this week'))->modify('-7 week')->format('Y-m-d');
    $week_to   = (new DateTime('sunday this week'))->format('Y-m-d');
    $week_keys = [];
    for ($i = 7; $i >= 0; $i--) {
        $monday = new DateTime('monday this week');
        if ($i > 0) $monday->modify("-{$i} week");
        $week_keys[] = $monday->format('oW');
        $weekly_labels[] = 'W' . $monday->format('W');
        $weekly_scores[] = 0;
    }
    $stmt = $db->prepare("
        SELECT YEARWEEK(pl.timestamp, 3) as yw, SUM(pl.score_change) as net_change
        FROM performance_logs pl
        WHERE pl.user_id = ? AND DATE(pl.timestamp) BETWEEN ? AND ?
        GROUP BY yw
    ");
    $stmt->bind_param("iss", $user_id, $week_from, $week_to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $yw = strval($row['yw']);
        $key = substr($yw, 0, 4) . substr($yw, -2);
        $idx = array_search($key, $week_keys, true);
        if ($idx !== false) $weekly_scores[$idx] = (float)$row['net_change'];
    }
    $stmt->close();
}
?>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">
    <!-- Header -->
    <header class="h-16 flex items-center justify-between px-4 md:px-6 border-b border-gray-800 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex items-center space-x-3 md:space-x-4">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <h1 class="text-lg md:text-xl font-bold text-white font-tech tracking-wider">MY DESK</h1>
        </div>
        <div class="flex items-center space-x-3 md:space-x-4">
            <div class="text-right hidden sm:block">
                <p class="text-xs md:text-sm font-bold text-white font-tech tracking-wide leading-tight"><?= $employee_name ?></p>
                <p class="text-[9px] md:text-[10px] text-cyan-400 font-mono leading-tight"><?= $employee_role ?></p>
                <?php if (!empty($_SESSION['department_name'])): ?>
                <p class="text-[8px] md:text-[10px] text-indigo-400 font-mono tracking-widest uppercase leading-tight"><?= htmlspecialchars($_SESSION['department_name']) ?> Dept.</p>
                <?php endif; ?>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-gradient-to-tr from-cyan-500 to-indigo-500 p-[2px]">
                <img src="<?php echo htmlspecialchars(get_session_avatar_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="User" class="rounded-full w-full h-full object-cover">
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 scroll-smooth">

        <?php if (false): ?>
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- DEPARTMENT HEAD COMMAND CENTRE                                    -->
        <!-- ══════════════════════════════════════════════════════════════════ -->

        <!-- Section Heading -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <span class="w-1 h-8 bg-cyan-500 rounded-full mr-3 shadow-[0_0_10px_rgba(6,182,212,0.5)]"></span>
                <div>
                    <h2 class="text-lg font-bold text-white font-tech tracking-wider uppercase">Department Command Centre</h2>
                    <p id="dept-name-label" class="text-xs text-cyan-400 font-mono mt-0.5">Loading…</p>
                </div>
            </div>
            <span id="dept-head-loading" class="text-[10px] text-gray-600 font-mono animate-pulse">Fetching data…</span>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- BLOCK 1: PROJECTS & PROJECT TASKS (indigo/cyan)                      -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <h2 class="text-xl font-bold font-tech text-white mb-4 flex items-center">
            <span class="w-2 h-8 bg-indigo-500 mr-3 rounded-full shadow-[0_0_10px_#6366f1]"></span>
            PROJECTS &amp; PROJECT TASKS
        </h2>

        <!-- Project block: Team Members + Project Task stats (one row, aligned) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
            <div class="glass p-4 rounded-xl border border-indigo-500/20 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-16 h-16 bg-cyan-500/10 rounded-full blur-2xl group-hover:bg-cyan-500/20 transition-all"></div>
                <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Team Members</p>
                <h3 id="stat-members" class="text-2xl font-bold text-cyan-400 mt-1 font-tech">--</h3>
                <div class="mt-2 text-[10px] font-mono text-cyan-500">Assigned to project tasks</div>
            </div>
            <div class="glass p-4 rounded-xl border border-indigo-500/20 relative overflow-hidden group">
                <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Project Tasks — Total</p>
                <h3 id="stat-proj-task-total" class="text-2xl font-bold text-white mt-1 font-tech">--</h3>
            </div>
            <div class="glass p-4 rounded-xl border border-indigo-500/20 relative overflow-hidden group">
                <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Project Tasks — Done</p>
                <h3 id="stat-proj-task-done" class="text-2xl font-bold text-green-400 mt-1 font-tech">--</h3>
            </div>
            <div class="glass p-4 rounded-xl border border-indigo-500/20 relative overflow-hidden group">
                <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Project Tasks — Outstanding</p>
                <h3 id="stat-proj-task-outstanding" class="text-2xl font-bold text-amber-400 mt-1 font-tech">--</h3>
            </div>
            <div class="glass p-4 rounded-xl border border-indigo-500/20 relative overflow-hidden group">
                <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Project Tasks — Overdue</p>
                <h3 id="stat-proj-overdue" class="text-2xl font-bold text-red-400 mt-1 font-tech">--</h3>
            </div>
        </div>

        <!-- Project Task completion rate (project tasks only) -->
        <div class="glass p-4 rounded-xl border border-indigo-500/20 mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-mono text-gray-400 uppercase tracking-widest">Project Task Completion Rate</span>
                <span id="project-completion-pct" class="text-xs font-bold text-cyan-400 font-mono">0%</span>
            </div>
            <div class="w-full h-2 bg-gray-800 rounded-full overflow-hidden">
                <div id="project-completion-bar" class="h-full bg-gradient-to-r from-cyan-500 to-indigo-500 rounded-full transition-all duration-1000" style="width:0%"></div>
            </div>
        </div>

        <!-- ── Row: Active Projects + Overdue Tracker (Project Tasks) ───────── -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-6">

            <!-- Active / Incomplete Projects Table (3/5 width) -->
            <div class="lg:col-span-3 glass rounded-xl border border-gray-800 overflow-hidden">
                <div class="p-4 border-b border-gray-800 flex items-center justify-between bg-yellow-500/5">
                    <h3 class="text-sm font-bold text-yellow-400 font-tech uppercase tracking-wider flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Active &amp; Incomplete Projects
                    </h3>
                    <span id="active-proj-count" class="text-[10px] text-gray-500 font-mono">-- projects</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[600px]">
                        <thead class="bg-gray-800/50 text-[10px] text-gray-500 uppercase font-mono tracking-widest">
                            <tr>
                                <th class="px-4 py-2">Project</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2">Progress</th>
                                <th class="px-4 py-2">End Date</th>
                            </tr>
                        </thead>
                        <tbody id="active-projects-list" class="divide-y divide-gray-800/50 text-gray-300">
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-600 font-mono text-xs">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Overdue Accountability Tracker (2/5 width) -->
            <div class="lg:col-span-2 glass rounded-xl border border-red-900/30 overflow-hidden">
                <div class="p-4 border-b border-red-900/30 flex items-center justify-between bg-red-500/5">
                    <h3 class="text-sm font-bold text-red-400 font-tech uppercase tracking-wider flex items-center">
                        <svg class="w-4 h-4 mr-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Overdue Accountability
                    </h3>
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-mono px-1.5 py-0.5 rounded border border-red-500/30 text-red-300 bg-red-500/10 uppercase tracking-widest">Project Task</span>
                        <span id="overdue-count-badge" class="text-[10px] border border-red-500/30 text-red-400 font-mono px-2 py-0.5 rounded bg-red-500/10">0 members</span>
                    </div>
                </div>
                <div id="overdue-members-list" class="divide-y divide-gray-800/50">
                    <div class="px-4 py-6 text-center text-gray-600 font-mono text-xs">Loading…</div>
                </div>
            </div>
        </div>

        <!-- ── Sub-Projects Table (Project block) ───────────────────────────── -->
        <div class="glass rounded-xl border border-indigo-500/20 overflow-hidden mb-6">
            <div class="p-4 border-b border-gray-800 flex items-center justify-between bg-cyan-500/5">
                <h3 class="text-sm font-bold text-cyan-400 font-tech uppercase tracking-wider flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Sub-Projects at a Glance
                </h3>
                <span class="text-[10px] text-gray-500 font-mono">Latest 10 sub-projects</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse min-w-[600px]">
                    <thead class="bg-gray-800/50 text-[10px] text-gray-500 uppercase font-mono tracking-widest">
                        <tr>
                            <th class="px-5 py-3">Sub-Project</th>
                            <th class="px-5 py-3">Major Project</th>
                            <th class="px-5 py-3">Progress</th>
                            <th class="px-5 py-3">Tasks</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody id="desk-sub-projects" class="divide-y divide-gray-800/50 text-gray-300">
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-600 font-mono text-xs">Loading sub-projects…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- BLOCK 2: DIRECT TASKS (purple)                                      -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <h2 class="text-xl font-bold font-tech text-white mb-4 flex items-center">
            <span class="w-2 h-8 bg-purple-500 mr-3 rounded-full shadow-[0_0_10px_#a855f7]"></span>
            DIRECT TASKS
        </h2>

        <!-- Direct Task stats only -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            <div class="glass p-4 rounded-xl border border-purple-500/20 relative overflow-hidden group">
                <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Direct Tasks — Total</p>
                <h3 id="stat-direct-total" class="text-2xl font-bold text-white mt-1 font-tech">--</h3>
            </div>
            <div class="glass p-4 rounded-xl border border-purple-500/20 relative overflow-hidden group">
                <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Direct Tasks — Done</p>
                <h3 id="stat-direct-done" class="text-2xl font-bold text-green-400 mt-1 font-tech">--</h3>
            </div>
            <div class="glass p-4 rounded-xl border border-purple-500/20 relative overflow-hidden group">
                <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Direct Tasks — Outstanding</p>
                <h3 id="stat-direct-outstanding" class="text-2xl font-bold text-amber-400 mt-1 font-tech">--</h3>
            </div>
            <div class="glass p-4 rounded-xl border border-purple-500/20 relative overflow-hidden group">
                <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Direct Tasks — Overdue</p>
                <h3 id="stat-direct-overdue" class="text-2xl font-bold text-red-400 mt-1 font-tech">--</h3>
            </div>
        </div>

        <!-- Direct Task completion rate -->
        <div class="glass p-4 rounded-xl border border-purple-500/20 mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-mono text-gray-400 uppercase tracking-widest">Direct Task Completion Rate</span>
                <span id="direct-completion-pct" class="text-xs font-bold text-purple-400 font-mono">0%</span>
            </div>
            <div class="w-full h-2 bg-gray-800 rounded-full overflow-hidden">
                <div id="direct-completion-bar" class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full transition-all duration-1000" style="width:0%"></div>
            </div>
        </div>

        <!-- Recent Direct Tasks List -->
        <div class="glass rounded-xl border border-purple-500/20 overflow-hidden mb-6">
            <div class="p-4 border-b border-gray-800 flex items-center justify-between bg-purple-500/5">
                <h3 class="text-sm font-bold text-purple-400 font-tech uppercase tracking-wider flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Recent Direct Tasks
                </h3>
                <span class="text-[10px] text-gray-500 font-mono">Latest 5</span>
            </div>
            <div id="recent-direct-tasks-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3 p-4">
                <div class="col-span-full text-center py-8 text-gray-500 font-mono text-xs">Loading...</div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetch('api/get_dept_head_stats.php')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    document.getElementById('dept-head-loading').textContent = '';

                    // ── Heading ──────────────────────────────────────────
                    document.getElementById('dept-name-label').textContent = res.dept_name + ' Department';

                    // ── Projects & Project Tasks block ────────────────────
                    document.getElementById('stat-members').textContent        = res.assigned_members;

                    const projTotal   = (res.project_tasks && res.project_tasks.total) ? Number(res.project_tasks.total) : 0;
                    const projDone    = (res.project_tasks && res.project_tasks.completed) ? Number(res.project_tasks.completed) : 0;
                    const projOutstanding = Math.max(0, projTotal - projDone);
                    const projOverdueCount = (res.overdue_members || []).reduce((acc, m) => acc + Number(m.overdue_count || 0), 0);

                    document.getElementById('stat-proj-task-total').textContent      = projTotal;
                    document.getElementById('stat-proj-task-done').textContent      = projDone;
                    document.getElementById('stat-proj-task-outstanding').textContent = projOutstanding;
                    document.getElementById('stat-proj-overdue').textContent        = projOverdueCount;

                    const projectPct = projTotal > 0 ? Math.round((projDone / projTotal) * 100) : 0;
                    document.getElementById('project-completion-pct').textContent = projectPct + '%';
                    document.getElementById('project-completion-bar').style.width  = projectPct + '%';

                    // ── Direct Tasks block ───────────────────────────────
                    const directTotal = (res.direct_tasks && res.direct_tasks.total) ? Number(res.direct_tasks.total) : 0;
                    const directDone  = (res.direct_tasks && res.direct_tasks.completed) ? Number(res.direct_tasks.completed) : 0;
                    const directOutstanding = Math.max(0, directTotal - directDone);
                    const allOverdue  = (res.dept_performance && res.dept_performance.overdue_count) ? Number(res.dept_performance.overdue_count) : 0;
                    const directOverdueCount = Math.max(0, allOverdue - projOverdueCount);

                    document.getElementById('stat-direct-total').textContent       = directTotal;
                    document.getElementById('stat-direct-done').textContent      = directDone;
                    document.getElementById('stat-direct-outstanding').textContent = directOutstanding;
                    document.getElementById('stat-direct-overdue').textContent    = directOverdueCount;

                    const directPct = directTotal > 0 ? Math.round((directDone / directTotal) * 100) : 0;
                    document.getElementById('direct-completion-pct').textContent = directPct + '%';
                    document.getElementById('direct-completion-bar').style.width  = directPct + '%';

                    // ── Active / Incomplete Projects ─────────────────────
                    const activeList = document.getElementById('active-projects-list');
                    document.getElementById('active-proj-count').textContent = res.active_projects.length + ' projects';
                    if (res.active_projects.length === 0) {
                        activeList.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-green-400 font-mono text-xs">✓ All projects completed!</td></tr>';
                    } else {
                        activeList.innerHTML = '';
                        res.active_projects.forEach(p => {
                            const sc = p.status === 'In Progress' ? 'bg-blue-500/10 border-blue-500 text-blue-400'
                                      : p.status === 'On Hold'    ? 'bg-red-500/10 border-red-500 text-red-400'
                                      : 'bg-gray-700/50 border-gray-600 text-gray-400';
                            const prog  = p.progress_percentage || 0;
                            const pc    = prog >= 75 ? 'bg-green-500' : prog >= 50 ? 'bg-yellow-500' : prog >= 25 ? 'bg-orange-500' : 'bg-red-500';
                            const today = new Date().toISOString().slice(0,10);
                            const overdue = p.end_date && p.end_date < today ? '<span class="text-red-400 font-bold ml-1">OVERDUE</span>' : '';
                            activeList.innerHTML += `
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3 font-medium text-white text-sm">
                                        <a href="sub_projects.php?parent_id=${p.id}" class="hover:text-cyan-400 transition-colors">${p.name}</a>
                                        <div class="text-[10px] text-gray-500 font-mono">${p.sub_count} sub-projects • ${p.done_tasks}/${p.total_tasks} tasks</div>
                                    </td>
                                    <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs rounded border ${sc}">${p.status}</span></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-1.5 bg-gray-700 rounded-full overflow-hidden w-20">
                                                <div class="${pc} h-full" style="width:${prog}%"></div>
                                            </div>
                                            <span class="text-[10px] text-gray-400 font-mono">${prog}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-mono text-gray-400">${p.end_date || '—'}${overdue}</td>
                                </tr>`;
                        });
                    }

                    // ── Overdue Accountability Tracker ───────────────────
                    const overdueList = document.getElementById('overdue-members-list');
                    document.getElementById('overdue-count-badge').textContent = res.overdue_members.length + ' members';
                    if (res.overdue_members.length === 0) {
                        overdueList.innerHTML = '<div class="px-4 py-6 text-center text-green-400 font-mono text-xs">✓ No overdue tasks found.</div>';
                    } else {
                        overdueList.innerHTML = '';
                        const maxOverdue = Math.max(...res.overdue_members.map(m => m.overdue_count));
                        res.overdue_members.forEach(m => {
                            const barWidth = Math.round((m.overdue_count / maxOverdue) * 100);
                            overdueList.innerHTML += `
                                <div class="flex items-center justify-between px-4 py-3 hover:bg-white/5 transition-colors">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm text-white font-medium">${m.full_name}</span>
                                            <span class="text-sm font-bold text-red-400 font-mono ml-4">${m.overdue_count} overdue</span>
                                        </div>
                                        <div class="w-full h-1 bg-gray-800 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-red-600 to-orange-500 rounded-full" style="width:${barWidth}%"></div>
                                        </div>
                                    </div>
                                </div>`;
                        });
                    }

                    // ── Sub-Projects Table ───────────────────────────────
                    const tbody = document.getElementById('desk-sub-projects');
                    tbody.innerHTML = '';
                    if (res.sub_projects.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="px-5 py-8 text-center text-gray-600 font-mono text-xs">No sub-projects found.</td></tr>';
                    } else {
                        res.sub_projects.forEach(sub => {
                            const sc2 = sub.status === 'Completed'   ? 'bg-green-500/10 border-green-500 text-green-400'
                                      : sub.status === 'In Progress' ? 'bg-blue-500/10 border-blue-500 text-blue-400'
                                      : sub.status === 'On Hold'     ? 'bg-red-500/10 border-red-500 text-red-400'
                                      : 'bg-gray-700/50 border-gray-600 text-gray-400';
                            const pr  = sub.progress_percentage || 0;
                            const pc2 = pr >= 75 ? 'bg-green-500' : pr >= 50 ? 'bg-yellow-500' : pr >= 25 ? 'bg-orange-500' : 'bg-red-500';
                            tbody.innerHTML += `
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-5 py-3 font-medium text-white"><a href="tasks.php?project_id=${sub.id}" class="hover:text-cyan-400 transition-colors">${sub.name}</a></td>
                                    <td class="px-5 py-3 text-xs text-indigo-400 font-mono">${sub.major_project_name}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-1.5 bg-gray-700 rounded-full overflow-hidden w-24">
                                                <div class="${pc2} h-full" style="width:${pr}%"></div>
                                            </div>
                                            <span class="text-[10px] text-gray-400 font-mono">${pr}%</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-xs font-mono text-gray-400">${sub.done_tasks}/${sub.total_tasks}</td>
                                    <td class="px-5 py-3"><span class="px-2 py-0.5 text-xs rounded border ${sc2}">${sub.status}</span></td>
                                </tr>`;
                        });
                    }

                    // ── Recent Direct Tasks List ──────────────────────────
                    const rdList = document.getElementById('recent-direct-tasks-list');
                    const statusMap = {
                        'Pending':     { cls: 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20' },
                        'In Progress': { cls: 'text-blue-400 bg-blue-500/10 border-blue-500/20' },
                        'Review':      { cls: 'text-purple-400 bg-purple-500/10 border-purple-500/20' },
                        'Completed':   { cls: 'text-green-400 bg-green-500/10 border-green-500/20' },
                        'Missed':      { cls: 'text-red-400 bg-red-500/10 border-red-500/20' },
                    };
                    const prioMap = {
                        'Urgent': 'text-red-400 bg-red-500/10 border-red-500/30',
                        'High':   'text-orange-400 bg-orange-500/10 border-orange-500/30',
                        'Medium': 'text-cyan-400 bg-cyan-500/10 border-cyan-500/20',
                        'Low':    'text-gray-400 bg-gray-700/50 border-gray-600',
                    };
                    if (!res.recent_direct_tasks || !res.recent_direct_tasks.length) {
                        rdList.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500 font-mono text-xs uppercase">No direct tasks found.</div>';
                    } else {
                        rdList.innerHTML = res.recent_direct_tasks.map(t => {
                            const sBadge = statusMap[t.status] || statusMap['Pending'];
                            const pBadge = prioMap[t.priority] || prioMap['Medium'];
                            const initials = (t.assigned_name || 'U').charAt(0).toUpperCase();
                            const avatarHtml = t.assigned_avatar
                                ? `<img src="${t.assigned_avatar}" class="w-full h-full object-cover">`
                                : `<span class="text-[10px] font-bold text-white">${initials}</span>`;
                            const createdDate = t.created_at ? new Date(t.created_at).toLocaleDateString([], { month:'short', day:'numeric' }) : '';
                            return `<div class="glass rounded-xl border border-gray-700 p-3.5 flex flex-col gap-2.5 hover:border-purple-500/30 transition-colors">
                                <div class="flex items-start justify-between gap-1">
                                    <h3 class="text-xs font-bold text-white leading-snug line-clamp-2 flex-1">${t.title}</h3>
                                    <span class="text-[9px] font-mono px-1.5 py-0.5 rounded border uppercase flex-shrink-0 ml-1 ${pBadge}">${t.priority || 'Mid'}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-5 h-5 rounded-full bg-gray-700 border border-gray-600 overflow-hidden flex items-center justify-center flex-shrink-0">${avatarHtml}</div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-semibold text-gray-300 truncate">${t.assigned_name || 'Unassigned'}</p>
                                        <p class="text-[9px] text-gray-500 truncate">${t.dept_name || ''}</p>
                                        ${t.created_by_name ? `<p class="text-[9px] text-gray-500 font-mono truncate">Created by <span class="text-gray-300 font-semibold">${t.created_by_name}</span></p>` : ''}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-gray-700/50">
                                    <span class="text-[9px] font-mono px-1.5 py-0.5 rounded border ${sBadge.cls}">${t.status}</span>
                                    <span class="text-[9px] font-mono text-gray-500">${createdDate}</span>
                                </div>
                            </div>`;
                        }).join('');
                    }
                })
                .catch(() => {
                    document.getElementById('dept-head-loading').textContent = 'Failed to load data.';
                });
        });
        </script>

        <hr class="border-gray-800 mb-8">
        <?php endif; ?>











        <?php if ($isTeamMemberLike): ?>
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- TEAM MEMBER DESK — Detailed Overview                             -->
        <!-- ══════════════════════════════════════════════════════════════════ -->

        <style>
        @media (max-width: 639px) {
            .tm-metric-grid { grid-template-columns: 1fr; }
            .tm-task-table th, .tm-task-table td { padding: 0.5rem 0.75rem; font-size: 0.75rem; }
            .tm-task-table .hidden-mobile { display: none; }
            .tm-score-cards { flex-direction: column; }
        }
        </style>

        <!-- Section Heading -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 md:mb-6">
            <div class="flex items-center">
                <span class="w-1 h-6 sm:h-8 bg-purple-500 rounded-full mr-3 shadow-[0_0_10px_rgba(168,85,247,0.5)]"></span>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-white font-tech tracking-wider uppercase">My Work Overview</h2>
                    <p class="text-[10px] sm:text-xs text-purple-400 font-mono mt-0.5"><?= htmlspecialchars($employee_name) ?> · <?= date('F Y') ?></p>
                </div>
            </div>
        </div>

        <!-- ── Completed Task Points Only ────────────────────────────────────── -->
        <div class="glass p-4 sm:p-5 rounded-xl border border-cyan-500/20 mb-6 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 sm:w-24 sm:h-24 bg-cyan-500/10 rounded-full blur-2xl"></div>
            <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Total Points from Completed Tasks</p>
            <h3 id="tm-total-completed-points" class="text-3xl sm:text-5xl font-black text-cyan-300 mt-2 font-tech"><?= (int)$total_completed_points ?></h3>
            <p class="mt-2 text-[10px] sm:text-xs text-gray-400 font-mono">
                Project Completed: <span id="tm-project-completed-points" class="text-indigo-300"><?= (int)$project_completed_points ?></span>
                · Direct Completed: <span id="tm-direct-completed-points" class="text-purple-300"><?= (int)$direct_completed_points ?></span>
            </p>
        </div>

        <!-- ── PROJECT TASKS (separate view) ────────────────────────────────── -->
        <div class="mb-6 md:mb-8">
            <h3 class="text-sm sm:text-base font-bold font-tech text-indigo-400 mb-3 flex items-center">
                <span class="w-2 h-6 bg-indigo-500 mr-2 rounded-full"></span>
                PROJECT TASKS
            </h3>
            <div class="glass rounded-xl border border-indigo-500/20 overflow-hidden">
                <div class="p-3 sm:p-4 border-b border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-indigo-500/5">
                    <span id="tm-project-task-meta" class="text-xs font-mono text-indigo-400">Tasks from projects · <?= $project_pending ?> pending · <?= $project_completed ?> completed</span>
                    <a href="projects.php" class="text-[10px] font-mono text-indigo-400 hover:text-indigo-300 transition-colors">View Projects →</a>
                </div>
                <div class="overflow-x-auto -mx-2 sm:mx-0">
                    <table class="tm-task-table w-full text-left text-sm">
                        <thead class="bg-gray-800/50 text-[10px] text-gray-500 uppercase font-mono tracking-widest">
                            <tr>
                                <th class="px-3 sm:px-4 py-2 sm:py-3">Task</th>
                                <th class="px-3 sm:px-4 py-2 sm:py-3 hidden-mobile sm:table-cell">Project</th>
                                <th class="px-3 sm:px-4 py-2 sm:py-3">Priority</th>
                                <th class="px-3 sm:px-4 py-2 sm:py-3">Due</th>
                            </tr>
                        </thead>
                        <tbody id="tm-project-tasks-body" class="divide-y divide-gray-800/50 text-gray-300">
                            <?php if (empty($project_tasks)): ?>
                            <tr>
                                <td colspan="4" class="px-3 sm:px-4 py-6 sm:py-8 text-center text-green-400 font-mono text-xs">✓ No pending project tasks</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($project_tasks as $t):
                                $is_overdue = false;
                                $deadline_ts = task_deadline_timestamp($t['due_date'] ?? null, $t['specific_time'] ?? null);
                                if ($deadline_ts !== null) {
                                    $is_overdue = time() > $deadline_ts;
                                }
                                $p = $t['priority'] ?? 'Medium';
                                if ($p === 'Urgent')     $pc = 'bg-red-500/10 border-red-500 text-red-400';
                                elseif ($p === 'High')   $pc = 'bg-orange-500/10 border-orange-500 text-orange-400';
                                elseif ($p === 'Low')    $pc = 'bg-gray-700/50 border-gray-600 text-gray-400';
                                else                     $pc = 'bg-cyan-500/10 border-cyan-500 text-cyan-400';
                                $due_display = !empty($t['due_date']) ? date('d M', strtotime($t['due_date'])) : '—';
                            ?>
                            <tr class="hover:bg-white/5 transition-colors <?= $is_overdue ? 'bg-red-900/5' : '' ?>">
                                <td class="px-3 sm:px-4 py-2 sm:py-3 font-medium text-white text-xs sm:text-sm">
                                    <a href="tasks.php?project_id=<?= $t['project_id'] ?? '' ?>" class="hover:text-indigo-400 transition-colors"><?= htmlspecialchars($t['title']) ?></a>
                                    <?php if ($is_overdue): ?><span class="ml-1 text-[9px] font-bold text-red-400 border border-red-500/40 bg-red-500/10 px-1 py-0.5 rounded font-mono">OVERDUE</span><?php endif; ?>
                                </td>
                                <td class="px-3 sm:px-4 py-2 sm:py-3 text-[10px] sm:text-xs text-indigo-400 font-mono hidden-mobile sm:table-cell"><?= htmlspecialchars($t['project_name'] ?? '—') ?></td>
                                <td class="px-3 sm:px-4 py-2 sm:py-3"><span class="px-1.5 py-0.5 text-[10px] sm:text-xs rounded border <?= $pc ?>"><?= htmlspecialchars($p) ?></span></td>
                                <td class="px-3 sm:px-4 py-2 sm:py-3 text-[10px] sm:text-xs font-mono <?= $is_overdue ? 'text-red-400' : 'text-gray-400' ?>"><?= $due_display ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── DIRECT TASKS (separate view) ─────────────────────────────────── -->
        <div class="mb-6 md:mb-8">
            <h3 class="text-sm sm:text-base font-bold font-tech text-purple-400 mb-3 flex items-center">
                <span class="w-2 h-6 bg-purple-500 mr-2 rounded-full"></span>
                DIRECT TASKS
            </h3>
            <div class="glass rounded-xl border border-purple-500/20 overflow-hidden">
                <div class="p-3 sm:p-4 border-b border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-purple-500/5">
                    <span id="tm-direct-task-meta" class="text-xs font-mono text-purple-400">Standalone tasks · <?= $direct_pending ?> pending · <?= $direct_completed ?> completed</span>
                    <a href="direct_tasks.php" class="text-[10px] font-mono text-purple-400 hover:text-purple-300 transition-colors">View Direct Tasks →</a>
                </div>
                <div class="overflow-x-auto -mx-2 sm:mx-0">
                    <table class="tm-task-table w-full text-left text-sm">
                        <thead class="bg-gray-800/50 text-[10px] text-gray-500 uppercase font-mono tracking-widest">
                            <tr>
                                <th class="px-3 sm:px-4 py-2 sm:py-3">Task</th>
                                <th class="px-3 sm:px-4 py-2 sm:py-3">Priority</th>
                                <th class="px-3 sm:px-4 py-2 sm:py-3">Due</th>
                            </tr>
                        </thead>
                        <tbody id="tm-direct-tasks-body" class="divide-y divide-gray-800/50 text-gray-300">
                            <?php if (empty($direct_tasks)): ?>
                            <tr>
                                <td colspan="3" class="px-3 sm:px-4 py-6 sm:py-8 text-center text-green-400 font-mono text-xs">✓ No pending direct tasks</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($direct_tasks as $t):
                                $is_overdue = false;
                                $deadline_ts = task_deadline_timestamp($t['due_date'] ?? null, $t['specific_time'] ?? null);
                                if ($deadline_ts !== null) {
                                    $is_overdue = time() > $deadline_ts;
                                }
                                $p = $t['priority'] ?? 'Medium';
                                if ($p === 'Urgent')     $pc = 'bg-red-500/10 border-red-500 text-red-400';
                                elseif ($p === 'High')   $pc = 'bg-orange-500/10 border-orange-500 text-orange-400';
                                elseif ($p === 'Low')    $pc = 'bg-gray-700/50 border-gray-600 text-gray-400';
                                else                     $pc = 'bg-cyan-500/10 border-cyan-500 text-cyan-400';
                                $due_display = !empty($t['due_date']) ? date('d M', strtotime($t['due_date'])) : '—';
                            ?>
                            <tr class="hover:bg-white/5 transition-colors <?= $is_overdue ? 'bg-red-900/5' : '' ?>">
                                <td class="px-3 sm:px-4 py-2 sm:py-3 font-medium text-white text-xs sm:text-sm">
                                    <a href="direct_tasks.php" class="hover:text-purple-400 transition-colors"><?= htmlspecialchars($t['title']) ?></a>
                                    <?php if ($is_overdue): ?><span class="ml-1 text-[9px] font-bold text-red-400 border border-red-500/40 bg-red-500/10 px-1 py-0.5 rounded font-mono">OVERDUE</span><?php endif; ?>
                                </td>
                                <td class="px-3 sm:px-4 py-2 sm:py-3"><span class="px-1.5 py-0.5 text-[10px] sm:text-xs rounded border <?= $pc ?>"><?= htmlspecialchars($p) ?></span></td>
                                <td class="px-3 sm:px-4 py-2 sm:py-3 text-[10px] sm:text-xs font-mono <?= $is_overdue ? 'text-red-400' : 'text-gray-400' ?>"><?= $due_display ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const priorityClass = (priority) => {
                if (priority === 'Urgent') return 'bg-red-500/10 border-red-500 text-red-400';
                if (priority === 'High') return 'bg-orange-500/10 border-orange-500 text-orange-400';
                if (priority === 'Low') return 'bg-gray-700/50 border-gray-600 text-gray-400';
                return 'bg-cyan-500/10 border-cyan-500 text-cyan-400';
            };

            const dueLabel = (dueDate) => {
                if (!dueDate) return '—';
                const date = new Date(dueDate);
                if (Number.isNaN(date.getTime())) return '—';
                return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
            };

            const isTaskOverdue = (task) => {
                if (!task?.due_date) return false;
                const dueDate = String(task.due_date).slice(0, 10);
                const specificTime = task?.specific_time ? String(task.specific_time).slice(0, 8) : '23:59:59';
                const deadline = new Date(`${dueDate}T${specificTime}`);
                return !Number.isNaN(deadline.getTime()) && Date.now() > deadline.getTime();
            };

            const renderTaskTables = (projectTasks, directTasks) => {
                const projectBody = document.getElementById('tm-project-tasks-body');
                const directBody = document.getElementById('tm-direct-tasks-body');

                if (projectBody) {
                    if (!Array.isArray(projectTasks) || projectTasks.length === 0) {
                        projectBody.innerHTML = '<tr><td colspan="4" class="px-3 sm:px-4 py-6 sm:py-8 text-center text-green-400 font-mono text-xs">✓ No pending project tasks</td></tr>';
                    } else {
                        projectBody.innerHTML = projectTasks.map((task) => {
                            const priority = task?.priority || 'Medium';
                            const overdue = isTaskOverdue(task);
                            return `
                                <tr class="hover:bg-white/5 transition-colors ${overdue ? 'bg-red-900/5' : ''}">
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 font-medium text-white text-xs sm:text-sm">
                                        <a href="tasks.php?project_id=${encodeURIComponent(task?.project_id || '')}" class="hover:text-indigo-400 transition-colors">${escapeHtml(task?.title || 'Untitled')}</a>
                                        ${overdue ? '<span class="ml-1 text-[9px] font-bold text-red-400 border border-red-500/40 bg-red-500/10 px-1 py-0.5 rounded font-mono">OVERDUE</span>' : ''}
                                    </td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 text-[10px] sm:text-xs text-indigo-400 font-mono hidden-mobile sm:table-cell">${escapeHtml(task?.project_name || '—')}</td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3"><span class="px-1.5 py-0.5 text-[10px] sm:text-xs rounded border ${priorityClass(priority)}">${escapeHtml(priority)}</span></td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 text-[10px] sm:text-xs font-mono ${overdue ? 'text-red-400' : 'text-gray-400'}">${dueLabel(task?.due_date)}</td>
                                </tr>`;
                        }).join('');
                    }
                }

                if (directBody) {
                    if (!Array.isArray(directTasks) || directTasks.length === 0) {
                        directBody.innerHTML = '<tr><td colspan="3" class="px-3 sm:px-4 py-6 sm:py-8 text-center text-green-400 font-mono text-xs">✓ No pending direct tasks</td></tr>';
                    } else {
                        directBody.innerHTML = directTasks.map((task) => {
                            const priority = task?.priority || 'Medium';
                            const overdue = isTaskOverdue(task);
                            return `
                                <tr class="hover:bg-white/5 transition-colors ${overdue ? 'bg-red-900/5' : ''}">
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 font-medium text-white text-xs sm:text-sm">
                                        <a href="direct_tasks.php" class="hover:text-purple-400 transition-colors">${escapeHtml(task?.title || 'Untitled')}</a>
                                        ${overdue ? '<span class="ml-1 text-[9px] font-bold text-red-400 border border-red-500/40 bg-red-500/10 px-1 py-0.5 rounded font-mono">OVERDUE</span>' : ''}
                                    </td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3"><span class="px-1.5 py-0.5 text-[10px] sm:text-xs rounded border ${priorityClass(priority)}">${escapeHtml(priority)}</span></td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 text-[10px] sm:text-xs font-mono ${overdue ? 'text-red-400' : 'text-gray-400'}">${dueLabel(task?.due_date)}</td>
                                </tr>`;
                        }).join('');
                    }
                }
            };

            const refreshDynamicDashboard = async () => {
                try {
                    const response = await fetch('api/get_employee_dashboard_data.php', { cache: 'no-store' });
                    const res = await response.json();
                    if (!res?.success) return;

                    const projectStats = res.project_stats || {};
                    const directStats = res.direct_stats || {};
                    const points = res.points || {};

                    const projectPending = Number(projectStats.pending || 0);
                    const projectCompleted = Number(projectStats.completed || 0);
                    const directPending = Number(directStats.pending || 0);
                    const directCompleted = Number(directStats.completed || 0);

                    renderTaskTables(res.project_tasks || [], res.direct_tasks || []);

                    const projectPoints = Number(points.project_completed_points || 0);
                    const directPoints = Number(points.direct_completed_points || 0);
                    const totalPoints = Number(points.total_completed_points || (projectPoints + directPoints));

                    const totalPointsEl = document.getElementById('tm-total-completed-points');
                    if (totalPointsEl) totalPointsEl.textContent = String(totalPoints);

                    const projectPointsEl = document.getElementById('tm-project-completed-points');
                    if (projectPointsEl) projectPointsEl.textContent = String(projectPoints);

                    const directPointsEl = document.getElementById('tm-direct-completed-points');
                    if (directPointsEl) directPointsEl.textContent = String(directPoints);

                    const projectMetaEl = document.getElementById('tm-project-task-meta');
                    if (projectMetaEl) {
                        projectMetaEl.textContent = `Tasks from projects · ${projectPending} pending · ${projectCompleted} completed`;
                    }

                    const directMetaEl = document.getElementById('tm-direct-task-meta');
                    if (directMetaEl) {
                        directMetaEl.textContent = `Standalone tasks · ${directPending} pending · ${directCompleted} completed`;
                    }
                } catch (error) {
                    console.error('Failed to refresh employee dashboard data:', error);
                }
            };

            refreshDynamicDashboard();
            setInterval(refreshDynamicDashboard, 60000);
        });
        </script>

        <hr class="border-gray-800 mb-8">
        <?php endif; // end Team Member-like section ?>

        
       

    </div>
</main>




<script>

    // Sparkline Chart (Existing Code) – only if canvas exists
    const scoreSparklineEl = document.getElementById('scoreSparkline');
    if (scoreSparklineEl) {
        const ctx = scoreSparklineEl.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 150);
        gradient.addColorStop(0, 'rgba(34, 211, 238, 0.5)'); 
        gradient.addColorStop(1, 'rgba(34, 211, 238, 0)');   

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Today'],
                datasets: [{
                    data: [<?= implode(',', $score_trend) ?>],
                    borderColor: '#22d3ee',
                    borderWidth: 2,
                    backgroundColor: gradient,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: true, intersect: false, mode: 'index' } },
                scales: { x: { display: false }, y: { display: false, min: 80, max: 100 } },
                interaction: { mode: 'nearest', axis: 'x', intersect: false }
            }
        });
    }

    // Monthly Performance Bar Chart – only if canvas exists
    const monthlyPerfEl = document.getElementById('monthlyPerfChart');
    if (monthlyPerfEl) {
        const monthlyCtx = monthlyPerfEl.getContext('2d');
        const monthlyScores = [<?= implode(',', $monthly_scores) ?>];
        const monthlyColors = monthlyScores.map(v => v >= 0 ? 'rgba(34,197,94,0.6)' : 'rgba(239,68,68,0.6)');
        const monthlyBorderColors = monthlyScores.map(v => v >= 0 ? '#22c55e' : '#ef4444');

        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthly_labels) ?>,
                datasets: [{
                    label: 'Score Change',
                    data: monthlyScores,
                    backgroundColor: monthlyColors,
                    borderColor: monthlyBorderColors,
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => (ctx.raw >= 0 ? '+' : '') + ctx.raw + ' pts'
                        }
                    }
                },
                scales: {
                    y: {
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        border: { display: false },
                        ticks: { color: '#6b7280', font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#6b7280', font: { size: 10 } }
                    }
                }
            }
        });
    }

    // Live Countdown Logic
    function updateCountdowns() {
        $('.countdown').each(function() {
            const row = $(this).closest('.group');
            const alertBadge = row.find('.escalation-badge');
            const dueStr = $(this).data('due');
            
            if(!dueStr) return;

            const dueDate = new Date(dueStr).getTime();
            const now = new Date().getTime();
            const distance = dueDate - now;

            // Handle Expired
            if (distance < 0) {
                $(this).text("MISSED").addClass("text-red-500 animate-pulse");
                $(this).removeClass("text-cyan-400 text-yellow-400 text-orange-400 text-blue-400");
                alertBadge.removeClass('hidden');
                return;
            }

            const totalHours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Time Formatting
            const hStr = totalHours < 10 ? "0" + totalHours : totalHours;
            const mStr = minutes < 10 ? "0" + minutes : minutes;
            const sStr = seconds < 10 ? "0" + seconds : seconds;

            $(this).text(`${hStr}h : ${mStr}m : ${sStr}s`);

            // SMART ALERTS LOGIC
            // 1. Critical Escalation (< 24h)
            if (totalHours < 24) {
                $(this).addClass("text-red-500 animate-pulse").removeClass("text-yellow-400 text-cyan-400 text-orange-400 text-blue-400");
                alertBadge.removeClass('hidden');
            } 
            // 2. Yellow Alert (< 48h)
            else if (totalHours < 48) {
                $(this).addClass("text-yellow-400").removeClass("text-red-400 text-cyan-400 text-orange-400 text-blue-400 animate-pulse");
                alertBadge.addClass('hidden');
            }
            // 3. Normal State
            else {
                $(this).addClass("text-cyan-400").removeClass("text-red-400 text-yellow-400 animate-pulse");
                alertBadge.addClass('hidden');
            }
        });
    }

    // Update every second
    setInterval(updateCountdowns, 1000);
    // Check for missed tasks on load
    fetch('api/check_missed_tasks.php')
        .then(response => response.json())
        .then(data => {
            if(data.processed_count > 0) {
                console.log("Processed missed tasks:", data.processed_count);
                // Optional: Reload page to show updated scores/status
                // location.reload(); 
            }
        })
        .catch(console.error);

    updateCountdowns(); // Initial call
</script>
<?php include 'includes/footer.php'; ?>
