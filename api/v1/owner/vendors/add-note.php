<?php
// Nota dalaman tentang vendor (owner & owner_staff — sokongan).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner', 'owner_staff']);
Csrf::verifyRequest();

$job = new AddVendorNoteJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
