<?php
// Alamat kembali dari gateway (GET atau POST — iPay88 menghantar POST). Parameter gateway TIDAK dibaca:
// ibu bapa dibawa ke halaman pesanan berjaya yang meminta pelayan menyemak status ke API gateway (store/orders/check-payment).
require __DIR__ . '/../core/bootstrap.php';

$slug = $_GET['school'] ?? '';
$orderId = $_GET['order'] ?? '';
if (!is_string($slug) || !preg_match('/^[a-z0-9-]{1,80}$/', $slug) || !is_string($orderId) || !preg_match('/^\d{1,10}$/', $orderId)) {
    header('Location: ' . View::storeUrl('', 'home'), true, 303);
    exit;
}
header('Location: ' . View::storeUrl($slug, 'order-success', ['order' => (int) $orderId, 'returned' => 1]), true, 303);
