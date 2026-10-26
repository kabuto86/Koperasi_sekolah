<?php
// Padam kategori (ditolak jika masih ada produk).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new DeleteCategoryJob(VendorAuth::vendorId(), $cooperative['id'], Request::int('id'));
Response::ok($job->run());
