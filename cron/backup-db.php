<?php
// Backup harian pangkalan data (PELAN 15, B14). CLI sahaja — jalankan sekali sehari (waktu senyap):
//   15 3 * * * php /laluan/Koperasi_sekolah/cron/backup-db.php
// Windows (XAMPP): Task Scheduler → C:\xampp\php\php.exe C:\xampp\htdocs\Koperasi_sekolah\cron\backup-db.php
//   dan set config mysqldump_path = 'C:/xampp/mysql/bin/mysqldump.exe'
// Hasil: storage/backups/koperasi_saas-YYYYmmdd-HHiiss.sql.gz (disimpan backup_keep_days hari). SALIN ke luar pelayan juga
// (cth. rclone / rsync ke storan lain) — backup pada cakera yang sama tidak melindungi daripada cakera rosak.
// Pulih (pangkalan data kosong / ujian dahulu):
//   gunzip -c storage/backups/koperasi_saas-….sql.gz | mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/core/bootstrap.php';

$lock = fopen(APP_ROOT . '/storage/backup-db.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Larian lain masih berjalan.\n";
    exit(0);
}

try {
    $result = (new BackupDatabaseJob())->run();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Backup GAGAL: ' . $exception->getMessage() . "\n");
    exit(1);
}
echo 'Backup: ' . $result['file'] . ' (' . number_format($result['bytes']) . ' bait) · dibuang: ' . $result['deleted'] . "\n";
