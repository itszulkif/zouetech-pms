<?php
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
check_login();
$page_title = 'Calendar';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
/* Calendar: minimal, fast, mobile-first */
.fc { font-family: inherit; }
.fc-theme-standard td, .fc-theme-standard th { border-color: rgba(55, 65, 81, 0.6); }
.fc .fc-col-header-cell { background: rgba(17, 24, 39, 0.6); color: #9ca3af; font-size: 0.7rem; }
.fc .fc-daygrid-day { background: rgba(10, 10, 10, 0.5); }
.fc .fc-daygrid-day-number { color: #6b7280; font-size: 0.75rem; }
.fc .fc-day-today { background: rgba(6, 182, 212, 0.15); }
.fc .fc-daygrid-day:hover { background: rgba(255,255,255,0.03); }
.fc-event { cursor: pointer; border: none; padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; }
.fc-event-project { background: #3b82f6; color: #fff; }
.fc-event-direct { background: #22c55e; color: #fff; }
.cal-task-popup { position: fixed; inset: 0; z-index: 100; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); }
.cal-task-popup.hidden { display: none !important; }
.cal-task-popup-inner { background: #171717; border: 1px solid #374151; border-radius: 8px; max-width: 320px; width: 90%; padding: 1rem; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
.cal-scope-toggle { color: #9ca3af; }
.cal-scope-toggle:hover { color: #e5e7eb; }
.cal-scope-toggle.active {
    background: linear-gradient(135deg, rgb(6 182 212), rgb(8 145 178));
    color: #fff;
    border-color: rgba(6, 182, 212, 0.5);
}
@media (max-width: 640px) {
    /* Let header grow on wrap; stack controls cleanly */
    .cal-page-header { height: auto !important; padding-top: 0.5rem; padding-bottom: 0.5rem; align-items: flex-start; gap: 0.5rem; }
    .cal-page-header .cal-header-right { width: 100%; justify-content: flex-start; }
    .fc .fc-toolbar-title { font-size: 1rem; }
    .fc .fc-button { padding: 0.35rem 0.5rem; font-size: 0.75rem; }
    .fc-event { font-size: 0.65rem; padding: 1px 4px; }
}
</style>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">
    <header class="cal-page-header h-14 md:h-16 flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 md:px-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex items-center space-x-3">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <div>
                <h1 class="text-lg md:text-xl font-bold text-white uppercase font-tech tracking-wider">Calendar</h1>
                <p class="text-[10px] text-cyan-400 font-mono hidden sm:block">Project & Direct tasks by deadline</p>
            </div>
        </div>
        <div class="cal-header-right flex items-center gap-3 flex-wrap">
            <?php if (in_array($_SESSION['role'], ['Department Head', 'Super Admin'], true)): ?>
            <div id="calScopeFilter" class="flex items-center gap-2">
                <span class="text-xs font-mono text-gray-400 uppercase tracking-wider">View:</span>
                <div class="inline-flex rounded-lg border border-cyan-500/30 bg-gray-800/80 p-0.5" role="group">
                    <button type="button" id="calScopeMine" class="cal-scope-toggle px-2.5 py-1.5 text-xs font-mono rounded-md transition-all border border-transparent text-gray-400 hover:text-white" data-scope="mine">Own Tasks</button>
                    <button type="button" id="calScopeTeam" class="cal-scope-toggle px-2.5 py-1.5 text-xs font-mono rounded-md transition-all border border-transparent text-gray-400 hover:text-white" data-scope="team">Team Tasks</button>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
            <div id="calDeptFilter" class="flex items-center gap-2">
                <label for="calDeptSelect" class="text-xs font-mono text-gray-400 uppercase tracking-wider whitespace-nowrap">Filter by team:</label>
                <select id="calDeptSelect" class="cal-dept-select bg-gray-800 border border-amber-500/40 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/60 transition-colors min-w-[160px] max-w-[200px] cursor-pointer" title="Show tasks for a specific team or all teams">
                    <option value="">All teams</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="flex items-center gap-2 text-xs text-gray-400">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Project</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> Direct</span>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-3 md:p-4">
        <div id="calendar"></div>
    </div>
</main>

<!-- Quick View Popup -->
<div id="calTaskPopup" class="cal-task-popup hidden">
    <div class="cal-task-popup-inner">
        <h3 class="font-bold text-white text-sm mb-2" id="calPopupTitle">—</h3>
        <p class="text-gray-400 text-xs mb-1">Assigned to: <span id="calPopupAssigned" class="text-amber-300">—</span></p>
        <p class="text-gray-400 text-xs mb-1">Status: <span id="calPopupStatus" class="text-cyan-400">—</span></p>
        <p class="text-gray-400 text-xs mb-3">Type: <span id="calPopupType">—</span></p>
        <a id="calPopupChatLink" href="#" class="inline-flex items-center gap-1.5 text-cyan-400 hover:text-cyan-300 text-xs font-medium">
            Open Task →
        </a>
        <button type="button" class="mt-3 w-full py-2 text-gray-400 hover:text-white border border-gray-600 rounded-lg text-xs transition-colors" id="calPopupClose">Close</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.10/index.global.min.js"></script>
<script>
(function() {
    const isDeptHead = <?php echo json_encode(isset($_SESSION['role']) && $_SESSION['role'] === 'Department Head'); ?>;
    const isSuperAdmin = <?php echo json_encode(isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin'); ?>;
    const hasScopeToggle = isDeptHead || isSuperAdmin;
    let calendarScope = 'team';
    let calendarDeptId = '';

    const popup = document.getElementById('calTaskPopup');
    const popupTitle = document.getElementById('calPopupTitle');
    const popupAssigned = document.getElementById('calPopupAssigned');
    const popupStatus = document.getElementById('calPopupStatus');
    const popupType = document.getElementById('calPopupType');
    const popupChatLink = document.getElementById('calPopupChatLink');
    const popupClose = document.getElementById('calPopupClose');

    function showTaskPopup(task) {
        popupTitle.textContent = task.title || '—';
        if (popupAssigned) popupAssigned.textContent = (task.assigned_name || '').trim() || '—';
        popupStatus.textContent = task.status || '—';
        popupType.textContent = task.type === 'project' ? 'Project Task' : 'Direct Task';
        popupChatLink.href = task.type === 'project'
            ? 'tasks.php?project_id=' + task.project_id
            : 'direct_tasks.php';
        popupChatLink.textContent = 'Open Task →';
        popup.classList.remove('hidden');
    }

    function closePopup() {
        popup.classList.add('hidden');
    }

    if (popupClose) popupClose.addEventListener('click', closePopup);
    if (popup) popup.addEventListener('click', function(e) {
        if (e.target === popup) closePopup();
    });

    function fetchEvents(info, successCallback, failureCallback) {
        const start = info.startStr ? info.startStr.slice(0, 10) : '';
        const end = info.endStr ? info.endStr.slice(0, 10) : '';
        let url = 'api/get_calendar_tasks.php?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end);
        if (hasScopeToggle) {
            url += '&scope=' + encodeURIComponent(calendarScope);
        }
        if (isSuperAdmin && calendarDeptId !== '') {
            url += '&department_id=' + encodeURIComponent(calendarDeptId);
        }
        fetch(url)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') { successCallback([]); return; }
                const events = (res.data || []).map(t => ({
                    id: String(t.id) + '-' + t.type,
                    title: t.title,
                    start: (t.date || '').toString().slice(0, 10),
                    allDay: true,
                    extendedProps: {
                        taskId: t.id,
                        status: t.status,
                        type: t.type,
                        project_id: t.project_id || 0,
                        assigned_name: t.assigned_name || '',
                    },
                    classNames: [t.type === 'project' ? 'fc-event-project' : 'fc-event-direct'],
                }));
                successCallback(events);
            })
            .catch(function() { successCallback([]); });
    }

    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        height: 'auto',
        events: fetchEvents,
        eventClick: function(arg) {
            arg.jsEvent.preventDefault();
            const p = arg.event.extendedProps;
            showTaskPopup({
                id: p.taskId,
                title: arg.event.title,
                status: p.status,
                type: p.type,
                project_id: p.project_id,
                assigned_name: p.assigned_name,
            });
        },
    });
    calendar.render();

    if (hasScopeToggle) {
        document.querySelectorAll('.cal-scope-toggle').forEach(function(btn) {
            if (btn.dataset.scope === 'team') btn.classList.add('active');
            btn.addEventListener('click', function() {
                const scope = this.dataset.scope;
                if (!scope || scope === calendarScope) return;
                calendarScope = scope;
                document.querySelectorAll('.cal-scope-toggle').forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
                calendar.refetchEvents();
            });
        });
    }

    if (isSuperAdmin) {
        var calDeptSelect = document.getElementById('calDeptSelect');
        if (calDeptSelect) {
            fetch('api/get_departments.php')
                .then(function(r) { return r.json(); })
                .then(function(departments) {
                    (departments || []).forEach(function(d) {
                        var opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = (d.name || 'Team ' + d.id).trim();
                        calDeptSelect.appendChild(opt);
                    });
                })
                .catch(function() {});
            calDeptSelect.addEventListener('change', function() {
                calendarDeptId = (this.value || '').trim();
                calendar.refetchEvents();
            });
        }
    }
})();
</script>

<?php include 'includes/footer.php'; ?>
