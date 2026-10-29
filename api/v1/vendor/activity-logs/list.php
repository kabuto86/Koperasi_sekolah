<?php
// Log aktiviti vendor (semua koperasi, atau scope=current + cooperative_id). Pengurus: koperasi yang diberi sahaja.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperativeId = null;
if (Request::string('scope') === 'current') {
    $cooperativeId = VendorAuth::requireCooperative(Request::int('cooperative_id'))['id'];
}
$user = VendorAuth::user();
$allowed = $user['role'] === 'vendor_admin' ? null : $user['cooperative_ids'];

$job = new ListActivityLogsJob(VendorAuth::vendorId(), $allowed, $cooperativeId, Request::all());
Response::ok($job->run());
