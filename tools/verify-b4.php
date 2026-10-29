<?php
// Semakan B4 — vendor: koperasi, kelas, kategori, produk (+ varian, darjah, terjemahan, gambar), inventori, pembekal, PO,
// stock take, papan pemuka + sekatan peranan + ujian kebocoran VENDOR01 ↔ VENDOR02 & ibu bapa A ↔ B. CLI sahaja:
//   php tools/verify-b4.php
// Perlu: DB koperasi_saas (schema + seed), config/database.php, config/secret.key, sambungan PHP curl.
// Skrip memulakan pelayan PHP sendiri (php -S 127.0.0.1:8097). Jadual B4 & fail muat naik dipulihkan di hujung.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

// Gambar PNG 1×1 sebenar (data URL) dan "gambar" palsu (teks PHP)
const PNG_DATA_URL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
const FAKE_DATA_URL = 'data:image/png;base64,PD9waHAgZWNobyAnaGFjayc7ID8+';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

function errorOf($res, $field)
{
    return $res['errors'][$field] ?? null;
}

function stockQty($coopId, $productId, $variantId)
{
    return Database::fetchValue(
        "SELECT qty_on_hand FROM stock_levels WHERE cooperative_id = ? AND product_id = ? AND variant_id = ?",
        [$coopId, $productId, $variantId]
    );
}

function uploadFiles()
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT . '/uploads', FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $files[] = $file->getPathname();
    }
    return $files;
}

// Folder kosong yang dicipta oleh ujian (uploads/{vendor}/{koperasi}/...) dibuang; uploads/ sendiri kekal
function removeEmptyDirs($dir)
{
    foreach (glob($dir . '/*', GLOB_ONLYDIR) as $child) {
        removeEmptyDirs($child);
        if (count(scandir($child)) === 2) {
            rmdir($child);
        }
    }
}

function newCoopInput($slug, $extra = [])
{
    $day = ['open' => true, 'from' => '07:30', 'to' => '13:00'];
    $closed = ['open' => false, 'from' => '', 'to' => ''];
    return array_merge([
        'school_name' => 'SK Ujian B4', 'school_code' => 'bba9999', 'school_type' => 'SK', 'state' => 'Selangor',
        'district' => 'Gombak', 'address' => 'Jalan Ujian 1', 'phone' => '03-1111111', 'slug' => $slug, 'template_id' => 1,
        'default_language' => 'ms', 'accept_cash' => true, 'accept_online' => false, 'logo_url' => null,
        'opening_hours' => ['mon' => $day, 'tue' => $day, 'wed' => $day, 'thu' => $day, 'fri' => $day, 'sat' => $closed, 'sun' => $closed],
    ], $extra);
}

$port = 8097;
$base = 'http://127.0.0.1:' . $port;
$log = sys_get_temp_dir() . '/kop-verify-b4.log';
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes);
usleep(700000);

