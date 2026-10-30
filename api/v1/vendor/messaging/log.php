<?php
// Log mesej WhatsApp/SMS (nombor bertopeng).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'whatsapp_sms');

$job = new ListMessageLogJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
