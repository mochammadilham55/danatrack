# CLAUDE.md --- DanaTrack

Dokumen ini menjadi konteks proyek untuk Claude agar dapat melanjutkan
DanaTrack dari kondisi terakhir tanpa membangun ulang dari awal. Baca
dokumen ini sebelum mengubah kode.

## 1. Ringkasan Proyek

**Nama:** DanaTrack\
**Tujuan:** Aplikasi web pencatatan pemasukan dan pengeluaran pribadi,
dengan dashboard, kategori, transaksi, laporan, ekspor Excel, serta
integrasi Telegram. Pada tahap berikutnya, aplikasi direncanakan
menerima notifikasi transaksi dari email bank melalui Google Apps Script
(GAS), lalu meminta persetujuan melalui Telegram.

**Pengguna utama:** Pemilik akun yang login. Data transaksi dan kategori
harus terisolasi per pengguna.

**Bahasa UI:** Bahasa Indonesia.

**Prinsip penting:** - Jangan mulai proyek dari nol; lanjutkan struktur
Laravel yang sudah ada. - Jangan menghapus atau mengganti fitur yang
sudah berfungsi. - Sebelum mengedit file, periksa isi file aktual di
repository karena konteks ini dapat tertinggal dari perubahan terbaru. -
Jangan mengarang nama kolom, relasi, route, atau struktur tabel. Periksa
migration/model/controller yang ada. - Jangan pernah meminta atau
menampilkan token Telegram, password, atau kredensial `.env`. -
Transaksi manual dari website harus langsung berstatus `approved` dan
memicu notifikasi Telegram. - Transaksi otomatis dari email (fitur
mendatang) akan berstatus `pending` sampai pengguna menyetujui/menolak
melalui Telegram.

## 2. Lingkungan Pengembangan

Informasi terakhir yang diketahui: - OS: Windows - Local stack:
Laragon - Proyek: `D:\Aplikasi\laragon\www\DanaTrack` - Laravel: 13.39
(per informasi pengguna) - PHP: 8.3.35 (per informasi pengguna) -
Autentikasi: Laravel Breeze (Blade) - Frontend: Blade + Tailwind CSS +
Alpine.js (Breeze) - Database: sesuai konfigurasi `.env` lokal; jangan
mengasumsikan engine tanpa memeriksa - Chart: Chart.js sudah digunakan
pada dashboard - Spreadsheet export: direncanakan/dibuat menggunakan
`maatwebsite/excel`; pastikan package benar-benar terpasang dengan
memeriksa `composer.json`/`composer.lock`.

Perintah lokal yang umum:

``` bash
cd D:\Aplikasi\laragon\www\DanaTrack
php artisan serve
php artisan route:list
php artisan migrate:status
php artisan view:clear
php artisan config:clear
npm run dev
```

Jangan menjalankan migration destruktif (`migrate:fresh`,
`migrate:refresh`) tanpa persetujuan eksplisit dan backup data.

## 3. Fitur dan Status Implementasi

### Sudah dibuat / pernah diuji

-   Login/register melalui Laravel Breeze.
-   Navigasi utama: Dashboard, Kategori, Transaksi, Laporan.
-   CRUD kategori.
-   CRUD transaksi manual.
-   Dashboard dengan ringkasan pemasukan, pengeluaran, saldo, transaksi
    terbaru, grafik 6 bulan, filter periode, dan ringkasan pengeluaran
    kategori.
-   Halaman laporan dengan filter tanggal, total
    pemasukan/pengeluaran/saldo bersih, rekap pengeluaran per kategori,
    dan daftar transaksi.
-   Chart.js dashboard pernah berhasil tampil.
-   UI menggunakan Blade components Breeze.

**Catatan:** Status di atas berasal dari percakapan; tetap verifikasi
repository dan jalankan aplikasi sebelum menganggap semuanya masih
valid.

### Sedang dikerjakan / belum dikonfirmasi selesai

