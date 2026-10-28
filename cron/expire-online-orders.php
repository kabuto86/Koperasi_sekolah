<?php
// Pesanan online pending_payment yang lepas expires_at (config online_payment_expiry_minutes): bil disemak semula ke
// gateway dahulu (bayaran yang panggilan baliknya hilang → paid), selebihnya diluputkan & tahanan stok dilepas (B8).
// CLI sahaja — jalankan setiap 10 minit:
//   */10 * * * * php /laluan/Koperasi_sekolah/cron/expire-online-orders.php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/core/bootstrap.php';

$lock = fopen(APP_ROOT . '/storage/expire-online-orders.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Larian lain masih berjalan.\n";
    exit(0);
}
$counts = (new ExpirePendingPaymentsJob())->run();
echo 'Pesanan online diluputkan: ' . $counts['expired'] . ' · disahkan dibayar: ' . $counts['paid'] . ' · cuba semula: ' . $counts['retry'] . "\n";
flock($lock, LOCK_UN);
