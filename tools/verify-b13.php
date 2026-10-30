<?php
// Semakan B13 — modul add-on: berita (e-mel sekali sahaja, berjadual melalui cron, gambar), jualan murah (harga di store),
// kalendar cuti (tutup tempahan), pembukaan sekolah (musim, slot, kapasiti dalam transaksi), WhatsApp/SMS (tetapan disulitkan,
// baris gilir, cron hantar melalui pembekal palsu, peringatan tunai), ModuleAccess 403 + ujian kebocoran VENDOR01 ↔ VENDOR02
// dan ibu bapa A ↔ B. CLI sahaja:
//   php tools/verify-b13.php
// Perlu: DB koperasi_saas (schema + seed + migrasi 006), config/database.php, config/secret.key, sambungan PHP curl & gd.
// Skrip memulakan pelayan aplikasi (php -S, 4 pekerja) + pembekal mesej palsu (tools/fake-messaging.php). Jadual yang diubah dipulihkan.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

function locked($res)
{
    return is_array($res) && empty($res['ok']) && !empty($res['module_locked']);
}

function emails($template, $to = null)
{
    $sql = "SELECT COUNT(*) FROM email_queue WHERE template_key = ?";
    $args = [$template];
    if ($to !== null) {
        $sql .= " AND to_email = ?";
        $args[] = $to;
    }
    return (int) Database::fetchValue($sql, $args);
}

function activity($vendorId, $action)
{
    return (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE vendor_id = ? AND action = ?", [(int) $vendorId, $action]);
}

function subscribe($cooperativeId, $vendorId, $code)
{
    Database::execute(
        "INSERT INTO cooperative_modules (vendor_id, cooperative_id, module_id, status, started_at, ends_at)
         SELECT ?, ?, id, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY) FROM modules WHERE code = ?
         ON DUPLICATE KEY UPDATE status = 'active', ends_at = VALUES(ends_at)",
        [(int) $vendorId, (int) $cooperativeId, $code]
    );
}

function notifications($event = null)
{
    $sql = "SELECT * FROM notification_queue WHERE channel IN ('whatsapp', 'sms')";
    $args = [];
    if ($event !== null) {
        $sql .= " AND event = ?";
        $args[] = $event;
    }
    return Database::fetchAll($sql . " ORDER BY id", $args);
}

function fakeRequests($fakeBase)
{
    return (array) json_decode((string) file_get_contents($fakeBase . '/_admin/requests'), true);
}

// Pesanan tunai store (troli dikosongkan dahulu). Pulangkan id pesanan atau 0
function placeCash(Browser $parent, $slug, $productId, $qty, $childId, array $extra = [])
{
    Database::execute("DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.customer_id = (SELECT customer_id FROM children WHERE id = ?)",
        [$childId]);
    $parent->post('store/cart/add', ['slug' => $slug, 'product_id' => $productId, 'qty' => $qty, 'child_id' => $childId]);
    $res = $parent->post('store/checkout/place', ['slug' => $slug, 'pickup_by_type' => 'student', 'payment_method' => 'cash'] + $extra);
    return ok($res) ? (int) $res['data']['order_id'] : 0;
}

function pngDataUrl()
{
    $image = imagecreatetruecolor(4, 4);
    imagefill($image, 0, 0, imagecolorallocate($image, 15, 118, 110));
    ob_start();
    imagepng($image);
    imagedestroy($image);
    return 'data:image/png;base64,' . base64_encode(ob_get_clean());
}

$port = 8120;
$fakePort = 8121;
$base = 'http://127.0.0.1:' . $port;
$fakeBase = 'http://127.0.0.1:' . $fakePort;
$log = sys_get_temp_dir() . '/kop-verify-b13.log';
$stateFile = sys_get_temp_dir() . '/kop-verify-b13-messaging.json';
@unlink($stateFile);
$fake = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $fakePort, __DIR__ . '/fake-messaging.php'], [1 => ['file', $log . '.fake', 'w'], 2 => ['file', $log . '.fake', 'a']],
    $fakePipes, null, ['FAKE_MESSAGING_STATE' => $stateFile] + getenv());
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4'] + getenv());
usleep(900000);
putenv('KOPERASI_MESSAGING_TEST_URL=' . $fakeBase);

