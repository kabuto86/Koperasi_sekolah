<?php
// Semakan W5 — template store 2 (Ceria) & 3 (Klasik): folder lengkap ikut manifest, setiap halaman wajib dipapar (pratonton vendor),
// semua id / data-* yang dibaca assets/js/store/*.js wujud seperti Template 1, template dilanggan dipakai koperasi (tanpa ?template=),
// template tidak aktif / tidak dilanggan → Template 1, pratonton hanya owner / vendor pemilik, gambar pratonton sebenar. CLI sahaja:
//   php tools/verify-w5.php
// Perlu migrasi 011. Jadual yang diubah dipulihkan.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

// id & data-* dalam HTML (tanpa nilai). data-bs-* = atribut gaya/komponen Bootstrap, bukan cangkuk JS store → dikecualikan
function hooks($html)
{
    preg_match_all('/\sid="([^"]+)"/', $html, $ids);
    preg_match_all('/\s(data-(?!bs-)[a-z-]+)(?==|\s|>)/', $html, $data);
    return array_unique(array_merge(array_map(function ($id) { return '#' . $id; }, $ids[1]), $data[1]));
}

$port = 8200;
$base = 'http://127.0.0.1:' . $port;
$log = sys_get_temp_dir() . '/kop-verify-w5.log';
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4'] + getenv());
usleep(900000);

$tables = ['cooperatives', 'subscription_items', 'templates', 'login_attempts', 'rate_limits', 'platform_users', 'totp_recovery_codes', 'activity_logs', 'audit_logs'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();

try {
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $guest = new Browser($base);
    $pages = json_decode((string) file_get_contents(APP_ROOT . '/templates/template1/manifest.json'), true)['pages'];

    echo "1. Folder template\n";
    foreach (['template2', 'template3'] as $code) {
        $dir = APP_ROOT . '/templates/' . $code;
        $missing = array_filter($pages, function ($page) use ($dir) {
            return !is_file($dir . '/pages/' . $page . '.php');
        });
        check("$code: folderReady, manifest & 17 halaman wajib, css/theme.css", ResolveStoreTemplateJob::folderReady($code) && !$missing
            && is_file($dir . '/css/theme.css') && is_file($dir . '/partials/header.php') && is_file($dir . '/partials/footer.php'));
    }
    check('templates/.htaccess menyekat PHP (hanya css/js/json/gambar)', strpos((string) file_get_contents(APP_ROOT . '/templates/.htaccess'), 'Require all denied') === 0);
    $css = (string) file_get_contents(APP_ROOT . '/templates/template2/css/theme.css') . file_get_contents(APP_ROOT . '/templates/template3/css/theme.css');
    check('Tiada CDN / @import luar dalam tema', strpos($css, 'http') === false && strpos($css, '@import') === false);

    echo "2. Setiap halaman dipapar & cangkuk JS sama seperti Template 1 (pratonton vendor pemilik)\n";
    foreach (['template2', 'template3'] as $code) {
        $broken = [];
        $missingHooks = [];
        foreach ($pages as $page) {
            $url = 'store/index.php?school=sk-taman-melati&page=' . $page;
            $one = $v1->page($url . '&template=template1');
            $other = $v1->page($url . '&template=' . $code);
            if ($other['status'] !== 200 || strpos($other['body'], 'store-' . $code) === false || preg_match('/(Warning|Notice|Fatal error)/', $other['body'])) {
                $broken[] = $page;
            }
            $diff = array_diff(hooks($one['body']), hooks($other['body']));
            if ($diff) {
                $missingHooks[$page] = array_values($diff);
            }
        }
        check("$code: 17 halaman 200 tanpa ralat PHP" . ($broken ? ' — ' . implode(', ', $broken) : ''), !$broken);
        check("$code: semua id / data-* Template 1 wujud" . ($missingHooks ? ' — ' . json_encode($missingHooks) : ''), !$missingHooks);
    }
    $page = $v1->page('store/index.php?school=sk-taman-melati&page=home&template=template2');
    check('Template 2 memuat template1/css/template.css + template2/css/theme.css', strpos($page['body'], 'templates/template1/css/template.css') !== false
        && strpos($page['body'], 'templates/template2/css/theme.css') !== false);
    $page = $v1->page('store/index.php?school=sk-taman-melati&page=order-detail&template=template3');
    check('Halaman cetak (order-detail) memuat print-slip.css & qrcode.js', strpos($page['body'], 'print-slip.css') !== false && strpos($page['body'], 'qrcode.js') !== false);

    echo "3. Pilihan template koperasi & pratonton\n";
    check('Pelawat ?template=template2 → diabaikan (Template 1)', strpos($guest->page('store/index.php?school=sk-taman-melati&template=template2')['body'], 'store-template2') === false);
    check('VENDOR02 pratonton koperasi VENDOR01 → diabaikan', strpos($v2->page('store/index.php?school=sk-taman-melati&template=template3')['body'], 'store-template3') === false);
    Database::execute("UPDATE cooperatives SET template_id = 2 WHERE id = 1");
    check('Template 2 dipilih tetapi belum dilanggan → Template 1', strpos($guest->page('store/index.php?school=sk-taman-melati')['body'], 'store-template2') === false);
    $subId = (int) Database::fetchValue("SELECT id FROM subscriptions WHERE vendor_id = 1 ORDER BY id DESC LIMIT 1");
    Database::execute("INSERT INTO subscription_items (vendor_id, subscription_id, item_type, cooperative_id, ref_id, unit_price, started_at)
                       VALUES (1, ?, 'template', 1, 2, 10000, CURDATE())", [$subId]);
    $home = $guest->page('store/index.php?school=sk-taman-melati');
    check('Template 2 dilanggan → ibu bapa melihat Template 2 (tanpa ?template=)', $home['status'] === 200 && strpos($home['body'], 'store-template2') !== false);
    Database::execute("UPDATE templates SET is_active = 0 WHERE id = 2");
    check('Owner nyahaktif Template 2 → kembali ke Template 1', strpos($guest->page('store/index.php?school=sk-taman-melati')['body'], 'store-template2') === false);
    Database::execute("UPDATE templates SET is_active = 1 WHERE id = 2");
    check('Koperasi VENDOR02 tidak terjejas (Template 1)', strpos($guest->page('store/index.php?school=sjkc-chung-hwa')['body'], 'store-template') === false);

    echo "4. Gambar pratonton\n";
    list($owner) = ownerLogin($base, 'owner@koperasiku.my');
    $list = $owner->get('owner/templates/list');
    $images = array_column($list['data'] ?? [], 'preview_image', 'code');
    check('owner/templates/list: preview_image ketiga-tiga template', ok($list) && count(array_filter($images)) === 3
        && substr($images['template2'], -strlen('assets/img/templates/template2.jpg')) === 'assets/img/templates/template2.jpg');
    $allOk = true;
    foreach (['template1', 'template2', 'template3'] as $code) {
        $info = @getimagesize(APP_ROOT . '/assets/img/templates/' . $code . '.jpg');
        $allOk = $allOk && $info !== false && $info[2] === IMAGETYPE_JPEG && $info[0] === 640;
    }
    check('Fail JPEG 640 px wujud', $allOk);
    $catalog = $v1->get('vendor/addons/catalog', ['cooperative_id' => 1]);
    $vendorImages = array_column($catalog['data']['templates'] ?? [], 'preview_image', 'code');
    check('Kedai modul vendor: preview_image template aktif', ok($catalog) && !empty($vendorImages['template1']) && !empty($vendorImages['template2']));
} finally {
    proc_terminate($server);
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
