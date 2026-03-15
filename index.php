<?php
require_once 'includes/config.php';

$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'home';

$allowed_pages = ['home', 'login', 'register', 'logout', 'booking', 'my-tickets', 'track', 'profile', 'ticket-detail'];

if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

if ($page === 'logout') {
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pages_require_login = ['booking', 'my-tickets', 'profile', 'ticket-detail'];
if (in_array($page, $pages_require_login) && !is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php?page=login&redirect=' . $page);
    exit;
}

if (($page === 'login' || $page === 'register') && is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . BASE_URL . '/admin/index.php');
    } else {
        header('Location: ' . BASE_URL . '/index.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Wisata Alam Gorontalo</title>
    <meta name="description" content="Booking tiket wisata Danau Paisupok - Destinasi wisata alam terbaik di Gorontalo">

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Iconify -->
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        primary: {
                            DEFAULT: '#f54518',
                            50: '#fff4f1',
                            100: '#ffe4dc',
                            200: '#ffcdc0',
                            300: '#ffaa94',
                            400: '#ff7856',
                            500: '#f54518',
                            600: '#e23210',
                            700: '#bc260d',
                            800: '#9a2211',
                            900: '#7e2115',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        :root {
            --primary: #f54518;
            --primary-light: #ff7856;
            --bg: #ffffff;
            --bg-card: #f8f9fa;
            --bg-secondary: #f1f3f5;
            --text: #111827;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 4px 24px rgba(0,0,0,0.08);
        }

        .dark {
            --bg: #0f0f0f;
            --bg-card: #1a1a1a;
            --bg-secondary: #232323;
            --text: #f3f4f6;
            --text-muted: #9ca3af;
            --border: #2d2d2d;
            --shadow: 0 4px 24px rgba(0,0,0,0.4);
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            transition: background-color 0.3s, color 0.3s;
        }

        /* Mobile Frame Effect */
        .mobile-frame {
            max-width: 430px;
            margin: 0 auto;
            min-height: 100vh;
            background: var(--bg);
            position: relative;
            box-shadow: 0 0 60px rgba(0,0,0,0.15);
        }

        @media (min-width: 640px) {
            body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); padding: 20px 0; }
            .mobile-frame { border-radius: 40px; overflow: hidden; }
        }

        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 430px;
            background: var(--bg);
            border-top: 1px solid var(--border);
            z-index: 50;
            padding: 8px 0 16px;
        }

        .nav-item.active { color: var(--primary); }
        .nav-item.active .nav-icon { background: rgba(245,69,24,0.12); }

        /* Header */
        .app-header {
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 40;
        }

        /* Card */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        /* Gradient Hero */
        .hero-gradient {
            background: linear-gradient(135deg, #f54518 0%, #ff7856 50%, #ff9a6c 100%);
        }

        /* Ticket Card */
        .ticket-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        .ticket-card::before {
            content: '';
            position: absolute;
            left: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background: var(--bg);
            border-radius: 50%;
            border: 1px solid var(--border);
        }

        .ticket-card::after {
            content: '';
            position: absolute;
            right: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background: var(--bg);
            border-radius: 50%;
            border: 1px solid var(--border);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-completed { background: #dbeafe; color: #1e40af; }

        .dark .status-pending { background: rgba(245,158,11,0.2); color: #fbbf24; }
        .dark .status-confirmed { background: rgba(16,185,129,0.2); color: #34d399; }
        .dark .status-cancelled { background: rgba(239,68,68,0.2); color: #f87171; }
        .dark .status-completed { background: rgba(59,130,246,0.2); color: #60a5fa; }

        /* Input */
        .form-input {
            width: 100%;
            background: var(--bg-secondary);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            color: var(--text);
            font-size: 14px;
            transition: all 0.2s;
            outline: none;
        }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(245,69,24,0.12); }

        .btn-primary {
            background: linear-gradient(135deg, #f54518, #ff7856);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(245,69,24,0.35);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,69,24,0.45); }
        .btn-primary:active { transform: translateY(0); }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-outline:hover { background: rgba(245,69,24,0.08); }

        /* Ripple Effect */
        .ripple { position: relative; overflow: hidden; }
        .ripple::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.3);
            transform: scale(0);
            opacity: 0;
            top: 50%;
            left: 50%;
            margin: -50px 0 0 -50px;
        }
        .ripple:active::after { animation: ripple 0.4s ease-out; }
        @keyframes ripple { to { transform: scale(4); opacity: 0; } }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999;
        }

        .spinner {
            width: 40px; height: 40px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Fade animations */
        .fade-in { animation: fadeIn 0.4s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        /* Page content padding for bottom nav */
        .page-content { padding-bottom: 90px; }

        /* Quantity Stepper */
        .qty-stepper { display: flex; align-items: center; gap: 12px; }
        .qty-btn {
            width: 36px; height: 36px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: var(--bg-card);
            color: var(--text);
            font-size: 18px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .qty-btn:hover { border-color: var(--primary); color: var(--primary); }

        /* Date picker custom */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.5);
            cursor: pointer;
        }
        .dark input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.8); }
    </style>
</head>
<body>
<div class="mobile-frame">

<?php
// Render page based on $page variable
switch ($page) {
    case 'login':
    case 'register':
        include 'user/auth.php';
        break;
    case 'booking':
        include 'user/booking.php';
        break;
    case 'my-tickets':
        include 'user/my_tickets.php';
        break;
    case 'track':
        include 'user/track.php';
        break;
    case 'ticket-detail':
        include 'user/ticket_detail.php';
        break;
    case 'profile':
        include 'user/profile.php';
        break;
    default:
        include 'user/home.php';
        break;
}
?>

</div>

<!-- Global Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display:none;">
    <div class="text-center">
        <div class="spinner mx-auto mb-3"></div>
        <p class="text-white text-sm font-medium">Memproses...</p>
    </div>
</div>

<script>
// Theme Management
const html = document.documentElement;
const savedTheme = localStorage.getItem('theme') || 'light';
if (savedTheme === 'dark') html.classList.add('dark');

function toggleTheme() {
    html.classList.toggle('dark');
    localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
    updateThemeIcon();
}

function updateThemeIcon() {
    const isDark = html.classList.contains('dark');
    const icons = document.querySelectorAll('.theme-icon');
    icons.forEach(icon => {
        icon.setAttribute('icon', isDark ? 'mdi:weather-sunny' : 'mdi:weather-night');
    });
}

$(document).ready(function() {
    updateThemeIcon();

    // Active nav highlight
    const page = '<?php echo $page; ?>';
    const navMap = { 'home': 0, 'booking': 1, 'my-tickets': 2, 'track': 3, 'profile': 4 };
    const idx = navMap[page] ?? 0;
    $('.nav-item').eq(idx).addClass('active');
});

// Loading helpers
function showLoading() { $('#loadingOverlay').fadeIn(200); }
function hideLoading() { $('#loadingOverlay').fadeOut(200); }

// Toast notification
function showToast(message, type = 'success') {
    const icons = { success: 'mdi:check-circle', error: 'mdi:close-circle', warning: 'mdi:alert', info: 'mdi:information' };
    const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };

    const toast = $(`
        <div class="fixed top-4 left-1/2 transform -translate-x-1/2 z-[9999] flex items-center gap-3 px-4 py-3 rounded-2xl shadow-2xl text-white text-sm font-semibold max-w-[360px] w-full" 
             style="background:${colors[type]}; animation: slideDown 0.3s ease;">
            <iconify-icon icon="${icons[type]}" width="20"></iconify-icon>
            <span>${message}</span>
        </div>
    `);
    $('body').append(toast);
    setTimeout(() => toast.fadeOut(300, () => toast.remove()), 3000);
}

$('<style>.fixed.top-4 { animation: slideDown 0.3s ease; } @keyframes slideDown { from{opacity:0;transform:translateX(-50%) translateY(-20px)}to{opacity:1;transform:translateX(-50%) translateY(0)} }</style>').appendTo('head');
</script>
</body>
</html>