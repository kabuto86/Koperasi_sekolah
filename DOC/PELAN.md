# Pelan Induk — KoperasiKu SaaS (Koperasi Sekolah)

> **Dokumen rujukan tetap.** Baca fail ini SEBELUM menulis sebarang kod dalam
> mana-mana session. Keputusan di sini sudah disahkan oleh pengguna — jangan ubah
> tanpa bertanya. Status semasa: [PROSES.md](PROSES.md). Prompt kerja:
> [PROMPT-SESSION.md](PROMPT-SESSION.md).

Kemas kini terakhir: 26 Oktober 2026 (B1 — Skema, seed & core)
Persekitaran sasaran: XAMPP · PHP 8.2 · MariaDB 10.4+ / MySQL 8 · Bootstrap 5

---

## 1. Apa sistem ini

**Satu SaaS untuk koperasi sekolah.** Satu kod, satu pangkalan data, banyak vendor.

```
OWNER (platform — pemilik sistem)
  └── vendor              <- pelanggan SaaS, bayar langganan bulanan/tahunan
        └── cooperative   <- satu koperasi = satu sekolah (setiap satu dicaj)
              └── staff   <- pengurus koperasi, juruwang
CUSTOMER (ibu bapa / penjaga — akaun global)
  └── children            <- nama anak, sekolah, darjah/tingkatan, kelas
```

| Platform | Siapa | Peranti |
|---|---|---|
| **Owner** | Pemilik sistem | Desktop (web) |
| **Vendor** | Pengendali koperasi | Desktop (web) + POS kaunter |
| **User** | Ibu bapa | Web responsif (lalai) · **Apps Flutter** (modul add-on) |

Ibu bapa membeli barangan koperasi **melalui sekolah anak mereka**. Pesanan
mencatat **nama anak, darjah dan kelas**, dan barang diambil di koperasi sekolah itu.

## 2. Konvensyen projek (dari CLAUDE.md — WAJIB)

- Bahasa Melayu (bukan Indonesia) untuk semua teks yang dipaparkan
- Bahasa Inggeris untuk nama fail, fungsi, pemboleh ubah, lajur DB, kunci terjemahan
- **Bootstrap 5 sahaja — tiada inline CSS** (tiada atribut `style=""`, tiada `<style>` dalam halaman).
  Gaya tambahan hanya dalam `assets/css/app.css` atau `templates/{nama}/css/`
- **PHP vanila** — tiada Laravel, tiada framework, tiada Composer. Kod mudah dibaca:
  fungsi pendek, nama jelas, tiada magic method, tiada closure bersarang, tiada sintaks pelik
- **Satu job class = satu kerja.** Fail `api/` nipis (10–15 baris):
  semak sesi → sahkan input → panggil satu job → pulangkan JSON
- AJAX dengan `fetch()` vanila. Tiada jQuery, tiada React/Vue
- MySQL/MariaDB dengan PDO prepared statement sahaja

## 3. Susunan pembinaan — UI dahulu

**Diputuskan pengguna, 24 Okt 2026:**

1. **Peringkat 1 — UI desktop/web** (U1–U7). Semua skrin dibina dahulu dengan data contoh.
2. **Peringkat 2 — Backend & API** (B1–B15). Sambung skrin yang sudah ada kepada data sebenar.
3. **Peringkat 3 — Apps Flutter** (F1–F4). Hanya bermula selepas **semua** bahagian desktop siap.

### 3.1 Bagaimana UI dibina tanpa backend

- Halaman ialah fail `.php` (supaya `include` header/footer dan `t()` boleh diguna sejak awal),
  tetapi **tidak menyentuh pangkalan data** dalam Peringkat 1.
- Semua data datang melalui **satu fail**: `assets/js/api.js`. Dalam Peringkat 1, `api.js`
  memulangkan data dari `assets/js/mock/*.js` dengan bentuk yang sama seperti API sebenar:
  `{ ok: true, data: ... }`.
- Dalam Peringkat 2, hanya `api.js` ditukar kepada `fetch('/api/v1/...')`. **Halaman tidak
  ditulis semula.** Sebab itu setiap halaman WAJIB memanggil `Api.get()` / `Api.post()` dan
  tidak pernah membaca `mock/*.js` terus.
- Bentuk data mock ialah **kontrak API**. Ia didokumenkan dalam bahagian 10.3 dan mesti
  dipatuhi oleh backend kelak.

## 4. Peranan

| Peranan | Platform | Boleh | Tidak boleh |
|---|---|---|---|
| `owner` | Owner | Semua tetapan platform, vendor, pelan, modul, harga, template, bahasa | Mengubah data jualan vendor |
| `owner_staff` | Owner | Sokongan, lihat vendor, invois | Harga, pelan, tetapan platform |
| `vendor_admin` | Vendor | Semua dalam vendornya, semua koperasinya, langganan | Melihat vendor lain |
| `coop_manager` | Vendor | Koperasi yang diberi: produk, stok, kit, pesanan, laporan | Langganan, gateway, kakitangan |
| `cashier` | Vendor | POS, skrin serah barang | Katalog, laporan penuh, tetapan |
| `customer` | User | Beli, urus anak, lihat rekod sendiri | Apa-apa data orang lain |

> Kebenaran disemak **di dalam setiap endpoint**. Menyorok butang di UI bukan kawalan keselamatan.

**Menu ikut peranan (U7).** `owner/_layout/menu.php` & `vendor/_layout/menu.php` — item ada `'roles'` (tiada = semua peranan);
`core/PanelRole.php` menapis menu (`filterMenu`); seksyen modul add-on vendor disembunyikan untuk juruwang. Peringkat 1: peranan dari
cookie demo `mock_role` (dibuang dalam B2). **Sejak B2:** `PanelRole::current()` membaca sesi (`OwnerAuth` / `VendorAuth`);
`_layout/header.php` & `pos-header.php` memanggil `OwnerAuth::requirePage($activeMenu)` / `VendorAuth::requirePage($activeMenu)` sebelum
output — belum log masuk → `login.php`; menu tidak dibenarkan (`PanelRole::canOpen`, ikut `'roles'` item menu yang sama `key` dengan
`$activeMenu`; `module.*` = admin & pengurus) → halaman pertama yang dibenarkan (`PanelRole::firstPage`). API: `requireRole([...])` → 403.

Owner: `owner_staff` nampak papan pemuka, vendor, koperasi, langganan & invois, log audit sahaja; selebihnya `owner` sahaja.
**Sejak B3** API menguatkuasakan perkara sama (senarai laluan dalam 18.14); `owner_staff` boleh menambah nota vendor tetapi tidak boleh
menggantung / mengaktifkan vendor (butang tidak dipapar, API 403).

| Menu | `vendor_admin` | `coop_manager` | `cashier` |
|---|---|---|---|
| Papan pemuka, koperasi, produk, inventori, pembekal, kit, pesanan, ibu bapa | ✔ | ✔ | — |
| Serah barang, POS kaunter | ✔ | ✔ | ✔ |
| Refund, promosi, laporan, log aktiviti, modul add-on | ✔ | ✔ | — |
| Bayaran (gateway), kakitangan, tetapan, langganan & bil | ✔ | — | — |

## 5. Modul

### 5.1 Owner — `/owner/`

| # | Modul | Isi |
|---|---|---|
| 1 | Dashboard | MRR, vendor aktif/trial/tamat, koperasi, pesanan platform, langganan hampir tamat |
| 2 | Vendor | Senarai, butiran, lulus, gantung, aktifkan semula, nota. **Tiada "log masuk sebagai vendor"** |
| 3 | Koperasi | Semua koperasi semua vendor (baca sahaja), status, template, modul aktif |
| 4 | Pelan | Nama, harga bulanan, harga tahunan, had produk/kakitangan/koperasi, ciri |
| 5 | Harga koperasi tambahan | Peringkat `plan_coop_tiers` — lalai harga sama seperti koperasi pertama |
| 6 | Modul add-on | Katalog modul, harga bulanan/tahunan, aktif/tidak, keterangan 4 bahasa |
| 7 | Template frontend | Senarai template, pratonton, harga (Template 1 percuma) |
| 8 | Langganan & invois | Semua langganan, invois draft/issued/paid, tanda bayar manual |
| 9 | Gateway | Driver yang dibenarkan untuk vendor + gateway owner untuk kutip yuran |
| 10 | Bahasa & terjemahan | ms, en, zh, ta — sunting nilai, kunci hilang |
| 11 | Darjah/tingkatan | Senarai master (PRA, D1–D6, T1–T6) dan nama 4 bahasa |
| 12 | Templat e-mel | Pesanan baharu, resit, sedia diambil, telah diambil, invois — per bahasa |
| 13 | Pengumuman vendor | Mesej kepada semua/sebahagian vendor |
| 14 | Log audit | Tindakan sensitif seluruh platform |
| 15 | Tetapan platform | Nama, SMTP, SST, tempoh trial, tempoh tangguh |
| 16 | Kakitangan owner | `owner_staff`, 2FA |

### 5.2 Vendor — `/vendor/` (pakej asas)

| # | Modul | Isi |
|---|---|---|
| 1 | Dashboard | Jualan hari ini, pesanan menunggu disediakan / diambil, stok rendah, pemilih koperasi |
| 2 | Koperasi saya | Tambah/sunting koperasi. Wajib: **nama sekolah**, kod sekolah KPM, jenis (SK/SJKC/SJKT/SMK/SMKA/lain), negeri, daerah, alamat. Slug URL, logo, template, waktu operasi, kaedah bayaran (tunai/online/kedua-dua) |
| 3 | Produk & kategori | Nama, kategori, harga, kos, SKU, barcode, gambar, varian (saiz, warna), had beli, darjah sasaran, nama terjemahan (pilihan) |
| 4 | Inventori | Stok per koperasi, stok masuk, pelarasan, stock take, amaran stok rendah, sejarah pergerakan |
| 5 | Pembekal & PO | Senarai pembekal, pesanan belian, terima barang (stok masuk automatik) |
| 6 | **Kit keperluan sekolah** | Satu borang per darjah/tingkatan per tahun: item, kuantiti, wajib/pilihan, nota. Salin dari tahun lepas |
| 7 | Pesanan online | Senarai, tapis darjah/kelas/status/tarikh, butiran, tukar status, picking list ikut kelas, **label beg** (nama anak + kelas + no.) |
| 8 | **Serah barang** | Skrin kaunter untuk menyerahkan pesanan — lihat bahagian 7 |
| 9 | POS kaunter | Jual terus kepada murid/walk-in, imbas barcode, tunai/online, resit, syif & tunai laci |
| 10 | Ibu bapa & murid | Senarai pembeli & anak yang memilih koperasi ini (baca sahaja + nota) |
| 11 | Bayaran | Pilih gateway & isi kunci API sendiri, tunai, rekod bayaran |
| 12 | Pemulangan & refund | Pulang barang, pulang stok, rekod refund |
| 13 | Promosi asas | Kupon, diskaun peratus/tetap, tempoh |
| 14 | Kakitangan | Tambah pengurus/juruwang, akses koperasi mana |
| 15 | Laporan | Jualan harian/bulanan, ikut produk, ikut darjah, ikut kelas, untung, stok, tunai vs online, CSV |
| 16 | Tetapan | **E-mel amaran pesanan baharu** (boleh beberapa), bahasa lalai, resit, cara ambil barang (A–E), tempoh luput pesanan tunai |
| 17 | Langganan & bil | Pelan, koperasi, modul, template yang dilanggan, invois, bayar online, tambah koperasi/modul |
| 18 | Log aktiviti | Siapa buat apa |

### 5.3 Modul add-on (owner urus katalog; vendor langgan **per koperasi**)

| Kod | Modul | Isi |
|---|---|---|
| `news` | Berita & pengumuman | Pos berita kepada ibu bapa koperasi itu, paparan di store + e-mel pilihan |
| `clearance` | Jualan murah / clearance | Harga khas, tempoh, lencana "Clearance", halaman khas |
| `calendar` | Kalendar cuti | Cuti sekolah, cuti koperasi — tempahan ditutup automatik pada hari tutup |
| `school_opening` | Pembukaan sekolah | Musim pre-order kit, tarikh tutup tempahan, slot ambil barang |
| `mobile_app` | Apps Flutter | Ibu bapa guna apps; API token + push notification |
| `template_premium` | Template premium | Template frontend selain Template 1 |
| `whatsapp_sms` | Notifikasi WhatsApp/SMS | Mesej "sedia diambil" / "telah diambil" |
| `advanced_report` | Laporan lanjutan | Perbandingan tahun, analisis kit |
| `membership` | Keahlian koperasi | Ahli, syer, dividen (kemudian) |

- Owner boleh **menambah modul baharu**: satu baris `modules` + folder `modules/{code}/`.
- Akses disemak di pelayan: `ModuleAccess::has($cooperativeId, 'news')` dalam setiap endpoint modul.
  UI menyembunyikan menu modul yang tidak dilanggan dan memaparkan butang "Langgan".

### 5.4 User (ibu bapa) — `/{slug-sekolah}/` melalui template

**Pendaftaran** — nama penuh, no. telefon (format Malaysia, unik), e-mel (unik), kata laluan +
sahkan kata laluan, hubungan (ibu/bapa/penjaga), bahasa pilihan, **persetujuan PDPA** (wajib
ditanda), pengesahan e-mel. **Tiada no. IC** — tidak diperlukan dan berisiko di bawah PDPA.

**Anak** — nama, sekolah (koperasi), darjah/tingkatan, nama kelas, tahun. Seorang ibu bapa boleh
ada beberapa anak di sekolah berbeza. Setiap Januari sistem mencadangkan naik darjah; ibu bapa sahkan.

**Membeli — dua cara, boleh dicampur dalam satu troli:**

| Cara | Aliran |
|---|---|
| **Ala carte** | Katalog → tapis kategori/darjah → tambah barang satu-satu |
| **Kit sekolah** | Pilih anak → **Kit Darjah X** dipapar dengan semua item **siap ditanda** → ibu bapa **uncheck** yang tidak perlu / ubah kuantiti → masuk troli. Boleh tambah ala carte selepas itu |

Setiap item dalam troli ditanda **untuk anak mana**. Pesanan menyimpan salinan nama anak, darjah
dan kelas.

**Checkout** — semak item ikut anak → pilih **siapa ambil** (murid sendiri / ibu bapa / wakil + nama)
→ pilih kaedah bayaran (yang dibenarkan vendor: tunai di kaunter / online).

**Rekod pembelian** — sejarah penuh semua pesanan, status, butiran item ikut anak, resit boleh
cetak/PDF, tapis ikut anak & tahun. Rekod tidak pernah dipadam (hanya status berubah).

**Lain-lain** — lupa kata laluan (token e-mel), tukar bahasa, profil, tukar kata laluan, padam akaun (PDPA).

## 6. Aliran pesanan

```
Ibu bapa pilih sekolah -> pilih anak
 -> [Kit Darjah 3, uncheck yang tak perlu]  dan/atau  [ala carte]
 -> troli (setiap item ditanda untuk anak mana)
 -> checkout: siapa ambil (murid / ibu bapa / wakil), kaedah bayaran
 -> [online] gateway vendor -> callback disahkan pelayan -> PAID
    [tunai]  UNPAID_CASH (stok ditahan sehingga tarikh luput)
 -> e-mel kepada vendor (amaran pesanan baharu)
    e-mel kepada ibu bapa (no. pesanan pendek + QR + slip boleh cetak)
 -> vendor: PREPARING (picking list ikut kelas, label beg) -> READY
    e-mel kepada ibu bapa: "Sedia untuk diambil"
 -> di koperasi: kakitangan cari pesanan (bahagian 7) -> [tunai: bayar di POS]
 -> COLLECTED -> e-mel kepada ibu bapa:
    "Barang telah diambil oleh Aisyah (4 Bestari) pada 10:15 pagi"
```

| Status | Maksud |
|---|---|
| `pending_payment` | Menunggu bayaran online |
| `paid` | Dibayar online |
| `unpaid_cash` | Akan dibayar tunai semasa ambil |
| `preparing` | Sedang disediakan |
| `ready` | Sedia diambil |
| `partially_collected` | Sebahagian item sudah diambil (stok habis / belum siap) |
| `collected` | Selesai |
| `cancelled` · `expired` · `refunded` | Tamat tanpa jualan / dipulangkan |

## 7. Ambil barang — mudah, ada pilihan

**Diputuskan pengguna, 24 Okt 2026:** proses ambil **tidak boleh sulit**. Selalunya ibu bapa pesan
dan bayar online, tetapi **murid sendiri** yang mengambil barang di koperasi. Murid sekolah rendah
tiada telefon dan tidak ingat kod panjang.

Vendor memilih cara yang aktif (boleh lebih daripada satu) dalam Tetapan:

| Cara | Sesuai untuk | Di kaunter |
|---|---|---|
| **A. Cari nama murid / kelas** (lalai) | Murid datang sendiri | Taip "Aisyah" atau pilih kelas "4 Bestari" → senarai pesanan READY → tekan **Serah** |
| **B. No. pesanan pendek** | Murid bawa nota | Taip `A123` (4–5 aksara, unik per koperasi) |
| **C. Imbas QR** | Ibu bapa / wakil dengan telefon | Imbas QR dari e-mel / halaman pesanan (kamera atau pengimbas USB) |
| **D. Slip bercetak** | Murid sekolah rendah | Ibu bapa cetak slip (nama anak, kelas, no., QR), letak dalam beg |
| **E. Kod 4 digit** (pilihan) | Barang bernilai tinggi | Selepas A–D, minta kod yang ibu bapa beri kepada anak |

Supaya mudah **dan** masih selamat:

- Skrin serah memaparkan **nama murid, kelas, nama ibu bapa, senarai barang** — kakitangan semak
  secara lisan, seperti amalan koperasi sekarang.
- **Setiap serahan direkod** (`order_pickups`): cara, siapa ambil (murid/ibu bapa/wakil), nama,
  masa, kakitangan, item & kuantiti — bukti jika ada pertikaian.
- **Ibu bapa terus dimaklumkan** bila barang diambil — penyalahgunaan cepat dikesan.
- Kod 4 digit (E) disimpan sebagai hash; 5 cubaan salah → pesanan dikunci, perlu pengurus.
  **B9:** dikunci = `orders.pickup_attempts ≥ 5` (tiada lajur baharu); pentadbir / pengurus membuka semula di butiran pesanan
  (`vendor/orders/unlock-code` → cubaan 0, dilog). Cubaan salah disimpan dalam transaksi sendiri (`VerifyPickupCodeJob`).
- Tetapan: `pickup_methods` (cth. `A,B,C,D`), `pickup_code_required_above` (cth. wajib kod jika > RM100).
- Ambil separa dibenarkan: setiap item ada `collected_qty`.

## 8. Peraturan multi-tenant — paling penting

Diwarisi daripada pelan KedaiPOS dan **tidak boleh dilanggar**.

### 8.1 `vendor_id` dan `customer_id` dari SESI, tidak pernah dari permintaan

```
BETUL  : $vendorId = VendorAuth::vendorId();     // dari sesi
SALAH  : $vendorId = $_POST['vendor_id'];         // pengguna boleh tukar
BETUL  : $customerId = CustomerAuth::id();        // dari sesi / token
SALAH  : $customerId = $_GET['customer'];
```

### 8.2 Job menerima id melalui constructor

Job tidak membaca sesi sendiri dan tidak menerima id daripada input. Pemanggil (fail `api/`)
mengambilnya dari `Auth` dan menghantarnya. Kebocoran mudah dilihat semasa membaca kod.

### 8.3 Tiada SQL dalam `api/`

### 8.4 `vendor_id` = keselamatan · `cooperative_id` = perniagaan

```
WHERE vendor_id = ?        <- sempadan KESELAMATAN, dari sesi sahaja
  AND cooperative_id = ?   <- skop PERNIAGAAN, boleh dari input
                              TETAPI mesti disahkan milik vendor (ValidateCooperativeJob)
```

`vendor_id` kekal pada **setiap** jadual perniagaan walaupun `cooperative_id` ada, supaya
peraturan audit kekal satu baris: *setiap pertanyaan menapis `vendor_id`*.

### 8.5 Store ibu bapa

Store dibuka melalui slug (`/sk-taman-melati/`). Slug → `cooperative_id` + `vendor_id`
**diselesaikan di pelayan**. Pesanan ibu bapa ditapis `customer_id` dari sesi. Ibu bapa A cuba
buka pesanan ibu bapa B → **404**.

### 8.6 UNIQUE per vendor/koperasi, indeks bermula `vendor_id`

| Lajur | Kekangan |
|---|---|
| `cooperatives.slug` | UNIQUE global (URL) |
| `products.barcode` | UNIQUE(`cooperative_id`, `barcode`) |
| `orders.order_no` | UNIQUE(`cooperative_id`, `order_no`) |
| `orders.short_code` | UNIQUE(`cooperative_id`, `short_code`) |
| `school_kits` | UNIQUE(`cooperative_id`, `grade_level_code`, `school_year`) |
| `vendor_users.email` | UNIQUE global (log masuk sebelum vendor diketahui) |
| `customers.email`, `customers.phone` | UNIQUE global |

### 8.7 Seed DUA vendor sejak hari pertama

`VENDOR01` (1 koperasi: SK Taman Melati) dan `VENDOR02` (2 koperasi: SJKC Chung Hwa, SMK Seri
Indah) + 2 ibu bapa (seorang ada anak di dua sekolah). Dengan hanya satu vendor, kebocoran mustahil dikesan.

> B1: `database/seed.sql` mengikut susunan ini (id koperasi 1 = SK Taman Melati/V01, 2 = SJKC Chung Hwa/V02, 3 = SMK Seri Indah/V02).
> Mock Peringkat 1 (`assets/js/mock/`) berbeza (Chung Hwa di bawah VENDOR01, "SMK Bukit Indah") — bila api.js ditukar ke `fetch`
> dalam B2+, data datang dari DB; mock tidak perlu diubah. Ibu bapa A (Siti) ada anak di **dua vendor** (Taman Melati & Chung Hwa).
> Akaun demo & kata laluan disenaraikan di kepala `seed.sql` (tukar sebelum produksi).

### 8.8 Muat naik diasingkan

`uploads/{vendor_id}/{cooperative_id}/products/{hash}.jpg` — `.htaccess` halang perlaksanaan PHP.

## 9. Skema pangkalan data

Pangkalan data: **`koperasi_saas`** · `utf8mb4_unicode_ci` · InnoDB.
Setiap jadual perniagaan ada `vendor_id`. Semua jadual ada `created_at`; yang boleh disunting ada `updated_at`.
Nama, harga dan maklumat anak **disalin** (snapshot) ke pesanan dan invois.

