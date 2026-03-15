<?php
// admin/pages/reports.php
$conn = db_connect();

// Summary stats
$total_rev = $conn->query("SELECT SUM(total_amount) as s FROM bookings WHERE status IN ('confirmed','completed')")->fetch_assoc()['s'] ?? 0;
$this_month_rev = $conn->query("SELECT SUM(total_amount) as s FROM bookings WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status IN ('confirmed','completed')")->fetch_assoc()['s'] ?? 0;
$total_visitors = $conn->query("SELECT SUM(total_tickets) as s FROM bookings WHERE status IN ('confirmed','completed')")->fetch_assoc()['s'] ?? 0;
$this_month_vis = $conn->query("SELECT SUM(total_tickets) as s FROM bookings WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status IN ('confirmed','completed')")->fetch_assoc()['s'] ?? 0;

// Daily revenue last 7 days
$daily = $conn->query("SELECT DATE(created_at) as day, COUNT(*) as bookings, SUM(total_amount) as revenue, SUM(total_tickets) as tickets FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status IN ('confirmed','completed') GROUP BY DATE(created_at) ORDER BY day ASC");
$daily_labels = $daily_revenue = $daily_bookings = [];
while ($d = $daily->fetch_assoc()) {
    $daily_labels[] = date('d M', strtotime($d['day']));
    $daily_revenue[] = (float)$d['revenue'];
    $daily_bookings[] = (int)$d['bookings'];
}

// Status distribution
$status_data = [];
$statuses = ['pending','confirmed','completed','cancelled'];
foreach ($statuses as $s) {
    $r = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='$s'")->fetch_assoc();
    $status_data[$s] = (int)$r['c'];
}

$conn->close();
?>

<!-- Summary Metrics -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <?php
    $metrics = [
        ['Total Pendapatan', format_rupiah($total_rev), format_rupiah($this_month_rev) . ' bulan ini', 'mdi:cash-multiple', '#10b981'],
        ['Total Pengunjung', number_format($total_visitors), number_format($this_month_vis) . ' bulan ini', 'mdi:account-group-outline', '#3b82f6'],
        ['Tingkat Konfirmasi', $total_rev > 0 ? round(($status_data['confirmed']+$status_data['completed']) / max(1, array_sum($status_data)) * 100) . '%' : '0%', 'Pesanan dikonfirmasi', 'mdi:percent-outline', '#f59e0b'],
        ['Pesanan Dibatalkan', $status_data['cancelled'], 'Total pembatalan', 'mdi:cancel', '#ef4444'],
    ];
    foreach ($metrics as $i => $m):
    ?>
    <div class="stat-card fade-in" style="animation-delay:<?php echo $i*0.07; ?>s;">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-3" style="background:<?php echo $m[4]; ?>15;">
            <iconify-icon icon="<?php echo $m[3]; ?>" width="22" style="color:<?php echo $m[4]; ?>;"></iconify-icon>
        </div>
        <div class="font-extrabold text-xl mb-0.5" style="color:var(--text);"><?php echo $m[1]; ?></div>
        <div class="text-xs font-semibold mb-0.5" style="color:var(--text);"><?php echo $m[0]; ?></div>
        <div class="text-xs" style="color:var(--text-muted);"><?php echo $m[2]; ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-6">

    <!-- Revenue Trend -->
    <div class="admin-card p-5 xl:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <div>
                <div class="font-bold text-base" style="color:var(--text);">Pendapatan Harian</div>
                <div class="text-xs" style="color:var(--text-muted);">7 hari terakhir</div>
            </div>
            <select id="reportRangeSelect" class="admin-input" style="max-width:150px;">
                <option value="7">7 Hari</option>
                <option value="30">30 Hari</option>
                <option value="90">3 Bulan</option>
            </select>
        </div>
        <canvas id="revenueChart" height="90"></canvas>
    </div>

    <!-- Status Donut -->
    <div class="admin-card p-5">
        <div class="font-bold text-base mb-2" style="color:var(--text);">Distribusi Status</div>
        <div class="text-xs mb-4" style="color:var(--text-muted);">Semua waktu</div>
        <canvas id="statusChart" height="160"></canvas>
        <div class="mt-4 space-y-2">
            <?php
            $sColors = ['pending'=>'#f59e0b','confirmed'=>'#10b981','completed'=>'#3b82f6','cancelled'=>'#ef4444'];
            $sLabels = ['pending'=>'Menunggu','confirmed'=>'Dikonfirmasi','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
            $total_all = array_sum($status_data);
            foreach ($status_data as $st => $cnt):
                $pct = $total_all > 0 ? round($cnt/$total_all*100) : 0;
            ?>
            <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full" style="background:<?php echo $sColors[$st]; ?>;"></div>
                    <span style="color:var(--text-muted);"><?php echo $sLabels[$st]; ?></span>
                </div>
                <span class="font-bold" style="color:var(--text);"><?php echo $cnt; ?> (<?php echo $pct; ?>%)</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Detailed Report Table -->
<div class="admin-card p-5">
    <div class="flex items-center justify-between mb-4">
        <div class="font-bold text-base" style="color:var(--text);">Laporan Detail Bulanan</div>
        <button onclick="printReport()" class="admin-btn-sm flex items-center gap-1.5 px-4 py-2.5" style="background:rgba(245,69,24,0.1);color:var(--primary);border-radius:10px;font-size:13px;">
            <iconify-icon icon="mdi:printer-outline" width="14"></iconify-icon>
            Cetak
        </button>
    </div>
    <div class="overflow-x-auto">
        <table id="reportTable" class="w-full text-sm">
            <thead>
                <tr style="border-bottom:2px solid var(--border);">
                    <th class="text-left pb-3 text-xs font-bold" style="color:var(--text-muted);">BULAN</th>
                    <th class="text-left pb-3 text-xs font-bold" style="color:var(--text-muted);">PESANAN</th>
                    <th class="text-left pb-3 text-xs font-bold" style="color:var(--text-muted);">TIKET TERJUAL</th>
                    <th class="text-left pb-3 text-xs font-bold" style="color:var(--text-muted);">PENDAPATAN</th>
                    <th class="text-left pb-3 text-xs font-bold" style="color:var(--text-muted);">DIBATALKAN</th>
                </tr>
            </thead>
            <tbody id="reportTbody"></tbody>
        </table>
    </div>
</div>

<script>
// Revenue Line Chart
const isDark = document.documentElement.classList.contains('dark');
const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
const textColor = isDark ? 'rgba(255,255,255,0.4)' : 'rgba(0,0,0,0.4)';

const revCtx = document.getElementById('revenueChart').getContext('2d');
const revGrad = revCtx.createLinearGradient(0, 0, 0, 200);
revGrad.addColorStop(0, 'rgba(245,69,24,0.3)');
revGrad.addColorStop(1, 'rgba(245,69,24,0)');

const revenueChart = new Chart(revCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($daily_labels ?: ['Belum ada data']); ?>,
        datasets: [{
            label: 'Pendapatan',
            data: <?php echo json_encode($daily_revenue ?: [0]); ?>,
            borderColor: '#f54518',
            backgroundColor: revGrad,
            borderWidth: 2.5,
            pointBackgroundColor: '#f54518',
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID') },
                backgroundColor: isDark ? '#1a1a2e' : 'white',
                titleColor: isDark ? '#f1f5f9' : '#111827',
                bodyColor: isDark ? '#94a3b8' : '#6b7280',
                borderColor: isDark ? '#2d2d3d' : '#e5e7eb',
                borderWidth: 1, padding: 12, cornerRadius: 10
            }
        },
        scales: {
            y: {
                grid: { color: gridColor },
                ticks: { color: textColor, font: {size:11}, callback: v => 'Rp ' + (v/1000).toFixed(0) + 'K' }
            },
            x: { grid: { display: false }, ticks: { color: textColor, font: {size:11} } }
        }
    }
});

