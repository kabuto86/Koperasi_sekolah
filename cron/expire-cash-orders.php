<?php
// Luputkan pesanan tunai yang tidak diambil (expires_at lepas) dan lepaskan tahanan stok (PELAN 6, 16 perkara 4).
// CLI sahaja — jalankan setiap jam:
//   0 * * * * php /laluan/Koperasi_sekolah/cron/expire-cash-orders.php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/core/bootstrap.php';

$count = (new ExpireCashOrdersJob())->run();
echo 'Pesanan tunai diluputkan: ' . $count . "\n";
