# Proses — Apa Yang Telah Selesai

> **Satu-satunya sumber kebenaran tentang di mana kerja terhenti.**
> Kemas kini fail ini di HUJUNG setiap session, sebelum commit & push.
> Reka bentuk: [PELAN.md](PELAN.md) · Prompt kerja: [PROMPT-SESSION.md](PROMPT-SESSION.md)

**Status keseluruhan:** Peringkat 1 · kerangka UI, panel owner & vendor bahagian 1–2 siap
**Session terakhir siap:** U3b — UI Vendor bahagian 2
**Session seterusnya:** **U3c — UI Vendor (bahagian 3): bayaran, refund, promosi, kakitangan, laporan, tetapan, langganan**

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
| U3c | UI Vendor — bayaran, refund, promosi, kakitangan, laporan, tetapan, langganan | ⬜ | | | |
| U4 | UI POS kaunter | ⬜ | | | |
| U5 | UI Store ibu bapa — Template 1 | ⬜ | | | |
| U6 | UI modul add-on | ⬜ | | | |
| U7 | Semakan UI menyeluruh | ⬜ | | | |

## Peringkat 2 — Backend & API

| Session | Tajuk | Status | Tarikh siap | Commit | Nota |
|---|---|---|---|---|---|
| B1 | Skema, seed, core | ⬜ | | | |
| B2 | Auth owner, vendor, ibu bapa + 2FA + throttle | ⬜ | | | |
| B3 | Owner: pelan, harga koperasi, modul, template, vendor | ⬜ | | | |
| B4 | Vendor: koperasi, produk, kategori, varian, inventori, pembekal | ⬜ | | | |
| B5 | Kit keperluan sekolah | ⬜ | | | |
| B6 | Store ibu bapa: daftar, anak, katalog, kit, troli | ⬜ | | | |
| B7 | Checkout tunai, pesanan, queue e-mel | ⬜ | | | |
| B8 | Payment gateway + webhook | ⬜ | | | |
| B9 | Pengurusan pesanan & serah barang | ⬜ | | | |
| B10 | POS kaunter | ⬜ | | | |
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
- Store Peringkat 1: `store/index.php?school={slug}&page=...`; URL `/{slug}/` dalam B6
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