// Simpan keadaan asal jadual yang diubah (dipulihkan di hujung, FK dimatikan sementara)
$tables = ['cooperatives', 'cooperative_settings', 'subscription_items', 'school_classes', 'categories', 'products', 'product_translations',
    'product_variants', 'product_grade_levels', 'stock_levels', 'stock_movements', 'suppliers', 'purchase_orders', 'purchase_order_items',
    'stock_takes', 'stock_take_items', 'activity_logs', 'children', 'plans', 'invoices', 'invoice_lines', 'email_queue', 'platform_settings'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
$filesBefore = uploadFiles();
clearTestLocks();

try {
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');     // VENDOR01 admin — koperasi 1
    $v2 = vendorLogin($base, 'meiling@bestari.my');        // VENDOR02 admin — koperasi 2, 3
    $manager = vendorLogin($base, 'chong@bestari.my');     // VENDOR02 pengurus — koperasi 2 sahaja
    $cashier = vendorLogin($base, 'kasim@niagasekolah.my'); // VENDOR01 juruwang
    $v1->page('vendor/index.php');

    echo "1. Akses & peranan (PELAN 4)\n";
    $guest = new Browser($base);
    check('Tanpa sesi → products/list auth_required', !empty($guest->get('vendor/products/list', ['cooperative_id' => 1])['auth_required']));
    check('POST tanpa X-CSRF-Token → 419', $v1->postStatus('vendor/products/save', ['cooperative_id' => 1], false) === 419);
    check('GET ke endpoint POST → 405', $v1->getStatus('vendor/inventory/adjust') === 405);
    $cashierBlocked = 0;
    foreach (['products/list', 'inventory/list', 'categories/list', 'suppliers/list', 'purchase-orders/list', 'dashboard/summary', 'cooperatives/get'] as $route) {
        $cashierBlocked += $cashier->getStatus('vendor/' . $route . '.php?cooperative_id=1&id=1') === 403 ? 1 : 0;
    }
    check('Juruwang → 7 laluan katalog/inventori/papan pemuka 403', $cashierBlocked === 7);
    check('Juruwang boleh baca modules/active (menu)', ok($cashier->get('vendor/modules/active', ['cooperative_id' => 1])));
    check('Pengurus → pricing-preview 403 (pentadbir sahaja)', $manager->getStatus('vendor/cooperatives/pricing-preview') === 403);
    check('Pengurus → tambah koperasi baharu 403', $manager->postStatus('vendor/cooperatives/save', newCoopInput('sk-pengurus-b4')) === 403);
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    check('Sesi ibu bapa → API vendor 401', !empty($parentA->get('vendor/products/list', ['cooperative_id' => 1])['auth_required']));

    echo "2. Kebocoran VENDOR01 ↔ VENDOR02 (PELAN 8, 15)\n";
    check('V02 products/get produk V01 (koperasi 1) → 404', $v2->getStatus('vendor/products/get.php?cooperative_id=1&id=1') === 404);
    check('V02 products/get produk V01 melalui koperasi sendiri → 404', $v2->getStatus('vendor/products/get.php?cooperative_id=2&id=1') === 404);
    check('V01 categories/list koperasi V02 → 404', $v1->getStatus('vendor/categories/list.php?cooperative_id=2') === 404);
    check("V01 cooperative_id '2 OR 1=1' → 404", $v1->getStatus('vendor/inventory/list.php?cooperative_id=' . urlencode('2 OR 1=1')) === 404);
    check('V02 cooperatives/get koperasi V01 → 404', $v2->getStatus('vendor/cooperatives/get.php?id=1') === 404);
    $before = stockQty(1, 1, 0);
    $res = $v2->post('vendor/inventory/stock-in', ['cooperative_id' => 1, 'product_id' => 1, 'variant_id' => 0, 'qty' => 50]);
    check('V02 stok masuk ke koperasi V01 ditolak, stok tidak berubah', !ok($res) && stockQty(1, 1, 0) === $before);
    $res = $v2->post('vendor/inventory/stock-in', ['cooperative_id' => 2, 'product_id' => 1, 'variant_id' => 0, 'qty' => 50]);
    check('V02 stok masuk produk V01 melalui koperasi sendiri ditolak', !ok($res) && stockQty(1, 1, 0) === $before);
    check('V02 suppliers/save pembekal V01 → 404', $v2->postStatus('vendor/suppliers/save', ['id' => 1, 'name' => 'Curi', 'phone' => '1']) === 404);
    $suppliers = $v2->get('vendor/suppliers/list');
    check('Senarai pembekal V02 hanya miliknya', ok($suppliers) && array_column($suppliers['data']['items'], 'id') === [2]);
    check('V02 classes/delete kelas V01 → 404', $v2->postStatus('vendor/classes/delete', ['cooperative_id' => 2, 'id' => 1]) === 404
        && Database::fetchValue("SELECT COUNT(*) FROM school_classes WHERE id = 1") == 1);
    check('V02 products/set-active produk V01 → 404', $v2->postStatus('vendor/products/set-active', ['cooperative_id' => 2, 'id' => 1, 'is_active' => false]) === 404);
    check('Pengurus Chung Hwa → produk SMK Seri Indah (koperasi 3) 404', $manager->getStatus('vendor/products/list.php?cooperative_id=3') === 404);
    check('Pengurus Chung Hwa → produk koperasi 2 dibenarkan', ok($manager->get('vendor/products/list', ['cooperative_id' => 2])));
    check('V02 cooperatives/save sunting koperasi V01 → 404', $v2->postStatus('vendor/cooperatives/save', newCoopInput('sk-taman-melati', ['id' => 1])) === 404);
    check('V02 check-slug: slug V01 "sudah digunakan" tanpa dedah pemilik', ($r = $v2->get('vendor/cooperatives/check-slug', ['slug' => 'sk-taman-melati', 'id' => 1]))
        && $r['data']['available'] === false && !isset($r['data']['vendor_id']));

    echo "3. Koperasi saya\n";
    $coop = $v1->get('vendor/cooperatives/get', ['id' => 1]);
    check('cooperatives/get + waktu operasi 7 hari', ok($coop) && count($coop['data']['opening_hours']) === 7 && $coop['data']['opening_hours']['mon']['from'] === '07:30');
    check('check-slug: slug sendiri tersedia semasa sunting', $v1->get('vendor/cooperatives/check-slug', ['slug' => 'sk-taman-melati', 'id' => 1])['data']['available'] === true);
    check('check-slug: laluan sistem "api" tidak tersedia', $v1->get('vendor/cooperatives/check-slug', ['slug' => 'api'])['data']['available'] === false);
    check('check-slug: format salah tidak sah', $v1->get('vendor/cooperatives/check-slug', ['slug' => 'SK Taman!'])['data']['valid'] === false);
    $pricing = $v2->get('vendor/cooperatives/pricing-preview');
    check('V02 (Asas, 2/2 koperasi) → limit_reached', ok($pricing) && $pricing['data']['limit_reached'] === true && $pricing['data']['cooperative_number'] === 3);
    $res = $v2->post('vendor/cooperatives/save', newCoopInput('smk-had-b4'));
    check('V02 tambah koperasi ke-3 ditolak had pelan', !ok($res) && strpos($res['error'], '2') !== false);
    $res = $v1->post('vendor/cooperatives/save', newCoopInput('sk-ujian-b4', ['accept_cash' => false]));
    check('Tiada kaedah bayaran ditolak', errorOf($res, 'accept_online') !== null);
    $res = $v1->post('vendor/cooperatives/save', newCoopInput('sk-ujian-b4', ['school_code' => 'AB12']));
    check('Kod KPM salah ditolak', errorOf($res, 'school_code') !== null);
    $res = $v1->post('vendor/cooperatives/save', newCoopInput('sk-ujian-b4', ['template_id' => 2]));
    check('Template belum dilanggan ditolak', errorOf($res, 'template_id') !== null);
    $bad = newCoopInput('sk-ujian-b4');
    $bad['opening_hours']['mon'] = ['open' => true, 'from' => '13:00', 'to' => '07:00'];
    check('Waktu operasi mula selepas tamat ditolak', errorOf($v1->post('vendor/cooperatives/save', $bad), 'opening_hours.mon.to') !== null);
    check('Slug V02 ditolak "sudah digunakan"', errorOf($v1->post('vendor/cooperatives/save', newCoopInput('sjkc-chung-hwa')), 'slug') !== null);
    check('Logo palsu (PHP dalam data URL PNG) ditolak', errorOf($v1->post('vendor/cooperatives/save', newCoopInput('sk-ujian-b4', ['logo_url' => FAKE_DATA_URL])), 'logo_url') !== null);
    $res = $v1->post('vendor/cooperatives/save', newCoopInput('sk-ujian-b4', ['logo_url' => PNG_DATA_URL, 'vendor_id' => 2]));
    $newCoopId = $res['data']['id'] ?? 0;
    $newCoop = Database::fetchOne("SELECT * FROM cooperatives WHERE id = ?", [$newCoopId]);
    check('V01 tambah koperasi ke-2 berjaya; vendor_id dari sesi (input diabaikan)', ok($res) && (int) $newCoop['vendor_id'] === 1 && $newCoop['school_code'] === 'BBA9999');
    check('Logo disimpan sebagai fail hash di uploads/1/{id}/logo/', $newCoop['logo_path'] !== null
        && strpos($newCoop['logo_path'], '1/' . $newCoopId . '/logo/') === 0 && is_file(APP_ROOT . '/uploads/' . $newCoop['logo_path']));
    $item = Database::fetchOne("SELECT * FROM subscription_items WHERE item_type = 'cooperative' AND cooperative_id = ?", [$newCoopId]);
    check('Harga koperasi ke-2 (Pro tahunan peringkat 2–3) = 79000 direkod dalam subscription_items', $item !== null && (int) $item['unit_price'] === 79000);
    $settings = CooperativeSettings::all(1, $newCoopId);
    check('Tetapan awal koperasi baharu (pickup A–D, tunai luput 3 hari, waktu operasi)', ($settings['pickup_methods'] ?? '') === 'A,B,C,D'
        && ($settings['cash_order_expiry_days'] ?? '') === '3' && isset($settings['opening_hours']));
    $res = $manager->post('vendor/cooperatives/save', newCoopInput('sjkc-chung-hwa', ['id' => 2, 'school_name' => 'SJKC Chung Hwa', 'school_type' => 'SJKC', 'school_code' => 'JBC5678', 'state' => 'Johor']));
    check('Pengurus sunting koperasi yang diberi (Chung Hwa) dibenarkan', ok($res));
    check('Pengurus sunting koperasi lain (Seri Indah) → 404', $manager->postStatus('vendor/cooperatives/save', newCoopInput('smk-seri-indah', ['id' => 3])) === 404);
    $res = $v1->post('vendor/cooperatives/save', newCoopInput('sk-ujian-b4', ['id' => $newCoopId, 'logo_url' => null]));
    clearstatcache();
    check('Buang logo → logo_path NULL & fail dipadam', ok($res) && $res['data']['logo_url'] === null && !is_file(APP_ROOT . '/uploads/' . $newCoop['logo_path']));

    echo "4. Kelas & darjah\n";
    check('grades/list SMK → T1–T6', $v2->get('vendor/grades/list', ['cooperative_id' => 3])['data'] === ['T1', 'T2', 'T3', 'T4', 'T5', 'T6']);
    $res = $v1->post('vendor/classes/add', ['cooperative_id' => 1, 'grade_level_code' => 'T1', 'names' => ['Amanah']]);
    check('Tingkatan T1 untuk SK ditolak', errorOf($res, 'grade_level_code') !== null);
    $res = $v1->post('vendor/classes/add', ['cooperative_id' => 1, 'grade_level_code' => 'D1', 'names' => ['bestari', 'Dinamik']]);
    check('Kelas pendua (tanpa beza huruf besar) ditolak', !ok($res) && strpos($res['error'], 'bestari') !== false);
    $res = $v1->post('vendor/classes/add', ['cooperative_id' => 1, 'grade_level_code' => 'D2', 'names' => ['Dinamik', 'Elit', 'dinamik', '']]);
    check('Tambah 2 kelas sekali gus (pendua dalam input & kosong dibuang)', ok($res) && $res['data']['added'] === 2);
    $classId = (int) Database::fetchValue("SELECT id FROM school_classes WHERE cooperative_id = 1 AND grade_level_code = 'D2' AND class_name = 'Elit'");
    check('Tukar nama kelas', ok($v1->post('vendor/classes/update', ['cooperative_id' => 1, 'id' => $classId, 'class_name' => 'Elit Baharu'])));
    check('Tukar nama kepada nama sedia ada ditolak', errorOf($v1->post('vendor/classes/update', ['cooperative_id' => 1, 'id' => $classId, 'class_name' => 'DINAMIK']), 'class_name') !== null);
    check('Padam kelas', ok($v1->post('vendor/classes/delete', ['cooperative_id' => 1, 'id' => $classId])));

    echo "5. Kategori\n";
    check('Nama kategori pendua ditolak', errorOf($v1->post('vendor/categories/save', ['cooperative_id' => 1, 'name' => 'alat tulis', 'is_active' => true]), 'name') !== null);
    $res = $v1->post('vendor/categories/save', ['cooperative_id' => 1, 'name' => 'Kategori Ujian', 'sort_order' => 9, 'is_active' => true]);
    $categoryId = $res['data']['id'] ?? 0;
    check('Tambah kategori', ok($res) && $res['data']['product_count'] === 0);
    check('Padam kategori berproduk ditolak', !ok($v1->post('vendor/categories/delete', ['cooperative_id' => 1, 'id' => 1])));

    echo "6. Produk, varian, darjah, terjemahan, gambar\n";
    $product = [
        'cooperative_id' => 1, 'category_id' => $categoryId, 'name' => 'Baju Sukan B4', 'description' => 'Ujian', 'sku' => 'BS-B4',
        'barcode' => '9555000000123', 'price' => 3000, 'cost_price' => 2000, 'unit' => 'helai', 'max_per_order' => 2, 'is_active' => true,
        'has_variants' => true, 'grade_levels' => ['D1', 'D4'], 'image_url' => PNG_DATA_URL, 'vendor_id' => 2,
        'variants' => [
            ['label' => 'Saiz S', 'sku' => 'BS-B4-S', 'barcode' => '9555000999001', 'price_delta' => 0, 'is_active' => true],
            ['label' => 'Saiz M', 'sku' => 'BS-B4-M', 'barcode' => '9555000999002', 'price_delta' => 200, 'is_active' => true],
        ],
        'translations' => ['zh' => ['name' => '运动服 B4', 'description' => ''], 'ta' => ['name' => '', 'description' => '']],
    ];
    check('Barcode sama dengan produk lain dalam koperasi ditolak', errorOf($v1->post('vendor/products/save', array_merge($product, ['barcode' => '9555000000017'])), 'barcode') !== null);
    check('Barcode varian = barcode varian produk lain ditolak', errorOf($v1->post('vendor/products/save', array_merge($product, ['variants' => [
        ['label' => 'X', 'sku' => '', 'barcode' => '9555000000055', 'price_delta' => 0]]])), 'variants') !== null);
    check('SKU varian sama dengan SKU produk ditolak', errorOf($v1->post('vendor/products/save', array_merge($product, ['variants' => [
        ['label' => 'X', 'sku' => 'bs-b4', 'barcode' => '', 'price_delta' => 0]]])), 'variants') !== null);
    check('Darjah T1 untuk SK ditolak', errorOf($v1->post('vendor/products/save', array_merge($product, ['grade_levels' => ['T1']])), 'grade_levels') !== null);
    check('Kategori koperasi lain ditolak', errorOf($v1->post('vendor/products/save', array_merge($product, ['category_id' => 4])), 'category_id') !== null);
    check('Harga negatif ditolak', errorOf($v1->post('vendor/products/save', array_merge($product, ['price' => -1])), 'price') !== null);
    check('Varian dihidupkan tanpa varian ditolak', errorOf($v1->post('vendor/products/save', array_merge($product, ['variants' => []])), 'variants') !== null);
    check('Gambar palsu ditolak', errorOf($v1->post('vendor/products/save', array_merge($product, ['image_url' => FAKE_DATA_URL])), 'image_url') !== null);
    $res = $v1->post('vendor/products/save', $product);
    $productId = $res['data']['id'] ?? 0;
    $row = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
    check('Produk baharu (barcode sama dengan koperasi V02 dibenarkan — UNIQUE per koperasi)', ok($res) && $res['data']['variant_count'] === 2);
    check('vendor_id produk dari sesi (input vendor_id=2 diabaikan)', (int) $row['vendor_id'] === 1 && (int) $row['cooperative_id'] === 1);
    check('Gambar produk disimpan di uploads/1/1/products/', strpos((string) $row['image_path'], '1/1/products/') === 0 && is_file(APP_ROOT . '/uploads/' . $row['image_path']));
    $pensel = $v1->get('vendor/products/get', ['cooperative_id' => 1, 'id' => 2])['data'];
    $res = $v1->post('vendor/products/save', array_merge($pensel, ['cooperative_id' => 1, 'image_url' => PNG_DATA_URL]));
    check('Produk lain dengan gambar sama berkongsi fail hash', ok($res) && Database::fetchValue("SELECT image_path FROM products WHERE id = 2") === $row['image_path']);
    $res = $v1->post('vendor/products/save', array_merge($pensel, ['cooperative_id' => 1, 'image_url' => null]));
    clearstatcache();
    check('Buang gambar produk 2 — fail kekal kerana masih dipakai produk baharu', ok($res) && is_file(APP_ROOT . '/uploads/' . $row['image_path']));
    $levels = Database::fetchAll("SELECT variant_id, qty_on_hand, min_stock FROM stock_levels WHERE product_id = ?", [$productId]);
    check('Varian baharu → 2 baris stok 0, min_stock lalai koperasi (5)', count($levels) === 2 && array_sum(array_column($levels, 'qty_on_hand')) === 0
        && (int) $levels[0]['min_stock'] === 5);
    $detail = $v1->get('vendor/products/get', ['cooperative_id' => 1, 'id' => $productId]);
    check('Terjemahan Cina utf8mb4 disimpan; bahasa kosong tidak disimpan', ok($detail) && ($detail['data']['translations']['zh']['name'] ?? '') === '运动服 B4'
        && !isset($detail['data']['translations']['ta']));
    check('Darjah sasaran D1, D4', $detail['data']['grade_levels'] === ['D1', 'D4']);
    $variants = $detail['data']['variants'];
    $update = array_merge($product, ['id' => $productId, 'image_url' => $detail['data']['image_url'], 'variants' => [
        array_merge($variants[0], ['barcode' => $variants[1]['barcode']]), array_merge($variants[1], ['barcode' => $variants[0]['barcode']])]]);
    check('Tukar barcode antara dua varian produk sama dibenarkan', ok($v1->post('vendor/products/save', $update)));
    $update['variants'] = [$variants[0]];
    check('Buang varian tanpa sejarah → baris stok dibuang', ok($v1->post('vendor/products/save', $update))
        && (int) Database::fetchValue("SELECT COUNT(*) FROM stock_levels WHERE product_id = ?", [$productId]) === 1);
    $seed = $v1->get('vendor/products/get', ['cooperative_id' => 1, 'id' => 4])['data'];
    $seedInput = array_merge($seed, ['cooperative_id' => 1, 'variants' => array_slice($seed['variants'], 0, 2)]);
    check('Buang varian yang ada stok/sejarah (Saiz L) ditolak', errorOf($v1->post('vendor/products/save', $seedInput), 'variants') !== null);
    check('Varian produk lain dalam input ditolak', errorOf($v1->post('vendor/products/save', array_merge($seedInput, ['variants' => [['id' => 4, 'label' => 'Curi']]])), 'variants') !== null);
    check('Nyahaktif produk', ok($r = $v1->post('vendor/products/set-active', ['cooperative_id' => 1, 'id' => $productId, 'is_active' => false])) && $r['data']['is_active'] === false);
    $list = $v1->get('vendor/products/list', ['cooperative_id' => 1, 'grade' => 'all']);
    check('Tapisan darjah "all" = produk tanpa darjah', ok($list) && !in_array(1, array_column($list['data']['items'], 'id'), true) && in_array(2, array_column($list['data']['items'], 'id'), true));
    $lowIds = array_column($v1->get('vendor/products/list', ['cooperative_id' => 1, 'low_only' => 1])['data']['items'], 'id');
    check('Tapisan stok rendah: produk 4 (Saiz L 4 ≤ 5) ada, produk 1 (500) tiada', in_array(4, $lowIds, true) && !in_array(1, $lowIds, true));
    check('Carian % tidak memadankan semua (LIKE di-escape)', $v1->get('vendor/products/list', ['cooperative_id' => 1, 'q' => '%'])['data']['total'] === 0);
    Database::execute("UPDATE plans SET max_products = 6 WHERE id = 1");
    $res = $v2->post('vendor/products/save', ['cooperative_id' => 2, 'category_id' => 4, 'name' => 'Had', 'price' => 100, 'unit' => 'buah', 'is_active' => true]);
    check('Had pelan max_products (V02 6/6) ditolak', !ok($res) && strpos($res['error'], '6') !== false);

    echo "7. Inventori & sejarah stok\n";
    check('Stok masuk kuantiti 0 ditolak', errorOf($v1->post('vendor/inventory/stock-in', ['cooperative_id' => 1, 'product_id' => 1, 'variant_id' => 0, 'qty' => 0]), 'qty') !== null);
    $res = $v1->post('vendor/inventory/stock-in', ['cooperative_id' => 1, 'product_id' => 1, 'variant_id' => 0, 'qty' => 25, 'note' => 'Hadiah PIBG']);
    check('Stok masuk 25 → baki 525', ok($res) && $res['data']['qty_on_hand'] === 525);
    $move = Database::fetchOne("SELECT * FROM stock_movements WHERE cooperative_id = 1 AND product_id = 1 ORDER BY id DESC LIMIT 1");
    check('Pergerakan restock: balance_after 525, pengguna sesi, nota', $move['type'] === 'restock' && (int) $move['balance_after'] === 525
        && (int) $move['vendor_user_id'] === 1 && $move['note'] === 'Hadiah PIBG');
    Database::execute("UPDATE stock_levels SET qty_reserved = 10 WHERE cooperative_id = 1 AND product_id = 3 AND variant_id = 0");
    $res = $v1->post('vendor/inventory/adjust', ['cooperative_id' => 1, 'product_id' => 3, 'variant_id' => 0, 'new_qty' => 5, 'reason' => 'damaged']);
    check('Pelarasan bawah kuantiti ditahan (10) ditolak', errorOf($res, 'new_qty') !== null && stockQty(1, 3, 0) === 300);
    check('Sebab tidak sah ditolak', errorOf($v1->post('vendor/inventory/adjust', ['cooperative_id' => 1, 'product_id' => 3, 'variant_id' => 0, 'new_qty' => 290, 'reason' => 'x']), 'reason') !== null);
    $res = $v1->post('vendor/inventory/adjust', ['cooperative_id' => 1, 'product_id' => 3, 'variant_id' => 0, 'new_qty' => 290, 'reason' => 'damaged', 'note' => 'Basah', 'min_stock' => 40]);
    check('Pelarasan 300 → 290 + stok minimum 40', ok($res) && $res['data']['qty_on_hand'] === 290 && $res['data']['min_stock'] === 40 && $res['data']['qty_available'] === 280);
    $moves = $v1->get('vendor/inventory/movements', ['cooperative_id' => 1, 'type' => 'adjust']);
    check('Sejarah tapis jenis adjust: -10 dengan sebab "Rosak — Basah"', ok($moves) && $moves['data']['items'][0]['qty_change'] === -10
        && $moves['data']['items'][0]['note'] === 'Rosak — Basah' && $moves['data']['items'][0]['user_name'] === 'Ahmad bin Ismail');
    $inv = $v1->get('vendor/inventory/list', ['cooperative_id' => 1, 'low_only' => 1]);
    check('inventory/list low_only + low_count', ok($inv) && $inv['data']['low_count'] === count($inv['data']['items']) && $inv['data']['low_count'] >= 1);
    $outsideRejected = false;
    try {
        StockLedger::move(1, 1, 1, 0, 'restock', 1, 1);
    } catch (RuntimeException $exception) {
        $outsideRejected = true;
    }
    check('StockLedger::move di luar transaksi DB ditolak', $outsideRejected && stockQty(1, 1, 0) === 525);

    echo "8. Pembekal & pesanan belian\n";
    check('Pembekal tanpa telefon ditolak', errorOf($v1->post('vendor/suppliers/save', ['name' => 'X', 'phone' => '']), 'phone') !== null);
    $res = $v1->post('vendor/suppliers/save', ['name' => 'Pembekal Ujian B4', 'phone' => '03-2222222', 'email' => 'JUAL@UJIAN.MY', 'is_active' => true]);
    $supplierId = $res['data']['id'] ?? 0;
    check('Tambah pembekal (e-mel huruf kecil)', ok($res) && $res['data']['email'] === 'jual@ujian.my');
    $po = ['cooperative_id' => 1, 'supplier_id' => $supplierId, 'note' => 'Ujian', 'total' => 1, 'items' => [
        ['product_id' => 1, 'variant_id' => 0, 'qty_ordered' => 100, 'unit_cost' => 110],
        ['product_id' => 4, 'variant_id' => 3, 'qty_ordered' => 10, 'unit_cost' => 1600],
    ]];
    check('PO pembekal V02 ditolak', errorOf($v1->post('vendor/purchase-orders/save', array_merge($po, ['supplier_id' => 2])), 'supplier_id') !== null);
    check('PO produk koperasi lain ditolak', errorOf($v1->post('vendor/purchase-orders/save', array_merge($po, ['items' => [['product_id' => 6, 'variant_id' => 0, 'qty_ordered' => 1, 'unit_cost' => 1]]])), 'items') !== null);
    check('PO produk bervarian tanpa varian ditolak', errorOf($v1->post('vendor/purchase-orders/save', array_merge($po, ['items' => [['product_id' => 4, 'variant_id' => 0, 'qty_ordered' => 1, 'unit_cost' => 1]]])), 'items') !== null);
    $prefix = 'PO-' . date('Y') . '-';
    $lastNo = (int) Database::fetchValue("SELECT MAX(CAST(SUBSTRING(po_no, 9) AS UNSIGNED)) FROM purchase_orders WHERE cooperative_id = 1 AND po_no LIKE ?", [$prefix . '%']);
    $nextNo = function ($offset) use ($prefix, $lastNo) {
        return $prefix . str_pad((string) ($lastNo + $offset), 4, '0', STR_PAD_LEFT);
    };
    $res = $v1->post('vendor/purchase-orders/save', $po);
    $poId = $res['data']['id'] ?? 0;
    check('PO draf: no. ' . $nextNo(1) . ' (bersiri dalam koperasi), jumlah dikira pelayan 27000', ok($res) && $res['data']['po_no'] === $nextNo(1)
        && $res['data']['total'] === 27000 && $res['data']['status'] === 'draft');
    $res = $v1->post('vendor/purchase-orders/save', array_merge($po, ['id' => $poId, 'submit' => true]));
    check('Hantar PO → ordered', ok($res) && $res['data']['status'] === 'ordered' && $res['data']['ordered_at'] !== null);
    check('PO dipesan tidak boleh disunting', !ok($v1->post('vendor/purchase-orders/save', array_merge($po, ['id' => $poId]))));
    check('V02 purchase-orders/get PO V01 → 404', $v2->getStatus('vendor/purchase-orders/get.php?cooperative_id=2&id=' . $poId) === 404);
    $items = $res['data']['items'];
    $res = $v1->post('vendor/purchase-orders/receive', ['cooperative_id' => 1, 'id' => $poId, 'lines' => [['item_id' => $items[0]['id'], 'qty' => 101]]]);
    check('Terima melebihi baki ditolak, stok tidak berubah', !ok($res) && stockQty(1, 1, 0) === 525);
    check('V02 terima PO V01 → 404', $v2->postStatus('vendor/purchase-orders/receive', ['cooperative_id' => 2, 'id' => $poId, 'lines' => [['item_id' => $items[0]['id'], 'qty' => 1]]]) === 404);
    $res = $v1->post('vendor/purchase-orders/receive', ['cooperative_id' => 1, 'id' => $poId, 'lines' => [['item_id' => $items[0]['id'], 'qty' => 60]]]);
    check('Terima separa → partial, stok +60 (po_receive)', ok($res) && $res['data']['status'] === 'partial' && stockQty(1, 1, 0) === 585
        && Database::fetchValue("SELECT type FROM stock_movements WHERE ref_type = 'purchase_order' AND ref_id = ? ORDER BY id DESC LIMIT 1", [$poId]) === 'po_receive');
    check('PO separa tidak boleh dibatalkan', !ok($v1->post('vendor/purchase-orders/cancel', ['cooperative_id' => 1, 'id' => $poId])));
    $res = $v1->post('vendor/purchase-orders/receive', ['cooperative_id' => 1, 'id' => $poId, 'lines' => [
        ['item_id' => $items[0]['id'], 'qty' => 40], ['item_id' => $items[1]['id'], 'qty' => 10]]]);
    check('Terima baki → received, produk 1 → 625, varian Saiz L 4 → 14', ok($res) && $res['data']['status'] === 'received' && $res['data']['received_at'] !== null
        && stockQty(1, 1, 0) === 625 && stockQty(1, 4, 3) === 14);
    check('Pembekal: open_po_count 0 selepas diterima', ListSuppliersJob::find(1, $supplierId)['open_po_count'] === 0);
    $res = $v1->post('vendor/purchase-orders/save', array_merge($po, ['submit' => false]));
    check('PO kedua → ' . $nextNo(2), ($res['data']['po_no'] ?? '') === $nextNo(2));
    check('Batal PO draf', ok($c = $v1->post('vendor/purchase-orders/cancel', ['cooperative_id' => 1, 'id' => $res['data']['id']])) && $c['data']['status'] === 'cancelled');
    check('purchase-orders/list tapis status received', array_column($v1->get('vendor/purchase-orders/list', ['cooperative_id' => 1, 'status' => 'received'])['data']['items'], 'id') === [$poId]);

    echo "9. Stock take\n";
    $res = $v1->post('vendor/stock-takes/start', ['cooperative_id' => 1]);
    $takeId = $res['data']['id'] ?? 0;
    check('Mula stock take: salin produk aktif sahaja', ok($res) && $res['data']['status'] === 'open'
        && $res['data']['item_count'] === (int) Database::fetchValue("SELECT COUNT(*) FROM stock_levels s JOIN products p ON p.id = s.product_id WHERE s.cooperative_id = 1 AND p.is_active = 1"));
    check('Stock take kedua ditolak (satu terbuka)', !ok($v1->post('vendor/stock-takes/start', ['cooperative_id' => 1])));
    $take = $v1->get('vendor/stock-takes/get', ['cooperative_id' => 1, 'id' => $takeId]);
    $byKey = [];
    foreach ($take['data']['items'] as $line) {
        $byKey[$line['product_id'] . ':' . $line['variant_id']] = $line;
    }
    check('V02 stock-takes/get milik V01 → 404', $v2->getStatus('vendor/stock-takes/get.php?cooperative_id=2&id=' . $takeId) === 404);
    $res = $v1->post('vendor/stock-takes/save-counts', ['cooperative_id' => 1, 'id' => $takeId, 'counts' => [
        ['item_id' => $byKey['1:0']['id'], 'qty_counted' => 620], ['item_id' => $byKey['2:0']['id'], 'qty_counted' => 120], ['item_id' => $byKey['5:0']['id'], 'qty_counted' => null]]]);
    check('Simpan kiraan: 2 dikira, perbezaan -5', ok($res) && $res['data']['counted_count'] === 2 && $res['data']['variance_total'] === -5);
    $res = $v1->post('vendor/stock-takes/complete', ['cooperative_id' => 1, 'id' => $takeId]);
    check('Selesai: baki 625 → 620 (pergerakan stocktake), item belum dikira tidak diubah', ok($res) && $res['data']['status'] === 'completed'
        && stockQty(1, 1, 0) === 620 && stockQty(1, 5, 0) === 200
        && Database::fetchValue("SELECT qty_change FROM stock_movements WHERE ref_type = 'stock_take' AND ref_id = ?", [$takeId]) == -5);
    check('Simpan kiraan selepas selesai ditolak', !ok($v1->post('vendor/stock-takes/save-counts', ['cooperative_id' => 1, 'id' => $takeId, 'counts' => []])));

    echo "10. Papan pemuka, modul & log aktiviti\n";
    $dash = $v1->get('vendor/dashboard/summary', ['cooperative_id' => 1]);
    check('Papan pemuka: 7 hari jualan, stok rendah aktif, 2 pengumuman terbit', ok($dash) && count($dash['data']['sales_7days']) === 7
        && $dash['data']['low_stock_count'] === count(array_filter($dash['data']['low_stock'], function ($row) { return $row['is_active']; }))
        && count($dash['data']['announcements']) === 2);
    $dash2 = $v2->get('vendor/dashboard/summary', ['cooperative_id' => 2]);
    check('Pengumuman draf sasaran V02 belum dipapar; stok rendah Chung Hwa (produk 8)', ok($dash2) && count($dash2['data']['announcements']) === 2
        && $dash2['data']['low_stock'][0]['product_id'] === 8);
    check('modules/active koperasi 3 (V02) = clearance', $v2->get('vendor/modules/active', ['cooperative_id' => 3])['data']['modules'] === ['clearance']);
    check('V01 modules/active koperasi 3 → 404', $v1->getStatus('vendor/modules/active.php?cooperative_id=3') === 404);
    $actions = array_column(Database::fetchAll("SELECT DISTINCT action FROM activity_logs WHERE vendor_id = 1"), 'action');
    $expected = ['cooperative_save', 'product_save', 'stock_in', 'stock_adjust', 'supplier_save', 'po_save', 'po_receive', 'po_cancel', 'stocktake_complete'];
    check('Log aktiviti 9 jenis tindakan B4 direkod', count(array_intersect($expected, $actions)) === 9);
    check('Tiada log aktiviti V01 ditulis di bawah V02', (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE vendor_id = 2 AND action IN ('stock_in', 'po_receive')") === 0);

    echo "11. Kebocoran ibu bapa A ↔ B\n";
    list($parentB) = storeLogin($base, 'rajesh@contoh.my');
    check('Ibu bapa B → API vendor 401 (sesi berasingan)', !empty($parentB->get('vendor/inventory/list', ['cooperative_id' => 3])['auth_required']));
    check('Ibu bapa A → stok masuk 401, tiada pergerakan', !empty($parentA->post('vendor/inventory/stock-in', ['cooperative_id' => 1, 'product_id' => 1, 'qty' => 1])['auth_required'])
        && stockQty(1, 1, 0) === 620);
} finally {
    proc_terminate($server);
    // Pulihkan jadual B4 & buang fail muat naik ujian
    Database::execute('SET FOREIGN_KEY_CHECKS = 0');
    foreach (array_reverse($tables) as $table) {
        Database::execute("DELETE FROM $table");
    }
    foreach ($tables as $table) {
        foreach ($snapshot[$table] as $row) {
            $columns = array_keys($row);
            Database::execute(
                "INSERT INTO $table (" . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
                array_values($row)
            );
        }
    }
    Database::execute('SET FOREIGN_KEY_CHECKS = 1');
    foreach (array_diff(uploadFiles(), $filesBefore) as $file) {
        unlink($file);
    }
    removeEmptyDirs(APP_ROOT . '/uploads');
    clearTestLocks();
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
