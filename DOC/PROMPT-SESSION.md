# Prompt Session — KoperasiKu SaaS

> Cara guna: salin **satu** prompt di bawah ke dalam session Claude Code baharu.
> Jalankan ikut susunan dalam [PROSES.md](PROSES.md). Jangan langkau session.
> Setiap prompt boleh dijalankan sendiri — ia memberitahu Claude apa yang perlu dibaca dahulu.
>
> **Bila pengguna mengubah keperluan:** kemas kini prompt yang terlibat di sini, PELAN.md
> dan PROSES.md (Log perubahan pengguna) dalam commit yang sama.

---

## Peraturan umum — berlaku untuk SETIAP session

Setiap prompt di bawah bermula dengan "Ikut peraturan umum". Maksudnya:

1. **Baca dahulu:** `CLAUDE.md`, `DOC/PROSES.md`, dan bahagian `DOC/PELAN.md` yang disebut dalam prompt.
   Sahkan session sebelum sudah ✅ dalam PROSES.md. Kalau belum, berhenti dan beritahu pengguna.
2. Tandakan session ini 🟡 dalam PROSES.md semasa bermula.
3. **Larangan:** tiada inline CSS (tiada `style=""`, tiada `<style>`), tiada framework/Composer/jQuery,
   tiada CDN, tiada teks paparan ditulis terus (guna `t()` / `T()`), tiada `vendor_id` atau
   `customer_id` dari input, tiada SQL dalam `api/`.
4. Nama fail & kod dalam Bahasa Inggeris; teks paparan dalam Bahasa Melayu (bukan Indonesia)
   melalui fail bahasa — **tambah kunci baharu ke keempat-empat** `lang/ms.php`, `en.php`, `zh.php`, `ta.php`.
5. Kod mudah dibaca: fungsi pendek, nama jelas, komen ringkas dalam Bahasa Melayu bila perlu.
6. **Peringkat 1 (U):** halaman tidak menyentuh DB. Data hanya melalui `Api.get()` / `Api.post()`
   dalam `assets/js/api.js`. Bentuk data baharu dicatat dalam PELAN.md bahagian 18.
   Ikut konvensyen UI dalam PELAN 18.5 (layout, `$activeMenu`, `cooperativechange`, helpers) dan
   PELAN 18.6 (borang `bindForm`/`data-type`, senarai `bindListFilters`/`renderTable`/`renderPagination`).
   Satu fail JS per halaman dalam `assets/js/{owner|vendor}/`. Halaman vendor: data melalui
   `onCooperativeReady()` + `withCoop()`; halaman berkaitan dikumpul dengan sub-navigasi (`$subNavGroup`).
   Mock mendaftar storan dengan `Mock.persist()` supaya rekod baharu kekal antara halaman.
   Halaman pesanan/serah barang guna `assets/js/vendor/order-common.js` (`childLine`, `paymentBadge`, `bindGradeClassFilters`).
   Mock vendor: `vendor.js` (katalog/stok) · `vendor-orders.js` (kit/pesanan/serah) · `vendor-finance.js` (bayaran, refund, promosi,
   kakitangan, tetapan, laporan, langganan, log aktiviti — panggil `mockActivity()` untuk tindakan baharu). Baki item pesanan =
   qty − collected − cancelled (`mockItemRemaining`). CSV laporan: `downloadCsv()` dalam helpers.js.
   POS (sejak U4): `vendor-pos.js` (mock) · `assets/js/vendor/pos-cart.js` (`posUnitPrice`, `posTotals`, `posBestAutomatic`,
   `posSuggestCash`) — jualan POS ialah pesanan `channel = 'pos'`; layout skrin penuh `vendor/_layout/pos-header.php`.
   Sebelum commit jalankan `php tools/check-ui.php` (tiada inline CSS, kunci 4 bahasa sama).
7. **Peringkat 2 (B):** jalankan ujian kebocoran dua vendor + dua ibu bapa (PELAN 15) sebelum tutup session.
8. **Penutup wajib:**
   - Kemas kini `DOC/PROSES.md`: status ✅, tarikh, commit, nota, dan "Butiran session yang siap"
     (fail dicipta, keputusan kecil, perkara tertangguh)
   - Kalau ada keputusan baharu → kemas kini PELAN.md
   - Commit dengan mesej jelas, kemudian **push ke `kabuto86/Koperasi_sekolah`**
   - Laporkan kepada pengguna: apa yang siap, apa yang belum, session seterusnya

