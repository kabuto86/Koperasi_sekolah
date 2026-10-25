# Pelan Induk — KoperasiKu SaaS (Koperasi Sekolah)

> **Dokumen rujukan tetap.** Baca fail ini SEBELUM menulis sebarang kod dalam
> mana-mana session. Keputusan di sini sudah disahkan oleh pengguna — jangan ubah
> tanpa bertanya. Status semasa: [PROSES.md](PROSES.md). Prompt kerja:
> [PROMPT-SESSION.md](PROMPT-SESSION.md).

Kemas kini terakhir: 25 Oktober 2026 (U3b — UI vendor bahagian 2)
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

### E. Kit keperluan sekolah (2)

- **`school_kits`** — id, vendor_id, cooperative_id, grade_level_code, school_year, name, note, is_published · UNIQUE(cooperative_id, grade_level_code, school_year)
- **`school_kit_items`** — id, vendor_id, school_kit_id FK, product_id, variant_id NULL, qty, is_mandatory, note, sort_order
  > `is_mandatory = 1` tetap boleh di-uncheck oleh ibu bapa (mungkin sudah ada di rumah), tetapi UI memaparkan amaran "Item wajib sekolah".

### F. Pembeli (5)

- **`customers`** — id, full_name, phone UNIQUE, email UNIQUE, password_hash VARCHAR(255), relationship ENUM(mother, father, guardian), language, email_verified_at, pdpa_consent_at, is_active, failed_attempts, locked_until, last_login_at, deleted_at
  > Tiada `vendor_id` — ibu bapa ialah akaun global yang boleh membeli dari beberapa koperasi.
- **`children`** — id, customer_id FK, cooperative_id FK, vendor_id (salinan, untuk tapisan vendor), full_name, grade_level_code, school_class_id NULL, class_name, school_year, is_active
- **`customer_tokens`** — id, customer_id, type ENUM(verify_email, reset_password), token_hash, expires_at, used_at
- **`api_tokens`** — id, customer_id, token_hash UNIQUE, device_name, last_used_at, expires_at, revoked_at
- **`push_devices`** — id, customer_id, platform ENUM(android, ios), push_token, created_at

### G. Pesanan, ambil & bayaran (13)

- **`carts`** — id, customer_id, cooperative_id, vendor_id, updated_at · UNIQUE(customer_id, cooperative_id)
- **`cart_items`** — id, cart_id, product_id, variant_id, child_id, qty, source ENUM(ala_carte, kit), school_kit_id NULL
- **`orders`** — id, vendor_id, cooperative_id, customer_id NULL (NULL = POS walk-in), order_no, short_code, channel ENUM(online, pos), payment_method ENUM(cash, online), status (lihat 6), subtotal, discount, total, promotion_id, pickup_by_type ENUM(student, parent, proxy), pickup_by_name, pickup_code_hash NULL, pickup_qr_token UNIQUE, pickup_attempts, customer_name, customer_phone, customer_email (snapshot), expires_at, paid_at, collected_at, cancelled_at, created_at · UNIQUE(cooperative_id, order_no), UNIQUE(cooperative_id, short_code), INDEX(vendor_id, cooperative_id, status)
- **`order_items`** — id, vendor_id, order_id, product_id, variant_id, product_name, variant_label, unit_price, qty, line_total, source ENUM(ala_carte, kit, pos), school_kit_id NULL, child_id NULL, child_name, grade_label, class_name, collected_qty, cancelled_qty (refund sebelum diambil), returned_qty (dipulang selepas diambil)
- **`order_status_logs`** — id, vendor_id, order_id, from_status, to_status, actor_type, actor_id, note, created_at
- **`order_pickups`** — id, vendor_id, order_id, method ENUM(A, B, C, D, E), picked_by_type ENUM(student, parent, proxy), picked_by_name, vendor_user_id, created_at
- **`order_pickup_items`** — id, vendor_id, order_pickup_id, order_item_id, qty
- **`payment_gateways`** — code PK (`toyyibpay`, `billplz`, `senangpay`, `ipay88`, `stripe`), name, config_fields_json, is_enabled_for_vendors, is_enabled_for_platform
- **`cooperative_gateways`** — id, vendor_id, cooperative_id, gateway_code, credentials_encrypted, mode ENUM(sandbox, live), is_active · UNIQUE(cooperative_id, gateway_code)
- **`payments`** — id, vendor_id, cooperative_id, order_id, method ENUM(cash, online), gateway_code NULL, gateway_ref UNIQUE NULL, amount, status ENUM(pending, paid, failed, refunded), raw_payload, verified_at, received_by NULL (juruwang), created_at
- **`refunds`** — id, vendor_id, cooperative_id, refund_no, order_id, payment_id, amount, reason, method ENUM(cash, bank_transfer, gateway), restock, status, vendor_user_id, created_at
  > Butiran barang: **`refund_items`** — id, vendor_id, refund_id, order_item_id, qty, cancelled_qty (U3c)
