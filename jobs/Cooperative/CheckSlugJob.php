<?php
// Semak slug store: format + unik seluruh platform (PELAN 8.6). Tidak mendedahkan pemilik slug.
// $exceptId = koperasi vendor sesi yang sedang disunting (slugnya sendiri dikira tersedia).
class CheckSlugJob
{
    // Laluan sistem — tidak boleh menjadi alamat store (/{slug}/ dalam B6)
    const RESERVED = ['api', 'assets', 'admin', 'owner', 'vendor', 'store', 'templates', 'uploads', 'login', 'register',
        'cron', 'tools', 'core', 'config', 'jobs', 'lang', 'database', 'partials', 'payments', 'modules', 'webhooks', 'www'];

    private $vendorId;
    private $slug;
    private $exceptId;

    public function __construct($vendorId, $slug, $exceptId)
    {
        $this->vendorId = (int) $vendorId;
        $this->slug = strtolower(trim((string) $slug));
        $this->exceptId = (int) $exceptId;
    }

    // [slug, valid, available]
    public function run()
    {
        $valid = self::isValid($this->slug);
        $available = false;
        if ($valid && !in_array($this->slug, self::RESERVED, true)) {
            // Slug sendiri hanya dikecualikan jika koperasi itu memang milik vendor sesi
            $taken = Database::fetchValue(
                "SELECT 1 FROM cooperatives WHERE slug = ? AND NOT (id = ? AND vendor_id = ?)",
                [$this->slug, $this->exceptId, $this->vendorId]
            );
            $available = !$taken;
        }
        return ['slug' => $this->slug, 'valid' => $valid, 'available' => $available];
    }

    public static function isValid($slug)
    {
        $length = strlen($slug);
        return $length >= 3 && $length <= 60 && preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) === 1;
    }
}
