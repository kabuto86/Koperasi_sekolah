<?php
// Semakan B6 — store ibu bapa: sekolah, kelas, anak, katalog, produk, kit, troli, berita & jualan murah, template
// + ujian kebocoran ibu bapa A ↔ B dan VENDOR01 ↔ VENDOR02 (koperasi slug). CLI sahaja:
//   php tools/verify-b6.php
// Perlu: DB koperasi_saas (schema + seed), config/database.php, config/secret.key, sambungan PHP curl.
// Skrip memulakan pelayan PHP sendiri (php -S 127.0.0.1:8099). Jadual yang diubah dipulihkan di hujung.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

function cartLines($customerId, $cooperativeId)
{
    return Database::fetchAll(
        "SELECT ci.* FROM cart_items ci JOIN carts ca ON ca.id = ci.cart_id WHERE ca.customer_id = ? AND ca.cooperative_id = ? ORDER BY ci.id",
        [$customerId, $cooperativeId]
    );
}

// Semua baris CartQuote (rata)
function quoteItems($quote)
{
    $items = [];
    foreach (ok($quote) ? $quote['data']['groups'] : [] as $group) {
        foreach ($group['items'] as $item) {
            $items[] = $item;
        }
    }
    return $items;
}

$port = 8099;
$base = 'http://127.0.0.1:' . $port;
$log = sys_get_temp_dir() . '/kop-verify-b6.log';
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes);
usleep(700000);

