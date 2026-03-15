<?php
// user/booking.php
require_once __DIR__ . '/../includes/config.php';
$conn = db_connect();

$cats = $conn->query("SELECT * FROM ticket_categories WHERE is_active=1 ORDER BY price ASC");
$settings_q = $conn->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($s = $settings_q->fetch_assoc()) $settings[$s['setting_key']] = $s['setting_value'];

$min_date = date('Y-m-d', strtotime('+' . ($settings['min_booking_hours'] ?? 24) . ' hours'));
$max_date = date('Y-m-d', strtotime('+' . ($settings['max_booking_days'] ?? 30) . ' days'));
$conn->close();
?>

<!-- App Header -->
<header class="app-header px-5 py-4">
    <div class="flex items-center gap-4">
        <a href="index.php" class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg-secondary);">
            <iconify-icon icon="mdi:arrow-left" width="20" style="color:var(--text);"></iconify-icon>
        </a>
        <div>
            <h1 class="font-bold text-base" style="color:var(--text);">Pesan Tiket</h1>
            <p class="text-xs" style="color:var(--text-muted);">Danau Paisupok</p>
        </div>
        <button onclick="toggleTheme()" class="ml-auto w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--bg-secondary);">
            <iconify-icon class="theme-icon" icon="mdi:weather-night" width="18" style="color:var(--text-muted);"></iconify-icon>
        </button>
    </div>
</header>