---

# PERINGKAT 1 — UI DESKTOP / WEB

## U1 — Kerangka UI

```
Ikut peraturan umum dalam DOC/PROMPT-SESSION.md. Session: U1 — Kerangka UI.
Baca DOC/PELAN.md bahagian 2, 3, 10.1, 10.4, 12.

Bina kerangka yang akan digunakan oleh semua session UI seterusnya:
1. assets/lib/: Bootstrap 5 + Bootstrap Icons (salin fail setempat, tiada CDN)
2. assets/css/app.css — gaya tambahan minimum (warna jenama, sidebar). Tiada inline CSS
3. core/Lang.php — muat lang/{kod}.php sekali, fungsi t($key, $params) dengan placeholder
   {name}, jatuh balik ms → kunci; fungsi e() = htmlspecialchars. Bahasa dipilih dari
   ?lang= / cookie, lalai ms
4. lang/ms.php, en.php, zh.php, ta.php — kunci umum (menu, butang, status pesanan, darjah)
5. assets/js/lang.js — T(key, params) membaca window.LANG yang dikeluarkan oleh PHP
   (melalui atribut data- atau fail JSON, BUKAN inline script — CSP)
6. assets/js/api.js — Api.get(path, params) / Api.post(path, body) memulangkan Promise
   { ok, data }. Peringkat 1: baca dari assets/js/mock/*.js dengan kelewatan 200ms
7. assets/js/helpers.js — formatRM(sen), formatDate, showToast, confirmDialog, renderTable
8. Tiga layout dengan partial header/sidebar/footer:
   - owner/_layout (sidebar menu owner PELAN 5.1)
   - vendor/_layout (sidebar menu vendor PELAN 5.2 + pemilih koperasi + menu add-on berkunci)
   - templates/template1/layout.php (mobile-first, navbar bawah di telefon)
9. Halaman contoh: owner/index.php, vendor/index.php, store/index.php — kosong tetapi berlayout
10. Pemilih bahasa (ms/en/zh/ta) di ketiga-tiga layout, dengan fon Noto Sans SC & Tamil
11. Halaman log masuk statik: owner/login.php, vendor/login.php (UI sahaja)

Sahkan: tiada atribut style= (grep), tukar bahasa mengubah semua teks, ketiga-tiga layout
responsif pada 375px dan 1366px (tangkap layar Playwright).
Catat bentuk { ok, data } dan format senarai dalam PELAN 18.
```

## U2 — UI Owner

```
Ikut peraturan umum. Session: U2 — UI Owner. Prasyarat: U1 ✅.
Baca DOC/PELAN.md bahagian 4, 5.1, 9 (A, B), 14.

Bina semua halaman owner/ dengan data mock (assets/js/mock/owner.js):
dashboard (kad MRR, vendor aktif/trial/tamat, carta ringkas), vendor (senarai + carian +
butiran + gantung/aktif), koperasi (semua vendor, baca sahaja), pelan (CRUD, harga bulanan &
tahunan, had), harga koperasi tambahan (jadual peringkat plan_coop_tiers, lalai harga sama),
modul add-on (CRUD, harga bulanan/tahunan, aktif), template (senarai, pratonton, harga),
langganan & invois (senarai, butiran invois, tanda dibayar), gateway (driver dibenarkan untuk
vendor + gateway owner), bahasa & terjemahan (jadual kunci × 4 bahasa, tapis kunci hilang),
darjah/tingkatan, templat e-mel, pengumuman vendor, log audit, tetapan platform, kakitangan owner,
halaman 2FA (UI sahaja).

Setiap borang ada pengesahan sisi klien (Bootstrap validation) — pengesahan sebenar di B3.
Mock data mesti ada VENDOR01 dan VENDOR02. Catat bentuk objek dalam PELAN 18.
```

## U3a — UI Vendor (bahagian 1)

