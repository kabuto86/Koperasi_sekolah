<?php
// Log aktiviti vendor (semua koperasi, atau scope=current + cooperative_id). Pengurus: koperasi yang diberi sahaja.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
// cooperative_id sentiasa disahkan bila dihantar (B14: koperasi vendor lain → 404 walaupun skop semua koperasi)
$cooperativeId = null;
if (Request::string('scope') === 'current' || Request::int('cooperative_id') > 0) {
    $cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
    $cooperativeId = Request::string('scope') === 'current' ? $cooperative['id'] : null;
}
$user = VendorAuth::user();
$allowed = $user['role'] === 'vendor_admin' ? null : $user['cooperative_ids'];

$job = new ListActivityLogsJob(VendorAuth::vendorId(), $allowed, $cooperativeId, Request::all());
Response::ok($job->run());
