<?php
// Bayar invois online melalui gateway owner → { redirect_url }. "Dibayar" hanya selepas gateway disahkan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES, true);
Csrf::verifyRequest();

$job = new PayInvoiceJob(VendorAuth::vendorId(), VendorAuth::id(), Request::int('id'));
Response::ok($job->run());
