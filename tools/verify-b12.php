<?php
// Semakan B12 — langganan & invois vendor: tukar pelan, kedai add-on (prorata, serentak), ModuleAccess, invois vendor/owner,
// bayaran online melalui gateway owner (ToyyibPay palsu + webhook platform), tanda dibayar manual, kitaran bil (draf → pusingan,
// trial, tempoh terlepas), tunggakan (past_due → suspended → store ditutup sementara → pulih), langganan semula, koperasi tambahan,
// log aktiviti + ujian kebocoran VENDOR01 ↔ VENDOR02 dan ibu bapa A ↔ B. CLI sahaja:
//   php tools/verify-b12.php
// Perlu: DB koperasi_saas (schema + seed), config/database.php, config/secret.key, sambungan PHP curl.
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

function invoice($id)
{
    return Database::fetchOne("SELECT * FROM invoices WHERE id = ?", [(int) $id]);
}

function lines($invoiceId)
{
    return Database::fetchAll("SELECT * FROM invoice_lines WHERE invoice_id = ? ORDER BY id", [(int) $invoiceId]);
}

function vendorStatus($vendorId)
{
    return Database::fetchValue("SELECT status FROM vendors WHERE id = ?", [(int) $vendorId]);
}

function sub($vendorId)
{
    return Database::fetchOne("SELECT * FROM subscriptions WHERE vendor_id = ? ORDER BY id DESC LIMIT 1", [(int) $vendorId]);
}

function invoiceCount($vendorId)
{
    return (int) Database::fetchValue("SELECT COUNT(*) FROM invoices WHERE vendor_id = ?", [(int) $vendorId]);
}

function auditCount($vendorId, $action)
{
    return (int) Database::fetchValue("SELECT COUNT(*) FROM audit_logs WHERE vendor_id = ? AND action = ?", [(int) $vendorId, $action]);
}

function fakeGateway($fakeBase, $path)
{
    return json_decode((string) file_get_contents($fakeBase . $path), true);
}

function setBill($fakeBase, $ref, $status = 'paid', $amount = null)
{
    return fakeGateway($fakeBase, '/_admin/set?' . http_build_query(['ref' => $ref, 'status' => $status] + ($amount === null ? [] : ['amount' => $amount])));
}

// Panggilan balik gateway owner → [status, badan]
function platformWebhook($base, $gateway, array $fields)
{
    $curl = curl_init($base . '/api/v1/webhooks/platform.php?gateway=' . $gateway);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $body = (string) curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return [$status, $body];
}

function lastWebhook()
{
    return Database::fetchOne("SELECT * FROM webhook_logs ORDER BY id DESC LIMIT 1");
}

function moduleRow($cooperativeId, $code)
{
    return Database::fetchOne(
        "SELECT cm.* FROM cooperative_modules cm JOIN modules m ON m.id = cm.module_id WHERE cm.cooperative_id = ? AND m.code = ?",
        [(int) $cooperativeId, $code]
    );
}

function openItem($cooperativeId, $type, $refCode = null)
{
    $sql = "SELECT si.* FROM subscription_items si LEFT JOIN modules m ON si.item_type = 'module' AND m.id = si.ref_id
             WHERE si.cooperative_id = ? AND si.item_type = ? AND si.ended_at IS NULL";
    $params = [(int) $cooperativeId, $type];
    if ($refCode !== null) {
        $sql .= " AND m.code = ?";
        $params[] = $refCode;
    }
    return Database::fetchOne($sql . " ORDER BY si.id DESC LIMIT 1", $params);
}

function prorata($price, $periodStart, $periodEnd)
{
    $days = BillingPeriod::days(date('Y-m-d'), $periodEnd);
    $total = BillingPeriod::days($periodStart, $periodEnd);
    return (int) round($price * $days / $total);
}

function newCoopInput($slug)
{
    $day = ['open' => true, 'from' => '07:30', 'to' => '13:00'];
    $closed = ['open' => false, 'from' => '', 'to' => ''];
    return [
        'school_name' => 'SK Ujian B12', 'school_code' => 'bba9912', 'school_type' => 'SK', 'state' => 'Selangor',
        'district' => 'Gombak', 'address' => 'Jalan Ujian 12', 'phone' => '03-1212121', 'slug' => $slug, 'template_id' => 1,
        'default_language' => 'ms', 'accept_cash' => true, 'accept_online' => false, 'logo_url' => null,
        'opening_hours' => ['mon' => $day, 'tue' => $day, 'wed' => $day, 'thu' => $day, 'fri' => $day, 'sat' => $closed, 'sun' => $closed],
    ];
}

function setPeriod($vendorId, $start, $end, $extra = [])
{
    $sets = ['current_period_start = ?', 'current_period_end = ?'];
    $params = [$start, $end];
    foreach ($extra as $column => $value) {
        $sets[] = "$column = ?";
        $params[] = $value;
    }
    $params[] = (int) sub($vendorId)['id'];
    Database::execute("UPDATE subscriptions SET " . implode(', ', $sets) . " WHERE id = ?", $params);
}

$port = 8110;
$fakePort = 8111;
$base = 'http://127.0.0.1:' . $port;
$fakeBase = 'http://127.0.0.1:' . $fakePort;
$log = sys_get_temp_dir() . '/kop-verify-b12.log';
$stateFile = sys_get_temp_dir() . '/kop-verify-b12-gateway.json';
@unlink($stateFile);
$fake = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $fakePort, __DIR__ . '/fake-gateway.php'], [1 => ['file', $log . '.fake', 'w'], 2 => ['file', $log . '.fake', 'a']],
    $fakePipes, null, ['FAKE_GATEWAY_STATE' => $stateFile] + getenv());
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4', 'KOPERASI_GATEWAY_TEST_URL' => $fakeBase] + getenv());
usleep(900000);
putenv('KOPERASI_GATEWAY_TEST_URL=' . $fakeBase);

