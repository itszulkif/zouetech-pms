$(document).ready(function () {

    // State
    let allTasks = [];
    let currentPage = 1;
    const itemsPerPage = 10;
    const LIVE_REFRESH_MS = 5000;
    let liveRefreshTimer = null;
    const filters = { search: '', status: '', priority: '' };
    const urlParams = new URLSearchParams(window.location.search);
    const focusTaskId = parseInt(urlParams.get('task_id') || '', 10);
    // Own Tasks = tasks assigned to me. Team Tasks = tasks assigned to peers.
    let taskScope = 'mine';

    // Elements — Desktop
    const taskListBody = $('#directTaskListBody');
    const paginationContainer = $('#paginationContainer');
    const prevPageBtn = $('#prevPageBtn'), nextPageBtn = $('#nextPageBtn');
    const currentPageLabel = $('#currentPageLabel');
    const pageStartItem = $('#pageStartItem'), pageEndItem = $('#pageEndItem'), pageTotalItems = $('#pageTotalItems');

    // Elements — Mobile
    const cardContainer = $('#directTaskCardContainer');
    const mobilePagination = $('#mobilePaginationContainer');
    const mobilePrev = $('#mobilePrevPageBtn'), mobileNext = $('#mobileNextPageBtn');
    const mobilePageLabel = $('#mobileCurrentPageLabel');
    const mobileStart = $('#mobilePageStartItem'), mobileEnd = $('#mobilePageEndItem'), mobileTotal = $('#mobilePageTotalItems');

    // Filters
    const stdSearch = $('#standaloneSearchInput');
    const stdStatus = $('#standaloneStatusFilter');
    const stdPriority = $('#standalonePriorityFilter');

    // Filter Listeners
    stdSearch.on('input', function () { filters.search = $(this).val(); currentPage = 1; renderAll(); });
    stdStatus.on('change', function () { filters.status = $(this).val(); currentPage = 1; renderAll(); });
    stdPriority.on('change', function () { filters.priority = $(this).val(); currentPage = 1; renderAll(); });

    // Scope: My Tasks / Team Tasks
    const scopeFilterWrap = $('#directTaskScopeFilter');
    const deptFilterWrap = $('#directTaskDeptFilter');
    if (window.userRole === 'Team Member') {
        scopeFilterWrap.addClass('hidden');
        deptFilterWrap.addClass('hidden');
        taskScope = 'mine';
    } else if (window.userRole === 'Super Admin') {
        deptFilterWrap.addClass('hidden');
        taskScope = 'team';
        $('#scopeFilterMine, #scopeFilterTeam').on('click', function () {
            const scope = $(this).data('scope');
            if (scope && scope !== taskScope) {
                taskScope = scope;
                $('#scopeFilterMine, #scopeFilterTeam').removeClass('active');
                $(this).addClass('active');
                currentPage = 1;
                loadTasks();
                restartLiveRefresh();
            }
        });
        $('#scopeFilterTeam').addClass('active');
    } else {
        deptFilterWrap.addClass('hidden');
        $('#scopeFilterMine, #scopeFilterTeam').on('click', function () {
            const scope = $(this).data('scope');
            if (scope && scope !== taskScope) {
                taskScope = scope;
                $('#scopeFilterMine, #scopeFilterTeam').removeClass('active');
                $(this).addClass('active');
                currentPage = 1;
                loadTasks();
                restartLiveRefresh();
            }
        });
        $('#scopeFilterMine').addClass('active');
    }

    // Pagination — Desktop
    prevPageBtn.on('click', function () { if (currentPage > 1) { currentPage--; renderAll(); } });
    nextPageBtn.on('click', function () {
        const maxPage = Math.ceil(getFilteredTasks().length / itemsPerPage);
        if (currentPage < maxPage) { currentPage++; renderAll(); }
    });

    // Pagination — Mobile
    mobilePrev.on('click', function () { if (currentPage > 1) { currentPage--; renderAll(); } });
    mobileNext.on('click', function () {
        const maxPage = Math.ceil(getFilteredTasks().length / itemsPerPage);
        if (currentPage < maxPage) { currentPage++; renderAll(); }
    });

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------
    function getFilteredTasks() {
        return allTasks.filter(task => {
            const title = (task.title || '').toLowerCase();
            const matchSearch = filters.search === '' || title.includes(filters.search.toLowerCase());
            const matchStatus = filters.status === '' || task.status === filters.status;
            const matchPriority = filters.priority === '' || task.priority === filters.priority;
            return matchSearch && matchStatus && matchPriority;
        });
    }

    function getPriorityClass(priority) {
        priority = (priority || 'Medium').trim();
        if (priority === 'Urgent') return 'text-red-400 bg-red-500/10 border-red-500/30';
        if (priority === 'High') return 'text-orange-400 bg-orange-500/10 border-orange-500/30';
        if (priority === 'Low') return 'text-gray-400 bg-gray-700/50 border-gray-600';
        return 'text-cyan-400 bg-cyan-500/10 border-cyan-500/20';
    }

    function getStatusClass(status) {
        const m = {
            'Pending': 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
            'In Progress': 'bg-blue-500/10 text-blue-500 border-blue-500/20',
            'Completed': 'bg-green-500/10 text-green-500 border-green-500/20',
            'Review': 'bg-purple-500/10 text-purple-500 border-purple-500/20',
            'Missed': 'bg-red-500/10 text-red-500 border-red-500/20'
        };
        return m[status] || m['Pending'];
    }

    function getTimeframeDisplay(task) {
        const timeStr = task.specific_time ? ` <span class="text-purple-400">@ ${task.specific_time.substring(0, 5)}</span>` : '';
        return `<div class="flex flex-col leading-tight">
            <span class="text-[10px] text-gray-500"><span class="font-mono">start</span> ${task.start_date || '—'}</span>
            <span class="text-[10px] text-gray-300"><span class="font-mono text-gray-300">end</span> ${task.due_date || '—'}${timeStr}</span>
        </div>`;
    }

    function getActionButtons(task) {
        if (window.userRole !== 'Super Admin') {
            if (task.status === 'Review') {
                return '<span class="px-2 py-1 text-[10px] font-mono text-amber-400 bg-amber-500/10 border border-amber-500/30 rounded">🔒 Under Review</span>';
            }
            if (task.status === 'Missed') {
                return '<span class="px-2 py-1 text-[10px] font-mono text-red-400 bg-red-500/10 border border-red-500/30 rounded">🔒 Missed</span>';
            }
            return '';
        }
        const safeTitle = (task.title || '').replace(/'/g, "\\'");
        return `<button onclick="editDirectTask(${task.id})" class="text-amber-400 hover:text-amber-300 transition-colors" title="Edit Task">
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 013 3L12 14l-4 1 1-4 7.5-7.5z"></path></svg>
        </button><button onclick="deleteTask(${task.id}, '${safeTitle}')" class="text-red-400 hover:text-red-300 transition-colors" title="Delete Task">
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        </button>`;
    }

    function renderTaskRow(task) {
        const priorityClass = getPriorityClass(task.priority);
        const badgeClass = getStatusClass(task.status);
        const statuses = Array.isArray(task.allowed_statuses) && task.allowed_statuses.length
            ? task.allowed_statuses
            : ["Pending", "In Progress", "Review", "Completed"];
        const fullDescription = task.description ? String(task.description).replace(/</g, '&lt;').replace(/>/g, '&gt;') : 'No description provided.';
        const detailsLine = [
            'Start: ' + (task.start_date || '-'),
            'End: ' + (task.end_date || '-'),
            'Due: ' + (task.due_date || '-'),
            'Time: ' + (task.specific_time ? task.specific_time.substring(0, 5) : '-')
        ].join(' | ');
        const statusControl = `<select class="task-status-select bg-gray-800 border border-gray-700 rounded px-2 py-1 text-xs text-white" data-task-id="${task.id}">
            ${statuses.map(s => `<option value="${s}" ${s === task.status ? "selected" : ""}>${s}</option>`).join("")}
        </select>`;

        return `<tr id="task-row-${task.id}" class="border-b border-gray-700 hover:bg-gray-800 transition-colors">
            <td class="py-3 px-5 font-medium text-white">
                <div class="flex flex-col gap-1">
                    <span>${task.title}</span>
                    <span class="text-[11px] text-gray-300 whitespace-pre-wrap break-words">${fullDescription}</span>
                    <span class="text-[10px] text-purple-300/80 font-mono whitespace-normal break-words">${detailsLine}</span>
                </div>
            </td>
            <td class="py-3 px-5 text-sm text-gray-400">
                <div class="flex items-center">
                    <div class="w-6 h-6 rounded-full bg-gray-700 flex items-center justify-center text-[10px] mr-2 border border-gray-600 overflow-hidden flex-shrink-0">
                        ${task.assigned_avatar ? `<img src="${task.assigned_avatar}" class="w-full h-full object-cover">` : (task.assigned_name || 'U').charAt(0)}
                    </div>
                    <span class="truncate max-w-[120px]">${task.assigned_name || 'Unassigned'}</span>
                </div>
            </td>
            <td class="py-3 px-5 text-sm text-gray-400 font-mono">${getTimeframeDisplay(task)}</td>
            <td class="py-3 px-5">
                <span class="text-[10px] font-mono px-2 py-0.5 rounded border uppercase tracking-wider ${priorityClass}">${task.priority || 'Medium'}</span>
            </td>
            <td class="py-3 px-5">
                <span class="px-2.5 py-1 rounded-full text-xs border ${badgeClass}">${task.status}</span>
            </td>
            <td class="py-3 px-5 text-right">
                <div class="flex items-center justify-end gap-2">
                    ${statusControl}
                    ${getActionButtons(task)}
                </div>
            </td>
        </tr>`;
    }

    function renderMobileCard(task) {
        const priorityClass = getPriorityClass(task.priority);
        const badgeClass = getStatusClass(task.status);
        const statuses = Array.isArray(task.allowed_statuses) && task.allowed_statuses.length
            ? task.allowed_statuses
            : ["Pending", "In Progress", "Review", "Completed"];
        const fullDescription = task.description ? String(task.description).replace(/</g, '&lt;').replace(/>/g, '&gt;') : 'No description provided.';
        const statusControl = `<select class="task-status-select bg-gray-800 border border-gray-700 rounded px-2 py-1 text-xs text-white" data-task-id="${task.id}">
            ${statuses.map(s => `<option value="${s}" ${s === task.status ? "selected" : ""}>${s}</option>`).join("")}
        </select>`;

        return `<div id="task-card-${task.id}" class="glass rounded-xl border border-purple-500/15 p-4 space-y-3">
            <!-- Title row -->
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-white leading-tight">${task.title}</h3>
                    <p class="text-[11px] text-gray-300 mt-1 whitespace-pre-wrap break-words">${fullDescription}</p>
                </div>
                <span class="flex-shrink-0 text-[10px] font-mono px-2 py-1 rounded border uppercase tracking-wider ${priorityClass}">${task.priority || 'Med'}</span>
            </div>

            <!-- Meta row -->
            <div class="grid grid-cols-2 gap-2 text-xs text-gray-400">
                <div class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full bg-gray-700 flex items-center justify-center text-[9px] border border-gray-600 overflow-hidden flex-shrink-0">
                        ${task.assigned_avatar ? `<img src="${task.assigned_avatar}" class="w-full h-full object-cover">` : (task.assigned_name || 'U').charAt(0)}
                    </div>
                    <span class="truncate">${task.assigned_name || 'Unassigned'}</span>
                </div>
                <div class="text-right font-mono text-[10px]">
                    <div class="text-gray-500">${task.start_date || '—'}</div>
                    <div class="text-white font-bold">${task.due_date || '—'}</div>
                        <div class="text-purple-300/80">${task.end_date || '—'} ${task.specific_time ? ('@ ' + task.specific_time.substring(0, 5)) : ''}</div>
                </div>
            </div>

            <!-- Status & Actions -->
            <div class="flex items-center justify-between pt-1 border-t border-gray-700/50">
                <span class="px-2.5 py-1 rounded-full text-xs border ${badgeClass}">${task.status}</span>
                <div class="flex items-center gap-3">
                    ${statusControl}
                    ${getActionButtons(task)}
                </div>
            </div>
        </div>`;
    }

    // -------------------------------------------------------
    // Render — updates both desktop table and mobile cards
    // -------------------------------------------------------
    function renderAll() {
        const filteredTasks = getFilteredTasks();
        if (Number.isInteger(focusTaskId)) {
            const focusedIndex = filteredTasks.findIndex(task => Number(task.id) === focusTaskId);
            if (focusedIndex >= 0) {
                currentPage = Math.floor(focusedIndex / itemsPerPage) + 1;
            }
        }
        const totalItems = filteredTasks.length;

        if (totalItems === 0) {
            const emptyMsg = '<tr><td colspan="6" class="py-12 text-center text-gray-500 font-mono text-xs uppercase">No direct tasks found. Create one to get started.</td></tr>';
            taskListBody.html(emptyMsg);
            cardContainer.html('<div class="glass rounded-xl border border-purple-500/20 p-8 text-center text-gray-500 font-mono text-xs uppercase">No direct tasks found.</div>');
            paginationContainer.addClass('hidden').removeClass('flex');
            mobilePagination.addClass('hidden');
            return;
        }

        const maxPage = Math.ceil(totalItems / itemsPerPage);
        if (currentPage > maxPage) currentPage = maxPage;
        if (currentPage < 1) currentPage = 1;

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        const paginated = filteredTasks.slice(startIndex, endIndex);

        // Desktop table
        taskListBody.empty();
        paginated.forEach(task => taskListBody.append(renderTaskRow(task)));

        // Mobile cards
        cardContainer.empty();
        paginated.forEach(task => cardContainer.append(renderMobileCard(task)));

        if (Number.isInteger(focusTaskId)) {
            const focusedNode = document.getElementById('task-row-' + focusTaskId) || document.getElementById('task-card-' + focusTaskId);
            if (focusedNode) {
                focusedNode.classList.add('ring-2', 'ring-purple-400', 'ring-offset-2', 'ring-offset-gray-900');
                focusedNode.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Desktop pagination
        paginationContainer.removeClass('hidden').addClass('flex');
        currentPageLabel.text(currentPage);
        pageStartItem.text(startIndex + 1);
        pageEndItem.text(endIndex);
        pageTotalItems.text(totalItems);
        prevPageBtn.prop('disabled', currentPage === 1);
        nextPageBtn.prop('disabled', currentPage === maxPage);

        // Mobile pagination
        mobilePagination.removeClass('hidden');
        mobilePageLabel.text(currentPage);
        mobileStart.text(startIndex + 1);
        mobileEnd.text(endIndex);
        mobileTotal.text(totalItems);
        mobilePrev.prop('disabled', currentPage === 1);
        mobileNext.prop('disabled', currentPage === maxPage);
    }

    // -------------------------------------------------------
    // Data Loading
    // -------------------------------------------------------
    function loadTasks(options) {
        options = options || {};
        const showLoading = options.showLoading !== false;
        const resetPage = options.resetPage !== false;
        if (showLoading) {
            const loadMsg = '<tr><td colspan="6" class="py-8 text-center text-gray-500">Loading tasks...</td></tr>';
            taskListBody.html(loadMsg);
            cardContainer.html('<div class="glass rounded-xl border border-purple-500/20 p-6 text-center text-gray-400 text-sm">Loading...</div>');
        }

        const params = { project_id: 0 };
        params.scope = taskScope || 'team';
        $.ajax({
            url: 'api/get_tasks.php',
            data: params,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    allTasks = response.data || [];
                    if (resetPage) currentPage = 1;
                    renderAll();
                } else {
                    const empty = 'No direct tasks found. Create one to get started.';
                    taskListBody.html(`<tr><td colspan="6" class="py-12 text-center text-gray-500 font-mono text-xs uppercase">${empty}</td></tr>`);
                    cardContainer.html(`<div class="glass rounded-xl border border-purple-500/20 p-8 text-center text-gray-500 font-mono text-xs uppercase">${empty}</div>`);
                    paginationContainer.addClass('hidden').removeClass('flex');
                    mobilePagination.addClass('hidden');
                }
            },
            error: function () {
                taskListBody.html('<tr><td colspan="6" class="py-8 text-center text-red-500">Failed to load tasks.</td></tr>');
                cardContainer.html('<div class="glass rounded-xl border border-red-500/20 p-6 text-center text-red-400 text-sm">Failed to load tasks.</div>');
            }
        });
    }

    function restartLiveRefresh() {
        if (liveRefreshTimer) clearInterval(liveRefreshTimer);
        liveRefreshTimer = setInterval(function () {
            loadTasks({ showLoading: false, resetPage: false });
        }, LIVE_REFRESH_MS);
    }

    function loadMembers() {
        $.ajax({
            url: 'api/get_members.php?project_id=0',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                const container = $('#directAssigneeList');
                if (response.length === 0) {
                    container.html('<p class="text-gray-500 italic text-xs py-2">No members available.</p>');
                    return;
                }
                const grouped = {};
                response.forEach(m => {
                    const dept = m.department_name || 'General';
                    if (!grouped[dept]) grouped[dept] = [];
                    grouped[dept].push(m);
                });
                let html = '';
                for (const [dept, members] of Object.entries(grouped)) {
                    html += `<p class="text-[10px] font-mono text-purple-400 uppercase tracking-widest pt-2 pb-1 border-b border-gray-700/50">${dept}</p>`;
                    members.forEach(m => {
                        html += `<label class="flex items-center space-x-2.5 py-2 px-1 rounded hover:bg-gray-800 cursor-pointer transition-colors group">
                            <input type="checkbox" name="assigned_to[]" value="${m.id}" class="w-4 h-4 accent-purple-500 cursor-pointer flex-shrink-0">
                            <span class="text-gray-300 group-hover:text-white text-sm transition-colors flex-1">${m.full_name}</span>
                            <span class="text-[10px] text-gray-600 font-mono">${m.role}</span>
                        </label>`;
                    });
                }
                container.html(html);
            }
        });
    }

    function updateTaskStatus(taskId, status) {
        $.ajax({
            url: 'api/update_task_status.php',
            type: 'POST',
            data: { task_id: taskId, status: status },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    loadTasks({ showLoading: false, resetPage: false });
                } else {
                    alert('Failed to update status: ' + response.message);
                    loadTasks({ showLoading: false, resetPage: false });
                }
            }
        });
    }

    window.deleteTask = function (taskId, title) {
        if (!confirm(`Are you sure you want to delete task "${title}"? This cannot be undone.`)) return;
        $.ajax({
            url: 'api/delete_task.php',
            type: 'POST',
            data: { task_id: taskId },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    allTasks = allTasks.filter(t => t.id != taskId);
                    renderAll();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function () { alert('An error occurred while deleting the task.'); }
        });
    };

    // -------------------------------------------------------
    // Modal Logic
    // -------------------------------------------------------
    const directModal = $('#createDirectTaskModal');
    const directModalContent = $('#directModalContent');
    const directForm = $('#createDirectTaskForm');
    const editDirectModal = $('#editDirectTaskModal');
    const editDirectModalContent = $('#editDirectModalContent');
    const editDirectTaskForm = $('#editDirectTaskForm');

    $('#openDirectTaskModalBtn').on('click', function () {
        directModal.removeClass('hidden').addClass('flex');
        setTimeout(() => directModalContent.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100'), 10);
        loadMembers();
    });

    function closeDirectModal() {
        directModalContent.removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => directModal.addClass('hidden').removeClass('flex'), 300);
    }
    $('#closeDirectModalBtn, #closeDirectModalBtn2').on('click', closeDirectModal);
    directModal.on('click', function (e) { if ($(e.target).is(directModal)) closeDirectModal(); });

    function closeEditDirectModal() {
        editDirectModalContent.removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => editDirectModal.addClass('hidden').removeClass('flex'), 300);
    }
    $('#closeEditDirectModalBtn, #closeEditDirectModalBtn2').on('click', closeEditDirectModal);
    editDirectModal.on('click', function (e) { if ($(e.target).is(editDirectModal)) closeEditDirectModal(); });

    directForm.on('submit', function (e) {
        e.preventDefault();
        const checkedAssignees = directForm.find('input[name="assigned_to[]"]:checked');
        if (checkedAssignees.length === 0) {
            alert('Please select at least one member to assign this task to.');
            return;
        }
        const formData = new FormData(this);
        $.ajax({
            url: 'api/create_task.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    closeDirectModal();
                    directForm[0].reset();
                    $('#directAssigneeList input[type="checkbox"]').prop('checked', false);
                    loadTasks({ showLoading: false, resetPage: false });
                    if (window.TaskAssignmentNotifications && typeof window.TaskAssignmentNotifications.refreshNow === 'function') {
                        window.TaskAssignmentNotifications.refreshNow();
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function (xhr) {
                let msg = 'Request failed (' + xhr.status + ')';
                if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                    msg = xhr.responseJSON.message || xhr.responseJSON.error;
                } else if (xhr.responseText) {
                    try {
                        const parsed = JSON.parse(xhr.responseText);
                        msg = parsed.message || parsed.error || msg;
                    } catch (_) {
                        msg = xhr.responseText.substring(0, 300);
                    }
                }
                alert('Error: ' + msg);
            }
        });
    });

    function fillEditDirectMembers(selectedIds) {
        const container = $('#editDirectAssigneeList');
        container.html('<p class="text-gray-500 italic text-xs py-2">Loading members...</p>');
        $.ajax({
            url: 'api/get_members.php?project_id=0',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.length === 0) {
                    container.html('<p class="text-gray-500 italic text-xs py-2">No members available.</p>');
                    return;
                }
                const grouped = {};
                response.forEach(m => {
                    const dept = m.department_name || 'General';
                    if (!grouped[dept]) grouped[dept] = [];
                    grouped[dept].push(m);
                });
                let html = '';
                for (const [dept, members] of Object.entries(grouped)) {
                    html += `<p class="text-[10px] font-mono text-amber-400 uppercase tracking-widest pt-2 pb-1 border-b border-gray-700/50">${dept}</p>`;
                    members.forEach(m => {
                        const checked = selectedIds.includes(Number(m.id)) ? 'checked' : '';
                        html += `<label class="flex items-center space-x-2.5 py-2 px-1 rounded hover:bg-gray-800 cursor-pointer transition-colors group">
                            <input type="checkbox" name="assigned_to[]" value="${m.id}" ${checked} class="w-4 h-4 accent-amber-500 cursor-pointer flex-shrink-0">
                            <span class="text-gray-300 group-hover:text-white text-sm transition-colors flex-1">${m.full_name}</span>
                            <span class="text-[10px] text-gray-600 font-mono">${m.role}</span>
                        </label>`;
                    });
                }
                container.html(html);
            }
        });
    }

    window.editDirectTask = function (taskId) {
        $.ajax({
            url: 'api/get_task_details.php',
            type: 'GET',
            dataType: 'json',
            data: { task_id: taskId },
            success: function (response) {
                if (!response || response.status !== 'success' || !response.data) {
                    alert('Unable to load task details.');
                    return;
                }
                const task = response.data;
                $('#editDirectTaskId').val(task.id);
                $('#editDirectTaskTitle').val(task.title || '');
                $('#editDirectTaskDescription').val(task.description || '');
                $('#editDirectStartDate').val(task.start_date ? String(task.start_date).split(' ')[0] : '');
                $('#editDirectEndDate').val(task.end_date ? String(task.end_date).split(' ')[0] : '');
                $('#editDirectSpecificTime').val(task.specific_time ? String(task.specific_time).substring(0, 5) : '');
                editDirectTaskForm.find('input[name="priority"][value="' + (task.priority || 'Medium') + '"]').prop('checked', true);

                const selectedIds = Array.isArray(task.assigned_user_ids) ? task.assigned_user_ids.map(Number) : [];
                fillEditDirectMembers(selectedIds);

                editDirectModal.removeClass('hidden').addClass('flex');
                setTimeout(() => editDirectModalContent.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100'), 10);
            },
            error: function () {
                alert('Unable to load task details.');
            }
        });
    };

    editDirectTaskForm.on('submit', function (e) {
        e.preventDefault();
        const checkedAssignees = editDirectTaskForm.find('input[name="assigned_to[]"]:checked');
        if (checkedAssignees.length === 0) {
            alert('Please select at least one member to assign this task to.');
            return;
        }
        $.ajax({
            url: 'api/update_task.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    closeEditDirectModal();
                    loadTasks({ showLoading: false, resetPage: false });
                } else {
                    alert('Error: ' + (response.message || 'Failed to update task.'));
                }
            },
            error: function () {
                alert('Failed to update task.');
            }
        });
    });

    $(document).on('change', '.task-status-select', function () {
        const taskId = $(this).data('task-id');
        const newStatus = $(this).val();
        if (taskId) updateTaskStatus(taskId, newStatus);
    });

    // -------------------------------------------------------
    // Initial Load
    // -------------------------------------------------------
    loadTasks();
    restartLiveRefresh();
});
