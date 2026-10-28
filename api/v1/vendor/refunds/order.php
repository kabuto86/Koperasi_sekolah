<?php
// Pesanan dibayar untuk borang refund (order_id atau q = no. pesanan / kod pendek) + baki boleh direfund.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new FindRefundOrderJob(VendorAuth::vendorId(), $cooperative, PosAuth::counterUserId($cooperative), Request::all());
Response::ok($job->run());
