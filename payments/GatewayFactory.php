<?php
// Kod gateway → kelas driver (payments/drivers/). Hanya kod dalam senarai ini boleh digunakan untuk bayaran online.
class GatewayFactory
{
    const DRIVERS = [
        'toyyibpay' => 'ToyyibPayGateway',
        'billplz'   => 'BillplzGateway',
        'senangpay' => 'SenangPayGateway',
        'ipay88'    => 'Ipay88Gateway',
        'stripe'    => 'StripeGateway',
    ];

    public static function supports($code)
    {
        return isset(self::DRIVERS[(string) $code]);
    }

    // $config = medan bukan rahsia + rahsia (sudah dinyahsulit); $mode = sandbox | live
    public static function make($code, array $config, $mode)
    {
        if (!self::supports($code)) {
            throw new GatewayException('Driver gateway tidak wujud: ' . $code);
        }
        $class = self::DRIVERS[$code];
        return new $class($config, $mode === 'live' ? 'live' : 'sandbox');
    }
}
