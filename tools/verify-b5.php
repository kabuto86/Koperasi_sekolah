<?php
// Semakan B5 — kit keperluan sekolah: senarai, butiran, simpan, terbit, padam, salin tahun, pilihan produk + sekatan peranan
// + ujian kebocoran VENDOR01 ↔ VENDOR02 & ibu bapa A ↔ B. CLI sahaja:
//   php tools/verify-b5.php
// Perlu: DB koperasi_saas (schema + seed), config/database.php, config/secret.key, sambungan PHP curl.
// Skrip memulakan pelayan PHP sendiri (php -S 127.0.0.1:8098). Jadual yang diubah dipulihkan di hujung.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

function kitCount($where = '1', $args = [])
{
    return (int) Database::fetchValue("SELECT COUNT(*) FROM school_kits WHERE $where", $args);
}

function kitInput($extra = [])
{
    return array_merge([
        'cooperative_id' => 1, 'grade_level_code' => 'D2', 'school_year' => 2027, 'name' => 'Kit Darjah 2', 'note' => 'Nota ujian',
        'items' => [
            ['product_id' => 1, 'variant_id' => null, 'qty' => 5, 'is_mandatory' => true, 'note' => ''],
            ['product_id' => 4, 'variant_id' => 3, 'qty' => 1, 'is_mandatory' => false, 'note' => 'Saiz L'],
            ['product_id' => 4, 'variant_id' => null, 'qty' => 2, 'is_mandatory' => true, 'note' => 'Pilih saiz'],
        ],
        'publish' => false,
    ], $extra);
}

$port = 8098;
$base = 'http://127.0.0.1:' . $port;
$log = sys_get_temp_dir() . '/kop-verify-b5.log';
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes);
usleep(700000);

