<?php
// Anggaran harga prorata modul / template hingga hujung tempoh langganan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new GetAddonQuoteJob(VendorAuth::vendorId(), $cooperative, Request::all());
Response::ok($job->run());
