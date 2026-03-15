<?php
// admin/pages/users.php
?>
<div class="admin-card p-5">
    <div class="flex items-center justify-between mb-4">
        <div class="font-bold text-base" style="color:var(--text);">Daftar Pengguna</div>
    </div>
    <div class="overflow-x-auto">
        <table id="usersTable" class="w-full" style="width:100%;">
            <thead><tr>
                <th>Nama</th><th>Email</th><th>Telepon</th><th>Role</th><th>Status</th><th>Terdaftar</th><th>Aksi</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    const adminAjaxUrl = '../ajax/admin.php';

    const table = $('#usersTable').DataTable({
        language: { search:'Cari:', lengthMenu:'Tampilkan _MENU_', info:'_START_-_END_ dari _TOTAL_', emptyTable:'Tidak ada data' },
        columns: [
            { data: 'name', render: (d,t,r) => `<div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(245,69,24,0.1);">
                    <iconify-icon icon="${r.role==='admin'?'mdi:shield-account':'mdi:account'}" width="14" style="color:var(--primary);"></iconify-icon>
                </div><span class="font-semibold text-sm">${d}</span></div>` },
            { data: 'email', render: d => `<span class="text-xs">${d}</span>` },
            { data: 'phone', render: d => `<span class="text-xs">${d||'-'}</span>` },
            { data: 'role', render: d => `<span class="badge" style="background:${d==='admin'?'rgba(245,69,24,0.12)':'rgba(59,130,246,0.1)'};color:${d==='admin'?'#f54518':'#3b82f6'};">${d==='admin'?'Admin':'User'}</span>` },
            { data: 'is_active', render: d => `<span class="badge" style="background:${d?'rgba(16,185,129,0.1)':'rgba(239,68,68,0.1)'};color:${d?'#10b981':'#ef4444'};">${d?'Aktif':'Nonaktif'}</span>` },
            { data: 'created_at', render: d => new Date(d).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}) },
            { data: 'id', render: (id,t,r) => `<div class="flex gap-1.5">
                <button onclick="toggleUserStatus(${id},${r.is_active})" class="admin-btn-sm ${r.is_active?'btn-delete':'btn-confirm'}">
                    <iconify-icon icon="${r.is_active?'mdi:account-off-outline':'mdi:account-check-outline'}" width="12"></iconify-icon>
                </button>
                ${r.role!=='admin'?`<button onclick="viewUserBookings(${id},'${r.name}')" class="admin-btn-sm btn-view"><iconify-icon icon="mdi:ticket-outline" width="12"></iconify-icon></button>`:''}
            </div>` }
        ]
    });

    $.ajax({
        url: adminAjaxUrl,
        method: 'GET',
        data: { action:'get_users' },
        dataType:'json',
        success: function(res) {
            if (res && res.status === 'success') {
                table.rows.add(res.data).draw();
            } else {
                showToast((res && res.message) ? res.message : 'Gagal memuat data pengguna', 'error');
            }
        },
        error: function(xhr) {
            showToast('Request user gagal (' + xhr.status + ')', 'error');
            console.error('get_users error:', xhr.responseText);
        }
    });
});

function toggleUserStatus(id, current) {
    const msg = current ? 'Nonaktifkan akun ini?' : 'Aktifkan akun ini?';
    Swal.fire({ title: msg, icon:'warning', showCancelButton:true, confirmButtonColor:'#f54518', confirmButtonText:'Ya', cancelButtonText:'Batal' }).then(r => {
        if (r.isConfirmed) {
            $.ajax({ url:'../ajax/admin.php', method:'POST', data:{action:'toggle_user_status',id,status:current?0:1}, dataType:'json',
                success: function(res) {
                    if(res.status==='success'){ showToast(res.message,'success'); setTimeout(()=>location.reload(),800); }
                    else showToast(res.message,'error');
                },
                error: function(xhr) {
                    showToast('Request gagal (' + xhr.status + ')', 'error');
                    console.error('toggle_user_status error:', xhr.responseText);
                }
            });
        }
    });
}

function viewUserBookings(userId, name) {
    window.location = `?page=bookings&user_id=${userId}`;
}
</script>