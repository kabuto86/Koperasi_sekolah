<?php
// Langganan vendor sesi: pelan, penggunaan, koperasi & modul, anggaran pembaharuan. Dibenarkan semasa digantung (bayar sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES, true);

$job = new GetVendorSubscriptionJob(VendorAuth::vendorId());
Response::ok($job->run());
