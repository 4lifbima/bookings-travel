<?php
// admin/pages/tickets.php
?>
<div class="admin-card p-5">
    <div class="flex items-center justify-between mb-4">
        <div class="font-bold text-base" style="color:var(--text);">Manajemen Tiket</div>
        <div class="flex items-center gap-3">
            <select id="ticket_status_filter" class="admin-input" style="max-width:160px;">
                <option value="all">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="used">Digunakan</option>
                <option value="cancelled">Dibatalkan</option>
            </select>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table id="ticketsTable" class="w-full" style="width:100%;">
            <thead><tr>
                <th>Kode Tiket</th>
                <th>Kode Booking</th>
                <th>Pengunjung</th>
                <th>Kategori</th>
                <th>Tgl Kunjungan</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
let ticketTable;

$(document).ready(function() {
    ticketTable = $('#ticketsTable').DataTable({
        language: { search:'Cari:', lengthMenu:'Tampilkan _MENU_', info:'_START_-_END_ dari _TOTAL_', emptyTable:'Tidak ada tiket' },
        columns: [
            { data: 'ticket_code', render: d => `<span class="font-mono text-xs font-bold" style="color:var(--primary);">${d}</span>` },
            { data: 'booking_code', render: d => `<span class="font-mono text-xs">${d}</span>` },
            { data: 'user_name' },
            { data: 'category_name', render: d => `<span class="text-xs">${d}</span>` },
            { data: 'visit_date', render: d => new Date(d).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}) },
            { data: 'status', render: d => {
                const cfg = {active:['#10b981','Aktif','mdi:check-circle-outline'], used:['#6b7280','Digunakan','mdi:check-decagram-outline'], cancelled:['#ef4444','Batal','mdi:close-circle-outline']};
                const c = cfg[d] || cfg.active;
                return `<span class="badge" style="background:${c[0]}20;color:${c[0]};"><iconify-icon icon="${c[2]}" width="10"></iconify-icon>${c[1]}</span>`;
            }},
            { data: 'ticket_code', render: (code, t, r) => `<div class="flex gap-1.5">
                ${r.status === 'active' ? `<button onclick="markTicketUsed('${code}')" class="admin-btn-sm btn-confirm" title="Tandai Digunakan">
                    <iconify-icon icon="mdi:qrcode-scan" width="12"></iconify-icon> Scan
                </button>` : ''}
                <button onclick="viewTicketBooking('${r.booking_code}')" class="admin-btn-sm btn-view">
                    <iconify-icon icon="mdi:eye-outline" width="12"></iconify-icon>
                </button>
            </div>` }
        ]
    });

    loadTickets('all');

    $('#ticket_status_filter').on('change', function() {
        loadTickets($(this).val());
    });
});

function loadTickets(filter) {
    showLoading();
    $.ajax({
        url: '../ajax/admin.php',
        data: { action: 'get_tickets', filter },
        dataType: 'json',
        success: function(res) {
            hideLoading();
            ticketTable.clear();
            if (res.status === 'success') {
                ticketTable.rows.add(res.data).draw();
            } else {
                showToast('Gagal memuat: ' + res.message, 'error');
            }
        },
        error: function(xhr) {
            hideLoading();
            showToast('Gagal memuat data tiket', 'error');
            console.error('AJAX Error:', xhr.responseText);
        }
    });
}

function markTicketUsed(code) {
    Swal.fire({
        title: 'Tandai Tiket Digunakan',
        html: `<div class="text-sm text-gray-500">Kode: <b>${code}</b></div><div class="text-sm mt-2">Konfirmasi bahwa tiket ini telah digunakan oleh pengunjung?</div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        confirmButtonText: 'Konfirmasi Digunakan',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (r.isConfirmed) {
            showLoading();
            $.ajax({
                url: '../ajax/admin.php',
                method: 'POST',
                data: { action: 'mark_ticket_used', code },
                dataType: 'json',
                success: function(res) {
                    hideLoading();
                    if (res.status === 'success') {
                        showToast('Tiket berhasil ditandai digunakan', 'success');
                        loadTickets($('#ticket_status_filter').val());
                    } else {
                        showToast(res.message, 'error');
                    }
                },
                error: function() { hideLoading(); showToast('Terjadi kesalahan', 'error'); }
            });
        }
    });
}

function viewTicketBooking(bookingCode) {
    window.location = `?page=bookings`;
}
</script>