<div class="page-content px-4 pt-4">

    <!-- Step Indicator -->
    <div class="flex items-center gap-2 mb-6">
        <?php foreach (['Pilih Tiket','Tanggal & Jumlah','Konfirmasi'] as $i => $step): $num = $i + 1; ?>
        <div class="flex items-center <?php echo $i < 2 ? 'flex-1' : ''; ?>">
            <div class="flex items-center gap-2">
                <div class="step-circle w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all" 
                     id="step-circle-<?php echo $num; ?>"
                     style="background:<?php echo $num===1 ? 'var(--primary)' : 'var(--bg-secondary)'; ?>; color:<?php echo $num===1 ? 'white' : 'var(--text-muted)'; ?>;">
                    <?php echo $num; ?>
                </div>
                <span class="text-xs font-semibold hidden sm:block" style="color:<?php echo $num===1 ? 'var(--primary)' : 'var(--text-muted)'; ?>;" id="step-label-<?php echo $num; ?>"><?php echo $step; ?></span>
            </div>
            <?php if ($i < 2): ?>
            <div class="flex-1 h-0.5 mx-2 rounded-full" style="background:var(--border);" id="step-line-<?php echo $num; ?>"></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Step 1: Select Tickets -->
    <div id="step1">
        <h2 class="font-bold text-base mb-4" style="color:var(--text);">Pilih Jenis Tiket</h2>
        <div class="space-y-3" id="ticket-list">
            <?php
            $cats_arr = [];
            while ($cat = $cats->fetch_assoc()) $cats_arr[] = $cat;
            foreach ($cats_arr as $cat):
            ?>
            <div class="card p-4 cursor-pointer transition-all" onclick="toggleTicket(<?php echo $cat['id']; ?>)" id="ticket-card-<?php echo $cat['id']; ?>"
                 data-id="<?php echo $cat['id']; ?>" 
                 data-name="<?php echo htmlspecialchars($cat['name']); ?>"
                 data-price="<?php echo $cat['price']; ?>"
                 data-icon="<?php echo htmlspecialchars($cat['icon']); ?>"
                 data-color="<?php echo htmlspecialchars($cat['color']); ?>">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:<?php echo $cat['color']; ?>20;">
                        <iconify-icon icon="<?php echo htmlspecialchars($cat['icon']); ?>" width="22" style="color:<?php echo htmlspecialchars($cat['color']); ?>;"></iconify-icon>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-sm" style="color:var(--text);"><?php echo htmlspecialchars($cat['name']); ?></div>
                        <div class="text-xs" style="color:var(--text-muted);"><?php echo htmlspecialchars($cat['description']); ?></div>
                    </div>
                    <div class="text-right">
                        <div class="font-extrabold text-sm" style="color:var(--primary);"><?php echo format_rupiah($cat['price']); ?></div>
                        <div class="text-xs" style="color:var(--text-muted);">per orang</div>
                    </div>
                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0 ml-1 transition-all" 
                         style="border-color:var(--border);" id="check-<?php echo $cat['id']; ?>">
                    </div>
                </div>
                <!-- Qty Stepper (hidden by default) -->
                <div class="mt-3 pt-3 hidden" style="border-top:1px solid var(--border);" id="qty-<?php echo $cat['id']; ?>">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold" style="color:var(--text-muted);">Jumlah Tiket</span>
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn" onclick="changeQty(<?php echo $cat['id']; ?>,-1,event)">
                                <iconify-icon icon="mdi:minus" width="14"></iconify-icon>
                            </button>
                            <span class="font-bold text-base w-8 text-center" style="color:var(--text);" id="qty-val-<?php echo $cat['id']; ?>">1</span>
                            <button type="button" class="qty-btn" onclick="changeQty(<?php echo $cat['id']; ?>,1,event)">
                                <iconify-icon icon="mdi:plus" width="14"></iconify-icon>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary bar -->
        <div class="fixed bottom-0 left-1/2 transform -translate-x-1/2 w-full max-width:430px pb-4 px-4 pt-3 z-30" 
             style="max-width:430px;background:var(--bg);border-top:1px solid var(--border);" id="step1-footer">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <div class="text-xs" style="color:var(--text-muted);">Total Sementara</div>
                    <div class="font-extrabold text-lg" style="color:var(--primary);" id="temp-total">Rp 0</div>
                </div>
                <div class="text-xs" style="color:var(--text-muted);" id="selected-count">0 tiket dipilih</div>
            </div>
            <button onclick="goToStep2()" class="btn-primary w-full ripple flex items-center justify-center gap-2">
                Lanjut ke Tanggal
                <iconify-icon icon="mdi:arrow-right" width="18"></iconify-icon>
            </button>
        </div>
    </div>

    <!-- Step 2: Date & Details -->
    <div id="step2" class="hidden">
        <h2 class="font-bold text-base mb-4" style="color:var(--text);">Tanggal Kunjungan</h2>

        <div class="card p-4 mb-4">
            <label class="block text-xs font-semibold mb-2" style="color:var(--text-muted);">TANGGAL KUNJUNGAN</label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 z-10">
                    <iconify-icon icon="mdi:calendar-outline" width="18" style="color:var(--primary);"></iconify-icon>
                </span>
                <input type="date" id="visit_date" class="form-input pl-11" 
                       min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>" 
                       value="<?php echo $min_date; ?>">
            </div>
            <p class="text-xs mt-2" style="color:var(--text-muted);">
                <iconify-icon icon="mdi:information-outline" width="14" class="inline"></iconify-icon>
                Pemesanan minimal <?php echo $settings['min_booking_hours'] ?? 24; ?> jam sebelum kunjungan
            </p>
        </div>

        <!-- Order Summary -->
        <div class="card p-4 mb-4">
            <h3 class="font-bold text-sm mb-3" style="color:var(--text);">Ringkasan Pesanan</h3>
            <div id="order-summary" class="space-y-2"></div>
            <div class="border-t mt-3 pt-3" style="border-color:var(--border);">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-sm" style="color:var(--text);">Total Pembayaran</span>
                    <span class="font-extrabold text-lg" style="color:var(--primary);" id="final-total">Rp 0</span>
                </div>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="card p-4 mb-24">
            <h3 class="font-bold text-sm mb-3" style="color:var(--text);">Metode Pembayaran</h3>
            <div class="space-y-2">
                <?php
                $payments = [
                    ['transfer_bank', 'mdi:bank-outline', 'Transfer Bank', 'BRI / BNI / Mandiri / BCA'],
                    ['qris', 'mdi:qrcode', 'QRIS', 'Semua dompet digital'],
                    ['tunai', 'mdi:cash', 'Bayar di Tempat', 'Bayar saat tiba di lokasi'],
                ];
                foreach ($payments as $p):
                ?>
                <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all" 
                       style="border:1.5px solid var(--border);" 
                       id="payment-label-<?php echo $p[0]; ?>"
                       onclick="selectPayment('<?php echo $p[0]; ?>')">
                    <input type="radio" name="payment_method" value="<?php echo $p[0]; ?>" class="hidden">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:var(--bg-secondary);">
                        <iconify-icon icon="<?php echo $p[1]; ?>" width="20" style="color:var(--primary);"></iconify-icon>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-sm" style="color:var(--text);"><?php echo $p[2]; ?></div>
                        <div class="text-xs" style="color:var(--text-muted);"><?php echo $p[3]; ?></div>
                    </div>
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center" 
                         style="border-color:var(--border);" id="radio-<?php echo $p[0]; ?>">
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="fixed bottom-0 left-1/2 transform -translate-x-1/2 w-full pb-4 px-4 pt-3 z-30" 
             style="max-width:430px;background:var(--bg);border-top:1px solid var(--border);">
            <div class="flex gap-3">
                <button onclick="goToStep1()" class="btn-outline flex-shrink-0 flex items-center gap-2 px-4">
                    <iconify-icon icon="mdi:arrow-left" width="18"></iconify-icon>
                </button>
                <button onclick="goToStep3()" class="btn-primary flex-1 ripple flex items-center justify-center gap-2">
                    Konfirmasi Pesanan
                    <iconify-icon icon="mdi:arrow-right" width="18"></iconify-icon>
                </button>
            </div>
        </div>
    </div>

    <!-- Step 3: Confirmation -->
    <div id="step3" class="hidden">
        <h2 class="font-bold text-base mb-4" style="color:var(--text);">Konfirmasi Pesanan</h2>
        <div id="confirm-preview" class="space-y-4 mb-24"></div>

        <div class="fixed bottom-0 left-1/2 transform -translate-x-1/2 w-full pb-4 px-4 pt-3 z-30" 
             style="max-width:430px;background:var(--bg);border-top:1px solid var(--border);">
            <div class="flex gap-3">
                <button onclick="goToStep2()" class="btn-outline flex-shrink-0 flex items-center gap-2 px-4">
                    <iconify-icon icon="mdi:arrow-left" width="18"></iconify-icon>
                </button>
                <button onclick="submitBooking()" class="btn-primary flex-1 ripple flex items-center justify-center gap-2" id="submitBtn">
                    <iconify-icon icon="mdi:check-circle-outline" width="18"></iconify-icon>
                    Buat Pesanan
                </button>
            </div>
        </div>
    </div>

