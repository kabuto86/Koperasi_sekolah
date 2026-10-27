<?php
// Semakan B7 — checkout tunai, pesanan ibu bapa, promosi, slot, queue e-mel & cron
// + ujian kebocoran ibu bapa A ↔ B dan VENDOR01 ↔ VENDOR02, dan dua checkout serentak untuk stok terakhir. CLI sahaja:
//   php tools/verify-b7.php
// Perlu: DB koperasi_saas (schema + seed), config/database.php, config/secret.key, sambungan PHP curl.
// Skrip memulakan pelayan PHP sendiri (php -S, 4 pekerja) + pelayan SMTP palsu (tools/fake-smtp.php).
// Jadual yang diubah dipulihkan di hujung.
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

function reserved($productId, $variantId = 0, $cooperativeId = 1)
{
    return (int) Database::fetchValue(
        "SELECT qty_reserved FROM stock_levels WHERE cooperative_id = ? AND product_id = ? AND variant_id = ?",
        [$cooperativeId, $productId, $variantId]
    );
}

function cartCount($customerId, $cooperativeId)
{
    return (int) Database::fetchValue(
        "SELECT COUNT(*) FROM cart_items ci JOIN carts ca ON ca.id = ci.cart_id WHERE ca.customer_id = ? AND ca.cooperative_id = ?",
        [$customerId, $cooperativeId]
    );
}

function emptyCarts()
{
    Database::execute('DELETE FROM cart_items');
}

$port = 8098;
$smtpPort = 2526;
$base = 'http://127.0.0.1:' . $port;
$log = sys_get_temp_dir() . '/kop-verify-b7.log';
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4'] + getenv());
$mailDir = sys_get_temp_dir() . '/kop-verify-b7-mail';
@mkdir($mailDir);
array_map('unlink', glob($mailDir . '/*.eml'));
$smtp = proc_open([PHP_BINARY, __DIR__ . '/fake-smtp.php', (string) $smtpPort, $mailDir], [1 => ['file', '/dev/null', 'w'], 2 => ['file', $log . '.smtp', 'w']], $smtpPipes);
usleep(900000);

