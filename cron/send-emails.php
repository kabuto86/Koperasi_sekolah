<?php
// Hantar e-mel dari email_queue (PELAN 9 I). CLI sahaja — jalankan setiap minit:
//   * * * * * php /laluan/Koperasi_sekolah/cron/send-emails.php
// Windows (XAMPP): Task Scheduler → C:\xampp\php\php.exe C:\xampp\htdocs\Koperasi_sekolah\cron\send-emails.php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/core/bootstrap.php';

// Satu larian sahaja pada satu masa (cron bertindih bila SMTP perlahan)
$lock = fopen(APP_ROOT . '/storage/send-emails.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Larian lain masih berjalan.\n";
    exit(0);
}

$result = (new SendQueuedEmailsJob())->run();
if (!$result['configured']) {
    echo "SMTP belum ditetapkan (Owner → Tetapan). E-mel kekal dalam queue.\n";
    exit(0);
}
echo 'Dihantar: ' . $result['sent'] . ' · cuba semula: ' . $result['retry'] . ' · gagal: ' . $result['failed'] . "\n";
