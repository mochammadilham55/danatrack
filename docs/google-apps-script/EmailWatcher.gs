/**
 * DanaTrack -- Email Watcher (Google Apps Script)
 *
 * Baca email notifikasi transaksi bank (rencana awal: BRI) di Gmail,
 * lalu kirim ke webhook DanaTrack supaya dibuatkan transaksi
 * `status = pending` yang menunggu persetujuan lewat Telegram.
 *
 * CARA PAKAI
 * 1. Buka https://script.google.com -> New project. Hapus isi default,
 *    paste seluruh file ini.
 * 2. Project Settings (ikon gerigi) -> Script Properties -> tambahkan:
 *      WEBHOOK_URL    = https://<subdomain-ngrok-anda>.ngrok-free.app/webhooks/email-transactions
 *      WEBHOOK_SECRET = (nilai EMAIL_WEBHOOK_SECRET dari .env DanaTrack)
 *    JANGAN hardcode dua nilai ini langsung di kode -- taruh di Script
 *    Properties supaya tidak ikut ter-commit kalau script ini di-share.
 * 3. Jalankan function `setupLabel` sekali secara manual (klik Run) untuk
 *    membuat label Gmail "DanaTrack/Processed".
 * 4. Jalankan function `createTrigger` sekali secara manual untuk
 *    memasang trigger otomatis (default: setiap 5 menit).
 * 5. SESUAIKAN `GMAIL_SEARCH_QUERY` dan fungsi `parseEmailBri_()` di
 *    bawah dengan format email notifikasi BRI yang SEBENARNYA Anda
 *    terima -- yang ada di file ini baru kerangka/placeholder.
 *
 * CATATAN KEAMANAN
 * - Setiap kali ngrok di-restart, URL-nya berubah -- WEBHOOK_URL di
 *   Script Properties harus diupdate manual setelah itu.
 * - Jangan percaya isi email mentah-mentah; Laravel tetap memvalidasi
 *   ulang semua field di endpoint webhook.
 */

var GMAIL_SEARCH_QUERY = 'from:bri_notification@example.co.id is:unread -label:DanaTrack/Processed';
var PROCESSED_LABEL = 'DanaTrack/Processed';

function setupLabel() {
  if (!GmailApp.getUserLabelByName(PROCESSED_LABEL)) {
    GmailApp.createLabel(PROCESSED_LABEL);
  }
}

function createTrigger() {
  // Hapus trigger lama dulu supaya tidak dobel kalau dijalankan ulang.
  ScriptApp.getProjectTriggers().forEach(function (trigger) {
    if (trigger.getHandlerFunction() === 'cekEmailBaru') {
      ScriptApp.deleteTrigger(trigger);
    }
  });

  ScriptApp.newTrigger('cekEmailBaru')
    .timeBased()
    .everyMinutes(5)
    .create();
}

function cekEmailBaru() {
  var properties = PropertiesService.getScriptProperties();
  var webhookUrl = properties.getProperty('WEBHOOK_URL');
  var webhookSecret = properties.getProperty('WEBHOOK_SECRET');

  if (!webhookUrl || !webhookSecret) {
    Logger.log('WEBHOOK_URL / WEBHOOK_SECRET belum diset di Script Properties.');
    return;
  }

  var threads = GmailApp.search(GMAIL_SEARCH_QUERY, 0, 20);

  threads.forEach(function (thread) {
    thread.getMessages().forEach(function (message) {
      if (message.isUnread() === false) {
        return; // sudah pernah diproses run sebelumnya di thread yang sama
      }

      var payload = parseEmailBri_(message);

      if (!payload) {
        Logger.log('Gagal parsing email, dilewati: ' + message.getId());
        return;
      }

      var terkirim = kirimKeWebhook_(webhookUrl, webhookSecret, payload);

      if (terkirim) {
        message.markRead();
        thread.addLabel(GmailApp.getUserLabelByName(PROCESSED_LABEL));
      }
    });
  });
}

/**
 * TODO: sesuaikan dengan format ASLI email notifikasi BRI Anda.
 * Kerangka di bawah ini CONTOH, bukan hasil final.
 *
 * Harus menghasilkan object dengan field:
 *   email_message_id, subject, sender, type ('expense'|'income'),
 *   amount (angka, tanpa titik/koma ribuan), transaction_date
 *   (format 'YYYY-MM-DD HH:mm:ss'), description, merchant (opsional),
 *   reference_number (opsional).
 * Return null kalau email ini bukan notifikasi transaksi yang dikenali.
 */
function parseEmailBri_(message) {
  var body = message.getPlainBody();

  var nominalMatch = body.match(/Nominal\s*:?\s*Rp\s*([\d.,]+)/i);
  var tanggalMatch = body.match(/Tanggal\s*:?\s*(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})/i);
  var keteranganMatch = body.match(/Keterangan\s*:?\s*(.+)/i);
  var referensiMatch = body.match(/No\.?\s*Referensi\s*:?\s*(\S+)/i);
  var jenisMatch = body.match(/Jenis Transaksi\s*:?\s*(Debit|Kredit)/i);

  if (!nominalMatch || !tanggalMatch || !jenisMatch) {
    return null; // bukan format yang dikenali -- jangan tebak-tebak
  }

  var nominal = parseFloat(nominalMatch[1].replace(/\./g, '').replace(',', '.'));
  var tanggal = konversiTanggal_(tanggalMatch[1]);
  var jenis = jenisMatch[1].toLowerCase() === 'debit' ? 'expense' : 'income';

  return {
    email_message_id: message.getId(),
    subject: message.getSubject(),
    sender: message.getFrom(),
    type: jenis,
    amount: nominal,
    transaction_date: tanggal,
    description: keteranganMatch ? keteranganMatch[1].trim() : 'Transaksi dari email',
    merchant: null,
    reference_number: referensiMatch ? referensiMatch[1].trim() : null,
  };
}

/** Ubah "03/11/2026 14:22:10" -> "2026-11-03 14:22:10". */
function konversiTanggal_(tanggalIndo) {
  var bagian = tanggalIndo.split(' ');
  var tgl = bagian[0].split('/'); // [dd, mm, yyyy]
  var jam = bagian[1] || '00:00:00';

  return tgl[2] + '-' + tgl[1] + '-' + tgl[0] + ' ' + jam;
}

function kirimKeWebhook_(url, secret, payload) {
  try {
    var response = UrlFetchApp.fetch(url, {
      method: 'post',
      contentType: 'application/json',
      headers: { 'X-Webhook-Secret': secret },
      payload: JSON.stringify(payload),
      muteHttpExceptions: true,
    });

    var status = response.getResponseCode();

    if (status >= 200 && status < 300) {
      return true;
    }

    Logger.log('Webhook membalas status ' + status + ': ' + response.getContentText());

    return false;
  } catch (e) {
    Logger.log('Gagal mengirim ke webhook: ' + e.message);

    return false;
  }
}
