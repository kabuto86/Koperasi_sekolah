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
   Store ibu bapa (sejak U5): logik dalam `assets/js/store/{halaman}.js` + `common.js` (`storeGet/storePost` dengan slug & auth_required,
   `storeUrl`, `onStoreReady`, `renderSlips`, `storeStartPayment`) + `layout.js`; template (`templates/template1/`) paparan sahaja.
   Mock `store.js` berkongsi data vendor (pesanan store muncul di panel vendor) — PELAN 18.12.
   Modul add-on (sejak U6): halaman `vendor/addons/{code}.php` set `$moduleCode` → gerbang "Langgan modul ini" (`_layout/module-gate.php`);
   JS guna `onModuleReady()` + `moduleResponse()` (`assets/js/vendor/addon-common.js`). Data modul dalam mock `vendor-addons.js`
   (`MockAddons`, dikongsi panel & store; `addonRequire()` = koperasi + `ModuleAccess`) — PELAN 18.13.
   Sejak U7: menu panel ada `'roles'` (ditapis `core/PanelRole.php`; demo Peringkat 1 cookie `mock_role`); senarai penuh laluan API
   dalam PELAN 18.14 — **laluan baharu mesti ditambah ke jadual itu**. Aksesibiliti: setiap medan ada `<label>` / `aria-label`,
   butang ikon ada `aria-label`, carta SVG ada `<title>`, kontras teks ≥ 4.5:1 (WCAG AA).
   Sebelum commit jalankan `php tools/check-ui.php` (tiada inline CSS, tiada CDN, tiada teks paparan ditulis terus dalam HTML, kunci 4 bahasa sama).
