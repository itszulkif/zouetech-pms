<?php
// login.php
require_once 'db_connect.php';

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $database = new Database();
        $db = $database->connect();

        $stmt = $db->prepare("
            SELECT u.id, u.full_name, u.role, u.department_id, u.password_hash, u.avatar_url,
                   d.name AS department_name
            FROM users u
            LEFT JOIN departments d ON d.id = u.department_id
            WHERE u.email = ?
        ");
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                // Login Success
                $_SESSION['user_id']         = $user['id'];
                $_SESSION['full_name']        = $user['full_name'];
                $_SESSION['role']             = $user['role'];
                $_SESSION['department_id']    = $user['department_id'];    // Required for dept-based data isolation
                $_SESSION['department_name']  = $user['department_name'] ?? '';  // For display in headers
                $_SESSION['avatar_url']       = $user['avatar_url'] ?? '';
                
                if ($user['role'] === 'Department Head') {
                    $error = "Department Head access has been removed. Please contact Super Admin.";
                    $stmt->close();
                    $db->close();
                    session_unset();
                    session_destroy();
                    goto render_login;
                }

                // Redirect based on role
                if (in_array($user['role'], ['Team Member', 'Team Lead'], true)) {
                    header("Location: employee_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No account found with this email.";
        }
        $stmt->close();
    }
}
render_login:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1d4ed8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Zouetech-PMS">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="pwa-icons/icon.png">
    <title>Login - Zouetech-PMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                        tech: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        cyan: { 400: '#60a5fa', 500: '#2563eb', 900: '#1e3a8a' },
                        slate: { 800: '#1e293b', 900: '#0f172a' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-white h-screen flex items-center justify-center font-sans relative overflow-hidden">
    
    <!-- Background Effects -->
    <div class="absolute inset-0 z-0">
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] bg-blue-900/30 rounded-full blur-[110px]"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[50%] h-[50%] bg-indigo-900/25 rounded-full blur-[120px]"></div>
    </div>

    <div class="relative z-10 w-full max-w-md p-8 bg-slate-900/70 backdrop-blur-xl rounded-2xl border border-slate-700/70 shadow-[0_20px_60px_rgba(2,6,23,0.5)]">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-semibold font-tech tracking-tight text-blue-300 mb-2">ZOUETECH-PMS</h1>
            <p class="text-slate-400 text-sm">Project Management System Access</p>
        </div>

        <?php if($error): ?>
            <div class="mb-4 p-3 bg-red-500/10 border border-red-500/50 rounded text-red-400 text-sm text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-6">
                <label class="block text-xs font-mono text-blue-300 mb-2 uppercase tracking-widest">Email Address</label>
                <input type="email" name="email" required 
                    class="w-full bg-slate-950/70 border border-slate-600 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-500/40 transition-all placeholder-slate-500"
                    placeholder="name@zouetech.org">
            </div>

            <div class="mb-8">
                <label class="block text-xs font-mono text-blue-300 mb-2 uppercase tracking-widest">Password</label>
                <input type="password" name="password" required 
                    class="w-full bg-slate-950/70 border border-slate-600 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-500/40 transition-all placeholder-slate-500"
                    placeholder="********">
            </div>

            <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 rounded-lg transition-all transform hover:-translate-y-0.5 shadow-[0_12px_28px_rgba(37,99,235,0.35)]">
                Sign In
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-500 font-mono">
            Secure connection enabled
        </div>
    </div>

</body>
<script src="assets/js/pwa-register.js?v=1.0.0"></script>
</html>

