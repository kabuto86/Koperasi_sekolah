<?php
// Semakan B3b — kebenaran ikut pengguna (lalai peranan ± pengecualian, PELAN 4.1) untuk vendor & owner,
// termasuk ujian kebocoran VENDOR01 ↔ VENDOR02. CLI sahaja:
//   php tools/verify-b3b.php
// Perlu: DB koperasi_saas (schema + seed, atau migrations/002), config/database.php, config/secret.key, sambungan PHP curl.
// Skrip memulakan pelayan PHP sendiri (php -S 127.0.0.1:8097). Data yang diubah dipulihkan di hujung.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

$port = 8097;
$base = 'http://127.0.0.1:' . $port;
$server = proc_open(
    [PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT],
    [0 => ['pipe', 'r'], 1 => ['file', sys_get_temp_dir() . '/kop-verify-b3b.log', 'w'], 2 => ['file', sys_get_temp_dir() . '/kop-verify-b3b.log', 'a']],
    $pipes
);
usleep(700000);

function vendorOverrides($userId)
{
    return Database::fetchAll("SELECT permission, allowed FROM vendor_user_permissions WHERE vendor_user_id = ? ORDER BY permission", [$userId]);
}

function hasLink($html, $path)
{
    return strpos($html, $path) !== false;
}

$savedUsers = Database::fetchAll("SELECT id, role, totp_secret, totp_last_step FROM platform_users");
$savedCodes = Database::fetchAll("SELECT user_type, user_id, code_hash, used_at, created_at FROM totp_recovery_codes WHERE user_type = 'owner'");
$savedVendorUsers = Database::fetchAll("SELECT id, role FROM vendor_users");
$maxActivityId = (int) Database::fetchValue("SELECT COALESCE(MAX(id), 0) FROM activity_logs");
clearTestLocks();
Database::execute("UPDATE platform_users SET totp_secret = NULL, totp_last_step = NULL");
Database::execute("DELETE FROM vendor_user_permissions");
Database::execute("DELETE FROM platform_user_permissions");

