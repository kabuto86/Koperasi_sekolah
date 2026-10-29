<?php
// Tanda invois dibayar secara manual (pindahan bank, tunai, cek, lain-lain) — owner sahaja.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new MarkInvoicePaidJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
