# Proses — Apa Yang Telah Selesai

> **Satu-satunya sumber kebenaran tentang di mana kerja terhenti.**
> Kemas kini fail ini di HUJUNG setiap session, sebelum commit & push.
> Reka bentuk: [PELAN.md](PELAN.md) · Prompt kerja: [PROMPT-SESSION.md](PROMPT-SESSION.md)

**Status keseluruhan:** Peringkat 1 · kerangka UI, panel owner, panel vendor (U3a–U3c), POS kaunter (U4), store ibu bapa (U5) & modul add-on (U6) siap
**Session terakhir siap:** U6 — UI modul add-on
**Session seterusnya:** **U7 — Semakan UI menyeluruh**

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
| U6 | UI modul add-on | ✅ | 25 Okt 2026 | (lihat butiran) | 9 halaman vendor, banner & slot ambil di store, 195 kunci baharu, ujian kebocoran lulus, `DOC/screenshots/U6/` |
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
