<?php
// Padam kelas.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new DeleteSchoolClassJob(VendorAuth::vendorId(), $cooperative['id'], Request::int('id'));
Response::ok($job->run());
