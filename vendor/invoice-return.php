<?php
// Alamat kembali dari gateway owner selepas bayar invois (GET atau POST — iPay88 menghantar POST). Parameter gateway TIDAK
// dibaca: vendor dibawa (303 → GET, cookie sesi SameSite=Lax dihantar) ke vendor/invoices.php?paid_return= yang meminta
// pelayan menyemak status ke API gateway (vendor/invoices/check-payment).
require __DIR__ . '/../core/bootstrap.php';

$invoiceId = $_GET['invoice'] ?? '';
$target = (is_string($invoiceId) && preg_match('/^\d{1,10}$/', $invoiceId))
    ? 'vendor/invoices.php?paid_return=' . (int) $invoiceId : 'vendor/invoices.php';
header('Location: ' . View::url($target), true, 303);
exit;
