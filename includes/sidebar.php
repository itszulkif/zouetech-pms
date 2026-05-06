<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>
<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 glass border-r border-slate-700/60 flex flex-col transition-all duration-300 -translate-x-full md:translate-x-0 md:static md:flex font-mono sharp-gpu">
    <!-- Logo -->
    <div class="h-16 flex items-center justify-between px-4 border-b border-slate-700/60 relative overflow-visible group">
        <div class="absolute inset-0 bg-blue-500/10 blur-xl group-hover:bg-blue-500/15 transition-all"></div>
        <h1 class="text-2xl font-bold text-white font-tech tracking-wider relative z-10">
            <span class="text-blue-300">ZOUETECH</span> 
        </h1>
        <div id="tan-sidebar-notification-anchor" class="relative z-10 flex items-center ml-3 shrink-0"></div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-6 space-y-8 px-4">
        
        <!-- Section: Overview -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3 pl-3">Overview</h3>
            <ul class="space-y-2">
                <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                <li>
                    <a href="dashboard.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-blue-400 group-hover:text-blue-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span class="font-medium">Dashboard</span>
                    </a>
                </li>
                <?php
endif; ?>
                <?php if ($_SESSION['role'] !== 'Super Admin'): ?>
                 <li>
                    <a href="employee_dashboard.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'employee_dashboard.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-purple-500 group-hover:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span class="font-medium">My Desk</span>
                    </a>
                </li>
                <?php
endif; ?>
            </ul>
        </div>

        <?php if (in_array($_SESSION['role'], ['Super Admin'])): ?>
        <!-- Section: Strategic -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3 pl-3">Strategic</h3>
            <ul class="space-y-2">
                <li>
                    <a href="projects.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-indigo-500 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span class="font-medium">Projects</span>
                    </a>
                </li>
              
            </ul>
        </div>
        <?php
endif; ?>

        <!-- Section: Operations -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3 pl-3">Operations</h3>
            <ul class="space-y-2">

                <li>
                    <a href="tasks.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-pink-500 group-hover:text-pink-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <span class="font-medium">Project Task</span>
                    </a>
                </li>
                <li>
                    <a href="direct_tasks.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'direct_tasks.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-purple-500 group-hover:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span class="font-medium">Direct Tasks</span>
                    </a>
                </li>
                <?php if (in_array($_SESSION['role'], ['Super Admin'])): ?>
                <li>
                    <a href="review_tasks.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'review_tasks.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-amber-500 group-hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="font-medium">Review Tasks</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="calendar.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-cyan-500 group-hover:text-cyan-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="font-medium">Calendar</span>
                    </a>
                </li>
                <?php if (in_array($_SESSION['role'], ['Super Admin'])): ?>
                <li>
                    <a href="day_todo_calendar.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'day_todo_calendar.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-violet-400 group-hover:text-violet-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 9h6M9 13h4m-7 8h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span class="font-medium">Day To-Do Calendar</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="attendance_sheet.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'attendance_sheet.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-emerald-500 group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zM9 14l2 2 4-4"></path></svg>
                        <span class="font-medium">Attendance Sheet</span>
                    </a>
                </li>
                <?php if (in_array($_SESSION['role'], ['Super Admin'])): ?>
                <li>
                    <a href="performance_overview.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'performance_overview.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-cyan-500 group-hover:text-cyan-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span class="font-medium">Performance Overview</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Section: Accountability -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3 pl-3">Accountability</h3>
             <ul class="space-y-2">

            </ul>
        </div>
        
        <!-- Section: System -->
        <div>
             <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3 pl-3">System</h3>
             <ul class="space-y-2">
                <li>
                    <a href="settings.php" class="flex items-center px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800/70 rounded-lg transition-all group <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-blue-500/12 border-l-4 border-blue-400 text-white shadow-[0_6px_20px_rgba(37,99,235,0.22)]' : 'border-l-4 border-transparent'; ?>">
                        <svg class="w-5 h-5 mr-3 text-green-500 group-hover:text-green-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="font-medium">Settings</span>
                    </a>
                </li>
            </ul>
        </div>

    </nav>
    
    <!-- Logout -->
    <div class="p-4 border-t border-slate-700/60">
        <a href="logout.php" class="flex items-center px-3 py-2 text-red-400 hover:text-red-300 hover:bg-red-500/15 rounded-lg transition-all">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <span class="font-medium">Logout</span>
        </a>
    </div>
</aside>
