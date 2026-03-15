<?php
// user/auth.php
require_once __DIR__ . '/../includes/config.php';
$is_register = ($page === 'register');
?>

<style>
.auth-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.auth-hero {
    background: linear-gradient(135deg, #f54518 0%, #ff6b35 60%, #ff9a6c 100%);
    padding: 48px 24px 80px;
    position: relative;
    overflow: hidden;
}
.auth-hero::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0; right: 0;
    height: 50px;
    background: var(--bg);
    border-radius: 40px 40px 0 0;
}
.auth-card {
    background: var(--bg);
    flex: 1;
    padding: 0 24px 32px;
    margin-top: -20px;
}
</style>

<div class="auth-container">
    <!-- Theme Toggle -->
    <div class="absolute top-4 right-4 z-50">
        <button onclick="toggleTheme()" class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:rgba(255,255,255,0.2);">
            <iconify-icon class="theme-icon" icon="mdi:weather-night" width="18" style="color:white;"></iconify-icon>
        </button>
    </div>

    <!-- Hero Section -->
    <div class="auth-hero">
        <div class="absolute top-4 left-0 right-0 bottom-0 overflow-hidden pointer-events-none">
            <div class="absolute top-4 right-6 w-32 h-32 rounded-full opacity-20" style="background:white;"></div>
            <div class="absolute bottom-10 left-4 w-20 h-20 rounded-full opacity-15" style="background:white;"></div>
        </div>

        <a href="index.php" class="inline-flex items-center gap-2 text-white opacity-80 mb-6 text-sm">
            <iconify-icon icon="mdi:arrow-left" width="18"></iconify-icon>
            Kembali
        </a>

        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background:rgba(255,255,255,0.25);">
                <iconify-icon icon="mdi:waves" width="28" style="color:white;"></iconify-icon>
            </div>
            <div>
                <div class="text-white font-extrabold text-xl">Danau Paisupok</div>
                <div class="text-xs" style="color:rgba(255,255,255,0.75);">Wisata Alam Gorontalo</div>
            </div>
        </div>

        <h2 class="text-white font-bold text-2xl"><?php echo $is_register ? 'Buat Akun Baru' : 'Selamat Datang Kembali'; ?></h2>
        <p style="color:rgba(255,255,255,0.8);" class="text-sm mt-1">
            <?php echo $is_register ? 'Daftar untuk mulai memesan tiket' : 'Masuk untuk melanjutkan'; ?>
        </p>
    </div>

    <!-- Form Card -->
    <div class="auth-card">

        <?php if ($is_register): ?>
        <!-- Register Form -->
        <form id="registerForm" class="space-y-4 mt-6">
            <div>
                <label class="block text-xs font-semibold mb-2" style="color:var(--text-muted);">NAMA LENGKAP</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <iconify-icon icon="mdi:account-outline" width="18" style="color:var(--text-muted);"></iconify-icon>
                    </span>
                    <input type="text" name="name" id="reg_name" placeholder="Nama lengkap Anda" class="form-input pl-11" required>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-2" style="color:var(--text-muted);">EMAIL</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <iconify-icon icon="mdi:email-outline" width="18" style="color:var(--text-muted);"></iconify-icon>
                    </span>
                    <input type="email" name="email" id="reg_email" placeholder="email@contoh.com" class="form-input pl-11" required>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-2" style="color:var(--text-muted);">NOMOR TELEPON</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <iconify-icon icon="mdi:phone-outline" width="18" style="color:var(--text-muted);"></iconify-icon>
                    </span>
                    <input type="tel" name="phone" id="reg_phone" placeholder="08xxxxxxxxxx" class="form-input pl-11">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-2" style="color:var(--text-muted);">PASSWORD</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <iconify-icon icon="mdi:lock-outline" width="18" style="color:var(--text-muted);"></iconify-icon>
                    </span>
                    <input type="password" name="password" id="reg_password" placeholder="Minimal 8 karakter" class="form-input pl-11 pr-11" required>
                    <button type="button" onclick="togglePass('reg_password','eyeReg')" class="absolute right-4 top-1/2 -translate-y-1/2">
                        <iconify-icon id="eyeReg" icon="mdi:eye-off-outline" width="18" style="color:var(--text-muted);"></iconify-icon>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-2" style="color:var(--text-muted);">KONFIRMASI PASSWORD</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <iconify-icon icon="mdi:lock-check-outline" width="18" style="color:var(--text-muted);"></iconify-icon>
                    </span>
                    <input type="password" name="password_confirm" id="reg_password2" placeholder="Ulangi password" class="form-input pl-11" required>
                </div>
            </div>
            <button type="submit" class="btn-primary w-full mt-2 ripple">
                <span class="flex items-center justify-center gap-2">
                    <iconify-icon icon="mdi:account-plus-outline" width="18"></iconify-icon>
                    Buat Akun
                </span>
            </button>
        </form>

        <p class="text-center text-sm mt-5" style="color:var(--text-muted);">
            Sudah punya akun?
            <a href="index.php?page=login" class="font-bold" style="color:var(--primary);">Masuk Sekarang</a>
        </p>

        <?php else: ?>
        <!-- Login Form -->
        <form id="loginForm" class="space-y-4 mt-6">
            <div>
                <label class="block text-xs font-semibold mb-2" style="color:var(--text-muted);">EMAIL</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <iconify-icon icon="mdi:email-outline" width="18" style="color:var(--text-muted);"></iconify-icon>
                    </span>
                    <input type="email" name="email" id="login_email" placeholder="email@contoh.com" class="form-input pl-11" required>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-2" style="color:var(--text-muted);">PASSWORD</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <iconify-icon icon="mdi:lock-outline" width="18" style="color:var(--text-muted);"></iconify-icon>
                    </span>
                    <input type="password" name="password" id="login_password" placeholder="Masukkan password" class="form-input pl-11 pr-11" required>
                    <button type="button" onclick="togglePass('login_password','eyeLogin')" class="absolute right-4 top-1/2 -translate-y-1/2">
                        <iconify-icon id="eyeLogin" icon="mdi:eye-off-outline" width="18" style="color:var(--text-muted);"></iconify-icon>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full ripple">
                <span class="flex items-center justify-center gap-2">
                    <iconify-icon icon="mdi:login" width="18"></iconify-icon>
                    Masuk
                </span>
            </button>
        </form>

        <p class="text-center text-sm mt-5" style="color:var(--text-muted);">
            Belum punya akun?
            <a href="index.php?page=register" class="font-bold" style="color:var(--primary);">Daftar Sekarang</a>
        </p>
        <?php endif; ?>

    </div>
