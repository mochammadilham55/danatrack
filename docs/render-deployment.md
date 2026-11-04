# Deploy DanaTrack ke Render

## Perbedaan penting dibanding Railway

Sebelum mulai, dua hal ini mengubah cara kerja dibanding panduan
Railway (`docs/railway-deployment.md`):

1. **Render tidak punya database MySQL terkelola** -- cuma PostgreSQL
   bawaan. Sudah dicek: query di aplikasi ini (`SUM()` lewat
   `selectRaw`/`DB::raw`, kolom `enum`/`decimal` di migration) semuanya
   standar dan kompatibel dengan PostgreSQL, jadi **disarankan pakai
   PostgreSQL** -- tidak perlu akun database pihak ketiga tambahan.
   Kalau tetap ingin MySQL, Anda perlu provider eksternal (misalnya
   PlanetScale) dan cukup hubungkan `DB_*` ke sana; Dockerfile di
   proyek ini sudah menyediakan driver untuk MySQL maupun PostgreSQL.
2. **Tingkat gratis Render "tidur" setelah +-15 menit tanpa traffic**,
   dan butuh waktu bangun lagi (cold start) saat ada request masuk.
   Ini berdampak ke webhook Telegram & email -- kalau servis sedang
   tidur, panggilan webhook bisa telat/timeout. Untuk fitur approval
   Telegram/email bekerja konsisten, pertimbangkan paket berbayar
   terendah Render (bukan free tier).

Deploy di sini pakai **Dockerfile** (sudah dibuat di root proyek, plus
`.dockerignore` supaya `.env` tidak pernah ikut masuk ke image) --
bukan mengandalkan buildpack otomatis Render, agar perilakunya pasti
dan bisa diverifikasi langsung dari isi file, bukan ditebak dari nama
tombol UI yang bisa berubah.

Isi `Dockerfile`-nya sengaja sederhana: base image `php:8.3-cli`,
install ekstensi yang dibutuhkan (`pdo_mysql`, `pdo_pgsql`, `zip` --
untuk ekspor Excel), build asset lewat `npm run build`, lalu jalan
pakai `php artisan serve`. Catatan jujur: `php artisan serve` adalah
server development, bukan server produksi kelas berat (single-threaded).
Untuk aplikasi personal satu pengguna seperti DanaTrack ini cukup
memadai; kalau nanti butuh menangani banyak pengguna/traffic tinggi,
baru perlu beralih ke setup Nginx + PHP-FPM yang lebih kompleks.

## 1. Buat database PostgreSQL

1. Di dashboard Render, klik **New +** -> **PostgreSQL**.
2. Beri nama (misalnya `danatrack-db`), pilih region, pilih paket.
3. Setelah jadi, catat **Internal Database URL** yang disediakan --
   dipakai di langkah env var berikutnya.

## 2. Buat Web Service dari repo GitHub

1. Klik **New +** -> **Web Service**.
2. Hubungkan ke repo `mochammadilham55/danatrack` (beri akses lewat
   GitHub App Render kalau belum muncul di daftar).
3. Pada **Environment**, pilih **Docker** (bukan "PHP" native runtime)
   -- Render otomatis mendeteksi `Dockerfile` di root proyek.
4. **Instance Type**: pilih sesuai kebutuhan. Kalau fitur Telegram/email
   mau responsif, hindari free tier (lihat catatan cold-start di atas).

## 3. Atur Environment Variables

Tab **Environment** pada service ini. Isi (nilai asli disalin manual
dari `.env` lokal Anda -- jangan tempel ke chat manapun):

```env
APP_NAME=DanaTrack
APP_ENV=production
APP_KEY=                      # generate baru, lihat langkah 3a
APP_DEBUG=false
APP_URL=https://<nama-service-anda>.onrender.com

DB_CONNECTION=pgsql
DB_HOST=                      # dari Internal Database URL Postgres
DB_PORT=5432
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

TELEGRAM_BOT_TOKEN=            # dari .env lokal
TELEGRAM_CHAT_ID=              # dari .env lokal
TELEGRAM_WEBHOOK_SECRET=       # dari .env lokal
EMAIL_WEBHOOK_SECRET=          # dari .env lokal
EMAIL_WEBHOOK_USER_ID=         # lihat langkah 5, bisa beda dari lokal
```

`DB_HOST`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` diambil dari
**Internal Database URL** Postgres yang dicatat di langkah 1 --
formatnya `postgresql://user:password@host:port/database`, pecah
sesuai bagiannya ke masing-masing variabel di atas.

### 3a. Generate APP_KEY baru khusus produksi

**Jangan pakai `APP_KEY` yang sama dengan lokal.**

```sh
php artisan key:generate --show
```

Jalankan di komputer Anda, salin hasilnya ke variabel `APP_KEY` di
Render.

## 4. Migrasi database

Sudah ditangani otomatis -- `Dockerfile` menjalankan
`php artisan migrate --force` setiap kali container start, sebelum
server dinyalakan. Tidak perlu langkah manual, dan ini juga menyiasati
keterbatasan **free tier Render yang tidak punya akses Shell/SSH**
(akses shell langsung adalah fitur paket berbayar).

Kalau Anda pakai paket berbayar dan ingin menjalankan migrasi manual
sewaktu-waktu (misalnya setelah menambah migration baru tanpa restart
penuh), bisa lewat tab **Shell** di dashboard Render:

```sh
php artisan migrate --force
```

## 5. Buat akun pertama, cek EMAIL_WEBHOOK_USER_ID

1. Buka domain Render Anda, daftar akun lewat halaman register.
2. Kalau punya akses Shell (paket berbayar):

   ```sh
   php artisan tinker --execute="echo App\Models\User::first()->id;"
   ```

   Kalau tidak punya akses Shell (free tier), cek lewat halaman
   Reports atau Transaksi setelah login -- ID user pertama hampir
   pasti `1` untuk database yang baru, tapi pastikan tidak ada akun
   lain yang lebih dulu terdaftar sebelum mengasumsikan ini.
3. Samakan `EMAIL_WEBHOOK_USER_ID` di Environment Render dengan ID itu.

## 6. Alihkan webhook Telegram & GAS ke domain Render

**Telegram:**

```sh
curl -s "https://api.telegram.org/bot<TOKEN>/setWebhook" \
  -d "url=https://<nama-service-anda>.onrender.com/webhooks/telegram" \
  -d "secret_token=<TELEGRAM_WEBHOOK_SECRET>"
```

**Google Apps Script** (`docs/google-apps-script/EmailWatcher.gs`):

Script Properties -> `WEBHOOK_URL`:

```
https://<nama-service-anda>.onrender.com/webhooks/email-transactions
```

## 7. Checklist akhir sebelum dianggap live

- [ ] `APP_ENV=production` dan `APP_DEBUG=false`.
- [ ] `APP_KEY` baru, bukan hasil copy dari lokal.
- [ ] `DB_CONNECTION=pgsql` cocok dengan database yang dibuat (bukan
      `mysql` bawaan `.env.example` lokal).
- [ ] Bisa login, buat transaksi, dashboard tampil benar.
- [ ] Notifikasi Telegram transaksi manual masuk.
- [ ] `setWebhook` Telegram & `WEBHOOK_URL` GAS sudah ke domain Render.
- [ ] Ekspor Excel di halaman Laporan berhasil diunduh (ekstensi `zip`
      sudah diinstall lewat `Dockerfile`, tapi tetap dicoba langsung).
- [ ] Sudah diputuskan: pakai paket berbayar kalau butuh webhook
      Telegram/email responsif tanpa delay cold-start free tier.
