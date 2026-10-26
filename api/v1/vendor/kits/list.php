<?php
// Senarai kit koperasi untuk satu tahun persekolahan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListKitsJob(VendorAuth::vendorId(), $cooperative['id'], $cooperative['school_type'], Request::all());
Response::ok($job->run());
