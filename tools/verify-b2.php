<?php
// Semakan B2 — auth owner, vendor, ibu bapa + 2FA + kunci + ujian kebocoran. CLI sahaja:
//   php tools/verify-b2.php
// Perlu: DB koperasi_saas (schema + seed + migrations/001), config/database.php, config/secret.key, sambungan PHP curl.
// Skrip memulakan pelayan PHP sendiri (php -S 127.0.0.1:8098) dan menguji melalui HTTP sebenar (sesi, cookie, CSRF).
// Data yang diubah (2FA owner, status vendor, kunci log masuk ujian) dipulihkan di hujung.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';

require __DIR__ . '/test-helpers.php';

// ---------------------------------------------------------------------
// Pelayan ujian
// ---------------------------------------------------------------------
$port = 8098;
$base = 'http://127.0.0.1:' . $port;
$server = proc_open(
    [PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT],
    [0 => ['pipe', 'r'], 1 => ['file', sys_get_temp_dir() . '/kop-verify-b2.log', 'w'], 2 => ['file', sys_get_temp_dir() . '/kop-verify-b2.log', 'a']],
    $pipes
);
usleep(700000);

// Simpan keadaan asal untuk dipulihkan
$savedUsers = Database::fetchAll("SELECT id, totp_secret, totp_last_step, failed_attempts, locked_until FROM platform_users");
$savedVendors = Database::fetchAll("SELECT id, status FROM vendors");
$savedCodes = Database::fetchAll("SELECT user_type, user_id, code_hash, used_at, created_at FROM totp_recovery_codes WHERE user_type = 'owner'");
clearTestLocks();
// Mula dengan owner tanpa 2FA (aliran log masuk pertama diuji); keadaan asal dipulihkan di hujung
Database::execute("UPDATE platform_users SET totp_secret = NULL, totp_last_step = NULL");
$stamp = date('His') . random_int(10, 99);

