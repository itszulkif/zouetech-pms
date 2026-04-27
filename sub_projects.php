<?php
// sub_projects.php - Sub-Projects Management
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
require_role(['Super Admin', 'Department Head']);

$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

if ($parent_id <= 0) {
    header("Location: projects.php");
    exit;
}

// Get parent project details
$database = new Database();
$conn = $database->connect();
$stmt = $conn->prepare("SELECT id, name, department_id, project_type FROM projects WHERE id = ?");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$parent_project = $result->fetch_assoc();

$is_super = ($_SESSION['role'] === 'Super Admin');
$user_dept = $_SESSION['department_id'] ?? 0;

if (!$parent_project || $parent_project['project_type'] !== 'Major') {
    header("Location: projects.php");
    exit;
}

// Data Isolation: Dept Head can only see projects in their department
// [REMOVED] The legacy check `if (!$is_super && $parent_project['department_id'] != $user_dept)` 
// was deleted because Major Projects now have NULL department_id, and sub-project filtering 
// is correctly handled securely inside `api/get_sub_projects.php`.

// Fetch all departments (only Super Admin needs the list; Dept Head sees a single checkbox)
$all_departments = [];
if ($is_super) {
    $dept_result = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
    while ($d = $dept_result->fetch_assoc()) {
        $all_departments[] = $d;
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">
    <!-- Header -->
    <header class="h-16 flex items-center justify-between px-4 md:px-6 border-b border-gray-800 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex items-center space-x-3 overflow-hidden">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <div class="overflow-hidden">
                <a href="projects.php" class="text-gray-500 hover:text-gray-300 text-[10px] uppercase font-mono mb-0.5 block truncate">← Back</a>
                <h1 class="text-sm md:text-lg font-bold text-white font-tech tracking-wider uppercase truncate"><?php echo htmlspecialchars($parent_project['name']); ?></h1>
            </div>
        </div>
        <div class="flex items-center space-x-3 md:space-x-6 flex-shrink-0">
            <?php if (in_array($_SESSION['role'], ['Super Admin', 'Department Head'])): ?>
            <button onclick="openModal('modal-add-sub')" class="bg-indigo-600 hover:bg-indigo-500 text-white p-2 md:px-4 md:py-2 rounded flex items-center font-mono text-xs transition-all shadow-lg hover:shadow-indigo-500/30">
                <svg class="w-4 h-4 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span class="hidden md:inline">ADD UNIT</span>
            </button>
            <?php
endif; ?>

            <div class="flex items-center space-x-3 md:space-x-4 border-l border-gray-800 pl-3 md:pl-6">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-white font-tech tracking-wide uppercase leading-tight truncate max-w-[100px]"><?php echo $_SESSION['full_name']; ?></p>
                    <p class="text-[9px] text-cyan-400 font-mono leading-tight uppercase"><?php echo $_SESSION['role']; ?></p>
                </div>
                <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-gradient-to-tr from-cyan-500 to-indigo-500 p-[2px] flex-shrink-0">
                    <div class="w-full h-full rounded-full bg-gray-900 border-2 border-transparent relative overflow-hidden">
                        <img src="<?php echo htmlspecialchars(get_session_avatar_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="User" class="rounded-full w-full h-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <div class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 scroll-smooth">
        <div class="glass rounded-xl border border-gray-700/50 overflow-hidden">
            <!-- Desktop View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-800/50 text-[10px] text-gray-400 uppercase font-mono">
                        <tr>
                            <th class="px-6 py-3">Unit Name</th>
                            <th class="px-6 py-3">Departments</th>
                            <th class="px-6 py-3">Progress</th>
                            <th class="px-6 py-3">Tasks</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sub-projects-list-desktop" class="text-sm text-gray-300 divide-y divide-gray-800">
                        <tr><td colspan="6" class="px-6 py-4 text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div id="sub-projects-list-mobile" class="md:hidden divide-y divide-gray-800">
                <div class="px-6 py-4 text-center text-gray-500">Loading units...</div>
            </div>
        </div>
    </div>
</main>

<!-- Add Sub-Project Modal -->
<div id="modal-add-sub" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-2xl p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeModal('modal-add-sub')" class="absolute top-4 right-4 text-gray-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-xl font-bold text-white font-tech mb-4">Add Sub-Project / Unit</h3>
        <form id="form-add-sub" class="space-y-4">
            <input type="hidden" name="parent_project_id" value="<?php echo $parent_id; ?>">
            <input type="hidden" name="project_type" value="Sub">

            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Unit Name *</label>
                <input type="text" name="name" required placeholder="e.g., School #01, Location A" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
            </div>

            <!-- Department Selection -->
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-2">
                    Assign to Department(s) *
                    <?php if ($is_super): ?>
                    <span class="text-gray-500 normal-case">(select one or more)</span>
                    <?php
endif; ?>
                </label>

                <?php if ($is_super): ?>
                <!-- Super Admin: checkboxes for all departments -->
                <div id="add-dept-checkboxes" class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto bg-gray-800 border border-gray-700 rounded p-3">
                    <?php foreach ($all_departments as $dept): ?>
                    <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer hover:text-white">
                        <input type="checkbox" name="department_ids[]" value="<?php echo $dept['id']; ?>"
                               class="add-dept-cb w-4 h-4 accent-cyan-500 rounded">
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </label>
                    <?php
    endforeach; ?>
                </div>
                <p id="add-dept-error" class="text-red-400 text-xs mt-1 hidden">Please select at least one department.</p>

                <?php
else: ?>
                <!-- Dept Head: single locked checkbox -->
                <div class="bg-gray-800 border border-gray-700 rounded p-3">
                    <label class="flex items-center gap-2 text-sm text-gray-300">
                        <input type="checkbox" name="department_ids[]" value="<?php echo $user_dept; ?>" checked disabled
                               class="w-4 h-4 accent-cyan-500">
                        <?php echo htmlspecialchars($_SESSION['department_name'] ?? 'Your Department'); ?>
                        <span class="text-[10px] text-gray-500">(locked)</span>
                    </label>
                </div>
                <!-- Ensure the value is still submitted when disabled -->
                <input type="hidden" name="department_ids[]" value="<?php echo $user_dept; ?>">
                <?php
endif; ?>
            </div>

            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Description</label>
                <textarea name="kpi_target" rows="2" placeholder="Brief description of this unit" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Status</label>
                    <select name="status" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                        <option value="Planned">Planned</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Start Date *</label>
                    <input type="date" name="start_date" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">End Date *</label>
                    <input type="date" name="end_date" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                </div>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 rounded mt-2 transition-all">Add Sub-Project</button>
        </form>
    </div>
</div>

<!-- Edit Sub-Project Modal (Super Admin only) -->
<div id="modal-edit-sub" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-2xl p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeModal('modal-edit-sub')" class="absolute top-4 right-4 text-gray-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-xl font-bold text-white font-tech mb-4">Edit Sub-Project</h3>
        <form id="form-edit-sub" class="space-y-4">
            <input type="hidden" name="id" id="edit-sub-id">
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Unit Name *</label>
                <input type="text" name="name" id="edit-sub-name" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
            </div>

            <!-- Edit Department Selection (Super Admin only) -->
            <?php if ($is_super): ?>
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-2">
                    Assign to Department(s) * <span class="text-gray-500 normal-case">(select one or more)</span>
                </label>
                <div id="edit-dept-checkboxes" class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto bg-gray-800 border border-gray-700 rounded p-3">
                    <?php foreach ($all_departments as $dept): ?>
                    <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer hover:text-white">
                        <input type="checkbox" name="department_ids[]" value="<?php echo $dept['id']; ?>"
                               class="edit-dept-cb w-4 h-4 accent-cyan-500 rounded"
                               data-dept-id="<?php echo $dept['id']; ?>">
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </label>
                    <?php
    endforeach; ?>
                </div>
                <p id="edit-dept-error" class="text-red-400 text-xs mt-1 hidden">Please select at least one department.</p>
            </div>
            <?php
endif; ?>

            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Description</label>
                <textarea name="kpi_target" id="edit-sub-desc" rows="2" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Status</label>
                    <select name="status" id="edit-sub-status" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                        <option value="Planned">Planned</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Start Date *</label>
                    <input type="date" name="start_date" id="edit-sub-start" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">End Date *</label>
                    <input type="date" name="end_date" id="edit-sub-end" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                </div>
            </div>
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 rounded mt-2 transition-all">Update Sub-Project</button>
        </form>
    </div>
</div>

<script>
    const parentId  = <?php echo $parent_id; ?>;
    const userRole  = '<?php echo $_SESSION['role']; ?>';
    const isSuperAdmin = userRole === 'Super Admin';
    let allSubProjects = [];

    // ── Modal Helpers ──────────────────────────────────────────────────────────
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    // ── Load Data ──────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', loadSubProjects);

    function loadSubProjects() {
        fetch(`api/get_sub_projects.php?parent_id=${parentId}`)
            .then(res => res.json())
            .then(data => {
                allSubProjects = data;
                const tbodyDesktop = document.getElementById('sub-projects-list-desktop');
                const listMobile  = document.getElementById('sub-projects-list-mobile');
                
                tbodyDesktop.innerHTML = '';
                listMobile.innerHTML  = '';

                if (data.length === 0) {
                    const emptyMsg = 'No Sub-Projects found. Add your first unit!';
                    tbodyDesktop.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">${emptyMsg}</td></tr>`;
                    listMobile.innerHTML  = `<div class="px-6 py-8 text-center text-gray-500 font-mono text-sm">${emptyMsg}</div>`;
                    return;
                }

                data.forEach(sub => {
                    const statusClass = sub.status === 'Completed' ? 'bg-green-500/10 border-green-500 text-green-400' :
                                       sub.status === 'In Progress' ? 'bg-blue-500/10 border-blue-500 text-blue-400' :
                                       sub.status === 'On Hold'     ? 'bg-red-500/10 border-red-500 text-red-400' :
                                       'bg-gray-700/50 border-gray-600 text-gray-400';

                    const progress      = sub.progress_percentage || 0;
                    const progressColor = progress >= 75 ? 'bg-green-500' : progress >= 50 ? 'bg-yellow-500' : progress >= 25 ? 'bg-orange-500' : 'bg-red-500';

                    // Departments badges
                    const depts = (sub.departments && sub.departments.length)
                        ? sub.departments.map(d => `<span class="px-1.5 py-0.5 bg-indigo-500/10 border border-indigo-500/40 text-indigo-300 rounded text-[10px] font-mono">${d}</span>`).join(' ')
                        : '<span class="text-gray-500 text-xs">—</span>';

                    // Prepare Action Buttons
                    const actionButtons = `
                        <a href="tasks.php?project_id=${sub.id}" class="text-cyan-400 hover:text-cyan-300 transition-colors text-xs font-mono" title="Manage Tasks">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Tasks
                        </a>
                        ${isSuperAdmin || userRole === 'Department Head' ? `
                        <button onclick="editSubProject(${sub.id})" class="text-purple-400 hover:text-purple-300 transition-colors" title="Edit">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        ` : ''}
                        ${isSuperAdmin ? `
                        <button onclick="deleteSubProject(${sub.id}, '${sub.name}')" class="text-red-400 hover:text-red-300 transition-colors" title="Delete">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                        ` : ''}
                    `;

                    // Desktop Row
                    tbodyDesktop.innerHTML += `
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-white">${sub.name}</div>
                                <div class="text-xs text-gray-500 mt-1">${sub.kpi_target || 'No description'}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">${depts}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-gray-700 rounded-full overflow-hidden w-32">
                                        <div class="${progressColor} h-full transition-all" style="width: ${progress}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400 font-mono">${progress}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-400 font-mono">
                                ${sub.completed_tasks}/${sub.total_tasks} completed
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded border ${statusClass}">${sub.status}</span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                ${actionButtons}
                            </td>
                        </tr>
                    `;

                    // Mobile Card
                    listMobile.innerHTML += `
                        <div class="px-4 py-4 space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-white font-bold truncate">${sub.name}</h4>
                                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">${sub.kpi_target || 'No description'}</p>
                                </div>
                                <span class="px-2 py-1 text-[10px] rounded border flex-shrink-0 ${statusClass}">${sub.status}</span>
                            </div>
                            
                            <div class="flex flex-wrap gap-1">
                                ${depts}
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-[10px] font-mono text-gray-400 uppercase tracking-tighter">
                                    <span>Progress</span>
                                    <span>${progress}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                    <div class="${progressColor} h-full transition-all" style="width: ${progress}%"></div>
                                </div>
                                <div class="text-[10px] text-gray-500 font-mono">
                                    ${sub.completed_tasks}/${sub.total_tasks} tasks done
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-4 pt-2 border-t border-gray-800/50">
                                ${actionButtons}
                            </div>
                        </div>
                    `;
                });
            });
    }

    // ── Edit ───────────────────────────────────────────────────────────────────
    function editSubProject(id) {
        const sub = allSubProjects.find(s => s.id == id);
        if (!sub) return;

        document.getElementById('edit-sub-id').value     = sub.id;
        document.getElementById('edit-sub-name').value   = sub.name;
        document.getElementById('edit-sub-desc').value   = sub.kpi_target || '';
        document.getElementById('edit-sub-status').value = sub.status;
        document.getElementById('edit-sub-start').value  = sub.start_date || '';
        document.getElementById('edit-sub-end').value    = sub.end_date   || '';

        // Pre-check the currently assigned departments
        if (isSuperAdmin) {
            const assignedIds = (sub.departments_raw || []).map(Number);
            document.querySelectorAll('.edit-dept-cb').forEach(cb => {
                cb.checked = assignedIds.includes(Number(cb.value));
            });
        }

        openModal('modal-edit-sub');
    }

    // ── Delete ─────────────────────────────────────────────────────────────────
    function deleteSubProject(id, name) {
        if (!confirm(`Delete Sub-Project "${name}"? This will also delete all associated tasks.`)) return;
        fetch('api/delete_project.php', { method: 'POST', body: JSON.stringify({ id }) })
            .then(res => res.json())
            .then(res => { res.success ? loadSubProjects() : alert(res.message); });
    }

    // ── Helpers ────────────────────────────────────────────────────────────────
    function getCheckedDeptIds(selector) {
        return [...document.querySelectorAll(selector)]
            .filter(cb => cb.checked)
            .map(cb => parseInt(cb.value));
    }

    // ── Add Form Submit ────────────────────────────────────────────────────────
    document.getElementById('form-add-sub').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd   = new FormData(e.target);
        const data = Object.fromEntries(fd);

        // Build department_ids array
        const deptIds = isSuperAdmin
            ? getCheckedDeptIds('.add-dept-cb')
            : fd.getAll('department_ids[]').map(Number);

        if (deptIds.length === 0) {
            document.getElementById('add-dept-error').classList.remove('hidden');
            return;
        }
        document.getElementById('add-dept-error')?.classList.add('hidden');

        data.department_ids = deptIds;
        delete data['department_ids[]'];

        fetch('api/create_project.php', { method: 'POST', body: JSON.stringify(data) })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    closeModal('modal-add-sub');
                    e.target.reset();
                    loadSubProjects();
                } else {
                    alert(res.message);
                }
            });
    });

    // ── Edit Form Submit ───────────────────────────────────────────────────────
    document.getElementById('form-edit-sub').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd   = new FormData(e.target);
        const data = Object.fromEntries(fd);

        const deptIds = isSuperAdmin
            ? getCheckedDeptIds('.edit-dept-cb')
            : [<?php echo $user_dept; ?>];

        if (deptIds.length === 0) {
            document.getElementById('edit-dept-error')?.classList.remove('hidden');
            return;
        }
        document.getElementById('edit-dept-error')?.classList.add('hidden');

        data.department_ids    = deptIds;
        data.department_id     = deptIds[0];
        data.parent_project_id = parentId;
        data.project_type      = 'Sub';
        delete data['department_ids[]'];

        fetch('api/update_project.php', { method: 'POST', body: JSON.stringify(data) })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    closeModal('modal-edit-sub');
                    loadSubProjects();
                } else {
                    alert(res.message);
                }
            });
    });
</script>
<?php include 'includes/footer.php'; ?>