try {
    echo "1. Lalai peranan (sama seperti sebelum B3b)\n";
    check('vendor_admin = semua kebenaran vendor', Permission::effective('vendor_admin', ['pos.sell' => false]) === Permission::all('vendor'));
    check('Juruwang lalai = serah barang, POS, diskaun POS', (new LoadUserPermissionsJob('vendor', 2, 'cashier'))->run() === ['handover.process', 'pos.sell', 'pos.discount']);
    check('Pengurus lalai tiada kebenaran had', count(array_intersect(Permission::defaults('coop_manager'), Permission::LOCKED['vendor'])) === 0);
    check('owner_staff lalai = papan pemuka, vendor, nota, koperasi, langganan, audit',
        Permission::defaults('owner_staff') === ['dashboard.view', 'vendor.view', 'vendor.note', 'cooperative.view', 'subscription.view', 'audit.view']);
    check('Pengecualian had diabaikan (juruwang + staff.manage)', !in_array('staff.manage', Permission::effective('cashier', ['staff.manage' => true]), true));

    $ahmad = vendorLogin($base, 'ahmad@niagasekolah.my');
    $meiling = vendorLogin($base, 'meiling@bestari.my');
    $kasim = vendorLogin($base, 'kasim@niagasekolah.my');
    $chong = vendorLogin($base, 'chong@bestari.my');
    $ahmad->page('vendor/staff.php');
    $meiling->page('vendor/staff.php');

    echo "2. Juruwang diberi refund (VENDOR01)\n";
    $kasimPage = $kasim->page('vendor/refunds.php');
    check('Sebelum: juruwang ke halaman refund → dialih', $kasimPage['status'] === 302);
    $perm = $ahmad->get('vendor/staff/permissions', ['id' => 2]);
    $flat = array_merge(...array_map(function ($group) { return $group['items']; }, $perm['data']['groups']));
    $byCode = array_column($flat, null, 'code');
    check('Modal: boleh disunting, staff.manage dikunci, pos.sell lalai', $perm['ok'] && $perm['data']['editable'] === true
        && $byCode['staff.manage']['locked'] === true && $byCode['pos.sell']['default'] === true && $byCode['refund.manage']['allowed'] === false);
    $saved = $ahmad->post('vendor/staff/save-permissions', ['id' => 2, 'permissions' => ['handover.process', 'pos.sell', 'pos.discount', 'refund.manage', 'staff.manage', 'tiada.kod']]);
    check('Simpan: hanya beza disimpan (+refund.manage), had & kod palsu diabaikan', $saved['ok']
        && vendorOverrides(2) === [['permission' => 'refund.manage', 'allowed' => 1]]);
    $kasimPage = $kasim->page('vendor/refunds.php');
    check('Selepas: juruwang boleh buka halaman refund (sesi sedia ada)', $kasimPage['status'] === 200 && hasLink($kasimPage['body'], 'vendor/refunds.php'));
    check('Juruwang masih tiada akses kakitangan (API 403, halaman dialih)', $kasim->getStatus('vendor/staff/list') === 403
        && $kasim->page('vendor/staff.php')['status'] === 302);
    check('Log aktiviti staff.permissions', Database::fetchValue("SELECT detail_json FROM activity_logs WHERE id > ? AND action = 'staff.permissions' ORDER BY id DESC LIMIT 1", [$maxActivityId]) !== null);

    echo "3. Pengurus ditarik laporan (VENDOR02)\n";
    check('Sebelum: pengurus boleh buka laporan', $chong->page('vendor/reports.php')['status'] === 200);
    $defaultsChong = Permission::defaults('coop_manager');
    $without = array_values(array_diff($defaultsChong, ['report.view', 'report.export']));
    check('Tarik report.view & report.export', $meiling->post('vendor/staff/save-permissions', ['id' => 4, 'permissions' => $without])['ok']
        && vendorOverrides(4) === [['permission' => 'report.export', 'allowed' => 0], ['permission' => 'report.view', 'allowed' => 0]]);
    $reports = $chong->page('vendor/reports.php');
    check('Selepas: halaman laporan dialih & pautan menu hilang', $reports['status'] === 302
        && !hasLink($chong->page('vendor/index.php')['body'], 'vendor/reports.php'));
    check('Pengurus tidak boleh buka API kebenaran (403)', $chong->getStatus('vendor/staff/permissions.php?id=5') === 403);

    echo "4. Kebocoran VENDOR01 ↔ VENDOR02\n";
    check('VENDOR01 baca kebenaran kakitangan VENDOR02 → 404', $ahmad->get('vendor/staff/permissions', ['id' => 4])['ok'] === false);
    $leak = $ahmad->post('vendor/staff/save-permissions', ['id' => 4, 'permissions' => Permission::all('vendor')]);
    check('VENDOR01 simpan kebenaran kakitangan VENDOR02 → ditolak, DB tidak berubah', $leak['ok'] === false && count(vendorOverrides(4)) === 2);
    check('VENDOR02 reset kebenaran kakitangan VENDOR01 → ditolak', $meiling->post('vendor/staff/save-permissions', ['id' => 2, 'reset' => true])['ok'] === false
        && count(vendorOverrides(2)) === 1);
    check('vendor_id dalam input diabaikan', $ahmad->post('vendor/staff/save-permissions', ['id' => 4, 'vendor_id' => 2, 'reset' => true])['ok'] === false);

    echo "5. Had suntingan\n";
    check('Pentadbir tidak boleh sunting diri sendiri', $ahmad->post('vendor/staff/save-permissions', ['id' => 1, 'permissions' => []])['ok'] === false);
    $adminPerm = $meiling->get('vendor/staff/permissions', ['id' => 3])['data'];
    check('Modal pentadbir: tidak boleh disunting, semua dibenarkan', $adminPerm['editable'] === false
        && count(array_filter(array_merge(...array_map(function ($g) { return $g['items']; }, $adminPerm['groups'])), function ($i) { return !$i['allowed']; })) === 0);

    echo "6. Tukar peranan & set semula\n";
    $staffRow = ['id' => 2, 'full_name' => 'Kasim bin Osman', 'email' => 'kasim@niagasekolah.my', 'phone' => '013-4567890', 'cooperative_ids' => [1], 'pin' => ''];
    check('Juruwang → pengurus: pengecualian dibuang', $ahmad->post('vendor/staff/save', $staffRow + ['role' => 'coop_manager'])['ok'] && count(vendorOverrides(2)) === 0);
    $ahmad->post('vendor/staff/save', $staffRow + ['role' => 'cashier']);
    $ahmad->post('vendor/staff/save-permissions', ['id' => 2, 'permissions' => ['pos.sell', 'refund.manage']]);
    check('Tarik + tambah serentak disimpan', count(vendorOverrides(2)) === 3);
    check('Set semula ke lalai → tiada pengecualian', $ahmad->post('vendor/staff/save-permissions', ['id' => 2, 'reset' => true])['ok'] && count(vendorOverrides(2)) === 0);

    echo "7. Kakitangan tanpa sebarang kebenaran menu\n";
    $ahmad->post('vendor/staff/save-permissions', ['id' => 2, 'permissions' => []]);
    $empty = $kasim->page('vendor/index.php');
    check('Dialih ke log masuk dengan notis no_access & sesi ditamatkan', $empty['status'] === 302 && strpos($empty['location'], 'reason=no_access') !== false
        && !empty($kasim->get('vendor/cooperatives/list')['auth_required']));
    check('Halaman log masuk memapar notis', strpos($kasim->page('vendor/login.php?reason=no_access')['body'], e(t('auth.no_access'))) !== false);
    $ahmad->post('vendor/staff/save-permissions', ['id' => 2, 'reset' => true]);

    echo "8. Owner: owner_staff\n";
    list($owner) = ownerLogin($base, 'owner@koperasiku.my');
    list($staff) = ownerLogin($base, 'staf@koperasiku.my');
    $owner->page('owner/staff.php');
    check('Sebelum: owner_staff → plans/list 403, audit dibenarkan', $staff->getStatus('owner/plans/list') === 403 && $staff->get('owner/audit-logs/list')['ok']);
    $ownerPerm = $owner->get('owner/staff/permissions', ['id' => 2]);
    $ownerItems = array_column(array_merge(...array_map(function ($g) { return $g['items']; }, $ownerPerm['data']['groups'])), null, 'code');
    check('Modal owner_staff: boleh disunting, settings.manage dikunci, audit.view lalai', $ownerPerm['ok'] && $ownerPerm['data']['editable']
        && $ownerItems['settings.manage']['locked'] === true && $ownerItems['audit.view']['default'] === true);
    $grant = $owner->post('owner/staff/save-permissions', ['id' => 2, 'permissions' => ['dashboard.view', 'vendor.view', 'vendor.note', 'cooperative.view',
        'subscription.view', 'plan.manage', 'settings.manage']]);
    $rows = Database::fetchAll("SELECT permission, allowed FROM platform_user_permissions WHERE platform_user_id = 2 ORDER BY permission");
    check('Simpan: +plan.manage, -audit.view; settings.manage (had) diabaikan', $grant['ok']
        && $rows === [['permission' => 'audit.view', 'allowed' => 0], ['permission' => 'plan.manage', 'allowed' => 1]]);
    check('owner_staff kini boleh plans/list & halaman pelan', $staff->get('owner/plans/list')['ok'] && $staff->page('owner/plans.php')['status'] === 200);
    check('owner_staff kini tidak boleh log audit (API 403 & halaman dialih)', $staff->getStatus('owner/audit-logs/list') === 403
        && $staff->page('owner/audit-log.php')['status'] === 302);
    check('owner_staff masih tidak boleh tetapan', $staff->getStatus('owner/settings/get') === 403);
    check('owner_staff tidak boleh API kebenaran (403)', $staff->getStatus('owner/staff/permissions.php?id=2') === 403);
    check('Owner tidak boleh sunting diri sendiri / owner', $owner->post('owner/staff/save-permissions', ['id' => 1, 'permissions' => []])['ok'] === false);
    check('Log audit staff.permissions', Database::fetchValue("SELECT COUNT(*) FROM audit_logs WHERE action = 'staff.permissions' AND entity_id = 2") > 0);
    $owner->post('owner/staff/save', ['id' => 2, 'full_name' => 'Siti Aminah', 'email' => 'staf@koperasiku.my', 'role' => 'owner']);
    check('Tukar peranan owner_staff → owner: pengecualian dibuang',
        (int) Database::fetchValue("SELECT COUNT(*) FROM platform_user_permissions WHERE platform_user_id = 2") === 0);
    $owner->post('owner/staff/save', ['id' => 2, 'full_name' => 'Siti Aminah', 'email' => 'staf@koperasiku.my', 'role' => 'owner_staff']);

    echo "9. Halaman\n";
    $ownerStaffPage = $owner->page('owner/staff.php');
    $vendorStaffPage = $ahmad->page('vendor/staff.php');
    check('Modal kebenaran & skrip dimuat di halaman kakitangan owner & vendor', hasLink($ownerStaffPage['body'], 'permission-modal')
        && hasLink($vendorStaffPage['body'], 'js/permission-editor.js'));
    check('Sesi vendor tidak boleh API kebenaran owner (401)', !empty($ahmad->get('owner/staff/permissions', ['id' => 2])['auth_required']));
} finally {
    foreach ($savedUsers as $user) {
        Database::execute("UPDATE platform_users SET role = ?, totp_secret = ?, totp_last_step = ? WHERE id = ?",
            [$user['role'], $user['totp_secret'], $user['totp_last_step'], $user['id']]);
    }
    Database::execute("DELETE FROM totp_recovery_codes WHERE user_type = 'owner'");
    foreach ($savedCodes as $code) {
        Database::insert(
            "INSERT INTO totp_recovery_codes (user_type, user_id, code_hash, used_at, created_at) VALUES (?, ?, ?, ?, ?)",
            [$code['user_type'], $code['user_id'], $code['code_hash'], $code['used_at'], $code['created_at']]
        );
    }
    foreach ($savedVendorUsers as $user) {
        Database::execute("UPDATE vendor_users SET role = ? WHERE id = ?", [$user['role'], $user['id']]);
    }
    Database::execute("DELETE FROM vendor_user_permissions");
    Database::execute("DELETE FROM platform_user_permissions");
    clearTestLocks();
    proc_terminate($server);
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
