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

    // URL penuh (https://hos/...) untuk pautan dalam e-mel. config app_url atau dari permintaan semasa
    public static function absoluteUrl($path = '')
    {
        $appUrl = Config::get('app_url');
        if ($appUrl) {
            return rtrim($appUrl, '/') . '/' . ltrim($path, '/');
        }
        $scheme = SecurityHeaders::isHttps() ? 'https' : 'http';
        $host = preg_replace('/[^A-Za-z0-9.:\[\]-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . self::url($path);
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

    // URL halaman store ibu bapa: store/index.php?school=slug&page=... atau /{slug}/{halaman} (config pretty_store_urls, B6).
    // Tanpa slug (belum pilih sekolah) → hanya halaman akaun / pilih sekolah
    public static function storeUrl($slug, $page = 'home', $params = [])
    {
        if ($slug !== '' && Config::get('pretty_store_urls')) {
            $url = self::url(rawurlencode($slug) . '/' . ($page !== 'home' ? rawurlencode($page) : ''));
            return $params ? $url . '?' . http_build_query($params) : $url;
        }
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

    // URL store penuh untuk e-mel (B7: pautan pesanan dalam resit ibu bapa)
    public static function absoluteStoreUrl($slug, $page = 'home', $params = [])
    {
        $relative = substr(self::storeUrl($slug, $page, $params), strlen(self::baseUrl()));
        return self::absoluteUrl($relative);
    }
}
