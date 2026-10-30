<?php
// Semakan B14 — modul terjemahan (jadual translations → storage/lang/*.php, MissingKeysJob) + pengerasan keselamatan PELAN 15
// satu per satu: header & CSP, cookie & sesi, rate limit, muat naik, token hash, PDPA padam akaun, log audit, backup.
// Ujian penembusan asas ke atas SETIAP endpoint api/v1: tanpa sesi, kaedah salah, CSRF, peranan, IDOR VENDOR02 → rekod VENDOR01,
// ibu bapa B → rekod ibu bapa A, SQLi pada semua parameter, XSS pada nama produk & anak. CLI sahaja:
//   php tools/verify-b14.php
// Perlu: DB koperasi_saas (schema + seed + migrasi 007), config/database.php, config/secret.key, mysqldump & mysql, sambungan curl & gd.
// Pangkalan data dibackup dengan BackupDatabaseJob pada permulaan dan DIPULIHKAN dari backup itu di akhir (juga menguji backup).
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

function header1(array $res, $name)
{
    return $res['headers'][$name][0] ?? null;
}

// Ungkapan cetak: syarat ? 'teks' . e($x) . 'teks' : '' — setiap cabang hanya rentetan pemalar dan/atau e(...)
function safeTernary($expr)
{
    $question = strpos($expr, ' ? ');
    $colon = strrpos($expr, ' : ');
    if ($question === false || $colon === false || $colon < $question) {
        return false;
    }
    $part = "('[^']*'|e\\((?:[^()]|\\([^()]*\\))*\\))";
    $branch = '/^' . $part . '(\\s*\\.\\s*' . $part . ')*$/';
    return preg_match($branch, trim(substr($expr, $question + 3, $colon - $question - 3))) && preg_match($branch, trim(substr($expr, $colon + 3)));
}

// Senarai endpoint dari fail api/v1: laluan, kaedah, kawasan, peranan, skop koperasi, CSRF
function endpoints()
{
    $list = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT . '/api/v1', FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $code = file_get_contents($file);
        $route = substr(str_replace('\\', '/', substr($file, strlen(APP_ROOT . '/api/v1/'))), 0, -4);
        preg_match("/requireMethod\('([A-Z]+)'\)/", $code, $method);
        $list[$route] = [
            'route'     => $route,
            'area'      => explode('/', $route)[0],
            'method'    => $method[1] ?? 'ANY',
            'auth'      => (bool) preg_match('/(OwnerAuth|VendorAuth|CustomerAuth)::require(Role|Login)/', $code),
            'owner_only'=> strpos($code, "OwnerAuth::requireRole(['owner'])") !== false,
            'admin_only'=> strpos($code, 'VendorAuth::ADMIN_ROLES') !== false,
            'panel_only'=> strpos($code, 'VendorAuth::PANEL_ROLES') !== false,
            'coop'      => strpos($code, 'VendorAuth::requireCooperative') !== false,
            'cashier'   => strpos($code, 'PosAuth::requireCashier') !== false,
            'csrf'      => strpos($code, 'Csrf::verifyRequest') !== false,
            // B15: log masuk apps (token Bearer) — tiada sesi / cookie, jadi tiada CSRF (diuji dalam verify-b15)
            'token_issue' => strpos($code, 'IssueApiTokenJob') !== false,
            'sql'       => (bool) preg_match('/Database::|\b(SELECT|INSERT|UPDATE|DELETE)\s+(\*|[a-z_]+\s+(FROM|INTO|SET)|FROM|INTO)\b/i', $code),
            'raw_input' => (bool) preg_match('/\$_(GET|POST|REQUEST|COOKIE)\b/', $code),
        ];
    }
    ksort($list);
    return $list;
}

function inArea(array $endpoints, $area, $skipAuth = true)
{
    return array_filter($endpoints, function ($ep) use ($area, $skipAuth) {
        return $ep['area'] === $area && !($skipAuth && strpos($ep['route'], $area . '/auth/') === 0);
    });
}

// Hantar setiap endpoint × setiap set parameter. Kumpul 500, kebocoran (teks kanari dalam jawapan) & status
function sweep(Browser $browser, array $endpoints, array $paramSets, array $canaries = [])
{
    $result = ['count' => 0, 'errors' => [], 'leaks' => [], 'statuses' => []];
    foreach ($endpoints as $ep) {
        foreach ($paramSets as $params) {
            $method = $ep['method'] === 'ANY' ? 'POST' : $ep['method'];
            $res = $browser->raw($method, $ep['route'], $params);
            $result['count']++;
            $result['statuses'][$ep['route']][] = $res['status'];
            if ($res['status'] >= 500 || $res['status'] === 0) {
                $result['errors'][] = $ep['route'] . ' ' . $res['status'] . ' ' . substr(json_encode($params), 0, 80);
            }
            foreach ($canaries as $canary) {
                if ($canary !== '' && stripos($res['body'], $canary) !== false) {
                    $result['leaks'][] = $ep['route'] . ' → "' . $canary . '"';
                }
            }
        }
    }
    $result['errors'] = array_values(array_unique($result['errors']));
    $result['leaks'] = array_values(array_unique($result['leaks']));
    return $result;
}

function showList($label, array $items, $max = 15)
{
    if (count($items) > 0) {
        echo "      $label: " . implode(' | ', array_slice($items, 0, $max)) . (count($items) > $max ? ' …(+' . (count($items) - $max) . ')' : '') . "\n";
    }
}

// Cap jari semua baris milik vendor (setiap jadual dengan lajur vendor_id)
function vendorFingerprint($vendorId)
{
    $tables = Database::fetchAll(
        "SELECT table_name AS t FROM information_schema.columns WHERE table_schema = DATABASE() AND column_name = 'vendor_id' ORDER BY table_name"
    );
    $print = [];
    foreach (array_column($tables, 't') as $table) {
        $print[$table] = md5(json_encode(Database::fetchAll("SELECT * FROM `$table` WHERE vendor_id = ?", [(int) $vendorId])));
    }
    $print['vendors'] = md5(json_encode(Database::fetchAll("SELECT * FROM vendors WHERE id = ?", [(int) $vendorId])));
    return $print;
}

// Cap jari data ibu bapa (akaun, anak, troli, pesanan, token)
function customerFingerprint($customerId)
{
    $id = (int) $customerId;
    return md5(json_encode([
        Database::fetchAll("SELECT * FROM customers WHERE id = ?", [$id]),
        Database::fetchAll("SELECT * FROM children WHERE customer_id = ? ORDER BY id", [$id]),
        Database::fetchAll("SELECT ci.* FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.customer_id = ? ORDER BY ci.id", [$id]),
        Database::fetchAll("SELECT * FROM orders WHERE customer_id = ? ORDER BY id", [$id]),
        Database::fetchAll("SELECT oi.* FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.customer_id = ? ORDER BY oi.id", [$id]),
        Database::fetchAll("SELECT * FROM customer_tokens WHERE customer_id = ? ORDER BY id", [$id]),
    ]));
}

function firstId($table, $where, array $args)
{
    return (int) Database::fetchValue("SELECT id FROM `$table` WHERE $where ORDER BY id LIMIT 1", $args);
}

// Pulihkan pangkalan data dari backup .sql.gz (mysql CLI, kata laluan dalam fail pilihan sementara)
function restoreBackup($file)
{
    $config = require APP_ROOT . '/config/database.php';
    $options = tempnam(APP_ROOT . '/storage', 'myrest');
    chmod($options, 0600);
    $quote = function ($value) {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value) . '"';
    };
    file_put_contents($options, "[client]\nuser=" . $quote($config['username']) . "\npassword=" . $quote($config['password'])
        . "\nhost=" . $quote($config['host']) . "\nport=" . (int) $config['port'] . "\n");
    $mysql = preg_replace('/mysqldump(\.exe)?$/', 'mysql$1', (string) Config::get('mysqldump_path'));
    $process = proc_open([$mysql, '--defaults-extra-file=' . $options, '--default-character-set=utf8mb4', $config['database']],
        [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['pipe', 'w']], $pipes);
    $gz = gzopen($file, 'rb');
    while (!gzeof($gz)) {
        fwrite($pipes[0], gzread($gz, 65536));
    }
    gzclose($gz);
    fclose($pipes[0]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $code = proc_close($process);
    @unlink($options);
    if ($code !== 0) {
        echo "  Pemulihan GAGAL: $error\n";
    }
    return $code === 0;
}

$port = 8140;
$base = 'http://127.0.0.1:' . $port;
$log = sys_get_temp_dir() . '/kop-verify-b14.log';
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4'] + getenv());
usleep(900000);

$backupDir = sys_get_temp_dir() . '/kop-b14-backup-' . getmypid();
$uploadsBefore = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT . '/uploads', FilesystemIterator::SKIP_DOTS)) as $file) {
    $uploadsBefore[(string) $file] = true;
}
$langMtime = filemtime(APP_ROOT . '/lang/ms.php');

