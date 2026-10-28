# Proses — Apa Yang Telah Selesai

> **Satu-satunya sumber kebenaran tentang di mana kerja terhenti.**
> Kemas kini fail ini di HUJUNG setiap session, sebelum commit & push.
> Reka bentuk: [PELAN.md](PELAN.md) · Prompt kerja: [PROMPT-SESSION.md](PROMPT-SESSION.md)

**Status keseluruhan:** **Peringkat 1 siap** (U1–U7) · **Peringkat 2 berjalan** — skema & core (B1), auth sebenar (B2), panel owner (B3), katalog & inventori vendor (B4), kit sekolah (B5), store ibu bapa: anak, katalog, kit, troli (B6), checkout tunai, pesanan & queue e-mel (B7), payment gateway + webhook (B8), pesanan vendor & serah barang (B9), POS kaunter & promosi (B10)
**Session terakhir siap:** B10 — POS kaunter
**Session seterusnya:** **B11 — Laporan, refund, PO & stock take**

Petunjuk: ⬜ belum mula · 🟡 sedang berjalan · ✅ siap · ⛔ tersekat

---

## Peringkat 0 — Pelan

| Session | Tajuk | Status | Tarikh siap | Commit | Nota |
|---|---|---|---|---|---|
| S0 | Pelan, CLAUDE.md, DOC/ | ✅ | 24 Okt 2026 | (commit pertama) | PELAN, PROSES, PROMPT-SESSION |

## Peringkat 1 — UI desktop/web (data mock)

| Session | Tajuk | Status | Tarikh siap | Commit | Nota |
|---|---|---|---|---|---|
| U1 | Kerangka UI: layout, Bootstrap, `t()` 4 bahasa, `api.js` mock | ✅ | 24 Okt 2026 | `1082584` | 3 layout, 4 bahasa (155 kunci), CSP, tangkap layar `DOC/screenshots/U1/` |
| U2 | UI Owner | ✅ | 24 Okt 2026 | `98c608e` | 20 halaman, 338 kunci baharu, mock VENDOR01–07, tangkap layar `DOC/screenshots/U2/` |
| U3a | UI Vendor — dashboard, koperasi, produk, inventori, pembekal | ✅ | 25 Okt 2026 | `41b3a2e` | 18 halaman, 231 kunci baharu, ujian kebocoran VENDOR02 lulus, `DOC/screenshots/U3a/` |
| U3b | UI Vendor — kit sekolah, pesanan, serah barang, ibu bapa | ✅ | 25 Okt 2026 | `331aa9f` | 9 halaman, 154 kunci baharu, serah barang papan kekunci sahaja, `DOC/screenshots/U3b/` |
| U3c | UI Vendor — bayaran, refund, promosi, kakitangan, laporan, tetapan, langganan | ✅ | 25 Okt 2026 | `26d3640` | 11 halaman, 328 kunci baharu, ujian kebocoran VENDOR02 lulus, `DOC/screenshots/U3c/` |
| U4 | UI POS kaunter | ✅ | 25 Okt 2026 | `2bf72c8` | Skrin penuh, PIN, syif, imbas, QR, resit 58/80, 116 kunci baharu, ujian kebocoran lulus, `DOC/screenshots/U4/` |
| U5 | UI Store ibu bapa — Template 1 | ✅ | 25 Okt 2026 | `597d2d1` | 17 halaman, 250 kunci baharu, aliran kit → troli → checkout → slip, ujian kebocoran lulus, `DOC/screenshots/U5/` |
| U6 | UI modul add-on | ✅ | 25 Okt 2026 | `db41ded` | 9 halaman vendor, banner & slot ambil di store, 195 kunci baharu, ujian kebocoran lulus, `DOC/screenshots/U6/` |
| U7 | Semakan UI menyeluruh | ✅ | 25 Okt 2026 | `30558a0` | 83 halaman × 4 bahasa × 2 lebar, axe 0 pelanggaran, indeks 214 laluan (PELAN 18.14), 6 perkara tertangguh U7 siap, `DOC/screenshots/U7/` |

## Peringkat 2 — Backend & API

| Session | Tajuk | Status | Tarikh siap | Commit | Nota |
|---|---|---|---|---|---|
| B1 | Skema, seed, core | ✅ | 26 Okt 2026 | `8479340` | 75 jadual, seed 2 vendor/3 koperasi/2 ibu bapa, 6 kelas core baharu, `ValidateCooperativeJob`, `tools/verify-b1.php` 42/42 lulus |
| B2 | Auth owner, vendor, ibu bapa + 2FA + throttle | ✅ | 26 Okt 2026 | `847ea89` | 38 endpoint `api/v1/`, 39 job, 13 kelas core baharu, sesi sebenar semua panel & store, `tools/verify-b2.php` 94/94 lulus |
| B3 | Owner: pelan, harga koperasi, modul, template, vendor | ✅ | 26 Okt 2026 | `764f4c7` | 32 endpoint owner, 33 job + 3 pembantu, `CalculateCoopPriceJob`, owner_staff 403 di pelayan, `tools/verify-b3.php` 101/101 lulus |
| B4 | Vendor: koperasi, produk, kategori, varian, inventori, pembekal | ✅ | 26 Okt 2026 | `64290c1` | 36 endpoint vendor, 37 job + 7 pembantu, `core/Uploader`, `StockLedger` (stok dalam transaksi), PO & stock take, `tools/verify-b4.php` 118/118 lulus |
| B5 | Kit keperluan sekolah | ✅ | 26 Okt 2026 | `8ab4bf5` | 7 endpoint `vendor/kits/*`, 7 job + pembantu `SchoolKits` (`findPublished` untuk store), harga kit dari harga semasa, `tools/verify-b5.php` 59/59 lulus |
| B6 | Store ibu bapa: daftar, anak, katalog, kit, troli | ✅ | 26 Okt 2026 | `47a4acb` | 19 endpoint store, 19 job + 4 pembantu (`StoreSchool`, `StorePricing`, `Children`, `Carts`), template ikut koperasi, URL `/{slug}/`, `tools/verify-b6.php` 85/85 lulus |
| B7 | Checkout tunai, pesanan, queue e-mel | ✅ | 27 Okt 2026 | `2f15ccc` | 6 endpoint store, 7 job + 4 pembantu `jobs/Order/` + `Promotions` + `SendQueuedEmailsJob`, `core/Mailer` (SMTP vanila) · `Money` · `QrCode`, 2 cron, `mock/store.js` dibuang, `tools/verify-b7.php` 92/92 lulus |
| B8 | Payment gateway + webhook | ✅ | 28 Okt 2026 | `97e3b42` | 5 driver `payments/`, 13 job `jobs/Payment/`, `vendor/payments/*` sebenar, 5 webhook, semakan semula + amaun + idempotent, cron luput online, `tools/verify-b8.php` 95/95 lulus |
| B9 | Pengurusan pesanan & serah barang | ✅ | 28 Okt 2026 | `cdd17ba` | 16 endpoint vendor (pesanan, serah, ibu bapa, tetapan), pembantu `VendorOrders` + 16 job, `StockLedger::collect`, e-mel sedia/diambil/dibayar, kod 4 digit dikunci + buka oleh pengurus, `tools/verify-b9.php` 106/106 lulus |
| B10 | POS kaunter | ✅ | 28 Okt 2026 | (lihat log B10) | 15 endpoint (`vendor/pos/*` 12, `vendor/promotions/*` 3), `core/PosAuth`, 15 job + 3 pembantu `jobs/Pos/`, 3 job promosi, PIN Argon2id sebenar, QR gateway + webhook, `PaymentSettlement` cabang POS, mock POS dibuang, `tools/verify-b10.php` 97/97 lulus |
| B11 | Laporan, refund, PO, stock take | ⬜ | | | |
| B12 | Langganan & invois vendor + cron | ⬜ | | | |
| B13 | Modul add-on | ⬜ | | | |
| B14 | Modul terjemahan + pengerasan keselamatan | ⬜ | | | |
| B15 | API token Flutter + push | ⬜ | | | |

## Peringkat 3 — Apps Flutter (selepas Peringkat 1 & 2 siap)

| Session | Tajuk | Status | Tarikh siap | Commit | Nota |
|---|---|---|---|---|---|
| F1 | UI Flutter | ⬜ | | | |
| F2 | Sambung API | ⬜ | | | |
| F3 | Push notification | ⬜ | | | |
| F4 | Terbit Play Store / App Store | ⬜ | | | |

---

## Butiran session yang siap

### S0 — Pelan & dokumen (24 Okt 2026)

Dibuat:
- `CLAUDE.md` — konvensyen, susunan kerja, peraturan dokumen & push
- `DOC/PELAN.md` — reka bentuk penuh, 68 jadual
- `DOC/PROSES.md` — fail ini
- `DOC/PROMPT-SESSION.md` — prompt U1–U7, B1–B15, F1–F4

### U1 — Kerangka UI (24 Okt 2026)

