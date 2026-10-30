<?php
// Hantar WhatsApp/SMS dari notification_queue (modul whatsapp_sms — B13). CLI sahaja — jalankan setiap minit:
//   * * * * * php /laluan/Koperasi_sekolah/cron/send-messages.php
// Windows (XAMPP): Task Scheduler → C:\xampp\php\php.exe C:\xampp\htdocs\Koperasi_sekolah\cron\send-messages.php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/core/bootstrap.php';

// Satu larian sahaja pada satu masa (pembekal perlahan)
$lock = fopen(APP_ROOT . '/storage/send-messages.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Larian lain masih berjalan.\n";
    exit(0);
}

$result = (new SendQueuedMessagesJob())->run();
echo 'Dihantar: ' . $result['sent'] . ' · cuba semula: ' . $result['retry'] . ' · gagal: ' . $result['failed'] . "\n";
