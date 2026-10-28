<?php
// Pembantu gateway koperasi (cooperative_gateways — PELAN 13, 18.10). Medan bukan rahsia → config_json;
// rahsia → credentials_encrypted (Crypto, JSON). Rahsia TIDAK pernah dipulangkan kepada pelayar (publicShape → secrets_set).
class CooperativeGateways
{
    // Gateway aktif koperasi yang driver-nya dibenarkan owner DAN wujud (GatewayFactory), atau null
    public static function active($vendorId, $cooperativeId)
    {
        $row = Database::fetchOne(
            "SELECT cg.*, pg.name, pg.config_fields_json, pg.supports_refund FROM cooperative_gateways cg
               JOIN payment_gateways pg ON pg.code = cg.gateway_code
              WHERE cg.vendor_id = ? AND cg.cooperative_id = ? AND cg.is_active = 1 AND pg.is_enabled_for_vendors = 1
              ORDER BY cg.id LIMIT 1",
            [(int) $vendorId, (int) $cooperativeId]
        );
        return $row !== null && GatewayFactory::supports($row['gateway_code']) ? $row : null;
    }

    public static function find($vendorId, $cooperativeId, $gatewayCode)
    {
        return Database::fetchOne(
            "SELECT cg.*, pg.name, pg.config_fields_json, pg.supports_refund FROM cooperative_gateways cg
               JOIN payment_gateways pg ON pg.code = cg.gateway_code
              WHERE cg.vendor_id = ? AND cg.cooperative_id = ? AND cg.gateway_code = ?",
            [(int) $vendorId, (int) $cooperativeId, (string) $gatewayCode]
        );
    }

    // Panggilan balik: koperasi dari URL (?coop=), vendor dari baris koperasi (bukan dari input).
    // Aktif atau tidak — bil yang sudah dicipta mesti tetap boleh disahkan selepas vendor menukar gateway
    public static function forCallback($cooperativeId, $gatewayCode)
    {
        return Database::fetchOne(
            "SELECT cg.*, pg.name FROM cooperative_gateways cg
               JOIN cooperatives c ON c.id = cg.cooperative_id AND c.vendor_id = cg.vendor_id
               JOIN payment_gateways pg ON pg.code = cg.gateway_code
              WHERE cg.cooperative_id = ? AND cg.gateway_code = ?",
            [(int) $cooperativeId, (string) $gatewayCode]
        );
    }

    // Nilai bukan rahsia + rahsia yang dinyahsulit (pelayan sahaja)
    public static function config(array $row)
    {
        $values = json_decode((string) $row['config_json'], true);
        return array_merge(is_array($values) ? $values : [], self::secrets($row));
    }

    public static function secrets(array $row)
    {
        $plain = $row['credentials_encrypted'] === null ? null : Crypto::decrypt($row['credentials_encrypted']);
        $secrets = $plain === null ? null : json_decode($plain, true);
        return is_array($secrets) ? array_filter($secrets, 'strlen') : [];
    }

    public static function driver(array $row)
    {
        return GatewayFactory::make($row['gateway_code'], self::config($row), $row['mode']);
    }

    // GatewayPublic (PELAN 18.10)
    public static function publicShape($row)
    {
        if ($row === null) {
            return null;
        }
        $values = json_decode((string) $row['config_json'], true);
        return [
            'gateway_code' => $row['gateway_code'],
            'mode'         => $row['mode'],
            'is_active'    => (bool) $row['is_active'],
            'values'       => (object) (is_array($values) ? $values : []),
            'secrets_set'  => array_keys(self::secrets($row)),
        ];
    }

    // URL panggilan balik yang vendor masukkan dalam tetapan gateway (Billplz/ToyyibPay/iPay88 juga dihantar setiap bil)
    public static function callbackUrl($gatewayCode, $cooperativeId)
    {
        return View::absoluteUrl('api/v1/webhooks/' . $gatewayCode . '.php') . '?coop=' . (int) $cooperativeId;
    }
}
