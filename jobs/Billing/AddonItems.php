<?php
// Item kedai add-on untuk satu koperasi vendor sesi (PELAN 5.3, 18.10): modul (cooperative_modules) & template premium
// (subscription_items item_type template). Koperasi sudah disahkan oleh pemanggil (VendorAuth::requireCooperative).
class AddonItems
{
    // ['item_type', 'id', 'code', 'name', 'price', 'owned'] atau null (tiada / tidak aktif / template percuma)
    public static function resolve($vendorId, array $cooperative, $itemType, $code, $cycle)
    {
        $code = (string) $code;
        if ($itemType === 'module') {
            $module = Database::fetchOne("SELECT * FROM modules WHERE code = ? AND is_active = 1", [$code]);
            if ($module === null) {
                return null;
            }
            return [
                'item_type' => 'module',
                'id'        => (int) $module['id'],
                'code'      => $module['code'],
                'name'      => Invoices::moduleName($module['code']),
                'price'     => Subscriptions::cyclePrice($module, $cycle),
                'owned'     => ModuleAccess::has($vendorId, $cooperative['id'], $module['code']),
            ];
        }
        if ($itemType === 'template') {
            $template = Database::fetchOne("SELECT * FROM templates WHERE code = ? AND is_active = 1 AND is_free = 0", [$code]);
            if ($template === null) {
                return null;
            }
            return [
                'item_type' => 'template',
                'id'        => (int) $template['id'],
                'code'      => $template['code'],
                'name'      => $template['name'],
                'price'     => Subscriptions::cyclePrice($template, $cycle),
                'owned'     => self::templateSubscribed($vendorId, $cooperative['id'], $template['id']),
            ];
        }
        return null;
    }

    // Template premium dilanggan untuk koperasi ini (item terbuka)
    public static function templateSubscribed($vendorId, $cooperativeId, $templateId)
    {
        return (bool) Database::fetchValue(
            "SELECT 1 FROM subscription_items WHERE vendor_id = ? AND cooperative_id = ? AND item_type = 'template' AND ref_id = ?
                AND (ended_at IS NULL OR ended_at >= CURDATE()) LIMIT 1",
            [(int) $vendorId, (int) $cooperativeId, (int) $templateId]
        );
    }

    // Langganan semasa vendor, atau ralat (tiada langganan → tidak boleh membeli add-on)
    public static function requireSubscription($vendorId, $lock = false)
    {
        $subscription = Subscriptions::current($vendorId, $lock);
        if ($subscription === null) {
            throw new ValidationException(t('billing.no_subscription'), [], 404);
        }
        return $subscription;
    }
}
