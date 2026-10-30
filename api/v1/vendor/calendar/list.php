<?php
// Acara kalendar dalam julat tarikh (modul calendar).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'calendar');

$job = new ListCalendarEventsJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
