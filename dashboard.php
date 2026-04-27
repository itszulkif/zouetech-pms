<?php
require_once 'includes/auth_middleware.php';
require_role(['Super Admin']);
include 'includes/header.php';
include 'includes/sidebar.php';
$userRole = htmlspecialchars($_SESSION['role']);
$userName = htmlspecialchars($_SESSION['full_name']);
$avatarUrl = htmlspecialchars(get_session_avatar_url(), ENT_QUOTES, 'UTF-8');
?>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">

    <!-- Header -->
    <header class="min-h-14 md:min-h-16 py-2 flex items-center justify-between px-4 md:px-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20 flex-shrink-0">
        <div class="flex items-center space-x-3">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
            </button>
            <div class="flex items-center space-x-2">
                <div class="w-2 h-6 rounded-full bg-gradient-to-b from-cyan-500 to-indigo-500 shadow-[0_0_8px_rgba(6,182,212,0.5)]"></div>
                <div>
                    <h1 class="text-base md:text-lg font-bold text-white font-tech tracking-wider uppercase">Command Center</h1>
                    <p class="text-[9px] text-cyan-400 font-mono hidden sm:block"><?php echo $userRole; ?> — Full System View</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden xl:flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-slate-800/70 border border-slate-700/70">
                <span class="text-[9px] font-mono text-slate-400 uppercase tracking-wider">Status</span>
                <div class="h-3.5 w-px bg-slate-700"></div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-[9px] font-mono text-slate-300 uppercase">On Track</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="text-[9px] font-mono text-slate-300 uppercase">Needs Attention</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span class="text-[9px] font-mono text-slate-300 uppercase">Critical</span>
                </div>
            </div>

            <div class="flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-green-500/10 border border-green-500/20">
                <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                <span class="text-[9px] font-mono text-green-400 uppercase tracking-wider">Live</span>
            </div>
            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-cyan-500 to-indigo-500 p-[2px]">
                <div class="w-full h-full rounded-full bg-gray-900 overflow-hidden">
                    <img src="<?php echo $avatarUrl; ?>" class="w-full h-full object-cover rounded-full">
                </div>
            </div>
            <div class="hidden sm:block text-right">
                <p class="text-xs font-bold text-white font-tech uppercase leading-tight"><?php echo $userName; ?></p>
                <p class="text-[9px] text-cyan-400 font-mono"><?php echo $userRole; ?></p>
            </div>
        </div>
    </header>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden p-3 md:p-5 space-y-5 md:space-y-6">

        <!-- Loading State -->
        <div id="loadingState" class="flex items-center justify-center py-20">
            <div class="text-center">
                <div class="w-8 h-8 border-2 border-cyan-500 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-xs font-mono text-gray-500 uppercase tracking-wider">Syncing data streams...</p>
            </div>
        </div>

        <!-- Main Dashboard (hidden until data loads) -->
        <div id="dashMain" class="hidden space-y-5 md:space-y-6">

            <!-- ─── ORGANIZATION OVERVIEW ─── -->
            <div>
                <div class="flex items-center space-x-2 mb-3">
                    <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                    <h2 class="text-sm md:text-base font-bold text-white font-tech uppercase tracking-wide">Organization Overview</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-cyan-500/40 transition-colors">
                        <div class="p-2 bg-cyan-500/10 rounded-lg text-cyan-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Major Projects</p>
                            <h3 id="s-projects" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>
                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-indigo-500/40 transition-colors">
                        <div class="p-2 bg-indigo-500/10 rounded-lg text-indigo-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Members</p>
                            <h3 id="s-members" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── PROJECT TASKS SUMMARY ─── -->
            <div>
                <div class="flex items-center space-x-2 mb-3">
                    <div class="w-1 h-5 bg-cyan-500 rounded-full"></div>
                    <h2 class="text-sm md:text-base font-bold text-white font-tech uppercase tracking-wide">Project Tasks Summary</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-blue-500/40 transition-colors">
                        <div class="p-2 bg-blue-500/10 rounded-lg text-blue-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Active Projects</p>
                            <h3 id="s-active-projects" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>

                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-green-500/40 transition-colors">
                        <div class="p-2 bg-green-500/10 rounded-lg text-green-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Completed Project</p>
                            <h3 id="s-proj-completed" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>

                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-red-500/40 transition-colors">
                        <div class="p-2 bg-red-500/10 rounded-lg text-red-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Missed Project</p>
                            <h3 id="s-proj-missed" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>

                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-cyan-500/40 transition-colors">
                        <div class="p-2 bg-cyan-500/10 rounded-lg text-cyan-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10M6 6h12a2 2 0 012 2v8a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Project Completion %</p>
                            <h3 id="s-proj-rate" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── DIRECT TASKS SUMMARY ─── -->
            <div>
                <div class="flex items-center space-x-2 mb-3">
                    <div class="w-1 h-5 bg-purple-500 rounded-full"></div>
                    <h2 class="text-sm md:text-base font-bold text-white font-tech uppercase tracking-wide">Direct Tasks Summary</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-purple-500/40 transition-colors">
                        <div class="p-2 bg-purple-500/10 rounded-lg text-purple-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Active Direct</p>
                            <h3 id="s-dir-tasks" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>

                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-green-500/40 transition-colors">
                        <div class="p-2 bg-green-500/10 rounded-lg text-green-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Completed Direct</p>
                            <h3 id="s-dir-completed" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>

                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-red-500/40 transition-colors">
                        <div class="p-2 bg-red-500/10 rounded-lg text-red-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Missed Direct</p>
                            <h3 id="s-dir-missed" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>

                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-fuchsia-500/40 transition-colors">
                        <div class="p-2 bg-fuchsia-500/10 rounded-lg text-fuchsia-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10M6 6h12a2 2 0 012 2v8a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Direct Completion %</p>
                            <h3 id="s-dir-rate" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── REVIEW QUEUE SNAPSHOT ─── -->
            <div>
                <div class="flex items-center space-x-2 mb-3">
                    <div class="w-1 h-5 bg-amber-500 rounded-full"></div>
                    <h2 class="text-sm md:text-base font-bold text-white font-tech uppercase tracking-wide">Review Queue Snapshot</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-amber-500/40 transition-colors">
                        <div class="p-2 bg-amber-500/10 rounded-lg text-amber-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Project Tasks In Review</p>
                            <h3 id="s-proj-review" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>
                    <div class="glass rounded-xl border border-gray-700 p-3 md:p-4 flex items-center space-x-3 hover:border-amber-500/40 transition-colors">
                        <div class="p-2 bg-amber-500/10 rounded-lg text-amber-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-mono text-gray-500 uppercase truncate">Direct Tasks In Review</p>
                            <h3 id="s-dir-review" class="text-xl font-bold text-white font-tech">—</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── PROJECT TASKS — DEPT-WISE ─── -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-1 h-5 bg-cyan-500 rounded-full"></div>
                        <h2 class="text-sm md:text-base font-bold text-white font-tech uppercase tracking-wide">Project Tasks</h2>
                        <span class="text-[10px] font-mono text-cyan-400 bg-cyan-500/10 border border-cyan-500/20 px-2 py-0.5 rounded">by Team Member</span>
                    </div>
                    <a href="tasks.php" class="text-[10px] font-mono text-gray-400 hover:text-cyan-400 transition-colors">View All →</a>
                </div>
                <div id="deptGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"></div>
            </div>

            <!-- ─── DIRECT TASKS — 5 MOST RECENT ─── -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-1 h-5 bg-purple-500 rounded-full"></div>
                        <h2 class="text-sm md:text-base font-bold text-white font-tech uppercase tracking-wide">Direct Tasks</h2>
                        <span class="text-[10px] font-mono text-purple-400 bg-purple-500/10 border border-purple-500/20 px-2 py-0.5 rounded">5 Most Recent</span>
                    </div>
                    <a href="direct_tasks.php" class="text-[10px] font-mono text-gray-400 hover:text-purple-400 transition-colors">View All →</a>
                </div>
                <div id="recentDirectList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
                    <div class="col-span-full text-center py-8 text-gray-500 font-mono text-xs">Loading...</div>
                </div>
            </div>

            <!-- ─── AT-RISK PROJECTS ─── -->
            <div>
                <div class="flex items-center space-x-2 mb-3">
                    <div class="w-1 h-5 bg-red-500 rounded-full"></div>
                    <h2 class="text-sm md:text-base font-bold text-white font-tech uppercase tracking-wide">At-Risk Projects</h2>
                    <span class="text-[10px] font-mono text-red-400 bg-red-500/10 border border-red-500/20 px-2 py-0.5 rounded animate-pulse">Action Required</span>
                </div>
                <div class="glass rounded-xl border border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left min-w-[500px]">
                            <thead class="bg-gray-800/80 text-gray-400 text-[10px] font-mono uppercase tracking-wider">
                                <tr>
                                    <th class="py-2.5 px-4">Project</th>
                                    <th class="px-3">Team</th>
                                    <th class="px-3">Progress</th>
                                    <th class="px-3 text-center text-red-400">Delayed Tasks</th>
                                    <th class="py-2.5 px-4 text-right">Deadline</th>
                                </tr>
                            </thead>
                            <tbody id="riskTable" class="divide-y divide-gray-700/50 text-gray-300 text-xs">
                                <tr><td colspan="5" class="py-10 text-center text-gray-500 font-mono text-xs">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /#dashMain -->
    </div>
