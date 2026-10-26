<?php
// Driver gateway & gateway owner untuk kutip yuran (PELAN 18.7 Gateways). Rahsia tidak pernah dipulangkan.
class ListPaymentGatewaysJob
{
    public function run()
    {
        $rows = Database::fetchAll(
            "SELECT g.*, (SELECT COUNT(DISTINCT cg.vendor_id) FROM cooperative_gateways cg
                           WHERE cg.gateway_code = g.code AND cg.is_active = 1) AS vendor_count
               FROM payment_gateways g ORDER BY g.is_enabled_for_vendors DESC, g.name"
        );
        $drivers = array_map(function ($row) {
            return [
                'code'                => $row['code'],
                'name'                => $row['name'],
                'allowed_for_vendors' => (bool) $row['is_enabled_for_vendors'],
                'supports_refund'     => (bool) $row['supports_refund'],
                'vendor_count'        => (int) $row['vendor_count'],
                'fields'              => self::fields($row['config_fields_json']),
            ];
        }, $rows);
        return ['drivers' => $drivers, 'owner_gateway' => self::ownerGateway()];
    }

    // [{ name, secret }]
    public static function fields($json)
    {
        $fields = json_decode((string) $json, true);
        $clean = [];
        foreach (is_array($fields) ? $fields : [] as $field) {
            $clean[] = ['name' => (string) $field['name'], 'secret' => !empty($field['secret'])];
        }
        return $clean;
    }

    public static function ownerGateway()
    {
        $values = json_decode(PlatformSettings::get('owner_gateway_config', '{}'), true);
        return [
            'driver_code' => PlatformSettings::get('owner_gateway_code'),
            'sandbox'     => PlatformSettings::get('owner_gateway_mode', 'sandbox') !== 'live',
            'values'      => is_array($values) ? (object) $values : (object) [],
            'secrets_set' => array_keys(self::ownerSecrets()),
        ];
    }

    // Rahsia gateway owner (nyahsulit) — untuk pelayan sahaja (B12)
    public static function ownerSecrets()
    {
        $encrypted = PlatformSettings::get('owner_gateway_secrets_enc');
        $secrets = $encrypted === '' ? null : json_decode((string) Crypto::decrypt($encrypted), true);
        return is_array($secrets) ? array_filter($secrets, 'strlen') : [];
    }
}
