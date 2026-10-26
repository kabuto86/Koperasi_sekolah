<?php
// Tukar nama kelas.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new RenameSchoolClassJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