</main>

<script>
const DASHBOARD_REFRESH_MS = 15000;

function loadDashboardData(showLoadingOnError = true) {
    fetch('api/get_dashboard_data.php')
        .then(r => r.text())
        .then(text => {
            let data;
            try { data = JSON.parse(text); }
            catch(e) {
                if (showLoadingOnError) {
                    document.getElementById('loadingState').innerHTML =
                        `<pre class="text-red-400 font-mono text-xs whitespace-pre-wrap p-4">${text.substring(0, 600)}</pre>`;
                }
                return;
            }
            if (!data.success) {
                if (showLoadingOnError) {
                    document.getElementById('loadingState').innerHTML =
                        `<p class="text-red-400 font-mono text-xs p-4">DB Error: ${data.error || 'Unknown'}</p>`;
                }
                return;
            }
            renderStats(data.stats);
            renderDeptTasks(data.dept_tasks);
            renderDirectTasks(data.recent_direct_tasks);
            renderAtRisk(data.at_risk);
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('dashMain').classList.remove('hidden');
        })
        .catch(err => {
            if (showLoadingOnError) {
                document.getElementById('loadingState').innerHTML =
                    `<p class="text-red-400 font-mono text-xs p-4">Fetch Error: ${err.message}</p>`;
            }
        });
}