- **`pos_shifts`** — id, vendor_id, cooperative_id, vendor_user_id, opened_at, opening_cash, closed_at, closing_cash, expected_cash, variance, status
- **`webhook_logs`** — id, gateway_code, cooperative_id NULL, payload, signature_valid, processed, created_at

### H. Modul add-on (7)

- **`news_posts`** — id, vendor_id, cooperative_id, title, body, image_path, published_at, send_email, is_active
- **`clearance_sales`** — id, vendor_id, cooperative_id, name, starts_at, ends_at, is_active
- **`clearance_items`** — id, vendor_id, clearance_sale_id, product_id, variant_id, sale_price, qty_limit
- **`calendar_events`** — id, vendor_id, cooperative_id, title, event_type ENUM(school_holiday, coop_closed, school_opening, other), starts_on, ends_on, blocks_orders
- **`preorder_seasons`** — id, vendor_id, cooperative_id, school_year, opens_at, closes_at, pickup_starts_on, note
- **`pickup_slots`** — id, vendor_id, cooperative_id, preorder_season_id, slot_date, time_start, time_end, grade_level_code NULL, capacity, booked
- **`notification_queue`** — id, vendor_id, channel ENUM(push, whatsapp, sms), recipient, message, status, attempts, sent_at

### I. Sistem (2)

- **`email_queue`** — id, vendor_id NULL, to_email, subject, body_html, template_key, status ENUM(pending, sent, failed), attempts, last_error, created_at, sent_at
  > E-mel dihantar oleh `cron/send-emails.php`, bukan semasa checkout — checkout tidak lambat walaupun SMTP perlahan.
- **`rate_limits`** — bucket_key, hits, window_start · PK(bucket_key)

Jumlah: **68 jadual**.

## 10. Backend, API & frontend

### 10.1 Struktur folder

