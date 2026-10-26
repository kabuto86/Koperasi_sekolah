<?php
// Terbit / tarik balik kit.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new PublishKitJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::int('id'), Request::bool('is_published'));
Response::ok($job->run());
