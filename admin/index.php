<?php
// admin/index.php
require_once '../includes/config.php';
require_login();
require_admin();

$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'dashboard';
$allowed = ['dashboard','bookings','tickets','users','categories','settings','reports'];
if (!in_array($page, $allowed)) $page = 'dashboard';

$conn = db_connect();
$admin = $conn->query("SELECT * FROM users WHERE id=".$_SESSION['user_id'])->fetch_assoc();
$conn->close();

$page_titles = [
    'dashboard' => 'Dashboard',
    'bookings' => 'Manajemen Pemesanan',
    'tickets' => 'Manajemen Tiket',
    'users' => 'Manajemen Pengguna',
    'categories' => 'Kategori Tiket',
    'settings' => 'Pengaturan',
    'reports' => 'Laporan',
];
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300..800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        primary: { DEFAULT: '#f54518', light: '#ff7856', dark: '#bc260d' }
                    }
                }
            }
        }
    </script>

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        :root {
            --primary: #f54518;
            --bg: #f8f9fb;
            --bg-card: #ffffff;
            --bg-sidebar: #111827;
            --bg-sidebar-hover: rgba(245,69,24,0.12);
            --text: #111827;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
        }

        .dark {
            --bg: #0a0a0f;
            --bg-card: #111118;
            --bg-sidebar: #07070c;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #1e2029;
            --shadow: 0 1px 3px rgba(0,0,0,0.4);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.3);
        }

        body { background: var(--bg); color: var(--text); transition: all 0.3s; }

        /* Sidebar */
        .admin-sidebar {
            background: var(--bg-sidebar);
            width: 260px;
            min-height: 100vh;
            position: fixed;
            left: 0; top: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
        }

        @media (max-width: 1024px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0 !important; }
        }

        .admin-main { margin-left: 260px; transition: margin-left 0.3s; }

        .sidebar-logo {
            background: linear-gradient(135deg, rgba(245,69,24,0.15), rgba(245,69,24,0.05));
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            border-radius: 12px;
            margin: 3px 12px;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.85);
        }

        .nav-link.active {
            background: linear-gradient(135deg, rgba(245,69,24,0.2), rgba(245,69,24,0.08));
            color: #ff7856;
            font-weight: 700;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: #f54518;
            border-radius: 0 4px 4px 0;
        }

        .nav-group-label {
            color: rgba(255,255,255,0.3);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            padding: 16px 20px 6px;
            text-transform: uppercase;
        }

        /* Top Bar */
        .admin-topbar {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 40;
        }

        /* Cards */
        .admin-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

        /* Form */
        .admin-input {
            width: 100%;
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 10px 14px;
            color: var(--text);
            font-size: 13.5px;
            transition: all 0.2s;
            outline: none;
        }
        .admin-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(245,69,24,0.1); }

        .admin-btn-primary {
            background: linear-gradient(135deg, #f54518, #ff7856);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(245,69,24,0.3);
        }
        .admin-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(245,69,24,0.4); }

        .admin-btn-sm {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
        }
        .btn-edit { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .btn-delete { background: rgba(239,68,68,0.1); color: #ef4444; }
        .btn-confirm { background: rgba(16,185,129,0.1); color: #10b981; }
        .btn-view { background: rgba(139,92,246,0.1); color: #8b5cf6; }

        /* DataTable Dark override */
        .dark .dataTables_wrapper { color: var(--text); }
        .dark table.dataTable thead th { background: var(--bg); color: var(--text); border-color: var(--border); }
        .dark table.dataTable tbody td { background: var(--bg-card); border-color: var(--border); color: var(--text); }
        .dark .dataTables_filter input, .dark .dataTables_length select { background: var(--bg); border-color: var(--border); color: var(--text); border-radius: 8px; padding: 6px 10px; }
        table.dataTable thead th, table.dataTable tbody td { font-size: 13px; }

        /* Status badges */
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-completed { background: #dbeafe; color: #1e40af; }
        .dark .badge-pending { background: rgba(245,158,11,0.2); color: #fbbf24; }
        .dark .badge-confirmed { background: rgba(16,185,129,0.2); color: #34d399; }
        .dark .badge-cancelled { background: rgba(239,68,68,0.2); color: #f87171; }
        .dark .badge-completed { background: rgba(59,130,246,0.2); color: #60a5fa; }

        /* Overlay */
        .sidebar-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
            display: none;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        /* Modal */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            display: flex; align-items: center; justify-content: center;
            z-index: 200;
            padding: 16px;
        }
        .modal-box {
            background: var(--bg-card);
            border-radius: 20px;
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 80px rgba(0,0,0,0.25);
            border: 1px solid var(--border);
        }

        .spinner { width: 36px; height: 36px; border: 3px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .fade-in { animation: fadeIn 0.35s ease; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)} }
    </style>
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <!-- Logo -->
    <div class="sidebar-logo p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#f54518,#ff9a6c);">
                <iconify-icon icon="mdi:waves" width="22" style="color:white;"></iconify-icon>
            </div>
            <div>
                <div class="text-white font-extrabold text-sm">Danau Paisupok</div>
                <div class="text-xs" style="color:rgba(255,255,255,0.4);">Admin Panel</div>
            </div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto py-3">
        <div class="nav-group-label">Utama</div>
        <?php
        $nav_items = [
            ['dashboard', 'mdi:view-dashboard-outline', 'Dashboard'],
            ['bookings', 'mdi:calendar-clock-outline', 'Pemesanan'],
            ['tickets', 'mdi:ticket-outline', 'Tiket'],
        ];
        foreach ($nav_items as $n):
        ?>
        <a href="?page=<?php echo $n[0]; ?>" class="nav-link <?php echo $page === $n[0] ? 'active' : ''; ?>">
            <iconify-icon icon="<?php echo $n[1]; ?>" width="18"></iconify-icon>
            <?php echo $n[2]; ?>
        </a>
        <?php endforeach; ?>

        <div class="nav-group-label">Pengelolaan</div>
        <?php
        $nav2 = [
            ['categories', 'mdi:tag-multiple-outline', 'Kategori Tiket'],
            ['users', 'mdi:account-multiple-outline', 'Pengguna'],
            ['reports', 'mdi:chart-bar', 'Laporan'],
            ['settings', 'mdi:cog-outline', 'Pengaturan'],
        ];
        foreach ($nav2 as $n):
        ?>
        <a href="?page=<?php echo $n[0]; ?>" class="nav-link <?php echo $page === $n[0] ? 'active' : ''; ?>">
            <iconify-icon icon="<?php echo $n[1]; ?>" width="18"></iconify-icon>
            <?php echo $n[2]; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- User Info -->
    <div class="p-4 border-t" style="border-color:rgba(255,255,255,0.06);">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(245,69,24,0.2);">
                <iconify-icon icon="mdi:shield-account" width="18" style="color:#f54518;"></iconify-icon>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-white font-semibold text-sm truncate"><?php echo htmlspecialchars($admin['name']); ?></div>
                <div class="text-xs truncate" style="color:rgba(255,255,255,0.35);">Administrator</div>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo BASE_URL; ?>/index.php" target="_blank" class="flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg text-xs font-semibold transition-all" style="background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.5);">
                <iconify-icon icon="mdi:open-in-new" width="13"></iconify-icon>
                Site
            </a>
            <a href="<?php echo BASE_URL; ?>/index.php?page=logout" class="flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg text-xs font-semibold transition-all" style="background:rgba(239,68,68,0.12);color:#ef4444;">
                <iconify-icon icon="mdi:logout" width="13"></iconify-icon>
                Logout
            </a>
        </div>
    </div>
</aside>

<!-- Main Content -->
<div class="admin-main">

    <!-- Topbar -->
    <header class="admin-topbar px-5 py-3.5">
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg);">
                <iconify-icon icon="mdi:menu" width="22" style="color:var(--text);"></iconify-icon>
            </button>
            <div>
                <h1 class="font-bold text-base" style="color:var(--text);"><?php echo $page_titles[$page]; ?></h1>
                <div class="text-xs" style="color:var(--text-muted);">Danau Paisupok - Admin Panel</div>
            </div>
            <div class="ml-auto flex items-center gap-3">
                <button onclick="toggleTheme()" class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg);border:1px solid var(--border);">
                    <iconify-icon class="theme-icon" icon="mdi:weather-night" width="18" style="color:var(--text-muted);"></iconify-icon>
                </button>
                <a href="?page=bookings" class="relative w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg);border:1px solid var(--border);">
                    <iconify-icon icon="mdi:bell-outline" width="18" style="color:var(--text-muted);"></iconify-icon>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full" style="background:#f54518;" id="notif-dot"></span>
                </a>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <div class="p-5">
        <?php
        $page_file = __DIR__ . '/pages/' . $page . '.php';
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<div class="text-center py-20 text-gray-400">Halaman tidak ditemukan</div>';
        }
        ?>
    </div>
</div>

<!-- Global Loading -->
<div id="loadingOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:none;align-items:center;justify-content:center;">
    <div class="text-center">
        <div class="spinner mx-auto mb-3"></div>
        <p class="text-white text-sm font-medium">Memproses...</p>
    </div>
</div>

<script>
// Theme
const html = document.documentElement;
const saved = localStorage.getItem('adminTheme') || 'light';
if (saved === 'dark') html.classList.add('dark');

function toggleTheme() {
    html.classList.toggle('dark');
    localStorage.setItem('adminTheme', html.classList.contains('dark') ? 'dark' : 'light');
    updateThemeIcon();
}

function updateThemeIcon() {
    const isDark = html.classList.contains('dark');
    document.querySelectorAll('.theme-icon').forEach(i => i.setAttribute('icon', isDark ? 'mdi:weather-sunny' : 'mdi:weather-night'));
}

function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('open');
    overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
}

function showLoading() { $('#loadingOverlay').css('display','flex'); }
function hideLoading() { $('#loadingOverlay').css('display','none'); }

function showToast(message, type = 'success') {
    const colors = { success:'#10b981', error:'#ef4444', warning:'#f59e0b', info:'#3b82f6' };
    const icons = { success:'mdi:check-circle', error:'mdi:close-circle', warning:'mdi:alert', info:'mdi:information' };
    const toast = $(`<div style="position:fixed;top:20px;right:20px;z-index:99999;background:${colors[type]};color:white;padding:12px 18px;border-radius:12px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(0,0,0,0.2);animation:slideIn 0.3s ease;min-width:280px;">
        <iconify-icon icon="${icons[type]}" width="18"></iconify-icon>
        <span>${message}</span>
    </div>`);
    $('body').append(toast);
    setTimeout(() => toast.fadeOut(300, () => toast.remove()), 3500);
}
$('<style>@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}</style>').appendTo('head');

$(document).ready(function() {
    updateThemeIcon();

    // Check pending bookings for notification dot
    $.ajax({ url: '../ajax/admin.php', data: { action: 'get_pending_count' }, dataType: 'json',
        success: function(res) {
            if (!res.count || res.count == 0) $('#notif-dot').hide();
        }
    });
});
</script>
</body>
</html>