$tables = ['news_posts', 'clearance_sales', 'clearance_items', 'calendar_events', 'preorder_seasons', 'pickup_slots', 'messaging_settings',
    'notification_queue', 'cooperative_modules', 'email_queue', 'activity_logs', 'orders', 'order_items', 'order_status_logs', 'order_pickups',
    'order_pickup_items', 'stock_levels', 'stock_movements', 'carts', 'cart_items', 'payments', 'login_attempts', 'rate_limits'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
$uploaded = [];
clearTestLocks();

try {
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $cashier1 = vendorLogin($base, 'kasim@niagasekolah.my');
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    list($parentB) = storeLogin($base, 'rajesh@contoh.my');
    $guest = new Browser($base);
    $c1 = ['cooperative_id' => 1];
    $c2 = ['cooperative_id' => 2];
    $c3 = ['cooperative_id' => 3];

    echo "1. Peranan, CSRF & ModuleAccess\n";
    check('Tanpa sesi → 401 (news/list, clearance/list, calendar/list, school-opening/seasons, messaging/get)',
        $guest->status('vendor/news/list', $c1) === 401 && $guest->status('vendor/clearance/list', $c3) === 401
        && $guest->status('vendor/calendar/list', $c1) === 401 && $guest->status('vendor/school-opening/seasons', $c1) === 401
        && $guest->status('vendor/messaging/get', $c1) === 401);
    check('Juruwang → 403 (modul add-on untuk admin & pengurus sahaja)', $cashier1->status('vendor/news/list', $c1) === 403
        && $cashier1->status('vendor/calendar/list', $c1) === 403 && $cashier1->postStatus('vendor/school-opening/slot-delete', $c1 + ['id' => 1]) === 403);
    check('POST tanpa CSRF → 419; GET ke POST → 405', $v1->postStatus('vendor/news/save', $c1, false) === 419
        && $v1->getStatus('vendor/calendar/save') === 405);
    $res = $v1->get('vendor/clearance/list', $c1);
    check('Modul tidak dilanggan (clearance @ SK Taman Melati) → 403 module_locked', locked($res)
        && $v1->status('vendor/clearance/list', $c1) === 403 && $res['error'] === t('addon.module_locked'));
    check('Modul tidak dilanggan: messaging/get @ SMK, news/save @ SJKC (VENDOR02) → module_locked', locked($v2->get('vendor/messaging/get', $c3))
        && locked($v2->post('vendor/news/save', $c2 + ['title' => 'X', 'body' => 'Y', 'published_at' => '2026-01-01T08:00'])));
    check('Koperasi vendor lain → 404 sebelum semakan modul (V02 → koperasi 1, V01 → koperasi 3)', $v2->status('vendor/news/list', $c1) === 404
        && $v1->status('vendor/clearance/list', $c3) === 404 && $v2->postStatus('vendor/calendar/save', $c1 + ['title' => 'X']) === 404);

    echo "2. Berita — simpan, e-mel sekali sahaja, gambar\n";
    $recipients = $v1->get('vendor/news/recipients', $c1);
    check('Penerima = ibu bapa dengan anak aktif di koperasi (Siti → 1)', ok($recipients) && $recipients['data']['count'] === 1);
    $res = $v1->post('vendor/news/save', $c1 + ['title' => str_repeat('x', 151), 'body' => '', 'published_at' => '2026-13-40T99:00']);
    check('Pengesahan: tajuk > 150, isi kosong, tarikh tidak sah', !ok($res) && isset($res['errors']['title'], $res['errors']['body'], $res['errors']['published_at']));
    $before = emails('news_post');
    $res = $v1->post('vendor/news/save', $c1 + ['title' => 'Ujian B13 terbit', 'body' => 'Isi berita ujian.', 'published_at' => date('Y-m-d\TH:i', time() - 60),
        'is_active' => true, 'send_email' => true, 'image_url' => pngDataUrl()]);
    $postId = ok($res) ? $res['data']['post']['id'] : 0;
    $post = Database::fetchOne("SELECT * FROM news_posts WHERE id = ?", [$postId]);
    $uploaded[] = $post['image_path'] ?? '';
    check('Berita diterbit + send_email → emails_queued 1, e-mel news_post kepada siti@contoh.my',
        ok($res) && $res['data']['emails_queued'] === 1 && $res['data']['post']['state'] === 'published' && emails('news_post', 'siti@contoh.my') === $before + 1
        && $post['email_sent_at'] !== null && (int) $post['email_recipients'] === 1);
    check('Gambar data URL → fail uploads/1/1/news/ (image_url dipulangkan)', $post['image_path'] && strpos($post['image_path'], '1/1/news/') === 0
        && is_file(APP_ROOT . '/uploads/' . $post['image_path']) && strpos((string) $res['data']['post']['image_url'], 'uploads/1/1/news/') !== false);
    $email = Database::fetchOne("SELECT * FROM email_queue WHERE template_key = 'news_post' ORDER BY id DESC LIMIT 1");
    check('Kandungan e-mel: tajuk + sekolah dalam subjek, pautan halaman berita store', strpos($email['subject'], 'Ujian B13 terbit') !== false
        && strpos($email['subject'], 'SK Taman Melati') !== false && strpos($email['body_html'], 'page=news') !== false);
    $res = $v1->post('vendor/news/save', $c1 + ['id' => $postId, 'title' => 'Ujian B13 terbit (sunting)', 'body' => 'Isi baharu.',
        'published_at' => date('Y-m-d\TH:i', time() - 60), 'is_active' => true, 'send_email' => false, 'image_url' => $res['data']['post']['image_url']]);
    check('Sunting → tiada e-mel kali kedua; send_email kekal true; gambar dikekalkan', ok($res) && $res['data']['emails_queued'] === 0
        && emails('news_post') === $before + 1 && $res['data']['post']['send_email'] === true
        && Database::fetchValue("SELECT image_path FROM news_posts WHERE id = ?", [$postId]) === $post['image_path']);
    $res = $v1->post('vendor/news/save', $c1 + ['title' => 'Ujian B13 berjadual', 'body' => 'Akan datang.', 'published_at' => date('Y-m-d\TH:i', time() + 86400),
        'is_active' => true, 'send_email' => true]);
    $scheduledId = ok($res) ? $res['data']['post']['id'] : 0;
    check('Berita berjadual → state scheduled, tiada e-mel lagi', ok($res) && $res['data']['post']['state'] === 'scheduled' && $res['data']['emails_queued'] === 0);
    $res = $v1->post('vendor/news/save', $c1 + ['title' => 'Ujian B13 draf', 'body' => 'Draf.', 'published_at' => date('Y-m-d\TH:i'), 'is_active' => false]);
    $draftId = ok($res) ? $res['data']['post']['id'] : 0;
    $list = $v1->get('vendor/news/list', $c1 + ['state' => 'scheduled', 'per_page' => 50]);
    check('news/list tapis state=scheduled', ok($list) && in_array($scheduledId, array_column($list['data']['items'], 'id'), true)
        && !in_array($postId, array_column($list['data']['items'], 'id'), true));
    $storeNews = $parentA->get('store/news/list', ['slug' => 'sk-taman-melati', 'per_page' => 50]);
    $storeIds = ok($storeNews) ? array_column($storeNews['data']['items'], 'id') : [];
    check('Store: berita terbit kelihatan; berjadual & draf tidak', in_array($postId, $storeIds, true) && !in_array($scheduledId, $storeIds, true)
        && !in_array($draftId, $storeIds, true));
    Database::execute("UPDATE news_posts SET published_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE id = ?", [$scheduledId]);
    $queued = (new QueueDueNewsEmailsJob())->run();
    $again = (new QueueDueNewsEmailsJob())->run();
    check('Cron: berita berjadual yang kini terbit → e-mel sekali (kali kedua 0)', $queued === 1 && $again === 0 && emails('news_post') === $before + 2);
    $res = $v1->post('vendor/news/delete', $c1 + ['id' => $postId]);
    check('Padam berita → baris & fail gambar dibuang', ok($res) && !Database::fetchValue("SELECT 1 FROM news_posts WHERE id = ?", [$postId])
        && !is_file(APP_ROOT . '/uploads/' . $post['image_path']));
    check('Log aktiviti news_save / news_delete', activity(1, 'news_save') >= 4 && activity(1, 'news_delete') === 1);
    subscribe(3, 2, 'news');
    check('V02 (modul news dilanggan sementara) sunting / padam berita V01 → 404', $v2->postStatus('vendor/news/delete', $c3 + ['id' => $draftId]) === 404
        && $v2->postStatus('vendor/news/save', $c3 + ['id' => $draftId, 'title' => 'Ambil alih', 'body' => 'x', 'published_at' => '2026-01-01T08:00']) === 404
        && Database::fetchValue("SELECT title FROM news_posts WHERE id = ?", [$draftId]) === 'Ujian B13 draf'
        && ok($v2->get('vendor/news/list', $c3)) && !in_array($draftId, array_column($v2->get('vendor/news/list', $c3)['data']['items'], 'id'), true));

    echo "3. Jualan murah (clearance) — VENDOR02 @ SMK Seri Indah\n";
    $res = $v2->post('vendor/clearance/save', $c3 + ['name' => 'Ujian B13', 'starts_on' => '2026-02-10', 'ends_on' => '2026-02-01']);
    check('Pengesahan: tamat sebelum mula', !ok($res) && $res['errors']['ends_on'] === t('addon.end_before_start'));
    $res = $v2->post('vendor/clearance/save', $c3 + ['name' => 'Ujian B13', 'starts_on' => date('Y-m-d', time() - 86400),
        'ends_on' => date('Y-m-d', time() + 86400 * 7), 'is_active' => true]);
    $saleId = ok($res) ? $res['data']['id'] : 0;
    check('Jualan dicipta → state active, starts_at 00:00:00 / ends_at 23:59:59', ok($res) && $res['data']['state'] === 'active'
        && substr(Database::fetchValue("SELECT starts_at FROM clearance_sales WHERE id = ?", [$saleId]), 11) === '00:00:00'
        && substr(Database::fetchValue("SELECT ends_at FROM clearance_sales WHERE id = ?", [$saleId]), 11) === '23:59:59');
    $res = $v2->post('vendor/clearance/item-save', $c3 + ['sale_id' => $saleId, 'product_id' => 11, 'sale_price' => 4990]);
    check('Harga jualan ≥ harga biasa → ditolak', !ok($res) && $res['errors']['sale_price'] === t('clearance.price_not_lower', ['amount' => 'RM 49.90']));
    $res = $v2->post('vendor/clearance/item-save', $c3 + ['sale_id' => $saleId, 'product_id' => 1, 'sale_price' => 50]);
    check('Produk koperasi lain (V01) → ditolak', !ok($res) && isset($res['errors']['product_id']));
    $variantOther = (int) Database::fetchValue("SELECT id FROM product_variants WHERE product_id = 4 LIMIT 1");
    $res = $v2->post('vendor/clearance/item-save', $c3 + ['sale_id' => $saleId, 'product_id' => 10, 'variant_id' => $variantOther, 'sale_price' => 100]);
    check('Varian produk lain → ditolak', !ok($res) && isset($res['errors']['variant_id']));
    $res = $v2->post('vendor/clearance/item-save', $c3 + ['sale_id' => $saleId, 'product_id' => 11, 'sale_price' => 3990, 'qty_limit' => 2]);
    $itemId = ok($res) ? $res['data']['id'] : 0;
    check('Item disimpan → diskaun 20%, had 2, baki stok', ok($res) && $res['data']['discount_percent'] === 20 && $res['data']['qty_limit'] === 2
        && $res['data']['regular_price'] === 4990 && $res['data']['available'] !== null);
    $res = $v2->post('vendor/clearance/item-save', $c3 + ['sale_id' => $saleId, 'product_id' => 11, 'sale_price' => 3000]);
    check('Produk + varian sama dalam jualan → clearance.duplicate_item', !ok($res) && $res['errors']['product_id'] === t('clearance.duplicate_item'));
    $res = $v2->post('vendor/clearance/item-save', $c3 + ['sale_id' => $saleId, 'product_id' => 10, 'variant_id' => null, 'sale_price' => 3500]);
    check('Semua saiz (variant_id null) → available null', ok($res) && $res['data']['variant_id'] === null && $res['data']['available'] === null);
    $get = $v2->get('vendor/clearance/get', $c3 + ['id' => $saleId]);
    check('clearance/get → 2 item', ok($get) && count($get['data']['items']) === 2 && $get['data']['item_count'] === 2);
    $storeSale = $parentB->get('store/clearance/list', ['slug' => 'smk-seri-indah']);
    $storeItems = [];
    foreach (ok($storeSale) ? $storeSale['data'] : [] as $sale) {
        foreach ($sale['items'] as $item) {
            $storeItems[$sale['id'] . '#' . $item['product_id']] = $item;
        }
    }
    check('Store: harga jualan dari pelayan (RM 39.90) dalam halaman jualan murah', isset($storeItems[$saleId . '#11'])
        && $storeItems[$saleId . '#11']['sale_price'] === 3990);
    $product = $parentB->get('store/product', ['slug' => 'smk-seri-indah', 'id' => 11]);
    check('Store: harga produk = harga jualan + had kuantiti', ok($product) && $product['data']['price'] === 3990);
    subscribe(1, 1, 'clearance');
    check('V01 (clearance dilanggan sementara) baca / sunting / padam jualan V02 → 404', $v1->status('vendor/clearance/get', $c1 + ['id' => $saleId]) === 404
        && $v1->postStatus('vendor/clearance/item-delete', $c1 + ['sale_id' => $saleId, 'id' => $itemId]) === 404
        && $v1->postStatus('vendor/clearance/delete', $c1 + ['id' => $saleId]) === 404
        && $v1->postStatus('vendor/clearance/item-save', $c1 + ['sale_id' => $saleId, 'product_id' => 1, 'sale_price' => 10]) === 404);
    $res = $v2->post('vendor/clearance/item-delete', $c3 + ['sale_id' => $saleId, 'id' => $itemId]);
    check('Buang item', ok($res) && !Database::fetchValue("SELECT 1 FROM clearance_items WHERE id = ?", [$itemId]));
    $res = $v2->post('vendor/clearance/delete', $c3 + ['id' => $saleId]);
    check('Padam jualan → item dibuang juga', ok($res) && !Database::fetchValue("SELECT 1 FROM clearance_items WHERE clearance_sale_id = ?", [$saleId]));
    $list = $v2->get('vendor/clearance/list', $c3 + ['state' => 'active']);
    check('clearance/list tapis active (jualan seed masih ada)', ok($list) && count(array_filter($list['data']['items'], function ($row) {
        return $row['state'] !== 'active';
    })) === 0);
    check('Log aktiviti clearance_save / clearance_delete (V02)', activity(2, 'clearance_save') >= 3 && activity(2, 'clearance_delete') === 1);

    echo "4. WhatsApp/SMS — tetapan, ujian, cron hantar\n";
    $res = $v1->get('vendor/messaging/get', $c1);
    check('Tetapan lalai: dimatikan, tiada kunci, templat lalai, pemegang tempat', ok($res) && $res['data']['is_enabled'] === false
        && $res['data']['api_key_set'] === false && $res['data']['templates']['order_ready'] === t('messaging.default.order_ready')
        && in_array('short_code', $res['data']['placeholders'], true));
    $templates = ['order_ready' => 'Salam {parent_name}, {short_code} untuk {child_name} sedia di {school_name}.',
        'order_collected' => '{short_code} diambil oleh {picked_by} pada {time}.', 'cash_reminder' => 'Pesanan {short_code} luput {expires_on}.'];
    $settings = ['provider' => 'twilio', 'channel' => 'sms', 'sender' => '60123456789', 'api_key' => '', 'is_enabled' => true,
        'events' => ['order_ready' => true, 'order_collected' => true, 'cash_reminder' => true], 'templates' => $templates];
    $res = $v1->post('vendor/messaging/save', $c1 + $settings);
    check('Dihidupkan tanpa kunci API → api_key wajib', !ok($res) && $res['errors']['api_key'] === t('error.required'));
    $res = $v1->post('vendor/messaging/save', $c1 + ['api_key' => 'tiada-titik-dua', 'sender' => '0123'] + $settings);
    check('Kunci bukan ID:RAHSIA & no. penghantar bukan 60… → ditolak', !ok($res) && $res['errors']['api_key'] === t('messaging.key_format')
        && $res['errors']['sender'] === t('messaging.sender_format'));
    $bad = $settings;
    $bad['templates']['order_ready'] = 'Hai {nama_tak_wujud}';
    $bad['provider'] = 'isms';
    $bad['channel'] = 'whatsapp';
    $res = $v1->post('vendor/messaging/save', $c1 + ['api_key' => 'AC1:secret'] + $bad);
    check('Pemegang tempat tidak dikenali & iSMS + WhatsApp → ditolak', !ok($res)
        && $res['errors']['templates.order_ready'] === t('messaging.unknown_placeholder', ['tags' => '{nama_tak_wujud}'])
        && $res['errors']['channel'] === t('messaging.isms_sms_only'));
    check('Mesej ujian sebelum dihidupkan → messaging.enable_first', $v1->post('vendor/messaging/test', $c1 + ['phone' => '0123456789'])['error'] === t('messaging.enable_first'));
    $res = $v1->post('vendor/messaging/save', $c1 + ['api_key' => 'ACB13:secret'] + $settings);
    $row = Database::fetchOne("SELECT * FROM messaging_settings WHERE cooperative_id = 1");
    check('Simpan → api_key_set, kunci disulitkan (bukan teks biasa) & tidak dipulangkan', ok($res) && $res['data']['api_key_set'] === true
        && !isset($res['data']['api_key']) && strpos(json_encode($res), 'secret') === false && strpos((string) $row['api_key_enc'], 'secret') === false
        && Crypto::decrypt($row['api_key_enc']) === 'ACB13:secret');
    $res = $v1->post('vendor/messaging/save', $c1 + $settings);
    check('Simpan semula dengan kunci kosong → kunci lama kekal', ok($res) && Crypto::decrypt(Database::fetchValue("SELECT api_key_enc FROM messaging_settings WHERE cooperative_id = 1")) === 'ACB13:secret');
    check('Log aktiviti messaging_save', activity(1, 'messaging_save') === 2);
    check('Mesej ujian: no. telefon tidak sah → ditolak', !ok($res = $v1->post('vendor/messaging/test', $c1 + ['phone' => '12345'])) && isset($res['errors']['phone']));
    $res = $v1->post('vendor/messaging/test', $c1 + ['phone' => '012-345 6789']);
    $test = Database::fetchOne("SELECT * FROM notification_queue WHERE id = ?", [ok($res) ? $res['data']['id'] : 0]);
    check('Mesej ujian → baris gilir pending, nombor 60123456789, saluran sms', $test && $test['status'] === 'pending' && $test['recipient'] === '60123456789'
        && $test['channel'] === 'sms' && $test['event'] === 'test');
    $sent = (new SendQueuedMessagesJob())->run();
    $requests = fakeRequests($fakeBase);
    $last = end($requests);
    check('Cron hantar → Twilio palsu menerima To +60123456789, From +60123456789, teks ujian', $sent['sent'] >= 1 && $last['provider'] === 'twilio'
        && $last['id'] === 'ACB13' && $last['to'] === '+60123456789' && $last['from'] === '+60123456789'
        && $last['text'] === t('messaging.test_message', ['school' => 'SK Taman Melati'])
        && Database::fetchValue("SELECT status FROM notification_queue WHERE id = ?", [(int) $test['id']]) === 'sent');
    $log = $v1->get('vendor/messaging/log', $c1 + ['status' => 'sent']);
    check('messaging/log → nombor bertopeng 012-***6789, status sent', ok($log) && $log['data']['items'][0]['recipient'] === '012-***6789'
        && $log['data']['items'][0]['status'] === 'sent');

    echo "5. WhatsApp/SMS — peristiwa pesanan & peringatan tunai\n";
    $orderId = placeCash($parentA, 'sk-taman-melati', 1, 1, 1);
    check('Pesanan tunai ibu bapa A dibuat', $orderId > 0);
    Database::execute("UPDATE customers SET phone = '012-3456701' WHERE id = 1");
    $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
    Database::execute("UPDATE orders SET expires_at = DATE_ADD(NOW(), INTERVAL 2 HOUR) WHERE id = ?", [$orderId]);
    $reminders = (new QueueCashRemindersJob())->run();
    $again = (new QueueCashRemindersJob())->run();
    $reminder = notifications('cash_reminder');
    check('Peringatan tunai (luput < 24 jam) → sekali sahaja, order_id direkod', $reminders === 1 && $again === 0 && count($reminder) === 1
        && (int) $reminder[0]['order_id'] === $orderId && strpos($reminder[0]['message'], $order['short_code']) !== false);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId, 'status' => 'preparing']);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId, 'status' => 'ready']);
    $ready = notifications('order_ready');
    check('Sedia diambil → mesej order_ready ke telefon ibu bapa (templat vendor + pemegang tempat)', count($ready) === 1
        && $ready[0]['recipient'] === '60123456701' && (int) $ready[0]['order_id'] === $orderId
        && $ready[0]['message'] === 'Salam Siti Nurhaliza binti Ahmad, ' . $order['short_code'] . ' untuk Aisyah binti Faizal sedia di SK Taman Melati.');
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId, 'status' => 'preparing']);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId, 'status' => 'ready']);
    check('ready → preparing → ready tidak menghantar semula', count(notifications('order_ready')) === 1);
    $cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $orderId, 'amount_received' => (int) $order['total']]);
    $item = Database::fetchOne("SELECT id FROM order_items WHERE order_id = ?", [$orderId]);
    $res = $cashier1->post('vendor/handover/collect', $c1 + ['id' => $orderId, 'picked_by_type' => 'student', 'picked_by_name' => 'Aisyah binti Faizal',
        'method' => 'B', 'items' => [['item_id' => (int) $item['id'], 'qty' => 1]]]);
    $collected = notifications('order_collected');
    check('Serah barang → mesej order_collected (diambil oleh …)', ok($res) && count($collected) === 1
        && strpos($collected[0]['message'], 'diambil oleh Aisyah binti Faizal') !== false);
    $settings['events']['order_collected'] = false;
    $v1->post('vendor/messaging/save', $c1 + $settings);
    MessageNotifications::orderCollected(['id' => 1, 'vendor_id' => 1, 'school_name' => 'SK Taman Melati'], $order, 'X', ['Y']);
    check('Peristiwa tidak ditanda → tiada mesej', count(notifications('order_collected')) === 1);
    $sent = (new SendQueuedMessagesJob())->run();
    check('Cron hantar 3 mesej pesanan', $sent['sent'] === 3 && count(array_filter(notifications(), function ($row) {
        return $row['status'] !== 'sent';
    })) === 0);
    Database::execute("UPDATE messaging_settings SET api_key_enc = ? WHERE cooperative_id = 1", [Crypto::encrypt('ACB13:salah')]);
    $v1->post('vendor/messaging/test', $c1 + ['phone' => '0123456789']);
    $runs = [(new SendQueuedMessagesJob())->run(), (new SendQueuedMessagesJob())->run(), (new SendQueuedMessagesJob())->run()];
    $failedRow = Database::fetchOne("SELECT * FROM notification_queue WHERE event = 'test' ORDER BY id DESC LIMIT 1");
    check('Kunci salah → cuba semula, selepas 3 cubaan failed + last_error', $runs[0]['retry'] === 1 && $runs[2]['failed'] === 1
        && $failedRow['status'] === 'failed' && (int) $failedRow['attempts'] === 3 && strpos((string) $failedRow['last_error'], 'twilio') === 0);
    Database::execute("UPDATE messaging_settings SET is_enabled = 0 WHERE cooperative_id = 1");
    MessageNotifications::orderReady(['id' => 1, 'vendor_id' => 1, 'school_name' => 'SK Taman Melati'], $order);
    check('Dimatikan → peristiwa tidak masuk baris gilir', count(notifications('order_ready')) === 1);
    check('V02 → log / tetapan / ujian koperasi V01 → 404', $v2->status('vendor/messaging/log', $c1) === 404 && $v2->status('vendor/messaging/get', $c1) === 404
        && $v2->postStatus('vendor/messaging/test', $c1 + ['phone' => '0123456789']) === 404);
    check('V02 log koperasi sendiri (modul tidak dilanggan) → module_locked, tiada mesej V01', locked($v2->get('vendor/messaging/log', $c3)));

    echo "6. Kalendar cuti — tutup tempahan\n";
    $res = $v1->post('vendor/calendar/save', $c1 + ['title' => 'Ujian', 'event_type' => 'party', 'starts_on' => '2026-11-02', 'ends_on' => '2026-11-01']);
    check('Pengesahan: jenis tidak sah, tamat sebelum mula', !ok($res) && isset($res['errors']['event_type']));
    $res = $v1->post('vendor/calendar/save', $c1 + ['title' => 'Ujian', 'event_type' => 'other', 'starts_on' => '2026-11-02', 'ends_on' => '2026-11-01']);
    check('Pengesahan: tamat sebelum mula → addon.end_before_start', !ok($res) && $res['errors']['ends_on'] === t('addon.end_before_start'));
    $today = date('Y-m-d');
    $res = $v1->post('vendor/calendar/save', $c1 + ['title' => 'Koperasi tutup B13', 'event_type' => 'coop_closed', 'starts_on' => $today,
        'ends_on' => $today, 'blocks_orders' => true]);
    $eventId = ok($res) ? $res['data']['id'] : 0;
    $list = $v1->get('vendor/calendar/list', $c1 + ['from' => $today, 'to' => $today]);
    check('calendar/list → acara hari ini + today_blocked', ok($list) && in_array($eventId, array_column($list['data']['items'], 'id'), true)
        && $list['data']['today'] === $today && $list['data']['today_blocked'] !== null);
    $school = $parentA->get('store/school', ['slug' => 'sk-taman-melati']);
    check('Store: banner tutup hari ini', ok($school) && $school['data']['notices']['closure']['is_today'] === true);
    check('Store: checkout ditolak pada hari tutup', placeCash($parentA, 'sk-taman-melati', 1, 1, 1) === 0);
    check('V02 sunting / padam acara V01 → 404 (koperasi) / tidak dijumpai', $v2->postStatus('vendor/calendar/delete', $c1 + ['id' => $eventId]) === 404
        && Database::fetchValue("SELECT 1 FROM calendar_events WHERE id = ?", [$eventId]));
    $res = $v1->post('vendor/calendar/delete', $c1 + ['id' => $eventId]);
    check('Padam acara → checkout dibuka semula', ok($res) && placeCash($parentA, 'sk-taman-melati', 1, 1, 1) > 0);
    check('Log aktiviti calendar_save / calendar_delete', activity(1, 'calendar_save') === 1 && activity(1, 'calendar_delete') === 1);

    echo "7. Pembukaan sekolah — musim & slot\n";
    $year = (int) date('Y') + 3;
    $res = $v1->post('vendor/school-opening/season-save', $c1 + ['school_year' => $year, 'opens_at' => date('Y-m-d', time() - 86400),
        'closes_at' => date('Y-m-d', time() + 86400 * 10), 'pickup_starts_on' => date('Y-m-d', time() + 86400 * 5)]);
    check('Ambil bermula sebelum tarikh tutup → opening.pickup_after_close', !ok($res) && $res['errors']['pickup_starts_on'] === t('opening.pickup_after_close'));
    $pickup = date('Y-m-d', time() + 86400 * 12);
    $seasonInput = ['school_year' => $year, 'opens_at' => date('Y-m-d', time() - 86400), 'closes_at' => date('Y-m-d', time() + 86400 * 10),
        'pickup_starts_on' => $pickup, 'note' => 'Musim ujian B13'];
    $res = $v1->post('vendor/school-opening/season-save', $c1 + $seasonInput);
    $seasonId = ok($res) ? $res['data']['id'] : 0;
    check('Musim dicipta → state open', ok($res) && $res['data']['state'] === 'open' && $res['data']['closes_at'] === $seasonInput['closes_at']);
    $res = $v1->post('vendor/school-opening/season-save', $c1 + $seasonInput);
    check('Tahun sama → opening.duplicate_year', !ok($res) && $res['errors']['school_year'] === t('opening.duplicate_year'));
    $seasons = $v1->get('vendor/school-opening/seasons', $c1);
    check('seasons → musim + darjah SK (PRA–D6)', ok($seasons) && in_array($seasonId, array_column($seasons['data']['items'], 'id'), true)
        && $seasons['data']['grades'][0] === 'PRA' && !in_array('T1', $seasons['data']['grades'], true));
    $slotInput = ['season_id' => $seasonId, 'slot_date' => $pickup, 'time_start' => '08:00', 'time_end' => '10:00', 'capacity' => 2];
    $res = $v1->post('vendor/school-opening/slot-save', $c1 + ['slot_date' => date('Y-m-d'), 'time_end' => '07:00', 'capacity' => 1001] + $slotInput);
    check('Pengesahan slot: sebelum tarikh ambil, masa terbalik, kapasiti > 1000', !ok($res) && isset($res['errors']['slot_date'], $res['errors']['capacity'])
        && $res['errors']['time_end'] === t('opening.time_order'));
    $res = $v1->post('vendor/school-opening/slot-save', $c1 + $slotInput + ['grades' => ['D4', 'D1', 'T1']]);
    check('Tambah slot untuk D4 & D1 (T1 bukan darjah SK diabaikan) → 2 slot', ok($res) && $res['data']['created'] === 2
        && array_column($res['data']['items'], 'grade_level_code') === ['D1', 'D4']);
    $slotD4 = 0;
    foreach (ok($res) ? $res['data']['items'] : [] as $slot) {
        $slotD4 = $slot['grade_level_code'] === 'D4' ? $slot['id'] : $slotD4;
    }
    $school = $parentA->get('store/school', ['slug' => 'sk-taman-melati']);
    check('Store: banner pre-order (musim dibuka, ada slot)', ok($school) && $school['data']['notices']['preorder']['has_slots'] === true
        && $school['data']['notices']['preorder']['school_year'] === $year);
    $orderSlot = placeCash($parentA, 'sk-taman-melati', 1, 1, 1, ['pickup_slot_id' => $slotD4]);
    check('Checkout memilih slot D4 → booked 1', $orderSlot > 0 && (int) Database::fetchValue("SELECT booked FROM pickup_slots WHERE id = ?", [$slotD4]) === 1);
    $res = $v1->post('vendor/school-opening/slot-save', $c1 + ['id' => $slotD4, 'capacity' => 0] + $slotInput);
    check('Sunting kapasiti 0 → ditolak (julat)', !ok($res));
    Database::execute("UPDATE pickup_slots SET booked = 2 WHERE id = ?", [$slotD4]);
    $res = $v1->post('vendor/school-opening/slot-save', $c1 + ['id' => $slotD4, 'capacity' => 1, 'grades' => ['D4']] + $slotInput);
    check('Kapasiti < booked (dikunci) → opening.capacity_below_booked', !ok($res)
        && $res['errors']['capacity'] === t('opening.capacity_below_booked', ['n' => 2]));
    $res = $v1->post('vendor/school-opening/slot-save', $c1 + ['id' => $slotD4, 'capacity' => 5, 'time_start' => '09:00', 'grades' => ['D4']] + $slotInput);
    check('Sunting slot (kapasiti 5, masa) → created 0, booked tidak diubah', ok($res) && $res['data']['created'] === 0
        && $res['data']['items'][0]['capacity'] === 5 && $res['data']['items'][0]['booked'] === 2 && $res['data']['items'][0]['time_start'] === '09:00');
    check('Padam slot bertempah → opening.slot_has_bookings', $v1->post('vendor/school-opening/slot-delete', $c1 + ['id' => $slotD4])['error']
        === t('opening.slot_has_bookings', ['n' => 2]));
    check('Padam musim yang ada tempahan → opening.season_has_bookings', $v1->post('vendor/school-opening/season-delete', $c1 + ['id' => $seasonId])['error']
        === t('opening.season_has_bookings'));
    Database::execute("UPDATE pickup_slots SET booked = 0 WHERE id = ?", [$slotD4]);
    check('Slot pernah dipilih dalam pesanan (booked 0) → opening.slot_in_orders', $v1->post('vendor/school-opening/slot-delete', $c1 + ['id' => $slotD4])['error']
        === t('opening.slot_in_orders'));
    $slots = $v1->get('vendor/school-opening/slots', $c1 + ['season_id' => $seasonId]);
    check('slots → musim + 2 slot (remaining)', ok($slots) && count($slots['data']['items']) === 2 && $slots['data']['season']['slot_count'] === 2);
    check('V02 → musim / slot V01 → 404', $v2->status('vendor/school-opening/slots', $c1 + ['season_id' => $seasonId]) === 404
        && $v2->postStatus('vendor/school-opening/slot-delete', $c1 + ['id' => $slotD4]) === 404);
    subscribe(3, 2, 'school_opening');
    check('V02 (modul dilanggan sementara) baca / padam musim & slot V01 dengan koperasi sendiri → 404',
        $v2->status('vendor/school-opening/slots', $c3 + ['season_id' => $seasonId]) === 404
        && $v2->postStatus('vendor/school-opening/slot-delete', $c3 + ['id' => $slotD4]) === 404
        && $v2->postStatus('vendor/school-opening/season-delete', $c3 + ['id' => $seasonId]) === 404
        && $v2->postStatus('vendor/school-opening/slot-save', $c3 + ['id' => $slotD4] + $slotInput) === 404
        && (int) Database::fetchValue("SELECT capacity FROM pickup_slots WHERE id = ?", [$slotD4]) === 5);
    $otherSlot = 0;
    foreach ($slots['data']['items'] as $slot) {
        $otherSlot = $slot['id'] !== $slotD4 ? $slot['id'] : $otherSlot;
    }
    check('Padam slot tanpa tempahan', ok($v1->post('vendor/school-opening/slot-delete', $c1 + ['id' => $otherSlot])));
    Database::execute("UPDATE orders SET pickup_slot_id = NULL WHERE id = ?", [$orderSlot]);
    $res = $v1->post('vendor/school-opening/season-delete', $c1 + ['id' => $seasonId]);
    check('Padam musim (tiada tempahan) → slot dibuang juga', ok($res) && !Database::fetchValue("SELECT 1 FROM pickup_slots WHERE preorder_season_id = ?", [$seasonId]));
    check('Log aktiviti season_save / season_delete', activity(1, 'season_save') >= 3 && activity(1, 'season_delete') === 1);

    echo "8. Mock dibuang & laluan sebenar\n";
    $api = file_get_contents(APP_ROOT . '/assets/js/api.js');
    check("Api.livePrefixes: vendor/news, clearance, calendar, school-opening, messaging",
        strpos($api, "'vendor/news/', 'vendor/clearance/', 'vendor/calendar/', 'vendor/school-opening/', 'vendor/messaging/'") !== false);
    $mock = '';
    foreach (glob(APP_ROOT . '/assets/js/mock/*.js') as $file) {
        $mock .= file_get_contents($file);
    }
    $footer = file_get_contents(APP_ROOT . '/vendor/_layout/footer.php') . file_get_contents(APP_ROOT . '/vendor/_layout/pos-footer.php');
    check('Mock modul dibuang (vendor-addons.js, vendor.js, MockVendor.activeModules, mockToday) & layout vendor tidak memuatnya',
        !is_file(APP_ROOT . '/assets/js/mock/vendor-addons.js') && !is_file(APP_ROOT . '/assets/js/mock/vendor.js')
        && strpos($mock, 'activeModules') === false && strpos($mock, 'mockToday') === false && strpos($footer, 'vendor-addons.js') === false
        && strpos($footer, 'vendor.js') === false);
    $page = $v1->page('vendor/addons/news.php');
    check('Halaman modul vendor 200 tanpa skrip mock vendor', $page['status'] === 200 && strpos($page['body'], 'js/mock/vendor') === false);
    $missing = [];
    foreach (['news/list', 'news/recipients', 'news/save', 'news/delete', 'clearance/list', 'clearance/get', 'clearance/save', 'clearance/delete',
        'clearance/item-save', 'clearance/item-delete', 'calendar/list', 'calendar/save', 'calendar/delete', 'school-opening/seasons',
        'school-opening/season-save', 'school-opening/season-delete', 'school-opening/slots', 'school-opening/slot-save', 'school-opening/slot-delete',
        'messaging/get', 'messaging/save', 'messaging/test', 'messaging/log'] as $route) {
        $source = (string) @file_get_contents(APP_ROOT . '/api/v1/vendor/' . $route . '.php');
        if (strpos($source, 'ModuleAccess::require(') === false || preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b/', $source)) {
            $missing[] = $route;
        }
    }
    check('23 endpoint modul wujud, memanggil ModuleAccess::require, tiada SQL dalam api/', $missing === []);

    echo "9. Ibu bapa A ↔ B\n";
    check('Ibu bapa → laluan modul vendor 401', $parentA->status('vendor/news/list', $c1) === 401 && $parentB->postStatus('vendor/calendar/save', $c3) === 401
        && $parentA->status('vendor/messaging/log', $c1) === 401);
    check('Ibu bapa B tidak nampak berita SK Taman Melati melalui sekolah sendiri', ok($res = $parentB->get('store/news/list', ['slug' => 'smk-seri-indah']))
        && count(array_filter($res['data']['items'], function ($row) use ($scheduledId) {
            return $row['id'] === $scheduledId;
        })) === 0);
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
    Database::execute("UPDATE customers SET phone = '012-3456701' WHERE id = 1");
    foreach (array_filter($uploaded) as $path) {
        Uploader::delete($path);
    }
    @unlink($stateFile);
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
