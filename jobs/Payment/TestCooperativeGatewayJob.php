<?php
// Uji kunci API gateway aktif koperasi (vendor/payments/test-gateway) — panggilan sebenar ke API gateway.
class TestCooperativeGatewayJob
{
    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        $row = CooperativeGateways::active($this->vendorId, $this->cooperativeId);
        if ($row === null) {
            throw new ValidationException(t('payment.no_gateway'));
        }
        try {
            CooperativeGateways::driver($row)->testConnection();
        } catch (GatewayException $error) {
            error_log('[KoperasiKu] test gateway koperasi ' . $this->cooperativeId . ': ' . $error->getMessage());
            throw new ValidationException($error->getCode() === 1
                ? t('payment.test_unsupported', ['name' => $row['name']])
                : t('payment.test_failed', ['name' => $row['name']]));
        }
        return ['gateway_code' => $row['gateway_code'], 'mode' => $row['mode'], 'success' => true];
    }
}