7. **Peringkat 2 (B):** jalankan ujian kebocoran dua vendor + dua ibu bapa (PELAN 15) sebelum tutup session.
   Sejak B1: skema sebenar = `database/schema.sql` (75 jadual, PELAN 9 J); lajur baharu → fail `database/migrations/NNN-nama.sql`
   **dan** kemas kini `schema.sql` + PELAN 9. Konvensyen job & core dalam PELAN 10.2 (constructor menerima id dari sesi + input, `run()`,
   `Database::fetchOne/fetchAll/execute/insert`, `Response::ok/fail/notFound`). Data demo & akaun: kepala `database/seed.sql`.
   Semakan asas sebelum commit: `php tools/verify-b1.php` + `verify-b2.php` + `verify-b3.php` + `verify-b4.php` + `verify-b5.php` + `verify-b6.php` + `verify-b7.php` + `verify-b8.php` + `verify-b9.php` + `verify-b10.php` + `verify-b11.php` + `verify-b12.php` + `verify-b13.php` + `verify-b14.php` (mesti 0 gagal) + `php tools/check-ui.php`; tambah skrip semakan
   session sendiri `tools/verify-bN.php` dengan ujian kebocoran VENDOR01↔VENDOR02 dan ibu bapa A↔B (contoh HTTP sebenar: kelas `Browser`
   dalam `tools/test-helpers.php` sejak B3 — pelayan `php -S` sendiri, cookie, CSRF; `require __DIR__ . '/test-helpers.php'`).
   Sejak B4: skop koperasi melalui `VendorAuth::requireCooperative()`; stok HANYA melalui `StockLedger::move()` dalam transaksi DB;
   gambar melalui `core/Uploader` (data URL dari borang) — PELAN 10.2 "Konvensyen B4".
   Sejak B5: kit sekolah dalam `jobs/SchoolKit/` (`SchoolKits` pembantu); store mesti guna `SchoolKits::findPublished()` (draf tidak
   pernah kelihatan); harga kit sentiasa dikira dari harga semasa produk — PELAN 18.9 "B5 (sebenar)".
   Sejak B10: POS melalui `core/PosAuth` (juruwang PIN dalam sesi) + `jobs/Pos/` — jualan HANYA `PosCheckoutJob`, harga HANYA
   `PosCalculateCartJob` (sama dengan `pos-cart.js` — ubah kedua-duanya bersama), bayaran diterima HANYA `PosSales::complete` — PELAN 10.2 "Konvensyen B10".
   Sejak B11: refund HANYA `RefundOrderJob` (`jobs/Refund/` — kesan stok/status HANYA `Refunds::apply`; baki = bayaran − refund selesai − pending;
   gateway = pending → `driver->refund()` di luar transaksi → completed/failed; refund tunai → `pos_shift_id` syif terbuka, ditolak dari laci);
   laporan HANYA `RunReportJob` → satu job setiap jenis (`jobs/Report/`, pembantu `ReportRange`) — PELAN 10.2 "Konvensyen B11", 13 "Pelaksanaan B11".
   `mock/vendor-orders.js` sudah dibuang; `MockFinance` tinggal langganan, bil, kedai modul & log aktiviti.
   Sejak B12: bil dalam `jobs/Billing/` — langganan semasa HANYA `Subscriptions::current()` (kunci `Subscriptions::lockVendor()`), invois HANYA
   `Invoices::create/issue` (snapshot, `INV-{tahun}-{0001}`), baris pembaharuan HANYA `RenewalLines::build`, prorata HANYA `CalculateProrataJob`,
   akses modul HANYA `ModuleAccess::codes/has/require($vendorId, $cooperativeId, $kod)` (`jobs/Module/`), bayaran yuran HANYA
   `PlatformPaymentSettlement` (gateway owner `PlatformGateway`, webhook `webhooks/platform.php?gateway=`); status vendor ikut tunggakan HANYA
   `SuspendOverdueJob` / `VendorBillingStatus::restore`. Cron: `generate-invoices.php` (harian) & `suspend-overdue.php` (setiap jam) — PELAN 10.2
   "Konvensyen B12", 14 "Pelaksanaan B12". `mock/vendor-finance.js` dibuang; `mock/owner.js` tinggal terjemahan (B14).
   Sejak B13: modul add-on dalam `jobs/News/`, `jobs/Clearance/`, `jobs/Calendar/`, `jobs/SchoolOpening/`, `jobs/Messaging/` — setiap endpoint modul
   `requireCooperative` → `ModuleAccess::require`; e-mel berita HANYA `NewsPosts::queueEmail` (sekali sahaja); mesej WhatsApp/SMS HANYA
   `MessageNotifications` → `notification_queue` → `cron/send-messages.php` (`SendQueuedMessagesJob`, driver `messaging/` tanpa DB; ujian
   `KOPERASI_MESSAGING_TEST_URL` + `tools/fake-messaging.php`); `booked` slot HANYA checkout/batal, panel mengunci slot `FOR UPDATE` — PELAN 10.2
   "Konvensyen B13". `mock/vendor-addons.js` & `mock/vendor.js` dibuang; satu-satunya mock yang tinggal = `mock/owner.js` (terjemahan, B14).
   Sejak B14: **tiada mock lagi** (`assets/js/mock/` & `use_mock` dibuang; `Api.get/post` sentiasa `fetch`). Teks paparan dari `storage/lang/*.php`
   (DIJANA — `lang/*.php` + `translations`; kunci baharu tetap ditambah ke `lang/*.php` 4 bahasa); job yang menyimpan terjemahan memanggil
   `GenerateLangFilesJob` selepas commit. Gambar HANYA `Uploader` (kod semula GD). IP HANYA `Request::ip()`. Endpoint yang menerima `cooperative_id`
   SENTIASA `requireCooperative`. `tools/verify-b14.php` menyapu setiap fail `api/v1` (401/405/419/403/IDOR/SQLi) — endpoint baharu ikut serta
   automatik; backup harian `cron/backup-db.php` — PELAN 10.2 "Konvensyen B14", 12 "Pelaksanaan B14", 15.
   Sejak B6: store menyelesaikan koperasi dengan `StoreSchool::require($slug)` (baris koperasi → job; tiada id dari input); harga/stok/had
   store HANYA melalui `StorePricing`; troli melalui `Carts` (`quote` = CartQuote), anak melalui `Children` — PELAN 10.2 "Konvensyen B6", 18.12 "B6 (sebenar)".
   Ujian HTTP dengan parameter: `$browser->status($laluan, $params)` (`tools/test-helpers.php`).
   Sejak B7: pesanan ibu bapa melalui `CustomerOrders` (`jobs/Order/`); sebut harga checkout HANYA `CheckoutQuote::build()`; tahan/lepas stok
   `StockLedger::reserve/release`; no. pesanan & kod pendek `OrderNumbers::next()` (dalam transaksi); e-mel melalui `QueueEmailJob` (+ `$htmlVars`)
   → `email_queue` → `cron/send-emails.php` (`core/Mailer`); wang dalam teks `Money::format()`; QR untuk e-mel `QrCode::emailTable()` — PELAN 10.2
   "Konvensyen B7", 18.12 "B7 (sebenar)". Ujian serentak: `parallelPost()` (`tools/test-helpers.php`); SMTP ujian: `tools/fake-smtp.php`.
   Store tidak memuat mock lagi (sejak B7).
   Sejak B8: gateway melalui `payments/` (driver tanpa DB) + `jobs/Payment/` — driver HANYA dari `CooperativeGateways::driver()`; bil HANYA melalui
   `StartOnlinePaymentJob` (di luar transaksi DB); status `paid` HANYA oleh `PaymentSettlement::recheck/apply` (tanya API gateway + semak amaun,
   idempotent) — jangan sekali-kali tanda dibayar dari redirect / input. Ujian gateway: `tools/fake-gateway.php` + env `KOPERASI_GATEWAY_TEST_URL`
   (sandbox sahaja) — PELAN 10.2 "Konvensyen B8", 13 "Pelaksanaan B8".
   Sejak B9: pesanan panel vendor HANYA melalui `VendorOrders` (`jobs/Order/` — vendor sesi + koperasi disahkan; `STATUS_FLOW`); serah barang HANYA
   `HandoverOrderJob` (stok `StockLedger::collect` = tolak qty_on_hand DAN qty_reserved); bayaran tunai pesanan = baris `payments` cash
   (`ReceiveCashPaymentJob`); e-mel ibu bapa `OrderEmails::queueReady/queueCollected/queuePaid`; tetapan koperasi `Get/SaveCooperativeSettingsJob`
   (juruwang baca tanpa `alert_emails`) — PELAN 10.2 "Konvensyen B9", 18.9 "B9 (sebenar)", 18.10.
   Sejak B2: endpoint ikut konvensyen PELAN 10.2 (`Request::requireMethod` → `XAuth::requireRole/requireLogin` → `Csrf::verifyRequest` →
   job yang melempar `ValidationException`). Bila modul siap: **tambah awalan laluan ke `Api.livePrefixes`** (`assets/js/api.js`), buang
   `Mock.register` laluan itu, dan tanda `— (Bn)` dalam PELAN 18.14. Halaman panel sudah dilindungi sesi (`requirePage`); akaun demo:
   kepala `database/seed.sql` (kata laluan `Demo@12345`; kod 2FA owner untuk ujian: `php tools/dev-totp.php owner@koperasiku.my`).
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
(lencana clearance, banner tutup, pilih slot ambil semasa checkout). Store sudah membaca
MockStore.news / clearanceSales / clearanceItems (U5) — panel vendor menulis ke data yang sama.
Setiap modul berkunci jika tidak dilanggan: papar kad "Langgan modul ini".
```

## U7 — Semakan UI menyeluruh

```
Ikut peraturan umum. Session: U7 — Semakan UI. Prasyarat: U6 ✅.
(✅ siap 25 Okt 2026 — lihat PROSES.md. Juga diselesaikan: perkara tertangguh bertanda U7 — baris cukai, penapis slot ambil,
banner tutup di POS, menu ikut peranan, navbar Tamil.)

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
Rujukan laluan & skop: PELAN 18.14 (indeks 214 laluan). Senarai UI tertangguh: PROSES.md "Perkara UI tertangguh (U7)".
Baca DOC/PELAN.md bahagian 8, 9 (semua), 10.1, 16 (perkara 9).

