<?php
// Terjemahan: fail bahasa dimuat sekali.
// Kunci hilang → nilai Bahasa Melayu → kunci itu sendiri (tidak pernah kosong).
// Sejak B14: storage/lang/{kod}.php = lang/{kod}.php + nilai owner dalam jadual translations (dijana oleh LangFiles).
// Fail jana lebih lama daripada lang/*.php (selepas deploy) → dijana semula; gagal → lang/*.php sahaja.
class Lang
{
    private static $code = 'ms';
    private static $strings = [];
    private static $fallback = [];

    public static function init()
    {
        self::$code = self::detectCode();
        self::$fallback = self::loadFile('ms');
        self::$strings = (self::$code === 'ms') ? self::$fallback : self::loadFile(self::$code);
    }

    // Pilihan bahasa: ?lang= (disimpan dalam cookie) → cookie → lalai
    private static function detectCode()
    {
        $fromQuery = $_GET['lang'] ?? '';
        if (self::isSupported($fromQuery)) {
            self::rememberCode($fromQuery);
            return $fromQuery;
        }

        $fromCookie = $_COOKIE['lang'] ?? '';
        if (self::isSupported($fromCookie)) {
            return $fromCookie;
        }

        return Config::get('default_language');
    }

    private static function rememberCode($code)
    {
        if (headers_sent()) {
            return;
        }

        setcookie('lang', $code, [
            'expires'  => time() + 365 * 24 * 3600,
            'path'     => View::baseUrl() . '/',
            'secure'   => SecurityHeaders::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['lang'] = $code;
    }

    public static function isSupported($code)
    {
        return is_string($code) && in_array($code, Config::get('languages'), true);
    }

    // Fail yang dipakai semasa berjalan: jana (jika terkini) atau sumber
    private static function loadFile($code)
    {
        $source = LangFiles::sourcePath($code);
        if (!is_file($source)) {
            return [];
        }
        if (!LangFiles::isFresh($code) && !LangFiles::tryGenerate()) {
            return require $source;
        }
        return require LangFiles::compiledPath($code);
    }

    // lang/{kod}.php sahaja (tanpa nilai owner) — asas modul terjemahan
    public static function sourceStrings($code)
    {
        if (!isset(self::$sources[$code])) {
            $file = LangFiles::sourcePath($code);
            self::$sources[$code] = self::isSupported($code) && is_file($file) ? require $file : [];
        }
        return self::$sources[$code];
    }

    private static $sources = [];

    public static function code()
    {
        return self::$code;
    }

    public static function get($key, $params = [])
    {
        $text = self::$strings[$key] ?? self::$fallback[$key] ?? $key;

        foreach ($params as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }

        return $text;
    }

    private static $files = [];

    // Fail bahasa lain (dicache) — untuk e-mel ikut bahasa penerima & nilai asal terjemahan (B3)
    private static function fileFor($code)
    {
        if (!isset(self::$files[$code])) {
            self::$files[$code] = self::isSupported($code) ? self::loadFile($code) : [];
        }
        return self::$files[$code];
    }

    // Nilai kunci dalam lang/{kod}.php sahaja (tiada jatuh balik, tiada nilai owner) atau null
    public static function fileValue($code, $key)
    {
        $strings = self::sourceStrings($code);
        return $strings[$key] ?? null;
    }

    // Teks dalam bahasa lain (cth. e-mel ikut bahasa penerima, bukan bahasa permintaan)
    public static function getFor($code, $key, $params = [])
    {
        $strings = self::fileFor($code);
        $text = $strings[$key] ?? self::$fallback[$key] ?? $key;
        foreach ($params as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }
        return $text;
    }

    // Semua teks (dengan jatuh balik Melayu) untuk T() dalam JS
    public static function json()
    {
        $merged = array_merge(self::$fallback, self::$strings);
        return json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // URL halaman semasa dengan ?lang= ditukar
    public static function switchUrl($code)
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $query = $_GET;
        $query['lang'] = $code;

        return $path . '?' . http_build_query($query);
    }
}

// Terjemah kunci. Contoh: t('pickup.collected_by', ['name' => $n])
function t($key, $params = [])
{
    return Lang::get($key, $params);
}

// Escape untuk HTML. Semua output data mesti melalui e()
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