</div>

<script>
let selectedTickets = {};
let selectedPayment = '';

// Toggle ticket selection
function toggleTicket(id) {
    const card = $(`#ticket-card-${id}`);
    const check = $(`#check-${id}`);
    const qty = $(`#qty-${id}`);

    if (selectedTickets[id]) {
        delete selectedTickets[id];
        card.css('border-color', 'var(--border)');
        check.css({'border-color':'var(--border)', 'background':''}).html('');
        qty.addClass('hidden');
    } else {
        const price = parseFloat(card.data('price'));
        const name = card.data('name');
        const icon = card.data('icon');
        const color = card.data('color');
        selectedTickets[id] = { id, name, price, quantity: 1, icon, color };
        card.css('border-color', '#f54518');
        check.css({'border-color':'#f54518','background':'#f54518'}).html('<iconify-icon icon="mdi:check" width="14" style="color:white;"></iconify-icon>');
        qty.removeClass('hidden');
    }
    updateTotals();
}

function changeQty(id, delta, e) {
    e.stopPropagation();
    if (!selectedTickets[id]) return;
    let q = selectedTickets[id].quantity + delta;
    if (q < 1) q = 1;
    if (q > 50) q = 50;
    selectedTickets[id].quantity = q;
    $(`#qty-val-${id}`).text(q);
    updateTotals();
}

