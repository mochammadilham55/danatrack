# DanaTrack

Aplikasi pencatat keuangan pribadi (income/expense tracker) berbasis Laravel, antarmuka berbahasa Indonesia.

## Stack

| Bagian | Teknologi |
|---|---|
| Framework | Laravel `^13.17`, PHP `^8.3` (lokal: 8.3.35) |
| Auth | Laravel Breeze `^2.4` (Blade + Alpine stack) |
| Frontend | Blade + Alpine.js 3, Tailwind CSS 3 (via PostCSS), Vite 8 |
| Database | MySQL (`danatrack` @ 127.0.0.1:3306, Laragon) |
| Session/Cache/Queue | driver `database` |
| Grafik | Chart.js dari CDN jsDelivr (bukan bundel Vite) |
| Test | PHPUnit 12, SQLite `:memory:` (lihat `phpunit.xml`) |

Tooling tambahan: `laravel/pint` (formatter), `laravel/pail` (log tail), `laravel/pao`, `laravel/tinker`.

## Perintah

```sh
composer setup          # install + key:generate + migrate + npm install + build
composer dev            # php artisan dev (server + vite + queue, concurrently)
composer test           # config:clear + php artisan test
npm run dev             # vite saja
vendor/bin/pint         # format PHP
php artisan migrate:status
```

Laragon harus menyala sebelum perintah apa pun yang menyentuh database — tanpa MySQL, `migrate:status` gagal dengan `SQLSTATE[HY000] [2002]`.

## Domain

### Model & relasi (`app/Models/`)

```
User ──< Category ──< Transaction
 │                        │
 ├──< Transaction         ├──< EmailLog
 ├──< EmailLog            └──< TelegramMessage
 ├──< TelegramMessage
 ├──< approvedTransactions (Transaction.approved_by)
 └──1 TelegramUser
```

- `Transaction` — inti aplikasi. Kolom penting: `transaction_date` (datetime), `type` (`expense|income`), `amount` (`decimal:15,2`), `category_id` (nullable, `nullOnDelete`), `source`, `status`, `reference_number`, `approved_by`, `approved_at`.
- `Category` — `unique(user_id, name, type)`, `color` kolom `char(7)` default `#64748B`.
- `EmailLog` / `TelegramUser` / `TelegramMessage` — tabel audit untuk kanal masuk transaksi.

### Dua enum yang menentukan arsitektur

- `transactions.source` = `email` | `website` | `telegram` — DanaTrack dirancang menerima transaksi dari tiga kanal.
- `transactions.status` = `pending` | `approved`, default `approved`.

**Yang sudah jadi hanya kanal `website`.** Model + migrasi untuk email dan Telegram sudah ada, tapi tidak ada controller, job, command, webhook, atau route yang menulis ke tabel-tabel itu. `TELEGRAM_BOT_TOKEN` ada di `.env` tapi tidak terdaftar di `config/services.php`, tidak ada di `.env.example`, dan tidak dibaca kode mana pun. Anggap lapisan email/Telegram sebagai *scaffolding*, bukan fitur.

Konsekuensi dari `status`: `TransactionController::store()` selalu menulis `status = 'approved'` + `approved_by = auth()->id()`. Tidak ada route/UI approval, dan Dashboard serta Laporan memfilter `where('status', 'approved')` — jadi transaksi `pending` (yang nanti datang dari email/Telegram) tidak akan terlihat di mana pun sampai alur approval dibuat.

## Routing (`routes/web.php`)

| Route | Controller | Middleware |
|---|---|---|
| `/` | closure → `welcome` | — |
| `/dashboard` | `DashboardController@index` | `auth`, `verified` |
| `categories.*` (resource) | `CategoryController` | `auth` |
| `transactions.*` (resource) | `TransactionController` | `auth` |
| `/reports` | `ReportController@index` | `auth` |
| `/profile` (edit/update/destroy) | `ProfileController` | `auth` |
| `/up` | health check | — |

`routes/auth.php` = bawaan Breeze (register, login, forgot/reset password, verify email, confirm password, logout). Tidak ada `routes/api.php`.

## Konvensi yang WAJIB diikuti

### 1. Bahasa: campuran yang disengaja

- **Inggris**: nama tabel, kolom, model, controller, route, nilai enum (`expense`, `income`, `approved`).
- **Indonesia**: UI/Blade, pesan flash (`'Kategori berhasil ditambahkan.'`), pesan validasi kustom, **dan variabel di dalam controller/view** — `$totalPemasukan`, `$totalPengeluaran`, `$saldo`, `$transaksiTerbaru`, `$rekapKategori`, `$tanggalAwal`, `$tanggalAkhir`, `$labelsGrafik`.
- Query string filter juga Indonesia: `?periode=bulan_ini&dari=…&sampai=…` dengan nilai `hari_ini|bulan_ini|tahun_ini|rentang`.

Pertahankan pola ini saat menambah kode — jangan "perbaiki" menjadi seragam satu bahasa.

### 2. Otorisasi manual, tanpa Policy

Tidak ada Policy/Gate sama sekali. Setiap controller melakukan scoping dan cek kepemilikan sendiri:

```php
Category::where('user_id', auth()->id())->…             // selalu scope query
abort_unless($category->user_id === auth()->id(), 403); // private authorizeX() helper
```