### A. Platform (8)

- **`platform_users`** — id, email UNIQUE, password_hash VARCHAR(255), full_name, role ENUM(owner, owner_staff), totp_secret (disulitkan), is_active, failed_attempts, locked_until, last_login_at
- **`languages`** — code PK (`ms`,`en`,`zh`,`ta`), name, native_name, is_active, is_default, sort_order
- **`translations`** — language_code FK, translation_key, value · PK(language_code, translation_key)
- **`grade_levels`** — code PK (`PRA`,`D1`…`D6`,`T1`…`T5`,`T6`), name_key (kunci terjemahan), level_type ENUM(preschool, primary, secondary), sort_order
- **`email_templates`** — id, template_key, language_code, subject, body_html · UNIQUE(template_key, language_code)
- **`platform_settings`** — setting_key PK, setting_value, updated_at, updated_by
- **`audit_logs`** — id, actor_type ENUM(owner, vendor_user, customer, system), actor_id, vendor_id NULL, action, entity, entity_id, detail_json, ip_address, created_at
- **`login_attempts`** — id, login_type ENUM(owner, vendor, customer), identifier, ip_address, user_agent, success, attempted_at · INDEX(identifier, attempted_at), INDEX(ip_address, attempted_at)

### B. Langganan & bil (11)

- **`plans`** — id, code, name, price_monthly, price_yearly, max_cooperatives, max_products, max_staff, features_json, is_active, sort_order
- **`plan_coop_tiers`** — id, plan_id FK, from_coop, to_coop (NULL = seterusnya), price_monthly, price_yearly
  > Koperasi ke-1 termasuk dalam harga pelan. Owner menetapkan harga koperasi tambahan. Lalai: satu peringkat `from_coop=2, to_coop=NULL` dengan harga sama seperti pelan.
- **`modules`** — id, code UNIQUE, name_key, description_key, price_monthly, price_yearly, is_core, is_active, sort_order
- **`templates`** — id, code UNIQUE (`template1`…), name, folder, preview_image, price_monthly, price_yearly, is_free, is_active
- **`vendors`** — id, code UNIQUE, company_name, registration_no, contact_name, phone, email, address, status ENUM(trial, active, past_due, suspended, cancelled), trial_ends_at
- **`subscriptions`** — id, vendor_id FK, plan_id FK, billing_cycle ENUM(monthly, yearly), status ENUM(trial, active, past_due, suspended, cancelled), started_at, current_period_start, current_period_end, cancelled_at
- **`subscription_items`** — id, vendor_id, subscription_id FK, item_type ENUM(cooperative, module, template), cooperative_id, ref_id (module_id / template_id), unit_price (snapshot), started_at, ended_at
- **`invoices`** — id, vendor_id, invoice_no UNIQUE, period_start, period_end, subtotal, tax, total, status ENUM(draft, issued, paid, void), issued_at, due_at, paid_at
- **`invoice_lines`** — id, invoice_id FK, vendor_id, description, item_type, cooperative_id NULL, qty, unit_price, amount, is_prorated
- **`platform_payments`** — id, vendor_id, invoice_id FK, gateway_code, gateway_ref UNIQUE, amount, status, raw_payload, paid_at
- **`vendor_announcements`** — id, title, body, target ENUM(all, selected), published_at, created_by

### C. Vendor & koperasi (7)

- **`vendor_users`** — id, vendor_id FK, email UNIQUE global, password_hash VARCHAR(255), full_name, phone, role ENUM(vendor_admin, coop_manager, cashier), pin_hash NULL (POS), totp_secret NULL, language, is_active, failed_attempts, locked_until, last_login_at
- **`vendor_user_cooperatives`** — vendor_id, vendor_user_id FK, cooperative_id FK · PK(vendor_user_id, cooperative_id)
- **`cooperatives`** — id, vendor_id FK, slug UNIQUE, school_name, school_code (KPM), school_type ENUM(SK, SJKC, SJKT, SMK, SMKA, SBP, other), state, district, address, phone, logo_path, template_id FK, default_language, accept_cash, accept_online, status ENUM(active, suspended, closed)
- **`cooperative_settings`** — vendor_id, cooperative_id, setting_key, setting_value · PK(cooperative_id, setting_key)
  > Isi awal: `alert_emails`, `pickup_methods` (`A,B,C,D`), `pickup_code_required_above` (0 = tidak wajib), `cash_order_expiry_days` (3), `opening_hours`, `receipt_footer`, `tax_rate` (0), `low_stock_default` (5)
- **`cooperative_modules`** — vendor_id, cooperative_id, module_id, status ENUM(active, expired), started_at, ends_at · PK(cooperative_id, module_id)
- **`school_classes`** — id, vendor_id, cooperative_id, grade_level_code, class_name (cth. "Bestari"), sort_order · UNIQUE(cooperative_id, grade_level_code, class_name)
- **`activity_logs`** — id, vendor_id, cooperative_id NULL, vendor_user_id, action, entity, entity_id, detail_json, created_at

### D. Katalog & inventori (13)

- **`categories`** — id, vendor_id, cooperative_id, name, sort_order, is_active
- **`products`** — id, vendor_id, cooperative_id, category_id FK, name, description, sku, barcode, price, cost_price, unit, image_path, max_per_order NULL, has_variants, is_active, sort_order · UNIQUE(cooperative_id, barcode)
- **`product_translations`** — vendor_id, product_id, language_code, name, description · PK(product_id, language_code)
  > Pilihan. Tiada terjemahan → guna `products.name`. Berguna untuk SJKC/SJKT.
- **`product_variants`** — id, vendor_id, product_id FK, label (cth. "Saiz M / Biru"), sku, barcode, price_delta, is_active
- **`product_grade_levels`** — vendor_id, product_id, grade_level_code · PK(product_id, grade_level_code)
- **`stock_levels`** — vendor_id, cooperative_id, product_id, variant_id (0 = tiada varian), qty_on_hand, qty_reserved, min_stock · PK(cooperative_id, product_id, variant_id)
  > `qty_reserved` = ditahan untuk pesanan yang belum diambil. Boleh dijual = on_hand − reserved.
- **`stock_movements`** — id, vendor_id, cooperative_id, product_id, variant_id, type ENUM(sale, pos_sale, reserve, release, collect, refund, restock, adjust, stocktake, po_receive), qty_change, balance_after, ref_type, ref_id, vendor_user_id, note, created_at
- **`suppliers`** — id, vendor_id, name, contact_name, phone, email, address, is_active
- **`purchase_orders`** — id, vendor_id, cooperative_id, supplier_id, po_no, status ENUM(draft, ordered, partial, received, cancelled), ordered_at, received_at, total, note
- **`purchase_order_items`** — id, vendor_id, purchase_order_id, product_id, variant_id, qty_ordered, qty_received, unit_cost
- **`stock_takes`** — id, vendor_id, cooperative_id, status ENUM(open, completed), started_by, started_at, completed_at
- **`stock_take_items`** — id, vendor_id, stock_take_id, product_id, variant_id, qty_system, qty_counted, variance
- **`promotions`** — id, vendor_id, cooperative_id, name, code NULL (kupon), type ENUM(percent, fixed), value, min_spend, starts_on, ends_on, max_uses, used_count, is_active
  > Seed (B7): kupon `JIMAT5` (RM5, min RM50) & automatik 3% (min RM200) di SK Taman Melati; kupon `SI10` di SMK Seri Indah. Panel promosi vendor masih mock (B10).

### E. Kit keperluan sekolah (2)

- **`school_kits`** — id, vendor_id, cooperative_id, grade_level_code, school_year, name, note, is_published · UNIQUE(cooperative_id, grade_level_code, school_year)
- **`school_kit_items`** — id, vendor_id, school_kit_id FK, product_id, variant_id NULL, qty, is_mandatory, note, sort_order
  > `is_mandatory = 1` tetap boleh di-uncheck oleh ibu bapa (mungkin sudah ada di rumah), tetapi UI memaparkan amaran "Item wajib sekolah".

### F. Pembeli (5)

- **`customers`** — id, full_name, phone UNIQUE, email UNIQUE, password_hash VARCHAR(255), relationship ENUM(mother, father, guardian), language, email_verified_at, pdpa_consent_at, is_active, failed_attempts, locked_until, last_login_at, deleted_at
  > Tiada `vendor_id` — ibu bapa ialah akaun global yang boleh membeli dari beberapa koperasi.
- **`children`** — id, customer_id FK, cooperative_id FK, vendor_id (salinan, untuk tapisan vendor), full_name, grade_level_code, school_class_id NULL, class_name, school_year, is_active
  > Seed (B6): Adam (anak ibu bapa A) Darjah 1 tahun **2027** — sepadan dengan Kit Darjah 1 2027 yang diterbitkan (demo aliran kit).
- **`customer_tokens`** — id, customer_id, type ENUM(verify_email, reset_password), token_hash, expires_at, used_at
- **`api_tokens`** — id, customer_id, token_hash UNIQUE, device_name, last_used_at, expires_at, revoked_at
- **`push_devices`** — id, customer_id, platform ENUM(android, ios), push_token, created_at

### G. Pesanan, ambil & bayaran (13)

- **`carts`** — id, customer_id, cooperative_id, vendor_id, updated_at · UNIQUE(customer_id, cooperative_id)
- **`cart_items`** — id, cart_id, product_id, variant_id, child_id, qty, source ENUM(ala_carte, kit), school_kit_id NULL, school_kit_item_id NULL (U5: kit yang sama untuk anak yang sama diganti, bukan ditambah)
- **`orders`** — id, vendor_id, cooperative_id, customer_id NULL (NULL = POS walk-in), order_no, short_code, channel ENUM(online, pos), payment_method ENUM(cash, online), status (lihat 6), subtotal, discount, promotion_discount, manual_discount, tax_rate, tax, total, promotion_id, pos_shift_id NULL, cashier_id NULL (vendor_user POS), amount_received NULL, change_given NULL, pickup_by_type ENUM(student, parent, proxy), pickup_by_name, pickup_slot_id NULL (U6, modul school_opening), pickup_code_hash NULL, pickup_qr_token UNIQUE, pickup_attempts, customer_name, customer_phone, customer_email (snapshot), expires_at, paid_at, collected_at, cancelled_at, created_at · UNIQUE(cooperative_id, order_no), UNIQUE(cooperative_id, short_code), INDEX(vendor_id, cooperative_id, status)
- **`order_items`** — id, vendor_id, order_id, product_id, variant_id, product_name, variant_label, unit_price, qty, line_total, source ENUM(ala_carte, kit, pos), school_kit_id NULL, clearance_item_id NULL (U5), child_id NULL, child_name, grade_label, class_name, collected_qty, cancelled_qty (refund sebelum diambil), returned_qty (dipulang selepas diambil)
- **`order_status_logs`** — id, vendor_id, order_id, from_status, to_status, actor_type, actor_id, note, created_at
- **`order_pickups`** — id, vendor_id, order_id, method ENUM(A, B, C, D, E), picked_by_type ENUM(student, parent, proxy), picked_by_name, vendor_user_id, created_at
- **`order_pickup_items`** — id, vendor_id, order_pickup_id, order_item_id, qty
- **`payment_gateways`** — code PK (`toyyibpay`, `billplz`, `senangpay`, `ipay88`, `stripe`), name, config_fields_json, is_enabled_for_vendors, is_enabled_for_platform
- **`cooperative_gateways`** — id, vendor_id, cooperative_id, gateway_code, credentials_encrypted, mode ENUM(sandbox, live), is_active · UNIQUE(cooperative_id, gateway_code)
- **`payments`** — id, vendor_id, cooperative_id, order_id, method ENUM(cash, online), gateway_code NULL, gateway_ref UNIQUE NULL, amount, status ENUM(pending, paid, failed, refunded), raw_payload, verified_at, received_by NULL (juruwang), created_at
- **`refunds`** — id, vendor_id, cooperative_id, refund_no, order_id, payment_id, amount, reason, method ENUM(cash, bank_transfer, gateway), restock, status, vendor_user_id, created_at
  > Butiran barang: **`refund_items`** — id, vendor_id, refund_id, order_item_id, qty, cancelled_qty (U3c)
- **`pos_shifts`** — id, vendor_id, cooperative_id, vendor_user_id, opened_at, opening_cash, closed_at, closing_cash, expected_cash, variance, note, status ENUM(open, closed)
  > U4: satu syif terbuka setiap juruwang setiap koperasi. Jangkaan tunai = tunai permulaan + jualan tunai POS syif itu
- **`webhook_logs`** — id, gateway_code, cooperative_id NULL, payload, signature_valid, processed, result (B8, migrasi `003-webhook-result.sql`), created_at
  > **B8:** `payments.raw_payload` = JSON `{ reference, redirect_url, form, verified }` (rujukan kami `{order_no}-{n}`, pautan / borang gateway,
  > jawapan semakan semula). `gateway_ref` = id bil di gateway (BillCode ToyyibPay, id Billplz, id sesi Stripe; SenangPay & iPay88 = rujukan kami).
  > `result`: `paid`, `already_paid`, `pending`, `failed`, `paid_late`, `amount_mismatch`, `currency_mismatch`, `bad_signature`, `unknown_reference`,
  > `no_gateway`, `recheck_failed` (+ ` (unsigned)` untuk ToyyibPay yang tiada tandatangan).

### H. Modul add-on (8)

- **`news_posts`** — id, vendor_id, cooperative_id, title, body, image_path, published_at, send_email, email_sent_at NULL, email_recipients, is_active
  > U6: e-mel dimasukkan ke `email_queue` **sekali** bila berita sudah terbit (`email_sent_at`); berita berjadual → cron semasa terbit
- **`clearance_sales`** — id, vendor_id, cooperative_id, name, starts_at, ends_at, is_active
- **`clearance_items`** — id, vendor_id, clearance_sale_id, product_id, variant_id, sale_price, qty_limit
- **`calendar_events`** — id, vendor_id, cooperative_id, title, event_type ENUM(school_holiday, coop_closed, school_opening, other), starts_on, ends_on, blocks_orders
- **`preorder_seasons`** — id, vendor_id, cooperative_id, school_year, opens_at, closes_at, pickup_starts_on, note
- **`pickup_slots`** — id, vendor_id, cooperative_id, preorder_season_id, slot_date, time_start, time_end, grade_level_code NULL, capacity, booked
- **`notification_queue`** — id, vendor_id, cooperative_id NULL, channel ENUM(push, whatsapp, sms), event, recipient, message, status ENUM(pending, sent, failed), attempts, created_at, sent_at
- **`messaging_settings`** (U6) — id, vendor_id, cooperative_id UNIQUE, provider, channel ENUM(whatsapp, sms), sender, api_key_enc, is_enabled, events_json, templates_json, updated_at
  > Kunci API disulitkan (seperti gateway), tidak pernah dipulangkan; kosong semasa simpan = kekal

### I. Sistem (2)

- **`email_queue`** — id, vendor_id NULL, to_email, subject, body_html, template_key, status ENUM(pending, sent, failed), attempts, last_error, created_at, sent_at
  > E-mel dihantar oleh `cron/send-emails.php`, bukan semasa checkout — checkout tidak lambat walaupun SMTP perlahan.
  > **B7:** maksimum 5 cubaan (`attempts`) → `failed` + `last_error`; SMTP owner belum ditetapkan → baris kekal `pending`. `config mail_driver`
  > `smtp` (lalai) / `log` (pembangunan → `storage/mail.log`). Templat `order_receipt` kini ada `{pickup_qr}` (migrasi `002-order-receipt-qr.sql`).
- **`rate_limits`** — bucket_key, hits, window_start · PK(bucket_key)

Jumlah: **69 jadual** (U6: + `messaging_settings`).

### J. Tambahan B1 — jadual yang diperlukan oleh kontrak UI (PELAN 18) · jumlah sebenar **75 jadual** (B2: **76**)

`database/schema.sql` ialah sumber kebenaran lajur. Tambahan berbanding senarai A–I di atas:

| Jadual / lajur baharu | Sebab (kontrak UI) |
|---|---|
| `refund_items` (sudah disebut di G, kini dikira) | butiran barang refund (U3c) |
| `totp_recovery_codes` — user_type ENUM(owner, vendor_user), user_id, code_hash, used_at | 8 kod pemulihan 2FA (18.7) |
| `vendor_notes` — vendor_id, body, created_by | nota dalaman owner + sebab gantung (18.7) |
| `vendor_announcement_targets` — PK(announcement_id, vendor_id) | pengumuman `target = selected` (18.7) |
| `vendor_user_tokens` — type ENUM(invite, reset_password), token_hash, expires_at, used_at | e-mel jemputan / reset kakitangan (18.10) |
| `vendor_customer_notes` — PK(vendor_id, customer_id), note | nota vendor tentang ibu bapa, setiap vendor nampak notanya sahaja (18.9) |
| `platform_users.language` · `vendor_users.pin_failed_attempts` | profil owner; 5 PIN salah → dikunci (18.11) |
| `subscriptions.pending_plan_id, pending_billing_cycle` · `subscription_items.cancel_at_period_end` · `cooperative_modules.cancel_at_period_end` | tukar pelan / batal modul pada hujung tempoh (18.10) |
| `invoices.sst_rate` · `invoice_lines.item_type` + `plan`, `other` · `platform_payments.method, note` (`gateway_ref` = rujukan manual juga) | invois owner (18.7) |
| `product_variants.cooperative_id` + UNIQUE(cooperative_id, barcode / sku) · `products` UNIQUE(cooperative_id, sku) | barcode & SKU unik dalam koperasi, termasuk varian (18.8) |
| `orders.pickup_slot_json` (salinan slot) · `order_items.sku` | snapshot slot & SKU (18.9, 18.13) |
| `cooperative_gateways.config_json` (medan bukan rahsia) · `payment_gateways.supports_refund` | `values` + `secrets_set` (18.10) |
| `purchase_orders` UNIQUE(cooperative_id, po_no), `created_by` · `refunds` UNIQUE(cooperative_id, refund_no) | nombor unik per koperasi |
| `stock_take_items` UNIQUE(stock_take_id, product_id, variant_id) · `preorder_seasons` UNIQUE(cooperative_id, school_year) · `clearance_items` UNIQUE(sale, product, variant) | peraturan "satu sahaja" dari UI |
| `login_attempts.login_type` + `pos_pin` · `notification_queue.last_error` | throttle PIN POS; log mesej |
| `platform_settings`: `smtp_password_enc`, `owner_gateway_code / mode / config / secrets_enc` | rahsia owner disulitkan (`Crypto`) |
| **B2** `platform_user_tokens` — platform_user_id, type ENUM(invite, reset_password), token_hash, expires_at, used_at (`migrations/001`) | jemputan kakitangan owner (`staff.invite_note`) & lupa kata laluan owner |
| **B2** `platform_users.totp_last_step` · `vendor_users.totp_last_step` | kod TOTP yang sudah diterima tidak boleh diguna semula |

Konvensyen lajur: wang `INT` sen; `variant_id = 0` bermaksud tiada varian (stok, pesanan, troli), kecuali
`school_kit_items.variant_id NULL` = ibu bapa pilih varian; tarikh `DATETIME` waktu Malaysia (`+08:00`); JSON dalam `TEXT` (`*_json`).
Keterangan modul disimpan dalam `translations` (`module.{code}.description`); nama modul & darjah dari `lang/*.php` (`module.{code}`, `grade.{code}`).

## 10. Backend, API & frontend

### 10.1 Struktur folder

```
Koperasi_sekolah/
├── CLAUDE.md
├── DOC/            PELAN.md · PROSES.md · PROMPT-SESSION.md
├── config/         app.php · malaysia.php (negeri, jenis sekolah, hari) · database.php (.gitignore) · database.example.php · secret.key (.gitignore)
├── core/           bootstrap.php · Config · View · SecurityHeaders (CSP) — sejak U1
│                   Database · Request · Response · Validator · Csrf · Lang (+ t(), e()) · ValidationException (B2)
│                   OwnerAuth · VendorAuth · CustomerAuth · Session · Password · Totp · RateLimit (B2) · ApiTokenAuth
│                   PhoneNumber · IsoDate · Pagination (B2) · PanelRole (U7, sesi sejak B2)
│                   Crypto (sodium) · ModuleAccess · Uploader (B4) · View · SecurityHeaders
│                   Mailer (SMTP vanila: tls/ssl/none + AUTH LOGIN, driver log) · Money (sen → "RM 1.50") · QrCode (QR e-mel) — B7
│                   (PlanLimit = jobs/Billing/PlanLimit.php sejak B4 — membaca DB, jadi bukan dalam core/)
├── jobs/           SATU KELAS = SATU KERJA
│   ├── Auth/ Owner/ Vendor/ Cooperative/ Plan/ Billing/ Module/ Template/
│   ├── Product/ Inventory/ Supplier/ Promotion/ SchoolKit/
│   ├── Customer/ Child/ Cart/ Store/ Order/ Pickup/ Payment/ Pos/ Report/   (Store/ = slug → koperasi, harga store, katalog, kit — B6)
│   │   (Payment/ = gateway owner B3 + gateway koperasi, bayaran online, panggilan balik, luput — B8)
│   │   (Order/ = checkout, pesanan ibu bapa, no. pesanan, e-mel pesanan, luput tunai — B7 · Promotion/ = pilih promosi — B7)
│   └── News/ Clearance/ Calendar/ SchoolOpening/ Notification/ Language/ Setting/ Announcement/ (B3)
├── payments/       GatewayInterface · GatewayFactory · BaseGateway (HTTP curl, URL sandbox/live) · GatewayException — B8 (dimuat automatik)
│   └── drivers/    ToyyibPayGateway · BillplzGateway · SenangPayGateway · Ipay88Gateway · StripeGateway
├── api/v1/
│   ├── owner/  vendor/  store/  pos/
│   └── webhooks/   toyyibpay.php · billplz.php · senangpay.php · ipay88.php · stripe.php (B8, ?coop={id}, tiada sesi/CSRF)
├── partials/       head · lang-switcher · sidebar-menu · ui-shell (toast + confirm) · scripts · login-form
│                   page-title (tajuk + butang tindakan + kembali) · stat-card (kad papan pemuka) · sub-nav (pills)
├── owner/          panel owner (halaman .php)
│   └── _layout/    header · sidebar · footer · profile-menu · menu.php (senarai menu)
├── vendor/         panel vendor (halaman .php)
│   ├── _layout/    header · sidebar · footer · profile-menu · menu.php · addons.php · sub-nav.php (kumpulan sub-navigasi)
│   │               module-gate.php (kad "Langgan modul ini") · addons/ (partial modul: clearance-sale-modal, coming-soon)
│   ├── addons/     halaman modul add-on: {code}.php (U6) — news · clearance (+ clearance-sale) · calendar · school_opening ·
│   │               whatsapp_sms · advanced_report / mobile_app / membership (halaman sementara)
│   └── pos/        POS kaunter + skrin serah barang
├── store/          index.php (titik masuk ibu bapa: slug → koperasi → template) · payment-return.php (kembali dari gateway → order-success)
│                   payment-redirect.php (borang POST automatik untuk iPay88) — B8
├── .htaccess       URL store cantik /{slug}/{halaman} → store/index.php (B6; aktif bila config pretty_store_urls = true)
├── templates/
│   └── template1/  manifest.json · layout.php · partials/ · pages/ · css/ · js/
├── modules/        news/ · clearance/ · calendar/ · school_opening/
├── lang/           ms.php · en.php · zh.php · ta.php
├── cron/           send-emails · expire-cash-orders (B7, CLI sahaja, `.htaccess` halang web) · expire-online-orders (B8) · generate-invoices · suspend-overdue · promote-grades
├── storage/        mail.log (mail_driver log) · fail kunci cron (.gitignore isi, `.htaccess` halang web) — B7
├── tools/          check-ui.php (tiada inline CSS + kunci 4 bahasa sama) · generate-secret.php · verify-b1.php · verify-b2.php
│                   dev-totp.php (kod 2FA ujian) · ui-audit.js (pelayar, log masuk sebenar sejak B2) — CLI sahaja
│                   verify-b3.php · test-helpers.php (Browser HTTP dikongsi — B3) · verify-b4.php (B4) · verify-b5.php (B5) · verify-b6.php (B6)
│                   verify-b7.php · fake-smtp.php (pelayan SMTP palsu untuk ujian) — B7
│                   verify-b8.php · fake-gateway.php (gateway palsu 5 driver, `php -S` router) — B8
├── assets/
│   ├── lib/        bootstrap/ (5.3.3) · bootstrap-icons/ (1.11.3) · qrcode/ (qrcode-generator 1.4.4, MIT — sejak U2)
│   ├── css/        app.css · print-label.css · print-slip.css · receipt.css
│   ├── js/         api.js · lang.js · helpers.js · login.js · vendor-layout.js
│   │   ├── owner/  satu fail per halaman owner (dashboard.js, vendors.js, …)
│   │   ├── vendor/ satu fail per halaman vendor (dashboard.js, …) · addon-common.js · addons/{code}.js (U6)
│   │   └── mock/   mock.js · owner.js · vendor.js · vendor-orders.js · vendor-finance.js · vendor-addons.js (Peringkat 1 sahaja; store.js dibuang B7)
│   ├── img/
│   └── fonts/      noto-sans-sc/ · noto-sans-tamil/ (woff2 400 & 700, unicode-range — dimuat bila perlu)
├── uploads/        {vendor_id}/{cooperative_id}/… (.gitignore isi, .htaccess halang PHP)
└── database/       create-database.sql (DB + koperasi_user) · schema.sql (75 jadual) · seed.sql · migrations/ (`.htaccess` halang akses web; juga `config/ core/ jobs/ lang/ tools/`)
```

