<?php
// Serah barang: rekod pengambil & item, tolak stok, e-mel ibu bapa. B10: kakitangan = juruwang PIN POS jika ada.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();
// Kod ambil 4 digit: setiap pesanan dikunci selepas 5 salah; had ini menghalang cubaan merentas banyak pesanan (B14)
RateLimit::enforce('pickup_code:' . VendorAuth::id(), 300, 600);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new HandoverOrderJob(VendorAuth::vendorId(), $cooperative, PosAuth::counterUserId($cooperative), Request::all());
Response::ok($job->run());
