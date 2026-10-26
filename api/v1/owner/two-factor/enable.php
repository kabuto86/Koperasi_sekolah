<?php
// Sahkan kod dengan rahsia baharu → simpan (disulitkan) + kod pemulihan baharu.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireLogin();
Csrf::verifyRequest();

$secret = OwnerAuth::setupSecret();
if ($secret === null) {
    Response::fail(t('auth.session_expired'));
}
$job = new VerifyTotpJob('owner', OwnerAuth::id(), Request::string('code'), $secret);
$result = $job->run();
OwnerAuth::clearSetupSecret();
(new WriteAuditLogJob('owner', OwnerAuth::id(), null, 'two_factor.enable', 'platform_user', OwnerAuth::id()))->run();
Response::ok(['enabled' => true, 'recovery_codes' => $result['recovery_codes']]);
