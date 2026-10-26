<?php
// Kod modul add-on aktif untuk koperasi (menu add-on panel).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListActiveModulesJob(VendorAuth::vendorId(), $cooperative['id']);
Response::ok($job->run());
