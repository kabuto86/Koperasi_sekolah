<?php
// Padam acara kalendar.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'calendar');

$job = new DeleteCalendarEventJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::int('id'));
Response::ok($job->run());
