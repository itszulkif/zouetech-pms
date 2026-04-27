<?php
// settings.php
require_once 'db_connect.php';
require_once 'includes/auth_middleware.php';
require_role(['Super Admin', 'Team Member']);
include 'includes/header.php';
include 'includes/sidebar.php';
$sessionAvatarUrl = htmlspecialchars(get_session_avatar_url(), ENT_QUOTES, 'UTF-8');
?>

<main class="flex-1 flex flex-col overflow-hidden bg-gray-900/40 backdrop-blur-md">
    <!-- Header -->
    <header class="min-h-16 py-2 flex items-center justify-between gap-2 px-3 sm:px-4 md:px-6 border-b border-gray-800 bg-gray-900/50 backdrop-blur-sm sticky top-0 z-20">
        <div class="flex items-center space-x-2 sm:space-x-3 md:space-x-4 min-w-0">
            <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <h1 class="text-sm sm:text-base md:text-xl font-bold text-white font-tech tracking-wide md:tracking-wider truncate">SYSTEM SETTINGS</h1>
        </div>
        <div class="flex items-center gap-2 md:gap-4 flex-shrink-0">
            <div class="text-right hidden sm:block">
                <p class="text-xs md:text-sm font-bold text-white font-tech tracking-wide uppercase leading-tight"><?php echo $_SESSION['full_name']; ?></p>
                <p class="text-[9px] md:text-[10px] text-cyan-400 font-mono leading-tight uppercase"><?php echo $_SESSION['role']; ?></p>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-gradient-to-tr from-cyan-500 to-indigo-500 p-[2px]">
                <div class="w-full h-full rounded-full bg-gray-900 border-2 border-transparent relative overflow-hidden">
                    <img src="<?php echo $sessionAvatarUrl; ?>" alt="User" class="rounded-full w-full h-full object-cover">
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <div class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 scroll-smooth">
        
        <!-- Tabs (Departments tab hidden from Department Heads) -->
        <div class="mb-6 border-b border-gray-700 overflow-x-auto">
            <div class="flex min-w-max space-x-2 sm:space-x-4">
            <?php if (in_array($_SESSION['role'], ['Super Admin'])): ?>
            <button onclick="switchTab('team')" id="tab-team" class="px-3 sm:px-4 py-2 text-xs sm:text-sm text-cyan-400 border-b-2 border-cyan-400 font-mono focus:outline-none transition-colors whitespace-nowrap">Team Members</button>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
            <button onclick="switchTab('departments')" id="tab-departments" class="px-3 sm:px-4 py-2 text-xs sm:text-sm text-gray-400 hover:text-white font-mono focus:outline-none transition-colors whitespace-nowrap">Departments</button>
            <?php endif; ?>
            <button onclick="switchTab('notifications')" id="tab-notifications" class="px-3 sm:px-4 py-2 text-xs sm:text-sm <?php echo in_array($_SESSION['role'], ['Super Admin']) ? 'text-gray-400 hover:text-white' : 'text-cyan-400 border-b-2 border-cyan-400'; ?> font-mono focus:outline-none transition-colors whitespace-nowrap">Notifications</button>
            </div>
        </div>

        <!-- Team Members Section -->
        <div id="content-team" class="settings-tab <?php echo in_array($_SESSION['role'], ['Super Admin']) ? '' : 'hidden'; ?>">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg text-white font-tech">Manage Team</h2>
                <button onclick="openModal('modal-add-user')" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded flex items-center font-mono text-sm transition-all shadow-lg hover:shadow-cyan-500/30">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    ADD MEMBER
                </button>
            </div>

            <div class="glass rounded-xl border border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-gray-800/50 text-[10px] text-gray-400 uppercase font-mono tracking-widest">
                            <tr>
                                <th class="px-6 py-3">Member</th>
                                <th class="px-6 py-3">Access Level</th>
                                <th class="px-6 py-3">Unit/Dept</th>
                                <th class="px-6 py-3">Contact info</th>
                                <th class="px-6 py-3 text-right">Control</th>
                            </tr>
                        </thead>
                        <tbody id="users-list" class="text-sm text-gray-300 divide-y divide-gray-800">
                            <!-- Users populated via JS -->
                            <tr><td colspan="5" class="px-6 py-4 text-center">Loading registry...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Departments Section -->
        <div id="content-departments" class="settings-tab hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg text-white font-tech">Manage Departments</h2>
                <button onclick="openModal('modal-add-dept')" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded flex items-center font-mono text-sm transition-all shadow-lg hover:shadow-indigo-500/30">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    ADD DEPARTMENT
                </button>
            </div>

            <div class="glass rounded-xl border border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[600px]">
                        <thead class="bg-gray-800/50 text-[10px] text-gray-400 uppercase font-mono tracking-widest">
                            <tr>
                                <th class="px-6 py-3">Department Name</th>
                                <th class="px-6 py-3">Manager (Head)</th>
                                <th class="px-6 py-3 text-right">Control</th>
                            </tr>
                        </thead>
                        <tbody id="departments-list" class="text-sm text-gray-300 divide-y divide-gray-800">
                            <!-- Departments populated via JS -->
                             <tr><td colspan="3" class="px-6 py-4 text-center">Loading units...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notifications Section (Push) -->
        <div id="content-notifications" class="settings-tab <?php echo in_array($_SESSION['role'], ['Super Admin']) ? 'hidden' : ''; ?>">
            <div class="glass rounded-xl border border-gray-700/50 p-6 max-w-lg">
                <h2 class="text-lg text-white font-tech mb-2">Push Notifications</h2>
                <p class="text-sm text-gray-400 mb-4">Receive notifications for new messages, task assignments, and mentions even when the app is in the background.</p>
                <div id="push-status" class="text-sm text-gray-400 mb-4"></div>
                <button id="btn-enable-push" type="button" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded font-mono text-sm transition-all">
                    Enable Push Notifications
                </button>
                <?php if (in_array($_SESSION['role'], ['Team Member', 'Team Lead'], true)): ?>
                <div class="mt-4 pt-4 border-t border-gray-700">
                    <button type="button" onclick="openSelfEdit()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded font-mono text-sm transition-all">
                        Edit My Profile
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Add User Modal -->
<div id="modal-add-user" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-md p-6 shadow-2xl relative">
        <button onclick="closeModal('modal-add-user')" class="absolute top-4 right-4 text-gray-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-xl font-bold text-white font-tech mb-4">Add New Member</h3>
        <form id="form-add-user" class="space-y-4">
            <!-- Avatar Upload Area -->
            <div class="flex items-center space-x-4 mb-2">
                <div class="w-16 h-16 rounded-full border-2 border-dashed border-gray-600 bg-gray-800 flex items-center justify-center overflow-hidden flex-shrink-0 group relative">
                    <div id="add-avatar-preview" class="absolute inset-0 z-0"></div>
                    <label for="add-avatar-input" class="absolute inset-0 z-10 flex flex-col items-center justify-center cursor-pointer bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </label>
                    <input type="file" id="add-avatar-input" name="avatar" accept="image/jpeg, image/png, image/gif, image/webp" class="hidden">
                </div>
                <div>
                    <p class="text-sm text-white font-medium">Profile Picture</p>
                    <p class="text-xs text-gray-500 font-mono mt-0.5">Optional. JPG, PNG or WEBP.</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Full Name</label>
                <input type="text" name="full_name" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-mono text-cyan-400 mb-1">Phone Number</label>
                <input type="text" name="phone_number" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Role</label>
                    <select name="role" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                        <option value="Team Member">Team Member</option>
                        <option value="Team Lead">Team Lead</option>
                        <option value="Super Admin">Super Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Department</label>
                    <select name="department_id" id="select-dept-user" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                        <option value="">None</option>
                        <!-- Populated via JS -->
                    </select>
                </div>
            </div>
            <div>
                 <label class="block text-xs font-mono text-cyan-400 mb-1">Password (Default: zouetech123)</label>
                 <input type="text" name="password" placeholder="Leave blank for default" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-2 rounded mt-2 transition-all">Create User</button>
        </form>
    </div>
