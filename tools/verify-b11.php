<?php
// Semakan B11 — refund (batal / pulang barang, stok melalui StockLedger, tunai syif POS, gateway Stripe palsu, gagal, serentak)
// & laporan (harian, bulanan, tunai vs online, produk, untung, stok, darjah, kelas) + ujian kebocoran VENDOR01 ↔ VENDOR02 dan
// ibu bapa A ↔ B. CLI sahaja:
//   php tools/verify-b11.php
// Perlu: DB koperasi_saas (schema + seed + migrasi 005), config/database.php, config/secret.key, sambungan PHP curl.
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

function items($orderId)
{
    return Database::fetchAll("SELECT * FROM order_items WHERE order_id = ? ORDER BY id", [(int) $orderId]);
}

function level($productId, $cooperativeId = 1)
{
    $row = Database::fetchOne("SELECT qty_on_hand, qty_reserved FROM stock_levels WHERE cooperative_id = ? AND product_id = ? AND variant_id = 0",
        [$cooperativeId, $productId]);
    return [(int) $row['qty_on_hand'], (int) $row['qty_reserved']];
}

function setStock($productId, $onHand, $cooperativeId = 1)
{
    Database::execute("UPDATE stock_levels SET qty_on_hand = ?, qty_reserved = 0 WHERE cooperative_id = ? AND product_id = ? AND variant_id = 0",
        [$onHand, $cooperativeId, $productId]);
}

function refundCount()
{
    return (int) Database::fetchValue("SELECT COUNT(*) FROM refunds");
}

function fakeGateway($fakeBase, $path)
{
    return json_decode((string) file_get_contents($fakeBase . $path), true);
}

// Pesanan store (troli dikosongkan dahulu). Pulangkan id pesanan atau 0
function place(Browser $parent, $productId, $qty, $childId, $method = 'cash', $slug = 'sk-taman-melati')
{
    Database::execute("DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.customer_id = (SELECT customer_id FROM children WHERE id = ?)",
        [$childId]);
    $parent->post('store/cart/add', ['slug' => $slug, 'product_id' => $productId, 'qty' => $qty, 'child_id' => $childId]);
    $res = $parent->post('store/checkout/place', ['slug' => $slug, 'pickup_by_type' => 'parent', 'payment_method' => $method]);
    return ok($res) ? (int) $res['data']['order_id'] : 0;
}

function refund(Browser $browser, array $coop, $orderId, $amount, $method = 'cash', array $items = [], $restock = true, $reason = 'Ujian B11')
{
    return $browser->post('vendor/refunds/create', $coop + ['order_id' => $orderId, 'amount' => $amount, 'method' => $method,
        'items' => $items, 'restock' => $restock, 'reason' => $reason]);
}

function pinLogin(Browser $device, array $coop, $userId, $pin = '1234')
{
    return $device->post('vendor/pos/pin-login', $coop + ['user_id' => $userId, 'pin' => $pin]);
}

function itemLine(array $item, $qty)
{
    return ['item_id' => (int) $item['id'], 'qty' => $qty];
}

$port = 8100;
$fakePort = 8101;
$base = 'http://127.0.0.1:' . $port;
$fakeBase = 'http://127.0.0.1:' . $fakePort;
$log = sys_get_temp_dir() . '/kop-verify-b11.log';
$stateFile = sys_get_temp_dir() . '/kop-verify-b11-gateway.json';
@unlink($stateFile);
$fake = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $fakePort, __DIR__ . '/fake-gateway.php'], [1 => ['file', $log . '.fake', 'w'], 2 => ['file', $log . '.fake', 'a']],
    $fakePipes, null, ['FAKE_GATEWAY_STATE' => $stateFile] + getenv());
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4', 'KOPERASI_GATEWAY_TEST_URL' => $fakeBase] + getenv());
usleep(900000);
putenv('KOPERASI_GATEWAY_TEST_URL=' . $fakeBase);