### 10.2 Contoh endpoint nipis

```php
<?php
// api/v1/vendor/products/save.php
require __DIR__ . '/../../../../core/bootstrap.php';

VendorAuth::requireRole(['vendor_admin', 'coop_manager']);
Csrf::verifyRequest();

$vendorId = VendorAuth::vendorId();                                   // dari sesi
$coopJob  = new ValidateCooperativeJob($vendorId, Request::int('cooperative_id'));
$cooperative = $coopJob->run();                                        // null = tiada / milik vendor lain
if ($cooperative === null) {
    Response::notFound();
}

$job = new SaveProductJob($vendorId, $cooperative['id'], Request::all());
$result = $job->run();                    // job mengesahkan input (Validator) & menulis SQL
Response::ok($result);
```

**Konvensyen job (B1):** satu fail `jobs/{Folder}/{Nama}Job.php`, satu kelas, dimuat automatik oleh `core/bootstrap.php`.
Id keselamatan (`vendor_id`, `customer_id`) + input dihantar melalui **constructor**; satu kaedah awam `run()`.
Job memanggil `Database::fetchOne / fetchAll / fetchValue / execute / insert` (PDO prepared, tiada SQL dalam `api/`).
Ralat pengesahan → job memulangkan / melempar ralat yang dipetakan ke `Response::fail(t('error.check_form'), $errors)`.
Ralat tidak dijangka dalam `api/` → pengendali global memulangkan `{ ok:false, error: t('error.server') }` (HTTP 500).

**Core B1:** `Database` (PDO utf8mb4, `EMULATE_PREPARES` off, `ERRMODE_EXCEPTION`, zon `+08:00`) · `Request` (GET query, POST JSON/borang;
`string/int/bool/arrayValue`) · `Response` (`ok`, `fail`, `notFound` 404, `forbidden` 403) · `Validator` (berantai, medan bersarang `names.ms`) ·
`Csrf` (`token()`, `verifyRequest()` — header `X-CSRF-Token`) · `SecurityHeaders` (+ HSTS bila HTTPS) · `Crypto` (sodium secretbox,
`config/secret.key` dari `php tools/generate-secret.php`; `randomToken`, `hashToken` SHA-256).
Semakan: `php tools/verify-b1.php` (utf8mb4, UNIQUE barcode, kebocoran dua vendor, Argon2id, Crypto, Validator).

**Konvensyen endpoint (B2):** `Request::requireMethod('GET'|'POST')` (405) → auth (`OwnerAuth::requireLogin/requireRole`,
`VendorAuth::requireRole($roles, $allowBillingOnly)`, `CustomerAuth::requireLogin`; laluan awam: `XAuth::start()` untuk sesi + CSRF) →
`Csrf::verifyRequest()` (419) → `RateLimit::enforce(bucket, max, saat)` (429) bila perlu → job → `Response::ok`.
Job melempar `ValidationException(mesej, errors, status, extra)` untuk ralat dijangka; pengendali global → `{ ok:false, error, errors }`.
Belum log masuk → 401 `{ auth_required: true }` (panel: `api.js` ke `data-login-url`; store: `storeHandle` ke halaman log masuk).
Vendor digantung → 403 `{ billing_only: true }`. Tarikh API melalui `IsoDate::format()`; senarai melalui `Pagination::from/result`.
Sesi: `KOPOWNER` (idle 2 jam) · `KOPVENDOR` (2 jam) · `KOPSTORE` (30 hari; "ingat saya" = cookie 30 hari) — `HttpOnly`, `SameSite=Lax`,
`Secure` bila HTTPS; `<body data-csrf>` dari `partials/head.php` bila sesi aktif. E-mel: `QueueEmailJob` → `email_queue`
(templat `email_templates` ikut bahasa penerima → jatuh balik kunci `email.{kunci}.subject|body`); pautan penuh `View::absoluteUrl()`
(`config app_url` WAJIB di produksi). Log: `WriteAuditLogJob` (owner → `audit_logs`) · `WriteActivityLogJob` (vendor → `activity_logs`).

**Konvensyen B3:** setiap tindakan owner yang mengubah data menulis `WriteAuditLogJob` (kod/teks dalam `detail`, `entity_id` hanya untuk id
nombor). Tetapan platform melalui `PlatformSettings::get/set` (dicache satu permintaan). Teks 4 bahasa yang owner sunting melalui
`TranslationValues::many/get/save` (DB → `lang/*.php` → `''`). Pengendali ralat global memanggil `Database::rollBack()` supaya job yang
gagal di tengah transaksi tidak meninggalkan separuh perubahan. Ujian HTTP: `tools/test-helpers.php` (`check()`, `Browser`, `ownerLogin`,
`vendorLogin`, `storeLogin`, `clearTestLocks`) dikongsi oleh `verify-b2.php` & `verify-b3.php`.

**Konvensyen B4:** endpoint skop "vendor sesi + koperasi" memanggil `VendorAuth::requireCooperative(Request::int('cooperative_id'))`
(`ValidateCooperativeJob` + akses kakitangan → 404 jika gagal) dan menghantar `$cooperative['id']` kepada job. Peranan katalog/inventori =
`VendorAuth::PANEL_ROLES` (juruwang 403). **Stok hanya diubah melalui `StockLedger::move()`** (`jobs/Inventory/`) — mengunci baris
`stock_levels` (`FOR UPDATE`), menulis `stock_movements.balance_after`, dan menolak panggilan di luar transaksi DB. Nombor bersiri & had pelan
dikunci dengan `SELECT … FOR UPDATE` pada baris koperasi / vendor. Pembantu B4: `PlanLimit` (jobs/Billing), `CooperativeSettings`,
`ProductCatalog`, `InventoryRows`, `StockTakes`, `PurchaseOrders`. Muat naik: `core/Uploader::saveImage()` (data URL → fail).

**Konvensyen B6 (store):** endpoint store menyelesaikan koperasi dengan `StoreSchool::require(Request::string('slug'))` (404
`store.school_not_found`) dan menghantar **baris koperasi** (bukan id dari input) kepada job; `vendor_id` diambil dari baris itu. Laluan ibu bapa:
`CustomerAuth::requireLogin()` dahulu (401 `auth_required`), kemudian slug; `CustomerAuth::id()` → constructor job. Harga, stok & had sentiasa melalui
`StorePricing`; troli melalui `Carts` (`ensure` = INSERT IGNORE + `FOR UPDATE` dalam transaksi, `assertFits`, `quote`); anak melalui `Children`
(`find` sentiasa `customer_id` + `is_active`, pilihan koperasi). Had kadar: `store_cart:{customer}` 120/minit, `store_child_save:{customer}` 60/10 minit.

**Konvensyen B7 (checkout & pesanan):** sebut harga checkout HANYA melalui `CheckoutQuote::build($customer, $koperasi, $kupon)` (CartQuote dari
`Carts::quoteWithLines` + `Promotions::choose` + cukai selepas diskaun + kaedah bayaran + kod 4 digit + slot + tutup tempahan) — digunakan oleh
paparan (`GetCheckoutQuoteJob`) dan `CheckoutJob` (dikira semula dalam transaksi). `CheckoutJob` = SATU transaksi dengan tertib kunci tetap:
troli (`FOR UPDATE`) → baris troli → baris stok ikut (product_id, variant_id) menaik (`StockLedger::lock`) → slot → promosi → baris koperasi
(`OrderNumbers::next`). Baki stok disemak semula dengan nilai yang DIKUNCI (stok terakhir: satu pesanan sahaja). Tahan/lepas stok melalui
`StockLedger::reserve/release` (ubah `qty_reserved`, pergerakan `reserve`/`release` dengan `qty_change 0` + `ref_type order`). Pesanan ibu bapa
melalui `CustomerOrders` (`find/require` sentiasa `customer_id` + `vendor_id` + `cooperative_id` + `channel online`; `releaseHolds` untuk
batal/luput; `sortStockKeys` = tertib kunci sama). E-mel pesanan: `OrderEmails::queueNewOrder` dalam transaksi checkout → `email_queue`;
`QueueEmailJob` menerima `$htmlVars` (serpihan HTML yang dibina pelayan, cth. `{items_table}`, `{pickup_qr}`) yang tidak di-escape. Penghantaran
HANYA oleh `cron/send-emails.php` (`SendQueuedEmailsJob` + `core/Mailer`, 5 cubaan → `failed`, `flock` elak larian bertindih). Had kadar:
`store_checkout:{customer}` 20/10 minit. Ujian serentak: `parallelPost()` dalam `tools/test-helpers.php` (pelayan `PHP_CLI_SERVER_WORKERS`).

**Konvensyen B8 (payment gateway):** driver dalam `payments/drivers/` (satu kelas setiap gateway, `extends BaseGateway implements GatewayInterface`)
TIDAK menyentuh DB — hanya bercakap dengan API gateway (curl) dan menyemak tandatangan. Dapatkan driver HANYA melalui
`CooperativeGateways::driver($baris)` (kunci dinyahsulit `Crypto` di pelayan) / `GatewayFactory::make()`. Gateway koperasi: `CooperativeGateways`
(`active` = aktif + dibenarkan owner + driver wujud; `find`; `forCallback` = koperasi dari URL, vendor dari baris DB; `publicShape` tanpa rahsia).
Bayaran dimulakan HANYA oleh `StartOnlinePaymentJob` (selepas transaksi checkout — panggilan rangkaian tidak pernah dalam transaksi DB).
Status `paid` HANYA diputuskan oleh `PaymentSettlement::recheck/apply` (tanya API gateway → semak amaun sen + MYR = `payments.amount` =
`orders.total` → kunci `payments` & `orders` FOR UPDATE → idempotent). Dipanggil oleh `HandleGatewayCallbackJob` (webhook), `CheckOrderPaymentJob`
(ibu bapa kembali) dan `ExpirePendingPaymentsJob` (cron). Ralat rangkaian = `GatewayException` (mesej untuk log; ibu bapa melihat
`payment.gateway_error`). Ujian: `KOPERASI_GATEWAY_TEST_URL` menghala mod **sandbox sahaja** ke `tools/fake-gateway.php`. Had kadar:
`store_payment_start:{customer}` 20/10 minit, `store_payment_check:{customer}` 30/10 minit, `vendor_gateway_test:{user}` 10/10 minit,
`webhook:{ip}` 300/minit. Jawapan teks gateway: `Response::text()`; badan & tandatangan mentah: `Request::webhook()`; `?coop=`: `Request::queryInt()`.

**Konvensyen B9 (pesanan vendor & serah barang):** pesanan panel vendor HANYA melalui `VendorOrders` (`find/require` sentiasa `vendor_id` sesi +
`cooperative_id` disahkan) — jangan guna `CustomerOrders::find` (skop ibu bapa) di panel. Peralihan status = `VendorOrders::STATUS_FLOW`;
`collected` / `partially_collected` HANYA oleh `HandoverOrderJob`. Barang diserah = `StockLedger::collect` (tolak `qty_on_hand` DAN
`qty_reserved`, pergerakan `collect` −qty, `ref_type order`, `vendor_user_id`) dalam transaksi yang sama dengan `order_pickups`. Bayaran tunai
pesanan store = baris `payments` (cash) oleh `ReceiveCashPaymentJob` — `paid_at` tanpa baris `payments` tidak dibenarkan. Kod 4 digit =
`VerifyPickupCodeJob` (`password_verify`, cubaan disimpan walaupun serahan gagal). E-mel pesanan ibu bapa: `OrderEmails::queueReady`,
`queueCollected`, `queuePaid` (dari `PaymentSettlement` bila `paid`) — penerima = `orders.customer_email` + bahasa akaun ibu bapa.

### 10.3 Kontrak API

- Format jawapan: `{ "ok": true, "data": ... }` atau `{ "ok": false, "error": "mesej", "errors": { "field": "mesej" } }`
- Laluan: `/api/v1/{owner|vendor|store|pos}/{modul}/{tindakan}.php`
- Web: sesi + header `X-CSRF-Token`. Flutter: `Authorization: Bearer {token}` (hanya `store/`)
- Senarai bertapis: `?page=1&per_page=20&q=...` → `data: { items: [...], total, page, per_page }`
- Wang dalam **sen (integer)** dalam API dan pengiraan; dipapar sebagai `RM 12.50`
- Tarikh ISO 8601 (`2026-10-24T10:15:00+08:00`), zon waktu `Asia/Kuala_Lumpur`
- Bentuk setiap objek ditetapkan dalam `assets/js/mock/*.js` semasa Peringkat 1 dan dicatat
  di hujung PELAN ini (bahagian 18) apabila session U siap

### 10.4 Frontend

- Bootstrap 5 + Bootstrap Icons, disimpan setempat dalam `assets/lib/` (tiada CDN — sekolah kadang-kadang internet perlahan dan CSP lebih ketat)
- Panel owner & vendor: sidebar kiri + topbar (pemilih koperasi, bahasa, profil). Desktop-first tetapi boleh digunakan di tablet
- Store ibu bapa: **mobile-first** (kebanyakan ibu bapa guna telefon)
- Semua teks melalui `t('key')` (PHP) / `T('key')` (JS, dari `window.LANG`)
- Satu fail JS per halaman, fungsi biasa (`function loadProducts() {...}`), tiada kelas JS kompleks

### 10.5 Template frontend ibu bapa

- `templates/template1/` mengandungi **paparan sahaja**: HTML + `echo` + `foreach`, semua output melalui `e()`
- Logik, harga, stok, kit — semuanya dalam job/API. Template baharu = salin folder, tukar rupa. **Aliran kerja sama**
- `manifest.json`: `code`, `name`, `version`, `pages` wajib: `home, school-select, catalog, product, kit, cart, checkout, order-success, orders, order-detail, children, login, register, forgot-password, profile, news, clearance`
- Vendor memilih template yang dilanggan untuk setiap koperasi (`cooperatives.template_id`). Template tidak dilanggan → jatuh balik ke `template1`
  (**sejak B6** `ResolveStoreTemplateJob`: template aktif + dilanggan vendor + folder lengkap dengan semua halaman wajib manifest; pratonton
  `?template=` hanya owner / vendor pemilik koperasi)
- **Sejak U5:** logik setiap halaman store dalam `assets/js/store/{halaman}.js` (+ `common.js`, `layout.js`) — dikongsi semua template.
  Template baharu mesti mengekalkan `id` / atribut `data-*` yang dibaca JS (cth. `#store-context`, `data-cart-count`, `data-module`,
  `data-auth-guest|user`, `#slip-area`) dan boleh menukar rupa melalui `css/` sendiri. Slip & resit: `templates/{kod}/css/print-slip.css`

## 11. Log masuk & kata laluan

| Jenis | Cara | Tambahan |
|---|---|---|
| Owner | E-mel + kata laluan | **2FA TOTP wajib** |
| Vendor (admin/pengurus) | E-mel + kata laluan | 2FA TOTP pilihan, pemilih koperasi selepas log masuk |
| Juruwang POS | E-mel + kata laluan, atau PIN pada peranti POS berdaftar | PIN 5 cubaan salah → dikunci (selaras 18.11; pentadbir tetapkan PIN baharu) |
| Ibu bapa | E-mel **atau** no. telefon + kata laluan | Pengesahan e-mel |

- **Argon2id** `memory_cost 19456`, `time_cost 2`, `threads 1` (bukan lalai PHP — terlalu perlahan, 801ms diukur). `password_hash` VARCHAR(255). PIN juga di-hash Argon2id
- `password_needs_rehash()` setiap log masuk berjaya
- Mesej ralat sama untuk akaun tiada & kata laluan salah: *"E-mel atau kata laluan tidak sah"*
- Kunci 15 minit selepas 5 cubaan gagal, rekod setiap cubaan + IP
- `session_regenerate_id(true)` selepas log masuk. Tiga nama sesi berasingan untuk owner/vendor/ibu bapa
- Status langganan vendor disemak selepas pengesahan, dan pada setiap permintaan

**Pelaksanaan B2:**
- Owner langkah 1 → `beginPending` (10 minit); langkah 2 `verify-2fa`. Akaun tanpa 2FA (log masuk pertama / selepas "set semula 2FA")
  menerima `setup { secret, otpauth_url }` → QR dalam pelayar (`assets/lib/qrcode`) → kod pertama menyimpan rahsia (disulitkan `Crypto`)
  dan memulangkan 8 kod pemulihan (dipapar sekali). Kod pemulihan `XXXX-XXXX` diterima di langkah 2 (sekali guna, hash SHA-256)
- TOTP RFC 6238 (SHA1, 6 digit, 30 s, ±1 langkah); `totp_last_step` → kod yang sama tidak diterima dua kali. Cubaan 2FA gagal juga dikunci (5 → 15 minit)
- Kunci: `login_attempts` dikira ikut pengecam (e-mel / telefon dinormalkan / `totp:{jenis}:{id}`) — sama untuk akaun wujud atau tidak;
  cubaan semasa dikunci juga direkod. `failed_attempts` / `locked_until` dikemas kini untuk paparan pentadbir. Tambahan had per IP (`rate_limits`)
- Vendor `suspended` / `cancelled`: hanya `vendor_admin` boleh log masuk, dan hanya halaman `menu.subscription` (langganan, invois, kedai modul);
  pengurus/juruwang ditolak & sesi sedia ada ditamatkan (`login.php?reason=suspended`)
- Lupa kata laluan: `owner|vendor/forgot-password.php` & store `forgot-password` — jawapan sentiasa sama, maks 3 e-mel/jam per alamat;
  token 1 jam. Jemputan kakitangan (owner & vendor) = token `invite` 7 hari → `owner|vendor/reset-password.php?token=`
- Pengesahan e-mel ibu bapa: token 24 jam, pautan `store/index.php?verify_token=` (disahkan oleh `layout.js` sebelum sesi dimuat)
- Kata laluan minimum: panel 10 aksara, ibu bapa 8. Log keluar = POST + CSRF (`data-panel-logout`)
- PIN POS: `PosPinLoginJob` (B2) — endpoint `vendor/pos/pin-login` disambung dalam B10

## 12. Pelbagai bahasa — ms · en · zh · ta

- Bahasa lalai **Bahasa Melayu**. Pengguna boleh tukar; koperasi ada `default_language`
- `t()` wujud **sejak U1** — tiada teks paparan ditulis terus dalam HTML
- Kunci dalam Bahasa Inggeris (`order.status.ready`), nilai dalam bahasa masing-masing
- Kunci hilang → nilai Melayu → kunci itu sendiri. **Jangan sekali-kali kosong**
- **Jangan cantum serpihan terjemahan** — guna placeholder: `t('pickup.collected_by', ['name' => $n, 'class' => $c])`
- Data vendor (nama produk) tidak diterjemah kecuali vendor isi `product_translations`
- E-mel kepada ibu bapa ikut bahasa ibu bapa; resit ikut `default_language` koperasi
- Fon Noto Sans SC dan Noto Sans Tamil dimuatkan supaya aksara Cina & Tamil dipapar betul
- Peringkat 1: `lang/*.php` ditulis tangan. Peringkat 2: dijana dari jadual `translations`

## 13. Payment gateway

**Diputuskan pengguna:** vendor boleh pilih mana-mana gateway yang disokong; wang **terus ke akaun vendor**.
Owner hanya mengutip yuran langganan (melalui gateway owner sendiri).

- `GatewayInterface`: `createBill(Order $o): RedirectInfo`, `verifyCallback(array $req): CallbackResult`, `fetchStatus(string $ref): string`, `supportsRefund(): bool`
- Driver awal: **ToyyibPay** (dibina dahulu), Billplz, SenangPay, iPay88, Stripe. Owner aktifkan driver yang dibenarkan
- Vendor isi kunci API per koperasi; kunci disulitkan `sodium_crypto_secretbox` dengan `config/secret.key` (luar git)
- **Jangan percaya redirect pelanggan.** `paid` hanya selepas:
  1. callback/webhook disahkan tandatangan, **dan**
  2. semakan semula status ke API gateway, **dan**
  3. amaun & mata wang sama dengan pesanan dalam DB
