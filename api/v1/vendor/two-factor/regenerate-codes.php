<?php
// Jana semula kod pemulihan (perlu kod TOTP semasa).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES, true);
Csrf::verifyRequest();

$job = new RegenerateRecoveryCodesJob('vendor_user', VendorAuth::id(), Request::string('code'));
$result = $job->run();
(new WriteActivityLogJob(VendorAuth::vendorId(), null, VendorAuth::id(), 'two_factor_regenerate', 'vendor_user', VendorAuth::id()))->run();
Response::ok($result);
