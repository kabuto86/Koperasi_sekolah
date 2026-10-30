<?php
// Simpan terjemahan satu kunci → jana semula fail bahasa (owner sahaja — B14).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SaveTranslationJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
