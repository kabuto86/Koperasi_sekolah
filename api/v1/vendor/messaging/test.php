<?php
// Mesej ujian ke baris gilir.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'whatsapp_sms');

$job = new SendTestMessageJob($cooperative, Request::string('phone'));
Response::ok($job->run());
