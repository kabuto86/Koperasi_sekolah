<?php
// Butiran stock take + item.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new GetStockTakeJob(VendorAuth::vendorId(), $cooperative['id'], Request::int('id'));
$data = $job->run();
if ($data === null) {
    Response::notFound();
}
Response::ok($data);
