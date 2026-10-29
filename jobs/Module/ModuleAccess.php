<?php
// Akses modul add-on per koperasi (PELAN 5.3): baris cooperative_modules `active` yang belum tamat (ends_at) + modul aktif katalog.
// Diletak dalam jobs/ (bukan core/) kerana ia membaca DB — sama seperti PlanLimit. Setiap endpoint modul (B13) memanggil require().
//
//   ModuleAccess::has($vendorId, $cooperativeId, 'news')       → bool
//   ModuleAccess::require($vendorId, $cooperativeId, 'news')   → 403 { module_locked: true } jika tidak dilanggan
class ModuleAccess
{
    // Kod modul aktif koperasi (ikut susunan katalog)
    public static function codes($vendorId, $cooperativeId)
    {
        $rows = Database::fetchAll(
            "SELECT m.code FROM cooperative_modules cm JOIN modules m ON m.id = cm.module_id
              WHERE cm.vendor_id = ? AND cm.cooperative_id = ? AND cm.status = 'active' AND m.is_active = 1
                AND (cm.ends_at IS NULL OR cm.ends_at >= CURDATE())
              ORDER BY m.sort_order",
            [(int) $vendorId, (int) $cooperativeId]
        );
        return array_column($rows, 'code');
    }

    public static function has($vendorId, $cooperativeId, $code)
    {
        return in_array((string) $code, self::codes($vendorId, $cooperativeId), true);
    }

    public static function require($vendorId, $cooperativeId, $code)
    {
        if (!self::has($vendorId, $cooperativeId, $code)) {
            throw new ValidationException(t('addon.module_locked'), [], 403, ['module_locked' => true]);
        }
    }
}
