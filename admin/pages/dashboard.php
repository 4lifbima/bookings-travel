<?php
// admin/pages/dashboard.php
$conn = db_connect();

$total_bookings = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];
$pending_bookings = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='pending'")->fetch_assoc()['c'];
$confirmed_bookings = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='confirmed'")->fetch_assoc()['c'];
$total_revenue = $conn->query("SELECT SUM(total_amount) as s FROM bookings WHERE status IN ('confirmed','completed')")->fetch_assoc()['s'] ?? 0;
$total_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_tickets = $conn->query("SELECT SUM(total_tickets) as s FROM bookings WHERE status IN ('confirmed','completed')")->fetch_assoc()['s'] ?? 0;

// Today's bookings
$today_bookings = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
$today_revenue = $conn->query("SELECT SUM(total_amount) as s FROM bookings WHERE DATE(created_at)=CURDATE() AND status IN ('confirmed','completed')")->fetch_assoc()['s'] ?? 0;

// Recent bookings
$recent = $conn->query("SELECT b.*, u.name FROM bookings b JOIN users u ON b.user_id=u.id ORDER BY b.created_at DESC LIMIT 8");

// Monthly chart data
$monthly = $conn->query("SELECT DATE_FORMAT(created_at,'%b') as month, COUNT(*) as total, SUM(total_amount) as revenue FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY MONTH(created_at), YEAR(created_at) ORDER BY created_at ASC");
$chart_labels = $chart_totals = $chart_revenue = [];
while ($m = $monthly->fetch_assoc()) {
    $chart_labels[] = $m['month'];
    $chart_totals[] = $m['total'];
    $chart_revenue[] = $m['revenue'];
}

// Category popularity
$cat_stats = $conn->query("SELECT tc.name, SUM(bi.quantity) as qty FROM booking_items bi JOIN ticket_categories tc ON bi.ticket_category_id=tc.id GROUP BY tc.id ORDER BY qty DESC LIMIT 5");

$conn->close();
?>

<!-- Stat Cards Grid -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <?php
    $stats = [
        ['Pemesanan Hari Ini', $today_bookings, format_rupiah($today_revenue), 'mdi:calendar-today-outline', '#f54518', 'rgba(245,69,24,0.1)'],
        ['Menunggu Konfirmasi', $pending_bookings, 'Perlu perhatian', 'mdi:clock-alert-outline', '#f59e0b', 'rgba(245,158,11,0.1)'],
        ['Total Pendapatan', '', format_rupiah($total_revenue), 'mdi:cash-multiple', '#10b981', 'rgba(16,185,129,0.1)'],
        ['Total Pengguna', $total_users, $total_tickets . ' tiket terjual', 'mdi:account-multiple-outline', '#3b82f6', 'rgba(59,130,246,0.1)'],
    ];
    foreach ($stats as $i => $s):
    ?>
    <div class="stat-card fade-in" style="animation-delay:<?php echo $i*0.08; ?>s;">
        <div class="flex items-start justify-between mb-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:<?php echo $s[4]; ?>15;">
                <iconify-icon icon="<?php echo $s[3]; ?>" width="22" style="color:<?php echo $s[4]; ?>;"></iconify-icon>
            </div>
            <?php if ($i === 1 && $pending_bookings > 0): ?>
            <span class="text-xs font-bold px-2 py-1 rounded-full" style="background:rgba(245,158,11,0.15);color:#f59e0b;"><?php echo $pending_bookings; ?> baru</span>
            <?php endif; ?>
        </div>
        <div class="font-extrabold text-2xl mb-0.5" style="color:var(--text);">
            <?php echo $i === 2 ? format_rupiah($total_revenue) : ($i === 3 ? $total_users : ($i === 0 ? $today_bookings : $pending_bookings)); ?>
        </div>
        <div class="text-xs font-semibold mb-0.5" style="color:var(--text);"><?php echo $s[0]; ?></div>
        <div class="text-xs" style="color:var(--text-muted);"><?php echo $s[2]; ?></div>
        <!-- Decorative gradient -->
        <div class="absolute top-0 right-0 w-20 h-20 rounded-full opacity-5" style="background:<?php echo $s[4]; ?>;transform:translate(30%,-30%);"></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-6">
    <!-- Booking Chart -->
    <div class="admin-card p-5 xl:col-span-2">
        <div class="flex items-center justify-between mb-5">
            <div>
                <div class="font-bold text-base" style="color:var(--text);">Tren Pemesanan</div>
                <div class="text-xs" style="color:var(--text-muted);">6 bulan terakhir</div>
            </div>
        </div>
        <canvas id="bookingChart" height="90"></canvas>
    </div>

    <!-- Category Stats -->
    <div class="admin-card p-5">
        <div class="font-bold text-base mb-4" style="color:var(--text);">Tiket Terlaris</div>
        <div id="cat-stats-list" class="space-y-3">
            <?php
            $colors = ['#f54518','#3b82f6','#10b981','#f59e0b','#8b5cf6'];
            $i = 0;
            while ($cat = $cat_stats->fetch_assoc()):
                $pct = $total_tickets > 0 ? min(100, round($cat['qty'] / $total_tickets * 100)) : 0;
            ?>
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="font-semibold" style="color:var(--text);"><?php echo htmlspecialchars($cat['name']); ?></span>
                    <span class="font-bold" style="color:<?php echo $colors[$i]; ?>;"><?php echo $cat['qty']; ?> terjual</span>
                </div>
                <div class="h-1.5 rounded-full" style="background:var(--border);">
                    <div class="h-full rounded-full transition-all" style="width:<?php echo $pct; ?>%;background:<?php echo $colors[$i]; ?>;"></div>
                </div>
            </div>
            <?php $i++; endwhile; ?>
        </div>
    </div>
