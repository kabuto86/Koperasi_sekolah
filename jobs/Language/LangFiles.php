<?php
// Fail bahasa jana (PELAN 12, B14): storage/lang/{kod}.php = lang/{kod}.php (sumber dalam git) + nilai owner dalam jadual
// translations. Lang memuat fail jana; TranslationValues / modul terjemahan membaca sumber untuk tahu nilai asal.
// Ditulis dengan var_export (tiada kod dari input) ke fail sementara → rename (atomik, permintaan serentak tidak nampak separuh fail).
class LangFiles
{
    public static function sourcePath($code)
    {
        return APP_ROOT . '/lang/' . $code . '.php';
    }

    public static function compiledPath($code)
    {
        return APP_ROOT . '/storage/lang/' . $code . '.php';
    }

    // Fail jana wujud dan tidak lebih lama daripada sumbernya
    public static function isFresh($code)
    {
        $compiled = self::compiledPath($code);
        return is_file($compiled) && filemtime($compiled) >= filemtime(self::sourcePath($code));
    }

    // Jana semua bahasa. Pulangkan [ kod => bilangan nilai owner ]
    public static function generate()
    {
        $overrides = [];
        foreach (Database::fetchAll("SELECT language_code, translation_key, value FROM translations") as $row) {
            $overrides[$row['language_code']][$row['translation_key']] = $row['value'];
        }
        $dir = dirname(self::compiledPath('ms'));
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Folder storage/lang tidak dapat dicipta.');
        }
        $counts = [];
        foreach (Config::get('languages') as $code) {
            $strings = array_merge(Lang::sourceStrings($code), $overrides[$code] ?? []);
            self::write($code, $strings);
            $counts[$code] = count($overrides[$code] ?? []);
        }
        return $counts;
    }

    // Untuk Lang::init — gagal (DB / kebenaran folder) tidak memecahkan halaman; sumber dipakai
    public static function tryGenerate()
    {
        try {
            self::generate();
            return true;
        } catch (Throwable $exception) {
            error_log('[KoperasiKu] jana fail bahasa gagal: ' . $exception->getMessage());
            return false;
        }
    }

    private static function write($code, array $strings)
    {
        $file = self::compiledPath($code);
        ksort($strings);
        $php = "<?php\n// DIJANA oleh LangFiles (B14) — jangan sunting. Sumber: lang/" . $code . ".php + jadual translations.\n"
             . 'return ' . var_export($strings, true) . ";\n";
        $temp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($temp, $php, LOCK_EX) === false || !rename($temp, $file)) {
            @unlink($temp);
            throw new RuntimeException('Fail bahasa tidak dapat ditulis: ' . $file);
        }
        // Masa ubah sentiasa ≥ sumber walaupun jam fail sumber di hadapan
        touch($file, max(time(), filemtime(self::sourcePath($code))));
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
    }
}
