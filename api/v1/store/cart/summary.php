<?php
// Bilangan item troli untuk header (0 jika belum log masuk) — awam.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
$cooperative = StoreSchool::require(Request::string('slug'));

$customerId = CustomerAuth::id();
Response::ok(['item_count' => $customerId ? Carts::count($customerId, $cooperative) : 0]);