- `payments.gateway_ref` UNIQUE → callback berganda tidak menggandakan bayaran (idempotent)
- Setiap callback dilog dalam `webhook_logs`
- Tunai: pesanan `unpaid_cash`, stok ditahan sehingga `expires_at` (`cash_order_expiry_days`), dibayar di POS semasa ambil

**Pelaksanaan B8:**
- Antara muka sebenar (array, bukan objek): `createBill($bill) → { gateway_ref, redirect_url, form }`, `verifyCallback($request) → { signature_valid:
  true|false|null, gateway_ref }`, `fetchStatus($ref, $amount) → { status: paid|failed|pending, amount, currency, raw }`, `amountLocked()`,
  `testConnection()`, `supportsRefund()`, `callbackResponse()`
- Tandatangan: **Billplz** X-Signature (HMAC-SHA256, medan `kunciNilai` disusun, `|`); **SenangPay** HMAC-SHA256 (`secret+status_id+order_id+
  transaction_id+msg`); **iPay88** SHA256 (`key+code+PaymentId+RefNo+amaun_tanpa_titik+MYR+Status`, jawab `RECEIVEOK`); **Stripe**
  `Stripe-Signature` (t + v1, toleransi 5 minit); **ToyyibPay tiada tandatangan** → `signature_valid null`, disahkan sepenuhnya melalui
  `getBillTransactions` dengan kunci rahsia (log `… (unsigned)`)
- Semakan semula: ToyyibPay `getBillTransactions`, Billplz `GET /v3/bills/{id}`, SenangPay `query_order_status`, iPay88 `enquiry.asp` (dengan amaun —
  "00" hanya jika sama), Stripe `GET /v1/checkout/sessions/{id}`. `amountLocked()` = SenangPay (amaun dalam hash pautan)
- Panggilan balik: tandatangan salah → 400; rujukan tiada / milik koperasi lain → 200 tanpa perubahan; API semakan gagal → 502 (gateway cuba semula)
- Redirect ibu bapa: `store/payment-return.php` (GET/POST, parameter gateway dibuang) → `order-success?returned=1` → `store/orders/check-payment`
  (pelayan bertanya sendiri ke gateway). Redirect palsu "berjaya" tanpa bayaran sebenar → pesanan kekal `pending_payment`
- Luput `pending_payment`: `expires_at` = masa checkout + `online_payment_expiry_minutes` (config, lalai 60). `cron/expire-online-orders.php` (setiap
  10 minit) menyemak semula bil dahulu (panggilan balik hilang → `paid`), gateway rosak → cuba lagi sehingga 24 jam, kemudian `expired` + lepas tahanan
- Wang diterima selepas pesanan dibatal / luput (atau bil kedua) → `payments.paid`, pesanan kekal, log nota `payment.paid_after_close` → refund B11
- Bil `pending` yang sama diguna semula 30 minit (`StartOnlinePaymentJob::REUSE_MINUTES`); gateway gagal mencipta bil → pesanan tetap disimpan,
  `payment: null` + `payment_error`; ibu bapa tekan "Bayar" semula

## 14. Langganan & harga

- Kitaran **bulanan** atau **tahunan**
- Koperasi pertama termasuk dalam harga pelan. Koperasi tambahan ikut `plan_coop_tiers` —
  **owner menetapkan harga**; lalai sama seperti koperasi pertama
- Modul add-on & template premium dicaj **per koperasi**, ikut kitaran langganan
- Tambah koperasi/modul di tengah kitaran → **prorata**, `invoice_lines.is_prorated`
- Invois `draft → issued → paid`. Bayar online melalui gateway owner atau owner tanda manual
- Tempoh tangguh 7 hari selepas `due_at` → `past_due` → `suspended`. Store ibu bapa papar
  *"Koperasi ditutup sementara"*; data kekal; vendor_admin boleh log masuk untuk bayar sahaja
- Trial (lalai 14 hari, owner set)
- Had pelan dikuatkuasakan dalam job simpan (`PlanLimit`), bukan hanya UI
- Invois ialah snapshot — perubahan harga tidak mengubah invois lama

## 15. Keselamatan — senarai semak wajib

- [ ] `vendor_id` & `customer_id` dari sesi/token sahaja; setiap pertanyaan menapis
- [ ] `cooperative_id` disahkan milik vendor (`ValidateCooperativeJob`)
- [ ] Tiada SQL dalam `api/`; PDO prepared statement, `EMULATE_PREPARES` dimatikan
- [ ] Pengguna MySQL `koperasi_user` dengan akses `koperasi_saas` sahaja (bukan root)
- [ ] Argon2id 19456/2/1; `password_needs_rehash`; mesej ralat sama; kunci selepas 5 gagal
- [ ] 2FA TOTP wajib owner, pilihan vendor_admin
- [ ] `session_regenerate_id(true)`; cookie `HttpOnly`, `Secure`, `SameSite=Lax`; sesi idle tamat (vendor 2 jam, ibu bapa 30 hari "ingat saya")
- [ ] CSRF pada semua POST
- [ ] Header: `Content-Security-Policy` (tiada inline script/style — sebab lain larangan inline CSS), `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Strict-Transport-Security`
- [ ] `e()` (`htmlspecialchars`) pada setiap output PHP; JS guna `textContent`, bukan `innerHTML` untuk data
- [ ] Muat naik: `finfo` (bukan sambungan), maks 2MB, nama hash rawak, folder per vendor, `.htaccess` halang PHP
- [ ] Rate limit: log masuk, daftar, lupa kata laluan, kod ambil, API Flutter
- [ ] Harga, promosi, kit, stok dikira semula di pelayan; stok `SELECT … FOR UPDATE` dalam DB transaction
- [ ] Webhook: tandatangan + semakan semula + amaun + idempotent
- [ ] Kredensial gateway & TOTP disulitkan; `config/database.php` & `secret.key` dalam `.gitignore`
- [ ] Token (reset, verify, API) disimpan sebagai hash SHA-256, ada tarikh luput
- [ ] **PDPA**: data anak minimum (tiada IC), persetujuan direkod, padam akaun = nyahnama data peribadi tetapi rekod jualan vendor kekal
- [ ] Log audit untuk tindakan sensitif (tukar harga, refund, tukar gateway, gantung vendor)
- [ ] Backup DB harian (cron `mysqldump`)
- [ ] Ujian kebocoran **setiap session backend**: rekod VENDOR01 dicapai sebagai VENDOR02 → 403/404; pesanan ibu bapa A dicapai ibu bapa B → 404

## 16. Perkara yang mudah tersilap

1. **Kebocoran antara vendor** — risiko nombor satu (bahagian 8)
2. **Checkout mesti dalam DB transaction** — pesanan, item, tahan stok, guna promosi: semua atau tiada
3. **Kunci stok `SELECT … FOR UPDATE`** — musim pembukaan sekolah, ratusan ibu bapa beli kit serentak
4. **Stok ditahan (`qty_reserved`) bila pesan, ditolak bila diambil.** Pesanan dibatal/luput → lepaskan tahanan
5. **No. pesanan & kod pendek dijana dalam transaction yang sama**, unik per koperasi
6. **Snapshot** nama produk, harga, nama anak, darjah, kelas dalam `order_items`. Anak naik darjah tahun depan — rekod tahun ini mesti kekal "Darjah 3"
7. **Kit bukan harga pakej** — kit hanya cara memilih item; setiap item dicaj harga semasanya
8. **Jangan percaya redirect gateway** (bahagian 13)
9. **utf8mb4 pada sambungan PDO**, bukan hanya pada jadual. Import SQL guna `mysql --default-character-set=utf8mb4` — di Windows `mysql.exe` lalai `cp850` dan merosakkan aksara Cina/Tamil/emoji **tanpa amaran** (disahkan dalam projek KedaiPOS). Fail SQL UTF-8 tanpa BOM, baris pertama `SET NAMES utf8mb4;`
10. **Jangan cantum serpihan terjemahan** (bahagian 12)
11. **Tiada inline CSS** — termasuk dalam HTML yang dijana JS (guna kelas Bootstrap, `classList`)
12. **E-mel melalui queue**, bukan terus semasa checkout
13. **Tahun persekolahan bukan tahun kalendar sentiasa** — kit disimpan ikut `school_year`, musim pembukaan sekolah mungkin bermula Disember

## 17. Prinsip yang diwarisi dari KedaiPOS (pos-system)

Repo `kabuto86/pos-system` mempunyai pelan KedaiPOS yang sudah dikaji. Yang diambil terus:

| Perkara | Dari KedaiPOS |
|---|---|
| vendor_id dari sesi, job terima id melalui constructor, tiada SQL dalam api | Bahagian 3 |
| Seed dua vendor sejak hari pertama | 3.4 |
| Argon2id 19456/2/1 (diukur 123ms vs lalai 801ms) | 4.4 |
| `t()` sejak hari pertama, kunci Inggeris, jatuh balik, placeholder | 7.9–7.11 |
| Snapshot nama/harga dalam transaksi | 6.D |
| Perangkap `mysql.exe` cp850 | 15.1 |
| Logik troli/jumlah POS mod runcit (`calcUnitPrice`, `getTotals`) | 16 — dirujuk semasa B10 |

## 18. Kontrak data (diisi semasa Peringkat 1)

> Setiap session U menambah di sini bentuk objek yang digunakan dalam `assets/js/mock/*.js`.
> Backend (Peringkat 2) mesti memulangkan bentuk yang sama.

### 18.1 Bentuk jawapan (U1)

Setiap `Api.get(path, params)` / `Api.post(path, body)` memulangkan **Promise** yang sentiasa
*resolve* (tidak pernah *reject*) kepada salah satu:

```js
{ ok: true,  data: ... }
{ ok: false, error: "Mesej untuk pengguna", errors: { field: "mesej" } }   // errors boleh {}
```

- `path` tanpa `/api/v1/` dan tanpa `.php`: `'vendor/cooperatives/list'` → Peringkat 2:
  `GET /api/v1/vendor/cooperatives/list.php?...`
- Mesej `error` sudah diterjemah (bahasa pengguna). Halaman terus papar dengan `showToast(res.error, 'danger')`
- Ralat rangkaian (Peringkat 2) juga dipulangkan sebagai `{ ok: false, error: T('error.network') }`

### 18.2 Format senarai (U1)

Semua endpoint senarai menerima `page` (lalai 1), `per_page` (lalai 20, maks 100), `q` (carian)
dan tapisan khusus modul. Jawapan:

```js
{ ok: true, data: { items: [ ... ], total: 42, page: 1, per_page: 20 } }
```

Dalam mock: `Mock.paginate(rows, params, ['medan_carian', ...])`.

### 18.3 Mock (Peringkat 1 sahaja)

- `assets/js/mock/mock.js` — `Mock.register(path, handler)`, `Mock.ok(data)`, `Mock.fail(msg, errors)`,
  `Mock.paginate()`. `Mock.handle()` memulangkan **salinan dalam** supaya halaman tidak mengubah data mock
- `mock/owner.js`, `mock/vendor.js` (+ `vendor-*.js`) — dimuat oleh layout masing-masing. **Sejak B7** store tidak memuat sebarang fail mock
  (`mock/store.js` dibuang; jambatan `Mock.onLive`, `Mock.afterLive`, `Mock.beforeFirst`, `Mock.ready` juga dibuang dari `mock.js` / `api.js`)
- `config/app.php` `use_mock => false` (Peringkat 2) → skrip mock tidak dimuat; hanya `Api.request()` ditukar
- **Peralihan (sejak B2):** `Api.livePrefixes` dalam `api.js` = laluan yang sudah sebenar (`fetch`, POST JSON + `X-CSRF-Token`);
  selebihnya masih mock. Setiap session B **menambah awalan** modulnya dan membuang `Mock.register` laluan itu. Jambatan sementara:
  `Mock.onLive(laluan, fn)` (selepas jawapan sebenar) dan `Mock.beforeFirst(fn)` (sekali sebelum jawapan mock pertama) — cth.
  `mock/store.js` menyelaraskan `MockStore.session` dengan ibu bapa sebenar (dipadankan ikut e-mel) supaya pesanan mock (B7) ditapis
  ibu bapa sesi. **Sejak B6** sekolah, kelas, anak, katalog, produk, kit, troli, berita & jualan murah store sebenar; `mock/store.js` hanya
  tinggal checkout & pesanan (B7) + simulasi bayaran (B8). Jambatan B6→B7: halaman checkout memuat troli SEBENAR (`store/cart/get`) melalui
  `Mock.beforeFirst`, checkout mock mengira promosi/cukai/slot atas baris itu (pesanan mock bertanda `live_cart`, stok mock tidak ditahan) dan
  mengosongkan troli sebenar (`store/cart/clear`, `fetch keepalive`). Sekolah yang tiada dalam mock (`smk-seri-indah`) → checkout gagal sehingga B7. **Sejak B4** laluan koperasi, kelas, kategori, produk, inventori, pembekal, PO,
  stock take, papan pemuka, `templates/options` & `modules/active` sebenar; `mock/vendor.js` hanya menyimpan data contoh (`MockVendor`, `sessionVendorId = 1`)
  untuk mock modul lain (pesanan vendor B9, POS B10, add-on B13) — id mock tidak sepadan dengan DB. **Sejak B7** checkout & pesanan ibu bapa
  sebenar; pesanan store TIDAK lagi muncul dalam panel vendor mock sehingga B9 (`vendor/orders/*` sebenar). **Sejak B5** `vendor/kits/*` sebenar;
  `MockOrders.kits` dibuang dalam B6 (store/kit sebenar).
  Sejak B3 `mock/owner.js` hanya mengandungi langganan & invois (B12) dan terjemahan (B14); data vendor/pelan di dalamnya hanya untuk
  ringkasan laluan itu (id mock tidak sepadan dengan DB)
- `vendor_id` dalam mock datang dari `MockVendor.sessionVendorId` (meniru sesi) — tidak pernah dari parameter
- **Persistence (sejak U3a):** `Mock.persist(nama, objek, [kunci])` menyimpan data mock dalam `sessionStorage`
  selepas setiap POST yang berjaya, supaya rekod baharu kekal bila halaman bertukar (seperti DB sebenar).
  Hilang bila tab ditutup; `Mock.resetStores()` di konsol untuk kembali ke data contoh. Naikkan
  `Mock.storageVersion` bila bentuk data mock berubah

### 18.4 Objek U1

**Cooperative (vendor)** — `vendor/cooperatives/list` (format senarai, `q` pada nama/kod/slug)
```js
{ id: 1, vendor_id: 1, slug: 'sk-taman-melati', school_name: 'SK Taman Melati', school_code: 'BBA1234',
  school_type: 'SK', state: 'Selangor', district: 'Gombak', status: 'active' }
```

| Laluan | Kaedah | Input | `data` |
|---|---|---|---|
| `owner/auth/login` | POST | `email, password` | `{ requires_2fa: true, setup: { secret, otpauth_url } \| null }` — `setup` hanya jika 2FA belum disediakan (B2) |
| `owner/auth/verify-2fa` | POST | `code` (6 digit atau kod pemulihan) | `{ user: { id, name, role }, recovery_codes: [8] \| null }` |
| `vendor/auth/login` | POST | `email, password` | `{ user: { id, name, role }, requires_2fa: false, billing_only }` · ada 2FA → `{ user: null, requires_2fa: true }` |
| `vendor/auth/verify-2fa` (B2) | POST | `code` | `{ user: { id, name, role } }` |
| `owner\|vendor/auth/logout` · `forgot { email }` · `reset { token, password, password_confirm }` (B2) | POST | | `forgot` sentiasa `{ sent: true }`; `reset` terima token `reset_password` atau `invite` |
| `vendor/cooperatives/list` | GET | `page, per_page, q, status` | senarai Cooperative (+ `template_name, class_count, product_count, modules`); pengurus/juruwang: koperasi yang diberi sahaja (B2) |
| `vendor/session/cooperative` | POST | `cooperative_id` | `{ cooperative }` — pelayan sahkan milik vendor (`ValidateCooperativeJob`) + akses kakitangan, simpan dalam sesi |
| `vendor/modules/active` | GET | `cooperative_id` | `{ cooperative_id, modules: ['news', ...] }` |
| `store/school` | GET | `slug` | `{ id, slug, school_name, school_type, state, district, payment_methods: ['cash','online'], default_language }` |
| `store/cart/summary` | GET | — | `{ item_count }` |

Mock demo: kata laluan `salah` → ralat log masuk; kod 2FA `000000` → ditolak.

### 18.5 Konvensyen UI (U1)

- Halaman panel: `require core/bootstrap.php` → set `$pageTitle`, `$activeMenu` (kunci menu, cth.
  `'menu.orders'`), `$pageScripts` (pilihan) → `require _layout/header.php` → kandungan →
  `require _layout/footer.php`
- Teks JS: PHP mengeluarkan semua teks (bahasa semasa + jatuh balik Melayu) dalam
  `<body data-lang="{json}">`; `lang.js` membacanya ke `window.LANG`. Tiada skrip inline
- CSP (`core/SecurityHeaders.php`): `script-src 'self'; style-src 'self'` — skrip/gaya inline
  akan disekat pelayar, jadi pelanggaran kelihatan serta-merta
- Bahasa: `?lang=xx` → cookie `lang` (1 tahun) → lalai `ms`. Tukar bahasa = muat semula halaman
- Koperasi semasa (vendor): `currentCooperativeId()`, `currentCooperative()`. Halaman vendor memuat data melalui
  `onCooperativeReady(fn)` (dipanggil sekarang + setiap kali koperasi ditukar) dan menghantar parameter melalui
  `withCoop(params)` (tambah `cooperative_id`). Halaman butiran satu rekod (produk, PO, stock take) kembali ke
  senarai jika koperasi ditukar
- Sub-navigasi vendor: halaman set `$subNavGroup` + `$subNavActive` (kumpulan dalam `vendor/_layout/sub-nav.php`)
- Menu add-on vendor: dirender berkunci (pautan ke `vendor/addon-store.php?module=code` — sejak U3c, terus membuka anggaran harga);
  dibuka oleh `vendor-layout.js` jika `vendor/modules/active` mengandungi kodnya
- Store Peringkat 1: `store/index.php?school={slug}&page={halaman}` — `View::storeUrl($slug, $page, $params)` / JS `storeUrl()`.
  Tanpa slug hanya `home, school-select, login, register, forgot-password, profile, children` (akaun global); halaman lain → kembali ke utama.
  `&template={kod}` = pratonton template — **sejak B6** hanya owner (sesi KOPOWNER) atau vendor pemilik koperasi itu (sesi KOPVENDOR), dan hanya
  jika folder lengkap (`ResolveStoreTemplateJob::folderReady`). **URL cantik (B6):** `/{slug}/` & `/{slug}/{halaman}` melalui `.htaccess` akar +
  `config pretty_store_urls = true` (Apache mod_rewrite); `View::storeUrl()` & JS `storeUrl()` (`data-pretty-url` pada `#store-context`) menjana
  bentuk itu. Lalai `false` (php -S / ujian) — `?school=` tetap diterima
- Helpers: `formatRM(sen)`, `formatDate(iso, withTime)`, `showToast(msg, type)`,
  `confirmDialog(msg, {title, okText, okClass})` → Promise<boolean>,
  `renderTable(tbody, items, [{key, className, render}])`, `statusBadge(status)`, `createEl(tag, class, text)`
- Halaman modul add-on (sejak U6): set `$moduleCode = 'news'` sebelum header → layout memapar `module-gate.php` (kad "Langgan modul ini",
  pautan `addon-store.php?module=`) dan membungkus kandungan dalam `#module-content`. JS halaman guna `onModuleReady(fn)` (dari
  `assets/js/vendor/addon-common.js`, bukan `onCooperativeReady`) — fn dipanggil hanya jika modul aktif untuk koperasi semasa; jawapan API
  disemak dengan `moduleResponse(res)` (`module_locked: true` → kad langgan). Butang `$pageActions` disembunyikan semasa berkunci
- Data pengguna tidak pernah dimasukkan melalui `innerHTML` — guna `textContent` / `createEl()`
- Semakan sebelum commit: `php tools/check-ui.php` — juga memastikan setiap `t('kunci')` / `T('kunci')`
  literal wujud dalam `lang/ms.php`. Kunci dinamik (cth. `T('vendor.status.' + status)`) mesti disemak sendiri

### 18.6 Konvensyen borang & senarai (U2)

- Medan borang (dibaca oleh `formToObject()` / diisi oleh `fillForm()` dalam `helpers.js`):
  `name="names.ms"` → objek bersarang · `data-type="money"` → input RM, dihantar sebagai **sen** ·
  `data-type="int"` (kosong → `null`) · `data-type="lines"` → array baris · checkbox → `true/false` ·
  checkbox `data-multi` → array nilai
- `bindForm(form, path, onSuccess, buildPayload)` = pengesahan Bootstrap → `Api.post` → `errors` dari pelayan
  ditanda `is-invalid` pada medan bernama sama (`{ ok:false, errors: { 'names.ms': '...' } }`)
- Senarai: `bindListFilters(form, state, load)` + `renderTable()` + `renderPagination()`; `state` = parameter
  `page, per_page, q, …` terus ke `Api.get()`
- Medan rahsia (kunci gateway, kata laluan SMTP) **tidak pernah dipulangkan**; API pulangkan `*_set` / `secrets_set`,
  dan nilai kosong semasa simpan = kekalkan nilai lama
- Tindakan sensitif owner direkod dalam log audit (mock: `MockOwner.audit()`; B3: jadual `audit_logs`)

### 18.7 Objek owner (U2) — `assets/js/mock/owner.js`

Wang dalam sen. Tarikh ISO 8601 (`+08:00`) atau `YYYY-MM-DD` untuk tarikh sahaja.

**VendorSummary** — `owner/vendors/list` (senarai; tapis `q, status, plan_id`)
```js
{ id, code: 'VENDOR01', company_name, contact_name, email, phone,
  status: 'trial|active|past_due|suspended|cancelled', trial_ends_at, plan_id, plan_name,
  billing_cycle: 'monthly|yearly', cooperative_count, current_period_end, created_at }
```
**VendorDetail** — `owner/vendors/get {id}` = semua medan `vendors` + `plan_name`, `subscription` (SubscriptionSummary),
`cooperatives` [CooperativeSummary], `invoices` [InvoiceSummary], `notes` [{ id, body, created_by, created_at }]

