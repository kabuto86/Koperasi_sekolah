<?php
// Simpan tetapan WhatsApp/SMS (kunci API kosong = kekal).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'whatsapp_sms');

$job = new SaveMessagingSettingsJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::all());
Response::ok($job->run());
