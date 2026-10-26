<?php
// Senarai kelas koperasi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListSchoolClassesJob(VendorAuth::vendorId(), $cooperative['id']);
Response::ok($job->run());
