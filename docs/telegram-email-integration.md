# Integrasi Email (GAS) + Approval via Telegram

Dokumen ini menjelaskan cara menyalakan alur: email notifikasi bank ->
webhook DanaTrack -> transaksi `pending` -> approve/reject lewat tombol
Telegram. Kodenya sudah ada di repo; dokumen ini hanya langkah
konfigurasi yang harus dilakukan manual (akun pihak ketiga, tidak bisa
diotomatisasi dari sini).

## Arsitektur singkat

```
Gmail (email bank) --[GAS: cekEmailBaru, tiap 5 menit]-->
  POST /webhooks/email-transactions (header X-Webhook-Secret)
    -> EmailLog (idempotency by email_message_id)
    -> Transaction (status=pending, source=email)
    -> TelegramService::sendApprovalRequest() (tombol Setujui/Tolak)

Telegram (klik tombol) --[Telegram setWebhook]-->
  POST /webhooks/telegram (header X-Telegram-Bot-Api-Secret-Token)
    -> TelegramWebhookController mencocokkan telegram_id ke telegram_users
    -> TransactionApprovalService::approve()/reject()
```

Karena DanaTrack masih jalan lokal di Laragon (bukan di domain publik),
kedua webhook di atas butuh **ngrok** supaya bisa diakses dari internet
oleh Google dan Telegram.

## 1. Siapkan ngrok

```sh
# sekali saja, pakai authtoken dari dashboard ngrok.com (akun Anda sendiri)
d:\Aplikasi\laragon\bin\ngrok\ngrok config add-authtoken TOKEN_ANDA

# jalankan setiap kali mau testing (biarkan tetap terbuka di terminal terpisah)
d:\Aplikasi\laragon\bin\ngrok\ngrok http 8000
```

Catat URL `https://xxxx.ngrok-free.app` yang muncul -- itu berubah
setiap ngrok di-restart (kecuali Anda pakai static domain berbayar/gratis
dari ngrok). Setiap kali berubah, ulangi langkah 2 dan 3 di bawah.

Jalankan juga server Laravelnya di port yang sama:

```sh
php artisan serve --port=8000
```

## 2. Daftarkan webhook URL ke Script Properties GAS

Lihat komentar di `docs/google-apps-script/EmailWatcher.gs` untuk
langkah lengkap deploy script-nya. Intinya:

- `WEBHOOK_URL` = `https://xxxx.ngrok-free.app/webhooks/email-transactions`
- `WEBHOOK_SECRET` = nilai `EMAIL_WEBHOOK_SECRET` di `.env` lokal Anda.

**Sebelum itu**, sesuaikan dulu `GMAIL_SEARCH_QUERY` dan function
`parseEmailBri_()` di script tersebut dengan format email notifikasi
BRI yang sebenarnya Anda terima -- yang ada sekarang baru kerangka.

## 3. Daftarkan webhook Telegram

Ganti `<TOKEN>` dengan isi `TELEGRAM_BOT_TOKEN`, dan `<URL_NGROK>` dengan
URL ngrok aktif saat ini. `<SECRET>` ambil dari `TELEGRAM_WEBHOOK_SECRET`
di `.env` (sudah digenerate otomatis, lihat file itu langsung -- jangan
minta saya menampilkannya).

```sh
curl -s "https://api.telegram.org/bot<TOKEN>/setWebhook" \
  -d "url=<URL_NGROK>/webhooks/telegram" \
  -d "secret_token=<SECRET>"
```

Cek status webhook kapan saja dengan:

```
https://api.telegram.org/bot<TOKEN>/getWebhookInfo
```

Field `url` harus terisi URL ngrok Anda, dan `last_error_message`
sebaiknya kosong.

**Penting:** begitu webhook Telegram aktif, `getUpdates` (cara polling
yang kita pakai sebelumnya untuk ambil `chat_id`) tidak akan menerima
update baru lagi -- semua update dikirim ke webhook ini. Itu normal.

## 4. Setiap kali ngrok restart

URL ngrok berubah setiap restart (tier gratis tanpa static domain).
Setelah restart, ulangi:

1. Update `WEBHOOK_URL` di Script Properties GAS (langkah 2).
2. Jalankan ulang `setWebhook` dengan URL baru (langkah 3).

## 5. Status fitur ini

- [x] Endpoint `POST /webhooks/email-transactions` (Laravel) -- sudah ada, teruji.
- [x] Endpoint `POST /webhooks/telegram` (Laravel) -- sudah ada, teruji.
- [x] Baris `telegram_users` penghubung akun Anda sudah dibuat.
- [ ] Format regex `parseEmailBri_()` di GAS -- **masih placeholder**,
      perlu contoh email asli untuk difinalisasi.
- [ ] ngrok authtoken -- perlu disiapkan manual oleh Anda.
- [ ] `setWebhook` Telegram -- jalankan setelah ngrok aktif.
