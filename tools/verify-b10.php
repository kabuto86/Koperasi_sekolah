<?php
// Semakan B10 — POS kaunter: log masuk PIN (Argon2id, 5 salah → dikunci), syif & tunai laci, katalog, pengiraan troli pelayan
// (sama dengan pos-cart.js), jualan tunai & QR online (gateway palsu, webhook, semakan semula, batal, luput), resit, jualan syif,
// promosi (vendor/promotions/*), serentak + ujian kebocoran VENDOR01 ↔ VENDOR02 dan ibu bapa A ↔ B. CLI sahaja:
//   php tools/verify-b10.php
// Perlu: DB koperasi_saas (schema + seed + migrasi), config/database.php, config/secret.key, sambungan PHP curl, node (semakan pos-cart.js).
// Skrip memulakan pelayan aplikasi (php -S, 4 pekerja) + gateway palsu (tools/fake-gateway.php). Jadual yang diubah dipulihkan di hujung.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

function order($id)
{
    return Database::fetchOne("SELECT * FROM orders WHERE id = ?", [(int) $id]);
}

function level($productId, $variantId = 0, $cooperativeId = 1)
{
    $row = Database::fetchOne(
        "SELECT qty_on_hand, qty_reserved FROM stock_levels WHERE cooperative_id = ? AND product_id = ? AND variant_id = ?",
        [$cooperativeId, $productId, $variantId]
    );
    return [(int) $row['qty_on_hand'], (int) $row['qty_reserved']];
}

function setStock($productId, $onHand, $variantId = 0, $cooperativeId = 1)
{
    Database::execute("UPDATE stock_levels SET qty_on_hand = ?, qty_reserved = 0 WHERE cooperative_id = ? AND product_id = ? AND variant_id = ?",
        [$onHand, $cooperativeId, $productId, $variantId]);
}

function setBill($fakeBase, $ref, $status = 'paid')
{
    return json_decode((string) file_get_contents($fakeBase . '/_admin/set?' . http_build_query(['ref' => $ref, 'status' => $status])), true);
}

function webhook($base, $code, $coop, array $fields)
{
    $curl = curl_init($base . '/api/v1/webhooks/' . $code . '.php?coop=' . $coop);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields), CURLOPT_TIMEOUT => 30]);
    curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return $status;
}

function sale(Browser $device, array $coop, $shiftId, array $items, $method = 'cash', array $extra = [])
{
    return $device->post('vendor/pos/checkout', $extra + $coop + ['shift_id' => $shiftId, 'items' => $items, 'payment_method' => $method,
        'amount_received' => 100000, 'coupon_code' => '', 'manual_discount' => 0, 'customer_name' => '']);
}

function pinLogin(Browser $device, array $coop, $userId, $pin = '1234')
{
    return $device->post('vendor/pos/pin-login', $coop + ['user_id' => $userId, 'pin' => $pin]);
}

// posTotals (assets/js/vendor/pos-cart.js) dijalankan dengan node — pulangkan senarai jumlah atau null
function jsTotals(array $cases)
{
    $script = file_get_contents(APP_ROOT . '/assets/js/vendor/pos-cart.js') . "\nvar cases = " . json_encode($cases) . ";\n"
        . "console.log(JSON.stringify(cases.map(function (c) {\n"
        . "  var promo = c.coupon ? c.coupon : posBestAutomatic(c.promotions, c.lines.reduce(function (s, l) { return s + l.unit_price * l.qty; }, 0));\n"
        . "  return posTotals(c.lines, { promotion: promo, manualDiscount: c.manual, taxRate: c.tax });\n"
        . "})));\n";
    $file = tempnam(sys_get_temp_dir(), 'kopjs') . '.js';
    file_put_contents($file, $script);
    $out = shell_exec('node ' . escapeshellarg($file) . ' 2>/dev/null');
    @unlink($file);
    return $out ? json_decode(trim($out), true) : null;
}

$port = 8098;
$fakePort = 8099;
$base = 'http://127.0.0.1:' . $port;
$fakeBase = 'http://127.0.0.1:' . $fakePort;
$log = sys_get_temp_dir() . '/kop-verify-b10.log';
$stateFile = sys_get_temp_dir() . '/kop-verify-b10-gateway.json';
@unlink($stateFile);
$fake = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $fakePort, __DIR__ . '/fake-gateway.php'], [1 => ['file', $log . '.fake', 'w'], 2 => ['file', $log . '.fake', 'a']],
    $fakePipes, null, ['FAKE_GATEWAY_STATE' => $stateFile] + getenv());
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4', 'KOPERASI_GATEWAY_TEST_URL' => $fakeBase] + getenv());
usleep(900000);
putenv('KOPERASI_GATEWAY_TEST_URL=' . $fakeBase);