```
Ikut peraturan umum. Session: U3a — UI Vendor bahagian 1. Prasyarat: U2 ✅.
Baca DOC/PELAN.md bahagian 5.2 (modul 1–5), 9 (C, D).

Bina dengan mock (assets/js/mock/vendor.js): dashboard vendor, koperasi saya (senarai +
borang tambah: nama sekolah, kod KPM, jenis sekolah, negeri, daerah, alamat, slug, logo,
template, waktu operasi, tunai/online; amaran harga koperasi tambahan), kelas sekolah,
produk & kategori (senarai, carian, borang dengan varian & darjah sasaran & terjemahan
pilihan, muat naik gambar UI), inventori (stok per koperasi, stok masuk, pelarasan,
stok rendah, sejarah pergerakan), pembekal & PO (senarai, borang PO, terima barang),
stock take.

Pemilih koperasi di topbar menukar data yang dipapar. Catat bentuk objek dalam PELAN 18.
```

## U3b — UI Vendor (bahagian 2) — kit, pesanan, serah barang

```
Ikut peraturan umum. Session: U3b — UI Vendor bahagian 2. Prasyarat: U3a ✅.
Baca DOC/PELAN.md bahagian 5.2 (modul 6–8, 10), 6, 7, 9 (E, G).

Bina dengan mock:
1. Kit keperluan sekolah — senarai kit per darjah/tingkatan per tahun; borang kit (tambah
   produk/varian, kuantiti, wajib/pilihan, nota, susunan); butang "Salin dari tahun lepas";
   terbit/draf
2. Pesanan online — senarai dengan tapis (status, darjah, kelas, tarikh, tunai/online),
   butiran pesanan (item dikumpul ikut anak: nama, darjah, kelas; siapa ambil; sejarah status),
   tukar status, picking list ikut kelas (halaman cetak, assets/css/print-label.css),
   label beg (nama anak, kelas, no. pendek, QR)
3. Skrin SERAH BARANG (vendor/pos/handover.php) — mesti sangat mudah:
   - Satu kotak carian besar: nama murid / no. pendek / imbas QR (pengimbas USB = papan kekunci)
   - Tapis pantas ikut darjah & kelas
   - Kad keputusan: nama murid, kelas, nama ibu bapa, senarai barang, status bayaran
   - Butang besar "Serah": pilih siapa ambil (murid/ibu bapa/wakil + nama), tanda item
     (ambil separa), minta kod 4 digit jika cara E aktif / melebihi had
   - Tunai belum bayar → butang "Terima bayaran tunai" dahulu
   - Mesej berjaya + "Ibu bapa telah dimaklumkan"
4. Ibu bapa & murid — senarai baca sahaja ikut koperasi

Sahkan skrin serah boleh digunakan dengan papan kekunci sahaja (Tab/Enter).
Catat bentuk objek dalam PELAN 18.
```

## U3c — UI Vendor (bahagian 3)

```
Ikut peraturan umum. Session: U3c — UI Vendor bahagian 3. Prasyarat: U3b ✅.
Baca DOC/PELAN.md bahagian 5.2 (modul 11–18), 5.3, 13, 14.

Bina dengan mock: bayaran (pilih gateway dari senarai dibenarkan owner, borang kunci API
ikut config_fields gateway, mod sandbox/live, tunai on/off, senarai bayaran), pemulangan &
refund, promosi/kupon, kakitangan (peranan + akses koperasi), laporan (jualan harian/bulanan,
ikut produk, ikut darjah, ikut kelas, untung, stok, tunai vs online, butang CSV), tetapan
koperasi (e-mel amaran — boleh banyak, bahasa lalai, resit, cara ambil A–E dengan suis,
had wajib kod, tempoh luput tunai), langganan & bil (pelan semasa, kitaran bulanan/tahunan,
koperasi dilanggan, modul & template — butang Langgan dengan anggaran harga prorata, invois,
bayar), log aktiviti, kedai modul add-on (kad modul + harga).
Catat bentuk objek dalam PELAN 18.
```

## U4 — UI POS kaunter

```
Ikut peraturan umum. Session: U4 — UI POS kaunter. Prasyarat: U3c ✅.
Baca DOC/PELAN.md bahagian 5.2 (modul 9), 7, 17.
Rujuk juga logik troli POS runcit dalam repo kabuto86/pos-system (js/data.js: calcUnitPrice,
getTotals) — boleh disalin, jangan tulis semula dari awal.

Bina vendor/pos/index.php: skrin penuh, kategori + grid produk, carian, imbas barcode
(medan sentiasa fokus), modal varian, troli, diskaun/kupon, bayar tunai (cadangan wang,
baki) / online (QR gateway — UI), resit (assets/css/receipt.css 58/80mm), buka/tutup syif,
log masuk PIN juruwang (UI — kakitangan & `pin_set` dari `vendor/staff/list`, U3c). Guna promosi dari
`vendor/promotions/list` (kod kupon / automatik) dan tetapan resit dari `vendor/settings/get`. Tab "Serah barang" memaut ke skrin U3b.
Sahkan boleh digunakan dengan pengimbas USB (papan kekunci) dan skrin sentuh 1024px.
```

