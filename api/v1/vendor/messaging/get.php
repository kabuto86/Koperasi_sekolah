<?php
// Tetapan WhatsApp/SMS koperasi (modul whatsapp_sms). Kunci API tidak dipulangkan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'whatsapp_sms');

$job = new GetMessagingSettingsJob(VendorAuth::vendorId(), $cooperative['id']);
Response::ok($job->run());
