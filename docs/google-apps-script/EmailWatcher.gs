/**
 * DanaTrack -- Email Watcher (Google Apps Script)
 *
 * Baca email notifikasi transaksi BRImo di Gmail, lalu kirim ke webhook
 * DanaTrack supaya dibuatkan transaksi `status = pending` yang menunggu
 * persetujuan lewat Telegram.
 *
 * Parser di bawah (ekstrakField_, parseEmailBri_, parseTanggalBri_)
 * sudah disusun dari 7 contoh email BRImo ASLI (QRIS Bayar, QRIS
 * Tampil, Pembayaran BRIVA, Transfer BI-FAST, Transfer Bank BRI,
 * top-up DANA, top-up ShopeePay) -- bukan tebakan. Pendekatannya:
 * baca baris "Label<spasi/tab>Nilai" secara generik, bukan regex per
 * template, karena BRI memakai beberapa layout berbeda untuk jenis
 * transaksi berbeda.
 *
 * CARA PAKAI
 * 1. Buka https://script.google.com -> New project. Hapus isi default,
 *    paste seluruh file ini.
 * 2. Project Settings (ikon gerigi) -> Script Properties -> tambahkan:
 *      WEBHOOK_URL    = https://<subdomain-ngrok-anda>.ngrok-free.app/webhooks/email-transactions
 *      WEBHOOK_SECRET = (nilai EMAIL_WEBHOOK_SECRET dari .env DanaTrack)
 *    JANGAN hardcode dua nilai ini langsung di kode.
 * 3. Jalankan function `setupLabel` sekali secara manual (klik Run).
 * 4. Jalankan function `createTrigger` sekali secara manual (trigger
 *    otomatis tiap 5 menit).
 * 5. TODO: GMAIL_SEARCH_QUERY di bawah baru pakai filter domain
 *    "bri.co.id" + potongan kalimat yang muncul di body. Kalau Anda
 *    tahu alamat "From:" pasti dari email notifikasi ini (cek di
 *    Gmail, klik salah satu email itu, lihat detail pengirim), kabari
 *    saya supaya query-nya dipersempit.
 * 6. TODO: deteksi pemasukan (`type: 'income'`) BELUM teruji -- semua
 *    7 contoh yang ada adalah transaksi KELUAR (QRIS/transfer/top-up).
 *    Begitu ada contoh email transfer MASUK, kabari saya untuk
 *    menyesuaikan KATA_KUNCI_MASUK di bawah.
 *
 * CATATAN KEAMANAN
 * - URL ngrok berubah setiap restart -- WEBHOOK_URL di Script
 *   Properties harus diupdate manual setelah itu.
 * - Jangan percaya isi email mentah-mentah; Laravel tetap memvalidasi
 *   ulang semua field di endpoint webhook.
 */

var GMAIL_SEARCH_QUERY = 'from:bri.co.id ("Berikut detail transaksi kamu di BRImo" OR "informasi transaksi yang telah Anda lakukan di Aplikasi BRImo") -label:DanaTrack/Processed';
var PROCESSED_LABEL = 'DanaTrack/Processed';

// Field yang muncul sebagai baris "Label<spasi/tab berlebih>Nilai" di
// berbagai template email BRImo. Urutan di sini tidak penting --
// ekstrakField_ mencocokkan tiap baris terhadap semua label ini.
var LABEL_PATTERN = /^(Nomor Referensi|No\.\s*Ref|Tanggal Transaksi|Tanggal|Jenis Transaksi|Nama Merchant|Nama Tujuan|Bank Tujuan|Keterangan|Catatan|Nominal)\s{2,}(.+)$/i;

// Lihat TODO poin 6 di atas -- daftar ini belum pernah divalidasi
// terhadap email transfer masuk yang sungguhan.
var KATA_KUNCI_MASUK = ['dana masuk', 'menerima transfer', 'transfer masuk', 'setoran masuk', 'terima dana'];

