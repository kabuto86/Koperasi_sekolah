<?php
// Tambah beberapa kelas untuk satu darjah.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new AddSchoolClassesJob(VendorAuth::vendorId(), $cooperative, Request::all());
Response::ok($job->run());
