<?php
// Profil ibu bapa sesi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
CustomerAuth::requireLogin();

Response::ok(LoadCustomerSessionJob::publicShape(CustomerAuth::customer()));