try {
    echo "1. Halaman dilindungi & CSRF\n";
    $guest = new Browser($base);
    $r = $guest->page('owner/index.php');
    check('owner/index.php tanpa sesi → log masuk', $r['status'] === 302 && strpos($r['location'], 'owner/login.php') !== false);
    $r = $guest->page('vendor/orders.php');
    check('vendor/orders.php tanpa sesi → log masuk', $r['status'] === 302 && strpos($r['location'], 'vendor/login.php') !== false);
    $r = $guest->page('vendor/pos/index.php');
    check('vendor/pos/index.php tanpa sesi → log masuk', $r['status'] === 302);
    $res = $guest->get('owner/staff/list');
    check('API owner tanpa sesi → auth_required', $res['ok'] === false && !empty($res['auth_required']));
    $guest->page('owner/login.php');
    check('POST tanpa X-CSRF-Token → 419', $guest->postStatus('owner/auth/login', ['email' => 'a@b.my', 'password' => 'x'], false) === 419);
    check('GET ke endpoint POST → 405', $guest->getStatus('owner/auth/login') === 405);

    echo "2. Owner — 2FA wajib, TOTP disulitkan, kod tidak boleh diguna semula\n";
    $owner = new Browser($base);
    $owner->page('owner/login.php');
    $wrong = $owner->post('owner/auth/login', ['email' => 'owner@koperasiku.my', 'password' => 'salah-sekali']);
    $missing = $owner->post('owner/auth/login', ['email' => 'tiada-' . $stamp . '@koperasiku.my', 'password' => 'salah-sekali']);
    check('Mesej sama: kata laluan salah = akaun tiada', $wrong['error'] === $missing['error'] && $wrong['error'] === t('auth.invalid_credentials'));
    $sessionBefore = $owner->cookie('KOPOWNER');
    $step1 = $owner->post('owner/auth/login', ['email' => 'owner@koperasiku.my', 'password' => 'Demo@12345']);
    check('Langkah 1 berjaya → requires_2fa', $step1['ok'] && $step1['data']['requires_2fa'] === true);
    check('Id sesi ditukar selepas log masuk (session_regenerate_id)', $owner->cookie('KOPOWNER') !== $sessionBefore);
    check('Belum 2FA → API owner masih 401', !empty($owner->get('owner/profile/get')['auth_required']));
    $secret = $step1['data']['setup']['secret'] ?? null;
    check('Log masuk pertama: rahsia TOTP + otpauth_url untuk QR', is_string($secret) && strpos($step1['data']['setup']['otpauth_url'], 'otpauth://totp/') === 0);
    $bad = $owner->post('owner/auth/verify-2fa', ['code' => '000000']);
    check('Kod 2FA salah ditolak', $bad['ok'] === false);
    $code = totpNow($secret);
    $ok = $owner->post('owner/auth/verify-2fa', ['code' => $code]);
    check('Kod 2FA betul → masuk + 8 kod pemulihan', $ok['ok'] && count($ok['data']['recovery_codes']) === 8);
    $recovery = $ok['data']['recovery_codes'];
    $stored = Database::fetchValue("SELECT totp_secret FROM platform_users WHERE id = 1");
    check('totp_secret disimpan disulitkan (bukan teks asal)', $stored !== $secret && Crypto::decrypt($stored) === $secret);
    check('Kod pemulihan disimpan sebagai hash', (int) Database::fetchValue("SELECT COUNT(*) FROM totp_recovery_codes WHERE user_type = 'owner' AND user_id = 1 AND code_hash = ?", [Crypto::hashToken(str_replace('-', '', $recovery[0]))]) === 1);
    check('Profil owner dibaca selepas 2FA', $owner->get('owner/profile/get')['data']['totp_enabled'] === true);

    $again = new Browser($base);
    $again->page('owner/login.php');
    $again->post('owner/auth/login', ['email' => 'owner@koperasiku.my', 'password' => 'Demo@12345']);
    $replay = $again->post('owner/auth/verify-2fa', ['code' => $code]);
    check('Kod TOTP yang sama tidak boleh diguna semula', $replay['ok'] === false);
    $viaRecovery = $again->post('owner/auth/verify-2fa', ['code' => $recovery[1]]);
    check('Kod pemulihan diterima', $viaRecovery['ok'] === true);
    $third = new Browser($base);
    $third->page('owner/login.php');
    $third->post('owner/auth/login', ['email' => 'owner@koperasiku.my', 'password' => 'Demo@12345']);
    check('Kod pemulihan yang sama ditolak kali kedua', $third->post('owner/auth/verify-2fa', ['code' => $recovery[1]])['ok'] === false);

    $owner->page('owner/staff.php');
    $profile = $owner->post('owner/profile/change-password', ['current_password' => 'salah', 'new_password' => 'BaruSangat123']);
    check('Tukar kata laluan: kata laluan semasa salah ditolak', $profile['ok'] === false && isset($profile['errors']['current_password']));

    echo "3. owner_staff disekat (PELAN 4)\n";
    list($staff) = ownerLogin($base, 'staf@koperasiku.my');
    check('owner_staff → owner/staff/list 403', $staff->get('owner/staff/list')['ok'] === false);
    $r = $staff->page('owner/plans.php');
    check('owner_staff → halaman pelan dialih ke halaman pertama', $r['status'] === 302 && strpos($r['location'], 'owner/index.php') !== false);
    check('owner_staff → owner/vendors.php dibenarkan', $staff->page('owner/vendors.php')['status'] === 200);
    $self = $owner->post('owner/staff/set-active', ['id' => 1, 'is_active' => false]);
    check('Owner tidak boleh nyahaktif akaun sendiri', $self['ok'] === false);

    echo "4. Vendor — log masuk, peranan, pilih koperasi\n";
    $v1 = new Browser($base);
    $v1->page('vendor/login.php');
    $wrong = $v1->post('vendor/auth/login', ['email' => 'ahmad@niagasekolah.my', 'password' => 'salah-sekali']);
    $missing = $v1->post('vendor/auth/login', ['email' => 'tiada-' . $stamp . '@niagasekolah.my', 'password' => 'salah-sekali']);
    check('Vendor: mesej sama untuk salah / tiada', $wrong['error'] === $missing['error']);
    $login = $v1->post('vendor/auth/login', ['email' => 'ahmad@niagasekolah.my', 'password' => 'Demo@12345']);
    check('VENDOR01 admin log masuk (tiada 2FA)', $login['ok'] && $login['data']['requires_2fa'] === false && $login['data']['user']['role'] === 'vendor_admin');
    $v1->page('vendor/login.php');
    $coops = $v1->get('vendor/cooperatives/list');
    check('VENDOR01 nampak 1 koperasi sahaja (SK Taman Melati)', $coops['ok'] && $coops['data']['total'] === 1 && $coops['data']['items'][0]['id'] === 1);
    check('VENDOR01 pilih koperasi 1 → OK', $v1->post('vendor/session/cooperative', ['cooperative_id' => 1])['ok'] === true);

    $cashier = vendorLogin($base, 'kasim@niagasekolah.my');
    $r = $cashier->page('vendor/orders.php');
    check('Juruwang → halaman pesanan dialih ke serah barang', $r['status'] === 302 && strpos($r['location'], 'vendor/pos/handover.php') !== false);
    check('Juruwang → POS dibenarkan', $cashier->page('vendor/pos/index.php')['status'] === 200);
    check('Juruwang → vendor/staff/list 403', $cashier->get('vendor/staff/list')['ok'] === false);

    $manager = vendorLogin($base, 'chong@bestari.my');
    $list = $manager->get('vendor/cooperatives/list');
    check('Pengurus VENDOR02 nampak koperasi yang diberi sahaja (Chung Hwa)', $list['ok'] && array_column($list['data']['items'], 'id') === [2]);
    check('Pengurus VENDOR02 → koperasi 3 (tidak diberi) 404', $manager->post('vendor/session/cooperative', ['cooperative_id' => 3])['ok'] === false);

    echo "5. Ujian kebocoran VENDOR01 ↔ VENDOR02\n";
    check('VENDOR01 → pilih koperasi 2 (VENDOR02) → 404', $v1->post('vendor/session/cooperative', ['cooperative_id' => 2])['ok'] === false);
    check('VENDOR01 → pilih koperasi "1 OR 1=1" ditolak', $v1->post('vendor/session/cooperative', ['cooperative_id' => '2 OR 1=1'])['ok'] === false);
    $staffList = $v1->get('vendor/staff/list', ['vendor_id' => 2, 'per_page' => 100]);
    $emails = array_column($staffList['data']['items'], 'email');
    check('Senarai kakitangan VENDOR01 tiada kakitangan VENDOR02 (vendor_id dalam input diabaikan)', $staffList['ok'] && !in_array('meiling@bestari.my', $emails, true) && count($emails) === 2);
    check('VENDOR01 → set-active kakitangan VENDOR02 → 404', $v1->post('vendor/staff/set-active', ['id' => 3, 'is_active' => false])['ok'] === false);
    check('VENDOR01 → send-reset kakitangan VENDOR02 → 404', $v1->post('vendor/staff/send-reset', ['id' => 4])['ok'] === false);
    $edit = $v1->post('vendor/staff/save', ['id' => 5, 'full_name' => 'Curi', 'email' => 'kavitha@bestari.my', 'role' => 'cashier', 'cooperative_ids' => [1]]);
    check('VENDOR01 → sunting kakitangan VENDOR02 → 404', $edit['ok'] === false && Database::fetchValue("SELECT full_name FROM vendor_users WHERE id = 5") === 'Kavitha a/p Raman');
    $foreignCoop = $v1->post('vendor/staff/save', ['full_name' => 'Ujian ' . $stamp, 'email' => 'ujian' . $stamp . '@niagasekolah.my', 'role' => 'cashier', 'cooperative_ids' => [2]]);
    check('VENDOR01 → beri akses koperasi VENDOR02 ditolak', $foreignCoop['ok'] === false && isset($foreignCoop['errors']['cooperative_ids']));
    $dupEmail = $v1->post('vendor/staff/save', ['full_name' => 'Ujian', 'email' => 'meiling@bestari.my', 'role' => 'cashier', 'cooperative_ids' => [1]]);
    check('E-mel kakitangan unik seluruh platform (mesej umum)', $dupEmail['ok'] === false && ($dupEmail['errors']['email'] ?? '') === t('error.duplicate'));

    $v2 = vendorLogin($base, 'meiling@bestari.my');
    $v2coops = $v2->get('vendor/cooperatives/list');
    check('VENDOR02 nampak koperasi 2 & 3 sahaja', $v2coops['ok'] && array_column($v2coops['data']['items'], 'id') === [2, 3]);
    check('VENDOR02 → pilih koperasi 1 (VENDOR01) → 404', $v2->post('vendor/session/cooperative', ['cooperative_id' => 1])['ok'] === false);
    check('VENDOR02 → set-active kakitangan VENDOR01 → 404', $v2->post('vendor/staff/set-active', ['id' => 2, 'is_active' => false])['ok'] === false);

    echo "6. Kakitangan vendor baharu → jemputan e-mel → tetapkan kata laluan\n";
    $newEmail = 'baru' . $stamp . '@niagasekolah.my';
    $created = $v1->post('vendor/staff/save', ['full_name' => 'Kakitangan Baru', 'email' => $newEmail, 'role' => 'cashier', 'cooperative_ids' => [1], 'pin' => '4321']);
    check('VENDOR01 tambah juruwang (PIN di-hash)', $created['ok'] && $created['data']['pin_set'] === true);
    $newId = $created['data']['id'] ?? 0;
    $pinHash = (string) Database::fetchValue("SELECT pin_hash FROM vendor_users WHERE id = ?", [$newId]);
    check('PIN disimpan Argon2id', strpos($pinHash, '$argon2id$') === 0);
    $queued = Database::fetchOne("SELECT body_html FROM email_queue WHERE to_email = ? AND template_key = 'staff_invite' ORDER BY id DESC LIMIT 1", [$newEmail]);
    check('E-mel jemputan masuk email_queue', $queued !== null);
    preg_match('/token=([a-f0-9]{64})/', $queued['body_html'] ?? '', $m);
    $token = $m[1] ?? '';
    check('Token dalam DB hanya hash SHA-256', $token !== '' && (int) Database::fetchValue("SELECT COUNT(*) FROM vendor_user_tokens WHERE token_hash = ?", [Crypto::hashToken($token)]) === 1);
    $invitee = new Browser($base);
    $invitee->page('vendor/reset-password.php?token=' . $token);
    check('Kata laluan < 10 aksara ditolak', $invitee->post('vendor/auth/reset', ['token' => $token, 'password' => 'pendek', 'password_confirm' => 'pendek'])['ok'] === false);
    check('Tetapkan kata laluan dari jemputan', $invitee->post('vendor/auth/reset', ['token' => $token, 'password' => 'KataLaluan123', 'password_confirm' => 'KataLaluan123'])['ok'] === true);
    check('Token jemputan tidak boleh diguna kali kedua', $invitee->post('vendor/auth/reset', ['token' => $token, 'password' => 'KataLaluan999', 'password_confirm' => 'KataLaluan999'])['ok'] === false);
    $invitee->page('vendor/login.php');
    check('Kakitangan baharu log masuk', $invitee->post('vendor/auth/login', ['email' => $newEmail, 'password' => 'KataLaluan123'])['ok'] === true);
    $off = $v1->post('vendor/staff/set-active', ['id' => $newId, 'is_active' => false]);
    $invitee->page('vendor/login.php');
    check('Dinyahaktif → sesi sedia ada terkeluar', $off['ok'] && $invitee->page('vendor/pos/index.php')['status'] === 302);

    echo "7. POS PIN (PosPinLoginJob — endpoint B10)\n";
    $pin = new PosPinLoginJob(1, 1, $newId, '4321');
    Database::execute("UPDATE vendor_users SET is_active = 1 WHERE id = ?", [$newId]);
    check('PIN betul → juruwang', $pin->run()['id'] === $newId);
    $wrongCount = 0;
    for ($i = 0; $i < 5; $i++) {
        try {
            (new PosPinLoginJob(1, 1, $newId, '0000'))->run();
        } catch (ValidationException $exception) {
            $wrongCount++;
        }
    }
    $locked = false;
    try {
        (new PosPinLoginJob(1, 1, $newId, '4321'))->run();
    } catch (ValidationException $exception) {
        $locked = $exception->getMessage() === t('pos.pin_locked');
    }
    check('5 PIN salah → dikunci walaupun PIN betul', $wrongCount === 5 && $locked);
    $leak = false;
    try {
        (new PosPinLoginJob(2, 2, 2, '1234'))->run();
    } catch (ValidationException $exception) {
        $leak = true;
    }
    check('PIN juruwang VENDOR01 di kaunter VENDOR02 → tiada akses', $leak);

    echo "8. Vendor digantung → halaman bil sahaja\n";
    Database::execute("UPDATE vendors SET status = 'suspended' WHERE id = 2");
    $kav = new Browser($base);
    $kav->page('vendor/login.php');
    $refused = $kav->post('vendor/auth/login', ['email' => 'kavitha@bestari.my', 'password' => 'Demo@12345']);
    check('Juruwang vendor digantung tidak boleh log masuk', $refused['ok'] === false && $refused['error'] === t('auth.vendor_suspended'));
    $admin = new Browser($base);
    $admin->page('vendor/login.php');
    $adminLogin = $admin->post('vendor/auth/login', ['email' => 'meiling@bestari.my', 'password' => 'Demo@12345']);
    check('Pentadbir vendor digantung boleh log masuk (billing_only)', $adminLogin['ok'] && $adminLogin['data']['billing_only'] === true);
    $r = $admin->page('vendor/index.php');
    check('Vendor digantung → papan pemuka dialih ke langganan', $r['status'] === 302 && strpos($r['location'], 'vendor/subscription.php') !== false);
    check('Vendor digantung → halaman langganan & invois dibuka', $admin->page('vendor/subscription.php')['status'] === 200 && $admin->page('vendor/invoices.php')['status'] === 200);
    $blocked = $admin->get('vendor/staff/list');
    check('Vendor digantung → API lain 403 billing_only', $blocked['ok'] === false && !empty($blocked['billing_only']));
    $r = $manager->page('vendor/index.php');
    check('Sesi pengurus vendor digantung ditamatkan', $r['status'] === 302 && strpos($r['location'], 'reason=suspended') !== false);
    Database::execute("UPDATE vendors SET status = 'active' WHERE id = 2");

    echo "9. Ibu bapa — daftar, pengesahan e-mel, log masuk, kunci\n";
    $parent = new Browser($base);
    $parent->page('store/index.php?page=register');
    $regEmail = 'ujian' . $stamp . '@contoh.my';
    $regPhone = '011-' . substr($stamp, 0, 7) . '9';
    $registerBody = ['full_name' => 'Ibu Ujian', 'phone' => $regPhone, 'email' => strtoupper($regEmail), 'password' => 'Rahsia123',
        'password_confirm' => 'Rahsia123', 'relationship' => 'mother', 'language' => 'zh', 'pdpa' => true];
    $reg = $parent->post('store/auth/register', $registerBody);
    check('Daftar akaun → log masuk terus, email_verified false', $reg['ok'] && $reg['data']['customer']['email_verified'] === false);
    check('Jawapan tiada id dalaman / hash / demo_token', $reg['ok'] && !isset($reg['data']['customer']['id']) && !isset($reg['data']['demo_token']));
    $row = Database::fetchOne("SELECT id, email, phone, password_hash, pdpa_consent_at FROM customers WHERE email = ?", [$regEmail]);
    check('Hash kata laluan $argon2id$ (19456/2/1)', strpos($row['password_hash'], '$argon2id$v=19$m=19456,t=2,p=1$') === 0 && !Password::needsRehash($row['password_hash']));
    check('E-mel disimpan huruf kecil, persetujuan PDPA direkod', $row['email'] === $regEmail && $row['pdpa_consent_at'] !== null);
    $dup = (new Browser($base));
    $dup->page('store/index.php?page=register');
    $dupRes = $dup->post('store/auth/register', array_merge($registerBody, ['phone' => '019-' . substr($stamp, 0, 7) . '1']));
    check('E-mel sama tidak boleh daftar dua kali', $dupRes['ok'] === false && isset($dupRes['errors']['email']));
    $dupPhone = $dup->post('store/auth/register', array_merge($registerBody, ['email' => 'lain' . $stamp . '@contoh.my', 'phone' => str_replace('-', '', $regPhone)]));
    check('Telefon sama (format lain) tidak boleh daftar dua kali', $dupPhone['ok'] === false && isset($dupPhone['errors']['phone']));
    $noPdpa = $dup->post('store/auth/register', array_merge($registerBody, ['email' => 'pdpa' . $stamp . '@contoh.my', 'phone' => '018-' . substr($stamp, 0, 7) . '2', 'pdpa' => false]));
    check('Tanpa persetujuan PDPA ditolak', $noPdpa['ok'] === false && isset($noPdpa['errors']['pdpa']));

    $mail = Database::fetchOne("SELECT subject, body_html FROM email_queue WHERE to_email = ? AND template_key = 'verify_email' ORDER BY id DESC LIMIT 1", [$regEmail]);
    check('E-mel pengesahan dalam bahasa ibu bapa (zh)', $mail !== null && strpos($mail['subject'], '验证') !== false);
    preg_match('/verify_token=([a-f0-9]{64})/', $mail['body_html'] ?? '', $m);
    $verifyToken = $m[1] ?? '';
    check('Sahkan e-mel dengan token', $parent->post('store/auth/verify-email', ['token' => $verifyToken])['ok'] === true);
    check('Token pengesahan sekali guna', $parent->post('store/auth/verify-email', ['token' => $verifyToken])['ok'] === false);
    check('Profil: email_verified true', $parent->get('store/profile/get')['data']['email_verified'] === true);

    $parent->post('store/auth/logout');
    check('Log keluar → profil auth_required', !empty($parent->get('store/profile/get')['auth_required']));
    list(, $byPhone) = storeLogin($base, str_replace('-', '', $regPhone), 'Rahsia123');
    check('Log masuk dengan no. telefon (tanpa sengkang)', $byPhone['ok'] === true);
    list(, $wrongPw) = storeLogin($base, $regEmail, 'salah-salah');
    list(, $noAcc) = storeLogin($base, 'tiada' . $stamp . '@contoh.my', 'salah-salah');
    check('Ibu bapa: mesej sama untuk salah / tiada', $wrongPw['error'] === $noAcc['error'] && $wrongPw['error'] === t('store.auth.invalid'));
    for ($i = 0; $i < 4; $i++) {
        storeLogin($base, $regEmail, 'salah-salah');
    }
    list(, $lockedRes) = storeLogin($base, $regEmail, 'Rahsia123');
    check('5 cubaan gagal → dikunci 15 minit (walaupun kata laluan betul)', $lockedRes['ok'] === false && $lockedRes['error'] === t('store.auth.locked'));
    check('Cubaan direkod dengan IP', (int) Database::fetchValue("SELECT COUNT(*) FROM login_attempts WHERE login_type = 'customer' AND identifier = ? AND ip_address <> ''", [$regEmail]) >= 6);
    Database::execute("DELETE FROM login_attempts WHERE identifier = ?", [$regEmail]);

    echo "10. Lupa kata laluan — jawapan sama, token sekali guna\n";
    $forgot = new Browser($base);
    $forgot->page('store/index.php?page=forgot-password');
    $before = (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue");
    $known = $forgot->post('store/auth/forgot', ['email' => $regEmail]);
    $unknown = $forgot->post('store/auth/forgot', ['email' => 'tiada' . $stamp . '@contoh.my']);
    check('Jawapan sama untuk e-mel berdaftar / tidak', $known === $unknown && $known['data'] === ['sent' => true]);
    check('Hanya satu e-mel reset dihantar (e-mel berdaftar)', (int) Database::fetchValue("SELECT COUNT(*) FROM email_queue") === $before + 1);
    $resetMail = Database::fetchValue("SELECT body_html FROM email_queue WHERE to_email = ? AND template_key = 'reset_password' ORDER BY id DESC LIMIT 1", [$regEmail]);
    preg_match('/token=([a-f0-9]{64})/', (string) $resetMail, $m);
    $resetToken = $m[1] ?? '';
    check('Reset kata laluan dengan token', $forgot->post('store/auth/reset', ['token' => $resetToken, 'password' => 'BaharuSekali1', 'password_confirm' => 'BaharuSekali1'])['ok'] === true);
    check('Token reset sekali guna', $forgot->post('store/auth/reset', ['token' => $resetToken, 'password' => 'LainLagi123', 'password_confirm' => 'LainLagi123'])['ok'] === false);
    list(, $newPw) = storeLogin($base, $regEmail, 'BaharuSekali1');
    check('Log masuk dengan kata laluan baharu', $newPw['ok'] === true);

    echo "11. Ujian kebocoran ibu bapa A ↔ B\n";
    list($siti) = storeLogin($base, 'siti@contoh.my');
    list($rajesh) = storeLogin($base, '016-7788990');
    check('Ibu bapa A nampak profil sendiri', $siti->get('store/profile/get')['data']['email'] === 'siti@contoh.my');
    check('Ibu bapa B nampak profil sendiri', $rajesh->get('store/profile/get')['data']['email'] === 'rajesh@contoh.my');
    $attack = $rajesh->post('store/profile/save', ['customer_id' => 1, 'id' => 1, 'full_name' => 'Dicuri', 'phone' => '016-7788990', 'relationship' => 'father', 'language' => 'ta']);
    check('customer_id dalam input diabaikan (B hanya ubah profil sendiri)', $attack['ok'] && Database::fetchValue("SELECT full_name FROM customers WHERE id = 1") === 'Siti Nurhaliza binti Ahmad');
    Database::execute("UPDATE customers SET full_name = 'Rajesh a/l Kumar' WHERE id = 2");
    $stealPhone = $rajesh->post('store/profile/save', ['full_name' => 'Rajesh a/l Kumar', 'phone' => '012-3456701', 'relationship' => 'father', 'language' => 'ta']);
    check('B tidak boleh guna telefon A', $stealPhone['ok'] === false && isset($stealPhone['errors']['phone']));
    check('Sesi store berasingan dari sesi vendor/owner (cookie KOPSTORE)', $siti->cookie('KOPSTORE') !== null && $siti->cookie('KOPVENDOR') === null);

    echo "12. Padam akaun (PDPA)\n";
    list($deleter) = storeLogin($base, $regEmail, 'BaharuSekali1');
    check('Padam akaun: kata laluan salah ditolak', $deleter->post('store/profile/delete', ['password' => 'salah'])['ok'] === false);
    check('Padam akaun berjaya', $deleter->post('store/profile/delete', ['password' => 'BaharuSekali1'])['ok'] === true);
    $gone = Database::fetchOne("SELECT email, phone, deleted_at, is_active FROM customers WHERE id = ?", [$row['id']]);
    check('Data peribadi dinyahnama (e-mel & telefon NULL)', $gone['email'] === null && $gone['phone'] === null && $gone['deleted_at'] !== null);
    list(, $afterDelete) = storeLogin($base, $regEmail, 'BaharuSekali1');
    check('Akaun dipadam tidak boleh log masuk', $afterDelete['ok'] === false);

    echo "13. Kunci log masuk owner/vendor (5 gagal)\n";
    $lock = new Browser($base);
    $lock->page('vendor/login.php');
    for ($i = 0; $i < 5; $i++) {
        $lock->post('vendor/auth/login', ['email' => $newEmail, 'password' => 'salah-salah']);
    }
    $lockedVendor = $lock->post('vendor/auth/login', ['email' => $newEmail, 'password' => 'KataLaluan123']);
    check('Vendor: 5 gagal → dikunci', $lockedVendor['ok'] === false && $lockedVendor['error'] === t('auth.locked'));
    check('Kolum locked_until dikemas kini', Database::fetchValue("SELECT locked_until FROM vendor_users WHERE id = ?", [$newId]) !== null);
} finally {
    // Pulihkan keadaan asal
    foreach ($savedUsers as $user) {
        Database::execute(
            "UPDATE platform_users SET totp_secret = ?, totp_last_step = ?, failed_attempts = ?, locked_until = ?, is_active = 1 WHERE id = ?",
            [$user['totp_secret'], $user['totp_last_step'], $user['failed_attempts'], $user['locked_until'], $user['id']]
        );
    }
    Database::execute("DELETE FROM totp_recovery_codes WHERE user_type = 'owner'");
    foreach ($savedCodes as $code) {
        Database::insert(
            "INSERT INTO totp_recovery_codes (user_type, user_id, code_hash, used_at, created_at) VALUES (?, ?, ?, ?, ?)",
            [$code['user_type'], $code['user_id'], $code['code_hash'], $code['used_at'], $code['created_at']]
        );
    }
    foreach ($savedVendors as $vendor) {
        Database::execute("UPDATE vendors SET status = ? WHERE id = ?", [$vendor['status'], $vendor['id']]);
    }
    Database::execute("UPDATE customers SET full_name = 'Rajesh a/l Kumar', phone = '016-7788990', relationship = 'father', language = 'ta' WHERE id = 2");
    if (!empty($newId)) {
        Database::execute("DELETE FROM vendor_users WHERE id = ?", [$newId]);
    }
    clearTestLocks();
    proc_terminate($server);
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
