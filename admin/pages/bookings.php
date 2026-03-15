<?php
// admin/pages/bookings.php
?>
<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-3">
        <?php
        $filters = ['all'=>'Semua','pending'=>'Menunggu','confirmed'=>'Dikonfirmasi','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
        foreach ($filters as $val => $label):
        ?>
        <button class="filter-tab px-4 py-2 rounded-xl text-xs font-bold transition-all border"
                data-filter="<?php echo $val; ?>"
                style="border-color:var(--border);background:var(--bg-card);color:var(--text-muted);">
            <?php echo $label; ?>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<div class="admin-card p-5">
    <div class="flex items-center justify-between mb-4">
        <div class="font-bold text-base" style="color:var(--text);">Daftar Pemesanan</div>
        <div class="flex items-center gap-3">
            <input type="date" id="filter_date" class="admin-input" style="max-width:180px;">
            <button onclick="exportData()" class="admin-btn-sm" style="background:rgba(16,185,129,0.1);color:#10b981;padding:8px 14px;border-radius:10px;">
                <iconify-icon icon="mdi:microsoft-excel" width="14"></iconify-icon>
                Export
            </button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table id="bookingsTable" class="w-full" style="width:100%;">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Pengunjung</th>
                    <th>Tanggal Kunjungan</th>
                    <th>Tiket</th>
                    <th>Total</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="bookings-tbody"></tbody>
        </table>
    </div>
</div>

<!-- Detail Modal -->
<div id="bookingDetailModal" class="modal-overlay" style="display:none;" onclick="closeModal('bookingDetailModal')">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="p-5 border-b" style="border-color:var(--border);">
            <div class="flex items-center justify-between">
                <div class="font-bold text-base" style="color:var(--text);">Detail Pemesanan</div>
                <button onclick="closeModal('bookingDetailModal')" class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:var(--bg);">
                    <iconify-icon icon="mdi:close" width="16" style="color:var(--text-muted);"></iconify-icon>
                </button>
            </div>
        </div>
        <div class="p-5" id="bookingDetailContent">
            <div class="flex justify-center py-8"><div class="spinner"></div></div>
        </div>
        <div class="p-5 border-t flex gap-3" style="border-color:var(--border);" id="bookingActionBtns"></div>
    </div>
</div>

<script>
let currentBookingCode = '';
let currentFilter = 'all';
let bookingsTable;

$(document).ready(function() {
    bookingsTable = $('#bookingsTable').DataTable({
        processing: true,
        serverSide: false,
        language: {
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
            emptyTable: 'Tidak ada data',
            infoEmpty: 'Tidak ada data',
        },
        columns: [
            { data: 'booking_code', render: d => `<span class="font-mono text-xs font-bold" style="color:var(--primary);">${d}</span>` },
            { data: 'user_name' },
            { data: 'visit_date', render: d => new Date(d).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}) },
            { data: 'total_tickets', render: d => `<span class="font-bold">${d}</span>` },
            { data: 'total_amount', render: d => `<span class="font-bold text-xs" style="color:var(--primary);">Rp ${parseInt(d).toLocaleString('id-ID')}</span>` },
            { data: 'payment_method', render: d => { const m={transfer_bank:'Transfer Bank',qris:'QRIS',tunai:'Bayar Ditempat'}; return `<span class="text-xs">${m[d]||d||'-'}</span>`; }},
            { data: 'status', render: d => { const l={pending:'Menunggu',confirmed:'Dikonfirmasi',cancelled:'Dibatalkan',completed:'Selesai'}; return `<span class="badge badge-${d}">${l[d]}</span>`; }},
            { data: 'booking_code', render: d => `<div class="flex gap-2">
                <button onclick="openBookingDetail('${d}')" class="admin-btn-sm btn-view"><iconify-icon icon="mdi:eye-outline" width="12"></iconify-icon></button>
                <button onclick="quickConfirm('${d}')" class="admin-btn-sm btn-confirm" id="confirm-btn-${d}"><iconify-icon icon="mdi:check" width="12"></iconify-icon></button>
            </div>` }
        ]
    });

    // Filter tabs
    $('.filter-tab').on('click', function() {
        $('.filter-tab').css({background:'var(--bg-card)',color:'var(--text-muted)',borderColor:'var(--border)'});
        $(this).css({background:'#f54518',color:'white',borderColor:'#f54518'});
        currentFilter = $(this).data('filter');
        loadBookings(currentFilter);
    });

    // Activate first tab
    $('.filter-tab[data-filter="all"]').css({background:'#f54518',color:'white',borderColor:'#f54518'});
    loadBookings('all');
});

function loadBookings(filter) {
    showLoading();
    $.ajax({
        url: '../ajax/admin.php',
        data: { action: 'get_all_bookings', filter },
        dataType: 'json',
        success: function(res) {
            hideLoading();
            bookingsTable.clear();
            if (res.status === 'success') {
                bookingsTable.rows.add(res.data).draw();
            } else {
                showToast('Gagal memuat: ' + res.message, 'error');
            }
        },
        error: function(xhr) {
            hideLoading();
            showToast('Gagal memuat data pemesanan', 'error');
            console.error('AJAX Error:', xhr.responseText);
        }
    });
}