**CooperativeSummary** — `owner/cooperatives/list` (senarai; tapis `q, vendor_id, status, school_type`)
```js
{ id, vendor_id, vendor_code, vendor_name, slug, school_name, school_code, school_type, state, district,
  status: 'active|suspended|closed', template_code, template_name, modules: ['news'], created_at }
```
**Plan** — `owner/plans/list` (array, bukan senarai bertapis)
```js
{ id, code, name, price_monthly, price_yearly, max_cooperatives, max_products, max_staff,  // null = tanpa had
  features: ['...'], is_active, sort_order, vendor_count }
```
**PlanTiers** — `owner/plans/tiers {plan_id}` → `{ plan: Plan, tiers: [{ id, from_coop, to_coop, price_monthly, price_yearly }] }`
(`to_coop: null` = dan seterusnya). Simpan: `owner/plans/save-tiers { plan_id, tiers }` — pelayan sahkan berturutan
(mula 2, `from = to sebelumnya + 1`, terakhir `null`)

**Module** — `owner/modules/list`
```js
{ id, code, names: { ms, en, zh, ta }, descriptions: { ms, en, zh, ta }, price_monthly, price_yearly,
  is_core, is_active, sort_order, cooperative_count }
```
**Template** — `owner/templates/list`
```js
{ id, code, name, folder, preview_image /* url atau null */, description, price_monthly, price_yearly,
  is_free, is_active, cooperative_count }
```
**SubscriptionSummary** — `owner/subscriptions/list` (senarai; tapis `q, status, billing_cycle, plan_id`)
```js
{ id, vendor_id, vendor_code, vendor_name, plan_id, plan_name, billing_cycle, status,
  started_at, current_period_start, current_period_end, cooperative_count }
```
**InvoiceSummary** — `owner/invoices/list` (senarai; tapis `q, status, vendor_id`)
```js
{ id, invoice_no, vendor_id, vendor_code, vendor_name, period_start, period_end, subtotal, tax, total,
  status: 'draft|issued|paid|void', issued_at, due_at, paid_at }
```
**InvoiceDetail** — `owner/invoices/get {id}` = InvoiceSummary + `vendor { id, code, company_name, registration_no, address, email }`,
`lines [{ description, item_type: 'plan|cooperative|module|template', qty, unit_price, amount, is_prorated }]`,
`payments [{ method: 'online|bank_transfer|cash|cheque|other', gateway_code, reference, amount, paid_at, note }]`, `sst_rate`

**Gateways** — `owner/gateways/list` → `{ drivers: [{ code, name, allowed_for_vendors, supports_refund, vendor_count,
fields: [{ name, secret }] }], owner_gateway: { driver_code, sandbox, values: { medan_bukan_rahsia }, secrets_set: ['secret_key'] } }`

**Translation** — `owner/translations/list` (senarai; tapis `q, group, missing: ''|any|ms|en|zh|ta`)
→ item `{ key, group, values: { ms, en, zh, ta }, missing: ['ta'] }`; `data` juga ada `groups: []`, `missing_counts: { ms, en, zh, ta }`

**Grade** — `owner/grades/list` → `{ code: 'PRA|D1…D6|T1…T6', level_type: 'preschool|primary|secondary', sort_order, names: { ms, en, zh, ta } }`

**EmailTemplate** — `owner/email-templates/list` → `[{ template_key, languages: { ms: { subject, updated_at } | null, … } }]`;
`get {template_key, language_code}` → `{ template_key, language_code, subject, body_html, updated_at, placeholders: ['{order_no}', …] }`.
Kunci: `order_new_vendor, order_receipt, order_ready, order_collected, order_paid` (B9), `invoice_issued, password_reset`.
Pelayan menolak pemegang tempat yang tiada dalam `placeholders`

**Announcement** — `owner/announcements/list` (senarai)
```js
{ id, title, body, target: 'all|selected', vendor_ids: [], vendor_names: ['VENDOR04'], published_at /* null = draf */, created_by, created_at }
```
**AuditLog** — `owner/audit-logs/list` (senarai; tapis `q, actor_type, date_from, date_to`)
```js
{ id, actor_type: 'owner|vendor_user|customer|system', actor_name, vendor_code, action: 'vendor.suspend', entity, entity_id, detail, ip_address, created_at }
```
**PlatformSettings** — `owner/settings/get`
```js
{ platform_name, support_email, support_phone, smtp_host, smtp_port, smtp_encryption: 'tls|ssl|none', smtp_username,
  smtp_password_set, mail_from_address, mail_from_name, sst_rate /* % */, sst_registration_no, trial_days, grace_days, default_language }
```
**OwnerStaff** — `owner/staff/list` → `{ id, full_name, email, role: 'owner|owner_staff', is_active, totp_enabled, last_login_at }`

**Profil & 2FA** — `owner/profile/get` → `{ id, full_name, email, language, totp_enabled }`;
`owner/two-factor/setup` → `{ enabled, secret, otpauth_url }`; `enable {code}` / `regenerate-codes {code}` → `{ recovery_codes: [8] }`.
QR dijana dalam pelayar (`assets/lib/qrcode/`) — rahsia tidak dihantar ke perkhidmatan luar. Owner tidak boleh mematikan 2FA

| Laluan tindakan (POST) | Input | Nota |
|---|---|---|
| `owner/vendors/set-status` | `id, status: active\|suspended, reason` | `reason` wajib untuk gantung; disimpan sebagai nota |
| `owner/vendors/add-note` | `id, body` | |
| `owner/plans/save` · `delete` | Plan / `id` | pelan baharu → satu peringkat lalai (harga sama); padam ditolak jika ada vendor |
| `owner/modules/save` | Module | `code` `^[a-z][a-z0-9_]+$`, unik, tidak boleh ditukar |
| `owner/templates/save` | `id, name, description, is_free, is_active, price_*` | `template1` sentiasa percuma & aktif |
| `owner/invoices/issue` · `void` · `mark-paid` | `id` (+ `method, reference, paid_at, note`) | tanda dibayar hanya untuk `issued` |
| `owner/gateways/toggle` | `code, allowed` | |
| `owner/gateways/save-owner` | `driver_code, sandbox, credentials: {}` | rahsia kosong = kekal |
| `owner/translations/save` | `key, values` | `values.ms` wajib |
| `owner/grades/save` | Grade | |
| `owner/email-templates/save` | `template_key, language_code, subject, body_html` | |
| `owner/announcements/save` · `delete` | Announcement + `publish: bool` | yang sudah terbit tidak boleh disunting |
| `owner/settings/save` · `test-email` | PlatformSettings + `smtp_password` / `to` | |
| `owner/staff/save` · `set-active` · `reset-2fa` | | tidak boleh nyahaktif / turun peranan diri sendiri |
| `owner/profile/save` · `change-password` | | kata laluan ≥ 10 aksara |

Mock demo: kata laluan semasa `salah` → ralat; kod 2FA `000000` → ditolak.

**Sejak B3 (sebenar):** semua laluan owner di atas kecuali `subscriptions/*`, `invoices/*` (B12) dan `translations/*` (B14).
- `owner/plans/options` (baharu) → `[{ id, code, name, is_active }]` — tapisan pelan di senarai vendor (owner_staff tidak boleh `plans/list`)
- `owner/vendors/set-status` hanya `owner`; peralihan: `active` ← trial/past_due/suspended, `suspended` ← trial/active/past_due (lain → ralat).
  Langganan terkini mengikut status; sebab → `vendor_notes`. `add-note` dibenarkan untuk `owner_staff` (sokongan)
- `VendorDetail.subscription` boleh `null` (vendor tanpa langganan); `notes` terkini dahulu, `created_by` = nama pengguna owner
- Nama modul, keterangan modul & nama darjah: nilai owner dalam jadual `translations` (`module.{code}`, `module.{code}.description`,
  `grade.{code}`) mengatasi `lang/*.php`; bahasa kosong = `''` (buang baris → kembali ke fail bahasa). Kod modul tidak boleh ditukar
- `owner/dashboard/summary`: MRR = harga pelan + `subscription_items` yang masih berjalan (snapshot `unit_price`) bagi langganan
  `active|past_due`, tahunan ÷ 12; `mrr_history` 6 bulan dikira dengan cara sama (bulan lalu: langganan bukan trial yang sudah bermula &
  belum dibatalkan); `expiring` = tamat dalam 30 hari; pesanan bulan ini = bukan cancelled/expired, nilai = `paid_at` ada
- Gateway owner disimpan dalam `platform_settings` (`owner_gateway_code|mode|config` + `owner_gateway_secrets_enc` = JSON rahsia disulitkan);
  tukar driver = rahsia lama tidak dibawa. `smtp_password_enc` disulitkan; `sst_rate` nombor (cth. `8`)
- `owner/settings/test-email` → `email_queue` dengan kunci `test_email` (teks `email.test_email.*` dalam lang)
- Templat e-mel: senarai kunci & pemegang tempat dalam `jobs/Notification/EmailTemplateCatalog.php`; seed mengandungi templat contoh
- `CalculateCoopPriceJob($planId, $bilanganKoperasi, 'monthly'|'yearly')` → `{ plan_price, lines: [{ coop_no, price }], extra_total, total }`
  (koperasi ke-1 = 0; tiada peringkat meliputi n → harga pelan). Digunakan oleh invois/langganan (B12)

### 18.8 Objek vendor bahagian 1 (U3a) — `assets/js/mock/vendor.js`

Setiap laluan vendor (kecuali pembekal & templat) menerima `cooperative_id` dan pelayan **mesti** mengesahkan ia milik
vendor sesi (`ValidateCooperativeJob`); bukan milik → `{ ok:false, error: 'Data tidak dijumpai.' }` (sama seperti tiada,
tidak mendedahkan kewujudan). Rekod anak (produk, PO, stock take, kelas) dicari dengan `vendor_id` + `cooperative_id` + `id`.

**Dashboard** — `vendor/dashboard/summary {cooperative_id}`
```js
{ sales_today, sales_today_count, orders_to_prepare, orders_ready, low_stock_count,
  low_stock: [InventoryRow ×6], sales_7days: [{ date: 'YYYY-MM-DD', amount }], announcements: [{ id, title, body, published_at }] }
```
**Cooperative** — `vendor/cooperatives/get {id}` (senarai: `vendor/cooperatives/list` → ringkasan + `template_name, class_count, product_count, modules`)
```js
{ id, slug, school_name, school_code /* AAA9999 */, school_type, state, district, address, phone, logo_url,
  template_id, default_language, accept_cash, accept_online, status, created_at,
  opening_hours: { mon: { open: true, from: '07:30', to: '13:00' }, … sun } }
```
- `vendor/cooperatives/pricing-preview` → `{ cooperative_number, plan_name, billing_cycle, price_monthly, price_yearly, current_period_end, max_cooperatives, limit_reached }`
- `vendor/cooperatives/check-slug {slug, id}` → `{ slug, valid, available }` — unik seluruh platform, tidak dedah pemilik
- `vendor/templates/options` → `[{ id, code, name, is_free, price_monthly, price_yearly, subscribed }]`
- `vendor/cooperatives/save` — had pelan (`PlanLimit`) disemak; sekurang-kurangnya satu kaedah bayaran; template mesti dilanggan

**Kelas** — `vendor/grades/list {cooperative_id}` → `['PRA','D1',…]` ikut jenis sekolah (SK/SJKC/SJKT: PRA–D6; SMK/SMKA/SBP: T1–T6);
`vendor/classes/list` → `[{ id, grade_level_code, class_name, sort_order }]`; `add { grade_level_code, names: [] }` (pendua ditolak),
`update { id, class_name }`, `delete { id }`

**Category** — `vendor/categories/list` → `[{ id, name, sort_order, is_active, product_count }]`; `save`, `delete` (ditolak jika ada produk)

**Product** — `vendor/products/get {id}` (senarai `list`: tapis `q, category_id, grade ('' | 'all' | kod), status, low_only`)
```js
{ id, cooperative_id, category_id, name, description, sku, barcode, price, cost_price, unit, image_url,
  max_per_order /* null = tiada had */, has_variants, is_active, sort_order,
  grade_levels: ['D1'] /* [] = semua darjah */,
  variants: [{ id, label, sku, barcode, price_delta, is_active }],
  translations: { en: { name, description }, zh: {…} } /* pilihan; bahasa kosong tidak dihantar */ }
```
Ringkasan senarai: `{ id, name, sku, barcode, category_id, category_name, price, unit, image_url, has_variants, variant_count,
grade_levels, stock_available, is_low, is_active }`. SKU & barcode unik dalam koperasi. Varian baharu → baris stok 0.
`vendor/products/options` → `[{ product_id, variant_id /* 0 = tiada */, name, sku, cost_price }]` (untuk PO).
Gambar Peringkat 1: data URL; Peringkat 2: muat naik berasingan (`finfo`, ≤ 2MB, `uploads/{vendor_id}/{cooperative_id}/`)

> **B4 (sebenar):** `image_url` / `logo_url` dalam badan simpan: data URL = gambar baharu (disahkan `core/Uploader` — finfo + getimagesize,
> JPG/PNG/WebP ≤ 2MB, nama fail = SHA-256) · kosong/`null` = buang · URL sedia ada = kekal. Fail: `uploads/{vendor_id}/{cooperative_id}/products|logo/`.
> SKU & barcode unik dalam koperasi **merentas produk dan varian** (tanpa beza huruf besar). Varian/baris tanpa varian yang sudah ada stok atau
> sejarah (pergerakan, pesanan, PO, stock take, kit, troli) tidak boleh dibuang → `product.variant_locked` (nyahaktif sahaja). Had pelan
> `max_products` (semua koperasi vendor) & `max_cooperatives` (koperasi bukan `closed`). Koperasi ke-n ≥ 2 → baris `subscription_items`
> (`item_type = cooperative`, harga peringkat kitaran semasa). No. PO `PO-{tahun}-{0001}` bersiri dalam koperasi. Slug sistem (`api`, `vendor`,
> `store`, …) dilaporkan "sudah digunakan". Pengurus boleh sunting koperasi yang diberi; tambah koperasi & `pricing-preview` pentadbir sahaja.

**InventoryRow** — `vendor/inventory/list` (tapis `q, category_id, low_only`; `data.low_count`)
```js
{ product_id, variant_id, product_name, variant_label, sku, category_id, category_name, unit,
  qty_on_hand, qty_reserved, qty_available /* on_hand − reserved */, min_stock, is_low /* available ≤ min */, is_active }
```
- `vendor/inventory/stock-in { product_id, variant_id, qty ≥ 1, note }` → pergerakan `restock`
- `vendor/inventory/adjust { product_id, variant_id, new_qty, reason: count_correction|damaged|lost|found|other, note, min_stock }`
  → pergerakan `adjust` (tidak boleh di bawah `qty_reserved`)

**StockMovement** — `vendor/inventory/movements` (tapis `q, type, product_id, variant_id, date_from, date_to`)
```js
{ id, cooperative_id, product_id, variant_id, product_name, variant_label, sku,
  type: 'sale|pos_sale|reserve|release|collect|refund|restock|adjust|stocktake|po_receive',
  qty_change, balance_after, ref_type: 'order|purchase_order|stock_take'|null, ref_id, user_name, note, created_at }
```
**Supplier** (peringkat vendor, dikongsi semua koperasi) — `vendor/suppliers/list` (tapis `q, status`)
→ `{ id, name, contact_name, phone, email, address, is_active, open_po_count }`; `save`

**PurchaseOrder** — `vendor/purchase-orders/get {id}` (senarai `list`: tapis `q, status, supplier_id`; tanpa `items`, ada `item_count`)
```js
{ id, cooperative_id, supplier_id, supplier_name, po_no, status: 'draft|ordered|partial|received|cancelled',
  ordered_at, received_at, note, total,
  items: [{ id, product_id, variant_id, product_name, variant_label, sku, qty_ordered, qty_received, unit_cost, amount }] }
```
- `save { id?, supplier_id, note, items: [{ product_id, variant_id, qty_ordered, unit_cost }], submit: bool }` — draf sahaja; `submit` → `ordered`
- `receive { id, lines: [{ item_id, qty }] }` — separa dibenarkan; melebihi baki ditolak; pergerakan `po_receive`; status `partial`/`received`
- `cancel { id }` — draf, atau dipesan tanpa barang diterima

**StockTake** — `vendor/stock-takes/list` → `{ id, status: 'open|completed', started_by, started_at, completed_at, item_count, counted_count, variance_total }`;
`get` + `items: [{ id, product_id, variant_id, product_name, variant_label, sku, category_name, qty_system, qty_counted /* null = belum */ }]`;
`start` (satu terbuka sahaja; salin baki produk aktif), `save-counts { id, counts: [{ item_id, qty_counted }] }`,
`complete { id }` → pergerakan `stocktake` untuk item yang dikira sahaja

### 18.9 Objek vendor bahagian 2 (U3b) — `assets/js/mock/vendor-orders.js`

**Status pesanan vs status bayaran.** `status` ikut PELAN 6. `payment_status: 'paid' | 'unpaid' | 'pending' | 'none'` dipulangkan
berasingan (dikira dari jadual `payments`), kerana pesanan tunai bergerak `unpaid_cash → preparing → ready` dan hanya dibayar
semasa diambil. Skrin serah meminta "Terima bayaran tunai" selagi `payment_status !== 'paid'`.

**Rahsia pesanan:** `pickup_code` (4 digit) tidak pernah dipulangkan oleh mana-mana API vendor (disimpan sebagai hash);
API hanya memulangkan `requires_code` dan `pickup_locked`. `qr_token` hanya dipulangkan oleh `vendor/orders/labels` (untuk dicetak).

**Kit** — `vendor/kits/list { school_year }` → `{ school_year, grades: ['PRA',…], previous_year_count, kits: [KitSummary] }`
```js
KitSummary = { id, grade_level_code, school_year, name, note, is_published, item_count, total_price, mandatory_price }
KitDetail  = KitSummary + { items: [{ id, product_id, variant_id /* null = ibu bapa pilih varian */, product_name, variant_label,
                                      has_variants, unit_price, qty, is_mandatory, note, sort_order, is_active }] }
```
- `vendor/kits/product-options` → `[{ product_id, variant_id|null, name, variant_label, price, has_variants }]` (produk aktif sahaja)
- `save { id?, grade_level_code, school_year, name, note, items: [{ product_id, variant_id, qty, is_mandatory, note }] /* susunan = sort_order */, publish }`
  — UNIQUE(koperasi, darjah, tahun); `set-published { id, is_published }`; `delete { id }` (draf sahaja)
- `copy-year { school_year }` → salin kit `school_year − 1` sebagai draf; darjah sedia ada dilangkau; item produk tidak aktif dibuang
  → `{ copied, skipped, removed_items }`

> **B5 (sebenar, `jobs/SchoolKit/`):** semua 7 laluan `vendor/kits/*` menerima `cooperative_id` (`VendorAuth::requireCooperative`) — kit dicari
> `vendor_id` + `cooperative_id` + `id`. Item hanya produk/varian **aktif** koperasi yang sama; `variant_id` hanya untuk produk bervarian
> (null = ibu bapa pilih saiz); pasangan produk+varian tidak berulang; kuantiti 1–999; ≤ 100 item. `total_price` / `mandatory_price` = harga
> semasa (`price + price_delta`) × qty bagi item aktif sahaja (`item_count` = semua item) — tiada harga disimpan dalam kit (PELAN 16.7).
> Sunting mengekalkan id item yang produk+variannya sama (rujukan `cart_items.school_kit_item_id` untuk B6). `publish` dalam `save` menetapkan
> status (Simpan draf = draf); terbit memerlukan ≥ 1 item aktif. Tahun boleh disimpan/disalin: tahun semasa −1 hingga +2.
> `delete` = draf sahaja, ditolak jika kit ada dalam `order_items` (`kit.delete_has_orders`); baris troli kit itu dibuang bersama.
> Store (B6) WAJIB guna `SchoolKits::findPublished($cooperative, $grade, $year)` — draf tidak pernah dipulangkan (sejak B6: `StoreKitForChildJob`,
> `AddKitToCartJob`).
> Log aktiviti: `kit_save`, `kit_publish`, `kit_unpublish`, `kit_delete`, `kit_copy`.

**OrderSummary** — `vendor/orders/list` (tapis `q` [no., kod pendek, nama murid/ibu bapa, telefon, token QR], `status` [boleh senarai
dipisah koma], `grade`, `class_name`, `payment_method`, `date_from`, `date_to`, `pickup_slot_id` (U7); `data.status_counts: { ready: 7, … }`)
```js
{ id, order_no, short_code, status, payment_method: 'cash|online', payment_status, total, customer_name, customer_phone,
  children: [{ child_name, grade_level_code, class_name }], item_count,
  pickup_slot: { slot_date, time_start, time_end, grade_level_code } | null /* U7 */, created_at, expires_at }
```
**OrderDetail** — `vendor/orders/get {id}` = OrderSummary + `subtotal, discount, tax_rate, tax /* U7: baris cukai dipapar bila > 0 */, customer_email, pickup_by_type: 'student|parent|proxy',
pickup_by_name, requires_code, pickup_locked, paid_at, collected_at`,
`groups: [{ child_id, child_name, grade_level_code, class_name, items: [{ id, product_name, variant_label, sku, unit_price, qty, line_total,
collected_qty, source: 'ala_carte|kit|pos' }] }]` (item dikumpul ikut anak),
`logs: [{ from_status, to_status, actor: 'customer|system|vendor_user', actor_name, note, created_at }]`,
`pickups: [{ method: 'A'…'E', picked_by_type, picked_by_name, user_name, created_at, items: [{ product_name, variant_label, child_name, qty }] }]`

| Laluan (POST) | Input | Peraturan |
|---|---|---|
| `vendor/orders/set-status` | `id, status, note` | `paid/unpaid_cash → preparing → ready`, `ready → preparing`; batal hanya `pending_payment`/`unpaid_cash` (sebab wajib); dibayar → refund (U3c). `collected` hanya melalui serah |
| `vendor/orders/bulk-status` | `ids[], status` | sama seperti di atas (tiada batal pukal) → `{ changed, skipped }` |
| `vendor/handover/receive-cash` | `id, amount_received` (sen) | tunai sahaja, ≥ jumlah → `{ change, order }` |
| `vendor/handover/collect` | `id, picked_by_type, picked_by_name, items: [{ item_id, qty }], code?` | status `ready`/`partially_collected`; dibayar; kod jika `requires_code` (5 salah → `pickup_locked`); qty ≤ baki; → `{ status, parent_notified: true, order }`; pergerakan stok `collect` |

