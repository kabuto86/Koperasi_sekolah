# Proses — Apa Yang Telah Selesai

> **Satu-satunya sumber kebenaran tentang di mana kerja terhenti.**
> Kemas kini fail ini di HUJUNG setiap session, sebelum commit & push.
> Reka bentuk: [PELAN.md](PELAN.md) · Prompt kerja: [PROMPT-SESSION.md](PROMPT-SESSION.md)

**Status keseluruhan:** Peringkat 1 · kerangka UI & panel owner siap
**Session terakhir siap:** U2 — UI Owner
**Session seterusnya:** **U3a — UI Vendor (bahagian 1)**

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
| U3a | UI Vendor — dashboard, koperasi, produk, inventori, pembekal | ⬜ | | | |
| U3b | UI Vendor — kit sekolah, pesanan, serah barang, ibu bapa | ⬜ | | | |
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
