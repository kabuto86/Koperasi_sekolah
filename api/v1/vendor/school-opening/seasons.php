<?php
// Senarai musim pre-order + darjah koperasi (modul school_opening).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'school_opening');

$job = new ListPreorderSeasonsJob(VendorAuth::vendorId(), $cooperative);
Response::ok($job->run());