## U5 — UI Store ibu bapa (Template 1)

```
Ikut peraturan umum. Session: U5 — UI Store ibu bapa Template 1. Prasyarat: U4 ✅.
Baca DOC/PELAN.md bahagian 5.4, 6, 7, 10.5.

Bina templates/template1/ (mobile-first) dengan mock (assets/js/mock/store.js) — semua
halaman dalam manifest.json PELAN 10.5:
- home (pilih sekolah / sekolah terakhir), school-select (carian sekolah ikut nama & negeri)
- register (nama, telefon MY, e-mel, kata laluan + sahkan, hubungan, bahasa, PDPA wajib),
  login (e-mel atau telefon), forgot-password
- children (tambah/sunting anak: nama, sekolah, darjah, kelas, tahun)
- catalog (ala carte, tapis kategori & darjah), product (varian, kuantiti, pilih anak)
- kit — pilih anak → senarai kit darjah dengan SEMUA item siap ditanda; uncheck / ubah
  kuantiti; amaran untuk item wajib; jumlah dikemas kini serta-merta; "Masuk troli"
- cart — item dikumpul ikut anak, boleh campur kit & ala carte
- checkout — semak, siapa ambil (murid sendiri/ibu bapa/wakil + nama), tunai/online (ikut
  tetapan koperasi)
- order-success — no. pendek besar, QR, butang "Cetak slip" (print-slip.css: nama anak,
  kelas, no., QR, untuk dimasukkan dalam beg)
- orders (rekod pembelian, tapis anak & tahun), order-detail (status, item, siapa ambil, resit)
- profile (bahasa, kata laluan, padam akaun)
- news & clearance (dipapar hanya jika modul aktif — bendera dari mock)
Template mesti paparan sahaja: semua logik dalam assets/js/ dan Api.
Sahkan pada 375px, dan aliran penuh kit → troli → checkout → slip berjalan dengan mock.
```

## U6 — UI modul add-on

```
Ikut peraturan umum. Session: U6 — UI modul add-on. Prasyarat: U5 ✅.
Baca DOC/PELAN.md bahagian 5.3, 9 (H).

Bina UI vendor untuk: news (senarai, borang pos, hantar e-mel), clearance (jualan, item &
harga khas, tempoh), calendar (kalendar bulanan, cuti sekolah/koperasi, sekat tempahan),
school_opening (musim pre-order, tarikh tutup, slot ambil ikut darjah & kapasiti),
whatsapp_sms (tetapan, UI sahaja). Paparan ibu bapa yang berkaitan dalam template1
(lencana clearance, banner tutup, pilih slot ambil semasa checkout).
Setiap modul berkunci jika tidak dilanggan: papar kad "Langgan modul ini".
```

## U7 — Semakan UI menyeluruh

```
Ikut peraturan umum. Session: U7 — Semakan UI. Prasyarat: U6 ✅.

1. grep: tiada style=, tiada <style>, tiada CDN, tiada teks Melayu terus dalam fail .php/.js
   (semua melalui t()/T())
2. Semak keempat-empat fail bahasa ada kunci yang sama (skrip kecil bandingkan kunci)
3. Tangkap layar Playwright setiap halaman pada 375px & 1366px dalam ms dan zh → DOC/screenshots/
4. Semak aksesibiliti asas: label borang, kontras, navigasi papan kekunci
5. Lengkapkan PELAN 18 (kontrak data) — ini menjadi spesifikasi backend
6. Senaraikan dalam PROSES.md semua perkara UI yang tertangguh
```

---

# PERINGKAT 2 — BACKEND & API

> Setiap session B: gantikan panggilan mock dalam `assets/js/api.js` untuk modul berkenaan
> dengan `fetch()` sebenar. Halaman tidak ditulis semula. Ikut kontrak PELAN 18.

