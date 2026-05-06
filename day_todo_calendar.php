<?php
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
require_role(['Super Admin']);
$page_title = 'Day To-Do Calendar';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
.todo-day-layout { display: grid; grid-template-columns: 1fr; gap: 1rem; }
@media (min-width: 1024px) {
    .todo-day-layout { grid-template-columns: minmax(320px, 440px) 1fr; }
}
.todo-card {
    border: 1px solid rgba(51, 65, 85, 0.7);
    background: rgba(15, 23, 42, 0.72);
    border-radius: 0.75rem;
}
.todo-task-item { cursor: pointer; }
.todo-task-item.active { border-color: rgba(56, 189, 248, 0.7); background: rgba(8, 47, 73, 0.35); }
.todo-task-item.done { opacity: 0.78; }
.todo-progress-track {
    width: 100%;
    height: 0.6rem;
    background: rgba(30, 41, 59, 0.9);
    border-radius: 999px;
    overflow: hidden;
}
.todo-progress-bar {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #06b6d4, #3b82f6);
    transition: width 0.25s ease;
}
.fc .fc-daygrid-day-number { color: #cbd5e1; font-size: 0.8rem; }
.fc .fc-day-today { background: rgba(6, 182, 212, 0.14); }
.fc .fc-daygrid-day.fc-day-selected { background: rgba(59, 130, 246, 0.26); }
.fc-event.todo-day-summary {
    border: none;
    font-size: 0.65rem;
    border-radius: 999px;
    padding: 1px 6px;
    cursor: pointer;
}
</style>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">
    <header class="h-14 md:h-16 flex items-center justify-between px-4 md:px-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex items-center space-x-3">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <div>
                <h1 class="text-lg md:text-xl font-bold text-white uppercase font-tech tracking-wider">Day-Wise To-Do Calendar</h1>
                <p class="text-[10px] text-cyan-400 font-mono hidden sm:block">Super Admin daily planning board</p>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-3 md:p-5">
        <div class="todo-day-layout">
            <section class="todo-card p-4 md:p-5 space-y-4">
                <div>
                    <p class="text-xs text-gray-400 font-mono uppercase tracking-widest">Selected day</p>
                    <h2 id="todoSelectedDate" class="text-lg font-semibold text-white mt-1">-</h2>
                </div>

                <div class="space-y-2">
                    <label for="todoOwnerSelect" class="text-xs text-gray-300 font-mono uppercase tracking-widest">Super Admin list</label>
                    <select id="todoOwnerSelect" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white"></select>
                    <p id="todoOwnerHint" class="text-[11px] text-gray-400"></p>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono">
                        <span class="text-gray-300">Progress</span>
                        <span id="todoProgressText" class="text-cyan-300">0%</span>
                    </div>
                    <div class="todo-progress-track">
                        <div id="todoProgressBar" class="todo-progress-bar"></div>
                    </div>
                    <p id="todoProgressSubText" class="text-[11px] text-gray-400">0 of 0 tasks completed</p>
                </div>

                <form id="todoAddForm" class="space-y-2">
                    <input id="todoTitleInput" type="text" maxlength="255" placeholder="Add task title..." class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white" required>
                    <textarea id="todoDetailsInput" rows="2" placeholder="Optional details..." class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white"></textarea>
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-sm font-semibold bg-cyan-600 hover:bg-cyan-500 text-white transition-colors">Add Task</button>
                </form>

                <div id="todoListWrap" class="space-y-2 max-h-[46vh] overflow-y-auto pr-1">
                    <p class="text-sm text-gray-400">No tasks for this day yet.</p>
                </div>
            </section>

            <section class="todo-card p-3 md:p-4">
                <div id="todoCalendar"></div>
            </section>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.10/index.global.min.js"></script>
<script>
(function() {
    const selectedDateLabel = document.getElementById('todoSelectedDate');
    const progressText = document.getElementById('todoProgressText');
    const progressSubText = document.getElementById('todoProgressSubText');
    const progressBar = document.getElementById('todoProgressBar');
    const listWrap = document.getElementById('todoListWrap');
    const addForm = document.getElementById('todoAddForm');
    const titleInput = document.getElementById('todoTitleInput');
    const detailsInput = document.getElementById('todoDetailsInput');
    const ownerSelect = document.getElementById('todoOwnerSelect');
    const ownerHint = document.getElementById('todoOwnerHint');

    function toYmdLocal(dateObj) {
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    let selectedDate = toYmdLocal(new Date());
    let monthSummaryByDate = {};
    let activeTaskId = 0;
    let currentUserId = 0;
    let selectedOwnerId = 0;
    let superAdmins = [];

    function formatDateLabel(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString(undefined, {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function setProgress(summary) {
        const total = Number(summary.total || 0);
        const completed = Number(summary.completed || 0);
        const percent = Number(summary.progress_percent || 0);
        progressText.textContent = percent + '%';
        progressSubText.textContent = completed + ' of ' + total + ' tasks completed';
        progressBar.style.width = percent + '%';
    }

    function isOwnListSelected() {
        return Number(selectedOwnerId) > 0 && Number(selectedOwnerId) === Number(currentUserId);
    }

    function updateOwnerHint() {
        const submitBtn = addForm.querySelector('button[type="submit"]');
        if (!isOwnListSelected()) {
            ownerHint.textContent = 'Viewing another Super Admin list in read-only mode.';
            addForm.classList.add('opacity-60');
            titleInput.disabled = true;
            detailsInput.disabled = true;
            if (submitBtn) submitBtn.disabled = true;
            return;
        }
        ownerHint.textContent = 'You can add, toggle, and delete tasks on your own list.';
        addForm.classList.remove('opacity-60');
        titleInput.disabled = false;
        detailsInput.disabled = false;
        if (submitBtn) submitBtn.disabled = false;
    }

    function renderOwnerOptions() {
        ownerSelect.innerHTML = superAdmins.map(function(admin) {
            const adminId = Number(admin.id || 0);
            const selected = adminId === Number(selectedOwnerId) ? ' selected' : '';
            const label = adminId === Number(currentUserId)
                ? String(admin.full_name || 'Super Admin') + ' (You)'
                : String(admin.full_name || 'Super Admin');
            return '<option value="' + adminId + '"' + selected + '>' + escapeHtml(label) + '</option>';
        }).join('');
        updateOwnerHint();
    }

    function loadSuperAdmins() {
        return fetch('api/day_todos.php?super_admins=1')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res || res.status !== 'success') {
                    throw new Error('Failed to load Super Admin users.');
                }
                superAdmins = Array.isArray(res.super_admins) ? res.super_admins : [];
                currentUserId = Number(res.current_user_id || 0);
                if (!selectedOwnerId) {
                    selectedOwnerId = currentUserId;
                }
                const ownerExists = superAdmins.some(function(admin) {
                    return Number(admin.id || 0) === Number(selectedOwnerId);
                });
                if (!ownerExists) {
                    selectedOwnerId = currentUserId;
                }
                renderOwnerOptions();
            });
    }

    function renderList(tasks) {
        if (!Array.isArray(tasks) || tasks.length === 0) {
            listWrap.innerHTML = '<p class="text-sm text-gray-400">No tasks for this day yet.</p>';
            return;
        }
        listWrap.innerHTML = tasks.map(function(task) {
            const doneClass = task.is_completed ? 'done' : '';
            const activeClass = Number(task.id) === Number(activeTaskId) ? 'active' : '';
            const details = (task.details || '').trim();
            const detailHtml = details !== ''
                ? '<p class="mt-2 text-xs text-gray-400">' + escapeHtml(details) + '</p>'
                : '<p class="mt-2 text-xs text-gray-500 italic">No details</p>';
            return ''
                + '<article class="todo-task-item ' + doneClass + ' ' + activeClass + ' border border-gray-700 rounded-lg p-3" data-task-id="' + task.id + '">'
                + '  <div class="flex items-start justify-between gap-3">'
                + '      <div class="flex items-start gap-2 min-w-0">'
                + '          <button type="button" class="todo-toggle mt-0.5 w-5 h-5 rounded border ' + (task.is_completed ? 'bg-emerald-500 border-emerald-400' : 'border-gray-500') + '" data-task-id="' + task.id + '" aria-label="Toggle task"></button>'
                + '          <p class="text-sm text-white ' + (task.is_completed ? 'line-through text-gray-400' : '') + '">' + escapeHtml(task.title) + '</p>'
                + '      </div>'
                + '      <button type="button" class="todo-delete text-[11px] text-red-400 hover:text-red-300" data-task-id="' + task.id + '">Delete</button>'
                + '  </div>'
                + '  <div class="todo-detail-wrap hidden">' + detailHtml + '</div>'
                + '</article>';
        }).join('');
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function loadDay(dateStr) {
        selectedDate = dateStr;
        activeTaskId = 0;
        selectedDateLabel.textContent = formatDateLabel(dateStr);
        fetch('api/day_todos.php?date=' + encodeURIComponent(dateStr) + '&owner_id=' + encodeURIComponent(String(selectedOwnerId)))
            .then(function(r) {
                return r.text().then(function(raw) {
                    let parsed = null;
                    let parseError = '';
                    try {
                        parsed = JSON.parse(raw);
                    } catch (e) {
                        parseError = e && e.message ? e.message : 'json_parse_failed';
                    }
                    if (parseError) {
                        throw new Error('Invalid JSON: ' + parseError);
                    }
                    return parsed;
                });
            })
            .then(function(res) {
                if (!res || res.status !== 'success') {
                    throw new Error('Unable to load tasks.');
                }
                setProgress(res.summary || { total: 0, completed: 0, progress_percent: 0 });
                renderList(res.tasks || []);
                calendar.refetchEvents();
            })
            .catch(function() {
                setProgress({ total: 0, completed: 0, progress_percent: 0 });
                listWrap.innerHTML = '<p class="text-sm text-red-300">Failed to load tasks for this day.</p>';
            });
    }

    function postAction(formData) {
        return fetch('api/day_todos.php', {
            method: 'POST',
            body: formData
        }).then(function(r) {
            return r.text().then(function(raw) {
                let parsed = null;
                let parseError = '';
                try {
                    parsed = JSON.parse(raw);
                } catch (e) {
                    parseError = e && e.message ? e.message : 'json_parse_failed';
                }
                if (parseError) {
                    throw new Error('Invalid JSON: ' + parseError);
                }
                return parsed;
            });
        });
    }

    addForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!isOwnListSelected()) {
            alert('You can only add tasks to your own list.');
            return;
        }
        const title = (titleInput.value || '').trim();
        if (!title) return;
        const fd = new FormData();
        fd.append('action', 'create');
        fd.append('date', selectedDate);
        fd.append('title', title);
        fd.append('details', (detailsInput.value || '').trim());
        postAction(fd).then(function(res) {
            if (res.status !== 'success') {
                throw new Error(res.message || 'Failed to add task.');
            }
            titleInput.value = '';
            detailsInput.value = '';
            loadDay(selectedDate);
            loadMonthSummary(calendar.view.activeStart, calendar.view.activeEnd);
        }).catch(function(err) {
            alert(err.message || 'Failed to add task.');
        });
    });

    listWrap.addEventListener('click', function(e) {
        const toggleBtn = e.target.closest('.todo-toggle');
        const deleteBtn = e.target.closest('.todo-delete');
        const taskItem = e.target.closest('.todo-task-item');

        if (toggleBtn) {
            if (!isOwnListSelected()) {
                alert('You can only update tasks on your own list.');
                return;
            }
            const taskId = Number(toggleBtn.dataset.taskId || 0);
            if (taskId <= 0) return;
            const fd = new FormData();
            fd.append('action', 'toggle');
            fd.append('task_id', String(taskId));
            postAction(fd).then(function(res) {
                if (res.status !== 'success') {
                    throw new Error(res.message || 'Failed to update task.');
                }
                loadDay(selectedDate);
                loadMonthSummary(calendar.view.activeStart, calendar.view.activeEnd);
            }).catch(function(err) {
                alert(err.message || 'Failed to update task.');
            });
            return;
        }

        if (deleteBtn) {
            if (!isOwnListSelected()) {
                alert('You can only delete tasks from your own list.');
                return;
            }
            const taskId = Number(deleteBtn.dataset.taskId || 0);
            if (taskId <= 0) return;
            if (!confirm('Delete this task?')) return;
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('task_id', String(taskId));
            postAction(fd).then(function(res) {
                if (res.status !== 'success') {
                    throw new Error(res.message || 'Failed to delete task.');
                }
                if (activeTaskId === taskId) activeTaskId = 0;
                loadDay(selectedDate);
                loadMonthSummary(calendar.view.activeStart, calendar.view.activeEnd);
            }).catch(function(err) {
                alert(err.message || 'Failed to delete task.');
            });
            return;
        }

        if (taskItem) {
            const taskId = Number(taskItem.dataset.taskId || 0);
            activeTaskId = (activeTaskId === taskId) ? 0 : taskId;
            Array.prototype.forEach.call(listWrap.querySelectorAll('.todo-task-item'), function(el) {
                const isActive = Number(el.dataset.taskId || 0) === activeTaskId;
                el.classList.toggle('active', isActive);
                const detail = el.querySelector('.todo-detail-wrap');
                if (detail) detail.classList.toggle('hidden', !isActive);
            });
        }
    });

    function loadMonthSummary(startDateObj, endDateObj) {
        const start = toYmdLocal(startDateObj);
        const inclusiveEnd = new Date(endDateObj.getTime());
        inclusiveEnd.setDate(inclusiveEnd.getDate() - 1);
        const end = toYmdLocal(inclusiveEnd);
        return fetch('api/day_todos.php?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end) + '&owner_id=' + encodeURIComponent(String(selectedOwnerId)))
            .then(function(r) {
                return r.text().then(function(raw) {
                    let parsed = null;
                    let parseError = '';
                    try {
                        parsed = JSON.parse(raw);
                    } catch (e) {
                        parseError = e && e.message ? e.message : 'json_parse_failed';
                    }
                    if (parseError) {
                        throw new Error('Invalid JSON: ' + parseError);
                    }
                    return parsed;
                });
            })
            .then(function(res) {
                if (!res || res.status !== 'success') {
                    monthSummaryByDate = {};
                    return;
                }
                monthSummaryByDate = {};
                (res.data || []).forEach(function(row) {
                    monthSummaryByDate[row.date] = row;
                });
            })
            .catch(function() {
                monthSummaryByDate = {};
            });
    }

    const calendarEl = document.getElementById('todoCalendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        height: 'auto',
        events: function(info, successCallback) {
            loadMonthSummary(info.start, info.end).then(function() {
                const events = Object.keys(monthSummaryByDate).map(function(dateKey) {
                    const row = monthSummaryByDate[dateKey] || { progress_percent: 0, completed: 0, total: 0 };
                    const p = Number(row.progress_percent || 0);
                    let color = '#ef4444';
                    if (p >= 100) color = '#10b981';
                    else if (p >= 60) color = '#f59e0b';
                    return {
                        start: dateKey,
                        title: p + '% (' + Number(row.completed || 0) + '/' + Number(row.total || 0) + ')',
                        allDay: true,
                        classNames: ['todo-day-summary'],
                        backgroundColor: color,
                        borderColor: color
                    };
                });
                successCallback(events);
            });
        },
        dateClick: function(info) {
            loadDay(info.dateStr);
            highlightSelectedDate();
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            if (info.event.start) {
                loadDay(toYmdLocal(info.event.start));
                highlightSelectedDate();
            }
        },
        datesSet: function() {
            highlightSelectedDate();
        }
    });

    function highlightSelectedDate() {
        const allCells = calendarEl.querySelectorAll('.fc-daygrid-day');
        Array.prototype.forEach.call(allCells, function(cell) {
            const date = cell.getAttribute('data-date');
            cell.classList.toggle('fc-day-selected', date === selectedDate);
        });
    }

    calendar.render();
    loadSuperAdmins()
        .then(function() {
            loadDay(selectedDate);
            highlightSelectedDate();
        })
        .catch(function() {
            ownerHint.textContent = 'Unable to load Super Admin users.';
            listWrap.innerHTML = '<p class="text-sm text-red-300">Failed to initialize day to-do board.</p>';
            addForm.classList.add('opacity-60');
        });

    ownerSelect.addEventListener('change', function() {
        selectedOwnerId = Number(ownerSelect.value || 0);
        activeTaskId = 0;
        updateOwnerHint();
        loadDay(selectedDate);
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
