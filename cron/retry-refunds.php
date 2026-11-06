<?php
// Refund gateway yang tersangkut `pending` (W2) dicuba semula dengan kunci idempotensi yang sama (refund_no):
// berjaya → completed (stok & status pesanan dikemas kini), ditolak gateway → failed. Lebih 23 jam → kekal pending
// dan dilaporkan "perlu semakan manual" (semak papan pemuka gateway). CLI sahaja — jalankan setiap 15 minit:
//   */15 * * * * php /laluan/Koperasi_sekolah/cron/retry-refunds.php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/core/bootstrap.php';

$lock = fopen(APP_ROOT . '/storage/retry-refunds.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Larian lain masih berjalan.\n";
    exit(0);
}
$counts = (new RetryPendingRefundsJob())->run();
echo 'Refund selesai: ' . $counts['completed'] . ' · gagal: ' . $counts['failed'] . ' · perlu semakan manual: ' . $counts['manual'] . "\n";
flock($lock, LOCK_UN);
