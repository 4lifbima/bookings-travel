<?php
// admin/pages/categories.php
$conn = db_connect();
$categories = $conn->query("SELECT * FROM ticket_categories ORDER BY price ASC");
$cats_arr = [];
while ($c = $categories->fetch_assoc()) $cats_arr[] = $c;
$conn->close();
?>
<div class="flex items-center justify-between mb-5">
    <div></div>
    <button onclick="openCatModal()" class="admin-btn-primary flex items-center gap-2">
        <iconify-icon icon="mdi:plus" width="16"></iconify-icon>
        Tambah Kategori
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($cats_arr as $cat): ?>
    <div class="admin-card p-5">
        <div class="flex items-start justify-between mb-3">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background:<?php echo htmlspecialchars($cat['color']); ?>20;">
                <iconify-icon icon="<?php echo htmlspecialchars($cat['icon']); ?>" width="24" style="color:<?php echo htmlspecialchars($cat['color']); ?>;"></iconify-icon>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-1 rounded-lg font-semibold" style="background:<?php echo $cat['is_active'] ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'; ?>; color:<?php echo $cat['is_active'] ? '#10b981' : '#ef4444'; ?>;">
                    <?php echo $cat['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                </span>
            </div>
        </div>
        <div class="font-bold text-base mb-1" style="color:var(--text);"><?php echo htmlspecialchars($cat['name']); ?></div>
        <div class="text-xs mb-3" style="color:var(--text-muted);"><?php echo htmlspecialchars($cat['description']); ?></div>
        <div class="flex items-center justify-between">
            <div>
                <div class="font-extrabold text-base" style="color:var(--primary);"><?php echo format_rupiah($cat['price']); ?></div>
                <div class="text-xs" style="color:var(--text-muted);">Kuota: <?php echo $cat['quota_per_day']; ?>/hari</div>
            </div>
            <div class="flex gap-2">
                <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)" class="admin-btn-sm btn-edit">
                    <iconify-icon icon="mdi:pencil-outline" width="12"></iconify-icon>
                </button>
                <button onclick="toggleCatStatus(<?php echo $cat['id']; ?>, <?php echo $cat['is_active']; ?>)" class="admin-btn-sm <?php echo $cat['is_active'] ? 'btn-delete' : 'btn-confirm'; ?>">
                    <iconify-icon icon="<?php echo $cat['is_active'] ? 'mdi:eye-off-outline' : 'mdi:eye-outline'; ?>" width="12"></iconify-icon>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Category Modal -->
<div id="catModal" class="modal-overlay" style="display:none;" onclick="closeCatModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="p-5 border-b" style="border-color:var(--border);">
            <div class="flex items-center justify-between">
                <div class="font-bold text-base" style="color:var(--text);" id="catModalTitle">Tambah Kategori</div>
                <button onclick="closeCatModal()" class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:var(--bg);">
                    <iconify-icon icon="mdi:close" width="16" style="color:var(--text-muted);"></iconify-icon>
                </button>
            </div>
        </div>
        <form id="catForm" class="p-5 space-y-4">
            <input type="hidden" name="cat_id" id="cat_id">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">NAMA KATEGORI</label>
                    <input type="text" name="name" id="cat_name" class="admin-input" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">DESKRIPSI</label>
                    <input type="text" name="description" id="cat_description" class="admin-input">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">HARGA (Rp)</label>
                    <input type="number" name="price" id="cat_price" class="admin-input" required min="0">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">KUOTA/HARI</label>
                    <input type="number" name="quota_per_day" id="cat_quota" class="admin-input" required min="1">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">ICON (Iconify)</label>
                    <input type="text" name="icon" id="cat_icon" class="admin-input" placeholder="mdi:ticket">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted);">WARNA</label>
                    <input type="color" name="color" id="cat_color" class="admin-input h-11" value="#f54518">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="admin-btn-primary flex-1">Simpan</button>
                <button type="button" onclick="closeCatModal()" class="admin-btn-sm px-5 py-2.5" style="background:var(--bg);color:var(--text-muted);border:1px solid var(--border);font-size:13px;">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCatModal(data = null) {
    if (data) {
        $('#catModalTitle').text('Edit Kategori');
        $('#cat_id').val(data.id);
        $('#cat_name').val(data.name);
        $('#cat_description').val(data.description);
        $('#cat_price').val(data.price);
        $('#cat_quota').val(data.quota_per_day);
        $('#cat_icon').val(data.icon);
        $('#cat_color').val(data.color);
    } else {
        $('#catModalTitle').text('Tambah Kategori');
        $('#catForm')[0].reset();
        $('#cat_id').val('');
    }
    $('#catModal').css('display','flex');
}

function editCategory(data) { openCatModal(data); }
function closeCatModal() { $('#catModal').css('display','none'); }

$('#catForm').on('submit', function(e) {
    e.preventDefault();
    const data = {
        action: 'save_category',
        cat_id: $('#cat_id').val(),
        name: $('#cat_name').val(),
        description: $('#cat_description').val(),
        price: $('#cat_price').val(),
        quota_per_day: $('#cat_quota').val(),
        icon: $('#cat_icon').val(),
        color: $('#cat_color').val(),
    };
    showLoading();
    $.ajax({ url: '../ajax/admin.php', method:'POST', data, dataType:'json',
        success: function(res) {
            hideLoading();
            if (res.status === 'success') { showToast(res.message, 'success'); closeCatModal(); setTimeout(() => location.reload(), 800); }
            else showToast(res.message, 'error');
        }
    });
});

function toggleCatStatus(id, current) {
    const newStatus = current ? 0 : 1;
    const msg = current ? 'Nonaktifkan kategori ini?' : 'Aktifkan kategori ini?';
    Swal.fire({ title: msg, icon: 'question', showCancelButton: true, confirmButtonColor: '#f54518', confirmButtonText: 'Ya' }).then(r => {
        if (r.isConfirmed) {
            $.ajax({ url: '../ajax/admin.php', method:'POST', data:{ action:'toggle_cat_status', id, status:newStatus }, dataType:'json',
                success: function(res) {
                    if (res.status === 'success') { showToast(res.message, 'success'); setTimeout(() => location.reload(), 800); }
                    else showToast(res.message, 'error');
                }
            });
        }
    });
}
</script>