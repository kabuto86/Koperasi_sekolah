<?php
// Jualan POS — pelayan mengira semula harga, stok, promosi & cukai. Tunai terus selesai; online → bil QR gateway.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
$cashier = PosAuth::requireCashier($cooperative);
RateLimit::enforce('pos_checkout:' . $cashier['id'], 120, 60);

$job = new PosCheckoutJob(VendorAuth::vendorId(), $cooperative, $cashier, Request::all());
Response::ok($job->run());
