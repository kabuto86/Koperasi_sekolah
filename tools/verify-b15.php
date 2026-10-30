<?php
// Semakan B15 — API token apps Flutter & push: log masuk token (hash SHA-256, luput, had 10 token), Bearer pada endpoint store/
// (tiada sesi / cookie / CSRF, token salah tidak jatuh balik ke sesi), hanya koperasi dengan modul mobile_app, rate limit token,
// senarai & batal peranti, tukar / reset kata laluan membatal token, daftar peranti push, notifikasi push (sedia diambil,
// telah diambil, berita — sekali sahaja) → notification_queue → SendPushJob melalui FCM palsu (JWT RS256), PDPA padam akaun,
// ujian kebocoran ibu bapa A ↔ B dan VENDOR01 ↔ VENDOR02. CLI sahaja:
//   php tools/verify-b15.php
// Perlu: DB koperasi_saas (schema + seed + migrasi 008), config/database.php, config/secret.key, sambungan PHP curl & openssl.
// Skrip memulakan pelayan aplikasi (php -S, 4 pekerja) + FCM palsu (tools/fake-messaging.php). Jadual yang diubah dipulihkan.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

function fakeRequests($fakeBase, $provider)
{
    return array_values(array_filter((array) json_decode((string) file_get_contents($fakeBase . '/_admin/requests'), true),
        function ($row) use ($provider) {
            return $row['provider'] === $provider;
        }));
}

function pushRows($event = null)
{
    $sql = "SELECT * FROM notification_queue WHERE channel = 'push'";
    $args = [];
    if ($event !== null) {
        $sql .= " AND event = ?";
        $args[] = $event;
    }
    return Database::fetchAll($sql . " ORDER BY id", $args);
}

// Browser apps: token Bearer, tiada CSRF
function appBrowser($base, $token)
{
    $app = new Browser($base);
    $app->bearer = $token;
    return $app;
}

function issueToken(Browser $browser, $login, $device = 'Ujian B15')
{
    return $browser->post('store/auth/token', ['login' => $login, 'password' => 'Demo@12345', 'device_name' => $device], false);
}

function setCookieNames(array $res)
{
    return array_map(function ($line) {
        return explode('=', $line, 2)[0];
    }, $res['headers']['set-cookie'] ?? []);
}

$port = 8150;
$fakePort = 8151;
$base = 'http://127.0.0.1:' . $port;
$fakeBase = 'http://127.0.0.1:' . $fakePort;
$log = sys_get_temp_dir() . '/kop-verify-b15.log';
$stateFile = sys_get_temp_dir() . '/kop-verify-b15-fcm.json';
$accountFile = sys_get_temp_dir() . '/kop-verify-b15-account.json';
$publicKeyFile = sys_get_temp_dir() . '/kop-verify-b15-public.pem';
@unlink($stateFile);

// Akaun perkhidmatan Firebase palsu: kunci RSA baharu; FCM palsu menyemak tandatangan JWT dengan kunci awamnya
$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
openssl_pkey_export($key, $privatePem);
file_put_contents($publicKeyFile, openssl_pkey_get_details($key)['key']);
file_put_contents($accountFile, json_encode(['type' => 'service_account', 'project_id' => 'koperasiku-ujian',
    'client_email' => 'push@koperasiku-ujian.iam.gserviceaccount.com', 'private_key' => $privatePem]));

$fake = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $fakePort, __DIR__ . '/fake-messaging.php'], [1 => ['file', $log . '.fake', 'w'], 2 => ['file', $log . '.fake', 'a']],
    $fakePipes, null, ['FAKE_MESSAGING_STATE' => $stateFile, 'FAKE_FCM_PUBLIC_KEY' => $publicKeyFile] + getenv());
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4'] + getenv());
usleep(900000);
putenv('KOPERASI_MESSAGING_TEST_URL=' . $fakeBase);