echo "0. Backup pangkalan data (BackupDatabaseJob) — juga titik pemulihan ujian ini\n";
touch($backupDir . '-dummy');
@mkdir($backupDir, 0700, true);
touch($backupDir . '/koperasi_saas-20000101-000000.sql.gz', time() - 30 * 86400);
touch($backupDir . '/nota-lain.txt', time() - 30 * 86400);
$backup = (new BackupDatabaseJob($backupDir, 14))->run();
$dump = gzdecode((string) file_get_contents($backup['file']));
$dbConfig = require APP_ROOT . '/config/database.php';
check('Fail .sql.gz dicipta (nama ikut corak, 0600) & sah gzip', is_file($backup['file']) && $dump !== false
    && preg_match('#/koperasi_saas-\d{8}-\d{6}\.sql\.gz$#', $backup['file']) && (fileperms($backup['file']) & 0777) === 0600);
check('Kandungan: CREATE TABLE `orders`, INSERT INTO `vendors`, utf8mb4, "Dump completed"', strpos($dump, 'CREATE TABLE `orders`') !== false
    && strpos($dump, 'INSERT INTO `vendors`') !== false && strpos($dump, 'utf8mb4') !== false && strpos($dump, 'Dump completed') !== false);
check('Kata laluan DB tiada dalam backup; fail pilihan sementara dibuang', strpos($dump, (string) $dbConfig['password']) === false
    && count(glob(APP_ROOT . '/storage/mydump*')) === 0);
check('Backup lama (> 14 hari) dibuang; fail lain dalam folder tidak disentuh', $backup['deleted'] === 1
    && !is_file($backupDir . '/koperasi_saas-20000101-000000.sql.gz') && is_file($backupDir . '/nota-lain.txt'));
@unlink($backupDir . '-dummy');
$cron = file_get_contents(APP_ROOT . '/cron/backup-db.php');
check('cron/backup-db.php: CLI sahaja, flock, dokumen pulih', strpos($cron, "PHP_SAPI !== 'cli'") !== false && strpos($cron, 'flock') !== false
    && strpos($cron, 'gunzip -c') !== false);
unset($dump);
clearTestLocks();

