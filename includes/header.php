<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1d4ed8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Zouetech-PMS">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="pwa-icons/icon.png">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Zouetech-PMS' : 'Zouetech-PMS'; ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gray: {
                            950: '#0b1120',
                            900: '#111827',
                            800: '#1f2937',
                            700: '#334155',
                        },
                        cyan: {
                            500: '#2563eb',
                            400: '#60a5fa',
                            glow: 'rgba(37, 99, 235, 0.35)',
                        },
                        indigo: {
                            500: '#1d4ed8',
                            400: '#3b82f6',
                            glow: 'rgba(29, 78, 216, 0.35)',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        tech: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    boxShadow: {
                        'neon-cyan': '0 8px 24px rgba(37, 99, 235, 0.2)',
                        'neon-indigo': '0 8px 24px rgba(29, 78, 216, 0.2)',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --surface-0: #0b1120;
            --surface-1: #111827;
            --surface-2: #1f2937;
            --border-soft: rgba(148, 163, 184, 0.22);
            --text-primary: #f8fafc;
            --text-muted: #94a3b8;
            --brand: #2563eb;
            --brand-soft: rgba(37, 99, 235, 0.16);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top right, rgba(37, 99, 235, 0.1), transparent 40%), var(--surface-0);
            color: var(--text-primary);
        }
        h1, h2, h3, h4, .font-tech { font-family: 'Inter', sans-serif; letter-spacing: -0.01em; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        /* Subtle texture background */
        .bg-grid {
            background-size: 34px 34px;
            background-image: 
                linear-gradient(to right, rgba(148, 163, 184, 0.04) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(148, 163, 184, 0.04) 1px, transparent 1px);
        }
        
        /* Surface card styling */
        .glass {
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.88), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--border-soft);
            box-shadow: 0 18px 40px rgba(2, 6, 23, 0.35);
            border-radius: 12px;
        }

        .glass:hover {
            border-color: rgba(96, 165, 250, 0.36);
        }
        
        .sharp-gpu {
            transform: translateZ(0);
            backface-visibility: hidden;
            perspective: 1000px;
            will-change: transform;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }

        .surface-panel {
            background: rgba(15, 23, 42, 0.72);
            border: 1px solid var(--border-soft);
            border-radius: 0.875rem;
        }

        button, a, input, select, textarea {
            transition: all 0.18s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: rgba(96, 165, 250, 0.55);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-6px); }
            100% { transform: translateY(0px); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gray-950 text-white font-sans antialiased bg-grid selection:bg-cyan-500/30 overflow-x-hidden">
    <div class="fixed top-0 left-0 w-full h-full pointer-events-none z-0 overflow-hidden">
        <div class="absolute top-[-12%] left-[-8%] w-[36%] h-[36%] bg-blue-500/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-12%] right-[-10%] w-[38%] h-[38%] bg-indigo-500/10 rounded-full blur-[130px]"></div>
    </div>

    <div class="flex h-screen overflow-hidden relative">
        <!-- Sidebar Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden transition-opacity duration-300 opacity-0"></div>
