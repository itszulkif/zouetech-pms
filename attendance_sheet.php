<?php
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
require_role(['Super Admin', 'Department Head', 'Team Member', 'Team Lead']);
include 'includes/header.php';
include 'includes/sidebar.php';
$isSuperAdmin = ($_SESSION['role'] === 'Super Admin');
$isTeamLead = ($_SESSION['role'] === 'Team Lead');
$isDeptHead = ($_SESSION['role'] === 'Department Head');
$isTeamMember = ($_SESSION['role'] === 'Team Member');
$isGlobalAttendanceViewer = ($isSuperAdmin || $isTeamLead);
$canViewReports = ($isGlobalAttendanceViewer || $isDeptHead);
?>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">
    <header class="h-16 flex items-center justify-between px-4 md:px-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex items-center gap-3">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <div>
                <h1 class="text-lg md:text-xl font-bold text-white uppercase font-tech tracking-wider">Attendance Sheet</h1>
                <p class="text-[10px] text-cyan-400 font-mono hidden sm:block">Independent daily attendance sign-in / sign-out</p>
            </div>
        </div>
        <div class="text-right hidden sm:block">
            <p class="text-xs font-bold text-white font-tech uppercase"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            <p class="text-[10px] text-cyan-400 font-mono"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-3 md:p-6 space-y-4">
        <section class="glass rounded-xl border border-cyan-500/20 p-4">
            <div class="flex flex-col lg:flex-row gap-4 lg:items-end lg:justify-between">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 flex-1">
                    <div>
                        <label class="block text-[10px] text-gray-400 uppercase font-mono mb-1">System Date</label>
                        <div id="attDateLabel" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white"></div>
                    </div>
                    <div>
                        <label class="block text-[10px] text-gray-400 uppercase font-mono mb-1">Status</label>
                        <div id="myAttendanceStatus" class="bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-cyan-300">Loading...</div>
                    </div>
                </div>
                <?php if (!$isSuperAdmin): ?>
                <div class="flex flex-col sm:flex-row gap-2">
                    <button id="signInBtn" class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-500 text-white text-sm font-semibold">Sign-In</button>
                    <button id="openSignOutBtn" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold">Sign-Out</button>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="glass rounded-xl border border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-700 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-bold text-white uppercase font-tech tracking-wide"><?php echo $isTeamMember ? 'My Attendance' : 'Team Attendance'; ?></h2>
                    <span id="attCount" class="text-[10px] font-mono text-gray-400">0 records</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <div>
                        <label class="block text-[10px] text-gray-500 uppercase font-mono mb-1">Attendance Date</label>
                        <input id="attDateInput" type="date" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] text-gray-500 uppercase font-mono mb-1">Search</label>
                        <input id="attSearchInput" type="text" placeholder="Search name or department..." class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white">
                    </div>
                    <?php if ($isGlobalAttendanceViewer): ?>
                    <div>
                        <label class="block text-[10px] text-gray-500 uppercase font-mono mb-1">View Mode</label>
                        <select id="attViewMode" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white">
                            <option value="global">Global List</option>
                            <option value="department">Department-wise Group</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($canViewReports): ?>
                    <div>
                        <label class="block text-[10px] text-gray-500 uppercase font-mono mb-1">Team Export</label>
                        <button id="exportScopeBtn" class="w-full px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold">
                            <?php echo $isGlobalAttendanceViewer ? 'Export Organization Excel' : 'Export Team Excel'; ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead class="bg-gray-800/80 text-gray-400 text-[10px] uppercase font-mono tracking-widest">
                        <tr>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-3 py-2">Department</th>
                            <th class="px-3 py-2">Role</th>
                            <th class="px-3 py-2">Sign-In</th>
                            <th class="px-3 py-2">Sign-Out</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceBody" class="divide-y divide-gray-700/50 text-sm text-gray-300">
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500 font-mono text-xs">Loading attendance...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($canViewReports): ?>
        <section class="grid grid-cols-1 gap-4">
            <div class="glass rounded-xl border border-indigo-500/20 overflow-hidden">
                <div class="p-4 border-b border-gray-700 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-2">
                    <h3 class="text-sm font-bold text-indigo-300 uppercase font-tech tracking-wide">Weekly Attendance Report (7 days)</h3>
                    <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto">
                        <input id="reportWeekInput" type="week" class="w-full sm:w-auto bg-gray-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left">
                        <thead class="bg-gray-800/80 text-gray-400 text-[10px] uppercase font-mono tracking-widest">
                            <tr>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Dept</th>
                                <th class="px-3 py-2">Present</th>
                                <th class="px-3 py-2">Absent</th>
                                <th class="px-3 py-2">Auto SO</th>
                                <th class="px-3 py-2">Hours</th>
                            </tr>
                        </thead>
                        <tbody id="weeklyReportBody" class="divide-y divide-gray-700/50 text-xs text-gray-300"></tbody>
                    </table>
                </div>
            </div>
            <div class="glass rounded-xl border border-purple-500/20 overflow-hidden">
                <div class="p-4 border-b border-gray-700 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-2">
                    <h3 class="text-sm font-bold text-purple-300 uppercase font-tech tracking-wide">Monthly Attendance Report (Calendar Month)</h3>
                    <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto">
                        <?php if ($isGlobalAttendanceViewer): ?>
                        <select id="reportDeptFilter" class="w-full sm:w-auto bg-gray-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                            <option value="">All Departments</option>
                        </select>
                        <?php endif; ?>
                        <select id="reportRoleFilter" class="w-full sm:w-auto bg-gray-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                            <option value="">All Roles</option>
                            <option value="Department Head">Department Head</option>
                            <option value="Team Lead">Team Lead</option>
                            <option value="Team Member">Team Member</option>
                        </select>
                        <input id="reportSearchInput" type="text" placeholder="Filter name/dept" class="w-full sm:w-auto bg-gray-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                        <input id="exportMonthInput" type="month" class="w-full sm:w-auto bg-gray-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                        <button id="exportMonthlyBtn" class="w-full sm:w-auto px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold">Export Monthly</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left">
                        <thead class="bg-gray-800/80 text-gray-400 text-[10px] uppercase font-mono tracking-widest">
                            <tr>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Dept</th>
                                <th class="px-3 py-2">Present</th>
                                <th class="px-3 py-2">Absent</th>
                                <th class="px-3 py-2">Auto SO</th>
                                <th class="px-3 py-2">Hours</th>
                            </tr>
                        </thead>
                        <tbody id="monthlyReportBody" class="divide-y divide-gray-700/50 text-xs text-gray-300"></tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>