$tables = ['subscriptions', 'subscription_items', 'cooperative_modules', 'invoices', 'invoice_lines', 'platform_payments', 'platform_settings',
    'vendors', 'vendor_notes', 'email_queue', 'activity_logs', 'audit_logs', 'webhook_logs', 'cooperatives', 'cooperative_settings', 'plans',
    'login_attempts', 'rate_limits', 'platform_users', 'totp_recovery_codes'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
Database::execute("DELETE FROM rate_limits WHERE bucket_key LIKE 'invoice_check:%' OR bucket_key LIKE 'webhook:%'");
$today = date('Y-m-d');

try {
    list($owner) = ownerLogin($base, 'owner@koperasiku.my');
    list($staff) = ownerLogin($base, 'staf@koperasiku.my');
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $manager2 = vendorLogin($base, 'chong@bestari.my');
    $cashier1 = vendorLogin($base, 'kasim@niagasekolah.my');
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    list($parentB) = storeLogin($base, 'rajesh@contoh.my');
    $guest = new Browser($base);
    $c1 = ['cooperative_id' => 1];
    $c2 = ['cooperative_id' => 2];
    $c3 = ['cooperative_id' => 3];

    echo "1. Peranan & keselamatan laluan\n";
    check('Tanpa sesi → 401 (subscription/get, invoices/list, addons/catalog, owner/invoices/list)', $guest->status('vendor/subscription/get') === 401
        && $guest->status('vendor/invoices/list') === 401 && $guest->status('vendor/addons/catalog', $c1) === 401
        && $guest->status('owner/invoices/list') === 401);
    check('Juruwang → 403 (subscription/get, invoices/list, addons/catalog, activity-logs/list)', $cashier1->status('vendor/subscription/get') === 403
        && $cashier1->status('vendor/invoices/list') === 403 && $cashier1->status('vendor/addons/catalog', $c1) === 403
        && $cashier1->status('vendor/activity-logs/list') === 403);
    check('Pengurus → 403 (subscription/get, addons/subscribe, invoices/pay) — langganan pentadbir sahaja',
        $manager2->status('vendor/subscription/get') === 403 && $manager2->postStatus('vendor/addons/subscribe', $c2 + ['item_type' => 'module', 'code' => 'news']) === 403
        && $manager2->postStatus('vendor/invoices/pay', ['id' => 2]) === 403);
    check('owner_staff: baca langganan & invois 200; terbit / batal / tanda dibayar 403', $staff->status('owner/subscriptions/list') === 200
        && $staff->status('owner/invoices/list') === 200 && $staff->status('owner/invoices/get', ['id' => 1]) === 200
        && $staff->postStatus('owner/invoices/issue', ['id' => 1]) === 403 && $staff->postStatus('owner/invoices/void', ['id' => 1]) === 403
        && $staff->postStatus('owner/invoices/mark-paid', ['id' => 1]) === 403);
    check('owner_staff: halaman invois tanpa butang tindakan', strpos($staff->page('owner/invoice.php?id=1')['body'], 'btn-mark-paid') === false
        && strpos($owner->page('owner/invoice.php?id=1')['body'], 'btn-mark-paid') !== false);
    check('POST tanpa CSRF → 419; GET ke pay → 405', $v1->postStatus('vendor/addons/subscribe', $c1 + ['item_type' => 'module', 'code' => 'clearance'], false) === 419
        && $v1->getStatus('vendor/invoices/pay') === 405);
    check('Ibu bapa (sesi store) → laluan bil vendor & owner 401', $parentA->status('vendor/invoices/list') === 401
        && $parentA->status('owner/invoices/list') === 401 && $parentB->status('vendor/subscription/get') === 401);

    echo "2. Langganan vendor\n";
    $s1 = $v1->get('vendor/subscription/get')['data'] ?? [];
    check('V01: Pro tahunan, 1 koperasi (termasuk), 4 modul, anggaran pembaharuan 164000 + SST 8% = 177120', ($s1['plan']['code'] ?? '') === 'PRO'
        && $s1['billing_cycle'] === 'yearly' && $s1['usage']['cooperatives'] === 1 && $s1['cooperatives'][0]['included'] === true
        && count($s1['cooperatives'][0]['modules']) === 4 && $s1['renewal']['subtotal'] === 164000 && $s1['renewal']['total'] === 177120
        && $s1['renewal']['sst_rate'] == 8 && $s1['pending_change'] === null && $s1['unpaid_count'] === 0);
    $s2 = $v2->get('vendor/subscription/get')['data'] ?? [];
    check('V02: Asas bulanan, koperasi ke-2 RM49 (peringkat), modul clearance → anggaran 10800', ($s2['plan']['code'] ?? '') === 'ASAS'
        && $s2['cooperatives'][1]['number'] === 2 && $s2['cooperatives'][1]['price'] === 4900 && $s2['renewal']['subtotal'] === 10800
        && array_column($s2['renewal']['lines'], 'type') === ['plan', 'cooperative', 'module']);
    $plans = $v1->get('vendor/subscription/plans')['data'] ?? [];
    check('Pelan aktif 3, current_plan_id V01 = 2', count($plans['plans'] ?? []) === 3 && $plans['current_plan_id'] === 2);

    echo "3. Tukar pelan (berkuat kuasa pada pembaharuan)\n";
    $res = $v1->post('vendor/subscription/change', ['plan_id' => 1, 'billing_cycle' => 'monthly']);
    $pending = $res['data']['pending_change'] ?? null;
    check('V01 → Asas bulanan: dijadualkan pada 2027-01-05; anggaran dihargakan semula bulanan (4900 + 1500 + 500 + 2000 + 2500)', ok($res)
        && $pending['plan_name'] === 'Asas' && $pending['billing_cycle'] === 'monthly' && $pending['effective_at'] === '2027-01-05'
        && $res['data']['renewal']['subtotal'] === 11400 && (int) sub(1)['plan_id'] === 2);
    check('Log aktiviti subscription_change (peringkat vendor)', (int) Database::fetchValue(
        "SELECT COUNT(*) FROM activity_logs WHERE vendor_id = 1 AND action = 'subscription_change' AND cooperative_id IS NULL") === 1);
    $bad = $v1->post('vendor/subscription/change', ['plan_id' => 1, 'billing_cycle' => 'mingguan']);
    $badPlan = $v1->post('vendor/subscription/change', ['plan_id' => 999, 'billing_cycle' => 'monthly']);
    check('Kitaran / pelan tidak sah → ditolak', !ok($bad) && isset($bad['errors']['billing_cycle']) && !ok($badPlan) && isset($badPlan['errors']['plan_id']));
    Database::execute("UPDATE plans SET max_products = 1 WHERE id = 1");
    $res = $v1->post('vendor/subscription/change', ['plan_id' => 1, 'billing_cycle' => 'yearly']);
    check('Turun taraf melebihi had produk → billing.downgrade_blocked, jadual lama kekal', !ok($res)
        && $res['error'] === t('billing.downgrade_blocked', ['plan' => 'Asas']) && sub(1)['pending_billing_cycle'] === 'monthly');
    Database::execute("UPDATE plans SET max_products = 300 WHERE id = 1");
    check('V02 tidak nampak jadual V01', array_key_exists('pending_change', $v2->get('vendor/subscription/get')['data'] ?? [])
        && $v2->get('vendor/subscription/get')['data']['pending_change'] === null);
    $res = $v1->post('vendor/subscription/cancel-change', []);
    check('Batal pertukaran → pending null', ok($res) && $res['data']['pending_change'] === null && sub(1)['pending_plan_id'] === null);

    echo "4. Kedai add-on (prorata, serentak, henti)\n";
    $cat = $v2->get('vendor/addons/catalog', $c3)['data'] ?? [];
    $mods = array_column($cat['modules'] ?? [], 'status', 'code');
    $tpls = array_column($cat['templates'] ?? [], null, 'code');
    check('Katalog SMK Seri Indah: clearance aktif, news belum, membership akan datang; template1 dipakai, template2 belum, template3 tiada',
        $mods['clearance'] === 'active' && $mods['news'] === 'available' && $mods['membership'] === 'unavailable'
        && $tpls['template1']['in_use'] === true && $tpls['template2']['subscribed'] === false && !isset($tpls['template3'])
        && $cat['billing_cycle'] === 'monthly');
    check('Katalog SJKC Chung Hwa: clearance belum dilanggan (per koperasi)',
        array_column($v2->get('vendor/addons/catalog', $c2)['data']['modules'], 'status', 'code')['clearance'] === 'available');
    check('V01 → katalog / anggaran / langgan koperasi V02 → 404', $v1->status('vendor/addons/catalog', $c3) === 404
        && $v1->status('vendor/addons/quote', $c2 + ['item_type' => 'module', 'code' => 'news']) === 404
        && $v1->postStatus('vendor/addons/subscribe', $c3 + ['item_type' => 'module', 'code' => 'news']) === 404);
    $sub2 = sub(2);
    $expected = prorata(1500, $sub2['current_period_start'], $sub2['current_period_end']);
    $quote = $v2->get('vendor/addons/quote', $c3 + ['item_type' => 'module', 'code' => 'news'])['data'] ?? [];
    check('Anggaran news: 1500 × baki hari / hari tempoh = ' . $expected . ' + SST', $quote['amount'] === $expected && $quote['period_days'] === 31
        && $quote['tax'] === (int) round($expected * 0.08) && $quote['total'] === $expected + $quote['tax'] && $quote['owned'] === false
        && $quote['period_end'] === $sub2['current_period_end'] && $quote['is_trial'] === false);
    check('Anggaran kod tidak wujud / template percuma → 404', $v2->status('vendor/addons/quote', $c3 + ['item_type' => 'module', 'code' => 'tiada']) === 404
        && $v2->status('vendor/addons/quote', $c3 + ['item_type' => 'template', 'code' => 'template1']) === 404);
    $mailsBefore = (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue WHERE template_key = 'invoice_issued'");
    $res = $v2->post('vendor/addons/subscribe', $c3 + ['item_type' => 'module', 'code' => 'news']);
    $newsInvoice = invoice($res['data']['invoice_id'] ?? 0);
    $newsLines = lines($newsInvoice['id'] ?? 0);
    check('Langgan news: invois INV-' . date('Y') . '-0003 issued, jumlah = anggaran, tarikh akhir +7 hari 23:59:59', ok($res)
        && $res['data']['invoice_no'] === 'INV-' . date('Y') . '-0003' && $res['data']['total'] === $quote['total']
        && $newsInvoice['status'] === 'issued' && $newsInvoice['due_at'] === BillingPeriod::addDays($today, 7) . ' 23:59:59'
        && (float) $newsInvoice['sst_rate'] === 8.0);
    check('Baris invois: modul, koperasi 3, prorata, penerangan snapshot', count($newsLines) === 1 && $newsLines[0]['item_type'] === 'module'
        && (int) $newsLines[0]['cooperative_id'] === 3 && (int) $newsLines[0]['is_prorated'] === 1
        && strpos($newsLines[0]['description'], 'Modul Berita & pengumuman: SMK Seri Indah (prorata ') === 0);
    $row = moduleRow(3, 'news');
    $item = openItem(3, 'module', 'news');
    check('cooperative_modules aktif hingga hujung tempoh; subscription_items harga kitaran 1500', $row['status'] === 'active'
        && $row['ends_at'] === $sub2['current_period_end'] && (int) $item['unit_price'] === 1500 && (int) $item['subscription_id'] === (int) $sub2['id']);
    check('E-mel invoice_issued ke meiling@bestari.my (queue)', (int) Database::fetchValue(
        "SELECT COUNT(*) FROM email_queue WHERE template_key = 'invoice_issued'") === $mailsBefore + 1
        && Database::fetchValue("SELECT to_email FROM email_queue WHERE template_key = 'invoice_issued' ORDER BY id DESC LIMIT 1") === 'meiling@bestari.my');
    check('Menu modul (vendor/modules/active) & store SMK Seri Indah kini ada news',
        in_array('news', $v2->get('vendor/modules/active', $c3)['data']['modules'], true)
        && in_array('news', $guest->get('store/school', ['slug' => 'smk-seri-indah'])['data']['modules'], true));
    $count = invoiceCount(2);
    $res = $v2->post('vendor/addons/subscribe', $c3 + ['item_type' => 'module', 'code' => 'news']);
    check('Langgan semula → addon.already_subscribed, tiada invois baharu', !ok($res) && $res['error'] === t('addon.already_subscribed')
        && invoiceCount(2) === $count);
    $results = parallelPost([[$v2, 'vendor/addons/subscribe', $c3 + ['item_type' => 'module', 'code' => 'calendar']],
        [$v2, 'vendor/addons/subscribe', $c3 + ['item_type' => 'module', 'code' => 'calendar']]]);
    check('Dua klik serentak (calendar) → tepat satu berjaya, satu invois, satu item', count(array_filter($results, 'ok')) === 1
        && invoiceCount(2) === $count + 1 && (int) Database::fetchValue(
            "SELECT COUNT(*) FROM subscription_items WHERE cooperative_id = 3 AND item_type = 'module' AND ref_id = 3") === 1);
    $res = $v2->post('vendor/addons/cancel', $c3 + ['code' => 'news']);
    $renewal = $v2->get('vendor/subscription/get')['data']['renewal'];
    $newsLine = array_values(array_filter($renewal['lines'], function ($line) { return $line['label'] === 'news'; }))[0] ?? null;
    check('Henti news: cancelling, kekal aktif hingga hujung tempoh, tidak dikira dalam anggaran', ok($res) && $res['data']['cancel_at_period_end'] === true
        && array_column($v2->get('vendor/addons/catalog', $c3)['data']['modules'], 'status', 'code')['news'] === 'cancelling'
        && $newsLine['cancelling'] === true && $renewal['subtotal'] === 10800 + 500 && ModuleAccess::has(2, 3, 'news'));
    $res = $v2->post('vendor/addons/cancel', $c3 + ['code' => 'news', 'undo' => true]);
    check('Teruskan (undo) → aktif semula, dikira semula', ok($res) && $res['data']['cancel_at_period_end'] === false
        && $v2->get('vendor/subscription/get')['data']['renewal']['subtotal'] === 10800 + 500 + 1500);
    check('Henti modul koperasi V02 oleh V01 → 404; modul tidak dilanggan → 404', $v1->postStatus('vendor/addons/cancel', $c3 + ['code' => 'news']) === 404
        && $v2->postStatus('vendor/addons/cancel', $c3 + ['code' => 'whatsapp_sms']) === 404);
    $res = $v2->post('vendor/addons/subscribe', $c3 + ['item_type' => 'template', 'code' => 'template2']);
    $templateInvoiceId = $res['data']['invoice_id'] ?? 0;
    $opts3 = array_column($v2->get('vendor/templates/options', $c3)['data'], 'subscribed', 'code');
    $opts2 = array_column($v2->get('vendor/templates/options', $c2)['data'], 'subscribed', 'code');
    $optsNew = array_column($v2->get('vendor/templates/options')['data'], 'subscribed', 'code');
    check('Template 2 untuk SMK Seri Indah: invois template, dilanggan koperasi 3 sahaja (bukan koperasi 2 / koperasi baharu)', ok($res)
        && lines($templateInvoiceId)[0]['item_type'] === 'template' && $opts3['template2'] === true && $opts2['template2'] === false
        && $optsNew['template2'] === false);
    check('Template dilanggan tetapi belum dipakai → berhenti pada pembaharuan (cancelling)', in_array(true, array_map(function ($line) {
        return $line['type'] === 'template' && $line['cancelling'];
    }, $v2->get('vendor/subscription/get')['data']['renewal']['lines']), true));
    check('V02 templates/options koperasi V01 → 404', $v2->status('vendor/templates/options', $c1) === 404);
    Database::execute("UPDATE subscriptions SET status = 'trial' WHERE id = ?", [(int) $sub2['id']]);
    $quote = $v2->get('vendor/addons/quote', $c3 + ['item_type' => 'module', 'code' => 'advanced_report'])['data'] ?? [];
    $count = invoiceCount(2);
    $res = $v2->post('vendor/addons/subscribe', $c3 + ['item_type' => 'module', 'code' => 'advanced_report']);
    check('Trial: anggaran 0, langgan tanpa invois (dicaj bermula invois pertama)', $quote['is_trial'] === true && $quote['total'] === 0 && ok($res)
        && $res['data']['invoice_id'] === null && invoiceCount(2) === $count && ModuleAccess::has(2, 3, 'advanced_report'));
    Database::execute("UPDATE subscriptions SET status = 'active' WHERE id = ?", [(int) $sub2['id']]);

    echo "5. ModuleAccess\n";
    $locked = null;
    try {
        ModuleAccess::require(2, 2, 'news');
    } catch (ValidationException $error) {
        $locked = $error;
    }
    check('Modul tidak dilanggan → 403 + module_locked (endpoint modul B13 memanggil require)', $locked !== null && $locked->status() === 403
        && $locked->extra() === ['module_locked' => true]);
    check('has: news koperasi 3 (V02) ya; koperasi 2 tidak; vendor salah (V01, koperasi 3) tidak', ModuleAccess::has(2, 3, 'news')
        && !ModuleAccess::has(2, 2, 'news') && !ModuleAccess::has(1, 3, 'news'));
    Database::execute("UPDATE cooperative_modules SET ends_at = ? WHERE cooperative_id = 1 AND module_id = 1", [BillingPeriod::addDays($today, -1)]);
    check('Modul tamat (ends_at lepas) → tiada akses, hilang dari menu & store', !ModuleAccess::has(1, 1, 'news')
        && !in_array('news', $v1->get('vendor/modules/active', $c1)['data']['modules'], true)
        && !in_array('news', $guest->get('store/school', ['slug' => 'sk-taman-melati'])['data']['modules'], true));
    Database::execute("UPDATE cooperative_modules SET ends_at = '2027-01-04' WHERE cooperative_id = 1 AND module_id = 1");
    check('Juruwang boleh membaca modules/active (menu POS)', ok($cashier1->get('vendor/modules/active', $c1)));

    echo "6. Invois vendor\n";
    $list2 = $v2->get('vendor/invoices/list')['data'] ?? [];
    $list1 = $v1->get('vendor/invoices/list')['data'] ?? [];
    check('V02 nampak invois sendiri sahaja (+ line_count); V01 hanya INV-' . date('Y') . '-0001', $list2['total'] === 4
        && count(array_unique(array_column($list2['items'], 'vendor_id'))) === 1 && $list2['items'][0]['vendor_id'] === 2
        && isset($list2['items'][0]['line_count']) && $list1['total'] === 1 && $list1['items'][0]['invoice_no'] === 'INV-2026-0001');
    $detail = $v2->get('vendor/invoices/get', ['id' => $newsInvoice['id']])['data'] ?? [];
    check('Butiran: baris, SST snapshot, vendor { code, company_name }, tiada vendor_id', $detail['vendor']['code'] === 'VENDOR02'
        && count($detail['lines']) === 1 && $detail['sst_rate'] == 8 && !isset($detail['vendor_id']) && $detail['payments'] === []);
    check('V01 → invois V02 (get / pay / check-payment) → 404', $v1->status('vendor/invoices/get', ['id' => $newsInvoice['id']]) === 404
        && $v1->postStatus('vendor/invoices/pay', ['id' => $newsInvoice['id']]) === 404
        && $v1->postStatus('vendor/invoices/check-payment', ['id' => $newsInvoice['id']]) === 404);
    check('Tapis status=issued', count($v2->get('vendor/invoices/list', ['status' => 'issued'])['data']['items']) === 3);

    echo "7. Bayar online melalui gateway owner (jangan percaya redirect)\n";
    $res = $v2->post('vendor/invoices/pay', ['id' => $newsInvoice['id']]);
    check('Gateway owner belum ada kunci → billing.gateway_unavailable', !ok($res) && $res['error'] === t('billing.gateway_unavailable'));
    $res = $owner->post('owner/gateways/save-owner', ['driver_code' => 'toyyibpay', 'sandbox' => true,
        'credentials' => ['secret_key' => 'tp-secret', 'category_code' => 'cat123']]);
    check('Owner simpan gateway ToyyibPay (sandbox)', ok($res));
    $pay = $v2->post('vendor/invoices/pay', ['id' => $newsInvoice['id']]);
    $billCode = substr($pay['data']['redirect_url'] ?? '', strlen($fakeBase . '/toyyibpay/'));
    $payment = Database::fetchOne("SELECT * FROM platform_payments WHERE invoice_id = ?", [$newsInvoice['id']]);
    $bills = fakeGateway($fakeBase, '/_admin/bills');
    check('Bayar → redirect_url gateway, platform_payments pending (gateway_ref = BillCode, amaun = jumlah invois)', ok($pay)
        && $pay['data']['paid'] === false && strpos($pay['data']['redirect_url'], $fakeBase . '/toyyibpay/tp') === 0
        && $payment['status'] === 'pending' && $payment['gateway_ref'] === $billCode && (int) $payment['amount'] === (int) $newsInvoice['total']);
    $bill = $bills['bills'][$billCode] ?? $bills[$billCode] ?? null;
    check('Bil: panggilan balik webhooks/platform.php?gateway=toyyibpay, kembali ke vendor/invoices.php?paid_return=', $bill !== null
        && strpos($bill['callback'], '/api/v1/webhooks/platform.php?gateway=toyyibpay') !== false
        && strpos($bill['return'], '/vendor/invoices.php?paid_return=' . $newsInvoice['id']) !== false);
    $again = $v2->post('vendor/invoices/pay', ['id' => $newsInvoice['id']]);
    check('Bayar sekali lagi → bil sama diguna semula (satu baris pending)', ($again['data']['redirect_url'] ?? '') === $pay['data']['redirect_url']
        && (int) Database::fetchValue("SELECT COUNT(*) FROM platform_payments WHERE invoice_id = ?", [$newsInvoice['id']]) === 1);
    $check = $v2->post('vendor/invoices/check-payment', ['id' => $newsInvoice['id']]);
    $v2->page('vendor/invoices.php?paid_return=' . $newsInvoice['id'] . '&status_id=1&billcode=' . $billCode);
    check('Kembali dari gateway sebelum dibayar (redirect palsu status_id=1) → pending, invois kekal issued', ($check['data']['result'] ?? '') === 'pending'
        && invoice($newsInvoice['id'])['status'] === 'issued');
    setBill($fakeBase, $billCode, 'paid');
    list($status, $body) = platformWebhook($base, 'toyyibpay', ['billcode' => $billCode, 'status' => '1', 'order_id' => $payment['gateway_ref']]);
    $hook = lastWebhook();
    check('Webhook platform → disemak semula dengan API gateway → invois paid, bayaran paid', $status === 200 && $body === 'OK'
        && invoice($newsInvoice['id'])['status'] === 'paid' && Database::fetchValue("SELECT status FROM platform_payments WHERE id = ?", [$payment['id']]) === 'paid'
        && $hook['result'] === 'paid (unsigned)' && $hook['cooperative_id'] === null && auditCount(2, 'invoice.paid_online') === 1);
    list($status) = platformWebhook($base, 'toyyibpay', ['billcode' => $billCode, 'status' => '1']);
    check('Webhook berganda → already_paid, satu bayaran sahaja', $status === 200 && strpos(lastWebhook()['result'], 'already_paid') === 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM platform_payments WHERE invoice_id = ? AND status = 'paid'", [$newsInvoice['id']]) === 1);
    $detail = $v2->get('vendor/invoices/get', ['id' => $newsInvoice['id']])['data'];
    check('Butiran invois: bayaran online, rujukan BillCode', $detail['status'] === 'paid' && $detail['payments'][0]['method'] === 'online'
        && $detail['payments'][0]['reference'] === $billCode);
    $res = $v2->post('vendor/invoices/pay', ['id' => $newsInvoice['id']]);
    check('Bayar invois yang sudah dibayar → billing.cannot_pay', !ok($res) && $res['error'] === t('billing.cannot_pay'));
    list($status) = platformWebhook($base, 'toyyibpay', ['billcode' => 'tiada123']);
    list($status2) = platformWebhook($base, 'billplz', ['id' => 'x']);
    check('Rujukan tidak dikenali → 200 tanpa perubahan; gateway bukan gateway owner → 404', $status === 200 && $status2 === 404
        && lastWebhook()['result'] === 'no_gateway' && (new Browser($base))->getStatus('webhooks/platform') === 405);
    $calendarInvoice = (int) Database::fetchValue(
        "SELECT i.id FROM invoices i JOIN invoice_lines l ON l.invoice_id = i.id WHERE i.vendor_id = 2 AND l.description LIKE 'Modul Kalendar%' ORDER BY i.id DESC LIMIT 1");
    $pay = $v2->post('vendor/invoices/pay', ['id' => $calendarInvoice]);
    $code2 = substr($pay['data']['redirect_url'] ?? '', strlen($fakeBase . '/toyyibpay/'));
    setBill($fakeBase, $code2, 'paid', 1);
    list($status) = platformWebhook($base, 'toyyibpay', ['billcode' => $code2]);
    check('Gateway melapor amaun lain → amount_mismatch, invois kekal issued', $status === 200 && strpos(lastWebhook()['result'], 'amount_mismatch') === 0
        && invoice($calendarInvoice)['status'] === 'issued');
    check('check-payment → amount_mismatch (pelayan bertanya sendiri)', ($v2->post('vendor/invoices/check-payment', ['id' => $calendarInvoice])['data']['result'] ?? '')
        === 'amount_mismatch');

    echo "8. Panel owner — langganan & invois\n";
    $subs = $owner->get('owner/subscriptions/list')['data'] ?? [];
    check('Senarai langganan: 2, tapis tahunan → VENDOR01, carian VENDOR02', $subs['total'] === 2
        && $owner->get('owner/subscriptions/list', ['billing_cycle' => 'yearly'])['data']['items'][0]['vendor_code'] === 'VENDOR01'
        && $owner->get('owner/subscriptions/list', ['q' => 'VENDOR02'])['data']['total'] === 1
        && $subs['items'][0]['cooperative_count'] >= 1);
    check('Senarai invois: tapis vendor_id=2 & status=paid', count(array_filter($owner->get('owner/invoices/list', ['vendor_id' => 2])['data']['items'],
        function ($row) { return $row['vendor_id'] !== 2; })) === 0 && $owner->get('owner/invoices/list', ['status' => 'paid'])['data']['total'] === 3);
    $detail = $owner->get('owner/invoices/get', ['id' => $calendarInvoice])['data'] ?? [];
    check('Butiran owner: vendor { registration_no, address, email }', $detail['vendor']['registration_no'] === 'JM0876543-T'
        && $detail['vendor']['email'] === 'meiling@bestari.my' && $detail['vendor']['id'] === 2);
    $future = $owner->post('owner/invoices/mark-paid', ['id' => $calendarInvoice, 'method' => 'bank_transfer', 'reference' => 'BT-B12-1',
        'paid_at' => BillingPeriod::addDays($today, 1)]);
    $noRef = $owner->post('owner/invoices/mark-paid', ['id' => $calendarInvoice, 'method' => 'bank_transfer', 'paid_at' => $today]);
    $badMethod = $owner->post('owner/invoices/mark-paid', ['id' => $calendarInvoice, 'method' => 'kripto', 'reference' => 'X', 'paid_at' => $today]);
    $paidAlready = $owner->post('owner/invoices/mark-paid', ['id' => $newsInvoice['id'], 'method' => 'cash', 'reference' => 'C-1', 'paid_at' => $today]);
    check('Tanda dibayar: tarikh hadapan / tiada rujukan / cara tidak sah / invois sudah dibayar → ditolak', isset($future['errors']['paid_at'])
        && isset($noRef['errors']['reference']) && isset($badMethod['errors']['method']) && $paidAlready['error'] === t('invoice.cannot_mark_paid'));
    $res = $owner->post('owner/invoices/mark-paid', ['id' => $calendarInvoice, 'method' => 'bank_transfer', 'reference' => 'BT-B12-1',
        'paid_at' => $today, 'note' => 'Maybank']);
    $manual = Database::fetchOne("SELECT * FROM platform_payments WHERE invoice_id = ? AND method = 'bank_transfer'", [$calendarInvoice]);
    check('Tanda dibayar (pindahan bank) → paid, bayaran manual + nota, log audit invoice.mark_paid', ok($res) && $res['data']['status'] === 'paid'
        && $manual['gateway_ref'] === 'BT-B12-1' && $manual['note'] === 'Maybank' && $manual['status'] === 'paid' && auditCount(2, 'invoice.mark_paid') === 1);
    $res = $owner->post('owner/invoices/mark-paid', ['id' => $templateInvoiceId, 'method' => 'cash', 'reference' => 'BT-B12-1', 'paid_at' => $today]);
    check('Rujukan yang sama untuk invois lain → errors.reference', !ok($res) && $res['errors']['reference'] === t('invoice.reference_used'));
    $res = $owner->post('owner/invoices/void', ['id' => $templateInvoiceId]);
    $again = $owner->post('owner/invoices/void', ['id' => $templateInvoiceId]);
    $issue = $owner->post('owner/invoices/issue', ['id' => $calendarInvoice]);
    check('Batal invois template → void; batal lagi / terbit bukan draf → ditolak', ok($res) && invoice($templateInvoiceId)['status'] === 'void'
        && $again['error'] === t('invoice.cannot_void') && $issue['error'] === t('invoice.cannot_issue'));
    check('VENDOR02 tiada lagi invois belum dibayar', $v2->get('vendor/subscription/get')['data']['unpaid_count'] === 0);

    echo "9. Kitaran bil (cron/generate-invoices.php)\n";
    setPeriod(2, BillingPeriod::addDays($today, -27), BillingPeriod::addDays($today, 3));
    $cycle = (new RunBillingCycleJob())->run();
    $draft = Database::fetchOne("SELECT * FROM invoices WHERE vendor_id = 2 AND status = 'draft'");
    check('7 hari sebelum tamat → draf pembaharuan (tempoh seterusnya), vendor tidak nampak draf', $cycle['drafts'] === 1 && $draft !== null
        && $draft['period_start'] === BillingPeriod::addDays($today, 4)
        && !in_array('draft', array_column($v2->get('vendor/invoices/list', ['per_page' => 50])['data']['items'], 'status'), true)
        && $v2->status('vendor/invoices/get', ['id' => $draft['id']]) === 404
        && $owner->get('owner/invoices/list', ['status' => 'draft'])['data']['total'] === 1);
    check('Cron sekali lagi → tiada draf berganda', (new RunBillingCycleJob())->run()['drafts'] === 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM invoices WHERE vendor_id = 2 AND status = 'draft'") === 1);
    $v2->post('vendor/subscription/change', ['plan_id' => 1, 'billing_cycle' => 'yearly']);
    $v2->post('vendor/addons/cancel', $c3 + ['code' => 'clearance']);
    $yesterday = BillingPeriod::addDays($today, -1);
    // "Masa berlalu": tempoh tamat semalam → draf (tempoh seterusnya) kini bermula hari ini
    setPeriod(2, BillingPeriod::addDays($today, -31), $yesterday);
    Database::execute("UPDATE invoices SET period_start = ? WHERE id = ?", [$today, $draft['id']]);
    $mailsBefore = (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue WHERE template_key = 'invoice_issued'");
    $cycle = (new RunBillingCycleJob())->run();
    $s = sub(2);
    $renewed = invoice($draft['id']);
    $renewedLines = lines($draft['id']);
    check('Tempoh tamat → pusingan: Asas TAHUNAN (pertukaran dijadualkan), tempoh baharu bermula hari ini, pending kosong', $cycle['renewed'] === 1
        && $s['billing_cycle'] === 'yearly' && $s['pending_plan_id'] === null && $s['current_period_start'] === $today
        && $s['current_period_end'] === BillingPeriod::endFor($today, 'yearly') && $s['status'] === 'active');
    check('Draf dikira semula & diterbitkan: pelan 49000 + koperasi 49000 + news 15000 + calendar 5000 + advanced_report 20000 (tahunan)',
        $renewed['status'] === 'issued' && $renewed['period_start'] === $today && (int) $renewed['subtotal'] === 138000
        && $renewedLines[0]['description'] === 'Pelan Asas (Tahunan)' && count($renewedLines) === 5
        && count(array_filter($renewedLines, function ($line) { return strpos($line['description'], 'Jualan murah') !== false; })) === 0);
    check('E-mel invois pembaharuan dihantar ke queue', (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue WHERE template_key = 'invoice_issued'")
        === $mailsBefore + 1);
    check('Modul dihentikan (clearance) tamat: expired, item ended_at semalam, store tiada jualan murah', moduleRow(3, 'clearance')['status'] === 'expired'
        && !ModuleAccess::has(2, 3, 'clearance') && openItem(3, 'module', 'clearance') === null
        && !in_array('clearance', $guest->get('store/school', ['slug' => 'smk-seri-indah'])['data']['modules'], true));
    check('Modul berterusan dilanjutkan & dihargakan semula tahunan; template tidak dipakai berhenti', moduleRow(3, 'news')['ends_at'] === $s['current_period_end']
        && (int) openItem(3, 'module', 'news')['unit_price'] === 15000 && (int) openItem(3, 'cooperative')['unit_price'] === 49000
        && openItem(3, 'template') === null);
    check('Tiada draf / invois berganda selepas pusingan', (new RunBillingCycleJob())->run() === ['drafts' => 0, 'renewed' => 0]
        && (int) Database::fetchValue("SELECT COUNT(*) FROM invoices i JOIN invoice_lines l ON l.invoice_id = i.id AND l.item_type = 'plan'
            WHERE i.vendor_id = 2 AND i.period_start = ?", [$today]) === 1);
    $count = invoiceCount(1);
    setPeriod(1, BillingPeriod::addDays($today, -14), $yesterday, ['status' => 'trial']);
    Database::execute("UPDATE vendors SET status = 'trial' WHERE id = 1");
    (new RunBillingCycleJob())->run();
    $trialInvoice = Database::fetchOne("SELECT * FROM invoices WHERE vendor_id = 1 ORDER BY id DESC LIMIT 1");
    check('Trial tamat → vendor & langganan active, invois pertama (Pro 99000 + 4 modul = 164000) diterbitkan', vendorStatus(1) === 'active'
        && sub(1)['status'] === 'active' && invoiceCount(1) === $count + 1 && $trialInvoice['status'] === 'issued'
        && (int) $trialInvoice['subtotal'] === 164000 && $trialInvoice['period_start'] === $today);
    $start = sub(1)['current_period_start'];
    setPeriod(1, BillingPeriod::addDays($today, -130), BillingPeriod::addDays($today, -100), ['billing_cycle' => 'monthly']);
    Database::execute("UPDATE subscription_items SET unit_price = 1000 WHERE vendor_id = 1 AND item_type = 'module'");
    (new RunBillingCycleJob())->run();
    check('Tempoh terlepas (> satu kitaran) tidak dicaj: tempoh baharu bermula hari ini', sub(1)['current_period_start'] === $today
        && sub(1)['current_period_end'] === BillingPeriod::endFor($today, 'monthly'));
    $draftV1 = null;
    setPeriod(1, BillingPeriod::addDays($today, -25), BillingPeriod::addDays($today, 5));
    (new RunBillingCycleJob())->run();
    $draftV1 = Database::fetchOne("SELECT * FROM invoices WHERE vendor_id = 1 AND status = 'draft'");
    $res = $owner->post('owner/invoices/issue', ['id' => $draftV1['id'] ?? 0]);
    check('Owner terbitkan draf lebih awal → issued + e-mel; pusingan kemudian tidak menerbitkan invois kedua', ok($res)
        && invoice($draftV1['id'])['status'] === 'issued' && auditCount(1, 'invoice.issue') === 1);
    $count = invoiceCount(1);
    setPeriod(1, BillingPeriod::addDays($today, -30), $yesterday);
    (new RunBillingCycleJob())->run();
    check('… pusingan V01 berlaku, tiada invois baharu', sub(1)['current_period_start'] === $today && invoiceCount(1) === $count);
    $cli = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(APP_ROOT . '/cron/generate-invoices.php'));
    $cli2 = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(APP_ROOT . '/cron/suspend-overdue.php'));
    check('cron/generate-invoices.php & suspend-overdue.php berjalan (CLI)', strpos((string) $cli, 'Langganan diperbaharui: 0') !== false
        && strpos((string) $cli2, 'Digantung:') !== false);

    echo "10. Tunggakan → past_due → digantung → store ditutup sementara → pulih\n";
    Database::execute("UPDATE invoices SET due_at = NOW() - INTERVAL 1 DAY WHERE id = ?", [$renewed['id']]);
    $result = (new SuspendOverdueJob())->run();
    check('Lepas tarikh akhir → V02 past_due (vendor & langganan), store masih dibuka', $result['past_due'] === 1 && vendorStatus(2) === 'past_due'
        && sub(2)['status'] === 'past_due' && auditCount(2, 'vendor.auto_past_due') === 1
        && $guest->status('store/school', ['slug' => 'smk-seri-indah']) === 200 && ok($v2->get('vendor/products/list', $c3)));
    check('Cron sekali lagi → tiada perubahan', (new SuspendOverdueJob())->run() === ['past_due' => 0, 'suspended' => 0]);
    Database::execute("UPDATE invoices SET due_at = NOW() - INTERVAL 8 DAY WHERE id = ?", [$renewed['id']]);
    $result = (new SuspendOverdueJob())->run();
    check('Lepas tempoh tangguh (7 hari) → digantung, nota & audit vendor.auto_suspend', $result['suspended'] === 1 && vendorStatus(2) === 'suspended'
        && sub(2)['status'] === 'suspended' && auditCount(2, 'vendor.auto_suspend') === 1
        && strpos((string) Database::fetchValue("SELECT body FROM vendor_notes WHERE vendor_id = 2 ORDER BY id DESC LIMIT 1"), $renewed['invoice_no']) !== false);
    $closed = $guest->get('store/school', ['slug' => 'smk-seri-indah']);
    check('Store: "Koperasi ditutup sementara" (503 + school_closed), halaman 503, tiada dalam carian sekolah', !ok($closed)
        && $closed['error'] === t('store.school_closed') && !empty($closed['school_closed'])
        && $guest->status('store/school', ['slug' => 'smk-seri-indah']) === 503
        && $guest->page('store/index.php?school=sjkc-chung-hwa')['status'] === 503
        && $parentB->status('store/catalog', ['slug' => 'smk-seri-indah']) === 503
        && $guest->get('store/schools/list')['data']['total'] === 1);
    check('Slug tidak wujud kekal 404 (bukan 503)', $guest->status('store/school', ['slug' => 'tiada-sekolah']) === 404);
    $panel = $v2->page('vendor/index.php');
    check('V02 pentadbir: panel dialih ke langganan; API operasi 403 billing_only; bil & katalog add-on dibenarkan', $panel['status'] === 302
        && strpos($panel['location'], 'vendor/subscription.php') !== false
        && !empty($v2->get('vendor/products/list', $c3)['billing_only']) && $v2->status('vendor/subscription/get') === 200
        && $v2->status('vendor/invoices/list') === 200 && $v2->status('vendor/addons/catalog', $c3) === 200
        && !empty($v2->post('vendor/addons/subscribe', $c3 + ['item_type' => 'module', 'code' => 'whatsapp_sms'])['billing_only']));
    check('Pengurus V02 → API 403 (billing_only)', $manager2->status('vendor/products/list', $c2) === 403);
    check('V01 tidak terjejas (store & panel)', $guest->status('store/school', ['slug' => 'sk-taman-melati']) === 200
        && ok($v1->get('vendor/products/list', $c1)));
    $res = $owner->post('owner/invoices/mark-paid', ['id' => $renewed['id'], 'method' => 'bank_transfer', 'reference' => 'BT-B12-2', 'paid_at' => $today]);
    check('Owner tanda dibayar → V02 pulih automatik (active), store dibuka semula', ok($res) && vendorStatus(2) === 'active'
        && sub(2)['status'] === 'active' && auditCount(2, 'vendor.auto_activate') === 1
        && $guest->status('store/school', ['slug' => 'smk-seri-indah']) === 200 && ok($v2->get('vendor/products/list', $c3)));
    $owner->post('owner/vendors/set-status', ['id' => 2, 'status' => 'suspended', 'reason' => 'Ujian B12 manual']);
    Database::beginTransaction();
    $manualId = Invoices::create(2, $today, $today, [['description' => 'Caj lain', 'item_type' => 'other', 'amount' => 100]], 'issued');
    Database::commit();
    $owner->post('owner/invoices/mark-paid', ['id' => $manualId, 'method' => 'cash', 'reference' => 'C-B12-3', 'paid_at' => $today]);
    check('Digantung MANUAL oleh owner → tidak dibuka automatik selepas bayaran', vendorStatus(2) === 'suspended' && invoice($manualId)['status'] === 'paid');
    $owner->post('owner/vendors/set-status', ['id' => 2, 'status' => 'active']);

    echo "11. Langganan semula (SubscribeJob)\n";
    Database::execute("UPDATE subscriptions SET status = 'cancelled', cancelled_at = NOW() WHERE vendor_id = 2");
    Database::execute("UPDATE vendors SET status = 'cancelled' WHERE id = 2");
    $none = $v2->get('vendor/subscription/get')['data'] ?? [];
    check('Dibatalkan → plan null / status none; kedai add-on → billing.no_subscription', $none['plan'] === null && $none['status'] === 'none'
        && $v2->get('vendor/addons/catalog', $c3)['error'] === t('billing.no_subscription'));
    $count = invoiceCount(2);
    $res = $v2->post('vendor/subscription/change', ['plan_id' => 1, 'billing_cycle' => 'monthly']);
    $s = sub(2);
    $first = Database::fetchOne("SELECT * FROM invoices WHERE vendor_id = 2 ORDER BY id DESC LIMIT 1");
    check('Pilih Asas bulanan → langganan baharu aktif hari ini, vendor active, invois tempoh penuh diterbitkan', ok($res)
        && $res['data']['plan']['code'] === 'ASAS' && $s['status'] === 'active' && $s['current_period_start'] === $today
        && $s['billing_cycle'] === 'monthly' && vendorStatus(2) === 'active' && invoiceCount(2) === $count + 1 && $first['status'] === 'issued'
        && $first['period_end'] === BillingPeriod::endFor($today, 'monthly'));
    check('Item lama dipindah & dihargakan semula bulanan: 4900 + 4900 + news 1500 + calendar 500 + advanced_report 2000 = 13800',
        (int) $first['subtotal'] === 13800 && (int) openItem(3, 'module', 'news')['subscription_id'] === (int) $s['id']
        && (int) openItem(3, 'module', 'news')['unit_price'] === 1500);
    $res = $v2->post('vendor/subscription/change', ['plan_id' => 1, 'billing_cycle' => 'monthly']);
    check('Langganan hidup → change biasa (tiada langganan kedua)', ok($res) && (int) Database::fetchValue(
        "SELECT COUNT(*) FROM subscriptions WHERE vendor_id = 2 AND status <> 'cancelled'") === 1);

    echo "12. Koperasi tambahan → invois prorata\n";
    $s1 = sub(1);
    $count = invoiceCount(1);
    $res = $v1->post('vendor/cooperatives/save', newCoopInput('sk-ujian-b12'));
    $newCoop = (int) ($res['data']['id'] ?? 0);
    $coopInvoice = Database::fetchOne("SELECT * FROM invoices WHERE vendor_id = 1 ORDER BY id DESC LIMIT 1");
    $coopLine = lines($coopInvoice['id'])[0] ?? [];
    $expected = prorata(7900, $s1['current_period_start'], $s1['current_period_end']);
    check('Koperasi ke-2 (Pro bulanan, peringkat 7900) → item snapshot + invois prorata ' . $expected, ok($res) && invoiceCount(1) === $count + 1
        && (int) openItem($newCoop, 'cooperative')['unit_price'] === 7900 && $coopLine['item_type'] === 'cooperative'
        && (int) $coopLine['cooperative_id'] === $newCoop && (int) $coopLine['amount'] === $expected);
    check('Anggaran pembaharuan V01 kini ada koperasi tambahan', in_array('cooperative', array_column(
        $v1->get('vendor/subscription/get')['data']['renewal']['lines'], 'type'), true));

    echo "13. Log aktiviti\n";
    $act2 = $v2->get('vendor/activity-logs/list', ['per_page' => 100])['data'] ?? [];
    $act1 = $v1->get('vendor/activity-logs/list', ['per_page' => 100])['data'] ?? [];
    check('V02: module_subscribe & template_subscribe dengan nama koperasi & pengguna; senarai pengguna V02 sahaja',
        in_array('module_subscribe', array_column($act2['items'], 'action'), true) && in_array('template_subscribe', array_column($act2['items'], 'action'), true)
        && in_array('SMK Seri Indah', array_column($act2['items'], 'cooperative_name'), true)
        && in_array('Lim Mei Ling', array_column($act2['items'], 'user_name'), true)
        && !in_array('Ahmad bin Ismail', array_column($act2['users'], 'full_name'), true));
    check('V01 tidak nampak log V02', !in_array('SMK Seri Indah', array_column($act1['items'], 'cooperative_name'), true)
        && in_array('subscription_change', array_column($act1['items'], 'action'), true));
    $actM = $manager2->get('vendor/activity-logs/list', ['per_page' => 100])['data'] ?? null;
    check('Pengurus Chung Hwa: koperasi 2 sahaja (tiada log koperasi 3 / peringkat vendor)', $actM !== null
        && count(array_filter($actM['items'], function ($row) { return $row['cooperative_id'] !== 2; })) === 0);
    check('Skop koperasi semasa: V02 → koperasi V01 404; tapis koperasi 3 sahaja', $v2->status('vendor/activity-logs/list', $c1 + ['scope' => 'current']) === 404
        && count(array_filter($v2->get('vendor/activity-logs/list', $c3 + ['scope' => 'current', 'per_page' => 100])['data']['items'],
            function ($row) { return $row['cooperative_id'] !== 3; })) === 0);

    echo "14. Ibu bapa A ↔ B\n";
    check('Ibu bapa A & B: store dibuka semula; tiada akses bil vendor', $parentA->status('store/school', ['slug' => 'sjkc-chung-hwa']) === 200
        && $parentB->status('store/school', ['slug' => 'smk-seri-indah']) === 200 && $parentA->status('vendor/invoices/get', ['id' => 1]) === 401
        && $parentB->postStatus('vendor/invoices/pay', ['id' => 2]) === 401);
} finally {
    proc_terminate($server);
    proc_terminate($fake);
    Database::rollBack();
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
    @unlink($stateFile);
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
