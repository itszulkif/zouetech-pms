<?php
// projects.php - Major Projects (direct task containers)
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
require_role(['Super Admin']);
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<style>
/* Mobile card view for projects */
.project-card-mobile { display: none; }
@media (max-width: 767px) {
    .project-table-desktop { display: none; }
    .project-card-mobile { display: block; }
}
/* Modal adjustment for mobile */
#modal-add-project .modal-content {
    max-height: 90vh;
    overflow-y: auto;
}
</style>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">
    <!-- Header -->
    <header class="min-h-16 py-2 flex items-center justify-between gap-2 px-3 sm:px-4 md:px-6 border-b border-gray-800 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex items-center space-x-2 sm:space-x-3 md:space-x-4 min-w-0">
            <!-- Mobile Toggle -->
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <h1 class="text-sm sm:text-base md:text-xl font-bold text-white font-tech tracking-wide md:tracking-wider uppercase truncate">MAJOR PROJECTS</h1>
        </div>
        <div class="flex items-center gap-2 md:gap-6 flex-shrink-0">
            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
            <button onclick="openModal('modal-add-project')" class="bg-cyan-600 hover:bg-cyan-500 text-white px-2.5 sm:px-3 md:px-4 py-2 rounded flex items-center font-mono text-[10px] sm:text-xs md:text-sm transition-all shadow-lg hover:shadow-cyan-500/30">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span class="hidden sm:inline">CREATE MAJOR PROJECT</span>
                <span class="sm:hidden">NEW</span>
            </button>
            <?php
endif; ?>

            <div class="flex items-center space-x-2 md:space-x-4 border-l border-gray-800 pl-2.5 md:pl-6">
                <div class="text-right hidden sm:block text-sm">
                    <p class="font-bold text-white font-tech tracking-wide uppercase leading-tight"><?php echo $_SESSION['full_name']; ?></p>
                    <p class="text-[10px] text-cyan-400 font-mono leading-tight"><?php echo $_SESSION['role']; ?></p>
                </div>
                <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-gradient-to-tr from-cyan-500 to-indigo-500 p-[2px]">
                    <div class="w-full h-full rounded-full bg-gray-900 border-2 border-transparent relative overflow-hidden">
                        <img src="<?php echo htmlspecialchars(get_session_avatar_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="User" class="rounded-full w-full h-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <div class="flex-1 overflow-x-hidden overflow-y-auto p-3 md:p-6 scroll-smooth">
        
        <!-- Desktop Table View -->
        <div class="project-table-desktop glass rounded-xl border border-gray-700/50 overflow-hidden mb-5">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead class="bg-gray-800/50 text-xs text-gray-400 uppercase font-mono">
                        <tr>
                            <th class="px-6 py-3">Project Name</th>
                            <th class="px-6 py-3">Tasks</th>
                            <th class="px-6 py-3">Progress</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="projects-list" class="text-sm text-gray-300 divide-y divide-gray-800">
                        <tr><td colspan="5" class="px-6 py-4 text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div id="projects-mobile-list" class="project-card-mobile space-y-4 mb-5">
            <div class="glass rounded-xl border border-gray-700/50 p-6 text-center text-gray-500 font-mono text-xs uppercase">Loading major projects...</div>
        </div>

    </div>
</main>

<!-- Add Major Project Modal -->
<div id="modal-add-project" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-2xl p-4 md:p-6 shadow-2xl relative modal-content">
        <button onclick="closeModal('modal-add-project')" class="absolute top-4 right-4 text-gray-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-xl font-bold text-white font-tech mb-4">Create Major Project</h3>
        <form id="form-add-project" class="space-y-4">
            <input type="hidden" name="project_type" value="Major">
            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Team Members</label>
                <p class="text-[11px] text-gray-500 mb-2">Select one or more team members. Choose at least one assignee across Team Members or Super Admins.</p>
                <div id="majorProjectHeadList" class="max-h-44 overflow-y-auto rounded border border-gray-700 bg-gray-800 p-2 space-y-1.5">
                    <div class="text-gray-500 text-xs font-mono py-2">Loading team members...</div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Super Admin Assignees (optional)</label>
                <p class="text-[11px] text-gray-500 mb-2">Select Super Admin collaborators that can be assigned project tasks immediately.</p>
                <div id="majorProjectSuperAdminList" class="max-h-36 overflow-y-auto rounded border border-gray-700 bg-gray-800 p-2 space-y-1.5">
                    <div class="text-gray-500 text-xs font-mono py-2">Loading Super Admins...</div>
                </div>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Project Title *</label>
                <input type="text" name="name" required placeholder="e.g., Build 20 Schools" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Description / KPI Target</label>
                <textarea name="kpi_target" rows="3" placeholder="e.g., Educate 500 children across remote villages" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none"></textarea>
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
            <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-2 rounded mt-2 transition-all">Create Major Project</button>
        </form>
    </div>