<!-- Sign-out report modal -->
<div id="signOutModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 p-4">
    <div class="w-full max-w-xl bg-gray-800 border border-gray-700 rounded-xl p-4 md:p-5">
        <h3 class="text-base font-bold text-white mb-2">Submit Activity Report</h3>
        <p class="text-xs text-gray-400 mb-3">Sign-out requires your activity report for today.</p>
        <textarea id="activityReportInput" rows="7" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white" placeholder="Describe your completed activities, updates, blockers, and next steps..."></textarea>
        <div class="mt-3 flex justify-end gap-2">
            <button id="cancelSignOutBtn" class="px-3 py-2 rounded-lg border border-gray-600 text-gray-300 text-sm">Cancel</button>
            <button id="confirmSignOutBtn" class="px-3 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold">Submit & Sign-Out</button>
        </div>
    </div>
</div>

<!-- Attendance detailed report modal -->
<div id="detailModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 p-4">
    <div class="w-full max-w-4xl max-h-[92vh] bg-gray-800 border border-gray-700 rounded-xl overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 id="detailTitle" class="text-sm font-bold text-white uppercase font-tech tracking-wide">User Attendance Detail</h3>
            <div class="flex items-center gap-2">
                <input id="detailMonthInput" type="month" class="bg-gray-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                <button id="exportUserBtn" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold">Export User</button>
                <button id="closeDetailBtn" class="px-2 py-1 rounded border border-gray-600 text-gray-300 hover:text-white">✕</button>
            </div>
        </div>
        <div class="p-4 overflow-auto">
            <table class="w-full min-w-[700px] text-left">
                <thead class="text-[10px] uppercase font-mono text-gray-400">
                    <tr>
                        <th class="py-2 px-2">Date</th>
                        <th class="py-2 px-2">Sign-In</th>
                        <th class="py-2 px-2">Sign-Out</th>
                        <th class="py-2 px-2">Method</th>
                        <th class="py-2 px-2">Hours</th>
                        <th class="py-2 px-2">Activity Report</th>
                    </tr>
                </thead>
                <tbody id="detailBody" class="divide-y divide-gray-700/50 text-xs text-gray-300"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
