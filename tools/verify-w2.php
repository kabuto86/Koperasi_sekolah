<?php
// Semakan W2 — bayaran gateway borang POST (iPay88) untuk invois langganan vendor (vendor/invoice-pay.php, vendor/invoice-return.php)
// dan cron cuba semula refund gateway yang tersangkut `pending` (RetryPendingRefundsJob, Stripe palsu, kunci idempotensi).
// QR kaunter POS iPay88 (store/pay.php) diuji dalam verify-b10. CLI sahaja:
//   php tools/verify-w2.php
// Skrip memulakan pelayan aplikasi (php -S) + gateway palsu (tools/fake-gateway.php). Jadual yang diubah dipulihkan di hujung.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

function fakeGateway($fakeBase, $path)
{
    return json_decode((string) file_get_contents($fakeBase . $path), true);
}

function postForm($url, array $fields)
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_HEADER => true]);
    $body = (string) curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return [$status, $body];
}

function formFields($html)
{
    preg_match_all('/name="([A-Za-z]+)" value="([^"]*)"/', $html, $m);
    return array_combine($m[1], array_map('html_entity_decode', $m[2]));
}

$port = 8170;
$fakePort = 8171;
$base = 'http://127.0.0.1:' . $port;
$fakeBase = 'http://127.0.0.1:' . $fakePort;
$log = sys_get_temp_dir() . '/kop-verify-w2.log';
$stateFile = sys_get_temp_dir() . '/kop-verify-w2-gateway.json';
@unlink($stateFile);
$fake = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $fakePort, __DIR__ . '/fake-gateway.php'], [1 => ['file', $log . '.fake', 'w'], 2 => ['file', $log . '.fake', 'a']],
    $fakePipes, null, ['FAKE_GATEWAY_STATE' => $stateFile] + getenv());
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4', 'KOPERASI_GATEWAY_TEST_URL' => $fakeBase] + getenv());
usleep(900000);
putenv('KOPERASI_GATEWAY_TEST_URL=' . $fakeBase);