- `vendor/orders/picking-list { status (lalai 'paid,unpaid_cash,preparing'), grade, class_name, pickup_slot_id }` →
  `{ school_name, generated_at, order_count, groups: [{ grade_level_code, class_name, children: [{ child_name, order_no, short_code, status,
  items: [{ product_name, variant_label, qty /* baki belum diambil */ }] }] }], totals: [{ product_name, variant_label, sku, qty }] }`
- `vendor/orders/labels { grade, class_name, pickup_slot_id, order_id? }` → `{ school_name, labels: [{ order_id, order_no, short_code, child_name,
  grade_level_code, class_name, item_count, payment_status, pickup_slot, qr_token }] }` — satu label setiap anak setiap pesanan (slot dicetak pada label)
- `vendor/orders/class-options` → `[{ grade_level_code, class_name }]`
- `vendor/orders/slot-options` (U7) → `[Slot]` semua slot musim koperasi ini; `[]` jika modul `school_opening` tidak aktif → tapisan slot
  disembunyikan di senarai pesanan, picking list & label beg (`bindSlotFilter()` dalam `order-common.js`)
- `vendor/handover/search { q, grade, class_name }` → `{ items: [OrderDetail ×20], settings: { methods: ['A','B','C','D','E'?], code_required_above /* sen, 0 = tiada */ } }`
  — padanan tepat token QR / kod pendek dahulu; jika tidak, pesanan aktif (`paid, unpaid_cash, preparing, ready, partially_collected`)
  disusun `ready` dahulu

> **B9 (sebenar, `jobs/Order/` + `jobs/Customer/`):** semua laluan pesanan/serah/ibu bapa menapis `vendor_id` (sesi) + `cooperative_id`
> (`VendorAuth::requireCooperative` — juruwang/pengurus hanya koperasi yang diberi) melalui pembantu `VendorOrders` (`find/require`, `filters`,
> `summary`, `detail`, `STATUS_FLOW`, `pickupSettings`). Senarai, picking list & label = `channel online` sahaja; butiran juga POS. Peranan:
> `orders/*` & `customers/*` = admin + pengurus; `handover/*`, `orders/class-options`, `grades/list`, `settings/get` = juga juruwang.
> `logs[].actor_name`: ibu bapa = nama snapshot, kakitangan = `vendor_users.full_name`, sistem = `order.actor_system`.
> - **Status:** `UpdateOrderStatusJob` (transaksi, pesanan `FOR UPDATE`); batal = `releaseHolds` + bil pending → failed; → `ready` kali pertama
>   sahaja menghantar e-mel `order_ready` (nama anak, kelas, waktu operasi). `BulkOrderStatusJob` = satu transaksi setiap pesanan, ≤ 200 id.
> - **Carian serah** (`FindOrdersForPickupJob`) ikut `pickup_methods`: C/D token QR (32 hex, tepat) → B no. pendek (tepat, huruf besar/kecil) →
>   A nama murid/ibu bapa/no. pesanan + darjah/kelas (pesanan aktif, `ready` dahulu). Setiap hasil membawa **`found_by`** (A/B/C); skrin serah
>   menghantarnya semula sebagai `method` → direkod dalam `order_pickups` (E bila kod disahkan; C → D bila hanya slip aktif; lain → A).
> - **Terima tunai** (`ReceiveCashPaymentJob`): baris `payments` (cash, paid, `received_by` juruwang, `verified_at`) + `orders.paid_at`,
>   `amount_received`, `change_given`, `expires_at` = NULL (tidak diluputkan cron). Status pesanan kekal.
> - **Serah** (`HandoverOrderJob`): kod dahulu (`VerifyPickupCodeJob`, transaksi sendiri), kemudian SATU transaksi: kunci pesanan → semak semula
>   status/bayar/kunci → qty ≤ baki (qty − collected − cancelled) → `order_pickups` + `order_pickup_items` → `collected_qty` →
>   `StockLedger::collect` (tertib kunci `sortStockKeys`) → `collected` / `partially_collected` (+ `collected_at`) → log (nota = nama pengambil) →
>   e-mel `order_collected` setiap serahan → log aktiviti `handover_collect`. Serahan serentak: satu sahaja berjaya.
> - **Ibu bapa** (`ListVendorCustomersJob`): akaun aktif (`deleted_at IS NULL`) yang ada anak aktif di koperasi ini; kiraan/jumlah pesanan tanpa
>   batal/luput; nota `vendor_customer_notes` (satu setiap vendor, kosong = padam, ≤ 1000 aksara) — `SaveCustomerNoteJob` 404 jika tiada anak di koperasi.
> - Mock `vendor-orders.js` (`MockOrders`) kekal untuk POS (B10), refund & laporan (B11) mock; laluan B9 dibuang dari mock.

**Customer** — `vendor/customers/list { q, grade, class_name }` (hanya ibu bapa yang ada anak di koperasi ini; hanya anak itu)
```js
{ id, full_name, phone, email, relationship: 'mother|father|guardian', children: [{ id, full_name, grade_level_code, class_name }],
  order_count, total_spent, last_order_at, note /* nota vendor ini sahaja */ }
```
`vendor/customers/save-note { customer_id, note }` — ditolak jika ibu bapa tiada anak di koperasi ini

**Skrin serah barang (vendor/pos/handover.php) — aliran papan kekunci:** fokus automatik pada carian → taip / imbas (pengimbas USB =
papan kekunci + Enter) → satu hasil dibuka terus → [tunai: jumlah diterima diisi, Enter] → pilih siapa ambil (anak panah), Tab ke nama &
item, Enter = sahkan → skrin berjaya ("Ibu bapa telah dimaklumkan"), fokus pada "Seterusnya" → Enter → kembali ke carian kosong

### 18.10 Objek vendor bahagian 3 (U3c) — `assets/js/mock/vendor-finance.js`

Laluan koperasi menerima `cooperative_id` (disahkan `ValidateCooperativeJob`); kakitangan, langganan, invois & log aktiviti ialah
**peringkat vendor** (tiada `cooperative_id`, ditapis `vendor_id` sesi). Setiap tindakan di bawah menulis `activity_logs`
(`action` = kod, label `T('activity.action.' + action)`).

**Bayaran** — `vendor/payments/settings` → `{ accept_cash, accept_online, drivers: [{ code, name, supports_refund, fields: [{ name, secret }], configured }],
gateway: GatewayPublic|null, callback_url }` (hanya driver `is_enabled_for_vendors`). `GatewayPublic = { gateway_code, mode: 'sandbox|live', is_active,
values: { medan_bukan_rahsia }, secrets_set: ['secret_key'] }` — **rahsia tidak pernah dipulangkan**. `vendor/payments/gateway { gateway_code }` → GatewayPublic|null.

| Laluan (POST) | Input | Peraturan |
|---|---|---|
| `vendor/payments/save-gateway` | `gateway_code, mode, credentials: {}` | driver dibenarkan owner; rahsia kosong = kekal; satu gateway aktif setiap koperasi (yang lain disimpan tidak aktif) |
| `vendor/payments/test-gateway` | — | Peringkat 2: panggil API gateway dengan kunci tersimpan |
| `vendor/payments/save-methods` | `accept_cash, accept_online` | sekurang-kurangnya satu; online perlu gateway aktif |

**PaymentRecord** — `vendor/payments/list` (tapis `q, method, status, date_from, date_to`; `data.summary: { cash_total, online_total, refunded_total, pending_count }`)
```js
{ id, order_id, order_no, short_code, customer_name, method: 'cash|online', gateway_code, gateway_ref, amount, refunded_amount,
  status: 'paid|pending|failed|refunded', received_by, paid_at, created_at }
```

> **B8 (sebenar — `jobs/Payment/`, `payments/`):** `vendor/payments/*` (6 laluan) dari DB, **`vendor_admin` sahaja** (pengurus & juruwang 403).
> Driver dalam `drivers` = `is_enabled_for_vendors` **dan** ada kelas driver. Simpan: rahsia → `credentials_encrypted` (JSON, `Crypto`), bukan rahsia
> → `config_json`; rahsia kosong = kekal; gateway disimpan menjadi aktif, yang lain `is_active 0`; log aktiviti `gateway_save` / `payment_methods_save`.
> `test-gateway`: panggilan sebenar (ToyyibPay `getCategoryDetails`, Billplz koleksi, SenangPay `query_order_status`, Stripe `/v1/balance`;
> iPay88 tiada API → `payment.test_unsupported`); gagal → `payment.test_failed`. `callback_url` = `{app_url}/api/v1/webhooks/{gateway}.php?coop={id}`.
> Rekod bayaran dari jadual `payments` (online sejak B8; tunai ditulis bila dibayar di kaunter — B9/B10); `paid_at` = `verified_at`;
> `refunded_amount` = jumlah `refunds` selesai. Pautan rekod ke `vendor/order.php` masih panel pesanan mock sehingga B9.

**Refund** — `vendor/refunds/list` (tapis `q, method, date_from, date_to`; `data.total_amount`)
```js
{ id, refund_no: 'RF-1001', order_id, order_no, short_code, customer_name, amount, method: 'cash|bank_transfer|gateway', reason, restock, item_count, user_name, created_at }
```
- `vendor/refunds/order { q | order_id }` → pesanan dibayar sahaja: `{ id, order_no, short_code, status, customer_name, payment_method, gateway_name,
  gateway_supports_refund, total, refunded_amount, refundable_amount, items: [{ id, product_name, variant_label, child_name, unit_price, qty, collected_qty,
  cancelled_qty, returned_qty, returnable_qty }] }`
- `vendor/refunds/create { order_id, items: [{ item_id, qty }], restock, amount, method, reason }` — `0 < amount ≤ refundable`; `qty ≤ returnable`;
  `gateway` hanya jika driver `supports_refund`. Unit **belum diambil → dibatalkan** (`order_items.cancelled_qty`, `qty_reserved` dilepas, pergerakan `release`);
  unit **sudah diambil → dipulang** (`returned_qty`; `restock` → pergerakan `refund` +qty). Refund penuh → status `refunded`. Skrin serah & picking list
  menolak `cancelled_qty` (baki = qty − collected − cancelled). OrderDetail kini ada `refunded_amount` dan item `cancelled_qty, returned_qty`

**Promotion** — `vendor/promotions/list` (tapis `q, state`)
```js
{ id, cooperative_id, name, code /* null = diskaun automatik */, type: 'percent|fixed', value /* % atau sen */, min_spend, starts_on, ends_on,
  max_uses /* null = tanpa had */, used_count, is_active, state: 'active|scheduled|expired|inactive|used_up' /* dikira pelayan */ }
```
`save` (kod `^[A-Z0-9-]{3,20}$` unik dalam koperasi, huruf besar; % 1–100; tamat ≥ mula) · `delete` (ditolak jika `used_count > 0` — nyahaktif sahaja)

**VendorUser** — `vendor/staff/list` (tapis `q, role, status`; `data.limit: { max /* null = tanpa had */, used }` = kakitangan aktif vs `plans.max_staff`)
```js
{ id, full_name, email, phone, role: 'vendor_admin|coop_manager|cashier', cooperative_ids: [] /* admin = semua */, cooperative_names,
  is_active, pin_set, totp_enabled, last_login_at, is_self }
```
`save { id?, full_name, email, phone, role, cooperative_ids, pin? }` — e-mel unik **seluruh platform** (ralat umum, tidak dedah vendor lain); bukan admin
perlu ≥ 1 koperasi milik vendor; PIN 4–6 digit (hash Argon2id); had pelan; tidak boleh tukar peranan sendiri · `set-active` (bukan diri sendiri; had pelan) ·
`send-reset { id }` → `{ email }`

**CooperativeSettings** — `vendor/settings/get` / `save`
```js
{ alert_emails: [] /* 1–5 */, default_language, receipt_header, receipt_footer, receipt_show_logo, receipt_paper: '58|80', tax_rate /* % 0–10 */,
  pickup_methods: ['A','B','C','D','E'?] /* ≥ 1 dari A–D */, pickup_code_required_above /* sen, 0 = tidak */, cash_order_expiry_days /* 1–14 */, low_stock_default }
```
`default_language` disimpan dalam `cooperatives`; selebihnya `cooperative_settings`. Skrin serah membaca `pickup_methods` & had kod yang sama.
> **B9 (sebenar):** `GetCooperativeSettingsJob` / `SaveCooperativeSettingsJob` (`jobs/Cooperative/`, atas `CooperativeSettings`). `get` dibenarkan
> juruwang & pengurus (POS / skrin serah) **tanpa** `alert_emails`; `save` = `vendor_admin`. Pengesahan: e-mel huruf kecil & unik (1–5),
> ≥ 1 cara A–D, had kod 0–RM1,000,000 (sen), luput tunai 1–14, cukai 0–10 (2 titik perpuluhan), stok minimum 0–100000, bahasa aktif dalam
> `languages`, pengepala ≤ 80, pengaki ≤ 200, kertas 58/80. Log aktiviti `settings_save`. Kod 4 digit pesanan sedia ada tidak berubah bila had ditukar
> (`requires_code` = hash disimpan semasa checkout).

**Laporan** — `vendor/reports/run { type, date_from, date_to }`, `type: daily|monthly|payment|product|profit|stock|grade|class` →
`{ type, school_name, date_from, date_to /* null untuk stock */, generated_at, columns: [{ key, label /* kunci terjemahan */, format: 'text|int|money|percent|date|month|grade|method' }],
rows: [{…}], totals: {…}, chart: [{ label, value, format? }] | null }`. Satu pemapar untuk semua jenis. Jualan = pesanan `payment_status = paid` ikut tarikh bayar;
refund ditolak dalam "Bersih"; kos = `cost_price` produk; darjah/kelas dari snapshot `order_items`. **CSV dijana di pelayar** dari data yang sama
(`downloadCsv()`, BOM UTF-8, wang dalam RM 2 titik perpuluhan). Peringkat 2: `ReportJob` per jenis, julat tarikh maks 1 tahun.

**Langganan** — `vendor/subscription/get` →
```js
{ plan: { id, code, name, price_monthly, price_yearly, max_cooperatives, max_products, max_staff, features }, billing_cycle, status, started_at,
  current_period_start, current_period_end, days_left, usage: { cooperatives, products, staff },
  cooperatives: [{ id, school_name, number, included /* ke-1 */, price, template_name, template_price, modules: [{ code, price, started_at, cancel_at_period_end }] }],
  renewal: { lines: [{ type: 'plan|cooperative|module|template', label, cooperative_name, price, cancelling }], subtotal, tax, total, sst_rate },
  pending_change: { plan_id, plan_name, billing_cycle, effective_at } | null, unpaid_count, unpaid_total }
```
- `vendor/subscription/plans` → `{ current_plan_id, billing_cycle, plans: [Plan] }`; `change { plan_id, billing_cycle }` → berkuat kuasa pada `current_period_end`
  (turun taraf ditolak jika penggunaan > had baharu); `cancel-change`
- `vendor/addons/catalog` → `{ billing_cycle, current_period_end, modules: [{ code, price_monthly, price_yearly, price, status: 'active|cancelling|available|unavailable', started_at }],
  templates: [{ id, code, name, is_free, price, subscribed, in_use }] }`
- `vendor/addons/quote { item_type: module|template, code }` → `{ item_name, cooperative_name, billing_cycle, unit_price, period_start, period_end, days_left, period_days,
  amount /* = unit_price × days_left / period_days */, tax, total, sst_rate, owned }`
- `vendor/addons/subscribe` → **aktif serta-merta** + invois prorata `issued` (bayar dalam 7 hari, tempoh tangguh PELAN 14) → `{ invoice_id, invoice_no, total }`
- `vendor/addons/cancel { code, undo }` → modul kekal aktif hingga hujung tempoh, tidak dicaj pada pembaharuan (tiada refund)
- **Invois** — `vendor/invoices/list` (tapis `q, status`) → InvoiceSummary (18.7) + `line_count`; `get` → + `lines, payments, vendor { code, company_name }, sst_rate`
  (draf tidak dipapar kepada vendor); `pay { id }` → Peringkat 1 simulasi `{ paid: true }`; Peringkat 2 `{ redirect_url }` ke gateway pemilik, `paid` hanya selepas webhook

**ActivityLog** — `vendor/activity-logs/list` (tapis `q, user_id, scope: ''|current (+ cooperative_id), date_from, date_to`; `data.users: [{ id, full_name }]`)
```js
{ id, cooperative_id, cooperative_name /* null = peringkat vendor */, user_id, user_name, action, entity, entity_id, detail, created_at }
```

### 18.11 Objek POS kaunter (U4) — `assets/js/mock/vendor-pos.js`

Skrin `vendor/pos/index.php` (layout skrin penuh `vendor/_layout/pos-header.php` / `pos-footer.php`, tanpa sidebar).
Peranti kaunter log masuk ke panel vendor seperti biasa; **juruwang** kemudian dikenal pasti dengan **PIN** (`vendor_users.pin_hash`).
Semua laluan POS menerima `cooperative_id` (`ValidateCooperativeJob`) dan — selain `session`, `pin-login`, `lock`, `catalog` —
mesti ada juruwang PIN dalam sesi untuk koperasi itu (`$_SESSION['pos_user_id'][cooperative_id]`), jika tidak
`{ ok:false, error: 'Sila log masuk dengan PIN juruwang.' }`.

**Jualan POS = pesanan `channel = 'pos'`** dalam jadual `orders` (`customer_id` NULL, `customer_name` pilihan, terus `collected` bila dibayar,
`order_items.source = 'pos'`, tiada anak). No. resit `POS-YYMMDD-001` (berturut setiap koperasi setiap hari), `short_code` `P001`.
Ia muncul dalam rekod bayaran, laporan, pergerakan stok (`pos_sale`) dan pemulangan & refund tanpa laluan baharu, tetapi
**tidak** dalam `vendor/orders/list` (Pesanan online). `vendor/orders/get` boleh membukanya (OrderSummary kini ada `channel`).

| Laluan | Kaedah | Input | `data` / peraturan |
|---|---|---|---|
| `vendor/pos/session` | GET | — | `{ cooperative: { id, school_name, address, phone, logo_url }, cashier: { id, full_name, role } \| null, shift: PosShift \| null, online_available, gateway_name, closed_today: { title, reopens_on } \| null }` — U7: hari "koperasi tutup" (modul `calendar`) → banner amaran di POS, jualan kaunter tidak disekat |
| `vendor/pos/pin-login` | POST | `user_id, pin` | kakitangan vendor sesi, aktif, `pin_set`, ada akses koperasi (admin = semua); 5 PIN salah → dikunci (pentadbir tetapkan semula di Kakitangan) → `{ cashier, shift }` |
| `vendor/pos/lock` | POST | — | buang juruwang dari sesi kaunter; syif kekal dibuka |
| `vendor/pos/shift-open` | POST | `opening_cash` (sen ≥ 0) | satu syif terbuka setiap juruwang setiap koperasi → PosShift |
| `vendor/pos/shift-summary` | GET | `shift_id` | PosShift |
| `vendor/pos/shift-close` | POST | `shift_id, closing_cash, note` | syif sendiri sahaja; ditolak jika ada bayaran QR `pending_payment`; `variance = closing − expected`; juruwang dilog keluar |
| `vendor/pos/catalog` | GET | — | `{ categories: [{ id, name }], products: [PosProduct] }` — produk aktif sahaja |
| `vendor/pos/checkout` | POST | `shift_id, items: [{ product_id, variant_id, qty }], coupon_code, manual_discount (sen), customer_name, payment_method: cash\|online, amount_received (sen)` | **pelayan mengira semula** harga, stok (`on_hand − reserved`), `max_per_order`, promosi & cukai (nilai harga dari pelayar diabaikan) → `{ receipt: PosReceipt, payment: null \| { reference, gateway_name, qr_payload, expires_in } }` |
| `vendor/pos/payment-status` | GET | `order_id` | `{ status: pending\|paid\|cancelled, receipt }` — Peringkat 2 dibaca dari `payments` yang dikemas kini webhook gateway |
| `vendor/pos/cancel-payment` | POST | `order_id` | QR `pending_payment` sahaja → `cancelled`, tahanan stok dilepas (`release`) |
| `vendor/pos/sales` | GET | `shift_id` | senarai `{ order_id, order_no, created_at, total, payment_method, status, payment_status, customer_name, item_count }` |
| `vendor/pos/receipt` | GET | `order_id` | PosReceipt (jualan POS koperasi ini sahaja) |

```js
PosProduct = { id, category_id, name, display_name /* terjemahan bahasa semasa jika ada */, sku, barcode, price, unit, image_url, max_per_order,
               has_variants, stock_available, variants: [{ id, label, sku, barcode, price_delta, stock_available }] }
PosShift   = { id, cooperative_id, user_id, user_name, status: 'open|closed', opened_at, closed_at, opening_cash, sale_count, item_count,
               cash_count, cash_sales, online_count, online_sales, discount_total, tax_total, pending_count, expected_cash, closing_cash, variance, note }
PosReceipt = { order_id, order_no, short_code, status, payment_status, created_at, paid_at, cashier_name, customer_name, shift_id,
               items: [{ product_name, variant_label, sku, qty, unit_price, line_total, returned_qty }],
               subtotal, promotion_name, promotion_code, promotion_discount, manual_discount, discount, tax_rate, tax, total, refunded_amount,
               payment_method, gateway_name, gateway_ref, amount_received, change, school_name, address, phone, logo_url }
```

**Pengiraan troli** (`assets/js/vendor/pos-cart.js`, disalin & diubah suai dari KedaiPOS `calcUnitPrice` / `getTotals` / `suggestCashAmounts`; wang dalam sen):
harga seunit = `price + price_delta`; satu baris setiap produk + varian; **satu promosi setiap jualan** — kupon yang dimasukkan, jika tidak
diskaun automatik (tanpa kod) yang paling menjimatkan; diskaun = promosi + manual (≤ subtotal); **cukai = `tax_rate` koperasi atas (subtotal − diskaun)**;
jumlah = subtotal − diskaun + cukai. `used_count` promosi naik bila dibayar. Mock memanggil fungsi yang sama; Peringkat 2 `PosCalculateCartJob` dalam PHP.

**Bayaran:** tunai **sentiasa** dibenarkan di POS (`accept_cash` hanya untuk pesanan store); QR online perlu gateway aktif koperasi.
Online: pesanan `pending_payment` + stok ditahan (`reserve`) → dibayar → `pos_sale` (tahanan dilepas). Mock: dibayar pada semakan status ke-2.

**Resit:** tetapan dari `vendor/settings/get` (`receipt_header`, `receipt_footer`, `receipt_show_logo`, `receipt_paper` 58/80, `tax_rate`);
`assets/css/receipt.css` — cetak hanya `#receipt-print`, `@page { margin: 0 }`, lebar kertas ditetapkan dalam pemacu pencetak.
Laporan syif (Z) dicetak pada kertas yang sama selepas tutup syif.

