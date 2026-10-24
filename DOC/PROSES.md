# Proses — Apa Yang Telah Selesai

> **Satu-satunya sumber kebenaran tentang di mana kerja terhenti.**
> Kemas kini fail ini di HUJUNG setiap session, sebelum commit & push.
> Reka bentuk: [PELAN.md](PELAN.md) · Prompt kerja: [PROMPT-SESSION.md](PROMPT-SESSION.md)

**Status keseluruhan:** Pelan siap · belum ada kod
**Session terakhir siap:** S0 — Pelan & dokumen
**Session seterusnya:** **U1 — Kerangka UI**

Petunjuk: ⬜ belum mula · 🟡 sedang berjalan · ✅ siap · ⛔ tersekat

---

## Peringkat 0 — Pelan

| Session | Tajuk | Status | Tarikh siap | Commit | Nota |
|---|---|---|---|---|---|
| S0 | Pelan, CLAUDE.md, DOC/ | ✅ | 24 Okt 2026 | (commit pertama) | PELAN, PROSES, PROMPT-SESSION |

## Peringkat 1 — UI desktop/web (data mock)

| Session | Tajuk | Status | Tarikh siap | Commit | Nota |
|---|---|---|---|---|---|
| U1 | Kerangka UI: layout, Bootstrap, `t()` 4 bahasa, `api.js` mock | ⬜ | | | |
| U2 | UI Owner | ⬜ | | | |
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