Lihat `CategoryController::authorizeCategory()` dan `TransactionController::authorizeTransaction()`. Controller baru harus melakukan hal yang sama, atau pindahkan seluruhnya ke Policy sekaligus — jangan setengah-setengah.

### 3. Validasi inline, bukan FormRequest

`CategoryController` dan `TransactionController` memakai `$request->validate([...])` langsung. FormRequest hanya ada untuk hal bawaan Breeze (`LoginRequest`, `ProfileUpdateRequest`).

`TransactionController::validateCategory()` menegakkan aturan bahwa kategori harus milik user **dan** `type`-nya cocok dengan `type` transaksi.

### 4. Format Rupiah manual

Tidak ada helper/macro. Di Blade: `Rp{{ number_format($nilai, 0, ',', '.') }}`. Di JS: `new Intl.NumberFormat('id-ID')`.

### 5. Script per-halaman lewat stack

`layouts/app.blade.php` menyediakan `@stack('scripts')` sebelum `</body>`. `dashboard.blade.php` memakai `@push('scripts')` untuk memuat Chart.js dari CDN. Script khusus halaman masuk lewat jalur ini, bukan ke `resources/js/app.js`.

## Catatan per fitur

**Dashboard** (`DashboardController`) — filter periode, lalu tiga total (pemasukan/pengeluaran/saldo), 5 transaksi terbaru, kategori pengeluaran terbesar, dan grafik bar 6 bulan terakhir. Query dasar dibangun sekali lalu `(clone $transaksiPeriode)` dipakai berulang; ikuti pola clone ini. Periode `rentang` memvalidasi `dari`/`sampai` secara kondisional di dalam `switch`.

**Laporan** (`ReportController`) — rentang tanggal (default bulan ini), total, rekap pengeluaran per kategori via `leftJoin` + `groupBy('categories.name')`, dan daftar transaksi lengkap. Daftar ini `->get()` tanpa paginasi; rentang tanggal lebar akan memuat semua baris sekaligus.

**Transaksi** — index `paginate(10)`. Form create/edit punya JS kecil yang menyembunyikan `<option>` kategori yang `type`-nya tidak cocok dengan jenis transaksi terpilih (lihat atribut `data-type` pada option).

**Kategori** — CRUD penuh, unik per `(user, name, type)`, warna hex.

## Utang teknis & jebakan yang diketahui

Catat ini sebelum mengubah kode di area terkait:

1. **SQL khusus MySQL di query dashboard.** `DashboardController` memakai `selectRaw('YEAR(transaction_date) …, MONTH(transaction_date) …')`. `phpunit.xml` menjalankan test di SQLite `:memory:`, yang tidak punya `YEAR()`/`MONTH()` — test untuk dashboard akan gagal sampai query dibuat portabel atau test diarahkan ke MySQL.
2. **Tidak ada factory untuk `Category`/`Transaction`.** Hanya `UserFactory` yang ada, jadi belum bisa menulis test domain tanpa membuatnya lebih dulu.
3. **Nol test untuk kode domain.** `tests/` hanya berisi bawaan Breeze (auth, profile, example). Category, Transaction, Dashboard, Report belum tersentuh.
4. **`validateCategory()` mengembalikan JSON 422 ke form HTML.** `abort(response()->json([...], 422))` di `TransactionController` akan menampilkan JSON mentah ke pengguna, bukan error di form. Seharusnya `ValidationException::withMessages()`.
5. **Panjang `color` tidak konsisten.** Kolom `char(7)`, tapi validasi mengizinkan `max:20` → input 8–20 karakter lolos validasi lalu ditolak/terpotong database. Default juga beda huruf: migrasi `#64748B`, controller `#64748b`.
6. **Dependensi Tailwind ganda.** Pipeline aktif = Tailwind 3 + PostCSS (`postcss.config.js`, `@tailwind` directives, `tailwind.config.js`). Tapi `@tailwindcss/vite ^4.0.0` ada di `devDependencies` dan `vite.config.js` tidak memakainya. Pilih satu sebelum menyentuh build.
7. **`TELEGRAM_BOT_TOKEN` terisi di `.env`.** Token hidup tersimpan plaintext di file lokal. Rotasi token itu sebelum repo ini dibagikan, dan daftarkan key-nya di `config/services.php` + `.env.example` saat kanal Telegram digarap.
8. **Belum ada repo git.** Direktori ini bukan git repository — tidak ada riwayat atau jaring keamanan untuk rollback.
9. **`README.md` masih bawaan Laravel**, belum mendeskripsikan DanaTrack.
10. **Composer 2.4.1** (rilis 2022) — cukup tua untuk Laravel 13; pertimbangkan `composer self-update`.

## Langkah selanjutnya yang paling masuk akal

Urutan ini mengikuti bentuk yang sudah ada di kode, bukan desain baru:

1. Buat `CategoryFactory` + `TransactionFactory`, lalu test fitur untuk CRUD + isolasi antar-user (`abort_unless` 403).
2. Perbaiki (4) dan (5) — keduanya kecil dan berdampak langsung ke pengguna.
3. Bangun alur approval: daftar transaksi `pending`, aksi approve yang mengisi `approved_by`/`approved_at`. Tanpa ini, kanal email/Telegram tidak ada gunanya.
4. Baru setelah itu, isi kanal `telegram` (webhook → `TelegramMessage` → `Transaction` status `pending`) dan `email`.

---

<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>