</div>

<script>
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('icon', 'mdi:eye-outline');
    } else {
        input.type = 'password';
        icon.setAttribute('icon', 'mdi:eye-off-outline');
    }
}

// Login Form
$('#loginForm').on('submit', function(e) {
    e.preventDefault();
    const btn = $(this).find('button[type=submit]');
    const email = $('#login_email').val();
    const password = $('#login_password').val();
    if (!email || !password) { showToast('Isi semua field', 'warning'); return; }

    btn.prop('disabled', true).html('<span class="flex items-center justify-center gap-2"><iconify-icon icon="mdi:loading" class="animate-spin" width="18"></iconify-icon>Memproses...</span>');

    $.ajax({
        url: 'ajax/auth.php',
        method: 'POST',
        data: { action: 'login', email, password },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast('Berhasil masuk!', 'success');
                setTimeout(() => { window.location = res.redirect; }, 800);
            } else {
                showToast(res.message, 'error');
                btn.prop('disabled', false).html('<span class="flex items-center justify-center gap-2"><iconify-icon icon="mdi:login" width="18"></iconify-icon>Masuk</span>');
            }
        },
        error: function() {
            showToast('Terjadi kesalahan. Coba lagi.', 'error');
            btn.prop('disabled', false).html('<span class="flex items-center justify-center gap-2"><iconify-icon icon="mdi:login" width="18"></iconify-icon>Masuk</span>');
        }
    });
});

// Register Form
$('#registerForm').on('submit', function(e) {
    e.preventDefault();
    const name = $('#reg_name').val().trim();
    const email = $('#reg_email').val().trim();
    const phone = $('#reg_phone').val().trim();
    const password = $('#reg_password').val();
    const confirm = $('#reg_password2').val();

    if (!name || !email || !password) { showToast('Isi semua field yang wajib', 'warning'); return; }
    if (password !== confirm) { showToast('Konfirmasi password tidak cocok', 'error'); return; }
    if (password.length < 8) { showToast('Password minimal 8 karakter', 'error'); return; }

    const btn = $(this).find('button[type=submit]');
    btn.prop('disabled', true).html('<span class="flex items-center justify-center gap-2"><iconify-icon icon="mdi:loading" class="animate-spin" width="18"></iconify-icon>Mendaftar...</span>');

    $.ajax({
        url: 'ajax/auth.php',
        method: 'POST',
        data: { action: 'register', name, email, phone, password },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast('Akun berhasil dibuat!', 'success');
                setTimeout(() => { window.location = 'index.php?page=login'; }, 1000);
            } else {
                showToast(res.message, 'error');
                btn.prop('disabled', false).html('<span class="flex items-center justify-center gap-2"><iconify-icon icon="mdi:account-plus-outline" width="18"></iconify-icon>Buat Akun</span>');
            }
        }
    });
});
</script>