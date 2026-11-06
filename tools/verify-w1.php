<?php
// Semakan W1 — profil & 2FA kakitangan vendor: profil (semua peranan, e-mel unik), tukar kata laluan, sediakan TOTP (rahsia
// dalam sesi), kod pemulihan, log masuk 2 langkah selepas 2FA aktif, matikan 2FA (kata laluan + kod), pentadbir set semula
// 2FA kakitangan, ujian kebocoran VENDOR01 ↔ VENDOR02, halaman dibuka ketika langganan digantung. CLI sahaja:
//   php tools/verify-w1.php
// Jadual yang diubah dipulihkan di akhir.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

function ok($res)
{
    return is_array($res) && !empty($res['ok']);
}

$port = 8160;
$base = 'http://127.0.0.1:' . $port;
$log = sys_get_temp_dir() . '/kop-verify-w1.log';
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT], [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes,
    null, ['PHP_CLI_SERVER_WORKERS' => '4'] + getenv());
usleep(900000);

$tables = ['vendor_users', 'totp_recovery_codes', 'activity_logs', 'vendor_user_tokens', 'vendors'];
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = Database::fetchAll("SELECT * FROM $table");
}
clearTestLocks();

try {
    echo "1. Profil vendor\n";
    $v1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $page = $v1->page('vendor/profile.php');
    check('vendor/profile.php 200 (pautan menu profil tidak rosak lagi)', $page['status'] === 200 && strpos($page['body'], 'profile-form') !== false);
    $res = $v1->get('vendor/profile/get');
    check('profile/get: data diri + syarikat + peranan, tiada hash', ok($res) && $res['data']['email'] === 'ahmad@niagasekolah.my'
        && $res['data']['company_name'] === 'Niaga Sekolah Sdn Bhd' && $res['data']['role'] === 'vendor_admin'
        && !isset($res['data']['password_hash']) && !isset($res['data']['totp_secret']));
    $res = $v1->post('vendor/profile/save', ['full_name' => 'Ahmad Ismail', 'email' => 'meiling@bestari.my', 'phone' => '', 'language' => 'en']);
    check('E-mel milik kakitangan lain (vendor lain) ditolak', !ok($res) && isset($res['errors']['email']));
    $res = $v1->post('vendor/profile/save', ['full_name' => '', 'email' => 'x', 'language' => 'xx']);
    check('Nama kosong, e-mel & bahasa tidak sah ditolak', !ok($res) && isset($res['errors']['full_name'], $res['errors']['email'], $res['errors']['language']));
    $res = $v1->post('vendor/profile/save', ['full_name' => 'Ahmad Ismail', 'email' => 'AHMAD@niagasekolah.my', 'phone' => '012-3456789', 'language' => 'en',
        'role' => 'cashier', 'vendor_id' => 2]);
    $row = Database::fetchOne("SELECT * FROM vendor_users WHERE id = 1");
    check('Simpan: nama & bahasa berubah, e-mel huruf kecil; role/vendor_id dari input diabaikan', ok($res) && $row['full_name'] === 'Ahmad Ismail'
        && $row['language'] === 'en' && $row['email'] === 'ahmad@niagasekolah.my' && $row['role'] === 'vendor_admin' && (int) $row['vendor_id'] === 1);
    check('Log aktiviti profile_update', (bool) Database::fetchValue("SELECT 1 FROM activity_logs WHERE vendor_id = 1 AND action = 'profile_update' AND vendor_user_id = 1"));
    $res = $v1->post('vendor/profile/save', ['full_name' => 'X'], false);
    check('Tanpa CSRF → ditolak', !ok($res));

    $cashier = vendorLogin($base, 'kasim@niagasekolah.my');
    check('Juruwang boleh membuka profil sendiri', $cashier->page('vendor/profile.php')['status'] === 200
        && ok($res = $cashier->get('vendor/profile/get')) && $res['data']['email'] === 'kasim@niagasekolah.my');
    $guest = new Browser($base);
    check('Tanpa log masuk → 401 / redirect', $guest->getStatus('vendor/profile/get') === 401
        && $guest->page('vendor/profile.php')['status'] === 302);

    echo "2. Tukar kata laluan\n";
    $res = $cashier->post('vendor/profile/change-password', ['current_password' => 'salah', 'new_password' => 'BaharuKuat@2026']);
    check('Kata laluan semasa salah ditolak', !ok($res) && isset($res['errors']['current_password']));
    $res = $cashier->post('vendor/profile/change-password', ['current_password' => 'Demo@12345', 'new_password' => 'pendek']);
    check('Kata laluan baharu < 10 aksara ditolak', !ok($res) && isset($res['errors']['new_password']));
    AuthToken::issue('vendor', 2, 'reset_password', 3600, 1);
    $res = $cashier->post('vendor/profile/change-password', ['current_password' => 'Demo@12345', 'new_password' => 'BaharuKuat@2026']);
    $hash = Database::fetchValue("SELECT password_hash FROM vendor_users WHERE id = 2");
    check('Kata laluan ditukar (Argon2id) & pautan reset lama dibatalkan', ok($res) && Password::verify('BaharuKuat@2026', $hash)
        && strpos($hash, '$argon2id$') === 0
        && (int) Database::fetchValue("SELECT COUNT(*) FROM vendor_user_tokens WHERE vendor_user_id = 2 AND used_at IS NULL") === 0);
    $login = new Browser($base);
    $login->page('vendor/login.php');
    check('Log masuk dengan kata laluan lama gagal, baharu berjaya', !ok($login->post('vendor/auth/login', ['email' => 'kasim@niagasekolah.my', 'password' => 'Demo@12345']))
        && ok($login->post('vendor/auth/login', ['email' => 'kasim@niagasekolah.my', 'password' => 'BaharuKuat@2026'])));
    clearTestLocks();

    echo "3. Sediakan 2FA\n";
    $page = $v1->page('vendor/two-factor.php');
    check('vendor/two-factor.php 200', $page['status'] === 200 && strpos($page['body'], 'enable-form') !== false);
    $setup = $v1->get('vendor/two-factor/setup');
    check('setup: belum aktif, rahsia + otpauth', ok($setup) && $setup['data']['enabled'] === false && strlen($setup['data']['secret']) >= 16
        && strpos($setup['data']['otpauth_url'], 'otpauth://totp/') === 0);
    $secret = $setup['data']['secret'];
    check('Rahsia BELUM disimpan dalam DB sebelum disahkan', Database::fetchValue("SELECT totp_secret FROM vendor_users WHERE id = 1") === null);
    $res = $v1->post('vendor/two-factor/enable', ['code' => '000000']);
    check('Kod salah → ditolak', !ok($res));
    $other = vendorLogin($base, 'meiling@bestari.my');
    $res = $other->post('vendor/two-factor/enable', ['code' => totpNow($secret)]);
    check('Sesi lain (VENDOR02) tidak boleh guna rahsia sesi VENDOR01', !ok($res)
        && Database::fetchValue("SELECT totp_secret FROM vendor_users WHERE id = 3") === null);
    $res = $v1->post('vendor/two-factor/enable', ['code' => totpNow($secret)]);
    $stored = Database::fetchValue("SELECT totp_secret FROM vendor_users WHERE id = 1");
    check('Kod betul → aktif, 8 kod pemulihan, rahsia disulitkan', ok($res) && count($res['data']['recovery_codes']) === 8
        && $stored !== null && $stored !== $secret && Crypto::decrypt($stored) === $secret);
    $codes = $res['data']['recovery_codes'];
    check('Kod pemulihan disimpan sebagai hash', (int) Database::fetchValue("SELECT COUNT(*) FROM totp_recovery_codes WHERE user_type = 'vendor_user' AND user_id = 1") === 8
        && !Database::fetchValue("SELECT 1 FROM totp_recovery_codes WHERE code_hash = ?", [$codes[0]]));
    $res = $v1->get('vendor/profile/get');
    check('Profil menunjukkan 2FA aktif', ok($res) && $res['data']['totp_enabled'] === true);

    echo "4. Log masuk 2 langkah\n";
    $login = new Browser($base);
    $login->page('vendor/login.php');
    $res = $login->post('vendor/auth/login', ['email' => 'ahmad@niagasekolah.my', 'password' => 'Demo@12345']);
    check('Langkah 1 meminta 2FA (belum log masuk)', ok($res) && !empty($res['data']['requires_2fa']) && $login->getStatus('vendor/profile/get') === 401);
    $res = $login->post('vendor/auth/verify-2fa', ['code' => $codes[0]]);
    check('Kod pemulihan diterima → log masuk', ok($res) && $login->getStatus('vendor/profile/get') === 200);
    $login2 = new Browser($base);
    $login2->page('vendor/login.php');
    $login2->post('vendor/auth/login', ['email' => 'ahmad@niagasekolah.my', 'password' => 'Demo@12345']);
    check('Kod pemulihan sama tidak boleh diguna semula', !ok($login2->post('vendor/auth/verify-2fa', ['code' => $codes[0]])));
    clearTestLocks();

    echo "5. Jana semula kod & matikan 2FA\n";
    $res = $v1->post('vendor/two-factor/regenerate-codes', ['code' => '123456']);
    check('Jana semula dengan kod salah ditolak', !ok($res));
    $res = $v1->post('vendor/two-factor/regenerate-codes', ['code' => totpNow($secret, 1)]);
    check('Jana semula: 8 kod baharu, kod lama tidak sah', ok($res) && count($res['data']['recovery_codes']) === 8
        && !RecoveryCodes::consume('vendor_user', 1, $codes[1]));
    $newCodes = $res['data']['recovery_codes'];
    $res = $v1->post('vendor/two-factor/disable', ['password' => 'salah', 'code' => $newCodes[0]]);
    check('Matikan dengan kata laluan salah ditolak (2FA kekal)', !ok($res) && Database::fetchValue("SELECT totp_secret FROM vendor_users WHERE id = 1") !== null);
    $res = $v1->post('vendor/two-factor/disable', ['password' => 'Demo@12345', 'code' => '999999']);
    check('Matikan dengan kod salah ditolak', !ok($res) && Database::fetchValue("SELECT totp_secret FROM vendor_users WHERE id = 1") !== null);
    clearTestLocks();
    $res = $v1->post('vendor/two-factor/disable', ['password' => 'Demo@12345', 'code' => $newCodes[1]]);
    check('Matikan (kata laluan + kod pemulihan) → rahsia & kod dibuang', ok($res)
        && Database::fetchValue("SELECT totp_secret FROM vendor_users WHERE id = 1") === null
        && (int) Database::fetchValue("SELECT COUNT(*) FROM totp_recovery_codes WHERE user_type = 'vendor_user' AND user_id = 1") === 0);
    check('Log aktiviti: enable, regenerate, disable', (int) Database::fetchValue(
        "SELECT COUNT(DISTINCT action) FROM activity_logs WHERE vendor_id = 1 AND action IN ('two_factor_enable','two_factor_regenerate','two_factor_disable')") === 3);

    echo "6. Pentadbir set semula 2FA kakitangan\n";
    $setup = $other->get('vendor/two-factor/setup');
    $secret2 = $setup['data']['secret'];
    $other->post('vendor/two-factor/enable', ['code' => totpNow($secret2)]);
    $chong = vendorLogin($base, 'chong@bestari.my');
    $setupC = $chong->get('vendor/two-factor/setup');
    $chong->post('vendor/two-factor/enable', ['code' => totpNow($setupC['data']['secret'])]);
    check('Kakitangan VENDOR02 (Chong) ada 2FA', Database::fetchValue("SELECT totp_secret FROM vendor_users WHERE id = 4") !== null);
    $res = $v1->post('vendor/staff/reset-2fa', ['id' => 4]);
    check('VENDOR01 set semula 2FA kakitangan VENDOR02 → 404, tidak berubah', !ok($res)
        && Database::fetchValue("SELECT totp_secret FROM vendor_users WHERE id = 4") !== null);
    check('Pengurus koperasi tidak boleh set semula 2FA (pentadbir sahaja)', $chong->postStatus('vendor/staff/reset-2fa', ['id' => 3]) === 403);
    $res = $other->post('vendor/staff/reset-2fa', ['id' => 4]);
    check('Pentadbir VENDOR02 set semula 2FA Chong', ok($res) && $res['data']['totp_enabled'] === false
        && Database::fetchValue("SELECT totp_secret FROM vendor_users WHERE id = 4") === null
        && (bool) Database::fetchValue("SELECT 1 FROM activity_logs WHERE vendor_id = 2 AND action = 'staff_reset_2fa' AND entity_id = 4"));
    $list = $other->get('vendor/staff/list');
    $mine = array_values(array_filter($list['data']['items'], function ($r) { return (int) $r['id'] === 3; }));
    check('Senarai kakitangan memapar 2FA pentadbir aktif', $mine && $mine[0]['totp_enabled'] === true);

    echo "7. Langganan digantung — profil masih boleh dibuka\n";
    Database::execute("UPDATE vendors SET status = 'suspended' WHERE id = 1");
    check('Vendor digantung: profil 200, API profil OK; produk disekat', $v1->page('vendor/profile.php')['status'] === 200
        && ok($v1->get('vendor/profile/get')) && $v1->getStatus('vendor/products/list') === 403);
    Database::execute("UPDATE vendors SET status = 'active' WHERE id = 1");

    echo "8. Struktur\n";
    $apiFiles = array_merge(glob(APP_ROOT . '/api/v1/vendor/profile/*.php'), glob(APP_ROOT . '/api/v1/vendor/two-factor/*.php'),
        [APP_ROOT . '/api/v1/vendor/staff/reset-2fa.php']);
    $sql = array_filter($apiFiles, function ($file) {
        return preg_match('/Database::|\b(SELECT|INSERT|UPDATE|DELETE)\b/', file_get_contents($file));
    });
    check('Tiada SQL dalam api/ baharu (' . count($apiFiles) . ' fail)', count($apiFiles) === 8 && !$sql);
    $vendorIdInput = array_filter($apiFiles, function ($file) {
        return preg_match('/Request::(int|string|all)\(\'vendor_id\'/', file_get_contents($file));
    });
    check('vendor_id tidak dibaca dari input', !$vendorIdInput);
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
