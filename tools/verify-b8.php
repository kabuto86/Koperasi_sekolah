<?php
// Semakan B8 — payment gateway: driver (tandatangan), tetapan gateway vendor (rahsia disulitkan), bayaran online store,
// panggilan balik (tandatangan + semakan semula + amaun + idempotent), redirect palsu, luput, + ujian kebocoran
// VENDOR01 ↔ VENDOR02 dan ibu bapa A ↔ B. CLI sahaja:
//   php tools/verify-b8.php
// Perlu: DB koperasi_saas (schema + seed + migrasi 003), config/database.php, config/secret.key, sambungan PHP curl.
// Skrip memulakan pelayan aplikasi (php -S, 4 pekerja) + gateway palsu (tools/fake-gateway.php). Aplikasi dihala ke
// gateway palsu dengan KOPERASI_GATEWAY_TEST_URL (mod sandbox sahaja). Jadual yang diubah dipulihkan di hujung.
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

function payments($orderId)
{
    return Database::fetchAll("SELECT * FROM payments WHERE order_id = ? ORDER BY id", [(int) $orderId]);
}

function reserved($productId, $cooperativeId = 1)
{
    return (int) Database::fetchValue(
        "SELECT qty_reserved FROM stock_levels WHERE cooperative_id = ? AND product_id = ? AND variant_id = 0",
        [$cooperativeId, $productId]
    );
}

function lastWebhook()
{
    return Database::fetchOne("SELECT * FROM webhook_logs ORDER BY id DESC LIMIT 1");
}

// Panggilan balik gateway (borang atau badan mentah) → [status, badan]
function webhookHandle($base, $code, $coop, array $fields, array $headers = [], $raw = null)
{
    $query = $coop === null ? '' : '?coop=' . $coop;
    $curl = curl_init($base . '/api/v1/webhooks/' . $code . '.php' . $query);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $raw !== null ? $raw : http_build_query($fields),
        CURLOPT_HTTPHEADER     => array_merge([$raw !== null ? 'Content-Type: application/json' : 'Content-Type: application/x-www-form-urlencoded'], $headers),
        CURLOPT_TIMEOUT        => 30,
    ]);
    return $curl;
}

function webhook($base, $code, $coop, array $fields, array $headers = [], $raw = null)
{
    $curl = webhookHandle($base, $code, $coop, $fields, $headers, $raw);
    $body = (string) curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return [$status, $body];
}

function fakeGateway($fakeBase, $path)
{
    return json_decode((string) file_get_contents($fakeBase . $path), true);
}

function setBill($fakeBase, $ref, $status = 'paid', $amount = null)
{
    return fakeGateway($fakeBase, '/_admin/set?' . http_build_query(['ref' => $ref, 'status' => $status] + ($amount === null ? [] : ['amount' => $amount])));
}

function billplzCallback($id, $paid, $amount, $key)
{
    $fields = ['id' => $id, 'collection_id' => 'col123', 'paid' => $paid ? 'true' : 'false', 'state' => $paid ? 'paid' : 'due',
        'amount' => (string) $amount, 'paid_amount' => $paid ? (string) $amount : '0', 'due_at' => date('Y-m-d'),
        'email' => 'siti@contoh.my', 'mobile' => '', 'name' => 'SITI', 'url' => 'http://x/' . $id, 'paid_at' => date('Y-m-d H:i:s O')];
    $fields['x_signature'] = BillplzGateway::sign($fields, $key);
    return $fields;
}

function stripeEvent($sessionId, $secret, $timestamp = null)
{
    $raw = json_encode(['id' => 'evt_1', 'type' => 'checkout.session.completed', 'data' => ['object' => ['id' => $sessionId, 'payment_status' => 'paid']]]);
    $timestamp = $timestamp ?? time();
    return [$raw, 'Stripe-Signature: t=' . $timestamp . ',v1=' . hash_hmac('sha256', $timestamp . '.' . $raw, $secret)];
}

// Troli ibu bapa A (SK Taman Melati) → place online. Pulangkan jawapan place
function placeOnline(Browser $parent, $productId = 1, $qty = 1, $childId = 1, $slug = 'sk-taman-melati')
{
    Database::execute("DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.customer_id = (SELECT customer_id FROM children WHERE id = ?)",
        [$childId]);
    $parent->post('store/cart/add', ['slug' => $slug, 'product_id' => $productId, 'qty' => $qty, 'child_id' => $childId]);
    return $parent->post('store/checkout/place', ['slug' => $slug, 'pickup_by_type' => 'parent', 'payment_method' => 'online']);
}

$port = 8095;
$fakePort = 8096;
$base = 'http://127.0.0.1:' . $port;
$fakeBase = 'http://127.0.0.1:' . $fakePort;
$log = sys_get_temp_dir() . '/kop-verify-b8.log';
$stateFile = sys_get_temp_dir() . '/kop-verify-b8-gateway.json';
@unlink($stateFile);
$fake = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $fakePort, __DIR__ . '/fake-gateway.php'], [1 => ['file', $log . '.fake', 'w'], 2 => ['file', $log . '.fake', 'a']],
    $fakePipes, null, ['FAKE_GATEWAY_STATE' => $stateFile] + getenv());
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4', 'KOPERASI_GATEWAY_TEST_URL' => $fakeBase] + getenv());
usleep(900000);
// Job yang dijalankan terus dalam proses ini (cron luput) juga dihala ke gateway palsu
putenv('KOPERASI_GATEWAY_TEST_URL=' . $fakeBase);

