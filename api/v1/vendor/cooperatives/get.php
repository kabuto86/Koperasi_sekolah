<?php
// Butiran koperasi + waktu operasi (pengurus: koperasi yang diberi sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('id'));

$job = new GetCooperativeJob(VendorAuth::vendorId(), $cooperative['id']);
$data = $job->run();
if ($data === null) {
    Response::notFound();
}
Response::ok($data);
