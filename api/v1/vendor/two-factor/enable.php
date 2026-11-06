<?php
// Sahkan kod dengan rahsia baharu → simpan (disulitkan) + kod pemulihan baharu (dipapar sekali).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES, true);
Csrf::verifyRequest();

$secret = VendorAuth::setupSecret();
if ($secret === null) {
    Response::fail(t('auth.session_expired'));
}
$job = new VerifyTotpJob('vendor_user', VendorAuth::id(), Request::string('code'), $secret);
$result = $job->run();
VendorAuth::clearSetupSecret();
(new WriteActivityLogJob(VendorAuth::vendorId(), null, VendorAuth::id(), 'two_factor_enable', 'vendor_user', VendorAuth::id()))->run();
Response::ok(['enabled' => true, 'recovery_codes' => $result['recovery_codes']]);
