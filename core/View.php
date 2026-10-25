<?php
// Fungsi bantu paparan: URL, aset dan partial.
class View
{
    private static $baseUrl = null;

    // Laluan asas aplikasi, cth. '' (php -S) atau '/Koperasi_sekolah' (XAMPP)
    public static function baseUrl()
    {
        if (self::$baseUrl !== null) {
            return self::$baseUrl;
        }

        $configured = Config::get('base_url');
        if ($configured !== null) {
            self::$baseUrl = rtrim($configured, '/');
            return self::$baseUrl;
        }

        self::$baseUrl = self::detectBaseUrl();
        return self::$baseUrl;
    }

    private static function detectBaseUrl()
    {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $appRoot = realpath(APP_ROOT);
        if ($docRoot === false || $appRoot === false) {
            return '';
        }

        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        $appRoot = str_replace('\\', '/', $appRoot);
        if (strpos($appRoot, $docRoot) !== 0) {
            return '';
        }

        return rtrim(substr($appRoot, strlen($docRoot)), '/');
    }

    // URL halaman, cth. View::url('vendor/orders.php')
    public static function url($path = '')
    {
        return self::baseUrl() . '/' . ltrim($path, '/');
    }

    // URL aset dengan versi untuk pecah cache
    public static function asset($path)
    {
        return self::url('assets/' . ltrim($path, '/')) . '?v=' . Config::get('asset_version');
    }

    // Masukkan partial dengan pemboleh ubah tempatan
    public static function partial($file, $vars = [])
    {
        extract($vars);
        require APP_ROOT . '/' . $file;
    }

    // URL halaman store ibu bapa. Peringkat 1: store/index.php?school=slug&page=...
    // (B6 boleh tukar kepada /{slug}/{page} melalui .htaccess tanpa ubah template)
    // Tanpa slug (belum pilih sekolah) → hanya halaman akaun / pilih sekolah
    public static function storeUrl($slug, $page = 'home', $params = [])
    {
        $query = [];
        if ($slug !== '') {
            $query['school'] = $slug;
        }
        if ($page !== 'home') {
            $query['page'] = $page;
        }
        $query = array_merge($query, $params);

        $url = self::url('store/index.php');
        return $query ? $url . '?' . http_build_query($query) : $url;
    }
}
