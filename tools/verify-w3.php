<?php
// Semakan W3 — templat WhatsApp diluluskan Meta (WhatsApp Cloud), e-mel "refund diproses" kepada ibu bapa, peringatan invois
// tertunggak & notis digantung kepada vendor (sekali setiap peralihan), templat baharu boleh disunting owner. CLI sahaja:
//   php tools/verify-w3.php
// Perlu migrasi 009. Skrip memulakan pelayan aplikasi (php -S) + pembekal mesej palsu (tools/fake-messaging.php). Jadual dipulihkan.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

function fakeRequests($fakeBase)
{
    return (array) json_decode((string) file_get_contents($fakeBase . '/_admin/requests'), true);
}

function emails($key)
{
    return Database::fetchAll("SELECT * FROM email_queue WHERE template_key = ? ORDER BY id", [$key]);
}

function placeCash(Browser $parent, $productId, $qty, $childId)
{
    Database::execute("DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.customer_id = (SELECT customer_id FROM children WHERE id = ?)",
        [$childId]);
    $parent->post('store/cart/add', ['slug' => 'sk-taman-melati', 'product_id' => $productId, 'qty' => $qty, 'child_id' => $childId]);
    $res = $parent->post('store/checkout/place', ['slug' => 'sk-taman-melati', 'pickup_by_type' => 'student', 'payment_method' => 'cash']);
    return ok($res) ? (int) $res['data']['order_id'] : 0;
}

$port = 8180;
$fakePort = 8181;
$base = 'http://127.0.0.1:' . $port;
$fakeBase = 'http://127.0.0.1:' . $fakePort;
$log = sys_get_temp_dir() . '/kop-verify-w3.log';
$stateFile = sys_get_temp_dir() . '/kop-verify-w3-messaging.json';
@unlink($stateFile);
$fake = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $fakePort, __DIR__ . '/fake-messaging.php'], [1 => ['file', $log . '.fake', 'w'], 2 => ['file', $log . '.fake', 'a']],
    $fakePipes, null, ['FAKE_MESSAGING_STATE' => $stateFile] + getenv());
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4'] + getenv());
usleep(900000);
putenv('KOPERASI_MESSAGING_TEST_URL=' . $fakeBase);