```
Koperasi_sekolah/
├── CLAUDE.md
├── DOC/            PELAN.md · PROSES.md · PROMPT-SESSION.md
├── config/         app.php · malaysia.php (negeri, jenis sekolah, hari) · database.php (.gitignore) · database.example.php · secret.key (.gitignore)
├── core/           bootstrap.php · Config · View · SecurityHeaders (CSP) — sejak U1
│                   Database · Request · Response · Validator · Csrf · Lang (+ t(), e())
│                   OwnerAuth · VendorAuth · CustomerAuth · ApiTokenAuth
│                   Crypto (sodium) · RateLimit · ModuleAccess · PlanLimit · Mailer
│                   Uploader · View · Totp · SecurityHeaders
├── jobs/           SATU KELAS = SATU KERJA
│   ├── Auth/ Owner/ Vendor/ Cooperative/ Plan/ Billing/ Module/ Template/
│   ├── Product/ Inventory/ Supplier/ Promotion/ SchoolKit/
│   ├── Customer/ Child/ Cart/ Order/ Pickup/ Payment/ Pos/ Report/
│   └── News/ Clearance/ Calendar/ SchoolOpening/ Notification/ Language/ Setting/
├── payments/       GatewayInterface.php · GatewayFactory.php
│   └── drivers/    ToyyibPayGateway · BillplzGateway · SenangPayGateway · Ipay88Gateway · StripeGateway
├── api/v1/
│   ├── owner/  vendor/  store/  pos/
│   └── webhooks/   toyyibpay.php · billplz.php · …
├── partials/       head · lang-switcher · sidebar-menu · ui-shell (toast + confirm) · scripts · login-form
│                   page-title (tajuk + butang tindakan + kembali) · stat-card (kad papan pemuka) · sub-nav (pills)
├── owner/          panel owner (halaman .php)
│   └── _layout/    header · sidebar · footer · profile-menu · menu.php (senarai menu)
├── vendor/         panel vendor (halaman .php)
│   ├── _layout/    header · sidebar · footer · profile-menu · menu.php · addons.php · sub-nav.php (kumpulan sub-navigasi)
│   ├── addons/     halaman modul add-on: {code}.php (U6)
│   └── pos/        POS kaunter + skrin serah barang
├── store/          index.php (titik masuk ibu bapa: slug → koperasi → template)
├── templates/
│   └── template1/  manifest.json · layout.php · partials/ · pages/ · css/ · js/
├── modules/        news/ · clearance/ · calendar/ · school_opening/
├── lang/           ms.php · en.php · zh.php · ta.php
├── cron/           send-emails · expire-cash-orders · generate-invoices · suspend-overdue · promote-grades
├── tools/          check-ui.php (tiada inline CSS + kunci 4 bahasa sama) — CLI sahaja
├── assets/
│   ├── lib/        bootstrap/ (5.3.3) · bootstrap-icons/ (1.11.3) · qrcode/ (qrcode-generator 1.4.4, MIT — sejak U2)
│   ├── css/        app.css · print-label.css · print-slip.css · receipt.css
│   ├── js/         api.js · lang.js · helpers.js · login.js · vendor-layout.js
│   │   ├── owner/  satu fail per halaman owner (dashboard.js, vendors.js, …)
│   │   ├── vendor/ satu fail per halaman vendor (dashboard.js, …)
│   │   └── mock/   mock.js · owner.js · vendor.js · vendor-orders.js · vendor-finance.js · store.js (Peringkat 1 sahaja)
│   ├── img/
│   └── fonts/      noto-sans-sc/ · noto-sans-tamil/ (woff2 400 & 700, unicode-range — dimuat bila perlu)
├── uploads/        {vendor_id}/{cooperative_id}/… (.gitignore isi, .htaccess halang PHP)
└── database/       schema.sql · seed.sql · migrations/
```

### 10.2 Contoh endpoint nipis

```php
<?php
// api/v1/vendor/products/save.php
require __DIR__ . '/../../../../core/bootstrap.php';

VendorAuth::requireRole(['vendor_admin', 'coop_manager']);
Csrf::check();

$vendorId      = VendorAuth::vendorId();                       // dari sesi
$cooperativeId = (new ValidateCooperativeJob($vendorId))->run(Request::int('cooperative_id'));
$input         = Validator::product(Request::all());

$result = (new SaveProductJob($vendorId, $cooperativeId))->run($input);
Response::ok($result);
```

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

## 11. Log masuk & kata laluan

| Jenis | Cara | Tambahan |
|---|---|---|
| Owner | E-mel + kata laluan | **2FA TOTP wajib** |
| Vendor (admin/pengurus) | E-mel + kata laluan | 2FA TOTP pilihan, pemilih koperasi selepas log masuk |
| Juruwang POS | E-mel + kata laluan, atau PIN pada peranti POS berdaftar | PIN 3 cubaan → dikunci |
| Ibu bapa | E-mel **atau** no. telefon + kata laluan | Pengesahan e-mel |

