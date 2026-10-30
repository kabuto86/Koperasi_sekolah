<?php
// Hantar push apps Flutter dari notification_queue (modul mobile_app — B15). CLI sahaja — jalankan setiap minit:
//   * * * * * php /laluan/Koperasi_sekolah/cron/send-push.php
// Windows (XAMPP): Task Scheduler → C:\xampp\php\php.exe C:\xampp\htdocs\Koperasi_sekolah\cron\send-push.php
// Perlu fail akaun perkhidmatan Firebase (config fcm_service_account_file) — tanpanya push kekal dalam baris gilir.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/core/bootstrap.php';

// Satu larian sahaja pada satu masa
$lock = fopen(APP_ROOT . '/storage/send-push.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Larian lain masih berjalan.\n";
    exit(0);
}

$result = (new SendPushJob())->run();
if (!$result['configured']) {
    echo "Akaun perkhidmatan Firebase belum dipasang (config fcm_service_account_file). Push kekal dalam queue.\n";
    exit(0);
}
echo 'Dihantar: ' . $result['sent'] . ' · cuba semula: ' . $result['retry'] . ' · gagal: ' . $result['failed'] . "\n";
