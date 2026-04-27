<?php
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
check_login();
require_role(['Super Admin', 'Department Head']);
include 'includes/header.php';
include 'includes/sidebar.php';

$is_super_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin');
?>
<style>
.workload-card { transition: transform 0.2s, box-shadow 0.2s; }
.workload-card:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(0,0,0,0.4); }
.workload-card.overloaded { border-color: rgba(239,68,68,0.5); box-shadow: 0 0 12px rgba(239,68,68,0.15); }
.workload-card.moderate { border-color: rgba(234,179,8,0.4); }
.workload-card.free { border-color: rgba(34,197,94,0.4); }
.busy-dot { width: 8px; height: 8px; border-radius: 50%; }
.busy-low { background: #22c55e; }
.busy-mid { background: #eab308; }
.busy-high { background: #ef4444; }
.workload-bar { height: 6px; border-radius: 3px; }
@media (max-width: 767px) {
    .workload-grid { grid-template-columns: 1fr; }
    .workload-card .task-list { max-height: 12rem; overflow-y: auto; }
    .summary-cards { flex-direction: column; }
}
</style>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">
    <header class="h-16 flex items-center justify-between px-4 md:px-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex items-center space-x-3 md:space-x-4">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors" aria-label="Menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <div>
                <h1 class="text-lg md:text-xl font-bold text-white uppercase font-tech tracking-wider">Performance Overview</h1>
                <p class="text-[10px] text-cyan-400 font-mono hidden sm:block">Workload & availability — check before assigning tasks</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="workloadRefreshBtn" class="p-2 rounded-lg text-gray-400 hover:text-cyan-400 hover:bg-white/5 transition-colors" title="Refresh" aria-label="Refresh">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
            <span class="text-xs font-mono text-gray-500 hidden sm:inline">Auto-updates</span>
            <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-gradient-to-tr from-cyan-500 to-indigo-500 p-[2px]">
                <div class="w-full h-full rounded-full bg-gray-900 overflow-hidden">
                    <img src="<?php echo htmlspecialchars(get_session_avatar_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-x-hidden overflow-y-auto p-3 md:p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Executive Summary Bar -->
            <div id="summaryBar" class="hidden summary-cards grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
                <div class="glass rounded-xl border border-cyan-500/20 p-4">
                    <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Total Members</p>
                    <p id="summaryMembers" class="text-2xl md:text-3xl font-black text-white font-tech mt-1">0</p>
                </div>
                <div class="glass rounded-xl border border-indigo-500/20 p-4">
                    <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Active Tasks</p>
                    <p id="summaryActive" class="text-2xl md:text-3xl font-black text-indigo-400 font-tech mt-1">0</p>
                </div>
                <div class="glass rounded-xl border border-red-500/20 p-4">
                    <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Overloaded</p>
                    <p id="summaryOverloaded" class="text-2xl md:text-3xl font-black text-red-400 font-tech mt-1">0</p>
                </div>
                <div class="glass rounded-xl border border-green-500/20 p-4">
                    <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Avg Score</p>
                    <p id="summaryScore" class="text-2xl md:text-3xl font-black text-green-400 font-tech mt-1">0</p>
                </div>
            </div>

            <!-- Filters and Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex flex-wrap items-center gap-2">
                    <div id="deptFilterWrap" class="<?php echo $is_super_admin ? '' : 'hidden'; ?>">
                        <select id="deptFilter" class="bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white font-mono focus:ring-cyan-500 focus:border-cyan-500">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <select id="periodFilter" class="bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white font-mono focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="weekly">Weekly View</option>
                        <option value="monthly">Monthly View</option>
                    </select>
                    <input type="month" id="monthFilter" class="bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white font-mono focus:ring-cyan-500 focus:border-cyan-500">
                    <input type="text" id="searchInput" placeholder="Search by name..." class="bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white font-mono placeholder-gray-500 focus:ring-cyan-500 focus:border-cyan-500 w-48">
                    <select id="sortSelect" class="bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white font-mono focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="workload">Sort: Workload</option>
                        <option value="name">Sort: Name</option>
                        <option value="overdue">Sort: Overdue</option>
                        <option value="score">Sort: Score</option>
                    </select>
                    <div class="flex rounded-lg overflow-hidden border border-gray-600">
                        <button type="button" id="viewCards" class="px-3 py-2 text-sm font-mono bg-cyan-600/30 text-cyan-400 border-r border-gray-600">Cards</button>
                        <button type="button" id="viewTable" class="px-3 py-2 text-sm font-mono text-gray-400 hover:bg-white/5">Table</button>
                    </div>
                </div>
                <button type="button" id="exportBtn" class="px-4 py-2 rounded-lg bg-green-600/20 text-green-400 hover:bg-green-600/30 border border-green-500/30 text-sm font-mono flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export Excel
                </button>
            </div>

            <div id="workloadLoading" class="text-center py-12 text-gray-500 font-mono text-sm">Loading workload...</div>
            <div id="workloadEmpty" class="hidden text-center py-12 text-gray-500 font-mono text-sm">No team members or department heads found.</div>

            <!-- Card View -->
            <div id="workloadGrid" class="hidden grid workload-grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-5"></div>

            <!-- Table View -->
            <div id="workloadTableWrap" class="hidden overflow-x-auto">
                <table id="workloadTable" class="w-full text-left text-sm">
                    <thead class="bg-gray-800/50 text-[10px] text-gray-500 uppercase font-mono tracking-widest">
                        <tr>
                            <th class="px-4 py-3 cursor-pointer hover:text-cyan-400" data-sort="name">Name</th>
                            <th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3 cursor-pointer hover:text-cyan-400" data-sort="workload">Active</th>
                            <th class="px-4 py-3">Pending</th>
                            <th class="px-4 py-3 cursor-pointer hover:text-cyan-400" data-sort="overdue">Overdue</th>
                            <th class="px-4 py-3 cursor-pointer hover:text-cyan-400" data-sort="score">Score</th>
                            <th class="px-4 py-3" id="periodHoursHeader">Weekly Hrs</th>
                            <th class="px-4 py-3" id="periodCompletedHeader">Completed (7d)</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody id="workloadTableBody" class="divide-y divide-gray-800/50"></tbody>
                </table>
            </div>

            <!-- Monthly Historical Record -->
            <div id="monthlyRecordWrap" class="hidden mt-6 overflow-x-auto">
                <div class="flex items-center space-x-2 mb-3">
                    <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
                    <h2 class="text-sm md:text-base font-bold text-white font-tech uppercase tracking-wide">Monthly Detailed Record</h2>
                    <span id="monthRecordLabel" class="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded">Current Month</span>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-800/50 text-[10px] text-gray-500 uppercase font-mono tracking-widest">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3">Total Tasks</th>
                            <th class="px-4 py-3">Completed</th>
                            <th class="px-4 py-3">Missed</th>
                            <th class="px-4 py-3">On-Time</th>
                            <th class="px-4 py-3">Late</th>
                            <th class="px-4 py-3">Avg Completion (hrs)</th>
                            <th class="px-4 py-3">Points Earned</th>
                            <th class="px-4 py-3">Net Points</th>
                        </tr>
                    </thead>
                    <tbody id="monthlyRecordBody" class="divide-y divide-gray-800/50"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
(function () {
    const grid = document.getElementById('workloadGrid');
    const tableWrap = document.getElementById('workloadTableWrap');
    const tableBody = document.getElementById('workloadTableBody');
    const loading = document.getElementById('workloadLoading');
    const empty = document.getElementById('workloadEmpty');
    const summaryBar = document.getElementById('summaryBar');
    const periodFilter = document.getElementById('periodFilter');
    const monthFilter = document.getElementById('monthFilter');
    const periodHoursHeader = document.getElementById('periodHoursHeader');
    const periodCompletedHeader = document.getElementById('periodCompletedHeader');
    const monthlyRecordWrap = document.getElementById('monthlyRecordWrap');
    const monthlyRecordBody = document.getElementById('monthlyRecordBody');
    const monthRecordLabel = document.getElementById('monthRecordLabel');
    const isSuperAdmin = <?php echo $is_super_admin ? 'true' : 'false'; ?>;

    let rawData = [];
    let viewMode = 'cards';
    let periodMode = 'weekly';
    let periodDays = 7;
    let selectedMonth = new Date().toISOString().slice(0, 7);

    function load(deptId) {
        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        grid.classList.add('hidden');
        tableWrap.classList.add('hidden');
        summaryBar.classList.add('hidden');
        const params = new URLSearchParams();
        params.set('period', periodMode);
        params.set('month', selectedMonth);
        if (isSuperAdmin && deptId) params.set('department_id', deptId);
        const url = 'api/get_workload_overview.php?' + params.toString();
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(function () {
                loading.classList.add('hidden');
                empty.classList.remove('hidden');
                empty.textContent = 'Failed to load workload.';
            });
    }

    function loadDepartments() {
        if (!isSuperAdmin) return;
        fetch('api/get_departments.php')
            .then(function (r) { return r.json(); })
            .then(function (depts) {
                const sel = document.getElementById('deptFilter');
                sel.innerHTML = '<option value="">All Departments</option>';
                (depts || []).forEach(function (d) {
                    sel.appendChild(new Option(d.name, d.id));
                });
            })
            .catch(function () {});
    }

    function render(res) {
        loading.classList.add('hidden');
        if (res.status !== 'success' || !res.data || res.data.length === 0) {
            empty.classList.remove('hidden');
            empty.textContent = 'No team members or department heads found.';
            return;
        }
        rawData = res.data;
        periodMode = (res.period === 'monthly') ? 'monthly' : 'weekly';
        periodDays = Number(res.period_days || (periodMode === 'monthly' ? 30 : 7));
        selectedMonth = res.month || selectedMonth;
        if (periodFilter) periodFilter.value = periodMode;
        if (monthFilter) monthFilter.value = selectedMonth;
        if (periodHoursHeader) periodHoursHeader.textContent = periodMode === 'monthly' ? 'Monthly Hrs' : 'Weekly Hrs';
        if (periodCompletedHeader) periodCompletedHeader.textContent = 'Completed (' + periodDays + 'd)';
        if (monthRecordLabel) monthRecordLabel.textContent = selectedMonth;
        if (res.summary) {
            summaryBar.classList.remove('hidden');
            document.getElementById('summaryMembers').textContent = res.summary.total_members || 0;
            document.getElementById('summaryActive').textContent = res.summary.total_active_tasks || 0;
            document.getElementById('summaryOverloaded').textContent = res.summary.overloaded_count || 0;
            document.getElementById('summaryScore').textContent = res.summary.avg_performance_score || 0;
        }
        applyFiltersAndSort();
    }

    function applyFiltersAndSort() {
        let data = rawData.slice();
        const search = (document.getElementById('searchInput') || {}).value || '';
        if (search.trim()) {
            const q = search.toLowerCase().trim();
            data = data.filter(function (u) {
                return (u.full_name || '').toLowerCase().includes(q) ||
                       (u.department_name || '').toLowerCase().includes(q);
            });
        }
        if (data.length === 0) {
            empty.classList.remove('hidden');
            empty.textContent = 'No members match your search.';
            grid.classList.add('hidden');
            tableWrap.classList.add('hidden');
            return;
        }
        empty.classList.add('hidden');
        const sortBy = (document.getElementById('sortSelect') || {}).value || 'workload';
        if (sortBy === 'name') {
            data.sort(function (a, b) { return (a.full_name || '').localeCompare(b.full_name || ''); });
        } else if (sortBy === 'workload') {
            data.sort(function (a, b) { return (b.total_active || 0) - (a.total_active || 0); });
        } else if (sortBy === 'overdue') {
            data.sort(function (a, b) { return (b.overdue_count || 0) - (a.overdue_count || 0); });
        } else if (sortBy === 'score') {
            data.sort(function (a, b) { return (b.performance_score || 0) - (a.performance_score || 0); });
        }
        if (data.length === 0) {
            empty.classList.remove('hidden');
            empty.textContent = 'No members match your search or filter.';
            grid.classList.add('hidden');
            tableWrap.classList.add('hidden');
            return;
        }
        empty.classList.add('hidden');
        if (viewMode === 'cards') {
            grid.classList.remove('hidden');
            tableWrap.classList.add('hidden');
            grid.innerHTML = data.map(buildCard).join('');
        } else {
            grid.classList.add('hidden');
            tableWrap.classList.remove('hidden');
            tableBody.innerHTML = data.map(buildTableRow).join('');
        }
        renderMonthlyRecords(data);
    }

    function renderMonthlyRecords(data) {
        if (!monthlyRecordWrap || !monthlyRecordBody) return;
        monthlyRecordWrap.classList.remove('hidden');
        if (!data || !data.length) {
            monthlyRecordBody.innerHTML = '<tr><td colspan="10" class="px-4 py-8 text-center text-gray-500 font-mono text-xs">No monthly records found.</td></tr>';
            return;
        }
        monthlyRecordBody.innerHTML = data.map(function (u) {
            return '<tr class="hover:bg-white/5">' +
                '<td class="px-4 py-3 font-medium text-white">' + (u.full_name || 'Unknown') + '</td>' +
                '<td class="px-4 py-3 text-xs text-gray-400">' + (u.department_name || '—') + '</td>' +
                '<td class="px-4 py-3 font-mono text-cyan-400">' + (u.month_total_tasks || 0) + '</td>' +
                '<td class="px-4 py-3 font-mono text-green-400">' + (u.month_completed_tasks || 0) + '</td>' +
                '<td class="px-4 py-3 font-mono text-red-400">' + (u.month_missed_tasks || 0) + '</td>' +
                '<td class="px-4 py-3 font-mono text-emerald-400">' + (u.month_on_time_completed || 0) + '</td>' +
                '<td class="px-4 py-3 font-mono text-amber-400">' + (u.month_late_completed || 0) + '</td>' +
                '<td class="px-4 py-3 font-mono text-indigo-400">' + (u.month_avg_completion_hours || 0) + '</td>' +
                '<td class="px-4 py-3 font-mono text-green-300">+' + (u.month_points_earned || 0) + '</td>' +
                '<td class="px-4 py-3 font-mono ' + ((u.month_net_points || 0) >= 0 ? 'text-cyan-300' : 'text-red-300') + '">' + (u.month_net_points || 0) + '</td>' +
                '</tr>';
        }).join('');
    }

    function priorityClass(p) {
        p = (p || 'Medium').trim();
        if (p === 'Urgent') return 'text-red-400 bg-red-500/20';
        if (p === 'High') return 'text-orange-400 bg-orange-500/20';
        if (p === 'Low') return 'text-gray-400 bg-gray-600/30';
        return 'text-cyan-400 bg-cyan-500/20';
    }

    function statusClass(s) {
        const m = { 'Pending': 'text-yellow-400', 'Pending Approval': 'text-amber-400', 'In Progress': 'text-blue-400', 'Review': 'text-purple-400' };
        return m[s] || 'text-gray-400';
    }

    function formatDue(d) {
        if (!d) return '—';
        const dt = new Date(d);
        if (isNaN(dt)) return d;
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const due = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
        const diff = Math.ceil((due - today) / (1000 * 60 * 60 * 24));
        if (diff < 0) return '<span class="text-red-400">Overdue</span>';
        if (diff === 0) return '<span class="text-amber-400">Today</span>';
        if (diff === 1) return 'Tomorrow';
        if (diff <= 7) return 'In ' + diff + ' days';
        return dt.toLocaleDateString();
    }

    function overloadStatusClass(status) {
        if (status === 'Overloaded') return 'bg-red-500/20 text-red-400 border-red-500/40';
        if (status === 'Moderate') return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/40';
        return 'bg-green-500/20 text-green-400 border-green-500/40';
    }

    function buildCard(user) {
        const active = user.total_active || 0;
        const pending = user.pending_count || 0;
        const overdue = user.overdue_count || 0;
        const score = user.performance_score ?? 0;
        const periodHours = user.period_hours ?? (periodMode === 'monthly' ? (user.monthly_hours || 0) : (user.weekly_hours || 0));
        const completedCount = user.completed_count || 0;
        const status = user.overload_status || 'Free';
        const statusCls = overloadStatusClass(status);
        const cardCls = status === 'Overloaded' ? 'overloaded' : (status === 'Moderate' ? 'moderate' : 'free');
        const tasks = user.tasks || [];
        const avatar = user.avatar_url || 'uploads/avatars/default-avatar.svg';
        const roleLabel = user.role === 'Department Head' ? 'Dept Head' : 'Team';
        const dept = user.department_name || '—';
        const barPct = Math.min(100, (active / 8) * 100);
        const barColor = active >= 5 ? 'bg-red-500' : (active >= 1 ? 'bg-yellow-500' : 'bg-green-500');

        const taskRows = tasks.slice(0, 5).map(function (t) {
            return '<div class="flex items-start justify-between gap-2 py-1.5 border-b border-gray-700/50 last:border-0 text-xs">' +
                '<span class="text-gray-300 truncate flex-1 min-w-0" title="' + (t.title || '').replace(/"/g, '&quot;') + '">' + (t.title || 'Task') + '</span>' +
                '<span class="flex-shrink-0 ' + priorityClass(t.priority) + ' px-1.5 py-0.5 rounded font-mono text-[10px]">' + (t.priority || 'Medium') + '</span>' +
                '</div>' +
                '<div class="flex items-center justify-between text-[10px] font-mono text-gray-500 pl-0 pb-1.5">' +
                '<span class="' + statusClass(t.status) + '">' + (t.status || '') + '</span>' +
                '<span class="text-gray-500">Due: ' + formatDue(t.due_date) + '</span>' +
                '</div>';
        }).join('');
        const more = tasks.length > 5 ? '<p class="text-[10px] font-mono text-gray-500 pt-1">+' + (tasks.length - 5) + ' more</p>' : '';

        return '<div class="workload-card glass rounded-xl border p-4 flex flex-col h-full ' + cardCls + '">' +
            '<div class="flex items-center gap-3 mb-3">' +
            '<img src="' + avatar + '" alt="" class="w-10 h-10 rounded-full object-cover border-2 border-cyan-500/30 flex-shrink-0">' +
            '<div class="min-w-0 flex-1">' +
            '<p class="font-bold text-white truncate font-tech">' + (user.full_name || 'Unknown') + '</p>' +
            '<p class="text-[10px] font-mono text-gray-400">' + dept + ' · ' + roleLabel + '</p>' +
            '</div>' +
            '<span class="flex-shrink-0 px-2 py-0.5 text-[10px] font-mono rounded border ' + statusCls + '">' + status + '</span>' +
            '</div>' +
            '<div class="flex flex-wrap gap-2 mb-2">' +
            '<span class="text-[10px] font-mono px-2 py-1 rounded bg-cyan-500/20 text-cyan-400">' + active + ' active</span>' +
            '<span class="text-[10px] font-mono px-2 py-1 rounded bg-yellow-500/20 text-yellow-400">' + pending + ' pending</span>' +
            '<span class="text-[10px] font-mono px-2 py-1 rounded bg-red-500/20 text-red-400">' + overdue + ' overdue</span>' +
            '<span class="text-[10px] font-mono px-2 py-1 rounded bg-indigo-500/20 text-indigo-400">' + score + ' pts</span>' +
            '<span class="text-[10px] font-mono px-2 py-1 rounded bg-emerald-500/20 text-emerald-400">' + periodHours + 'h ' + (periodMode === 'monthly' ? 'mo' : 'wk') + '</span>' +
            '<span class="text-[10px] font-mono px-2 py-1 rounded bg-teal-500/20 text-teal-400">' + completedCount + ' done/' + periodDays + 'd</span>' +
            '</div>' +
            '<div class="mb-2">' +
            '<div class="workload-bar w-full bg-gray-700 overflow-hidden">' +
            '<div class="' + barColor + ' workload-bar transition-all" style="width:' + barPct + '%"></div>' +
            '</div>' +
            '<p class="text-[9px] font-mono text-gray-500 mt-0.5">Workload capacity</p>' +
            '</div>' +
            '<div class="task-list flex-1 min-h-0">' +
            (tasks.length ? ('<div class="space-y-0">' + taskRows + more + '</div>') : '<p class="text-[10px] font-mono text-gray-500 py-2">No active tasks</p>') +
            '</div>' +
            '</div>';
    }

    function buildTableRow(user) {
        const status = user.overload_status || 'Free';
        const periodHours = user.period_hours ?? (periodMode === 'monthly' ? (user.monthly_hours || 0) : (user.weekly_hours || 0));
        const completedCount = user.completed_count || 0;
        const statusCls = overloadStatusClass(status);
        const roleLabel = user.role === 'Department Head' ? 'Dept Head' : 'Team';
        const rowBg = status === 'Overloaded' ? 'bg-red-900/10' : (status === 'Moderate' ? 'bg-yellow-900/5' : '');
        return '<tr class="hover:bg-white/5 ' + rowBg + '">' +
            '<td class="px-4 py-3 font-medium text-white">' + (user.full_name || 'Unknown') + '</td>' +
            '<td class="px-4 py-3 text-xs text-gray-400">' + (user.department_name || '—') + '</td>' +
            '<td class="px-4 py-3 text-xs font-mono text-cyan-400">' + roleLabel + '</td>' +
            '<td class="px-4 py-3 font-mono text-indigo-400 font-bold">' + (user.total_active || 0) + '</td>' +
            '<td class="px-4 py-3 font-mono text-yellow-400">' + (user.pending_count || 0) + '</td>' +
            '<td class="px-4 py-3 font-mono ' + (user.overdue_count > 0 ? 'text-red-400 font-bold' : 'text-gray-400') + '">' + (user.overdue_count || 0) + '</td>' +
            '<td class="px-4 py-3 font-mono text-green-400">' + (user.performance_score ?? 0) + '</td>' +
            '<td class="px-4 py-3 font-mono text-emerald-400">' + periodHours + '</td>' +
            '<td class="px-4 py-3 font-mono text-teal-400">' + completedCount + '</td>' +
            '<td class="px-4 py-3"><span class="px-2 py-0.5 text-[10px] font-mono rounded border ' + statusCls + '">' + status + '</span></td>' +
            '</tr>';
    }

    function exportExcel() {
        const search = ((document.getElementById('searchInput') || {}).value || '').trim();
        const deptId = ((document.getElementById('deptFilter') || {}).value || '').trim();
        const sortBy = (document.getElementById('sortSelect') || {}).value || 'workload';
        const period = periodMode || 'weekly';
        const month = selectedMonth || new Date().toISOString().slice(0, 7);
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (deptId) params.set('department_id', deptId);
        if (sortBy) params.set('sort', sortBy);
        params.set('period', period);
        params.set('month', month);
        window.location.href = 'api/export_attendance_activity_report.php?' + params.toString();
    }

    document.getElementById('workloadRefreshBtn').addEventListener('click', function () {
        load(document.getElementById('deptFilter').value || undefined);
    });
    document.getElementById('deptFilter').addEventListener('change', function () {
        load(this.value || undefined);
    });
    if (periodFilter) {
        periodFilter.addEventListener('change', function () {
            periodMode = this.value === 'monthly' ? 'monthly' : 'weekly';
            load(document.getElementById('deptFilter').value || undefined);
        });
    }
    if (monthFilter) {
        monthFilter.value = selectedMonth;
        monthFilter.addEventListener('change', function () {
            selectedMonth = this.value || new Date().toISOString().slice(0, 7);
            load(document.getElementById('deptFilter').value || undefined);
        });
    }
    document.getElementById('searchInput').addEventListener('input', applyFiltersAndSort);
    document.getElementById('sortSelect').addEventListener('change', applyFiltersAndSort);
    document.getElementById('viewCards').addEventListener('click', function () {
        viewMode = 'cards';
        document.getElementById('viewCards').classList.add('bg-cyan-600/30', 'text-cyan-400');
        document.getElementById('viewTable').classList.remove('bg-cyan-600/30', 'text-cyan-400');
        document.getElementById('viewTable').classList.add('text-gray-400');
        applyFiltersAndSort();
    });
    document.getElementById('viewTable').addEventListener('click', function () {
        viewMode = 'table';
        document.getElementById('viewTable').classList.add('bg-cyan-600/30', 'text-cyan-400');
        document.getElementById('viewCards').classList.remove('bg-cyan-600/30', 'text-cyan-400');
        document.getElementById('viewCards').classList.add('text-gray-400');
        applyFiltersAndSort();
    });
    document.getElementById('exportBtn').addEventListener('click', exportExcel);

    document.querySelectorAll('#workloadTable th[data-sort]').forEach(function (th) {
        th.addEventListener('click', function () {
            document.getElementById('sortSelect').value = th.getAttribute('data-sort');
            applyFiltersAndSort();
        });
    });

    loadDepartments();
    load();
})();
</script>

<?php include 'includes/footer.php'; ?>
