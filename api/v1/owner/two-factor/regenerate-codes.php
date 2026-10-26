<?php
// Jana semula kod pemulihan (perlu kod TOTP semasa). 2FA owner tidak boleh dimatikan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireLogin();
Csrf::verifyRequest();

$job = new RegenerateRecoveryCodesJob('owner', OwnerAuth::id(), Request::string('code'));
$result = $job->run();
(new WriteAuditLogJob('owner', OwnerAuth::id(), null, 'two_factor.regenerate_codes', 'platform_user', OwnerAuth::id()))->run();
Response::ok($result);
