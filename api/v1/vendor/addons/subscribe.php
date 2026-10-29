<?php
// Langgan modul / template untuk koperasi semasa: aktif serta-merta + invois prorata.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

if (Request::string('item_type') === 'template') {
    $job = new SubscribeTemplateJob(VendorAuth::vendorId(), $cooperative, VendorAuth::id(), Request::string('code'));
} else {
    $job = new SubscribeModuleJob(VendorAuth::vendorId(), $cooperative, VendorAuth::id(), Request::string('code'));
}
Response::ok($job->run());
