# API Apps Flutter — KoperasiKu (B15)

> Rujukan untuk session F1–F4 (`mobile/`). Apps ibu bapa memanggil **endpoint `store/` yang sama** dengan laman web; bezanya
> hanya pengesahan: token Bearer menggantikan cookie sesi + CSRF. Bentuk `data` terperinci setiap objek: [PELAN.md](PELAN.md) 18.12–18.13.
> Reka bentuk: [PELAN.md](PELAN.md) 10.2 "Konvensyen B15", 11, 15 · Ujian: `php tools/verify-b15.php`.

---

## 1. Asas

| Perkara | Nilai |
|---|---|
| URL asas | `{app_url}/api/v1/` — cth. `https://koperasiku.my/api/v1/store/session.php` (setiap laluan berakhir `.php`) |
| Format | Permintaan POST: `Content-Type: application/json` (badan JSON). GET: query string |
| Jawapan berjaya | `{ "ok": true, "data": … }` |
| Jawapan ralat | `{ "ok": false, "error": "mesej (bahasa akaun)", "errors": { "medan": "mesej" }, …penanda }` |
| Wang | **sen (integer)** — `1250` = RM 12.50 |
| Tarikh | ISO 8601 dengan zon, `2026-10-30T17:33:00+08:00` (Asia/Kuala_Lumpur) |
| Senarai | `?page=1&per_page=20` → `data: { items, total, page, per_page }` |
| Bahasa mesej | `?lang=ms\|en\|zh\|ta` atau bahasa akaun; teks push ikut `customers.language` (`store/profile/save { language }`) |
| HTTPS | Wajib di produksi (token dalam header) |

**Pengepala setiap permintaan selepas log masuk:**

```
Authorization: Bearer 3f9c…(64 aksara hex)
Accept: application/json
```

Tiada cookie, tiada `X-CSRF-Token`. Apps **tidak boleh** menghantar `vendor_id`, `cooperative_id` atau `customer_id` — pelayan
mengambil ibu bapa dari token dan koperasi dari `slug`.

## 2. Kod status & penanda

| HTTP | Penanda dalam badan | Maksud / tindakan apps |
|---|---|---|
| 200 | `ok: true` | Berjaya |
| 401 | `auth_required: true` (+ `token_invalid: true`) | Token tiada / salah / luput / dibatal / akaun dinyahaktif → padam token dari secure storage, ke skrin log masuk |
| 403 | `app_unavailable: true` | Koperasi slug ini **tidak melanggan modul `mobile_app`** → papar mesej + pautan laman web sekolah |
| 403 | `token_required: true` | Laluan khas apps dipanggil dari sesi web (tidak berlaku dalam apps) |
| 403 | `module_locked` / `store.module_unavailable` (404) | Modul store (berita, jualan murah) tidak aktif — semak `store/school.modules` dahulu |
| 404 | | Rekod tiada **atau milik orang lain** (mesej sama — tidak dedah kewujudan) |
| 405 | | Kaedah salah (GET/POST) |
| 422 | `errors` | Ralat borang — papar `errors.{medan}` di bawah medan |
| 429 | | Terlalu banyak permintaan — tunggu & cuba semula (lihat had di bawah) |
| 503 | `school_closed: true` | Koperasi ditutup sementara (vendor digantung) |
| 500 | | Ralat pelayan — `error` umum |

## 3. Had kadar (PELAN 15)

| Bucket | Had |
|---|---|
| Log masuk token setiap IP (`store/auth/token`) | 30 / 15 minit (+ kunci akaun 5 gagal → 15 minit, sama seperti web) |
| Setiap token | 120 permintaan / minit |
| Token salah setiap IP | 30 / 15 minit (selepas itu 429 walaupun token betul) |
| Daftar peranti push setiap token | 20 / jam |
| Sedia ada (troli, checkout, anak, bayaran) | sama seperti web — PELAN 10.2 B6–B8 |

## 4. Token

### `POST store/auth/token` — log masuk apps (awam)

```json
{ "login": "siti@contoh.my", "password": "…", "device_name": "Pixel 8 Siti" }
```

`login` = e-mel **atau** no. telefon (`012-3456701` / `0123456701`). `device_name` pilihan (≤ 100 aksara; lalai "Apps mudah alih").

```json
{ "ok": true, "data": {
    "token": "3f9c…64 hex", "token_id": 12, "expires_at": "2027-01-28T17:33:00+08:00",
    "customer": { "full_name": "…", "phone": "012-3456701", "email": "…", "relationship": "mother", "language": "ms", "email_verified": true } } }
```

