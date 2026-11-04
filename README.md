# DanaTrack

Aplikasi web pencatatan keuangan pribadi (pemasukan dan pengeluaran) berbasis Laravel. Antarmuka berbahasa Indonesia, dengan dashboard, kategori, transaksi, laporan dengan ekspor Excel, serta integrasi notifikasi dan approval transaksi lewat Telegram.

## Fitur Utama

- Autentikasi pengguna (login, registrasi, lupa password, verifikasi email) menggunakan Laravel Breeze.
- CRUD kategori transaksi (pemasukan/pengeluaran, dengan warna penanda).
- CRUD transaksi manual, dengan isolasi data per pengguna.
- Dashboard: ringkasan pemasukan/pengeluaran/saldo, filter periode, grafik 6 bulan terakhir, kategori pengeluaran terbesar, transaksi terbaru.
- Laporan: filter rentang tanggal, rekap pengeluaran per kategori, rincian transaksi, dan ekspor ke file Excel (`.xlsx`).
- Notifikasi Telegram otomatis setiap kali transaksi manual dibuat.
- Alur approval transaksi: transaksi berstatus `pending` (misalnya dari kanal otomatis) harus disetujui sebelum masuk ke perhitungan dashboard/laporan. Approval bisa dilakukan lewat tombol di aplikasi web maupun tombol inline di Telegram.
- Integrasi email bank (Google Apps Script): email notifikasi transaksi dari bank dibaca otomatis, dikirim ke aplikasi sebagai transaksi `pending`, lalu diminta persetujuan lewat Telegram.

## Teknologi yang Dipakai

- PHP 8.3, Laravel 13
- Blade, Tailwind CSS, Alpine.js (lewat Laravel Breeze)
- MySQL
- Chart.js (grafik dashboard)
- maatwebsite/excel (ekspor laporan ke `.xlsx`)
- Telegram Bot API (notifikasi dan approval)
- Google Apps Script (pembaca email notifikasi bank, opsional)

## Prasyarat

Pastikan sudah terpasang di komputer Anda:

- PHP 8.3 atau lebih baru, beserta ekstensi `pdo_mysql` dan `zip` (dibutuhkan untuk ekspor Excel).
- Composer
- Node.js dan npm
- MySQL (bisa lewat Laragon, XAMPP, atau instalasi mandiri)

## Instalasi

1. Clone atau unduh proyek ini, lalu masuk ke foldernya.

   ```bash
   git clone <url-repo-ini> danatrack
   cd danatrack
   ```

2. Install dependency PHP.

   ```bash
   composer install
   ```

3. Salin file environment, lalu buat `APP_KEY`.

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Buat database MySQL baru (misalnya bernama `danatrack`), lalu sesuaikan bagian berikut di `.env` dengan kredensial database Anda:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=danatrack
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Jalankan migrasi database.

   ```bash
   php artisan migrate
   ```

6. Install dependency frontend dan build asset.

   ```bash
   npm install
   npm run build
   ```

7. Jalankan server pengembangan.

   ```bash
   php artisan serve
   ```

8. Buka `http://127.0.0.1:8000` di browser, lalu daftarkan akun baru lewat halaman registrasi.

Langkah 1–7 di atas juga bisa dijalankan sekaligus lewat:

```bash
composer setup
```

Untuk pengembangan sehari-hari (server, build asset, dan antrean job berjalan bersamaan), gunakan:

```bash
composer dev
```

## Konfigurasi Opsional: Notifikasi Telegram

Fitur ini membuat aplikasi mengirim pesan Telegram setiap transaksi manual dibuat, dan mengizinkan approval transaksi `pending` lewat tombol Telegram.

1. Buat bot baru lewat [@BotFather](https://t.me/BotFather) di Telegram untuk mendapatkan token bot.
2. Kirim pesan apa saja ke bot Anda, lalu buka `https://api.telegram.org/bot<TOKEN>/getUpdates` di browser untuk menemukan `chat.id` Anda.
3. Isi di `.env`:

   ```env
   TELEGRAM_BOT_TOKEN=token_bot_anda
   TELEGRAM_CHAT_ID=chat_id_anda
   ```

4. Bersihkan cache konfigurasi, lalu uji koneksi.

   ```bash
   php artisan config:clear
   php artisan telegram:test
   ```

Jika berhasil, akan ada pesan uji yang masuk ke chat Telegram Anda.

## Konfigurasi Opsional: Impor Otomatis dari Email Bank

Fitur ini membaca email notifikasi transaksi dari bank (lewat Google Apps Script), mengirimkannya ke aplikasi sebagai transaksi `pending`, lalu meminta persetujuan Anda lewat tombol Telegram sebelum masuk ke dashboard/laporan.

Fitur ini butuh aplikasi bisa diakses dari internet (lewat domain publik atau tunnel seperti ngrok untuk pengujian lokal). Langkah lengkapnya, termasuk contoh skrip Google Apps Script, ada di:

- [`docs/telegram-email-integration.md`](docs/telegram-email-integration.md)
- [`docs/google-apps-script/EmailWatcher.gs`](docs/google-apps-script/EmailWatcher.gs)

Variabel environment yang terkait:

```env
EMAIL_WEBHOOK_SECRET=
EMAIL_WEBHOOK_USER_ID=
TELEGRAM_WEBHOOK_SECRET=
```

## Menjalankan Test

```bash
php artisan test
```

atau

```bash
composer test
```

## Referensi Desain

Folder [`contoh halaman/`](contoh%20halaman/) berisi contoh mockup HTML statis untuk beberapa arah desain tampilan (bukan bagian dari aplikasi, murni referensi visual). Tampilan aplikasi yang aktif sekarang mengikuti arah "Desain 2 — Vibrant Modern" dari contoh tersebut.

## Catatan dan Keterbatasan

- Deteksi otomatis transaksi masuk (`type: income`) dari email bank belum diuji dengan contoh email transfer masuk yang sesungguhnya. Lihat catatan TODO di `docs/google-apps-script/EmailWatcher.gs`.
- Aplikasi ini belum pernah di-deploy ke lingkungan produksi. Catatan kompatibilitas untuk beberapa platform hosting ada di riwayat diskusi proyek; secara umum aplikasi ini cocok untuk hosting yang mendukung PHP/Laravel secara native (misalnya Railway, Render, atau VPS biasa).
- Jangan pernah menyimpan token Telegram, kredensial database, atau secret lainnya di dalam kode atau commit Git. File `.env` sudah terdaftar di `.gitignore`.