var BULAN_MAP = {
  jan: '01', januari: '01',
  feb: '02', februari: '02',
  mar: '03', maret: '03',
  apr: '04', april: '04',
  mei: '05', may: '05',
  jun: '06', juni: '06', june: '06',
  jul: '07', juli: '07', july: '07',
  aug: '08', agu: '08', agustus: '08', august: '08',
  sep: '09', september: '09',
  okt: '10', oktober: '10', oct: '10', october: '10',
  nov: '11', november: '11',
  des: '12', desember: '12', dec: '12', december: '12',
};

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

  var processedLabel = GmailApp.getUserLabelByName(PROCESSED_LABEL);
  var threads = GmailApp.search(GMAIL_SEARCH_QUERY, 0, 20);

  threads.forEach(function (thread) {
    thread.getMessages().forEach(function (message) {
      var payload = parseEmailBri_(message);

      if (!payload) {
        Logger.log('Gagal parsing email (format tidak dikenali), dilewati: ' + message.getId());
        return;
      }

      var terkirim = kirimKeWebhook_(webhookUrl, webhookSecret, payload);

      if (terkirim) {
        thread.addLabel(processedLabel);
      }
      // Kalau gagal, SENGAJA tidak dilabel -- supaya dicoba lagi run
      // berikutnya. Idempotency di sisi Laravel (email_message_id
      // unik) membuat pengiriman ulang aman, tidak akan dobel.
    });
  });
}

/**
 * Baca baris "Label<spasi berlebih>Nilai" dari body email, hasilnya
 * dictionary {Label: Nilai}. Baris dengan nilai "-" diabaikan (artinya
 * "tidak ada catatan" di template BRImo).
 */
function ekstrakField_(body) {
  var hasil = {};

  body.split('\n').forEach(function (baris) {
    var cocok = baris.match(LABEL_PATTERN);

    if (cocok) {
      var nilai = cocok[2].trim();

      if (nilai && nilai !== '-') {
        hasil[cocok[1].trim()] = nilai;
      }
    }
  });

  return hasil;
}

function parseEmailBri_(message) {
  var body = message.getPlainBody();
  var field = ekstrakField_(body);

  if (!field['Nominal']) {
    return null; // bukan email transaksi yang dikenali
  }

  var nominal = parseFloat(
    field['Nominal'].replace(/Rp\s*/i, '').replace(/\./g, '').replace(',', '.')
  );

  var tanggalMentah = field['Tanggal Transaksi'] || field['Tanggal'];
  var tanggal = parseTanggalBri_(tanggalMentah || body);

  if (!tanggal) {
    return null;
  }

  var deskripsi = field['Nama Merchant']
    || (field['Nama Tujuan']
      ? field['Nama Tujuan'] + (field['Bank Tujuan'] ? ' (' + field['Bank Tujuan'] + ')' : '')
      : null)
    || ((field['Jenis Transaksi'] || 'Transaksi BRImo') + (field['Keterangan'] ? ' - ' + field['Keterangan'] : ''));

  return {
    email_message_id: message.getId(),
    subject: message.getSubject(),
    sender: message.getFrom(),
    type: tentukanJenis_(body),
    amount: nominal,
    transaction_date: tanggal,
    description: deskripsi,
    merchant: field['Nama Merchant'] || null,
    reference_number: field['Nomor Referensi'] || field['No. Ref'] || null,
  };
}

/** Lihat TODO poin 6 di komentar atas file ini. */
function tentukanJenis_(body) {
  var teksRendah = body.toLowerCase();

  for (var i = 0; i < KATA_KUNCI_MASUK.length; i++) {
    if (teksRendah.indexOf(KATA_KUNCI_MASUK[i]) !== -1) {
      return 'income';
    }
  }

  return 'expense';
}

/**
 * Cocok untuk "01 Nov 2026, 08:38:45 WIB", "27 Oktober 2026, 10:14 WIB"
 * (tanpa detik), dan "22 Oktober 2026 , 13:55:01 WIB" (spasi sebelum
 * koma). Nama bulan boleh singkatan Inggris ("Nov") atau nama Indonesia
 * lengkap ("Oktober") -- keduanya muncul di contoh email asli.
 */
function parseTanggalBri_(teks) {
  var cocok = teks.match(/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})\s*,?\s*(\d{1,2}):(\d{2})(?::(\d{2}))?\s*WIB/i);

  if (!cocok) {
    return null;
  }

  var bulan = bulanKeNomor_(cocok[2]);

  if (!bulan) {
    return null;
  }

  var tanggal = cocok[1].length === 1 ? '0' + cocok[1] : cocok[1];
  var jam = cocok[4].length === 1 ? '0' + cocok[4] : cocok[4];
  var detik = cocok[6] || '00';

  return cocok[3] + '-' + bulan + '-' + tanggal + ' ' + jam + ':' + cocok[5] + ':' + detik;
}

function bulanKeNomor_(namaBulan) {
  var key = namaBulan.toLowerCase();

  return BULAN_MAP[key] || BULAN_MAP[key.substring(0, 3)] || null;
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
