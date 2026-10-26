<?php
// Semak slug store: format + unik seluruh platform (tidak dedah pemilik).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);

$job = new CheckSlugJob(VendorAuth::vendorId(), Request::string('slug'), (int) Request::int('id', 0));
Response::ok($job->run());
