<?php
// Nota dalaman tentang vendor.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('vendor.note');
Csrf::verifyRequest();

$job = new AddVendorNoteJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
