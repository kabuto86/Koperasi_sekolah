<?php
// Batalkan invois draf / belum dibayar — owner sahaja.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new VoidInvoiceJob(OwnerAuth::id(), Request::int('id'));
Response::ok($job->run());