-   ReportController pernah mengalami query SQL ambigu pada kolom
    `user_id` karena clone query dan join categories. Solusi terakhir
    yang disarankan adalah query baru dengan kolom terqualifikasi, bukan
    clone query dasar:

``` php
$rekapKategori = Transaction::query()
    ->where('transactions.user_id', $userId)
    ->where('transactions.status', 'approved')
    ->whereBetween('transactions.transaction_date', [$tanggalAwal, $tanggalAkhir])
    ->where('transactions.type', 'expense')
    ->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
    ->select(
        'categories.name as nama_kategori',
        DB::raw('SUM(transactions.amount) as total')
    )
    ->groupBy('categories.name')
    ->orderByDesc('total')
    ->get();
```

Pastikan query aktual, nama kolom, dan grouping sesuai migration/model.
Lebih aman group by `categories.id` juga jika kategori dengan nama sama
dapat ada. - Ekspor Excel sedang direncanakan/diberikan implementasi
contoh. Pastikan package, Export class, controller method, route, dan
tombol view sudah benar-benar ada sebelum melanjutkan. - Integrasi
Telegram belum selesai. Bot dibuat oleh pengguna dengan username
`DanaTrackPro_bot` (tautan bot: `https://t.me/DanaTrackPro_bot`). Token
pernah terekspos di percakapan dan menghasilkan HTTP 401. Pengguna perlu
revoke token lama di BotFather dan memakai token baru yang dirahasiakan.
Jangan menyalin token lama dari chat atau dokumen ini.

## 4. Struktur Data yang Diketahui

Periksa migrations aktual untuk memastikan detail. Skema yang pernah
dibahas:

### `categories`

-   `id`
-   `user_id`
-   `name`
-   `type` (`expense` / `income`)
-   `color`
-   timestamps

Kategori milik pengguna yang login.

### `transactions`

-   `id`
-   `user_id`
-   `category_id` nullable
-   `transaction_date` datetime
-   `type` (`expense` / `income`)
-   `amount` decimal
-   `description`
-   `merchant` nullable
-   `source` enum/string (`email`, `website`, `telegram`)
-   `status` (`pending`, `approved`)
-   `reference_number` nullable
-   `approved_by` nullable
-   `approved_at` nullable
-   timestamps

Semua transaksi harus difilter dengan `user_id` sesuai pengguna login.
Data untuk dashboard/laporan hanya memasukkan `status = approved`.

### Tabel lain yang pernah disebut

-   `email_logs`
-   `telegram_users`
-   `telegram_messages`

Keberadaan dan kolom aktual harus diverifikasi dari migrations.

## 5. Model dan Relasi

Model yang diketahui: - `App\Models\Transaction` -
`App\Models\Category` - `App\Models\User`

Relasi yang diharapkan: - Transaction belongsTo Category melalui
`category_id`. - Category hasMany Transaction. - User hasMany Category
dan Transaction.

Periksa model aktual sebelum mengubah. Pastikan `$fillable`, casts, dan
relasi sesuai schema.

## 6. Routes yang Pernah Ada

`routes/web.php` memiliki pola:

``` php
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('transactions', TransactionController::class);

    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');

    // Profile routes dari Breeze
});

require __DIR__.'/auth.php';
```

Route ekspor Excel yang pernah direncanakan:

``` php
Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])
    ->name('reports.exportExcel');
```

Pastikan route ini berada di middleware `auth`, dan controller import
benar. Jangan menambahkan route duplikat.

## 7. Navigasi Breeze

File: `resources/views/layouts/navigation.blade.php`

Menu desktop dan mobile: - Dashboard --- `route('dashboard')` - Kategori
--- `route('categories.index')` - Transaksi ---
`route('transactions.index')` - Laporan --- `route('reports.index')`

Dropdown: - Profil --- `route('profile.edit')` - Keluar --- POST
`route('logout')`

Alpine hamburger:

``` blade
<nav x-data="{ open: false }">
...
<button @click="open = ! open">
```