**Papan kekunci & pengimbas:** medan imbas difokus semula selepas setiap sentuhan/modal; aksara yang ditaip di luar medan dihantar ke medan imbas;
Enter = padanan tepat barcode/SKU (varian dahulu) → tambah; `5*kod` = 5 unit; satu hasil carian → tambah; F2 tunai, F4 QR;
Enter pada resit = jualan baharu. Log aktiviti: `pos_login`, `pos_shift_open`, `pos_shift_close`, `pos_sale` (atas nama juruwang).

### 18.12 Objek store ibu bapa (U5) — `assets/js/mock/store.js`

Store memakai "pangkalan data" mock yang sama dengan panel vendor (layout template1 memuat `vendor.js`, `vendor-orders.js`,
`vendor-finance.js`, `store.js` + `js/vendor/pos-cart.js`), jadi pesanan dari store terus muncul di panel vendor dalam tab yang sama.
`Mock.storageVersion` → `v3` (ibu bapa & anak kini disimpan; anak ada `school_year`, `is_active`, `vendor_id`).

**Keselamatan:** setiap laluan menerima `slug` — pelayan menyelesaikan koperasi + `vendor_id` (B6: dari URL `/{slug}/`). Tiada `vendor_id`,
`cooperative_id` atau `customer_id` dari input; `customer_id` dari sesi (`MockStore.session`). Anak, troli & pesanan ditapis ibu bapa sesi
**dan** koperasi slug — milik orang lain / koperasi lain → `error.not_found`. Laluan yang perlu log masuk memulangkan
`{ ok:false, error, auth_required: true }` → JS (`storeGet/storePost`) membawa ke `login&next={halaman}&next_id=`.
Harga, diskaun, cukai & stok dikira semula di pelayan; `expected_total` dari pelayar hanya untuk mengesan perubahan harga.

| Laluan | Kaedah | Input | `data` / peraturan |
|---|---|---|---|
| `store/schools/list` | GET | `q, state, page` | senarai `{ slug, school_name, school_type, state, district }` (koperasi aktif) |
| `store/school` | GET | `slug` | + `address, phone, logo_url, default_language, opening_hours, grades, modules: ['news','clearance','calendar','school_opening'] (aktif sahaja), payment_methods, notices` (U6 — 18.13) |
| `store/classes` | GET | `slug, grade` | `[{ grade_level_code, class_name }]` (cadangan datalist) |
| `store/session` | GET | `slug?` | `{ customer: Customer\|null, cart_count, schools: [sekolah anak-anak] }` |
| `store/auth/register` | POST | `full_name, phone, email, password, password_confirm, relationship, language, pdpa` | telefon MY dinormalkan `012-3456789`; e-mel & telefon unik global (ralat umum); kata laluan ≥ 8; PDPA wajib → log masuk terus, `email_verified: false` |
| `store/auth/login` | POST | `login` (e-mel atau telefon), `password` | mesej sama untuk akaun tiada / salah; 5 gagal → kunci 15 minit |
| `store/auth/logout` · `forgot { email }` · `reset { token, password, password_confirm }` · `verify-email { token }` · `resend-verification` | POST | | token sekali guna, 1 jam. `forgot` sentiasa `{ sent: true }` |
| `store/profile/get` · `save { full_name, phone, relationship, language }` · `password { current_password, password, password_confirm }` · `delete { password }` | | | padam (PDPA): ditolak jika ada pesanan aktif; nama/e-mel/telefon dibuang, anak dinyahaktif, snapshot hubungan dalam pesanan dikosongkan |
| `store/children/list` · `save { id?, full_name, school_slug, grade_level_code, class_name, school_year }` · `remove { id }` · `promote { id }` | | | semua anak ibu bapa (semua sekolah); tahun = tahun ini / depan; kelas dipadankan dengan `school_classes` jika ada; `promote_to` dicadangkan Okt–Dis |
| `store/catalog` | GET | `slug, q, category_id, grade, page` | senarai StoreProduct + `categories` (aktif) |
| `store/product` | GET | `slug, id` | StoreProduct + `description, sku, max_per_order, variants: [{ id, label, price, regular_price, available, max_per_order }]` |
| `store/kit` | GET | `slug, child_id?` | `{ children, child, kit: { id, name, note, grade_level_code, school_year, in_cart, items: [KitItem] } \| null }` — kit diterbitkan ikut darjah & tahun anak |
| `store/cart/get` · `update { id, qty }` · `remove { id }` · `clear` | | | CartQuote |
| `store/cart/add` | POST | `product_id, variant_id, qty, child_id` | anak milik ibu bapa & di sekolah ini; varian wajib jika ada; stok (`on_hand − reserved`) & had beli dikira merentas troli |
| `store/cart/add-kit` | POST | `child_id, kit_id, items: [{ kit_item_id, qty, variant_id }]` | item yang ditanda sahaja; saiz wajib untuk "ibu bapa pilih saiz"; menggantikan baris kit yang sama untuk anak itu |
| `store/checkout/quote` | GET | `coupon_code` | CartQuote + `customer_name, email_verified` + (U6) `pickup_slots, slot_required, orders_blocked` |
| `store/checkout/place` | POST | `pickup_by_type, pickup_by_name (wakil), payment_method, coupon_code, pickup_code (jika requires_code), pickup_slot_id (jika slot_required), expected_total` | (U6) ditolak jika `orders_blocked`; slot disahkan semula (koperasi, darjah, baki) → `booked++`; | e-mel disahkan; troli tanpa masalah; kaedah dibenarkan koperasi → tunai `unpaid_cash` (+ `expires_at` = `cash_order_expiry_days`), online `pending_payment`; stok **ditahan** (`reserve`); promosi `used_count++`; troli dikosongkan → `{ order_id, order_no, short_code, payment: null \| { reference, gateway_name, amount, mode, redirect_url } }` |
| ~~`store/payment/simulate`~~ | — | — | **Dibuang dalam B8** — diganti redirect gateway + panggilan balik |
| `store/orders/check-payment` | POST | `slug, id` | (B8) ibu bapa kembali dari gateway: pelayan menyemak bil pending ke API gateway → StoreOrderDetail terkini |
| `store/orders/list` | GET | `slug, child_id, year, page` | pesanan online ibu bapa di koperasi ini + `children, years, ready_count` |
| `store/orders/get` | GET | `slug, id` | StoreOrderDetail |
| `store/orders/pay { id }` · `cancel { id }` | POST | | bayar semula `pending_payment` belum luput → `{ order_id, payment }` (B8); batal hanya `pending_payment`/`unpaid_cash` (tahanan dilepas, bil pending → failed) |
| `store/news/list` · `store/clearance/list` | GET | `slug` | hanya jika modul aktif, jika tidak `store.module_unavailable` |

```js
Customer        = { full_name, phone, email, relationship, language, email_verified }
StoreProduct    = { id, name /* terjemahan jika ada */, category_id, category_name, price, regular_price, price_from, on_sale, unit, image_url,
                    has_variants, available, grade_levels }
KitItem         = { id, product_id, name, image_url, unit, variant_id, variant_label, choose_variant, variants: [{ id, label, price, available }],
                    unit_price, qty, is_mandatory, note, available /* null jika pilih saiz */, max_per_order, in_cart: { qty, variant_id } | null }
CartQuote       = { groups: [{ child_id, child_name, grade_level_code, class_name, subtotal, items: [{ id, product_id, variant_id, name, variant_label,
                    image_url, unit_price, regular_price, qty, line_total, source: 'kit|ala_carte', school_kit_id, available, max,
                    problem: null|'unavailable'|'stock'|'max' }] }], item_count, has_problem, subtotal, discount, promotion_name, promotion_code,
                    coupon_code, coupon_error, tax_rate, tax, total, payment_methods, gateway_name, cash_expiry_days, requires_code, code_required_above }
StoreOrderDetail = { id, order_no, short_code, status, payment_method, payment_status, total, created_at, expires_at, children, item_count,
                    subtotal, discount, promotion_name, tax, tax_rate, refunded_amount, paid_at, collected_at, pickup_by_type, pickup_by_name,
                    requires_code, qr_token, gateway_ref, can_pay, can_cancel, customer_name, school: { school_name, address, phone },
                    groups: [{ child_name, grade_level_code, class_name, items: [{ product_name, variant_label, unit_price, qty, line_total,
                    collected_qty, cancelled_qty, returned_qty, source }] }], logs: [{ to_status, created_at }] /* tiada nama kakitangan */,
                    pickups: [{ picked_by_type, picked_by_name, created_at, items }] }
```

**Kit:** semua item siap ditanda (kecuali stok habis); item wajib boleh dinyahtanda dengan amaran "Item wajib sekolah"; jumlah dikira serta-merta di
pelayar (paparan sahaja). **Kod 4 digit:** jika koperasi mewajibkan (cara E atau jumlah > `pickup_code_required_above`), **ibu bapa menetapkan kod**
semasa checkout (B7: hash Argon2id, tidak boleh dipapar semula) dan memberitahu anak / wakil. **Jualan murah:** harga `clearance_items.sale_price`
digunakan automatik oleh pelayan (modul aktif, dalam tempoh) dengan had `qty_limit`. **Promosi:** sama seperti POS — satu setiap pesanan, kupon atau
diskaun automatik terbaik, cukai selepas diskaun. **Slip beg** (cara D): satu setiap anak (sekolah, nama, kelas, no. pendek besar, QR, "BAYAR TUNAI").

> **B6 (sebenar — `jobs/Store/`, `jobs/Child/`, `jobs/Cart/`):** 19 laluan store (sekolah, kelas, anak, katalog, produk, kit, troli, berita,
> jualan murah) dari DB. Slug → koperasi melalui `StoreSchool::findBySlug/require` (koperasi `active` **dan** vendor `trial|active|past_due`;
> vendor digantung → store ditutup, 404 `store.school_not_found`). Harga/stok/had dari `StorePricing` (harga + `price_delta`, jualan murah
> `clearance_items` hanya jika modul `clearance` aktif & dalam tempoh — `variant_id 0` = semua saiz; baki = `qty_on_hand − qty_reserved`; had =
> min(`max_per_order`, `qty_limit`)). Had & stok dikira **setiap produk+varian merentas seluruh troli** (semua anak, kit & ala carte); kuantiti
> 1–99; ≤ 200 baris troli. Produk dalam kategori tidak aktif tidak dijual. Nama/keterangan ikut bahasa (`product_translations`, jatuh balik nama asal).
> Anak: `store/children/save` → sekolah dari `school_slug` (cooperative_id & vendor_id dari baris koperasi), darjah ikut jenis sekolah, tahun =
> tahun ini / depan, kelas dipadankan tanpa beza huruf (`school_class_id` + nama rasmi), ≤ 20 anak aktif; tukar sekolah / buang anak → baris troli
> anak itu dibuang; `promote_to` (Okt–Dis atau tahun sudah lepas, bukan darjah terakhir) → `store/children/promote { id, class_name? }`.
> Kit (`store/kit`): kit diterbitkan ikut darjah + `school_year` anak; item aktif sahaja; `in_cart` dari baris troli `school_kit_item_id`.
> `store/cart/add-kit`: kit mesti kit anak itu (`kit_id` sepadan), item dari kit itu sahaja, tiada pendua, saiz wajib (`variant_{id}`), ralat stok
> `qty_{id}`; baris kit + anak yang sama **diganti**. `store/cart/update` menyimpan kuantiti 1–99 (melebihi stok/had → `problem` dalam CartQuote,
> checkout disekat). CartQuote B6: `discount 0`, `promotion_* null` (promosi & kupon dalam checkout B7), `tax` = `tax_rate` koperasi,
> `payment_methods` dari `accept_cash/accept_online`, `requires_code` dari `pickup_methods` (E) / `pickup_code_required_above`.
> `store/school.notices`: `closure` (modul calendar, `blocks_orders`, berlaku / dalam 14 hari), `preorder` (modul school_opening, musim dibuka).
> Berita: diterbitkan & `published_at <= NOW()`. Modul tidak aktif → 404 `store.module_unavailable` (JS halaman berita/jualan murah menyemak
> `Store.school.modules` dahulu). Template: `ResolveStoreTemplateJob` (`cooperatives.template_id` aktif + dilanggan + folder lengkap, jika tidak `template1`).

> **B7 (sebenar — `jobs/Order/`, `jobs/Promotion/`):** `store/checkout/quote|place`, `store/orders/list|get|cancel|pay` dari DB; `mock/store.js`
> dibuang. Quote = CartQuote + promosi (`promotions` aktif, dalam tarikh, `used_count < max_uses`; kupon tidak beza huruf; kupon salah / bawah
> minimum → `coupon_error` dan place ditolak `errors.coupon_code`; tanpa kod → automatik terbaik), cukai = round((subtotal − diskaun) × kadar),
> `requires_code` dikira atas jumlah selepas diskaun, `payment_methods`: tunai jika `accept_cash`; **online hanya jika `accept_online` + gateway
> koperasi aktif + driver B8 (`GatewayFactory`) wujud** — B7 = tunai sahaja. Slot: musim pre-order dibuka (modul school_opening), slot darjah
> troli atau semua darjah, **slot yang sudah lepas tidak ditawarkan**, `HH:MM`. `orders_blocked`: acara `blocks_orders` hari ini (modul calendar).
> Place: e-mel mesti disahkan; troli kosong (tiada baris) → `store.cart.empty`; ada `problem` → `store.checkout.fix_cart`; `expected_total`
> berbeza → `store.checkout.total_changed`; tunai → `unpaid_cash` + `expires_at` = hari ini + `cash_order_expiry_days` pukul 23:59:59.
> No. pesanan `{singkatan sekolah}-{YYMM}-{NNNN}` (turutan bulanan koperasi, cth. `TM-2610-0001`, `CH-…`, `SI-…`); kod pendek huruf (tanpa I/O)
> + 3 digit (jika sesak 4 digit), unik per koperasi; `pickup_qr_token` 32 hex rawak. Pengambil: `student` → nama anak-anak (koma), `parent` →
> nama ibu bapa, `proxy` → nama wakil (wajib, ≤ 150). Kod 4 digit hanya disimpan bila `requires_code` (hash Argon2id); `requires_code` dalam
> StoreOrderDetail = ada hash. Snapshot item: nama produk **asal vendor** (bukan terjemahan), varian, SKU (varian → produk), harga, `clearance_item_id`,
> anak/darjah/kelas. `payment_status`: `paid` (paid_at) · `pending` (pending_payment) · `unpaid` (tunai belum selesai) · `none`. Batal (ibu bapa,
> `pending_payment`/`unpaid_cash`) & luput (cron) → lepas tahanan stok baki item, `booked − 1`, promosi `used_count − 1`; log nota batal dalam bahasa
> koperasi. `store/orders/pay`: `pending_payment` sahaja (B8 — B7 memulangkan `store.checkout.method_unavailable`). E-mel: amaran vendor ke
> `alert_emails` (≤ 5, bahasa koperasi), resit ibu bapa (bahasanya) dengan `{items_table}` + `{pickup_qr}` (QR jadual HTML `bgcolor`, `core/QrCode`
> = port qrcode.js) + `{order_url}` (URL store penuh).

> **B8 (sebenar):** online ditawarkan (quote, `store/school`, troli) hanya bila `accept_online` + `CooperativeGateways::active`. Place online →
> `pending_payment` + `expires_at` (60 minit) → selepas transaksi `StartOnlinePaymentJob` → `payment: { reference: '{order_no}-{n}', gateway_name,
> amount, mode, redirect_url }`; gateway gagal → `payment: null, payment_error`. `storeStartPayment()` memaparkan modal ringkasan (amaun, rujukan,
> nota mod ujian) dengan pautan "Teruskan ke halaman bayaran" (`redirect_url`) / "Bayar kemudian" (order-success). iPay88: `redirect_url` =
> `store/payment-redirect.php?ref=` (borang POST automatik, bil milik ibu bapa sesi sahaja, CSP `form-action` + asal gateway). Kembali:
> `store/payment-return.php?school=&order=` → `order-success&returned=1` → `store/orders/check-payment`. `gateway_ref` dalam StoreOrderDetail =
> bil terkini.

### 18.13 Objek modul add-on (U6) — `assets/js/mock/vendor-addons.js`

Data modul (`MockAddons`: `news, clearanceSales, clearanceItems, calendarEvents, seasons, slots, messaging, notifications`) dikongsi panel vendor
**dan** store (layout template1 memuat `vendor-addons.js` sebelum `store.js`) — apa yang vendor cipta terus kelihatan di store dalam tab yang sama.
`Mock.storageVersion` → `v4`. Data contoh: SK Taman Melati melanggan `news, school_opening, calendar, whatsapp_sms` (`clearance` belum — untuk
menguji kad "Langgan modul ini"); SJKC Chung Hwa tiada modul; SMK Bukit Indah (VENDOR02) `clearance`.

**Keselamatan:** setiap laluan `vendor/{modul}/*` memanggil `addonRequire(params, kod)` = `ValidateCooperativeJob` + `ModuleAccess::has()` —
koperasi bukan milik vendor → `error.not_found`; modul tidak dilanggan → `{ ok:false, error, module_locked: true }`. Rekod dicari ikut
vendor sesi + koperasi (id rekod vendor lain → tidak dijumpai). `vendor_id` rekod baharu dari sesi. Kunci API WhatsApp/SMS tidak pernah dipulangkan.

| Laluan | Kaedah | Input | `data` / peraturan |
|---|---|---|---|
| `vendor/news/list` | GET | `q, state (published\|scheduled\|draft), page` | senarai NewsPost; berita berjadual yang kini terbit → e-mel dimasukkan baris gilir |
| `vendor/news/recipients` | GET | — | `{ count }` ibu bapa dengan anak aktif di koperasi ini |
| `vendor/news/save` | POST | `id?, title (≤150), body, image_url? (data URL; B13: Uploader), published_at ('YYYY-MM-DDTHH:MM'), is_active, send_email` | `{ post: NewsPost, emails_queued }` — e-mel sekali sahaja |
| `vendor/news/delete` | POST | `id` | |
| `vendor/clearance/list` | GET | `q, state (active\|scheduled\|ended\|inactive), page` | senarai `{ id, name, starts_on, ends_on, is_active, state, item_count }` |
| `vendor/clearance/get` | GET | `id` | jualan + `items: [ClearanceItem]` |
| `vendor/clearance/save` · `delete` | POST | `id?, name, starts_on, ends_on, is_active` | `starts_at = starts_on 00:00`, `ends_at = ends_on 23:59:59`; padam = buang item juga |
| `vendor/clearance/item-save` · `item-delete` | POST | `sale_id, id?, product_id, variant_id (null = semua saiz), sale_price (sen), qty_limit?` | produk milik koperasi; `sale_price` < harga biasa; produk+varian unik dalam jualan |
| `vendor/calendar/list` | GET | `from, to` | `{ items: [CalendarEvent] (bertindih julat), today, today_blocked: CalendarEvent\|null }` |
| `vendor/calendar/save` · `delete` | POST | `id?, title, event_type (school_holiday\|coop_closed\|school_opening\|other), starts_on, ends_on, blocks_orders` | |
| `vendor/school-opening/seasons` | GET | — | `{ items: [Season], grades }` |
| `vendor/school-opening/season-save` · `season-delete` | POST | `id?, school_year, opens_at, closes_at, pickup_starts_on (≥ closes_at), note` | satu musim setiap tahun; padam ditolak jika ada slot bertempah |
| `vendor/school-opening/slots` | GET | `season_id` | `{ season: Season, items: [Slot] }` |
| `vendor/school-opening/slot-save` | POST | `season_id, id?, slot_date (≥ pickup_starts_on), time_start, time_end, capacity (1–1000, ≥ booked), grades: []` | tambah: satu slot setiap darjah (kosong = semua darjah) → `{ created, items }`; sunting: satu darjah |
| `vendor/school-opening/slot-delete` | POST | `id` | ditolak jika `booked > 0` |
| `vendor/messaging/get` · `save` | | `provider (whatsapp_cloud\|twilio\|isms), channel, sender (60…), api_key (kosong = kekal), is_enabled, events.{ev}, templates.{ev}` | `{ …, api_key_set, placeholders }`; pemegang tempat tidak dikenali ditolak |
| `vendor/messaging/test` | POST | `phone` | perlu `is_enabled`; masuk `notification_queue` (Peringkat 1: terus "sent") |
| `vendor/messaging/log` | GET | `status, page` | senarai `{ channel, recipient (bertopeng 012-***4556), event, message, status, attempts, created_at, sent_at }` |

```js
NewsPost        = { id, title, body, image_url, published_at, is_active, send_email, email_sent_at, email_recipients, state }
ClearanceItem   = { id, product_id, variant_id, product_name, sku, variant_label, has_variants, regular_price, sale_price, qty_limit, discount_percent, available }
CalendarEvent   = { id, title, event_type, starts_on, ends_on, blocks_orders }
Season          = { id, school_year, opens_at, closes_at, pickup_starts_on, note, state: 'upcoming|open|closed', slot_count, capacity, booked }
Slot            = { id, slot_date, time_start, time_end, grade_level_code /* null = semua darjah */, capacity, booked, remaining }
StoreNotices    = { closure: { title, starts_on, ends_on, is_today, reopens_on } | null,   // acara blocks_orders hari ini / dalam 14 hari
                    preorder: { school_year, closes_at, pickup_starts_on, note, has_slots } | null }  // musim sedang dibuka
```

**Store (U6):** `store/school.notices` → banner `#store-closure` (semua halaman sekolah) & `#store-preorder` (utama & kit) dalam
`templates/template1/partials/notices.php`, diisi `assets/js/store/layout.js`. **Checkout:** `orders_blocked` (acara `blocks_orders` hari ini) →
amaran + butang dimatikan, `place` ditolak. Musim dibuka + ada slot → `slot_required`; `pickup_slots` = slot musim itu untuk darjah anak dalam troli
(atau semua darjah), slot penuh dimatikan. Satu pesanan = satu tempat. Pesanan menyimpan `pickup_slot_id` + salinan `pickup_slot`
`{ slot_date, time_start, time_end, grade_level_code }` (dipapar di `order-success`, `order-detail`, panel vendor `order.php`); batal (ibu bapa atau
vendor) → `booked--`. **Lencana "Jualan murah"** (sedia sejak U5) kini dari `MockAddons.clearanceItems` yang ditulis panel vendor.


### 18.14 Indeks laluan API lengkap (U7) — spesifikasi backend