$tables = ['children', 'carts', 'cart_items', 'products', 'categories', 'stock_levels', 'school_kits', 'cooperatives', 'vendors',
    'news_posts', 'cooperative_modules'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
// Data ujian bermula dari keadaan seed: troli kosong, Adam (D1) tahun 2027 → kit D1 2027
Database::execute('DELETE FROM cart_items');
Database::execute('DELETE FROM carts');
Database::execute("UPDATE children SET school_year = 2027 WHERE id = 2");
$year = (int) date('Y');
$kit1Items = array_column(Database::fetchAll("SELECT id, product_id FROM school_kit_items WHERE school_kit_id = 1 ORDER BY sort_order"), 'id', 'product_id');
$kit2Item = (int) Database::fetchValue("SELECT id FROM school_kit_items WHERE school_kit_id = 2 ORDER BY sort_order LIMIT 1");

try {
    $guest = new Browser($base);
    list($parentA, $loginA) = storeLogin($base, 'siti@contoh.my');   // anak: Aisyah (1), Adam (2) di SK Taman Melati, Faris (3) di Chung Hwa
    list($parentB, $loginB) = storeLogin($base, 'rajesh@contoh.my'); // anak: Arjun (4) di SMK Seri Indah
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $melati = ['slug' => 'sk-taman-melati'];
    $indah = ['slug' => 'smk-seri-indah'];

    echo "1. Akses & kaedah (PELAN 10.2)\n";
    check('Log masuk ibu bapa A & B', ok($loginA) && ok($loginB));
    check('Awam → schools/list, school, catalog, product OK', ok($guest->get('store/schools/list')) && ok($guest->get('store/school', $melati))
        && ok($guest->get('store/catalog', $melati)) && ok($guest->get('store/product', $melati + ['id' => 1])));
    $needLogin = 0;
    foreach (['store/kit' => $melati, 'store/cart/get' => $melati, 'store/children/list' => []] as $route => $params) {
        $needLogin += !empty($guest->get($route, $params)['auth_required']) ? 1 : 0;
    }
    check('Tanpa sesi → kit, cart/get, children/list auth_required', $needLogin === 3);
    check('Tanpa sesi → cart/add auth_required', !empty($guest->post('store/cart/add', $melati + ['product_id' => 1, 'qty' => 1, 'child_id' => 2])['auth_required']));
    check('Sesi vendor (bukan ibu bapa) → cart/get auth_required', !empty($v1->get('store/cart/get', $melati)['auth_required']));
    check('POST tanpa X-CSRF-Token → 419', $parentA->postStatus('store/cart/add', $melati + ['product_id' => 1, 'qty' => 1, 'child_id' => 2], false) === 419);
    check('GET ke endpoint POST → 405', $parentA->getStatus('store/cart/add') === 405 && $parentA->getStatus('store/children/save') === 405);
    check('Slug sah → 200; slug tidak wujud / tidak sah → 404', $guest->status('store/school', $melati) === 200 && $guest->status('store/school', ['slug' => 'tiada-sekolah']) === 404
        && $guest->status('store/catalog', ['slug' => "x' OR 1=1"]) === 404);

    echo "2. Sekolah & kelas (PELAN 8.5)\n";
    $schools = $guest->get('store/schools/list');
    check('3 sekolah aktif, tersusun ikut nama', ok($schools) && array_column($schools['data']['items'], 'slug') === ['sjkc-chung-hwa', 'sk-taman-melati', 'smk-seri-indah']);
    $search = $guest->get('store/schools/list', ['q' => 'melati']);
    check('Carian "melati" → SK Taman Melati sahaja; "%" tidak padan semua', ok($search) && $search['data']['total'] === 1
        && $guest->get('store/schools/list', ['q' => '%'])['data']['total'] === 0);
    check('Tapis negeri Johor → 2', $guest->get('store/schools/list', ['state' => 'Johor'])['data']['total'] === 2);
    $school = $guest->get('store/school', $melati);
    // Sejak B8: online hanya bila gateway koperasi aktif (seed tiada gateway → tunai sahaja walaupun accept_online)
    check('Butiran sekolah: darjah PRA–D6, modul store aktif sahaja, tunai (online perlu gateway aktif — B8)', ok($school)
        && $school['data']['grades'][0] === 'PRA'
        && $school['data']['modules'] === ['news', 'calendar', 'school_opening'] && $school['data']['payment_methods'] === ['cash']
        && !isset($school['data']['vendor_id']) && !isset($school['data']['id']));
    check('Waktu operasi 7 hari', ok($school) && count($school['data']['opening_hours']) === 7 && $school['data']['opening_hours']['sat']['open'] === false);
    Database::execute("UPDATE cooperatives SET status = 'suspended' WHERE id = 3");
    check('Koperasi digantung → hilang dari senarai & 404', $guest->get('store/schools/list')['data']['total'] === 2
        && $guest->status('store/school', ['slug' => 'smk-seri-indah']) === 404);
    Database::execute("UPDATE cooperatives SET status = 'active' WHERE id = 3");
    Database::execute("UPDATE vendors SET status = 'suspended' WHERE id = 2");
    check('Vendor digantung → semua koperasinya ditutup', $guest->get('store/schools/list')['data']['total'] === 1
        && $guest->status('store/catalog', ['slug' => 'sjkc-chung-hwa']) === 404);
    Database::execute("UPDATE vendors SET status = 'active' WHERE id = 2");
    $classes = $guest->get('store/classes', $melati + ['grade' => 'D1']);
    check('Kelas D1 SK Taman Melati: Bestari, Cemerlang (koperasi lain tidak bercampur)', ok($classes)
        && array_column($classes['data'], 'class_name') === ['Bestari', 'Cemerlang']);
    check('Kelas semua darjah SMK Seri Indah: 3', count($guest->get('store/classes', $indah)['data']) === 3);

    echo "3. Katalog & produk — harga dari pelayan (PELAN 18.12)\n";
    $catalog = $guest->get('store/catalog', $melati);
    check('Katalog SK Taman Melati: 5 produk + 3 kategori, tiada produk vendor lain', ok($catalog) && $catalog['data']['total'] === 5
        && count($catalog['data']['categories']) === 3 && !array_intersect(array_column($catalog['data']['items'], 'id'), [6, 7, 8, 9, 10, 11]));
    $byId = ok($catalog) ? array_column($catalog['data']['items'], null, 'id') : [];
    check('Baju (varian): harga dari RM25, baki semua saiz 104', isset($byId[4]) && $byId[4]['price'] === 2500 && $byId[4]['price_from'] === true
        && $byId[4]['available'] === 104 && $byId[4]['has_variants'] === true);
    check('Tapis kategori Pakaian → 1', $guest->get('store/catalog', $melati + ['category_id' => 3])['data']['total'] === 1);
    $t1 = $guest->get('store/catalog', $indah + ['grade' => 'T1']);
    check('Tapis T1 di SMK: kalkulator (T2 sahaja) disembunyi, produk tanpa darjah kekal', ok($t1) && $t1['data']['total'] === 2
        && !in_array(11, array_column($t1['data']['items'], 'id'), true));
    check('Carian SKU "BJ-PL" → 1; carian "%" → 0', $guest->get('store/catalog', $melati + ['q' => 'BJ-PL'])['data']['total'] === 1
        && $guest->get('store/catalog', $melati + ['q' => '%'])['data']['total'] === 0);
    $zh = $guest->get('store/catalog', $melati + ['lang' => 'zh', 'q' => '横线']);
    check('Nama terjemahan (zh) dipapar & boleh dicari', ok($zh) && $zh['data']['total'] === 1 && $zh['data']['items'][0]['name'] === '横线练习簿（80页）');
    $guest->get('store/catalog', $melati + ['lang' => 'ms']);
    $clear = $guest->get('store/catalog', $indah)['data']['items'][0];
    check('Jualan murah (modul aktif): RM2.50, harga asal RM3.20, on_sale', $clear['id'] === 9 && $clear['price'] === 250
        && $clear['regular_price'] === 320 && $clear['on_sale'] === true);
    Database::execute("UPDATE products SET is_active = 0 WHERE id = 3");
    Database::execute("UPDATE categories SET is_active = 0 WHERE id = 3");
    check('Produk tidak aktif & kategori tidak aktif disembunyi', $guest->get('store/catalog', $melati)['data']['total'] === 3
        && $guest->status('store/product', ['slug' => 'sk-taman-melati', 'id' => 3]) === 404 && $guest->status('store/product', ['slug' => 'sk-taman-melati', 'id' => 4]) === 404);
    Database::execute("UPDATE products SET is_active = 1 WHERE id = 3");
    Database::execute("UPDATE categories SET is_active = 1 WHERE id = 3");
    $product = $guest->get('store/product', $melati + ['id' => 4]);
    check('Produk: 3 varian, Saiz L RM28 baki 4, had 3', ok($product) && count($product['data']['variants']) === 3
        && $product['data']['variants'][2]['price'] === 2800 && $product['data']['variants'][2]['available'] === 4 && $product['data']['max_per_order'] === 3);
    check('Produk koperasi lain melalui slug ini → 404', $guest->status('store/product', ['slug' => 'sk-taman-melati', 'id' => 6]) === 404
        && $guest->status('store/product', ['slug' => 'smk-seri-indah', 'id' => 1]) === 404);

    echo "4. Anak — milik ibu bapa sesi sahaja (PELAN 5.4)\n";
    $listA = $parentA->get('store/children/list');
    $childrenA = ok($listA) ? array_column($listA['data'], null, 'id') : [];
    check('Ibu bapa A: 3 anak di 2 sekolah; tiada anak B', array_keys($childrenA) === [1, 2, 3]);
    check('Cadangan naik darjah: Aisyah D4 ' . ($year) . ' → D5; Adam (2027) tiada', isset($childrenA[1], $childrenA[2])
        && (date('n') < 10 || $childrenA[1]['promote_to'] === ['grade_level_code' => 'D5', 'school_year' => $year + 1]) && $childrenA[2]['promote_to'] === null);
    check('Ibu bapa B: 1 anak (Arjun)', array_column($parentB->get('store/children/list')['data'], 'id') === [4]);
    $bad = $parentA->post('store/children/save', ['full_name' => '', 'school_slug' => 'tiada', 'grade_level_code' => 'D1', 'class_name' => '', 'school_year' => $year + 5]);
    check('Simpan anak: medan wajib, sekolah tiada, tahun luar julat ditolak', !ok($bad) && isset($bad['errors']['full_name'], $bad['errors']['school_slug'],
        $bad['errors']['class_name'], $bad['errors']['school_year']));
    $badGrade = $parentA->post('store/children/save', ['full_name' => 'Ali', 'school_slug' => 'sk-taman-melati', 'grade_level_code' => 'T1', 'class_name' => 'X', 'school_year' => $year]);
    check('Tingkatan T1 untuk SK ditolak', !ok($badGrade) && isset($badGrade['errors']['grade_level_code']));
    $new = $parentA->post('store/children/save', ['full_name' => '  Hana binti Faizal ', 'school_slug' => 'sk-taman-melati', 'grade_level_code' => 'D4',
        'class_name' => 'cemerlang', 'school_year' => $year, 'customer_id' => 2, 'vendor_id' => 2, 'cooperative_id' => 3]);
    $hanaId = ok($new) ? $new['data']['id'] : 0;
    $hana = Database::fetchOne("SELECT * FROM children WHERE id = ?", [$hanaId]);
    check('Anak baharu: customer_id dari sesi, koperasi & vendor dari slug (input diabaikan)', $hana && (int) $hana['customer_id'] === 1
        && (int) $hana['cooperative_id'] === 1 && (int) $hana['vendor_id'] === 1 && $hana['full_name'] === 'Hana binti Faizal');
    check('Kelas dipadankan tanpa beza huruf: "cemerlang" → Cemerlang (school_class_id 4)', $hana && $hana['class_name'] === 'Cemerlang'
        && (int) $hana['school_class_id'] === 4);
    $other = $parentA->post('store/children/save', ['full_name' => '王小明', 'school_slug' => 'sjkc-chung-hwa', 'grade_level_code' => 'D1', 'class_name' => 'Kelas Baharu', 'school_year' => $year + 1]);
    check('Nama Cina utf8mb4 + kelas baharu (tiada padanan → null)', ok($other) && $other['data']['full_name'] === '王小明'
        && Database::fetchValue("SELECT school_class_id FROM children WHERE id = ?", [$other['data']['id']]) === null);
    $arjunBefore = Database::fetchOne("SELECT * FROM children WHERE id = 4");
    $hijack = $parentA->post('store/children/save', ['id' => 4, 'full_name' => 'Dirampas', 'school_slug' => 'sk-taman-melati', 'grade_level_code' => 'D1', 'class_name' => 'Bestari', 'school_year' => $year]);
    check('A sunting anak B → 404, data tidak berubah', !ok($hijack) && Database::fetchOne("SELECT * FROM children WHERE id = 4") == $arjunBefore);
    check('A buang / naik darjah anak B → 404', $parentA->postStatus('store/children/remove', ['id' => 4]) === 404
        && $parentA->postStatus('store/children/promote', ['id' => 4]) === 404 && (int) Database::fetchValue("SELECT is_active FROM children WHERE id = 4") === 1);
    if (date('n') >= 10) {
        $promoted = $parentA->post('store/children/promote', ['id' => 1]);
        check('Naik darjah Aisyah → D5 ' . ($year + 1) . ' (kelas Bestari dikekalkan, tiada kelas D5 → null)', ok($promoted)
            && $promoted['data']['grade_level_code'] === 'D5' && $promoted['data']['school_year'] === $year + 1 && $promoted['data']['class_name'] === 'Bestari');
        check('Naik darjah sekali lagi → ditolak', !ok($parentA->post('store/children/promote', ['id' => 1])));
    }

    echo "5. Kit ikut darjah & tahun anak — draf tidak kelihatan (PELAN 18.9)\n";
    $kit = $parentA->get('store/kit', $melati + ['child_id' => 2]);
    $kitItems = ok($kit) && $kit['data']['kit'] ? array_column($kit['data']['kit']['items'], null, 'product_id') : [];
    check('Adam D1 2027 → Kit Darjah 1 (4 item), anak di sekolah ini sahaja', ok($kit) && $kit['data']['kit']['id'] === 1 && count($kitItems) === 4
        && count($kit['data']['children']) === 3 && !in_array(3, array_column($kit['data']['children'], 'id'), true));
    check('Item baju: pilih saiz (3 varian, L RM28), harga semasa', isset($kitItems[4]) && $kitItems[4]['choose_variant'] === true
        && count($kitItems[4]['variants']) === 3 && $kitItems[4]['variants'][2]['price'] === 2800 && $kitItems[4]['available'] === null
        && $kitItems[1]['unit_price'] === 180 && $kitItems[1]['qty'] === 6);
    check('Tanpa child_id → senarai anak sahaja', ok($parentA->get('store/kit', $melati)) && $parentA->get('store/kit', $melati)['data']['kit'] === null);
    check('Anak B / anak sekolah lain → 404', $parentA->status('store/kit', ['slug' => 'sk-taman-melati', 'child_id' => 4]) === 404
        && $parentA->status('store/kit', ['slug' => 'sk-taman-melati', 'child_id' => 3]) === 404);
    Database::execute("UPDATE children SET school_year = 2027 WHERE id = 4");
    $draft = $parentB->get('store/kit', $indah + ['child_id' => 4]);
    check('Kit T2 2027 masih draf → tidak dipapar kepada ibu bapa', ok($draft) && $draft['data']['kit'] === null);
    Database::execute("UPDATE school_kits SET is_published = 1 WHERE id = 4");
    check('Selepas diterbitkan → dipapar', $parentB->get('store/kit', $indah + ['child_id' => 4])['data']['kit']['id'] === 4);
    Database::execute("UPDATE school_kits SET is_published = 0 WHERE id = 4");

    echo "6. Troli ala carte — anak, varian, stok & had dikira pelayan\n";
    $add = $parentA->post('store/cart/add', $melati + ['product_id' => 1, 'qty' => 2, 'child_id' => 2, 'unit_price' => 1, 'price' => 1]);
    check('Tambah 2 buku untuk Adam → cart_count 2', ok($add) && $add['data']['cart_count'] === 2 && $add['data']['child_name'] === 'Adam bin Faizal');
    $quote = $parentA->get('store/cart/get', $melati);
    $line = quoteItems($quote)[0] ?? [];
    check('Harga dari pelayar diabaikan: RM1.80 × 2 = RM3.60', ($line['unit_price'] ?? 0) === 180 && ($line['line_total'] ?? 0) === 360
        && $quote['data']['subtotal'] === 360 && $quote['data']['total'] === 360);
    $parentA->post('store/cart/add', $melati + ['product_id' => 1, 'qty' => 3, 'child_id' => 2]);
    check('Tambah produk sama untuk anak sama → kuantiti digabung (1 baris, 5)', count(cartLines(1, 1)) === 1 && (int) cartLines(1, 1)[0]['qty'] === 5);
    $cartB = cartLines(2, 3);
    check('A tambah untuk anak B → ditolak, tiada baris', !ok($parentA->post('store/cart/add', $melati + ['product_id' => 1, 'qty' => 1, 'child_id' => 4]))
        && cartLines(2, 3) === $cartB && count(cartLines(1, 1)) === 1);
    check('Anak A di sekolah lain (Faris) → ditolak', !ok($parentA->post('store/cart/add', $melati + ['product_id' => 1, 'qty' => 1, 'child_id' => 3])));
    check('Produk koperasi lain → 404', $parentA->postStatus('store/cart/add', $melati + ['product_id' => 6, 'qty' => 1, 'child_id' => 2]) === 404
        && $parentA->postStatus('store/cart/add', $melati + ['product_id' => 9, 'qty' => 1, 'child_id' => 2]) === 404);
    $noVariant = $parentA->post('store/cart/add', $melati + ['product_id' => 4, 'qty' => 1, 'child_id' => 2]);
    check('Varian wajib (tiada / varian produk lain) → ditolak', !ok($noVariant) && isset($noVariant['errors']['variant_id'])
        && !ok($parentA->post('store/cart/add', $melati + ['product_id' => 4, 'variant_id' => 4, 'qty' => 1, 'child_id' => 2])));
    $badQty = 0;
    foreach ([0, 100, -1, 'abc', 1.5] as $qty) {
        $badQty += !ok($parentA->post('store/cart/add', $melati + ['product_id' => 3, 'qty' => $qty, 'child_id' => 2])) ? 1 : 0;
    }
    check('Kuantiti 0 / 100 / -1 / "abc" / 1.5 → ditolak', $badQty === 5);
    check('Saiz L baki 4: tambah 5 → stok tidak mencukupi', !ok($parentA->post('store/cart/add', $melati + ['product_id' => 4, 'variant_id' => 3, 'qty' => 5, 'child_id' => 2])));
    check('Pensel had 5: 6 → ditolak', !ok($parentA->post('store/cart/add', $melati + ['product_id' => 2, 'qty' => 6, 'child_id' => 2])));
    $p1 = $parentA->post('store/cart/add', $melati + ['product_id' => 2, 'qty' => 3, 'child_id' => 2]);
    $p2 = $parentA->post('store/cart/add', $melati + ['product_id' => 2, 'qty' => 3, 'child_id' => $hanaId]);
    check('Had beli dikira merentas troli (Adam 3 + Hana 3 > 5) → kedua ditolak', ok($p1) && !ok($p2));
    $parentB->post('store/cart/add', $indah + ['product_id' => 9, 'qty' => 2, 'child_id' => 4]);
    $quoteB = $parentB->get('store/cart/get', $indah);
    check('Troli B (SMK): harga jualan murah RM2.50 × 2, kumpulan Arjun', ok($quoteB) && quoteItems($quoteB)[0]['unit_price'] === 250
        && $quoteB['data']['groups'][0]['child_name'] === 'Arjun a/l Rajesh' && $quoteB['data']['subtotal'] === 500);
    check('Troli A di SMK kosong (troli ikut koperasi)', ok($parentA->get('store/cart/get', $indah)) && $parentA->get('store/cart/get', $indah)['data']['item_count'] === 0);

    echo "7. Kit ke troli — item ditanda sahaja, saiz wajib, ganti baris kit\n";
    $kitBody = $melati + ['child_id' => 2, 'kit_id' => 1, 'items' => [
        ['kit_item_id' => $kit1Items[1], 'qty' => 6, 'variant_id' => null],
        ['kit_item_id' => $kit1Items[4], 'qty' => 2, 'variant_id' => 2],
    ]];
    $before = count(cartLines(1, 1));
    $addKit = $parentA->post('store/cart/add-kit', $kitBody);
    $kitLines = array_values(array_filter(cartLines(1, 1), function ($row) { return $row['source'] === 'kit'; }));
    check('2 item kit ditanda → 2 baris kit (school_kit_id 1, item kit dirujuk)', ok($addKit) && $addKit['data']['added'] === 2 && count($kitLines) === 2
        && (int) $kitLines[0]['school_kit_id'] === 1 && (int) $kitLines[1]['variant_id'] === 2 && (int) $kitLines[1]['school_kit_item_id'] === $kit1Items[4]);
    $kitBody['items'][0]['qty'] = 4;
    $parentA->post('store/cart/add-kit', $kitBody);
    $kitLines = array_values(array_filter(cartLines(1, 1), function ($row) { return $row['source'] === 'kit'; }));
    check('Masuk semula kit yang sama → baris diganti (bukan bertambah)', count($kitLines) === 2 && (int) $kitLines[0]['qty'] === 4
        && count(cartLines(1, 1)) === $before + 2);
    $inCart = $parentA->get('store/kit', $melati + ['child_id' => 2])['data']['kit'];
    check('store/kit: in_cart & pilihan troli dipulangkan', $inCart['in_cart'] === true
        && array_column($inCart['items'], 'in_cart', 'product_id')[4] === ['qty' => 2, 'variant_id' => 2]);
    $noSize = $parentA->post('store/cart/add-kit', $melati + ['child_id' => 2, 'kit_id' => 1, 'items' => [['kit_item_id' => $kit1Items[4], 'qty' => 1]]]);
    check('Item "pilih saiz" tanpa saiz → ralat variant_{id}', !ok($noSize) && isset($noSize['errors']['variant_' . $kit1Items[4]]));
    check('Item dari kit lain / item pendua / kuantiti 0 → ditolak', !ok($parentA->post('store/cart/add-kit', $melati + ['child_id' => 2, 'kit_id' => 1, 'items' => [['kit_item_id' => $kit2Item, 'qty' => 1]]]))
        && !ok($parentA->post('store/cart/add-kit', $melati + ['child_id' => 2, 'kit_id' => 1, 'items' => [['kit_item_id' => $kit1Items[1], 'qty' => 1], ['kit_item_id' => $kit1Items[1], 'qty' => 1]]]))
        && !ok($parentA->post('store/cart/add-kit', $melati + ['child_id' => 2, 'kit_id' => 1, 'items' => [['kit_item_id' => $kit1Items[1], 'qty' => 0]]])));
    check('Kit bukan untuk darjah anak (kit D4 untuk Adam) → tidak tersedia', !ok($parentA->post('store/cart/add-kit', $melati + ['child_id' => 2, 'kit_id' => 2, 'items' => [['kit_item_id' => $kit2Item, 'qty' => 1]]])));
    check('Senarai item kosong → "pilih sekurang-kurangnya satu"', !ok($parentA->post('store/cart/add-kit', $melati + ['child_id' => 2, 'kit_id' => 1, 'items' => []])));
    check('A masukkan kit untuk anak B → 404', $parentA->postStatus('store/cart/add-kit', $melati + ['child_id' => 4, 'kit_id' => 1, 'items' => [['kit_item_id' => $kit1Items[1], 'qty' => 1]]]) === 404);
    $stockKit = $parentA->post('store/cart/add-kit', $melati + ['child_id' => 2, 'kit_id' => 1, 'items' => [['kit_item_id' => $kit1Items[4], 'qty' => 3, 'variant_id' => 3],
        ['kit_item_id' => $kit1Items[1], 'qty' => 1]]]);
    check('Had baju 3 setiap pesanan: kit 3 + ala carte tiada → OK; stok L 4 cukup', ok($stockKit));
    $overMax = $parentA->post('store/cart/add', $melati + ['product_id' => 4, 'variant_id' => 3, 'qty' => 1, 'child_id' => $hanaId]);
    check('Saiz L untuk Hana selepas kit 3 helai → had 3 dikira merentas troli (semua anak)', !ok($overMax));

    echo "8. Ubah, buang, kosongkan — troli sendiri sahaja\n";
    $lineB = cartLines(2, 3)[0];
    check('A ubah kuantiti baris troli B → 404, tidak berubah', $parentA->postStatus('store/cart/update', $melati + ['id' => $lineB['id'], 'qty' => 9]) === 404
        && $parentA->postStatus('store/cart/update', $indah + ['id' => $lineB['id'], 'qty' => 9]) === 404 && (int) cartLines(2, 3)[0]['qty'] === 2);
    check('A buang baris troli B → 404, kekal', $parentA->postStatus('store/cart/remove', $indah + ['id' => $lineB['id']]) === 404 && count(cartLines(2, 3)) === 1);
    $firstA = cartLines(1, 1)[0];
    $updated = $parentA->post('store/cart/update', $melati + ['id' => $firstA['id'], 'qty' => 7]);
    check('Ubah kuantiti baris sendiri → CartQuote baharu', ok($updated) && (int) cartLines(1, 1)[0]['qty'] === 7 && isset($updated['data']['groups']));
    check('Kuantiti 0 → ditolak', !ok($parentA->post('store/cart/update', $melati + ['id' => $firstA['id'], 'qty' => 0])));
    Database::execute("UPDATE products SET price = 200 WHERE id = 1");
    $repriced = quoteItems($parentA->get('store/cart/get', $melati));
    check('Harga produk berubah → troli dikira semula (RM2.00)', $repriced[0]['unit_price'] === 200);
    Database::execute("UPDATE products SET is_active = 0 WHERE id = 3");
    $parentA->post('store/cart/add', $melati + ['product_id' => 5, 'qty' => 1, 'child_id' => 2]);
    Database::execute("UPDATE products SET is_active = 0 WHERE id = 5");
    Database::execute("UPDATE stock_levels SET qty_on_hand = 2 WHERE cooperative_id = 1 AND product_id = 1");
    $problems = $parentA->get('store/cart/get', $melati);
    $byProduct = [];
    foreach (quoteItems($problems) as $item) {
        $byProduct[$item['product_id']] = $item['problem'];
    }
    check('Produk dinyahaktif → "unavailable"; stok kurang → "stock"; has_problem, tidak dikira', ok($problems) && $byProduct[5] === 'unavailable'
        && $byProduct[1] === 'stock' && $problems['data']['has_problem'] === true);
    Database::execute("UPDATE stock_levels SET qty_on_hand = 500 WHERE cooperative_id = 1 AND product_id = 1");
    Database::execute("UPDATE products SET is_active = 1, price = 180 WHERE id IN (1, 3, 5)");
    $summary = $parentA->get('store/cart/summary', $melati);
    check('cart/summary & store/session = jumlah unit troli', ok($summary) && $summary['data']['item_count'] === array_sum(array_column(cartLines(1, 1), 'qty'))
        && $parentA->get('store/session', $melati)['data']['cart_count'] === $summary['data']['item_count'] && $guest->get('store/cart/summary', $melati)['data']['item_count'] === 0);
    $parentA->post('store/children/remove', ['id' => $hanaId]);
    check('Buang anak → baris troli anak itu dibuang', !in_array((string) $hanaId, array_column(cartLines(1, 1), 'child_id'), true)
        && (int) Database::fetchValue("SELECT is_active FROM children WHERE id = ?", [$hanaId]) === 0);
    $clearA = $parentA->post('store/cart/clear', $melati);
    check('Kosongkan troli A → kosong; troli B tidak terjejas', ok($clearA) && $clearA['data']['item_count'] === 0 && count(cartLines(1, 1)) === 0
        && count(cartLines(2, 3)) === 1);

    echo "9. Modul add-on di store (PELAN 18.13)\n";
    check('Berita SK Taman Melati: 1; SMK (tiada modul) → module_unavailable', $guest->get('store/news/list', $melati)['data']['total'] === 1
        && $guest->status('store/news/list', ['slug' => 'smk-seri-indah']) === 404);
    Database::execute("UPDATE news_posts SET published_at = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = 1");
    check('Berita berjadual (masa depan) tidak dipapar', $guest->get('store/news/list', $melati)['data']['total'] === 0);
    $sale = $guest->get('store/clearance/list', $indah);
    check('Jualan murah SMK: 1 jualan, 1 item RM2.50', ok($sale) && count($sale['data']) === 1 && $sale['data'][0]['items'][0]['sale_price'] === 250);
    Database::execute("UPDATE cooperative_modules SET status = 'expired' WHERE cooperative_id = 3 AND module_id = 2");
    check('Modul tamat → jualan murah ditutup & harga biasa', $guest->status('store/clearance/list', ['slug' => 'smk-seri-indah']) === 404
        && $guest->get('store/catalog', $indah)['data']['items'][0]['price'] === 320);

    echo "10. Halaman store & template (PELAN 10.5)\n";
    $page = $guest->page('store/index.php?school=sk-taman-melati&page=kit');
    check('Halaman kit → template1, slug dalam #store-context', $page['status'] === 200 && strpos($page['body'], 'templates/template1/css/template.css') !== false
        && strpos($page['body'], 'data-school-slug="sk-taman-melati"') !== false);
    Database::execute("UPDATE cooperatives SET template_id = 2 WHERE id = 1");
    check('Template koperasi tidak dilanggan / folder tiada → jatuh balik template1',
        strpos($guest->page('store/index.php?school=sk-taman-melati')['body'], 'templates/template1/') !== false);
    check('Slug tidak wujud → HTTP 404 (halaman ralat)', $guest->page('store/index.php?school=tiada-sekolah')['status'] === 404);
    check('Halaman perlu sekolah tanpa slug → redirect ke halaman utama', $guest->page('store/index.php?page=cart')['status'] === 302);
} finally {
    proc_terminate($server);
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
    clearTestLocks();
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
