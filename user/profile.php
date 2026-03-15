<?php
// user/profile.php
require_once __DIR__ . '/../includes/config.php';
$conn = db_connect();
$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
$stats = $conn->query("SELECT COUNT(*) as total, SUM(total_amount) as spent FROM bookings WHERE user_id=$user_id AND status!='cancelled'")->fetch_assoc();
$conn->close();
?>
<header class="app-header px-5 py-4">
    <div class="flex items-center gap-4">
        <a href="index.php" class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg-secondary);">
            <iconify-icon icon="mdi:arrow-left" width="20" style="color:var(--text);"></iconify-icon>
        </a>
        <h1 class="font-bold text-base" style="color:var(--text);">Profil Saya</h1>
        <button onclick="toggleTheme()" class="ml-auto w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg-secondary);">
            <iconify-icon class="theme-icon" icon="mdi:weather-night" width="18" style="color:var(--text-muted);"></iconify-icon>
        </button>
    </div>
</header>

<div class="page-content">
    <!-- Profile Header -->
    <div class="hero-gradient px-5 pt-6 pb-16 relative">
        <div class="flex flex-col items-center">
            <div class="w-20 h-20 rounded-3xl flex items-center justify-center mb-3" style="background:rgba(255,255,255,0.25);">
                <iconify-icon icon="mdi:account" width="40" style="color:white;"></iconify-icon>
            </div>
            <div class="text-white font-extrabold text-xl"><?php echo htmlspecialchars($user['name']); ?></div>
            <div style="color:rgba(255,255,255,0.8);" class="text-sm"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="px-4 -mt-8 mb-5 z-50 relative">
        <div class="card p-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="font-extrabold text-2xl" style="color:var(--primary);"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="text-xs" style="color:var(--text-muted);">Total Pesanan</div>
                </div>
                <div class="text-center">
                    <div class="font-extrabold text-lg" style="color:var(--primary);"><?php echo format_rupiah($stats['spent'] ?? 0); ?></div>
                    <div class="text-xs" style="color:var(--text-muted);">Total Pengeluaran</div>
                </div>
            </div>
        </div>
    </div>

    <div class="px-4 space-y-3">
        <!-- Edit Profile -->
        <div class="card p-4">
            <div class="font-bold text-sm mb-4" style="color:var(--text);">Informasi Akun</div>
            <form id="profileForm" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-muted);">NAMA LENGKAP</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2"><iconify-icon icon="mdi:account-outline" width="16" style="color:var(--text-muted);"></iconify-icon></span>
                        <input type="text" name="name" class="form-input pl-10 text-sm" value="<?php echo htmlspecialchars($user['name']); ?>">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-muted);">NOMOR TELEPON</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2"><iconify-icon icon="mdi:phone-outline" width="16" style="color:var(--text-muted);"></iconify-icon></span>
                        <input type="tel" name="phone" class="form-input pl-10 text-sm" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full text-sm ripple">
                    <span class="flex items-center justify-center gap-2">
                        <iconify-icon icon="mdi:content-save-outline" width="16"></iconify-icon>
                        Simpan Perubahan
                    </span>
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="card p-4">
            <div class="font-bold text-sm mb-4" style="color:var(--text);">Ubah Password</div>
            <form id="passForm" class="space-y-3">
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2"><iconify-icon icon="mdi:lock-outline" width="16" style="color:var(--text-muted);"></iconify-icon></span>
                    <input type="password" name="old_password" placeholder="Password lama" class="form-input pl-10 text-sm">
                </div>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2"><iconify-icon icon="mdi:lock-plus-outline" width="16" style="color:var(--text-muted);"></iconify-icon></span>
                    <input type="password" name="new_password" placeholder="Password baru (min. 8 karakter)" class="form-input pl-10 text-sm">
                </div>
                <button type="submit" class="btn-outline w-full text-sm">
                    <span class="flex items-center justify-center gap-2">
                        <iconify-icon icon="mdi:lock-reset" width="16"></iconify-icon>
                        Ubah Password
                    </span>
                </button>
            </form>
        </div>

        <!-- Logout -->
        <div class="card p-4">
            <a href="index.php?page=logout" class="flex items-center gap-3 w-full" style="color:#ef4444; text-decoration:none;">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(239,68,68,0.1);">
                    <iconify-icon icon="mdi:logout" width="20" style="color:#ef4444;"></iconify-icon>
                </div>
                <div>
                    <div class="font-bold text-sm">Keluar</div>
                    <div class="text-xs" style="color:var(--text-muted);">Logout dari akun Anda</div>
                </div>
                <iconify-icon icon="mdi:chevron-right" width="20" class="ml-auto" style="color:var(--text-muted);"></iconify-icon>
            </a>
        </div>
    </div>
</div>

<!-- Bottom Nav -->
<nav class="bottom-nav">
    <div class="flex items-center justify-around px-2">
        <?php $nav_items=[['home','mdi:home','Beranda'],['booking','mdi:ticket-outline','Pesan'],['my-tickets','mdi:ticket-confirmation-outline','Tiket Saya'],['track','mdi:magnify-scan','Lacak'],['profile','mdi:account-outline','Profil']];
        foreach ($nav_items as $nav): ?>
        <a href="index.php?page=<?php echo $nav[0]; ?>" class="nav-item flex flex-col items-center gap-1 px-3 py-1" style="color:var(--text-muted);text-decoration:none;">
            <div class="nav-icon w-10 h-10 rounded-xl flex items-center justify-center"><iconify-icon icon="<?php echo $nav[1]; ?>" width="22"></iconify-icon></div>
            <span class="text-xs font-semibold"><?php echo $nav[2]; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>

<script>
$('#profileForm').on('submit', function(e) {
    e.preventDefault();
    const data = { action: 'update_profile', name: $('[name=name]').val(), phone: $('[name=phone]').val() };
    showLoading();
    $.ajax({ url: 'ajax/auth.php', method: 'POST', data, dataType: 'json',
        success: function(res) { hideLoading(); showToast(res.message, res.status === 'success' ? 'success' : 'error'); }
    });
});

$('#passForm').on('submit', function(e) {
    e.preventDefault();
    const old_p = $('[name=old_password]').val();
    const new_p = $('[name=new_password]').val();
    if (!old_p || !new_p) { showToast('Isi semua field password', 'warning'); return; }
    if (new_p.length < 8) { showToast('Password minimal 8 karakter', 'error'); return; }
    showLoading();
    $.ajax({ url: 'ajax/auth.php', method: 'POST', data: { action: 'change_password', old_password: old_p, new_password: new_p }, dataType: 'json',
        success: function(res) { hideLoading(); showToast(res.message, res.status === 'success' ? 'success' : 'error'); if(res.status==='success') { $('[name=old_password],[name=new_password]').val(''); } }
    });
});
</script>