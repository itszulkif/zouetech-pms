$(document).ready(function () {
    const projectSelect = $("#projectSelect");
    const taskListBody = $("#taskListBody");
    const openModalBtn = $("#openTaskModalBtn");
    const modal = $("#createTaskModal");
    const modalContent = $("#modalContent");
    const createTaskForm = $("#createTaskForm");
    const modalProjectId = $("#modalProjectId");
    const editModal = $("#editTaskModal");
    const editModalContent = $("#editModalContent");
    const editTaskForm = $("#editTaskForm");

    const paginationContainer = $("#paginationContainer");
    const prevPageBtn = $("#prevPageBtn"), nextPageBtn = $("#nextPageBtn");
    const currentPageLabel = $("#currentPageLabel");
    const pageStartItem = $("#pageStartItem"), pageEndItem = $("#pageEndItem"), pageTotalItems = $("#pageTotalItems");

    const cardContainer = $("#taskCardContainer");
    const mobilePagination = $("#mobilePaginationContainer");
    const mobilePrev = $("#mobilePrevPageBtn"), mobileNext = $("#mobileNextPageBtn");
    const mobilePageLabel = $("#mobileCurrentPageLabel");
    const mobileStart = $("#mobilePageStartItem"), mobileEnd = $("#mobilePageEndItem"), mobileTotal = $("#mobilePageTotalItems");

    let allTasks = [];
    let currentPage = 1;
    const itemsPerPage = 10;
    const LIVE_REFRESH_MS = 5000;
    let liveRefreshTimer = null;
    const filters = { search: "", status: "", priority: "" };
    let taskScope = 'team';
    const urlParams = new URLSearchParams(window.location.search);
    const focusTaskId = parseInt(urlParams.get('task_id') || '', 10);
    const projectScopeFilterWrap = $('#projectTaskScopeFilter');
    const projectScopeMineBtn = $('#projectScopeMine');
    const projectScopeTeamBtn = $('#projectScopeTeam');

    if (window.userRole === 'Team Member') {
        taskScope = 'mine';
        projectScopeFilterWrap.addClass('hidden');
    } else {
        projectScopeTeamBtn.addClass('active');
        projectScopeMineBtn.on('click', function () {
            if (taskScope === 'mine') return;
            taskScope = 'mine';
            projectScopeMineBtn.addClass('active');
            projectScopeTeamBtn.removeClass('active');
            currentPage = 1;
            if (projectSelect.val()) loadTasks(projectSelect.val());
        });
        projectScopeTeamBtn.on('click', function () {
            if (taskScope === 'team') return;
            taskScope = 'team';
            projectScopeTeamBtn.addClass('active');
            projectScopeMineBtn.removeClass('active');
            currentPage = 1;
            if (projectSelect.val()) loadTasks(projectSelect.val());
        });
    }

    $("#projectSearchInput").on("input", function () { filters.search = $(this).val(); currentPage = 1; renderAll(); });
    $("#projectStatusFilter").on("change", function () { filters.status = $(this).val(); currentPage = 1; renderAll(); });
    $("#projectPriorityFilter").on("change", function () { filters.priority = $(this).val(); currentPage = 1; renderAll(); });

    prevPageBtn.on("click", function () { if (currentPage > 1) { currentPage--; renderAll(); } });
    nextPageBtn.on("click", function () { if (currentPage < Math.ceil(getFilteredTasks().length / itemsPerPage)) { currentPage++; renderAll(); } });
    mobilePrev.on("click", function () { if (currentPage > 1) { currentPage--; renderAll(); } });
    mobileNext.on("click", function () { if (currentPage < Math.ceil(getFilteredTasks().length / itemsPerPage)) { currentPage++; renderAll(); } });

    function getFilteredTasks() {
        return allTasks.filter(function (task) {
            const matchSearch = filters.search === "" || (task.title || "").toLowerCase().includes(filters.search.toLowerCase());
            const matchStatus = filters.status === "" || task.status === filters.status;
            const matchPriority = filters.priority === "" || task.priority === filters.priority;
            return matchSearch && matchStatus && matchPriority;
        });
    }

    function getPriorityClass(priority) {
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

    function resolveTaskDeadline(task) {
        if (!task || !task.due_date) return null;
        const datePart = String(task.due_date).split(' ')[0];
        if (task.specific_time) {
            return new Date(datePart + 'T' + task.specific_time);
        }
        return new Date(datePart + 'T23:59:59');
    }

    function getScoreImpact(task) {
        if (!task || !task.due_date || task.status === 'Completed' || task.status === 'Missed') return '';
        const deadline = resolveTaskDeadline(task);
        if (!deadline || Number.isNaN(deadline.getTime())) return '';
        const diffHours = (deadline.getTime() - new Date().getTime()) / (1000 * 60 * 60);
        if (diffHours < 0) return '<span class="text-[10px] font-bold text-red-500 bg-red-500/10 px-1.5 py-0.5 rounded ml-1.5 border border-red-500/20 animate-pulse" title="Late Penalty">-5</span>';
        if (diffHours <= 24) return '<span class="text-[10px] font-bold text-blue-400 bg-blue-500/10 px-1.5 py-0.5 rounded ml-1.5 border border-blue-500/20" title="On-Time">+2</span>';
        return '<span class="text-[10px] font-bold text-green-400 bg-green-500/10 px-1.5 py-0.5 rounded ml-1.5 border border-green-500/20" title="Early Bonus">+5</span>';
    }

    function getStatusOptionsHtml(task) {
        const statuses = Array.isArray(task.allowed_statuses) && task.allowed_statuses.length
            ? task.allowed_statuses
            : ["Pending", "In Progress", "Review", "Completed"];
        return statuses.map(function (s) {
            return '<option value="' + s + '"' + (s === task.status ? " selected" : "") + ">" + s + "</option>";
        }).join("");
    }

    function getStatusControl(task) {
        return '<select class="task-status-select bg-gray-800 border border-gray-700 rounded px-2 py-1 text-xs text-white" data-task-id="' + task.id + '">' + getStatusOptionsHtml(task) + "</select>";
    }

    function escapeHtml(value) {
        return String(value == null ? "" : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getProjectBadge(task) {
        if (projectSelect.val() !== 'all' || !task.project_name) return '';
        return '<span class="inline-flex items-center text-[10px] font-mono uppercase tracking-wider text-indigo-300 bg-indigo-500/10 border border-indigo-500/20 rounded px-2 py-0.5">Project: ' + escapeHtml(task.project_name) + '</span>';
    }

    function getDeadlineDisplay(task) {
        if (!task || !task.due_date) return '-';
        const datePart = String(task.due_date).split(' ')[0];
        const timePart = task.specific_time ? task.specific_time.substring(0, 5) : '23:59';
        return datePart + ' @ ' + timePart;
    }

    function getDeleteButton(task) {
        if (window.userRole !== "Super Admin") return "";
        const displayTitle = (task.title != null && task.title !== "" && String(task.title) !== "0") ? task.title : "Untitled Task";
        const safeTitle = (displayTitle || "").replace(/'/g, "\\'");
        return '<button onclick="deleteTask(' + task.id + ", '" + safeTitle + '\')" class="text-red-400 hover:text-red-300 transition-colors" title="Delete"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>';
    }

    function getEditButton(task) {
        if (window.userRole !== "Super Admin") return "";
        return '<button onclick="editTask(' + task.id + ')" class="text-amber-400 hover:text-amber-300 transition-colors" title="Edit"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 013 3L12 14l-4 1 1-4 7.5-7.5z"></path></svg></button>';
    }

    function renderTaskRow(task) {
        const scoreBadge = getScoreImpact(task);
        const priorityClass = getPriorityClass(task.priority);
        const badgeClass = getStatusClass(task.status);
        const avatarHtml = task.assigned_avatar ? '<img src="' + task.assigned_avatar + '" class="w-full h-full object-cover">' : (task.assigned_name || 'U').charAt(0);
        const displayTitle = (task.title != null && task.title !== '' && String(task.title) !== '0') ? task.title : 'Untitled Task';
        const fullDescription = task.description ? String(task.description).replace(/</g, '&lt;').replace(/>/g, '&gt;') : 'No description provided.';
        const projectBadge = getProjectBadge(task);
        return '<tr id="task-row-' + task.id + '" class="border-b border-gray-700 hover:bg-gray-800 transition-colors">' +
            '<td class="py-3 px-5 font-medium text-white"><div class="flex flex-col gap-1"><span class="flex items-center">' + displayTitle + scoreBadge + '</span>' +
            projectBadge +
            '<span class="text-[11px] text-gray-300 whitespace-pre-wrap break-words">' + fullDescription + '</span>' +
            '</div></td>' +
            '<td class="py-3 px-5 text-sm text-gray-400"><div class="flex items-center"><div class="w-6 h-6 rounded-full bg-gray-700 flex items-center justify-center text-[10px] mr-2 border border-gray-600 overflow-hidden flex-shrink-0">' + avatarHtml + '</div><span class="truncate max-w-[120px]">' + (task.assigned_name || 'Unassigned') + '</span></div></td>' +
            '<td class="py-3 px-5 text-sm text-gray-400 font-mono">' + getDeadlineDisplay(task) + '</td>' +
            '<td class="py-3 px-5"><span class="text-[10px] font-mono px-2 py-0.5 rounded border uppercase tracking-wider ' + priorityClass + '">' + (task.priority || 'Medium') + '</span></td>' +
            '<td class="py-3 px-5"><span class="px-2.5 py-1 rounded-full text-xs border ' + badgeClass + '">' + task.status + '</span></td>' +
            '<td class="py-3 px-5 text-right"><div class="flex items-center justify-end gap-2">' + getStatusControl(task) + getEditButton(task) + getDeleteButton(task) + '</div></td>' +
            '</tr>';
    }

    function renderMobileCard(task) {
        const priorityClass = getPriorityClass(task.priority);
        const badgeClass = getStatusClass(task.status);
        const scoreBadge = getScoreImpact(task);
        const displayTitle = (task.title != null && task.title !== '' && String(task.title) !== '0') ? task.title : 'Untitled Task';
        const avatarHtml = task.assigned_avatar ? '<img src="' + task.assigned_avatar + '" class="w-full h-full object-cover">' : (task.assigned_name || 'U').charAt(0);
        const fullDescription = task.description ? String(task.description).replace(/</g, '&lt;').replace(/>/g, '&gt;') : 'No description provided.';
        const projectBadge = getProjectBadge(task);
        return '<div id="task-card-' + task.id + '" class="glass rounded-xl border border-gray-700 p-4 space-y-3">' +
            '<div class="flex items-start justify-between gap-2">' +
            '<div class="min-w-0 flex-1"><h3 class="text-sm font-bold text-white leading-tight flex items-center flex-wrap gap-1">' + displayTitle + scoreBadge + '</h3>' +
            projectBadge +
            '<p class="text-[11px] text-gray-300 mt-1 whitespace-pre-wrap break-words">' + fullDescription + '</p>' +
            '</div><span class="flex-shrink-0 text-[10px] font-mono px-2 py-1 rounded border uppercase tracking-wider ' + priorityClass + '">' + (task.priority || 'Med') + '</span></div>' +
            '<div class="grid grid-cols-2 gap-2 text-xs text-gray-400">' +
            '<div class="flex items-center gap-1.5"><div class="w-5 h-5 rounded-full bg-gray-700 flex items-center justify-center text-[9px] border border-gray-600 overflow-hidden flex-shrink-0">' + avatarHtml + '</div><span class="truncate">' + (task.assigned_name || 'Unassigned') + '</span></div>' +
            '<div class="text-right font-mono text-[10px]"><div class="text-gray-500 uppercase tracking-wider">Deadline</div><div class="text-white font-bold">' + getDeadlineDisplay(task) + '</div></div></div>' +
            '<div class="flex items-center justify-between pt-1 border-t border-gray-700/50 gap-2">' +
            '<span class="px-2.5 py-1 rounded-full text-xs border ' + badgeClass + '">' + task.status + '</span>' +
            '<div class="flex items-center gap-2">' + getStatusControl(task) + getEditButton(task) + getDeleteButton(task) + "</div></div></div>";
    }

    function renderAll() {
        const filteredTasks = getFilteredTasks();
        if (Number.isInteger(focusTaskId)) {
            const focusedIndex = filteredTasks.findIndex(function (task) { return Number(task.id) === focusTaskId; });
            if (focusedIndex >= 0) {
                currentPage = Math.floor(focusedIndex / itemsPerPage) + 1;
            }
        }
        const totalItems = filteredTasks.length;
        const emptyMsgTable = '<tr><td colspan="6" class="py-12 text-center text-gray-500 font-mono text-xs uppercase">No matching tasks found.</td></tr>';
        const emptyMsgCard = '<div class="glass rounded-xl border border-gray-700 p-8 text-center text-gray-500 font-mono text-xs uppercase">No matching tasks found.</div>';

        if (totalItems === 0) {
            taskListBody.html(emptyMsgTable);
            cardContainer.html(emptyMsgCard);
            paginationContainer.addClass('hidden').removeClass('flex');
            mobilePagination.addClass('hidden');
            return;
        }

        const maxPage = Math.ceil(totalItems / itemsPerPage);
        if (currentPage > maxPage) currentPage = maxPage;
        if (currentPage < 1) currentPage = 1;
        const start = (currentPage - 1) * itemsPerPage;
        const end = Math.min(start + itemsPerPage, totalItems);
        const paginated = filteredTasks.slice(start, end);

        taskListBody.empty();
        cardContainer.empty();
        paginated.forEach(task => {
            taskListBody.append(renderTaskRow(task));
            cardContainer.append(renderMobileCard(task));
        });

        if (Number.isInteger(focusTaskId)) {
            const focusedNode = document.getElementById('task-row-' + focusTaskId) || document.getElementById('task-card-' + focusTaskId);
            if (focusedNode) {
                focusedNode.classList.add('ring-2', 'ring-cyan-400', 'ring-offset-2', 'ring-offset-gray-900');
                focusedNode.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        paginationContainer.removeClass('hidden').addClass('flex');
        currentPageLabel.text(currentPage);
        pageStartItem.text(start + 1); pageEndItem.text(end); pageTotalItems.text(totalItems);
        prevPageBtn.prop('disabled', currentPage === 1); nextPageBtn.prop('disabled', currentPage === maxPage);

        mobilePagination.removeClass('hidden');
        mobilePageLabel.text(currentPage);
        mobileStart.text(start + 1); mobileEnd.text(end); mobileTotal.text(totalItems);
        mobilePrev.prop('disabled', currentPage === 1); mobileNext.prop('disabled', currentPage === maxPage);
    }

    projectSelect.on('change', function () {
        const projectId = $(this).val();
        if (projectId === 'all') {
            loadTasks('all');
            openModalBtn.addClass('hidden').removeClass('flex');
            modalProjectId.val('');
            $('#projAssigneeList').html('<div class="text-gray-500 text-xs font-mono py-2">Select a specific project to load members.</div>');
            startLiveRefresh('all');
        } else if (projectId !== '') {
            loadTasks(projectId);
            loadMembers(projectId);
            openModalBtn.removeClass('hidden').addClass('flex');
            modalProjectId.val(projectId);
            startLiveRefresh(projectId);
        } else {
            taskListBody.html('<tr><td colspan="6" class="py-12 text-center text-gray-500 font-mono text-xs uppercase">Select a project unit to activate task stream.</td></tr>');
            cardContainer.html('<div class="glass rounded-xl border border-gray-700 p-8 text-center text-gray-500 font-mono text-xs uppercase">Select a project to view tasks.</div>');
            openModalBtn.addClass('hidden').removeClass('flex');
            paginationContainer.addClass('hidden').removeClass('flex');
            mobilePagination.addClass('hidden');
            startLiveRefresh(null);
        }
    });

    function loadMembers(projectId) {
        var container = $('#projAssigneeList');
        container.html('<div class="text-gray-500 text-xs font-mono py-2">Loading...</div>');
        $.ajax({
            url: 'api/get_members.php?project_id=' + projectId,
            type: 'GET', dataType: 'json',
            success: function (response) {
                if (!response.length) { container.html('<div class="text-gray-500 text-xs font-mono py-2">No members available.</div>'); return; }
                const grouped = {};
                response.forEach(m => { const d = m.department_name || 'General'; if (!grouped[d]) grouped[d] = []; grouped[d].push(m); });
                let html = '';
                for (const [dept, members] of Object.entries(grouped)) {
                    html += '<div class="text-[10px] text-cyan-400 font-mono uppercase tracking-wider border-b border-gray-700/50 pb-1 mb-1">' + dept + '</div>';
                    members.forEach(m => {
                        const roleTag = m.role === 'Team Lead'
                            ? ' <span class="text-[10px] text-indigo-300">[Lead]</span>'
                            : (m.role === 'Department Head'
                                ? ' <span class="text-[10px] text-cyan-400">[Head]</span>'
                                : '');
                        html += '<label class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-800/50 rounded px-2 -mx-2">';
                        html += '<input type="checkbox" name="assigned_to[]" value="' + m.id + '" class="w-4 h-4 accent-cyan-500 flex-shrink-0">';
                        html += '<span class="text-white truncate">' + m.full_name + roleTag + '</span></label>';
                    });
                }
                container.html(html);
            },
            error: function () { container.html('<div class="text-red-400 text-xs">Failed to load members.</div>'); }
        });
    }

    function loadTasks(projectId, options) {
        options = options || {};
        const showLoading = options.showLoading !== false;
        const resetPage = options.resetPage !== false;
        if (showLoading) {
            taskListBody.html('<tr><td colspan="6" class="py-8 text-center text-gray-500">Loading tasks...</td></tr>');
            cardContainer.html('<div class="glass rounded-xl border border-gray-700 p-6 text-center text-gray-400 text-sm">Loading...</div>');
        }
        $.ajax({
            url: 'api/get_tasks.php',
            data: { project_id: projectId, scope: taskScope },
            type: 'GET', dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    allTasks = response.data || [];
                    if (resetPage) currentPage = 1;
                    renderAll();
                } else {
                    taskListBody.html('<tr><td colspan="6" class="py-12 text-center text-gray-500 font-mono text-xs uppercase">No project tasks found.</td></tr>');
                    cardContainer.html('<div class="glass rounded-xl border border-gray-700 p-8 text-center text-gray-500 font-mono text-xs uppercase">No project tasks found.</div>');
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

    function startLiveRefresh(projectId) {
        if (liveRefreshTimer) {
            clearInterval(liveRefreshTimer);
            liveRefreshTimer = null;
        }
        if (!projectId) return;
        liveRefreshTimer = setInterval(function () {
            loadTasks(projectId, { showLoading: false, resetPage: false });
        }, LIVE_REFRESH_MS);
    }

    openModalBtn.on('click', function () {
        modal.removeClass('hidden').addClass('flex');
        setTimeout(() => modalContent.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100'), 10);
    });

    function closeModal() {
        modalContent.removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => modal.addClass('hidden').removeClass('flex'), 300);
    }
    $('#closeModalBtn, #closeModalBtn2').on('click', closeModal);
    modal.on('click', function (e) { if ($(e.target).is(modal)) closeModal(); });

    function closeEditModal() {
        editModalContent.removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => editModal.addClass('hidden').removeClass('flex'), 300);
    }
    $('#closeEditModalBtn, #closeEditModalBtn2').on('click', closeEditModal);
    editModal.on('click', function (e) { if ($(e.target).is(editModal)) closeEditModal(); });

    createTaskForm.on('submit', function (e) {
        e.preventDefault();
        if (!modalProjectId.val()) {
            alert('Please select a specific project before creating a task.');
            return;
        }
        $.ajax({
            url: 'api/create_task.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    closeModal();
                    createTaskForm[0].reset();
                    $('#projAssigneeList input[type="checkbox"]').prop('checked', false);
                    loadTasks(modalProjectId.val(), { showLoading: false, resetPage: false });
                    if (window.TaskAssignmentNotifications && typeof window.TaskAssignmentNotifications.refreshNow === 'function') {
                        window.TaskAssignmentNotifications.refreshNow();
                    }
                } else { alert('Error: ' + response.message); }
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

    window.deleteTask = function (taskId, title) {
        if (!confirm('Delete task "' + title + '"? This cannot be undone.')) return;
        $.ajax({
            url: 'api/delete_task.php', type: 'POST', data: { task_id: taskId }, dataType: 'json',
            success: function (response) {
                if (response.success) { allTasks = allTasks.filter(t => t.id != taskId); renderAll(); }
                else { alert('Error: ' + response.message); }
            },
            error: function () { alert('An error occurred while deleting the task.'); }
        });
    };

    function fillEditAssignees(projectId, selectedIds) {
        var container = $('#editProjAssigneeList');
        container.html('<div class="text-gray-500 text-xs font-mono py-2">Loading...</div>');
        $.ajax({
            url: 'api/get_members.php?project_id=' + projectId,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (!response.length) {
                    container.html('<div class="text-gray-500 text-xs font-mono py-2">No members available.</div>');
                    return;
                }
                const grouped = {};
                response.forEach(m => { const d = m.department_name || 'General'; if (!grouped[d]) grouped[d] = []; grouped[d].push(m); });
                let html = '';
                for (const [dept, members] of Object.entries(grouped)) {
                    html += '<div class="text-[10px] text-amber-400 font-mono uppercase tracking-wider border-b border-gray-700/50 pb-1 mb-1">' + dept + '</div>';
                    members.forEach(m => {
                        const checked = selectedIds.includes(Number(m.id)) ? 'checked' : '';
                        const roleTag = m.role === 'Team Lead'
                            ? ' <span class="text-[10px] text-indigo-300">[Lead]</span>'
                            : '';
                        html += '<label class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-800/50 rounded px-2 -mx-2">';
                        html += '<input type="checkbox" name="assigned_to[]" value="' + m.id + '" ' + checked + ' class="w-4 h-4 accent-amber-500 flex-shrink-0">';
                        html += '<span class="text-white truncate">' + m.full_name + roleTag + '</span></label>';
                    });
                }
                container.html(html);
            },
            error: function () {
                container.html('<div class="text-red-400 text-xs">Failed to load members.</div>');
            }
        });
    }

    window.editTask = function (taskId) {
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
                $('#editTaskId').val(task.id);
                $('#editTaskProjectId').val(task.project_id || '');
                $('#editTaskTitle').val(task.title || '');
                $('#editTaskDescription').val(task.description || '');
                $('#editTaskDueDate').val(task.due_date ? String(task.due_date).split(' ')[0] : '');
                $('#editTaskSpecificTime').val(task.specific_time ? String(task.specific_time).substring(0, 5) : '');
                editTaskForm.find('input[name="priority"][value="' + (task.priority || 'Medium') + '"]').prop('checked', true);

                const selectedIds = Array.isArray(task.assigned_user_ids) ? task.assigned_user_ids.map(Number) : [];
                fillEditAssignees(task.project_id || 0, selectedIds);

                editModal.removeClass('hidden').addClass('flex');
                setTimeout(() => editModalContent.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100'), 10);
            },
            error: function () {
                alert('Unable to load task details.');
            }
        });
    };

    editTaskForm.on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: 'api/update_task.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    closeEditModal();
                    loadTasks(projectSelect.val(), { showLoading: false, resetPage: false });
                } else {
                    alert('Error: ' + (response.message || 'Failed to update task.'));
                }
            },
            error: function () {
                alert('Failed to update task.');
            }
        });
    });

    function updateTaskStatus(taskId, status) {
        $.ajax({
            url: 'api/update_task_status.php', type: 'POST', data: { task_id: taskId, status: status }, dataType: 'json',
            success: function (response) {
                if (response.status === 'success') { loadTasks(projectSelect.val(), { showLoading: false, resetPage: false }); }
                else { alert('Failed to update status: ' + response.message); loadTasks(projectSelect.val(), { showLoading: false, resetPage: false }); }
            }
        });
    }

    $(document).on("change", ".task-status-select", function () {
        const taskId = $(this).data("task-id");
        const newStatus = $(this).val();
        if (taskId) updateTaskStatus(taskId, newStatus);
    });
});
