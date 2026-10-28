<?php
// Gateway palsu untuk ujian B8 (tools/verify-b8.php) — pelayan router `php -S`:
//   FAKE_GATEWAY_STATE=/tmp/state.json php -S 127.0.0.1:8097 tools/fake-gateway.php
// Aplikasi dihala ke sini dengan KOPERASI_GATEWAY_TEST_URL=http://127.0.0.1:8097 (mod sandbox sahaja).
// Meniru API ToyyibPay, Billplz, SenangPay, iPay88 & Stripe (termasuk refund Stripe, B11) secukupnya. Kunci sah: lihat FAKE_KEYS.
// /_admin/set?ref=…&status=paid|failed|pending[&amount=sen] = "ibu bapa membayar" (atau gateway melapor amaun lain).
// Bukan sebahagian aplikasi — CLI / ujian sahaja.
if (PHP_SAPI !== 'cli-server') {
    exit(1);
}

const FAKE_KEYS = [
    'toyyibpay' => ['secret_key' => 'tp-secret', 'category_code' => 'cat123'],
    'billplz'   => ['api_key' => 'bp-key', 'collection_id' => 'col123'],
    'senangpay' => ['merchant_id' => 'sp-merchant', 'secret_key' => 'sp-secret'],
    'ipay88'    => ['merchant_code' => 'M00001', 'merchant_key' => 'ip-key'],
    'stripe'    => ['secret_key' => 'sk_test_fake'],
];

$file = getenv('FAKE_GATEWAY_STATE') ?: sys_get_temp_dir() . '/kop-fake-gateway.json';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

function state($file)
{
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    return is_array($data) ? $data : ['bills' => [], 'requests' => 0];
}

function save($file, $state)
{
    file_put_contents($file, json_encode($state), LOCK_EX);
}

function reply($status, $body, $type = 'application/json')
{
    http_response_code($status);
    header('Content-Type: ' . $type);
    echo is_string($body) ? $body : json_encode($body);
    exit;
}

function newBill(&$state, $code, $ref, $amount, $extra = [])
{
    $state['bills'][$ref] = array_merge(['code' => $code, 'ref' => $ref, 'amount' => (int) $amount, 'status' => 'pending',
        'reported_amount' => null], $extra);
}

function bill($state, $ref)
{
    return $state['bills'][$ref] ?? null;
}

function reportedAmount($bill)
{
    return $bill['reported_amount'] !== null ? (int) $bill['reported_amount'] : (int) $bill['amount'];
}

function basicUser()
{
    return (string) ($_SERVER['PHP_AUTH_USER'] ?? '');
}