function updateTotals() {
    let total = 0, count = 0;
    for (const id in selectedTickets) {
        total += selectedTickets[id].price * selectedTickets[id].quantity;
        count += selectedTickets[id].quantity;
    }
    $('#temp-total').text(formatRupiah(total));
    $('#selected-count').text(count + ' tiket dipilih');
}

function formatRupiah(n) {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function goToStep1() {
    $('#step2,#step3').addClass('hidden');
    $('#step1').removeClass('hidden');
    updateStepUI(1);
}

function goToStep2() {
    if (Object.keys(selectedTickets).length === 0) {
        showToast('Pilih minimal satu tiket', 'warning'); return;
    }
    $('#step1,#step3').addClass('hidden');
    $('#step2').removeClass('hidden');
    updateStepUI(2);
    renderOrderSummary();
}

function goToStep3() {
    if (!$('input[name=payment_method]:checked').val()) {
        showToast('Pilih metode pembayaran', 'warning'); return;
    }
    if (!$('#visit_date').val()) {
        showToast('Pilih tanggal kunjungan', 'warning'); return;
    }
    $('#step1,#step2').addClass('hidden');
    $('#step3').removeClass('hidden');
    updateStepUI(3);
    renderConfirmPreview();
}

function updateStepUI(active) {
    for (let i = 1; i <= 3; i++) {
        const circle = $(`#step-circle-${i}`);
        if (i < active) {
            circle.css({background:'#10b981',color:'white'}).html('<iconify-icon icon="mdi:check" width="14" style="color:white;"></iconify-icon>');
        } else if (i === active) {
            circle.css({background:'var(--primary)',color:'white'}).html(i);
        } else {
            circle.css({background:'var(--bg-secondary)',color:'var(--text-muted)'}).html(i);
        }
    }
}

function renderOrderSummary() {
    let html = '', total = 0;
    for (const id in selectedTickets) {
        const t = selectedTickets[id];
        const sub = t.price * t.quantity;
        total += sub;
        html += `<div class="flex justify-between items-center py-2" style="border-bottom:1px solid var(--border);">
            <div class="flex items-center gap-2">
                <iconify-icon icon="${t.icon}" width="16" style="color:${t.color};"></iconify-icon>
                <span class="text-sm font-medium" style="color:var(--text);">${t.name}</span>
                <span class="text-xs px-2 py-0.5 rounded-full" style="background:var(--bg-secondary);color:var(--text-muted);">x${t.quantity}</span>
            </div>
            <span class="font-semibold text-sm" style="color:var(--text);">${formatRupiah(sub)}</span>
        </div>`;
    }
    $('#order-summary').html(html);
    $('#final-total').text(formatRupiah(total));
}

function selectPayment(method) {
    selectedPayment = method;
    $('[id^=payment-label-]').css('border-color', 'var(--border)');
    $('[id^=radio-]').css({'border-color':'var(--border)','background':''}).html('');
    $(`#payment-label-${method}`).css('border-color', '#f54518');
    $(`#radio-${method}`).css({'border-color':'#f54518','background':'#f54518'}).html('<iconify-icon icon="mdi:check" width="10" style="color:white;"></iconify-icon>');
    $(`input[value=${method}]`).prop('checked', true);
}

function renderConfirmPreview() {
    const date = $('#visit_date').val();
    const dateFormatted = new Date(date).toLocaleDateString('id-ID', {weekday:'long',year:'numeric',month:'long',day:'numeric'});
    let items = '', total = 0;
    for (const id in selectedTickets) {
        const t = selectedTickets[id];
        const sub = t.price * t.quantity;
        total += sub;
        items += `<div class="flex justify-between py-2" style="border-bottom:1px solid var(--border);">
            <div>
                <div class="text-sm font-medium" style="color:var(--text);">${t.name}</div>
                <div class="text-xs" style="color:var(--text-muted);">${formatRupiah(t.price)} x ${t.quantity}</div>
            </div>
            <div class="font-semibold text-sm" style="color:var(--text);">${formatRupiah(sub)}</div>
        </div>`;
    }
    const payLabels = {transfer_bank:'Transfer Bank',qris:'QRIS',tunai:'Bayar di Tempat'};
    $('#confirm-preview').html(`
        <div class="card p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(245,69,24,0.1);">
                    <iconify-icon icon="mdi:calendar-check-outline" width="20" style="color:var(--primary);"></iconify-icon>
                </div>
                <div>
                    <div class="font-bold text-sm" style="color:var(--text);">Tanggal Kunjungan</div>
                    <div class="text-xs" style="color:var(--text-muted);">${dateFormatted}</div>
                </div>
            </div>
        </div>
        <div class="card p-4">
            <div class="font-bold text-sm mb-3" style="color:var(--text);">Detail Tiket</div>
            ${items}
            <div class="flex justify-between pt-3 mt-1">
                <span class="font-bold text-sm" style="color:var(--text);">Total</span>
                <span class="font-extrabold text-base" style="color:var(--primary);">${formatRupiah(total)}</span>
            </div>
        </div>
        <div class="card p-4">
            <div class="font-bold text-sm mb-2" style="color:var(--text);">Metode Pembayaran</div>
            <div class="text-sm" style="color:var(--text-muted);">${payLabels[selectedPayment] || selectedPayment}</div>
        </div>
        <div class="card p-3" style="background:rgba(245,69,24,0.04);border-color:rgba(245,69,24,0.15);">
            <div class="flex items-start gap-2">
                <iconify-icon icon="mdi:information-outline" width="16" style="color:var(--primary);" class="flex-shrink-0 mt-0.5"></iconify-icon>
                <div class="text-xs" style="color:var(--text-muted);">
                    Pesanan akan dikonfirmasi admin dalam 1x24 jam. Tiket dikirim ke email Anda setelah pembayaran diverifikasi.
                </div>
            </div>
        </div>
    `);
}

function submitBooking() {
    const btn = $('#submitBtn');
    btn.prop('disabled', true).html('<iconify-icon icon="mdi:loading" class="animate-spin" width="18"></iconify-icon> Memproses...');

    const items = [];
    for (const id in selectedTickets) {
        items.push({ category_id: id, quantity: selectedTickets[id].quantity });
    }

    $.ajax({
        url: 'ajax/booking.php',
        method: 'POST',
        data: {
            action: 'create_booking',
            visit_date: $('#visit_date').val(),
            payment_method: selectedPayment,
            items: JSON.stringify(items)
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Pemesanan Berhasil',
                    html: `<div class="text-center">
                        <div class="text-sm text-gray-500 mb-2">Kode Booking Anda:</div>
                        <div class="font-bold text-lg" style="color:#f54518;">${res.booking_code}</div>
                        <div class="text-xs text-gray-400 mt-2">Simpan kode ini untuk melacak pesanan</div>
                    </div>`,
                    confirmButtonText: 'Lihat Tiket Saya',
                    confirmButtonColor: '#f54518',
                    showCancelButton: true,
                    cancelButtonText: 'Kembali ke Beranda'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location = 'index.php?page=my-tickets';
                    } else {
                        window.location = 'index.php';
                    }
                });
            } else {
                showToast(res.message, 'error');
                btn.prop('disabled', false).html('<iconify-icon icon="mdi:check-circle-outline" width="18"></iconify-icon> Buat Pesanan');
            }
        },
        error: function() {
            showToast('Terjadi kesalahan. Coba lagi.', 'error');
            btn.prop('disabled', false).html('<iconify-icon icon="mdi:check-circle-outline" width="18"></iconify-icon> Buat Pesanan');
        }
    });
}

// Check for pre-selected category
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const catId = urlParams.get('cat');
    if (catId) { setTimeout(() => toggleTicket(catId), 300); }
});
</script>