</div>

<!-- Quick Actions + Recent Bookings -->
<div class="grid grid-cols-1 xl:grid-cols-4 gap-5">
    <!-- Quick Actions -->
    <div class="admin-card p-5">
        <div class="font-bold text-base mb-4" style="color:var(--text);">Aksi Cepat</div>
        <div class="space-y-2">
            <?php
            $actions = [
                ['?page=bookings', 'mdi:check-circle-outline', 'Konfirmasi Pesanan', '#10b981', $pending_bookings . ' menunggu'],
                ['?page=categories', 'mdi:tag-plus-outline', 'Tambah Kategori', '#3b82f6', 'Kelola tiket'],
                ['?page=users', 'mdi:account-plus-outline', 'Kelola Pengguna', '#8b5cf6', $total_users . ' pengguna'],
                ['?page=reports', 'mdi:chart-line', 'Lihat Laporan', '#f59e0b', 'Statistik lengkap'],
            ];
            foreach ($actions as $a):
            ?>
            <a href="<?php echo $a[0]; ?>" class="flex items-center gap-3 p-3 rounded-xl transition-all" style="text-decoration:none;border:1px solid var(--border);" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background:<?php echo $a[3]; ?>15;">
                    <iconify-icon icon="<?php echo $a[1]; ?>" width="18" style="color:<?php echo $a[3]; ?>;"></iconify-icon>
                </div>
                <div>
                    <div class="font-semibold text-sm" style="color:var(--text);"><?php echo $a[2]; ?></div>
                    <div class="text-xs" style="color:var(--text-muted);"><?php echo $a[4]; ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="admin-card p-5 xl:col-span-3">
        <div class="flex items-center justify-between mb-4">
            <div class="font-bold text-base" style="color:var(--text);">Pemesanan Terbaru</div>
            <a href="?page=bookings" class="text-xs font-bold" style="color:var(--primary);">Lihat Semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="border-bottom:2px solid var(--border);">
                        <th class="text-left pb-3 text-xs font-bold" style="color:var(--text-muted);">KODE</th>
                        <th class="text-left pb-3 text-xs font-bold" style="color:var(--text-muted);">PENGUNJUNG</th>
                        <th class="text-left pb-3 text-xs font-bold hidden md:table-cell" style="color:var(--text-muted);">TGL KUNJUNGAN</th>
                        <th class="text-left pb-3 text-xs font-bold" style="color:var(--text-muted);">TOTAL</th>
                        <th class="text-left pb-3 text-xs font-bold" style="color:var(--text-muted);">STATUS</th>
                        <th class="text-left pb-3 text-xs font-bold" style="color:var(--text-muted);">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($b = $recent->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td class="py-3 font-mono text-xs font-bold" style="color:var(--text);"><?php echo htmlspecialchars($b['booking_code']); ?></td>
                        <td class="py-3 text-sm" style="color:var(--text);"><?php echo htmlspecialchars($b['name']); ?></td>
                        <td class="py-3 text-xs hidden md:table-cell" style="color:var(--text-muted);"><?php echo date('d M Y', strtotime($b['visit_date'])); ?></td>
                        <td class="py-3 font-bold text-xs" style="color:var(--primary);"><?php echo format_rupiah($b['total_amount']); ?></td>
                        <td class="py-3">
                            <span class="badge badge-<?php echo $b['status']; ?>">
                                <?php $labels=['pending'=>'Pending','confirmed'=>'Konfirmasi','cancelled'=>'Batal','completed'=>'Selesai']; echo $labels[$b['status']]; ?>
                            </span>
                        </td>
                        <td class="py-3">
                            <button onclick="viewBookingDetail('<?php echo $b['booking_code']; ?>')" class="admin-btn-sm btn-view">
                                <iconify-icon icon="mdi:eye-outline" width="12"></iconify-icon>
                                Detail
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Booking Chart
const ctx = document.getElementById('bookingChart').getContext('2d');
const isDark = document.documentElement.classList.contains('dark');
const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
const textColor = isDark ? 'rgba(255,255,255,0.5)' : 'rgba(0,0,0,0.5)';

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Pesanan',
            data: <?php echo json_encode($chart_totals); ?>,
            backgroundColor: 'rgba(245,69,24,0.75)',
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: isDark ? '#1a1a2e' : 'white',
                titleColor: isDark ? '#f1f5f9' : '#111827',
                bodyColor: isDark ? '#94a3b8' : '#6b7280',
                borderColor: isDark ? '#2d2d3d' : '#e5e7eb',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 10,
            }
        },
        scales: {
            y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } }
        }
    }
});

