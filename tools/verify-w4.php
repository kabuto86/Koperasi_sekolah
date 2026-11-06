<?php
// Semakan W4 — modul add-on laporan lanjutan (perbandingan tahun, analisis kit), keahlian koperasi (ahli, syer, dividen)
// dan halaman apps mudah alih vendor (statistik penggunaan + pautan muat turun tetapan owner). Gerbang modul, peranan &
// ujian kebocoran VENDOR01 ↔ VENDOR02. CLI sahaja:
//   php tools/verify-w4.php
// Perlu migrasi 010 (jadual keahlian). Skrip memulakan pelayan aplikasi (php -S). Jadual yang diubah dipulihkan.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

// Pesanan tunai store dibayar di kaunter, kemudian paid_at ditetapkan. $lines = [[product_id, qty], …]. Pulangkan id pesanan
function paidOrder(Browser $parent, Browser $vendor, array $lines, $paidAt)
{
    Database::execute("DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.customer_id = 1");
    foreach ($lines as $line) {
        $parent->post('store/cart/add', ['slug' => 'sk-taman-melati', 'product_id' => $line[0], 'qty' => $line[1], 'child_id' => 1]);
    }
    $res = $parent->post('store/checkout/place', ['slug' => 'sk-taman-melati', 'pickup_by_type' => 'student', 'payment_method' => 'cash']);
    $orderId = ok($res) ? (int) $res['data']['order_id'] : 0;
    $total = (int) Database::fetchValue("SELECT total FROM orders WHERE id = ?", [$orderId]);
    $vendor->post('vendor/handover/receive-cash', ['cooperative_id' => 1, 'id' => $orderId, 'amount_received' => $total]);
    Database::execute("UPDATE orders SET paid_at = ? WHERE id = ?", [$paidAt, $orderId]);
    return $orderId;
}

function subscribeModule($vendorId, $cooperativeId, $code)
{
    $moduleId = (int) Database::fetchValue("SELECT id FROM modules WHERE code = ?", [$code]);
    Database::execute("UPDATE modules SET is_active = 1 WHERE id = ?", [$moduleId]);
    Database::execute(
        "INSERT INTO cooperative_modules (vendor_id, cooperative_id, module_id, status, started_at, ends_at) VALUES (?, ?, ?, 'active', CURDATE(), CURDATE() + INTERVAL 30 DAY)
         ON DUPLICATE KEY UPDATE status = 'active', ends_at = CURDATE() + INTERVAL 30 DAY",
        [$vendorId, $cooperativeId, $moduleId]
    );
}

$port = 8190;
$base = 'http://127.0.0.1:' . $port;
$log = sys_get_temp_dir() . '/kop-verify-w4.log';
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4'] + getenv());
usleep(900000);

