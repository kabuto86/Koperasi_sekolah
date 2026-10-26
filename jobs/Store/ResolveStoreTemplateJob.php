<?php
// Template store untuk koperasi (PELAN 10.5): cooperatives.template_id → folder templates/{kod}.
// Template tidak aktif / tidak dilanggan vendor / folder tiada → jatuh balik 'template1'.
// $previewCode (dari owner / vendor pemilik sahaja — disemak oleh store/index.php) mengatasi pilihan koperasi jika folder wujud.
class ResolveStoreTemplateJob
{
    const FALLBACK = 'template1';

    private $cooperative;
    private $previewCode;

    // $cooperative = baris StoreSchool atau null (halaman tanpa sekolah)
    public function __construct($cooperative, $previewCode = '')
    {
        $this->cooperative = $cooperative;
        $this->previewCode = (string) $previewCode;
    }

    public function run()
    {
        if ($this->previewCode !== '' && self::folderReady($this->previewCode)) {
            return $this->previewCode;
        }
        if ($this->cooperative === null) {
            return self::FALLBACK;
        }
        $code = Database::fetchValue(
            "SELECT code FROM templates WHERE id = ? AND is_active = 1",
            [(int) $this->cooperative['template_id']]
        );
        if ($code === null || !ListTemplateOptionsJob::isUsable($this->cooperative['vendor_id'], $this->cooperative['template_id'])) {
            return self::FALLBACK;
        }
        return self::folderReady($code) ? $code : self::FALLBACK;
    }

    // Folder template lengkap: layout.php + manifest.json dengan semua halaman wajib
    public static function folderReady($code)
    {
        if (!preg_match('/^[a-z0-9_]{1,40}$/', (string) $code)) {
            return false;
        }
        $dir = APP_ROOT . '/templates/' . $code;
        if (!is_file($dir . '/layout.php') || !is_file($dir . '/manifest.json')) {
            return false;
        }
        $manifest = json_decode((string) file_get_contents($dir . '/manifest.json'), true);
        $required = json_decode((string) file_get_contents(APP_ROOT . '/templates/' . self::FALLBACK . '/manifest.json'), true)['pages'];
        return is_array($manifest) && is_array($manifest['pages'] ?? null) && !array_diff($required, $manifest['pages']);
    }
}
