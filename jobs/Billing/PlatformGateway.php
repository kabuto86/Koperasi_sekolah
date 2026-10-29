<?php
// Gateway pemilik platform untuk kutip yuran langganan (PELAN 13, 14) — platform_settings owner_gateway_* (B3).
// Driver mesti dibenarkan untuk platform (payment_gateways.is_enabled_for_platform) DAN wujud (GatewayFactory). Rahsia dinyahsulit di pelayan sahaja.
class PlatformGateway
{
    // ['code', 'name', 'mode'] atau null jika belum ditetapkan / tidak dibenarkan / kunci rahsia kosong
    public static function settings()
    {
        $code = PlatformSettings::get('owner_gateway_code');
        if ($code === '' || !GatewayFactory::supports($code)) {
            return null;
        }
        $row = Database::fetchOne("SELECT code, name FROM payment_gateways WHERE code = ? AND is_enabled_for_platform = 1", [$code]);
        if ($row === null || count(ListPaymentGatewaysJob::ownerSecrets()) === 0) {
            return null;
        }
        return ['code' => $row['code'], 'name' => $row['name'], 'mode' => PlatformSettings::get('owner_gateway_mode', 'sandbox')];
    }

    // Driver gateway owner untuk kod tertentu (bil lama tetap boleh disahkan selagi kod sama dengan tetapan semasa)
    public static function driver($code)
    {
        $settings = self::settings();
        if ($settings === null || $settings['code'] !== $code) {
            return null;
        }
        $values = json_decode(PlatformSettings::get('owner_gateway_config', '{}'), true);
        $config = array_merge(is_array($values) ? $values : [], ListPaymentGatewaysJob::ownerSecrets());
        return GatewayFactory::make($code, $config, $settings['mode']);
    }

    public static function callbackUrl($code)
    {
        return View::absoluteUrl('api/v1/webhooks/platform.php') . '?gateway=' . rawurlencode($code);
    }
}
