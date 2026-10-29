<?php
// Jadual tukar pelan / kitaran pada tarikh pembaharuan (tiada langganan → langganan baharu serta-merta).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES, true);
Csrf::verifyRequest();

$job = new ChangePlanJob(VendorAuth::vendorId(), VendorAuth::id(), Request::all());
Response::ok($job->run());