Kode navigation pernah diperbaiki secara menyeluruh. Periksa file aktual
sebelum mengganti.

## 8. Aturan Bisnis Transaksi

### Transaksi manual dari website

-   Pengguna mengisi form pemasukan/pengeluaran.
-   Validasi kategori harus sesuai jenis transaksi (`income` atau
    `expense`) dan milik pengguna yang login.
-   Simpan `user_id` dari authenticated user, jangan dari input form.
-   `source = website`.
-   `status = approved`.
-   `approved_by = user_id` (jika schema mendukung).
-   `approved_at = now()`.
-   Setelah transaksi berhasil tersimpan, kirim notifikasi Telegram.
-   Jika Telegram gagal, transaksi tetap tersimpan. Jangan rollback
    transaksi hanya karena layanan Telegram tidak tersedia. Catat/log
    error dan tampilkan UX yang sesuai.

### Transaksi otomatis dari email (fitur mendatang)

-   GAS membaca email notifikasi bank (rencana awal BRI).
-   GAS mengirim payload ke endpoint backend yang diamankan.
-   Laravel menyimpan transaksi `source = email`, `status = pending`.
-   Kirim pesan Telegram dengan tombol Setujui/Tolak.
-   Hanya setelah disetujui status menjadi `approved`; baru masuk
    dashboard/laporan.
-   Tolak transaksi dengan status yang jelas (perlu diputuskan apakah
    memakai `rejected` atau field status tambahan; jangan menambah enum
    tanpa menyesuaikan migration).
-   Implementasikan idempotency menggunakan `reference_number`/ID
    transaksi agar email yang sama tidak membuat duplikat.
-   Jangan menganggap isi email sebagai data tepercaya; validasi amount,
    tanggal, tipe, referensi, dan signature/token API.

## 9. Integrasi Telegram --- Tujuan dan Status

### Tujuan tahap pertama

Kirim notifikasi Telegram saat pengguna menambah transaksi manual di
website.

Contoh pesan:

    DanaTrack — Transaksi Baru

    Jenis: Pengeluaran
    Jumlah: Rp25.000
    Kategori: Makanan
    Tanggal: 03 November 2026
    Deskripsi: Makan siang
    Status: Disetujui

Untuk transaksi manual: - Tidak perlu tombol Setujui/Tolak. - Bot hanya
mengirim notifikasi ke chat pengguna.

Untuk transaksi email otomatis (tahap selanjutnya): - Inline keyboard
Setujui/Tolak. - Callback webhook harus memverifikasi transaksi milik
user yang terhubung dengan Telegram. - Callback harus aman terhadap klik
berulang dan transaksi yang sudah diproses. - Simpan Telegram chat/user
mapping secara aman; jangan percaya `chat_id` yang dikirim sembarang
client.

### Bot yang dibuat

-   Username: `DanaTrackPro_bot`
-   URL: `https://t.me/DanaTrackPro_bot`
-   Token lama pernah terekspos dan `getUpdates` mengembalikan
    `401 Unauthorized`.
-   **Token lama harus dianggap compromised/invalid.** Revoke via
    @BotFather dan gunakan token baru.
-   Chat ID belum berhasil dikonfirmasi.
-   Pengguna belum menyatakan token baru valid atau Chat ID sudah
    didapat.

### Rencana implementasi yang disarankan

1.  Revoke token lama di BotFather; generate token baru.
2.  User buka bot dan kirim `/start`.
3.  Gunakan `getUpdates` dengan token baru untuk mendapatkan
    `message.chat.id`. Jangan meminta user mengirim token.
4.  Simpan secrets hanya di `.env`:

``` env
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

5.  Tambahkan config di `config/services.php`:

``` php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
],
```

6.  Jalankan `php artisan config:clear`.
7.  Buat service, misalnya `app/Services/TelegramService.php`, yang
    menggunakan Laravel HTTP client:
    -   POST ke Telegram Bot API `sendMessage`
    -   gunakan `config('services.telegram.bot_token')`
    -   kirim `chat_id`, `text`, dan optional `parse_mode`
    -   gunakan timeout, tangani response non-2xx, dan log tanpa
        membocorkan token.
8.  Buat endpoint/command sederhana untuk uji pesan (jangan expose
    endpoint publik tanpa auth).
9.  Panggil service setelah transaksi manual tersimpan.
10. Test income dan expense, serta test ketika token/chat ID salah untuk
    memastikan transaksi tetap tersimpan.

**Keamanan:** Jangan menulis token ke log, source code, view, Git, atau
pesan chat. Pastikan `.env` di `.gitignore`. Jika token terekspos,
revoke segera.

## 10. Dashboard dan Reports

Dashboard: - Ringkasan total pemasukan, pengeluaran, saldo. - Data
approved milik user. - Transaksi terbaru. - Grafik 6 bulan (Chart.js). -
Filter periode: `hari_ini`, `bulan_ini`, `tahun_ini`, `rentang`. -
Ringkasan kategori pengeluaran.

Reports: - Filter `dari` dan `sampai`. - Validasi tanggal akhir tidak
sebelum tanggal awal. - Default periode pernah memakai bulan berjalan. -
Ringkasan pemasukan, pengeluaran, saldo bersih. - Rekap kategori
pengeluaran. - Daftar transaksi detail. - Data harus terfilter user +
approved. - Export Excel harus memakai filter tanggal yang sama dengan
halaman laporan.

## 11. Panduan Cara Bekerja untuk Claude

Saat mulai: 1. Periksa `README.md`, `composer.json`, `package.json`,
`.env.example` (jangan tampilkan `.env`), migrations, models, routes,
controllers, dan views terkait. 2. Jalankan `git status` bila Git
tersedia. 3. Catat kondisi aktual dan file yang sudah ada. 4. Jangan
mengganti banyak file sekaligus tanpa kebutuhan. 5. Untuk setiap fitur,
berikan daftar file yang akan diubah dan alasan. 6. Implementasikan
dalam langkah kecil, jalankan pemeriksaan yang relevan
(`php artisan route:list`, test, lint/build jika tersedia). 7. Jangan
menghapus data atau melakukan migration destruktif. 8. Jangan membuat
kredensial palsu atau hardcode secrets. 9. Gunakan Bahasa Indonesia
untuk UI dan penjelasan. 10. Berikan instruksi terminal yang sesuai
Windows/Laragon.

### Prioritas pekerjaan saat ini

1.  Periksa kondisi aktual halaman Reports dan ekspor Excel.
2.  Selesaikan Telegram Bot API connection dengan token baru yang valid
    dan Chat ID.
3.  Buat `TelegramService` dan test sendMessage.
4.  Hubungkan notifikasi ke `TransactionController` setelah transaksi
    manual berhasil dibuat.
5.  Uji pemasukan dan pengeluaran manual.
6.  Setelah itu baru rancang webhook GAS/email dan persetujuan transaksi
    pending melalui Telegram.

## 12. Definition of Done --- Telegram Manual Notification

Fitur dianggap selesai jika: - Token dan Chat ID tersimpan di `.env`,
tidak di source code. - Pesan uji dapat dikirim dari Laravel ke
Telegram. - Membuat transaksi pemasukan manual mengirim notifikasi. -
Membuat transaksi pengeluaran manual mengirim notifikasi. - Pesan berisi
jenis, nominal, kategori, tanggal, deskripsi, dan status. - Transaksi
tetap tersimpan meskipun Telegram gagal. - Tidak ada token rahasia di
log atau response. - User isolation tetap diterapkan.

## 13. Catatan Kredensial

**Jangan masukkan token Telegram asli ke CLAUDE.md.** File ini aman
untuk menyimpan konteks teknis, tetapi tidak untuk secrets. Simpan token
di `.env` lokal. Bila memakai Claude Code, jangan menempelkan nilai
`.env` ke prompt. Pastikan `.env` tidak dilacak Git.