document.addEventListener('DOMContentLoaded', function () {
    loadDashboardData(true);
    setInterval(() => loadDashboardData(false), DASHBOARD_REFRESH_MS);
});

function escapeHtml(s) {
    if (s == null) return '';
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}
function renderStats(s) {
    document.getElementById('s-projects').textContent   = s.total_projects;
    document.getElementById('s-members').textContent    = s.total_members;
    document.getElementById('s-active-projects').textContent = s.active_projects;
    document.getElementById('s-dir-tasks').textContent  = s.active_direct_tasks;
    document.getElementById('s-proj-completed').textContent = s.project_completed;
    document.getElementById('s-proj-missed').textContent    = s.project_missed;
    document.getElementById('s-proj-rate').textContent      = `${s.project_completion_rate}%`;
    document.getElementById('s-dir-completed').textContent  = s.direct_completed;
    document.getElementById('s-dir-missed').textContent     = s.direct_missed;
    document.getElementById('s-dir-rate').textContent       = `${s.direct_completion_rate}%`;
    document.getElementById('s-proj-review').textContent    = s.project_review_tasks ?? 0;
    document.getElementById('s-dir-review').textContent     = s.direct_review_tasks ?? 0;
}

function renderDeptTasks(depts) {
    const grid = document.getElementById('deptGrid');
    if (!depts.length) {
        grid.innerHTML = '<p class="col-span-3 text-center text-gray-500 font-mono text-xs py-8">No team member data found.</p>';
        return;
    }
    grid.innerHTML = depts.map(d => {
        const statuses = [
            { label:'Pending',     val:d.pending,     color:'bg-yellow-500' },
            { label:'In Progress', val:d.in_progress, color:'bg-blue-500'   },
            { label:'Review',      val:d.review,      color:'bg-purple-500' },
            { label:'Completed',   val:d.completed,   color:'bg-green-500'  },
            { label:'Missed',      val:d.missed,      color:'bg-red-500'    },
        ];
        const segments = d.total > 0
            ? statuses.map(s => s.val > 0
                ? `<div class="${s.color} h-full" style="width:${(s.val/d.total*100).toFixed(1)}%" title="${s.label}: ${s.val}"></div>`
                : '').join('')
            : '<div class="bg-gray-700 h-full w-full"></div>';
        const dotRow = statuses.map(s =>
            `<div class="flex items-center gap-1">
                <div class="w-1.5 h-1.5 rounded-full ${s.color} flex-shrink-0"></div>
                <span class="text-[10px] text-gray-400 font-mono">${s.val}</span>
            </div>`).join('');
        const pctColor = d.pct >= 75 ? 'text-green-400' : d.pct >= 40 ? 'text-yellow-400' : 'text-red-400';
        const health = d.health || (d.pct >= 75 ? 'On Track' : d.pct >= 45 ? 'Needs Attention' : 'Critical');
        const healthBadgeClass = health === 'On Track'
            ? 'text-green-400 bg-green-500/10 border-green-500/30'
            : (health === 'Needs Attention'
                ? 'text-yellow-400 bg-yellow-500/10 border-yellow-500/30'
                : 'text-red-400 bg-red-500/10 border-red-500/30');
        const cardBorderClass = health === 'On Track'
            ? 'hover:border-green-500/30'
            : (health === 'Needs Attention'
                ? 'hover:border-yellow-500/30'
                : 'hover:border-red-500/30');
        return `<div class="glass rounded-xl border border-gray-700 p-4 ${cardBorderClass} transition-colors">
            <div class="flex items-start justify-between mb-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-white truncate">${d.dept_name}</h3>
                    <p class="text-[10px] text-gray-500 font-mono">${d.total} total task${d.total !== 1 ? 's' : ''}</p>
                </div>
                <span class="text-lg font-bold font-tech ${pctColor} flex-shrink-0 ml-2">${d.pct}%</span>
            </div>
            <div class="mb-3">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded border text-[10px] font-mono uppercase tracking-wider ${healthBadgeClass}">
                    <span class="w-1.5 h-1.5 rounded-full ${health === 'On Track' ? 'bg-green-400' : (health === 'Needs Attention' ? 'bg-yellow-400' : 'bg-red-400')}"></span>
                    ${health}
                </span>
            </div>
            <div class="h-2 bg-gray-800 rounded-sm flex gap-px mb-3 overflow-hidden">${segments}</div>
            <div class="flex items-center justify-between flex-wrap gap-2">${dotRow}</div>
            <div class="mt-3 pt-3 border-t border-gray-700/60 flex justify-end">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-500">Member snapshot</span>
            </div>
        </div>`;
    }).join('');
}

