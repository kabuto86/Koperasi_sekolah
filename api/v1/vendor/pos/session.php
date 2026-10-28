<?php
// Keadaan kaunter POS: koperasi, juruwang PIN, syif terbuka, QR online, senarai kakitangan untuk log masuk PIN.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new GetPosSessionJob(VendorAuth::vendorId(), $cooperative, PosAuth::cashier($cooperative));
Response::ok($job->run());
