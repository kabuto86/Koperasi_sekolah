<?php
// Papan pemuka vendor untuk koperasi semasa.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new GetVendorDashboardJob(VendorAuth::vendorId(), $cooperative['id']);
Response::ok($job->run());
