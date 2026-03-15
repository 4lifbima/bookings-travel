<?php
// admin/pages/settings.php
$conn = db_connect();
$settings_q = $conn->query("SELECT setting_key, setting_value FROM site_settings");
$s = [];
while ($row = $settings_q->fetch_assoc()) $s[$row['setting_key']] = $row['setting_value'];

// Announcement list
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
$ann_arr = [];
while ($ann = $announcements->fetch_assoc()) $ann_arr[] = $ann;
$conn->close();
?>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

    <!-- General Settings -->
    <div class="admin-card p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(245,69,24,0.1);">
                <iconify-icon icon="mdi:cog-outline" width="20" style="color:var(--primary);"></iconify-icon>
            </div>
            <div class="font-bold text-base" style="color:var(--text);">Pengaturan Umum</div>
        </div>
        <form id="generalSettingsForm" class="space-y-4">
            <div>
                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">JAM BUKA</label>
                <input type="time" name="open_time" class="admin-input" value="<?php echo htmlspecialchars($s['open_time'] ?? '07:00'); ?>">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">JAM TUTUP</label>
                <input type="time" name="close_time" class="admin-input" value="<?php echo htmlspecialchars($s['close_time'] ?? '18:00'); ?>">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">BATAS BOOKING (HARI)</label>
                <input type="number" name="max_booking_days" class="admin-input" value="<?php echo htmlspecialchars($s['max_booking_days'] ?? '30'); ?>" min="1" max="365">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">MINIMAL BOOKING (JAM SEBELUM KUNJUNGAN)</label>
                <input type="number" name="min_booking_hours" class="admin-input" value="<?php echo htmlspecialchars($s['min_booking_hours'] ?? '24'); ?>" min="1">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">NOMOR WHATSAPP</label>
                <input type="text" name="whatsapp_number" class="admin-input" value="<?php echo htmlspecialchars($s['whatsapp_number'] ?? ''); ?>" placeholder="628xxx">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">LOKASI</label>
                <input type="text" name="location" class="admin-input" value="<?php echo htmlspecialchars($s['location'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">DESKRIPSI WISATA</label>
                <textarea name="about" class="admin-input" rows="3" style="resize:vertical;"><?php echo htmlspecialchars($s['about'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="admin-btn-primary w-full flex items-center justify-center gap-2">
                <iconify-icon icon="mdi:content-save-outline" width="16"></iconify-icon>
                Simpan Pengaturan
            </button>
        </form>
    </div>

    <!-- Announcements -->
    <div class="admin-card p-5">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(59,130,246,0.1);">
                    <iconify-icon icon="mdi:bullhorn-outline" width="20" style="color:#3b82f6;"></iconify-icon>
                </div>
                <div class="font-bold text-base" style="color:var(--text);">Pengumuman</div>
            </div>
            <button onclick="openAnnModal()" class="admin-btn-sm flex items-center gap-1.5 px-4 py-2" style="background:rgba(245,69,24,0.1);color:var(--primary);border-radius:10px;">
                <iconify-icon icon="mdi:plus" width="14"></iconify-icon>
                Tambah
            </button>
        </div>

        <div class="space-y-3">
            <?php foreach ($ann_arr as $ann):
                $ann_colors = ['info'=>['#3b82f6','mdi:information-outline'],'warning'=>['#f59e0b','mdi:alert-outline'],'success'=>['#10b981','mdi:check-circle-outline'],'danger'=>['#ef4444','mdi:close-circle-outline']];
                [$color, $icon] = $ann_colors[$ann['type']] ?? $ann_colors['info'];
            ?>
            <div class="p-3 rounded-xl flex items-start gap-3" style="border:1px solid var(--border);background:var(--bg);">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:<?php echo $color; ?>15;">
                    <iconify-icon icon="<?php echo $icon; ?>" width="16" style="color:<?php echo $color; ?>;"></iconify-icon>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm" style="color:var(--text);"><?php echo htmlspecialchars($ann['title']); ?></div>
                    <div class="text-xs mt-0.5 line-clamp-2" style="color:var(--text-muted);"><?php echo htmlspecialchars($ann['content']); ?></div>
                </div>
                <div class="flex gap-1.5 flex-shrink-0">
                    <button onclick="toggleAnnStatus(<?php echo $ann['id']; ?>, <?php echo $ann['is_active']; ?>)" class="admin-btn-sm <?php echo $ann['is_active'] ? 'btn-confirm' : 'btn-delete'; ?>" style="width:28px;height:28px;padding:0;justify-content:center;">
                        <iconify-icon icon="<?php echo $ann['is_active'] ? 'mdi:eye-outline' : 'mdi:eye-off-outline'; ?>" width="12"></iconify-icon>
                    </button>
                    <button onclick="deleteAnn(<?php echo $ann['id']; ?>)" class="admin-btn-sm btn-delete" style="width:28px;height:28px;padding:0;justify-content:center;">
                        <iconify-icon icon="mdi:trash-can-outline" width="12"></iconify-icon>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($ann_arr)): ?>
            <div class="text-center py-8 text-sm" style="color:var(--text-muted);">
                <iconify-icon icon="mdi:bullhorn-variant-outline" width="32" class="mb-2 opacity-30"></iconify-icon><br>
                Belum ada pengumuman
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Announcement Modal -->
<div id="annModal" class="modal-overlay" style="display:none;" onclick="closeAnnModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="p-5 border-b" style="border-color:var(--border);">
            <div class="flex items-center justify-between">
                <div class="font-bold text-base" style="color:var(--text);">Tambah Pengumuman</div>
                <button onclick="closeAnnModal()" class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:var(--bg);">
                    <iconify-icon icon="mdi:close" width="16" style="color:var(--text-muted);"></iconify-icon>
                </button>
            </div>
        </div>
        <form id="annForm" class="p-5 space-y-4">
            <div>
                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">JUDUL</label>
                <input type="text" name="title" class="admin-input" required placeholder="Judul pengumuman">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">ISI PENGUMUMAN</label>
                <textarea name="content" class="admin-input" rows="4" required placeholder="Isi pengumuman..." style="resize:vertical;"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">TIPE</label>
                <select name="type" class="admin-input">
                    <option value="info">Info</option>
                    <option value="success">Sukses / Promo</option>
                    <option value="warning">Peringatan</option>
                    <option value="danger">Penting / Bahaya</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="admin-btn-primary flex-1">Simpan Pengumuman</button>
                <button type="button" onclick="closeAnnModal()" class="admin-btn-sm px-5 py-2.5" style="background:var(--bg);color:var(--text-muted);border:1px solid var(--border);font-size:13px;">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
// General Settings
$('#generalSettingsForm').on('submit', function(e) {
    e.preventDefault();
    const formData = $(this).serializeArray();
    const data = { action: 'save_settings' };
    formData.forEach(f => { data[f.name] = f.value; });

    showLoading();
    $.ajax({ url:'../ajax/admin.php', method:'POST', data, dataType:'json',
        success: function(res) {
            hideLoading();
            showToast(res.message, res.status==='success' ? 'success' : 'error');
        }
    });
});

// Announcement functions
function openAnnModal() { $('#annModal').css('display','flex'); }
function closeAnnModal() { $('#annModal').css('display','none'); $('#annForm')[0].reset(); }

$('#annForm').on('submit', function(e) {
    e.preventDefault();
    const data = {
        action: 'save_announcement',
        title: $('[name=title]').val(),
        content: $('[name=content]').val(),
        type: $('[name=type]').val()
    };
    showLoading();
    $.ajax({ url:'../ajax/admin.php', method:'POST', data, dataType:'json',
        success: function(res) {
            hideLoading();
            if (res.status === 'success') { showToast(res.message,'success'); closeAnnModal(); setTimeout(()=>location.reload(),800); }
            else showToast(res.message,'error');
        }
    });
});

function toggleAnnStatus(id, current) {
    $.ajax({ url:'../ajax/admin.php', method:'POST', data:{action:'toggle_ann_status', id, status:current?0:1}, dataType:'json',
        success: function(res) {
            if(res.status==='success') { showToast(res.message,'success'); setTimeout(()=>location.reload(),600); }
            else showToast(res.message,'error');
        }
    });
}

function deleteAnn(id) {
    Swal.fire({ title:'Hapus Pengumuman?', text:'Tindakan ini tidak dapat dibatalkan.', icon:'warning', showCancelButton:true, confirmButtonColor:'#ef4444', confirmButtonText:'Hapus', cancelButtonText:'Batal' }).then(r => {
        if (r.isConfirmed) {
            $.ajax({ url:'../ajax/admin.php', method:'POST', data:{action:'delete_announcement', id}, dataType:'json',
                success: function(res) {
                    if(res.status==='success') { showToast(res.message,'success'); setTimeout(()=>location.reload(),600); }
                    else showToast(res.message,'error');
                }
            });
        }
    });
}
</script>