$tables = ['orders', 'order_items', 'order_status_logs', 'stock_levels', 'stock_movements', 'carts', 'cart_items', 'promotions',
    'payments', 'webhook_logs', 'cooperative_gateways', 'cooperatives', 'payment_gateways', 'activity_logs', 'email_queue', 'customers', 'children'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
Database::execute('DELETE FROM cart_items');
Database::execute('DELETE FROM carts');
// Promosi automatik seed tidak mengganggu jumlah kecil ujian ini
Database::execute("UPDATE promotions SET is_active = 0");

try {
    echo "1. Driver & tandatangan (PELAN 13)\n";
    check('GatewayFactory: 5 driver (toyyibpay, billplz, senangpay, ipay88, stripe); kod lain ditolak',
        count(GatewayFactory::DRIVERS) === 5 && GatewayFactory::supports('ipay88') && !GatewayFactory::supports('paypal'));
    $bp = GatewayFactory::make('billplz', ['api_key' => 'k', 'collection_id' => 'c', 'x_signature' => 'S3cret'], 'sandbox');
    $fields = billplzCallback('abc123', true, 1000, 'S3cret');
    $good = $bp->verifyCallback(['post' => $fields, 'get' => [], 'raw' => '', 'headers' => []]);
    $tampered = $bp->verifyCallback(['post' => array_merge($fields, ['amount' => '1']), 'get' => [], 'raw' => '', 'headers' => []]);
    check('Billplz X-Signature: sah → true + rujukan; amaun diubah → false', $good['signature_valid'] === true && $good['gateway_ref'] === 'abc123'
        && $tampered['signature_valid'] === false);
    $source = BillplzGateway::sign(['b' => '2', 'A' => '1', 'c' => '3'], 'k');
    check('Billplz: sumber tandatangan disusun tanpa beza huruf ("A1|b2|c3")', $source === hash_hmac('sha256', 'A1|b2|c3', 'k'));
    $sp = GatewayFactory::make('senangpay', ['merchant_id' => 'm', 'secret_key' => 'sk'], 'sandbox');
    $spPost = ['status_id' => '1', 'order_id' => 'TM-1', 'transaction_id' => 'T9', 'msg' => 'Payment_was_successful'];
    $spPost['hash'] = hash_hmac('sha256', 'sk1TM-1T9Payment_was_successful', 'sk');
    $spBad = array_merge($spPost, ['status_id' => '0']);
    check('SenangPay hash HMAC-SHA256: sah → true; status diubah → false',
        $sp->verifyCallback(['post' => $spPost])['signature_valid'] === true && $sp->verifyCallback(['post' => $spBad])['signature_valid'] === false);
    $ip = GatewayFactory::make('ipay88', ['merchant_code' => 'M1', 'merchant_key' => 'key'], 'sandbox');
    $ipPost = ['MerchantCode' => 'M1', 'PaymentId' => '2', 'RefNo' => 'TM-1', 'Amount' => '1,250.00', 'Currency' => 'MYR', 'Status' => '1'];
    $ipPost['Signature'] = hash('sha256', 'keyM12TM-1125000MYR1');
    check('iPay88 SHA256 (amaun tanpa titik/koma): sah → true; amaun / kod peniaga diubah → false',
        $ip->verifyCallback(['post' => $ipPost])['signature_valid'] === true
        && $ip->verifyCallback(['post' => array_merge($ipPost, ['Amount' => '1.00'])])['signature_valid'] === false
        && $ip->verifyCallback(['post' => array_merge($ipPost, ['MerchantCode' => 'M2'])])['signature_valid'] === false);
    $st = GatewayFactory::make('stripe', ['secret_key' => 'sk_test', 'webhook_secret' => 'whsec'], 'sandbox');
    list($raw, $header) = stripeEvent('cs_test_1', 'whsec');
    $sig = substr($header, strlen('Stripe-Signature: '));
    list($oldRaw, $oldHeader) = stripeEvent('cs_test_1', 'whsec', time() - 600);
    $stGood = $st->verifyCallback(['raw' => $raw, 'headers' => ['stripe-signature' => $sig]]);
    check('Stripe-Signature: sah → true + id sesi; rahsia salah / badan diubah / lebih 5 minit → false', $stGood['signature_valid'] === true
        && $stGood['gateway_ref'] === 'cs_test_1'
        && $st->verifyCallback(['raw' => $raw . ' ', 'headers' => ['stripe-signature' => $sig]])['signature_valid'] === false
        && $st->verifyCallback(['raw' => $oldRaw, 'headers' => ['stripe-signature' => substr($oldHeader, 18)]])['signature_valid'] === false
        && GatewayFactory::make('stripe', ['webhook_secret' => 'lain'], 'sandbox')
            ->verifyCallback(['raw' => $raw, 'headers' => ['stripe-signature' => $sig]])['signature_valid'] === false);
    $tp = GatewayFactory::make('toyyibpay', ['secret_key' => 'x', 'category_code' => 'y'], 'live');
    check('ToyyibPay tiada tandatangan → null (status disahkan melalui API sahaja)',
        $tp->verifyCallback(['post' => ['billcode' => 'abc', 'status' => '1']]) === ['signature_valid' => null, 'gateway_ref' => 'abc']);

    echo "2. Tetapan gateway vendor — peranan, rahsia disulitkan (PELAN 18.10)\n";
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $cashier = vendorLogin($base, 'kasim@niagasekolah.my');
    $manager = vendorLogin($base, 'chong@bestari.my');
    $c1 = ['cooperative_id' => 1];
    $settings = $v1->get('vendor/payments/settings', $c1);
    check('Driver dibenarkan owner sahaja (Billplz, ToyyibPay), belum ada gateway', ok($settings)
        && array_column($settings['data']['drivers'], 'code') === ['billplz', 'toyyibpay'] && $settings['data']['gateway'] === null
        && $settings['data']['accept_online'] === true);
    check('URL panggilan balik koperasi: webhooks/{gateway}.php?coop=1', ok($settings)
        && preg_match('#/api/v1/webhooks/\{gateway\}\.php\?coop=1$#', $settings['data']['callback_url']));
    check('Juruwang & pengurus koperasi → 403 (gateway = vendor_admin sahaja)', $cashier->status('vendor/payments/settings', $c1) === 403
        && $manager->status('vendor/payments/settings', ['cooperative_id' => 2]) === 403
        && $cashier->postStatus('vendor/payments/save-gateway', $c1) === 403);
    check('VENDOR02 → koperasi VENDOR01 (settings, gateway, list, save) → 404', $v2->status('vendor/payments/settings', $c1) === 404
        && $v2->status('vendor/payments/gateway', $c1 + ['gateway_code' => 'toyyibpay']) === 404
        && $v2->status('vendor/payments/list', $c1) === 404 && $v2->postStatus('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'toyyibpay']) === 404);
    $res = $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'stripe', 'mode' => 'sandbox', 'credentials' => []]);
    check('Driver tidak dibenarkan owner (Stripe) → ditolak', !ok($res) && isset($res['errors']['gateway_code']));
    $res = $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'toyyibpay', 'mode' => 'sandbox', 'credentials' => ['category_code' => 'cat123']]);
    check('Rahsia wajib kali pertama → errors.credentials.secret_key', !ok($res) && isset($res['errors']['credentials.secret_key']));
    $res = $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'toyyibpay', 'mode' => 'percuma',
        'credentials' => ['secret_key' => 'tp-secret', 'category_code' => 'cat123']]);
    check('Mod tidak sah → errors.mode', !ok($res) && isset($res['errors']['mode']));
    $res = $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'toyyibpay', 'mode' => 'sandbox',
        'credentials' => ['secret_key' => 'tp-secret', 'category_code' => 'cat123']]);
    $row = Database::fetchOne("SELECT * FROM cooperative_gateways WHERE cooperative_id = 1 AND gateway_code = 'toyyibpay'");
    check('Simpan ToyyibPay: aktif, secrets_set, nilai bukan rahsia dipulangkan', ok($res) && $res['data']['is_active'] === true
        && $res['data']['secrets_set'] === ['secret_key'] && $res['data']['values']['category_code'] === 'cat123' && $res['data']['mode'] === 'sandbox');
    check('Rahsia TIDAK dipulangkan & tidak disimpan teks biasa (Crypto)', ok($res) && strpos(json_encode($res), 'tp-secret') === false
        && $row && strpos($row['credentials_encrypted'] . $row['config_json'], 'tp-secret') === false
        && json_decode(Crypto::decrypt($row['credentials_encrypted']), true) === ['secret_key' => 'tp-secret']);
    $res = $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'toyyibpay', 'mode' => 'sandbox',
        'credentials' => ['secret_key' => '', 'category_code' => 'cat123']]);
    $row = Database::fetchOne("SELECT * FROM cooperative_gateways WHERE cooperative_id = 1 AND gateway_code = 'toyyibpay'");
    check('Rahsia kosong semasa simpan semula = kekal', ok($res) && json_decode(Crypto::decrypt($row['credentials_encrypted']), true)['secret_key'] === 'tp-secret');
    $test = $v1->post('vendor/payments/test-gateway', $c1);
    check('Uji sambungan ToyyibPay (getCategoryDetails, kunci tersimpan) → berjaya', ok($test) && $test['data']['success'] === true
        && $test['data']['gateway_code'] === 'toyyibpay' && $test['data']['mode'] === 'sandbox');
    $res = $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'billplz', 'mode' => 'sandbox',
        'credentials' => ['api_key' => 'salah', 'collection_id' => 'col123', 'x_signature' => 'bp-xsig']]);
    $test = $v1->post('vendor/payments/test-gateway', $c1);
    check('Simpan Billplz → Billplz aktif, ToyyibPay disimpan tetapi tidak aktif (satu aktif)', ok($res)
        && Database::fetchValue("SELECT GROUP_CONCAT(gateway_code, ':', is_active ORDER BY gateway_code) FROM cooperative_gateways WHERE cooperative_id = 1")
            === 'billplz:1,toyyibpay:0');
    check('Uji sambungan dengan kunci API salah → payment.test_failed', !ok($test) && $test['error'] === t('payment.test_failed', ['name' => 'Billplz']));
    $stored = $v1->get('vendor/payments/gateway', $c1 + ['gateway_code' => 'toyyibpay']);
    check('vendor/payments/gateway: nilai ToyyibPay tersimpan (tidak aktif), tanpa rahsia', ok($stored) && $stored['data']['is_active'] === false
        && $stored['data']['values']['category_code'] === 'cat123' && !isset($stored['data']['values']['secret_key']));
    $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'billplz', 'mode' => 'sandbox',
        'credentials' => ['api_key' => 'bp-key', 'collection_id' => 'col123', 'x_signature' => '']]);
    check('Billplz dikemas kini (rahsia x_signature kekal), uji sambungan berjaya', ok($v1->post('vendor/payments/test-gateway', $c1)));
    $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'toyyibpay', 'mode' => 'sandbox', 'credentials' => ['category_code' => 'cat123']]);
    check('Log aktiviti gateway_save (5 simpanan berjaya, koperasi 1)', (int) Database::fetchValue(
        "SELECT COUNT(*) FROM activity_logs WHERE vendor_id = 1 AND cooperative_id = 1 AND action = 'gateway_save'") === 5);
    $res = $v1->post('vendor/payments/save-methods', $c1 + ['accept_cash' => false, 'accept_online' => false]);
    check('Kaedah bayaran: tiada langsung → ditolak', !ok($res) && isset($res['errors']['accept_online']));
    $res = $v2->post('vendor/payments/save-methods', ['cooperative_id' => 3, 'accept_cash' => true, 'accept_online' => true]);
    check('Online tanpa gateway aktif (SMK Seri Indah) → payment.online_needs_gateway', !ok($res) && $res['error'] === t('payment.online_needs_gateway'));
    $res = $v1->post('vendor/payments/save-methods', $c1 + ['accept_cash' => true, 'accept_online' => true]);
    check('Tunai + online (gateway aktif) → disimpan', ok($res) && $res['data'] === ['accept_cash' => true, 'accept_online' => true]);

    echo "3. Bayaran online ToyyibPay — redirect palsu, panggilan balik, idempotent\n";
    list($parentA, $loginA) = storeLogin($base, 'siti@contoh.my');
    list($parentB, $loginB) = storeLogin($base, 'rajesh@contoh.my');
    $melati = ['slug' => 'sk-taman-melati'];
    $parentA->post('store/cart/add', $melati + ['product_id' => 1, 'qty' => 2, 'child_id' => 1]);
    $quote = $parentA->get('store/checkout/quote', $melati);
    check('Quote: online ditawarkan (gateway aktif + driver wujud), gateway_name ToyyibPay', ok($quote)
        && $quote['data']['payment_methods'] === ['cash', 'online'] && $quote['data']['gateway_name'] === 'ToyyibPay');
    $before = time();
    $placed = $parentA->post('store/checkout/place', $melati + ['pickup_by_type' => 'parent', 'payment_method' => 'online', 'status' => 'paid']);
    $orderId = ok($placed) ? $placed['data']['order_id'] : 0;
    $row = order($orderId);
    $pay = $placed['data']['payment'] ?? [];
    $paymentRows = payments($orderId);
    check('Pesanan online → pending_payment (status "paid" dari input diabaikan), stok ditahan 2', $row && $row['status'] === 'pending_payment'
        && $row['paid_at'] === null && reserved(1) === 2);
    check('Luput pending_payment = sekarang + 60 minit (config online_payment_expiry_minutes)', $row
        && abs(strtotime($row['expires_at']) - ($before + 3600)) <= 5);
    check('Jawapan place: payment { reference, gateway_name, amount, mode, redirect_url }', ok($placed)
        && $pay['reference'] === $row['order_no'] . '-1' && $pay['gateway_name'] === 'ToyyibPay' && $pay['amount'] === 360
        && $pay['mode'] === 'sandbox' && strpos($pay['redirect_url'], $fakeBase . '/toyyibpay/tp') === 0);
    $billCode = substr($pay['redirect_url'] ?? '', strlen($fakeBase . '/toyyibpay/'));
    $bill = fakeGateway($fakeBase, '/_admin/bills')[$billCode] ?? null;
    check('Satu baris payments pending: gateway_ref = BillCode, amaun 360', count($paymentRows) === 1 && $paymentRows[0]['status'] === 'pending'
        && $paymentRows[0]['gateway_ref'] === $billCode && (int) $paymentRows[0]['amount'] === 360 && $paymentRows[0]['gateway_code'] === 'toyyibpay');
    check('Bil gateway: amaun sen dari pelayan, callback ?coop=1, kembali melalui store/payment-return.php', $bill && $bill['amount'] === 360
        && strpos($bill['callback'], '/api/v1/webhooks/toyyibpay.php?coop=1') !== false
        && strpos($bill['return'], '/store/payment-return.php?school=sk-taman-melati&order=' . $orderId) !== false);
    $return = $parentA->page('store/payment-return.php?school=sk-taman-melati&order=' . $orderId . '&status_id=1&billcode=' . $billCode . '&msg=ok');
    check('Redirect palsu "berjaya" (status_id=1) → 303 ke order-success, parameter gateway dibuang', $return['status'] === 303
        && strpos($return['location'], 'page=order-success') !== false && strpos($return['location'], 'returned=1') !== false
        && strpos($return['location'], 'status_id') === false);
    $check = $parentA->post('store/orders/check-payment', $melati + ['id' => $orderId]);
    check('Redirect palsu tanpa panggilan balik → pesanan KEKAL pending_payment (pelayan tanya gateway: belum dibayar)', ok($check)
        && $check['data']['status'] === 'pending_payment' && $check['data']['can_pay'] === true && order($orderId)['status'] === 'pending_payment');
    $logs = (int) Database::fetchValue("SELECT COUNT(*) FROM webhook_logs");
    list($status, $body) = webhook($base, 'toyyibpay', 1, ['billcode' => $billCode, 'status' => '1', 'order_id' => $pay['reference'], 'amount' => '3.60']);
    check('Panggilan balik palsu "status=1" (bil belum dibayar di gateway) → OK tetapi pesanan kekal pending', $status === 200 && $body === 'OK'
        && order($orderId)['status'] === 'pending_payment' && payments($orderId)[0]['status'] === 'pending');
    $hook = lastWebhook();
    check('webhook_logs: dilog (koperasi 1, payload, keputusan "pending (unsigned)")', (int) Database::fetchValue("SELECT COUNT(*) FROM webhook_logs") === $logs + 1
        && (int) $hook['cooperative_id'] === 1 && strpos($hook['payload'], $billCode) !== false && $hook['result'] === 'pending (unsigned)');
    $again = $parentA->post('store/orders/pay', $melati + ['id' => $orderId]);
    check('Bayar semula dalam 30 minit → bil sama diguna semula (tiada bil berganda)', ok($again) && $again['data']['payment']['reference'] === $pay['reference']
        && $again['data']['payment']['redirect_url'] === $pay['redirect_url'] && count(payments($orderId)) === 1);
    setBill($fakeBase, $billCode, 'paid');
    list($status, $body) = webhook($base, 'toyyibpay', 1, ['billcode' => $billCode, 'status' => '1', 'order_id' => $pay['reference']]);
    $row = order($orderId);
    $paymentRows = payments($orderId);
    check('Ibu bapa bayar di gateway → panggilan balik + semakan semula → pesanan paid, paid_at, expires_at NULL', $status === 200
        && $row['status'] === 'paid' && $row['paid_at'] !== null && $row['expires_at'] === null);
    check('payments: paid + verified_at + raw_payload (rujukan & semakan)', $paymentRows[0]['status'] === 'paid' && $paymentRows[0]['verified_at'] !== null
        && strpos($paymentRows[0]['raw_payload'], '"verified"') !== false && strpos($paymentRows[0]['raw_payload'], $pay['reference']) !== false);
    check('Log status pending_payment → paid oleh system', Database::fetchValue(
        "SELECT CONCAT(from_status, '>', to_status, ':', actor_type) FROM order_status_logs WHERE order_id = ? ORDER BY id DESC LIMIT 1", [$orderId])
        === 'pending_payment>paid:system');
    $multi = curl_multi_init();
    $handles = [];
    for ($i = 0; $i < 4; $i++) {
        $handles[] = $handle = webhookHandle($base, 'toyyibpay', 1, ['billcode' => $billCode, 'status' => '1']);
        curl_multi_add_handle($multi, $handle);
    }
    do {
        curl_multi_exec($multi, $running);
        curl_multi_select($multi, 0.05);
    } while ($running > 0);
    $codes = array_map(function ($h) { return curl_getinfo($h, CURLINFO_HTTP_CODE); }, $handles);
    curl_multi_close($multi);
    check('Panggilan balik berganda (4 serentak) → semua 200, SATU payments paid, satu log "paid"', $codes === [200, 200, 200, 200]
        && (int) Database::fetchValue("SELECT COUNT(*) FROM payments WHERE order_id = ? AND status = 'paid'", [$orderId]) === 1
        && (int) Database::fetchValue("SELECT COUNT(*) FROM order_status_logs WHERE order_id = ? AND to_status = 'paid'", [$orderId]) === 1
        && lastWebhook()['result'] === 'already_paid (unsigned)');
    $detail = $parentA->get('store/orders/get', $melati + ['id' => $orderId]);
    check('Butiran ibu bapa: payment_status paid, gateway_ref, can_pay false', ok($detail) && $detail['data']['payment_status'] === 'paid'
        && $detail['data']['gateway_ref'] === $billCode && $detail['data']['can_pay'] === false);
    $res = $parentA->post('store/orders/pay', $melati + ['id' => $orderId]);
    check('Bayar pesanan yang sudah dibayar → store.order.not_payable', !ok($res) && $res['error'] === t('store.order.not_payable'));

    echo "4. Billplz — tandatangan salah, amaun berbeza, koperasi lain\n";
    $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'billplz', 'mode' => 'sandbox', 'credentials' => ['collection_id' => 'col123']]);
    $placed = placeOnline($parentA, 1, 1);
    $bpOrder = ok($placed) ? $placed['data']['order_id'] : 0;
    $bpId = payments($bpOrder)[0]['gateway_ref'] ?? '';
    check('Billplz: bil dicipta (Basic auth api_key), redirect ke URL bil', ok($placed) && $placed['data']['payment']['gateway_name'] === 'Billplz'
        && strpos($placed['data']['payment']['redirect_url'], '/billplz/pay/' . $bpId) !== false);
    setBill($fakeBase, $bpId, 'paid');
    list($status) = webhook($base, 'billplz', 1, billplzCallback($bpId, true, 180, 'kunci-salah'));
    check('X-Signature salah → 400, pesanan kekal pending, log bad_signature', $status === 400 && order($bpOrder)['status'] === 'pending_payment'
        && lastWebhook()['result'] === 'bad_signature' && (int) lastWebhook()['signature_valid'] === 0);
    setBill($fakeBase, $bpId, 'paid', 100);
    list($status) = webhook($base, 'billplz', 1, billplzCallback($bpId, true, 180, 'bp-xsig'));
    check('Gateway melapor amaun lain (100 ≠ 180) → kekal pending, amount_mismatch', $status === 200 && order($bpOrder)['status'] === 'pending_payment'
        && payments($bpOrder)[0]['status'] === 'pending' && lastWebhook()['result'] === 'amount_mismatch' && (int) lastWebhook()['signature_valid'] === 1);
    $v2->post('vendor/payments/save-gateway', ['cooperative_id' => 3, 'gateway_code' => 'billplz', 'mode' => 'sandbox',
        'credentials' => ['api_key' => 'bp-key', 'collection_id' => 'col123', 'x_signature' => 'v2-xsig']]);
    setBill($fakeBase, $bpId, 'paid');
    list($status) = webhook($base, 'billplz', 3, billplzCallback($bpId, true, 180, 'v2-xsig'));
    check('Rujukan bil VENDOR01 dihantar ke URL koperasi VENDOR02 (?coop=3) → unknown_reference, tiada perubahan', $status === 200
        && order($bpOrder)['status'] === 'pending_payment' && lastWebhook()['result'] === 'unknown_reference');
    list($status) = webhook($base, 'billplz', 2, billplzCallback($bpId, true, 180, 'bp-xsig'));
    check('Koperasi tanpa gateway itu (?coop=2) → 404 no_gateway', $status === 404 && lastWebhook()['result'] === 'no_gateway');
    list($status, $body) = webhook($base, 'billplz', 1, billplzCallback($bpId, true, 180, 'bp-xsig'));
    check('Tandatangan sah + gateway sahkan 180 → paid', $status === 200 && $body === 'OK' && order($bpOrder)['status'] === 'paid'
        && lastWebhook()['result'] === 'paid');

    echo "5. Kebocoran ibu bapa A ↔ B dan VENDOR01 ↔ VENDOR02\n";
    $placed = placeOnline($parentA, 1, 1);
    $aOrder = ok($placed) ? $placed['data']['order_id'] : 0;
    $aRef = payments($aOrder)[0]['gateway_ref'] ?? '';
    check('Ibu bapa B: bayar / semak bayaran pesanan A → 404 (slug sama atau koperasi B)',
        $parentB->postStatus('store/orders/pay', $melati + ['id' => $aOrder]) === 404
        && $parentB->postStatus('store/orders/check-payment', $melati + ['id' => $aOrder]) === 404
        && $parentB->postStatus('store/orders/check-payment', ['slug' => 'smk-seri-indah', 'id' => $aOrder]) === 404);
    check('Ibu bapa A: semak bayaran melalui slug koperasi lain → 404', $parentA->postStatus('store/orders/check-payment',
        ['slug' => 'smk-seri-indah', 'id' => $aOrder]) === 404);
    check('Tanpa sesi → check-payment auth_required; tanpa CSRF → 419', !empty((new Browser($base))->post('store/orders/check-payment',
        $melati + ['id' => $aOrder])['auth_required']) && $parentA->postStatus('store/orders/check-payment', $melati + ['id' => $aOrder], false) === 419);
    $list = $v1->get('vendor/payments/list', $c1);
    $refs = ok($list) ? array_column($list['data']['items'], 'gateway_ref') : [];
    check('VENDOR01 rekod bayaran koperasi 1: 2 paid + 1 pending, ringkasan online 540', ok($list) && in_array($billCode, $refs, true)
        && in_array($aRef, $refs, true) && $list['data']['summary']['online_total'] === 540 && $list['data']['summary']['pending_count'] === 1
        && $list['data']['summary']['cash_total'] === 0);
    $row = ok($list) ? $list['data']['items'][array_search($billCode, $refs, true)] : [];
    check('PaymentRecord: order_no, kaedah online, gateway_code, paid_at', $row && $row['method'] === 'online' && $row['gateway_code'] === 'toyyibpay'
        && $row['status'] === 'paid' && $row['paid_at'] !== null && $row['order_id'] === $orderId && $row['refunded_amount'] === 0);
    $filtered = $v1->get('vendor/payments/list', $c1 + ['status' => 'pending']);
    $searched = $v1->get('vendor/payments/list', $c1 + ['q' => $billCode]);
    check('Tapis status pending → 1; cari rujukan gateway → 1', ok($filtered) && $filtered['data']['total'] === 1 && ok($searched) && $searched['data']['total'] === 1);
    $list2 = $v2->get('vendor/payments/list', ['cooperative_id' => 3]);
    check('VENDOR02 (SMK Seri Indah): tiada rekod VENDOR01', ok($list2) && $list2['data']['total'] === 0);
    $redirectA = $parentA->page('store/payment-redirect.php?ref=' . $aRef);
    check('store/payment-redirect.php untuk bil bukan borang (Billplz) → 404', $redirectA['status'] === 404);

    echo "6. Gateway tidak dapat dihubungi\n";
    fakeGateway($fakeBase, '/_admin/down?on=1');
    $placed = placeOnline($parentA, 3, 1);
    $downOrder = ok($placed) ? $placed['data']['order_id'] : 0;
    check('Cipta bil gagal → pesanan tetap disimpan (pending_payment), payment null + payment_error', ok($placed) && $placed['data']['payment'] === null
        && $placed['data']['payment_error'] === t('payment.gateway_error') && order($downOrder)['status'] === 'pending_payment' && payments($downOrder) === []);
    $res = $parentA->post('store/orders/pay', $melati + ['id' => $downOrder]);
    check('Bayar semula semasa gateway rosak → payment.gateway_error', !ok($res) && $res['error'] === t('payment.gateway_error'));
    $check = $parentA->post('store/orders/check-payment', $melati + ['id' => $aOrder]);
    check('Semak bayaran semasa gateway rosak → 200, status kekal pending', ok($check) && $check['data']['status'] === 'pending_payment');
    list($status) = webhook($base, 'billplz', 1, billplzCallback($aRef, true, 180, 'bp-xsig'));
    check('Panggilan balik semasa API semakan gagal → 502 (gateway cuba semula), recheck_failed', $status === 502
        && lastWebhook()['result'] === 'recheck_failed' && order($aOrder)['status'] === 'pending_payment');
    fakeGateway($fakeBase, '/_admin/down?on=0');
    $res = $parentA->post('store/orders/pay', $melati + ['id' => $downOrder]);
    check('Gateway pulih → bayar semula mencipta bil', ok($res) && strpos($res['data']['payment']['redirect_url'], '/billplz/pay/') !== false
        && count(payments($downOrder)) === 1);

    echo "7. Luput pending_payment (cron/expire-online-orders.php)\n";
    $heldBefore = reserved(1);
    Database::execute("UPDATE orders SET expires_at = NOW() - INTERVAL 5 MINUTE WHERE id IN (?, ?)", [$aOrder, $downOrder]);
    $downRef = payments($downOrder)[0]['gateway_ref'];
    setBill($fakeBase, $downRef, 'paid');
    $counts = (new ExpirePendingPaymentsJob())->run();
    check('Cron: bil dibayar tetapi panggilan balik hilang → paid; yang lain → expired', $counts === ['expired' => 1, 'paid' => 1, 'retry' => 0]
        && order($downOrder)['status'] === 'paid' && order($aOrder)['status'] === 'expired');
    check('Luput: tahanan stok dilepas, bil pending → failed, log expired oleh system', reserved(1) === $heldBefore - 1
        && payments($aOrder)[0]['status'] === 'failed'
        && Database::fetchValue("SELECT CONCAT(to_status, ':', actor_type) FROM order_status_logs WHERE order_id = ? ORDER BY id DESC LIMIT 1", [$aOrder])
            === 'expired:system');
    $res = $parentA->post('store/orders/pay', $melati + ['id' => $aOrder]);
    check('Bayar pesanan luput → not_payable', !ok($res) && $res['error'] === t('store.order.not_payable'));
    setBill($fakeBase, $aRef, 'paid');
    list($status) = webhook($base, 'billplz', 1, billplzCallback($aRef, true, 180, 'bp-xsig'));
    check('Wang diterima selepas luput → payments paid, pesanan kekal expired, nota refund (paid_late)', $status === 200
        && lastWebhook()['result'] === 'paid_late' && payments($aOrder)[0]['status'] === 'paid' && order($aOrder)['status'] === 'expired'
        && strpos((string) Database::fetchValue("SELECT note FROM order_status_logs WHERE order_id = ? ORDER BY id DESC LIMIT 1", [$aOrder]), $aRef) !== false);
    $placed = placeOnline($parentA, 1, 1);
    $retryOrder = ok($placed) ? $placed['data']['order_id'] : 0;
    Database::execute("UPDATE orders SET expires_at = NOW() - INTERVAL 1 MINUTE WHERE id = ?", [$retryOrder]);
    fakeGateway($fakeBase, '/_admin/down?on=1');
    $counts = (new ExpirePendingPaymentsJob())->run();
    check('Gateway rosak semasa cron → tidak diluputkan (cuba semula)', $counts['retry'] === 1 && order($retryOrder)['status'] === 'pending_payment');
    Database::execute("UPDATE orders SET expires_at = NOW() - INTERVAL 25 HOUR WHERE id = ?", [$retryOrder]);
    $counts = (new ExpirePendingPaymentsJob())->run();
    fakeGateway($fakeBase, '/_admin/down?on=0');
    check('Melebihi 24 jam tempoh tangguh → diluputkan juga', $counts['expired'] === 1 && order($retryOrder)['status'] === 'expired');
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(APP_ROOT . '/cron/expire-online-orders.php'));
    check('cron/expire-online-orders.php berjalan (CLI)', strpos((string) $out, 'Pesanan online diluputkan: 0') !== false);

    echo "8. Batal, owner sekat driver\n";
    $placed = placeOnline($parentA, 1, 1);
    $cancelOrder = ok($placed) ? $placed['data']['order_id'] : 0;
    $res = $parentA->post('store/orders/cancel', $melati + ['id' => $cancelOrder]);
    check('Ibu bapa batal pesanan online → cancelled, bil pending → failed', ok($res) && order($cancelOrder)['status'] === 'cancelled'
        && payments($cancelOrder)[0]['status'] === 'failed');
    Database::execute("UPDATE payment_gateways SET is_enabled_for_vendors = 0 WHERE code = 'billplz'");
    $quote = $parentA->get('store/checkout/quote', $melati);
    check('Owner sekat Billplz untuk vendor → online tidak ditawarkan', ok($quote) && $quote['data']['payment_methods'] === ['cash']
        && $quote['data']['gateway_name'] === null);
    $parentA->post('store/cart/add', $melati + ['product_id' => 1, 'qty' => 1, 'child_id' => 1]);
    $placed = $parentA->post('store/checkout/place', $melati + ['pickup_by_type' => 'parent', 'payment_method' => 'online']);
    check('Place online selepas disekat → errors.payment_method', !ok($placed) && isset($placed['errors']['payment_method']));
    Database::execute("UPDATE payment_gateways SET is_enabled_for_vendors = 1 WHERE code = 'billplz'");

    echo "9. SenangPay, iPay88 & Stripe (driver lain)\n";
    Database::execute("UPDATE payment_gateways SET is_enabled_for_vendors = 1");
    // SenangPay
    $res = $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'senangpay', 'mode' => 'sandbox',
        'credentials' => ['merchant_id' => 'sp-merchant', 'secret_key' => 'sp-secret']]);
    check('SenangPay: simpan & uji sambungan (query_order_status bertandatangan)', ok($res) && ok($v1->post('vendor/payments/test-gateway', $c1)));
    $placed = placeOnline($parentA, 1, 2);
    $spOrder = ok($placed) ? $placed['data']['order_id'] : 0;
    $spUrl = $placed['data']['payment']['redirect_url'] ?? '';
    $spRef = payments($spOrder)[0]['gateway_ref'] ?? '';
    $spPage = @file_get_contents($spUrl);
    check('SenangPay: pautan bayaran bertandatangan (amaun 3.60) diterima gateway', $spPage !== false && strpos($spUrl, 'amount=3.60') !== false
        && $spRef === $placed['data']['payment']['reference']);
    $spCallback = ['status_id' => '1', 'order_id' => $spRef, 'transaction_id' => 'T1', 'msg' => 'Payment_was_successful'];
    $spCallback['hash'] = hash_hmac('sha256', 'sp-secret1' . $spRef . 'T1Payment_was_successful', 'sp-secret');
    list($status) = webhook($base, 'senangpay', 1, $spCallback);
    check('SenangPay: callback sah tetapi gateway belum terima bayaran → pending', $status === 200 && order($spOrder)['status'] === 'pending_payment');
    list($status) = webhook($base, 'senangpay', 1, array_merge($spCallback, ['hash' => str_repeat('0', 64)]));
    check('SenangPay: hash salah → 400', $status === 400);
    setBill($fakeBase, $spRef, 'paid');
    list($status) = webhook($base, 'senangpay', 1, $spCallback);
    check('SenangPay: dibayar → paid', $status === 200 && order($spOrder)['status'] === 'paid');
    // iPay88
    $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'ipay88', 'mode' => 'sandbox',
        'credentials' => ['merchant_code' => 'M00001', 'merchant_key' => 'ip-key']]);
    $test = $v1->post('vendor/payments/test-gateway', $c1);
    check('iPay88: uji sambungan tidak disokong → mesej jelas', !ok($test) && $test['error'] === t('payment.test_unsupported', ['name' => 'iPay88']));
    $placed = placeOnline($parentA, 1, 1);
    $ipOrder = ok($placed) ? $placed['data']['order_id'] : 0;
    $ipRef = payments($ipOrder)[0]['gateway_ref'] ?? '';
    check('iPay88: redirect_url = store/payment-redirect.php?ref=… (borang POST)', ok($placed)
        && strpos($placed['data']['payment']['redirect_url'], '/store/payment-redirect.php?ref=' . $ipRef) !== false);
    $formPage = $parentA->page('store/payment-redirect.php?ref=' . $ipRef);
    $headers = [];
    $curl = curl_init($base . '/store/payment-redirect.php?ref=' . $ipRef);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIE => 'KOPSTORE=' . $parentA->cookie('KOPSTORE')]);
    $rawPage = (string) curl_exec($curl);
    curl_close($curl);
    check('Halaman borang: action gateway, Signature, tiada skrip inline; CSP form-action membenarkan asal gateway', $formPage['status'] === 200
        && strpos($formPage['body'], 'action="' . $fakeBase . '/ipay88/entry.asp"') !== false && strpos($formPage['body'], 'name="Signature"') !== false
        && strpos($formPage['body'], '<script>') === false && preg_match("/form-action 'self' " . preg_quote($fakeBase, '/') . ';/', $rawPage) === 1);
    check('Ibu bapa B / tanpa sesi buka borang bil A → 404', $parentB->page('store/payment-redirect.php?ref=' . $ipRef)['status'] === 404
        && (new Browser($base))->page('store/payment-redirect.php?ref=' . $ipRef)['status'] === 404);
    preg_match_all('/name="([A-Za-z]+)" value="([^"]*)"/', $formPage['body'], $m);
    $form = array_combine($m[1], array_map('html_entity_decode', $m[2]));
    $curl = curl_init($fakeBase . '/ipay88/entry.asp');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($form)]);
    $entry = (string) curl_exec($curl);
    curl_close($curl);
    check('iPay88: borang diterima gateway (tandatangan sah, BackendURL ?coop=1)', strpos($entry, 'iPay88 sandbox') !== false
        && strpos($form['BackendURL'], '/api/v1/webhooks/ipay88.php?coop=1') !== false && $form['Amount'] === '1.80');
    $ipCallback = ['MerchantCode' => 'M00001', 'PaymentId' => '2', 'RefNo' => $ipRef, 'Amount' => '1.80', 'Currency' => 'MYR', 'Status' => '1',
        'TransId' => 'T88', 'AuthCode' => '', 'ErrDesc' => ''];
    $ipCallback['Signature'] = hash('sha256', 'ip-keyM000012' . $ipRef . '180MYR1');
    list($status, $body) = webhook($base, 'ipay88', 1, $ipCallback);
    check('iPay88: callback sah, enquiry "Record not found" → pending', $status === 200 && $body === 'RECEIVEOK' && order($ipOrder)['status'] === 'pending_payment');
    setBill($fakeBase, $ipRef, 'paid');
    list($status, $body) = webhook($base, 'ipay88', 1, $ipCallback);
    check('iPay88: enquiry "00" (amaun sama) → paid, jawapan RECEIVEOK', $status === 200 && $body === 'RECEIVEOK' && order($ipOrder)['status'] === 'paid');
    check('Borang iPay88 selepas dibayar → 404', $parentA->page('store/payment-redirect.php?ref=' . $ipRef)['status'] === 404);
    // Stripe
    $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'stripe', 'mode' => 'sandbox',
        'credentials' => ['publishable_key' => 'pk_test_x', 'secret_key' => 'sk_test_fake', 'webhook_secret' => 'whsec_test']]);
    check('Stripe: uji sambungan (/v1/balance) berjaya', ok($v1->post('vendor/payments/test-gateway', $c1)));
    $placed = placeOnline($parentA, 2, 1);
    $stOrder = ok($placed) ? $placed['data']['order_id'] : 0;
    $session = payments($stOrder)[0]['gateway_ref'] ?? '';
    check('Stripe: Checkout Session dicipta (MYR, 650 sen)', ok($placed) && strpos($session, 'cs_test_') === 0
        && fakeGateway($fakeBase, '/_admin/bills')[$session]['currency'] === 'myr' && fakeGateway($fakeBase, '/_admin/bills')[$session]['amount'] === 650);
    list($raw, $header) = stripeEvent($session, 'whsec_salah');
    list($status) = webhook($base, 'stripe', 1, [], [$header], $raw);
    check('Stripe: Stripe-Signature salah → 400', $status === 400 && order($stOrder)['status'] === 'pending_payment');
    setBill($fakeBase, $session, 'paid');
    list($raw, $header) = stripeEvent($session, 'whsec_test');
    list($status, $body) = webhook($base, 'stripe', 1, [], [$header], $raw);
    check('Stripe: webhook sah + sesi paid → paid', $status === 200 && $body === '{"received":true}' && order($stOrder)['status'] === 'paid');

    echo "10. Endpoint webhook\n";
    check('GET ke webhook → 405', (new Browser($base))->getStatus('webhooks/toyyibpay') === 405);
    list($status) = webhook($base, 'toyyibpay', null, ['billcode' => $billCode, 'status' => '1']);
    check('Tanpa ?coop → 404 (dilog)', $status === 404 && lastWebhook()['result'] === 'no_gateway' && lastWebhook()['cooperative_id'] === null);
    list($status) = webhook($base, 'toyyibpay', 999, ['billcode' => $billCode]);
    check('Koperasi tidak wujud → 404', $status === 404);
    check('Setiap panggilan dilog (webhook_logs ≥ 20)', (int) Database::fetchValue("SELECT COUNT(*) FROM webhook_logs") >= 20);

    echo "11. Halaman store\n";
    $page = $parentA->page('store/index.php?school=sk-taman-melati&page=checkout');
    check('Modal gateway: tiada teks simulasi lagi, butang ke halaman gateway', $page['status'] === 200
        && strpos($page['body'], 'id="gateway-pay"') !== false && strpos($page['body'], t('store.payment.redirect_note')) !== false);
    $page = $parentA->page('store/index.php?school=sk-taman-melati&page=order-success&order=' . $orderId . '&returned=1');
    check('Halaman order-success (kembali dari gateway) → 200', $page['status'] === 200 && strpos($page['body'], 'js/store/order-success.js') !== false);
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
    Database::execute('SET FOREIGN_KEY_CHECKS = 1');
    clearTestLocks();
    @unlink($stateFile);
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