- Token mentah dipulangkan **sekali sahaja** — simpan dalam `flutter_secure_storage`. Pelayan menyimpan SHA-256 sahaja.
- **Luput bergerak:** 90 hari selepas **penggunaan terakhir** (setiap penggunaan melanjutkan `expires_at`).
- Had **10 token aktif** setiap ibu bapa — log masuk ke-11 membatal token yang paling lama tidak digunakan.
- Kata laluan salah → 422 mesej umum (sama untuk akaun tiada). Akaun dikunci → 429.
- Pendaftaran akaun baharu, lupa kata laluan & sahkan e-mel kekal di laman web (`store/auth/register|forgot|reset|verify-email`) —
  apps membuka pautan laman web sekolah. Checkout memerlukan e-mel disahkan (`customer.email_verified`).

### `POST store/auth/token-revoke` — log keluar apps (token)

Badan kosong. Token semasa dibatal + peranti push yang didaftar olehnya dibuang → `{ revoked: true }`.

### `GET store/devices/list` — peranti yang log masuk (token atau sesi web)

`data.items[] = { id, device_name, created_at, last_used_at, expires_at, push_enabled, current }` (`current` = token permintaan ini).

### `POST store/devices/revoke { id }` — log keluar peranti lain

`id` dari senarai di atas; id milik ibu bapa lain → 404.

**Token dibatal secara automatik bila:** tukar kata laluan (`store/profile/password` — token lain dibatal, token semasa kekal),
reset kata laluan melalui e-mel (semua), padam akaun (semua + peranti push), akaun dinyahaktif.

## 5. Modul `mobile_app`

Setiap endpoint yang menerima `slug` menolak permintaan Bearer dengan **403 `app_unavailable`** jika koperasi itu tidak melanggan modul
`mobile_app` (`StoreSchool::require` — juga katalog awam bila token dihantar). Laman web sekolah yang sama tidak terjejas.

- `store/session` → `schools[] = { slug, school_name, school_type, state, district, app_enabled }` (sekolah anak-anak)
- `store/school?slug=` (panggil **tanpa** token untuk menyemak) → `app_enabled: true|false`

Apps hanya memaparkan sekolah dengan `app_enabled: true`.

## 6. Push notification (FCM)

### `POST store/push/register` (token)

```json
{ "platform": "android", "push_token": "<token FCM peranti>" }
```

`platform` = `android | ios`; `push_token` 20–255 aksara `[A-Za-z0-9:_-.]`. **Satu peranti push setiap token apps** — daftar semula
dengan token FCM baharu (onTokenRefresh) menggantikan yang lama. Token FCM yang sama dari akaun lain dipindah ke akaun ini.

### `POST store/push/unregister` (token)

Matikan push untuk peranti ini (token apps kekal log masuk) → `{ removed: n }`.

### Muatan push

```json
{ "notification": { "title": "Pesanan sedia diambil", "body": "Pesanan TM-2610-0003 (Aisyah) di SK Taman Melati sedia diambil. Kod ambil: KQ123." },
  "data": { "event": "order_ready", "slug": "sk-taman-melati", "order_no": "TM-2610-0003" } }
```

| `data.event` | Bila | Medan `data` lain | Skrin apps |
|---|---|---|---|
| `order_ready` | Vendor menanda pesanan sedia diambil (kali pertama) | `slug, order_no` | Butiran pesanan (QR ambil) |
| `order_collected` | Setiap serahan barang (termasuk separa) | `slug, order_no` | Butiran pesanan |
| `news` | Berita terbit (sekali sahaja; berjadual → pada masa terbit) | `slug, news_id` | Senarai berita sekolah |

Hanya koperasi dengan modul `mobile_app`; teks dalam bahasa akaun ibu bapa. Butiran pesanan dicari dengan `store/orders/list?slug=` →
padankan `order_no` → `store/orders/get?slug=&id=`.

**Pelayan (owner):** fail akaun perkhidmatan Firebase (Project settings → Service accounts → Generate new private key) disalin ke
`config/firebase-service-account.json` (`config fcm_service_account_file`, dalam `.gitignore`). Cron setiap minit:
`php cron/send-push.php` (FCM HTTP v1, JWT RS256 → OAuth, 3 cubaan; token ditolak FCM `UNREGISTERED` → peranti dibuang).
Projek Firebase apps (F3) **mesti sama** dengan akaun perkhidmatan ini.

## 7. Endpoint store yang digunakan apps

Semua menerima token Bearer. `slug` = sekolah (dari `store/session.schools`). Bentuk penuh: PELAN 18.12.