## B1 — Skema, seed & core

```
Ikut peraturan umum. Session: B1 — Skema, seed & core. Prasyarat: U7 ✅.
Baca DOC/PELAN.md bahagian 8, 9 (semua), 10.1, 16 (perkara 9).

1. database/schema.sql — 68 jadual, utf8mb4_unicode_ci, InnoDB, FK, UNIQUE & indeks ikut
   PELAN 8.6 (indeks bermula vendor_id). Baris pertama SET NAMES utf8mb4; UTF-8 tanpa BOM
2. database/seed.sql — languages (ms lalai, en, zh, ta), grade_levels, satu owner, plan,
   plan_coop_tiers, modules, template1, payment_gateways, VENDOR01 (1 koperasi), VENDOR02
   (2 koperasi), produk & kit setiap koperasi, 2 ibu bapa (seorang ada anak di 2 sekolah),
   nama produk Cina/Tamil dalam product_translations untuk uji utf8mb4
3. Cipta DB koperasi_saas + pengguna koperasi_user (akses DB ini sahaja). Jangan sentuh
   tuisyen / pos_saas
4. config/database.example.php, config/app.php, .gitignore (database.php, secret.key, uploads/*)
5. core/: Database (PDO utf8mb4, EMULATE_PREPARES off, ERRMODE_EXCEPTION), Request, Response,
   Validator, Csrf, SecurityHeaders, Crypto (sodium secretbox), bootstrap.php
6. jobs/Cooperative/ValidateCooperativeJob.php
Sahkan: import dengan --default-character-set=utf8mb4; HEX nama Cina betul melalui PDO;
UNIQUE barcode sama dibenarkan merentas koperasi.
```

## B2 — Auth

```
Ikut peraturan umum. Session: B2 — Auth. Prasyarat: B1 ✅.
Baca DOC/PELAN.md bahagian 4, 11, 15.

core/OwnerAuth, VendorAuth, CustomerAuth (tiga nama sesi berasingan), core/Totp, RateLimit.
jobs/Auth/: OwnerLoginJob, VendorLoginJob, CustomerLoginJob (e-mel atau telefon),
CustomerRegisterJob, VerifyEmailJob, ForgotPasswordJob, ResetPasswordJob, ThrottleJob,
CheckSubscriptionJob, SetupTotpJob, VerifyTotpJob, PosPinLoginJob.
api/v1/*/auth/*. Sambung halaman log masuk & daftar dari U1/U5.
Sahkan: hash $argon2id$; mesej ralat sama; kunci selepas 5 gagal; e-mel ibu bapa sama tidak
boleh daftar dua kali; vendor suspended hanya boleh lihat halaman bil; 2FA owner wajib.
```

## B3 — Owner

```
Ikut peraturan umum. Session: B3 — Owner. Prasyarat: B2 ✅.
Baca DOC/PELAN.md bahagian 5.1, 9 (A, B), 14.
Job untuk semua halaman U2: vendor, koperasi, pelan, plan_coop_tiers, modul, template,
gateway, darjah, templat e-mel, pengumuman, tetapan, kakitangan owner, log audit.
jobs/Billing/CalculateCoopPriceJob (peringkat). Tukar api.js bahagian owner kepada fetch.
Sahkan: owner_staff ditolak untuk harga & tetapan (403).
```

## B4 — Vendor: koperasi, katalog, inventori

```
Ikut peraturan umum. Session: B4. Prasyarat: B3 ✅.
Baca DOC/PELAN.md bahagian 5.2 (1–5), 8, 9 (C, D).
Job: SaveCooperativeJob (semak PlanLimit + kira harga tambahan), ListCooperativesJob,
SaveSchoolClassJob, kategori, produk (+ varian, darjah, terjemahan), UploadProductImageJob
(core/Uploader), stok masuk, pelarasan, sejarah, stok rendah, pembekal, PO + terima barang,
stock take. Semua stok_movements dalam DB transaction.
Ujian kebocoran: produk VENDOR01 diminta oleh VENDOR02 → 404; cooperative_id vendor lain → ditolak.
```

## B5 — Kit keperluan sekolah

