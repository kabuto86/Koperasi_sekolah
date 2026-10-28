<?php
// Rekod refund: batal / pulang barang (stok melalui StockLedger), tunai / pindahan bank / gateway (jika disokong).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new RefundOrderJob(VendorAuth::vendorId(), $cooperative, VendorAuth::id(), PosAuth::counterUserId($cooperative), Request::all());
Response::ok($job->run());
