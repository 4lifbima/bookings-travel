# Danau Paisupok - Sistem Booking Tiket Wisata

Website booking tiket masuk untuk Wisata Danau Paisupok, Gorontalo.
Dibangun dengan PHP Native Prosedural 8.3, Tailwind CSS CDN, jQuery, Ajax, DataTables.

---

## Struktur Proyek

```
danau-paisupok/
├── index.php                  # Router utama User Side
├── database.sql               # Schema database + data awal
├── includes/
│   └── config.php             # Konfigurasi DB, helper functions, session
├── user/
│   ├── home.php               # Halaman beranda
│   ├── auth.php               # Login & Register
│   ├── booking.php            # Pemesanan tiket (3 step)
│   ├── my_tickets.php         # Daftar tiket saya
│   ├── track.php              # Lacak tiket
│   ├── ticket_detail.php      # Detail tiket
│   └── profile.php            # Profil pengguna
├── admin/
│   ├── index.php              # Admin panel utama (sidebar layout)
│   └── pages/
│       ├── dashboard.php      # Dashboard statistik
│       ├── bookings.php       # Manajemen pemesanan
│       ├── tickets.php        # Manajemen tiket
│       ├── users.php          # Manajemen pengguna
│       ├── categories.php     # Kategori tiket
│       ├── reports.php        # Laporan & grafik
│       └── settings.php       # Pengaturan & pengumuman
└── ajax/
    ├── auth.php               # AJAX: Login, Register, Profil
    ├── booking.php            # AJAX: Booking, Track, Detail
    └── admin.php              # AJAX: Semua operasi admin
```

---

## Cara Instalasi

### 1. Persyaratan
- PHP 8.0+ (direkomendasikan 8.3)
- MySQL 5.7+ / MariaDB 10.3+
- Web Server: Apache / Nginx (atau PHP built-in server)
- Koneksi internet (untuk CDN: Tailwind, Iconify, jQuery, dll)

### 2. Konfigurasi Database
```sql
-- Import file database.sql ke MySQL:
mysql -u root -p < database.sql

-- Atau via phpMyAdmin:
-- Buat database "danau_paisupok"
-- Import file database.sql
```

### 3. Konfigurasi Aplikasi
Edit file `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // username MySQL Anda
define('DB_PASS', '');           // password MySQL Anda
define('DB_NAME', 'danau_paisupok');
define('BASE_URL', 'http://localhost/danau-paisupok');  // Sesuaikan URL
```

### 4. Jalankan Aplikasi
Tempatkan folder `danau-paisupok` di:
- XAMPP: `C:/xampp/htdocs/danau-paisupok`
- WAMP: `C:/wamp/www/danau-paisupok`
- Linux: `/var/www/html/danau-paisupok`

Akses via browser: `http://localhost/danau-paisupok`

---

## Akun Default

| Role  | Email                   | Password |
|-------|-------------------------|----------|
| Admin | admin@paisupok.com      | password |
| User  | user@paisupok.com       | password |

---

## Fitur Lengkap

### Role User (Mobile Android Frame):
- Halaman beranda dengan info wisata & tiket
- Pendaftaran & login akun
- Pemesanan tiket multi-kategori (3 langkah)
- Pilih metode pembayaran
- Lacak status pemesanan via kode booking
- Riwayat tiket dengan filter status
- Detail tiket dengan kode tiket unik
- Batalkan pesanan (hanya status pending)
- Edit profil & ubah password
- Dark mode / Light mode

### Role Admin (Dashboard Professional):
- Dashboard statistik real-time + chart
- Manajemen pemesanan (konfirmasi, tolak, selesaikan)
- Generate kode tiket otomatis saat konfirmasi
- Manajemen tiket (scan/tandai digunakan)
- Manajemen pengguna (aktif/nonaktif)
- Manajemen kategori tiket (CRUD)
- Laporan bulanan + grafik pendapatan
- Pengaturan operasional wisata
- Manajemen pengumuman
- Sidebar responsif (collapse di mobile)
- Dark mode / Light mode

---

## Teknologi yang Digunakan

| Teknologi       | Versi       | Keterangan                    |
|-----------------|-------------|-------------------------------|
| PHP             | 8.3         | Backend native prosedural     |
| MySQL           | 5.7+        | Database                      |
| Tailwind CSS    | CDN         | Styling utility-first         |
| jQuery          | 3.7.1       | DOM manipulation & AJAX       |
| DataTables      | 1.13.7      | Tabel interaktif admin        |
| Chart.js        | Latest CDN  | Grafik dashboard & laporan    |
| Iconify Icon    | 1.0.7       | Ikon (Material Design Icons)  |
| SweetAlert2     | 11          | Dialog konfirmasi & notifikasi|
| Plus Jakarta Sans | Google    | Font keseluruhan              |

---

## Catatan Penting

1. **Tidak ada emoji** di seluruh antarmuka, semua ikon menggunakan Iconify MDI
2. **Warna primer** `#f54518` (oranye merah) konsisten di seluruh antarmuka
3. **Mobile-first** untuk user side dengan frame Android (max-width 430px)
4. **Responsif penuh** untuk admin dashboard dengan sidebar
5. **Dark mode** tersimpan di `localStorage` masing-masing perangkat
6. **Session** digunakan untuk autentikasi, password di-hash dengan `password_hash()`

---

## Lisensi
Dibuat untuk keperluan wisata Danau Paisupok, Gorontalo.