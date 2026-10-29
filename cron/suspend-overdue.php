<?php
// Invois tertunggak (PELAN 14) — SuspendOverdueJob: melepasi tarikh akhir → past_due; melepasi tarikh akhir + tempoh tangguh
// (tetapan platform grace_days) → suspended (store "Koperasi ditutup sementara", vendor_admin hanya boleh membayar).
// CLI sahaja — jalankan setiap jam:
//   15 * * * * php /laluan/Koperasi_sekolah/cron/suspend-overdue.php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/core/bootstrap.php';

$result = (new SuspendOverdueJob())->run();
echo 'Vendor tertunggak (past_due): ' . $result['past_due'] . ' · Digantung: ' . $result['suspended'] . "\n";