function renderDirectTasks(tasks) {
    const container = document.getElementById('recentDirectList');
    if (!tasks || !tasks.length) {
        container.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500 font-mono text-xs uppercase">No direct tasks found.</div>';
        return;
    }
    const statusMap = {
        'Pending':     { cls: 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20' },
        'In Progress': { cls: 'text-blue-400   bg-blue-500/10   border-blue-500/20'   },
        'Review':      { cls: 'text-purple-400 bg-purple-500/10 border-purple-500/20' },
        'Completed':   { cls: 'text-green-400  bg-green-500/10  border-green-500/20'  },
        'Missed':      { cls: 'text-red-400    bg-red-500/10    border-red-500/20'    },
    };
    const prioMap = {
        'Urgent': 'text-red-400    bg-red-500/10    border-red-500/30',
        'High':   'text-orange-400 bg-orange-500/10 border-orange-500/30',
        'Medium': 'text-cyan-400   bg-cyan-500/10   border-cyan-500/20',
        'Low':    'text-gray-400   bg-gray-700/50   border-gray-600',
    };
    container.innerHTML = tasks.map(t => {
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
                </div>
            </div>
            <div class="flex items-center justify-between pt-1 border-t border-gray-700/50">
                <span class="text-[9px] font-mono px-1.5 py-0.5 rounded border ${sBadge.cls}">${t.status}</span>
                <span class="text-[9px] font-mono text-gray-500">${createdDate}</span>
            </div>
        </div>`;
    }).join('');
}

function renderAtRisk(projects) {
    const tbody = document.getElementById('riskTable');
    if (!projects.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="py-10 text-center text-green-400 font-mono text-xs uppercase">✓ No critical risks detected. System optimal.</td></tr>';
        return;
    }
    tbody.innerHTML = projects.map(p => {
        const d = parseInt(p.delayed);
        const alertClass = d > 3 ? 'text-red-500 animate-pulse' : 'text-red-400';
        const pct = parseInt(p.progress_percentage) || 0;
        return `<tr class="hover:bg-gray-800/40 transition-colors">
            <td class="py-3 px-4 font-semibold text-white">${p.name}</td>
            <td class="px-3 text-gray-400 text-[11px]">${p.dept}</td>
            <td class="px-3">
                <div class="flex items-center gap-2">
                    <div class="w-20 h-1.5 bg-gray-800 rounded-full overflow-hidden">
                        <div class="bg-red-500 h-full rounded-full" style="width:${pct}%"></div>
                    </div>
                    <span class="text-[11px] font-mono text-red-400">${pct}%</span>
                </div>
            </td>
            <td class="px-3 text-center">
                <span class="bg-red-500/10 ${alertClass} text-[10px] font-bold px-2 py-0.5 rounded border border-red-500/20 font-mono">${d} DELAYED</span>
            </td>
            <td class="px-4 text-right font-mono text-[11px] text-gray-400">${p.end_date || 'N/A'}</td>
        </tr>`;
    }).join('');
}
</script>

<?php include 'includes/footer.php'; ?>
