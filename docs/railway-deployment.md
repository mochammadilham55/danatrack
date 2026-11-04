# Deploy DanaTrack ke Railway

Panduan ini untuk deploy pertama kali. Railway mendukung Laravel secara
native lewat Nixpacks (deteksi otomatis `composer.json` + `artisan`),
jadi **tidak perlu ubah kode apa pun** -- beda dari kasus Vercel yang
sempat dibahas sebelumnya.

Repo proyek ini sudah ada di GitHub:
`https://github.com/mochammadilham55/danatrack` -- jadi langkah
"hubungkan repo" di bawah bisa langsung dilakukan.

## 1. Buat project di Railway

1. Buka [railway.app](https://railway.app), daftar/login (bisa pakai akun GitHub).
2. Klik **New Project** -> **Deploy from GitHub repo**.
3. Pilih repo `mochammadilham55/danatrack`. Kalau repo belum muncul,
   klik **Configure GitHub App** dan beri akses ke repo ini.
4. Railway otomatis mendeteksi ini proyek PHP/Laravel dan mulai build
   pertama kali. **Build pertama ini kemungkinan akan gagal** -- itu
   normal, karena belum ada database dan env var. Lanjut ke langkah
   berikutnya dulu.

## 2. Tambah database MySQL

1. Di dashboard project yang sama, klik **+ New** -> **Database** ->
   **Add MySQL**.
2. Railway otomatis membuat instance MySQL dan menyediakan variabel
   koneksinya (`MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`,
   `MYSQLUSER`, `MYSQLPASSWORD`) yang bisa dirujuk dari service
   aplikasi Anda.

## 3. Atur Environment Variables

Buka service aplikasi (bukan service database) -> tab **Variables**.
Isi semua ini (nilai yang diberi `=>` ambil dari `.env` lokal Anda --
**jangan tempel nilai asli ke chat manapun**, salin manual langsung
dari file ke dashboard Railway):

```env
APP_NAME=DanaTrack
APP_ENV=production
APP_KEY=                      # generate baru khusus produksi, lihat langkah 3a
APP_DEBUG=false
APP_URL=https://<domain-railway-anda>.up.railway.app

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

TELEGRAM_BOT_TOKEN=            => dari .env lokal
TELEGRAM_CHAT_ID=              => dari .env lokal
TELEGRAM_WEBHOOK_SECRET=       => dari .env lokal
EMAIL_WEBHOOK_SECRET=          => dari .env lokal
EMAIL_WEBHOOK_USER_ID=         => lihat langkah 5, bisa beda dari lokal
```

Baris `${{MySQL.MYSQLHOST}}` dkk itu sintaks referensi variabel milik
Railway -- otomatis terisi dari service MySQL yang baru dibuat, tidak
perlu diketik manual nilainya.

### 3a. Generate APP_KEY baru khusus produksi

**Jangan pakai `APP_KEY` yang sama dengan lokal.** Generate yang baru:

```sh
php artisan key:generate --show
```

Jalankan ini di komputer Anda (hasilnya cuma ditampilkan, tidak ditulis
ke `.env` lokal karena pakai `--show`), lalu salin hasilnya ke variabel
`APP_KEY` di Railway.

## 4. Pastikan database ter-migrasi

Railway punya fitur **Deploy Command** / **Custom Start Command** di
Settings service (nama tepatnya bisa berbeda tergantung versi UI
Railway). Set start command jadi:

```sh
php artisan migrate --force && php artisan serve --host 0.0.0.0 --port $PORT
```

Atau, kalau Railway menyediakan field terpisah untuk "Release Command"
(dijalankan sekali sebelum start, sebelum traffic masuk), lebih baik
taruh `php artisan migrate --force` di sana, dan start command cukup
yang menjalankan server.

Alternatif paling simpel untuk deploy pertama: biarkan deploy jalan
dulu, lalu jalankan migrasi manual sekali lewat tab **Shell**/**Console**
Railway (atau Railway CLI):

```sh
php artisan migrate --force
```

## 5. Buat akun pertama, cek EMAIL_WEBHOOK_USER_ID

1. Buka domain Railway Anda, daftar akun lewat halaman register.
2. Cek ID user yang baru dibuat -- lewat tab **Shell** Railway:

   ```sh
   php artisan tinker --execute="echo App\Models\User::first()->id;"
   ```

3. Pastikan `EMAIL_WEBHOOK_USER_ID` di Variables Railway cocok dengan
   ID itu (kemungkinan besar `1` kalau ini user pertama, tapi jangan
   diasumsikan -- pastikan).

## 6. Alihkan webhook Telegram & GAS ke domain Railway

Sebelumnya ini menunjuk ke tunnel ngrok sementara. Sekarang ganti ke
domain Railway yang permanen:

**Telegram:**

```sh
curl -s "https://api.telegram.org/bot<TOKEN>/setWebhook" \
  -d "url=https://<domain-railway-anda>/webhooks/telegram" \
  -d "secret_token=<TELEGRAM_WEBHOOK_SECRET>"
```

**Google Apps Script** (`docs/google-apps-script/EmailWatcher.gs`):

Buka Script Properties, ganti `WEBHOOK_URL` jadi:

```
https://<domain-railway-anda>/webhooks/email-transactions
```

Tidak perlu ngrok lagi sejak ini -- domain Railway permanen.

## 7. Periksa ext-zip (untuk ekspor Excel)

```sh
php -m | grep zip
```

Jalankan ini lewat tab Shell Railway. Kalau kosong, tambahkan file
`nixpacks.toml` di root proyek:

```toml
[phases.setup]
nixPkgsArchive = "..."
aptPkgs = ["php8.3-zip"]
```

(Baris `aptPkgs` tepatnya bisa berbeda tergantung versi image Nixpacks
Railway saat ini -- cek dokumentasi Nixpacks kalau `php -m` ternyata
tidak menunjukkan `zip`.)

## 8. Checklist akhir sebelum dianggap live

- [ ] `APP_ENV=production` dan `APP_DEBUG=false` (wajib -- lihat
      catatan keamanan di hasil uji penetrasi proyek ini).
- [ ] `APP_KEY` baru, bukan hasil copy dari lokal.
- [ ] Migrasi sudah jalan (`php artisan migrate --force`).
- [ ] Bisa login, bisa buat transaksi, dashboard tampil benar.
- [ ] Notifikasi Telegram transaksi manual masuk.
- [ ] `setWebhook` Telegram & `WEBHOOK_URL` GAS sudah diarahkan ke
      domain Railway, bukan ngrok lagi.
- [ ] Ekspor Excel di halaman Laporan berhasil diunduh.
- [ ] `npm run build` ikut jalan otomatis saat build Railway (cek log
      build -- harus ada langkah `vite build`, kalau tidak ada berarti
      perlu `nixpacks.toml` tambahan untuk memaksa langkah Node).