$tables = ['subscriptions', 'subscription_items', 'cooperative_modules', 'invoices', 'invoice_lines', 'platform_payments', 'platform_settings',
    'payment_gateways', 'vendors', 'email_queue', 'activity_logs', 'audit_logs', 'webhook_logs', 'orders', 'order_items', 'order_status_logs',
    'stock_levels', 'stock_movements', 'carts', 'cart_items', 'payments', 'refunds', 'refund_items', 'cooperative_gateways', 'cooperative_settings',
    'login_attempts', 'rate_limits', 'platform_users', 'totp_recovery_codes', 'promotions'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
Database::execute("DELETE FROM rate_limits WHERE bucket_key LIKE 'invoice_check:%' OR bucket_key LIKE 'webhook:%'");
Database::execute('DELETE FROM cart_items');
Database::execute('DELETE FROM carts');
Database::execute("UPDATE promotions SET is_active = 0");

try {
    list($owner) = ownerLogin($base, 'owner@koperasiku.my');
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $c1 = ['cooperative_id' => 1];

    echo "1. Invois langganan melalui iPay88 (gateway owner borang POST)\n";
    Database::execute("UPDATE payment_gateways SET is_enabled_for_platform = 1 WHERE code = 'ipay88'");
    $res = $owner->post('owner/gateways/save-owner', ['driver_code' => 'ipay88', 'sandbox' => true,
        'credentials' => ['merchant_code' => 'M00001', 'merchant_key' => 'ip-key']]);
    check('Owner simpan gateway iPay88 (sandbox)', ok($res) && PlatformGateway::settings()['code'] === 'ipay88');
    $sub = $v2->post('vendor/addons/subscribe', ['cooperative_id' => 3, 'item_type' => 'module', 'code' => 'news']);
    $invoiceId = (int) ($sub['data']['invoice_id'] ?? 0);
    $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
    check('Invois modul VENDOR02 diterbitkan', ok($sub) && $invoice['status'] === 'issued');
    $pay = $v2->post('vendor/invoices/pay', ['id' => $invoiceId]);
    $payment = Database::fetchOne("SELECT * FROM platform_payments WHERE invoice_id = ?", [$invoiceId]);
    $raw = json_decode((string) $payment['raw_payload'], true);
    check('Bayar → redirect_url vendor/invoice-pay.php?ref=… (bukan ralat gateway_unavailable), borang disimpan pelayan', ok($pay)
        && $pay['data']['redirect_url'] === $base . '/vendor/invoice-pay.php?ref=' . rawurlencode($payment['gateway_ref'])
        && $payment['status'] === 'pending' && isset($raw['form']['fields']['Signature']));
    $again = $v2->post('vendor/invoices/pay', ['id' => $invoiceId]);
    check('Bayar sekali lagi → bil sama diguna semula', ($again['data']['redirect_url'] ?? '') === $pay['data']['redirect_url']
        && (int) Database::fetchValue("SELECT COUNT(*) FROM platform_payments WHERE invoice_id = ?", [$invoiceId]) === 1);
    $path = 'vendor/invoice-pay.php?ref=' . rawurlencode($payment['gateway_ref']);
    $page = $v2->page($path);
    check('invoice-pay.php: borang ke entry.asp, tandatangan, ResponseURL invoice-return.php, BackendURL webhooks/platform', $page['status'] === 200
        && strpos($page['body'], 'action="' . $fakeBase . '/ipay88/entry.asp"') !== false
        && strpos(html_entity_decode($page['body']), $base . '/vendor/invoice-return.php?invoice=' . $invoiceId) !== false
        && strpos(html_entity_decode($page['body']), '/api/v1/webhooks/platform.php?gateway=ipay88') !== false);
    check('VENDOR01 buka borang bil VENDOR02 → 404; tanpa sesi → log masuk', $v1->page($path)['status'] === 404
        && (new Browser($base))->page($path)['status'] === 302);
    $form = formFields($page['body']);
    list($status, $entry) = postForm($fakeBase . '/ipay88/entry.asp', $form);
    check('Borang diterima gateway palsu (tandatangan sah)', strpos($entry, 'iPay88 sandbox') !== false);
    list($status, $body) = postForm($base . '/vendor/invoice-return.php?invoice=' . $invoiceId, ['RefNo' => $payment['gateway_ref'], 'Status' => '1']);
    check('invoice-return.php (POST iPay88) → 303 ke invoices.php?paid_return= (parameter gateway diabaikan)', $status === 303
        && preg_match('#Location: [^\r\n]*/vendor/invoices\.php\?paid_return=' . $invoiceId . '\r?\n#', $body) === 1);
    $check = $v2->post('vendor/invoices/check-payment', ['id' => $invoiceId]);
    check('Semak sebelum dibayar (enquiry) → pending, invois kekal issued', ($check['data']['result'] ?? '') === 'pending'
        && Database::fetchValue("SELECT status FROM invoices WHERE id = ?", [$invoiceId]) === 'issued');
    file_get_contents($fakeBase . '/_admin/set?' . http_build_query(['ref' => $payment['gateway_ref'], 'status' => 'paid']));
    $callback = ['MerchantCode' => 'M00001', 'PaymentId' => '2', 'RefNo' => $payment['gateway_ref'], 'Amount' => $form['Amount'], 'Currency' => 'MYR',
        'Status' => '1', 'TransId' => 'T88', 'AuthCode' => '', 'ErrDesc' => ''];
    $callback['Signature'] = hash('sha256', 'ip-keyM000012' . $payment['gateway_ref'] . str_replace(['.', ','], '', $form['Amount']) . 'MYR1');
    list($status, $body) = postForm($base . '/api/v1/webhooks/platform.php?gateway=ipay88', $callback);
    check('Webhook platform iPay88 (tandatangan + enquiry "00") → RECEIVEOK, invois paid', $status === 200 && substr($body, -9) === 'RECEIVEOK'
        && Database::fetchValue("SELECT status FROM invoices WHERE id = ?", [$invoiceId]) === 'paid'
        && Database::fetchValue("SELECT status FROM platform_payments WHERE id = ?", [$payment['id']]) === 'paid');
    check('Borang selepas dibayar → 404', $v2->page($path)['status'] === 404);

    echo "2. Cron cuba semula refund gateway tersangkut\n";
    Database::execute("UPDATE payment_gateways SET is_enabled_for_vendors = 1, supports_refund = 1 WHERE code = 'stripe'");
    CooperativeSettings::set(1, 1, ['tax_rate' => '0']);
    Database::execute("UPDATE stock_levels SET qty_on_hand = 100, qty_reserved = 0 WHERE cooperative_id = 1 AND product_id = 2 AND variant_id = 0");
    $saved = $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'stripe', 'mode' => 'sandbox',
        'credentials' => ['publishable_key' => 'pk_test_x', 'secret_key' => 'sk_test_fake', 'webhook_secret' => 'whsec_test']]);
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    $parentA->post('store/cart/add', ['slug' => 'sk-taman-melati', 'product_id' => 2, 'qty' => 2, 'child_id' => 1]);
    $placed = $parentA->post('store/checkout/place', ['slug' => 'sk-taman-melati', 'pickup_by_type' => 'parent', 'payment_method' => 'online']);
    $orderId = (int) ($placed['data']['order_id'] ?? 0);
    $orderPayment = Database::fetchOne("SELECT * FROM payments WHERE order_id = ?", [$orderId]);
    file_get_contents($fakeBase . '/_admin/set?' . http_build_query(['ref' => $orderPayment['gateway_ref'], 'status' => 'paid']));
    PaymentSettlement::recheck($orderPayment, CooperativeGateways::driver(CooperativeGateways::active(1, 1)));
    $item = Database::fetchOne("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
    check('Pesanan Stripe 2 × RM6.50 dibayar', ok($saved) && $orderId > 0
        && Database::fetchValue("SELECT status FROM orders WHERE id = ?", [$orderId]) === 'paid');
    // Proses mati selepas baris pending dicipta (tiada panggilan gateway): refund 650 + 1 unit, 20 minit lalu
    $insertPending = function ($no, $minutesAgo, $amount, $qty) use ($orderId, $orderPayment, $item) {
        $id = Database::insert("INSERT INTO refunds (vendor_id, cooperative_id, refund_no, order_id, payment_id, amount, reason, method, restock, status,
                vendor_user_id, created_at) VALUES (1, 1, ?, ?, ?, ?, 'Ujian W2', 'gateway', 1, 'pending', 1, NOW() - INTERVAL $minutesAgo MINUTE)",
            [$no, $orderId, (int) $orderPayment['id'], $amount]);
        if ($qty > 0) {
            Database::insert("INSERT INTO refund_items (vendor_id, refund_id, order_item_id, qty) VALUES (1, ?, ?, ?)", [$id, (int) $item['id'], $qty]);
        }
        return $id;
    };
    $stuck = $insertPending('RF-W2-1', 20, 650, 1);
    $fresh = $insertPending('RF-W2-2', 2, 100, 0);
    $old = $insertPending('RF-W2-3', 60 * 30, 100, 0);
    $counts = (new RetryPendingRefundsJob())->run();
    $row = Database::fetchOne("SELECT * FROM refunds WHERE id = ?", [$stuck]);
    $bill = fakeGateway($fakeBase, '/_admin/bills')[$orderPayment['gateway_ref']];
    check('Refund tersangkut 20 minit → completed, gateway_ref re_…, gateway memulangkan 650', $counts['completed'] === 1 && $row['status'] === 'completed'
        && strpos((string) $row['gateway_ref'], 're_') === 0 && (int) $bill['refunded'] === 650);
    check('Kesan refund dikenakan: 1 unit dibatal (cancelled_qty)', (int) Database::fetchValue("SELECT cancelled_qty FROM order_items WHERE id = ?", [(int) $item['id']]) === 1);
    check('Refund baru (2 minit) tidak disentuh; > 23 jam dilaporkan manual (kekal pending)', Database::fetchValue("SELECT status FROM refunds WHERE id = ?", [$fresh]) === 'pending'
        && Database::fetchValue("SELECT status FROM refunds WHERE id = ?", [$old]) === 'pending' && $counts['manual'] === 1);
    check('Log aktiviti refund_create (dicuba semula oleh sistem)', (bool) Database::fetchValue(
        "SELECT 1 FROM activity_logs WHERE action = 'refund_create' AND entity_id = ? AND detail_json LIKE ?", [$stuck, '%' . t('refund.retried') . '%']));
    // Gateway sudah menerima refund sebelum proses mati: cuba semula dengan refund_no sama tidak memulangkan wang dua kali
    $driver = CooperativeGateways::driver(CooperativeGateways::active(1, 1));
    $driver->refund($orderPayment['gateway_ref'], 100, 'RF-W2-4');
    $accepted = $insertPending('RF-W2-4', 20, 100, 0);
    $counts = (new RetryPendingRefundsJob())->run();
    $bill = fakeGateway($fakeBase, '/_admin/bills')[$orderPayment['gateway_ref']];
    check('Gateway sudah terima (kunci idempotensi sama) → completed, wang dipulang sekali sahaja (750)', $counts['completed'] === 1
        && Database::fetchValue("SELECT status FROM refunds WHERE id = ?", [$accepted]) === 'completed' && (int) $bill['refunded'] === 750);
    $rejected = $insertPending('RF-W2-5', 20, 99999, 0);
    $counts = (new RetryPendingRefundsJob())->run();
    check('Gateway menolak (amaun melebihi bil) → failed', $counts['failed'] === 1
        && Database::fetchValue("SELECT status FROM refunds WHERE id = ?", [$rejected]) === 'failed');
    Database::execute("UPDATE cooperative_gateways SET is_active = 0 WHERE cooperative_id = 1 AND gateway_code = 'stripe'");
    Database::execute("UPDATE payment_gateways SET supports_refund = 0 WHERE code = 'stripe'");
    $noGateway = $insertPending('RF-W2-6', 20, 50, 0);
    $counts = (new RetryPendingRefundsJob())->run();
    check('Gateway tidak lagi menyokong refund → failed (baki dilepaskan)', $counts['failed'] === 1
        && Database::fetchValue("SELECT status FROM refunds WHERE id = ?", [$noGateway]) === 'failed');
    $cron = file_get_contents(APP_ROOT . '/cron/retry-refunds.php');
    check('cron/retry-refunds.php: CLI sahaja, flock', strpos($cron, "PHP_SAPI !== 'cli'") !== false && strpos($cron, 'flock') !== false);
} finally {
    proc_terminate($server);
    proc_terminate($fake);
    Database::rollBack();
    Database::execute('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($tables as $table) {
        Database::execute("DELETE FROM $table");
        foreach ($snapshot[$table] as $row) {
            $columns = array_keys($row);
            Database::execute(
                "INSERT INTO $table (" . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
                array_values($row)
            );
        }
    }
    Database::execute('SET FOREIGN_KEY_CHECKS = 1');
    @unlink($stateFile);
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