$tables = ['orders', 'order_items', 'order_status_logs', 'stock_levels', 'stock_movements', 'carts', 'cart_items', 'promotions',
    'preorder_seasons', 'pickup_slots', 'calendar_events', 'email_queue', 'platform_settings', 'cooperative_settings', 'products',
    'children', 'customers', 'cooperatives'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
emptyCarts();
Database::execute('DELETE FROM carts');
Database::execute('DELETE FROM email_queue');
// Promosi seed sentiasa berjalan semasa ujian (tarikh relatif hari ini)
Database::execute("UPDATE promotions SET starts_on = DATE_SUB(CURDATE(), INTERVAL 1 DAY), ends_on = DATE_ADD(CURDATE(), INTERVAL 30 DAY), used_count = 0");
// Ibu bapa C (ujian serentak): anak di SK Taman Melati
Database::execute(
    "INSERT INTO customers (id, full_name, phone, email, password_hash, relationship, language, email_verified_at, pdpa_consent_at, is_active)
     VALUES (90, 'Ibu Bapa C', '019-9990001', 'c@contoh.my', ?, 'mother', 'ms', NOW(), NOW(), 1)",
    [Password::DUMMY_HASH]
);
Database::execute(
    "INSERT INTO children (id, customer_id, cooperative_id, vendor_id, full_name, grade_level_code, school_class_id, class_name, school_year, is_active)
     VALUES (90, 90, 1, 1, 'Anak C', 'D4', 3, 'Bestari', YEAR(CURDATE()), 1)"
);

try {
    $guest = new Browser($base);
    list($parentA, $loginA) = storeLogin($base, 'siti@contoh.my');   // Aisyah (1, D4) & Adam (2, D1) di SK Taman Melati, Faris (3) di Chung Hwa
    list($parentB, $loginB) = storeLogin($base, 'rajesh@contoh.my'); // Arjun (4) di SMK Seri Indah
    list($parentC, $loginC) = storeLogin($base, 'c@contoh.my');
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $melati = ['slug' => 'sk-taman-melati'];
    $indah = ['slug' => 'smk-seri-indah'];
    $chungHwa = ['slug' => 'sjkc-chung-hwa'];

    echo "1. Akses & kaedah (PELAN 10.2)\n";
    check('Log masuk ibu bapa A, B & C', ok($loginA) && ok($loginB) && ok($loginC));
    $needLogin = 0;
    foreach (['store/checkout/quote', 'store/orders/list', 'store/orders/get'] as $route) {
        $needLogin += !empty($guest->get($route, $melati + ['id' => 1])['auth_required']) ? 1 : 0;
    }
    check('Tanpa sesi → quote, orders/list, orders/get auth_required', $needLogin === 3);
    check('Tanpa sesi → checkout/place auth_required', !empty($guest->post('store/checkout/place', $melati)['auth_required']));
    check('Sesi vendor (bukan ibu bapa) → orders/list auth_required', !empty($v1->get('store/orders/list', $melati)['auth_required']));
    check('POST tanpa X-CSRF-Token → 419', $parentA->postStatus('store/checkout/place', $melati, false) === 419
        && $parentA->postStatus('store/orders/cancel', $melati + ['id' => 1], false) === 419);
    check('GET ke endpoint POST → 405', $parentA->getStatus('store/checkout/place') === 405 && $parentA->getStatus('store/orders/cancel') === 405);
    check('Slug tidak wujud → 404', $parentA->status('store/checkout/quote', ['slug' => 'tiada-sekolah']) === 404);

    echo "2. Sebut harga checkout — promosi, cukai, kaedah (PELAN 18.12)\n";
    $parentA->post('store/cart/add', $melati + ['product_id' => 1, 'qty' => 3, 'child_id' => 1]);
    $quote = $parentA->get('store/checkout/quote', $melati);
    check('Troli 3 × RM1.80: subtotal 540, tiada diskaun, jumlah 540', ok($quote) && $quote['data']['subtotal'] === 540
        && $quote['data']['discount'] === 0 && $quote['data']['total'] === 540 && $quote['data']['customer_name'] === 'Siti Nurhaliza binti Ahmad');
    check('Kaedah bayaran: tunai sahaja (online perlu gateway aktif + driver B8)', ok($quote) && $quote['data']['payment_methods'] === ['cash']
        && $quote['data']['gateway_name'] === null);
    $coupon = $parentA->get('store/checkout/quote', $melati + ['coupon_code' => 'jimat5']);
    check('Kupon JIMAT5 (belanja min RM50) di bawah minimum → coupon_error, diskaun 0', ok($coupon) && $coupon['data']['coupon_error'] !== null
        && strpos($coupon['data']['coupon_error'], 'RM 50.00') !== false && $coupon['data']['discount'] === 0);
    check('Kupon tidak wujud → coupon_error', $parentA->get('store/checkout/quote', $melati + ['coupon_code' => 'TIADA'])['data']['coupon_error'] !== null);
    check('Kupon vendor lain (SI10 milik SMK Seri Indah) → tidak sah di SK Taman Melati',
        $parentA->get('store/checkout/quote', $melati + ['coupon_code' => 'SI10'])['data']['coupon_error'] !== null);
    $parentA->post('store/cart/add', $melati + ['product_id' => 2, 'qty' => 5, 'child_id' => 1]);
    $parentA->post('store/cart/add', $melati + ['product_id' => 4, 'variant_id' => 2, 'qty' => 2, 'child_id' => 2]);
    $coupon = $parentA->get('store/checkout/quote', $melati + ['coupon_code' => 'JIMAT5']);
    check('Subtotal 8790 + kupon JIMAT5 → diskaun 500, jumlah 8290', ok($coupon) && $coupon['data']['subtotal'] === 8790
        && $coupon['data']['discount'] === 500 && $coupon['data']['promotion_code'] === 'JIMAT5' && $coupon['data']['total'] === 8290
        && $coupon['data']['coupon_error'] === null);
    Database::execute("UPDATE cooperative_settings SET setting_value = '6' WHERE cooperative_id = 1 AND setting_key = 'tax_rate'");
    $taxed = $parentA->get('store/checkout/quote', $melati + ['coupon_code' => 'JIMAT5']);
    check('Cukai 6% dikira SELEPAS diskaun: round(8290 × 6%) = 497, jumlah 8787', ok($taxed) && $taxed['data']['tax'] === 497
        && $taxed['data']['total'] === 8787);
    Database::execute("UPDATE cooperative_settings SET setting_value = '0' WHERE cooperative_id = 1 AND setting_key = 'tax_rate'");
    $parentA->post('store/cart/add', $melati + ['product_id' => 1, 'qty' => 67, 'child_id' => 2]);
    $auto = $parentA->get('store/checkout/quote', $melati);
    $expectedAuto = (int) round(20850 * 3 / 100);
    check('Subtotal 20850 ≥ RM200 → diskaun automatik 3% (626), tanpa kod', ok($auto) && $auto['data']['subtotal'] === 20850
        && $auto['data']['discount'] === $expectedAuto && $auto['data']['promotion_name'] === 'Diskaun automatik 3%' && $auto['data']['promotion_code'] === null);
    $both = $parentA->get('store/checkout/quote', $melati + ['coupon_code' => 'JIMAT5']);
    check('Satu promosi setiap pesanan: kupon menggantikan automatik (diskaun 500)', ok($both) && $both['data']['discount'] === 500);
    check('Jumlah > RM100 (pickup_code_required_above) → requires_code', ok($both) && $both['data']['requires_code'] === true
        && $both['data']['code_required_above'] === 10000);
    check('Tiada musim pre-order / tutup → slot_required false, orders_blocked null', ok($both) && $both['data']['slot_required'] === false
        && $both['data']['pickup_slots'] === [] && $both['data']['orders_blocked'] === null);

    echo "3. Buat pesanan — pengesahan input & harga pelayar diabaikan (PELAN 16 perkara 2)\n";
    $total = $both['data']['total'];
    $orderCount = (int) Database::fetchValue("SELECT COUNT(*) FROM orders");
    $basePlace = $melati + ['pickup_by_type' => 'proxy', 'pickup_by_name' => 'Pak Long Rahim', 'payment_method' => 'cash',
        'coupon_code' => 'JIMAT5', 'pickup_code' => '1234', 'expected_total' => $total];
    Database::execute("UPDATE customers SET email_verified_at = NULL WHERE id = 1");
    $res = $parentA->post('store/checkout/place', $basePlace);
    check('E-mel belum disahkan → ditolak', !ok($res) && $res['error'] === t('store.checkout.verify_first'));
    Database::execute("UPDATE customers SET email_verified_at = NOW() WHERE id = 1");
    $res = $parentA->post('store/checkout/place', array_merge($basePlace, ['expected_total' => $total - 1]));
    check('expected_total berbeza → "Jumlah telah berubah", tiada pesanan', !ok($res) && strpos($res['error'], Money::format($total)) !== false
        && (int) Database::fetchValue("SELECT COUNT(*) FROM orders") === $orderCount);
    $res = $parentA->post('store/checkout/place', array_merge($basePlace, ['pickup_code' => '12a4']));
    check('Kod 4 digit wajib (jumlah > RM100) — "12a4" ditolak', !ok($res) && isset($res['errors']['pickup_code']));
    $res = $parentA->post('store/checkout/place', array_merge($basePlace, ['pickup_by_name' => '']));
    check('Wakil tanpa nama → errors.pickup_by_name', !ok($res) && isset($res['errors']['pickup_by_name']));
    $res = $parentA->post('store/checkout/place', array_merge($basePlace, ['pickup_by_type' => 'jiran']));
    check('Jenis pengambil tidak sah → errors.pickup_by_type', !ok($res) && isset($res['errors']['pickup_by_type']));
    $res = $parentA->post('store/checkout/place', array_merge($basePlace, ['payment_method' => 'online']));
    check('Online tanpa gateway (B8) → errors.payment_method', !ok($res) && isset($res['errors']['payment_method']));
    $res = $parentA->post('store/checkout/place', array_merge($basePlace, ['coupon_code' => 'TIADA']));
    check('Kupon tidak sah semasa place → errors.coupon_code', !ok($res) && isset($res['errors']['coupon_code']));
    check('Semua percubaan gagal: tiada pesanan, troli & stok tidak berubah', (int) Database::fetchValue("SELECT COUNT(*) FROM orders") === $orderCount
        && cartCount(1, 1) === 4 && reserved(1) === 0);
    $forged = array_merge($basePlace, ['customer_id' => 2, 'vendor_id' => 2, 'cooperative_id' => 3, 'status' => 'paid', 'total' => 1,
        'unit_price' => 1, 'discount' => 99999, 'promotion_id' => 3]);
    $placed = $parentA->post('store/checkout/place', $forged);
    $orderId = ok($placed) ? $placed['data']['order_id'] : 0;
    $row = order($orderId);
    check('Pesanan dicipta: customer/vendor/koperasi dari sesi & slug (input palsu diabaikan)', ok($placed) && $row !== null
        && (int) $row['customer_id'] === 1 && (int) $row['vendor_id'] === 1 && (int) $row['cooperative_id'] === 1);
    check('Status unpaid_cash, jumlah pelayan (20850 − 500), promosi JIMAT5', $row && $row['status'] === 'unpaid_cash' && (int) $row['total'] === 20350
        && (int) $row['subtotal'] === 20850 && (int) $row['discount'] === 500 && (int) $row['promotion_id'] === 1 && $row['paid_at'] === null);
    check('No. pesanan TM-YYMM-NNNN, kod pendek huruf + 3 digit, token QR 32 hex (dipulangkan sama)', $row
        && preg_match('/^TM-' . date('ym') . '-\d{4}$/', $row['order_no']) && preg_match('/^[A-HJ-NP-Z]\d{3}$/', $row['short_code'])
        && preg_match('/^[0-9a-f]{32}$/', $row['pickup_qr_token']) && $placed['data']['order_no'] === $row['order_no']
        && $placed['data']['short_code'] === $row['short_code'] && $placed['data']['payment'] === null);
    check('Kod 4 digit disimpan sebagai hash Argon2id (bukan teks)', $row && strpos((string) $row['pickup_code_hash'], '$argon2id$') === 0
        && password_verify('1234', $row['pickup_code_hash']));
    check('Luput tunai = hari ini + 3 hari 23:59:59; pengambil wakil "Pak Long Rahim"', $row
        && $row['expires_at'] === date('Y-m-d', strtotime('+3 days')) . ' 23:59:59' && $row['pickup_by_type'] === 'proxy'
        && $row['pickup_by_name'] === 'Pak Long Rahim');
    $items = Database::fetchAll("SELECT * FROM order_items WHERE order_id = ? ORDER BY id", [$orderId]);
    check('4 baris item, harga seunit dari DB (180, 650, 2500, 180)', array_map('intval', array_column($items, 'unit_price')) === [180, 650, 2500, 180]
        && array_sum(array_column($items, 'line_total')) === 20850);
    check('Snapshot: nama produk, varian, SKU, anak, darjah & kelas', count($items) === 4 && $items[0]['product_name'] === 'Buku Latihan Garis 80 muka'
        && $items[2]['variant_label'] === 'Saiz M' && $items[2]['sku'] === 'BJ-PL-M' && $items[0]['child_name'] === 'Aisyah binti Faizal'
        && $items[0]['grade_label'] === 'D4' && $items[0]['class_name'] === 'Bestari' && $items[2]['child_name'] === 'Adam bin Faizal'
        && $items[2]['grade_label'] === 'D1' && (int) $items[0]['vendor_id'] === 1);
    check('Stok DITAHAN (qty_reserved): buku 70, pensel 5, baju M 2; qty_on_hand tidak berubah', reserved(1) === 70 && reserved(2) === 5
        && reserved(4, 2) === 2 && (int) Database::fetchValue("SELECT qty_on_hand FROM stock_levels WHERE cooperative_id = 1 AND product_id = 1 AND variant_id = 0") === 500);
    $moves = Database::fetchAll("SELECT * FROM stock_movements WHERE type = 'reserve' AND ref_type = 'order' AND ref_id = ?", [$orderId]);
    check('3 pergerakan "reserve" (satu setiap produk+varian), qty_change 0, nota no. pesanan', count($moves) === 3
        && array_sum(array_column($moves, 'qty_change')) === 0 && $moves[0]['note'] === $row['order_no']);
    check('Troli dikosongkan; promosi used_count 1; log status unpaid_cash oleh ibu bapa', cartCount(1, 1) === 0
        && (int) Database::fetchValue("SELECT used_count FROM promotions WHERE id = 1") === 1
        && Database::fetchValue("SELECT CONCAT(actor_type, ':', to_status) FROM order_status_logs WHERE order_id = ?", [$orderId]) === 'customer:unpaid_cash');
    $mails = Database::fetchAll("SELECT * FROM email_queue ORDER BY id");
    check('E-mel dalam queue (bukan dihantar terus): amaran vendor + resit ibu bapa', count($mails) === 2 && $mails[0]['status'] === 'pending'
        && $mails[0]['template_key'] === 'order_new_vendor' && $mails[0]['to_email'] === 'ahmad@niagasekolah.my' && (int) $mails[0]['vendor_id'] === 1
        && $mails[1]['template_key'] === 'order_receipt' && $mails[1]['to_email'] === 'siti@contoh.my');
    check('Resit: no. pesanan, kod pendek, jadual barang (anak) & QR jadual HTML', count($mails) === 2
        && strpos($mails[1]['subject'], $row['order_no']) !== false && strpos($mails[1]['body_html'], $row['short_code']) !== false
        && strpos($mails[1]['body_html'], 'Adam bin Faizal') !== false && substr_count($mails[1]['body_html'], 'bgcolor="#000000"') > 100
        && strpos($mails[1]['body_html'], 'order-detail') !== false && strpos($mails[1]['body_html'], '{') === false);

    echo "4. Pesanan saya & butiran (StoreOrderDetail)\n";
    $detail = $parentA->get('store/orders/get', $melati + ['id' => $orderId]);
    check('Butiran: kumpulan ikut anak (Aisyah 2 item, Adam 2 item), jumlah & diskaun', ok($detail) && count($detail['data']['groups']) === 2
        && count($detail['data']['groups'][0]['items']) === 2 && $detail['data']['total'] === 20350 && $detail['data']['promotion_name'] === 'Jimat RM5');
    check('Status bayaran "unpaid", boleh batal, tidak boleh bayar online', ok($detail) && $detail['data']['payment_status'] === 'unpaid'
        && $detail['data']['can_cancel'] === true && $detail['data']['can_pay'] === false);
    check('requires_code true tetapi hash / kod tidak dipulangkan; QR token dipulangkan', ok($detail) && $detail['data']['requires_code'] === true
        && !isset($detail['data']['pickup_code_hash']) && !isset($detail['data']['pickup_code']) && $detail['data']['qr_token'] === $row['pickup_qr_token']
        && strpos(json_encode($detail), '$argon2id') === false);
    check('Log untuk ibu bapa: status & masa sahaja (tiada actor)', ok($detail) && $detail['data']['logs'][0] === ['to_status' => 'unpaid_cash',
        'created_at' => IsoDate::format($row['created_at'])]);
    Database::execute("UPDATE products SET name = 'Buku Nama Baharu', price = 999 WHERE id = 1");
    Database::execute("UPDATE children SET grade_level_code = 'D5', class_name = 'Cemerlang' WHERE id = 1");
    $detail = $parentA->get('store/orders/get', $melati + ['id' => $orderId]);
    check('Produk ditukar nama/harga & anak naik darjah → pesanan kekal snapshot (PELAN 16 perkara 6)', ok($detail)
        && $detail['data']['groups'][0]['grade_level_code'] === 'D4' && $detail['data']['groups'][0]['class_name'] === 'Bestari'
        && $detail['data']['groups'][0]['items'][0]['product_name'] === 'Buku Latihan Garis 80 muka' && $detail['data']['groups'][0]['items'][0]['unit_price'] === 180);
    Database::execute("UPDATE products SET name = 'Buku Latihan Garis 80 muka', price = 180 WHERE id = 1");
    Database::execute("UPDATE children SET grade_level_code = 'D4', class_name = 'Bestari' WHERE id = 1");
    $list = $parentA->get('store/orders/list', $melati);
    check('Senarai: 1 pesanan, anak (2) & tahun untuk tapisan, ready_count 0', ok($list) && $list['data']['total'] === 1
        && count($list['data']['children']) === 2 && $list['data']['years'] === [(int) date('Y')] && $list['data']['ready_count'] === 0
        && $list['data']['items'][0]['item_count'] === 77);
    check('Tapis anak Adam → 1; anak lain (Faris) → 0; tahun lepas → 0', $parentA->get('store/orders/list', $melati + ['child_id' => 2])['data']['total'] === 1
        && $parentA->get('store/orders/list', $melati + ['child_id' => 3])['data']['total'] === 0
        && $parentA->get('store/orders/list', $melati + ['year' => (int) date('Y') - 1])['data']['total'] === 0);

    echo "5. Kebocoran ibu bapa A ↔ B & koperasi (PELAN 8.1, 8.5)\n";
    check('B baca pesanan A → 404', $parentB->status('store/orders/get', $melati + ['id' => $orderId]) === 404);
    $res = $parentB->post('store/orders/cancel', $melati + ['id' => $orderId]);
    check('B batal pesanan A → ditolak, pesanan A kekal unpaid_cash', !ok($res) && order($orderId)['status'] === 'unpaid_cash');
    check('B bayar pesanan A → ditolak', !ok($parentB->post('store/orders/pay', $melati + ['id' => $orderId])));
    check('B senarai di SK Taman Melati → 0; di SMK Seri Indah → 0', $parentB->get('store/orders/list', $melati)['data']['total'] === 0
        && $parentB->get('store/orders/list', $indah)['data']['total'] === 0);
    check('A baca pesanan sendiri melalui slug koperasi lain → 404', $parentA->status('store/orders/get', $chungHwa + ['id' => $orderId]) === 404
        && $parentA->get('store/orders/list', $chungHwa)['data']['total'] === 0);
    check('Id pesanan tidak wujud / "1 OR 1=1" → 404', $parentA->status('store/orders/get', $melati + ['id' => 999999]) === 404
        && $parentA->status('store/orders/get', $melati + ['id' => '1 OR 1=1']) === 404);

    echo "6. Batal pesanan — lepas tahanan (PELAN 16 perkara 4)\n";
    $res = $parentA->post('store/orders/pay', $melati + ['id' => $orderId]);
    check('Bayar pesanan tunai → "tidak menunggu bayaran"', !ok($res) && $res['error'] === t('store.order.not_payable'));
    $cancel = $parentA->post('store/orders/cancel', $melati + ['id' => $orderId]);
    check('Batal → cancelled, cancelled_at, can_cancel false', ok($cancel) && $cancel['data']['status'] === 'cancelled'
        && $cancel['data']['can_cancel'] === false && order($orderId)['cancelled_at'] !== null && $cancel['data']['payment_status'] === 'none');
    check('Tahanan dilepaskan (0), 3 pergerakan "release", promosi used_count kembali 0', reserved(1) === 0 && reserved(2) === 0 && reserved(4, 2) === 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM stock_movements WHERE type = 'release' AND ref_id = ?", [$orderId]) === 3
        && (int) Database::fetchValue("SELECT used_count FROM promotions WHERE id = 1") === 0);
    $res = $parentA->post('store/orders/cancel', $melati + ['id' => $orderId]);
    check('Batal kali kedua → ditolak (tahanan tidak dilepas dua kali)', !ok($res) && reserved(1) === 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM stock_movements WHERE type = 'release' AND ref_id = ?", [$orderId]) === 3);
    check('Log: unpaid_cash → cancelled (nota bahasa koperasi untuk kakitangan)', Database::fetchValue(
        "SELECT note FROM order_status_logs WHERE order_id = ? AND to_status = 'cancelled'", [$orderId]) === Lang::getFor('ms', 'store.order.cancelled_by_parent'));

    echo "7. Troli bermasalah, troli kosong\n";
    clearTestLocks();
    $res = $parentA->post('store/checkout/place', $melati + ['pickup_by_type' => 'student', 'payment_method' => 'cash']);
    check('Troli kosong → "Troli anda kosong"', !ok($res) && $res['error'] === t('store.cart.empty'));
    $parentA->post('store/cart/add', $melati + ['product_id' => 4, 'variant_id' => 3, 'qty' => 2, 'child_id' => 2]);
    $line = Database::fetchValue("SELECT ci.id FROM cart_items ci JOIN carts ca ON ca.id = ci.cart_id WHERE ca.customer_id = 1 AND ca.cooperative_id = 1");
    $parentA->post('store/cart/update', $melati + ['id' => $line, 'qty' => 5]);
    $res = $parentA->post('store/checkout/place', $melati + ['pickup_by_type' => 'student', 'payment_method' => 'cash']);
    check('Kuantiti melebihi stok (Saiz L baki 4) → "betulkan troli", tiada pesanan', !ok($res) && $res['error'] === t('store.checkout.fix_cart')
        && reserved(4, 3) === 0);
    emptyCarts();

    echo "8. Slot ambil (modul school_opening) & tutup tempahan (modul calendar)\n";
    Database::execute("INSERT INTO preorder_seasons (id, vendor_id, cooperative_id, school_year, opens_at, closes_at, pickup_starts_on)
        VALUES (90, 1, 1, 2030, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY))");
    Database::execute("INSERT INTO pickup_slots (id, vendor_id, cooperative_id, preorder_season_id, slot_date, time_start, time_end, grade_level_code, capacity, booked) VALUES
        (91, 1, 1, 90, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:00:00', '10:00:00', 'D1', 1, 0),
        (92, 1, 1, 90, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '08:00:00', '10:00:00', NULL, 5, 0),
        (93, 1, 1, 90, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', '12:00:00', 'T2', 5, 0),
        (94, 1, 1, 90, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '10:00:00', NULL, 5, 0)");
    $parentA->post('store/cart/add', $melati + ['product_id' => 3, 'qty' => 1, 'child_id' => 2]);
    $slotQuote = $parentA->get('store/checkout/quote', $melati);
    check('Slot ikut darjah troli (D1 + semua darjah), tanpa slot T2 & slot yang sudah lepas', ok($slotQuote) && $slotQuote['data']['slot_required'] === true
        && array_column($slotQuote['data']['pickup_slots'], 'id') === [91, 92] && $slotQuote['data']['pickup_slots'][0]['time_start'] === '08:00'
        && $slotQuote['data']['pickup_slots'][0]['remaining'] === 1);
    $slotPlace = $melati + ['pickup_by_type' => 'student', 'payment_method' => 'cash'];
    $res = $parentA->post('store/checkout/place', $slotPlace);
    check('Slot wajib tetapi tidak dipilih → errors.pickup_slot_id', !ok($res) && isset($res['errors']['pickup_slot_id']));
    $res = $parentA->post('store/checkout/place', $slotPlace + ['pickup_slot_id' => 93]);
    check('Slot darjah lain (T2) → ditolak', !ok($res) && isset($res['errors']['pickup_slot_id']));
    $slotOrder = $parentA->post('store/checkout/place', $slotPlace + ['pickup_slot_id' => 91]);
    $slotRow = ok($slotOrder) ? order($slotOrder['data']['order_id']) : null;
    check('Slot D1 dipilih → booked 1, salinan slot dalam pesanan', $slotRow !== null && (int) $slotRow['pickup_slot_id'] === 91
        && (int) Database::fetchValue("SELECT booked FROM pickup_slots WHERE id = 91") === 1
        && json_decode($slotRow['pickup_slot_json'], true)['time_start'] === '08:00' && $slotRow['pickup_by_name'] === 'Adam bin Faizal');
    check('Pengambil "murid sendiri" → nama anak; kod tidak wajib (jumlah kecil) → tiada hash', $slotRow !== null && $slotRow['pickup_by_type'] === 'student'
        && $slotRow['pickup_code_hash'] === null);
    $slotDetail = $parentA->get('store/orders/get', $melati + ['id' => $slotRow ? $slotRow['id'] : 0]);
    check('Butiran pesanan memaparkan slot', ok($slotDetail) && $slotDetail['data']['pickup_slot']['slot_date'] === date('Y-m-d', strtotime('+2 days')));
    $parentA->post('store/cart/add', $melati + ['product_id' => 3, 'qty' => 1, 'child_id' => 2]);
    $res = $parentA->post('store/checkout/place', $slotPlace + ['pickup_slot_id' => 91]);
    check('Slot penuh (kapasiti 1) → "slot sudah penuh", booked kekal 1', !ok($res) && $res['errors']['pickup_slot_id'] === t('store.slot.full')
        && (int) Database::fetchValue("SELECT booked FROM pickup_slots WHERE id = 91") === 1);
    $parentA->post('store/orders/cancel', $melati + ['id' => $slotRow ? $slotRow['id'] : 0]);
    check('Pesanan dibatal → tempahan slot dilepaskan (booked 0)', (int) Database::fetchValue("SELECT booked FROM pickup_slots WHERE id = 91") === 0);
    Database::execute("UPDATE orders SET pickup_slot_id = NULL WHERE pickup_slot_id BETWEEN 91 AND 94");
    Database::execute("DELETE FROM pickup_slots WHERE preorder_season_id = 90");
    Database::execute("DELETE FROM preorder_seasons WHERE id = 90");
    Database::execute("INSERT INTO calendar_events (id, vendor_id, cooperative_id, title, event_type, starts_on, ends_on, blocks_orders)
        VALUES (90, 1, 1, 'Stock take ujian', 'coop_closed', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 1)");
    $blocked = $parentA->get('store/checkout/quote', $melati);
    check('Koperasi tutup hari ini → orders_blocked { title, reopens_on }', ok($blocked) && $blocked['data']['orders_blocked']['title'] === 'Stock take ujian'
        && $blocked['data']['orders_blocked']['reopens_on'] === date('Y-m-d', strtotime('+2 days')));
    $res = $parentA->post('store/checkout/place', $slotPlace);
    check('Tempahan ditutup → ditolak', !ok($res) && strpos($res['error'], 'Stock take ujian') !== false);
    Database::execute("DELETE FROM calendar_events WHERE id = 90");
    emptyCarts();

    echo "9. Dua checkout serentak untuk stok terakhir (PELAN 16 perkara 3)\n";
    $winners = 0;
    $rounds = 3;
    for ($round = 1; $round <= $rounds; $round++) {
        clearTestLocks();
        emptyCarts();
        Database::execute("UPDATE stock_levels SET qty_on_hand = qty_reserved + 1 WHERE cooperative_id = 1 AND product_id = 5 AND variant_id = 0");
        $parentA->post('store/cart/add', $melati + ['product_id' => 5, 'qty' => 1, 'child_id' => 1]);
        $parentC->post('store/cart/add', $melati + ['product_id' => 5, 'qty' => 1, 'child_id' => 90]);
        $body = $melati + ['pickup_by_type' => 'student', 'payment_method' => 'cash'];
        $results = parallelPost([[$parentA, 'store/checkout/place', $body], [$parentC, 'store/checkout/place', $body]]);
        $okCount = count(array_filter($results, 'ok'));
        $losers = array_values(array_filter($results, function ($res) { return is_array($res) && empty($res['ok']); }));
        $level = Database::fetchOne("SELECT qty_on_hand, qty_reserved FROM stock_levels WHERE cooperative_id = 1 AND product_id = 5 AND variant_id = 0");
        // Yang kalah ditolak sama ada oleh semakan troli (baki 0 → "betulkan troli") atau semakan baris stok terkunci
        $stockError = $losers && (strpos($losers[0]['error'], 'Buku Tulisan Jawi') !== false || $losers[0]['error'] === t('store.checkout.fix_cart'));
        if ($okCount === 1 && count($losers) === 1 && (int) $level['qty_reserved'] === (int) $level['qty_on_hand'] && $stockError) {
            $winners++;
        }
    }
    check("Stok terakhir: tepat satu checkout berjaya, satu ditolak (stok), tiada lebih tahan ($winners/$rounds pusingan)", $winners === $rounds);
    $numbers = Database::fetchAll("SELECT order_no, short_code FROM orders WHERE cooperative_id = 1");
    check('No. pesanan & kod pendek unik dalam koperasi', count(array_unique(array_column($numbers, 'order_no'))) === count($numbers)
        && count(array_unique(array_column($numbers, 'short_code'))) === count($numbers));
    emptyCarts();

    echo "10. VENDOR02 (SMK Seri Indah) — skop vendor & jualan murah\n";
    clearTestLocks();
    $parentB->post('store/cart/add', $indah + ['product_id' => 9, 'qty' => 2, 'child_id' => 4]);
    $quoteB = $parentB->get('store/checkout/quote', $indah + ['coupon_code' => 'SI10']);
    check('Harga jualan murah RM2.50; kupon SI10 (min RM80) → coupon_error', ok($quoteB) && $quoteB['data']['subtotal'] === 500
        && $quoteB['data']['coupon_error'] !== null);
    $placedB = $parentB->post('store/checkout/place', $indah + ['pickup_by_type' => 'parent', 'payment_method' => 'cash']);
    $rowB = ok($placedB) ? order($placedB['data']['order_id']) : null;
    $itemB = $rowB ? Database::fetchOne("SELECT * FROM order_items WHERE order_id = ?", [$rowB['id']]) : null;
    check('Pesanan B: vendor 2, koperasi 3, no. SI-…, pengambil ibu bapa = nama B', $rowB !== null && (int) $rowB['vendor_id'] === 2
        && (int) $rowB['cooperative_id'] === 3 && strpos($rowB['order_no'], 'SI-') === 0 && $rowB['pickup_by_name'] === 'Rajesh a/l Kumar');
    check('Item jualan murah: clearance_item_id direkod, harga 250', $itemB !== null && $itemB['clearance_item_id'] !== null && (int) $itemB['unit_price'] === 250
        && (int) $itemB['vendor_id'] === 2);
    check('Luput ikut tetapan koperasi 3 (5 hari)', $rowB !== null && $rowB['expires_at'] === date('Y-m-d', strtotime('+5 days')) . ' 23:59:59');
    $alertB = Database::fetchAll("SELECT * FROM email_queue WHERE template_key = 'order_new_vendor' AND vendor_id = 2");
    check('Amaran vendor 2 → meiling@bestari.my sahaja (tiada e-mel VENDOR01)', count($alertB) === 1 && $alertB[0]['to_email'] === 'meiling@bestari.my');
    check('A tidak nampak pesanan B; B tidak nampak pesanan A', $parentA->status('store/orders/get', $indah + ['id' => $rowB ? $rowB['id'] : 0]) === 404
        && $parentB->get('store/orders/list', $indah)['data']['total'] === 1);
    $parentA->post('store/cart/add', $chungHwa + ['product_id' => 6, 'qty' => 2, 'child_id' => 3]);
    $placedZh = $parentA->post('store/checkout/place', $chungHwa + ['pickup_by_type' => 'student', 'payment_method' => 'cash', 'lang' => 'zh']);
    $itemZh = ok($placedZh) ? Database::fetchOne("SELECT product_name, HEX(product_name) AS hex FROM order_items WHERE order_id = ?", [$placedZh['data']['order_id']]) : null;
    check('SJKC Chung Hwa: no. CH-…, nama produk asal vendor (utf8mb4) disimpan utuh', ok($placedZh) && strpos($placedZh['data']['order_no'], 'CH-') === 0
        && $itemZh['product_name'] === '练习簿 Buku Latihan 80 muka' && $itemZh['hex'] === strtoupper(bin2hex('练习簿 Buku Latihan 80 muka')));
    $parentA->get('store/session', ['lang' => 'ms']);

    echo "11. Cron luput tunai (cron/expire-cash-orders.php)\n";
    $expireId = $rowB ? (int) $rowB['id'] : 0;
    $keepId = ok($placedZh) ? (int) $placedZh['data']['order_id'] : 0;
    $reservedBefore = reserved(9, 0, 3);
    Database::execute("UPDATE orders SET expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE id = ?", [$expireId]);
    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(APP_ROOT . '/cron/expire-cash-orders.php'));
    check('Cron melaporkan 1 pesanan diluputkan', strpos((string) $output, ': 1') !== false);
    check('Pesanan lewat → expired, tahanan stok dilepas, log oleh sistem', order($expireId)['status'] === 'expired'
        && reserved(9, 0, 3) === $reservedBefore - 2
        && Database::fetchValue("SELECT actor_type FROM order_status_logs WHERE order_id = ? AND to_status = 'expired'", [$expireId]) === 'system');
    check('Pesanan belum luput tidak disentuh', order($keepId)['status'] === 'unpaid_cash');
    check('Larian kedua: tiada lagi untuk diluputkan', (new ExpireCashOrdersJob())->run() === 0);

    echo "12. Queue e-mel & SMTP (core/Mailer, cron/send-emails.php)\n";
    $pending = (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
    Database::execute("UPDATE platform_settings SET setting_value = '' WHERE setting_key = 'smtp_host'");
    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(APP_ROOT . '/cron/send-emails.php'));
    check('SMTP belum ditetapkan → mesej amaran, e-mel kekal pending (attempts 0)', strpos((string) $output, 'SMTP') !== false
        && (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue WHERE status = 'pending' AND attempts = 0") === $pending);
    $settings = ['smtp_host' => '127.0.0.1', 'smtp_port' => (string) $smtpPort, 'smtp_encryption' => 'none', 'smtp_username' => 'ujian',
        'smtp_password_enc' => Crypto::encrypt('rahsia')];
    foreach ($settings as $key => $value) {
        Database::execute("UPDATE platform_settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
    }
    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(APP_ROOT . '/cron/send-emails.php'));
    $files = glob($mailDir . '/*.eml');
    check("Cron menghantar semua $pending e-mel melalui SMTP (AUTH LOGIN) → status sent", strpos((string) $output, 'Dihantar: ' . $pending) !== false
        && count($files) === $pending && (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue WHERE status = 'sent' AND sent_at IS NOT NULL") === $pending);
    $receiptFile = '';
    $alertFile = '';
    foreach ($files as $file) {
        $raw = file_get_contents($file);
        if (strpos($raw, 'To: <siti@contoh.my>') !== false && strpos($raw, 'Subject: Resit pesanan TM-') !== false) {
            $receiptFile = $raw;
        }
        if (strpos($raw, 'To: <chong@bestari.my>') !== false) {
            $alertFile = $raw;
        }
    }
    $parts = explode("\r\n\r\n", $receiptFile, 2);
    $body = isset($parts[1]) ? base64_decode(str_replace("\r\n", '', $parts[1])) : '';
    check('Resit melalui SMTP: HTML base64 UTF-8 dengan QR, jadual barang & pautan pesanan', $receiptFile !== ''
        && strpos($receiptFile, 'Content-Type: text/html; charset=UTF-8') !== false && strpos($body, 'bgcolor="#000000"') !== false
        && strpos($body, 'Aisyah binti Faizal') !== false && strpos($body, 'order-detail') !== false);
    preg_match('/^Subject: (.*)$/m', $alertFile, $subject);
    $decodedSubject = isset($subject[1]) ? mb_decode_mimeheader(trim($subject[1])) : '';
    check('Amaran VENDOR02 (2 alert_emails): subjek bukan ASCII dikod =?UTF-8?B? → "Pesanan baharu CH-… — SJKC Chung Hwa"',
        strpos($alertFile, 'Subject: =?UTF-8?B?') !== false && strpos($decodedSubject, 'Pesanan baharu CH-') === 0
        && strpos($decodedSubject, '— SJKC Chung Hwa') !== false);
    Database::execute("INSERT INTO email_queue (vendor_id, to_email, subject, body_html, template_key) VALUES (1, 'x@contoh.my', 'Ujian', '<p>x</p>', 'test_email')");
    $broken = new Mailer(['host' => '127.0.0.1', 'port' => 1, 'encryption' => 'none', 'from_address' => 'noreply@koperasiku.my', 'timeout' => 2]);
    $first = (new SendQueuedEmailsJob($broken))->run();
    $failRow = Database::fetchOne("SELECT * FROM email_queue WHERE to_email = 'x@contoh.my'");
    check('SMTP gagal → kekal pending, attempts 1, last_error direkod', $first['retry'] === 1 && $failRow['status'] === 'pending'
        && (int) $failRow['attempts'] === 1 && $failRow['last_error'] !== null);
    for ($i = 0; $i < 5; $i++) {
        (new SendQueuedEmailsJob($broken))->run();
    }
    $failRow = Database::fetchOne("SELECT * FROM email_queue WHERE to_email = 'x@contoh.my'");
    check('Selepas 5 cubaan → failed (tidak dicuba lagi)', $failRow['status'] === 'failed' && (int) $failRow['attempts'] === 5);
    $logFile = sys_get_temp_dir() . '/kop-verify-b7-mail.log';
    @unlink($logFile);
    (new Mailer(['driver' => 'log', 'log_file' => $logFile, 'from_address' => 'noreply@koperasiku.my']))->send('a@contoh.my', 'Log ujian', '<p>ok</p>');
    check('mail_driver "log" → mesej ditulis ke fail, tiada rangkaian', strpos((string) @file_get_contents($logFile), 'Subject: Log ujian') !== false);
    $rejected = false;
    try {
        (new Mailer(['driver' => 'log', 'log_file' => $logFile, 'from_address' => 'noreply@koperasiku.my']))->send("a@contoh.my\r\nBcc: b@contoh.my", 'x', 'x');
    } catch (RuntimeException $error) {
        $rejected = true;
    }
    $message = (new Mailer(['from_address' => 'noreply@koperasiku.my']))->buildMessage('a@contoh.my', "Subjek\r\nBcc: jahat@contoh.my", '<p>x</p>');
    check('Suntikan header (CR/LF dalam penerima / subjek) disekat', ($rejected || strpos((string) file_get_contents($logFile), 'Bcc:') === false)
        && strpos($message, "\r\nBcc:") === false);

    echo "13. QR e-mel (core/QrCode) sama dengan pustaka JS\n";
    $node = trim((string) shell_exec('command -v node'));
    if ($node === '') {
        check('node tiada — perbandingan QR dilangkau (bukan ralat)', true);
    } else {
        $tokens = [bin2hex(random_bytes(16)), bin2hex(random_bytes(16)), 'A123', 'https://koperasiku.my/sk-taman-melati/order-detail?id=12345'];
        $script = 'const q=require(' . json_encode(APP_ROOT . '/assets/lib/qrcode/qrcode.js') . ');const t=' . json_encode($tokens) . ';'
            . 'console.log(JSON.stringify(t.map(s=>{const r=q(0,"M");r.addData(s);r.make();let o="";const n=r.getModuleCount();'
            . 'for(let i=0;i<n;i++)for(let j=0;j<n;j++)o+=r.isDark(i,j)?"1":"0";return o;})));';
        $js = json_decode((string) shell_exec(escapeshellarg($node) . ' -e ' . escapeshellarg($script)), true);
        $same = 0;
        foreach ($tokens as $index => $token) {
            $bits = '';
            foreach (QrCode::matrix($token) as $line) {
                foreach ($line as $dark) {
                    $bits .= $dark ? '1' : '0';
                }
            }
            $same += isset($js[$index]) && $js[$index] === $bits ? 1 : 0;
        }
        check("Matriks QR PHP = qrcode.js untuk " . count($tokens) . " teks ($same sama)", $same === count($tokens));
    }

    echo "14. Halaman store tanpa mock (Peringkat 2)\n";
    $page = $parentA->page('store/index.php?school=sk-taman-melati&page=checkout');
    check('Halaman checkout tidak memuat mock/store.js atau mock vendor', $page['status'] === 200 && strpos($page['body'], 'js/mock/store.js') === false
        && strpos($page['body'], 'js/mock/vendor.js') === false);
    $page = $parentA->page('store/index.php?school=sk-taman-melati&page=order-detail&id=' . $orderId);
    check('Halaman butiran pesanan → 200', $page['status'] === 200 && strpos($page['body'], 'js/store/order-detail.js') !== false);
} finally {
    proc_terminate($server);
    proc_terminate($smtp);
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
    array_map('unlink', glob($mailDir . '/*.eml'));
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
