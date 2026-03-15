<?php
// user/my_tickets.php
require_once __DIR__ . '/../includes/config.php';
?>

<!-- App Header -->
<header class="app-header px-5 py-4">
    <div class="flex items-center gap-4">
        <a href="index.php" class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg-secondary);">
            <iconify-icon icon="mdi:arrow-left" width="20" style="color:var(--text);"></iconify-icon>
        </a>
        <div>
            <h1 class="font-bold text-base" style="color:var(--text);">Tiket Saya</h1>
            <p class="text-xs" style="color:var(--text-muted);">Riwayat pemesanan</p>
        </div>
        <button onclick="toggleTheme()" class="ml-auto w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg-secondary);">
            <iconify-icon class="theme-icon" icon="mdi:weather-night" width="18" style="color:var(--text-muted);"></iconify-icon>
        </button>
    </div>
</header>

<div class="page-content px-4 pt-4">

    <!-- Filter Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide">
        <?php
        $filters = ['all'=>'Semua','pending'=>'Menunggu','confirmed'=>'Dikonfirmasi','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
        foreach ($filters as $val => $label):
        ?>
        <button class="filter-btn flex-shrink-0 px-4 py-2 rounded-xl text-xs font-semibold transition-all ripple"
                data-filter="<?php echo $val; ?>"
                style="background:var(--bg-secondary);color:var(--text-muted);">
            <?php echo $label; ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Bookings List -->
    <div id="bookings-container">
        <div class="flex flex-col items-center justify-center py-16">
            <div class="spinner mb-3" style="border-top-color:var(--primary);border-color:rgba(245,69,24,0.2);"></div>
            <p class="text-sm" style="color:var(--text-muted);">Memuat pesanan...</p>
        </div>
    </div>

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
let currentFilter = 'all';

function loadBookings(filter = 'all') {
    currentFilter = filter;
    const container = $('#bookings-container');
    container.html('<div class="flex flex-col items-center justify-center py-16"><div class="spinner mb-3" style="border-top-color:var(--primary);border-color:rgba(245,69,24,0.2);"></div><p class="text-sm" style="color:var(--text-muted);">Memuat...</p></div>');

    $.ajax({
        url: 'ajax/booking.php',
        data: { action: 'get_my_bookings', filter },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success' && res.data.length > 0) {
                let html = '<div class="space-y-3">';
                res.data.forEach(b => {
                    const statusClass = `status-${b.status}`;
                    const statusLabels = {pending:'Menunggu Konfirmasi',confirmed:'Dikonfirmasi',cancelled:'Dibatalkan',completed:'Selesai'};
                    const statusIcons = {pending:'mdi:clock-outline',confirmed:'mdi:check-circle-outline',cancelled:'mdi:close-circle-outline',completed:'mdi:check-decagram-outline'};
                    html += `<div class="ticket-card p-4 pl-6 fade-in cursor-pointer" onclick="viewDetail('${b.booking_code}')">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <div class="font-bold text-sm mb-1" style="color:var(--text);">${b.booking_code}</div>
                                <div class="text-xs" style="color:var(--text-muted);">${b.visit_date_formatted}</div>
                            </div>
                            <span class="status-badge ${statusClass}">
                                <iconify-icon icon="${statusIcons[b.status]}" width="12"></iconify-icon>
                                ${statusLabels[b.status]}
                            </span>
                        </div>
                        <div class="flex items-center justify-between pt-3" style="border-top:1px dashed var(--border);">
                            <div class="flex items-center gap-2">
                                <iconify-icon icon="mdi:ticket-outline" width="16" style="color:var(--text-muted);"></iconify-icon>
                                <span class="text-xs font-semibold" style="color:var(--text-muted);">${b.total_tickets} Tiket</span>
                            </div>
                            <div class="font-extrabold text-sm" style="color:var(--primary);">${b.total_amount_formatted}</div>
                        </div>
                    </div>`;
                });
                html += '</div>';
                container.html(html);
            } else {
                container.html(`<div class="flex flex-col items-center justify-center py-16 fade-in">
                    <div class="w-20 h-20 rounded-full flex items-center justify-center mb-4" style="background:var(--bg-secondary);">
                        <iconify-icon icon="mdi:ticket-outline" width="36" style="color:var(--text-muted);"></iconify-icon>
                    </div>
                    <div class="font-bold text-base mb-1" style="color:var(--text);">Belum Ada Pesanan</div>
                    <div class="text-xs mb-5" style="color:var(--text-muted);">Yuk, pesan tiket kunjungan pertama Anda</div>
                    <a href="index.php?page=booking" class="btn-primary text-sm px-6">Pesan Tiket Sekarang</a>
                </div>`);
            }
        }
    });
}

function viewDetail(code) {
    window.location = `index.php?page=ticket-detail&code=${code}`;
}

// Filter buttons
$('.filter-btn').on('click', function() {
    $('.filter-btn').css({background:'var(--bg-secondary)',color:'var(--text-muted)'});
    $(this).css({background:'#f54518',color:'white'});
    loadBookings($(this).data('filter'));
});

$(document).ready(function() {
    // Activate first filter
    $('.filter-btn[data-filter="all"]').css({background:'#f54518',color:'white'});
    loadBookings();
});
</script>