function quickConfirm(code) {
    Swal.fire({
        title: 'Konfirmasi Pesanan',
        text: 'Konfirmasi pesanan ' + code + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Konfirmasi',
        confirmButtonColor: '#10b981',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (r.isConfirmed) updateBookingStatus(code, 'confirmed');
    });
}

function openBookingDetail(code) {
    currentBookingCode = code;
    $('#bookingDetailModal').css('display','flex');
    $('#bookingDetailContent').html('<div class="flex justify-center py-8"><div class="spinner"></div></div>');

    $.ajax({
        url: '../ajax/booking.php',
        data: { action: 'get_booking_detail', code },
        dataType: 'json',
        success: function(res) {
            if (res.status !== 'success') { closeModal('bookingDetailModal'); return; }
            const d = res.data;
            const statusLabels = {pending:'Menunggu Konfirmasi',confirmed:'Dikonfirmasi',cancelled:'Dibatalkan',completed:'Selesai'};
            const payLabels = {transfer_bank:'Transfer Bank',qris:'QRIS',tunai:'Bayar di Tempat'};

            let items = d.items.map(i => `<div class="flex justify-between py-2.5" style="border-bottom:1px solid var(--border);">
                <div>
                    <div class="text-sm font-semibold" style="color:var(--text);">${i.category_name}</div>
                    <div class="text-xs" style="color:var(--text-muted);">${i.unit_price_formatted} x ${i.quantity}</div>
                </div>
                <span class="font-bold text-sm" style="color:var(--text);">${i.subtotal_formatted}</span>
            </div>`).join('');

            $('#bookingDetailContent').html(`<div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-xl" style="background:var(--bg);">
                        <div class="text-xs mb-1" style="color:var(--text-muted);">Kode Booking</div>
                        <div class="font-bold text-xs font-mono" style="color:var(--primary);">${d.booking_code}</div>
                    </div>
                    <div class="p-3 rounded-xl" style="background:var(--bg);">
                        <div class="text-xs mb-1" style="color:var(--text-muted);">Status</div>
                        <span class="badge badge-${d.status}">${statusLabels[d.status]}</span>
                    </div>
                    <div class="p-3 rounded-xl" style="background:var(--bg);">
                        <div class="text-xs mb-1" style="color:var(--text-muted);">Pengunjung</div>
                        <div class="font-bold text-sm" style="color:var(--text);">${d.name}</div>
                    </div>
                    <div class="p-3 rounded-xl" style="background:var(--bg);">
                        <div class="text-xs mb-1" style="color:var(--text-muted);">Tgl Kunjungan</div>
                        <div class="font-bold text-sm" style="color:var(--text);">${d.visit_date_formatted}</div>
                    </div>
                    <div class="p-3 rounded-xl col-span-2" style="background:var(--bg);">
                        <div class="text-xs mb-1" style="color:var(--text-muted);">Metode Pembayaran</div>
                        <div class="font-bold text-sm" style="color:var(--text);">${payLabels[d.payment_method]||d.payment_method||'-'}</div>
                    </div>
                </div>
                <div>
                    <div class="font-bold text-sm mb-2" style="color:var(--text);">Detail Tiket</div>
                    ${items}
                    <div class="flex justify-between pt-3">
                        <span class="font-bold" style="color:var(--text);">Total</span>
                        <span class="font-extrabold text-base" style="color:var(--primary);">${d.total_amount_formatted}</span>
                    </div>
                </div>
            </div>`);

            let actionBtns = '';
            if (d.status === 'pending') {
                actionBtns += `<button onclick="updateBookingStatus('${code}','confirmed')" class="admin-btn-primary flex items-center gap-2 flex-1">
                    <iconify-icon icon="mdi:check-circle-outline" width="16"></iconify-icon> Konfirmasi
                </button>
                <button onclick="updateBookingStatus('${code}','cancelled')" class="admin-btn-sm btn-delete px-4 py-2.5 flex items-center gap-2" style="font-size:13px;">
                    <iconify-icon icon="mdi:close-circle-outline" width="14"></iconify-icon> Tolak
                </button>`;
            } else if (d.status === 'confirmed') {
                actionBtns += `<button onclick="updateBookingStatus('${code}','completed')" class="admin-btn-primary flex items-center gap-2 flex-1">
                    <iconify-icon icon="mdi:check-decagram-outline" width="16"></iconify-icon> Tandai Selesai
                </button>`;
            }
            $('#bookingActionBtns').html(actionBtns || '<div class="text-xs" style="color:var(--text-muted);">Tidak ada aksi yang tersedia</div>');
        }
    });
}

function updateBookingStatus(code, status) {
    showLoading();
    $.ajax({
        url: '../ajax/admin.php',
        method: 'POST',
        data: { action: 'update_booking_status', code, status },
        dataType: 'json',
        success: function(res) {
            hideLoading();
            if (res.status === 'success') {
                showToast('Status berhasil diperbarui', 'success');
                closeModal('bookingDetailModal');
                loadBookings(currentFilter);
            } else {
                showToast(res.message, 'error');
            }
        }
    });
}

function closeModal(id) { $(`#${id}`).css('display','none'); }
function exportData() { showToast('Fitur export akan segera tersedia', 'info'); }
</script>