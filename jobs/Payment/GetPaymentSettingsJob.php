<?php
// Tetapan bayaran koperasi (PELAN 18.10 vendor/payments/settings): kaedah tunai/online, driver yang dibenarkan owner,
// gateway aktif (tanpa rahsia) dan URL panggilan balik.
class GetPaymentSettingsJob
{
    private $vendorId;
    private $cooperative;

    public function __construct($vendorId, array $cooperative)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
    }

    public function run()
    {
        $coopId = (int) $this->cooperative['id'];
        $stored = array_column(Database::fetchAll(
            "SELECT gateway_code FROM cooperative_gateways WHERE vendor_id = ? AND cooperative_id = ?",
            [$this->vendorId, $coopId]
        ), 'gateway_code');
        $drivers = [];
        foreach (Database::fetchAll("SELECT * FROM payment_gateways WHERE is_enabled_for_vendors = 1 ORDER BY name") as $row) {
            if (!GatewayFactory::supports($row['code'])) {
                continue;
            }
            $drivers[] = [
                'code'            => $row['code'],
                'name'            => $row['name'],
                'supports_refund' => (bool) $row['supports_refund'],
                'fields'          => ListPaymentGatewaysJob::fields($row['config_fields_json']),
                'configured'      => in_array($row['code'], $stored, true),
            ];
        }
        return [
            'accept_cash'   => (bool) $this->cooperative['accept_cash'],
            'accept_online' => (bool) $this->cooperative['accept_online'],
            'drivers'       => $drivers,
            'gateway'       => CooperativeGateways::publicShape(CooperativeGateways::active($this->vendorId, $coopId)),
            'callback_url'  => CooperativeGateways::callbackUrl('{gateway}', $coopId),
        ];
    }
}
