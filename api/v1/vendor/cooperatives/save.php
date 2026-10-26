<?php
// Tambah koperasi (pentadbir sahaja — had pelan & harga) atau sunting koperasi yang boleh dibuka.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
if (Request::int('id')) {
    VendorAuth::requireCooperative(Request::int('id'));
} elseif (VendorAuth::role() !== 'vendor_admin') {
    Response::forbidden();
}

$job = new SaveCooperativeJob(VendorAuth::vendorId(), VendorAuth::id(), Request::all());
Response::ok($job->run());