$tables = ['orders', 'order_items', 'order_status_logs', 'stock_levels', 'stock_movements', 'carts', 'cart_items', 'payments', 'email_queue',
    'activity_logs', 'audit_logs', 'cooperative_modules', 'modules', 'membership_members', 'membership_share_transactions', 'membership_dividends',
    'membership_dividend_lines', 'api_tokens', 'push_devices', 'notification_queue', 'platform_settings', 'login_attempts', 'rate_limits',
    'platform_users', 'totp_recovery_codes', 'promotions'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
Database::execute("UPDATE promotions SET is_active = 0");
Database::execute("UPDATE stock_levels SET qty_on_hand = 500, qty_reserved = 0 WHERE cooperative_id = 1 AND variant_id = 0");
$year = (int) date('Y');
$prev = $year - 1;

try {
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $cashier1 = vendorLogin($base, 'kasim@niagasekolah.my');
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    $c1 = ['cooperative_id' => 1];
    $c2 = ['cooperative_id' => 2];

    echo "1. Gerbang modul & peranan\n";
    check('Belum melanggan: advanced-report / membership / mobile-app → 403 module_locked', !empty($v1->get('vendor/advanced-report/run', $c1 + ['type' => 'year_compare', 'year' => $year])['module_locked'])
        && !empty($v1->get('vendor/membership/members', $c1)['module_locked']) && !empty($v1->get('vendor/mobile-app/stats', $c1)['module_locked']));
    subscribeModule(1, 1, 'advanced_report');
    subscribeModule(1, 1, 'membership');
    subscribeModule(1, 1, 'mobile_app');
    check('Juruwang → 403 (bukan modul pengurusan)', $cashier1->status('vendor/advanced-report/run', $c1) === 403
        && $cashier1->status('vendor/membership/members', $c1) === 403 && $cashier1->status('vendor/mobile-app/stats', $c1) === 403);
    check('VENDOR02 + koperasi 1 (milik VENDOR01) → 404', $v2->status('vendor/advanced-report/run', $c1 + ['type' => 'year_compare', 'year' => $year]) === 404
        && $v2->status('vendor/membership/members', $c1) === 404 && $v2->status('vendor/mobile-app/stats', $c1) === 404);
    check('VENDOR02 koperasi sendiri tanpa modul → module_locked', !empty($v2->get('vendor/membership/members', $c2)['module_locked']));
    check('POST keahlian tanpa CSRF → 419', $v1->postStatus('vendor/membership/save-member', $c1 + ['full_name' => 'X'], false) === 419);
    foreach (['advanced_report', 'membership', 'mobile_app'] as $code) {
        $page = $v1->page('vendor/addons/' . $code . '.php');
        check("Halaman vendor/addons/$code.php 200 (bukan 'akan datang')", $page['status'] === 200 && strpos($page['body'], 'module-content') !== false
            && strpos($page['body'], 'coming-soon') === false);
    }

    echo "2. Laporan lanjutan\n";
    $o1 = paidOrder($parentA, $v1, [[1, 2]], "$prev-03-15 10:00:00");
    $o2 = paidOrder($parentA, $v1, [[1, 3]], "$year-03-10 10:00:00");
    $o3 = paidOrder($parentA, $v1, [[2, 1]], "$year-05-05 10:00:00");
    $t1 = (int) Database::fetchValue("SELECT total FROM orders WHERE id = ?", [$o1]);
    $t2 = (int) Database::fetchValue("SELECT total FROM orders WHERE id = ?", [$o2]);
    $t3 = (int) Database::fetchValue("SELECT total FROM orders WHERE id = ?", [$o3]);
    $res = $v1->get('vendor/advanced-report/run', $c1 + ['type' => 'year_compare', 'year' => $year]);
    $march = $res['data']['rows'][2] ?? [];
    check('year_compare: 12 bulan, Mac tahun lepas vs tahun ini + perubahan %', ok($res) && count($res['data']['rows']) === 12
        && $march['month'] === "$year-03" && $march['net_prev'] === $t1 && $march['net_cur'] === $t2
        && $march['change'] == round(($t2 - $t1) / $t1 * 100, 1) && $res['data']['rows'][4]['change'] === null
        && $res['data']['totals']['net_cur'] === $t2 + $t3 && $res['data']['compare_year'] === $prev);
    $res = $v1->get('vendor/advanced-report/run', $c1 + ['type' => 'product_compare', 'year' => $year]);
    $rows = array_column($res['data']['rows'] ?? [], null, 'product');
    $product1 = Database::fetchValue("SELECT name FROM products WHERE id = 1");
    check('product_compare: produk 1 — 2 unit tahun lepas, 3 unit tahun ini', ok($res) && $rows[$product1]['qty_prev'] === 2 && $rows[$product1]['qty_cur'] === 3);
    // Kit Darjah 1 2027 (kit 1: produk 1, 2 wajib; 3 pilihan; 4 wajib): dua pembelian kit — satu dengan produk 1 & 2, satu produk 1 sahaja
    $k1 = paidOrder($parentA, $v1, [[1, 6], [2, 1]], "$year-01-05 09:00:00");
    $k2 = paidOrder($parentA, $v1, [[1, 6]], "$year-01-06 09:00:00");
    Database::execute("UPDATE order_items SET source = 'kit', school_kit_id = 1 WHERE order_id IN (?, ?)", [$k1, $k2]);
    $res = $v1->get('vendor/advanced-report/run', $c1 + ['type' => 'kit_summary', 'year' => 2027]);
    $d1 = $res['data']['rows'][0] ?? [];
    $kitSales = (int) Database::fetchValue("SELECT SUM(line_total) FROM order_items WHERE order_id IN (?, ?)", [$k1, $k2]);
    check('kit_summary: kit D1 & D4 2027, D1 = 2 pembelian, jualan & purata', ok($res) && count($res['data']['rows']) === 2 && $d1['grade'] === 'D1'
        && $d1['items'] === 4 && $d1['purchases_cur'] === 2 && $d1['sales'] === $kitSales && $d1['avg_purchase'] === (int) round($kitSales / 2));
    $res = $v1->get('vendor/advanced-report/run', $c1 + ['type' => 'kit_items', 'year' => 2027]);
    $items = [];
    foreach ($res['data']['rows'] ?? [] as $row) {
        if ($row['grade'] === 'D1') {
            $items[$row['product']] = $row;
        }
    }
    $product2 = Database::fetchValue("SELECT name FROM products WHERE id = 2");
    $product3 = Database::fetchValue("SELECT name FROM products WHERE id = 3");
    check('kit_items: produk 1 uncheck 0%, produk 2 50%, produk 3 (pilihan) 100%; disusun ikut kadar', ok($res)
        && $items[$product1]['uncheck_rate'] == 0 && $items[$product1]['included'] === 2 && $items[$product2]['uncheck_rate'] == 50
        && $items[$product3]['uncheck_rate'] == 100 && $items[$product3]['mandatory'] === t('advreport.optional')
        && $res['data']['rows'][0]['uncheck_rate'] == 100);
    check('Jenis / tahun tidak sah → ralat pengesahan', !ok($v1->get('vendor/advanced-report/run', $c1 + ['type' => 'x', 'year' => $year]))
        && !ok($v1->get('vendor/advanced-report/run', $c1 + ['type' => 'year_compare', 'year' => 1999])));
    subscribeModule(2, 2, 'advanced_report');
    $res = $v2->get('vendor/advanced-report/run', $c2 + ['type' => 'year_compare', 'year' => $year]);
    check('VENDOR02 (koperasi 2): tiada jualan VENDOR01 dalam laporan', ok($res) && $res['data']['totals']['net_cur'] === 0);

    echo "3. Keahlian — ahli & syer\n";
    $res = $v1->post('vendor/membership/save-member', $c1 + ['full_name' => '', 'member_type' => 'x', 'joined_on' => '2099-01-01', 'status' => 'active',
        'email' => 'bukan-emel']);
    check('Nama kosong, jenis tidak sah, e-mel tidak sah ditolak', !ok($res) && isset($res['errors']['full_name'], $res['errors']['member_type'], $res['errors']['email']));
    $res = $v1->post('vendor/membership/save-member', $c1 + ['full_name' => 'Ali', 'member_type' => 'student', 'joined_on' => '2099-01-01', 'status' => 'active']);
    check('Tarikh sertai masa hadapan ditolak', !ok($res) && isset($res['errors']['joined_on']));
    $a1 = $v1->post('vendor/membership/save-member', $c1 + ['full_name' => 'Aisyah binti Faizal', 'member_type' => 'student', 'class_name' => '4 Bestari',
        'joined_on' => "$prev-01-10", 'status' => 'active', 'member_no' => 'Z9', 'vendor_id' => 2]);
    $a2 = $v1->post('vendor/membership/save-member', $c1 + ['full_name' => 'Cikgu Rahim', 'member_type' => 'teacher', 'class_name' => 'Tidak disimpan',
        'joined_on' => "$prev-02-01", 'status' => 'active', 'email' => 'Rahim@Sekolah.my']);
    $m1 = $a1['data']['id'] ?? 0;
    $m2 = $a2['data']['id'] ?? 0;
    check('Ahli A0001 & A0002 (no. ahli dijana; member_no / vendor_id dari input diabaikan; kelas hanya murid; e-mel huruf kecil)', ok($a1) && ok($a2)
        && $a1['data']['member_no'] === 'A0001' && $a2['data']['member_no'] === 'A0002' && $a2['data']['class_name'] === ''
        && $a2['data']['email'] === 'rahim@sekolah.my' && (int) Database::fetchValue("SELECT vendor_id FROM membership_members WHERE id = ?", [$m1]) === 1);
    $edit = $v1->post('vendor/membership/save-member', $c1 + ['id' => $m1, 'full_name' => 'Aisyah Faizal', 'member_type' => 'student', 'class_name' => '5 Bestari',
        'joined_on' => "$prev-01-10", 'status' => 'active']);
    check('Sunting: no. ahli kekal', ok($edit) && $edit['data']['member_no'] === 'A0001' && $edit['data']['class_name'] === '5 Bestari');
    $tx = function ($memberId, $type, $amount, $date) use ($v1, $c1) {
        return $v1->post('vendor/membership/add-transaction', $c1 + ['member_id' => $memberId, 'type' => $type, 'amount' => $amount, 'transaction_date' => $date]);
    };
    $tx($m1, 'buy', 10000, "$prev-06-01");
    $tx($m2, 'buy', 5000, "$prev-12-31");
    $res = $tx($m1, 'buy', 2000, "$year-01-05");
    check('Beli syer → baki 120.00, sejarah 2 transaksi', ok($res) && $res['data']['share_balance'] === 12000 && count($res['data']['transactions']) === 2);
    $res = $tx($m1, 'withdraw', 20000, date('Y-m-d'));
    check('Keluarkan melebihi baki → ditolak', !ok($res) && isset($res['errors']['amount']));
    $res = $tx($m1, 'withdraw', 1000, date('Y-m-d'));
    check('Keluarkan 10.00 → baki 110.00', ok($res) && $res['data']['share_balance'] === 11000);
    check('Amaun 0 / tarikh masa hadapan / jenis dividend dari input → ditolak', !ok($tx($m1, 'buy', 0, date('Y-m-d')))
        && !ok($tx($m1, 'buy', 100, '2099-01-01')) && !ok($tx($m1, 'dividend', 100, date('Y-m-d'))));
    $list = $v1->get('vendor/membership/members', $c1 + ['q' => 'Rahim']);
    $all = $v1->get('vendor/membership/members', $c1);
    check('Senarai: carian & ringkasan (2 ahli aktif, jumlah syer 160.00)', ok($list) && $list['data']['total'] === 1
        && $all['data']['summary']['active_members'] === 2 && $all['data']['summary']['total_shares'] === 16000);

    echo "4. Keahlian — dividen\n";
    $res = $v1->get('vendor/membership/preview-dividend', $c1 + ['year' => $prev, 'rate' => '5']);
    check("Pratonton $prev @ 5%: A0001 100.00 → 5.00, A0002 50.00 → 2.50 (baki 31 Dis sahaja)", ok($res) && $res['data']['member_count'] === 2
        && $res['data']['lines'][0]['share_balance'] === 10000 && $res['data']['lines'][0]['amount'] === 500 && $res['data']['lines'][1]['amount'] === 250
        && $res['data']['total_amount'] === 750 && $res['data']['exists'] === false);
    check('Kadar / tahun tidak sah ditolak', !ok($v1->get('vendor/membership/preview-dividend', $c1 + ['year' => $prev, 'rate' => '0']))
        && !ok($v1->get('vendor/membership/preview-dividend', $c1 + ['year' => $prev, 'rate' => '5.555']))
        && !ok($v1->get('vendor/membership/preview-dividend', $c1 + ['year' => $year + 1, 'rate' => '5'])));
    $declared = $v1->post('vendor/membership/declare-dividend', $c1 + ['year' => $prev, 'rate' => '5', 'payout' => 'reinvest']);
    $dvId = $declared['data']['id'] ?? 0;
    check('Isytihar (reinvest): salinan baris disimpan, status declared', ok($declared) && $declared['data']['status'] === 'declared'
        && $declared['data']['total_amount'] === 750 && count($declared['data']['lines']) === 2);
    check('Isytihar tahun sama sekali lagi → ditolak', !ok($v1->post('vendor/membership/declare-dividend', $c1 + ['year' => $prev, 'rate' => '6', 'payout' => 'cash'])));
    check('Tahun tanpa ahli → ditolak', !ok($v1->post('vendor/membership/declare-dividend', $c1 + ['year' => $prev - 3, 'rate' => '5', 'payout' => 'cash'])));
    $paid = $v1->post('vendor/membership/pay-dividend', $c1 + ['id' => $dvId]);
    $member = $v1->get('vendor/membership/member', $c1 + ['id' => $m1]);
    check('Tanda dibayar (reinvest) → transaksi syer dividen 5.00, baki 115.00', ok($paid) && $paid['data']['status'] === 'paid'
        && ok($member) && $member['data']['share_balance'] === 11500 && $member['data']['transactions'][0]['type'] === 'dividend');
    check('Bayar kali kedua & padam dividen dibayar → ditolak', !ok($v1->post('vendor/membership/pay-dividend', $c1 + ['id' => $dvId]))
        && !ok($v1->post('vendor/membership/delete-dividend', $c1 + ['id' => $dvId])));
    $cash = $v1->post('vendor/membership/declare-dividend', $c1 + ['year' => $year, 'rate' => '2.5', 'payout' => 'cash']);
    $deleted = $v1->post('vendor/membership/delete-dividend', $c1 + ['id' => $cash['data']['id'] ?? 0]);
    check('Dividen belum dibayar boleh dipadam (baris ikut dipadam)', ok($cash) && ok($deleted)
        && !Database::fetchValue("SELECT 1 FROM membership_dividend_lines WHERE dividend_id = ?", [$cash['data']['id']]));
    $list = $v1->get('vendor/membership/dividends', $c1);
    check('Senarai dividen: 1 (dibayar)', ok($list) && count($list['data']) === 1 && $list['data'][0]['status'] === 'paid');
    $v1->post('vendor/membership/save-member', $c1 + ['id' => $m2, 'full_name' => 'Cikgu Rahim', 'member_type' => 'teacher', 'joined_on' => "$prev-02-01", 'status' => 'inactive']);
    check('Ahli tidak aktif tidak boleh beli syer', !ok($tx($m2, 'buy', 100, date('Y-m-d'))));
    check('Log aktiviti keahlian', (int) Database::fetchValue("SELECT COUNT(DISTINCT action) FROM activity_logs WHERE vendor_id = 1 AND action LIKE 'membership_%'") === 6);
    subscribeModule(2, 2, 'membership');
    check('VENDOR02 (koperasi 2) → ahli / dividen VENDOR01 404, senarai kosong', $v2->status('vendor/membership/member', $c2 + ['id' => $m1]) === 404
        && $v2->status('vendor/membership/dividend', $c2 + ['id' => $dvId]) === 404
        && !ok($v2->post('vendor/membership/add-transaction', $c2 + ['member_id' => $m1, 'type' => 'withdraw', 'amount' => 100, 'transaction_date' => date('Y-m-d')]))
        && !ok($v2->post('vendor/membership/pay-dividend', $c2 + ['id' => $dvId])) && $v2->get('vendor/membership/members', $c2)['data']['total'] === 0
        && Membership::balance(1, $m1) === 11500);

    echo "5. Apps mudah alih\n";
    $res = $v1->get('vendor/mobile-app/stats', $c1);
    check('Statistik: ibu bapa berdaftar 1 (Siti), belum guna apps, tiada pautan muat turun', ok($res) && $res['data']['parents'] === 1
        && $res['data']['app_parents'] === 0 && $res['data']['android_url'] === '' && strpos($res['data']['store_url'], 'sk-taman-melati') !== false);
    $app = new Browser($base);
    $token = $app->post('store/auth/token', ['login' => 'siti@contoh.my', 'password' => 'Demo@12345', 'device_name' => 'Ujian W4'], false);
    $app->bearer = $token['data']['token'] ?? '';
    $app->post('store/push/register', ['platform' => 'android', 'push_token' => str_repeat('w', 40)]);
    Database::execute("INSERT INTO notification_queue (vendor_id, cooperative_id, channel, event, recipient, message, status) VALUES
        (1, 1, 'push', 'news', 'x', '{}', 'sent'), (1, 1, 'push', 'order_ready', 'x', '{}', 'failed'), (2, 2, 'push', 'news', 'x', '{}', 'sent')");
    $res = $v1->get('vendor/mobile-app/stats', $c1);
    check('Selepas log masuk apps: 1 ibu bapa guna apps, 1 peranti, push ikut peristiwa (koperasi sendiri sahaja)', ok($res)
        && $res['data']['app_parents'] === 1 && $res['data']['devices'] === 1 && $res['data']['push']['news']['sent'] === 1
        && $res['data']['push']['order_ready']['failed'] === 1 && strpos(json_encode($res['data']), 'siti@') === false);
    list($owner) = ownerLogin($base, 'owner@koperasiku.my');
    $settings = array_merge($owner->get('owner/settings/get')['data'], ['smtp_host' => 'smtp.contoh.my', 'mail_from_address' => 'noreply@koperasiku.my']);
    $bad = $owner->post('owner/settings/save', array_merge($settings, ['app_android_url' => 'http://play.google.com/x']));
    check('Owner: pautan bukan https ditolak', !ok($bad) && isset($bad['errors']['app_android_url']));
    $saved = $owner->post('owner/settings/save', array_merge($settings, ['app_android_url' => 'https://play.google.com/store/apps/details?id=my.koperasiku',
        'app_ios_url' => '']));
    $res = $v1->get('vendor/mobile-app/stats', $c1);
    check('Owner simpan pautan Play Store → dipapar kepada vendor', ok($saved) && $res['data']['android_url'] === 'https://play.google.com/store/apps/details?id=my.koperasiku'
        && $res['data']['ios_url'] === '' && strpos($owner->page('owner/settings.php')['body'], 'name="app_android_url"') !== false);

    echo "6. Struktur\n";
    $apiFiles = array_merge(glob(APP_ROOT . '/api/v1/vendor/membership/*.php'), glob(APP_ROOT . '/api/v1/vendor/advanced-report/*.php'),
        glob(APP_ROOT . '/api/v1/vendor/mobile-app/*.php'));
    $bad = array_filter($apiFiles, function ($file) {
        $code = file_get_contents($file);
        return preg_match('/Database::|\b(SELECT|INSERT|UPDATE|DELETE)\b/', $code) || strpos($code, 'ModuleAccess::require') === false
            || preg_match("/Request::(int|string)\('vendor_id'/", $code);
    });
    check('12 endpoint: tiada SQL, ModuleAccess::require, tiada vendor_id dari input', count($apiFiles) === 12 && !$bad);
    check('Tiada lagi halaman "akan datang"', !is_file(APP_ROOT . '/vendor/_layout/addons/coming-soon.php') && !is_file(APP_ROOT . '/assets/js/vendor/addons/coming-soon.js'));
} finally {
    proc_terminate($server);
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
    clearTestLocks();
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
