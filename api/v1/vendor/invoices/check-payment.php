<?php
// Selepas kembali dari gateway: pelayan bertanya sendiri status bil ke gateway owner.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES, true);
Csrf::verifyRequest();
RateLimit::enforce('invoice_check:' . VendorAuth::vendorId(), 20, 60);

$job = new CheckInvoicePaymentJob(VendorAuth::vendorId(), Request::int('id'));
Response::ok($job->run());