$tables = ['api_tokens', 'push_devices', 'notification_queue', 'cooperative_modules', 'news_posts', 'email_queue', 'activity_logs', 'audit_logs',
    'orders', 'order_items', 'order_status_logs', 'order_pickups', 'order_pickup_items', 'stock_levels', 'stock_movements', 'carts', 'cart_items',
    'payments', 'login_attempts', 'rate_limits', 'customers', 'children', 'customer_tokens'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();
Database::execute("DELETE FROM rate_limits");

try {
    // Hanya SK Taman Melati (VENDOR01) melanggan mobile_app
    $mobileModule = (int) Database::fetchValue("SELECT id FROM modules WHERE code = 'mobile_app'");
    Database::execute(
        "INSERT INTO cooperative_modules (vendor_id, cooperative_id, module_id, status, started_at, ends_at) VALUES (1, 1, ?, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))",
        [$mobileModule]
    );
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $cashier1 = vendorLogin($base, 'kasim@niagasekolah.my');
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    list($parentB) = storeLogin($base, 'rajesh@contoh.my');
    $guest = new Browser($base);
    $c1 = ['cooperative_id' => 1];
    $melati = ['slug' => 'sk-taman-melati'];
    $chungHwa = ['slug' => 'sjkc-chung-hwa'];

    echo "1. Statik\n";
    $authCode = file_get_contents(APP_ROOT . '/core/ApiTokenAuth.php');
    check('core/ApiTokenAuth: Bearer 64 hex, AuthenticateApiTokenJob (SHA-256), rate limit token & token salah',
        strpos($authCode, '[a-f0-9]{64}') !== false && strpos($authCode, 'AuthenticateApiTokenJob') !== false
        && strpos($authCode, "'api_token:'") !== false && strpos($authCode, "'api_token_bad:'") !== false);
    check('Tiada SQL dalam api/ untuk laluan baharu (token, devices, push)', count(array_filter(array_merge(
        glob(APP_ROOT . '/api/v1/store/{devices,push}/*.php', GLOB_BRACE), [APP_ROOT . '/api/v1/store/auth/token.php', APP_ROOT . '/api/v1/store/auth/token-revoke.php']
    ), function ($file) {
        return preg_match('/Database::|\b(SELECT|INSERT|UPDATE|DELETE)\b/', file_get_contents($file));
    })) === 0);
    check('.gitignore: fail akaun perkhidmatan Firebase; .htaccess menyalin header Authorization',
        strpos(file_get_contents(APP_ROOT . '/.gitignore'), 'config/firebase-service-account.json') !== false
        && strpos(file_get_contents(APP_ROOT . '/.htaccess'), 'E=HTTP_AUTHORIZATION') !== false);
    $cron = file_get_contents(APP_ROOT . '/cron/send-push.php');
    check('cron/send-push.php: CLI sahaja, flock, SendPushJob', strpos($cron, "PHP_SAPI !== 'cli'") !== false && strpos($cron, 'flock') !== false
        && strpos($cron, 'SendPushJob') !== false);

    echo "2. Log masuk token (store/auth/token)\n";
    $wrong = $guest->post('store/auth/token', ['login' => 'siti@contoh.my', 'password' => 'Salah12345'], false);
    $wrongWeb = (new Browser($base))->post('store/auth/token', ['login' => 'tiada@contoh.my', 'password' => 'Salah12345'], false);
    check('Kata laluan salah → ralat, mesej sama untuk akaun wujud / tidak', !ok($wrong) && !ok($wrongWeb) && $wrong['error'] === $wrongWeb['error']);
    clearTestLocks();
    $res = $guest->raw('POST', 'store/auth/token', ['login' => '012-3456701', 'password' => 'Demo@12345', 'device_name' => "Pixel\n8 Siti"], false);
    $tokenA = $res['json']['data']['token'] ?? '';
    $tokenAId = (int) ($res['json']['data']['token_id'] ?? 0);
    $row = Database::fetchOne("SELECT * FROM api_tokens WHERE id = ?", [$tokenAId]);
    check('Log masuk telefon → token 64 hex + expires_at + profil (tiada id dalaman)', ok($res['json']) && preg_match('/^[a-f0-9]{64}$/', $tokenA)
        && $res['json']['data']['customer']['email'] === 'siti@contoh.my' && !isset($res['json']['data']['customer']['id'])
        && strpos((string) $res['json']['data']['expires_at'], 'T') !== false);
    check('DB menyimpan SHA-256 sahaja (token mentah tiada), luput 90 hari, nama peranti tanpa aksara kawalan', $row !== null
        && $row['token_hash'] === hash('sha256', $tokenA) && Database::fetchValue("SELECT COUNT(*) FROM api_tokens WHERE token_hash = ?", [$tokenA]) == 0
        && abs(strtotime($row['expires_at']) - strtotime('+90 days')) < 120 && $row['device_name'] === 'Pixel 8 Siti');
    check('Tiada cookie sesi (KOPSTORE) dalam jawapan token', !in_array('KOPSTORE', setCookieNames($res), true));
    $long = issueToken($guest, 'siti@contoh.my', str_repeat('x', 101));
    check('Nama peranti > 100 aksara → 422 device_name', !ok($long) && isset($long['errors']['device_name']));
    check('GET ke store/auth/token → 405', $guest->getStatus('store/auth/token') === 405);
    $tokenB = issueToken($guest, 'rajesh@contoh.my', 'iPhone Rajesh')['data']['token'] ?? '';
    check('Ibu bapa B juga menerima token', strlen($tokenB) === 64);

    echo "3. Bearer pada endpoint store/\n";
    $appA = appBrowser($base, $tokenA);
    $appB = appBrowser($base, $tokenB);
    $session = $appA->raw('GET', 'store/session', $melati);
    $schools = array_column($session['json']['data']['schools'] ?? [], 'app_enabled', 'slug');
    check('store/session dengan token → ibu bapa A; sekolah anak dengan app_enabled (Melati ya, Chung Hwa tidak)',
        ($session['json']['data']['customer']['email'] ?? '') === 'siti@contoh.my' && $schools === ['sjkc-chung-hwa' => false, 'sk-taman-melati' => true]);
    check('Tiada cookie sesi dicipta untuk permintaan token', !in_array('KOPSTORE', setCookieNames($session), true));
    check('Token: anak (Melati) 200; sekolah tanpa mobile_app → 403 app_unavailable (log masuk & awam)',
        ok($appA->get('store/orders/list', $melati)) && ($r = $appA->raw('GET', 'store/orders/list', $chungHwa))['status'] === 403
        && !empty($r['json']['app_unavailable']) && $appA->status('store/catalog', $chungHwa) === 403);
    check('Tanpa token (laman web) sekolah sama tetap dibuka', $guest->status('store/catalog', $chungHwa) === 200
        && ok($parentA->get('store/orders/list', $chungHwa)));
    check('store/school: app_enabled (Melati true, Chung Hwa false)', $guest->get('store/school', $melati)['data']['app_enabled'] === true
        && $guest->get('store/school', $chungHwa)['data']['app_enabled'] === false);
    $add = $appA->raw('POST', 'store/cart/add', $melati + ['product_id' => 1, 'qty' => 1, 'child_id' => 1], false);
    check('POST dengan token tanpa X-CSRF-Token → diterima (troli A)', ok($add['json']) && !in_array('KOPSTORE', setCookieNames($add), true));
    $bad = appBrowser($base, str_repeat('a', 64));
    $badRes = $bad->raw('GET', 'store/session', $melati);
    check('Token tidak sah → 401 auth_required + token_invalid', $badRes['status'] === 401 && !empty($badRes['json']['token_invalid']));
    $parentA->bearer = str_repeat('b', 64);
    check('Token salah + cookie sesi sah → 401 (tiada jatuh balik ke sesi)', $parentA->raw('GET', 'store/children/list', $melati)['status'] === 401);
    $parentA->bearer = '';
    check('Format bukan Bearer / token pendek → 401 untuk laluan log masuk', appBrowser($base, 'abc')->raw('GET', 'store/children/list', $melati)['status'] === 401);
    $v1->bearer = $tokenA;
    check('Token apps → panel vendor tetap perlu CSRF sesi vendor (419) & bukan sesi vendor (401 tanpa cookie)',
        $v1->postStatus('vendor/news/save', $c1, false) === 419 && $appA->status('vendor/products/list', $c1) === 401
        && $appA->status('owner/vendors/list') === 401);
    $v1->bearer = '';
    check('Sesi web → laluan khas apps (push/register) 403 token_required; sesi web tanpa CSRF masih 419',
        !empty($parentA->post('store/push/register', ['platform' => 'android', 'push_token' => str_repeat('w', 40)])['token_required'])
        && $parentA->postStatus('store/push/register', ['platform' => 'android'], false) === 419);
    Database::execute("UPDATE api_tokens SET last_used_at = NOW() - INTERVAL 1 DAY, expires_at = NOW() + INTERVAL 1 DAY WHERE id = ?", [$tokenAId]);
    $appA->get('store/session', $melati);
    $row = Database::fetchOne("SELECT * FROM api_tokens WHERE id = ?", [$tokenAId]);
    check('Luput bergerak: digunakan → last_used_at & expires_at (+90 hari) dikemas kini', strtotime($row['last_used_at']) > time() - 60
        && strtotime($row['expires_at']) > strtotime('+89 days'));
    Database::execute("UPDATE api_tokens SET expires_at = NOW() - INTERVAL 1 MINUTE WHERE id = ?", [$tokenAId]);
    $expired = $appA->raw('GET', 'store/session', $melati)['status'];
    Database::execute("UPDATE api_tokens SET expires_at = NOW() + INTERVAL 90 DAY WHERE id = ?", [$tokenAId]);
    check('Token luput → 401', $expired === 401 && ok($appA->get('store/children/list', $melati)));
    Database::execute("UPDATE customers SET is_active = 0 WHERE id = 2");
    $inactive = $appB->raw('GET', 'store/session', $melati)['status'];
    Database::execute("UPDATE customers SET is_active = 1 WHERE id = 2");
    check('Akaun ibu bapa dinyahaktif → token ditolak 401', $inactive === 401);

    echo "4. Rate limit\n";
    clearTestLocks();
    $codes = [];
    for ($i = 0; $i < ApiTokenAuth::RATE_PER_TOKEN + 1; $i++) {
        $codes[] = $appB->raw('GET', 'store/children/list', ['slug' => 'sk-taman-melati'])['status'];
    }
    check('Setiap token: ' . ApiTokenAuth::RATE_PER_TOKEN . ' permintaan / minit, seterusnya 429', count(array_filter($codes, function ($code) {
        return $code === 429;
    })) === 1 && end($codes) === 429 && ok($appA->get('store/children/list', $melati)));
    $codes = [];
    for ($i = 0; $i < ApiTokenAuth::RATE_BAD_PER_IP + 1; $i++) {
        $codes[] = appBrowser($base, bin2hex(random_bytes(32)))->raw('GET', 'store/session', [])['status'];
    }
    check('Token salah: ' . ApiTokenAuth::RATE_BAD_PER_IP . ' / 15 minit setiap IP → 429 (cubaan meneka)', $codes[0] === 401 && end($codes) === 429);
    clearTestLocks();
    $codes = [];
    for ($i = 0; $i < 31; $i++) {
        $codes[] = $guest->raw('POST', 'store/auth/token', ['login' => 'x' . $i . '@contoh.my', 'password' => 'x'], false)['status'];
    }
    check('Log masuk token: 30 / 15 minit setiap IP → 429', end($codes) === 429 && $codes[0] === 422);
    clearTestLocks();
    Database::execute("DELETE FROM rate_limits");

    echo "5. Senarai & batal peranti, had token\n";
    $list = $appA->get('store/devices/list');
    $current = array_values(array_filter($list['data']['items'] ?? [], function ($item) {
        return $item['current'];
    }));
    check('devices/list (token): peranti semasa ditanda, tiada hash', ok($list) && count($current) === 1 && $current[0]['id'] === $tokenAId
        && $current[0]['device_name'] === 'Pixel 8 Siti' && strpos(json_encode($list), 'token_hash') === false);
    $webList = $parentA->get('store/devices/list');
    check('devices/list (sesi web) — sama, tiada yang "current"', ok($webList) && count($webList['data']['items']) === count($list['data']['items'])
        && count(array_filter(array_column($webList['data']['items'], 'current'))) === 0);
    $tokenBId = (int) Database::fetchValue("SELECT id FROM api_tokens WHERE customer_id = 2 AND revoked_at IS NULL ORDER BY id DESC LIMIT 1");
    $idor = $parentA->post('store/devices/revoke', ['id' => $tokenBId]);
    check('IDOR: A membatal token B → 404, token B kekal sah', !ok($idor) && ok($appB->get('store/session', $melati))
        && Database::fetchValue("SELECT revoked_at FROM api_tokens WHERE id = ?", [$tokenBId]) === null);
    check('B tidak nampak peranti A', count(array_filter($appB->get('store/devices/list')['data']['items'], function ($item) use ($tokenAId) {
        return $item['id'] === $tokenAId;
    })) === 0);
    $extra = issueToken($guest, 'siti@contoh.my', 'Tablet lama')['data'];
    check('Batal peranti dari laman web (sesi + CSRF) → token itu 401', ok($parentA->post('store/devices/revoke', ['id' => $extra['token_id']]))
        && appBrowser($base, $extra['token'])->status('store/session') === 401 && $parentA->postStatus('store/devices/revoke', ['id' => 1], false) === 419);
    Database::execute("UPDATE api_tokens SET last_used_at = NOW() - INTERVAL 30 DAY WHERE customer_id = 2");
    for ($i = 0; $i < ApiTokenAuth::MAX_ACTIVE_PER_CUSTOMER; $i++) {
        issueToken($guest, 'rajesh@contoh.my', 'Peranti ' . $i);
    }
    $activeB = (int) Database::fetchValue("SELECT COUNT(*) FROM api_tokens WHERE customer_id = 2 AND revoked_at IS NULL AND expires_at > NOW()");
    check('Had ' . ApiTokenAuth::MAX_ACTIVE_PER_CUSTOMER . ' token aktif: token paling lama digunakan dibatal', $activeB === ApiTokenAuth::MAX_ACTIVE_PER_CUSTOMER
        && $appB->raw('GET', 'store/session', [])['status'] === 401);
    $tokenB = issueToken($guest, 'rajesh@contoh.my', 'iPhone Rajesh')['data']['token'];
    $appB = appBrowser($base, $tokenB);
    clearTestLocks();

    echo "6. Daftar peranti push\n";
    $pushA = 'fcmA_' . str_repeat('k', 60);
    $badPush = $appA->post('store/push/register', ['platform' => 'windows', 'push_token' => 'x']);
    check('Platform / token FCM tidak sah → 422', !ok($badPush) && isset($badPush['errors']['platform']) && isset($badPush['errors']['push_token']));
    $reg = $appA->post('store/push/register', ['platform' => 'android', 'push_token' => 'lama_' . str_repeat('k', 60)]);
    $reg2 = $appA->post('store/push/register', ['platform' => 'android', 'push_token' => $pushA]);
    $devicesA = Database::fetchAll("SELECT * FROM push_devices WHERE customer_id = 1");
    check('Daftar (token apps A) → satu peranti setiap token apps; token FCM baharu menggantikan yang lama', ok($reg) && ok($reg2)
        && count($devicesA) === 1 && $devicesA[0]['push_token'] === $pushA && (int) $devicesA[0]['api_token_id'] === $tokenAId);
    $shared = 'kongsi_' . str_repeat('s', 60);
    $appB->post('store/push/register', ['platform' => 'ios', 'push_token' => $shared]);
    $tokenA2 = issueToken($guest, 'siti@contoh.my', 'iPad Siti')['data']['token'];
    $appA2 = appBrowser($base, $tokenA2);
    $appA2->post('store/push/register', ['platform' => 'ios', 'push_token' => $shared]);
    check('Token FCM yang sama didaftar oleh akaun lain → dipindah (B tiada peranti itu lagi)',
        Database::fetchValue("SELECT customer_id FROM push_devices WHERE push_token = ?", [$shared]) == 1
        && (int) Database::fetchValue("SELECT COUNT(*) FROM push_devices WHERE customer_id = 2") === 0);
    $unreg = $appA2->post('store/push/unregister', []);
    check('push/unregister → peranti token ini dibuang, token apps kekal sah', ok($unreg) && $unreg['data']['removed'] === 1
        && ok($appA2->get('store/session', $melati)));
    $appA2->post('store/push/register', ['platform' => 'ios', 'push_token' => 'UNREGISTERED_' . str_repeat('u', 50)]);
    $appB->post('store/push/register', ['platform' => 'android', 'push_token' => 'fcmB_' . str_repeat('b', 60)]);

    echo "7. Notifikasi push — pesanan (modul mobile_app)\n";
    Database::execute("DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.customer_id = 1");
    $appA->post('store/cart/add', $melati + ['product_id' => 1, 'qty' => 1, 'child_id' => 1]);
    $place = $appA->post('store/checkout/place', $melati + ['pickup_by_type' => 'student', 'payment_method' => 'cash']);
    $orderId = (int) ($place['data']['order_id'] ?? 0);
    check('Checkout tunai melalui apps (token, tanpa CSRF) → pesanan', ok($place) && $orderId > 0);
    $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
    $orderGet = $appA->get('store/orders/get', $melati + ['id' => $orderId]);
    check('store/orders/get melalui token → pesanan A', ok($orderGet) && $orderGet['data']['order_no'] === $order['order_no']);
    check('IDOR: token B → pesanan A 404; senarai pesanan B tiada pesanan A', $appB->status('store/orders/get', $melati + ['id' => $orderId]) === 404
        && strpos(json_encode($appB->get('store/orders/list', $melati)), $order['order_no']) === false);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId, 'status' => 'preparing']);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId, 'status' => 'ready']);
    $ready = pushRows('order_ready');
    $message = json_decode($ready[0]['message'] ?? '{}', true);
    check('Sedia diambil → push ke 2 peranti A (bukan B), order_id direkod', count($ready) === 2
        && count(array_intersect(array_column($ready, 'recipient'), [$pushA, 'UNREGISTERED_' . str_repeat('u', 50)])) === 2
        && (int) $ready[0]['order_id'] === $orderId && (int) $ready[0]['vendor_id'] === 1 && (int) $ready[0]['cooperative_id'] === 1);
    check('Mesej JSON: tajuk & teks bahasa ibu bapa, kod ambil, data event/slug/order_no', ($message['title'] ?? '') === 'Pesanan sedia diambil'
        && strpos($message['body'] ?? '', $order['short_code']) !== false && strpos($message['body'], 'Aisyah binti Faizal') !== false
        && ($message['data'] ?? []) === ['event' => 'order_ready', 'slug' => 'sk-taman-melati', 'order_no' => $order['order_no']]);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId, 'status' => 'preparing']);
    $v1->post('vendor/orders/set-status', $c1 + ['id' => $orderId, 'status' => 'ready']);
    check('ready → preparing → ready tidak menghantar semula', count(pushRows('order_ready')) === 2);
    $cashier1->post('vendor/handover/receive-cash', $c1 + ['id' => $orderId, 'amount_received' => (int) $order['total']]);
    $item = Database::fetchOne("SELECT id FROM order_items WHERE order_id = ?", [$orderId]);
    $collect = $cashier1->post('vendor/handover/collect', $c1 + ['id' => $orderId, 'picked_by_type' => 'student', 'picked_by_name' => 'Aisyah binti Faizal',
        'method' => 'B', 'items' => [['item_id' => (int) $item['id'], 'qty' => 1]]]);
    $collected = pushRows('order_collected');
    check('Serah barang → push order_collected (diambil oleh …)', ok($collect) && count($collected) === 2
        && strpos(json_decode($collected[0]['message'], true)['body'], 'diambil oleh Aisyah binti Faizal') !== false);
    Database::execute("UPDATE customers SET language = 'en' WHERE id = 1");
    $chung = Database::fetchOne("SELECT id, vendor_id, slug, school_name FROM cooperatives WHERE id = 2");
    $none = PushNotifications::orderReady($chung, ['customer_id' => 1] + $order);
    $en = PushNotifications::orderReady(['id' => 1, 'vendor_id' => 1, 'slug' => 'sk-taman-melati', 'school_name' => 'SK Taman Melati'], $order);
    $enRow = json_decode(pushRows('order_ready')[2]['message'] ?? '{}', true);
    Database::execute("DELETE FROM notification_queue WHERE channel = 'push' AND event = 'order_ready' AND id > ?", [(int) $ready[1]['id']]);
    Database::execute("UPDATE customers SET language = 'ms' WHERE id = 1");
    check('Koperasi tanpa mobile_app → tiada push; bahasa akaun en → tajuk Inggeris', $none === 0 && $en === 2
        && ($enRow['title'] ?? '') === 'Order ready for pickup');
    check('Push tidak muncul dalam log WhatsApp/SMS vendor', ok($log1 = $v1->get('vendor/messaging/log', $c1))
        && count(array_filter($log1['data']['items'], function ($row) {
            return $row['channel'] === 'push';
        })) === 0);

    echo "8. Notifikasi push — berita (sekali sahaja)\n";
    $news = $v1->post('vendor/news/save', $c1 + ['title' => 'Hari Sukan Tahunan', 'body' => str_repeat('Aktiviti sukan untuk semua murid. ', 10),
        'published_at' => date('Y-m-d\TH:i', time() - 60), 'is_active' => true, 'send_email' => false]);
    $newsId = (int) ($news['data']['post']['id'] ?? 0);
    $newsRows = pushRows('news');
    $newsMessage = json_decode($newsRows[0]['message'] ?? '{}', true);
    check('Berita terbit → push ke peranti ibu bapa yang ada anak di koperasi (A sahaja, 2 peranti)', ok($news) && count($newsRows) === 2
        && ($newsMessage['title'] ?? '') === 'SK Taman Melati: Hari Sukan Tahunan' && mb_strlen($newsMessage['body'], 'UTF-8') <= PushNotifications::BODY_MAX
        && ($newsMessage['data']['news_id'] ?? '') === (string) $newsId && $newsMessage['data']['event'] === 'news');
    $postRow = Database::fetchOne("SELECT push_sent_at, push_recipients FROM news_posts WHERE id = ?", [$newsId]);
    check('news_posts.push_sent_at & push_recipients ditanda', $postRow['push_sent_at'] !== null && (int) $postRow['push_recipients'] === 2);
    $v1->post('vendor/news/save', $c1 + ['id' => $newsId, 'title' => 'Hari Sukan Tahunan (dikemas kini)', 'body' => 'Teks baharu',
        'published_at' => date('Y-m-d\TH:i', time() - 60), 'is_active' => true, 'send_email' => false]);
    check('Sunting berita → tiada push kali kedua', count(pushRows('news')) === 2);
    $scheduled = $v1->post('vendor/news/save', $c1 + ['title' => 'Jualan buku', 'body' => 'Esok', 'published_at' => date('Y-m-d\TH:i', time() + 3600),
        'is_active' => true, 'send_email' => false]);
    $scheduledId = (int) ($scheduled['data']['post']['id'] ?? 0);
    check('Berita berjadual → tiada push semasa simpan', count(pushRows('news')) === 2);
    Database::execute("UPDATE news_posts SET published_at = NOW() - INTERVAL 1 MINUTE WHERE id = ?", [$scheduledId]);
    (new QueueDueNewsEmailsJob())->run();
    (new QueueDueNewsEmailsJob())->run();
    check('Cron (QueueDueNewsEmailsJob) pada masa terbit → push sekali sahaja', count(pushRows('news')) === 4);
    $v2->post('vendor/news/save', ['cooperative_id' => 3, 'title' => 'Berita VENDOR02', 'body' => 'x', 'published_at' => date('Y-m-d\TH:i', time() - 60),
        'is_active' => true, 'send_email' => false]);
    check('Berita koperasi tanpa mobile_app (VENDOR02) → tiada push', count(pushRows('news')) === 4);

    echo "9. Hantar push (SendPushJob → FCM palsu)\n";
    $pending = count(array_filter(pushRows(), function ($row) {
        return $row['status'] === 'pending';
    }));
    $none = (new SendPushJob(100, sys_get_temp_dir() . '/tiada-akaun.json'))->run();
    check('Tiada akaun perkhidmatan → configured false, baris kekal pending', $none['configured'] === false
        && count(array_filter(pushRows(), function ($row) {
            return $row['status'] === 'pending';
        })) === $pending);
    $failToken = 'FAIL_' . str_repeat('f', 60);
    Database::execute("INSERT INTO push_devices (customer_id, api_token_id, platform, push_token) VALUES (1, NULL, 'android', ?)", [$failToken]);
    Database::execute(
        "INSERT INTO notification_queue (vendor_id, cooperative_id, channel, event, recipient, message) VALUES (1, 1, 'push', 'news', ?, ?)",
        [$failToken, json_encode(['title' => 'T', 'body' => 'B', 'data' => ['event' => 'news']])]
    );
    Database::execute(
        "INSERT INTO notification_queue (vendor_id, cooperative_id, channel, event, recipient, message) VALUES (1, 1, 'push', 'news', 'tiada_peranti_xxxxxxxxxxxxxxxx', '{}')"
    );
    $run = (new SendPushJob(100, $accountFile))->run();
    $tokenCalls = fakeRequests($fakeBase, 'fcm_token');
    $sends = array_values(array_filter(fakeRequests($fakeBase, 'fcm'), function ($row) {
        return $row['ok'];
    }));
    check('JWT RS256 akaun perkhidmatan disahkan oleh FCM palsu; satu token OAuth untuk satu larian', count($tokenCalls) === 1 && $tokenCalls[0]['ok']
        && $tokenCalls[0]['iss'] === 'push@koperasiku-ujian.iam.gserviceaccount.com');
    check('Dihantar: semua push ke ' . $pushA . ' (pesanan × 2 + berita × 2) dengan tajuk/teks/data', $run['configured'] && $run['sent'] === 4
        && count($sends) === 4 && $sends[0]['to'] === $pushA && $sends[0]['title'] === 'Pesanan sedia diambil'
        && $sends[0]['data']['order_no'] === $order['order_no'] && ($sends[0]['id'] ?? '') === 'koperasiku-ujian');
    $unregRows = Database::fetchAll("SELECT status, last_error FROM notification_queue WHERE recipient LIKE 'UNREGISTERED%'");
    check('Token UNREGISTERED → failed + peranti dibuang', count($unregRows) >= 1 && $unregRows[0]['status'] === 'failed'
        && strpos((string) $unregRows[0]['last_error'], 'UNREGISTERED') !== false
        && (int) Database::fetchValue("SELECT COUNT(*) FROM push_devices WHERE push_token LIKE 'UNREGISTERED%'") === 0);
    check('Peranti tiada lagi → failed tanpa dihantar', Database::fetchValue("SELECT status FROM notification_queue WHERE recipient = 'tiada_peranti_xxxxxxxxxxxxxxxx'") === 'failed');
    $retry = Database::fetchOne("SELECT status, attempts FROM notification_queue WHERE recipient = ?", [$failToken]);
    (new SendPushJob(100, $accountFile))->run();
    (new SendPushJob(100, $accountFile))->run();
    $final = Database::fetchOne("SELECT status, attempts, last_error FROM notification_queue WHERE recipient = ?", [$failToken]);
    check('FCM 503 → cuba semula; selepas 3 cubaan failed + last_error', $retry['status'] === 'pending' && (int) $retry['attempts'] === 1
        && $final['status'] === 'failed' && (int) $final['attempts'] === 3 && strpos((string) $final['last_error'], 'fcm: HTTP 503') === 0);
    Database::execute(
        "INSERT INTO notification_queue (vendor_id, cooperative_id, channel, event, recipient, message) VALUES (1, 1, 'push', 'news', ?, ?)",
        [$pushA, json_encode(['title' => 'T', 'body' => 'B', 'data' => []])]
    );
    Database::execute("UPDATE cooperative_modules SET ends_at = CURDATE() - INTERVAL 1 DAY WHERE module_id = ?", [$mobileModule]);
    (new SendPushJob(100, $accountFile))->run();
    Database::execute("UPDATE cooperative_modules SET ends_at = CURDATE() + INTERVAL 1 YEAR WHERE module_id = ?", [$mobileModule]);
    check('Modul mobile_app tamat → push dalam baris gilir tidak dihantar (failed)', Database::fetchValue(
        "SELECT status FROM notification_queue WHERE recipient = ? ORDER BY id DESC LIMIT 1", [$pushA]) === 'failed'
        && count(fakeRequests($fakeBase, 'fcm')) === count($sends) + 1 + 3);

    echo "10. Log keluar apps, tukar & reset kata laluan\n";
    $revoke = $appA2->post('store/auth/token-revoke', []);
    check('token-revoke → token semasa 401 selepas itu', ok($revoke) && $appA2->status('store/session') === 401);
    check('token-revoke dari sesi web → 403 token_required', !empty($parentA->post('store/auth/token-revoke', [])['token_required']));
    $tokenA3 = issueToken($guest, 'siti@contoh.my', 'Telefon kedua')['data']['token'];
    $change = $appA->post('store/profile/password', ['current_password' => 'Demo@12345', 'password' => 'Baharu12345', 'password_confirm' => 'Baharu12345']);
    check('Tukar kata laluan melalui apps → token lain dibatal, token semasa kekal', ok($change) && appBrowser($base, $tokenA3)->status('store/session') === 401
        && ok($appA->get('store/session', $melati)) && (int) Database::fetchValue("SELECT COUNT(*) FROM push_devices WHERE customer_id = 1 AND api_token_id = ?", [$tokenAId]) === 1);
    $raw = AuthToken::issue('customer', 1, 'reset_password', 3600);
    (new ResetPasswordJob('customer', ['token' => $raw, 'password' => 'Demo@12345', 'password_confirm' => 'Demo@12345']))->run();
    check('Reset kata laluan (e-mel) → SEMUA token apps dibatal + peranti push dibuang', $appA->status('store/session') === 401
        && (int) Database::fetchValue("SELECT COUNT(*) FROM api_tokens WHERE customer_id = 1 AND revoked_at IS NULL") === 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM push_devices WHERE customer_id = 1 AND api_token_id IS NOT NULL") === 0);
    clearTestLocks();

    echo "11. PDPA — padam akaun melalui apps\n";
    Database::execute(
        "INSERT INTO customers (id, full_name, phone, email, password_hash, relationship, language, email_verified_at, pdpa_consent_at, is_active)
         VALUES (90, 'Ujian Padam B15', '0199990015', 'padam.b15@contoh.my', ?, 'mother', 'ms', NOW(), NOW(), 1)",
        [Password::hash('Demo@12345')]
    );
    Database::execute("INSERT INTO children (customer_id, cooperative_id, vendor_id, full_name, grade_level_code, class_name, school_year, is_active)
        VALUES (90, 1, 1, 'Anak Padam', 'D2', 'Bestari', YEAR(CURDATE()), 1)");
    $tokenC = issueToken($guest, 'padam.b15@contoh.my', 'Telefon C')['data']['token'] ?? '';
    $appC = appBrowser($base, $tokenC);
    $pushC = 'fcmC_' . str_repeat('c', 60);
    $appC->post('store/push/register', ['platform' => 'android', 'push_token' => $pushC]);
    $v1->post('vendor/news/save', $c1 + ['title' => 'Notis PDPA', 'body' => 'x', 'published_at' => date('Y-m-d\TH:i', time() - 60), 'is_active' => true,
        'send_email' => false]);
    $queuedC = (int) Database::fetchValue("SELECT COUNT(*) FROM notification_queue WHERE recipient = ?", [$pushC]);
    $delete = $appC->post('store/profile/delete', ['password' => 'Demo@12345']);
    check('Padam akaun (token): token & peranti dibuang, push tertunda dibuang', $queuedC === 1 && ok($delete)
        && (int) Database::fetchValue("SELECT COUNT(*) FROM api_tokens WHERE customer_id = 90") === 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM push_devices WHERE customer_id = 90") === 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM notification_queue WHERE recipient = ?", [$pushC]) === 0
        && $appC->status('store/session') === 401);

    echo "12. Kebocoran VENDOR01 ↔ VENDOR02\n";
    check('VENDOR02 tidak menerima push / baris gilir koperasi VENDOR01', (int) Database::fetchValue(
        "SELECT COUNT(*) FROM notification_queue WHERE channel = 'push' AND vendor_id = 2") === 0);
    check('VENDOR02 log mesej koperasi 1 → 404', $v2->status('vendor/messaging/log', $c1) === 404);
    check('Ibu bapa tidak boleh memanggil laluan vendor dengan token', $appB->status('vendor/news/list', $c1) === 401);
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
    @unlink($accountFile);
    @unlink($publicKeyFile);
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
