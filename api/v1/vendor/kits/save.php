<?php
// Simpan kit (draf / terbit).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new SaveKitJob(VendorAuth::vendorId(), $cooperative['id'], $cooperative['school_type'], VendorAuth::id(), Request::all());
Response::ok($job->run());