Senarai **semua** laluan yang didaftar dalam `assets/js/mock/*.js` (214 laluan, dijana dari kod mock pada U7; B2 menambah 9 laluan auth
— jumlah **223**; B3 menambah `owner/plans/options` — **224**; B4, B5 & B6 tiada laluan baharu; B8 menambah `store/orders/check-payment` dan
membuang `store/payment/simulate` — kekal **224**, + 5 webhook `webhooks/{gateway}` di luar jadual; B9 menambah `vendor/orders/unlock-code` — **225**). Lajur Mock `— (B2)` … `— (B5)` = sudah sebenar (`api/v1/`), mock dibuang.
**B4:** PO, stock take, `templates/options` & `modules/active` disiapkan lebih awal (prompt B4); `vendor/settings/*` ditangguh ke B9 (skrin serah & POS mock masih membaca tetapan mock). Bentuk `data` setiap laluan
ada dalam 18.4–18.13; jadual ini ialah senarai semak Peringkat 2 — setiap baris mesti menjadi `api/v1/{laluan}.php` (nipis, tiada SQL)
yang memanggil job class. Kaedah: GET = baca sahaja; POST = ubah data (+ semakan CSRF, B14).

**Skop disahkan pelayan:**
- `owner` — sesi owner (2FA lengkap); `owner_staff` disekat untuk harga/pelan/tetapan (PELAN 4)
  **Sejak B3:** `owner_staff` dibenarkan hanya `owner/dashboard/summary`, `owner/vendors/list|get|options|add-note`, `owner/cooperatives/list`,
  `owner/audit-logs/list`, `owner/plans/options` (+ langganan/invois B12); laluan owner lain → 403 (`OwnerAuth::requireRole(['owner'])`)
- `vendor sesi` — `vendor_id` dari sesi sahaja; rekod dicari `WHERE vendor_id = :sesi`
- `vendor sesi + koperasi` — juga `cooperative_id` disahkan milik vendor (`ValidateCooperativeJob`); id rekod dicari dalam koperasi itu
- `koperasi + modul` — juga `ModuleAccess::has(cooperative_id, kod)`; tidak dilanggan → `module_locked: true`
- `awam + slug` — sekolah dari slug (koperasi aktif sahaja); `ibu bapa (sesi)` — `customer_id` dari sesi/token sahaja
- Peranan vendor (B2): laluan kewangan/kakitangan/langganan hanya `vendor_admin`; juruwang hanya `vendor/pos/*`, `vendor/handover/*`
  dan bacaan yang diperlukan oleh skrin itu (lihat jadual peranan dalam PELAN 4)

**owner/** 

| Laluan | Kaedah | Skop disahkan pelayan | Session | Mock |
|---|---|---|---|---|
| `owner/announcements/delete` | POST | owner | B3 | — (B3) |
| `owner/announcements/list` | GET | owner | B3 | — (B3) |
| `owner/announcements/save` | POST | owner | B3 | — (B3) |
| `owner/audit-logs/list` | GET | owner | B3 | — (B3) |
| `owner/auth/login` | POST | awam | B2 | — (B2) |
| `owner/auth/verify-2fa` | POST | awam | B2 | — (B2) |
| `owner/auth/logout` · `owner/auth/forgot` · `owner/auth/reset` | POST | awam | B2 | — (baharu B2) |
| `owner/cooperatives/list` | GET | owner | B3 | — (B3) |
| `owner/dashboard/summary` | GET | owner | B3 | — (B3) |
| `owner/email-templates/get` | GET | owner | B3 | — (B3) |
| `owner/email-templates/list` | GET | owner | B3 | — (B3) |
| `owner/email-templates/save` | POST | owner | B3 | — (B3) |
| `owner/gateways/list` | GET | owner | B3 | — (B3) |
| `owner/gateways/save-owner` | POST | owner | B3 | — (B3) |
| `owner/gateways/toggle` | POST | owner | B3 | — (B3) |
| `owner/grades/list` | GET | owner | B3 | — (B3) |
| `owner/grades/save` | POST | owner | B3 | — (B3) |
| `owner/invoices/get` | GET | owner | B12 | `owner.js` |
| `owner/invoices/issue` | POST | owner | B12 | `owner.js` |
| `owner/invoices/list` | GET | owner | B12 | `owner.js` |
| `owner/invoices/mark-paid` | POST | owner | B12 | `owner.js` |
| `owner/invoices/void` | POST | owner | B12 | `owner.js` |
| `owner/modules/list` | GET | owner | B3 | — (B3) |
| `owner/modules/save` | POST | owner | B3 | — (B3) |
| `owner/plans/delete` | POST | owner | B3 | — (B3) |
| `owner/plans/list` | GET | owner | B3 | — (B3) |
| `owner/plans/options` | GET | owner + owner_staff (tanpa harga — tapisan senarai vendor) | B3 | — (baharu B3) |
| `owner/plans/save` | POST | owner | B3 | — (B3) |
| `owner/plans/save-tiers` | POST | owner | B3 | — (B3) |
| `owner/plans/tiers` | GET | owner | B3 | — (B3) |
| `owner/profile/change-password` | POST | owner | B2 | — (B2) |
| `owner/profile/get` | GET | owner | B2 | — (B2) |
| `owner/profile/save` | POST | owner | B2 | — (B2) |
| `owner/settings/get` | GET | owner | B3 | — (B3) |
| `owner/settings/save` | POST | owner | B3 | — (B3) |
| `owner/settings/test-email` | POST | owner | B3 | — (B3) |
| `owner/staff/list` | GET | owner | B2 | — (B2) |
| `owner/staff/reset-2fa` | POST | owner | B2 | — (B2) |
| `owner/staff/save` | POST | owner | B2 | — (B2) |
| `owner/staff/set-active` | POST | owner | B2 | — (B2) |
| `owner/subscriptions/list` | GET | owner | B12 | `owner.js` |
| `owner/templates/list` | GET | owner | B3 | — (B3) |
| `owner/templates/save` | POST | owner | B3 | — (B3) |
| `owner/translations/list` | GET | owner | B14 | `owner.js` |
| `owner/translations/save` | POST | owner | B14 | `owner.js` |
| `owner/two-factor/enable` | POST | owner | B2 | — (B2) |
| `owner/two-factor/regenerate-codes` | POST | owner | B2 | — (B2) |
| `owner/two-factor/setup` | GET | owner | B2 | — (B2) |
| `owner/vendors/add-note` | POST | owner | B3 | — (B3) |
| `owner/vendors/get` | GET | owner | B3 | — (B3) |
| `owner/vendors/list` | GET | owner | B3 | — (B3) |
| `owner/vendors/options` | GET | owner | B3 | — (B3) |
| `owner/vendors/set-status` | POST | owner | B3 | — (B3) |

**store/** 

| Laluan | Kaedah | Skop disahkan pelayan | Session | Mock |
|---|---|---|---|---|
| `store/auth/forgot` | POST | awam | B2 | — (B2) |
| `store/auth/login` | POST | awam | B2 | — (B2) |
| `store/auth/logout` | POST | awam | B2 | — (B2) |
| `store/auth/register` | POST | awam | B2 | — (B2) |
| `store/auth/resend-verification` | POST | ibu bapa (sesi) | B2 | — (B2) |
| `store/auth/reset` | POST | awam | B2 | — (B2) |
| `store/auth/verify-email` | POST | awam | B2 | — (B2) |
| `store/cart/add` | POST | ibu bapa (sesi) + slug | B6 | — (B6) |
| `store/cart/add-kit` | POST | ibu bapa (sesi) + slug | B6 | — (B6) |
| `store/cart/clear` | POST | ibu bapa (sesi) + slug | B6 | — (B6) |
| `store/cart/get` | GET | ibu bapa (sesi) + slug | B6 | — (B6) |
| `store/cart/remove` | POST | ibu bapa (sesi) + slug | B6 | — (B6) |
| `store/cart/summary` | GET | awam + slug | B6 | — (B6) |
| `store/cart/update` | POST | ibu bapa (sesi) + slug | B6 | — (B6) |
| `store/catalog` | GET | awam + slug | B6 | — (B6) |
| `store/checkout/place` | POST | ibu bapa (sesi) + slug | B7 | — (B7) |
| `store/checkout/quote` | GET | ibu bapa (sesi) + slug | B7 | — (B7) |
| `store/children/list` | GET | ibu bapa (sesi) | B6 | — (B6) |
| `store/children/promote` | POST | ibu bapa (sesi) | B6 | — (B6) |
| `store/children/remove` | POST | ibu bapa (sesi) | B6 | — (B6) |
| `store/children/save` | POST | ibu bapa (sesi) | B6 | — (B6) |
| `store/classes` | GET | awam + slug | B6 | — (B6) |
| `store/clearance/list` | GET | awam + slug | B6 | — (B6) |
| `store/kit` | GET | ibu bapa (sesi) + slug | B6 | — (B6) |
| `store/news/list` | GET | awam + slug | B6 | — (B6) |
| `store/orders/cancel` | POST | ibu bapa (sesi) + slug | B7 | — (B7) |
| `store/orders/get` | GET | ibu bapa (sesi) + slug | B7 | — (B7) |
| `store/orders/list` | GET | ibu bapa (sesi) + slug | B7 | — (B7) |
| `store/orders/check-payment` | POST | ibu bapa (sesi) + slug | B8 | — (B8) |
| `store/orders/pay` | POST | ibu bapa (sesi) + slug | B7 | — (B8: bil gateway sebenar) |
| `store/product` | GET | awam + slug | B6 | — (B6) |
| `store/profile/delete` | POST | ibu bapa (sesi) | B2 | — (B2) |
| `store/profile/get` | GET | ibu bapa (sesi) | B2 | — (B2) |
| `store/profile/password` | POST | ibu bapa (sesi) | B2 | — (B2) |
| `store/profile/save` | POST | ibu bapa (sesi) | B2 | — (B2) |
| `store/school` | GET | awam + slug | B6 | — (B6) |
| `store/schools/list` | GET | awam | B6 | — (B6) |
| `store/session` | GET | awam + slug | B2 | — (B2) |

**vendor/** 

| Laluan | Kaedah | Skop disahkan pelayan | Session | Mock |
|---|---|---|---|---|
| `vendor/activity-logs/list` | GET | vendor sesi + koperasi | B11 | `vendor-finance.js` |
| `vendor/addons/cancel` | POST | vendor sesi + koperasi | B13 | `vendor-finance.js` |
| `vendor/addons/catalog` | GET | vendor sesi + koperasi | B13 | `vendor-finance.js` |
| `vendor/addons/quote` | GET | vendor sesi + koperasi | B13 | `vendor-finance.js` |
| `vendor/addons/subscribe` | POST | vendor sesi + koperasi | B13 | `vendor-finance.js` |
| `vendor/auth/login` | POST | awam | B2 | — (B2) |
| `vendor/auth/verify-2fa` · `vendor/auth/logout` · `vendor/auth/forgot` · `vendor/auth/reset` | POST | awam | B2 | — (baharu B2) |
| `vendor/calendar/delete` | POST | koperasi + modul `calendar` | B13 | `vendor-addons.js` |
| `vendor/calendar/list` | GET | koperasi + modul `calendar` | B13 | `vendor-addons.js` |
| `vendor/calendar/save` | POST | koperasi + modul `calendar` | B13 | `vendor-addons.js` |
| `vendor/categories/delete` | POST | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/categories/list` | GET | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/categories/save` | POST | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/classes/add` | POST | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/classes/delete` | POST | vendor sesi | B4 | — (B4) |
| `vendor/classes/list` | GET | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/classes/update` | POST | vendor sesi | B4 | — (B4) |
| `vendor/clearance/delete` | POST | koperasi + modul `clearance` | B13 | `vendor-addons.js` |
| `vendor/clearance/get` | GET | koperasi + modul `clearance` | B13 | `vendor-addons.js` |
| `vendor/clearance/item-delete` | POST | koperasi + modul `clearance` | B13 | `vendor-addons.js` |
| `vendor/clearance/item-save` | POST | koperasi + modul `clearance` | B13 | `vendor-addons.js` |
| `vendor/clearance/list` | GET | koperasi + modul `clearance` | B13 | `vendor-addons.js` |
| `vendor/clearance/save` | POST | koperasi + modul `clearance` | B13 | `vendor-addons.js` |
| `vendor/cooperatives/check-slug` | GET | vendor sesi | B4 | — (B4) |
| `vendor/cooperatives/get` | GET | vendor sesi | B4 | — (B4) |
| `vendor/cooperatives/list` | GET | vendor sesi | B2 (baca) / B4 | — (B2) |
| `vendor/cooperatives/pricing-preview` | GET | vendor sesi | B4 | — (B4) |
| `vendor/cooperatives/save` | POST | vendor sesi | B4 | — (B4) |
| `vendor/customers/list` | GET | vendor sesi + koperasi (admin + pengurus) | B9 | — (B9) |
| `vendor/customers/save-note` | POST | vendor sesi + koperasi (admin + pengurus) | B9 | — (B9) |
| `vendor/dashboard/summary` | GET | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/grades/list` | GET | vendor sesi + koperasi (+ juruwang sejak B9) | B4 | — (B4) |
| `vendor/handover/collect` | POST | vendor sesi + koperasi (+ juruwang) | B9 | — (B9) |
| `vendor/handover/receive-cash` | POST | vendor sesi + koperasi (+ juruwang) | B9 | — (B9) |
| `vendor/handover/search` | GET | vendor sesi + koperasi (+ juruwang) | B9 | — (B9) |
| `vendor/inventory/adjust` | POST | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/inventory/list` | GET | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/inventory/movements` | GET | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/inventory/stock-in` | POST | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/invoices/get` | GET | vendor sesi | B12 | `vendor-finance.js` |
| `vendor/invoices/list` | GET | vendor sesi | B12 | `vendor-finance.js` |
| `vendor/invoices/pay` | POST | vendor sesi | B12 | `vendor-finance.js` |
| `vendor/kits/copy-year` | POST | vendor sesi + koperasi | B5 | — (B5) |
| `vendor/kits/delete` | POST | vendor sesi + koperasi | B5 | — (B5) |
| `vendor/kits/get` | GET | vendor sesi + koperasi | B5 | — (B5) |
| `vendor/kits/list` | GET | vendor sesi + koperasi | B5 | — (B5) |
| `vendor/kits/product-options` | GET | vendor sesi + koperasi | B5 | — (B5) |
| `vendor/kits/save` | POST | vendor sesi + koperasi | B5 | — (B5) |
| `vendor/kits/set-published` | POST | vendor sesi + koperasi | B5 | — (B5) |
| `vendor/messaging/get` | GET | koperasi + modul `whatsapp_sms` | B13 | `vendor-addons.js` |
| `vendor/messaging/log` | GET | koperasi + modul `whatsapp_sms` | B13 | `vendor-addons.js` |
| `vendor/messaging/save` | POST | koperasi + modul `whatsapp_sms` | B13 | `vendor-addons.js` |
| `vendor/messaging/test` | POST | koperasi + modul `whatsapp_sms` | B13 | `vendor-addons.js` |
| `vendor/modules/active` | GET | vendor sesi + koperasi | B4 (asal B13) | — (B4) |
| `vendor/news/delete` | POST | koperasi + modul `news` | B13 | `vendor-addons.js` |
| `vendor/news/list` | GET | koperasi + modul `news` | B13 | `vendor-addons.js` |
| `vendor/news/recipients` | GET | koperasi + modul `news` | B13 | `vendor-addons.js` |
| `vendor/news/save` | POST | koperasi + modul `news` | B13 | `vendor-addons.js` |
| `vendor/orders/bulk-status` | POST | vendor sesi + koperasi (admin + pengurus) | B9 | — (B9) |
| `vendor/orders/class-options` | GET | vendor sesi + koperasi (+ juruwang) | B9 | — (B9) |
| `vendor/orders/get` | GET | vendor sesi + koperasi (admin + pengurus) | B9 | — (B9) |
| `vendor/orders/labels` | GET | vendor sesi + koperasi (admin + pengurus) | B9 | — (B9) |
| `vendor/orders/list` | GET | vendor sesi + koperasi (admin + pengurus) | B9 | — (B9) |
| `vendor/orders/picking-list` | GET | vendor sesi + koperasi (admin + pengurus) | B9 | — (B9) |
| `vendor/orders/set-status` | POST | vendor sesi + koperasi (admin + pengurus) | B9 | — (B9) |
| `vendor/orders/slot-options` | GET | vendor sesi + koperasi (admin + pengurus) | B9 | — (B9) |
| `vendor/orders/unlock-code` | POST | vendor sesi + koperasi (admin + pengurus) | B9 (baharu) | — (B9) |
| `vendor/payments/gateway` | GET | vendor sesi + koperasi (`vendor_admin`) | B8 | — (B8) |
| `vendor/payments/list` | GET | vendor sesi + koperasi (`vendor_admin`) | B8 | — (B8) |
| `vendor/payments/save-gateway` | POST | vendor sesi + koperasi (`vendor_admin`) | B8 | — (B8) |
| `vendor/payments/save-methods` | POST | vendor sesi + koperasi (`vendor_admin`) | B8 | — (B8) |
| `vendor/payments/settings` | GET | vendor sesi + koperasi (`vendor_admin`) | B8 | — (B8) |
| `vendor/payments/test-gateway` | POST | vendor sesi + koperasi (`vendor_admin`) | B8 | — (B8) |
| `vendor/pos/cancel-payment` | POST | vendor sesi | B10 | `vendor-pos.js` |
| `vendor/pos/catalog` | GET | vendor sesi + koperasi | B10 | `vendor-pos.js` |
| `vendor/pos/checkout` | POST | vendor sesi | B10 | `vendor-pos.js` |
| `vendor/pos/lock` | POST | vendor sesi + koperasi | B10 | `vendor-pos.js` |
| `vendor/pos/payment-status` | GET | vendor sesi | B10 | `vendor-pos.js` |
| `vendor/pos/pin-login` | POST | vendor sesi + koperasi | B10 | `vendor-pos.js` |
| `vendor/pos/receipt` | GET | vendor sesi | B10 | `vendor-pos.js` |
| `vendor/pos/sales` | GET | vendor sesi | B10 | `vendor-pos.js` |
| `vendor/pos/session` | GET | vendor sesi + koperasi | B10 | `vendor-pos.js` |
| `vendor/pos/shift-close` | POST | vendor sesi | B10 | `vendor-pos.js` |
| `vendor/pos/shift-open` | POST | vendor sesi | B10 | `vendor-pos.js` |
| `vendor/pos/shift-summary` | GET | vendor sesi | B10 | `vendor-pos.js` |
| `vendor/products/get` | GET | vendor sesi | B4 | — (B4) |
| `vendor/products/list` | GET | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/products/options` | GET | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/products/save` | POST | vendor sesi + koperasi | B4 | — (B4) |
| `vendor/products/set-active` | POST | vendor sesi | B4 | — (B4) |
| `vendor/promotions/delete` | POST | vendor sesi | B10 | `vendor-finance.js` |
| `vendor/promotions/list` | GET | vendor sesi + koperasi | B10 | `vendor-finance.js` |
| `vendor/promotions/save` | POST | vendor sesi + koperasi | B10 | `vendor-finance.js` |
| `vendor/purchase-orders/cancel` | POST | vendor sesi | B4 (asal B11) | — (B4) |
| `vendor/purchase-orders/get` | GET | vendor sesi | B4 (asal B11) | — (B4) |
| `vendor/purchase-orders/list` | GET | vendor sesi + koperasi | B4 (asal B11) | — (B4) |
| `vendor/purchase-orders/receive` | POST | vendor sesi | B4 (asal B11) | — (B4) |
| `vendor/purchase-orders/save` | POST | vendor sesi + koperasi | B4 (asal B11) | — (B4) |
| `vendor/refunds/create` | POST | vendor sesi + koperasi | B11 | `vendor-finance.js` |
| `vendor/refunds/list` | GET | vendor sesi + koperasi | B11 | `vendor-finance.js` |
| `vendor/refunds/order` | GET | vendor sesi + koperasi | B11 | `vendor-finance.js` |
| `vendor/reports/run` | GET | vendor sesi + koperasi | B11 | `vendor-finance.js` |
| `vendor/school-opening/season-delete` | POST | koperasi + modul `school_opening` | B13 | `vendor-addons.js` |
| `vendor/school-opening/season-save` | POST | koperasi + modul `school_opening` | B13 | `vendor-addons.js` |
| `vendor/school-opening/seasons` | GET | koperasi + modul `school_opening` | B13 | `vendor-addons.js` |
| `vendor/school-opening/slot-delete` | POST | koperasi + modul `school_opening` | B13 | `vendor-addons.js` |
| `vendor/school-opening/slot-save` | POST | koperasi + modul `school_opening` | B13 | `vendor-addons.js` |
| `vendor/school-opening/slots` | GET | koperasi + modul `school_opening` | B13 | `vendor-addons.js` |
| `vendor/session/cooperative` | POST | vendor sesi + koperasi | B2 | — (B2) |
| `vendor/settings/get` | GET | vendor sesi + koperasi (+ juruwang, tanpa `alert_emails`) | B9 (asal B4) | — (B9) |
| `vendor/settings/save` | POST | vendor sesi + koperasi (`vendor_admin`) | B9 (asal B4) | — (B9) |
| `vendor/staff/list` | GET | vendor sesi | B2 | — (B2) |
| `vendor/staff/save` | POST | vendor sesi | B2 | — (B2) |
| `vendor/staff/send-reset` | POST | vendor sesi | B2 | — (B2) |
| `vendor/staff/set-active` | POST | vendor sesi | B2 | — (B2) |
| `vendor/stock-takes/complete` | POST | vendor sesi | B4 (asal B11) | — (B4) |
| `vendor/stock-takes/get` | GET | vendor sesi | B4 (asal B11) | — (B4) |
| `vendor/stock-takes/list` | GET | vendor sesi + koperasi | B4 (asal B11) | — (B4) |
| `vendor/stock-takes/save-counts` | POST | vendor sesi | B4 (asal B11) | — (B4) |
| `vendor/stock-takes/start` | POST | vendor sesi + koperasi | B4 (asal B11) | — (B4) |
| `vendor/subscription/cancel-change` | POST | vendor sesi | B12 | `vendor-finance.js` |
| `vendor/subscription/change` | POST | vendor sesi | B12 | `vendor-finance.js` |
| `vendor/subscription/get` | GET | vendor sesi | B12 | `vendor-finance.js` |
| `vendor/subscription/plans` | GET | vendor sesi | B12 | `vendor-finance.js` |
| `vendor/suppliers/list` | GET | vendor sesi | B4 | — (B4) |
| `vendor/suppliers/save` | POST | vendor sesi | B4 | — (B4) |
| `vendor/templates/options` | GET | vendor sesi | B4 (asal B12) | — (B4) |
