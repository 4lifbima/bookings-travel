<?php
// user/track.php
require_once __DIR__ . '/../includes/config.php';
$initial_code = isset($_GET['code']) ? sanitize($_GET['code']) : '';
?>

<!-- App Header -->
<header class="app-header px-5 py-4">
    <div class="flex items-center gap-4">
        <a href="index.php" class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg-secondary);">
            <iconify-icon icon="mdi:arrow-left" width="20" style="color:var(--text);"></iconify-icon>
        </a>
        <div>
            <h1 class="font-bold text-base" style="color:var(--text);">Lacak Tiket</h1>
            <p class="text-xs" style="color:var(--text-muted);">Cek status pemesanan</p>
        </div>
        <button onclick="toggleTheme()" class="ml-auto w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg-secondary);">
            <iconify-icon class="theme-icon" icon="mdi:weather-night" width="18" style="color:var(--text-muted);"></iconify-icon>
        </button>
    </div>
</header>

<div class="page-content px-4 pt-6">

    <!-- Search Box -->
    <div class="card p-4 mb-6" style="background:linear-gradient(135deg,rgba(245,69,24,0.05),rgba(245,69,24,0.02));">
        <div class="font-bold text-sm mb-3" style="color:var(--text);">Masukkan Kode Booking</div>
        <div class="flex gap-2">
            <input type="text" id="trackCode" placeholder="Contoh: BKG-20241201-AB1234" 
                   class="form-input flex-1 text-sm" value="<?php echo htmlspecialchars($initial_code); ?>">
            <button onclick="doTrack()" class="btn-primary flex-shrink-0 flex items-center gap-2 px-4" style="border-radius:12px;">
                <iconify-icon icon="mdi:magnify" width="18"></iconify-icon>
            </button>
        </div>
    </div>

    <!-- Result Area -->
    <div id="track-result"></div>

</div>

<!-- Bottom Navigation -->
<nav class="bottom-nav">
    <div class="flex items-center justify-around px-2">
        <?php
        $nav_items = [['home','mdi:home','Beranda'],['booking','mdi:ticket-outline','Pesan'],['my-tickets','mdi:ticket-confirmation-outline','Tiket Saya'],['track','mdi:magnify-scan','Lacak'],['profile','mdi:account-outline','Profil']];
        foreach ($nav_items as $nav):
        ?>
        <a href="index.php?page=<?php echo $nav[0]; ?>" class="nav-item flex flex-col items-center gap-1 px-3 py-1" style="color:var(--text-muted);text-decoration:none;">
            <div class="nav-icon w-10 h-10 rounded-xl flex items-center justify-center">
                <iconify-icon icon="<?php echo $nav[1]; ?>" width="22"></iconify-icon>
            </div>
            <span class="text-xs font-semibold"><?php echo $nav[2]; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>

<script>
function doTrack() {
    const code = $('#trackCode').val().trim();
    if (!code) { showToast('Masukkan kode booking', 'warning'); return; }

    showLoading();
    $.ajax({
        url: 'ajax/booking.php',
        data: { action: 'track_booking', code },
        dataType: 'json',
        success: function(res) {
            hideLoading();
            if (res.status === 'success') {
                renderTrackResult(res.data);
            } else {
                $('#track-result').html(`<div class="flex flex-col items-center py-10 fade-in">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-3" style="background:rgba(239,68,68,0.1);">
                        <iconify-icon icon="mdi:close-circle-outline" width="32" style="color:#ef4444;"></iconify-icon>
                    </div>
                    <div class="font-bold mb-1" style="color:var(--text);">Kode Tidak Ditemukan</div>
                    <div class="text-xs text-center" style="color:var(--text-muted);">Pastikan kode booking yang Anda masukkan sudah benar</div>
                </div>`);
            }
        },
        error: function() { hideLoading(); showToast('Terjadi kesalahan', 'error'); }
    });
}