</div>

<!-- Edit Major Project Modal -->
<div id="modal-edit-project" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-2xl p-4 md:p-6 shadow-2xl relative modal-content">
        <button onclick="closeModal('modal-edit-project')" class="absolute top-4 right-4 text-gray-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-xl font-bold text-white font-tech mb-4">Edit Major Project</h3>
        <form id="form-edit-project" class="space-y-4">
            <input type="hidden" name="id" id="editProjectId">
            <input type="hidden" name="project_type" value="Major">
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Team Members *</label>
                <div id="editMajorProjectMemberList" class="max-h-44 overflow-y-auto rounded border border-gray-700 bg-gray-800 p-2 space-y-1.5"></div>
            </div>
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Project Title *</label>
                <input type="text" name="name" id="editProjectName" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Description / KPI Target</label>
                <textarea name="kpi_target" id="editProjectKpi" rows="3" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Status</label>
                    <select name="status" id="editProjectStatus" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                        <option value="Planned">Planned</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Start Date *</label>
                    <input type="date" name="start_date" id="editProjectStart" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">End Date *</label>
                    <input type="date" name="end_date" id="editProjectEnd" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                </div>
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 rounded mt-2 transition-all">Update Major Project</button>
        </form>
    </div>
</div>

<script>
    const userRole = '<?php echo $_SESSION['role']; ?>';
    let allProjects = [];
    let allTeamMembers = [];
    let allSuperAdmins = [];

    // Modal Handling
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    // Load Data
    document.addEventListener('DOMContentLoaded', () => {
        loadProjects();
        loadOptions();
    });

    function loadOptions() {
        if (userRole !== 'Super Admin') return;
        fetch('api/get_users.php')
            .then(res => res.json())
            .then((users) => {
                const safeUsers = Array.isArray(users) ? users : [];
                allTeamMembers = safeUsers.filter(u => u.role === 'Team Member');
                allSuperAdmins = safeUsers.filter(u => u.role === 'Super Admin');
                renderDepartmentHeadOptions();
                renderSuperAdminOptions();
            })
            .catch(() => {
                const wrap = document.getElementById('majorProjectHeadList');
                if (!wrap) return;
                wrap.innerHTML = '<div class="text-red-400 text-xs font-mono py-2">Failed to load team members.</div>';
                const saWrap = document.getElementById('majorProjectSuperAdminList');
                if (saWrap) {
                    saWrap.innerHTML = '<div class="text-red-400 text-xs font-mono py-2">Failed to load Super Admins.</div>';
                }
            });
    }

    function renderDepartmentHeadOptions() {
        const wrap = document.getElementById('majorProjectHeadList');
        if (!wrap) return;

        if (!allTeamMembers.length) {
            wrap.innerHTML = '<div class="text-gray-500 text-xs font-mono py-2">No team members found.</div>';
            return;
        }
        let html = '';
        allTeamMembers.forEach(h => {
            html += `<label class="flex items-start gap-2 py-1.5 px-2 rounded hover:bg-gray-700/40 cursor-pointer">
                    <input type="checkbox" name="member_ids[]" value="${h.id}" class="mt-0.5 w-4 h-4 accent-cyan-500">
                    <span class="text-sm text-white"><span class="font-semibold">${h.full_name}</span></span>
                </label>`;
        });
        wrap.innerHTML = html || '<div class="text-gray-500 text-xs font-mono py-2">No team members found.</div>';
    }

    function renderSuperAdminOptions() {
        const wrap = document.getElementById('majorProjectSuperAdminList');
        if (!wrap) return;
        if (!allSuperAdmins.length) {
            wrap.innerHTML = '<div class="text-gray-500 text-xs font-mono py-2">No Super Admins found.</div>';
            return;
        }
        wrap.innerHTML = allSuperAdmins.map(sa => `
            <label class="flex items-start gap-2 py-1.5 px-2 rounded hover:bg-gray-700/40 cursor-pointer">
                <input type="checkbox" name="super_admin_ids[]" value="${sa.id}" class="mt-0.5 w-4 h-4 accent-indigo-500">
                <span class="text-sm text-white"><span class="font-semibold">${sa.full_name}</span></span>
            </label>
        `).join('');
    }

    function loadProjects() {
        fetch('api/get_projects.php?type=Major')
            .then(res => res.json())
            .then(data => {
                allProjects = data;
                const tbody = document.getElementById('projects-list');
                const mobileContainer = document.getElementById('projects-mobile-list');
                
                tbody.innerHTML = '';
                mobileContainer.innerHTML = '';
                
                if (data.length === 0) {
                    const emptyMsg = 'No Major Projects found. Create your first one!';
                    tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 font-mono text-xs uppercase">${emptyMsg}</td></tr>`;
                    mobileContainer.innerHTML = `<div class="glass rounded-xl border border-gray-700/50 p-8 text-center text-gray-500 font-mono text-xs uppercase">${emptyMsg}</div>`;
                    return;
                }
                
                data.forEach(project => {
                    tbody.innerHTML += renderProjectRow(project);
                    mobileContainer.innerHTML += renderProjectCard(project);
                });
            })
            .catch(err => {
                console.error('Error loading projects:', err);
                const errMsg = 'Failed to load projects. View console for details.';
                document.getElementById('projects-list').innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-400 font-mono text-xs uppercase">${errMsg}</td></tr>`;
                document.getElementById('projects-mobile-list').innerHTML = `<div class="glass rounded-xl border border-red-500/20 p-6 text-center text-red-400 font-mono text-xs uppercase">${errMsg}</div>`;
            });
    }

    function renderProjectRow(p) {
        const sClass = getStatusClass(p.status);
        const progress = p.progress_percentage || 0;
        const pColor = getProgressColor(progress);
        const avatar = p.project_head_avatar ? p.project_head_avatar : 'uploads/avatars/default-avatar.svg';

        return `<tr class="hover:bg-white/5 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full border border-gray-700 overflow-hidden bg-gray-800">
                        <img src="${avatar}" class="w-full h-full object-cover">
                    </div>
                    <div class="min-w-0">
                        <div class="font-medium text-white truncate max-w-[200px]">${p.name}</div>
                        <div class="text-[10px] text-gray-500 mt-0.5 truncate max-w-[200px] italic">${p.kpi_target || 'No description'}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <a href="tasks.php?project_id=${p.id}" class="text-cyan-400 hover:text-cyan-300 font-mono text-xs flex items-center gap-1 group">
                    <span>${p.task_count} Tasks</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </a>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <div class="flex-1 min-w-[80px] h-1.5 bg-gray-700 rounded-full overflow-hidden">
                        <div class="${pColor} h-full transition-all" style="width: ${progress}%"></div>
                    </div>
                    <span class="text-[10px] text-gray-400 font-mono w-8 text-right">${progress}%</span>
                </div>
            </td>
            <td class="px-6 py-4">
                <span class="px-2 py-0.5 text-[10px] rounded border uppercase font-mono tracking-wider ${sClass}">
                    ${p.status}
                </span>
            </td>
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-3 text-gray-400">
                    <a href="tasks.php?project_id=${p.id}" class="hover:text-cyan-400 transition-colors" title="Manage Tasks">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </a>
                    <button onclick="openEditProject(${p.id})" class="hover:text-emerald-400 transition-colors" title="Edit Project">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    ${userRole === 'Super Admin' ? `
                    <button onclick="deleteProject(${p.id}, \`${p.name.replace(/'/g, "\\'")}\`)" class="hover:text-red-400 transition-colors" title="Delete Project">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                    ` : ''}
                </div>
            </td>
        </tr>`;
    }

    function renderProjectCard(p) {
        const sClass = getStatusClass(p.status);
        const progress = p.progress_percentage || 0;
        const pColor = getProgressColor(progress);
        const avatar = p.project_head_avatar ? p.project_head_avatar : 'uploads/avatars/default-avatar.svg';

        return `<div class="glass rounded-xl border border-gray-700/50 p-4 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full border border-gray-700 overflow-hidden bg-gray-800">
                        <img src="${avatar}" class="w-full h-full object-cover">
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-white truncate">${p.name}</h3>
                        <p class="text-[10px] text-gray-500 mt-0.5 line-clamp-1 italic">${p.kpi_target || 'No description'}</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 text-[9px] rounded border uppercase font-mono tracking-wider flex-shrink-0 ${sClass}">${p.status}</span>
            </div>

            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-[10px] text-gray-400 font-mono uppercase">Progress</span>
                    <span class="text-[10px] text-white font-mono">${progress}%</span>
                </div>
                <div class="w-full h-1.5 bg-gray-800 rounded-full overflow-hidden">
                    <div class="${pColor} h-full transition-all" style="width: ${progress}%"></div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-1 border-t border-gray-800">
                <a href="tasks.php?project_id=${p.id}" class="text-[10px] text-cyan-400 font-mono uppercase hover:text-cyan-300 transition-colors">
                    ${p.task_count} Tasks →
                </a>
                <div class="flex items-center gap-3">
                    <a href="tasks.php?project_id=${p.id}" class="p-1.5 rounded bg-gray-800 text-gray-400 hover:text-cyan-400 transition-colors" title="Manage">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </a>
                    <button onclick="openEditProject(${p.id})" class="p-1.5 rounded bg-gray-800 text-gray-400 hover:text-emerald-400 transition-colors" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    ${userRole === 'Super Admin' ? `
                    <button onclick="deleteProject(${p.id}, \`${p.name.replace(/'/g, "\\'")}\`)" class="p-1.5 rounded bg-gray-800 text-gray-400 hover:text-red-400 transition-colors" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                    ` : ''}
                </div>
            </div>
        </div>`;
    }

    function getStatusClass(status) {
        if (status === 'Completed') return 'bg-green-500/10 border-green-500/30 text-green-400';
        if (status === 'In Progress') return 'bg-blue-500/10 border-blue-500/30 text-blue-400';
        if (status === 'On Hold') return 'bg-red-500/10 border-red-500/30 text-red-400';
        return 'bg-gray-700/50 border-gray-600 text-gray-400';
    }

    function getProgressColor(progress) {
        if (progress >= 75) return 'bg-green-500';
        if (progress >= 50) return 'bg-yellow-500';
        if (progress >= 25) return 'bg-orange-500';
        return 'bg-red-500';
    }

    function deleteProject(id, name) {
        if (!confirm(`Are you sure you want to delete "${name}"? This will also delete all related tasks.`)) {
            return;
        }
        
        fetch('api/delete_project.php', {
            method: 'POST',
            body: JSON.stringify({ id })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                loadProjects();
            } else {
                alert(res.message);
            }
        });
    }

    // Form Submissions
    document.getElementById('form-add-project').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        if (userRole === 'Super Admin') {
            const memberIds = formData.getAll('member_ids[]').map(Number).filter(v => Number.isInteger(v) && v > 0);
            const superAdminIds = formData.getAll('super_admin_ids[]').map(Number).filter(v => Number.isInteger(v) && v > 0);
            if (!memberIds.length && !superAdminIds.length) {
                alert('Please select at least one assignee (Team Member or Super Admin) for this Major Project.');
                return;
            }
            data.member_ids = memberIds;
            data.super_admin_ids = superAdminIds;
            delete data['member_ids[]'];
            delete data['super_admin_ids[]'];
        }
        
        fetch('api/create_project.php', {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                closeModal('modal-add-project');
                e.target.reset();
                loadProjects();
            } else {
                alert(res.message);
            }
        });
    });

    function renderEditMemberOptions(selectedIds = []) {
        const wrap = document.getElementById('editMajorProjectMemberList');
        if (!wrap) return;
        if (!allTeamMembers.length) {
            wrap.innerHTML = '<div class="text-gray-500 text-xs font-mono py-2">No team members found.</div>';
            return;
        }
        const selectedSet = new Set(selectedIds.map(Number));
        wrap.innerHTML = allTeamMembers.map(member => `
            <label class="flex items-start gap-2 py-1.5 px-2 rounded hover:bg-gray-700/40 cursor-pointer">
                <input type="checkbox" name="member_ids[]" value="${member.id}" class="mt-0.5 w-4 h-4 accent-cyan-500" ${selectedSet.has(Number(member.id)) ? 'checked' : ''}>
                <span class="text-sm text-white"><span class="font-semibold">${member.full_name}</span></span>
            </label>
        `).join('');
    }

    function openEditProject(projectId) {
        const p = allProjects.find(project => Number(project.id) === Number(projectId));
        if (!p) return;
        document.getElementById('editProjectId').value = p.id;
        document.getElementById('editProjectName').value = p.name || '';
        document.getElementById('editProjectKpi').value = p.kpi_target || '';
        document.getElementById('editProjectStatus').value = p.status || 'Planned';
        document.getElementById('editProjectStart').value = p.start_date || '';
        document.getElementById('editProjectEnd').value = p.end_date || '';
        renderEditMemberOptions([]);
        openModal('modal-edit-project');
    }

    document.getElementById('form-edit-project').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const memberIds = formData.getAll('member_ids[]').map(Number).filter(v => Number.isInteger(v) && v > 0);
        if (!memberIds.length) {
            alert('Please select at least one team member for this Major Project.');
            return;
        }
        const data = Object.fromEntries(formData);
        data.member_ids = memberIds;
        delete data['member_ids[]'];
        fetch('api/update_project.php', {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                closeModal('modal-edit-project');
                loadProjects();
            } else {
                alert(res.message || 'Failed to update project');
            }
        });
    });
</script>
<?php include 'includes/footer.php'; ?>