(() => {
    const isSuperAdmin = <?php echo json_encode($_SESSION['role'] === 'Super Admin'); ?>;
    const isGlobalAttendanceViewer = <?php echo json_encode(in_array($_SESSION['role'], ['Super Admin', 'Team Lead'])); ?>;
    const canViewReports = <?php echo json_encode(in_array($_SESSION['role'], ['Super Admin', 'Team Lead', 'Department Head'])); ?>;
    const attDateLabel = document.getElementById('attDateLabel');
    const attendanceBody = document.getElementById('attendanceBody');
    const myAttendanceStatus = document.getElementById('myAttendanceStatus');
    const attCount = document.getElementById('attCount');
    const signOutModal = document.getElementById('signOutModal');
    const detailModal = document.getElementById('detailModal');
    const signInBtn = document.getElementById('signInBtn');
    const openSignOutBtn = document.getElementById('openSignOutBtn');
    const exportMonthlyBtn = document.getElementById('exportMonthlyBtn');
    const exportMonthInput = document.getElementById('exportMonthInput');
    const reportDeptFilter = document.getElementById('reportDeptFilter');
    const reportRoleFilter = document.getElementById('reportRoleFilter');
    const reportSearchInput = document.getElementById('reportSearchInput');
    const reportWeekInput = document.getElementById('reportWeekInput');
    const attDateInput = document.getElementById('attDateInput');
    function getCurrentIsoWeek() {
        const d = new Date();
        const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        const dayNum = date.getUTCDay() || 7;
        date.setUTCDate(date.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
        const weekNum = Math.ceil((((date - yearStart) / 86400000) + 1) / 7);
        return date.getUTCFullYear() + '-W' + String(weekNum).padStart(2, '0');
    }

    const attSearchInput = document.getElementById('attSearchInput');
    const attViewMode = document.getElementById('attViewMode');
    const exportScopeBtn = document.getElementById('exportScopeBtn');
    const detailMonthInput = document.getElementById('detailMonthInput');
    const exportUserBtn = document.getElementById('exportUserBtn');
    let currentSheetRows = [];
    let currentDetailUserId = 0;

    const systemDate = new Date().toISOString().slice(0, 10);
    attDateLabel.textContent = systemDate;
    if (attDateInput) attDateInput.value = systemDate;
    if (attDateInput) attDateInput.max = systemDate;
    if (detailMonthInput) detailMonthInput.value = new Date().toISOString().slice(0, 7);

    function fmt(dt) {
        if (!dt) return '—';
        const d = new Date(dt.replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return dt;
        return d.toLocaleString();
    }

    function statusBadge(status) {
        if (status === 'Signed Out') return 'text-green-400 bg-green-500/10 border-green-500/30';
        if (status === 'Signed In') return 'text-amber-400 bg-amber-500/10 border-amber-500/30';
        if (status === 'Future Date') return 'text-sky-300 bg-sky-500/10 border-sky-500/30';
        return 'text-gray-400 bg-gray-700/40 border-gray-600';
    }

    function getFilteredRows() {
        const query = (attSearchInput?.value || '').trim().toLowerCase();
        if (!query) return [...currentSheetRows];
        return currentSheetRows.filter(r => {
            const name = (r.full_name || '').toLowerCase();
            const dept = (r.department_name || '').toLowerCase();
            return name.includes(query) || dept.includes(query);
        });
    }

    function renderAttendanceRows() {
        const rows = getFilteredRows();
        attCount.textContent = rows.length + ' records';
        if (!rows.length) {
            attendanceBody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500 font-mono text-xs">No attendance data.</td></tr>';
            return;
        }

        const mode = attViewMode ? attViewMode.value : 'global';
        if (isGlobalAttendanceViewer && mode === 'department') {
            const grouped = {};
            rows.forEach(r => {
                const dept = r.department_name || 'Unassigned Department';
                if (!grouped[dept]) grouped[dept] = [];
                grouped[dept].push(r);
            });
            const html = Object.keys(grouped).sort().map(dept => {
                const deptRows = grouped[dept].map(r => renderAttendanceRow(r)).join('');
                return `<tr class="bg-gray-800/60"><td colspan="7" class="px-4 py-2 text-[11px] font-mono uppercase tracking-wider text-cyan-300">${dept}</td></tr>${deptRows}`;
            }).join('');
            attendanceBody.innerHTML = html;
            return;
        }

        attendanceBody.innerHTML = rows.map(r => renderAttendanceRow(r)).join('');
    }

    function renderAttendanceRow(r) {
        const isAuto = (r.sign_out_method || '') === 'Automatic';
        const signOutCellClass = isAuto ? 'text-amber-300' : '';
        const signOutMethodBadge = isAuto
            ? '<span class="ml-2 px-1.5 py-0.5 rounded border border-amber-500/40 bg-amber-500/10 text-amber-300 text-[10px] font-mono">System-Auto</span>'
            : '';
        const rowClass = isAuto ? 'bg-amber-500/5 hover:bg-amber-500/10' : 'hover:bg-gray-800/40';
        return `
            <tr class="${rowClass}">
                <td class="px-4 py-2 font-semibold text-white">${r.full_name || '-'}</td>
                <td class="px-3 py-2">${r.department_name || '-'}</td>
                <td class="px-3 py-2">${r.role || '-'}</td>
                <td class="px-3 py-2 font-mono">${fmt(r.sign_in_at)}</td>
                <td class="px-3 py-2 font-mono ${signOutCellClass}">${fmt(r.sign_out_at)}${signOutMethodBadge}</td>
                <td class="px-3 py-2"><span class="px-2 py-0.5 text-[10px] font-mono border rounded ${statusBadge(r.attendance_status)}">${r.attendance_status}</span></td>
                <td class="px-4 py-2">
                    ${(canViewReports && Number(r.user_id) > 0)
                        ? `<button class="px-2 py-1 rounded bg-cyan-600/20 text-cyan-300 text-[11px] view-detail-btn" data-user="${r.user_id}" data-name="${(r.full_name || '').replace(/"/g, '&quot;')}">View Detail</button>`
                        : '<span class="text-gray-500 text-xs">—</span>'
                    }
                </td>
            </tr>
        `;
    }

    function loadSheet() {
        const date = attDateInput?.value || systemDate;
        fetch('api/get_attendance_sheet.php?date=' + encodeURIComponent(date))
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') return;
                currentSheetRows = res.data || [];
                const self = res.self;
                if (self) {
                    const selfAutoNote = (self.sign_out_method === 'Automatic')
                        ? ' (auto-closed at 11:30 PM due to inactivity)'
                        : '';
                    myAttendanceStatus.textContent = `${self.attendance_status}${selfAutoNote}`;
                } else {
                    myAttendanceStatus.textContent = 'Not Signed In';
                }
                renderAttendanceRows();
            });
    }
    
    function renderReportRows(targetId, rows) {
        const el = document.getElementById(targetId);
        if (!el) return;
        if (!rows || !rows.length) {
            el.innerHTML = '<tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">No data.</td></tr>';
            return;
        }
        el.innerHTML = rows.map(r => `
            <tr>
                <td class="px-3 py-2 font-semibold text-white">${r.full_name || '-'}</td>
                <td class="px-3 py-2">${r.department_name || '-'}</td>
                <td class="px-3 py-2 text-green-400 font-mono">${r.present_days || 0}</td>
                <td class="px-3 py-2 text-red-400 font-mono">${r.absent_days || 0}</td>
                <td class="px-3 py-2 font-mono ${(Number(r.auto_signed_out_days || 0) > 0) ? 'text-amber-300' : 'text-gray-400'}">${r.auto_signed_out_days || 0}</td>
                <td class="px-3 py-2 text-cyan-300 font-mono">${r.total_hours || 0}</td>
            </tr>
        `).join('');
    }

    function loadAdminReports() {
        if (!canViewReports) return;
        const month = (exportMonthInput && exportMonthInput.value) ? exportMonthInput.value : new Date().toISOString().slice(0, 7);
        const week = (reportWeekInput && reportWeekInput.value) ? reportWeekInput.value : getCurrentIsoWeek();
        const params = new URLSearchParams();
        params.set('month', month);
        params.set('week', week);
        if (reportDeptFilter && reportDeptFilter.value) params.set('department_id', reportDeptFilter.value);
        if (reportRoleFilter && reportRoleFilter.value) params.set('role', reportRoleFilter.value);
        if (reportSearchInput && reportSearchInput.value.trim()) params.set('search', reportSearchInput.value.trim());
        fetch('api/get_attendance_admin_reports.php?' + params.toString())
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') return;
                renderReportRows('weeklyReportBody', res.weekly || []);
                renderReportRows('monthlyReportBody', res.monthly || []);
            });
    }

    function loadReportDepartments() {
        if (!reportDeptFilter) return;
        fetch('api/get_departments.php')
            .then(r => r.json())
            .then(rows => {
                reportDeptFilter.innerHTML = '<option value="">All Departments</option>';
                (rows || []).forEach(d => {
                    reportDeptFilter.appendChild(new Option(d.name, d.id));
                });
            })
            .catch(() => {});
    }

    if (exportMonthInput) {
        exportMonthInput.value = new Date().toISOString().slice(0, 7);
        exportMonthInput.max = new Date().toISOString().slice(0, 7);
        exportMonthInput.addEventListener('change', loadAdminReports);
    }
    if (reportWeekInput) {
        reportWeekInput.value = getCurrentIsoWeek();
        reportWeekInput.addEventListener('change', loadAdminReports);
    }
    if (reportDeptFilter) reportDeptFilter.addEventListener('change', loadAdminReports);
    if (reportRoleFilter) reportRoleFilter.addEventListener('change', loadAdminReports);
    if (reportSearchInput) reportSearchInput.addEventListener('input', loadAdminReports);

    if (signInBtn) {
        signInBtn.addEventListener('click', () => {
            fetch('api/attendance_sign_in.php', { method: 'POST' })
                .then(r => r.json())
                .then(res => { alert(res.message); loadSheet(); });
        });
    }

    if (openSignOutBtn) {
        openSignOutBtn.addEventListener('click', () => {
            signOutModal.classList.remove('hidden');
            signOutModal.classList.add('flex');
        });
    }

    document.getElementById('cancelSignOutBtn').addEventListener('click', () => {
        signOutModal.classList.add('hidden');
        signOutModal.classList.remove('flex');
    });

    document.getElementById('confirmSignOutBtn').addEventListener('click', () => {
        const report = document.getElementById('activityReportInput').value.trim();
        const form = new FormData();
        form.append('activity_report', report);
        fetch('api/attendance_sign_out.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(res => {
                alert(res.message);
                if (res.status === 'success') {
                    document.getElementById('activityReportInput').value = '';
                    signOutModal.classList.add('hidden');
                    signOutModal.classList.remove('flex');
                    loadSheet();
                }
            });
    });

    function loadUserDetail(uid, name) {
        const month = detailMonthInput?.value || '';
        currentDetailUserId = Number(uid) || 0;
        let url = 'api/get_attendance_user_report.php?user_id=' + encodeURIComponent(uid);
        if (month) url += '&month=' + encodeURIComponent(month);
        fetch(url)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') return;
                document.getElementById('detailTitle').textContent = `${name} - Detailed Attendance Report`;
                const rows = res.entries || [];
                const body = document.getElementById('detailBody');
                body.innerHTML = rows.length ? rows.map(x => `
                    <tr class="${(x.sign_out_method === 'Automatic') ? 'bg-amber-500/5' : ''}">
                        <td class="px-2 py-2 font-mono">${x.attendance_date || '-'}</td>
                        <td class="px-2 py-2 font-mono">${fmt(x.sign_in_at)}</td>
                        <td class="px-2 py-2 font-mono">${fmt(x.sign_out_at)}</td>
                        <td class="px-2 py-2">
                            ${(x.sign_out_method === 'Automatic')
                                ? '<span class="px-2 py-0.5 rounded border border-amber-500/40 bg-amber-500/10 text-amber-300 text-[10px] font-mono">System-Auto</span>'
                                : '<span class="px-2 py-0.5 rounded border border-green-500/40 bg-green-500/10 text-green-300 text-[10px] font-mono">Manual</span>'
                            }
                        </td>
                        <td class="px-2 py-2 font-mono">${x.hours || 0}</td>
                        <td class="px-2 py-2">
                            ${(x.activity_report ? x.activity_report : '<span class="text-gray-500">No report</span>')}
                            ${(x.sign_out_method === 'Automatic')
                                ? '<div class="text-[10px] text-amber-300 font-mono mt-1">System note: session auto-closed at 11:30 PM due to inactivity.</div>'
                                : ''
                            }
                        </td>
                    </tr>
                `).join('') : '<tr><td colspan="6" class="px-2 py-6 text-center text-gray-500">No records found.</td></tr>';
                detailModal.classList.remove('hidden');
                detailModal.classList.add('flex');
            });
    }

    attendanceBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.view-detail-btn');
        if (!btn) return;
        const uid = btn.dataset.user;
        const name = btn.dataset.name || 'User';
        loadUserDetail(uid, name);
    });

    document.getElementById('closeDetailBtn').addEventListener('click', () => {
        detailModal.classList.add('hidden');
        detailModal.classList.remove('flex');
    });

    if (exportMonthlyBtn) {
        exportMonthlyBtn.addEventListener('click', () => {
            const m = (exportMonthInput && exportMonthInput.value) ? exportMonthInput.value : new Date().toISOString().slice(0, 7);
            const params = new URLSearchParams();
            params.set('month', m);
            if (reportDeptFilter && reportDeptFilter.value) params.set('department_id', reportDeptFilter.value);
            if (reportRoleFilter && reportRoleFilter.value) params.set('role', reportRoleFilter.value);
            if (reportSearchInput && reportSearchInput.value.trim()) params.set('search', reportSearchInput.value.trim());
            window.location.href = 'api/export_attendance_monthly.php?' + params.toString();
        });
    }

    if (exportScopeBtn) {
        exportScopeBtn.addEventListener('click', () => {
            const m = (exportMonthInput && exportMonthInput.value) ? exportMonthInput.value : new Date().toISOString().slice(0, 7);
            const params = new URLSearchParams();
            params.set('month', m);
            if (reportDeptFilter && reportDeptFilter.value) params.set('department_id', reportDeptFilter.value);
            if (reportRoleFilter && reportRoleFilter.value) params.set('role', reportRoleFilter.value);
            if (reportSearchInput && reportSearchInput.value.trim()) params.set('search', reportSearchInput.value.trim());
            window.location.href = 'api/export_attendance_monthly.php?' + params.toString();
        });
    }

    if (attSearchInput) {
        attSearchInput.addEventListener('input', renderAttendanceRows);
    }
    if (attViewMode) {
        attViewMode.addEventListener('change', renderAttendanceRows);
    }
    if (attDateInput) {
        attDateInput.addEventListener('change', loadSheet);
    }
    if (detailMonthInput) {
        detailMonthInput.addEventListener('change', () => {
            if (!currentDetailUserId) return;
            const selectedName = document.getElementById('detailTitle').textContent.split(' - ')[0] || 'User';
            loadUserDetail(currentDetailUserId, selectedName);
        });
    }
    if (exportUserBtn) {
        exportUserBtn.addEventListener('click', () => {
            if (!currentDetailUserId) return;
            const month = detailMonthInput?.value || '';
            let url = 'api/export_attendance_user.php?user_id=' + encodeURIComponent(currentDetailUserId);
            if (month) url += '&month=' + encodeURIComponent(month);
            window.location.href = url;
        });
    }

    loadSheet();
    loadReportDepartments();
    loadAdminReports();
})();
</script>

<?php include 'includes/footer.php'; ?>
