<?php
// user/home.php
require_once __DIR__ . '/../includes/config.php';
$conn = db_connect();

// Get ticket categories
$cats = $conn->query("SELECT * FROM ticket_categories WHERE is_active=1 ORDER BY price ASC");

// Get announcements
$announcements = $conn->query("SELECT * FROM announcements WHERE is_active=1 ORDER BY created_at DESC LIMIT 3");

// Get settings
$settings_q = $conn->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($s = $settings_q->fetch_assoc()) $settings[$s['setting_key']] = $s['setting_value'];
$conn->close();
?>

<!-- App Header -->
<header class="app-header px-5 py-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#f54518,#ff9a6c);">
                <iconify-icon icon="mdi:waves" width="20" style="color:white;"></iconify-icon>
            </div>
            <div>
                <h1 class="font-bold text-sm leading-tight" style="color:var(--text);">Danau Paisupok</h1>
                <p class="text-xs" style="color:var(--text-muted);">Wisata Alam Gorontalo</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="toggleTheme()" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all" style="background:var(--bg-secondary);">
                <iconify-icon class="theme-icon" icon="mdi:weather-night" width="18" style="color:var(--text-muted);"></iconify-icon>
            </button>
            <?php if (is_logged_in()): ?>
            <a href="index.php?page=profile" class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg-secondary);">
                <iconify-icon icon="mdi:account" width="18" style="color:var(--text-muted);"></iconify-icon>
            </a>
            <?php else: ?>
            <a href="index.php?page=login" class="btn-primary text-xs px-4 py-2" style="border-radius:10px;">Masuk</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Page Content -->