</div>

<!-- Add Department Modal -->
<div id="modal-add-dept" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-md p-6 shadow-2xl relative">
        <button onclick="closeModal('modal-add-dept')" class="absolute top-4 right-4 text-gray-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-xl font-bold text-white font-tech mb-4">Add Department</h3>
        <form id="form-add-dept" class="space-y-4">
            <div>
                <label class="block text-xs font-mono text-indigo-400 mb-1">Department Name</label>
                <input type="text" name="name" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-mono text-indigo-400 mb-1">Assign Manager (Optional)</label>
                <select name="head_id" id="select-head-dept" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-indigo-500 focus:outline-none">
                    <option value="">None</option>
                    <!-- Populated via JS -->
                </select>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 rounded mt-2 transition-all">Create Department</button>
        </form>
    </div>
</div>

    <!-- Edit User Modal -->
    <div id="modal-edit-user" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-md p-6 shadow-2xl relative">
            <button onclick="closeModal('modal-edit-user')" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h3 class="text-xl font-bold text-white font-tech mb-4">Edit Member</h3>
            <form id="form-edit-user" class="space-y-4">
                <input type="hidden" name="id" id="edit-user-id">

                <!-- Avatar Upload Area -->
                <div class="flex items-center space-x-4 mb-2">
                    <div class="w-16 h-16 rounded-full border-2 border-dashed border-gray-600 bg-gray-800 flex items-center justify-center overflow-hidden flex-shrink-0 group relative">
                        <div id="edit-avatar-preview" class="absolute inset-0 z-0"></div>
                        <label for="edit-avatar-input" class="absolute inset-0 z-10 flex flex-col items-center justify-center cursor-pointer bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </label>
                        <input type="file" id="edit-avatar-input" name="avatar" accept="image/jpeg, image/png, image/gif, image/webp" class="hidden">
                    </div>
                    <div>
                        <p class="text-sm text-white font-medium">Profile Picture</p>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">Optional. JPG, PNG or WEBP.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Full Name</label>
                    <input type="text" name="full_name" id="edit-user-name" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Email Address</label>
                    <input type="email" name="email" id="edit-user-email" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-mono text-cyan-400 mb-1">Phone Number</label>
                    <input type="text" name="phone_number" id="edit-user-phone" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-mono text-cyan-400 mb-1">Role</label>
                        <select name="role" id="edit-user-role" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none" onchange="toggleEditDept(this.value)">
                            <option value="Team Member">Team Member</option>
                            <option value="Team Lead">Team Lead</option>
                            <option value="Super Admin">Super Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-cyan-400 mb-1">Department</label>
                        <select name="department_id" id="edit-user-dept" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                            <option value="">None</option>
                            <!-- Populated via JS -->
                        </select>
                    </div>
                </div>
                <div>
                     <label class="block text-xs font-mono text-cyan-400 mb-1">Password</label>
                     <input type="text" id="edit-user-password" name="password" placeholder="Password from database will appear here" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-2 rounded mt-2 transition-all">Update User</button>
            </form>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div id="modal-edit-dept" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-md p-6 shadow-2xl relative">
            <button onclick="closeModal('modal-edit-dept')" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h3 class="text-xl font-bold text-white font-tech mb-4">Edit Department</h3>
            <form id="form-edit-dept" class="space-y-4">
                <input type="hidden" name="id" id="edit-dept-id">
                <div>
                    <label class="block text-xs font-mono text-indigo-400 mb-1">Department Name</label>
                    <input type="text" name="name" id="edit-dept-name" required class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-mono text-indigo-400 mb-1">Assign Manager (Optional)</label>
                    <select name="head_id" id="edit-dept-head" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:border-indigo-500 focus:outline-none">
                        <option value="">None</option>
                        <!-- Populated via JS -->
                    </select>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 rounded mt-2 transition-all">Update Department</button>
            </form>
        </div>
    </div>