function bearer()
{
    return preg_replace('/^Bearer\s+/', '', (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
}

$state = state($file);
$state['requests'] = ($state['requests'] ?? 0) + 1;
save($file, $state);

// ---------- pentadbir ujian ----------
if ($path === '/_admin/set') {
    $ref = (string) ($_GET['ref'] ?? '');
    if (!isset($state['bills'][$ref])) {
        reply(404, ['error' => 'no bill']);
    }
    $state['bills'][$ref]['status'] = (string) ($_GET['status'] ?? 'paid');
    $state['bills'][$ref]['reported_amount'] = isset($_GET['amount']) ? (int) $_GET['amount'] : null;
    save($file, $state);
    reply(200, $state['bills'][$ref]);
}
if ($path === '/_admin/bills') {
    reply(200, $state['bills']);
}
if ($path === '/_admin/down') {
    $state['down'] = ($_GET['on'] ?? '1') === '1';
    save($file, $state);
    reply(200, ['down' => $state['down']]);
}
if (!empty($state['down'])) {
    reply(503, 'Service Unavailable', 'text/plain');
}

// ---------- ToyyibPay ----------
if ($path === '/toyyibpay/index.php/api/createBill' && $method === 'POST') {
    if (($_POST['userSecretKey'] ?? '') !== FAKE_KEYS['toyyibpay']['secret_key']) {
        reply(200, '[KEY-DID-NOT-EXIST]', 'text/plain');
    }
    $code = 'tp' . substr(md5(($_POST['billExternalReferenceNo'] ?? '') . microtime()), 0, 8);
    newBill($state, 'toyyibpay', $code, $_POST['billAmount'] ?? 0, ['external' => $_POST['billExternalReferenceNo'] ?? '',
        'callback' => $_POST['billCallbackUrl'] ?? '', 'return' => $_POST['billReturnUrl'] ?? '']);
    save($file, $state);
    reply(200, [['BillCode' => $code]]);
}
if ($path === '/toyyibpay/index.php/api/getBillTransactions' && $method === 'POST') {
    $bill = bill($state, (string) ($_POST['billCode'] ?? ''));
    if ($bill === null || $bill['status'] === 'pending') {
        reply(200, []);
    }
    reply(200, [[
        'billName' => 'x', 'billpaymentStatus' => $bill['status'] === 'paid' ? '1' : '3',
        'billpaymentAmount' => number_format(reportedAmount($bill) / 100, 2, '.', ''), 'billExternalReferenceNo' => $bill['external'],
    ]]);
}
if ($path === '/toyyibpay/index.php/api/getCategoryDetails' && $method === 'POST') {
    $ok = ($_POST['userSecretKey'] ?? '') === FAKE_KEYS['toyyibpay']['secret_key']
        && ($_POST['categoryCode'] ?? '') === FAKE_KEYS['toyyibpay']['category_code'];
    reply(200, $ok ? [['categoryName' => 'Koperasi', 'categoryDescription' => 'x', 'categoryStatus' => '1']] : '[KEY-DID-NOT-EXIST]');
}

// ---------- Billplz ----------
if ($path === '/billplz/v3/bills' && $method === 'POST') {
    if (basicUser() !== FAKE_KEYS['billplz']['api_key']) {
        reply(401, ['error' => ['type' => 'Unauthorized']]);
    }
    $id = 'bp' . substr(md5(($_POST['reference_1'] ?? '') . microtime()), 0, 8);
    newBill($state, 'billplz', $id, $_POST['amount'] ?? 0, ['callback' => $_POST['callback_url'] ?? '']);
    save($file, $state);
    reply(200, ['id' => $id, 'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/billplz/pay/' . $id, 'amount' => (int) ($_POST['amount'] ?? 0)]);
}
if (preg_match('#^/billplz/v3/bills/([A-Za-z0-9]+)$#', $path, $m) && $method === 'GET') {
    $bill = bill($state, $m[1]);
    if ($bill === null || basicUser() !== FAKE_KEYS['billplz']['api_key']) {
        reply(404, ['error' => ['type' => 'RecordNotFound']]);
    }
    $paid = $bill['status'] === 'paid';
    reply(200, ['id' => $bill['ref'], 'paid' => $paid, 'state' => $paid ? 'paid' : ($bill['status'] === 'failed' ? 'deleted' : 'due'),
        'amount' => (int) $bill['amount'], 'paid_amount' => $paid ? reportedAmount($bill) : 0]);
}
if (preg_match('#^/billplz/v3/collections/([A-Za-z0-9]+)$#', $path, $m)) {
    $ok = basicUser() === FAKE_KEYS['billplz']['api_key'] && $m[1] === FAKE_KEYS['billplz']['collection_id'];
    reply($ok ? 200 : 401, $ok ? ['id' => $m[1], 'title' => 'Koperasi'] : ['error' => ['type' => 'Unauthorized']]);
}

// ---------- SenangPay ----------
if (preg_match('#^/senangpay/payment/([A-Za-z0-9-]+)$#', $path, $m)) {
    // Halaman bayaran: daftar bil jika tandatangan sah (seperti SenangPay menolak hash salah)
    $secret = FAKE_KEYS['senangpay']['secret_key'];
    $expected = hash_hmac('sha256', $secret . ($_GET['detail'] ?? '') . ($_GET['amount'] ?? '') . ($_GET['order_id'] ?? ''), $secret);
    if ($m[1] !== FAKE_KEYS['senangpay']['merchant_id'] || !hash_equals($expected, (string) ($_GET['hash'] ?? ''))) {
        reply(400, 'Invalid hash', 'text/plain');
    }
    newBill($state, 'senangpay', (string) $_GET['order_id'], (int) round((float) $_GET['amount'] * 100));
    save($file, $state);
    reply(200, '<html><body>SenangPay sandbox</body></html>', 'text/html');
}
if ($path === '/senangpay/apiv1/query_order_status') {
    $secret = FAKE_KEYS['senangpay']['secret_key'];
    $merchant = (string) ($_GET['merchant_id'] ?? '');
    $orderId = (string) ($_GET['order_id'] ?? '');
    if ($merchant !== FAKE_KEYS['senangpay']['merchant_id']
        || !hash_equals(hash_hmac('sha256', $merchant . $secret . $orderId, $secret), (string) ($_GET['hash'] ?? ''))) {
        reply(200, ['status' => 0, 'msg' => 'Invalid hash or merchant']);
    }
    $bill = bill($state, $orderId);
    if ($bill === null || $bill['status'] === 'pending') {
        reply(200, ['status' => 0, 'msg' => 'No record found', 'data' => []]);
    }
    reply(200, ['status' => 1, 'msg' => 'Success', 'data' => [['payment_info' => [
        'status' => $bill['status'], 'amount' => number_format(reportedAmount($bill) / 100, 2, '.', ''), 'transaction_reference' => 'SP' . $orderId,
    ]]]]);
}

// ---------- iPay88 ----------
if ($path === '/ipay88/entry.asp' && $method === 'POST') {
    $key = FAKE_KEYS['ipay88']['merchant_key'];
    $expected = hash('sha256', $key . ($_POST['MerchantCode'] ?? '') . ($_POST['RefNo'] ?? '')
        . str_replace(['.', ','], '', (string) ($_POST['Amount'] ?? '')) . ($_POST['Currency'] ?? ''));
    if (!hash_equals($expected, (string) ($_POST['Signature'] ?? ''))) {
        reply(400, 'Invalid signature', 'text/plain');
    }
    newBill($state, 'ipay88', (string) $_POST['RefNo'], (int) round((float) str_replace(',', '', $_POST['Amount']) * 100));
    save($file, $state);
    reply(200, '<html><body>iPay88 sandbox</body></html>', 'text/html');
}
if ($path === '/ipay88/enquiry.asp' && $method === 'POST') {
    $bill = bill($state, (string) ($_POST['RefNo'] ?? ''));
    if ($bill === null || ($_POST['MerchantCode'] ?? '') !== FAKE_KEYS['ipay88']['merchant_code']) {
        reply(200, 'Record not found', 'text/plain');
    }
    $asked = (int) round((float) str_replace(',', '', (string) ($_POST['Amount'] ?? '0')) * 100);
    if ($asked !== reportedAmount($bill)) {
        reply(200, 'Incorrect amount', 'text/plain');
    }
    reply(200, $bill['status'] === 'paid' ? '00' : ($bill['status'] === 'failed' ? 'Payment fail' : 'Record not found'), 'text/plain');
}

// ---------- Stripe ----------
if ($path === '/stripe/v1/checkout/sessions' && $method === 'POST') {
    if (bearer() !== FAKE_KEYS['stripe']['secret_key']) {
        reply(401, ['error' => ['type' => 'invalid_request_error']]);
    }
    $id = 'cs_test_' . substr(md5(($_POST['client_reference_id'] ?? '') . microtime()), 0, 12);
    $amount = (int) ($_POST['line_items'][0]['price_data']['unit_amount'] ?? 0);
    newBill($state, 'stripe', $id, $amount, ['currency' => (string) ($_POST['line_items'][0]['price_data']['currency'] ?? '')]);
    save($file, $state);
    reply(200, ['id' => $id, 'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/stripe/pay/' . $id]);
}
if (preg_match('#^/stripe/v1/checkout/sessions/([A-Za-z0-9_]+)$#', $path, $m) && $method === 'GET') {
    $bill = bill($state, $m[1]);
    if ($bill === null || bearer() !== FAKE_KEYS['stripe']['secret_key']) {
        reply(404, ['error' => ['type' => 'invalid_request_error']]);
    }
    reply(200, ['id' => $bill['ref'], 'payment_status' => $bill['status'] === 'paid' ? 'paid' : 'unpaid',
        'status' => $bill['status'] === 'paid' ? 'complete' : ($bill['status'] === 'failed' ? 'expired' : 'open'),
        'amount_total' => reportedAmount($bill), 'currency' => $bill['currency'], 'payment_intent' => 'pi_' . $bill['ref']]);
}
// Refund (B11): payment_intent pi_{ref} mesti dibayar; jumlah refund ≤ amaun bil; Idempotency-Key sama → jawapan sama
if ($path === '/stripe/v1/refunds' && $method === 'POST') {
    $ref = preg_replace('/^pi_/', '', (string) ($_POST['payment_intent'] ?? ''));
    $bill = bill($state, $ref);
    $key = (string) ($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '');
    if ($bill === null || bearer() !== FAKE_KEYS['stripe']['secret_key']) {
        reply(404, ['error' => ['type' => 'invalid_request_error']]);
    }
    if ($key !== '' && isset($state['refund_keys'][$key])) {
        reply(200, $state['refund_keys'][$key]);
    }
    $amount = (int) ($_POST['amount'] ?? 0);
    $refunded = (int) ($bill['refunded'] ?? 0);
    if ($bill['status'] !== 'paid' || $amount <= 0 || $refunded + $amount > (int) $bill['amount']) {
        reply(400, ['error' => ['type' => 'invalid_request_error', 'code' => 'charge_already_refunded']]);
    }
    $state['bills'][$ref]['refunded'] = $refunded + $amount;
    $result = ['id' => 're_' . substr(md5($ref . $key . microtime()), 0, 12), 'object' => 'refund', 'amount' => $amount, 'status' => 'succeeded'];
    $state['refund_keys'][$key] = $result;
    save($file, $state);
    reply(200, $result);
}
if ($path === '/stripe/v1/balance') {
    $ok = bearer() === FAKE_KEYS['stripe']['secret_key'];
    reply($ok ? 200 : 401, $ok ? ['object' => 'balance'] : ['error' => ['type' => 'invalid_request_error']]);
}

reply(404, ['error' => 'fake gateway: ' . $method . ' ' . $path]);