try {
    $endpoints = endpoints();
    // 2FA owner disediakan semula semasa log masuk (data dipulihkan dari backup di akhir)
    Database::execute("UPDATE platform_users SET totp_secret = NULL, totp_last_step = NULL");
    list($owner) = ownerLogin($base, 'owner@koperasiku.my');
    list($ownerStaff) = ownerLogin($base, 'staf@koperasiku.my');
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $manager2 = vendorLogin($base, 'chong@bestari.my');
    $cashier1 = vendorLogin($base, 'kasim@niagasekolah.my');
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    list($parentB) = storeLogin($base, 'rajesh@contoh.my');
    $guest = new Browser($base);
    $guest->page('store/index.php');

    echo "1. Senarai endpoint (" . count($endpoints) . ") — semakan statik\n";
    check('Tiada SQL / Database:: dalam api/', count(array_filter($endpoints, function ($ep) { return $ep['sql']; })) === 0);
    check('Tiada $_GET / $_POST / $_REQUEST / $_COOKIE terus dalam api/ (input melalui Request)',
        count(array_filter($endpoints, function ($ep) { return $ep['raw_input']; })) === 0);
    $noCsrf = array_keys(array_filter($endpoints, function ($ep) { return $ep['method'] === 'POST' && !$ep['csrf'] && $ep['area'] !== 'webhooks' && !$ep['token_issue']; }));
    check('Setiap endpoint POST (kecuali webhook gateway & log masuk token apps) memanggil Csrf::verifyRequest', count($noCsrf) === 0);
    showList('tanpa CSRF', $noCsrf);
    $noMethod = array_keys(array_filter($endpoints, function ($ep) { return $ep['method'] === 'ANY'; }));
    check('Setiap endpoint menetapkan kaedah (Request::requireMethod)', count($noMethod) === 0);
    showList('tanpa kaedah', $noMethod);
    $apiVendorInput = [];
    foreach (glob(APP_ROOT . '/api/v1/{owner,vendor,store}/*/*.php', GLOB_BRACE) as $file) {
        if (preg_match("/Request::(int|get|string)\('(vendor_id|customer_id)'\)/", file_get_contents($file), $m)
            && strpos($file, '/vendor/customers/') === false) {
            $apiVendorInput[] = basename(dirname($file)) . '/' . basename($file);
        }
    }
    check('vendor_id / customer_id tidak dibaca dari input (kecuali customer_id sasaran nota pelanggan vendor)', count($apiVendorInput) === 0);
    $jobsRaw = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT . '/jobs', FilesystemIterator::SKIP_DOTS)) as $file) {
        $code = file_get_contents($file);
        if (preg_match('/\$_(GET|POST|REQUEST|COOKIE)\b/', $code)) {
            $jobsRaw[] = basename($file);
        }
        // Interpolasi input terus dalam SQL: "... $this->input[...]" atau '...' . $this->input
        if (preg_match('/"[^"]*\b(SELECT|UPDATE|INSERT|DELETE|WHERE)\b[^"]*\{?\$this->input/s', $code)) {
            $jobsRaw[] = basename($file) . ' (SQL)';
        }
    }
    check('jobs/: tiada superglobal input & tiada $this->input diinterpolasi dalam SQL', count($jobsRaw) === 0);
    showList('jobs', $jobsRaw);
    $dbCode = file_get_contents(APP_ROOT . '/core/Database.php');
    check('PDO: EMULATE_PREPARES false, ERRMODE_EXCEPTION, charset utf8mb4', strpos($dbCode, 'PDO::ATTR_EMULATE_PREPARES   => false') !== false
        && strpos($dbCode, 'ERRMODE_EXCEPTION') !== false && strpos($dbCode, 'charset=utf8mb4') !== false);
    $createDb = file_get_contents(APP_ROOT . '/database/create-database.sql');
    preg_match_all('/^\s*GRANT\b[^;]*;/mi', $createDb, $grants);
    check('Pengguna MySQL koperasi_user: GRANT pada koperasi_saas.* sahaja (bukan *.* / ALL)', count($grants[0]) === 1
        && strpos($grants[0][0], 'ON koperasi_saas.* TO') !== false && strpos($grants[0][0], '*.*') === false && stripos($grants[0][0], 'GRANT ALL') === false);
    $gitignore = file_get_contents(APP_ROOT . '/.gitignore');
    check('.gitignore: config/database.php, config/secret.key, uploads/*, storage/*', strpos($gitignore, 'config/database.php') !== false
        && strpos($gitignore, 'config/secret.key') !== false && strpos($gitignore, 'uploads/*') !== false && strpos($gitignore, 'storage/*') !== false);
    $denied = [];
    foreach (['config', 'core', 'jobs', 'database', 'cron', 'tools', 'lang', 'storage', 'messaging', 'payments', 'partials', 'DOC',
        'owner/_layout', 'vendor/_layout'] as $dir) {
        if (strpos((string) @file_get_contents(APP_ROOT . '/' . $dir . '/.htaccess'), 'Require all denied') === false) {
            $denied[] = $dir;
        }
    }
    check('Folder dalaman ada .htaccess "Require all denied" (config, core, jobs, database, cron, tools, lang, storage, DOC …)', count($denied) === 0);
    showList('tiada', $denied);
    $rootHt = file_get_contents(APP_ROOT . '/.htaccess');
    $uploadHt = file_get_contents(APP_ROOT . '/uploads/.htaccess');
    check('.htaccess akar: Options -Indexes, fail tersembunyi (.git/.env), .md/.sql/.key dihalang', strpos($rootHt, 'Options -Indexes') !== false
        && strpos($rootHt, '.(git|svn|hg|env)') !== false && strpos($rootHt, 'md|sql|log|lock|key') !== false);
    check('uploads/.htaccess: PHP tidak dilaksana, hanya jpg/png/webp, CSP sandbox, tiada senarai', strpos($uploadHt, 'php_flag engine off') !== false
        && strpos($uploadHt, '(jpe?g|png|webp)') !== false && strpos($uploadHt, 'sandbox') !== false && strpos($uploadHt, 'Options -Indexes') !== false);
    $js = '';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT . '/assets/js', FilesystemIterator::SKIP_DOTS)) as $file) {
        $js .= preg_replace('#^\s*//.*$#m', '', file_get_contents($file));
    }
    check('JS: tiada innerHTML / outerHTML / insertAdjacentHTML / document.write / eval (data melalui textContent)',
        !preg_match('/\.(innerHTML|outerHTML)\s*=|insertAdjacentHTML|document\.write|\beval\(|new Function\(/', $js));
    $rawEcho = [];
    foreach (['owner', 'vendor', 'store', 'templates', 'partials'] as $dir) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT . '/' . $dir, FilesystemIterator::SKIP_DOTS)) as $file) {
            if (substr($file, -4) !== '.php') {
                continue;
            }
            // Setiap <?= mesti e(...) atau pilihan pemalar ($x ? ' active' : '')
            preg_match_all('/<\?=\s*(.*?)\s*\?>/s', file_get_contents($file), $matches);
            foreach ($matches[1] as $expr) {
                if (strpos($expr, 'e(') !== 0 && !safeTernary($expr)) {
                    $rawEcho[] = basename($file) . ': ' . $expr;
                }
            }
            if (preg_match('/<\?php\s+(echo|print)\b/', file_get_contents($file))) {
                $rawEcho[] = basename($file) . ': echo';
            }
        }
    }
    check('Paparan PHP: setiap <?= melalui e() (atau pemalar)', count($rawEcho) === 0);
    showList('tanpa e()', $rawEcho);
    $passwordOptions = Config::get('password_options');
    check('Argon2id 19456/2/1 (config) + needs_rehash', $passwordOptions === ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1]
        && strpos(file_get_contents(APP_ROOT . '/core/Password.php'), 'password_needs_rehash') !== false);
    check('Sesi idle: owner & vendor 2 jam, ibu bapa 30 hari', OwnerAuth::IDLE_SECONDS === 7200 && VendorAuth::IDLE_SECONDS === 7200
        && CustomerAuth::IDLE_SECONDS === 30 * 86400);

    echo "2. Header keselamatan & cookie\n";
    $page = $guest->raw('GET', 'store/index.php');
    $api = $guest->raw('GET', 'store/schools/list', ['q' => 'sk']);
    $csp = (string) header1($page, 'content-security-policy');
    check('CSP: default-src self, tiada unsafe-inline / unsafe-eval, object-src none, frame-ancestors none', strpos($csp, "default-src 'self'") === 0
        && strpos($csp, 'unsafe') === false && strpos($csp, "object-src 'none'") !== false && strpos($csp, "frame-ancestors 'none'") !== false
        && strpos($csp, "script-src 'self';") !== false && strpos($csp, "style-src 'self';") !== false);
    $headerOk = function ($res) {
        return header1($res, 'x-frame-options') === 'DENY' && header1($res, 'x-content-type-options') === 'nosniff'
            && header1($res, 'referrer-policy') === 'strict-origin-when-cross-origin' && strpos((string) header1($res, 'permissions-policy'), 'camera=()') !== false
            && header1($res, 'cross-origin-opener-policy') === 'same-origin' && header1($res, 'x-powered-by') === null
            && header1($res, 'content-security-policy') !== null;
    };
    check('Halaman: X-Frame-Options DENY, nosniff, Referrer-Policy, Permissions-Policy, COOP, tiada X-Powered-By', $headerOk($page));
    check('API: header sama + Content-Type JSON + Cache-Control no-store', $headerOk($api)
        && strpos((string) header1($api, 'content-type'), 'application/json') === 0 && header1($api, 'cache-control') === 'no-store');
    check('HTTP biasa: tiada HSTS (hanya HTTPS)', header1($page, 'strict-transport-security') === null);
    $html = $page['body'];
    check('HTML: tiada <script> / <style> inline, tiada atribut style= / on*= ', !preg_match('/<script(?![^>]*\bsrc=)[^>]*>|<style\b|\sstyle="|\son[a-z]+="/i', $html));

    $fresh = new Browser($base);
    $loginPage = $fresh->raw('GET', 'vendor/login.php');
    $before = $fresh->cookie('KOPVENDOR');
    $login = $fresh->raw('POST', 'vendor/auth/login', ['email' => 'ahmad@niagasekolah.my', 'password' => 'Demo@12345'], false);
    $fresh->page('vendor/login.php');
    $loginRes = $fresh->raw('POST', 'vendor/auth/login', ['email' => 'ahmad@niagasekolah.my', 'password' => 'Demo@12345']);
    $setCookie = implode("\n", $loginRes['headers']['set-cookie'] ?? []);
    check('Log masuk tanpa CSRF → 419', $login['status'] === 419);
    check('Cookie sesi: HttpOnly, SameSite=Lax, path aplikasi', stripos($setCookie, 'KOPVENDOR=') !== false && stripos($setCookie, 'HttpOnly') !== false
        && stripos($setCookie, 'SameSite=Lax') !== false);
    check('Id sesi ditukar selepas log masuk (session_regenerate_id)', ok($loginRes['json']) && $before !== null && $fresh->cookie('KOPVENDOR') !== $before);
    $langRes = $guest->raw('GET', 'store/index.php', ['lang' => 'en']);
    check('Cookie bahasa: HttpOnly + SameSite=Lax', stripos(implode("\n", $langRes['headers']['set-cookie'] ?? []), 'lang=en') !== false
        && stripos(implode("\n", $langRes['headers']['set-cookie']), 'HttpOnly') !== false);
    $guest->raw('GET', 'store/index.php', ['lang' => 'ms']);

    // Proksi dipercayai: hanya config trusted_proxies boleh menetapkan IP pelanggan / HTTPS
    $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    check('X-Forwarded-For / -Proto diabaikan dari pelanggan biasa (IP & HTTPS tidak boleh dipalsukan)', Request::ip() === '203.0.113.9'
        && !SecurityHeaders::isHttps() && Config::get('trusted_proxies') === []);
    unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    echo "3. Rate limit (log masuk, daftar, lupa kata laluan, kod ambil)\n";
    clearTestLocks();
    $spam = new Browser($base);
    $spam->page('store/index.php?page=login');
    $codes = [];
    for ($i = 0; $i < 31; $i++) {
        $codes[] = $spam->raw('POST', 'store/auth/login', ['login' => 'tiada' . $i . '@contoh.my', 'password' => 'x'])['status'];
    }
    check('Log masuk ibu bapa: 30 per 15 minit per IP → cubaan ke-31 = 429', $codes[29] !== 429 && $codes[30] === 429);
    check('Mesej ralat log masuk sama (akaun wujud / tidak) — tiada pendedahan', ($r1 = $spam->post('store/auth/login', ['login' => 'x@y.my', 'password' => 'a']))
        && Database::execute("DELETE FROM rate_limits WHERE bucket_key LIKE 'store_login_ip:%'") >= 0
        && ($a = $spam->post('store/auth/login', ['login' => 'tiadalangsung@contoh.my', 'password' => 'Salah12345']))
        && ($b = $spam->post('store/auth/login', ['login' => 'rajesh@contoh.my', 'password' => 'Salah12345']))
        && $a['error'] === $b['error']);
    clearTestLocks();
    $limits = [
        ['store/auth/register', 'store_register_ip:127.0.0.1', 10],
        ['store/auth/forgot', 'forgot_ip:127.0.0.1', 10],
        ['vendor/auth/login', 'vendor_login_ip:127.0.0.1', 30],
        ['owner/auth/login', 'owner_login_ip:127.0.0.1', 20],
    ];
    foreach ($limits as $limit) {
        Database::execute("REPLACE INTO rate_limits (bucket_key, hits, window_start) VALUES (?, ?, NOW())", [$limit[1], $limit[2]]);
        $browser = new Browser($base);
        $browser->page(strpos($limit[0], 'store') === 0 ? 'store/index.php' : explode('/', $limit[0])[0] . '/login.php');
        check($limit[0] . ': had ' . $limit[2] . ' dicapai → 429', $browser->raw('POST', $limit[0], ['email' => 'a@b.my', 'login' => 'a@b.my'])['status'] === 429);
    }
    $kasimId = (int) Database::fetchValue("SELECT id FROM vendor_users WHERE email = 'kasim@niagasekolah.my'");
    Database::execute("REPLACE INTO rate_limits (bucket_key, hits, window_start) VALUES (?, 300, NOW())", ['pickup_code:' . $kasimId]);
    check('Kod ambil (serah barang): 300 per 10 minit per pengguna → 429', $cashier1->raw('POST', 'vendor/handover/collect', ['cooperative_id' => 1, 'id' => 1])['status'] === 429);
    Database::execute("REPLACE INTO rate_limits (bucket_key, hits, window_start) VALUES (?, 600, NOW())", ['pickup_search:' . $kasimId]);
    check('Carian kod pendek / QR: 600 per 10 minit per pengguna → 429', $cashier1->raw('GET', 'vendor/handover/search', ['cooperative_id' => 1, 'q' => '1234'])['status'] === 429);
    clearTestLocks();
    check('Kunci akaun selepas 5 gagal (LoginLockout::MAX_FAILURES)', LoginLockout::MAX_FAILURES === 5);

    echo "4. Penembusan: tanpa sesi, kaedah salah, CSRF, kawasan & peranan — SETIAP endpoint\n";
    $scope = ['slug' => 'sk-taman-melati', 'cooperative_id' => 1, 'id' => 1];
    $bad = [];
    foreach ($endpoints as $ep) {
        if ($ep['auth']) {
            $status = $guest->raw($ep['method'], $ep['route'], $scope)['status'];
            if ($status !== 401) {
                $bad[] = $ep['route'] . '=' . $status;
            }
        }
    }
    check('Tanpa sesi → 401 untuk setiap endpoint berlog masuk (' . count(array_filter($endpoints, function ($ep) { return $ep['auth']; })) . ')', count($bad) === 0);
    showList('bukan 401', $bad);
    $bad = [];
    foreach ($endpoints as $ep) {
        $wrong = $ep['method'] === 'GET' ? 'POST' : 'GET';
        $status = $guest->raw($wrong, $ep['route'], $scope)['status'];
        if ($status !== 405) {
            $bad[] = $ep['route'] . '=' . $status;
        }
    }
    check('Kaedah salah → 405 untuk setiap endpoint (' . count($endpoints) . ', termasuk webhook)', count($bad) === 0);
    showList('bukan 405', $bad);
    $sessions = ['owner' => $owner, 'vendor' => $v1, 'store' => $parentA];
    $bad = [];
    $posts = 0;
    foreach ($endpoints as $ep) {
        if ($ep['method'] !== 'POST' || $ep['area'] === 'webhooks' || $ep['token_issue']) {
            continue;
        }
        $posts++;
        $browser = $ep['auth'] ? $sessions[$ep['area']] : $guest;
        $res = $browser->raw('POST', $ep['route'], $scope, false);
        if ($res['status'] !== 419) {
            $bad[] = $ep['route'] . '=' . $res['status'];
        }
    }
    check("POST tanpa token CSRF (dengan sesi sah) → 419 untuk setiap endpoint ($posts)", count($bad) === 0);
    showList('bukan 419', $bad);
    $bad = [];
    foreach ($endpoints as $ep) {
        if (!$ep['auth'] || $ep['area'] === 'webhooks') {
            continue;
        }
        // Sesi kawasan lain tidak pernah diterima: vendor → owner, ibu bapa → vendor, owner → store
        $other = ['owner' => $v1, 'vendor' => $parentA, 'store' => $owner][$ep['area']];
        $status = $other->raw($ep['method'], $ep['route'], $scope)['status'];
        if ($status !== 401) {
            $bad[] = $ep['route'] . '=' . $status;
        }
    }
    check('Sesi kawasan lain (vendor→owner, ibu bapa→vendor, owner→store) → 401', count($bad) === 0);
    showList('bukan 401', $bad);
    $bad = [];
    $roleCount = 0;
    foreach ($endpoints as $ep) {
        $checks = [];
        if ($ep['owner_only']) {
            $checks[] = [$ownerStaff, 'owner_staff'];
        }
        if ($ep['admin_only'] || $ep['panel_only']) {
            $checks[] = [$cashier1, 'juruwang'];
        }
        if ($ep['admin_only']) {
            $checks[] = [$manager2, 'pengurus'];
        }
        foreach ($checks as $item) {
            $roleCount++;
            $status = $item[0]->raw($ep['method'], $ep['route'], $scope + ['cooperative_id' => $item[1] === 'pengurus' ? 2 : 1])['status'];
            if ($status !== 403) {
                $bad[] = $item[1] . ' ' . $ep['route'] . '=' . $status;
            }
        }
    }
    check("Peranan: owner_staff → endpoint owner sahaja, juruwang → panel/admin, pengurus → admin sahaja = 403 ($roleCount)", count($bad) === 0);
    showList('bukan 403', $bad);

    echo "5. Penembusan: IDOR VENDOR02 → setiap rekod VENDOR01 (setiap endpoint vendor)\n";
    // Data VENDOR01 untuk disasarkan: pesanan dibuat oleh ibu bapa A di SK Taman Melati
    $productV1 = (int) Database::fetchValue("SELECT p.id FROM products p JOIN stock_levels s ON s.product_id = p.id
        WHERE p.vendor_id = 1 AND p.cooperative_id = 1 AND p.is_active = 1 AND p.has_variants = 0 AND s.qty_on_hand - s.qty_reserved > 5 ORDER BY p.id LIMIT 1");
    $childA = (int) Database::fetchValue("SELECT id FROM children WHERE customer_id = 1 AND cooperative_id = 1 ORDER BY id LIMIT 1");
    $parentA->post('store/cart/add', ['slug' => 'sk-taman-melati', 'product_id' => $productV1, 'qty' => 1, 'child_id' => $childA]);
    $orderA = $parentA->post('store/checkout/place', ['slug' => 'sk-taman-melati', 'pickup_by_type' => 'student', 'payment_method' => 'cash']);
    $orderAId = ok($orderA) ? (int) $orderA['data']['order_id'] : 0;
    $parentA->post('store/cart/add', ['slug' => 'sk-taman-melati', 'product_id' => $productV1, 'qty' => 1, 'child_id' => $childA]);
    check('Pesanan & troli ibu bapa A di koperasi VENDOR01 disediakan untuk sasaran', $orderAId > 0);

    $idTables = ['products', 'product_variants', 'categories', 'school_classes', 'school_kits', 'school_kit_items', 'suppliers', 'purchase_orders',
        'purchase_order_items', 'stock_takes', 'promotions', 'orders', 'order_items', 'payments', 'refunds', 'vendor_users', 'news_posts',
        'clearance_sales', 'clearance_items', 'calendar_events', 'preorder_seasons', 'pickup_slots', 'pos_shifts', 'invoices', 'children',
        'cooperatives', 'carts', 'notification_queue', 'stock_movements'];
    $v1Ids = [];
    foreach ($idTables as $table) {
        $id = firstId($table, 'vendor_id = ?', [1]);
        if ($id > 0) {
            $v1Ids[$table] = $id;
        }
    }
    $named = function (array $ids) {
        $get = function ($table) use ($ids) {
            return $ids[$table] ?? 999999;
        };
        $items = [['product_id' => $get('products'), 'variant_id' => $get('product_variants'), 'qty' => 1, 'item_id' => $get('order_items'),
            'order_item_id' => $get('order_items'), 'id' => $get('school_kit_items'), 'counted_qty' => 1, 'unit_cost' => 1]];
        return [
            'product_id' => $get('products'), 'variant_id' => $get('product_variants'), 'category_id' => $get('categories'),
            'school_class_id' => $get('school_classes'), 'class_id' => $get('school_classes'), 'kit_id' => $get('school_kits'),
            'supplier_id' => $get('suppliers'), 'purchase_order_id' => $get('purchase_orders'), 'stock_take_id' => $get('stock_takes'),
            'promotion_id' => $get('promotions'), 'order_id' => $get('orders'), 'payment_id' => $get('payments'), 'refund_id' => $get('refunds'),
            'user_id' => $get('vendor_users'), 'staff_id' => $get('vendor_users'), 'news_id' => $get('news_posts'), 'sale_id' => $get('clearance_sales'),
            'item_id' => $get('clearance_items'), 'event_id' => $get('calendar_events'), 'season_id' => $get('preorder_seasons'),
            'slot_id' => $get('pickup_slots'), 'pickup_slot_id' => $get('pickup_slots'), 'shift_id' => $get('pos_shifts'),
            'invoice_id' => $get('invoices'), 'child_id' => $get('children'), 'vendor_id' => 1, 'customer_id' => 1,
            'items' => $items, 'lines' => $items, 'product_ids' => [$get('products')], 'ids' => [$get('orders')], 'order_ids' => [$get('orders')],
            'cooperative_ids' => [1], 'from_year' => (int) date('Y'), 'school_year' => (int) date('Y'),
        ];
    };
    $vendorEndpoints = inArea($endpoints, 'vendor');
    $before = vendorFingerprint(1);
    // Pusingan 1: cooperative_id koperasi VENDOR01 → mesti 404 (403 bila peranan / PIN POS diperiksa dahulu)
    $round1 = sweep($v2, array_filter($vendorEndpoints, function ($ep) { return $ep['coop']; }), [['cooperative_id' => 1, 'id' => $orderAId] + $named($v1Ids)]);
    $not404 = [];
    foreach ($round1['statuses'] as $route => $statuses) {
        foreach ($statuses as $status) {
            if ($status !== 404 && !($status === 403 && !empty($endpoints[$route]['cashier']))) {
                $not404[] = "$route=$status";
            }
        }
    }
    check('VENDOR02 + cooperative_id VENDOR01 → 404 pada setiap endpoint berskop koperasi (' . count($round1['statuses']) . ')', count($not404) === 0);
    showList('bukan 404', $not404);
    // Pusingan 2: koperasi sendiri (2 & 3) + id setiap jadual VENDOR01 sebagai `id` + semua parameter *_id bernama
    $sets = [];
    foreach ([2, 3] as $coop) {
        foreach ($v1Ids as $table => $id) {
            $sets[] = ['cooperative_id' => $coop, 'id' => $id] + $named($v1Ids);
        }
    }
    $v1Canaries = array_merge(['niagasekolah', 'Taman Melati'], array_column(Database::fetchAll(
        "SELECT name FROM products WHERE vendor_id = 1 AND name NOT IN (SELECT name FROM products WHERE vendor_id = 2)"), 'name'),
        array_column(Database::fetchAll("SELECT full_name FROM children WHERE vendor_id = 1"), 'full_name'),
        array_column(Database::fetchAll("SELECT company_name FROM vendors WHERE id = 1"), 'company_name'));
    $round2 = sweep($v2, $vendorEndpoints, $sets, $v1Canaries);
    $after = vendorFingerprint(1);
    $okCount = 0;
    foreach ($round2['statuses'] as $statuses) {
        $okCount += count(array_filter($statuses, function ($status) { return $status === 200; }));
    }
    $changed = array_keys(array_diff_assoc($before, $after));
    check('IDOR VENDOR02: ' . $round2['count'] . ' permintaan (' . count($vendorEndpoints) . ' endpoint × ' . count($sets)
        . ' set id VENDOR01, ' . $okCount . ' jawapan 200 pada data sendiri) — tiada satu pun baris VENDOR01 berubah (' . count($before) . ' jadual)',
        count($changed) === 0);
    showList('jadual berubah', $changed);
    check('IDOR VENDOR02: tiada data VENDOR01 (nama produk / anak / koperasi / syarikat / e-mel kakitangan) dalam jawapan', count($round2['leaks']) === 0);
    showList('bocor', $round2['leaks']);
    check('IDOR VENDOR02: tiada ralat 500', count($round1['errors']) + count($round2['errors']) === 0);
    showList('500', array_merge($round1['errors'], $round2['errors']));
    check('Sesi VENDOR02 masih sah selepas sapuan (keputusan tidak dipalsukan oleh log keluar)', ok($v2->get('vendor/staff/list')));

    echo "6. Penembusan: ibu bapa B → rekod ibu bapa A (setiap endpoint store)\n";
    $aIds = [
        'orders' => $orderAId, 'children' => $childA,
        'cart_items' => (int) Database::fetchValue("SELECT ci.id FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.customer_id = 1 LIMIT 1"),
        'order_items' => (int) Database::fetchValue("SELECT id FROM order_items WHERE order_id = ?", [$orderAId]),
    ];
    $storeSets = [];
    foreach (['sk-taman-melati', 'sjkc-chung-hwa', 'smk-seri-indah'] as $slug) {
        foreach ($aIds as $id) {
            $storeSets[] = ['slug' => $slug, 'school_slug' => $slug, 'id' => $id, 'order_id' => $orderAId, 'child_id' => $childA,
                'item_id' => $aIds['cart_items'], 'cart_item_id' => $aIds['cart_items'], 'product_id' => $productV1, 'qty' => 1,
                'customer_id' => 1, 'children' => [['id' => $childA, 'grade_level_code' => 'D2']], 'child_ids' => [$childA], 'full_name' => 'Ujian'];
        }
    }
    $aCanaries = array_merge(['siti@contoh.my', '0123456701', '012-3456701'],
        array_column(Database::fetchAll("SELECT full_name FROM children WHERE customer_id = 1"), 'full_name'),
        [Database::fetchValue("SELECT order_no FROM orders WHERE id = ?", [$orderAId]) . '"']);
    $beforeA = customerFingerprint(1);
    $storeEndpoints = array_filter(inArea($endpoints, 'store'), function ($ep) { return $ep['route'] !== 'store/profile/delete'; });
    $round3 = sweep($parentB, $storeEndpoints, $storeSets, $aCanaries);
    check('IDOR ibu bapa B: ' . $round3['count'] . ' permintaan (' . count($storeEndpoints) . ' endpoint × ' . count($storeSets)
        . ' set) — akaun, anak, troli, pesanan & token ibu bapa A tidak berubah', customerFingerprint(1) === $beforeA);
    check('IDOR ibu bapa B: tiada nama anak / e-mel / telefon / no. pesanan A dalam jawapan', count($round3['leaks']) === 0);
    showList('bocor', $round3['leaks']);
    check('IDOR ibu bapa B: tiada ralat 500', count($round3['errors']) === 0);
    showList('500', $round3['errors']);
    $orderStatus = $parentB->raw('GET', 'store/orders/get', ['slug' => 'sk-taman-melati', 'id' => $orderAId])['status'];
    check('Contoh: store/orders/get pesanan A sebagai B → 404', $orderStatus === 404);

    echo "7. Penembusan: SQLi pada setiap parameter (owner, vendor, store)\n";
    $payloads = ["' OR '1'='1' -- ", '1 OR 1=1', "1; DROP TABLE orders; --", "x' UNION SELECT password_hash FROM platform_users -- ", '%', '\\'];
    $sqliSets = [];
    foreach ($payloads as $payload) {
        $sqliSets[] = ['q' => $payload, 'search' => $payload, 'status' => $payload, 'sort' => $payload, 'order' => $payload, 'date_from' => $payload,
            'date_to' => $payload, 'type' => $payload, 'group' => $payload, 'missing' => $payload, 'grade' => $payload, 'grade_level_code' => $payload,
            'class_name' => $payload, 'code' => $payload, 'slug' => $payload, 'id' => $payload, 'page' => $payload, 'per_page' => $payload,
            'cooperative_id' => $payload, 'channel' => $payload, 'method' => $payload, 'actor_type' => $payload, 'category_id' => $payload,
            'year' => $payload, 'school_year' => $payload, 'name' => $payload, 'email' => $payload, 'template_key' => $payload,
            'language_code' => $payload, 'key' => $payload];
    }
    $getOnly = function ($area) use ($endpoints) {
        return array_filter(inArea($endpoints, $area), function ($ep) { return $ep['method'] === 'GET'; });
    };
    $sqli = [];
    $countSqli = 0;
    foreach (['owner' => $owner, 'vendor' => $v1, 'store' => $parentA] as $area => $browser) {
        $result = sweep($browser, $getOnly($area), $sqliSets, ['$argon2id', 'SQLSTATE', 'You have an error in your SQL', 'PDOException']);
        $countSqli += $result['count'];
        $sqli = array_merge($sqli, $result['errors'], $result['leaks']);
    }
    $vendorPosts = array_filter(inArea($endpoints, 'vendor'), function ($ep) { return $ep['method'] === 'POST'; });
    $result = sweep($v2, $vendorPosts, [$sqliSets[0] + ['cooperative_id' => 2], $sqliSets[3] + ['cooperative_id' => 3]],
        ['$argon2id', 'SQLSTATE', 'PDOException']);
    $countSqli += $result['count'];
    $sqli = array_merge($sqli, $result['errors'], $result['leaks']);
    check("SQLi: $countSqli permintaan (GET 3 kawasan × 6 muatan + POST vendor) — tiada 500, tiada hash / ralat SQL dalam jawapan", count($sqli) === 0);
    showList('masalah', $sqli);
    check('Jadual masih wujud selepas muatan DROP TABLE', (int) Database::fetchValue("SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name IN ('orders', 'platform_users')") === 2);

    echo "8. Modul terjemahan (owner/translations — jadual translations → storage/lang/*.php)\n";
    $list = $owner->get('owner/translations/list', ['per_page' => 20]);
    $sourceKeys = count(Lang::sourceStrings('ms'));
    check('Senarai: semua kunci lang/*.php (' . $sourceKeys . '), 20 sehalaman, kumpulan, missing_counts 4 bahasa = 0', ok($list)
        && $list['data']['total'] >= $sourceKeys && count($list['data']['items']) === 20 && in_array('menu', $list['data']['groups'], true)
        && $list['data']['missing_counts'] === ['ms' => 0, 'en' => 0, 'zh' => 0, 'ta' => 0]);
    $item = $list['data']['items'][0] ?? [];
    check('Bentuk item { key, group, values{ms,en,zh,ta}, missing[] }', isset($item['key'], $item['group'], $item['values']['ta'], $item['missing']));
    $found = $owner->get('owner/translations/list', ['q' => 'simpan produk']);
    check('Cari teks (tanpa huruf besar/kecil) → activity.action.product_save', ok($found)
        && in_array('activity.action.product_save', array_column($found['data']['items'], 'key'), true));
    $group = $owner->get('owner/translations/list', ['group' => 'btn', 'per_page' => 100]);
    check('Tapis kumpulan btn → hanya kunci btn.*', ok($group) && $group['data']['total'] > 0
        && count(array_filter($group['data']['items'], function ($row) { return strpos($row['key'], 'btn.') !== 0; })) === 0);
    $source = ['ms' => Lang::fileValue('ms', 'btn.save'), 'en' => Lang::fileValue('en', 'btn.save'), 'zh' => Lang::fileValue('zh', 'btn.save'),
        'ta' => Lang::fileValue('ta', 'btn.save')];
    $res = $owner->post('owner/translations/save', ['key' => 'btn.save', 'values' => ['ms' => '  '] + $source]);
    check('Bahasa Melayu kosong → 422 translation.ms_required', !ok($res) && $res['error'] === t('translation.ms_required') && isset($res['errors']['values.ms']));
    check('Kunci tiada → 404', $owner->postStatus('owner/translations/save', ['key' => 'tiada.kunci.ini', 'values' => $source]) === 404);
    $res = $owner->post('owner/translations/save', ['key' => 'pickup.collected_by', 'values' => ['ms' => Lang::fileValue('ms', 'pickup.collected_by'),
        'en' => 'Collected by {name} {bogus}', 'zh' => '', 'ta' => '']]);
    check('Pemegang tempat tidak wujud dalam teks Melayu asal ({bogus}) → 422 pada values.en', !ok($res) && isset($res['errors']['values.en'])
        && strpos($res['errors']['values.en'], '{bogus}') !== false);
    $res = $owner->post('owner/translations/save', ['key' => 'btn.save', 'values' => ['en' => 'Save now'] + $source]);
    check('Simpan en "Save now" → baris translations (en sahaja; bahasa sama dengan fail tidak disimpan)', ok($res) && $res['data']['values']['en'] === 'Save now'
        && Database::fetchAll("SELECT language_code FROM translations WHERE translation_key = 'btn.save'") === [['language_code' => 'en']]);
    $compiled = require LangFiles::compiledPath('en');
    check('storage/lang/en.php dijana semula dengan nilai owner; lang/en.php (sumber) tidak berubah', ($compiled['btn.save'] ?? '') === 'Save now'
        && Lang::fileValue('en', 'btn.save') === $source['en']);
    $en = new Browser($base);
    $enPage = $en->raw('GET', 'store/index.php', ['lang' => 'en']);
    check('Halaman (lang=en) memakai nilai owner dalam T() (data-lang)', strpos($enPage['body'], 'Save now') !== false);
    check('Log audit translation.update', (int) Database::fetchValue("SELECT COUNT(*) FROM audit_logs WHERE action = 'translation.update' AND detail_json LIKE '%btn.save%'") >= 1);
    $res = $owner->post('owner/translations/save', ['key' => 'btn.save', 'values' => ['en' => '<script>alert(1)</script>"><img src=x onerror=alert(2)>'] + $source]);
    $enPage = $en->raw('GET', 'store/index.php');
    check('XSS dalam terjemahan: disimpan, tetapi halaman memapar ber-escape (tiada <script>alert / <img src=x mentah)', ok($res)
        && strpos($enPage['body'], '<script>alert(1)') === false && strpos($enPage['body'], '<img src=x') === false
        && strpos($enPage['body'], '&lt;script&gt;alert(1)') !== false);
    $res = $owner->post('owner/translations/save', ['key' => 'btn.save', 'values' => $source]);
    check('Simpan nilai sama dengan fail → baris dibuang (kembali ke lang/*.php)', ok($res)
        && !Database::fetchValue("SELECT 1 FROM translations WHERE translation_key = 'btn.save'") && (require LangFiles::compiledPath('en'))['btn.save'] === $source['en']);
    $description = TranslationValues::get('module.news.description');
    $res = $owner->post('owner/translations/save', ['key' => 'module.news.description', 'values' => ['zh' => ''] + $description]);
    $missingZh = $owner->get('owner/translations/list', ['missing' => 'zh']);
    $missingJob = (new MissingKeysJob())->run();
    check('Kunci DB sahaja dikosongkan (zh) → MissingKeysJob & tapis missing=zh menyenaraikannya', ok($res) && $missingJob['keys']['zh'] === ['module.news.description']
        && ok($missingZh) && array_column($missingZh['data']['items'], 'key') === ['module.news.description'] && $missingZh['data']['missing_counts']['zh'] === 1);
    check('Tapis missing=any juga menyenaraikannya; missing=en kosong', $owner->get('owner/translations/list', ['missing' => 'any'])['data']['total'] === 1
        && $owner->get('owner/translations/list', ['missing' => 'en'])['data']['total'] === 0);
    $owner->post('owner/translations/save', ['key' => 'module.news.description', 'values' => $description]);
    check('Nilai zh dipulihkan → tiada kunci hilang', (new MissingKeysJob())->run()['counts']['zh'] === 0);
    $grades = $owner->get('owner/grades/list')['data'];
    $d1 = array_values(array_filter($grades, function ($grade) { return $grade['code'] === 'D1'; }))[0];
    $res = $owner->post('owner/grades/save', ['code' => 'D1', 'level_type' => $d1['level_type'], 'sort_order' => $d1['sort_order'],
        'names' => ['en' => 'Year One (owner)'] + $d1['names']]);
    $vendorPage = $v1->raw('GET', 'vendor/products.php', ['lang' => 'en']);
    check('Nama darjah owner (grades/save) → fail bahasa dijana → halaman vendor memakainya (sebelum B14 hanya lang/*.php)', ok($res)
        && strpos($vendorPage['body'], 'Year One (owner)') !== false);
    $owner->post('owner/grades/save', ['code' => 'D1', 'level_type' => $d1['level_type'], 'sort_order' => $d1['sort_order'], 'names' => $d1['names']]);
    $v1->raw('GET', 'vendor/products.php', ['lang' => 'ms']);
    touch(APP_ROOT . '/lang/ms.php', time() + 5);
    clearstatcache();
    $owner->post('owner/translations/save', ['key' => 'btn.save', 'values' => ['en' => 'Save again'] + $source]);
    touch(APP_ROOT . '/lang/ms.php', time() + 60);
    clearstatcache();
    $stale = !LangFiles::isFresh('ms');
    $guest->raw('GET', 'store/index.php');
    clearstatcache();
    check('lang/ms.php lebih baharu (deploy) → permintaan seterusnya menjana semula; nilai owner kekal', $stale && LangFiles::isFresh('ms')
        && (require LangFiles::compiledPath('en'))['btn.save'] === 'Save again');
    touch(APP_ROOT . '/lang/ms.php', $langMtime);
    $owner->post('owner/translations/save', ['key' => 'btn.save', 'values' => $source]);
    $compiledCode = file_get_contents(LangFiles::compiledPath('en'));
    check('Fail jana = var_export (return array) sahaja, tanpa kod lain', strpos($compiledCode, "return array (") !== false
        && substr_count($compiledCode, '<?php') === 1 && strpos($compiledCode, '$') === false);
    $api = file_get_contents(APP_ROOT . '/assets/js/api.js');
    check('Mock dibuang: assets/js/mock/ tiada, api.js tanpa Mock, use_mock tiada dalam config', !is_dir(APP_ROOT . '/assets/js/mock')
        && strpos($api, 'Mock.') === false && Config::get('use_mock') === null
        && strpos($owner->raw('GET', 'owner/translations.php')['body'], 'js/mock/') === false);

    echo "9. Muat naik gambar (finfo, 2MB, dimensi, kod semula GD, nama hash, folder vendor)\n";
    $product = $v1->get('vendor/products/get', ['cooperative_id' => 1, 'id' => $productV1])['data'];
    $upload = function ($dataUrl) use ($v1, $product) {
        return $v1->post('vendor/products/save', array_merge($product, ['cooperative_id' => 1, 'image_url' => $dataUrl]));
    };
    $gdImage = function ($width, $height, $type) {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 15, 118, 110));
        ob_start();
        $type === 'png' ? imagepng($image) : imagejpeg($image, null, 90);
        imagedestroy($image);
        return ob_get_clean();
    };
    $polyglot = $gdImage(8, 8, 'png') . "<?php system('id'); ?><script>alert(1)</script>";
    $res = $upload('data:image/png;base64,' . base64_encode($polyglot));
    $path = (string) Database::fetchValue("SELECT image_path FROM products WHERE id = ?", [$productV1]);
    $stored = (string) @file_get_contents(APP_ROOT . '/uploads/' . $path);
    check('PNG + kod PHP/JS selepas penamat fail → diterima tetapi dikod semula (tiada <?php / <script> dalam fail)', ok($res) && $stored !== ''
        && strpos($stored, '<?php') === false && strpos($stored, '<script') === false);
    check('Laluan uploads/1/1/products/{sha256}.png (folder vendor/koperasi, nama hash)', (bool) preg_match('#^1/1/products/[a-f0-9]{64}\.png$#', $path)
        && (new finfo(FILEINFO_MIME_TYPE))->file(APP_ROOT . '/uploads/' . $path) === 'image/png');
    $jpeg = $gdImage(8, 8, 'jpeg');
    $exif = "Exif\0\0" . 'MM' . str_repeat("\0", 6) . 'GPSLatitude 3.1390 N GPSLongitude 101.6869 E';
    $withExif = substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif . substr($jpeg, 2);
    $res = $upload('data:image/jpeg;base64,' . base64_encode($withExif));
    $path = (string) Database::fetchValue("SELECT image_path FROM products WHERE id = ?", [$productV1]);
    $stored = (string) @file_get_contents(APP_ROOT . '/uploads/' . $path);
    check('JPEG dengan EXIF (lokasi GPS) → metadata dibuang (PDPA)', ok($res) && substr($path, -4) === '.jpg' && strpos($stored, 'GPSLatitude') === false
        && strpos($stored, 'Exif') === false);
    $res = $upload('data:image/png;base64,' . base64_encode($gdImage(4100, 10, 'png')));
    check('Dimensi > 4000 piksel → upload.too_many_pixels', !ok($res) && ($res['errors']['image_url'] ?? $res['errors']['image'] ?? $res['error'] ?? '')
        === t('upload.too_many_pixels', ['max' => 4000]));
    $res = $upload('data:image/png;base64,' . base64_encode("<?php echo 'x'; ?>"));
    check('data:image/png tetapi kandungan PHP → upload.invalid_type (finfo, bukan data URL)', !ok($res) && strpos(json_encode($res), json_encode(t('upload.invalid_type'))) !== false);
    $res = $upload('data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'));
    check('SVG (boleh membawa skrip) → ditolak', !ok($res) && strpos(json_encode($res), json_encode(t('upload.invalid_type'))) !== false);
    $res = $upload('data:image/png;base64,' . base64_encode(str_repeat('A', 2200000)));
    check('> 2MB → upload.too_large', !ok($res) && strpos(json_encode($res), json_encode(t('upload.too_large', ['max' => '2MB']))) !== false);

    echo "10. Token (reset, sahkan e-mel, API) — hash SHA-256 + tarikh luput\n";
    $tokenTables = ['customer_tokens', 'vendor_user_tokens', 'platform_user_tokens', 'api_tokens'];
    $columns = [];
    foreach ($tokenTables as $table) {
        $columns[$table] = array_column(Database::fetchAll("SELECT column_name AS c, column_type AS t FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ?", [$table]), 't', 'c');
    }
    check('Jadual token: token_hash CHAR(64) + expires_at (tiada lajur token mentah)', count(array_filter($columns, function ($cols) {
        return ($cols['token_hash'] ?? '') === 'char(64)' && isset($cols['expires_at']) && !isset($cols['token']);
    })) === 4);
    clearTestLocks();
    $forgot = new Browser($base);
    $forgot->page('store/index.php?page=forgot-password');
    $forgot->post('store/auth/forgot', ['email' => 'rajesh@contoh.my']);
    $mail = (string) Database::fetchValue("SELECT body_html FROM email_queue WHERE to_email = 'rajesh@contoh.my' AND template_key = 'reset_password' ORDER BY id DESC LIMIT 1");
    preg_match('/token=([A-Za-z0-9_-]{20,})/', $mail, $tokenMatch);
    $raw = $tokenMatch[1] ?? '';
    $row = Database::fetchOne("SELECT * FROM customer_tokens WHERE customer_id = 2 AND type = 'reset_password' ORDER BY id DESC LIMIT 1");
    check('Lupa kata laluan: e-mel memuat token mentah; DB hanya sha256(token), luput ≤ 1 jam, token tiada dalam DB', $raw !== '' && $row !== null
        && hash_equals($row['token_hash'], hash('sha256', $raw)) && strtotime($row['expires_at']) <= time() + 3700
        && !Database::fetchValue("SELECT 1 FROM customer_tokens WHERE token_hash = ?", [$raw]));
    $reset = $forgot->post('store/auth/reset', ['token' => $raw, 'password' => 'BaharuRajesh1', 'password_confirm' => 'BaharuRajesh1']);
    $again = $forgot->post('store/auth/reset', ['token' => $raw, 'password' => 'LainLagi12345', 'password_confirm' => 'LainLagi12345']);
    check('Token sekali guna: reset berjaya, kali kedua ditolak', ok($reset) && !ok($again));
    Database::execute("UPDATE customer_tokens SET expires_at = NOW() - INTERVAL 1 MINUTE WHERE customer_id = 2");
    $forgot->post('store/auth/forgot', ['email' => 'rajesh@contoh.my']);
    $mail = (string) Database::fetchValue("SELECT body_html FROM email_queue WHERE to_email = 'rajesh@contoh.my' AND template_key = 'reset_password' ORDER BY id DESC LIMIT 1");
    preg_match('/token=([A-Za-z0-9_-]{20,})/', $mail, $tokenMatch);
    Database::execute("UPDATE customer_tokens SET expires_at = NOW() - INTERVAL 1 MINUTE WHERE customer_id = 2");
    check('Token luput → ditolak', !ok($forgot->post('store/auth/reset', ['token' => $tokenMatch[1] ?? 'x', 'password' => 'LainLagi12345', 'password_confirm' => 'LainLagi12345'])));
    $secrets = Database::fetchAll("SELECT credentials_encrypted FROM cooperative_gateways WHERE credentials_encrypted IS NOT NULL LIMIT 3");
    check('Kredensial gateway disulitkan (bukan JSON mentah)', count(array_filter($secrets, function ($row) {
        return json_decode((string) $row['credentials_encrypted'], true) !== null;
    })) === 0);
    check('Rahsia TOTP owner disulitkan (bukan base32 mentah)', count(array_filter(Database::fetchAll("SELECT totp_secret FROM platform_users WHERE totp_secret IS NOT NULL"),
        function ($row) { return preg_match('/^[A-Z2-7]{16,}$/', (string) $row['totp_secret']); })) === 0);

    echo "11. PDPA — padam akaun ibu bapa (DeleteCustomerAccountJob — nyahnama, jualan vendor kekal)\n";
    $template = Database::fetchOne("SELECT * FROM children WHERE id = ?", [$childA]);
    $customerC = Database::insert("INSERT INTO customers (full_name, phone, email, password_hash, relationship, language, email_verified_at, pdpa_consent_at)
        VALUES ('Puan Pdpa Ujian', '0175550101', 'pdpa.ujian@contoh.my', ?, 'mother', 'ms', NOW(), NOW())", [Password::hash('Demo@12345')]);
    $childC = Database::insert("INSERT INTO children (customer_id, cooperative_id, vendor_id, full_name, grade_level_code, school_class_id, class_name, school_year)
        VALUES (?, 1, 1, 'Nur Iman Pdpa', ?, ?, ?, ?)", [$customerC, $template['grade_level_code'], $template['school_class_id'], $template['class_name'], $template['school_year']]);
    list($parentC) = storeLogin($base, 'pdpa.ujian@contoh.my');
    $parentC->post('store/cart/add', ['slug' => 'sk-taman-melati', 'product_id' => $productV1, 'qty' => 2, 'child_id' => $childC]);
    $placed = $parentC->post('store/checkout/place', ['slug' => 'sk-taman-melati', 'pickup_by_type' => 'proxy', 'pickup_by_name' => 'Pak Long Pdpa',
        'payment_method' => 'cash']);
    $orderC = ok($placed) ? (int) $placed['data']['order_id'] : 0;
    $v1->post('vendor/orders/set-status', ['cooperative_id' => 1, 'id' => $orderC, 'status' => 'preparing']);
    $v1->post('vendor/orders/set-status', ['cooperative_id' => 1, 'id' => $orderC, 'status' => 'ready']);
    $total = (int) Database::fetchValue("SELECT total FROM orders WHERE id = ?", [$orderC]);
    $cashier1->post('vendor/handover/receive-cash', ['cooperative_id' => 1, 'id' => $orderC, 'amount_received' => $total]);
    $itemC = (int) Database::fetchValue("SELECT id FROM order_items WHERE order_id = ?", [$orderC]);
    $collect = $cashier1->post('vendor/handover/collect', ['cooperative_id' => 1, 'id' => $orderC, 'picked_by_type' => 'proxy',
        'picked_by_name' => 'Pak Long Pdpa', 'items' => [['item_id' => $itemC, 'qty' => 2]]]);
    Database::insert("INSERT INTO notification_queue (vendor_id, cooperative_id, order_id, channel, event, recipient, message, status)
        VALUES (1, 1, ?, 'sms', 'order_ready', '60175550101', 'Nur Iman Pdpa: pesanan sedia', 'sent')", [$orderC]);
    Database::insert("INSERT INTO notification_queue (vendor_id, cooperative_id, order_id, channel, event, recipient, message, status)
        VALUES (1, 1, ?, 'sms', 'order_collected', '60175550101', 'Nur Iman Pdpa: diambil', 'pending')", [$orderC]);
    $parentC->raw('POST', 'store/auth/logout', []);
    storeLogin($base, 'pdpa.ujian@contoh.my', 'SalahSalah1');
    list($parentC) = storeLogin($base, 'pdpa.ujian@contoh.my');
    check('Persediaan: pesanan tunai diambil oleh proksi, e-mel, mesej & cubaan log masuk wujud', $orderC > 0 && ok($collect)
        && Database::fetchValue("SELECT status FROM orders WHERE id = ?", [$orderC]) === 'collected'
        && (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue WHERE to_email = 'pdpa.ujian@contoh.my'") > 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM login_attempts WHERE identifier = 'pdpa.ujian@contoh.my'") > 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM order_status_logs WHERE order_id = ? AND note LIKE '%Pak Long Pdpa%'", [$orderC]) > 0);
    $salesBefore = Database::fetchOne("SELECT total, subtotal, status, paid_at FROM orders WHERE id = ?", [$orderC]);
    $itemsBefore = Database::fetchAll("SELECT product_id, qty, unit_price, grade_label, class_name FROM order_items WHERE order_id = ?", [$orderC]);
    check('Kata laluan salah → ditolak', !ok($parentC->post('store/profile/delete', ['password' => 'salah'])));
    $deleted = $parentC->post('store/profile/delete', ['password' => 'Demo@12345']);
    $customer = Database::fetchOne("SELECT * FROM customers WHERE id = ?", [$customerC]);
    $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderC]);
    check('Akaun: nama "Akaun dipadam", e-mel & telefon NULL, tidak aktif, deleted_at, hash kata laluan ditukar', ok($deleted)
        && $customer['full_name'] === t('store.profile.deleted_name') && $customer['email'] === null && $customer['phone'] === null
        && (int) $customer['is_active'] === 0 && $customer['deleted_at'] !== null && !Password::verify('Demo@12345', $customer['password_hash']));
    check('Anak: nama "-", tidak aktif (tiada IC disimpan — skema tiada lajur IC)', Database::fetchValue("SELECT full_name FROM children WHERE id = ?", [$childC]) === '-'
        && !Database::fetchValue("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'children' AND column_name REGEXP 'ic|mykad|nric'"));
    check('Snapshot pesanan: nama pelanggan dinyahnama, telefon/e-mel/pengambil NULL; nama anak item & pengambil serahan "-"',
        $order['customer_name'] === t('store.profile.deleted_name') && $order['customer_phone'] === null && $order['customer_email'] === null
        && $order['pickup_by_name'] === null
        && Database::fetchValue("SELECT GROUP_CONCAT(DISTINCT child_name) FROM order_items WHERE order_id = ?", [$orderC]) === '-'
        && Database::fetchValue("SELECT GROUP_CONCAT(DISTINCT picked_by_name) FROM order_pickups WHERE order_id = ?", [$orderC]) === '-');
    check('Nota status pesanan tidak lagi memuat nama (pengambil / anak)', !Database::fetchValue(
        "SELECT 1 FROM order_status_logs WHERE order_id = ? AND (note LIKE '%Pak Long%' OR note LIKE '%Nur Iman%' OR note LIKE '%Puan Pdpa%')", [$orderC]));
    check('Mesej WhatsApp/SMS: belum dihantar dibuang, yang dihantar tanpa nombor & teks', !Database::fetchValue(
        "SELECT 1 FROM notification_queue WHERE order_id = ? AND (status = 'pending' OR recipient <> '-' OR message <> '-')", [$orderC])
        && (int) Database::fetchValue("SELECT COUNT(*) FROM notification_queue WHERE order_id = ?", [$orderC]) === 1);
    check('E-mel dalam baris gilir & cubaan log masuk dengan e-mel/telefon dibuang', !Database::fetchValue("SELECT 1 FROM email_queue WHERE to_email = 'pdpa.ujian@contoh.my' OR body_html LIKE '%Nur Iman Pdpa%'")
        && !Database::fetchValue("SELECT 1 FROM login_attempts WHERE identifier IN ('pdpa.ujian@contoh.my', '0175550101')"));
    check('Rekod jualan vendor kekal: jumlah, status, tarikh bayar, produk, kuantiti, harga, darjah & kelas sama',
        Database::fetchOne("SELECT total, subtotal, status, paid_at FROM orders WHERE id = ?", [$orderC]) === $salesBefore
        && Database::fetchAll("SELECT product_id, qty, unit_price, grade_label, class_name FROM order_items WHERE order_id = ?", [$orderC]) === $itemsBefore);
    check('Log audit customer.delete_account; sesi tamat & tidak boleh log masuk semula', (int) Database::fetchValue(
        "SELECT COUNT(*) FROM audit_logs WHERE action = 'customer.delete_account' AND actor_type = 'customer' AND actor_id = ?", [$customerC]) === 1
        && $parentC->raw('GET', 'store/profile/get')['status'] === 401 && !ok(storeLogin($base, 'pdpa.ujian@contoh.my')[1]));
    $report = $v1->get('vendor/reports/run', ['cooperative_id' => 1, 'type' => 'daily', 'date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d')]);
    check('Laporan jualan vendor masih mengira pesanan itu', ok($report) && (int) ($report['data']['totals']['net'] ?? 0) >= $total);
    $openC = Database::insert("INSERT INTO customers (full_name, phone, email, password_hash, email_verified_at, pdpa_consent_at)
        VALUES ('Encik Terbuka', '0175550102', 'terbuka@contoh.my', ?, NOW(), NOW())", [Password::hash('Demo@12345')]);
    $childOpen = Database::insert("INSERT INTO children (customer_id, cooperative_id, vendor_id, full_name, grade_level_code, school_class_id, class_name, school_year)
        VALUES (?, 1, 1, 'Anak Terbuka', ?, ?, ?, ?)", [$openC, $template['grade_level_code'], $template['school_class_id'], $template['class_name'], $template['school_year']]);
    list($parentOpen) = storeLogin($base, 'terbuka@contoh.my');
    $parentOpen->post('store/cart/add', ['slug' => 'sk-taman-melati', 'product_id' => $productV1, 'qty' => 1, 'child_id' => $childOpen]);
    $parentOpen->post('store/checkout/place', ['slug' => 'sk-taman-melati', 'pickup_by_type' => 'student', 'payment_method' => 'cash']);
    $res = $parentOpen->post('store/profile/delete', ['password' => 'Demo@12345']);
    check('Pesanan belum selesai → padam akaun ditolak (store.profile.delete_active_orders)', !ok($res) && $res['error'] === t('store.profile.delete_active_orders'));

    echo "12. Log audit tindakan sensitif (tukar harga, refund, tukar gateway, gantung vendor)\n";
    $product = $v1->get('vendor/products/get', ['cooperative_id' => 1, 'id' => $productV1])['data'];
    $oldPrice = (int) $product['price'];
    $res = $v1->post('vendor/products/save', array_merge($product, ['cooperative_id' => 1, 'price' => $oldPrice + 50]));
    $detail = (string) Database::fetchValue("SELECT detail_json FROM activity_logs WHERE vendor_id = 1 AND action = 'price_change' AND entity_id = ? ORDER BY id DESC LIMIT 1", [$productV1]);
    check('Tukar harga → log aktiviti price_change "RM lama → RM baharu"', ok($res) && strpos($detail, Money::format($oldPrice)) !== false
        && strpos($detail, Money::format($oldPrice + 50)) !== false);
    $count = (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE vendor_id = 1 AND action = 'price_change'");
    $v1->post('vendor/products/save', array_merge($product, ['cooperative_id' => 1, 'price' => $oldPrice + 50, 'name' => $product['name'] . ' ']));
    check('Simpan tanpa tukar harga → tiada log price_change baharu', (int) Database::fetchValue("SELECT COUNT(*) FROM activity_logs WHERE vendor_id = 1 AND action = 'price_change'") === $count);
    check('Label log aktiviti price_change wujud dalam 4 bahasa', Lang::fileValue('ta', 'activity.action.price_change') !== null);
    $sources = ['refund' => 'jobs/Refund/RefundOrderJob.php', 'gateway_save' => 'jobs/Payment', 'vendor.suspend' => 'jobs/Owner/SetVendorStatusJob.php',
        'gateway.owner_update' => 'jobs/Payment', 'invoice.mark_paid' => 'jobs/Billing'];
    $missing = [];
    foreach ($sources as $action => $where) {
        $code = '';
        foreach (is_dir(APP_ROOT . '/' . $where) ? glob(APP_ROOT . '/' . $where . '/*.php') : [APP_ROOT . '/' . $where] as $file) {
            $code .= file_get_contents($file);
        }
        if (strpos($code, "'" . $action . "'") === false) {
            $missing[] = $action;
        }
    }
    check('Log audit: refund, gateway_save (vendor), gateway.owner_update, vendor.suspend, invoice.mark_paid ditulis oleh job', count($missing) === 0);
    showList('tiada', $missing);
    $res = $owner->post('owner/vendors/set-status', ['id' => 2, 'status' => 'suspended', 'reason' => 'Ujian B14']);
    check('Gantung vendor → audit_logs vendor.suspend (+ IP)', ok($res) && Database::fetchValue(
        "SELECT ip_address FROM audit_logs WHERE action = 'vendor.suspend' AND vendor_id = 2 ORDER BY id DESC LIMIT 1") !== null);
    $owner->post('owner/vendors/set-status', ['id' => 2, 'status' => 'active']);

    echo "13. XSS pada nama produk & nama anak\n";
    $xss = '<img src=x onerror=alert(1)><script>alert(2)</script>"\'';
    $res = $v1->post('vendor/products/save', array_merge($product, ['cooperative_id' => 1, 'name' => $xss, 'price' => $oldPrice]));
    $childRes = $parentA->post('store/children/save', ['id' => $childA, 'full_name' => $xss, 'school_slug' => 'sk-taman-melati',
        'grade_level_code' => $template['grade_level_code'], 'class_name' => $template['class_name'], 'school_year' => $template['school_year']]);
    check('Nama produk & anak dengan HTML disimpan apa adanya (data, bukan kod)', ok($res) && ok($childRes)
        && Database::fetchValue("SELECT name FROM products WHERE id = ?", [$productV1]) === $xss);
    $pages = [[$v1, 'vendor/products.php'], [$v1, 'vendor/orders.php'], [$parentA, 'store/index.php?school=sk-taman-melati&page=catalog'],
        [$parentA, 'store/index.php?school=sk-taman-melati&page=children'], [$cashier1, 'vendor/pos/handover.php']];
    $rawFound = [];
    foreach ($pages as $item) {
        $body = $item[0]->raw('GET', $item[1])['body'];
        if (strpos($body, '<img src=x') !== false || strpos($body, '<script>alert(2)') !== false) {
            $rawFound[] = $item[1];
        }
    }
    check('Halaman PHP tidak pernah mencetak nama mentah (5 halaman)', count($rawFound) === 0);
    showList('mentah', $rawFound);
    $json = $v1->raw('GET', 'vendor/products/list', ['cooperative_id' => 1, 'q' => 'onerror']);
    check('JSON: Content-Type application/json + nosniff (pelayar tidak melaksana jawapan sebagai HTML)',
        strpos((string) header1($json, 'content-type'), 'application/json') === 0 && header1($json, 'x-content-type-options') === 'nosniff');
    $parentA->post('store/cart/add', ['slug' => 'sk-taman-melati', 'product_id' => $productV1, 'qty' => 1, 'child_id' => $childA]);
    $placed = $parentA->post('store/checkout/place', ['slug' => 'sk-taman-melati', 'pickup_by_type' => 'student', 'payment_method' => 'cash']);
    $mail = (string) Database::fetchValue("SELECT body_html FROM email_queue WHERE to_email = 'siti@contoh.my' ORDER BY id DESC LIMIT 1");
    check('E-mel resit: nama produk & anak ber-escape (&lt;img …), tiada <script> mentah', ok($placed) && strpos($mail, '&lt;img src=x') !== false
        && strpos($mail, '<img src=x') === false && strpos($mail, '<script>alert(2)') === false);
} finally {
    proc_terminate($server);
    echo "Pemulihan pangkalan data dari backup awal\n";
    check('Backup dipulihkan dengan mysql (pangkalan data kembali seperti sebelum ujian)', restoreBackup($backup['file']));
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT . '/uploads', FilesystemIterator::SKIP_DOTS)) as $file) {
        if (!isset($uploadsBefore[(string) $file])) {
            @unlink($file);
        }
    }
    array_map('unlink', glob($backupDir . '/*'));
    @rmdir($backupDir);
    touch(APP_ROOT . '/lang/ms.php', $langMtime);
    (new GenerateLangFilesJob())->run();
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
