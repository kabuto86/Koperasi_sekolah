<?php
// Semakan B9 — pengurusan pesanan & serah barang: senarai/butiran/status pesanan vendor, picking list, label beg, carian serah
// (cara A/B/C ikut tetapan), terima tunai (baris payments), serah (stok, log, e-mel), kod 4 digit (5 salah → dikunci, buka oleh
// pengurus), serentak, tetapan koperasi, ibu bapa & nota + ujian kebocoran VENDOR01 ↔ VENDOR02, juruwang koperasi lain,
// ibu bapa A ↔ B. CLI sahaja:
//   php tools/verify-b9.php
// Perlu: DB koperasi_saas (schema + seed + migrasi 004), config/database.php, config/secret.key, sambungan PHP curl.
// Skrip memulakan pelayan aplikasi (php -S, 4 pekerja). Jadual yang diubah dipulihkan di hujung.
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

function level($productId, $cooperativeId = 1)
{
    return Database::fetchOne(
        "SELECT qty_on_hand, qty_reserved FROM stock_levels WHERE cooperative_id = ? AND product_id = ? AND variant_id = 0",
        [$cooperativeId, $productId]
    );
}

function emails($template, $to)
{
    return (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue WHERE template_key = ? AND to_email = ?", [$template, $to]);
}

// Pesanan tunai store (troli dikosongkan dahulu). Pulangkan id pesanan atau 0
function placeCash(Browser $parent, $slug, $productId, $qty, $childId, $code = null)
{
    Database::execute("DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.customer_id = (SELECT customer_id FROM children WHERE id = ?)",
        [$childId]);
    $parent->post('store/cart/add', ['slug' => $slug, 'product_id' => $productId, 'qty' => $qty, 'child_id' => $childId]);
    $body = ['slug' => $slug, 'pickup_by_type' => 'student', 'payment_method' => 'cash'] + ($code === null ? [] : ['pickup_code' => $code]);
    $res = $parent->post('store/checkout/place', $body);
    return ok($res) ? (int) $res['data']['order_id'] : 0;
}

// Pesanan online yang dibayar melalui PaymentSettlement (seperti panggilan balik gateway yang disahkan)
function placePaidOnline(Browser $parent, $productId, $qty, $childId)
{
    $id = placeCash($parent, 'sk-taman-melati', $productId, $qty, $childId);
    $row = order($id);
    Database::execute("UPDATE orders SET payment_method = 'online', status = 'pending_payment', expires_at = NULL WHERE id = ?", [$id]);
    $paymentId = Database::insert(
        "INSERT INTO payments (vendor_id, cooperative_id, order_id, method, gateway_code, gateway_ref, amount, status, raw_payload)
         VALUES (?, ?, ?, 'online', 'toyyibpay', ?, ?, 'pending', '{}')",
        [(int) $row['vendor_id'], (int) $row['cooperative_id'], $id, 'B9TEST' . $id, (int) $row['total']]
    );
    $result = PaymentSettlement::apply($paymentId, ['status' => 'paid', 'amount' => (int) $row['total'], 'currency' => 'MYR', 'raw' => []], true);
    return [$id, $result];
}

$port = 8097;
$base = 'http://127.0.0.1:' . $port;
$log = sys_get_temp_dir() . '/kop-verify-b9.log';
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4'] + getenv());
usleep(900000);

$tables = ['orders', 'order_items', 'order_status_logs', 'order_pickups', 'order_pickup_items', 'stock_levels', 'stock_movements', 'carts',
    'cart_items', 'promotions', 'payments', 'activity_logs', 'email_queue', 'cooperative_settings', 'cooperatives', 'vendor_customer_notes',
    'customers', 'children'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
Database::execute('DELETE FROM cart_items');
Database::execute('DELETE FROM carts');
Database::execute("UPDATE promotions SET is_active = 0");

try {
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $cashier1 = vendorLogin($base, 'kasim@niagasekolah.my');
    $manager2 = vendorLogin($base, 'chong@bestari.my');
    $cashier2 = vendorLogin($base, 'kavitha@bestari.my');
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    list($parentB) = storeLogin($base, 'rajesh@contoh.my');
    $c1 = ['cooperative_id' => 1];
    $c3 = ['cooperative_id' => 3];

    echo "1. Peranan & keselamatan laluan (PELAN 4)\n";
    $guest = new Browser($base);
    check('Tanpa sesi → 401 (orders/list, handover/search, settings/get)', $guest->status('vendor/orders/list', $c1) === 401
        && $guest->status('vendor/handover/search', $c1) === 401 && $guest->status('vendor/settings/get', $c1) === 401);
    check('Juruwang: orders/list, picking-list, customers/list → 403; settings/save → 403', $cashier1->status('vendor/orders/list', $c1) === 403
        && $cashier1->status('vendor/orders/picking-list', $c1) === 403 && $cashier1->status('vendor/customers/list', $c1) === 403
        && $cashier1->postStatus('vendor/settings/save', $c1) === 403 && $cashier1->postStatus('vendor/orders/set-status', $c1) === 403);
    check('Juruwang: skrin serah (search, class-options, grades/list, settings/get) → 200',
        $cashier1->status('vendor/handover/search', $c1) === 200 && $cashier1->status('vendor/orders/class-options', $c1) === 200
        && $cashier1->status('vendor/grades/list', $c1) === 200 && $cashier1->status('vendor/settings/get', $c1) === 200);
    check('Pengurus koperasi → settings/save 403 (pentadbir sahaja)', $manager2->postStatus('vendor/settings/save', ['cooperative_id' => 2]) === 403);
    check('POST tanpa CSRF → 419; GET ke POST → 405', $v1->postStatus('vendor/handover/collect', $c1, false) === 419
        && $v1->getStatus('vendor/handover/collect') === 405);

    echo "2. Tetapan koperasi (vendor/settings/get|save)\n";
    $settings = $v1->get('vendor/settings/get', $c1);
    check('get: e-mel amaran, kaedah ambil A–D, had kod RM100, bahasa ms', ok($settings) && $settings['data']['alert_emails'] === ['ahmad@niagasekolah.my']
        && $settings['data']['pickup_methods'] === ['A', 'B', 'C', 'D'] && $settings['data']['pickup_code_required_above'] === 10000
        && $settings['data']['default_language'] === 'ms' && $settings['data']['receipt_paper'] === '58' && $settings['data']['cash_order_expiry_days'] === 3);
    $cashierSettings = $cashier1->get('vendor/settings/get', $c1);
    check('get oleh juruwang: tanpa alert_emails (resit & cara ambil sahaja)', ok($cashierSettings) && !isset($cashierSettings['data']['alert_emails'])
        && $cashierSettings['data']['pickup_methods'] === ['A', 'B', 'C', 'D']);
    $valid = $settings['data'];
    $res = $v1->post('vendor/settings/save', $c1 + array_merge($valid, ['alert_emails' => [], 'pickup_methods' => ['E'], 'cash_order_expiry_days' => 0,
        'tax_rate' => 11, 'default_language' => 'xx', 'receipt_paper' => '100']));
    check('Pengesahan: e-mel wajib, ≥ 1 cara A–D, luput 1–14, cukai 0–10, bahasa, kertas', !ok($res)
        && isset($res['errors']['alert_emails'], $res['errors']['pickup_methods'], $res['errors']['cash_order_expiry_days'], $res['errors']['tax_rate'],
            $res['errors']['default_language'], $res['errors']['receipt_paper']));
    $res = $v1->post('vendor/settings/save', $c1 + array_merge($valid, ['alert_emails' => ['a@x.my', 'b@x.my', 'c@x.my', 'd@x.my', 'e@x.my', 'f@x.my']]));
    $bad = $v1->post('vendor/settings/save', $c1 + array_merge($valid, ['alert_emails' => ['bukan-emel']]));
    check('Maksimum 5 e-mel; e-mel tidak sah ditolak', !ok($res) && $res['errors']['alert_emails'] === t('settings_v.alert_emails_max', ['max' => 5])
        && !ok($bad) && $bad['errors']['alert_emails'] === t('settings_v.alert_emails_invalid'));
    $res = $v1->post('vendor/settings/save', $c1 + array_merge($valid, ['alert_emails' => ['Koperasi@Melati.my', 'ahmad@niagasekolah.my', 'koperasi@melati.my'],
        'pickup_methods' => ['A', 'B', 'C', 'D', 'E'], 'default_language' => 'en', 'tax_rate' => 6, 'receipt_footer' => '谢谢 Terima kasih',
        'receipt_show_logo' => false]));
    check('Simpan: e-mel huruf kecil & unik, E aktif, bahasa en (cooperatives), cukai 6, nota resit Cina utuh', ok($res)
        && $res['data']['alert_emails'] === ['koperasi@melati.my', 'ahmad@niagasekolah.my'] && $res['data']['pickup_methods'] === ['A', 'B', 'C', 'D', 'E']
        && Database::fetchValue("SELECT default_language FROM cooperatives WHERE id = 1") === 'en' && $res['data']['tax_rate'] === 6
        && CooperativeSettings::get(1, 1, 'receipt_footer') === '谢谢 Terima kasih' && $res['data']['receipt_show_logo'] === false);
    check('Log aktiviti settings_save', (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE vendor_id = 1 AND action = 'settings_save'") >= 1);
    check('VENDOR02 → tetapan koperasi VENDOR01 (get/save) → 404', $v2->status('vendor/settings/get', $c1) === 404
        && $v2->postStatus('vendor/settings/save', $c1 + $valid) === 404);
    $v1->post('vendor/settings/save', $c1 + $valid);
    check('Pulih tetapan asal (A–D, ms, cukai 0)', CooperativeSettings::get(1, 1, 'pickup_methods') === 'A,B,C,D'
        && Database::fetchValue("SELECT default_language FROM cooperatives WHERE id = 1") === 'ms');

    echo "3. Pesanan store di panel vendor (list / get)\n";
    $o1 = placeCash($parentA, 'sk-taman-melati', 1, 4, 1);
    $o2 = placeCash($parentA, 'sk-taman-melati', 3, 2, 2);
    $o3 = placeCash($parentA, 'sk-taman-melati', 5, 1, 1);
    $oB = placeCash($parentB, 'smk-seri-indah', 9, 2, 4);
    check('4 pesanan tunai dicipta (3 SK Taman Melati, 1 SMK Seri Indah)', $o1 && $o2 && $o3 && $oB);
    $list = $v1->get('vendor/orders/list', $c1);
    $ids = ok($list) ? array_column($list['data']['items'], 'id') : [];
    check('Senarai V01: 3 pesanan sendiri, terbaharu dahulu, tiada pesanan V02', ok($list) && $list['data']['total'] === 3 && $ids === [$o3, $o2, $o1]
        && !in_array($oB, $ids, true) && $list['data']['status_counts']['unpaid_cash'] === 3);
    $row = ok($list) ? $list['data']['items'][2] : [];
    check('OrderSummary: status tunai unpaid, anak + darjah/kelas, item_count, tanpa token/kod', ok($list) && $row['payment_status'] === 'unpaid'
        && $row['children'][0]['child_name'] === 'Aisyah binti Faizal' && $row['children'][0]['grade_level_code'] === 'D4'
        && $row['children'][0]['class_name'] === 'Bestari' && $row['item_count'] === 4 && strpos(json_encode($list), 'pickup_qr_token') === false
        && strpos(json_encode($list), 'pickup_code') === false);
    $short = order($o2)['short_code'];
    check('Tapis q (nama anak / no. pendek) & darjah/kelas', $v1->get('vendor/orders/list', $c1 + ['q' => 'Adam'])['data']['total'] === 1
        && $v1->get('vendor/orders/list', $c1 + ['q' => $short])['data']['items'][0]['id'] === $o2
        && $v1->get('vendor/orders/list', $c1 + ['grade' => 'D4', 'class_name' => 'Bestari'])['data']['total'] === 2
        && $v1->get('vendor/orders/list', $c1 + ['status' => 'ready,collected'])['data']['total'] === 0);
    $detail = $v1->get('vendor/orders/get', $c1 + ['id' => $o1]);
    check('OrderDetail: groups (sku), logs (pelaku ibu bapa), pickups kosong, requires_code false', ok($detail)
        && $detail['data']['groups'][0]['items'][0]['sku'] === 'BL-080' && $detail['data']['logs'][0]['actor'] === 'customer'
        && $detail['data']['logs'][0]['actor_name'] === 'Siti Nurhaliza binti Ahmad' && $detail['data']['pickups'] === []
        && $detail['data']['requires_code'] === false && $detail['data']['pickup_locked'] === false);
    check('Butiran tidak memulangkan token QR / hash kod', strpos(json_encode($detail), order($o1)['pickup_qr_token']) === false
        && strpos(json_encode($detail), 'hash') === false);
    check('VENDOR02 → pesanan V01 (koperasi 1 → 404; id V01 melalui koperasi 3 → 404)', $v2->status('vendor/orders/get', $c1 + ['id' => $o1]) === 404
        && $v2->status('vendor/orders/get', $c3 + ['id' => $o1]) === 404 && $v2->status('vendor/orders/list', $c1) === 404);
    $listB = $v2->get('vendor/orders/list', $c3);
    check('Senarai V02 (SMK Seri Indah): hanya pesanan ibu bapa B', ok($listB) && array_column($listB['data']['items'], 'id') === [$oB]);
    check('Pengurus V02 (koperasi 2 sahaja) → pesanan koperasi 3 → 404', $manager2->status('vendor/orders/get', $c3 + ['id' => $oB]) === 404);

    echo "4. Tukar status (PELAN 6, 18.9)\n";
    $res = $v1->post('vendor/orders/set-status', $c1 + ['id' => $o1, 'status' => 'ready']);
    check('unpaid_cash → ready terus → ditolak', !ok($res) && $res['error'] === t('order.status_not_allowed'));
    $res = $v1->post('vendor/orders/set-status', $c1 + ['id' => $o1, 'status' => 'collected']);
    check('collected melalui set-status → ditolak (hanya skrin serah)', !ok($res));
    $res = $v1->post('vendor/orders/set-status', $c1 + ['id' => $o1, 'status' => 'preparing']);
    check('unpaid_cash → preparing; log oleh kakitangan (nama)', ok($res) && $res['data']['status'] === 'preparing'
        && end($res['data']['logs'])['actor_name'] === 'Ahmad bin Ismail');
    $before = emails('order_ready', 'siti@contoh.my');
    $res = $v1->post('vendor/orders/set-status', $c1 + ['id' => $o1, 'status' => 'ready']);
    check('preparing → ready + e-mel "Sedia diambil" kepada ibu bapa', ok($res) && $res['data']['status'] === 'ready'
        && emails('order_ready', 'siti@contoh.my') === $before + 1);
    $mail = Database::fetchOne("SELECT * FROM email_queue WHERE template_key = 'order_ready' ORDER BY id DESC LIMIT 1");
    check('E-mel sedia: nama anak, kelas & waktu operasi', $mail && strpos($mail['body_html'], 'Aisyah binti Faizal') !== false
        && strpos($mail['body_html'], 'Bestari') !== false && strpos($mail['body_html'], '07:30') !== false);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $o1, 'status' => 'preparing']);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $o1, 'status' => 'ready']);
    check('ready → preparing → ready: e-mel tidak dihantar semula', emails('order_ready', 'siti@contoh.my') === $before + 1);
    $res = $v1->post('vendor/orders/set-status', $c1 + ['id' => $o3, 'status' => 'cancelled', 'note' => '']);
    check('Batal tanpa sebab → errors.note', !ok($res) && isset($res['errors']['note']));
    $reserved = level(5)['qty_reserved'];
    $res = $v1->post('vendor/orders/set-status', $c1 + ['id' => $o3, 'status' => 'cancelled', 'note' => 'Ibu bapa telefon']);
    check('Batal unpaid_cash dengan sebab → cancelled, tahanan stok dilepas', ok($res) && order($o3)['status'] === 'cancelled'
        && level(5)['qty_reserved'] === $reserved - 1 && order($o3)['cancelled_at'] !== null);
    check('VENDOR02 tukar status pesanan V01 → 404, tiada perubahan', $v2->postStatus('vendor/orders/set-status', $c1 + ['id' => $o2, 'status' => 'preparing']) === 404
        && $v2->postStatus('vendor/orders/set-status', $c3 + ['id' => $o2, 'status' => 'preparing']) === 404 && order($o2)['status'] === 'unpaid_cash');
    $res = $v1->post('vendor/orders/bulk-status', $c1 + ['ids' => [$o2, $o3, $oB], 'status' => 'preparing']);
    check('Pukal: o2 → preparing; o3 (batal) & pesanan V02 dilangkau', ok($res) && $res['data'] === ['changed' => 1, 'skipped' => 2]
        && order($o2)['status'] === 'preparing' && order($oB)['status'] === 'unpaid_cash');
    check('Pukal batal → ditolak', !ok($v1->post('vendor/orders/bulk-status', $c1 + ['ids' => [$o2], 'status' => 'cancelled'])));
    check('Log aktiviti order_status', (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE vendor_id = 1 AND action = 'order_status'") >= 5);

    echo "5. Picking list, label beg, pilihan kelas & slot\n";
    $pick = $v1->get('vendor/orders/picking-list', $c1);
    $groups = ok($pick) ? $pick['data']['groups'] : [];
    check('Picking list lalai (paid, unpaid_cash, preparing): D1 Bestari (Adam) sahaja; jumlah produk', ok($pick) && $pick['data']['order_count'] === 1
        && count($groups) === 1 && $groups[0]['grade_level_code'] === 'D1' && $groups[0]['children'][0]['child_name'] === 'Adam bin Faizal'
        && $pick['data']['totals'][0]['qty'] === 2 && $pick['data']['school_name'] === 'SK Taman Melati');
    $pick = $v1->get('vendor/orders/picking-list', $c1 + ['status' => 'ready,preparing']);
    check('Picking list status ready+preparing: D1 sebelum D4 (susunan darjah)', ok($pick)
        && array_column($pick['data']['groups'], 'grade_level_code') === ['D1', 'D4']);
    $labels = $v1->get('vendor/orders/labels', $c1 + ['order_id' => $o1]);
    check('Label beg satu pesanan: token QR untuk dicetak, bilangan barang', ok($labels) && count($labels['data']['labels']) === 1
        && $labels['data']['labels'][0]['qr_token'] === order($o1)['pickup_qr_token'] && $labels['data']['labels'][0]['item_count'] === 4);
    check('Label ikut kelas D1 → Adam sahaja', array_column($v1->get('vendor/orders/labels', $c1 + ['grade' => 'D1'])['data']['labels'], 'child_name')
        === ['Adam bin Faizal']);
    check('VENDOR02 → picking list / label koperasi 1 → 404; label pesanan V01 melalui koperasi 3 → kosong',
        $v2->status('vendor/orders/picking-list', $c1) === 404 && $v2->status('vendor/orders/labels', $c1) === 404
        && $v2->get('vendor/orders/labels', $c3 + ['order_id' => $o1])['data']['labels'] === []);
    $classes = $v1->get('vendor/orders/class-options', $c1);
    check('class-options: kelas koperasi ini sahaja', ok($classes) && in_array('Bestari', array_column($classes['data'], 'class_name'), true)
        && !in_array('Amanah', array_column($classes['data'], 'class_name'), true));
    check('slot-options: modul pembukaan sekolah tidak aktif → []', $v1->get('vendor/orders/slot-options', $c1)['data'] === []);

    echo "6. Carian serah barang — cara A/B/C ikut tetapan (PELAN 7)\n";
    $o1row = order($o1);
    $res = $cashier1->get('vendor/handover/search', $c1 + ['q' => strtolower($o1row['short_code'])]);
    check('B: no. pendek (huruf kecil pun) → satu hasil, found_by B, tetapan dipulangkan', ok($res) && count($res['data']['items']) === 1
        && $res['data']['items'][0]['id'] === $o1 && $res['data']['items'][0]['found_by'] === 'B'
        && $res['data']['settings'] === ['methods' => ['A', 'B', 'C', 'D'], 'code_required_above' => 10000]);
    $res = $cashier1->get('vendor/handover/search', $c1 + ['q' => $o1row['pickup_qr_token']]);
    check('C/D: token QR → found_by C', ok($res) && $res['data']['items'][0]['id'] === $o1 && $res['data']['items'][0]['found_by'] === 'C');
    $res = $cashier1->get('vendor/handover/search', $c1 + ['q' => 'faizal']);
    check('A: nama murid → pesanan aktif sahaja, ready dahulu (o3 dibatal tidak muncul)', ok($res)
        && array_column($res['data']['items'], 'id') === [$o1, $o2] && $res['data']['items'][0]['found_by'] === 'A');
    check('A: tapis kelas D1 Bestari tanpa teks → Adam sahaja',
        array_column($cashier1->get('vendor/handover/search', $c1 + ['grade' => 'D1', 'class_name' => 'Bestari'])['data']['items'], 'id') === [$o2]);
    check('Carian kosong → tiada hasil', $cashier1->get('vendor/handover/search', $c1)['data']['items'] === []);
    CooperativeSettings::set(1, 1, ['pickup_methods' => 'A,D']);
    $byCode = $cashier1->get('vendor/handover/search', $c1 + ['q' => $o1row['short_code']]);
    $byQr = $cashier1->get('vendor/handover/search', $c1 + ['q' => $o1row['pickup_qr_token']]);
    check('Cara B dimatikan → no. pendek tidak sepadan; D sahaja → QR masih (slip)', $byCode['data']['items'] === []
        && $byQr['data']['items'][0]['id'] === $o1);
    CooperativeSettings::set(1, 1, ['pickup_methods' => 'B']);
    check('Cara A dimatikan → carian nama tiada hasil', $cashier1->get('vendor/handover/search', $c1 + ['q' => 'Aisyah'])['data']['items'] === []);
    CooperativeSettings::set(1, 1, ['pickup_methods' => 'A,B,C,D']);
    check('Juruwang koperasi lain (V02 Kavitha) → carian koperasi 1 → 404; QR V01 melalui koperasi 3 → tiada',
        $cashier2->status('vendor/handover/search', $c1 + ['q' => 'Aisyah']) === 404
        && $cashier2->get('vendor/handover/search', $c3 + ['q' => $o1row['pickup_qr_token']])['data']['items'] === []);
    check('Juruwang V01 → koperasi 3 (V02) → 404', $cashier1->status('vendor/handover/search', $c3 + ['q' => 'Arjun']) === 404);

    echo "7. Terima tunai (payments method cash)\n";
    $res = $cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $o1, 'amount_received' => 100]);
    check('Wang kurang → errors.amount_received', !ok($res) && isset($res['errors']['amount_received']));
    $res = $cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $o1, 'amount_received' => 'RM10']);
    check('Amaun bukan nombor → ditolak', !ok($res));
    $total1 = (int) $o1row['total'];
    $res = $cashier2->post('vendor/handover/receive-cash', $c1 + ['id' => $o1, 'amount_received' => 5000]);
    check('Juruwang V02 terima tunai pesanan V01 → 404', !ok($res) && order($o1)['paid_at'] === null);
    $res = $cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $o1, 'amount_received' => 1000]);
    $payment = Database::fetchOne("SELECT * FROM payments WHERE order_id = ? AND method = 'cash'", [$o1]);
    check('Terima RM10.00 → baki dipulangkan, payment_status paid', ok($res) && $res['data']['change'] === 1000 - $total1
        && $res['data']['order']['payment_status'] === 'paid' && order($o1)['change_given'] == 1000 - $total1 && order($o1)['expires_at'] === null);
    check('Baris payments: cash, paid, amaun = jumlah, received_by = juruwang', $payment && $payment['status'] === 'paid'
        && (int) $payment['amount'] === $total1 && (int) $payment['received_by'] === 2 && $payment['verified_at'] !== null);
    $records = $v1->get('vendor/payments/list', $c1 + ['method' => 'cash']);
    check('vendor/payments/list memaparkan bayaran tunai + nama juruwang', ok($records) && $records['data']['items'][0]['order_id'] === $o1
        && $records['data']['items'][0]['received_by'] === 'Kasim bin Osman');
    $res = $cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $o1, 'amount_received' => 1000]);
    check('Terima kali kedua → "sudah dibayar", satu payments sahaja', !ok($res) && $res['error'] === t('handover.already_paid')
        && (int) Database::fetchValue("SELECT COUNT(*) FROM payments WHERE order_id = ?", [$o1]) === 1);
    check('Pesanan dibatal → tidak boleh terima tunai', !ok($cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $o3, 'amount_received' => 9999])));

    echo "8. Serah barang (order_pickups, stok, e-mel)\n";
    $res = $cashier1->post('vendor/handover/collect', $c1 + ['id' => $o2, 'picked_by_type' => 'student', 'picked_by_name' => 'Adam',
        'items' => [['item_id' => 1, 'qty' => 1]]]);
    check('Pesanan belum sedia (preparing) → handover.not_ready', !ok($res) && $res['error'] === t('handover.not_ready'));
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $o2, 'status' => 'ready']);
    $item2 = Database::fetchOne("SELECT * FROM order_items WHERE order_id = ?", [$o2]);
    $res = $cashier1->post('vendor/handover/collect', $c1 + ['id' => $o2, 'picked_by_type' => 'student', 'picked_by_name' => 'Adam',
        'items' => [['item_id' => (int) $item2['id'], 'qty' => 1]]]);
    check('Sedia tetapi tunai belum dibayar → handover.pay_first', !ok($res) && $res['error'] === t('handover.pay_first'));
    $item1 = Database::fetchOne("SELECT * FROM order_items WHERE order_id = ?", [$o1]);
    $base1 = ['id' => $o1, 'picked_by_type' => 'student', 'picked_by_name' => 'Aisyah binti Faizal', 'method' => 'B'];
    $res = $cashier1->post('vendor/handover/collect', $c1 + $base1 + ['items' => [['item_id' => (int) $item1['id'], 'qty' => 5]]]);
    check('Kuantiti > baki → handover.too_many', !ok($res) && $res['error'] === t('handover.too_many'));
    $res = $cashier1->post('vendor/handover/collect', $c1 + $base1 + ['items' => [['item_id' => (int) $item2['id'], 'qty' => 1]]]);
    check('Item pesanan lain → ditolak', !ok($res) && $res['error'] === t('handover.too_many'));
    $res = $cashier1->post('vendor/handover/collect', $c1 + $base1 + ['items' => []]);
    $res2 = $cashier1->post('vendor/handover/collect', $c1 + array_merge($base1, ['picked_by_name' => '', 'picked_by_type' => 'jiran'])
        + ['items' => [['item_id' => (int) $item1['id'], 'qty' => 1]]]);
    check('Tiada item → handover.no_items; nama & jenis pengambil wajib', !ok($res) && $res['error'] === t('handover.no_items')
        && !ok($res2) && isset($res2['errors']['picked_by_name'], $res2['errors']['picked_by_type']));
    $res = $cashier2->post('vendor/handover/collect', $c1 + $base1 + ['items' => [['item_id' => (int) $item1['id'], 'qty' => 4]]]);
    $res3 = $cashier2->post('vendor/handover/collect', $c3 + $base1 + ['items' => [['item_id' => (int) $item1['id'], 'qty' => 4]]]);
    check('Juruwang koperasi lain (V02) serah pesanan V01 → 404 (koperasi 1 & 3), tiada perubahan', !ok($res) && !ok($res3)
        && (int) Database::fetchValue("SELECT collected_qty FROM order_items WHERE id = ?", [(int) $item1['id']]) === 0);
    $stock = level(1);
    $before = emails('order_collected', 'siti@contoh.my');
    $res = $cashier1->post('vendor/handover/collect', $c1 + $base1 + ['items' => [['item_id' => (int) $item1['id'], 'qty' => 3]]]);
    $pickup = Database::fetchOne("SELECT * FROM order_pickups WHERE order_id = ? ORDER BY id DESC LIMIT 1", [$o1]);
    check('Serah separa 3/4 → partially_collected, parent_notified', ok($res) && $res['data']['status'] === 'partially_collected'
        && $res['data']['parent_notified'] === true && order($o1)['collected_at'] === null);
    check('order_pickups: cara B, pengambil, juruwang; order_pickup_items qty 3', $pickup && $pickup['method'] === 'B'
        && $pickup['picked_by_name'] === 'Aisyah binti Faizal' && (int) $pickup['vendor_user_id'] === 2
        && (int) Database::fetchValue("SELECT qty FROM order_pickup_items WHERE order_pickup_id = ?", [(int) $pickup['id']]) === 3);
    $after = level(1);
    $movement = Database::fetchOne("SELECT * FROM stock_movements WHERE type = 'collect' ORDER BY id DESC LIMIT 1");
    check('Stok: qty_on_hand −3 DAN qty_reserved −3; pergerakan collect −3 (ref pesanan, juruwang)',
        (int) $after['qty_on_hand'] === (int) $stock['qty_on_hand'] - 3 && (int) $after['qty_reserved'] === (int) $stock['qty_reserved'] - 3
        && $movement && (int) $movement['qty_change'] === -3 && (int) $movement['ref_id'] === $o1 && (int) $movement['vendor_user_id'] === 2
        && (int) $movement['balance_after'] === (int) $after['qty_on_hand']);
    $mail = Database::fetchOne("SELECT * FROM email_queue WHERE template_key = 'order_collected' ORDER BY id DESC LIMIT 1");
    check('E-mel "telah diambil oleh …" kepada ibu bapa', emails('order_collected', 'siti@contoh.my') === $before + 1
        && strpos($mail['body_html'], 'Aisyah binti Faizal') !== false);
    $res = $cashier1->post('vendor/handover/collect', $c1 + array_merge($base1, ['picked_by_type' => 'parent', 'picked_by_name' => 'Siti', 'method' => 'Z'])
        + ['items' => [['item_id' => (int) $item1['id'], 'qty' => 1]]]);
    $detail = $v1->get('vendor/orders/get', $c1 + ['id' => $o1]);
    check('Serah baki → collected + collected_at; cara tidak sah direkod A', ok($res) && $res['data']['status'] === 'collected'
        && order($o1)['collected_at'] !== null && end($detail['data']['pickups'])['method'] === 'A');
    check('Butiran: 2 serahan (nama juruwang, item & anak), collected_qty 4/4, qty_reserved kembali asal', count($detail['data']['pickups']) === 2
        && $detail['data']['pickups'][0]['user_name'] === 'Kasim bin Osman' && $detail['data']['pickups'][0]['items'][0]['child_name'] === 'Aisyah binti Faizal'
        && $detail['data']['groups'][0]['items'][0]['collected_qty'] === 4 && (int) level(1)['qty_reserved'] === (int) $stock['qty_reserved'] - 4);
    check('Pesanan selesai → serah lagi ditolak', !ok($cashier1->post('vendor/handover/collect', $c1 + $base1 + ['items' => [['item_id' => (int) $item1['id'], 'qty' => 1]]])));
    $storeView = $parentA->get('store/orders/get', ['slug' => 'sk-taman-melati', 'id' => $o1]);
    check('Ibu bapa A melihat rekod serahan; ibu bapa B → 404', ok($storeView) && count($storeView['data']['pickups']) === 2
        && $parentB->status('store/orders/get', ['slug' => 'sk-taman-melati', 'id' => $o1]) === 404);
    check('Log aktiviti handover_collect', (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE action = 'handover_collect' AND entity_id = ?", [$o1]) === 2);

    echo "9. Kod 4 digit — cara E, 5 salah → dikunci, buka oleh pengurus\n";
    $oc = placeCash($parentA, 'sk-taman-melati', 1, 60, 1, '4321');
    check('Pesanan > RM100 dengan kod 4321 (hash sahaja)', $oc > 0 && order($oc)['pickup_code_hash'] !== null
        && strpos(order($oc)['pickup_code_hash'], '4321') === false);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $oc, 'status' => 'preparing']);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $oc, 'status' => 'ready']);
    $cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $oc, 'amount_received' => (int) order($oc)['total']]);
    $itemC = Database::fetchOne("SELECT * FROM order_items WHERE order_id = ?", [$oc]);
    $baseC = ['id' => $oc, 'picked_by_type' => 'proxy', 'picked_by_name' => 'Pak Long', 'items' => [['item_id' => (int) $itemC['id'], 'qty' => 60]]];
    $res = $cashier1->post('vendor/handover/collect', $c1 + $baseC);
    check('Tanpa kod → kod salah, baki cubaan 4', !ok($res) && $res['errors']['code'] === t('handover.code_wrong', ['left' => 4])
        && (int) order($oc)['pickup_attempts'] === 1 && order($oc)['status'] === 'ready');
    for ($i = 0; $i < 3; $i++) {
        $cashier1->post('vendor/handover/collect', $c1 + $baseC + ['code' => '000' . $i]);
    }
    $res = $cashier1->post('vendor/handover/collect', $c1 + $baseC + ['code' => '9999']);
    check('Cubaan ke-5 salah → dikunci', !ok($res) && $res['error'] === t('handover.locked') && (int) order($oc)['pickup_attempts'] === 5);
    $res = $cashier1->post('vendor/handover/collect', $c1 + $baseC + ['code' => '4321']);
    $search = $cashier1->get('vendor/handover/search', $c1 + ['q' => order($oc)['short_code']]);
    check('Kod betul selepas dikunci → tetap ditolak; carian: pickup_locked', !ok($res) && $res['error'] === t('handover.locked')
        && $search['data']['items'][0]['pickup_locked'] === true && $search['data']['items'][0]['requires_code'] === true
        && (int) Database::fetchValue("SELECT collected_qty FROM order_items WHERE id = ?", [(int) $itemC['id']]) === 0);
    check('Juruwang buka kunci → 403; VENDOR02 → 404', $cashier1->postStatus('vendor/orders/unlock-code', $c1 + ['id' => $oc]) === 403
        && $v2->postStatus('vendor/orders/unlock-code', $c1 + ['id' => $oc]) === 404 && (int) order($oc)['pickup_attempts'] === 5);
    $res = $v1->post('vendor/orders/unlock-code', $c1 + ['id' => $oc]);
    check('Pentadbir buka kunci → cubaan 0, dilog', ok($res) && $res['data']['pickup_locked'] === false && (int) order($oc)['pickup_attempts'] === 0
        && end($res['data']['logs'])['note'] === t('order.code_unlocked_log'));
    check('Buka kunci pesanan yang tidak dikunci → ditolak', !ok($v1->post('vendor/orders/unlock-code', $c1 + ['id' => $oc])));
    $res = $cashier1->post('vendor/handover/collect', $c1 + $baseC + ['code' => '4321', 'method' => 'B']);
    check('Kod betul → collected, cara direkod E', ok($res) && $res['data']['status'] === 'collected'
        && Database::fetchValue("SELECT method FROM order_pickups WHERE order_id = ?", [$oc]) === 'E');

    echo "10. Serentak — dua kaunter menyerah baki yang sama\n";
    $op = placeCash($parentA, 'sk-taman-melati', 3, 2, 1);
    $v1->post('vendor/orders/bulk-status', $c1 + ['ids' => [$op], 'status' => 'preparing']);
    $v1->post('vendor/orders/bulk-status', $c1 + ['ids' => [$op], 'status' => 'ready']);
    $cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $op, 'amount_received' => (int) order($op)['total']]);
    $itemP = Database::fetchOne("SELECT * FROM order_items WHERE order_id = ?", [$op]);
    $stockP = level(3);
    $body = $c1 + ['id' => $op, 'picked_by_type' => 'student', 'picked_by_name' => 'Aisyah', 'items' => [['item_id' => (int) $itemP['id'], 'qty' => 2]]];
    $results = parallelPost([[$cashier1, 'vendor/handover/collect', $body], [$v1, 'vendor/handover/collect', $body], [$cashier1, 'vendor/handover/collect', $body]]);
    check('3 serahan serentak → tepat satu berjaya; collected_qty 2; stok −2 sekali sahaja',
        count(array_filter($results, 'ok')) === 1 && (int) Database::fetchValue("SELECT collected_qty FROM order_items WHERE id = ?", [(int) $itemP['id']]) === 2
        && (int) level(3)['qty_on_hand'] === (int) $stockP['qty_on_hand'] - 2
        && (int) Database::fetchValue("SELECT COUNT(*) FROM order_pickups WHERE order_id = ?", [$op]) === 1);

    echo "11. Bayaran online → e-mel \"bayaran diterima\" (PaymentSettlement)\n";
    $before = emails('order_paid', 'siti@contoh.my');
    list($oo, $result) = placePaidOnline($parentA, 3, 1, 2);
    check('PaymentSettlement paid → pesanan paid + log sistem + e-mel order_paid', $result === 'paid' && order($oo)['status'] === 'paid'
        && emails('order_paid', 'siti@contoh.my') === $before + 1);
    $paidDetail = $v1->get('vendor/orders/get', $c1 + ['id' => $oo]);
    check('Log "paid" oleh Sistem dalam panel', ok($paidDetail) && end($paidDetail['data']['logs'])['actor'] === 'system'
        && end($paidDetail['data']['logs'])['actor_name'] === t('order.actor_system'));
    $paymentId = (int) Database::fetchValue("SELECT id FROM payments WHERE order_id = ?", [$oo]);
    PaymentSettlement::apply($paymentId, ['status' => 'paid', 'amount' => (int) order($oo)['total'], 'currency' => 'MYR', 'raw' => []], true);
    check('Panggilan balik berganda → tiada e-mel kedua', emails('order_paid', 'siti@contoh.my') === $before + 1);
    check('Online dibayar: terima tunai → ditolak; terus preparing', !ok($cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $oo, 'amount_received' => 9999]))
        && ok($v1->post('vendor/orders/set-status', $c1 + ['id' => $oo, 'status' => 'preparing'])));

    echo "12. Ibu bapa & nota (vendor/customers/*)\n";
    $customers = $v1->get('vendor/customers/list', $c1);
    $siti = ok($customers) ? $customers['data']['items'][0] : [];
    check('V01: hanya Siti (anak di SK Taman Melati), anak sekolah ini sahaja (tiada Faris di SJKC)', ok($customers) && $customers['data']['total'] === 1
        && $siti['id'] === 1 && array_column($siti['children'], 'full_name') === ['Adam bin Faizal', 'Aisyah binti Faizal']
        && $customers['data']['child_count'] === 2);
    check('Kiraan pesanan & jumlah belanja (tanpa batal)', $siti['order_count'] === 5 && $siti['total_spent'] > 0 && $siti['last_order_at'] !== null);
    check('Tapis darjah D1 → anak D1 sahaja; q nama anak', array_column($v1->get('vendor/customers/list', $c1 + ['grade' => 'D1'])['data']['items'][0]['children'], 'full_name')
        === ['Adam bin Faizal'] && $v1->get('vendor/customers/list', $c1 + ['q' => 'Aisyah'])['data']['total'] === 1
        && $v1->get('vendor/customers/list', $c1 + ['q' => 'Rajesh'])['data']['total'] === 0);
    $listB = $v2->get('vendor/customers/list', $c3);
    check('V02 SMK Seri Indah: hanya Rajesh', ok($listB) && array_column($listB['data']['items'], 'full_name') === ['Rajesh a/l Kumar']);
    $res = $v1->post('vendor/customers/save-note', $c1 + ['customer_id' => 1, 'note' => 'Suka ambil petang']);
    check('V01 simpan nota → dipapar dalam senarai', ok($res) && $v1->get('vendor/customers/list', $c1)['data']['items'][0]['note'] === 'Suka ambil petang');
    check('V01 nota ibu bapa B (tiada anak di koperasi V01) → 404', $v1->postStatus('vendor/customers/save-note', $c1 + ['customer_id' => 2, 'note' => 'x']) === 404);
    check('V02 nota Siti melalui SMK Seri Indah (tiada anak di situ) → 404', $v2->postStatus('vendor/customers/save-note', $c3 + ['customer_id' => 1, 'note' => 'x']) === 404);
    $res = $v2->post('vendor/customers/save-note', ['cooperative_id' => 2, 'customer_id' => 1, 'note' => 'Nota V02']);
    $v2list = $v2->get('vendor/customers/list', ['cooperative_id' => 2]);
    check('V02 nota Siti (anak di SJKC) — nota berasingan, nota V01 tidak kelihatan', ok($res) && $v2list['data']['items'][0]['note'] === 'Nota V02'
        && strpos(json_encode($v2list), 'Suka ambil petang') === false && $v1->get('vendor/customers/list', $c1)['data']['items'][0]['note'] === 'Suka ambil petang');
    check('Juruwang → customers 403; VENDOR02 → senarai koperasi 1 → 404', $cashier1->postStatus('vendor/customers/save-note', $c1 + ['customer_id' => 1]) === 403
        && $v2->status('vendor/customers/list', $c1) === 404);
    $v1->post('vendor/customers/save-note', $c1 + ['customer_id' => 1, 'note' => '']);
    check('Nota kosong → dipadam', (int) Database::fetchValue("SELECT COUNT(*) FROM vendor_customer_notes WHERE vendor_id = 1") === 0);

    echo "13. Halaman panel\n";
    foreach (['vendor/orders.php', 'vendor/order.php?id=' . $o1, 'vendor/picking-list.php', 'vendor/bag-labels.php', 'vendor/customers.php', 'vendor/settings.php',
        'vendor/pos/handover.php'] as $page) {
        $result = $v1->page($page);
        check("Pentadbir: $page → 200", $result['status'] === 200);
    }
    check('Juruwang: skrin serah 200; pesanan → dialih', $cashier1->page('vendor/pos/handover.php')['status'] === 200
        && $cashier1->page('vendor/orders.php')['status'] === 302);
    $api = file_get_contents(APP_ROOT . '/assets/js/api.js');
    check('Api.livePrefixes: vendor/orders/, handover/, customers/, settings/', strpos($api, "'vendor/orders/', 'vendor/handover/', 'vendor/customers/', 'vendor/settings/'") !== false);
    $mock = file_get_contents(APP_ROOT . '/assets/js/mock/vendor-orders.js') . file_get_contents(APP_ROOT . '/assets/js/mock/vendor-finance.js')
        . file_get_contents(APP_ROOT . '/assets/js/mock/vendor-addons.js');
    check('Mock.register laluan B9 dibuang', !preg_match("#Mock\.register\('vendor/(orders|handover|customers|settings)/#", $mock));
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