| Laluan | Kaedah | Input | Nota |
|---|---|---|---|
| `store/schools/list` | GET | `q, state, page` | awam — cari sekolah |
| `store/school` | GET | `slug` | maklumat, `modules`, `payment_methods`, `notices`, `app_enabled` |
| `store/session` | GET | `slug?` | `{ customer, cart_count, schools }` |
| `store/profile/get` · `save` · `password` · `delete` | GET/POST | lihat PELAN 18.12 | `delete { password }` (PDPA) membatal semua token |
| `store/children/list` · `save` · `remove` · `promote` | GET/POST | | semua anak (semua sekolah) |
| `store/classes` | GET | `slug, grade` | cadangan nama kelas |
| `store/catalog` · `store/product` | GET | `slug, q, category_id, grade, page` · `slug, id` | |
| `store/kit` | GET | `slug, child_id?` | kit darjah anak |
| `store/cart/get` · `summary` · `add` · `add-kit` · `update` · `remove` · `clear` | GET/POST | `slug` + … | CartQuote — harga & stok dikira semula di pelayan |
| `store/checkout/quote` · `place` | GET/POST | `slug` + … | `place` → `{ order_id, order_no, short_code, payment }` |
| `store/orders/list` · `get` · `pay` · `cancel` · `check-payment` | GET/POST | `slug, id` | `get` memulangkan `qr_token` (QR ambil barang) |
| `store/news/list` · `store/clearance/list` | GET | `slug` | jika modul aktif |
| `store/auth/token` · `token-revoke` | POST | | §4 |
| `store/devices/list` · `revoke` | GET/POST | | §4 |
| `store/push/register` · `unregister` | POST | | §6 |

### Bayaran online (webview)

`checkout/place` atau `orders/pay` dengan `payment_method: online` → `payment = { reference, gateway_name, amount, mode, redirect_url, form }`:

1. `form === null` → buka `redirect_url` dalam webview.
2. `form = { action, fields }` (iPay88) → bina borang HTML `POST action` dengan `fields` tersembunyi dan hantar automatik dalam webview
   (`redirect_url` iPay88 menunjuk ke `store/payment-redirect.php` yang memerlukan sesi web — jangan guna dalam apps).
3. Bila webview menuju ke `{app_url}/store/payment-return.php…` → tutup webview, panggil `POST store/orders/check-payment { slug, id }`
   untuk status sebenar (pelayan bertanya API gateway — parameter URL gateway tidak dipercayai).

## 8. Contoh aliran

```
POST store/auth/token            { login, password, device_name }      → simpan token
POST store/push/register         { platform, push_token }
GET  store/session                                                     → sekolah (app_enabled)
GET  store/school?slug=sk-taman-melati
GET  store/kit?slug=sk-taman-melati&child_id=1
POST store/cart/add-kit          { slug, child_id, kit_id, items }
GET  store/checkout/quote?slug=sk-taman-melati
POST store/checkout/place        { slug, pickup_by_type: "student", payment_method: "cash", expected_total }
GET  store/orders/get?slug=sk-taman-melati&id=42                       → QR (qr_token) + short_code
POST store/auth/token-revoke                                           → log keluar
```

Contoh `curl`:

```bash
curl -s -X POST https://koperasiku.my/api/v1/store/auth/token.php \
     -H 'Content-Type: application/json' -d '{"login":"siti@contoh.my","password":"Demo@12345","device_name":"curl"}'
curl -s 'https://koperasiku.my/api/v1/store/session.php' -H "Authorization: Bearer $TOKEN"
```

## 9. Keselamatan (ringkas)

- Token 256-bit rawak; DB menyimpan `SHA-256`; perbandingan melalui hash (indeks UNIQUE). Token tidak pernah dilog / dipulangkan semula.
- Permintaan Bearer tidak memulakan sesi PHP & tidak menerima cookie; token salah **tidak** jatuh balik ke cookie sesi.
- CSRF dilangkau hanya bila token Bearer **sudah disahkan** oleh `CustomerAuth` (laluan `store/`) — panel owner/vendor tidak menerima token.
- `customer_id` dari token sahaja; setiap pertanyaan menapis `customer_id` + koperasi slug (ujian kebocoran A ↔ B dalam `verify-b15`).
- Apache CGI/FastCGI: `.htaccess` akar menyalin header `Authorization` ke `HTTP_AUTHORIZATION`. Nginx + PHP-FPM: `fastcgi_param HTTP_AUTHORIZATION $http_authorization;`