function viewBookingDetail(code) {
    showLoading();
    $.ajax({
        url: '../ajax/booking.php',
        data: { action: 'get_booking_detail', code },
        dataType: 'json',
        success: function(res) {
            hideLoading();
            if (res.status !== 'success') { showToast('Data tidak ditemukan', 'error'); return; }
            const d = res.data;
            const statusLabels = {pending:'Menunggu',confirmed:'Dikonfirmasi',cancelled:'Dibatalkan',completed:'Selesai'};
            let items = d.items.map(i => `<div class="flex justify-between py-2" style="border-bottom:1px solid var(--border);">
                <span class="text-sm" style="color:var(--text);">${i.category_name} x${i.quantity}</span>
                <span class="font-bold text-sm" style="color:var(--text);">${i.subtotal_formatted}</span>
            </div>`).join('');

            Swal.fire({
                title: d.booking_code,
                html: `<div class="text-left text-sm space-y-3">
                    <div><b>Pengunjung:</b> ${d.name}</div>
                    <div><b>Tgl Kunjungan:</b> ${d.visit_date_formatted}</div>
                    <div><b>Status:</b> ${statusLabels[d.status]}</div>
                    <div class="border-t pt-2" style="border-color:#e5e7eb;">${items}</div>
                    <div class="flex justify-between font-bold pt-2"><span>Total</span><span style="color:#f54518">${d.total_amount_formatted}</span></div>
                </div>`,
                confirmButtonColor: '#f54518',
                confirmButtonText: 'Tutup',
            });
        }
    });
}
</script>