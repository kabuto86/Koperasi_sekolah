<?php
// Pilihan template untuk borang koperasi (subscribed = percuma / dilanggan untuk koperasi ini; koperasi baharu = percuma sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);

$cooperativeId = Request::int('cooperative_id') ?: 0;
if ($cooperativeId > 0) {
    $cooperativeId = VendorAuth::requireCooperative($cooperativeId)['id'];
}

$job = new ListTemplateOptionsJob(VendorAuth::vendorId(), $cooperativeId);
Response::ok($job->run());