$tables = ['orders', 'order_items', 'order_status_logs', 'order_pickups', 'order_pickup_items', 'stock_levels', 'stock_movements', 'carts',
    'cart_items', 'promotions', 'payments', 'webhook_logs', 'cooperative_gateways', 'cooperative_settings', 'activity_logs', 'email_queue',
    'pos_shifts', 'vendor_users', 'login_attempts', 'rate_limits', 'payment_gateways'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
Database::execute("DELETE FROM rate_limits WHERE bucket_key LIKE 'pos_%'");
Database::execute('DELETE FROM cart_items');
Database::execute('DELETE FROM carts');
Database::execute("UPDATE promotions SET is_active = 0");

try {
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $cashier1 = vendorLogin($base, 'kasim@niagasekolah.my');
    $cashier2 = vendorLogin($base, 'kavitha@bestari.my');
    $manager2 = vendorLogin($base, 'chong@bestari.my');
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    list($parentB) = storeLogin($base, 'rajesh@contoh.my');
    $c1 = ['cooperative_id' => 1];
    $c2 = ['cooperative_id' => 2];
    $c3 = ['cooperative_id' => 3];

    echo "1. Peranan & keselamatan laluan (PELAN 4)\n";
    $guest = new Browser($base);
    check('Tanpa sesi → 401 (pos/session, pos/catalog, promotions/list)', $guest->status('vendor/pos/session', $c1) === 401
        && $guest->status('vendor/pos/catalog', $c1) === 401 && $guest->status('vendor/promotions/list', $c1) === 401);
    check('Juruwang: katalog panel & tetapan → 403 (products/list, products/save, inventory/list, settings/save, promotions/list, staff/list)',
        $cashier1->status('vendor/products/list', $c1) === 403 && $cashier1->postStatus('vendor/products/save', $c1) === 403
        && $cashier1->status('vendor/inventory/list', $c1) === 403 && $cashier1->postStatus('vendor/settings/save', $c1) === 403
        && $cashier1->status('vendor/promotions/list', $c1) === 403 && $cashier1->postStatus('vendor/promotions/save', $c1) === 403
        && $cashier1->status('vendor/staff/list') === 403);
    check('Juruwang: POS (session, catalog) & settings/get → 200', $cashier1->status('vendor/pos/session', $c1) === 200
        && $cashier1->status('vendor/pos/catalog', $c1) === 200 && $cashier1->status('vendor/settings/get', $c1) === 200);
    $res = $cashier1->post('vendor/pos/shift-open', $c1 + ['opening_cash' => 1000]);
    check('Tanpa PIN juruwang → 403 pos_login_required (shift-open, checkout, sales, receipt)', !ok($res) && !empty($res['pos_login_required'])
        && $res['error'] === t('pos.error.login_required') && $cashier1->postStatus('vendor/pos/checkout', $c1) === 403
        && $cashier1->status('vendor/pos/sales', $c1 + ['shift_id' => 1]) === 403 && $cashier1->status('vendor/pos/receipt', $c1 + ['order_id' => 1]) === 403);
    check('POST tanpa CSRF → 419; GET ke POST → 405', $cashier1->postStatus('vendor/pos/pin-login', $c1, false) === 419
        && $cashier1->getStatus('vendor/pos/checkout') === 405);

    echo "2. Sesi kaunter & log masuk PIN (PELAN 11, 18.11)\n";
    $session = $cashier1->get('vendor/pos/session', $c1);
    $ids = array_column($session['data']['cashiers'], 'id');
    sort($ids);
    check('session: koperasi, tiada juruwang, senarai PIN = Ahmad (admin) + Kasim (juruwang) sahaja', ok($session)
        && $session['data']['cooperative']['school_name'] === 'SK Taman Melati' && $session['data']['cashier'] === null
        && $session['data']['shift'] === null && $ids === [1, 2] && $session['data']['online_available'] === false);
    check('Kakitangan VENDOR02 (Kavitha) → pin-login koperasi 1 → 403 tiada akses', !ok(pinLogin($cashier1, $c1, 5)));
    $res = pinLogin($cashier1, $c1, 2, '9999');
    check('PIN salah → "Baki cubaan: 4"', !ok($res) && $res['errors']['pin'] === t('pos.pin_wrong', ['left' => 4]));
    for ($i = 0; $i < 3; $i++) {
        pinLogin($cashier1, $c1, 2, '0000');
    }
    $res = pinLogin($cashier1, $c1, 2, '0000');
    $locked = pinLogin($cashier1, $c1, 2, '1234');
    check('5 PIN salah → dikunci; PIN betul selepas dikunci tetap ditolak (423)', !ok($res) && $res['error'] === t('pos.pin_locked')
        && !ok($locked) && $locked['error'] === t('pos.pin_locked') && $cashier1->postStatus('vendor/pos/pin-login', $c1 + ['user_id' => 2, 'pin' => '1234']) === 423);
    $list = $cashier1->get('vendor/pos/session', $c1)['data']['cashiers'];
    $kasim = array_values(array_filter($list, function ($row) { return $row['id'] === 2; }))[0];
    check('Senarai juruwang menanda PIN dikunci', $kasim['pin_locked'] === true);
    $staff = $v1->get('vendor/staff/list', ['per_page' => 50]);
    $row = array_values(array_filter($staff['data']['items'], function ($row) { return $row['id'] === 2; }))[0];
    $reset = $v1->post('vendor/staff/save', ['id' => 2, 'full_name' => $row['full_name'], 'email' => $row['email'], 'phone' => $row['phone'],
        'role' => 'cashier', 'cooperative_ids' => [1], 'pin' => '2468']);
    $res = pinLogin($cashier1, $c1, 2, '2468');
    check('Pentadbir tetapkan PIN baharu → kunci dibuka; log masuk PIN berjaya', ok($reset) && ok($res) && $res['data']['cashier']['id'] === 2
        && $res['data']['shift'] === null && (int) Database::fetchValue("SELECT pin_failed_attempts FROM vendor_users WHERE id = 2") === 0);
    check('PIN disimpan Argon2id (bukan teks biasa)', strpos((string) Database::fetchValue("SELECT pin_hash FROM vendor_users WHERE id = 2"), '$argon2id$') === 0);
    check('Log aktiviti pos_login atas nama juruwang', (int) Database::fetchValue(
        "SELECT COUNT(*) FROM activity_logs WHERE vendor_id = 1 AND cooperative_id = 1 AND vendor_user_id = 2 AND action = 'pos_login'") === 1);
    check('Juruwang V01 → pin-login koperasi VENDOR02 → ditolak (404)', !ok(pinLogin($v1, $c2, 2, '2468')) && $v1->postStatus('vendor/pos/pin-login', $c2 + ['user_id' => 2, 'pin' => '2468']) === 404);
    $session = $cashier1->get('vendor/pos/session', $c1);
    check('session: juruwang Kasim dalam sesi kaunter', $session['data']['cashier']['full_name'] === 'Kasim bin Osman');

    echo "3. Syif & tunai laci\n";
    $res = $cashier1->post('vendor/pos/shift-open', $c1 + ['opening_cash' => -5]);
    check('Tunai permulaan negatif ditolak', !ok($res) && isset($res['errors']['opening_cash']));
    $shift = $cashier1->post('vendor/pos/shift-open', $c1 + ['opening_cash' => 10000]);
    $shiftId = $shift['data']['id'];
    check('Buka syif RM100 → PosShift open, jangkaan RM100', ok($shift) && $shift['data']['status'] === 'open' && $shift['data']['opening_cash'] === 10000
        && $shift['data']['expected_cash'] === 10000 && $shift['data']['user_name'] === 'Kasim bin Osman');
    $again = $cashier1->post('vendor/pos/shift-open', $c1 + ['opening_cash' => 0]);
    check('Satu syif terbuka setiap juruwang → kali kedua ditolak', !ok($again) && $again['error'] === t('pos.shift_already_open'));
    $cashier1->post('vendor/pos/lock', $c1);
    $locked = $cashier1->get('vendor/pos/session', $c1);
    $relog = pinLogin($cashier1, $c1, 2, '2468');
    check('Kunci kaunter → juruwang keluar, syif kekal; log masuk semula → syif sama', $locked['data']['cashier'] === null && ok($relog)
        && $relog['data']['shift']['id'] === $shiftId);

    echo "4. Katalog POS\n";
    $catalog = $cashier1->get('vendor/pos/catalog', $c1);
    $products = array_column($catalog['data']['products'], null, 'id');
    check('Produk koperasi 1 sahaja (5), tiada produk VENDOR02', ok($catalog) && array_keys($products) === [1, 2, 3, 4, 5]
        || (ok($catalog) && count($products) === 5 && !isset($products[9]) && !isset($products[6])));
    check('Barcode, had beli, varian & stok boleh dijual (on_hand − reserved)', $products[1]['barcode'] === '9555000000017'
        && $products[2]['max_per_order'] === 5 && $products[4]['has_variants'] === true && count($products[4]['variants']) === 3
        && $products[4]['stock_available'] === 104 && $products[1]['stock_available'] === 500);
    Database::execute("UPDATE promotions SET is_active = 1 WHERE id IN (1, 2)");
    $catalog = $cashier1->get('vendor/pos/catalog', $c1);
    check('Promosi berjalan dalam katalog (JIMAT5 + automatik 3%), tanpa promosi koperasi 3', count($catalog['data']['promotions']) === 2
        && $catalog['data']['promotions'][0]['code'] === 'JIMAT5' && $catalog['data']['promotions'][1]['code'] === null);
    Database::execute("UPDATE products SET is_active = 0 WHERE id = 5");
    $catalog = $cashier1->get('vendor/pos/catalog', $c1);
    check('Produk tidak aktif tidak dipapar', !in_array(5, array_column($catalog['data']['products'], 'id'), true));

    echo "5. Pengiraan troli pelayan = pos-cart.js\n";
    $promoPct = ['id' => 1, 'name' => 'P', 'code' => null, 'type' => 'percent', 'value' => 7, 'min_spend' => 1000];
    $promoFix = ['id' => 2, 'name' => 'F', 'code' => 'F5', 'type' => 'fixed', 'value' => 500, 'min_spend' => 5000];
    $cases = [
        ['lines' => [['unit_price' => 333, 'qty' => 3], ['unit_price' => 1999, 'qty' => 1]], 'promotions' => [$promoPct], 'coupon' => null, 'manual' => 15, 'tax' => 6],
        ['lines' => [['unit_price' => 2500, 'qty' => 3]], 'promotions' => [], 'coupon' => $promoFix, 'manual' => 0, 'tax' => 6.5],
        ['lines' => [['unit_price' => 180, 'qty' => 1]], 'promotions' => [$promoPct], 'coupon' => null, 'manual' => 500, 'tax' => 8],
        ['lines' => [['unit_price' => 4990, 'qty' => 2], ['unit_price' => 101, 'qty' => 7]], 'promotions' => [$promoPct], 'coupon' => null, 'manual' => 0, 'tax' => 0],
    ];
    $js = jsTotals($cases);
    $same = is_array($js);
    foreach ($cases as $i => $case) {
        $subtotal = array_sum(array_map(function ($l) { return $l['unit_price'] * $l['qty']; }, $case['lines']));
        $promotion = $case['coupon'];
        if ($promotion === null) {
            foreach ($case['promotions'] as $p) {
                if (Promotions::discount($p, $subtotal) > 0) {
                    $promotion = $p;
                }
            }
        }
        $php = PosCalculateCartJob::totals($subtotal, $promotion, $case['manual'], $case['tax']);
        $same = $same && $js[$i] == $php;
    }
    check('PosCalculateCartJob::totals = posTotals (pembundaran %, cukai 6.5%, diskaun manual > baki)', $same);

    echo "6. Jualan tunai\n";
    Database::execute("UPDATE products SET is_active = 1 WHERE id = 5");
    CooperativeSettings::set(1, 1, ['tax_rate' => '6']);
    $before1 = level(1);
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 1, 'variant_id' => 0, 'qty' => 2, 'unit_price' => 1], ['product_id' => 4, 'variant_id' => 3, 'qty' => 1]],
        'cash', ['amount_received' => 3000, 'customer_name' => 'Aiman (walk-in)']);
    // 2 × 180 + 2800 = 3160; kupon tidak dimasukkan & automatik 3% hanya ≥ RM200 → tiada promosi; cukai 6% = 190 → 3350
    check('Wang tidak cukup (RM30 < jumlah) → ditolak, tiada pesanan', !ok($res) && isset($res['errors']['amount_received'])
        && (int) Database::fetchValue("SELECT COUNT(*) FROM orders WHERE channel = 'pos'") === 0);
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 1, 'variant_id' => 0, 'qty' => 2, 'unit_price' => 1], ['product_id' => 4, 'variant_id' => 3, 'qty' => 1]],
        'cash', ['amount_received' => 5000, 'customer_name' => 'Aiman (walk-in)']);
    $r = $res['data']['receipt'] ?? [];
    check('Harga dari pelayan (harga pelayar diabaikan): 3160 + cukai 6% 190 = RM33.50, baki RM16.50', ok($res) && $r['subtotal'] === 3160
        && $r['tax'] === 190 && $r['total'] === 3350 && $r['change'] === 1650 && $r['amount_received'] === 5000 && $res['data']['payment'] === null);
    check('Resit: POS-YYMMDD-001, juruwang, pembeli, item, sekolah', $r['order_no'] === 'POS-' . date('ymd') . '-001' && $r['cashier_name'] === 'Kasim bin Osman'
        && $r['customer_name'] === 'Aiman (walk-in)' && count($r['items']) === 2 && $r['items'][1]['variant_label'] === 'Saiz L'
        && $r['items'][1]['unit_price'] === 2800 && $r['school_name'] === 'SK Taman Melati' && $r['status'] === 'collected' && $r['payment_status'] === 'paid');
    $o1 = $r['order_id'];
    $row = order($o1);
    check('Pesanan channel pos, customer_id NULL, collected, syif & juruwang, item source pos (collected_qty = qty)', $row['channel'] === 'pos'
        && $row['customer_id'] === null && $row['status'] === 'collected' && $row['paid_at'] !== null && (int) $row['pos_shift_id'] === $shiftId
        && (int) $row['cashier_id'] === 2 && (int) Database::fetchValue("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND source = 'pos' AND collected_qty = qty", [$o1]) === 2);
    check('payments: tunai paid, received_by juruwang', (int) Database::fetchValue(
        "SELECT COUNT(*) FROM payments WHERE order_id = ? AND method = 'cash' AND status = 'paid' AND amount = 3350 AND received_by = 2", [$o1]) === 1);
    check('Stok ditolak (pos_sale −2, varian L −1, tiada tahanan) atas nama juruwang', level(1) === [$before1[0] - 2, 0] && level(4, 3) === [3, 0]
        && (int) Database::fetchValue("SELECT COUNT(*) FROM stock_movements WHERE ref_type = 'order' AND ref_id = ? AND type = 'pos_sale' AND vendor_user_id = 2", [$o1]) === 2);
    check('Log status pending_payment → collected + log aktiviti pos_sale', Database::fetchValue(
        "SELECT GROUP_CONCAT(to_status ORDER BY id) FROM order_status_logs WHERE order_id = ?", [$o1]) === 'pending_payment,collected'
        && (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE action = 'pos_sale' AND entity_id = ? AND vendor_user_id = 2", [$o1]) === 1);
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 3, 'variant_id' => 0, 'qty' => 1]], 'cash', ['amount_received' => 106]);
    check('Jualan kedua → POS-YYMMDD-002', ok($res) && $res['data']['receipt']['order_no'] === 'POS-' . date('ymd') . '-002');
    $o2 = $res['data']['receipt']['order_id'];

    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 1, 'variant_id' => 0, 'qty' => 2]], 'cash', ['coupon_code' => 'jimat5']);
    check('Kupon JIMAT5 bawah belanja minimum RM50 → ralat coupon_code', !ok($res) && $res['errors']['coupon_code'] === t('pos.coupon_min_spend', ['amount' => 'RM 50.00']));
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 1, 'variant_id' => 0, 'qty' => 2]], 'cash', ['coupon_code' => 'TIADA']);
    check('Kupon tidak wujud → ralat coupon_code', !ok($res) && $res['errors']['coupon_code'] === t('pos.coupon_invalid'));
    $used = (int) Database::fetchValue("SELECT used_count FROM promotions WHERE id = 1");
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 1, 'variant_id' => 0, 'qty' => 30]], 'cash', ['coupon_code' => 'jimat5', 'manual_discount' => 100]);
    $r = $res['data']['receipt'] ?? [];
    check('Kupon JIMAT5 (RM5) + diskaun manual RM1 → cukai atas baki: 5400 − 600 = 4800 + 288', ok($res) && $r['promotion_discount'] === 500
        && $r['manual_discount'] === 100 && $r['discount'] === 600 && $r['tax'] === 288 && $r['total'] === 5088 && $r['promotion_code'] === 'JIMAT5'
        && (int) Database::fetchValue("SELECT used_count FROM promotions WHERE id = 1") === $used + 1);
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 4, 'variant_id' => 2, 'qty' => 3], ['product_id' => 1, 'variant_id' => 0, 'qty' => 80]], 'cash',
        ['amount_received' => 30000]);
    $r = $res['data']['receipt'] ?? [];
    check('Diskaun automatik 3% (≥ RM200) dipilih pelayan tanpa kod', ok($res) && $r['subtotal'] === 21900 && $r['promotion_discount'] === 657
        && $r['promotion_code'] === null && $r['promotion_name'] === 'Diskaun automatik 3%');
    CooperativeSettings::set(1, 1, ['tax_rate' => '0']);
    Database::execute("UPDATE promotions SET is_active = 0");

    $count = (int) Database::fetchValue("SELECT COUNT(*) FROM orders WHERE channel = 'pos'");
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 1, 'variant_id' => 0, 'qty' => 1]], 'cash', ['manual_discount' => 500]);
    check('Diskaun manual > jumlah kecil → ralat manual_discount', !ok($res) && isset($res['errors']['manual_discount']));
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 4, 'variant_id' => 3, 'qty' => 4]]);
    check('Stok tidak cukup (baki 3) → ralat, tiada apa disimpan', !ok($res) && $res['error'] === t('pos.error.stock', ['name' => 'Baju Sekolah Putih Lelaki — Saiz L', 'n' => 3]));
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 2, 'variant_id' => 0, 'qty' => 4], ['product_id' => 2, 'variant_id' => 0, 'qty' => 2]]);
    check('Had beli Pensel 5 unit (baris berganda dicantum) → ralat', !ok($res) && $res['error'] === t('pos.error.max_per_order', ['name' => 'Pensel 2B (kotak 12)', 'n' => 5]));
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 4, 'variant_id' => 0, 'qty' => 1]]);
    check('Produk bervarian tanpa varian → pilih varian', !ok($res) && $res['error'] === t('pos.error.choose_variant', ['name' => 'Baju Sekolah Putih Lelaki']));
    $bad = [sale($cashier1, $c1, $shiftId, [['product_id' => 9, 'variant_id' => 0, 'qty' => 1]]),
        sale($cashier1, $c1, $shiftId, [['product_id' => 4, 'variant_id' => 4, 'qty' => 1]]),
        sale($cashier1, $c1, $shiftId, [['product_id' => 1, 'variant_id' => 0, 'qty' => 0]]),
        sale($cashier1, $c1, $shiftId, [])];
    check('Produk / varian VENDOR02, qty 0, troli kosong → ditolak', !ok($bad[0]) && $bad[0]['error'] === t('pos.error.invalid_item')
        && !ok($bad[1]) && !ok($bad[2]) && !ok($bad[3]) && $bad[3]['error'] === t('pos.cart_empty'));
    $res = sale($cashier1, $c1, $shiftId + 999, [['product_id' => 1, 'variant_id' => 0, 'qty' => 1]]);
    check('shift_id bukan syif juruwang → tiada syif', !ok($res) && $res['error'] === t('pos.error.no_shift'));
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 1, 'variant_id' => 0, 'qty' => 1]], 'online');
    check('QR online tanpa gateway aktif → ditolak', !ok($res) && $res['error'] === t('pos.online_unavailable'));
    check('Jualan gagal tidak menyimpan apa-apa', (int) Database::fetchValue("SELECT COUNT(*) FROM orders WHERE channel = 'pos'") === $count);

    echo "7. Jualan POS dalam panel (pesanan, rekod bayaran)\n";
    $list = $v1->get('vendor/orders/list', $c1 + ['per_page' => 100]);
    check('vendor/orders/list (Pesanan online) tidak menyenaraikan jualan POS', ok($list)
        && !in_array($o1, array_column($list['data']['items'], 'id'), true));
    $detail = $v1->get('vendor/orders/get', $c1 + ['id' => $o1]);
    check('vendor/orders/get membuka jualan POS (channel pos)', ok($detail) && $detail['data']['channel'] === 'pos' && $detail['data']['status'] === 'collected');
    $records = $v1->get('vendor/payments/list', $c1 + ['per_page' => 100]);
    check('Rekod bayaran tunai POS muncul dalam vendor/payments/list', ok($records)
        && in_array($o1, array_column($records['data']['items'], 'order_id'), true));

    echo "8. QR online (gateway palsu, webhook, semakan semula, batal, luput)\n";
    $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'toyyibpay', 'mode' => 'sandbox',
        'credentials' => ['secret_key' => 'tp-secret', 'category_code' => 'cat123']]);
    $session = $cashier1->get('vendor/pos/session', $c1);
    check('session: online_available + nama gateway', $session['data']['online_available'] === true && $session['data']['gateway_name'] === 'ToyyibPay');
    $before = level(2);
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 2, 'variant_id' => 0, 'qty' => 2]], 'online');
    $q1 = $res['data']['receipt']['order_id'] ?? 0;
    $billCode = Database::fetchValue("SELECT gateway_ref FROM payments WHERE order_id = ?", [$q1]);
    check('QR: pending_payment, payment { reference, gateway_name, qr_payload, expires_in ≈ 15 min }', ok($res)
        && $res['data']['receipt']['status'] === 'pending_payment' && $res['data']['payment']['gateway_name'] === 'ToyyibPay'
        && strpos($res['data']['payment']['qr_payload'], $billCode) !== false && $res['data']['payment']['expires_in'] > 800
        && $res['data']['payment']['reference'] === $res['data']['receipt']['order_no'] . '-1');
    check('Stok ditahan (reserve), belum ditolak; payments pending', level(2) === [$before[0], 2]
        && Database::fetchValue("SELECT status FROM payments WHERE order_id = ?", [$q1]) === 'pending');
    $status = $cashier1->get('vendor/pos/payment-status', $c1 + ['order_id' => $q1]);
    check('payment-status: pending (gateway belum dibayar)', ok($status) && $status['data']['status'] === 'pending');
    $closeTry = $cashier1->post('vendor/pos/shift-close', $c1 + ['shift_id' => $shiftId, 'closing_cash' => 0]);
    check('Tutup syif ditolak semasa ada QR pending', !ok($closeTry) && $closeTry['error'] === t('pos.shift_pending_payments', ['n' => 1]));
    setBill($fakeBase, $billCode, 'paid');
    Database::execute("DELETE FROM rate_limits WHERE bucket_key LIKE 'pos_payment_check:%'");
    $status = $cashier1->get('vendor/pos/payment-status', $c1 + ['order_id' => $q1]);
    check('Pembeli bayar → payment-status bertanya gateway → paid + resit (rujukan gateway)', ok($status) && $status['data']['status'] === 'paid'
        && $status['data']['receipt']['gateway_ref'] === $billCode && $status['data']['receipt']['gateway_name'] === 'ToyyibPay');
    check('Selesai: collected, tahanan dilepas, stok ditolak pos_sale, log oleh sistem', order($q1)['status'] === 'collected'
        && level(2) === [$before[0] - 2, 0]
        && (int) Database::fetchValue("SELECT COUNT(*) FROM stock_movements WHERE ref_id = ? AND type IN ('reserve','release','pos_sale')", [$q1]) === 3
        && Database::fetchValue("SELECT actor_type FROM order_status_logs WHERE order_id = ? ORDER BY id DESC LIMIT 1", [$q1]) === 'system'
        && (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue WHERE template_key = 'order_paid' AND created_at > NOW() - INTERVAL 5 MINUTE") === 0);

    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 3, 'variant_id' => 0, 'qty' => 1]], 'online');
    $q2 = $res['data']['receipt']['order_id'];
    $code2 = Database::fetchValue("SELECT gateway_ref FROM payments WHERE order_id = ?", [$q2]);
    setBill($fakeBase, $code2, 'paid');
    $hook = webhook($base, 'toyyibpay', 1, ['billcode' => $code2, 'status' => '1', 'order_id' => $res['data']['payment']['reference']]);
    $hook2 = webhook($base, 'toyyibpay', 1, ['billcode' => $code2, 'status' => '1', 'order_id' => $res['data']['payment']['reference']]);
    check('Webhook gateway → jualan selesai; webhook berganda → satu pos_sale sahaja', $hook === 200 && $hook2 === 200 && order($q2)['status'] === 'collected'
        && (int) Database::fetchValue("SELECT COUNT(*) FROM stock_movements WHERE ref_id = ? AND type = 'pos_sale'", [$q2]) === 1
        && (int) Database::fetchValue("SELECT COUNT(*) FROM payments WHERE order_id = ? AND status = 'paid'", [$q2]) === 1);

    Database::execute("UPDATE promotions SET is_active = 1 WHERE id = 1");
    $used = (int) Database::fetchValue("SELECT used_count FROM promotions WHERE id = 1");
    $before = level(1);
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 1, 'variant_id' => 0, 'qty' => 30]], 'online', ['coupon_code' => 'JIMAT5']);
    $q3 = $res['data']['receipt']['order_id'];
    $cancel = $cashier1->post('vendor/pos/cancel-payment', $c1 + ['order_id' => $q3]);
    check('Batal QR: cancelled, tahanan & guna promosi dilepas, bil failed', ok($res) && ok($cancel) && order($q3)['status'] === 'cancelled'
        && level(1) === $before && (int) Database::fetchValue("SELECT used_count FROM promotions WHERE id = 1") === $used
        && Database::fetchValue("SELECT status FROM payments WHERE order_id = ?", [$q3]) === 'failed');
    $again = $cashier1->post('vendor/pos/cancel-payment', $c1 + ['order_id' => $q3]);
    check('Batal kali kedua → tidak lagi menunggu', !ok($again) && $again['error'] === t('pos.payment_not_pending'));
    Database::execute("UPDATE promotions SET is_active = 0");
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 3, 'variant_id' => 0, 'qty' => 2]], 'online');
    $q4 = $res['data']['receipt']['order_id'];
    setBill($fakeBase, Database::fetchValue("SELECT gateway_ref FROM payments WHERE order_id = ?", [$q4]), 'paid');
    $cancel = $cashier1->post('vendor/pos/cancel-payment', $c1 + ['order_id' => $q4]);
    check('Batal selepas pembeli sudah bayar → disemak ke gateway, jualan diselesaikan (bukan dibatal)', !ok($cancel)
        && $cancel['error'] === t('pos.payment_not_pending') && order($q4)['status'] === 'collected');
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 3, 'variant_id' => 0, 'qty' => 1]], 'online');
    $q5 = $res['data']['receipt']['order_id'];
    Database::execute("UPDATE orders SET expires_at = NOW() - INTERVAL 1 MINUTE WHERE id = ?", [$q5]);
    $expire = (new ExpirePendingPaymentsJob())->run();
    check('Cron luput: QR POS tidak dibayar → expired, tahanan dilepas', $expire['expired'] >= 1 && order($q5)['status'] === 'expired'
        && level(3)[1] === 0);
    Database::execute("UPDATE payment_gateways SET is_enabled_for_vendors = 1 WHERE code = 'ipay88'");
    $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'ipay88', 'mode' => 'sandbox',
        'credentials' => ['merchant_code' => 'M00001', 'merchant_key' => 'ip-key']]);
    $count = (int) Database::fetchValue("SELECT COUNT(*) FROM orders WHERE channel = 'pos' AND status = 'pending_payment'");
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 3, 'variant_id' => 0, 'qty' => 1]], 'online');
    check('Gateway borang POST (iPay88) → QR kaunter ditolak, jualan dibatalkan serta-merta', !ok($res) && $res['error'] === t('pos.online_qr_unsupported')
        && (int) Database::fetchValue("SELECT COUNT(*) FROM orders WHERE channel = 'pos' AND status = 'pending_payment'") === $count && level(3)[1] === 0);
    Database::execute("UPDATE cooperative_gateways SET is_active = (gateway_code = 'toyyibpay') WHERE cooperative_id = 1");

    echo "9. Jualan syif, resit & tunai pesanan store di kaunter\n";
    $sales = $cashier1->get('vendor/pos/sales', $c1 + ['shift_id' => $shiftId, 'per_page' => 100]);
    check('sales: semua jualan syif (terbaru dahulu) dengan status & bilangan item', ok($sales) && $sales['data']['total'] === 10
        && $sales['data']['items'][count($sales['data']['items']) - 1]['order_id'] === $o1 && $sales['data']['items'][count($sales['data']['items']) - 1]['item_count'] === 3);
    $receipt = $cashier1->get('vendor/pos/receipt', $c1 + ['order_id' => $o2]);
    check('receipt: salinan resit jualan', ok($receipt) && $receipt['data']['order_no'] === 'POS-' . date('ymd') . '-002');
    $storeOrder = (int) Database::fetchValue("SELECT id FROM orders WHERE channel = 'online' AND cooperative_id = 1 ORDER BY id LIMIT 1");
    check('receipt / payment-status untuk pesanan store (bukan POS) → 404', $cashier1->status('vendor/pos/receipt', $c1 + ['order_id' => $storeOrder]) === 404
        && $cashier1->status('vendor/pos/payment-status', $c1 + ['order_id' => $storeOrder]) === 404);
    // Pesanan tunai store ibu bapa A dibayar di skrin serah peranti Kasim semasa juruwang PIN log masuk
    Database::execute("DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.customer_id = 1");
    $parentA->post('store/cart/add', ['slug' => 'sk-taman-melati', 'product_id' => 1, 'qty' => 1, 'child_id' => 1]);
    $placed = $parentA->post('store/checkout/place', ['slug' => 'sk-taman-melati', 'pickup_by_type' => 'student', 'payment_method' => 'cash']);
    $storeId = $placed['data']['order_id'] ?? 0;
    $paid = $cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $storeId, 'amount_received' => 500]);
    check('Tunai pesanan store di kaunter → received_by juruwang PIN (ReceiveCashPaymentJob)', ok($paid)
        && (int) Database::fetchValue("SELECT received_by FROM payments WHERE order_id = ?", [$storeId]) === 2);
    $summary = $cashier1->get('vendor/pos/shift-summary', $c1 + ['shift_id' => $shiftId]);
    $cashSales = (int) Database::fetchValue("SELECT SUM(total) FROM orders WHERE pos_shift_id = ? AND payment_method = 'cash' AND paid_at IS NOT NULL", [$shiftId]);
    check('Ringkasan syif: jualan tunai/online, tunai pesanan store, jangkaan = permulaan + tunai POS + tunai store',
        ok($summary) && $summary['data']['cash_count'] === 4 && $summary['data']['cash_sales'] === $cashSales && $summary['data']['online_count'] === 3
        && $summary['data']['order_cash_count'] === 1 && $summary['data']['order_cash_total'] === 180
        && $summary['data']['expected_cash'] === 10000 + $cashSales + 180 && $summary['data']['pending_count'] === 0);

    echo "10. Tutup syif\n";
    $res = $v1->post('vendor/pos/shift-close', $c1 + ['shift_id' => $shiftId, 'closing_cash' => 0]);
    pinLogin($v1, $c1, 1, '1234');
    $other = $v1->post('vendor/pos/shift-close', $c1 + ['shift_id' => $shiftId, 'closing_cash' => 0]);
    check('Syif juruwang lain tidak boleh ditutup (404)', !ok($res) && !ok($other) && $v1->postStatus('vendor/pos/shift-close', $c1 + ['shift_id' => $shiftId, 'closing_cash' => 0]) === 404);
    $expected = $summary['data']['expected_cash'];
    $closed = $cashier1->post('vendor/pos/shift-close', $c1 + ['shift_id' => $shiftId, 'closing_cash' => $expected - 50, 'note' => 'Kurang 50 sen']);
    check('Tutup: variance = dikira − jangkaan (−50), nota, jangkaan dibekukan', ok($closed) && $closed['data']['status'] === 'closed'
        && $closed['data']['variance'] === -50 && $closed['data']['closing_cash'] === $expected - 50 && $closed['data']['note'] === 'Kurang 50 sen'
        && (int) Database::fetchValue("SELECT expected_cash FROM pos_shifts WHERE id = ?", [$shiftId]) === $expected);
    $session = $cashier1->get('vendor/pos/session', $c1);
    check('Juruwang dilog keluar selepas tutup syif + log aktiviti pos_shift_open/close', $session['data']['cashier'] === null
        && (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE vendor_user_id = 2 AND action IN ('pos_shift_open', 'pos_shift_close')") === 2);
    pinLogin($cashier1, $c1, 2, '2468');
    $res = sale($cashier1, $c1, $shiftId, [['product_id' => 1, 'variant_id' => 0, 'qty' => 1]]);
    check('Selepas tutup syif → jualan ditolak (tiada syif)', !ok($res) && $res['error'] === t('pos.error.no_shift'));

    echo "11. Serentak — stok terakhir\n";
    $shiftK = $cashier1->post('vendor/pos/shift-open', $c1 + ['opening_cash' => 0])['data']['id'];
    $shiftA = $v1->post('vendor/pos/shift-open', $c1 + ['opening_cash' => 0])['data']['id'];
    setStock(3, 1);
    $results = parallelPost([
        [$cashier1, 'vendor/pos/checkout', $c1 + ['shift_id' => $shiftK, 'items' => [['product_id' => 3, 'variant_id' => 0, 'qty' => 1]], 'payment_method' => 'cash', 'amount_received' => 100]],
        [$v1, 'vendor/pos/checkout', $c1 + ['shift_id' => $shiftA, 'items' => [['product_id' => 3, 'variant_id' => 0, 'qty' => 1]], 'payment_method' => 'cash', 'amount_received' => 100]],
    ]);
    check('Dua kaunter jual unit terakhir serentak → tepat satu berjaya, stok 0 (tidak negatif)', count(array_filter($results, 'ok')) === 1 && level(3) === [0, 0]);

    echo "12. Promosi (vendor/promotions/*)\n";
    $list = $v1->get('vendor/promotions/list', $c1);
    check('list: promosi koperasi 1 sahaja + state dikira pelayan', ok($list) && $list['data']['total'] === 2
        && !in_array('SI10', array_column($list['data']['items'], 'code'), true) && $list['data']['items'][0]['state'] === 'inactive'
        && !isset($list['data']['items'][0]['vendor_id']));
    $res = $v1->post('vendor/promotions/save', $c1 + ['name' => '', 'code' => 'a b', 'type' => 'percent', 'value' => 150, 'starts_on' => '2026-12-01',
        'ends_on' => '2026-11-01', 'max_uses' => 0]);
    check('Pengesahan: nama, format kod, % 1–100, tamat ≥ mula, had guna ≥ 1', !ok($res) && isset($res['errors']['name'], $res['errors']['code'],
        $res['errors']['value'], $res['errors']['ends_on'], $res['errors']['max_uses']));
    $dup = $v1->post('vendor/promotions/save', $c1 + ['name' => 'X', 'code' => 'jimat5', 'type' => 'fixed', 'value' => 100, 'starts_on' => date('Y-m-d'),
        'ends_on' => date('Y-m-d')]);
    check('Kod pendua dalam koperasi (huruf kecil → besar) → ditolak', !ok($dup) && $dup['errors']['code'] === t('error.duplicate'));
    $new = $v1->post('vendor/promotions/save', $c1 + ['name' => 'Cuti sekolah', 'code' => 'cuti-26', 'type' => 'fixed', 'value' => 300, 'min_spend' => 2000,
        'starts_on' => date('Y-m-d'), 'ends_on' => date('Y-m-d', strtotime('+7 days')), 'max_uses' => null, 'is_active' => true]);
    check('Simpan: kod huruf besar, state active, log aktiviti', ok($new) && $new['data']['code'] === 'CUTI-26' && $new['data']['state'] === 'active'
        && $new['data']['max_uses'] === null && (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE action = 'promotion_save' AND entity_id = ?", [$new['data']['id']]) === 1);
    $filtered = $v1->get('vendor/promotions/list', $c1 + ['state' => 'active']);
    check('Tapisan state=active', ok($filtered) && array_column($filtered['data']['items'], 'code') === ['CUTI-26']);
    $catalog = $cashier1->get('vendor/pos/catalog', $c1);
    check('Promosi baharu terus dipakai POS (katalog)', in_array('CUTI-26', array_column($catalog['data']['promotions'], 'code'), true));
    $del = $v1->post('vendor/promotions/delete', $c1 + ['id' => 1]);
    $delNew = $v1->post('vendor/promotions/delete', $c1 + ['id' => $new['data']['id']]);
    check('Padam: promosi pernah digunakan ditolak; belum digunakan dipadam', !ok($del) && $del['error'] === t('promo.delete_used') && ok($delNew)
        && Database::fetchValue("SELECT 1 FROM promotions WHERE id = ?", [$new['data']['id']]) === null);
    check('VENDOR02 → promosi koperasi 1 (list/save/delete) → 404', $v2->status('vendor/promotions/list', $c1) === 404
        && $v2->postStatus('vendor/promotions/save', $c1 + ['id' => 1, 'name' => 'X']) === 404 && $v2->postStatus('vendor/promotions/delete', $c1 + ['id' => 1]) === 404);
    check('VENDOR02 → id promosi V01 melalui koperasinya → 404 (tiada perubahan)', $v2->postStatus('vendor/promotions/delete', $c3 + ['id' => 1]) === 404
        && $v2->postStatus('vendor/promotions/save', $c3 + ['id' => 1, 'name' => 'Curi', 'type' => 'fixed', 'value' => 1, 'starts_on' => '2026-01-01', 'ends_on' => '2026-01-02']) === 404
        && Database::fetchValue("SELECT name FROM promotions WHERE id = 1") === 'Jimat RM5');
    check('Pengurus VENDOR02 (koperasi 2 sahaja) → promosi koperasi 3 → 404; koperasi 2 → 200', $manager2->status('vendor/promotions/list', $c3) === 404
        && $manager2->status('vendor/promotions/list', $c2) === 200);

    echo "13. Kebocoran VENDOR01 ↔ VENDOR02 & ibu bapa A ↔ B\n";
    check('VENDOR02 → pos/session, catalog, pin-login koperasi 1 → 404', $v2->status('vendor/pos/session', $c1) === 404
        && $v2->status('vendor/pos/catalog', $c1) === 404 && $v2->postStatus('vendor/pos/pin-login', $c1 + ['user_id' => 3, 'pin' => '1234']) === 404);
    pinLogin($cashier2, $c3, 5, '1234');
    $shift3 = $cashier2->post('vendor/pos/shift-open', $c3 + ['opening_cash' => 0]);
    check('Juruwang VENDOR02 log masuk PIN koperasi 3 & buka syif', ok($shift3));
    check('Juruwang VENDOR02 → resit / status / batal / jualan syif V01 melalui koperasi 3 → 404', $cashier2->status('vendor/pos/receipt', $c3 + ['order_id' => $o1]) === 404
        && $cashier2->status('vendor/pos/payment-status', $c3 + ['order_id' => $q1]) === 404
        && $cashier2->postStatus('vendor/pos/cancel-payment', $c3 + ['order_id' => $q1]) === 404
        && $cashier2->status('vendor/pos/sales', $c3 + ['shift_id' => $shiftId]) === 404
        && $cashier2->status('vendor/pos/shift-summary', $c3 + ['shift_id' => $shiftId]) === 404);
    $res = sale($cashier2, $c3, $shift3['data']['id'], [['product_id' => 1, 'variant_id' => 0, 'qty' => 1]]);
    check('Juruwang VENDOR02 jual produk V01 → ditolak', !ok($res) && $res['error'] === t('pos.error.invalid_item'));
    $res = sale($cashier2, $c3, $shiftId, [['product_id' => 9, 'variant_id' => 0, 'qty' => 1]]);
    check('Syif V01 dalam checkout V02 → tiada syif', !ok($res) && $res['error'] === t('pos.error.no_shift'));
    $res = sale($cashier2, $c3, $shift3['data']['id'], [['product_id' => 9, 'variant_id' => 0, 'qty' => 1]], 'cash', ['amount_received' => 320]);
    check('Jualan V02: no. resit bermula 001 di koperasi 3, baris vendor 2', ok($res) && $res['data']['receipt']['order_no'] === 'POS-' . date('ymd') . '-001'
        && (int) order($res['data']['receipt']['order_id'])['vendor_id'] === 2);
    $cat2 = $cashier2->get('vendor/pos/catalog', $c3);
    check('Katalog V02 tanpa produk/promosi V01', ok($cat2) && !array_intersect([1, 2, 3, 4, 5], array_column($cat2['data']['products'], 'id'))
        && !in_array('JIMAT5', array_column($cat2['data']['promotions'], 'code'), true));
    check('Juruwang VENDOR01 → koperasi 3 → 404; koperasi 2 (tiada akses) → 404', $cashier1->status('vendor/pos/session', $c3) === 404
        && $cashier1->status('vendor/pos/session', $c2) === 404);
    pinLogin($v2, $c3, 3, '1234');
    check('Sesi PIN koperasi 3 tidak sah untuk koperasi 2 (pentadbir V02) → perlu PIN', $v2->status('vendor/pos/sales', $c3 + ['shift_id' => 0]) === 404
        && $v2->postStatus('vendor/pos/shift-open', $c2 + ['opening_cash' => 0]) === 403);
    check('Ibu bapa (sesi store) → laluan POS → 401', $parentA->status('vendor/pos/session', $c1) === 401 && $parentB->status('vendor/pos/catalog', $c3) === 401);
    $ordersA = $parentA->get('store/orders/list', ['slug' => 'sk-taman-melati', 'per_page' => 100]);
    $ordersB = $parentB->get('store/orders/list', ['slug' => 'smk-seri-indah', 'per_page' => 100]);
    check('Jualan POS tidak muncul dalam pesanan ibu bapa A atau B', ok($ordersA) && ok($ordersB)
        && !in_array($o1, array_column($ordersA['data']['items'], 'id'), true) && !in_array($res['data']['receipt']['order_id'], array_column($ordersB['data']['items'], 'id'), true));
    check('Ibu bapa B tidak boleh membuka pesanan store ibu bapa A', $parentB->status('store/orders/get', ['slug' => 'sk-taman-melati', 'id' => $storeId]) === 404);

    echo "14. Halaman & frontend\n";
    $page = $cashier1->page('vendor/pos/index.php');
    check('Juruwang: vendor/pos/index.php 200, tiada teks PIN demo', $page['status'] === 200 && strpos($page['body'], 'PIN 1234') === false);
    check('Pentadbir: vendor/promotions.php 200; juruwang → dialih', $v1->page('vendor/promotions.php')['status'] === 200
        && $cashier1->page('vendor/promotions.php')['status'] === 302);
    $api = file_get_contents(APP_ROOT . '/assets/js/api.js');
    check("Api.livePrefixes: 'vendor/pos/', 'vendor/promotions/'", apiRouteLive($api, "'vendor/pos/', 'vendor/promotions/'"));
    $mock = '';
    foreach (glob(APP_ROOT . '/assets/js/mock/*.js') as $file) {
        $mock .= file_get_contents($file);
    }
    $finance = (string) @file_get_contents(APP_ROOT . '/assets/js/mock/vendor-finance.js'); // dibuang B12
    check('Mock POS dibuang (vendor-pos.js, Mock.register vendor/pos|promotions, MockFinance.settings & promotions)',
        !is_file(APP_ROOT . '/assets/js/mock/vendor-pos.js') && !preg_match("#Mock\.register\('vendor/(pos|promotions)/#", $mock)
        && strpos($finance, '    settings: {') === false && strpos($finance, '    promotions: [') === false);
    $posJs = file_get_contents(APP_ROOT . '/assets/js/vendor/pos.js');
    check('pos.js: juruwang dari session.cashiers, promosi dari katalog (tiada vendor/staff/list / promotions/list)',
        strpos($posJs, 'vendor/staff/list') === false && strpos($posJs, 'vendor/promotions/list') === false && strpos($posJs, 'session.cashiers') !== false);
} finally {
    proc_terminate($server);
    proc_terminate($fake);
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
    Database::execute("UPDATE products SET is_active = 1 WHERE id = 5");
    Database::execute('SET FOREIGN_KEY_CHECKS = 1');
    clearTestLocks();
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
