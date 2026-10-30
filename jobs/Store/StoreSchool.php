<?php
// Pembantu store ibu bapa (PELAN 8.5): slug → koperasi + vendor_id DISELESAIKAN DI PELAYAN.
// Tiada vendor_id / cooperative_id dari input — semua job store menerima baris koperasi dari sini.
class StoreSchool
{
    // Modul add-on yang mempengaruhi store (PELAN 18.13)
    const STORE_MODULES = ['news', 'clearance', 'calendar', 'school_opening'];

    // Vendor digantung / dibatalkan → store ditutup
    const OPEN_VENDOR_STATUSES = ['trial', 'active', 'past_due'];

    private static $cache = [];

    // Koperasi aktif untuk slug, atau null. Lajur yang dipulangkan: id, vendor_id, slug, school_name, …, template_id
    public static function findBySlug($slug)
    {
        $slug = (string) $slug;
        if (!preg_match('/^[a-z0-9-]{1,80}$/', $slug)) {
            return null;
        }
        if (!array_key_exists($slug, self::$cache)) {
            $marks = implode(',', array_fill(0, count(self::OPEN_VENDOR_STATUSES), '?'));
            self::$cache[$slug] = Database::fetchOne(
                "SELECT c.id, c.vendor_id, c.slug, c.school_name, c.school_type, c.state, c.district, c.address, c.phone, c.logo_path,
                        c.template_id, c.default_language, c.accept_cash, c.accept_online
                   FROM cooperatives c JOIN vendors v ON v.id = c.vendor_id
                  WHERE c.slug = ? AND c.status = 'active' AND v.status IN ($marks)",
                array_merge([$slug], self::OPEN_VENDOR_STATUSES)
            );
        }
        return self::$cache[$slug];
    }

    // Seperti findBySlug tetapi gagal dengan "Sekolah tidak dijumpai" (404), atau "Koperasi ditutup sementara" (503)
    // bila vendor digantung / dibatalkan atau koperasi digantung (PELAN 14 — data kekal)
    public static function require($slug)
    {
        $cooperative = self::findBySlug($slug);
        if ($cooperative === null && self::isClosed($slug)) {
            throw new ValidationException(t('store.school_closed'), [], 503, ['school_closed' => true]);
        }
        if ($cooperative === null) {
            throw new ValidationException(t('store.school_not_found'), [], 404);
        }
        // Apps Flutter (B15): hanya koperasi yang melanggan modul mobile_app
        if (ApiTokenAuth::hasBearer() && !self::appEnabled($cooperative)) {
            throw new ValidationException(t('store.app_unavailable'), [], 403, ['app_unavailable' => true]);
        }
        return $cooperative;
    }

    // Apps mudah alih tersedia untuk koperasi ini (modul mobile_app aktif)
    public static function appEnabled(array $cooperative)
    {
        return ModuleAccess::has($cooperative['vendor_id'], $cooperative['id'], 'mobile_app');
    }

    // Slug wujud tetapi store ditutup sementara (bukan koperasi yang sudah ditutup terus)
    public static function isClosed($slug)
    {
        $slug = (string) $slug;
        if (!preg_match('/^[a-z0-9-]{1,80}$/', $slug)) {
            return false;
        }
        $marks = implode(',', array_fill(0, count(self::OPEN_VENDOR_STATUSES), '?'));
        return (bool) Database::fetchValue(
            "SELECT 1 FROM cooperatives c JOIN vendors v ON v.id = c.vendor_id
              WHERE c.slug = ? AND (c.status = 'suspended' OR (c.status = 'active' AND v.status NOT IN ($marks)))",
            array_merge([$slug], self::OPEN_VENDOR_STATUSES)
        );
    }

    // Kod modul store yang aktif untuk koperasi ini (ModuleAccess — sama seperti panel vendor)
    public static function modules(array $cooperative)
    {
        $codes = ModuleAccess::codes($cooperative['vendor_id'], $cooperative['id']);
        return array_values(array_intersect($codes, self::STORE_MODULES));
    }

    public static function hasModule(array $cooperative, $code)
    {
        return in_array($code, self::modules($cooperative), true);
    }

    // Modul tidak aktif → store.module_unavailable
    public static function requireModule(array $cooperative, $code)
    {
        if (!self::hasModule($cooperative, $code)) {
            throw new ValidationException(t('store.module_unavailable'), [], 404);
        }
    }

    public static function grades(array $cooperative)
    {
        return (new ListCooperativeGradesJob($cooperative['school_type']))->run();
    }

    // Online hanya bila gateway koperasi aktif & dibenarkan (B8)
    public static function paymentMethods(array $cooperative)
    {
        $methods = [];
        if ((int) $cooperative['accept_cash'] === 1) {
            $methods[] = 'cash';
        }
        if ((int) $cooperative['accept_online'] === 1 && CooperativeGateways::active($cooperative['vendor_id'], $cooperative['id']) !== null) {
            $methods[] = 'online';
        }
        return $methods;
    }

    public static function publicShape(array $row)
    {
        return [
            'slug'        => $row['slug'],
            'school_name' => $row['school_name'],
            'school_type' => $row['school_type'],
            'state'       => $row['state'],
            'district'    => $row['district'],
        ];
    }
}
