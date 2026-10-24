Sila guna bahasa melayu bukan indonesia ketika menjawab
Sila guna bahasa melayu untuk display perkataan dalam sistem atau website
Sila guna bahasa inggeris untuk filename dan coding

Jangan pakai inline CSS, pakai Bootstrap sahaja

Sentiasa pakai job class, jangan pakai God Controller

Untuk bahasa pengaturcara PHP sila gunakan vanila code untuk senang faham, jangan guna laravel struktur

## Projek — KoperasiKu SaaS (Koperasi Sekolah)

SaaS multi-tenant: satu kod, satu pangkalan data, banyak vendor. Owner (platform),
vendor (pengendali koperasi, boleh ada banyak koperasi/sekolah) dan ibu bapa (pembeli).

Sebelum menulis sebarang kod, baca tiga fail ini dahulu:

- `DOC/PELAN.md` — keputusan reka bentuk, modul, skema jadual (rujukan tetap)
- `DOC/PROSES.md` — session mana sudah siap, mana seterusnya
- `DOC/PROMPT-SESSION.md` — prompt untuk setiap session; kerja dijalankan ikut prompt ini

## Susunan kerja (WAJIB)

1. **UI desktop/web dahulu** (U1–U7) — Bootstrap + data mock melalui `assets/js/api.js`
2. **Backend & API** (B1–B15) — sambung UI yang sudah ada
3. **Apps Flutter** (F1–F4) — hanya selepas semua bahagian desktop siap

## Peraturan dokumen (WAJIB)

- Setiap perubahan atau arahan baharu daripada pengguna → kemas kini **`DOC/PELAN.md`,
  `DOC/PROMPT-SESSION.md` dan `DOC/PROSES.md`** dalam commit yang sama, dan catat dalam
  "Log perubahan pengguna" di `DOC/PROSES.md`.
- Setiap session yang siap → tandakan dalam `DOC/PROSES.md`, commit, dan **push ke
  `kabuto86/Koperasi_sekolah`**. Kerja yang tidak di-push dianggap belum siap.

## Peraturan keselamatan paling penting

- `vendor_id` datang dari **sesi**, tidak pernah dari `$_POST` / `$_GET`. Setiap pertanyaan
  menapis `vendor_id`.
- `customer_id` (ibu bapa) juga dari sesi/token sahaja.
- `cooperative_id` ialah skop perniagaan — boleh dari input, tetapi mesti disahkan milik
  vendor semasa (`ValidateCooperativeJob`).
- Tiada SQL dalam `api/` — semua dalam job class.
- Harga, diskaun dan stok sentiasa dikira semula di pelayan.

Pangkalan data projek ini ialah `koperasi_saas`. Pangkalan data `tuisyen` dan `pos_saas`
pada pelayan MySQL yang sama milik projek lain — jangan sentuh.