1. database/schema.sql — 69 jadual (PELAN 9), utf8mb4_unicode_ci, InnoDB, FK, UNIQUE & indeks ikut
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

## B2 — Auth ✅ (26 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B2 — Auth. Prasyarat: B1 ✅.
Baca DOC/PELAN.md bahagian 4, 11, 15.

core/OwnerAuth, VendorAuth, CustomerAuth (tiga nama sesi berasingan), core/Totp, RateLimit.
Jadual sedia ada dari B1: login_attempts (+ jenis pos_pin), rate_limits, totp_recovery_codes, customer_tokens,
vendor_user_tokens (jemputan / reset kakitangan); TOTP disulitkan dengan Crypto. Tukar api.js kepada fetch() untuk
laluan auth (header X-CSRF-Token dari <body data-csrf>), mock kekal untuk modul lain sehingga session masing-masing.
jobs/Auth/: OwnerLoginJob, VendorLoginJob, CustomerLoginJob (e-mel atau telefon),
CustomerRegisterJob, VerifyEmailJob, ForgotPasswordJob, ResetPasswordJob, ThrottleJob,
CheckSubscriptionJob, SetupTotpJob, VerifyTotpJob, PosPinLoginJob.
api/v1/*/auth/*. Sambung halaman log masuk & daftar dari U1/U5.
Sahkan: hash $argon2id$; mesej ralat sama; kunci selepas 5 gagal; e-mel ibu bapa sama tidak
boleh daftar dua kali; vendor suspended hanya boleh lihat halaman bil; 2FA owner wajib.
```
> Hasil B2: juga `vendor/cooperatives/list` (baca) & `store/session` sebenar; laluan baharu `owner|vendor/auth/logout|forgot|reset`,
> `vendor/auth/verify-2fa`; halaman `owner|vendor/forgot-password.php` & `reset-password.php`; migrasi `001-auth-tokens.sql`.

## B3 — Owner ✅ (26 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B3 — Owner. Prasyarat: B2 ✅.
Baca DOC/PELAN.md bahagian 5.1, 9 (A, B), 14.
Job untuk semua halaman U2: vendor, koperasi, pelan, plan_coop_tiers, modul, template,
gateway, darjah, templat e-mel, pengumuman, tetapan, kakitangan owner, log audit.
jobs/Billing/CalculateCoopPriceJob (peringkat). Tukar api.js bahagian owner kepada fetch.
Sahkan: owner_staff ditolak untuk harga & tetapan (403).
```
> Hasil B3: 32 endpoint owner sebenar (langganan/invois kekal mock → B12, terjemahan → B14); laluan baharu `owner/plans/options`
> (tapisan vendor untuk owner_staff); `tools/verify-b3.php` + `tools/test-helpers.php` (kelas `Browser` dikongsi); seed templat e-mel,
> nota vendor & pengumuman contoh. Nama modul/darjah owner disimpan dalam `translations` — `t()` di halaman lain masih dari `lang/*.php` (B14).

## B4 — Vendor: koperasi, katalog, inventori ✅ (26 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B4. Prasyarat: B3 ✅.
Baca DOC/PELAN.md bahagian 5.2 (1–5), 8, 9 (C, D).
Job: SaveCooperativeJob (semak PlanLimit + kira harga tambahan), ListCooperativesJob (sudah ada sejak B2 — bacaan;
tambah GetCooperativeJob + opening_hours), SaveSchoolClassJob, kategori, produk (+ varian, darjah, terjemahan), UploadProductImageJob
(core/Uploader), stok masuk, pelarasan, sejarah, stok rendah, pembekal, PO + terima barang,
stock take. Semua stok_movements dalam DB transaction.
Ujian kebocoran: produk VENDOR01 diminta oleh VENDOR02 → 404; cooperative_id vendor lain → ditolak.
Peralihan: mock vendor masih `MockVendor.sessionVendorId = 1` — selepas B4 halaman katalog guna data DB (VENDOR02 / SMK Seri Indah).
```
> Hasil B4: 36 endpoint vendor sebenar (koperasi, kelas, kategori, produk, inventori, pembekal, PO, stock take, papan pemuka, juga
> `templates/options` & `modules/active`); `core/Uploader`, `jobs/Billing/PlanLimit`, `StockLedger`; `tools/verify-b4.php`.
> `vendor/settings/*` DITANGGUH ke B9 (skrin serah/POS mock masih membaca tetapan mock). Seed: contoh PO-2026-0001 & stock take selesai.

## B5 — Kit keperluan sekolah ✅ (26 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B5. Prasyarat: B4 ✅.
Baca DOC/PELAN.md bahagian 5.2 (6), 9 (E), 16 (perkara 7).
Job: ListKitsJob, GetKitJob, SaveKitJob, CopyKitFromYearJob, PublishKitJob.
Sahkan: kit hanya boleh mengandungi produk koperasi yang sama; kit draf tidak kelihatan di store.
```
> Hasil B5: 7 endpoint `vendor/kits/*` sebenar; 7 job + pembantu `SchoolKits` (`findPublished` untuk store B6) + `ListKitProductOptionsJob`;
> id item kit kekal semasa sunting (rujukan troli B6); padam kit draf ditolak jika ada pesanan; `tools/verify-b5.php`.
> Mock `MockOrders.kits` kekal untuk `store/kit` sehingga B6.

## B6 — Store ibu bapa: anak, katalog, kit, troli ✅ (26 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B6. Prasyarat: B5 ✅.
Baca DOC/PELAN.md bahagian 5.4, 8.5, 10.5.
store/index.php: slug → koperasi → template (jatuh balik template1) → halaman.
Job: SearchSchoolsJob, ListChildrenJob, SaveChildJob, StoreCatalogJob, StoreProductJob,
StoreKitForChildJob (kit ikut darjah anak & tahun semasa), cart: AddToCartJob,
AddKitToCartJob (terima senarai item yang masih ditanda; kit melalui `SchoolKits::findPublished()` — B5), UpdateCartItemJob, GetCartJob
(harga dikira semula di pelayan, ikut anak).
Ujian: ibu bapa A tidak boleh tambah item untuk anak ibu bapa B; harga dari browser diabaikan.
Sejak B2: akaun & `store/session` sudah sebenar (`GetStoreSessionJob` sudah kira troli & sekolah anak dari DB). Buang jambatan
sementara dalam `mock/store.js` (`storeAdoptCustomer`, `storeAdoptSession`, `Mock.onLive`, `Mock.beforeFirst`) bila anak & troli sebenar.
```
> Hasil B6: 19 laluan store sebenar (sekolah, kelas, anak, katalog, produk, kit, troli, berita, jualan murah); 19 job (termasuk `ResolveStoreTemplateJob`) + 4 pembantu
> `StoreSchool`, `StorePricing`, `Children`, `Carts`; template ikut `cooperatives.template_id` (jatuh balik template1), pratonton
> `?template=` hanya owner / vendor pemilik; URL cantik `/{slug}/` (`.htaccess` + `config pretty_store_urls`); `tools/verify-b6.php`.
> Jambatan sesi ibu bapa mock DIKEKALKAN untuk pesanan mock (B7) + jambatan troli sebenar → checkout mock (lihat B7).

## B7 — Checkout tunai, pesanan & e-mel ✅ (27 Okt 2026 — lihat PROSES.md)

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
Sejak B6: troli & harga sebenar — CheckoutJob guna `Carts::quote()` / `StorePricing` (jangan tulis semula pengiraan harga), tambah promosi
(satu setiap pesanan, kupon / automatik terbaik, cukai selepas diskaun) dan slot ambil. Bila checkout & pesanan sebenar: buang baki
`mock/store.js` (jambatan `MockStore.liveCart`, `storeClearLiveCart`, `storeAdoptCustomer/Session`, `Mock.onLive`, `Mock.beforeFirst`).
```
> Hasil B7: 6 laluan store sebenar (`checkout/quote|place`, `orders/list|get|cancel|pay`); `jobs/Order/` (CheckoutJob, GetCheckoutQuoteJob,
> ListCustomerOrdersJob, GetCustomerOrderJob, CancelOrderJob, PayCustomerOrderJob, ExpireCashOrdersJob + pembantu CheckoutQuote, CustomerOrders,
> OrderNumbers, OrderEmails), `jobs/Promotion/Promotions`, `SendQueuedEmailsJob`; `core/Mailer`, `Money`, `QrCode`; `cron/send-emails.php` &
> `expire-cash-orders.php`; `mock/store.js` + jambatan mock dibuang; seed promosi; migrasi `002-order-receipt-qr.sql`; `tools/verify-b7.php`.
> Online masih tertutup (perlu driver B8) — `store/orders/pay` & `storeStartPayment` menunggu B8.

## B8 — Payment gateway ✅ (28 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B8. Prasyarat: B7 ✅.
Baca DOC/PELAN.md bahagian 13.
payments/GatewayInterface.php, GatewayFactory.php, drivers/ToyyibPayGateway.php (dahulu),
kemudian Billplz, SenangPay, iPay88, Stripe. Job: SaveCooperativeGatewayJob (sulitkan
kredensial), StartOnlinePaymentJob, HandleGatewayCallbackJob (tandatangan + semakan semula +
amaun + idempotent), api/v1/webhooks/*.php, webhook_logs.
Sahkan dengan sandbox: redirect palsu "berjaya" tanpa callback → pesanan kekal pending;
callback berganda → satu payments sahaja.
Sejak B7: CheckoutJob sudah mencipta pesanan `pending_payment` bila `payment_method = online` — online hanya ditawarkan bila `accept_online` +
gateway koperasi aktif + kelas `GatewayFactory` wujud (CheckoutQuote::paymentMethods). Isi `payment` dalam jawapan place (`{ reference, gateway_name,
amount, mode, redirect_url }`) dan `PayCustomerOrderJob` (kini memulangkan method_unavailable). Ganti modal simulasi `storeStartPayment`
(`store/payment/simulate`, tiada mock sejak B7) dengan redirect gateway. Tentukan luput `pending_payment` (expires_at) + cron.
Laluan vendor/gateways/* sebenar di sini.
```
> Hasil B8: `payments/` (GatewayInterface, GatewayFactory, BaseGateway, GatewayException + 5 driver); 13 job `jobs/Payment/` (CooperativeGateways,
> PaymentSettlement, StartOnlinePaymentJob, HandleGatewayCallbackJob, CheckOrderPaymentJob, ExpirePendingPaymentsJob, GetPaymentFormJob +
> 6 job tetapan/rekod vendor); `vendor/payments/*` (6) sebenar (`vendor_admin`); `api/v1/webhooks/{5 gateway}.php`; `store/orders/check-payment`
> baharu, `store/payment/simulate` dibuang; `store/payment-return.php` & `payment-redirect.php` (iPay88); `cron/expire-online-orders.php`;
> migrasi `003-webhook-result.sql`; `tools/fake-gateway.php` + `verify-b8.php`. Tertangguh: refund melalui API gateway (B11), QR online POS (B10),
> e-mel "bayaran diterima" (B9), gateway owner untuk invois (B12 — `ListPaymentGatewaysJob::ownerSecrets` + driver yang sama).

## B9 — Pengurusan pesanan & serah barang ✅ (28 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B9. Prasyarat: B8 ✅.
Baca DOC/PELAN.md bahagian 6, 7.
Job: ListVendorOrdersJob, GetVendorOrderJob, UpdateOrderStatusJob, PickingListJob,
PrintLabelsJob, FindOrdersForPickupJob (cara A nama/kelas, B no. pendek, C/D token QR),
VerifyPickupCodeJob (cara E, had cubaan), HandoverOrderJob (DB transaction: order_pickups,
collected_qty, tolak stok sebenar, lepas tahanan, status, e-mel "telah diambil oleh ...").
Sahkan: juruwang koperasi lain tidak boleh serah pesanan koperasi ini; kod salah 5 kali → dikunci.
Juga (dari B4): vendor/settings/get|save sebenar (cooperative_settings + cooperatives.default_language; pickup_methods dibaca skrin serah)
— gunakan CooperativeSettings (jobs/Cooperative). Stok: tahan/lepas sudah ada (B7: StockLedger::reserve/release); ambil = tolak qty_on_hand
DAN qty_reserved (tambah StockLedger::collect, pergerakan 'collect'). Pesanan store sebenar sejak B7 — panel vendor pesanan/serah barang masih mock
sehingga session ini. Semak kod 4 digit dengan password_verify(orders.pickup_code_hash). E-mel order_ready / order_collected melalui QueueEmailJob.
Sejak B8: pesanan online `paid` datang dari PaymentSettlement (webhook); rekod `payments` online sudah ada — bayaran TUNAI semasa serah barang mesti
menulis baris `payments` (method cash, received_by juruwang) supaya vendor/payments/list lengkap. Pertimbang e-mel "bayaran diterima" (order_paid)
selepas PaymentSettlement `paid`. Log status `paid` oleh `system` + nota `payment.paid_after_close` = wang diterima selepas pesanan ditutup (perlu refund).
```
> Hasil B9: 16 laluan sebenar (`vendor/orders/*` 9 termasuk `unlock-code` baharu, `vendor/handover/*` 3, `vendor/customers/*` 2, `vendor/settings/*` 2);
> `jobs/Order/` pembantu `VendorOrders` + 12 job (ListVendorOrdersJob, GetVendorOrderJob, UpdateOrderStatusJob, BulkOrderStatusJob, PickingListJob,
> PrintLabelsJob, ListOrderSlotOptionsJob, FindOrdersForPickupJob, ReceiveCashPaymentJob, VerifyPickupCodeJob, HandoverOrderJob, UnlockPickupCodeJob),
> `jobs/Customer/` ListVendorCustomersJob & SaveCustomerNoteJob, `jobs/Cooperative/` Get/SaveCooperativeSettingsJob; `StockLedger::collect`;
> `OrderEmails::queueReady/queueCollected/queuePaid` (+ templat `order_paid`, migrasi `004-order-handover-emails.sql`); `tools/verify-b9.php`.
> Tertangguh: resit PDF (tidak perlu — slip/label dicetak dari pelayar), refund dari panel (B11, `vendor/refunds/*` masih mock), POS sebenar (B10).

## B10 — POS kaunter ✅ (28 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B10. Prasyarat: B9 ✅.
Baca DOC/PELAN.md bahagian 5.2 (9), 17, 18.11 (kontrak POS dari U4). Logik troli sudah dalam
assets/js/vendor/pos-cart.js (dari pos-system) — PosCalculateCartJob mesti memberi jumlah yang sama.
Job: PosCatalogJob, FindByBarcodeJob, PosCalculateCartJob, PosCheckoutJob (order channel=pos,
bayar serta-merta, tolak stok), ReceivePosCashForOrderJob (bayar tunai pesanan online),
OpenShiftJob, CloseShiftJob, ShiftReportJob, GetReceiptJob, PosPinLoginJob (SUDAH ADA sejak B2 — sambung ke vendor/pos/pin-login),
PosOnlinePaymentJob (QR gateway + webhook → PAID, tahanan stok). Gantikan PIN demo 1234.
Sahkan: cashier ditolak untuk endpoint katalog/tetapan (403).
Sejak B7: promosi guna Promotions::choose() (jobs/Promotion — sama dengan pos-cart.js) dan OrderNumbers::next() untuk no. pesanan POS;
vendor/promotions/* sebenar di sini (seed sudah ada 3 promosi).
Sejak B8: QR online POS = bil gateway koperasi (CooperativeGateways::active + driver->createBill, baris payments pending) dan status melalui
PaymentSettlement (webhook / vendor/pos/payment-status menyemak semula ke gateway) — jangan tulis logik bayaran kedua. POS mock `online_available`
masih membaca MockFinance.gateways sehingga session ini.
Sejak B9: vendor/settings/get sebenar (resit, cukai, juruwang dibenarkan) — POS sudah membacanya; ReceivePosCashForOrderJob = guna semula
ReceiveCashPaymentJob (baris payments cash + received_by) dan serah melalui HandoverOrderJob — jangan tulis logik kedua. Jualan POS menolak stok
dengan StockLedger::move('pos_sale'); butiran pesanan POS sudah dipapar oleh vendor/orders/get (VendorOrders::detail). Buang baki MockOrders
(mock/vendor-orders.js) yang hanya digunakan POS bila POS sebenar; MockFinance.settings boleh dibuang.
```
> Hasil B10: 15 laluan sebenar (`vendor/pos/*` 12, `vendor/promotions/*` 3); `core/PosAuth`; `jobs/Pos/` (PosCashiers, PosShifts, PosSales +
> GetPosSessionJob, PosLoginJob, OpenShiftJob, CloseShiftJob, ShiftReportJob, ListShiftSalesJob, PosCatalogJob, PosCalculateCartJob, PosCheckoutJob,
> PosOnlinePaymentJob, PosPaymentStatusJob, CancelPosPaymentJob, GetPosReceiptJob); `jobs/Promotion/` List/Save/DeletePromotionJob; `OrderNumbers::nextPos`;
> `PaymentSettlement` cabang POS; `mock/vendor-pos.js`, promosi & `MockFinance.settings` mock dibuang; `hideModal` menunggu animasi; `tools/verify-b10.php`.
> Tidak dibina (sengaja): `FindByBarcodeJob` (padanan di pelayar dari katalog), `ReceivePosCashForOrderJob` (= `vendor/handover/receive-cash` B9).
> Tertangguh: refund POS & tolak refund tunai dari laci (B11); QR iPay88 di kaunter (gateway borang POST perlu sesi ibu bapa).

## B11 — Laporan, refund, PO & stock take ✅ (28 Okt 2026 — lihat PROSES.md)
> PO & stock take sudah siap dalam B4 — B11 tinggal laporan, refund & promosi.

```
Ikut peraturan umum. Session: B11. Prasyarat: B10 ✅.
Job laporan (harian, bulanan, produk, darjah, kelas, untung, stok, tunai vs online, ExportCsvJob),
RefundOrderJob (pulang stok melalui StockLedger, gateway refund jika disokong — tambah kaedah refund() pada driver payments/ yang
supportsRefund(), sejak B8 hanya Stripe — audit log). Promosi SUDAH siap dalam B10 (SavePromotionJob; Promotions::choose dipakai
CheckoutQuote & PosCalculateCartJob) — tiada ApplyPromotionJob berasingan.
Sahkan: semua laporan menapis vendor_id + cooperative_id yang disahkan.
Sejak B9: pesanan, item (collected_qty/cancelled_qty/returned_qty), order_pickups & payments (online + tunai) sebenar — refund guna
VendorOrders::require (skop vendor + koperasi) dan CustomerOrders::releaseHolds / StockLedger untuk pulang stok; butang refund di
vendor/order.php sudah memaut ke vendor/refunds.php?order_id= (masih mock sehingga session ini). Pesanan `paid_late` (nota payment.paid_after_close)
perlu refund.
Sejak B10: jualan POS sebenar (`channel pos`, `order_items.source pos`, `pos_shift_id`, `cashier_id`, stok `pos_sale`) — refund POS dari halaman
refund yang sama; unit POS sentiasa sudah diambil → `returned_qty` + restock `refund`. Refund TUNAI semasa syif terbuka perlu ditolak dari
jangkaan laci (`PosShifts::summary` — tambah lajur/rujukan syif pada refunds jika perlu). Laporan jualan termasuk POS (paid_at). Bila refund &
laporan sebenar: buang `MockOrders` (mock/vendor-orders.js) & `MockFinance.gateways`/`refunds`.
```
> Hasil B11: 4 laluan sebenar (`vendor/refunds/{list,order,create}`, `vendor/reports/run`); `jobs/Refund/` (`Refunds`, `FindRefundOrderJob`,
> `ListRefundsJob`, `RefundOrderJob`); `jobs/Report/` (`RunReportJob` + 8 job laporan + `ReportRange`, `PeriodSales`, `ChildSales`);
> `GatewayInterface::refund()` (Stripe sebenar, lain → GatewayException); migrasi `005-refund-shift.sql` (`refunds.pos_shift_id`, `gateway_ref`,
> `refund_no` 40); `PosShifts::summary` menolak refund tunai syif (`refund_count`, `refund_total`, POS memaparnya); `mock/vendor-orders.js` &
> mock refund/laporan dibuang; `tools/verify-b11.php`. Tidak dibina (sengaja): `ExportCsvJob` (CSV dijana di pelayar dari jawapan yang sama).
> Tertangguh: e-mel "refund diproses" kepada ibu bapa; semakan semula refund gateway `pending` yang tersangkut (jika proses mati selepas gateway
> menerima) — kini kekal `pending` dan menempah baki; refund automatik ToyyibPay/Billplz (API tiada / manual).

## B12 — Langganan & invois vendor ✅ (29 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B12. Prasyarat: B11 ✅.
Baca DOC/PELAN.md bahagian 14.
Sejak B11: mock langganan & bil masih dalam mock/vendor-finance.js (MockFinance.subscriptions/plans/moduleSubs/templateSubs/invoices) — buang
bila vendor/subscription/*, vendor/addons/{catalog,quote,subscribe,cancel} & vendor/invoices/* sebenar. Laporan & refund vendor sudah sebenar.
Job: SubscribeJob (bulanan/tahunan), AddCooperativeSubscriptionJob, SubscribeModuleJob,
SubscribeTemplateJob, CalculateProrataJob, GenerateInvoiceJob (snapshot), PayInvoiceJob
(gateway owner), MarkInvoicePaidJob (owner). core/ModuleAccess, core/PlanLimit.
cron/generate-invoices.php, cron/suspend-overdue.php.
Sahkan: modul tidak dilanggan → endpoint modul 403; vendor suspended → store papar ditutup.
```
> Hasil B12: 21 laluan sebenar (`owner/subscriptions/list`, `owner/invoices/{list,get,issue,void,mark-paid}`, `vendor/subscription/{get,plans,change,
> cancel-change}`, `vendor/addons/{catalog,quote,subscribe,cancel}` — asal B13, `vendor/invoices/{list,get,pay}` + baharu `check-payment`,
> `vendor/activity-logs/list` — asal B11) + `webhooks/platform.php`; `jobs/Billing/` (pembantu `BillingPeriod`, `Subscriptions`, `Invoices`,
> `RenewalLines`, `AddonItems`, `VendorBillingStatus`, `PlatformGateway`, `PlatformPaymentSettlement` + job), `jobs/Module/ModuleAccess`,
> `jobs/Vendor/ListActivityLogsJob`, 2 cron. `core/PlanLimit` kekal dalam `jobs/Billing/PlanLimit.php` (sejak B4) dan `ModuleAccess` dalam `jobs/Module/`
> (kedua-duanya membaca DB — core tidak menulis SQL). Template premium kini per koperasi (`vendor/templates/options?cooperative_id=`). Store vendor
> digantung → 503 "Koperasi ditutup sementara". Tiada perubahan skema. Tertangguh: e-mel peringatan tunggakan / digantung; gateway borang POST
> (iPay88) untuk yuran; pro-rata kredit bila turun taraf / henti di tengah kitaran (sengaja tiada refund).

## B13 — Modul add-on ✅ (30 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B13. Prasyarat: B12 ✅.
Job & API untuk news, clearance (harga khas dalam GetCartJob), calendar (blocks_orders
disemak dalam CheckoutJob), school_opening (musim, slot ambil, kapasiti dalam transaction).
Setiap endpoint memanggil ModuleAccess::require($vendorId, $cooperativeId, 'kod') (sejak B12, jobs/Module/ModuleAccess.php —
403 + module_locked). Kedai add-on (vendor/addons/*) & ModuleAccess SUDAH siap dalam B12. Bila siap: buang mock/vendor-addons.js
(termasuk pembantu mockToday/mockDaysFromToday/mockActivity yang dipindah ke situ dalam B12) & MockVendor.activeModules; job modul menulis
WriteActivityLogJob (news_save, clearance_save, calendar_save, season_save, messaging_save … — label sudah ada dalam lang).
```
> Hasil B13: 23 laluan modul sebenar (`vendor/news/*` 4, `vendor/clearance/*` 6, `vendor/calendar/*` 3, `vendor/school-opening/*` 6,
> `vendor/messaging/*` 4); 5 folder job (`News`, `Clearance`, `Calendar`, `SchoolOpening`, `Messaging`); driver WhatsApp/SMS `messaging/` (Twilio,
> WhatsApp Cloud, iSMS — kunci `ID:RAHSIA`); cron baharu `send-messages.php` + berita berjadual dalam `send-emails.php` + peringatan tunai dalam
> `expire-cash-orders.php`; migrasi 006 (`notification_queue.order_id`). Harga clearance (StorePricing), tutup tempahan & slot (CheckoutQuote/
> CheckoutJob) sudah sebenar sejak B6/B7 — tidak diubah. Tertangguh: templat WhatsApp diluluskan Meta (mesej di luar 24 jam).

## B14 — Terjemahan & pengerasan keselamatan ✅ (30 Okt 2026 — lihat PROSES.md)

```
Ikut peraturan umum. Session: B14. Prasyarat: B13 ✅.
Baca DOC/PELAN.md bahagian 12, 15.
Modul terjemahan owner (jadual translations → jana lang/*.php), MissingKeysJob.
Semak senarai PELAN 15 satu per satu: header keselamatan & CSP, cookie, rate limit, muat naik,
token hash, PDPA padam akaun (DeleteCustomerAccountJob — nyahnama), audit log, skrip backup.
Ujian penembusan asas: IDOR setiap endpoint, CSRF, XSS pada nama produk/anak, SQLi.
Tulis hasil dalam PROSES.md.
```
> Hasil B14: `owner/translations/list|save` sebenar (`jobs/Language/` 5 fail baharu — `storage/lang/*.php` dijana), mock dibuang sepenuhnya,
> migrasi 007 (keterangan modul zh/ta → 0 kunci hilang); pengerasan: header tambahan, proksi dipercayai, muat naik dikod semula GD, rate limit
> kod ambil, PDPA nyahnama snapshot pesanan & baris gilir, audit `price_change`, backup harian `BackupDatabaseJob`; `tools/verify-b14.php`
> 112 semakan (sapuan automatik 230 endpoint). Tertangguh: rate limit API Flutter & token API (B15).

## B15 — API token Flutter & push

```
Ikut peraturan umum. Session: B15. Prasyarat: B14 ✅.
core/ApiTokenAuth (Bearer, hash SHA-256), jobs: IssueApiTokenJob, RevokeApiTokenJob,
Rate limit API Flutter (tertangguh dari B14 — log masuk token & setiap token). Jalankan verify-b14 (sapuan automatik endpoint baharu).
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
