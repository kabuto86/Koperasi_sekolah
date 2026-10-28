<?php
// Label beg (satu setiap anak setiap pesanan) dengan token QR untuk dicetak.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new PrintLabelsJob(VendorAuth::vendorId(), $cooperative, Request::all());
Response::ok($job->run());