$tables = ['school_kits', 'school_kit_items', 'products', 'product_variants', 'cart_items', 'activity_logs'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();

try {
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');     // VENDOR01 admin — koperasi 1
    $v2 = vendorLogin($base, 'meiling@bestari.my');        // VENDOR02 admin — koperasi 2, 3
    $manager = vendorLogin($base, 'chong@bestari.my');     // VENDOR02 pengurus — koperasi 2 sahaja
    $cashier = vendorLogin($base, 'kasim@niagasekolah.my'); // VENDOR01 juruwang
    $v1->page('vendor/index.php');

    echo "1. Akses & peranan (PELAN 4)\n";
    $guest = new Browser($base);
    check('Tanpa sesi → kits/list auth_required', !empty($guest->get('vendor/kits/list', ['cooperative_id' => 1])['auth_required']));
    check('POST tanpa X-CSRF-Token → 419', $v1->postStatus('vendor/kits/save', kitInput(), false) === 419);
    check('GET ke endpoint POST → 405', $v1->getStatus('vendor/kits/copy-year') === 405);
    $blocked = 0;
    foreach (['list', 'get', 'product-options'] as $route) {
        $blocked += $cashier->getStatus('vendor/kits/' . $route . '.php?cooperative_id=1&id=1&school_year=2027') === 403 ? 1 : 0;
    }
    check('Juruwang → 3 laluan kit 403', $blocked === 3);
    check('Juruwang → simpan kit 403', $cashier->postStatus('vendor/kits/save', kitInput()) === 403);
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    check('Sesi ibu bapa → API kit 401', !empty($parentA->get('vendor/kits/list', ['cooperative_id' => 1])['auth_required']));

    echo "2. Senarai & butiran — harga semasa dikira pelayan (PELAN 16.7)\n";
    $list = $v1->get('vendor/kits/list', ['cooperative_id' => 1, 'school_year' => 2027]);
    $kits = ok($list) ? array_column($list['data']['kits'], null, 'grade_level_code') : [];
    check('Koperasi 1 (SK): darjah PRA–D6, kit D1 & D4', ok($list) && $list['data']['grades'] === ['PRA', 'D1', 'D2', 'D3', 'D4', 'D5', 'D6']
        && array_keys($kits) === ['D1', 'D4'] && $list['data']['previous_year_count'] === 0);
    // D1: 6×180 + 1×650 + 2×100 + 2×2500 = 6930 · wajib 1080 + 650 + 5000 = 6730
    check('Kit D1: 4 item, jumlah 6930, wajib 6730, diterbitkan', isset($kits['D1']) && $kits['D1']['item_count'] === 4
        && $kits['D1']['total_price'] === 6930 && $kits['D1']['mandatory_price'] === 6730 && $kits['D1']['is_published'] === true);
    $grades3 = $v2->get('vendor/kits/list', ['cooperative_id' => 3, 'school_year' => 2027]);
    check('Koperasi 3 (SMK): tingkatan T1–T6, kit T2 draf', ok($grades3) && $grades3['data']['grades'][0] === 'T1'
        && $grades3['data']['kits'][0]['grade_level_code'] === 'T2' && $grades3['data']['kits'][0]['is_published'] === false);
    $detail = $v1->get('vendor/kits/get', ['cooperative_id' => 1, 'id' => 1]);
    check('Butiran kit 1: item ikut susunan, baju = ibu bapa pilih saiz', ok($detail) && array_column($detail['data']['items'], 'product_id') === [1, 2, 3, 4]
        && $detail['data']['items'][3]['variant_id'] === null && $detail['data']['items'][3]['has_variants'] === true
        && $detail['data']['items'][3]['unit_price'] === 2500);
    $options = $v1->get('vendor/kits/product-options', ['cooperative_id' => 1]);
    $keys = ok($options) ? array_map(function ($o) { return $o['product_id'] . ':' . $o['variant_id']; }, $options['data']) : [];
    check('Pilihan produk: 5 produk + 3 varian baju, harga varian L = 2800', count($keys) === 8 && in_array('4:', $keys, true)
        && in_array('4:3', $keys, true) && $options['data'][array_search('4:3', $keys, true)]['price'] === 2800);
    check('Pilihan produk koperasi 1 tiada produk V02', !array_intersect(array_column($options['data'], 'product_id'), [6, 7, 8, 9, 10, 11]));
    Database::execute("UPDATE products SET price = 200 WHERE id = 1");
    $after = $v1->get('vendor/kits/list', ['cooperative_id' => 1, 'school_year' => 2027]);
    $d1 = array_column($after['data']['kits'], null, 'grade_level_code')['D1'];
    check('Harga produk berubah → jumlah kit ikut harga semasa (6930 + 6×20)', $d1['total_price'] === 7050 && $d1['mandatory_price'] === 6850);
    Database::execute("UPDATE products SET price = 180 WHERE id = 1");

    echo "3. Kebocoran VENDOR01 ↔ VENDOR02 (PELAN 8, 15)\n";
    check('V02 kits/get kit V01 (koperasi 1) → 404', $v2->getStatus('vendor/kits/get.php?cooperative_id=1&id=1') === 404);
    check('V02 kits/get kit V01 melalui koperasi sendiri → 404', $v2->getStatus('vendor/kits/get.php?cooperative_id=2&id=1') === 404);
    check('V01 kits/list koperasi V02 → 404', $v1->getStatus('vendor/kits/list.php?cooperative_id=2&school_year=2027') === 404);
    check("V01 cooperative_id '2 OR 1=1' → 404", $v1->getStatus('vendor/kits/list.php?cooperative_id=' . urlencode('2 OR 1=1')) === 404);
    check('V01 product-options koperasi 3 → 404', $v1->getStatus('vendor/kits/product-options.php?cooperative_id=3') === 404);
    check('V02 set-published kit V01 → 404, kit kekal diterbitkan',
        $v2->postStatus('vendor/kits/set-published', ['cooperative_id' => 2, 'id' => 1, 'is_published' => false]) === 404
        && kitCount('id = 1 AND is_published = 1') === 1);
    check('V02 delete kit V01 → 404, kit kekal', $v2->postStatus('vendor/kits/delete', ['cooperative_id' => 2, 'id' => 2]) === 404 && kitCount('id = 2') === 1);
    $res = $v2->post('vendor/kits/save', kitInput(['cooperative_id' => 2, 'id' => 1, 'grade_level_code' => 'D3']));
    check('V02 save dengan id kit V01 → 404, kit tidak berubah', !ok($res) && kitCount("id = 1 AND grade_level_code = 'D1' AND vendor_id = 1") === 1);
    $res = $v2->post('vendor/kits/copy-year', ['cooperative_id' => 1, 'school_year' => 2028]);
    check('V02 copy-year ke koperasi V01 → ditolak', !ok($res) && kitCount('school_year = 2028') === 0);
    check('Pengurus Chung Hwa → kit koperasi 3 404', $manager->getStatus('vendor/kits/list.php?cooperative_id=3&school_year=2027') === 404);
    check('Pengurus Chung Hwa → kit koperasi 2 dibenarkan', ok($manager->get('vendor/kits/list', ['cooperative_id' => 2, 'school_year' => 2027])));

    echo "4. Simpan — item hanya produk koperasi yang sama\n";
    $res = $v1->post('vendor/kits/save', kitInput(['items' => [['product_id' => 6, 'variant_id' => null, 'qty' => 1, 'is_mandatory' => true]]]));
    check('Produk koperasi V02 dalam kit V01 → ditolak', !ok($res) && isset($res['errors']['items']));
    $res = $v2->post('vendor/kits/save', kitInput(['cooperative_id' => 2, 'grade_level_code' => 'D2']));
    check('V02 guna produk V01 dalam kit koperasi sendiri → ditolak', !ok($res) && isset($res['errors']['items']));
    $res = $v2->post('vendor/kits/save', kitInput(['cooperative_id' => 3, 'grade_level_code' => 'T1',
        'items' => [['product_id' => 7, 'variant_id' => null, 'qty' => 1, 'is_mandatory' => true]]]));
    check('Produk koperasi 2 dalam kit koperasi 3 (vendor sama) → ditolak', !ok($res) && isset($res['errors']['items']));
    $bad = [
        'varian produk lain'       => ['product_id' => 4, 'variant_id' => 4, 'qty' => 1],
        'varian pada produk biasa' => ['product_id' => 1, 'variant_id' => 1, 'qty' => 1],
        'kuantiti 0'               => ['product_id' => 1, 'variant_id' => null, 'qty' => 0],
        'kuantiti pecahan'         => ['product_id' => 1, 'variant_id' => null, 'qty' => '1.5'],
        'produk tiada'             => ['product_id' => 9999, 'variant_id' => null, 'qty' => 1],
    ];
    $rejected = 0;
    foreach ($bad as $item) {
        $rejected += ok($v1->post('vendor/kits/save', kitInput(['items' => [$item]]))) ? 0 : 1;
    }
    check('5 item tidak sah ditolak (varian salah, kuantiti salah, produk tiada)', $rejected === 5);
    $res = $v1->post('vendor/kits/save', kitInput(['items' => [['product_id' => 1, 'qty' => 1], ['product_id' => 1, 'qty' => 2]]]));
    check('Item pendua (produk + varian sama) ditolak', !ok($res));
    $res = $v1->post('vendor/kits/save', kitInput(['items' => []]));
    check('Tiada item → mesej "Tambah sekurang-kurangnya satu item"', !ok($res) && $res['error'] === t('kit.items_required'));
    check('Tingkatan T1 untuk sekolah rendah ditolak', isset($v1->post('vendor/kits/save', kitInput(['grade_level_code' => 'T1']))['errors']['grade_level_code']));
    check('Tahun di luar julat (2035) ditolak', isset($v1->post('vendor/kits/save', kitInput(['school_year' => 2035]))['errors']['school_year']));
    $res = $v1->post('vendor/kits/save', kitInput(['grade_level_code' => 'D1']));
    check('Darjah + tahun sedia ada → kit.duplicate', !ok($res) && ($res['errors']['grade_level_code'] ?? '') === t('kit.duplicate'));
    Database::execute("UPDATE products SET is_active = 0 WHERE id = 5");
    check('Produk tidak aktif ditolak', !ok($v1->post('vendor/kits/save', kitInput(['items' => [['product_id' => 5, 'qty' => 1]]]))));
    Database::execute("UPDATE products SET is_active = 1 WHERE id = 5");
    check('Tiada kit tersimpan daripada percubaan tidak sah', kitCount('vendor_id = 1') === 2);

    $res = $v1->post('vendor/kits/save', kitInput(['vendor_id' => 2, 'unit_price' => 1, 'total_price' => 1]));
    $newId = ok($res) ? $res['data']['id'] : 0;
    // 5×180 + 1×2800 + 2×2500 = 8700 · wajib 900 + 5000 = 5900
    check('Kit D2 baharu: draf, jumlah dikira pelayan 8700 / wajib 5900', ok($res) && $res['data']['is_published'] === false
        && $res['data']['total_price'] === 8700 && $res['data']['mandatory_price'] === 5900);
    check('vendor_id dalam input diabaikan (kit milik VENDOR01)', kitCount('id = ? AND vendor_id = 1 AND cooperative_id = 1', [$newId]) === 1);
    $first = $v1->get('vendor/kits/get', ['cooperative_id' => 1, 'id' => $newId])['data']['items'];
    $input = kitInput(['id' => $newId, 'publish' => true, 'items' => [
        ['product_id' => 4, 'variant_id' => null, 'qty' => 3, 'is_mandatory' => true, 'note' => 'Pilih saiz'],
        ['product_id' => 1, 'variant_id' => null, 'qty' => 5, 'is_mandatory' => true, 'note' => ''],
        ['product_id' => 3, 'variant_id' => null, 'qty' => 1, 'is_mandatory' => false, 'note' => ''],
    ]]);
    $res = $v1->post('vendor/kits/save', $input);
    $second = $v1->get('vendor/kits/get', ['cooperative_id' => 1, 'id' => $newId])['data']['items'];
    check('Sunting + terbit: susunan baharu, item sama kekal id, varian L dibuang', ok($res) && $res['data']['is_published'] === true
        && array_column($second, 'product_id') === [4, 1, 3] && $second[0]['id'] === $first[2]['id'] && $second[1]['id'] === $first[0]['id']
        && $second[0]['qty'] === 3 && (int) Database::fetchValue("SELECT COUNT(*) FROM school_kit_items WHERE school_kit_id = ?", [$newId]) === 3);
    $res = $v1->post('vendor/kits/save', kitInput(['grade_level_code' => 'D3', 'name' => '幼儿 Kit ஒன்று', 'items' => [['product_id' => 2, 'qty' => 1]]]));
    check('Nama kit Cina/Tamil utf8mb4 tersimpan', ok($res) && $res['data']['name'] === '幼儿 Kit ஒன்று');
    $d3 = ok($res) ? $res['data']['id'] : 0;

    echo "5. Terbit / draf — kit draf tidak kelihatan di store\n";
    $coop1 = Database::fetchOne("SELECT * FROM cooperatives WHERE id = 1");
    check('Store: kit D2 diterbitkan dijumpai', SchoolKits::findPublished($coop1, 'D2', 2027) !== null);
    check('Store: kit D3 draf TIDAK dijumpai', SchoolKits::findPublished($coop1, 'D3', 2027) === null);
    $res = $v1->post('vendor/kits/set-published', ['cooperative_id' => 1, 'id' => $newId, 'is_published' => false]);
    check('Tarik balik kit D2 → draf, hilang dari store', ok($res) && $res['data']['is_published'] === false
        && SchoolKits::findPublished($coop1, 'D2', 2027) === null);
    $res = $v1->post('vendor/kits/set-published', ['cooperative_id' => 1, 'id' => $d3, 'is_published' => true]);
    check('Terbit kit D3 → kelihatan di store', ok($res) && SchoolKits::findPublished($coop1, 'D3', 2027) !== null);
    $coop2 = Database::fetchOne("SELECT * FROM cooperatives WHERE id = 2");
    check('Store koperasi 2 tidak nampak kit koperasi 1', SchoolKits::findPublished($coop2, 'D3', 2027) === null);
    Database::execute("UPDATE products SET is_active = 0 WHERE id = 2");
    check('Terbit kit tanpa item aktif ditolak', !ok($v1->post('vendor/kits/set-published', ['cooperative_id' => 1, 'id' => $newId + 999, 'is_published' => true]))
        && !ok($v1->post('vendor/kits/set-published', ['cooperative_id' => 1, 'id' => $d3, 'is_published' => true])));
    $list = $v1->get('vendor/kits/list', ['cooperative_id' => 1, 'school_year' => 2027]);
    $d3row = array_column($list['data']['kits'], null, 'grade_level_code')['D3'];
    check('Item produk tidak aktif tidak dikira dalam jumlah kit', $d3row['item_count'] === 1 && $d3row['total_price'] === 0);
    Database::execute("UPDATE products SET is_active = 1 WHERE id = 2");

    echo "6. Padam\n";
    $res = $v1->post('vendor/kits/delete', ['cooperative_id' => 1, 'id' => $d3]);
    check('Padam kit diterbitkan ditolak', !ok($res) && $res['error'] === t('kit.delete_published') && kitCount('id = ?', [$d3]) === 1);
    $res = $v1->post('vendor/kits/delete', ['cooperative_id' => 1, 'id' => $newId]);
    check('Padam kit draf → kit & item dibuang', ok($res) && kitCount('id = ?', [$newId]) === 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM school_kit_items WHERE school_kit_id = ?", [$newId]) === 0);

    echo "7. Salin dari tahun lepas\n";
    Database::execute("UPDATE products SET is_active = 0 WHERE id = 3");
    $res = $v1->post('vendor/kits/copy-year', ['cooperative_id' => 1, 'school_year' => 2028]);
    // 2027: D1 (4 item, pemadam id 3 tidak aktif), D3 (1 item), D4 (3 item) → 3 disalin, 1 item dibuang
    check('Salin 2027 → 2028: 3 kit disalin, 1 item tidak aktif dibuang', ok($res) && $res['data'] === ['copied' => 3, 'skipped' => 0, 'removed_items' => 1]);
    check('Semua kit salinan = draf milik koperasi 1', kitCount('school_year = 2028') === 3 && kitCount('school_year = 2028 AND (is_published = 1 OR cooperative_id <> 1)') === 0);
    $copy = Database::fetchValue("SELECT id FROM school_kits WHERE cooperative_id = 1 AND school_year = 2028 AND grade_level_code = 'D1'");
    $copyItems = $v1->get('vendor/kits/get', ['cooperative_id' => 1, 'id' => $copy])['data']['items'];
    check('Salinan D1: 3 item (tanpa pemadam), susunan 1..3', array_column($copyItems, 'product_id') === [1, 2, 4]
        && array_column($copyItems, 'sort_order') === [1, 2, 3]);
    $res = $v1->post('vendor/kits/copy-year', ['cooperative_id' => 1, 'school_year' => 2028]);
    check('Salin semula: semua dilangkau', ok($res) && $res['data']['copied'] === 0 && $res['data']['skipped'] === 3);
    check('previous_year_count 2028 = 3', $v1->get('vendor/kits/list', ['cooperative_id' => 1, 'school_year' => 2028])['data']['previous_year_count'] === 3);
    check('Salin ke tahun di luar julat ditolak', !ok($v1->post('vendor/kits/copy-year', ['cooperative_id' => 1, 'school_year' => 2040])));
    check('Kit V02 tidak terjejas oleh salinan V01', kitCount('vendor_id = 2') === 2);
    Database::execute("UPDATE products SET is_active = 1 WHERE id = 3");

    echo "8. Log aktiviti & kebocoran ibu bapa A ↔ B\n";
    $actions = array_column(Database::fetchAll("SELECT DISTINCT action FROM activity_logs WHERE vendor_id = 1 AND entity = 'school_kit'"), 'action');
    check('Log aktiviti 5 jenis tindakan kit direkod', count(array_intersect(['kit_save', 'kit_publish', 'kit_unpublish', 'kit_delete', 'kit_copy'], $actions)) === 5);
    check('Tiada log kit V01 di bawah V02', (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE vendor_id = 2 AND entity = 'school_kit'") === 0);
    list($parentB) = storeLogin($base, 'rajesh@contoh.my');
    check('Ibu bapa B → kits/list koperasi 3 401', !empty($parentB->get('vendor/kits/list', ['cooperative_id' => 3])['auth_required']));
    $before = kitCount();
    check('Ibu bapa A → simpan kit 401, tiada kit baharu', !empty($parentA->post('vendor/kits/save', kitInput(['grade_level_code' => 'D5']))['auth_required'])
        && kitCount() === $before);
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
