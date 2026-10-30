<?php
// Backup harian pangkalan data koperasi_saas (PELAN 15) — mysqldump → gzip → storage/backups/koperasi_saas-YYYYmmdd-HHiiss.sql.gz.
// Kata laluan DB TIDAK dihantar dalam baris arahan (boleh dilihat dalam senarai proses): fail pilihan sementara 0600 dalam storage/.
// Fail siap ditulis sebagai .part dahulu → rename bila mysqldump berjaya (backup separuh tidak pernah kelihatan lengkap).
// Backup lebih lama daripada config backup_keep_days dibuang. Salin backup ke luar pelayan (cth. rclone) — lihat cron/backup-db.php.
class BackupDatabaseJob
{
    const PREFIX = 'koperasi_saas-';

    private $dir;
    private $keepDays;

    public function __construct($dir = null, $keepDays = null)
    {
        $this->dir = $dir ?? APP_ROOT . '/' . trim((string) Config::get('backup_dir'), '/');
        $this->keepDays = max(1, (int) ($keepDays ?? Config::get('backup_keep_days')));
    }

    // [ file, bytes, deleted ]
    public function run()
    {
        if (!is_dir($this->dir) && !mkdir($this->dir, 0700, true) && !is_dir($this->dir)) {
            throw new RuntimeException('Folder backup tidak dapat dicipta: ' . $this->dir);
        }
        $config = require APP_ROOT . '/config/database.php';
        $file = $this->dir . '/' . self::PREFIX . date('Ymd-His') . '.sql.gz';
        $optionFile = $this->optionFile($config);
        try {
            $this->dump($config['database'], $optionFile, $file);
        } finally {
            @unlink($optionFile);
        }
        return ['file' => $file, 'bytes' => filesize($file), 'deleted' => $this->prune()];
    }

    private function optionFile(array $config)
    {
        $quote = function ($value) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value) . '"';
        };
        $path = tempnam(APP_ROOT . '/storage', 'mydump');
        chmod($path, 0600);
        file_put_contents($path, "[client]\nuser=" . $quote($config['username']) . "\npassword=" . $quote($config['password'])
            . "\nhost=" . $quote($config['host']) . "\nport=" . (int) $config['port'] . "\n");
        return $path;
    }

    private function dump($database, $optionFile, $file)
    {
        $command = [
            (string) Config::get('mysqldump_path'), '--defaults-extra-file=' . $optionFile,
            '--single-transaction', '--quick', '--no-tablespaces', '--skip-triggers', '--hex-blob',
            '--default-character-set=utf8mb4', (string) $database,
        ];
        $errorFile = tempnam(sys_get_temp_dir(), 'mydumperr');
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['file', $errorFile, 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('mysqldump tidak dapat dijalankan (config mysqldump_path).');
        }
        $part = $file . '.part';
        $gz = gzopen($part, 'wb6');
        while (!feof($pipes[1])) {
            $chunk = fread($pipes[1], 65536);
            if ($chunk !== false && $chunk !== '') {
                gzwrite($gz, $chunk);
            }
        }
        fclose($pipes[1]);
        gzclose($gz);
        $exitCode = proc_close($process);
        $error = trim((string) file_get_contents($errorFile));
        @unlink($errorFile);
        if ($exitCode !== 0) {
            @unlink($part);
            throw new RuntimeException('mysqldump gagal (' . $exitCode . '): ' . mb_substr($error, 0, 500, 'UTF-8'));
        }
        rename($part, $file);
        chmod($file, 0600);
    }

    // Buang backup lama (nama ikut corak sahaja — fail lain dalam folder tidak disentuh)
    private function prune()
    {
        $deleted = 0;
        $cutoff = time() - $this->keepDays * 86400;
        foreach (glob($this->dir . '/' . self::PREFIX . '*.sql.gz') ?: [] as $old) {
            if (filemtime($old) < $cutoff && unlink($old)) {
                $deleted++;
            }
        }
        return $deleted;
    }
}
