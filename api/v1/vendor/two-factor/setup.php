<?php
// Rahsia TOTP baharu untuk kakitangan vendor semasa (2FA pilihan). Disimpan dalam sesi sehingga disahkan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES, true);

$job = new SetupTotpJob('vendor_user', VendorAuth::id());
$setup = $job->run();
VendorAuth::setSetupSecret($setup['secret']);
Response::ok($setup);
