<?php
// Serah barang: rekod pengambil & item, tolak stok, e-mel ibu bapa. B10: kakitangan = juruwang PIN POS jika ada.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new HandoverOrderJob(VendorAuth::vendorId(), $cooperative, PosAuth::counterUserId($cooperative), Request::all());
Response::ok($job->run());