```
Ikut peraturan umum. Session: B5. Prasyarat: B4 ✅.
Baca DOC/PELAN.md bahagian 5.2 (6), 9 (E), 16 (perkara 7).
Job: ListKitsJob, GetKitJob, SaveKitJob, CopyKitFromYearJob, PublishKitJob.
Sahkan: kit hanya boleh mengandungi produk koperasi yang sama; kit draf tidak kelihatan di store.
```

## B6 — Store ibu bapa: anak, katalog, kit, troli

```
Ikut peraturan umum. Session: B6. Prasyarat: B5 ✅.
Baca DOC/PELAN.md bahagian 5.4, 8.5, 10.5.
store/index.php: slug → koperasi → template (jatuh balik template1) → halaman.
Job: SearchSchoolsJob, ListChildrenJob, SaveChildJob, StoreCatalogJob, StoreProductJob,
StoreKitForChildJob (kit ikut darjah anak & tahun semasa), cart: AddToCartJob,
AddKitToCartJob (terima senarai item yang masih ditanda), UpdateCartItemJob, GetCartJob
(harga dikira semula di pelayan, ikut anak).
Ujian: ibu bapa A tidak boleh tambah item untuk anak ibu bapa B; harga dari browser diabaikan.
```

## B7 — Checkout tunai, pesanan & e-mel

```
Ikut peraturan umum. Session: B7. Prasyarat: B6 ✅.
Baca DOC/PELAN.md bahagian 6, 9 (G, I), 16 (perkara 2–6, 12).
Job: CheckoutJob (DB transaction: cipta pesanan, snapshot item + anak + darjah + kelas,
tahan stok FOR UPDATE, jana order_no & short_code unik, pickup_qr_token, kod 4 digit jika
perlu), ListCustomerOrdersJob, GetCustomerOrderJob, CancelOrderJob (lepas stok),
core/Mailer + email_queue + cron/send-emails.php (SMTP vanila atau PHPMailer disalin tanpa
Composer), cron/expire-cash-orders.php. E-mel: amaran vendor (semua alert_emails),
resit ibu bapa (no. pendek + QR), ikut bahasa.
Sahkan: dua checkout serentak untuk stok terakhir → satu sahaja berjaya.
```

## B8 — Payment gateway

```
Ikut peraturan umum. Session: B8. Prasyarat: B7 ✅.
Baca DOC/PELAN.md bahagian 13.
payments/GatewayInterface.php, GatewayFactory.php, drivers/ToyyibPayGateway.php (dahulu),
kemudian Billplz, SenangPay, iPay88, Stripe. Job: SaveCooperativeGatewayJob (sulitkan
kredensial), StartOnlinePaymentJob, HandleGatewayCallbackJob (tandatangan + semakan semula +
amaun + idempotent), api/v1/webhooks/*.php, webhook_logs.
Sahkan dengan sandbox: redirect palsu "berjaya" tanpa callback → pesanan kekal pending;
callback berganda → satu payments sahaja.
```

## B9 — Pengurusan pesanan & serah barang

```
Ikut peraturan umum. Session: B9. Prasyarat: B8 ✅.
Baca DOC/PELAN.md bahagian 6, 7.
Job: ListVendorOrdersJob, GetVendorOrderJob, UpdateOrderStatusJob, PickingListJob,
PrintLabelsJob, FindOrdersForPickupJob (cara A nama/kelas, B no. pendek, C/D token QR),
VerifyPickupCodeJob (cara E, had cubaan), HandoverOrderJob (DB transaction: order_pickups,
collected_qty, tolak stok sebenar, lepas tahanan, status, e-mel "telah diambil oleh ...").
Sahkan: juruwang koperasi lain tidak boleh serah pesanan koperasi ini; kod salah 5 kali → dikunci.
```

## B10 — POS kaunter

```
Ikut peraturan umum. Session: B10. Prasyarat: B9 ✅.
Baca DOC/PELAN.md bahagian 5.2 (9), 17, 18.11 (kontrak POS dari U4). Logik troli sudah dalam
assets/js/vendor/pos-cart.js (dari pos-system) — PosCalculateCartJob mesti memberi jumlah yang sama.
Job: PosCatalogJob, FindByBarcodeJob, PosCalculateCartJob, PosCheckoutJob (order channel=pos,
bayar serta-merta, tolak stok), ReceivePosCashForOrderJob (bayar tunai pesanan online),
OpenShiftJob, CloseShiftJob, ShiftReportJob, GetReceiptJob, PosPinLoginJob (pin_hash Argon2id, 5 cubaan → kunci),
PosOnlinePaymentJob (QR gateway + webhook → PAID, tahanan stok). Gantikan PIN demo 1234.
Sahkan: cashier ditolak untuk endpoint katalog/tetapan (403).
```

