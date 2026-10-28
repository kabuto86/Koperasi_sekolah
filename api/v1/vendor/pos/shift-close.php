<?php
// Tutup syif sendiri (kira tunai laci) — juruwang dilog keluar dari kaunter.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
$cashier = PosAuth::requireCashier($cooperative);

$job = new CloseShiftJob(VendorAuth::vendorId(), $cooperative, $cashier, Request::all());
$result = $job->run();
PosAuth::clear($cooperative['id']);
Response::ok($result);
