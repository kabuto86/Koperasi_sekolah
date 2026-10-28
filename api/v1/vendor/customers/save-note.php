<?php
// Simpan nota dalaman vendor tentang ibu bapa.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new SaveCustomerNoteJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::int('customer_id'),
    Request::string('note'));
Response::ok($job->run());