<script>
    const userRole   = '<?php echo $_SESSION["role"]; ?>';
    const userDeptId = <?php echo intval($_SESSION['department_id'] ?? 0); ?>;
    const sessionUserId = <?php echo intval($_SESSION['user_id'] ?? 0); ?>;

    // Tab Switching
    function switchTab(tab) {
        document.querySelectorAll('.settings-tab').forEach(el => el.classList.add('hidden'));
        document.getElementById('content-' + tab).classList.remove('hidden');

        const tabTeam = document.getElementById('tab-team');
        if (tabTeam) {
            tabTeam.classList.replace('text-cyan-400', 'text-gray-400');
            tabTeam.classList.remove('border-b-2', 'border-cyan-400');
        }
        const tabDepts = document.getElementById('tab-departments');
        if (tabDepts) {
            tabDepts.classList.replace('text-cyan-400', 'text-gray-400');
            tabDepts.classList.remove('border-b-2', 'border-cyan-400');
        }
        const tabNotif = document.getElementById('tab-notifications');
        if (tabNotif) {
            tabNotif.classList.replace('text-cyan-400', 'text-gray-400');
            tabNotif.classList.remove('border-b-2', 'border-cyan-400');
        }

        const activeTab = document.getElementById('tab-' + tab);
        if (activeTab) {
            activeTab.classList.replace('text-gray-400', 'text-cyan-400');
            activeTab.classList.add('border-b-2', 'border-cyan-400');
        }
    }

    // Modal Handling
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    // Super Admin Logic
    function toggleDept(roleValue) {
        const deptSelect = document.getElementById('select-dept-user');
        if (!deptSelect) return; // Dept Head: field is a locked hidden input
        if (roleValue === 'Super Admin') {
            deptSelect.value = "";
            deptSelect.setAttribute('disabled', 'disabled');
            deptSelect.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            deptSelect.removeAttribute('disabled');
            deptSelect.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    function toggleEditDept(roleValue) {
        const deptSelect = document.getElementById('edit-user-dept');
        if (roleValue === 'Super Admin') {
            deptSelect.value = "";
            deptSelect.setAttribute('disabled', 'disabled');
            deptSelect.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            deptSelect.removeAttribute('disabled');
            deptSelect.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    // Bind event to Add User Role Select (only exists for Super Admin)
    const roleSelect = document.querySelector('#form-add-user select[name="role"]');
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            toggleDept(this.value);
        });
    }

    // Load Data
    document.addEventListener('DOMContentLoaded', () => {
        if (userRole === 'Super Admin') {
            loadUsers();
            loadDepartments();
            loadOptions(); // For dropdowns
        } else {
            switchTab('notifications');
            loadOptions();
        }
        initPushNotifications();
    });

    function canEditUser(user) {
        if (userRole === 'Super Admin') return true;
        return Number(user.id) === Number(sessionUserId);
    }

    // Push Notifications
    async function ensureServiceWorkerReady() {
        try {
            if (!('serviceWorker' in navigator)) return null;
            let reg = await navigator.serviceWorker.getRegistration();
            if (!reg) {
                reg = await navigator.serviceWorker.register('sw.js?v=1.0.1');
            }
            return reg || await navigator.serviceWorker.ready;
        } catch (_) {
            return null;
        }
    }

    async function enablePushSubscription(statusEl, btnEl) {
        try {
            const res = await fetch('api/get_vapid_public.php');
            const data = await res.json();
            const publicKey = data && data.publicKey ? data.publicKey : '';
            if (!publicKey) {
                statusEl.textContent = 'Push not configured. Run: php setup_vapid_keys.php';
                return false;
            }

            const reg = await ensureServiceWorkerReady();
            if (!reg || !reg.pushManager) {
                statusEl.textContent = 'Service worker registration failed.';
                return false;
            }

            let sub = await reg.pushManager.getSubscription();
            if (!sub) {
                sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey)
                });
            }

            const subJson = sub.toJSON();
            const saveRes = await fetch('api/push_subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    endpoint: subJson.endpoint,
                    keys: { p256dh: subJson.keys.p256dh, auth: subJson.keys.auth }
                })
            });
            const saveData = await saveRes.json();
            if (!saveData.success) {
                statusEl.textContent = 'Failed: ' + (saveData.message || 'Unknown error');
                return false;
            }

            statusEl.textContent = 'Push notifications enabled.';
            btnEl.textContent = 'Enabled';
            btnEl.disabled = true;
            return true;
        } catch (e) {
            statusEl.textContent = 'Error: ' + (e.message || 'Subscription failed');
            return false;
        }
    }

    function initPushNotifications() {
        const btn = document.getElementById('btn-enable-push');
        const status = document.getElementById('push-status');
        if (!btn || !status) return;
        if (!('Notification' in window) || !('serviceWorker' in navigator)) {
            status.textContent = 'Push notifications are not supported in this browser.';
            btn.disabled = true;
            return;
        }
        if (Notification.permission === 'granted') {
            status.textContent = 'Permission granted. Verifying subscription...';
            enablePushSubscription(status, btn);
        } else if (Notification.permission === 'denied') {
            status.textContent = 'Notifications were blocked. Enable them in your browser settings.';
            btn.disabled = true;
        }
        btn.addEventListener('click', async function() {
            if (Notification.permission === 'granted') return;
            status.textContent = 'Requesting permission...';
            const perm = await Notification.requestPermission();
            if (perm !== 'granted') {
                status.textContent = 'Permission denied.';
                return;
            }
            status.textContent = 'Subscribing...';
            await enablePushSubscription(status, btn);
        });
    }
    function urlBase64ToUint8Array(base64) {
        const padding = '='.repeat((4 - base64.length % 4) % 4);
        const b64 = (base64 + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(b64);
        const arr = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    function loadUsers() {
        fetch('api/get_users.php')
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('users-list');
                tbody.innerHTML = '';
                data.forEach(user => {
                    const avatar = user.avatar_url && user.avatar_url.trim() !== ''
                        ? user.avatar_url
                        : 'uploads/avatars/default-avatar.svg';
                    tbody.innerHTML += `
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="px-6 py-4 flex items-center">
                                <img src="${avatar}" class="w-8 h-8 rounded-full mr-3 border border-gray-600">
                                <div>
                                    <div class="font-medium text-white">${user.full_name}</div>
                                    <div class="text-xs text-gray-500">${user.email}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded border 
                                    ${user.role === 'Super Admin' ? 'bg-purple-500/10 border-purple-500 text-purple-400' : 
                                      (user.role === 'Team Member' ? 'bg-gray-700/50 border-gray-600 text-gray-400' : 'bg-blue-500/10 border-blue-500 text-blue-400')}">
                                    ${user.role}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-400">${user.department_name || '-'}</td>
                             <td class="px-6 py-4 text-gray-400 font-mono text-xs">${user.phone_number || '-'}</td>
                             <td class="px-6 py-4 text-right opacity-0 group-hover:opacity-100 transition-opacity">
                                ${canEditUser(user) ? `
                                <button onclick='openEditUser(${JSON.stringify(user)})' class="text-cyan-400 hover:text-cyan-300 mr-2" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                ` : ''}
                                ${userRole === 'Super Admin' ? `
                                <button onclick="deleteUser(${user.id})" class="text-red-400 hover:text-red-300" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                ` : ''}
                            </td>
                        </tr>
                    `;
                });
            });
    }

    function loadDepartments() {
        fetch('api/get_departments.php')
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('departments-list');
                tbody.innerHTML = '';
                data.forEach(dept => {
                    tbody.innerHTML += `
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="px-6 py-4 font-medium text-white">${dept.name}</td>
                            <td class="px-6 py-4 text-gray-400 text-sm">
                                ${dept.head_name || 'No Manager'}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button onclick='openEditDept(${JSON.stringify(dept)})' class="text-indigo-400 hover:text-indigo-300 mr-2" title="Edit">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button onclick="deleteDepartment(${dept.id})" class="text-red-400 hover:text-red-300" title="Delete">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            });
    }
    
    // Global store for options
    let allUsers = [];
    let allDepts = [];

    function loadOptions() {
         fetch('api/get_users.php').then(res => res.json()).then(data => {
            allUsers = data;
            
            // Populate Manager Select in Add Dept Modal
            const select = document.getElementById('select-head-dept');
            if (select) {
                select.innerHTML = '<option value="">None</option>';
                data.forEach(u => {
                    select.innerHTML += `<option value="${u.id}">${u.full_name} (${u.role})</option>`;
                });
            }

             // Populate Manager Select in Edit Dept Modal
            const editSelect = document.getElementById('edit-dept-head');
            if (editSelect) {
                editSelect.innerHTML = '<option value="">None</option>';
                data.forEach(u => {
                    editSelect.innerHTML += `<option value="${u.id}">${u.full_name} (${u.role})</option>`;
                });
            }
         });

         fetch('api/get_departments.php').then(res => res.json()).then(data => {
            allDepts = data;

            // Populate Dept Select in Add User Modal
            const select = document.getElementById('select-dept-user');
            if (select) {
                select.innerHTML = '<option value="">None</option>';
                data.forEach(d => {
                    select.innerHTML += `<option value="${d.id}">${d.name}</option>`;
                });
            }

            // Populate Dept Select in Edit User Modal
            const editSelect = document.getElementById('edit-user-dept');
            if (editSelect) {
                editSelect.innerHTML = '<option value="">None</option>';
                data.forEach(d => {
                    editSelect.innerHTML += `<option value="${d.id}">${d.name}</option>`;
                });
            }
         });
    }

    // Edit/Delete Team Member Functions
    function openEditUser(user) {
        fetch('api/get_users.php')
            .then(res => res.json())
            .then(users => {
                const freshUser = Array.isArray(users)
                    ? users.find(u => Number(u.id) === Number(user.id))
                    : null;
                const selectedUser = freshUser || user;

                document.getElementById('edit-user-id').value = selectedUser.id;
                document.getElementById('edit-user-name').value = selectedUser.full_name;
                document.getElementById('edit-user-email').value = selectedUser.email;
                document.getElementById('edit-user-phone').value = selectedUser.phone_number || '';
                document.getElementById('edit-user-role').value = selectedUser.role;
                document.getElementById('edit-user-dept').value = selectedUser.department_id || '';
                document.getElementById('edit-user-password').value = selectedUser.password_plain || '';

                const editRole = document.getElementById('edit-user-role');
                const editDept = document.getElementById('edit-user-dept');
                if (userRole === 'Super Admin') {
                    editRole.removeAttribute('disabled');
                    editDept.removeAttribute('disabled');
                } else {
                    editRole.setAttribute('disabled', 'disabled');
                    editDept.setAttribute('disabled', 'disabled');
                }
                
                // Show existing avatar in preview (fallback to default avatar)
                const preview = document.getElementById('edit-avatar-preview');
                const fileInput = document.getElementById('edit-avatar-input');
                fileInput.value = ''; // Reset file input
                const currentAvatar = selectedUser.avatar_url && selectedUser.avatar_url.trim() !== ''
                    ? selectedUser.avatar_url
                    : 'uploads/avatars/default-avatar.svg';
                preview.innerHTML = `<img src="${currentAvatar}?t=${Date.now()}" class="w-full h-full object-cover">`;

                toggleEditDept(selectedUser.role);
                openModal('modal-edit-user');
            });
    }

    function openSelfEdit() {
        fetch('api/get_users.php')
            .then(res => res.json())
            .then(users => {
                if (!Array.isArray(users) || users.length === 0) {
                    alert('Unable to load your profile.');
                    return;
                }
                const me = users.find(u => Number(u.id) === Number(sessionUserId));
                if (!me) {
                    alert('Unable to locate your profile.');
                    return;
                }
                openEditUser(me);
            });
    }

    function deleteUser(id) {
        if(!confirm('Are you sure you want to delete this user?')) return;
        
        fetch('api/delete_user.php', {
            method: 'POST',
            body: JSON.stringify({id: id})
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                loadUsers();
            } else {
                alert(res.message);
            }
        });
    }

    document.getElementById('form-edit-user').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const roleField = document.getElementById('edit-user-role');
        const deptField = document.getElementById('edit-user-dept');
        if (roleField.disabled) {
            formData.set('role', roleField.value);
        }
        if (deptField.disabled) {
            formData.set('department_id', deptField.value);
        }
        // Handle disabled select logic if needed (disabled inputs aren't sent)
        if (formData.get('role') === 'Super Admin') {
            formData.delete('department_id');
        }

        fetch('api/update_user.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                closeModal('modal-edit-user');
                loadUsers();
            } else {
                alert(res.message);
            }
        });
    });


    // Edit/Delete Department Functions
    function openEditDept(dept) {
        document.getElementById('edit-dept-id').value = dept.id;
        document.getElementById('edit-dept-name').value = dept.name;
        document.getElementById('edit-dept-head').value = dept.head_id || '';
        openModal('modal-edit-dept');
    }

    function deleteDepartment(id) {
        if(!confirm('WARNING: Deleting a department will delete all its projects and tasks. Users will be unassigned. Continue?')) return;

        fetch('api/delete_department.php', {
            method: 'POST',
            body: JSON.stringify({id: id})
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                loadDepartments();
                loadOptions(); // Refresh dept lists
            } else {
                alert(res.message);
            }
        });
    }

    document.getElementById('form-edit-dept').addEventListener('submit', function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        
        fetch('api/update_department.php', {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                closeModal('modal-edit-dept');
                loadDepartments();
                loadOptions(); // Refresh
            } else {
                alert(res.message);
            }
        });
    });

    // Form Submissions (Add) - Existing
    document.getElementById('form-add-user').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('api/create_user.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                closeModal('modal-add-user');
                e.target.reset();
                loadUsers();
                loadOptions(); // Refresh options if needed
            } else {
                alert(res.message);
            }
        });
    });

    // Avatar preview — Add user

    document.getElementById('add-avatar-input').addEventListener('change', function() {

        const file = this.files[0];

        if (!file) return;

        const reader = new FileReader();

        reader.onload = ev => {

            document.getElementById('add-avatar-preview').innerHTML = '<img src="' + ev.target.result + '" class="w-full h-full object-cover">';

        };

        reader.readAsDataURL(file);

    });



    // Avatar preview — Edit user

    document.getElementById('edit-avatar-input').addEventListener('change', function() {

        const file = this.files[0];

        if (!file) return;

        const reader = new FileReader();

        reader.onload = ev => {

            document.getElementById('edit-avatar-preview').innerHTML = '<img src="' + ev.target.result + '" class="w-full h-full object-cover">';

        };

        reader.readAsDataURL(file);

    });



    document.getElementById('form-add-dept').addEventListener('submit', function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        
        fetch('api/create_department.php', {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                closeModal('modal-add-dept');
                e.target.reset();
                loadDepartments();
                loadOptions(); // Refresh options
            } else {
                alert(res.message);
            }
        });
    });

</script>

<?php include 'includes/footer.php'; ?>