- **Argon2id** `memory_cost 19456`, `time_cost 2`, `threads 1` (bukan lalai PHP — terlalu perlahan, 801ms diukur). `password_hash` VARCHAR(255). PIN juga di-hash Argon2id
- `password_needs_rehash()` setiap log masuk berjaya
- Mesej ralat sama untuk akaun tiada & kata laluan salah: *"E-mel atau kata laluan tidak sah"*
- Kunci 15 minit selepas 5 cubaan gagal, rekod setiap cubaan + IP
- `session_regenerate_id(true)` selepas log masuk. Tiga nama sesi berasingan untuk owner/vendor/ibu bapa
- Status langganan vendor disemak selepas pengesahan, dan pada setiap permintaan

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
- `mock/owner.js`, `mock/vendor.js`, `mock/store.js` — dimuat oleh layout masing-masing
- `config/app.php` `use_mock => false` (Peringkat 2) → skrip mock tidak dimuat; hanya `Api.request()` ditukar
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
| `owner/auth/login` | POST | `email, password` | `{ requires_2fa: true }` |
| `owner/auth/verify-2fa` | POST | `code` (6 digit) | `{ user: { id, name, role } }` |
| `vendor/auth/login` | POST | `email, password` | `{ user: { id, name, role }, requires_2fa: false }` |
| `vendor/cooperatives/list` | GET | `page, per_page, q` | senarai Cooperative |
| `vendor/session/cooperative` | POST | `cooperative_id` | `{ cooperative }` — pelayan sahkan milik vendor (`ValidateCooperativeJob`) dan simpan dalam sesi |
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
- Store Peringkat 1: `store/index.php?school={slug}&page={halaman}` — `View::storeUrl()`.
  `&template={kod}` = pratonton template dari panel owner (hanya jika folder wujud; B6 hadkan kepada owner).
  URL cantik `/{slug}/` ditambah dalam B6 (.htaccess) tanpa mengubah template
- Helpers: `formatRM(sen)`, `formatDate(iso, withTime)`, `showToast(msg, type)`,
  `confirmDialog(msg, {title, okText, okClass})` → Promise<boolean>,
  `renderTable(tbody, items, [{key, className, render}])`, `statusBadge(status)`, `createEl(tag, class, text)`
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
Kunci: `order_new_vendor, order_receipt, order_ready, order_collected, invoice_issued, password_reset`.
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

**OrderSummary** — `vendor/orders/list` (tapis `q` [no., kod pendek, nama murid/ibu bapa, telefon, token QR], `status` [boleh senarai
dipisah koma], `grade`, `class_name`, `payment_method`, `date_from`, `date_to`; `data.status_counts: { ready: 7, … }`)
```js
{ id, order_no, short_code, status, payment_method: 'cash|online', payment_status, total, customer_name, customer_phone,
  children: [{ child_name, grade_level_code, class_name }], item_count, created_at, expires_at }
```
**OrderDetail** — `vendor/orders/get {id}` = OrderSummary + `subtotal, discount, customer_email, pickup_by_type: 'student|parent|proxy',
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

- `vendor/orders/picking-list { status (lalai 'paid,unpaid_cash,preparing'), grade, class_name }` →
  `{ school_name, generated_at, order_count, groups: [{ grade_level_code, class_name, children: [{ child_name, order_no, short_code, status,
  items: [{ product_name, variant_label, qty /* baki belum diambil */ }] }] }], totals: [{ product_name, variant_label, sku, qty }] }`
- `vendor/orders/labels { grade, class_name, order_id? }` → `{ school_name, labels: [{ order_id, order_no, short_code, child_name,
  grade_level_code, class_name, item_count, payment_status, qr_token }] }` — satu label setiap anak setiap pesanan
- `vendor/orders/class-options` → `[{ grade_level_code, class_name }]`
- `vendor/handover/search { q, grade, class_name }` → `{ items: [OrderDetail ×20], settings: { methods: ['A','B','C','D','E'?], code_required_above /* sen, 0 = tiada */ } }`
  — padanan tepat token QR / kod pendek dahulu; jika tidak, pesanan aktif (`paid, unpaid_cash, preparing, ready, partially_collected`)
  disusun `ready` dahulu

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