## B11 — Laporan, refund, PO & stock take

```
Ikut peraturan umum. Session: B11. Prasyarat: B10 ✅.
Job laporan (harian, bulanan, produk, darjah, kelas, untung, stok, tunai vs online, ExportCsvJob),
RefundOrderJob (pulang stok, gateway refund jika disokong, audit log), promosi
(SavePromotionJob, ApplyPromotionJob dalam GetCartJob & PosCalculateCartJob).
Sahkan: semua laporan menapis vendor_id + cooperative_id yang disahkan.
```

## B12 — Langganan & invois vendor

```
Ikut peraturan umum. Session: B12. Prasyarat: B11 ✅.
Baca DOC/PELAN.md bahagian 14.
Job: SubscribeJob (bulanan/tahunan), AddCooperativeSubscriptionJob, SubscribeModuleJob,
SubscribeTemplateJob, CalculateProrataJob, GenerateInvoiceJob (snapshot), PayInvoiceJob
(gateway owner), MarkInvoicePaidJob (owner). core/ModuleAccess, core/PlanLimit.
cron/generate-invoices.php, cron/suspend-overdue.php.
Sahkan: modul tidak dilanggan → endpoint modul 403; vendor suspended → store papar ditutup.
```

## B13 — Modul add-on

```
Ikut peraturan umum. Session: B13. Prasyarat: B12 ✅.
Job & API untuk news, clearance (harga khas dalam GetCartJob), calendar (blocks_orders
disemak dalam CheckoutJob), school_opening (musim, slot ambil, kapasiti dalam transaction).
Setiap endpoint memanggil ModuleAccess::require($cooperativeId, 'kod').
```

## B14 — Terjemahan & pengerasan keselamatan

```
Ikut peraturan umum. Session: B14. Prasyarat: B13 ✅.
Baca DOC/PELAN.md bahagian 12, 15.
Modul terjemahan owner (jadual translations → jana lang/*.php), MissingKeysJob.
Semak senarai PELAN 15 satu per satu: header keselamatan & CSP, cookie, rate limit, muat naik,
token hash, PDPA padam akaun (DeleteCustomerAccountJob — nyahnama), audit log, skrip backup.
Ujian penembusan asas: IDOR setiap endpoint, CSRF, XSS pada nama produk/anak, SQLi.
Tulis hasil dalam PROSES.md.
```

## B15 — API token Flutter & push

```
Ikut peraturan umum. Session: B15. Prasyarat: B14 ✅.
core/ApiTokenAuth (Bearer, hash SHA-256), jobs: IssueApiTokenJob, RevokeApiTokenJob,
RegisterPushDeviceJob, SendPushJob (notification_queue). Endpoint store/ menerima sesi
ATAU token. Hanya koperasi dengan modul mobile_app.
Dokumentasi API lengkap untuk Flutter dalam DOC/API.md.
```

---

# PERINGKAT 3 — APPS FLUTTER (hanya selepas Peringkat 1 & 2 ✅)

## F1 — UI Flutter

```
Ikut peraturan umum. Session: F1. Prasyarat: semua U dan B ✅.
Cipta folder mobile/ (projek Flutter). Skrin sama seperti Template 1 (U5) dengan data mock,
4 bahasa (flutter_localizations, kunci sama seperti lang/*.php).
```

## F2 — Sambung API

```
Ikut peraturan umum. Session: F2. Prasyarat: F1 ✅.
Sambung ke API B15 (DOC/API.md): log masuk token (simpan dalam secure storage), anak,
katalog, kit, troli, checkout, gateway (webview), rekod pembelian, QR pesanan.
```

## F3 — Push notification

```
Ikut peraturan umum. Session: F3. Prasyarat: F2 ✅.
Firebase Cloud Messaging: daftar peranti, notifikasi sedia diambil / telah diambil / berita.
```

## F4 — Terbit

```
Ikut peraturan umum. Session: F4. Prasyarat: F3 ✅.
Ikon, nama, dasar privasi (PDPA), build Android & iOS, senarai semak Play Store / App Store.
```