<div class="page-content overflow-y-auto">

    <!-- Hero Banner -->
    <div class="mx-4 mt-4 rounded-3xl overflow-hidden relative" style="min-height:200px; background:linear-gradient(135deg,#f54518 0%,#ff6b35 50%,#ff9a6c 100%);">
        <!-- Decorative circles -->
        <div class="absolute top-0 right-0 w-40 h-40 rounded-full opacity-20" style="background:white; transform:translate(30%,-30%);"></div>
        <div class="absolute bottom-0 left-0 w-28 h-28 rounded-full opacity-10" style="background:white; transform:translate(-30%,30%);"></div>

        <!-- Wave decoration -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 430 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="height:60px;width:100%;opacity:0.15;">
                <path d="M0,30 C100,60 200,0 300,30 C380,55 400,20 430,30 L430,60 L0,60 Z" fill="white"/>
            </svg>
        </div>

        <div class="relative p-6 pt-7">
            <div class="flex items-center gap-2 mb-2">
                <iconify-icon icon="mdi:map-marker" width="16" style="color:rgba(255,255,255,0.8);"></iconify-icon>
                <span class="text-xs" style="color:rgba(255,255,255,0.8);"><?php echo htmlspecialchars($settings['location'] ?? 'Gorontalo'); ?></span>
            </div>
            <h2 class="text-white font-extrabold text-2xl leading-tight mb-1">Selamat Datang<br>di Danau Paisupok</h2>
            <p class="text-sm mb-5" style="color:rgba(255,255,255,0.85);">Buka pukul <?php echo $settings['open_time'] ?? '07:00'; ?> - <?php echo $settings['close_time'] ?? '18:00'; ?> WITA</p>
            <a href="index.php?page=booking" class="inline-flex items-center gap-2 bg-white font-bold text-sm px-5 py-2.5 rounded-xl shadow-lg ripple" style="color:var(--primary); border-radius:12px;">
                <iconify-icon icon="mdi:ticket-outline" width="18"></iconify-icon>
                Pesan Tiket Sekarang
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-3 gap-3 px-4 mt-5">
        <div class="card p-3 text-center">
            <iconify-icon icon="mdi:account-group" width="22" style="color:var(--primary);" class="mb-1"></iconify-icon>
            <div class="font-bold text-sm" style="color:var(--text);">10K+</div>
            <div class="text-xs" style="color:var(--text-muted);">Pengunjung</div>
        </div>
        <div class="card p-3 text-center">
            <iconify-icon icon="mdi:star" width="22" style="color:#f59e0b;" class="mb-1"></iconify-icon>
            <div class="font-bold text-sm" style="color:var(--text);">4.9/5</div>
            <div class="text-xs" style="color:var(--text-muted);">Rating</div>
        </div>
        <div class="card p-3 text-center">
            <iconify-icon icon="mdi:camera-outline" width="22" style="color:#3b82f6;" class="mb-1"></iconify-icon>
            <div class="font-bold text-sm" style="color:var(--text);">20+</div>
            <div class="text-xs" style="color:var(--text-muted);">Spot Foto</div>
        </div>
    </div>

    <!-- Announcements -->
    <?php if ($announcements->num_rows > 0): ?>
    <div class="px-4 mt-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-base" style="color:var(--text);">Pengumuman</h3>
        </div>
        <div class="space-y-2" id="announcements-list">
            <?php while ($ann = $announcements->fetch_assoc()):
                $ann_colors = ['info'=>'#3b82f6','warning'=>'#f59e0b','success'=>'#10b981','danger'=>'#ef4444'];
                $ann_icons = ['info'=>'mdi:information-outline','warning'=>'mdi:alert-outline','success'=>'mdi:check-circle-outline','danger'=>'mdi:close-circle-outline'];
                $color = $ann_colors[$ann['type']] ?? '#3b82f6';
                $icon = $ann_icons[$ann['type']] ?? 'mdi:information-outline';
            ?>
            <div class="card p-3 flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:<?php echo $color; ?>20;">
                    <iconify-icon icon="<?php echo $icon; ?>" width="18" style="color:<?php echo $color; ?>;"></iconify-icon>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm truncate" style="color:var(--text);"><?php echo htmlspecialchars($ann['title']); ?></div>
                    <div class="text-xs mt-0.5 line-clamp-2" style="color:var(--text-muted);"><?php echo htmlspecialchars($ann['content']); ?></div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ticket Categories -->
    <div class="px-4 mt-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-base" style="color:var(--text);">Jenis Tiket</h3>
            <a href="index.php?page=booking" class="text-xs font-semibold" style="color:var(--primary);">Pesan Tiket</a>
        </div>
        <div class="space-y-3">
            <?php while ($cat = $cats->fetch_assoc()): ?>
            <div class="card p-4 flex items-center gap-4 ripple cursor-pointer" onclick="window.location='index.php?page=booking&cat=<?php echo $cat['id']; ?>'">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:<?php echo $cat['color']; ?>20;">
                    <iconify-icon icon="<?php echo htmlspecialchars($cat['icon']); ?>" width="24" style="color:<?php echo htmlspecialchars($cat['color']); ?>;"></iconify-icon>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-sm" style="color:var(--text);"><?php echo htmlspecialchars($cat['name']); ?></div>
                    <div class="text-xs mt-0.5 truncate" style="color:var(--text-muted);"><?php echo htmlspecialchars($cat['description']); ?></div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="font-extrabold text-sm" style="color:var(--primary);"><?php echo format_rupiah($cat['price']); ?></div>
                    <div class="text-xs" style="color:var(--text-muted);">/orang</div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Track Tiket -->
    <div class="px-4 mt-5">
        <div class="card p-5" style="background:linear-gradient(135deg,rgba(245,69,24,0.06),rgba(245,69,24,0.02));">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(245,69,24,0.12);">
                    <iconify-icon icon="mdi:magnify-scan" width="22" style="color:var(--primary);"></iconify-icon>
                </div>
                <div>
                    <div class="font-bold text-sm" style="color:var(--text);">Lacak Tiket Anda</div>
                    <div class="text-xs" style="color:var(--text-muted);">Masukkan kode booking</div>
                </div>
            </div>
            <div class="flex gap-2">
                <input type="text" id="quickTrackCode" placeholder="Contoh: BKG-20241201-AB1234" class="form-input flex-1 text-xs">
                <button onclick="quickTrack()" class="btn-primary px-4 py-2 flex-shrink-0" style="border-radius:12px;padding:12px 16px;">
                    <iconify-icon icon="mdi:arrow-right" width="18"></iconify-icon>
                </button>
            </div>
        </div>
    </div>

    <!-- Info Section -->
    <div class="px-4 mt-5 mb-2">
        <div class="font-bold text-base mb-3" style="color:var(--text);">Fasilitas Wisata</div>
        <div class="grid grid-cols-2 gap-3">
            <?php
            $facilities = [
                ['mdi:parking', '#3b82f6', 'Area Parkir', 'Luas dan aman'],
                ['mdi:food-fork-drink', '#10b981', 'Kuliner', 'Warung makan lokal'],
                ['mdi:toilet', '#8b5cf6', 'Toilet Umum', 'Bersih dan terawat'],
                ['mdi:mosque', '#f59e0b', 'Mushola', 'Tersedia di area wisata'],
                ['mdi:camera', '#ec4899', 'Spot Foto', '20+ titik foto menarik'],
                ['mdi:medical-bag', '#ef4444', 'P3K', 'Pos kesehatan tersedia'],
            ];
            foreach ($facilities as $f): ?>
            <div class="card p-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:<?php echo $f[1]; ?>15;">
                    <iconify-icon icon="<?php echo $f[0]; ?>" width="18" style="color:<?php echo $f[1]; ?>;"></iconify-icon>
                </div>
                <div>
                    <div class="font-semibold text-xs" style="color:var(--text);"><?php echo $f[2]; ?></div>
                    <div class="text-xs" style="color:var(--text-muted);"><?php echo $f[3]; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Bottom Navigation -->
<nav class="bottom-nav">
    <div class="flex items-center justify-around px-2">
        <?php
        $nav_items = [
            ['home', 'mdi:home', 'Beranda'],
            ['booking', 'mdi:ticket-outline', 'Pesan'],
            ['my-tickets', 'mdi:ticket-confirmation-outline', 'Tiket Saya'],
            ['track', 'mdi:magnify-scan', 'Lacak'],
            ['profile', 'mdi:account-outline', 'Profil'],
        ];
        foreach ($nav_items as $nav):
        ?>
        <a href="index.php?page=<?php echo $nav[0]; ?>" class="nav-item flex flex-col items-center gap-1 px-3 py-1" style="color:var(--text-muted); text-decoration:none;">
            <div class="nav-icon w-10 h-10 rounded-xl flex items-center justify-center transition-all">
                <iconify-icon icon="<?php echo $nav[1]; ?>" width="22"></iconify-icon>
            </div>
            <span class="text-xs font-semibold"><?php echo $nav[2]; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>

<script>
function quickTrack() {
    const code = $('#quickTrackCode').val().trim();
    if (!code) { showToast('Masukkan kode booking terlebih dahulu', 'warning'); return; }
    window.location = 'index.php?page=track&code=' + encodeURIComponent(code);
}
$('#quickTrackCode').on('keypress', function(e) { if (e.which === 13) quickTrack(); });
</script>