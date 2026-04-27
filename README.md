## Quick Production Notes

- Full production runbook: see `PRODUCTION.md`.
- Required production environment variables: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_TIMEZONE`, `DB_TIMEZONE`.
- Run migrations in `sql/` before first live traffic:
  - `sql/add_task_assignment_schema.sql`
  - `sql/add_push_subscriptions.sql`
  - `sql/add_task_chat_read.sql`
- Ensure writable directories exist: `logs/`, `uploads/avatars/`.
- Keep runtime schema changes disabled in production (`APP_ALLOW_RUNTIME_MIGRATIONS` unset or `0`).

1. Detailed prompt describing all features (for another AI)
You can paste this as-is into another model:

You are analyzing an existing web-based project management system called **Zouetech-PMS**.
It is a PHP + MySQL application with a Tailwind-style utility CSS design, Chart.js for charts, and a PWA layer (service worker, offline support, push notifications).
Please read this summary of the system’s functionality and then use it as authoritative context when generating documentation, UX copy, or further code for the project.
---
HIGH-LEVEL PURPOSE
- Zouetech-PMS is a **role-based project and task management system** for organizations.
- It manages:
  - **Projects** (major and sub-projects)
  - **Project tasks**
  - **Direct tasks** (standalone, non-project tasks)
  - **Performance scoring** and logs
  - **Team communication via per-task chat**
  - **Department-level and organization-level dashboards**
- It has first-class support for:
  - **Super Admin**, **Department Head**, and **Team Member** roles.
  - **Progress / performance analytics** at project, department, and user level.
  - A **PWA shell** with offline behavior, manifest, and push notification hooks.
---
ROLES AND ACCESS CONTROL
- **Super Admin**
  - Full system view via a “Command Center” dashboard (`dashboard.php`).
  - Can see all departments, projects, tasks, and direct tasks.
  - Sees **tasks pending approval** from Department Heads.
  - Global stats: number of projects, departments, members, active project tasks, active direct tasks, completed direct tasks, missed tasks.
  - Department-wise view of project tasks and at-risk projects.
  - Access to direct tasks overview, user and department management APIs (`api/create_user.php`, `api/create_department.php`, etc.).
- **Department Head**
  - Uses **Employee Dashboard** (`employee_dashboard.php`) as a **Department Command Centre**.
  - Has visibility into:
    - Assigned team members on projects.
    - Project tasks statistics (total, done, outstanding, overdue).
    - Direct tasks statistics for their department (total, done, outstanding, overdue).
    - **Project completion rate** bar for department’s project tasks.
    - Active and incomplete major projects with progress, deadlines, and overdue status.
    - Sub-projects at a glance: progress, task counts, status.
    - Overdue accountability: list of members with overdue counts and visual bars.
    - Recent direct tasks in their department, with assignee, department, creator, priority, and status.
  - The Department Head can also:
    - Filter and inspect direct tasks by scope (My / Team / External) on `direct_tasks.php`.
    - Approve or reject task assignments via APIs (`api/approve_task_assignment.php`, `api/reject_task_assignment.php`).
    - See combined **department performance** metrics from `api/get_dept_head_stats.php`.
- **Team Member**
  - Uses **Employee Dashboard** as a “My Desk” view (`employee_dashboard.php`).
  - Sees:
    - Personal **performance score** and score trend (monthly/weekly).
    - Recent performance logs (reasons for score changes).
    - Personal workload stats:
      - Pending vs completed project tasks.
      - Pending vs completed direct tasks.
      - Overdue counts for each.
    - Project tasks table: project name, priority, due dates, overdue markers.
    - Direct tasks table: priority and due date, with overdue highlighting.
  - All Team Member views are restricted to their own tasks via query filters in APIs like `api/get_tasks.php` and `api/get_calendar_tasks.php`.
---
CORE MODULES AND SCREENS
1. **Projects & Sub-Projects**
   - Projects have:
     - Types: **Major** and **Sub**.
     - Department ownership, start/end dates, status, and progress percentage.
   - Sub-projects link back to major projects and have their own tasks.
   - Department Head and Super Admin can see:
     - Counts of sub-projects.
     - For each project/sub-project: progress bar, task counts (done/total), and status badges.
   - At-risk projects table shows:
     - Project name, department, progress, delayed tasks count, deadline, with visual emphasis for risk.
2. **Tasks (Project Tasks)**
   - Tasks associated with projects are managed in `tasks.php` and multiple APIs.
   - Each task has:
     - Title, description, status, priority, due/start/end dates.
     - Assignment (single user via `assigned_to` plus optional `task_assignments` table).
     - Per-task chat thread.
   - Status lifecycle:
     - Pending, In Progress, Review/Under Review, Completed, Missed.
   - The system computes:
     - Per-project and per-department counts of tasks by status.
     - Monthly/weekly/yearly task performance for reporting.
3. **Direct Tasks (Standalone Tasks)**
   - Managed via `direct_tasks.php`, `api/get_tasks.php`, and `api/get_dept_head_stats.php`.
   - Direct tasks:
     - Have no `project_id` (NULL/0).
     - Are assigned primarily via `task_assignments` for flexible multi-assignee support.
   - Features on the Direct Tasks page:
     - Role-based filters:
       - **Scope** toggle for Department Head: My Tasks / Team Tasks / Other Tasks.
       - **Department** filter for Super Admin (filter by department).
     - Search, status filter, and priority filter.
     - Dual-layout:
       - Desktop table (sortable-style) with columns: Task Title, Assignee, Timeframe, Priority, Status, Actions.
       - Mobile card view with pagination.
     - Full-screen **chat modal** for each direct task, including attachments and mentions.
     - Pagination and counts (showing X–Y of Z) for both desktop and mobile views.
4. **Per-Task Chat & Mentions**
   - Chat is available for both project tasks and direct tasks.
   - Implemented via:
     - PHP pages: `direct_tasks.php`, `tasks.php`.
     - JS modules: `assets/js/chat.js`, `assets/js/chat-export.js`, and `assets/js/mention_notifications.js`.
     - APIs: `api/chat/*` and `api/get_chat_messages.php`, `api/send_chat_message.php`.
   - Capabilities:
     - Real-time-style threaded conversation per task.
     - @mentions of users with mention dropdown, backed by mention notification APIs.
     - File attachments (documents, images, archives) for chat messages.
     - Export options for chat:
       - Excel, PDF, Print (UI buttons and export menu).
   - Chat permissions:
     - Carefully restricted for direct tasks so only appropriate roles (creator, assignee, dept heads) have certain status-change abilities (see `api/get_task_for_chat.php`).
5. **Dashboards**
   - **Super Admin Dashboard (`dashboard.php`)**
     - Live stats bar for projects, departments, members, active project tasks, active direct tasks, completed direct tasks, missed tasks.
     - Tasks pending approval (Department Head assignments).
     - Department-wise project task view.
     - Recent direct tasks, up to 5 most recent.
     - At-risk projects with delayed tasks.
     - Data pulled from `api/get_dashboard_data.php` and `api/get_pending_approval_tasks.php`, rendered with dynamic JS.
   - **Employee Dashboard (`employee_dashboard.php`)**
     - For Department Heads:
       - “Department Command Centre” header and section.
       - Project & project-task stats (members, totals, done, outstanding, overdue).
       - Completion rate progress bar for project tasks.
       - Active/incomplete projects table.
       - Overdue accountability list by member.
       - Sub-projects table with progress, done/total tasks, status.
       - Direct tasks stats and completion rate.
       - Recent direct tasks panel.
       - (Charts for project/direct task performance were previously present but can be considered part of the design concept.)
     - For Team Members:
       - “My Work Overview” section with metrics for project and direct tasks (pending/completed/overdue).
       - Current performance score and scoring trends.
       - Project task and direct task tables scoped to the current user.
6. **Calendar View**
   - `calendar.php` uses FullCalendar and `api/get_calendar_tasks.php` to show:
     - Project and direct tasks on a unified monthly calendar.
   - Color coding:
     - Project tasks: blue.
     - Direct tasks: green.
   - Role-specific filters:
     - Department Head: scope toggle (My Tasks / Team Tasks / Other / External) influences which tasks appear.
     - Super Admin: department filter to view tasks per team/department.
   - Clicking an event opens a quick-view popup:
     - Shows title, status, type, assignee(s) (via `assigned_name`), and link to open the related chat.
7. **Performance Engine**
   - Performance scoring uses:
     - `performance_logs` table (and `classes/PerformanceEngine.php`).
     - Logged score changes for actions like completing tasks or missing deadlines.
   - Data is surfaced to:
     - Employee dashboard score widgets and trends.
     - Department stats and at-risk detection.
---
PWA CAPABILITIES
- **Offline Support**
  - `sw.js`, `offline.html`, and `assets/js/pwa-register.js` implement:
    - Caching of the core app shell (login, offline page).
    - Network-first strategy for HTML.
    - Cache-first for static CDN assets (Tailwind, jQuery, Chart.js, fonts).
    - Network-only for API calls to avoid stale data.
  - When offline, navigation falls back to a friendly offline page.
- **Add to Home Screen**
  - `manifest.json` defines:
    - App name, icons, theme color, and display mode.
  - `pwa-icons/icon.php` generates PWA icons dynamically.
- **Push Notifications**
  - `includes/pwa_config.php`, `api/push_subscribe.php`, `api/get_vapid_public.php`, `setup_vapid_keys.php`.
  - Notifications are designed to be triggered for:
    - New chat messages.
    - New task assignments.
    - Approvals, etc.
  - Uses VAPID keys stored in a local config file (`pwa_config.local.php`), not committed to git.
---
SECURITY / PRODUCTION PRACTICES
- `includes/app_env.php`:
  - If `.production` file exists in root, production mode is active:
    - `display_errors` off, `log_errors` on.
- `.htaccess` rules:
  - Block access to `debug_*.php`, `test_*.php`, `*.log`, `*.local.php`, and some verification scripts.
  - Protect `logs/` and `api/` as needed.
- Role-based middleware:
  - `includes/auth_middleware.php` enforces user login and role requirements.
  - Many APIs call `require_role([...])` to lock down functionality.
---
With this context, when I ask you to generate documentation, UX copy, code enhancements, or architecture diagrams, **assume this is the current feature set and behavior of Zouetech-PMS**.
2. Prompt for ElevenLabs audio narration
Below is a ready-to-use narration prompt/script you can paste into ElevenLabs (or any TTS) to generate an audio overview of the project. You can adjust length or tone to taste.

You are a professional product narrator. 
Read the following script clearly, with an engaging but calm tone, suitable for a product walkthrough video for engineering and management stakeholders. 
Pause very briefly between sections.
---
Welcome to Zouetech P M S — a role-based project and task management system designed for modern organizations.
Zouetech P M S is a web-based application built on PHP and MySQL, with a clean, modern interface powered by Tailwind-style utility classes and Chart dot J S. It also ships as a Progressive Web App, meaning users can add it to their home screen, work offline with a cached app shell, and receive push notifications when important events occur.
The system is centered around three core roles: Super Admin, Department Head, and Team Member.
Super Admins work from a Command Center dashboard. 
They see global statistics across the entire organization: 
the total number of projects, departments, and members, 
the count of active project tasks and active direct tasks, 
how many direct tasks have been completed, and how many have been missed.
Super Admins also see a panel of tasks pending approval from Department Heads, a department-wise breakdown of project tasks, an at-risk projects table highlighting delayed work, and a feed of the five most recent direct tasks. All of this data is delivered via structured A P I endpoints, rendered as live, interactive cards and tables.
Department Heads use the Employee Dashboard as a Department Command Centre. 
Here, they get a focused view of their department’s performance.
They can see how many team members are currently assigned to project tasks, how many project tasks exist in total, how many have been completed, how many are outstanding, and how many are overdue. 
A completion rate bar makes it easy to assess how close the department is to clearing its project workload.
The dashboard also lists active and incomplete major projects, showing progress percentages, deadlines, and whether a project is overdue. 
Sub-projects are summarized with their parent projects, done versus total task counts, and status badges.
An Overdue Accountability section surfaces which team members have the highest count of overdue tasks, along with visual bars to compare them at a glance.
For direct, non-project work, Department Heads see their department’s direct task statistics: total, done, outstanding, and overdue, along with a completion rate bar. 
They also get a curated list of recent direct tasks in the department, including assignee names, departments, priorities, creators, and status.
Team Members see the same Employee Dashboard as their personal “My Desk.” 
They are shown their current performance score, along with monthly and weekly score trends based on entries in the performance logs. 
The dashboard highlights pending and completed project tasks and direct tasks, as well as counts of overdue items in each category. 
Separate tables and lists give each user a clear picture of what’s on their plate and what’s at risk.
Zouetech P M S distinguishes between project tasks and direct tasks.
Project tasks belong to a project or sub-project, with start and end dates, due dates, statuses such as Pending, In Progress, Review, Completed, and Missed, and progress aggregated into project and department level metrics.
Direct tasks, on the other hand, are standalone responsibilities that do not belong to a project. 
They are managed primarily through a dedicated Direct Tasks page. 
This page supports powerful filtering and scoping.
Department Heads can toggle between “My Tasks,” “Team Tasks,” and “Other Tasks,” while Super Admins can filter by department. 
All users get search, status, and priority filters, plus both a desktop table view and a mobile card view with pagination.
Every task — both project and direct — includes a built-in chat channel. 
Users can send messages, attach files, and use at-mentions to notify specific colleagues. 
The chat interface includes an export menu, allowing users to export conversations to Excel or PDF, or send them to print.
Mentions and chat activity feed into a notification system. 
Using Progressive Web App push subscriptions, the system can be extended to send push notifications when someone is assigned a task, mentioned in chat, or when a key workflow event occurs.
The Calendar view brings everything together visually. 
Using Full Calendar, the app shows project tasks and direct tasks in a unified calendar, with distinct colors for each type. 
Department Heads can scope the calendar to their own tasks, their team’s tasks, or external tasks. 
Super Admins can filter by department. 
Clicking a calendar event opens a quick-view popup with task title, status, type, assignee name, and a direct link into the associated chat thread.
Behind the scenes, a performance engine tracks score changes for user actions and stores them in a performance logs table. 
This data powers user-level scorecards and department-level indicators, from weekly trends to at-risk projects and overdue counts.
Security and production-readiness are also addressed. 
An app environment file supports a production mode controlled by a dot-production marker, turning off display errors and enabling error logging. 
Dot H T access rules prevent direct access to debug and test scripts, log files, and sensitive local configuration like P W A VAPID keys.
In short, Zouetech P M S is a comprehensive, role-aware system for managing projects, direct tasks, team communication, and performance in one cohesive, modern web interface, complete with offline support and push-ready notifications.
End of script.
# zoutech-pms
# zouetech-pms