$tables = ['orders', 'order_items', 'order_status_logs', 'order_pickups', 'order_pickup_items', 'stock_levels', 'stock_movements', 'carts',
    'cart_items', 'promotions', 'payments', 'refunds', 'refund_items', 'webhook_logs', 'cooperative_gateways', 'cooperative_settings',
    'activity_logs', 'email_queue', 'pos_shifts', 'vendor_users', 'login_attempts', 'rate_limits', 'payment_gateways'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
Database::execute("DELETE FROM rate_limits WHERE bucket_key LIKE 'pos_%'");
Database::execute('DELETE FROM cart_items');
Database::execute('DELETE FROM carts');
Database::execute("UPDATE promotions SET is_active = 0");
Database::execute("UPDATE payment_gateways SET is_enabled_for_vendors = 1 WHERE code = 'stripe'");
CooperativeSettings::set(1, 1, ['tax_rate' => '0']);
setStock(1, 500);
setStock(2, 100);
setStock(5, 50);

try {
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $cashier1 = vendorLogin($base, 'kasim@niagasekolah.my');
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    list($parentB) = storeLogin($base, 'rajesh@contoh.my');
    $c1 = ['cooperative_id' => 1];
    $c2 = ['cooperative_id' => 2];
    $c3 = ['cooperative_id' => 3];
    $today = date('Y-m-d');
    $range = ['date_from' => date('Y-m-01'), 'date_to' => $today];

    echo "1. Peranan & keselamatan laluan\n";
    $guest = new Browser($base);
    check('Tanpa sesi → 401 (refunds/list, refunds/order, reports/run)', $guest->status('vendor/refunds/list', $c1) === 401
        && $guest->status('vendor/refunds/order', $c1) === 401 && $guest->status('vendor/reports/run', $c1 + ['type' => 'stock']) === 401);
    check('Juruwang → 403 (refunds/list, refunds/order, refunds/create, reports/run)', $cashier1->status('vendor/refunds/list', $c1) === 403
        && $cashier1->status('vendor/refunds/order', $c1) === 403 && $cashier1->postStatus('vendor/refunds/create', $c1) === 403
        && $cashier1->status('vendor/reports/run', $c1 + ['type' => 'stock']) === 403);
    check('POST tanpa CSRF → 419; GET ke create → 405', $v1->postStatus('vendor/refunds/create', $c1, false) === 419
        && $v1->getStatus('vendor/refunds/create') === 405);
    check('Ibu bapa (sesi store) → laluan vendor 401', $parentA->status('vendor/refunds/list', $c1) === 401
        && $parentA->status('vendor/reports/run', $c1 + ['type' => 'stock']) === 401);
    check('Halaman: pentadbir refunds.php & reports.php 200; juruwang → dialih', $v1->page('vendor/refunds.php')['status'] === 200
        && $v1->page('vendor/reports.php')['status'] === 200 && $cashier1->page('vendor/refunds.php')['status'] === 302
        && $cashier1->page('vendor/reports.php')['status'] === 302);

    echo "2. Cari pesanan untuk refund\n";
    $o1 = place($parentA, 1, 6, 1);
    $row = order($o1);
    $res = $v1->get('vendor/refunds/order', $c1 + ['order_id' => $o1]);
    check('Pesanan tunai belum dibayar → refund.order_not_paid', $o1 > 0 && !ok($res) && $res['error'] === t('refund.order_not_paid'));
    $paid = $v1->post('vendor/handover/receive-cash', $c1 + ['id' => $o1, 'amount_received' => 1080]);
    $byNo = $v1->get('vendor/refunds/order', $c1 + ['q' => $row['order_no']]);
    $byCode = $v1->get('vendor/refunds/order', $c1 + ['q' => strtolower($row['short_code'])]);
    $data = $byNo['data'] ?? [];
    check('Selepas tunai diterima: cari ikut no. pesanan & kod pendek (huruf kecil) → pesanan sama', ok($paid) && ok($byNo) && ok($byCode)
        && $data['id'] === $o1 && $byCode['data']['id'] === $o1);
    check('RefundOrder: jumlah 1080, dibayar 1080, boleh direfund 1080, 6 unit boleh dipulang, tiada gateway, tiada syif laci',
        $data['total'] === 1080 && $data['paid_amount'] === 1080 && $data['refundable_amount'] === 1080 && $data['refunded_amount'] === 0
        && $data['items'][0]['returnable_qty'] === 6 && $data['gateway_supports_refund'] === false && $data['drawer_shift'] === null
        && $data['channel'] === 'online' && $data['items'][0]['child_name'] === 'Aisyah binti Faizal');
    $res = $v1->get('vendor/refunds/order', $c1 + ['q' => 'TIADA-0000']);
    check('No. tidak wujud → refund.order_not_found', !ok($res) && $res['error'] === t('refund.order_not_found'));
    check('VENDOR02: koperasi 1 → 404; order_id koperasi 1 melalui koperasi 2 → tidak dijumpai',
        $v2->status('vendor/refunds/order', $c1 + ['order_id' => $o1]) === 404
        && ($v2->get('vendor/refunds/order', $c2 + ['order_id' => $o1])['error'] ?? '') === t('refund.order_not_found')
        && ($v2->get('vendor/refunds/order', $c2 + ['q' => $row['order_no']])['error'] ?? '') === t('refund.order_not_found'));

    echo "3. Pengesahan (pelayan mengira semula)\n";
    $item1 = items($o1)[0];
    $before = refundCount();
    $res = refund($v1, $c1, $o1, 1081);
    check('Amaun > baki boleh direfund → errors.amount (maks RM 10.80)', !ok($res)
        && $res['errors']['amount'] === t('refund.amount_range', ['max' => Money::format(1080)]));
    $res = refund($v1, $c1, $o1, 0);
    check('Amaun 0 → ditolak', !ok($res) && isset($res['errors']['amount']));
    $res = refund($v1, $c1, $o1, 100, 'cash', [itemLine($item1, 7)]);
    check('Kuantiti > baki → refund.qty_too_many', !ok($res) && $res['error'] === t('refund.qty_too_many'));
    $res = refund($v1, $c1, $o1, 100, 'cash', [itemLine($item1, 4), itemLine($item1, 3)]);
    check('Baris berganda dicantum (4 + 3 > 6) → ditolak', !ok($res) && $res['error'] === t('refund.qty_too_many'));
    $res = refund($v1, $c1, $o1, 100, 'cash', [['item_id' => 999999, 'qty' => 1]]);
    check('Item bukan milik pesanan → ditolak', !ok($res) && $res['error'] === t('refund.qty_too_many'));
    $res = refund($v1, $c1, $o1, 100, 'gateway');
    check('Gateway untuk pesanan tunai → refund.gateway_unsupported', !ok($res) && $res['errors']['method'] === t('refund.gateway_unsupported'));
    $res = refund($v1, $c1, $o1, 100, 'cek');
    $noReason = refund($v1, $c1, $o1, 100, 'cash', [], true, '');
    check('Cara tidak sah & sebab kosong → ditolak', !ok($res) && isset($res['errors']['method']) && !ok($noReason) && isset($noReason['errors']['reason']));
    check('Tiada refund disimpan selepas semua penolakan', refundCount() === $before);

    echo "4. Refund tunai — unit belum diambil dibatalkan\n";
    list($onHand, $reserved) = level(1);
    $res = refund($v1, $c1, $o1, 360, 'cash', [itemLine($item1, 2)], true, 'Ibu bapa tukar fikiran');
    $item1 = items($o1)[0];
    check('Refund RF-{no. pesanan}-1 selesai, amaun 360, 2 unit, nama kakitangan', ok($res) && $res['data']['refund_no'] === 'RF-' . $row['order_no'] . '-1'
        && $res['data']['status'] === 'completed' && $res['data']['amount'] === 360 && $res['data']['item_count'] === 2
        && $res['data']['user_name'] === 'Ahmad bin Ismail' && $res['data']['pos_shift_id'] === null);
    check('order_items.cancelled_qty = 2; refund_items.cancelled_qty = 2', (int) $item1['cancelled_qty'] === 2 && (int) $item1['returned_qty'] === 0
        && (int) Database::fetchValue("SELECT cancelled_qty FROM refund_items WHERE refund_id = ?", [$res['data']['id']]) === 2);
    check('Stok: tahanan −2 (pergerakan release), baki fizikal sama', level(1) === [$onHand, $reserved - 2]
        && (int) Database::fetchValue("SELECT COUNT(*) FROM stock_movements WHERE type = 'release' AND ref_id = ? AND note = ?",
            [$o1, $res['data']['refund_no']]) === 1);
    check('Status pesanan kekal; log status bernota refund; log aktiviti refund_create', order($o1)['status'] === $row['status']
        && (int) Database::fetchValue("SELECT COUNT(*) FROM order_status_logs WHERE order_id = ? AND note LIKE ?", [$o1, '%' . $res['data']['refund_no'] . '%']) === 1
        && (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE vendor_id = 1 AND cooperative_id = 1 AND action = 'refund_create' AND entity_id = ?",
            [$res['data']['id']]) === 1);
    $lookup = $v1->get('vendor/refunds/order', $c1 + ['order_id' => $o1])['data'];
    $detail = $v1->get('vendor/orders/get', $c1 + ['id' => $o1])['data'];
    check('Selepas refund: boleh direfund 720, baki unit 4; OrderDetail refunded_amount 360', $lookup['refundable_amount'] === 720
        && $lookup['refunded_amount'] === 360 && $lookup['items'][0]['returnable_qty'] === 4 && $detail['refunded_amount'] === 360);

    echo "5. Refund penuh amaun sahaja → refunded, baki unit dibatalkan\n";
    list($onHand, $reserved) = level(1);
    $res = refund($v1, $c1, $o1, 720, 'bank_transfer', [], true, 'Murid bertukar sekolah');
    $item1 = items($o1)[0];
    check('Status refunded, bayaran refunded, 4 unit baki dibatalkan & tahanan dilepas', ok($res) && order($o1)['status'] === 'refunded'
        && Database::fetchValue("SELECT status FROM payments WHERE order_id = ?", [$o1]) === 'refunded'
        && (int) $item1['cancelled_qty'] === 6 && level(1) === [$onHand, $reserved - 4]);
    $res = refund($v1, $c1, $o1, 1, 'cash');
    check('Tiada baki → refund lagi ditolak (maks RM 0.00); no. kedua RF-…-2 sudah wujud', !ok($res)
        && $res['errors']['amount'] === t('refund.amount_range', ['max' => Money::format(0)])
        && (int) Database::fetchValue("SELECT COUNT(*) FROM refunds WHERE refund_no = ?", ['RF-' . $row['order_no'] . '-2']) === 1);
    $collect = $v1->post('vendor/handover/collect', $c1 + ['id' => $o1, 'picked_by_type' => 'parent', 'picked_by_name' => 'Siti',
        'items' => [itemLine($item1, 1)]]);
    check('Pesanan refunded tidak boleh diserah lagi', !ok($collect) && (int) items($o1)[0]['collected_qty'] === 0);

    echo "6. Refund jualan POS — barang dipulang, tunai dari laci syif\n";
    pinLogin($cashier1, $c1, 2);
    $shift = $cashier1->post('vendor/pos/shift-open', $c1 + ['opening_cash' => 10000]);
    $shiftId = $shift['data']['id'] ?? 0;
    $sale = $cashier1->post('vendor/pos/checkout', $c1 + ['shift_id' => $shiftId, 'items' => [['product_id' => 5, 'variant_id' => 0, 'qty' => 5]],
        'payment_method' => 'cash', 'amount_received' => 2000, 'coupon_code' => '', 'manual_discount' => 0, 'customer_name' => '']);
    $pos = $sale['data']['receipt']['order_id'] ?? 0;
    $posRow = order($pos);
    $posItem = items($pos)[0] ?? [];
    check('Jualan POS 5 × Buku Jawi (RM11.00), stok 50 → 45', ok($sale) && (int) $posRow['total'] === 1100 && level(5) === [45, 0]);
    $res = $v1->get('vendor/refunds/order', $c1 + ['q' => $posRow['order_no']]);
    check('Cari jualan POS (no. resit) → channel pos, 5 unit diambil, tiada syif laci untuk pentadbir tanpa PIN', ok($res)
        && $res['data']['channel'] === 'pos' && $res['data']['items'][0]['collected_qty'] === 5 && $res['data']['drawer_shift'] === null);
    pinLogin($v1, $c1, 2);
    $res = $v1->get('vendor/refunds/order', $c1 + ['order_id' => $pos]);
    check('Pentadbir log masuk PIN juruwang Kasim → drawer_shift = syif Kasim', ok($res) && $res['data']['drawer_shift']['id'] === $shiftId);
    $res = refund($v1, $c1, $pos, 440, 'cash', [itemLine($posItem, 2)], true, 'Tersalah beli');
    $posItem = items($pos)[0];
    check('Pulang 2 unit (restock): returned_qty 2, stok +2 (pergerakan refund, ref refund), pos_shift_id = syif', ok($res)
        && (int) $posItem['returned_qty'] === 2 && (int) $posItem['cancelled_qty'] === 0 && level(5) === [47, 0]
        && (int) Database::fetchValue("SELECT COUNT(*) FROM stock_movements WHERE type = 'refund' AND ref_type = 'refund' AND ref_id = ? AND qty_change = 2",
            [$res['data']['id']]) === 1 && $res['data']['pos_shift_id'] === $shiftId && $res['data']['channel'] === 'pos');
    $res = refund($v1, $c1, $pos, 220, 'cash', [itemLine($posItem, 1)], false, 'Rosak');
    check('Pulang 1 unit tanpa restock (rosak): returned_qty 3, stok kekal 47', ok($res) && (int) items($pos)[0]['returned_qty'] === 3 && level(5) === [47, 0]);
    $summary = $cashier1->get('vendor/pos/shift-summary', $c1 + ['shift_id' => $shiftId])['data'];
    check('Ringkasan syif: refund tunai 2 (RM6.60) ditolak — jangkaan = 10000 + 1100 − 660', $summary['refund_count'] === 2
        && $summary['refund_total'] === 660 && $summary['expected_cash'] === 10000 + 1100 - 660);
    $res = refund($v1, $c1, $pos, 440, 'bank_transfer', [itemLine($posItem, 2)], true, 'Baki dipulang');
    check('Refund baki (pindahan bank) → POS refunded, bukan dari laci (pos_shift_id null)', ok($res) && order($pos)['status'] === 'refunded'
        && $res['data']['pos_shift_id'] === null && level(5) === [49, 0]);
    $receipt = $cashier1->get('vendor/pos/receipt', $c1 + ['order_id' => $pos])['data'];
    check('Resit POS: refunded_amount 1100', $receipt['refunded_amount'] === 1100);
    $closed = $cashier1->post('vendor/pos/shift-close', $c1 + ['shift_id' => $shiftId, 'closing_cash' => 10440]);
    check('Tutup syif: jangkaan dibekukan 10440, beza 0', ok($closed) && $closed['data']['expected_cash'] === 10440 && $closed['data']['variance'] === 0);
    $v1->post('vendor/pos/lock', $c1);

    echo "7. Barang store yang sudah diambil → dipulang\n";
    $o2 = place($parentA, 2, 3, 2);
    $v1->post('vendor/handover/receive-cash', $c1 + ['id' => $o2, 'amount_received' => 1950]);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $o2, 'status' => 'preparing']);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $o2, 'status' => 'ready']);
    $item2 = items($o2)[0];
    $collect = $v1->post('vendor/handover/collect', $c1 + ['id' => $o2, 'picked_by_type' => 'parent', 'picked_by_name' => 'Siti',
        'items' => [itemLine($item2, 2)]]);
    list($onHand, $reserved) = level(2);
    check('2 daripada 3 diambil → partially_collected', ok($collect) && order($o2)['status'] === 'partially_collected');
    $res = refund($v1, $c1, $o2, 1300, 'cash', [itemLine($item2, 2)], true, 'Pulang 1 + batal 1');
    $item2 = items($o2)[0];
    check('Refund 2 unit: 1 belum diambil dibatal (tahanan −1) + 1 dipulang (stok +1)', ok($res) && (int) $item2['cancelled_qty'] === 1
        && (int) $item2['returned_qty'] === 1 && level(2) === [$onHand + 1, $reserved - 1]);
    check('Tiada baki untuk diambil → status collected (bayaran baki kekal)', order($o2)['status'] === 'collected' && order($o2)['collected_at'] !== null);

    echo "8. Refund melalui gateway (Stripe palsu)\n";
    $saved = $v1->post('vendor/payments/save-gateway', $c1 + ['gateway_code' => 'stripe', 'mode' => 'sandbox',
        'credentials' => ['publishable_key' => 'pk_test_x', 'secret_key' => 'sk_test_fake', 'webhook_secret' => 'whsec_test']]);
    $o3 = place($parentA, 2, 2, 1, 'online');
    $payment = Database::fetchOne("SELECT * FROM payments WHERE order_id = ?", [$o3]);
    file_get_contents($fakeBase . '/_admin/set?' . http_build_query(['ref' => $payment['gateway_ref'], 'status' => 'paid']));
    $gatewayRow = CooperativeGateways::active(1, 1);
    $settled = PaymentSettlement::recheck($payment, CooperativeGateways::driver($gatewayRow));
    check('Pesanan online Stripe 2 × RM6.50 dibayar', ok($saved) && $o3 > 0 && order($o3)['status'] === 'paid');
    $lookup = $v1->get('vendor/refunds/order', $c1 + ['order_id' => $o3])['data'];
    check('RefundOrder: gateway Stripe menyokong refund automatik', $lookup['gateway_name'] === 'Stripe' && $lookup['gateway_supports_refund'] === true);
    $item3 = items($o3)[0];
    list($onHand, $reserved) = level(2);
    $res = refund($v1, $c1, $o3, 650, 'gateway', [itemLine($item3, 1)], true, 'Tidak jadi');
    $refundRow = Database::fetchOne("SELECT * FROM refunds WHERE id = ?", [$res['data']['id'] ?? 0]);
    $bill = fakeGateway($fakeBase, '/_admin/bills')[$payment['gateway_ref']];
    check('Gateway: refund selesai, gateway_ref re_…, gateway memulangkan 650, payment_id bil itu', ok($res) && $res['data']['status'] === 'completed'
        && strpos((string) $refundRow['gateway_ref'], 're_') === 0 && (int) $bill['refunded'] === 650 && (int) $refundRow['payment_id'] === (int) $payment['id']);
    check('Gateway: 1 unit dibatal, tahanan −1', (int) items($o3)[0]['cancelled_qty'] === 1 && level(2) === [$onHand, $reserved - 1]);
    $res = refund($v1, $c1, $o3, 651, 'gateway');
    check('Gateway: amaun > baki bayaran (RM6.50) → ditolak', !ok($res) && $res['errors']['amount'] === t('refund.amount_range', ['max' => Money::format(650)]));
    fakeGateway($fakeBase, '/_admin/down?on=1');
    $res = refund($v1, $c1, $o3, 100, 'gateway', [itemLine($item3, 1)], true, 'Gateway mati');
    fakeGateway($fakeBase, '/_admin/down?on=0');
    $failedRow = Database::fetchOne("SELECT * FROM refunds WHERE order_id = ? ORDER BY id DESC LIMIT 1", [$o3]);
    check('Gateway gagal → refund.gateway_failed, baris refund failed, tiada kesan stok/item, log refund_failed', !ok($res)
        && $res['error'] === t('refund.gateway_failed') && $failedRow['status'] === 'failed' && (int) items($o3)[0]['cancelled_qty'] === 1
        && level(2) === [$onHand, $reserved - 1] && (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE action = 'refund_failed' AND entity_id = ?",
            [(int) $failedRow['id']]) === 1);
    $lookup = $v1->get('vendor/refunds/order', $c1 + ['order_id' => $o3])['data'];
    check('Refund gagal tidak mengurangkan baki (boleh direfund 650, 1 unit)', $lookup['refundable_amount'] === 650 && $lookup['items'][0]['returnable_qty'] === 1);
    Database::execute("INSERT INTO refunds (vendor_id, cooperative_id, refund_no, order_id, payment_id, amount, reason, method, restock, status)
                       VALUES (1, 1, 'RF-UJI-PENDING', ?, ?, 650, 'x', 'gateway', 1, 'pending')", [$o3, (int) $payment['id']]);
    Database::execute("INSERT INTO refund_items (vendor_id, refund_id, order_item_id, qty) VALUES (1, LAST_INSERT_ID(), ?, 1)", [(int) $item3['id']]);
    $lookup = $v1->get('vendor/refunds/order', $c1 + ['order_id' => $o3])['data'];
    $res = refund($v1, $c1, $o3, 1, 'cash');
    check('Refund gateway pending "menempah" baki & unit (boleh direfund 0, unit 0, pending 650)', $lookup['refundable_amount'] === 0
        && $lookup['pending_amount'] === 650 && $lookup['items'][0]['returnable_qty'] === 0 && !ok($res));
    Database::execute("DELETE ri FROM refund_items ri JOIN refunds r ON r.id = ri.refund_id WHERE r.refund_no = 'RF-UJI-PENDING'");
    Database::execute("DELETE FROM refunds WHERE refund_no = 'RF-UJI-PENDING'");
    $driver = CooperativeGateways::driver($gatewayRow);
    $first = $driver->refund($payment['gateway_ref'], 100, 'RF-IDEM-1');
    $second = $driver->refund($payment['gateway_ref'], 100, 'RF-IDEM-1');
    check('Driver Stripe: Idempotency-Key sama → id refund sama, wang dipulang sekali', $first['refund_ref'] === $second['refund_ref']
        && (int) fakeGateway($fakeBase, '/_admin/bills')[$payment['gateway_ref']]['refunded'] === 750);
    $toyyib = GatewayFactory::make('toyyibpay', [], 'sandbox');
    $threw = false;
    try {
        $toyyib->refund('X', 100, 'RF-X');
    } catch (GatewayException $error) {
        $threw = true;
    }
    check('Driver tanpa sokongan refund → GatewayException', $threw && $toyyib->supportsRefund() === false);

    echo "9. Bayaran diterima selepas pesanan dibatal (paid_late)\n";
    $o4 = place($parentA, 1, 2, 1, 'online');
    $cancel = $parentA->post('store/orders/cancel', ['slug' => 'sk-taman-melati', 'id' => $o4]);
    list($onHand, $reserved) = level(1);
    $payment4 = Database::fetchOne("SELECT * FROM payments WHERE order_id = ?", [$o4]);
    $late = PaymentSettlement::apply((int) $payment4['id'], ['status' => 'paid', 'amount' => (int) $payment4['amount'], 'currency' => 'MYR', 'raw' => []], true);
    $lookup = $v1->get('vendor/refunds/order', $c1 + ['order_id' => $o4]);
    check('Dibatal → dibayar lewat: boleh dicari, dibayar 360 walaupun paid_at kosong', ok($cancel) && $late === 'paid_late' && ok($lookup)
        && $lookup['data']['status'] === 'cancelled' && $lookup['data']['paid_amount'] === 360 && order($o4)['paid_at'] === null);
    $res = refund($v1, $c1, $o4, 360, 'bank_transfer', [], true, 'Dibayar selepas batal');
    check('Refund penuh → refunded; tahanan tidak dilepas dua kali', ok($res) && order($o4)['status'] === 'refunded' && level(1) === [$onHand, $reserved]);

    echo "10. Serentak\n";
    $o5 = place($parentA, 1, 1, 1);
    $v1->post('vendor/handover/receive-cash', $c1 + ['id' => $o5, 'amount_received' => 180]);
    $v1b = vendorLogin($base, 'ahmad@niagasekolah.my');
    $body = $c1 + ['order_id' => $o5, 'amount' => 180, 'method' => 'cash', 'items' => [], 'restock' => true, 'reason' => 'Serentak'];
    $results = parallelPost([[$v1, 'vendor/refunds/create', $body], [$v1b, 'vendor/refunds/create', $body]]);
    check('Dua refund penuh serentak → tepat satu berjaya, jumlah refund = 180', count(array_filter($results, 'ok')) === 1
        && (int) Database::fetchValue("SELECT SUM(amount) FROM refunds WHERE order_id = ? AND status = 'completed'", [$o5]) === 180);

    echo "11. Senarai refund\n";
    $list = $v1->get('vendor/refunds/list', $c1 + ['per_page' => 50]);
    $completed = (int) Database::fetchValue("SELECT SUM(amount) FROM refunds WHERE cooperative_id = 1 AND status = 'completed'");
    check('Senarai koperasi 1: semua refund, total_amount = jumlah selesai, terkini dahulu', ok($list)
        && $list['data']['total'] === (int) Database::fetchValue("SELECT COUNT(*) FROM refunds WHERE cooperative_id = 1")
        && $list['data']['total_amount'] === $completed && $list['data']['items'][0]['order_id'] === $o5);
    $cash = $v1->get('vendor/refunds/list', $c1 + ['method' => 'cash', 'per_page' => 50]);
    $failedList = $v1->get('vendor/refunds/list', $c1 + ['status' => 'failed']);
    $search = $v1->get('vendor/refunds/list', $c1 + ['q' => 'RF-' . $posRow['order_no']]);
    check('Tapis cara tunai, status gagal (1), q no. refund POS (3)', count(array_filter($cash['data']['items'], function ($r) { return $r['method'] !== 'cash'; })) === 0
        && $failedList['data']['total'] === 1 && $failedList['data']['items'][0]['status'] === 'failed' && $search['data']['total'] === 3);
    $dated = $v1->get('vendor/refunds/list', $c1 + ['date_from' => '2020-01-01', 'date_to' => '2020-01-31']);
    check('Tapis tarikh lampau → kosong', ok($dated) && $dated['data']['total'] === 0 && $dated['data']['total_amount'] === 0);

    echo "12. Laporan — pengesahan julat\n";
    $res = $v1->get('vendor/reports/run', $c1 + ['type' => 'daily']);
    check('Tanpa tarikh → ditolak', !ok($res) && isset($res['errors']['date_from']));
    $res = $v1->get('vendor/reports/run', $c1 + ['type' => 'daily', 'date_from' => '2026-10-10', 'date_to' => '2026-10-01']);
    check('Tamat sebelum mula → report.range_invalid', !ok($res) && $res['errors']['date_to'] === t('report.range_invalid'));
    $res = $v1->get('vendor/reports/run', $c1 + ['type' => 'daily', 'date_from' => '2025-01-01', 'date_to' => '2026-01-05']);
    check('Lebih 1 tahun → report.range_too_long', !ok($res) && $res['errors']['date_to'] === t('report.range_too_long'));
    $res = $v1->get('vendor/reports/run', $c1 + ['type' => 'rahsia'] + $range);
    check('Jenis tidak sah → ditolak', !ok($res) && isset($res['errors']['type']));

    echo "13. Laporan — jualan\n";
    list($paidSql, $paidArgs) = ReportRange::between('paid_at', $today, $today);
    $sales = Database::fetchOne("SELECT COUNT(*) AS n, SUM(total) AS total, SUM(CASE WHEN payment_method = 'cash' THEN total ELSE 0 END) AS cash
                                   FROM orders WHERE vendor_id = 1 AND cooperative_id = 1 AND paid_at IS NOT NULL$paidSql", $paidArgs);
    $refunded = (int) Database::fetchValue("SELECT SUM(r.amount) FROM refunds r JOIN orders o ON o.id = r.order_id
                                             WHERE r.cooperative_id = 1 AND r.status = 'completed' AND o.paid_at IS NOT NULL AND DATE(r.created_at) = CURDATE()");
    $daily = $v1->get('vendor/reports/run', $c1 + ['type' => 'daily', 'date_from' => $today, 'date_to' => $today]);
    $d = $daily['data'] ?? [];
    $todayRow = $d['rows'][0] ?? [];
    check('Harian: satu baris hari ini, pesanan & bersih = jumlah − refund (paid_late tidak dikira)', ok($daily) && count($d['rows']) === 1
        && $todayRow['date'] === $today && $todayRow['orders'] === (int) $sales['n'] && $todayRow['refunds'] === $refunded
        && $todayRow['net'] === (int) $sales['total'] - $refunded);
    check('Harian: tunai + online = bersih; jumlah baris = totals; lajur & carta', $todayRow['cash'] + $todayRow['online'] === $todayRow['net']
        && $d['totals']['net'] === $todayRow['net'] && $d['columns'][0] === ['key' => 'date', 'label' => 'report.col.date', 'format' => 'date']
        && count($d['chart']) === 1 && $d['school_name'] === 'SK Taman Melati' && $d['date_from'] === $today);
    $monthly = $v1->get('vendor/reports/run', $c1 + ['type' => 'monthly'] + $range)['data'];
    $monthDaily = $v1->get('vendor/reports/run', $c1 + ['type' => 'daily'] + $range)['data'];
    check('Bulanan: jumlah bersih = jumlah harian bulan yang sama', $monthly['totals']['net'] === $monthDaily['totals']['net']
        && $monthly['rows'][count($monthly['rows']) - 1]['month'] === date('Y-m'));
    $payment = $v1->get('vendor/reports/run', $c1 + ['type' => 'payment', 'date_from' => $today, 'date_to' => $today])['data'];
    $methods = array_column($payment['rows'], null, 'method');
    check('Tunai vs online: amaun & refund sama dengan harian; saluran online = Stripe; bahagian 100%',
        $payment['totals']['amount'] === (int) $sales['total'] && $payment['totals']['refunds'] === $refunded
        && $methods['online']['channel'] === 'Stripe' && $methods['cash']['amount'] === (int) $sales['cash']
        && abs($methods['cash']['share'] + $methods['online']['share'] - 100) < 0.2);

    echo "14. Laporan — produk, untung, darjah, kelas, stok\n";
    $product = $v1->get('vendor/reports/run', $c1 + ['type' => 'product', 'date_from' => $today, 'date_to' => $today])['data'];
    $bySku = array_column($product['rows'], null, 'sku');
    check('Produk: Buku Jawi bersih 0 unit (5 dijual, 5 dipulang) → tiada baris', !isset($bySku['BL-JAWI']));
    $pencil = $bySku['PS-2B12'] ?? null;
    $pencilNet = (int) Database::fetchValue("SELECT SUM(oi.qty - oi.cancelled_qty - oi.returned_qty) FROM order_items oi JOIN orders o ON o.id = oi.order_id
                                              WHERE o.cooperative_id = 1 AND o.paid_at IS NOT NULL AND oi.product_id = 2 AND DATE(o.paid_at) = CURDATE()");
    check('Produk: Pensel 2B unit bersih, jualan = unit × 650, kos = unit × 420, margin', $pencil !== null && $pencil['qty'] === $pencilNet
        && $pencil['sales'] === $pencilNet * 650 && $pencil['cost'] === $pencilNet * 420 && $pencil['margin'] === round(230 / 650 * 100, 1));
    $profit = $v1->get('vendor/reports/run', $c1 + ['type' => 'profit', 'date_from' => $today, 'date_to' => $today])['data'];
    check('Untung: jumlah jualan & kos sama dengan laporan produk', $profit['totals']['sales'] === $product['totals']['sales']
        && $profit['totals']['cost'] === $product['totals']['cost'] && $profit['totals']['profit'] === $product['totals']['profit']);
    $grade = $v1->get('vendor/reports/run', $c1 + ['type' => 'grade', 'date_from' => $today, 'date_to' => $today])['data'];
    $class = $v1->get('vendor/reports/run', $c1 + ['type' => 'class', 'date_from' => $today, 'date_to' => $today])['data'];
    $grades = array_column($grade['rows'], null, 'grade');
    check('Darjah: D1 & D4 (snapshot), murid berbeza, POS tanpa darjah tidak dikira; susunan D1 sebelum D4', isset($grades['D1'], $grades['D4'])
        && $grades['D4']['students'] === 1 && $grade['rows'][0]['grade'] === 'D1' && $grade['columns'][0]['format'] === 'grade');
    check('Kelas: lajur kelas, jumlah jualan sama dengan darjah', $class['columns'][1]['key'] === 'class_name' && $class['rows'][0]['class_name'] === 'Bestari'
        && $class['totals']['sales'] === $grade['totals']['sales'] && $class['chart'] === null);
    $stock = $v1->get('vendor/reports/run', $c1 + ['type' => 'stock']);
    $s = $stock['data'] ?? [];
    $stockSku = array_column($s['rows'] ?? [], null, 'sku');
    list($onHand) = level(1);
    check('Stok: tanpa tarikh (null), 7 baris (varian sahaja untuk baju), Buku Garis baki & nilai', ok($stock) && $s['date_from'] === null
        && count($s['rows']) === 7 && !isset($stockSku['BJ-PL']) && isset($stockSku['BJ-PL-L']) && $stockSku['BL-080']['on_hand'] === $onHand
        && $stockSku['BL-080']['cost_value'] === $onHand * 110 && $stockSku['BL-080']['retail_value'] === $onHand * 180
        && $stockSku['BJ-PL-L']['retail_value'] === $stockSku['BJ-PL-L']['on_hand'] * 2800);

    echo "15. Ujian kebocoran VENDOR01 ↔ VENDOR02\n";
    check('VENDOR02 → refunds/list, reports/run koperasi 1 → 404', $v2->status('vendor/refunds/list', $c1) === 404
        && $v2->status('vendor/reports/run', $c1 + ['type' => 'stock']) === 404);
    $before = refundCount();
    $res = refund($v2, $c2, $o2, 100);
    check('VENDOR02 refund pesanan koperasi 1 melalui koperasi 2/3 → 404, tiada apa disimpan', !ok($res)
        && $v2->postStatus('vendor/refunds/create', $c3 + ['order_id' => $o2, 'amount' => 100, 'method' => 'cash', 'reason' => 'x']) === 404
        && $v2->postStatus('vendor/refunds/create', $c1 + ['order_id' => $o2, 'amount' => 100, 'method' => 'cash', 'reason' => 'x']) === 404
        && refundCount() === $before);
    $list2 = $v2->get('vendor/refunds/list', $c2);
    check('Senarai refund koperasi 2 kosong (refund V01 tidak bocor)', ok($list2) && $list2['data']['total'] === 0 && $list2['data']['total_amount'] === 0);
    $daily2 = $v2->get('vendor/reports/run', $c2 + ['type' => 'daily', 'date_from' => $today, 'date_to' => $today])['data'];
    $product2 = $v2->get('vendor/reports/run', $c2 + ['type' => 'product', 'date_from' => $today, 'date_to' => $today])['data'];
    $stock2 = $v2->get('vendor/reports/run', $c2 + ['type' => 'stock'])['data'];
    check('Laporan koperasi 2: tiada jualan/refund V01; stok produk koperasi 2 sahaja', $daily2['rows'] === [] && $product2['rows'] === []
        && $daily2['school_name'] === 'SJKC Chung Hwa' && !in_array('BL-080', array_column($stock2['rows'], 'sku'), true)
        && in_array('ZH-BL80', array_column($stock2['rows'], 'sku'), true));
    $stock3 = $v2->get('vendor/reports/run', $c3 + ['type' => 'stock'])['data'];
    check('Laporan stok koperasi 3 berbeza dengan koperasi 2', !in_array('ZH-BL80', array_column($stock3['rows'], 'sku'), true));

    echo "16. Ujian kebocoran ibu bapa A ↔ B\n";
    $ordersA = $parentA->get('store/orders/list', ['slug' => 'sk-taman-melati']);
    $statuses = array_column($ordersA['data']['items'] ?? [], 'status', 'id');
    check('Ibu bapa A melihat pesanan sendiri berstatus refunded', ($statuses[$o1] ?? '') === 'refunded');
    check('Ibu bapa B tidak boleh membuka pesanan A yang direfund (404)', $parentB->status('store/orders/get', ['slug' => 'sk-taman-melati', 'id' => $o1]) === 404
        && !in_array($o1, array_column($parentB->get('store/orders/list', ['slug' => 'sk-taman-melati'])['data']['items'] ?? [], 'id'), true));

    echo "17. Frontend\n";
    $api = file_get_contents(APP_ROOT . '/assets/js/api.js');
    check("Api.livePrefixes: 'vendor/refunds/', 'vendor/reports/'", strpos($api, "'vendor/refunds/', 'vendor/reports/'") !== false);
    $mock = '';
    foreach (glob(APP_ROOT . '/assets/js/mock/*.js') as $file) {
        $mock .= file_get_contents($file);
    }
    check('Mock refund & laporan dibuang (vendor-orders.js, MockOrders, MockFinance.gateways/refunds, Mock.register vendor/refunds|reports)',
        !is_file(APP_ROOT . '/assets/js/mock/vendor-orders.js') && strpos($mock, 'MockOrders.') === false
        && strpos($mock, 'MockFinance.gateways') === false && strpos($mock, 'MockFinance.refunds') === false
        && !preg_match("#Mock\.register\('vendor/(refunds|reports)/#", $mock));
    $footer = file_get_contents(APP_ROOT . '/vendor/_layout/footer.php') . file_get_contents(APP_ROOT . '/vendor/_layout/pos-footer.php');
    check('Layout vendor tidak memuat vendor-orders.js', strpos($footer, 'vendor-orders.js') === false);
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