$tables = ['messaging_settings', 'notification_queue', 'email_queue', 'email_templates', 'activity_logs', 'audit_logs', 'orders', 'order_items',
    'order_status_logs', 'order_pickups', 'order_pickup_items', 'stock_levels', 'stock_movements', 'carts', 'cart_items', 'payments', 'refunds',
    'refund_items', 'invoices', 'vendors', 'subscriptions', 'vendor_notes', 'customers', 'login_attempts', 'rate_limits', 'platform_users',
    'totp_recovery_codes', 'promotions'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
Database::execute("UPDATE promotions SET is_active = 0");
Database::execute("UPDATE stock_levels SET qty_on_hand = 200, qty_reserved = 0 WHERE cooperative_id = 1 AND product_id = 1 AND variant_id = 0");

try {
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    list($owner) = ownerLogin($base, 'owner@koperasiku.my');
    $c1 = ['cooperative_id' => 1];

    echo "1. Templat WhatsApp diluluskan Meta\n";
    $res = $v1->get('vendor/messaging/get', $c1);
    check('get: meta_templates kosong (lalai ms) + tertib parameter setiap peristiwa', ok($res)
        && $res['data']['meta_templates']['order_ready'] === ['name' => '', 'language' => 'ms']
        && $res['data']['meta_params']['order_ready'] === ['parent_name', 'child_name', 'short_code', 'school_name']);
    $templates = ['order_ready' => 'Salam {parent_name}, {short_code} sedia.', 'order_collected' => '{short_code} diambil oleh {picked_by}.',
        'cash_reminder' => 'Pesanan {short_code} luput {expires_on}.'];
    $settings = ['provider' => 'whatsapp_cloud', 'channel' => 'whatsapp', 'sender' => '60123456789', 'api_key' => 'PNID1:secret', 'is_enabled' => true,
        'events' => ['order_ready' => true, 'order_collected' => true, 'cash_reminder' => false], 'templates' => $templates];
    $res = $v1->post('vendor/messaging/save', $c1 + $settings + ['meta_templates' => [
        'order_ready' => ['name' => 'Pesanan Sedia!', 'language' => 'ms'], 'order_collected' => ['name' => '', 'language' => 'melayu']]]);
    check('Nama templat (huruf besar/ruang) & kod bahasa tidak sah → ditolak', !ok($res)
        && $res['errors']['meta_templates.order_ready.name'] === t('messaging.meta_name_format')
        && $res['errors']['meta_templates.order_collected.language'] === t('messaging.meta_language_format'));
    $res = $v1->post('vendor/messaging/save', $c1 + $settings + ['meta_templates' => [
        'order_ready' => ['name' => 'order_ready_v2', 'language' => 'ms'], 'order_collected' => ['name' => '', 'language' => 'en']]]);
    check('Simpan nama templat Meta order_ready_v2 (ms); order_collected teks biasa', ok($res)
        && $res['data']['meta_templates']['order_ready'] === ['name' => 'order_ready_v2', 'language' => 'ms']
        && $res['data']['meta_templates']['order_collected']['name'] === '');
    $orderId = placeCash($parentA, 1, 1, 1);
    $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId, 'status' => 'preparing']);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId, 'status' => 'ready']);
    $row = Database::fetchOne("SELECT * FROM notification_queue WHERE event = 'order_ready' AND order_id = ?", [$orderId]);
    $template = json_decode((string) $row['template_json'], true);
    check('Sedia diambil → baris gilir dengan template_json {name, language, params ikut tertib}; teks kekal untuk log', $row !== null
        && $template['name'] === 'order_ready_v2' && $template['language'] === 'ms'
        && $template['params'] === ['Siti Nurhaliza binti Ahmad', 'Aisyah binti Faizal', $order['short_code'], 'SK Taman Melati']
        && $row['message'] === 'Salam Siti Nurhaliza binti Ahmad, ' . $order['short_code'] . ' sedia.');
    $sent = (new SendQueuedMessagesJob())->run();
    $requests = fakeRequests($fakeBase);
    $last = end($requests);
    check('Cron → WhatsApp Cloud palsu menerima mesej type=template, nama, bahasa & 4 parameter badan', $sent['sent'] === 1
        && $last['provider'] === 'whatsapp_cloud' && $last['type'] === 'template' && $last['template']['name'] === 'order_ready_v2'
        && $last['template']['language']['code'] === 'ms'
        && array_column($last['template']['components'][0]['parameters'], 'text') === $template['params']);
    $paid = $v1->post('vendor/handover/receive-cash', $c1 + ['id' => $orderId, 'amount_received' => (int) $order['total']]);
    $items = Database::fetchAll("SELECT id, qty FROM order_items WHERE order_id = ?", [$orderId]);
    $v1->post('vendor/handover/collect', $c1 + ['id' => $orderId, 'picked_by_type' => 'student', 'picked_by_name' => 'Aisyah',
        'items' => array_map(function ($item) { return ['item_id' => (int) $item['id'], 'qty' => (int) $item['qty']]; }, $items)]);
    $collected = Database::fetchOne("SELECT * FROM notification_queue WHERE event = 'order_collected' AND order_id = ?", [$orderId]);
    (new SendQueuedMessagesJob())->run();
    $requests = fakeRequests($fakeBase);
    $last = end($requests);
    check('Peristiwa tanpa nama templat → teks biasa (template_json null, type=text)', ok($paid) && $collected !== null
        && $collected['template_json'] === null && $last['type'] === 'text' && $last['text'] === $collected['message']);
    $v1->post('vendor/messaging/save', $c1 + array_merge($settings, ['provider' => 'twilio', 'channel' => 'sms', 'api_key' => 'AC1:secret']) + ['meta_templates' => [
        'order_ready' => ['name' => 'order_ready_v2', 'language' => 'ms']]]);
    $orderId2 = placeCash($parentA, 1, 1, 1);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId2, 'status' => 'preparing']);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId2, 'status' => 'ready']);
    check('Twilio/SMS: nama templat Meta diabaikan (template_json null)', Database::fetchValue(
        "SELECT template_json FROM notification_queue WHERE event = 'order_ready' AND order_id = ?", [$orderId2]) === null
        && Database::fetchValue("SELECT COUNT(*) FROM notification_queue WHERE event = 'order_ready' AND order_id = ?", [$orderId2]) == 1);
    $page = $v1->page('vendor/addons/whatsapp_sms.php');
    check('Halaman WhatsApp/SMS: medan templat Meta setiap peristiwa', $page['status'] === 200
        && substr_count($page['body'], 'name="meta_templates.') === 6);

    echo "2. E-mel refund diproses (ibu bapa)\n";
    $before = count(emails('refund_processed'));
    $res = $v1->post('vendor/refunds/create', $c1 + ['order_id' => $orderId, 'amount' => 100, 'method' => 'bank_transfer', 'items' => [],
        'restock' => false, 'reason' => 'Lebihan bayaran']);
    $mails = emails('refund_processed');
    $mail = end($mails);
    check('Refund pindahan bank → satu e-mel refund_processed ke e-mel pesanan (ms): no. refund, amaun, cara, sebab', ok($res)
        && count($mails) === $before + 1 && $mail['to_email'] === 'siti@contoh.my' && strpos($mail['subject'], $res['data']['refund_no']) !== false
        && strpos($mail['body_html'], 'RM 1.00') !== false && strpos($mail['body_html'], t('refund.method.bank_transfer')) !== false
        && strpos($mail['body_html'], 'Lebihan bayaran') !== false && strpos($mail['body_html'], 'SK Taman Melati') !== false);
    $res = $owner->post('owner/email-templates/save', ['template_key' => 'refund_processed', 'language_code' => 'ms',
        'subject' => 'Wang dipulangkan {refund_no}', 'body_html' => '<p>Hai {customer_name}, {amount} ({method}) — <b>{reason}</b></p>']);
    check('Owner boleh menyunting templat refund_processed (pemegang tempat dikenali)', ok($res));
    $res = $v1->post('vendor/refunds/create', $c1 + ['order_id' => $orderId, 'amount' => 50, 'method' => 'cash', 'items' => [],
        'restock' => false, 'reason' => '<script>x</script>']);
    $mails = emails('refund_processed');
    $mail = end($mails);
    check('Templat owner digunakan; sebab di-escape (tiada <script>)', ok($res) && strpos($mail['subject'], 'Wang dipulangkan ' . $res['data']['refund_no']) === 0
        && strpos($mail['body_html'], '<b>&lt;script&gt;') !== false && strpos($mail['body_html'], '<script>') === false);
    $list = $owner->get('owner/email-templates/list');
    $keys = array_column($list['data'] ?? [], 'template_key');
    check('Senarai templat owner: refund_processed, invoice_overdue, vendor_suspended', ok($list)
        && !array_diff(['refund_processed', 'invoice_overdue', 'vendor_suspended'], $keys));

    echo "3. Tunggakan & digantung (vendor)\n";
    Database::execute("UPDATE invoices SET status = 'issued', paid_at = NULL, due_at = NOW() - INTERVAL 2 DAY WHERE id = 2");
    Database::execute("UPDATE platform_settings SET setting_value = '7' WHERE setting_key = 'grace_days'");
    $result = (new SuspendOverdueJob())->run();
    $overdue = emails('invoice_overdue');
    $expectedSuspend = date('d/m/Y', strtotime(Database::fetchValue("SELECT due_at FROM invoices WHERE id = 2") . ' +7 day'));
    check('Lepas tarikh akhir → past_due + e-mel invoice_overdue ke e-mel syarikat VENDOR02 (tarikh akan digantung)', $result['past_due'] === 1
        && count($overdue) === 1 && $overdue[0]['to_email'] === 'meiling@bestari.my' && (int) $overdue[0]['vendor_id'] === 2
        && strpos($overdue[0]['body_html'], 'INV-2026-0002') !== false && strpos($overdue[0]['body_html'], $expectedSuspend) !== false);
    (new SuspendOverdueJob())->run();
    check('Larian cron kedua → tiada e-mel pendua', count(emails('invoice_overdue')) === 1);
    Database::execute("UPDATE invoices SET due_at = NOW() - INTERVAL 10 DAY WHERE id = 2");
    $result = (new SuspendOverdueJob())->run();
    $suspended = emails('vendor_suspended');
    check('Melepasi tempoh tangguh → suspended + e-mel vendor_suspended (sekali)', $result['suspended'] === 1 && count($suspended) === 1
        && $suspended[0]['to_email'] === 'meiling@bestari.my' && Database::fetchValue("SELECT status FROM vendors WHERE id = 2") === 'suspended');
    (new SuspendOverdueJob())->run();
    check('Tiada e-mel ke VENDOR01 & tiada pendua', count(emails('vendor_suspended')) === 1
        && !Database::fetchValue("SELECT 1 FROM email_queue WHERE template_key IN ('invoice_overdue', 'vendor_suspended') AND vendor_id = 1"));
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