// Status Donut
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Menunggu','Dikonfirmasi','Selesai','Dibatalkan'],
        datasets: [{
            data: [<?php echo implode(',', array_values($status_data)); ?>],
            backgroundColor: ['#f59e0b','#10b981','#3b82f6','#ef4444'],
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, cutout: '65%',
        plugins: {
            legend: { display: false },
            tooltip: { backgroundColor: isDark?'#1a1a2e':'white', titleColor: isDark?'#f1f5f9':'#111827', bodyColor: isDark?'#94a3b8':'#6b7280', borderColor: isDark?'#2d2d3d':'#e5e7eb', borderWidth:1, padding:10, cornerRadius:10 }
        }
    }
});

// Load monthly report table
$.ajax({ url:'../ajax/admin.php', data:{action:'get_monthly_report'}, dataType:'json',
    success: function(res) {
        if (res.status !== 'success') return;
        let html = '';
        res.data.forEach(row => {
            html += `<tr style="border-bottom:1px solid var(--border);">
                <td class="py-3 font-semibold text-sm" style="color:var(--text);">${row.month}</td>
                <td class="py-3 text-sm" style="color:var(--text);">${row.total_bookings}</td>
                <td class="py-3 text-sm" style="color:var(--text);">${row.total_tickets}</td>
                <td class="py-3 font-bold text-sm" style="color:var(--primary);">${row.revenue_formatted}</td>
                <td class="py-3 text-sm" style="color:#ef4444;">${row.cancelled}</td>
            </tr>`;
        });
        $('#reportTbody').html(html || '<tr><td colspan="5" class="py-8 text-center text-sm" style="color:var(--text-muted);">Belum ada data</td></tr>');
    }
});

// Range filter
$('#reportRangeSelect').on('change', function() {
    const days = $(this).val();
    showLoading();
    $.ajax({ url:'../ajax/admin.php', data:{action:'get_revenue_trend', days}, dataType:'json',
        success: function(res) {
            hideLoading();
            if (res.status==='success') {
                revenueChart.data.labels = res.labels;
                revenueChart.data.datasets[0].data = res.data;
                revenueChart.update();
            }
        }
    });
});

function printReport() { window.print(); }
</script>