<?php
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
check_login();
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
/* Mobile card view for task list */
.task-card-mobile { display: none; }
@media (max-width: 767px) {
    .task-table-desktop { display: none; }
    .task-card-mobile { display: block; }
}
/* Scrollable modal */
#createDirectTaskModal .modal-inner {
    max-height: 92vh;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}
/* Scope filter: My Tasks / Team Tasks */
.scope-toggle { color: #9ca3af; }
.scope-toggle:hover { color: #e5e7eb; }
.scope-toggle.active {
    background: linear-gradient(135deg, rgb(147 51 234), rgb(79 70 229));
    color: #fff;
    border-color: rgba(168, 85, 247, 0.5);
}
</style>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">

    <!-- Page Header -->
    <header class="h-16 flex items-center justify-between gap-2 px-3 sm:px-4 md:px-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex min-w-0 flex-1 items-center space-x-2 sm:space-x-3 md:space-x-4">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <div class="min-w-0">
                <h1 class="truncate text-base sm:text-lg md:text-xl leading-tight font-bold text-white uppercase font-tech tracking-wide sm:tracking-wider">Direct Tasks</h1>
                <p class="text-[10px] text-purple-400 font-mono hidden sm:block">Directly assigned tasks — no project required</p>
            </div>
        </div>
        <div id="tan-header-notification-anchor" class="flex shrink-0 items-center space-x-2 md:space-x-4">
            <?php if (in_array($_SESSION['role'], ['Super Admin'])): ?>
            <button id="openDirectTaskModalBtn" class="flex items-center bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white px-3 md:px-5 py-2 rounded-lg shadow-lg font-bold text-xs md:text-sm transition-all">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span class="hidden xs:inline">NEW</span> TASK
            </button>
            <?php
endif; ?>
            <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-gradient-to-tr from-purple-500 to-indigo-500 p-[2px]">
                <div class="w-full h-full rounded-full bg-gray-900 relative overflow-hidden">
                    <img src="<?php echo htmlspecialchars(get_session_avatar_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="User" class="w-full h-full rounded-full object-cover">
                </div>
            </div>
            <div class="text-left hidden sm:block">
                <p class="text-xs md:text-sm font-bold text-white font-tech tracking-wide uppercase leading-tight"><?php echo $_SESSION['full_name']; ?></p>
                <p class="text-[9px] md:text-[10px] text-purple-400 font-mono leading-tight"><?php echo $_SESSION['role']; ?></p>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex-1 overflow-x-hidden overflow-y-auto p-3 md:p-6">

        <!-- Scope filter: Own Tasks / Team Tasks -->
        <div id="directTaskScopeFilter" class="flex items-center gap-2 mb-4 <?php echo $_SESSION['role'] === 'Team Member' ? 'hidden' : ''; ?>">
            <span class="text-xs font-mono text-gray-400 uppercase tracking-wider">View:</span>
            <div class="inline-flex rounded-lg border border-purple-500/30 bg-gray-800/80 p-0.5" role="group">
                <button type="button" id="scopeFilterMine" class="scope-toggle px-3 py-1.5 text-xs font-mono rounded-md transition-all border border-transparent" data-scope="mine">Own Tasks</button>
                <button type="button" id="scopeFilterTeam" class="scope-toggle px-3 py-1.5 text-xs font-mono rounded-md transition-all border border-transparent" data-scope="team">Team Tasks</button>
            </div>
        </div>
        <div id="directTaskDeptFilter" class="hidden"></div>

        <!-- Filters Row -->
        <div class="flex flex-col sm:flex-row gap-3 mb-5">
            <input type="text" id="standaloneSearchInput" placeholder="Search tasks..." class="flex-1 bg-gray-800 border border-purple-500/30 rounded-lg px-4 py-2 text-white text-sm focus:outline-none focus:border-purple-500 transition-colors placeholder-gray-500">
            <div class="flex gap-3">
                <select id="standaloneStatusFilter" class="flex-1 sm:flex-none sm:w-36 bg-gray-800 border border-purple-500/30 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500 transition-colors">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Review">Review</option>
                    <option value="Completed">Completed</option>
                    <option value="Missed">Missed</option>
                </select>
                <select id="standalonePriorityFilter" class="flex-1 sm:flex-none sm:w-36 bg-gray-800 border border-purple-500/30 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500 transition-colors">
                    <option value="">All Priorities</option>
                    <option value="Urgent">Urgent</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>
        </div>

        <!-- ===== DESKTOP TABLE VIEW ===== -->
        <div class="task-table-desktop glass rounded-xl border border-purple-500/20 overflow-hidden mb-5">
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[700px]">
                    <thead class="bg-gray-800/80 text-gray-400 text-xs font-mono uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-5 font-medium">Task Title</th>
                            <th class="px-5 font-medium">Assignee</th>
                            <th class="px-5 font-medium">Timeframe</th>
                            <th class="px-5 font-medium">Priority</th>
                            <th class="px-5 font-medium">Status</th>
                            <th class="py-3 px-5 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="directTaskListBody" class="divide-y divide-gray-700/50 text-gray-300">
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-500 font-mono text-xs uppercase">Loading direct tasks...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div id="paginationContainer" class="hidden border-t border-gray-700 bg-gray-800/50 p-3 md:p-4 items-center justify-between text-sm text-gray-400">
                <div class="font-mono text-xs">Showing <span id="pageStartItem" class="text-white">0</span>–<span id="pageEndItem" class="text-white">0</span> of <span id="pageTotalItems" class="text-white">0</span></div>
                <div class="flex items-center space-x-2">
                    <button id="prevPageBtn" class="px-3 py-1.5 rounded bg-gray-700 hover:bg-gray-600 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-xs font-mono uppercase border border-gray-600">Prev</button>
                    <span id="currentPageLabel" class="text-white font-bold px-2">1</span>
                    <button id="nextPageBtn" class="px-3 py-1.5 rounded bg-gray-700 hover:bg-gray-600 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-xs font-mono uppercase border border-gray-600">Next</button>
                </div>
            </div>
        </div>

        <!-- ===== MOBILE CARD VIEW ===== -->
        <div class="task-card-mobile mb-5">
            <div id="directTaskCardContainer" class="space-y-3">
                <div class="glass rounded-xl border border-purple-500/20 p-4 text-center text-gray-500 font-mono text-xs uppercase">Loading direct tasks...</div>
            </div>
            <!-- Mobile Pagination -->
            <div id="mobilePaginationContainer" class="hidden mt-4 flex items-center justify-between text-sm text-gray-400">
                <div class="font-mono text-xs">Showing <span id="mobilePageStartItem" class="text-white">0</span>–<span id="mobilePageEndItem" class="text-white">0</span> of <span id="mobilePageTotalItems" class="text-white">0</span></div>
                <div class="flex items-center space-x-2">
                    <button id="mobilePrevPageBtn" class="px-3 py-1.5 rounded bg-gray-700 hover:bg-gray-600 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-xs font-mono uppercase border border-gray-600">Prev</button>
                    <span id="mobileCurrentPageLabel" class="text-white font-bold px-2">1</span>
                    <button id="mobileNextPageBtn" class="px-3 py-1.5 rounded bg-gray-700 hover:bg-gray-600 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-xs font-mono uppercase border border-gray-600">Next</button>
                </div>
            </div>
        </div>

    </div><!-- end main content -->

</main>

<!-- ====================================================== -->
<!-- Modal: Create Direct Task Form                         -->
<!-- ====================================================== -->
<div id="createDirectTaskModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-3 md:p-4 flex">
    <div class="modal-inner bg-gray-800 rounded-xl border border-purple-500/30 w-full max-w-lg shadow-2xl shadow-purple-900/20 transform transition-all scale-95 opacity-0" id="directModalContent">

        <!-- Modal Header -->
        <div class="flex items-center justify-between p-4 md:p-5 border-b border-gray-700 sticky top-0 bg-gray-800 rounded-t-xl z-10">
            <div class="flex items-center space-x-3">
                <div class="w-1.5 h-7 bg-gradient-to-b from-purple-500 to-indigo-500 rounded-full"></div>
                <div>
                    <h3 class="text-base md:text-lg font-bold text-white font-tech tracking-wide">NEW DIRECT TASK</h3>
                    <p class="text-[10px] text-purple-400 font-mono">Assign directly to team members</p>
                </div>
            </div>
            <button type="button" id="closeDirectModalBtn" class="p-2 rounded-lg bg-gray-700 text-gray-400 hover:text-white transition-colors flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="createDirectTaskForm" class="p-4 md:p-5 space-y-4">
            <input type="hidden" name="project_id" value="0">

            <!-- Title -->
            <div>
                <label class="block text-xs font-mono text-purple-400 uppercase mb-1.5">Task Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required placeholder="What needs to be done?" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 transition-colors placeholder-gray-600">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-mono text-purple-400 uppercase mb-1.5">Description</label>
                <textarea name="description" rows="2" placeholder="Add more detail (optional)..." class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 transition-colors resize-none placeholder-gray-600"></textarea>
            </div>

            <!-- Assignees -->
            <div>
                <label class="block text-xs font-mono text-purple-400 uppercase mb-1.5">Assign To <span class="text-red-500">*</span></label>
                <div id="directAssigneeList" class="max-h-44 overflow-y-auto bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 space-y-0.5">
                    <p class="text-gray-500 italic text-xs py-2">Loading members...</p>
                </div>
            </div>

            <!-- Timeframe -->
            <div class="bg-gray-900/60 border border-gray-700 rounded-lg p-3 md:p-4 space-y-3">
                <p class="text-[10px] font-mono text-purple-400 uppercase tracking-widest">Timeframe</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">Start Date <span class="text-red-500">*</span></label>
                        <input type="date" name="start_date" id="stdStartDate" required class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">End Date <span class="text-red-500">*</span></label>
                        <input type="date" name="end_date" id="stdEndDate" required class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 transition-colors">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-400 mb-1.5">Specific Time <span class="text-gray-600 italic">(optional)</span></label>
                        <input type="time" name="specific_time" id="stdSpecificTime" class="w-full sm:w-1/2 bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 transition-colors">
                    </div>
                </div>
            </div>

            <!-- Priority -->
            <div>
                <label class="block text-xs font-mono text-purple-400 uppercase mb-1.5">Priority</label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="cursor-pointer"><input type="radio" name="priority" value="Low" class="hidden peer"><span class="block text-center text-xs font-bold py-2 rounded-lg border border-gray-700 text-gray-500 peer-checked:border-green-500 peer-checked:text-green-400 peer-checked:bg-green-500/10 transition-all">LOW</span></label>
                    <label class="cursor-pointer"><input type="radio" name="priority" value="Medium" checked class="hidden peer"><span class="block text-center text-xs font-bold py-2 rounded-lg border border-gray-700 text-gray-500 peer-checked:border-yellow-500 peer-checked:text-yellow-400 peer-checked:bg-yellow-500/10 transition-all">MED</span></label>
                    <label class="cursor-pointer"><input type="radio" name="priority" value="High" class="hidden peer"><span class="block text-center text-xs font-bold py-2 rounded-lg border border-gray-700 text-gray-500 peer-checked:border-orange-500 peer-checked:text-orange-400 peer-checked:bg-orange-500/10 transition-all">HIGH</span></label>
                    <label class="cursor-pointer"><input type="radio" name="priority" value="Urgent" class="hidden peer"><span class="block text-center text-xs font-bold py-2 rounded-lg border border-gray-700 text-gray-500 peer-checked:border-red-500 peer-checked:text-red-400 peer-checked:bg-red-500/10 transition-all">URGENT</span></label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-1">
                <button type="button" id="closeDirectModalBtn2" class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold text-gray-400 hover:text-white transition-colors uppercase tracking-wider border border-gray-700 rounded-lg hover:bg-gray-700">Cancel</button>
                <button type="submit" class="w-full sm:w-auto bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white px-5 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all shadow-lg shadow-purple-900/30">Create Direct Task</button>
            </div>
        </form>
    </div>
</div>

<div id="editDirectTaskModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-3 md:p-4 flex">
    <div class="modal-inner bg-gray-800 rounded-xl border border-purple-500/30 w-full max-w-lg shadow-2xl shadow-purple-900/20 transform transition-all scale-95 opacity-0" id="editDirectModalContent">
        <div class="flex items-center justify-between p-4 md:p-5 border-b border-gray-700 sticky top-0 bg-gray-800 rounded-t-xl z-10">
            <div class="flex items-center space-x-3">
                <div class="w-1.5 h-7 bg-gradient-to-b from-amber-500 to-indigo-500 rounded-full"></div>
                <div>
                    <h3 class="text-base md:text-lg font-bold text-white font-tech tracking-wide">EDIT DIRECT TASK</h3>
                    <p class="text-[10px] text-amber-400 font-mono">Update direct task details</p>
                </div>
            </div>
            <button type="button" id="closeEditDirectModalBtn" class="p-2 rounded-lg bg-gray-700 text-gray-400 hover:text-white transition-colors flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="editDirectTaskForm" class="p-4 md:p-5 space-y-4">
            <input type="hidden" name="task_id" id="editDirectTaskId">
            <input type="hidden" name="project_id" value="0">
            <div>
                <label class="block text-xs font-mono text-amber-400 uppercase mb-1.5">Task Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="editDirectTaskTitle" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-mono text-amber-400 uppercase mb-1.5">Description</label>
                <textarea name="description" id="editDirectTaskDescription" rows="2" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors resize-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-mono text-amber-400 uppercase mb-1.5">Assign To <span class="text-red-500">*</span></label>
                <div id="editDirectAssigneeList" class="max-h-44 overflow-y-auto bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 space-y-0.5"></div>
            </div>
            <div class="bg-gray-900/60 border border-gray-700 rounded-lg p-3 md:p-4 space-y-3">
                <p class="text-[10px] font-mono text-amber-400 uppercase tracking-widest">Timeframe</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">Start Date <span class="text-red-500">*</span></label>
                        <input type="date" name="start_date" id="editDirectStartDate" required class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">End Date <span class="text-red-500">*</span></label>
                        <input type="date" name="end_date" id="editDirectEndDate" required class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-400 mb-1.5">Specific Time <span class="text-gray-600 italic">(optional)</span></label>
                        <input type="time" name="specific_time" id="editDirectSpecificTime" class="w-full sm:w-1/2 bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors">
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-mono text-amber-400 uppercase mb-1.5">Priority</label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="cursor-pointer"><input type="radio" name="priority" value="Low" class="hidden peer"><span class="block text-center text-xs font-bold py-2 rounded-lg border border-gray-700 text-gray-500 peer-checked:border-green-500 peer-checked:text-green-400 peer-checked:bg-green-500/10 transition-all">LOW</span></label>
                    <label class="cursor-pointer"><input type="radio" name="priority" value="Medium" class="hidden peer"><span class="block text-center text-xs font-bold py-2 rounded-lg border border-gray-700 text-gray-500 peer-checked:border-yellow-500 peer-checked:text-yellow-400 peer-checked:bg-yellow-500/10 transition-all">MED</span></label>
                    <label class="cursor-pointer"><input type="radio" name="priority" value="High" class="hidden peer"><span class="block text-center text-xs font-bold py-2 rounded-lg border border-gray-700 text-gray-500 peer-checked:border-orange-500 peer-checked:text-orange-400 peer-checked:bg-orange-500/10 transition-all">HIGH</span></label>
                    <label class="cursor-pointer"><input type="radio" name="priority" value="Urgent" class="hidden peer"><span class="block text-center text-xs font-bold py-2 rounded-lg border border-gray-700 text-gray-500 peer-checked:border-red-500 peer-checked:text-red-400 peer-checked:bg-red-500/10 transition-all">URGENT</span></label>
                </div>
            </div>
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-1">
                <button type="button" id="closeEditDirectModalBtn2" class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold text-gray-400 hover:text-white transition-colors uppercase tracking-wider border border-gray-700 rounded-lg hover:bg-gray-700">Cancel</button>
                <button type="submit" class="w-full sm:w-auto bg-gradient-to-r from-amber-600 to-indigo-600 hover:from-amber-500 hover:to-indigo-500 text-white px-5 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all shadow-lg">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    window.userRole = <?php echo json_encode($_SESSION['role']); ?>;
    window.userName = <?php echo json_encode($_SESSION['full_name']); ?>;
</script>
<script src="assets/js/direct_tasks.js?v=<?php echo time(); ?>"></script>

<?php include 'includes/footer.php'; ?>
