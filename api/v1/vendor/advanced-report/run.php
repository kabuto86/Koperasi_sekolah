<?php
// Laporan lanjutan koperasi (modul advanced_report): year_compare, product_compare, kit_summary, kit_items. CSV dijana di pelayar.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'advanced_report');

$job = new RunAdvancedReportJob(VendorAuth::vendorId(), $cooperative, Request::all());
Response::ok($job->run());