Dibuat:
- `assets/lib/` — Bootstrap 5.3.3 (css + bundle js) dan Bootstrap Icons 1.11.3, setempat (dari npm, tiada CDN)
- `assets/fonts/` — Noto Sans SC & Noto Sans Tamil (woff2, 400 & 700, `unicode-range` — hanya dimuat bila aksara itu dipapar)
- `assets/css/app.css` — warna jenama (`--app-brand` #0f766e), `.btn-brand`, `.text-bg-brand`, sidebar, log masuk
- `config/app.php`, `core/bootstrap.php`, `core/Config.php`, `core/View.php` (url, asset, partial, storeUrl),
  `core/SecurityHeaders.php` (CSP tanpa inline), `core/Lang.php` (`t()`, `e()`, `?lang=` → cookie → `ms`)
- `lang/ms.php, en.php, zh.php, ta.php` — 155 kunci: menu owner/vendor/add-on/store, butang, umum, ralat,
  log masuk, 10 status pesanan, kaedah bayaran, siapa ambil, darjah PRA/D1–D6/T1–T6
- `assets/js/lang.js` (`T()` dari `<body data-lang>`), `api.js`, `helpers.js`, `vendor-layout.js`, `login.js`,
  `vendor-dashboard.js`, `mock/mock.js, owner.js, vendor.js, store.js`
- `partials/` (head, lang-switcher, sidebar-menu, ui-shell, scripts, login-form)
- `owner/_layout/`, `vendor/_layout/` (header, sidebar, footer, profile-menu, menu.php; vendor + addons.php)
- `templates/template1/` (manifest.json, layout.php, partials/, pages/home.php, css/, js/layout.js) — navbar bawah di telefon
- Halaman: `owner/index.php`, `owner/login.php`, `vendor/index.php`, `vendor/login.php`, `store/index.php`
- `tools/check-ui.php` — semak tiada inline CSS & kunci bahasa sama; `.gitignore`; `.htaccess` halang akses terus ke `_layout/`, `partials/`, `templates/*.php`

Disahkan:
- `php tools/check-ui.php` → tiada `style=` / `<style>`, 155 kunci sama dalam 4 bahasa
- Playwright: 5 halaman × 4 bahasa × 375px & 1366px — tiada ralat konsol/CSP, tiada kunci mentah dipapar,
  tiada skrol mendatar. Pemilih bahasa menukar semua teks dan diingat dalam cookie
- Log masuk owner → langkah 2FA → papan pemuka; pemilih koperasi vendor membuka kunci modul add-on yang dilanggan

Keputusan kecil (dicatat dalam PELAN 10.1 & 18):
- Semua teks JS dihantar dalam atribut `data-lang` pada `<body>` (bukan fail JSON berasingan) — satu permintaan kurang
- Halaman modul add-on vendor: `vendor/addons/{code}.php` (U6 boleh ubah)
- Store Peringkat 1: `store/index.php?school={slug}&page=...`; URL `/{slug}/` dalam B6 (siap — `.htaccess` + `pretty_store_urls`)
- Pilihan koperasi vendor disimpan sementara dalam `localStorage` + `Api.post('vendor/session/cooperative')`

Tertangguh:
- Tajuk halaman store ikut halaman (U5); `assets/lib/qrcode/` (U3b)

### U2 — UI Owner (24 Okt 2026)

Dibuat (`owner/` + `assets/js/owner/` satu fail JS setiap halaman):
- `index.php` papan pemuka — kad MRR / vendor aktif / koperasi / pesanan, carta bar SVG MRR 6 bulan, vendor ikut status, langganan hampir tamat
- `vendors.php` (carian, tapis status & pelan, penomboran) · `vendor.php?id=` (maklumat, langganan, koperasi, invois, nota dalaman, gantung dengan sebab / aktifkan)
- `cooperatives.php` — semua koperasi semua vendor, baca sahaja, tapis vendor/jenis/status
- `plans.php` CRUD (harga bulanan & tahunan, had — kosong = tanpa had, ciri-ciri, petunjuk jimat tahunan)
- `coop-pricing.php` — jadual peringkat `plan_coop_tiers` boleh sunting, "guna harga lalai", kalkulator harga
- `modules.php` CRUD (nama & keterangan 4 bahasa dalam tab, lencana terjemahan lengkap)
- `templates.php` — kad, pratonton (modal + buka store `?template=`), sunting harga; Template 1 dikunci percuma & aktif
- `subscriptions.php` (tab Langganan & Invois) · `invoice.php?id=` (baris, prorata, SST, bayaran; terbit / tanda dibayar / batal / cetak)
- `gateways.php` — suis driver untuk vendor (amaran jika ada vendor menggunakannya) + borang gateway owner (medan ikut driver, rahsia tidak dipapar)
- `translations.php` — jadual kunci × 4 bahasa boleh sunting, tapis kunci hilang / kumpulan, amaran `{placeholder}` tidak sama
- `grades.php`, `email-templates.php` (per jenis × bahasa, cip pemegang tempat, pratonton iframe sandbox), `announcements.php` (draf / terbit, semua / vendor terpilih),
  `audit-log.php`, `settings.php` (umum, SMTP + e-mel ujian, SST, trial, tempoh tangguh), `staff.php`, `profile.php`, `two-factor.php` (QR, kunci manual, kod pemulihan)
- `assets/js/mock/owner.js` — VENDOR01–VENDOR07 (semua status), 9 koperasi, 3 pelan + peringkat, 8 modul, 3 template, 7 invois, log audit
- `helpers.js` ditambah: borang (`formToObject`, `fillForm`, `bindForm`, `submitForm`, `validateForm`, `showFormErrors`), senarai
  (`bindListFilters`, `renderPagination`, `tableLoading`, `fillSelect`), `statusBadge(status, kumpulan)`, `activeBadge`, `createButton`, `createLink`,
  `createStack`, `renderBarChart` (SVG), `formatMonth`, `parseRM`, `senToInput`, `debounce`, `queryParam`, `pageUrl`, `showModal`/`hideModal`
- `mock.js` ditambah: `findById`, `nextId`, `now`, `requireFields`, `hasErrors`, `filterBy`
- `partials/page-title.php` (tajuk + kembali + butang tindakan, dipakai layout owner & vendor), `partials/stat-card.php`
- `assets/lib/qrcode/` — qrcode-generator 1.4.4 (MIT)
- `lang/*.php` — 338 kunci baharu (jumlah 493) dalam 4 bahasa
- `tools/check-ui.php` kini juga mengesan `t()`/`T()` dengan kunci yang tiada dalam `lang/ms.php`

Disahkan:
- `php tools/check-ui.php` → tiada inline CSS, 493 kunci sama dalam 4 bahasa, tiada kunci literal hilang
- Playwright: 20 halaman × 4 bahasa × 375px & 1366px — tiada ralat konsol/CSP, tiada skrol mendatar
  (lajur kunci dalam Terjemahan & kod tindakan dalam Log audit memang memaparkan kunci — itu data)
- Aliran: carian & tapis vendor, gantung (sebab wajib) & aktifkan, tambah pelan (kod pendua ditolak), peringkat harga + kalkulator,
  modul (tab bahasa dibuka bila medan wajib kosong), template percuma, tanda invois dibayar, gateway billplz (medan wajib),
  terjemahan (tapis hilang, amaran placeholder), templat e-mel (placeholder tidak dikenali ditolak, pratonton), pengumuman
  terpilih tanpa vendor ditolak, nyahaktif diri sendiri ditolak, kata laluan tidak sama, 2FA (QR + 8 kod pemulihan), tetapan

Keputusan kecil (dicatat dalam PELAN 18.6–18.7):
- Rahsia (kunci gateway, kata laluan SMTP) tidak pernah dipulangkan oleh API; kosong semasa simpan = kekal
- Owner tidak boleh mematikan 2FA, hanya pindah peranti / jana semula kod pemulihan
- Pengumuman yang sudah terbit tidak boleh disunting (boleh dipadam)
- `assets/js/vendor-dashboard.js` dipindah ke `assets/js/vendor/dashboard.js` (konsisten satu folder per panel)
- `.table-responsive { position: relative }` dalam app.css — elak skrol mendatar oleh `.visually-hidden` dalam jadual

Tertangguh:
- Sekatan peranan `owner_staff` (sembunyi menu harga/pelan/tetapan) — kawalan sebenar di B2/B3, UI menyusul
- Gambar pratonton template sebenar (`preview_image`) — bila template2/3 dibina

### U3a — UI Vendor bahagian 1 (25 Okt 2026)

Dibuat (`vendor/` + `assets/js/vendor/` satu fail JS setiap halaman; semua data ikut koperasi semasa):
- `index.php` papan pemuka — jualan hari ini, menunggu disediakan / diambil, stok rendah, carta jualan 7 hari, pengumuman platform, senarai stok rendah
- `cooperatives.php` (kad koperasi) · `cooperative-form.php` — maklumat sekolah (kod KPM `AAA9999`, jenis, negeri, daerah, alamat),
  slug dijana dari nama + semakan unik seluruh platform, logo (pratonton), template (yang belum dilanggan dimatikan),
  bahasa lalai, waktu operasi 7 hari, tunai/online (wajib satu), **amaran harga koperasi tambahan** & had pelan
- `classes.php` — kelas ikut darjah (tambah beberapa sekali gus, tukar nama, padam; pendua ditolak)
- `products.php` (carian, tapis kategori/darjah/status/stok rendah) · `categories.php` · `product-form.php`
  (harga & kos + untung, had beli, gambar, darjah sasaran, varian dengan beza harga, terjemahan en/zh/ta pilihan)
- `inventory.php` (stok masuk, pelarasan dengan sebab, stok minimum, amaran stok rendah) · `stock-movements.php`
- `stock-takes.php` · `stock-take.php` (kiraan, Enter ke baris seterusnya, perbezaan langsung, selesai → laras stok)
- `suppliers.php` · `purchase-orders.php` · `purchase-order.php` (draf boleh sunting → dipesan → terima separa/penuh → stok masuk automatik; batal; cetak)
- `assets/js/mock/vendor.js` ditulis semula: 2 koperasi VENDOR01 + 1 koperasi VENDOR02, 24 produk (varian & terjemahan), stok,
  pergerakan, 5 pembekal, 6 PO, stock take; setiap laluan menapis vendor sesi & mengesahkan koperasi
- `mock.js`: `Mock.persist()` — data mock kekal antara halaman dalam `sessionStorage` (owner & vendor)
- `vendor-layout.js`: `onCooperativeReady()`, `withCoop()`, `currentCooperative()`
- `helpers.js`: `bindImageInput`, `showImagePreview`, `slugify`, `createIconLink`; `bindListFilters` kini sokong checkbox; lencana status PO
- `partials/sub-nav.php` + `vendor/_layout/sub-nav.php` — Koperasi | Kelas · Produk | Kategori · Stok | Sejarah | Stock take · Pembekal | PO
- `config/malaysia.php` — 16 negeri/wilayah, jenis sekolah, hari
- `lang/*.php` — 231 kunci baharu (jumlah 724)

Disahkan:
- `php tools/check-ui.php` → tiada inline CSS, 724 kunci sama dalam 4 bahasa
- Playwright: 18 halaman × 4 bahasa × 375px & 1366px — tiada ralat konsol/CSP, tiada kunci mentah, tiada skrol mendatar
- **Ujian kebocoran**: VENDOR01 memanggil API dengan `cooperative_id` VENDOR02 / id produk, PO, pembekal VENDOR02 →
  semuanya ditolak; senarai koperasi & pembekal hanya milik sendiri; slug VENDOR02 dilaporkan "sudah digunakan" tanpa dedah pemilik
- Aliran: tukar koperasi mengubah data papan pemuka & produk; koperasi baharu (amaran RM 790 tahunan koperasi ke-3, slug diambil,
  kod KPM salah, tiada kaedah bayaran, fail bukan gambar ditolak); kelas pendua; padam kategori berproduk ditolak; produk dengan
  varian (harga akhir, payload sen); stok masuk & pelarasan (di bawah kuantiti ditahan ditolak); PO hantar → terima melebihi baki
  ditolak → separa → penuh (stok bertambah); stock take mula → kira → selesai

Keputusan kecil (dicatat dalam PELAN 18):
- Sub-navigasi pills untuk halaman berkaitan; menu sidebar vendor kekal 18 modul (PELAN 5.2)
- Pembekal dikongsi semua koperasi vendor; kategori, produk, stok, PO & stock take per koperasi (ikut skema 9.D)
- Pelarasan stok = masukkan kuantiti sebenar (bukan +/-) + sebab wajib
- Stock take: item belum dikira tidak dilaras; satu stock take terbuka setiap koperasi
- Halaman satu rekod kembali ke senarai jika koperasi ditukar (rekod milik koperasi lain)

Tertangguh:
- Muat naik sebenar (logo, gambar produk) — B4 (`Uploader`, `finfo`); Peringkat 1 guna data URL
- Kalendar cuti dalam waktu operasi (modul `calendar`) — U6

### U3b — UI Vendor bahagian 2 (25 Okt 2026)

Dibuat:
- `school-kits.php` — grid kit per darjah untuk tahun dipilih; "Salin dari tahun lepas" (draf, darjah sedia ada dilangkau, produk tidak aktif dibuang)
- `school-kit.php` — item (produk/varian atau "ibu bapa pilih saiz"), kuantiti, wajib/pilihan, nota, susunan naik/turun,
  jumlah semua & wajib, **pratonton ibu bapa** (semua siap ditanda + lencana "Item wajib sekolah"), draf / terbit / tarik balik / padam draf
- `orders.php` — pil status dengan kiraan, tapis (carian, status, darjah → kelas, kaedah bayaran, tarikh), pilih & tukar status pukal,
  pautan picking list & label ikut tapisan · `order.php` — item dikumpul ikut anak (diambil x/y), siapa ambil, kod 4 digit (wajib/tidak/dikunci),
  tukar status, batal (sebab wajib), sejarah status (garis masa), rekod serahan
- `picking-list.php` (ikut kelas → murid → barang + jumlah keseluruhan, satu kelas satu halaman cetak) · `bag-labels.php`
  (label A4 3 lajur: sekolah, nama anak, kelas, no. pendek besar, QR, "BAYAR TUNAI") — `assets/css/print-label.css`
- `pos/handover.php` — **skrin serah barang**: kotak carian besar (nama / no. pendek / imbas QR), tapis darjah & kelas, kad keputusan
  (no. pendek, nama murid & kelas, ibu bapa, barang, status bayaran), "Terima tunai" → baki, "Serah": siapa ambil (murid/ibu bapa/wakil + nama),
  ambil separa (tanda + kuantiti), kod 4 digit bila wajib (5 salah → dikunci), mesej berjaya + "Ibu bapa telah dimaklumkan"
- `customers.php` — ibu bapa & anak di koperasi ini (baca sahaja) + nota dalaman vendor
- `assets/js/mock/vendor-orders.js` — 15 ibu bapa (akaun global), 20 anak, 9 kit, ~45 pesanan, rekod serahan, tetapan cara ambil per koperasi
- `assets/js/vendor/order-common.js`; `partials/page-title.php` pautan kembali tidak dicetak; layout vendor terima `$extraCss`
- `lang/*.php` — 154 kunci baharu (jumlah 878)

Disahkan:
- `php tools/check-ui.php` → tiada inline CSS, 878 kunci sama dalam 4 bahasa
- Playwright: 12 halaman × 4 bahasa × 375px & 1366px — tiada ralat konsol/CSP, tiada kunci mentah, tiada skrol mendatar; U3a diuji semula
- **Ujian kebocoran**: pesanan, kit, carian serah & nota ibu bapa VENDOR02 ditolak; ibu bapa dengan anak di dua vendor hanya menampakkan
  anak di koperasi sendiri; `pickup_code` tidak pernah muncul dalam jawapan API
- **Serah barang papan kekunci sahaja**: fokus pada carian semasa dimuat → taip token QR + Enter → modal terbuka (fokus "Murid") →
  Tab → Enter → berjaya (fokus "Seterusnya") → Enter → kembali ke carian kosong. Tunai: Enter → jumlah kurang ditolak → baki dikira →
  terus ke serah; ambil separa → "Diambil sebahagian"; kod salah ×5 → dikunci → butang "Dikunci — panggil pengurus"
- Kit: salin tahun lepas (5 disalin, 1 dilangkau), cipta & terbit, susunan; pesanan: tapis, pukal, tukar status, batal; cetak (media print)

Keputusan kecil (dicatat dalam PELAN 18.9):
- `payment_status` berasingan dari `status` (pesanan tunai boleh "Sedia diambil" tetapi belum dibayar)
- Status `collected` / `partially_collected` hanya melalui skrin serah; panel pesanan tidak boleh menandanya
- Batal dari panel hanya untuk pesanan belum dibayar; dibayar → Pemulangan & refund (U3c)
- Kit: produk bervarian boleh dimasukkan tanpa varian → ibu bapa pilih saiz di store
- Data contoh pesanan: koperasi tanpa tunai (SJKC Chung Hwa) tidak menerima pesanan tunai; `Mock.storageVersion` → `v2`

Tertangguh:
- Notifikasi sebenar kepada ibu bapa (e-mel / WhatsApp) — B7/B9 & modul `whatsapp_sms`
- Imbas QR dengan kamera telefon (pengimbas USB sudah disokong) — dinilai semula di U4/U7
- Buka kunci pesanan oleh pengurus (selepas 5 cubaan) — B9

### U3c — UI Vendor bahagian 3 (25 Okt 2026)

Dibuat (`vendor/` + `assets/js/vendor/` satu fail JS setiap halaman):
- `payments.php` — gateway koperasi (hanya driver dibenarkan owner, medan ikut `config_fields`, rahsia tidak dipapar — kosong = kekal,
  mod sandbox/live dengan pengesahan, uji sambungan, URL webhook) + suis tunai/online · `payment-records.php` — rekod bayaran
  (kad jumlah online/tunai/dipulang/menunggu, tapis kaedah/status/tarikh)
- `refunds.php` — senarai + borang refund (cari no. pesanan / kod pendek, atau terus dari butiran pesanan `?order_id=`): kuantiti dipulang per item,
  amaun dicadang (boleh ubah, ≤ baki), cara (tunai / pindahan bank / gateway jika sokong refund API), sebab wajib, "masukkan semula ke stok"
- `promotions.php` — kupon & diskaun automatik (tanpa kod), % / RM tetap, belanja minimum, had guna, tempoh, status dikira (berjalan / dijadual / tamat / had habis)
- `staff.php` — kakitangan vendor: peranan (pentadbir / pengurus koperasi / juruwang), akses koperasi, PIN POS juruwang, had pelan, aktif/nyahaktif, pautan kata laluan
- `settings.php` — e-mel amaran (≤ 5), bahasa lalai, resit (tajuk, nota kaki, kertas 58/80, logo, cukai) + pratonton, cara ambil A–E (suis), had wajib kod,
  tempoh luput tunai, stok minimum lalai — disegerakkan dengan skrin serah barang
- `reports.php` — 8 laporan (harian, bulanan, tunai vs online, produk, untung ikut kategori, nilai stok, darjah, kelas), julat pantas, carta SVG,
  jumlah, **CSV** (BOM UTF-8), cetak
- `subscription.php` (pelan, kitaran, tempoh, penggunaan vs had dengan `<progress>`, koperasi + template + modul, anggaran pembaharuan, tukar pelan/kitaran
  dijadualkan) · `addon-store.php` (kad modul & template, anggaran prorata, langgan, henti/teruskan) · `invoices.php` (senarai, butiran, bayar)
- `activity-log.php` — siapa buat apa, tapis pengguna / koperasi semasa / tarikh
- `assets/js/mock/vendor-finance.js` — gateway, refund, promosi, 7 kakitangan (2 vendor), tetapan, pelan & langganan, modul dilanggan, invois, log aktiviti, 8 laporan
- `order.php` — butang "Refund baharu" + baris dibatal/dipulang; `vendor-orders.js` & `handover.js` — baki = qty − diambil − dibatal
- `helpers.js` — `createIconLinkText`, `downloadCsv`, lencana promosi / rekod bayaran / modul; `app.css` — `.app-progress`
- Sub-navigasi: Bayaran | Rekod bayaran · Langganan & bil | Kedai modul | Invois; menu add-on berkunci kini ke `addon-store.php?module=`
- `lang/*.php` — 328 kunci baharu (jumlah 1206)

Disahkan:
- `php tools/check-ui.php` → tiada inline CSS, 1206 kunci sama dalam 4 bahasa
- Playwright: 18 halaman (11 baharu + 7 U3a/U3b diuji semula) × 4 bahasa × 375px & 1366px — tiada ralat konsol/CSP, tiada kunci mentah, tiada skrol mendatar
- **Ujian kebocoran** (mock, 12 laluan): tetapan/rekod bayaran, refund (senarai, cari, cipta), promosi (sunting, padam), tetapan, laporan, kedai modul,
  invois (lihat, bayar), gateway & kakitangan VENDOR02 semuanya ditolak; e-mel kakitangan VENDOR02 → "sudah digunakan" tanpa dedah pemilik;
  log aktiviti & senarai kakitangan hanya milik sendiri; rahsia gateway tidak pernah dalam jawapan API
- Aliran UI: refund dari butiran pesanan (amaun automatik → disahkan → tersenarai), refund penuh → status Dipulangkan, barang belum diambil → tahanan
  stok dilepas, barang diambil + restock → baki stok naik; kod kupon pendua ditanda; menu add-on berkunci → anggaran prorata → langgan → menu dibuka
  + invois; bayar invois; tetapan cara ambil → skrin serah ikut; turun taraf melebihi had ditolak; CSV dimuat turun; log aktiviti merekod

Keputusan kecil (dicatat dalam PELAN 9.G & 18.10):
- Refund unit belum diambil = **batal** (`cancelled_qty`, tahanan dilepas); unit sudah diambil = **pulang** (`returned_qty`, restock pilihan). Jadual `refund_items` ditambah
- Modul/template dilanggan **aktif serta-merta**, invois prorata diterbitkan (bayar dalam 7 hari); henti modul = hujung tempoh, tiada refund
- Tukar pelan/kitaran berkuat kuasa pada tarikh pembaharuan; turun taraf ditolak jika penggunaan melebihi had baharu
- Satu gateway aktif setiap koperasi; kunci driver lain kekal tersimpan (tukar balik tanpa taip semula)
- CSV laporan dijana di pelayar dari jawapan API yang sama (tiada endpoint eksport berasingan)
- Promosi tanpa kod = diskaun automatik; promosi yang pernah digunakan tidak boleh dipadam

Tertangguh:
- Halaman modul add-on `vendor/addons/{code}.php` (butang "Buka" & menu) — U6
- Bayaran invois sebenar melalui gateway pemilik + webhook — B12; refund automatik melalui API gateway — B8/B11
- E-mel jemputan / pautan kata laluan kakitangan sebenar — B2; kawalan peranan (juruwang tidak nampak menu kewangan) — B2/U7
- Kaitan promosi dengan pesanan (`orders.promotion_id`, `used_count`) — B7/B10

### U4 — UI POS kaunter (25 Okt 2026)

Dibuat:
- `vendor/pos/index.php` — **skrin penuh** (layout baharu `vendor/_layout/pos-header.php` / `pos-footer.php`, tiada sidebar; tab "Jualan" | "Serah barang",
  pemilih koperasi, juruwang semasa, menu syif, butang skrin penuh, bahasa). Tiga langkah dalam satu halaman:
  1. **Log masuk PIN juruwang** — kakitangan aktif dengan `pin_set` & akses koperasi dari `vendor/staff/list` (U3c), papan kekunci PIN 3×4
     (sentuh) atau taip; 5 PIN salah → dikunci
  2. **Buka syif** — tunai permulaan laci
  3. **Jualan** — medan imbas sentiasa difokus, pil kategori, grid produk (kad butang besar: harga, "dari RM" untuk varian, baki stok / habis),
     modal varian (saiz + kuantiti), troli (+/−/taip kuantiti, buang, kosongkan), "Nama pembeli, kupon & diskaun" (kupon dari `vendor/promotions/list`,
     diskaun automatik terbaik, diskaun manual), cukai dari tetapan, **Tunai** (wang pantas, baki) / **QR online** (QR gateway, menunggu bayaran,
     batal), **resit** 58/80mm (tetapan resit `vendor/settings/get`), "Jualan syif ini" (cetak semula resit), **tutup syif** (jangkaan vs tunai sebenar,
     beza, nota, cetak laporan syif)
- `assets/js/vendor/pos-cart.js` — logik troli **disalin & diubah suai dari KedaiPOS** (`kabuto86/pos-system`: `makeLineId`, `calcUnitPrice`, `getTotals`,
  `suggestCashAmounts`) dalam sen; dipakai halaman & mock
- `assets/js/vendor/pos.js` — halaman; `assets/js/mock/vendor-pos.js` — sesi PIN, syif, katalog, checkout, status QR, resit
- `assets/css/pos.css` (susun atur skrin penuh, kad sentuh, papan kekunci PIN), `assets/css/receipt.css` (resit & cetak)
- Jualan POS disimpan sebagai pesanan `channel = 'pos'` → terus muncul dalam rekod bayaran, laporan, pergerakan stok (`pos_sale`), refund
- `order.php` — pesanan POS: "Jualan kaunter (POS)", "Pelanggan kaunter", tiada label beg; `refunds.js`, `payment-records.js` — nama walk-in;
  `vendor/orders/list` tidak menyenaraikan jualan POS; rekod bayaran tunai POS → nama juruwang; skrin serah barang ada butang "POS kaunter"
- `lang/*.php` — 116 kunci baharu (jumlah 1322)
- `DOC/screenshots/U4/` — 14 tangkap layar (1024 sentuh, 375, 1366 zh, cetakan resit & laporan syif)

Disahkan:
- `php tools/check-ui.php` → tiada inline CSS, 1322 kunci sama dalam 4 bahasa
- Playwright: POS × 4 bahasa × 375px / 1024px (sentuh) / 1366px — log masuk → syif → imbas → bayar tunai → resit: tiada ralat konsol/CSP,
  tiada kunci mentah, tiada skrol mendatar; halaman pesanan, refund, rekod bayaran, laporan, pergerakan stok, serah barang diuji semula
- **Pengimbas USB (papan kekunci sahaja):** fokus pada medan imbas selepas syif dibuka → taip `5*955200000007` + Enter (5 pemadam) → `AT-P2B12` + Enter
  → F2 → wang pantas / Enter → resit (fokus "Jualan baharu") → Enter → kembali ke medan imbas kosong. Sentuh kategori/produk → fokus kembali ke medan imbas
- **Skrin sentuh 1024px:** kad produk ≥ 6.5rem, butang bayar 3.5rem, papan kekunci PIN, troli lajur kanan; < 992px troli jadi panel + bar bawah
- Kupon JIMAT5 di bawah belanja minimum → amaran & bayaran disekat; tambah barang → -RM 5.00; stok melebihi baki ditolak; QR online → dibayar → resit;
  QR dibatalkan → troli kekal, tahanan stok dilepas; kunci → PIN semula → syif & troli kekal; tutup syif → beza dikira → juruwang dilog keluar
- **Ujian kebocoran** (mock, 15 laluan): sesi/katalog koperasi VENDOR02, PIN juruwang VENDOR02 di koperasi VENDOR01, juruwang tanpa akses koperasi,
  checkout tanpa PIN, syif & produk & kupon VENDOR02, resit/batal/senarai jualan VENDOR02 — semuanya ditolak; harga & jumlah dari pelayar diabaikan
  (pelayan mengira semula)

Keputusan kecil (dicatat dalam PELAN 9.G & 18.11):
- Jualan POS = `orders.channel = 'pos'` (tiada jadual berasingan); tidak dalam senarai "Pesanan online"; no. resit `POS-YYMMDD-001`, kod `P001`
- Tambah lajur `orders`: `promotion_discount, manual_discount, tax_rate, tax, pos_shift_id, cashier_id, amount_received, change_given`; `pos_shifts.note`
- Satu promosi setiap jualan (kupon, jika tidak automatik terbaik); cukai dikira **selepas** diskaun
- Tunai sentiasa dibenarkan di POS; QR online perlu gateway aktif. Online menahan stok sehingga dibayar
- Satu syif terbuka setiap juruwang setiap koperasi; "Kunci" tidak menutup syif; tutup syif ditolak jika ada QR belum selesai
- Peranti kaunter guna sesi panel vendor + PIN juruwang (`$_SESSION['pos_user_id'][cooperative_id]`); log aktiviti & pergerakan stok atas nama juruwang
- Kertas resit: lebar ikut tetapan (58/80mm), `@page { margin: 0 }`; saiz kertas sebenar dalam pemacu pencetak

Tertangguh:
- PIN sebenar (Argon2id), buka kunci PIN oleh pentadbir, webhook QR sebenar — B10/B8 (demo: PIN 1234, QR dibayar pada semakan ke-2)
- Refund tunai POS tidak ditolak dari jangkaan tunai laci — B10/B11
- Baris cukai dalam butiran pesanan (`order.php`) — U7 (cukai koperasi semasa 0%)
- Imbas QR/barcode dengan kamera telefon — U7 (pengimbas USB disokong); pemilih koperasi POS disembunyikan < 768px
- Sekatan peranan (juruwang hanya POS & serah barang) — B2/U7

### U5 — UI Store ibu bapa, Template 1 (25 Okt 2026)

Dibuat:
- `templates/template1/pages/` — 17 halaman manifest: `home` (tanpa sekolah: carian, sekolah terakhir, sekolah anak-anak; dengan sekolah: pintasan,
  pesanan sedia diambil, anak di sekolah ini + "Beli kit", berita, banner jualan murah, waktu operasi), `school-select` (nama/daerah + negeri),
  `register` (telefon MY, hubungan, bahasa, PDPA wajib → pengesahan e-mel), `login` (e-mel atau telefon), `forgot-password` (e-mel → token → kata laluan baharu),
  `children` (tambah/sunting: nama, sekolah, darjah ikut jenis sekolah, kelas dengan cadangan, tahun; naik darjah; buang), `catalog` (carian, kategori,
  darjah, pintasan darjah anak), `product` (varian, kuantiti, pilih anak), `kit` (pilih anak → semua item siap ditanda, nyahtanda + amaran wajib, kuantiti,
  pilih saiz, jumlah serta-merta, bar melekat "Masuk troli"/"Kemas kini troli"), `cart` (ikut anak, kit & ala carte bercampur, masalah stok/had ditanda),
  `checkout` (semak, siapa ambil murid/ibu bapa/wakil + nama, kod 4 digit bila wajib, tunai/online ikut koperasi, kupon), `order-success` (no. pendek besar,
  QR, arahan ambil, cetak slip), `orders` (tapis anak & tahun), `order-detail` (status, garis masa, item ikut anak + diambil, rekod serahan, bayar semula,
  batal, cetak slip / resit), `profile` (maklumat, bahasa, kata laluan, pengesahan e-mel, padam akaun PDPA), `news`, `clearance` (modul aktif sahaja)
- `templates/template1/partials/` — header (akaun / log masuk, menu modul `data-module`), bottom-nav (menu ringkas tanpa sekolah), `gateway-modal.php`;
  `layout.php` tajuk ikut halaman; `css/template.css`, `css/print-slip.css` (slip 2 lajur A4, resit)
- `assets/js/store/` — `common.js`, `layout.js` + satu fail setiap halaman (17); `templates/template1/js/layout.js` dipindah ke sini (template paparan sahaja)
- `assets/js/mock/store.js` ditulis semula — sesi ibu bapa, troli, token, berita & jualan murah contoh; 30 laluan `store/*` (PELAN 18.12)
- `vendor-orders.js`: ibu bapa & anak kini disimpan (`Mock.persist`), anak ada `school_year`, `is_active`, `vendor_id`; `Mock.storageVersion` → `v3`
- `core/View.php` `storeUrl($slug, $page, $params)` (tanpa slug); `store/index.php` halaman tanpa sekolah; `app.css` `.btn-outline-brand`, `.border-brand`
- `lang/*.php` — 250 kunci baharu (jumlah 1572)
- `DOC/screenshots/U5/` — 21 tangkap layar (375 / 1366, zh/en/ta, cetakan slip & resit)

Disahkan:
- `php tools/check-ui.php` → tiada inline CSS, 1572 kunci sama dalam 4 bahasa
- Playwright: 17 halaman × 4 bahasa × 375px & 1366px (log masuk) — tiada ralat konsol/CSP, tiada kunci mentah, tiada skrol mendatar
- **Aliran penuh 375px:** log masuk dengan telefon → kit Aisyah (9 item, 8 ditanda — satu habis stok) → nyahtanda item wajib (amaran, jumlah turun) →
  +1 kuantiti → masuk troli tanpa saiz ditolak → pilih saiz → troli → produk untuk Adam → troli 2 kumpulan anak → checkout: kupon JIMAT5 (-RM 5),
  wakil tanpa nama ditolak, kod 4 digit (> RM100) → tunai → no. pendek + QR + 2 slip → cetakan slip A4 & resit → **pesanan muncul di panel vendor**
- Online: `pending_payment` → simulasi gateway → `paid`; SJKC Chung Hwa hanya online
- **Ujian kebocoran** (mock): tetamu → `auth_required`; pesanan ibu bapa lain / pesanan sendiri melalui slug sekolah lain / batal pesanan orang lain /
  anak ibu bapa lain dalam troli & kit / sunting anak orang lain / produk koperasi lain & vendor lain → semuanya ditolak; `customer_id` dalam parameter
  diabaikan; `pickup_code` tidak pernah dipulangkan; harga dari pelayar diabaikan; `expected_total` berbeza → minta semak semula
- Akaun: e-mel pendua & tanpa PDPA ditolak, checkout sebelum sahkan e-mel ditolak, token reset sekali guna, 5 log masuk gagal → dikunci 15 minit
- Modul: berita hanya SK Taman Melati, jualan murah hanya SMK Bukit Indah (harga jualan murah terus dalam troli)

Keputusan kecil (dicatat dalam PELAN 10.5, 18.5 & 18.12):
- Logik store dalam `assets/js/store/` dikongsi semua template; template mengekalkan `id`/`data-*` dan menukar rupa melalui CSS
- Akaun ibu bapa global: `profile` & `children` boleh dibuka tanpa sekolah; troli & pesanan per koperasi (slug)
- Kod 4 digit **ditetapkan ibu bapa** semasa checkout (bila koperasi mewajibkan) kerana kod di-hash dan tidak boleh dipapar semula
- Kit yang sama untuk anak yang sama → diganti dalam troli (`cart_items.school_kit_item_id`); item habis stok tidak ditanda
- Pesanan store menahan stok (`reserve`) sebaik dibuat; batal oleh ibu bapa hanya `pending_payment` / `unpaid_cash`
- Promosi store sama seperti POS (`posTotals`); `used_count` naik semasa pesanan dibuat
- Checkout perlu e-mel disahkan; log masuk dan troli dibenarkan sebelum itu
- Padam akaun ditolak jika ada pesanan aktif; rekod pesanan kekal tanpa telefon/e-mel
- Semua borang store dihalang daripada hantar asal pelayar (elak kata laluan dalam URL jika skrip lambat dimuat)

Tertangguh:
- E-mel sebenar (pengesahan, reset, pesanan baharu kepada vendor & ibu bapa, slip) — B7; Peringkat 1 memapar pautan demo
- Gateway sebenar (redirect + webhook) — B8; luput automatik pesanan tunai/online (cron) — B7
- Laporan "forgot" mendedahkan `demo_token` hanya untuk e-mel berdaftar — Peringkat 1 sahaja, dibuang dalam B2
- Gambar produk sebenar (ikon gantian sekarang); muat turun resit PDF (cetak sahaja sekarang) — B6/B9
- Pilih slot ambil, banner koperasi tutup — U6 (modul `school_opening`, `calendar`)
- Navbar desktop dalam bahasa Tamil membalut dua baris (label panjang) — U7

### U6 — UI modul add-on (25 Okt 2026)

Dibuat:
- `vendor/_layout/module-gate.php` — halaman modul set `$moduleCode`; layout memapar kad **"Langgan modul ini"** (pautan `addon-store.php?module=`,
  nama & keterangan modul, koperasi semasa) bila koperasi semasa belum melanggan, dan membungkus kandungan dalam `#module-content`.
  `assets/js/vendor/addon-common.js` — `onModuleReady(fn)`, `moduleResponse(res)`, `dateRangeText`, `localDay`; butang tindakan disembunyikan semasa berkunci
- `vendor/addons/news.php` — senarai (cari, tapis Diterbitkan / Dijadualkan / Draf), borang pos (tajuk, kandungan, gambar, tarikh terbit / jadual, terbit),
  **hantar e-mel** (bilangan penerima dipapar; sekali sahaja, berita berjadual → semasa terbit), pratonton
- `vendor/addons/clearance.php` + `clearance-sale.php?id=` — jualan (nama, tempoh, aktif, status dikira) & item (produk, saiz atau "semua saiz",
  harga khas < harga biasa, had / pesanan, baki stok, % jimat); `vendor/_layout/addons/clearance-sale-modal.php`, `js/vendor/addons/clearance-form.js`
- `vendor/addons/calendar.php` — kalendar bulanan (Isnin–Ahad, bulan sebelum/berikut/ini), acara berwarna ikut jenis (cuti sekolah, koperasi tutup,
  pembukaan sekolah, lain-lain), hari "tutup tempahan" berlatar merah, klik tarikh → tambah acara, senarai acara bulan ini, status "hari ini dibuka / ditutup";
  375px: bar warna + senarai di bawah
- `vendor/addons/school_opening.php` — musim pre-order (tahun, buka, tarikh tutup tempahan, ambil bermula, nota) + slot ambil (tarikh, masa,
  darjah — beberapa darjah = satu slot setiap darjah, kapasiti, ditempah dengan `<progress>`), slot bertempah tidak boleh dipadam
- `vendor/addons/whatsapp_sms.php` — **UI sahaja**: hidup/mati, saluran WhatsApp/SMS, pembekal, no. penghantar, kunci API (tidak dipapar), templat 3 acara
  (sedia diambil, telah diambil, peringatan tunai) dengan cip pemegang tempat + pratonton, mesej ujian, log mesej (no. bertopeng, status, cubaan)
- `vendor/addons/advanced_report.php`, `mobile_app.php`, `membership.php` — halaman sementara (gerbang langganan + "sedang dibina") supaya menu tidak 404
- `assets/js/mock/vendor-addons.js` — `MockAddons` dikongsi panel vendor & store (berita, jualan murah & item **dipindah dari `store.js`**, kalendar, musim,
  slot, tetapan & log mesej); 20 laluan `vendor/{news,clearance,calendar,school-opening,messaging}/*` dengan `addonRequire()` (koperasi + modul)
- Store (template1): `partials/notices.php` — banner **"Koperasi tutup hari ini"** / "akan ditutup" (14 hari) & banner **pre-order** (utama & kit);
  checkout: **pilih slot ambil** (ikut darjah anak dalam troli, slot penuh dimatikan, semua penuh → hubungi koperasi), amaran + butang dimatikan bila
  tempahan ditutup; slot dipapar di `order-success`, `order-detail` & panel vendor `order.php`; gambar berita di halaman Berita
- `store.js`: `store/school.notices`, quote `pickup_slots / slot_required / orders_blocked`, `place` sahkan slot & sekat hari tutup, batal → lepas slot;
  `vendor-orders.js`: batal dari panel → lepas slot
- Data contoh: SK Taman Melati kini melanggan `calendar` & `whatsapp_sms` juga (`clearance` sengaja belum — uji kad langgan); `Mock.storageVersion` → `v4`
- `app.css` — kad berkunci, kalendar (`.app-calendar`, `.app-cal-*`); `helpers.js` — warna lencana berita/jualan/musim/mesej
- `lang/*.php` — 195 kunci baharu (jumlah 1767)
- `DOC/screenshots/U6/` — 12 tangkap layar

Disahkan:
- `php tools/check-ui.php` → tiada inline CSS, 1767 kunci sama dalam 4 bahasa
- Playwright: 18 halaman (9 modul + pesanan, aktiviti, kedai modul, langganan, serah barang, store utama/berita/kit/checkout/butiran) × 4 bahasa × 375px & 1366px —
  tiada ralat konsol/CSP, tiada kunci mentah, tiada skrol mendatar
- **Aliran (76 semakan lulus):** clearance berkunci (butang disembunyi, API `module_locked`) → kad "Langgan modul ini" → kedai modul → langgan → halaman dibuka
  → tambah item bervarian (harga ≥ biasa ditolak, pendua ditolak) → **store: item di Jualan murah, harga RM 1.00 dalam troli**; berita + e-mel (12 ibu bapa)
  → muncul di store; acara "koperasi tutup" hari ini → banner store + checkout dimatikan + `place` ditolak → padam → checkout dengan **slot** (D4 penuh dimatikan,
  darjah lain tidak dipapar; API slot penuh / slot VENDOR02 / tanpa slot ditolak) → pesanan → slot di halaman berjaya & panel vendor, `booked` naik →
  batal → `booked` turun; slot berbilang darjah, kapasiti < ditempah, padam slot bertempah, slot sebelum tarikh ambil, musim pendua — ditolak;
  templat dengan `{nama}` ditolak; kunci API tidak dipulangkan; mesej ujian dalam log (bertopeng)
- **Ujian kebocoran** (mock, 18 laluan): berita / jualan / item / kalendar / musim / slot / log mesej VENDOR02 (melalui `cooperative_id` VENDOR02 atau id
  rekod VENDOR02 dengan koperasi sendiri) semuanya ditolak; koperasi sendiri tanpa langganan → `module_locked`; `vendor_id` dalam input diabaikan;
  kalendar & log mesej hanya milik sendiri; acara kalendar VENDOR02 tidak dipapar di store (modul tidak aktif)

Keputusan kecil (dicatat dalam PELAN 9.H, 10.1, 18.5, 18.12 & 18.13):
- Fail halaman modul = kod modul (`school_opening.php`, `whatsapp_sms.php`) ikut PELAN 10.1 `{code}.php`; JS sama nama dalam `js/vendor/addons/`
- Data modul dalam satu mock `vendor-addons.js` dikongsi panel & store (bukan dalam `store.js`) — panel vendor tidak memuat mock store
- Slot ambil per **pesanan** (satu pesanan = satu tempat, walaupun beberapa anak); wajib bila musim dibuka & ada slot; tiada musim → checkout biasa
- "Tutup tempahan" hanya menyekat pesanan **online** pada hari itu; POS kaunter tidak disekat (vendor sendiri yang buka kaunter)
- Banner tutup dipapar 14 hari sebelum; banner pre-order hanya di utama & kit (checkout sudah memapar slot)
- E-mel berita dihantar sekali sahaja (`email_sent_at`), menyunting tidak menghantar semula
- Jadual baharu `messaging_settings` (tetapan WhatsApp/SMS per koperasi); `notification_queue` + `cooperative_id, event`; `orders.pickup_slot_id`;
  `news_posts.email_sent_at, email_recipients` — jumlah 69 jadual

Tertangguh:
- Penghantaran WhatsApp/SMS sebenar, mesej automatik "sedia diambil / telah diambil" dari panel & skrin serah — B13 (UI tetapan sudah siap)
- E-mel berita sebenar melalui `email_queue` + cron; muat naik gambar berita sebenar (`Uploader`) — B13
- Penapis pesanan / picking list ikut slot ambil; slot dalam label beg — U7/B13
- Banner "koperasi tutup" dalam POS kaunter (amaran sahaja) — U7
- Halaman sebenar `advanced_report`, `mobile_app` (F1–F4), `membership` (kemudian)

### U7 — Semakan UI menyeluruh (25 Okt 2026)

Disemak:
- **Grep / `tools/check-ui.php`** (kini lebih ketat): tiada `style=` / `<style>`, **tiada CDN** (skrip/gaya dari luar), **tiada teks paparan
  ditulis terus** dalam HTML fail `.php` (teks & atribut `placeholder/title/aria-label/alt`; senarai dibenarkan: `RM`, `58 mm`, `TLS`, `Enter` …),
  kunci literal `t()`/`T()` wujud, kunci 4 bahasa sama. Teks dalam JS diimbas dengan skrip berasingan — hanya nama contoh pratonton templat
  WhatsApp (data). Dua pemegang tempat Melayu ditukar ke `t()` (`settings.test_email_placeholder`, `class.names_placeholder`)
- **Kunci bahasa:** 1773 kunci sama (6 kunci baharu) dalam `ms/en/zh/ta`
- **Playwright** (`tools/ui-audit.js`): 83 halaman (21 owner, 45 vendor termasuk modul & POS, 17 store) × 4 bahasa × 375px & 1366px = 664 paparan —
  tiada ralat konsol/CSP, tiada skrol mendatar, tiada `NaN/undefined/Invalid Date`, tiada medan tanpa label, butang tanpa nama atau gambar
  tanpa `alt`. Baki yang dijangka: kunci bahasa dalam Log audit & Terjemahan (memang data), kotak semak hiasan `aria-hidden` dalam kit sekolah
- **Tangkap layar:** `DOC/screenshots/U7/{ms,zh}-{375,1366}/` — 332 JPEG (setiap halaman) + 4 tangkap layar ciri baharu
- **Aksesibiliti** (axe-core WCAG 2 A/AA pada 83 halaman, ms 1366px + skrip sendiri pada semua paparan): setiap medan berlabel, butang ikon
  bernama, gambar ada `alt`. Dibaiki: kontras tajuk seksyen sidebar (4.23 → ≥ 4.5), pautan navbar store (2.76 → ≥ 4.5; aktif bergaris bawah),
  butang bahasa di halaman log masuk, menu modul berkunci (3.71 → ≥ 4.5), butang garis luar kelabu/merah di atas latar panel,
  teks kecil pilihan juruwang POS bila dipilih, carta SVG kini ada `<title>` ringkasan.
  **Papan kekunci:** pautan "Langkau ke kandungan" (`.visually-hidden-focusable`, Tab pertama) di semua layout; `.btn-brand` tiada cincin fokus
  (pemboleh ubah `--bs-btn-focus-shadow-rgb` hilang) — dibaiki; Tab melalui 12 elemen pertama 5 halaman — semua ada cincin fokus. Selepas pembaikan: **axe — 0 pelanggaran** pada 83 halaman

Pepijat dijumpai & dibaiki:
- Picking list (Tamil, 375px) menyebabkan skrol mendatar seluruh halaman → jadual dibungkus `.table-responsive`
- Tarikh pesanan contoh & pergerakan stok tidak sah (`2026-10-10T8:10` — jam tanpa sifar) → "—" dalam butiran pesanan; `Mock.storageVersion` → `v5`

Perkara tertangguh bertanda U7 yang diselesaikan:
- **Baris cukai** dalam butiran pesanan vendor (`order.php`) — dipapar bila `tax > 0` (`Cukai (6%)`); `vendor/orders/get` kini memulangkan `tax_rate, tax`
- **Penapis slot ambil** di senarai pesanan, picking list & label beg (disembunyikan bila koperasi tiada modul `school_opening` / tiada slot);
  slot dicetak pada label beg & dipapar di senarai pesanan; pautan picking/label membawa `pickup_slot_id`. Laluan baharu `vendor/orders/slot-options`
- **Banner "koperasi tutup"** dalam POS kaunter (amaran sahaja, di atas katalog) — `vendor/pos/session.closed_today`
- **Menu ikut peranan** — `core/PanelRole.php` + `'roles'` dalam menu owner & vendor: juruwang hanya Serah barang & POS (tiada modul add-on);
  pengurus koperasi tanpa bayaran/kakitangan/tetapan/langganan; `owner_staff` hanya papan pemuka, vendor, koperasi, langganan, log audit.
  Demo Peringkat 1: cookie `mock_role` (hanya bila `use_mock`)
- **Navbar store Tamil** tidak lagi membalut dua baris (992–1399px: ikon disembunyi, pautan dirapatkan, `nowrap`)
- **Imbas QR dengan kamera telefon** — dinilai: `BarcodeDetector` tiada di Safari iOS & Firefox; tiada pustaka tanpa CDN yang ringan.
  Keputusan: web kekal pengimbas USB/papan kekunci; imbasan kamera dalam app Flutter (F1)

Ujian ciri (18 semakan lulus): menu 3 peranan vendor + peranan tidak sah → lalai; pesanan store dengan slot → penapis slot menapis 1 pesanan,
pautan picking membawa slot, label beg memapar slot, picking list ikut slot; koperasi tanpa modul → penapis tersembunyi; kadar cukai 6% →
pesanan baharu memapar baris cukai, pesanan lama tiada; acara tutup hari ini → banner POS; **kebocoran**: `slot-options`, `pos/session`,
`orders/list` (+`vendor_id` dalam input), `orders/labels` dengan koperasi VENDOR02 → semua ditolak

Dokumen:
- **PELAN 18.14 — indeks 214 laluan API** (kaedah, skop yang mesti disahkan pelayan, session B, fail mock) — spesifikasi backend
- PELAN 4 — jadual menu ikut peranan; 18.9 (OrderSummary `pickup_slot`, OrderDetail `tax_rate/tax`, `slot-options`, label `pickup_slot`);
  18.11 (`closed_today`)
- Butang "Nyahterbit" kit: `btn-outline-warning` (kontras 1.63) → `btn-outline-secondary`
- `tools/ui-audit.js` — skrip semakan boleh diguna semula (tangkap layar dengan `shots`); `config/app.php` `asset_version` → `2`

### Perkara UI tertangguh (U7) — senarai lengkap untuk Peringkat 2

| Perkara | Session |
|---|---|
| ~~Sekatan halaman & API ikut peranan; halaman tidak dibenarkan → halaman pertama dibenarkan~~ | ✅ B2 |
| ~~Log masuk sebenar, sesi, 2FA, throttle; buang `demo_token` "lupa kata laluan" & cookie `mock_role`~~ | ✅ B2 |
| ~~E-mel jemputan / reset kata laluan kakitangan sebenar~~ (masuk `email_queue`; penghantaran SMTP oleh cron B7) | ✅ B2 |
| ~~Owner `owner_staff`: sekatan harga/pelan/tetapan di pelayan~~ — halaman B2, API owner B3 | ✅ B3 |
| Muat naik sebenar (logo, gambar produk, gambar berita) — `Uploader` + `finfo`; Peringkat 1 guna data URL / ikon gantian | B4/B13 |
| Gambar pratonton template sebenar (`preview_image`) | bila template2/3 dibina |
| E-mel sebenar (pengesahan, reset, pesanan baharu, slip, invois) melalui `email_queue` + cron | B7 |
| Luput automatik pesanan tunai/online (cron) | B7 |
| ~~Kaitan promosi dengan pesanan (`orders.promotion_id`, `used_count`)~~ (store B7, POS & panel promosi B10) | ✅ B10 |
| Gateway sebenar (redirect + webhook), QR POS sebenar | B8 |
| ~~Buka kunci pesanan oleh pengurus (selepas 5 cubaan kod)~~ (`vendor/orders/unlock-code`) | ✅ B9 |
| Muat turun resit / slip PDF — B9 memutuskan cetak terus dari pelayar (tiada PDF pelayan) | B9 (ditolak) |
| ~~PIN Argon2id, buka kunci PIN oleh pentadbir~~ (PIN baharu di Kakitangan mengosongkan kiraan) | ✅ B10 |
| Refund tunai POS ditolak dari jangkaan tunai laci; refund automatik melalui API gateway | B11 |
| Bayaran invois vendor melalui gateway pemilik + webhook | B12 |
| Penghantaran WhatsApp/SMS sebenar; mesej automatik "sedia diambil / telah diambil" | B13 |
| E-mel berita sebenar | B13 |
| ~~URL cantik store `/{slug}/` (.htaccess); pratonton `?template=` hadkan kepada owner~~ — owner / vendor pemilik | ✅ B6 |
| Halaman `advanced_report`, `membership` (sementara "sedang dibina"); `mobile_app` | kemudian / F1–F4 |
| Imbas QR/barcode dengan kamera | F1 (app) |
| Pemilih koperasi POS disembunyikan < 768px (tukar koperasi dari panel) | kekal — reka bentuk |

### B1 — Skema, seed & core (26 Okt 2026)

Dibuat:
- `database/create-database.sql` — `koperasi_saas` (utf8mb4_unicode_ci) + pengguna `koperasi_user@localhost` dengan akses **`koperasi_saas.*` sahaja**
  (tukar `GANTI_KATA_LALUAN_KUAT`; tidak menyentuh `tuisyen` / `pos_saas`)
- `database/schema.sql` — **75 jadual** (69 PELAN 9 + `refund_items` + 5 jadual yang kontrak UI perlukan — PELAN 9 J), InnoDB, FK,
  UNIQUE & indeks bermula `vendor_id` (PELAN 8.6). Baris pertama `SET NAMES utf8mb4`, UTF-8 tanpa BOM
- `database/seed.sql` — 4 bahasa (ms lalai), 13 darjah, owner + owner_staff, tetapan platform, 3 pelan + 4 peringkat koperasi, 8 modul (+ keterangan
  dalam `translations`), 3 template, 5 gateway; **VENDOR01** (SK Taman Melati) & **VENDOR02** (SJKC Chung Hwa, SMK Seri Indah), langganan + item,
  modul dilanggan, 5 kakitangan (PIN 1234), tetapan koperasi lengkap, kelas, kategori, 11 produk (6 varian), stok + pergerakan, pembekal, 4 kit 2027,
  2 ibu bapa (Siti: anak di Taman Melati **dan** Chung Hwa — dua vendor; Rajesh: Seri Indah), berita/kalendar/jualan murah contoh.
  Nama Cina & Tamil dalam `product_translations`, resit Cina dalam `cooperative_settings`. Akaun demo di kepala fail (kata laluan `Demo@12345`)
- `config/database.example.php` (+ `config/database.php` tempatan, .gitignore) · `config/app.php`: `debug`, `password_options` (Argon2id 19456/2/1), `secret_key_file`
- `core/Database.php` (PDO utf8mb4 + `SET NAMES` + zon `+08:00`, `EMULATE_PREPARES` off, `ERRMODE_EXCEPTION`, fetchOne/fetchAll/fetchValue/execute/insert, transaksi) ·
  `Request.php` · `Response.php` · `Validator.php` · `Csrf.php` · `Crypto.php` (sodium secretbox) · `SecurityHeaders.php` (+ HSTS bila HTTPS) ·
  `bootstrap.php` (autoload `core/` & `jobs/*/`, pengendali ralat JSON untuk `api/`)
- `jobs/Cooperative/ValidateCooperativeJob.php` — `new ValidateCooperativeJob($vendorIdDariSesi, $cooperativeId)` → `run()` = baris koperasi atau `null`
- `tools/generate-secret.php` (jana `config/secret.key`), `tools/verify-b1.php` (semakan B1)
- `.htaccess` "Require all denied" dalam `config/ core/ database/ jobs/ lang/ tools/`; `uploads/.htaccess` halang PHP
- 8 kunci bahasa baharu (`error.forbidden`, `error.method_not_allowed`, `error.csrf`, `error.server`, `error.max_length`, `error.min_length`,
  `error.date`, `error.integer`) dalam 4 bahasa — jumlah 1781

Disahkan (MariaDB 10.11, PHP 8.3 — `php tools/verify-b1.php` → **42 lulus, 0 gagal**):
- Import dari kosong dengan `mysql --default-character-set=utf8mb4` sebagai `koperasi_user`; pengguna itu hanya nampak `koperasi_saas`
- Sambungan PDO: client/connection/results `utf8mb4`, collation `utf8mb4_unicode_ci`; 75 jadual semua InnoDB + utf8mb4_unicode_ci
- **HEX nama Cina melalui PDO** `练习簿（80页）` = `E7BB83E4B9A0E7B0BF…` (sama dengan `bin2hex` PHP); Tamil dibaca sama; tulis/baca Cina + Tamil + emoji 📚
- **UNIQUE barcode**: `9555000000017` wujud di koperasi 1 & 2; pendua dalam koperasi sama → 1062; koperasi lain → dibenarkan
- **Kebocoran**: `ValidateCooperativeJob` VENDOR02→koperasi 1, VENDOR01→koperasi 2/3, id tiada, vendor 0, `'1 OR 1=1'` → semua `null`;
  anak ibu bapa B tidak dijumpai dengan `customer_id` ibu bapa A; `vendor_id` anak & produk sepadan dengan koperasi
- Argon2id `$argon2id$v=19$m=19456,t=2,p=1$…`, `password_needs_rehash` = false; Crypto: buka semula, nonce rawak, teks diubah → null
- Halaman sedia ada (owner, vendor, store) masih 200 dengan `bootstrap.php` baharu; ralat dalam `api/` → JSON `{ ok:false, error: "Ralat pelayan…" }` HTTP 500
- `php tools/check-ui.php` → OK, 1781 kunci sama dalam 4 bahasa

Keputusan kecil (dicatat dalam PELAN 8.7, 9 J, 10.1, 10.2):
- Seed ikut PELAN 8.7 (bukan susunan mock): VENDOR02 memiliki Chung Hwa & **SMK Seri Indah**; mock Peringkat 1 tidak diubah
- `variant_id = 0` = tiada varian dalam stok/pesanan/troli (PK tidak boleh NULL); kit `variant_id NULL` = ibu bapa pilih
- `ValidateCooperativeJob` hanya menyemak pemilikan; status koperasi dipulangkan dan pemanggil memutuskan (laporan koperasi `closed` masih boleh dibaca)
- POST dari `api.js` (B2+) dihantar sebagai JSON; CSRF melalui header `X-CSRF-Token` (`Csrf::verifyRequest()`), kod 419 bila tidak sah
- Keterangan modul dalam jadual `translations`; nama modul & darjah kekal dalam `lang/*.php`
- `api.js` belum ditukar — tiada laluan backend dalam B1 (`use_mock` kekal `true`)

Tertangguh:
- `api/v1/` belum wujud — bermula B2 (auth). `config/malaysia.php` kekal fail (tidak dipindah ke DB)
- Kata laluan demo & `config/database.php` tempatan mesti ditukar di pelayan sebenar

### B2 — Auth owner, vendor, ibu bapa + 2FA + throttle (26 Okt 2026)

Dibuat:
- **Core (13):** `OwnerAuth` · `VendorAuth` · `CustomerAuth` (sesi `KOPOWNER` / `KOPVENDOR` / `KOPSTORE`, pengguna disemak semula dari DB
  setiap permintaan) · `Session` (cookie HttpOnly/SameSite=Lax/Secure, idle, `regenerate`) · `Totp` (RFC 6238) · `RateLimit` (+ `ThrottleJob`) ·
  `Password` (Argon2id + hash palsu untuk masa sama) · `ValidationException` (ralat job → `{ ok:false, error, errors }`) · `PhoneNumber` ·
  `IsoDate` · `Pagination`. `PanelRole` kini dari sesi (`canOpen`, `firstPage`, `isBillingMenu`); `Request::requireMethod`;
  `View::absoluteUrl` (+ `config app_url`); `Lang::getFor` (e-mel ikut bahasa penerima)
- **Job (39):** `jobs/Auth/` OwnerLogin, VendorLogin, CustomerLogin, CustomerRegister, VerifyEmail, SendVerificationEmail, ForgotPassword,
  ResetPassword, Throttle, CheckSubscription, SetupTotp, VerifyTotp, RegenerateRecoveryCodes, PosPinLogin, Load{Owner,Vendor,Customer}Session,
  LoadVendorPending + pembantu `LoginLockout`, `AuthToken`, `RecoveryCodes` · `jobs/Owner/` profil, kata laluan, kakitangan (senarai/simpan/aktif/
  reset 2FA), `WriteAuditLogJob` · `jobs/Vendor/` kakitangan (senarai + had pelan, simpan + koperasi + PIN, aktif, hantar reset),
  `WriteActivityLogJob` · `jobs/Customer/` profil, kata laluan, padam akaun (PDPA), `GetStoreSessionJob` · `jobs/Cooperative/ListCooperativesJob`
  (bacaan; pengurus/juruwang hanya koperasi yang diberi) · `jobs/Notification/QueueEmailJob`
- **API (38 fail, tiada SQL):** `owner/auth/{login,verify-2fa,logout,forgot,reset}`, `owner/profile/*`, `owner/staff/*`, `owner/two-factor/*` ·
  `vendor/auth/{login,verify-2fa,logout,forgot,reset}`, `vendor/session/cooperative`, `vendor/cooperatives/list`, `vendor/staff/*` ·
  `store/auth/*`, `store/profile/*`, `store/session`
- **Halaman:** `owner|vendor/forgot-password.php` & `reset-password.php` (`partials/password-page.php` + `assets/js/password-page.js`, juga untuk
  jemputan kakitangan). `partials/login-form.php` + `login.js`: langkah 2FA untuk owner & vendor, **sediakan 2FA semasa log masuk pertama**
  (QR + kunci manual) → kod pemulihan dipapar sekali; pautan "Lupa kata laluan"; nota demo dibuang. Store: kotak "Ingat saya" (30 hari),
  pautan `?verify_token=` dari e-mel disahkan oleh `layout.js`, butang/pautan `demo_token` dibuang
- **Layout:** `owner|vendor/_layout/header.php` & `pos-header.php` → `requirePage($activeMenu)`; log keluar = butang POST `data-panel-logout`;
  `partials/head.php` → `<body data-csrf data-login-url>`; `store/index.php` mula sesi ibu bapa
- **`api.js`:** `Api.livePrefixes` → `fetch()` sebenar (GET query, POST JSON + `X-CSRF-Token`, 401 panel → halaman log masuk); laluan lain
  masih mock. `mock.js`: `Mock.onLive` & `Mock.beforeFirst` (jambatan peralihan). Mock laluan yang sudah sebenar dibuang
  (`owner.js`, `vendor.js`, `vendor-finance.js`, `store.js`); `Mock.storageVersion` → v6; `asset_version` → 3
- `database/migrations/001-auth-tokens.sql` (+ `schema.sql`): `platform_user_tokens`, `totp_last_step` (owner & vendor) — **76 jadual**
- `tools/verify-b2.php` (94 semakan HTTP sebenar), `tools/dev-totp.php` (kod 2FA ujian), `tools/ui-audit.js` (log masuk sebenar; senarai halaman
  dari sistem fail — `panelpages.txt` yang hilang tidak diperlukan lagi; `BASE_URL`), `verify-b1.php` → 76 jadual
- 17 kunci bahasa baharu × 4 (ralat 401/429, kunci, vendor digantung, 2FA, e-mel `verify_email` / `reset_password` / `staff_invite`);
  4 kunci demo dibuang; `staff_v.pin_help` 3 → 5 cubaan; `store.auth.demo_login` → akaun seed. Jumlah 1794
- Pembetulan kecil: atribut `pattern` telefon di `register` / `profile` store tidak sah dalam Chrome (bendera `v`) → `[\s\-]`

Disahkan (MariaDB 10.11, PHP 8.3, pemasangan baharu schema + seed — `php tools/verify-b2.php` → **94 lulus, 0 gagal**; `verify-b1` 42/42;
`check-ui` OK; `ui-audit` lang=ms 170 halaman, tiada ralat konsol baharu):
- Hash `$argon2id$v=19$m=19456,t=2,p=1$`, `needs_rehash` false; PIN Argon2id; token & kod pemulihan hanya hash SHA-256; `totp_secret` disulitkan
- Mesej sama untuk akaun tiada / kata laluan salah (owner, vendor, ibu bapa); 5 gagal → dikunci walaupun kata laluan betul (ibu bapa & vendor),
  setiap cubaan + IP direkod; id sesi bertukar selepas log masuk; POST tanpa CSRF → 419; GET ke endpoint POST → 405
- 2FA owner wajib: selepas langkah 1 API masih 401; kod salah ditolak; kod TOTP sama tidak boleh diguna semula; kod pemulihan sekali guna
- `owner_staff` → `owner/staff/list` 403, halaman pelan dialih; juruwang → halaman pesanan dialih ke serah barang, API kakitangan 403
- E-mel ibu bapa / telefon (format lain) sama tidak boleh daftar dua kali; PDPA wajib; e-mel pengesahan dalam bahasa ibu bapa; token sekali guna
- Vendor `suspended`: juruwang ditolak, pentadbir hanya langganan/invois (halaman lain → `subscription.php`, API → 403 `billing_only`),
  sesi pengurus sedia ada ditamatkan
- **Kebocoran VENDOR01 ↔ VENDOR02:** pilih koperasi vendor lain → 404 (termasuk `'2 OR 1=1'`); senarai koperasi & kakitangan hanya milik sendiri
  (`vendor_id` dalam input diabaikan); sunting / aktif / reset kakitangan vendor lain → 404; beri akses koperasi vendor lain ditolak;
  pengurus hanya koperasi yang diberi; PIN juruwang VENDOR01 di kaunter VENDOR02 → tiada akses
- **Kebocoran ibu bapa A ↔ B:** profil masing-masing; `customer_id` dalam input diabaikan; B tidak boleh guna telefon A
- Padam akaun: e-mel & telefon NULL, `deleted_at`, tidak boleh log masuk; pesanan aktif menghalang
- Playwright: log masuk owner (QR → kod → kod pemulihan → panel), staf, 2FA, log keluar; vendor papan pemuka/kakitangan/POS; lupa kata laluan
  vendor (375px); store log masuk, profil, daftar → pautan pengesahan dari `email_queue` — 0 ralat konsol

Keputusan kecil (dicatat dalam PELAN 4, 9 J, 10.1, 10.2, 11, 18.3, 18.4, 18.14):
- PIN POS: **5** cubaan (selaras kontrak U4 18.11), bukan 3 — PELAN 11 & teks bantuan dikemas kini
- Owner tanpa 2FA disediakan semasa log masuk (seed tiada rahsia TOTP); "set semula 2FA" kakitangan = sediakan semula pada log masuk seterusnya
- Jemputan kakitangan owner memerlukan jadual baharu `platform_user_tokens` (migrasi 001)
- `vendor/cooperatives/list` (baca) & `store/session` disambung dalam B2 kerana pemilih koperasi & header store bergantung pada sesi sebenar;
  simpan/sunting koperasi kekal B4
- Kunci log masuk dikira ikut pengecam dalam `login_attempts` (bukan lajur pengguna sahaja) supaya akaun tiada pun dikunci sama
- E-mel hanya masuk `email_queue` (cron hantar — B7); pautan e-mel guna `config app_url` (wajib di produksi)

Tertangguh / peralihan:
- Data modul lain masih mock (`MockVendor.sessionVendorId = 1`): log masuk VENDOR02 memaparkan koperasi DB (Chung Hwa, SMK Seri Indah) tetapi
  data katalog/pesanan mock hanya wujud untuk id koperasi mock — SMK Seri Indah (id 3) kosong/ralat sehingga B4. Gunakan VENDOR01 untuk demo
- Store: anak, troli, pesanan masih mock; sesi mock mengikut ibu bapa sebenar (dipadankan e-mel) — ibu bapa seed (Siti) bermula tanpa anak mock
- Endpoint `vendor/pos/pin-login` (job siap) — B10. 2FA vendor: log masuk menyokong, tetapi tiada halaman sediakan 2FA vendor (tiada UI U) — kemudian
- `vendor/_layout/profile-menu.php` memaut ke `vendor/profile.php` yang belum wujud (sejak U3) — perlu halaman profil vendor (kemudian)
- Penghantaran e-mel sebenar (SMTP + cron) — B7

### B3 — Owner: pelan, harga koperasi, modul, template, vendor (26 Okt 2026)

Dibuat:
- **Job (33 + 3 pembantu):** `jobs/Owner/` ListVendors, ListVendorOptions, GetVendor, SetVendorStatus, AddVendorNote, ListAllCooperatives, ListAuditLogs ·
  `jobs/Plan/` ListPlans, ListPlanOptions, SavePlan, DeletePlan, GetPlanTiers, SavePlanTiers · `jobs/Billing/CalculateCoopPriceJob` ·
  `jobs/Module/` ListModules, SaveModule · `jobs/Template/` ListTemplates, SaveTemplate · `jobs/Payment/` ListPaymentGateways,
  TogglePaymentGateway, SaveOwnerGateway · `jobs/Language/` ListGrades, SaveGrade + pembantu `TranslationValues` · `jobs/Notification/`
  ListEmailTemplates, GetEmailTemplate, SaveEmailTemplate + `EmailTemplateCatalog` · `jobs/Announcement/` List, Save, Delete ·
  `jobs/Setting/` GetPlatformSettings, SavePlatformSettings, SendTestEmail + pembantu `PlatformSettings` · `jobs/Report/GetOwnerDashboardJob`
- **API (32 fail, tiada SQL):** `owner/dashboard/summary`, `owner/vendors/{list,options,get,set-status,add-note}`, `owner/cooperatives/list`,
  `owner/plans/{list,options,save,delete,tiers,save-tiers}`, `owner/modules/{list,save}`, `owner/templates/{list,save}`,
  `owner/gateways/{list,toggle,save-owner}`, `owner/grades/{list,save}`, `owner/email-templates/{list,get,save}`,
  `owner/announcements/{list,save,delete}`, `owner/audit-logs/list`, `owner/settings/{get,save,test-email}`
- **Peranan:** `owner_staff` hanya papan pemuka, vendor (baca + nota), koperasi, log audit, `plans/options`; selebihnya 403.
  `owner/vendor.php` tidak memapar butang gantung/aktifkan untuk `owner_staff`; `vendors.js` guna `owner/plans/options`
- **`api.js`:** 12 awalan owner ditambah ke `Api.livePrefixes`; `mock/owner.js` dikecilkan kepada langganan/invois (B12) & terjemahan (B14);
  `Mock.storageVersion` → v7; `asset_version` → 4
- **Core:** `Lang::fileValue()` (nilai fail bahasa tanpa jatuh balik, dicache); pengendali ralat global → `Database::rollBack()`
  (selamat walaupun belum bersambung)
- **Seed:** 13 templat e-mel contoh (dari mock U2), 2 nota vendor, 3 pengumuman (satu draf sasaran VENDOR02)
- 2 kunci bahasa baharu × 4 (`email.test_email.subject|body`) — jumlah 1796
- `tools/test-helpers.php` (diasingkan dari `verify-b2.php`: `check`, `Browser`, log masuk, `clearTestLocks`) · `tools/verify-b3.php` (101 semakan)

Disahkan (MariaDB 10.11, PHP 8.3, pemasangan baharu schema + seed — `verify-b3` **101 lulus, 0 gagal**; `verify-b2` 94/94; `verify-b1` 42/42;
`check-ui` OK; `ui-audit` lang=ms owner 44 halaman × 2 lebar: tiada ralat konsol (isu "kunci mentah" di log audit & terjemahan = kod yang
memang dipapar); Playwright: papan pemuka MRR RM 244.66, senarai/butiran vendor, harga koperasi, modul, tetapan, gateway dengan data DB):
- **owner_staff → 403** untuk 22 laluan harga/pelan/modul/template/gateway/darjah/templat e-mel/pengumuman/tetapan/gantung vendor; halaman
  tetapan dialih; papan pemuka, vendor, koperasi, log audit, `plans/options` (tanpa harga) dibenarkan
- **Kebocoran:** sesi VENDOR01, VENDOR02, ibu bapa A & B → semua API owner 401 (sesi berasingan); tiada nota dicipta. Gantung VENDOR02 →
  VENDOR01 tidak terjejas; butiran VENDOR02 hanya koperasinya; sesi VENDOR02 sedia ada terus `billing_only`
- POST tanpa CSRF → 419; GET ke endpoint POST → 405; carian `%` tidak memadankan semua (LIKE di-escape)
- MRR = (99000 + 65000)/12 + 4900 + 4900 + 1000 = 24466 sen; langganan hampir tamat = VENDOR02
- Pelan: kod huruf besar & unik, peringkat lalai 2+ harga sama, 5 set peringkat tidak sah ditolak, padam pelan digunakan ditolak, peringkat
  dibuang bersama pelan; `CalculateCoopPriceJob` 5 koperasi = 9500, Pro tahunan 4 koperasi = 316000
- Modul: kod `^[a-z][a-z0-9_]{1,30}$`, unik, tidak boleh ditukar; nama Cina/Tamil utf8mb4 dalam `translations`
- Template: template1 sentiasa percuma & aktif; percuma → harga 0
- Gateway: rahsia wajib kali pertama, disulitkan, tidak pernah dipulangkan, kosong = kekal, tukar driver = tidak dibawa
- Templat e-mel: pemegang tempat tidak dikenali ditolak; tetapan: SMTP disulitkan, kosong = kekal, SST 0–100; e-mel ujian → `email_queue`
- Log audit: 15 jenis tindakan B3 direkod dengan pelaku, kod vendor, butiran, IP; tapisan jenis/tarikh/carian

Keputusan kecil (dicatat dalam PELAN 4, 10.1, 10.2, 18.3, 18.7, 18.14):
- Laluan baharu `owner/plans/options` — `owner_staff` perlu tapisan pelan di senarai vendor tanpa melihat harga (`plans/list` owner sahaja)
- `owner_staff` boleh tambah nota vendor (kerja sokongan) tetapi tidak boleh gantung/aktifkan
- Peralihan status vendor dihadkan (aktifkan: trial/past_due/suspended; gantung: trial/active/past_due); `cancelled` tidak boleh diubah di sini
- Nama modul & darjah yang owner sunting disimpan dalam `translations` (kunci sama seperti `lang/*.php`); kosong = kembali ke fail bahasa
- MRR & sejarahnya dikira dari harga snapshot langganan (bukan invois) — B12 boleh tukar kepada invois dibayar bila invois sebenar wujud
- `audit_logs.entity_id` hanya id nombor; kod (gateway, darjah, templat) dalam `detail`

Tertangguh:
- `t('module.x')` / `t('grade.x')` di halaman lain masih membaca `lang/*.php` sahaja — tindih nilai dari `translations` dalam B14
  (bersama `owner/translations/*`)
- `owner/subscriptions/list`, `owner/invoices/*` masih mock (B12): halaman langganan & invois memapar vendor mock (VENDOR01–07),
  manakala butiran vendor memapar invois DB (kosong sehingga B12)
- Pengumuman belum dipapar kepada vendor (tiada laluan vendor dalam U) — kemudian
- Gateway disekat owner: semakan semasa vendor memilih/membayar — B8. `preview_image` template sebenar — bila template2/3 dibina

### B4 — Vendor: koperasi, katalog, inventori (26 Okt 2026)

Dibuat:
- **Core:** `Uploader` (data URL → fail; jenis dari kandungan `finfo` + `getimagesizefromstring`, JPG/PNG/WebP ≤ 2MB, nama SHA-256, padam fail lama) ·
  `VendorAuth::requireCooperative($id)` (`ValidateCooperativeJob` + akses kakitangan → 404)
- **Job (37 + 7 pembantu):** `jobs/Cooperative/` GetCooperative (+ waktu operasi), SaveCooperative (had pelan, harga koperasi ke-n → `subscription_items`,
  tetapan awal, logo), CheckSlug (unik global + slug sistem dikhaskan), GetCooperativePricing, ListCooperativeGrades, ListSchoolClasses,
  AddSchoolClasses, RenameSchoolClass, DeleteSchoolClass + `CooperativeSettings` · `jobs/Product/` ListCategories, SaveCategory, DeleteCategory,
  ListProducts, GetProduct, SaveProduct (varian, darjah, terjemahan, SKU/barcode unik merentas produk & varian), SetProductActive,
  ListProductOptions, UploadProductImage + `ProductCatalog` · `jobs/Inventory/` ListInventory, StockIn, AdjustStock, ListStockMovements,
  ListStockTakes, StartStockTake, GetStockTake, SaveStockTakeCounts, CompleteStockTake + `StockLedger`, `InventoryRows`, `StockTakes` ·
  `jobs/Supplier/` ListSuppliers, SaveSupplier, ListPurchaseOrders, GetPurchaseOrder, SavePurchaseOrder, ReceivePurchaseOrder,
  CancelPurchaseOrder + `PurchaseOrders` · `jobs/Billing/PlanLimit` · `jobs/Template/ListTemplateOptionsJob` · `jobs/Module/ListActiveModulesJob` ·
  `jobs/Report/GetVendorDashboardJob`
- **API (36 fail, tiada SQL):** `vendor/cooperatives/{get,save,check-slug,pricing-preview}`, `vendor/templates/options`, `vendor/modules/active`,
  `vendor/grades/list`, `vendor/classes/{list,add,update,delete}`, `vendor/categories/{list,save,delete}`,
  `vendor/products/{list,get,save,set-active,options}`, `vendor/inventory/{list,stock-in,adjust,movements}`, `vendor/suppliers/{list,save}`,
  `vendor/purchase-orders/{list,get,save,receive,cancel}`, `vendor/stock-takes/{list,start,get,save-counts,complete}`, `vendor/dashboard/summary`
- **`api.js`:** 12 awalan vendor ditambah ke `Api.livePrefixes` (`vendor/cooperatives/list` → `vendor/cooperatives/`); `mock/vendor.js` dikecilkan
  kepada data contoh + pembantu yang dipakai mock modul lain (semua `Mock.register` dibuang); `Mock.storageVersion` → v8; `asset_version` → 5
- **Seed:** PO-2026-0001 (VENDOR01, dipesan) + stock take selesai tanpa perbezaan — halaman PO & stock take demo berisi
- 9 kunci bahasa baharu × 4 (`product.limit_reached`, `product.variant_locked`, 7 × `activity.action.*`) — jumlah 1805
- `tools/verify-b4.php` (118 semakan HTTP sebenar; jadual B4 & fail muat naik dipulihkan di hujung)

Disahkan (MariaDB 10.11, PHP 8.3, pemasangan baharu schema + seed — `verify-b4` **118 lulus, 0 gagal**; `verify-b3` 101/101; `verify-b2` 94/94;
`verify-b1` 42/42; `check-ui` OK; `ui-audit` lang=ms vendor 90 halaman × 2 lebar: tiada ralat konsol baru; Playwright: produk baharu dengan gambar
& 2 varian → senarai, PO-2026-0001, stock take, borang koperasi, VENDOR02 nampak katalog Chung Hwa dari DB — 0 ralat konsol):
- **Kebocoran VENDOR01 ↔ VENDOR02:** produk V01 diminta V02 (koperasi V01 atau koperasi sendiri) → 404; `cooperative_id` vendor lain /
  `'2 OR 1=1'` → 404; stok masuk, set-active, padam kelas, sunting koperasi, pembekal, PO & stock take vendor lain → 404 dan data tidak berubah;
  senarai pembekal hanya milik sendiri; `vendor_id` dalam input diabaikan (koperasi & produk baharu tetap vendor sesi); slug V01 "sudah digunakan"
  tanpa dedah pemilik; pengurus Chung Hwa → koperasi 3 404
- **Kebocoran ibu bapa A ↔ B:** sesi ibu bapa → semua API vendor 401, tiada pergerakan stok
- Peranan: juruwang → 7 laluan katalog/inventori/papan pemuka 403 (`modules/active` dibenarkan untuk menu); pengurus → `pricing-preview` &
  tambah koperasi 403; POST tanpa CSRF → 419; GET ke POST → 405
- Koperasi: had pelan VENDOR02 (Asas 2/2) menolak koperasi ke-3; koperasi ke-2 VENDOR01 → `subscription_items` 79000 (Pro tahunan peringkat 2–3);
  tetapan awal; kod KPM, template belum dilanggan, tiada kaedah bayaran, waktu operasi terbalik ditolak; logo palsu (PHP dalam data URL) ditolak,
  logo sebenar → `uploads/1/{id}/logo/{sha256}.png`, buang logo → fail dipadam
- Produk: barcode/SKU pendua dalam koperasi (termasuk varian) ditolak, barcode sama di koperasi lain dibenarkan; tukar barcode antara dua varian;
  buang varian tanpa sejarah (baris stok dibuang) vs varian bersejarah ditolak; darjah ikut jenis sekolah; terjemahan Cina utf8mb4; had
  `max_products`; carian `%` di-escape
- Stok: semua perubahan melalui `StockLedger` (FOR UPDATE + `balance_after` + pengguna sesi); di luar transaksi → ditolak; pelarasan bawah
  kuantiti ditahan ditolak; PO: pembekal/produk koperasi lain ditolak, jumlah dikira pelayan, terima melebihi baki ditolak, separa → penuh
  (stok + `po_receive`), batal separa ditolak; stock take: satu terbuka, kiraan → `stocktake`, item belum dikira tidak diubah

Keputusan kecil (dicatat dalam PELAN 10.1, 10.2, 18.3, 18.8, 18.14):
- PO, stock take, `templates/options` & `modules/active` disiapkan dalam B4 (prompt B4 / pemilih koperasi), bukan B11–B13
- `vendor/settings/*` ditangguh ke B9 — skrin serah & resit POS (mock) masih membaca tetapan mock; menukarnya sekarang memecahkan keselarasan
- `PlanLimit` diletak dalam `jobs/Billing/` (membaca DB) — core tidak menulis SQL
- Gambar/logo dihantar sebagai data URL dalam badan JSON simpan (UI tidak ditulis semula); pelayan mengesahkan kandungan & menyimpan fail
- Varian bersejarah tidak dipadam (nyahaktif sahaja) supaya sejarah stok/pesanan kekal bermakna
- Koperasi baharu: hanya pentadbir; pengurus boleh sunting koperasi yang diberi. Slug sistem dikhaskan untuk URL `/{slug}/` (B6)
- Stock take selesai: perbezaan = kiraan − baki semasa mula (jualan semasa kiraan sudah direkod sendiri); baki tidak jatuh bawah 0
- Selesai dari tertangguh B2/B3: katalog VENDOR02 (Chung Hwa, SMK Seri Indah) kini dari DB; pengumuman platform yang terbit dipapar di papan pemuka vendor

Tertangguh / peralihan:
- Mock kit (B5), store (B6), pesanan & serah (B9), POS (B10), add-on (B13) masih memakai produk/koperasi mock (`MockVendor`, id tidak sepadan DB)
- `vendor/settings/*` → B9. Produk dalam pesanan/POS sebenar menolak/menahan stok melalui `StockLedger` (B7, B9, B10)
- Pratonton harga koperasi baharu dikira, tetapi invois/prorata sebenar → B12

### B5 — Kit keperluan sekolah (26 Okt 2026)

Dibuat:
- **Job (`jobs/SchoolKit/`, 7 + pembantu):** `ListKitsJob`, `GetKitJob`, `SaveKitJob`, `PublishKitJob` (set-published), `DeleteKitJob`,
  `CopyKitFromYearJob`, `ListKitProductOptionsJob` + `SchoolKits` (find, summaries/detail dengan harga semasa, `findPublished` untuk store B6,
  `yearAllowed`)
- **API (7 fail, tiada SQL):** `vendor/kits/{list,get,product-options,save,set-published,delete,copy-year}` — semua `PANEL_ROLES` +
  `VendorAuth::requireCooperative()`; POST dengan CSRF
- **`api.js`:** `vendor/kits/` ditambah ke `Api.livePrefixes`; `Mock.register('vendor/kits/*')`, `mockKitSummary`, `mockFindKit` dibuang dari
  `mock/vendor-orders.js` (data contoh `MockOrders.kits` kekal untuk mock `store/kit` — B6); `Mock.storageVersion` → v9; `asset_version` → 6
- 5 kunci bahasa baharu × 4 (`kit.delete_has_orders`, `activity.action.kit_save|kit_unpublish|kit_delete|kit_copy`) — jumlah 1810
- `tools/verify-b5.php` (59 semakan HTTP sebenar; jadual kit, produk, varian, troli & log aktiviti dipulihkan di hujung)

Disahkan (MariaDB 10.11, PHP 8.3, pemasangan baharu schema + seed — `verify-b5` **59 lulus, 0 gagal**; `verify-b4` 118/118; `verify-b3` 101/101;
`verify-b2` 94/94; `verify-b1` 42/42; `check-ui` OK; Playwright: grid kit 2027 (D1 RM 69.30 / wajib RM 67.30 dari DB), cipta kit D2 → simpan draf →
terbit → tarik balik → padam — 0 ralat konsol):
- **Kebocoran VENDOR01 ↔ VENDOR02:** kit V01 diminta V02 (koperasi V01 atau koperasi sendiri) → 404; `cooperative_id` vendor lain / `'2 OR 1=1'`
  → 404; set-published, delete, save dengan id kit V01, copy-year ke koperasi V01 → ditolak dan data tidak berubah; `vendor_id` dalam input
  diabaikan; pengurus Chung Hwa → koperasi 3 404
- **Kit hanya produk koperasi yang sama:** produk V02 dalam kit V01, produk V01 dalam kit V02, produk koperasi 2 dalam kit koperasi 3 (vendor
  sama) → semua ditolak; varian produk lain, varian pada produk biasa, kuantiti 0/pecahan, produk tiada / tidak aktif, item pendua → ditolak
- **Kit draf tidak kelihatan di store:** `SchoolKits::findPublished` memulangkan kit diterbitkan sahaja; tarik balik → hilang; koperasi lain tidak nampak
- Harga: jumlah dikira pelayan (`unit_price`/`total_price` dalam input diabaikan); tukar harga produk → jumlah kit berubah serta-merta; item tidak
  aktif tidak dikira; darjah ikut jenis sekolah (T1 untuk SK ditolak); darjah+tahun pendua → `kit.duplicate`; nama Cina/Tamil utf8mb4
- Sunting: susunan baharu disimpan, id item produk+varian sama dikekalkan, item yang dibuang dipadam; padam kit diterbitkan ditolak, draf → kit & item dibuang
- Salin 2027 → 2028: 3 kit disalin sebagai draf, item produk tidak aktif dibuang, salin semula → semua dilangkau
- **Kebocoran ibu bapa A ↔ B:** sesi ibu bapa → semua API kit 401, tiada kit dicipta. Juruwang → 403. POST tanpa CSRF → 419; GET ke POST → 405

Keputusan kecil (dicatat dalam PELAN 18.3, 18.9, 18.14):
- `get`, `set-published`, `delete` juga menerima & mengesahkan `cooperative_id` (UI sudah menghantarnya melalui `withCoop`) — skop lebih ketat dari indeks U7
- Jumlah kit hanya mengira item aktif (yang ibu bapa nampak); `item_count` = semua item supaya vendor sedar item tidak aktif
- Item hanya produk/varian aktif; produk+varian tidak boleh berulang dalam satu kit (kuantiti mengawal bilangan)
- Tahun yang boleh disimpan/disalin: tahun semasa −1 hingga +2 (musim pre-order bermula Okt–Dis; PELAN 16.13)
- Terbit memerlukan ≥ 1 item aktif; "Simpan draf" pada kit diterbitkan menjadikannya draf (ikut kontrak mock `publish`)
- Padam kit yang sudah ada dalam pesanan ditolak (sejarah); baris troli kit yang dipadam dibuang bersama

Tertangguh / peralihan:
- Paparan kit di store (`store/kit`, `store/cart/add-kit`) masih mock `MockOrders.kits` → B6 (guna `SchoolKits::findPublished` + `SchoolKits::items`) — siap B6
- Item kit dengan produk yang kemudian dinyahaktif tidak boleh dipilih semula dalam borang (pilihan produk aktif sahaja) — vendor buang/ganti item itu

### B6 — Store ibu bapa: anak, katalog, kit, troli (26 Okt 2026)

Dibuat:
- **Pembantu:** `jobs/Store/StoreSchool` (slug → koperasi aktif + vendor tidak digantung, modul store aktif, darjah, kaedah bayaran),
  `jobs/Store/StorePricing` (produk aktif + terjemahan, varian aktif, harga + jualan murah, baki `on_hand − reserved`, had beli, kad StoreProduct),
  `jobs/Child/Children` (find/all ikut `customer_id`, `promote_to`, padanan kelas, buang baris troli), `jobs/Cart/Carts` (troli `ensure` FOR UPDATE,
  `assertFits` stok & had merentas troli, `quote` = CartQuote)
- **Job (19):** `jobs/Store/` SearchSchoolsJob, GetStoreSchoolJob (+ notis koperasi tutup & musim pre-order), ListStoreClassesJob, StoreCatalogJob,
  StoreProductJob, StoreKitForChildJob, ListStoreNewsJob, ListStoreClearanceJob, ResolveStoreTemplateJob · `jobs/Child/` ListChildrenJob,
  SaveChildJob, RemoveChildJob, PromoteChildJob · `jobs/Cart/` GetCartJob, AddToCartJob, AddKitToCartJob, UpdateCartItemJob, RemoveCartItemJob, ClearCartJob
- **API (19 fail, tiada SQL):** `store/{schools/list, school, classes, catalog, product, kit}`, `store/children/{list,save,remove,promote}`,
  `store/cart/{get,summary,add,add-kit,update,remove,clear}`, `store/news/list`, `store/clearance/list`
- **`store/index.php`:** slug diselesaikan di pelayan (slug tidak sah → HTTP 404 + mesej), template dari `cooperatives.template_id` melalui
  `ResolveStoreTemplateJob` (tidak dilanggan / tidak aktif / folder tidak lengkap → `template1`), pratonton `?template=` hanya owner atau vendor
  pemilik koperasi (sesi panel dibaca hanya jika cookie wujud)
- **URL cantik:** `.htaccess` akar (`/{slug}/`, `/{slug}/{halaman}`) + `config pretty_store_urls` (lalai `false`), `View::storeUrl()` & JS `storeUrl()`
  (`data-pretty-url`)
- **`api.js`:** 10 awalan store ditambah ke `Api.livePrefixes`; `mock/store.js` dikecilkan kepada checkout, pesanan & simulasi bayaran + jambatan
  (sesi ibu bapa, troli sebenar → checkout mock); `MockOrders.kits` dibuang; `Mock.storageVersion` → v10; `asset_version` → 7
- `store/news.js` & `store/clearance.js` menyemak `Store.school.modules` sebelum memanggil API (tiada permintaan 404 bila modul tidak aktif)
- 2 kunci bahasa baharu × 4 (`store.children.limit`, `store.cart.too_many_lines`) — jumlah 1812
- Seed: Adam (ibu bapa A) Darjah 1 tahun 2027 → sepadan Kit Darjah 1 2027 (demo aliran kit)
- `tools/verify-b6.php` (85 semakan HTTP sebenar; jadual diubah dipulihkan di hujung); `test-helpers.php`: `Browser::status($laluan, $params)`,
  `clearTestLocks()` juga membuang had kadar troli/anak

Disahkan (MariaDB 10.11, PHP 8.3, pemasangan baharu schema + seed — `verify-b6` **85 lulus, 0 gagal**; `verify-b5` 59/59; `verify-b4` 118/118;
`verify-b3` 101/101; `verify-b2` 94/94; `verify-b1` 42/42; `check-ui` OK; `ui-audit` lang=ms store 38 halaman × 2 lebar: 0 isu; Playwright 390px:
log masuk ibu bapa A → kit Adam (Darjah 1 2027, 4 item dari DB, RM 69.30) → pilih saiz → troli (11 unit) → checkout mock → pesanan TM-2610-…
dan troli sebenar dikosongkan — 0 ralat konsol):
- **Kebocoran ibu bapa A ↔ B:** A sunting / buang / naik darjah anak B → 404 dan data tidak berubah; A tambah barang / kit untuk anak B → ditolak,
  tiada baris; A ubah / buang baris troli B (melalui slug mana-mana) → 404, troli B kekal; kosongkan troli A tidak menyentuh troli B;
  `store/kit` dengan anak B / anak A di sekolah lain → 404; `customer_id`, `vendor_id`, `cooperative_id` dalam input anak diabaikan
- **Kebocoran VENDOR01 ↔ VENDOR02 (slug):** produk V02 melalui slug V01 (dan sebaliknya) → 404 untuk katalog/produk/troli; kelas & kategori tidak
  bercampur; slug `x' OR 1=1` → 404; koperasi digantung / vendor digantung → hilang dari senarai & 404
- Harga dari pelayar (`unit_price`, `price`) diabaikan; tukar harga produk → troli dikira semula; produk dinyahaktif → `unavailable`; stok kurang →
  `stock`; had beli & stok dikira merentas troli (semua anak, kit + ala carte); kuantiti 0/100/-1/"abc"/1.5 ditolak; varian wajib & varian produk lain ditolak
- Kit: draf (T2 2027) tidak dipapar walaupun anak sepadan → terbit → dipapar; item pilih saiz tanpa saiz → `variant_{id}`; item kit lain / pendua /
  kit bukan untuk darjah anak / senarai kosong → ditolak; masuk semula kit → baris diganti; `in_cart` dipulangkan
- Akses: tanpa sesi → kit/troli/anak `auth_required`; sesi vendor → `auth_required`; POST tanpa CSRF → 419; GET ke POST → 405
- Modul: berita berjadual tidak dipapar; modul tamat → jualan murah 404 & katalog kembali harga biasa; template tidak dilanggan → template1

Keputusan kecil (dicatat dalam PELAN 10.1, 10.2, 10.5, 18.3, 18.5, 18.12, 18.14):
- Store ditutup bila vendor `suspended` / `cancelled` (koperasi `active` sahaja tidak mencukupi)
- Had beli & stok dikira setiap **produk + varian** (sama seperti mock U5); kuantiti troli 1–99; ≤ 200 baris troli; ≤ 20 anak aktif setiap akaun
- `store/cart/update` menyimpan kuantiti walaupun melebihi stok/had — CartQuote menanda `problem` dan checkout disekat (ikut kontrak U5)
- CartQuote B6 tanpa promosi (`discount 0`); promosi & kupon dikira dalam checkout (B7) — paparan troli = jumlah sebelum diskaun
- Produk dalam kategori tidak aktif tidak dijual di store (katalog, produk, troli, kit)
- Tukar sekolah anak / buang anak → baris troli anak itu dibuang; naik darjah mengekalkan nama kelas (dipadankan semula dengan kelas darjah baharu)
- Modul tidak aktif → HTTP 404 `store.module_unavailable`; JS berita/jualan murah menyemak modul dahulu
- URL cantik pilihan (`pretty_store_urls`) kerana `php -S` (pembangunan & ujian) tidak membaca `.htaccess`

Tertangguh / peralihan:
- Checkout, pesanan saya (B7) & bayaran (B8) masih mock. Jambatan: checkout memuat troli SEBENAR, mengira promosi/cukai/slot mock, mencipta
  pesanan mock (`live_cart`, stok mock tidak ditahan) dan mengosongkan troli sebenar. Sekolah tiada dalam mock (`smk-seri-indah`) → checkout gagal sehingga B7
- `store/school.notices.preorder` & slot ambil dari DB (tiada musim dalam seed) — checkout mock masih memakai slot mock (B7/B13)
- Gambar produk sebenar dipapar jika vendor memuat naik; resit PDF → B9
- `ui-audit` vendor: `vendor_school-kit` melaporkan kotak semak pratonton `aria-hidden` tanpa label (sedia ada sejak B5, bukan ralat konsol)

### B7 — Checkout tunai, pesanan & queue e-mel (27 Okt 2026)

Dibuat:
- **Pembantu:** `jobs/Order/CheckoutQuote` (CartQuote + promosi + cukai selepas diskaun + kaedah bayaran + kod 4 digit + slot + tutup tempahan —
  dikongsi paparan & checkout), `CustomerOrders` (find/require ikut `customer_id` + vendor + koperasi + `online`, summary, StoreOrderDetail,
  `releaseHolds`, `sortStockKeys`, `logStatus`), `OrderNumbers` (no. pesanan `TM-2610-0001`, kod pendek `A123`, token QR — koperasi dikunci),
  `OrderEmails` (amaran vendor ke `alert_emails` + resit ibu bapa dengan jadual barang & QR), `jobs/Promotion/Promotions` (satu promosi: kupon /
  automatik terbaik, sama seperti `pos-cart.js`)
- **Job (8):** `jobs/Order/` CheckoutJob, GetCheckoutQuoteJob, ListCustomerOrdersJob, GetCustomerOrderJob, CancelOrderJob, PayCustomerOrderJob
  (B8), ExpireCashOrdersJob · `jobs/Notification/SendQueuedEmailsJob`
- **API (6 fail, tiada SQL):** `store/checkout/{quote,place}`, `store/orders/{list,get,cancel,pay}`
- **Core:** `Mailer` (SMTP vanila tanpa Composer: STARTTLS/SSL/none, AUTH LOGIN, header UTF-8 `=?UTF-8?B?`, badan base64, sekat CR/LF; driver
  `log`), `Money::format()`, `QrCode` (port qrcode.js — mod Byte, tahap M, versi 1–6; jadual HTML `bgcolor` untuk e-mel), `View::absoluteStoreUrl()`
- **Ubah:** `StockLedger::reserve/release` (qty_reserved, pergerakan `reserve`/`release` qty_change 0 + ref pesanan), `Carts::quoteWithLines`,
  `StorePricing` (`base_name` untuk snapshot), `QueueEmailJob` (`$htmlVars`), `EmailTemplateCatalog` (`{pickup_qr}`)
- **Cron:** `cron/send-emails.php` (flock, 50 setiap larian, 5 cubaan → failed; SMTP kosong → kekal pending) · `cron/expire-cash-orders.php`;
  `cron/.htaccess` & `storage/.htaccess` halang web; `config mail_driver` (`smtp`/`log`) + `mail_log_file`
- **JS:** `Api.livePrefixes` + `store/checkout/`, `store/orders/`; `mock/store.js` DIBUANG; `Mock.onLive/afterLive/beforeFirst/ready` dibuang;
  template1 tidak memuat mock; `Mock.storageVersion` → v11; `asset_version` → 8
- **Seed:** 3 promosi (JIMAT5, automatik 3%, SI10); templat `order_receipt` + `{pickup_qr}` & pautan pesanan · migrasi `002-order-receipt-qr.sql`
  (tiada perubahan jadual)
- 4 kunci bahasa baharu × 4 (`email.order_new_vendor.*`, `email.order_receipt.*` — sandaran bila templat DB tiada) — jumlah 1816
- `tools/verify-b7.php` (92 semakan; pelayan 4 pekerja + `tools/fake-smtp.php`; jadual diubah dipulihkan), `test-helpers.php`: `parallelPost()`,
  `Browser::postHandle()`, `clearTestLocks()` + `store_checkout:`; `ui-audit.js`: order-success membaca `?order=`

Disahkan (MariaDB 10.11, PHP 8.3, pemasangan baharu schema + seed — `verify-b7` **92 lulus, 0 gagal**; `verify-b6` 85/85; `verify-b5` 59/59;
`verify-b4` 118/118; `verify-b3` 101/101; `verify-b2` 94/94; `verify-b1` 42/42; `check-ui` OK; `ui-audit` lang=ms store 38 halaman × 2 lebar: 0 isu
(dengan pesanan sebenar); Playwright 390px: log masuk ibu bapa A → troli → checkout + kupon JIMAT5 (RM 76.00 − RM 5.00) → pesanan TM-2610-0001
→ halaman berjaya (no. pendek, QR, 2 slip beg) → butiran → batal — 0 ralat konsol):
- **Serentak (PELAN 16 perkara 3):** dua ibu bapa checkout stok terakhir serentak (curl_multi, 3 pusingan) → tepat satu berjaya, `qty_reserved = qty_on_hand`
- **Kebocoran ibu bapa A ↔ B:** B baca / batal / bayar pesanan A → 404 & tiada perubahan; senarai B tidak mengandungi pesanan A; A melalui slug
  koperasi lain → 404; `customer_id`, `vendor_id`, `cooperative_id`, `status`, `total`, `unit_price`, `discount`, `promotion_id` dalam input diabaikan
- **VENDOR01 ↔ VENDOR02:** kupon SI10 (V02) tidak sah di SK Taman Melati; pesanan SMK Seri Indah → vendor 2, amaran hanya ke e-mel V02; no. `SI-…`/`CH-…`
- Transaksi: semua percubaan gagal (e-mel belum sah, jumlah berubah, kod salah, wakil tanpa nama, online, kupon salah, troli bermasalah) → tiada
  pesanan, troli & stok tidak berubah; harga & diskaun dari pelayan; cukai 6% selepas diskaun; kod 4 digit → hash Argon2id, tidak dipulangkan
- Snapshot: tukar nama/harga produk & naik darjah anak → pesanan kekal; nama Cina disimpan utuh (HEX); slot penuh / darjah lain / tidak dipilih
  ditolak, slot lepas tidak ditawarkan; tutup tempahan (calendar) → ditolak; batal → tahanan, slot & promosi dilepas (sekali sahaja)
- Cron: pesanan tunai lewat → `expired` + tahanan dilepas (log `system`); e-mel dihantar melalui SMTP palsu (AUTH LOGIN), subjek UTF-8 dikod,
  resit mengandungi QR & jadual barang; SMTP gagal → cuba semula, 5 kali → `failed`; QR PHP = qrcode.js (node) untuk 4 teks

Keputusan kecil (dicatat dalam PELAN 9, 10.1, 10.2, 18.3, 18.12, 18.14):
- B7 = **tunai sahaja**: online ditawarkan bila `accept_online` + gateway koperasi aktif + driver `GatewayFactory` (B8); CheckoutJob sudah
  menyokong `pending_payment`, `payment` = null sehingga B8
- Kod 4 digit hanya disimpan bila wajib (cara E / jumlah > had) — `requires_code` pesanan = ada hash
- Snapshot nama produk = nama asal vendor (bukan terjemahan bahasa ibu bapa) supaya panel vendor & laporan konsisten
- Batal/luput turut mengurangkan `promotions.used_count` (kupon terhad tidak "hangus"); slot `booked − 1`
- Kod pendek: huruf tanpa I/O + 3 digit, 4 digit bila sesak; no. pesanan ikut turutan bulanan koperasi (bukan id global)
- Troli tanpa baris → "Troli anda kosong"; ada baris bermasalah (cth. stok habis kerana pesanan lain) → "betulkan troli"
- Templat e-mel DB diutamakan; kunci `email.order_*` dalam `lang/*.php` hanya sandaran

Tertangguh / peralihan:
- Bayaran online, `store/orders/pay`, modal `storeStartPayment` (simulasi) → B8; luput `pending_payment` → B8
- Panel vendor (pesanan, serah barang, POS) masih mock — pesanan store sebenar belum kelihatan di panel vendor sehingga B9
- Promosi vendor (`vendor/promotions/*`) masih mock → B10 (seed DB sudah ada promosi untuk store)
- `order_ready` / `order_collected` e-mel → B9; resit PDF → B9
- `ui-audit` vendor: `vendor_school-kit` kotak semak pratonton `aria-hidden` tanpa label (sedia ada sejak B5)

### B8 — Payment gateway + webhook (28 Okt 2026)

Dibuat:
- **`payments/`** (dimuat automatik oleh `core/bootstrap.php`): `GatewayInterface` (createBill, verifyCallback, fetchStatus, amountLocked,
  testConnection, supportsRefund, callbackResponse), `GatewayFactory` (5 kod), `BaseGateway` (curl, URL sandbox/live, `KOPERASI_GATEWAY_TEST_URL`
  untuk ujian — sandbox sahaja), `GatewayException`; `drivers/` ToyyibPay (createBill, getBillTransactions, getCategoryDetails), Billplz (v3 bills,
  X-Signature), SenangPay (pautan HMAC, query_order_status), iPay88 (borang POST SHA256, enquiry.asp, `RECEIVEOK`), Stripe (Checkout Session,
  Stripe-Signature)
- **Job (13, `jobs/Payment/`):** pembantu `CooperativeGateways`, `PaymentSettlement`; `StartOnlinePaymentJob`, `HandleGatewayCallbackJob`,
  `CheckOrderPaymentJob`, `ExpirePendingPaymentsJob`, `GetPaymentFormJob`, `GetPaymentSettingsJob`, `GetCooperativeGatewayJob`,
  `SaveCooperativeGatewayJob` (rahsia disulitkan, satu aktif), `TestCooperativeGatewayJob`, `SavePaymentMethodsJob`, `ListPaymentRecordsJob`
- **API (12 fail baharu, tiada SQL):** `vendor/payments/{settings,gateway,save-gateway,test-gateway,save-methods,list}` (`vendor_admin`),
  `webhooks/{toyyibpay,billplz,senangpay,ipay88,stripe}` (`?coop=`, tiada sesi/CSRF, had 300/minit/IP), `store/orders/check-payment` (baharu)
- **Ubah:** `CheckoutJob` (online → `expires_at` +60 minit, bil dicipta selepas commit, `payment` / `payment_error`), `PayCustomerOrderJob` →
  `StartOnlinePaymentJob`, `CancelOrderJob` (bil pending → failed), `CheckoutQuote` & `StoreSchool::paymentMethods` (online hanya dengan gateway
  aktif), `Request::queryInt/webhook`, `Response::text`, `SecurityHeaders::allowFormAction`, `config online_payment_expiry_minutes` (60)
- **Halaman:** `store/payment-return.php` (kembali dari gateway, GET/POST → order-success `returned=1`), `store/payment-redirect.php` +
  `assets/js/store/payment-redirect.js` (borang iPay88); modal gateway template1 = ringkasan + pautan "Teruskan ke halaman bayaran" / "Bayar kemudian"
  (simulasi dibuang); `order-success.js` memanggil `check-payment` bila `returned=1`
- **Cron:** `cron/expire-online-orders.php` (flock; semak semula bil → paid, atau expired + lepas tahanan; gateway rosak → cuba semula ≤ 24 jam)
- **DB:** migrasi `003-webhook-result.sql` (`webhook_logs.result`) + `schema.sql`; tiada perubahan seed
- **JS:** `Api.livePrefixes` + `vendor/payments/`; 6 `Mock.register` bayaran & `mockGatewayPublic` dibuang dari `mock/vendor-finance.js`
  (`MockFinance.gateways` kekal untuk refund/POS mock); `asset_version` → 9
- 10 kunci bahasa baharu × 4 (`payment.gateway_error|bill_description|paid_after_close|test_failed|test_unsupported`, `store.payment.redirect_note|
  sandbox_note|redirecting`, `store.order.payment_expired`), 2 diubah (`store.payment.pay|cancel`), 1 dibuang (`store.payment.demo_note`) — jumlah 1824
- `tools/fake-gateway.php` (5 gateway palsu + `/_admin/set|bills|down`), `tools/verify-b8.php` (95 semakan), `test-helpers.php` `clearTestLocks()`
  + `store_payment_*`, `vendor_gateway_test:`; `verify-b6.php` dikemas kini (online perlu gateway aktif)

Disahkan (MariaDB 10.11, PHP 8.3, pemasangan baharu schema + seed — `verify-b8` **95 lulus, 0 gagal**; `verify-b7` 92/92; `verify-b6` 85/85;
`verify-b5` 59/59; `verify-b4` 118/118; `verify-b3` 101/101; `verify-b2` 94/94; `verify-b1` 42/42; `check-ui` OK; `ui-audit` lang=ms store 38
halaman + bayaran vendor 4 halaman: 0 isu; Playwright: vendor simpan ToyyibPay + uji sambungan → ibu bapa 390px checkout online → modal →
gateway → kembali tanpa panggilan balik → "Bayaran berjaya" melalui semakan pelayan — 0 ralat konsol):
- **Redirect palsu:** `payment-return.php?status_id=1…` tanpa bayaran sebenar → pesanan kekal `pending_payment`; panggilan balik palsu ToyyibPay
  "status=1" → kekal pending (gateway: belum dibayar)
- **Idempotent:** 4 panggilan balik serentak → satu `payments` paid, satu log `paid`; bil yang sama diguna semula bila "Bayar" ditekan lagi
- **Tandatangan & amaun:** Billplz X-Signature salah → 400; gateway melapor amaun lain → `amount_mismatch`, kekal pending; SenangPay hash salah,
  Stripe-Signature salah → 400; iPay88 `enquiry` amaun
- **VENDOR01 ↔ VENDOR02:** settings/gateway/list/save koperasi lain → 404; rujukan bil V01 ke `?coop=3` (V02) → `unknown_reference`, tiada perubahan;
  rekod bayaran V02 tiada data V01; pengurus & juruwang → 403
- **Ibu bapa A ↔ B:** B bayar / semak / buka borang iPay88 bil A → 404; slug koperasi lain → 404
- **Rahsia:** tidak dipulangkan, tidak disimpan teks biasa (Crypto), kosong = kekal
- **Gagal & luput:** gateway rosak → pesanan disimpan + `payment_error`, panggilan balik → 502; cron: bil dibayar tanpa panggilan balik → paid,
  lain → expired + tahanan dilepas + bil failed; wang selepas luput → `paid_late` + nota refund; owner sekat driver → online hilang

Keputusan kecil (dicatat dalam PELAN 9, 10.1, 10.2, 13, 18.10, 18.12, 18.14):
- ToyyibPay tidak menandatangani panggilan balik → disahkan sepenuhnya melalui API dengan kunci rahsia (`signature_valid` 0 + `(unsigned)`)
- Luput `pending_payment` = 60 minit (config), cron setiap 10 minit; bil disemak semula sebelum luput
- Rujukan bil `{order_no}-{n}`; bil pending diguna semula 30 minit; panggilan rangkaian tidak pernah dalam transaksi DB
- `vendor/payments/*` = `vendor_admin` sahaja (ikut jadual peranan PELAN 4)
- Kembali dari gateway melalui `store/payment-return.php` (303) — cookie sesi `SameSite=Lax` tetap dihantar walaupun gateway menghantar POST (iPay88)
- Wang selepas pesanan ditutup tidak menghidupkan semula pesanan (stok sudah dilepas) — direkod & ditanda untuk refund (B11)

Tertangguh / peralihan:
- Refund melalui API gateway (Stripe `supportsRefund`) → B11; QR online POS & `vendor/pos/payment-status` → B10; e-mel "bayaran diterima" → B9
- Rekod bayaran tunai (`payments` method cash) ditulis semasa serah barang / POS → B9/B10
- Gateway owner untuk invois langganan (driver yang sama) → B12
- Panel vendor pesanan masih mock (pautan dari rekod bayaran ke `vendor/order.php`) → B9 ✅

### B9 — Pengurusan pesanan & serah barang (28 Okt 2026)

Dibuat:
- **Pembantu:** `jobs/Order/VendorOrders` (find/require ikut `vendor_id` sesi + `cooperative_id` disahkan, `filters` senarai/picking/label,
  `summary` = OrderSummary, `detail` = OrderDetail dengan nama pelaku & rekod serahan, `STATUS_FLOW`, `pickupSettings`, `remaining`, `gradeOrder`)
- **Job (16):** `jobs/Order/` ListVendorOrdersJob, GetVendorOrderJob, UpdateOrderStatusJob, BulkOrderStatusJob, PickingListJob, PrintLabelsJob,
  ListOrderSlotOptionsJob, FindOrdersForPickupJob (cara A/B/C ikut tetapan + `found_by`), ReceiveCashPaymentJob, VerifyPickupCodeJob,
  HandoverOrderJob, UnlockPickupCodeJob · `jobs/Customer/` ListVendorCustomersJob, SaveCustomerNoteJob · `jobs/Cooperative/`
  GetCooperativeSettingsJob, SaveCooperativeSettingsJob
- **API (16 fail, tiada SQL):** `vendor/orders/{list,get,set-status,bulk-status,picking-list,labels,class-options,slot-options,unlock-code}`,
  `vendor/handover/{search,receive-cash,collect}`, `vendor/customers/{list,save-note}`, `vendor/settings/{get,save}`; `vendor/grades/list`
  kini dibuka kepada juruwang (tapisan skrin serah)
- **Ubah:** `StockLedger::collect` (tolak `qty_on_hand` + `qty_reserved`, pergerakan `collect`), `OrderEmails::queueReady/queueCollected/queuePaid`,
  `PaymentSettlement` (e-mel `order_paid` bila `paid`), `EmailTemplateCatalog` (+`order_paid`)
- **JS:** `Api.livePrefixes` + `vendor/orders/`, `vendor/handover/`, `vendor/customers/`, `vendor/settings/`; `Mock.register` laluan itu + pembantu
  yang tinggal tidak digunakan dibuang dari `mock/vendor-orders.js`, `vendor-finance.js`, `vendor-addons.js` (`MockOrders` & `MockFinance.settings`
  kekal untuk POS/refund/laporan mock); `handover.js` menghantar `method` (= `found_by`); `order.js` butang "Buka kunci kod"; `asset_version` → 10
- **DB:** migrasi `004-order-handover-emails.sql` (templat `order_collected` en/zh/ta + nama anak, `order_paid` 4 bahasa) + `seed.sql`; tiada
  perubahan jadual
- 13 kunci bahasa baharu × 4 (`order.actor_system|unlock_code|code_unlocked_toast|code_unlocked_log|code_not_locked`,
  `activity.action.pickup_unlock`, `email_template.order_paid`, `email.order_ready|order_collected|order_paid.subject|body`) — jumlah 1837
- `tools/verify-b9.php` (106 semakan; pelayan 4 pekerja; jadual diubah dipulihkan); `verify-b3.php` dikemas kini (7 kunci templat e-mel);
  `ui-audit.js`: `vendor/order.php` membuka pesanan sebenar ibu bapa A

Disahkan (MariaDB 10.11, PHP 8.3, pemasangan baharu schema + seed + migrasi 004 — `verify-b9` **106 lulus, 0 gagal**; `verify-b8` 95/95;
`verify-b7` 92/92; `verify-b6` 85/85; `verify-b5` 59/59; `verify-b4` 118/118; `verify-b3` 101/101; `verify-b2` 94/94; `verify-b1` 42/42; `check-ui` OK;
`ui-audit` lang=ms halaman vendor pesanan/butiran/picking/label/ibu bapa/tetapan/serah/POS/papan pemuka/refund/bayaran/laporan × 2 lebar: 0 isu;
Playwright 1366px: pentadbir → senarai pesanan sebenar → butiran → "Sedang disediakan" → "Sedia diambil" → tetapan disimpan; juruwang → skrin serah
→ taip no. pendek + Enter → modal tunai → Enter → modal serah → Sahkan → "Semua barang telah diserahkan · Ibu bapa telah dimaklumkan" — 0 ralat konsol):
- **Juruwang koperasi lain tidak boleh serah:** juruwang VENDOR02 (Kavitha) cari / terima tunai / serah pesanan SK Taman Melati → 404 (melalui
  koperasi 1 dan koperasi 3), tiada perubahan; pengurus V02 (koperasi 2 sahaja) → pesanan koperasi 3 → 404; juruwang V01 → koperasi 3 → 404
- **Kod salah 5 kali → dikunci:** tanpa kod / salah → "Baki cubaan 4…"; ke-5 → dikunci; kod betul selepas dikunci tetap ditolak; juruwang buka
  kunci → 403; pentadbir buka → cubaan 0 + log; kod betul → collected, cara E
- **VENDOR01 ↔ VENDOR02:** list/get/set-status/bulk/picking/label/settings/customers koperasi lain → 404 atau tiada data; pukal dengan id V02 →
  `skipped`; label pesanan V01 melalui koperasi V02 → kosong; QR V01 di carian V02 → tiada
- **Ibu bapa A ↔ B:** B tidak nampak rekod serahan pesanan A (404); senarai ibu bapa V01 hanya Siti (anak SK Taman Melati sahaja, tiada Faris di SJKC);
  nota V01 & V02 untuk Siti berasingan; nota untuk ibu bapa tanpa anak di koperasi → 404
- Stok: serah 3/4 → `qty_on_hand −3` DAN `qty_reserved −3`, pergerakan `collect −3` (ref pesanan, juruwang, `balance_after`); baki → collected
- **Serentak:** 3 serahan baki yang sama serentak (curl_multi) → tepat satu berjaya, stok ditolak sekali
- Tunai: baris `payments` cash + `received_by` → muncul dalam `vendor/payments/list`; kali kedua → "sudah dibayar"; online → ditolak
- E-mel: `order_ready` sekali sahaja (ready → preparing → ready tidak berulang) dengan anak/kelas/waktu operasi; `order_collected` setiap serahan;
  `order_paid` selepas PaymentSettlement `paid` (panggilan balik berganda → satu e-mel)

Keputusan kecil (dicatat dalam PELAN 7, 10.2, 18.7, 18.9, 18.10, 18.14):
- Dikunci = `pickup_attempts ≥ 5` (tiada lajur `pickup_locked`); buka kunci = laluan baharu `vendor/orders/unlock-code` (admin + pengurus)
- Cara serahan direkod dari cara carian (`found_by`) — pelayan menyemak ia aktif dalam tetapan; kod 4 digit → E
- Terima tunai tidak menukar status pesanan, tetapi mengosongkan `expires_at` (cron tidak meluputkan pesanan yang sudah dibayar)
- `vendor/settings/get` dibenarkan juruwang & pengurus (POS/skrin serah) tanpa `alert_emails`; `save` = pentadbir sahaja
- E-mel `order_ready` dihantar sekali setiap pesanan; `order_collected` setiap serahan (separa juga) — ibu bapa cepat mengesan penyalahgunaan
- Picking list & label: darjah/kelas dari snapshot `order_items`, susunan ikut `grade_levels.sort_order`

Tertangguh / peralihan:
- POS kaunter (`vendor/pos/*`) masih mock — B10 (guna semula `ReceiveCashPaymentJob` / `HandoverOrderJob`); `MockOrders` kekal untuk POS mock
- Refund (`vendor/refunds/*`) masih mock — B11; butang refund di butiran pesanan sebenar membuka halaman refund mock
- Resit PDF tidak dibina — slip ibu bapa & label beg dicetak terus dari pelayar (CSS cetak)
- Picking list mengira baris setiap anak setiap pesanan ("2 murid" bila seorang anak ada dua pesanan) — sama seperti mock U3b

### B10 — POS kaunter (28 Okt 2026)

Dibuat:
- **Core:** `core/PosAuth.php` — juruwang PIN dalam sesi panel (`$_SESSION['pos_user_id'][cooperative_id]`), disemak semula setiap permintaan;
  `requireCashier` (403 `pos_login_required`), `counterUserId` (juruwang PIN atau pengguna panel — skrin serah)
- **Job (`jobs/Pos/`, 15 + 3 pembantu):** pembantu `PosCashiers`, `PosShifts` (PosShift + tunai pesanan store), `PosSales` (complete, recheckPayment,
  receipt); `GetPosSessionJob`, `PosLoginJob` (atas `PosPinLoginJob` B2), `OpenShiftJob`, `CloseShiftJob`, `ShiftReportJob`, `ListShiftSalesJob`,
  `PosCatalogJob`, `PosCalculateCartJob` (= `pos-cart.js`), `PosCheckoutJob`, `PosOnlinePaymentJob`, `PosPaymentStatusJob`, `CancelPosPaymentJob`,
  `GetPosReceiptJob` · `jobs/Promotion/` `ListPromotionsJob`, `SavePromotionJob`, `DeletePromotionJob` (+ `Promotions::find/shape/STATE_SQL`)
- **API (15 fail, tiada SQL):** `vendor/pos/{session,pin-login,lock,catalog,shift-open,shift-summary,shift-close,checkout,payment-status,cancel-payment,
  sales,receipt}`, `vendor/promotions/{list,save,delete}`
- **Ubah:** `PaymentSettlement` (pesanan `channel pos` dibayar → `PosSales::complete`, tiada e-mel), `OrderNumbers::nextPos` (`POS-YYMMDD-001`) +
  kod pendek 3/4/5 digit, `CheckoutQuote::ordersBlocked` awam (banner "koperasi tutup" POS), `ValidateCooperativeJob` (+ address, phone, logo_path),
  `vendor/handover/receive-cash|collect` merekod juruwang PIN (`PosAuth::counterUserId`), config `pos_payment_expiry_minutes` (15)
- **JS:** `Api.livePrefixes` + `vendor/pos/`, `vendor/promotions/`; `mock/vendor-pos.js` dibuang; promosi & `MockFinance.settings` dibuang dari
  `mock/vendor-finance.js`; `pos.js` — juruwang dari `session.cashiers` (PIN dikunci ditanda), promosi dari katalog, baris "Tunai pesanan online";
  `helpers.js` `hideModal` menunggu animasi buka (jawapan pelayan sebenar lebih pantas daripada animasi → modal tunai tersangkut); `pos-cart.js` komen;
  `asset_version` → 11
- **Halaman:** `vendor/pos/index.php` — teks "PIN demo 1234" dibuang
- 3 kunci bahasa baharu × 4 (`pos.pin_locked_short`, `pos.order_cash`, `pos.online_qr_unsupported`), 1 dibuang (`pos.pin_demo`) — jumlah 1839
- **DB:** tiada perubahan jadual / seed (PIN demo seed sudah Argon2id sejak B2)
- `tools/verify-b10.php` (97 semakan; pelayan 4 pekerja + gateway palsu; node untuk semakan `pos-cart.js`; jadual diubah dipulihkan)

Disahkan (MariaDB 10.11, PHP 8.3, pemasangan baharu schema + seed + migrasi — `verify-b10` **97 lulus, 0 gagal**; `verify-b9` 106/106; `verify-b8` 95/95;
`verify-b7` 92/92; `verify-b6` 85/85; `verify-b5` 59/59; `verify-b4` 118/118; `verify-b3` 101/101; `verify-b2` 94/94; `verify-b1` 42/42; `check-ui` OK;
`ui-audit` lang=ms `vendor/pos/*` & promosi × 2 lebar: 0 isu; Playwright 1366px: juruwang Kasim → panel → POS → pilih juruwang + PIN → buka syif RM100 →
imbas `2*9555000000017` + Enter → F2 → RM10 + Enter → resit POS-…-003 baki RM6.40 → tutup syif RM103.60 → beza RM0.00 — 0 ralat konsol):
- **Juruwang ditolak untuk katalog/tetapan (403):** `products/list|save`, `inventory/list`, `settings/save`, `promotions/list|save`, `staff/list`;
  POS (`session`, `catalog`) & `settings/get` → 200; laluan POS tanpa PIN → 403 `pos_login_required`
- **PIN:** salah → baki cubaan; 5 salah → dikunci (423), PIN betul tetap ditolak, senarai menanda "PIN dikunci"; pentadbir tetapkan PIN baharu → dibuka;
  hash `$argon2id$`; log `pos_login`
- **Jumlah sama dengan `pos-cart.js`:** `PosCalculateCartJob::totals` vs `posTotals` (node) — pembundaran %, cukai 6.5%, manual > baki
- **Pelayan mengira semula:** harga pelayar diabaikan; cukai 6% selepas diskaun; kupon JIMAT5 (min RM50) + manual; automatik 3% ≥ RM200; stok, had beli
  (baris berganda dicantum), varian wajib, produk/varian V02, qty 0, troli kosong, syif bukan milik juruwang → ditolak tanpa apa-apa disimpan
- **Tunai:** `collected`, `payments` cash `received_by` juruwang, `pos_sale` −qty atas nama juruwang, log pending_payment → collected, `pos_sale`;
  POS tidak dalam `vendor/orders/list` tetapi dibuka `vendor/orders/get` & muncul dalam `vendor/payments/list`
- **QR online (gateway palsu):** `pending_payment` + `reserve` + bil; `payment-status` bertanya gateway → paid → release + `pos_sale`, log `system`, tiada
  e-mel; webhook berganda → satu `pos_sale`; batal → cancelled + tahanan & guna promosi dilepas + bil failed; batal selepas dibayar → diselesaikan;
  cron luput → expired; iPay88 → `online_qr_unsupported`, jualan dibatal serta-merta; tutup syif ditolak semasa QR pending
- **Syif:** satu terbuka setiap juruwang; kunci kaunter → syif kekal; jangkaan = permulaan + tunai POS + tunai pesanan store (juruwang PIN di skrin
  serah); tutup → variance −50, jangkaan dibekukan, juruwang dilog keluar; syif juruwang lain → 404
- **Serentak:** dua kaunter jual unit terakhir serentak → tepat satu berjaya, stok 0
- **VENDOR01 ↔ VENDOR02:** session/catalog/pin-login koperasi lain → 404; resit/status/batal/jualan/ringkasan syif V01 melalui koperasi V02 → 404;
  produk V01 di kaunter V02 → ditolak; no. resit berasingan setiap koperasi; sesi PIN koperasi 3 tidak sah di koperasi 2; promosi koperasi lain → 404
- **Ibu bapa A ↔ B:** sesi store → laluan POS 401; jualan POS tiada dalam pesanan A atau B; B tidak boleh buka pesanan A

Keputusan kecil (dicatat dalam PELAN 9, 10.2, 11, 18.10, 18.11, 18.14):
- `vendor/pos/session` + `cashiers` dan `vendor/pos/catalog` + `promotions` — juruwang tidak boleh memanggil `vendor/staff/list` / `vendor/promotions/list`
- `short_code` POS dijana sama seperti store (bukan `P001` — akan habis); no. resit `POS-YYMMDD-001` dikekalkan
- `used_count` promosi naik semasa jualan dibuat (sama seperti store) dan dilepas bila QR dibatal/luput
- QR luput 15 minit (`pos_payment_expiry_minutes`) melalui cron B8; semakan gateway dari kaunter maks sekali / 10 saat setiap jualan
- Bil gagal dicipta → jualan QR dibatal serta-merta (juruwang boleh terima tunai)
- Tunai pesanan store di kaunter masuk jangkaan laci syif juruwang PIN yang menerimanya
- Tiada `FindByBarcodeJob` / `ReceivePosCashForOrderJob` — padanan barcode di pelayar; tunai pesanan online = `ReceiveCashPaymentJob` (B9)

Tertangguh / peralihan:
- Refund jualan POS & refund tunai ditolak dari jangkaan laci → B11 (`vendor/refunds/*` masih mock; `MockOrders` & `MockFinance.gateways` kekal)
- QR kaunter untuk gateway borang POST (iPay88) — perlu halaman bayaran awam tanpa sesi ibu bapa (kemudian)
- Laporan Z dicetak dari pelayar (CSS cetak) — tiada PDF

---

## Log perubahan pengguna

> Setiap kali pengguna mengubah keperluan: catat di sini **dan** kemas kini PELAN.md +
> PROMPT-SESSION.md dalam commit yang sama.

| Tarikh | Perubahan daripada pengguna | Fail dikemas kini |
|---|---|---|
| 24 Okt 2026 | Keperluan asal: owner/vendor/user, langganan bulanan/tahunan, koperasi tambahan, modul add-on, 4 bahasa, kit sekolah dengan uncheck, nama anak & darjah, e-mel amaran vendor, tunai/online, folder template | PELAN |
| 24 Okt 2026 | POS ialah modul vendor (murid datang ke kaunter) | PELAN 5.2 |
| 24 Okt 2026 | Vendor pilih gateway sendiri; wang terus ke akaun vendor | PELAN 13 |
| 24 Okt 2026 | Lalai web responsif; Flutter ialah modul add-on | PELAN 5.3 |
| 24 Okt 2026 | Beli ala carte **atau** kit; ambil barang jangan sulit, ada pilihan; murid boleh ambil barang yang ibu bapa bayar | PELAN 5.4, 7 |
| 24 Okt 2026 | Repo projek `kabuto86/Koperasi_sekolah`; setiap kerja siap di-push | CLAUDE.md |
| 24 Okt 2026 | UI dahulu → backend & API → Flutter selepas desktop siap; prompt session per modul; simpan dalam `DOC/` | PELAN 3, PROMPT-SESSION, CLAUDE.md |
