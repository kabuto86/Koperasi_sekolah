<?php
// Kitaran bil langganan vendor (PELAN 14) — RunBillingCycleJob:
//   1. draf invois pembaharuan 7 hari sebelum current_period_end (owner boleh semak; vendor tidak nampak draf)
//   2. tempoh tamat → pusingan (pertukaran pelan, modul dihentikan tamat, trial → active) + invois tempoh baharu diterbitkan & e-mel
// CLI sahaja — jalankan setiap hari selepas tengah malam:
//   5 0 * * * php /laluan/Koperasi_sekolah/cron/generate-invoices.php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/core/bootstrap.php';

$result = (new RunBillingCycleJob())->run();
echo 'Draf invois pembaharuan: ' . $result['drafts'] . ' · Langganan diperbaharui: ' . $result['renewed'] . "\n";
