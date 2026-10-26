<?php
// Log masuk owner langkah 2: kod TOTP (atau kod pemulihan). Log masuk pertama: sahkan rahsia baharu → kod pemulihan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::start();
Csrf::verifyRequest();

$pending = OwnerAuth::pending();
if ($pending === null) {
    Response::fail(t('auth.session_expired'));
}
$job = new VerifyTotpJob('owner', $pending['id'], Request::string('code'), $pending['setup_secret']);
$result = $job->run();
OwnerAuth::completeLogin($pending['id']);

$user = OwnerAuth::user();
(new WriteAuditLogJob('owner', $user['id'], null, 'auth.login', 'platform_user', $user['id']))->run();
Response::ok([
    'user'           => ['id' => $user['id'], 'name' => $user['full_name'], 'role' => $user['role']],
    'recovery_codes' => $result['recovery_codes'],
]);
