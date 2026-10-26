<?php
// Pembantu jadual cooperative_settings (kunci → nilai teks). Setiap pertanyaan menapis vendor_id + cooperative_id.
//
//   CooperativeSettings::get($vendorId, $coopId, 'opening_hours');
//   CooperativeSettings::set($vendorId, $coopId, ['low_stock_default' => '5']);
class CooperativeSettings
{
    // Isi awal koperasi baharu (PELAN 9 C)
    const DEFAULTS = [
        'pickup_methods'             => 'A,B,C,D',
        'pickup_code_required_above' => '0',
        'cash_order_expiry_days'     => '3',
        'receipt_footer'             => '',
        'receipt_show_logo'          => '1',
        'receipt_paper'              => '80',
        'tax_rate'                   => '0',
        'low_stock_default'          => '5',
    ];

    public static function all($vendorId, $cooperativeId)
    {
        $rows = Database::fetchAll(
            "SELECT setting_key, setting_value FROM cooperative_settings WHERE vendor_id = ? AND cooperative_id = ?",
            [(int) $vendorId, (int) $cooperativeId]
        );
        return array_column($rows, 'setting_value', 'setting_key');
    }

    public static function get($vendorId, $cooperativeId, $key, $default = null)
    {
        $value = Database::fetchValue(
            "SELECT setting_value FROM cooperative_settings WHERE vendor_id = ? AND cooperative_id = ? AND setting_key = ?",
            [(int) $vendorId, (int) $cooperativeId, $key]
        );
        return $value === null ? $default : $value;
    }

    public static function set($vendorId, $cooperativeId, array $values)
    {
        foreach ($values as $key => $value) {
            Database::execute(
                "INSERT INTO cooperative_settings (vendor_id, cooperative_id, setting_key, setting_value) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [(int) $vendorId, (int) $cooperativeId, $key, (string) $value]
            );
        }
    }

    // Stok minimum lalai untuk baris stok baharu
    public static function lowStockDefault($vendorId, $cooperativeId)
    {
        return max(0, (int) self::get($vendorId, $cooperativeId, 'low_stock_default', '5'));
    }
}