function renderTrackResult(data) {
    const statusColors = { pending:'#f59e0b', confirmed:'#10b981', cancelled:'#ef4444', completed:'#3b82f6' };
    const statusLabels = { pending:'Menunggu Konfirmasi', confirmed:'Dikonfirmasi', cancelled:'Dibatalkan', completed:'Selesai' };
    const statusIcons = { pending:'mdi:clock-outline', confirmed:'mdi:check-circle-outline', cancelled:'mdi:close-circle-outline', completed:'mdi:check-decagram-outline' };
    const color = statusColors[data.status];

    let ticketItems = '';
    data.items.forEach(item => {
        ticketItems += `<div class="flex justify-between py-2" style="border-bottom:1px solid var(--border);">
            <span class="text-sm" style="color:var(--text);">${item.category_name} x${item.quantity}</span>
            <span class="font-semibold text-sm" style="color:var(--text);">${item.subtotal_formatted}</span>
        </div>`;
    });

    const timeline = buildTimeline(data);

    $('#track-result').html(`
        <div class="space-y-4 fade-in">
            <!-- Status Card -->
            <div class="card p-5" style="border-color:${color}30; background:${color}08;">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:${color}20;">
                        <iconify-icon icon="${statusIcons[data.status]}" width="28" style="color:${color};"></iconify-icon>
                    </div>
                    <div>
                        <div class="font-extrabold text-base" style="color:var(--text);">${data.booking_code}</div>
                        <div class="text-sm font-semibold mt-0.5" style="color:${color};">${statusLabels[data.status]}</div>
                        <div class="text-xs mt-0.5" style="color:var(--text-muted);">Dipesan: ${data.created_at_formatted}</div>
                    </div>
                </div>
            </div>

            <!-- Visit Info -->
            <div class="card p-4">
                <div class="flex items-center gap-3 mb-3">
                    <iconify-icon icon="mdi:calendar-check" width="18" style="color:var(--primary);"></iconify-icon>
                    <span class="font-bold text-sm" style="color:var(--text);">Info Kunjungan</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-xl" style="background:var(--bg-secondary);">
                        <div class="text-xs mb-1" style="color:var(--text-muted);">Tanggal</div>
                        <div class="font-bold text-sm" style="color:var(--text);">${data.visit_date_formatted}</div>
                    </div>
                    <div class="p-3 rounded-xl" style="background:var(--bg-secondary);">
                        <div class="text-xs mb-1" style="color:var(--text-muted);">Jumlah Tiket</div>
                        <div class="font-bold text-sm" style="color:var(--text);">${data.total_tickets} Tiket</div>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="card p-4">
                <div class="font-bold text-sm mb-3" style="color:var(--text);">Detail Tiket</div>
                ${ticketItems}
                <div class="flex justify-between pt-3">
                    <span class="font-bold text-sm" style="color:var(--text);">Total</span>
                    <span class="font-extrabold" style="color:var(--primary);">${data.total_amount_formatted}</span>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card p-4">
                <div class="font-bold text-sm mb-4" style="color:var(--text);">Status Perjalanan</div>
                ${timeline}
            </div>
        </div>
    `);
}

function buildTimeline(data) {
    const steps = [
        { key: 'pending', icon: 'mdi:clipboard-text-outline', label: 'Pesanan Dibuat', desc: data.created_at_formatted },
        { key: 'confirmed', icon: 'mdi:check-circle-outline', label: 'Dikonfirmasi Admin', desc: 'Pembayaran diverifikasi' },
        { key: 'completed', icon: 'mdi:check-decagram-outline', label: 'Kunjungan Selesai', desc: data.visit_date_formatted },
    ];
    const order = ['pending', 'confirmed', 'completed'];
    const currentIdx = data.status === 'cancelled' ? -1 : order.indexOf(data.status);

    if (data.status === 'cancelled') {
        return `<div class="flex items-center gap-3 py-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background:rgba(239,68,68,0.1);">
                <iconify-icon icon="mdi:close-circle-outline" width="18" style="color:#ef4444;"></iconify-icon>
            </div>
            <div class="font-semibold text-sm" style="color:#ef4444;">Pesanan Dibatalkan</div>
        </div>`;
    }

    return steps.map((step, i) => {
        const done = i <= currentIdx;
        const active = i === currentIdx;
        const color = done ? '#10b981' : 'var(--border)';
        const iconColor = done ? (active ? '#f54518' : '#10b981') : 'var(--text-muted)';
        return `<div class="flex gap-3 ${i < steps.length-1 ? 'mb-4' : ''}">
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" 
                     style="background:${done ? iconColor+'15' : 'var(--bg-secondary)'}; border:2px solid ${done ? iconColor : 'var(--border)'};">
                    <iconify-icon icon="${done ? 'mdi:check' : step.icon}" width="14" style="color:${done ? iconColor : 'var(--text-muted)'};"></iconify-icon>
                </div>
                ${i < steps.length-1 ? `<div class="w-0.5 h-8 mt-1 rounded-full" style="background:${done ? '#10b981' : 'var(--border)'}"></div>` : ''}
            </div>
            <div class="pb-2">
                <div class="font-semibold text-sm ${active ? '' : ''}" style="color:${done ? 'var(--text)' : 'var(--text-muted)'};">${step.label}</div>
                <div class="text-xs" style="color:var(--text-muted);">${step.desc}</div>
            </div>
        </div>`;
    }).join('');
}

$(document).ready(function() {
    const code = '<?php echo $initial_code; ?>';
    if (code) { doTrack(); }
    $('#trackCode').on('keypress', function(e) { if (e.which === 13) doTrack(); });
});
</script>