<?php
// user/ticket_detail.php
require_once __DIR__ . '/../includes/config.php';
$booking_code = isset($_GET['code']) ? sanitize($_GET['code']) : '';
?>
<header class="app-header px-5 py-4">
    <div class="flex items-center gap-4">
        <a href="index.php?page=my-tickets" class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg-secondary);">
            <iconify-icon icon="mdi:arrow-left" width="20" style="color:var(--text);"></iconify-icon>
        </a>
        <div>
            <h1 class="font-bold text-base" style="color:var(--text);">Detail Tiket</h1>
            <p class="text-xs font-mono" style="color:var(--text-muted);"><?php echo htmlspecialchars($booking_code); ?></p>
        </div>
    </div>
</header>

<div class="page-content px-4 pt-4" id="detail-container">
    <div class="flex flex-col items-center justify-center py-16">
        <div class="spinner mb-3" style="border-top-color:var(--primary);border-color:rgba(245,69,24,0.2);"></div>
        <p class="text-sm" style="color:var(--text-muted);">Memuat detail tiket...</p>
    </div>
</div>

<script>
$(document).ready(function() {
    const code = '<?php echo $booking_code; ?>';
    if (!code) { window.location = 'index.php?page=my-tickets'; return; }

    $.ajax({
        url: 'ajax/booking.php',
        data: { action: 'get_booking_detail', code },
        dataType: 'json',
        success: function(res) {
            if (res.status !== 'success') {
                window.location = 'index.php?page=my-tickets'; return;
            }
            const d = res.data;
            const statusColors = { pending:'#f59e0b', confirmed:'#10b981', cancelled:'#ef4444', completed:'#3b82f6' };
            const statusLabels = { pending:'Menunggu Konfirmasi', confirmed:'Dikonfirmasi', cancelled:'Dibatalkan', completed:'Selesai' };
            const statusIcons = { pending:'mdi:clock-outline', confirmed:'mdi:check-circle-outline', cancelled:'mdi:close-circle-outline', completed:'mdi:check-decagram-outline' };
            const color = statusColors[d.status];

            let ticketItems = '', ticketCodes = '';
            d.items.forEach(item => {
                ticketItems += `<div class="flex justify-between py-2.5" style="border-bottom:1px solid var(--border);">
                    <div>
                        <div class="text-sm font-semibold" style="color:var(--text);">${item.category_name}</div>
                        <div class="text-xs" style="color:var(--text-muted);">${item.unit_price_formatted} x ${item.quantity}</div>
                    </div>
                    <span class="font-bold text-sm" style="color:var(--text);">${item.subtotal_formatted}</span>
                </div>`;
            });

            if (d.tickets && d.tickets.length > 0) {
                d.tickets.forEach(t => {
                    const tColor = t.status === 'used' ? '#6b7280' : (t.status === 'active' ? '#10b981' : '#ef4444');
                    const tLabel = {active:'Aktif', used:'Sudah Digunakan', cancelled:'Dibatalkan'}[t.status];
                    ticketCodes += `<div class="flex items-center justify-between p-3 rounded-xl mb-2" style="background:var(--bg-secondary);">
                        <div class="font-mono font-bold text-xs" style="color:var(--text);">${t.ticket_code}</div>
                        <span class="status-badge" style="background:${tColor}20;color:${tColor};">
                            <iconify-icon icon="${t.status === 'active' ? 'mdi:check-circle-outline' : 'mdi:circle-outline'}" width="10"></iconify-icon>
                            ${tLabel}
                        </span>
                    </div>`;
                });
            }

            let cancelBtn = '';
            if (d.status === 'pending') {
                cancelBtn = `<button onclick="cancelBooking('${d.booking_code}')" class="btn-outline w-full mt-3" style="color:#ef4444;border-color:#ef4444;">
                    <span class="flex items-center justify-center gap-2">
                        <iconify-icon icon="mdi:close-circle-outline" width="18"></iconify-icon>
                        Batalkan Pesanan
                    </span>
                </button>`;
            }

            const payLabels = {transfer_bank:'Transfer Bank', qris:'QRIS', tunai:'Bayar di Tempat'};
            const paymentProofUrl = d.payment_proof ? `<?php echo BASE_URL; ?>/${d.payment_proof}` : '';

            $('#detail-container').html(`<div class="space-y-4 fade-in">
                <div class="card p-5" style="border-color:${color}30;">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-mono font-extrabold text-base" style="color:var(--text);">${d.booking_code}</div>
                            <div class="text-xs mt-0.5" style="color:var(--text-muted);">Dipesan: ${d.created_at_formatted}</div>
                        </div>
                        <span class="status-badge status-${d.status}">
                            <iconify-icon icon="${statusIcons[d.status]}" width="12"></iconify-icon>
                            ${statusLabels[d.status]}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl" style="background:var(--bg-secondary);">
                            <div class="text-xs mb-1" style="color:var(--text-muted);">Tanggal Kunjungan</div>
                            <div class="font-bold text-sm" style="color:var(--text);">${d.visit_date_formatted}</div>
                        </div>
                        <div class="p-3 rounded-xl" style="background:var(--bg-secondary);">
                            <div class="text-xs mb-1" style="color:var(--text-muted);">Metode Bayar</div>
                            <div class="font-bold text-sm" style="color:var(--text);">${payLabels[d.payment_method] || d.payment_method || '-'}</div>
                        </div>
                    </div>
                </div>

                <div class="card p-4">
                    <div class="font-bold text-sm mb-3" style="color:var(--text);">Rincian Tiket</div>
                    ${ticketItems}
                    <div class="flex justify-between pt-3">
                        <span class="font-bold text-sm" style="color:var(--text);">Total</span>
                        <span class="font-extrabold text-base" style="color:var(--primary);">${d.total_amount_formatted}</span>
                    </div>
                </div>

                ${d.payment_method === 'transfer_bank' ? `<div class="card p-4">
                    <div class="font-bold text-sm mb-2" style="color:var(--text);">Bukti Pembayaran</div>
                    ${paymentProofUrl ? `<a href="${paymentProofUrl}" target="_blank" rel="noopener">
                        <img src="${paymentProofUrl}" alt="Bukti Pembayaran" class="w-full rounded-xl" style="border:1px solid var(--border);max-height:260px;object-fit:cover;">
                    </a>` : `<div class="text-xs" style="color:var(--text-muted);">Bukti pembayaran belum diunggah.</div>`}
                </div>` : ''}

                ${d.tickets && d.tickets.length > 0 ? `<div class="card p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <iconify-icon icon="mdi:qrcode" width="18" style="color:var(--primary);"></iconify-icon>
                        <span class="font-bold text-sm" style="color:var(--text);">Kode Tiket</span>
                    </div>
                    ${ticketCodes}
                    <div class="text-xs mt-2" style="color:var(--text-muted);">
                        <iconify-icon icon="mdi:information-outline" width="12" class="inline"></iconify-icon>
                        Tunjukkan kode ini kepada petugas di pintu masuk
                    </div>
                </div>` : ''}

                ${cancelBtn}
            </div>`);
        }
    });
});

function cancelBooking(code) {
    Swal.fire({
        title: 'Batalkan Pesanan?',
        text: 'Apakah Anda yakin ingin membatalkan pesanan ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            $.ajax({
                url: 'ajax/booking.php',
                method: 'POST',
                data: { action: 'cancel_booking', code },
                dataType: 'json',
                success: function(res) {
                    hideLoading();
                    if (res.status === 'success') {
                        showToast('Pesanan berhasil dibatalkan', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(res.message, 'error');
                    }
                }
            });
        }
    });
}
</script>